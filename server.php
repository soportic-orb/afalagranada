<?php
// Només per a desenvolupament local:  php -S localhost:8000 server.php
if (PHP_SAPI !== 'cli-server') { http_response_code(404); exit; }
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('#^/(app|storage|vendor)/#', $path) || $path === '/config.php') { http_response_code(403); exit; }
if ($path !== '/' && is_file(__DIR__ . $path) && !str_ends_with($path, '.php')) { return false; }
if (str_starts_with($path, '/install')) { $_SERVER['SCRIPT_NAME'] = '/install/index.php'; require __DIR__ . '/install/index.php'; return true; }
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';
