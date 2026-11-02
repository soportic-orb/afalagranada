<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('STORAGE_PATH', BASE_PATH . '/storage');
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
