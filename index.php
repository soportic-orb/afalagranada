<?php
declare(strict_types=1);

/**
 * AFA Escola Jacint Verdaguer — La Granada
 * Controlador frontal
 */

if (!is_file(__DIR__ . '/vendor/autoload.php')) {
    http_response_code(500);
    exit('Falta la carpeta vendor/. Puja tots els fitxers del projecte.');
}

if (!is_file(__DIR__ . '/config.php')) {
    header('Location: install/');
    exit;
}

define('AFA_ENTRY', true);
require __DIR__ . '/app/bootstrap.php';

$path = current_path();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (Updater::inMaintenance() && !str_starts_with($path, '/admin')) {
    http_response_code(503);
    header('Retry-After: 60');
    exit('<!doctype html><meta charset="utf-8"><title>Manteniment</title><body style="font-family:sans-serif;text-align:center;padding:4rem">'
        . '<h1>Estem actualitzant la web</h1><p>Torna-ho a provar d\'aquí a un minut.</p></body>');
}

$router = new Router();
require APP_PATH . '/routes/front.php';
require APP_PATH . '/routes/admin.php';
// Pàgines del CMS (ha d'anar al final)
$router->get('/{slug}', 'front_page');

try {
    $router->dispatch($method, $path);
} catch (Throwable $e) {
    error_log((string)$e);
    if (config('debug')) {
        throw $e;
    }
    abort(500, 'S\'ha produït un error inesperat.');
}
