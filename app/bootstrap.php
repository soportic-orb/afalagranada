<?php
declare(strict_types=1);

// Només es pot carregar des dels punts d'entrada (index.php, install/) o des de la línia d'ordres
if (PHP_SAPI !== 'cli' && !defined('AFA_ENTRY')) {
    http_response_code(404);
    exit;
}

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('CONFIG_FILE', BASE_PATH . '/config.php');

mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Madrid');

require BASE_PATH . '/vendor/autoload.php';
require APP_PATH . '/lib/helpers.php';
require APP_PATH . '/lib/Database.php';
require APP_PATH . '/lib/Auth.php';
require APP_PATH . '/lib/Router.php';
require APP_PATH . '/lib/Migrator.php';
require APP_PATH . '/lib/Stripe.php';
require APP_PATH . '/lib/Mailer.php';
require APP_PATH . '/lib/Tickets.php';
require APP_PATH . '/lib/Uploader.php';
require APP_PATH . '/lib/Sanitizer.php';
require APP_PATH . '/lib/Updater.php';
require APP_PATH . '/lib/Crud.php';

$GLOBALS['config'] = is_file(CONFIG_FILE) ? require CONFIG_FILE : null;

/**
 * Carpeta privada de dades (logs, còpies de seguretat, temporals).
 * Molts servidors (nginx, Plesk) serveixen els fitxers estàtics sense llegir .htaccess,
 * per això, si no es configura una ruta fora de l'arrel web ('storage_path'),
 * s'utilitza una subcarpeta de storage/ amb un nom impossible d'endevinar.
 */
function afa_storage_path(?array $cfg): string
{
    $custom = $cfg['storage_path'] ?? '';
    if ($custom !== '' && (is_dir($custom) || @mkdir($custom, 0755, true)) && is_writable($custom)) {
        return rtrim($custom, '/');
    }
    $key = (string)($cfg['app']['key'] ?? '');
    if ($key === '') {
        return BASE_PATH . '/storage';
    }
    return BASE_PATH . '/storage/data-' . substr(hash('sha256', 'afa-storage|' . $key), 0, 32);
}

define('STORAGE_PATH', afa_storage_path($GLOBALS['config']));

if (!is_dir(STORAGE_PATH . '/logs')) {
    @mkdir(STORAGE_PATH, 0755, true);
    // Trasllada les dades de la ubicació antiga (storage/) a la nova
    $legacy = BASE_PATH . '/storage';
    if (STORAGE_PATH !== $legacy) {
        foreach (['logs', 'tmp', 'backups', 'installed.lock', 'maintenance.flag'] as $item) {
            if (file_exists($legacy . '/' . $item) && !file_exists(STORAGE_PATH . '/' . $item)) {
                @rename($legacy . '/' . $item, STORAGE_PATH . '/' . $item);
            }
        }
    }
    foreach (['logs', 'tmp', 'backups'] as $dir) {
        @mkdir(STORAGE_PATH . '/' . $dir, 0755, true);
    }
    @file_put_contents(STORAGE_PATH . '/.htaccess', "Require all denied\nDeny from all\n");
    @file_put_contents(STORAGE_PATH . '/index.html', '');
}

$debug = (bool)(config('debug') ?? false);
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
if (is_dir(STORAGE_PATH . '/logs')) {
    ini_set('error_log', STORAGE_PATH . '/logs/php-error.log');
}

if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('afasess');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
