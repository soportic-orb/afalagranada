<?php
declare(strict_types=1);

defined('BASE_PATH') || exit;

/**
 * La pàgina de l'app passa de /app a /aplicacio
 * (/app entrava en conflicte amb la carpeta interna app/ en servidors nginx).
 */
return function (Database $db) {
    foreach (['pages' => 'content', 'posts' => 'content', 'blocks' => 'body', 'campaigns' => 'description', 'activities' => 'description'] as $table => $col) {
        $db->run("UPDATE `$table` SET `$col` = REPLACE(REPLACE(`$col`, 'href=\"/app\"', 'href=\"/aplicacio\"'), 'href=\"/app#', 'href=\"/aplicacio#') WHERE `$col` LIKE '%href=\"/app%'");
    }
    $db->run("UPDATE blocks SET link_url = '/aplicacio' WHERE link_url IN ('/app', '/app/')");
    foreach (['hero_cta_url'] as $key) {
        $db->run("UPDATE settings SET `value` = '/aplicacio' WHERE `key` = ? AND `value` IN ('/app', '/app/')", [$key]);
    }
};
