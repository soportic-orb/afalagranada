<?php
// Exemple de configuració. L'instal·lador web (/install) genera config.php automàticament.
return [
    'app' => ['url' => 'https://afalagranada.cat', 'key' => 'canvia-aquesta-clau'],
    'db' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'afalagranada',
        'username' => 'usuari',
        'password' => 'contrasenya',
    ],
    'debug' => false,
    'updates' => ['repo' => 'soportic-orb/afalagranada', 'branch' => 'main'],
];
