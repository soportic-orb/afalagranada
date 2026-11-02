<?php
declare(strict_types=1);

defined('BASE_PATH') || exit;

/**
 * La web passa a mostrar el contingut amb el mateix model que l'editor visual:
 * paràgrafs sense marge i línies en blanc com a separació.
 * El contingut escrit sense cap línia en blanc (p. ex. el contingut inicial) rep una
 * línia en blanc entre blocs perquè els paràgrafs no quedin enganxats.
 * El contingut que ja té línies en blanc (escrit amb l'editor) no es toca.
 */
return function (Database $db) {
    $blank = '<p><br></p>';
    $fix = function (?string $html) use ($blank): ?string {
        if ($html === null || trim($html) === '' || str_contains($html, $blank)) {
            return $html;
        }
        return preg_replace('#(</(?:p|ul|ol|blockquote)>)\s*(?=<(?:p|ul|ol|blockquote)[\s>])#i', '$1' . $blank, $html);
    };
    $targets = [
        'pages' => 'content', 'posts' => 'content', 'campaigns' => 'description',
        'activities' => 'description', 'products' => 'description',
    ];
    foreach ($targets as $table => $col) {
        try {
            $rows = $db->all("SELECT id, `$col` AS html FROM `$table`");
        } catch (Throwable $e) {
            continue; // taula inexistent
        }
        foreach ($rows as $r) {
            $new = $fix($r['html']);
            if ($new !== $r['html']) {
                $db->run("UPDATE `$table` SET `$col` = ? WHERE id = ?", [$new, $r['id']]);
            }
        }
    }
    foreach (['about_text', 'app_intro', 'app_help', 'shop_terms'] as $key) {
        $val = $db->value('SELECT `value` FROM settings WHERE `key` = ?', [$key]);
        $new = $fix($val);
        if ($val !== null && $new !== $val) {
            $db->run('UPDATE settings SET `value` = ? WHERE `key` = ?', [$new, $key]);
        }
    }
};
