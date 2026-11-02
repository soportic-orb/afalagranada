<?php
declare(strict_types=1);

defined('BASE_PATH') || exit;

/** Configuració inicial de la botiga */
return function (Database $db) {
    $settings = [
        'shop_enabled' => '0',
        'shop_title' => 'Botiga de l\'AFA',
        'shop_intro' => 'Samarretes, bosses i altres productes de l\'AFA. Tots els beneficis es destinen a projectes per als infants de l\'escola.',
        'shop_pickup_enabled' => '1',
        'shop_pickup_text' => 'Recollida a l\'escola (et direm quan la comanda estigui a punt)',
        'shop_shipping_enabled' => '0',
        'shop_shipping_cents' => '500',
        'shop_free_shipping_cents' => '',
        'shop_allow_cancel' => '1',
        'shop_terms' => '<p>Les comandes es preparen en un termini aproximat de 7 dies. Pots cancel·lar la comanda des de la pàgina de seguiment mentre no hagi entrat en preparació, i et retornarem l\'import íntegre. Per a canvis i devolucions, escriu-nos des de la pàgina de contacte en un termini de 14 dies des de la recepció.</p>',
    ];
    foreach ($settings as $k => $v) {
        $db->run('INSERT IGNORE INTO settings (`key`, `value`) VALUES (?, ?)', [$k, $v]);
    }
};
