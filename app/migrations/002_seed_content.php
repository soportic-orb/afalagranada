<?php
declare(strict_types=1);

/** Contingut inicial (editable des del panell) */
return function (Database $db) {
    $settings = [
        'site_name' => 'AFA Escola Jacint Verdaguer',
        'site_tagline' => 'La Granada',
        'hero_title' => 'Famílies que fan escola',
        'hero_subtitle' => 'Som l\'Associació de Famílies d\'Alumnes de l\'Escola Jacint Verdaguer de La Granada. Treballem plegats perquè els nostres infants gaudeixin d\'una escola més rica, oberta i compartida.',
        'hero_cta_label' => 'Fes-te soci/a',
        'hero_cta_url' => '/fes-te-soci',
        'about_title' => 'Què és l\'AFA?',
        'about_text' => '<p>L\'<strong>AFA (Associació de Famílies d\'Alumnes)</strong> és l\'entitat que agrupa les mares, pares i tutors dels infants de l\'escola. És una associació sense ànim de lucre, formada per famílies voluntàries, que treballa colze a colze amb l\'equip docent, el Consell Escolar i l\'Ajuntament de La Granada.</p><p>El nostre objectiu és <strong>millorar la vida a l\'escola</strong>: gestionem serveis que faciliten la conciliació, organitzem activitats per als infants i impulsem projectes que fan créixer l\'escola entre tots.</p>',
        'member_title' => 'Per què fer-se soci/a?',
        'member_text' => 'Ser soci/a de l\'AFA vol dir formar part d\'una comunitat que fa possible moltes de les coses que passen a l\'escola. A més, té avantatges directes per a la teva família.',
        'member_fee' => '',
        'services_title' => 'Què fem des de l\'AFA',
        'services_text' => 'Gestionem serveis i activitats pensats per als infants i per facilitar el dia a dia de les famílies.',
        'app_name' => 'Abaco',
        'app_tagline' => 'Tota la gestió de l\'AFA, al teu mòbil',
        'app_intro' => '<p>Les famílies sòcies de l\'AFA utilitzem l\'app <strong>Abaco</strong> per a la gestió del dia a dia amb l\'associació. Des del mòbil pots donar-te d\'alta com a soci/a, pagar la quota, contractar serveis com l\'acollida o el menjador, inscriure els infants a les extraescolars i rebre totes les comunicacions de l\'AFA.</p>',
        'app_help' => '<ol><li>Descarrega l\'app des de l\'App Store o Google Play.</li><li>Registra\'t amb el teu correu electrònic i cerca l\'AFA de l\'Escola Jacint Verdaguer de La Granada.</li><li>Afegeix les dades dels teus fills i filles.</li><li>Ja pots fer-te soci/a, contractar serveis i inscriure\'ls a activitats.</li></ol><p>Tens dubtes? Escriu-nos i t\'ajudarem.</p>',
        'contact_address' => 'Escola Jacint Verdaguer, La Granada (Alt Penedès)',
        'mail_from' => 'no-reply@afalagranada.cat',
        'mail_from_name' => 'AFA La Granada',
        'privacy_url' => '/privacitat',
        'github_repo' => 'soportic-orb/afalagranada',
        'github_branch' => 'main',
        'smtp_port' => '587',
        'smtp_encryption' => 'tls',
    ];
    foreach ($settings as $k => $v) {
        $db->run('INSERT IGNORE INTO settings (`key`, `value`) VALUES (?, ?)', [$k, $v]);
    }

    $services = [
        ['Activitats extraescolars', 'ball', '<p>Organitzem l\'oferta d\'extraescolars de l\'escola: esports, música, idiomes, robòtica, arts... Activitats de qualitat a prop de casa i a preus ajustats.</p>'],
        ['Servei d\'acollida', 'clock', '<p>Acollida matinal i de tarda per facilitar la conciliació familiar, amb monitoratge qualificat i activitats lúdiques.</p>'],
        ['Servei de menjador', 'food', '<p>Fem el seguiment del servei de menjador escolar: menús equilibrats, monitoratge i activitats durant l\'espai del migdia.</p>'],
        ['Llibres i material', 'book', '<p>Coordinem la compra conjunta i la socialització de llibres i material escolar, amb estalvi per a les famílies i més sostenibilitat.</p>'],
        ['Activitats culturals i festes', 'palette', '<p>Castanyada, Nadal, Carnestoltes, Sant Jordi, festa de final de curs... Organitzem i donem suport a les celebracions i activitats culturals per als infants.</p>'],
        ['Projectes per a l\'escola', 'gift', '<p>Impulsem millores per a l\'escola (patis, material, tecnologia...) amb les quotes i amb campanyes de micromecenatge.</p>'],
    ];
    foreach ($services as $i => [$t, $ic, $b]) {
        $db->insert('blocks', ['group_key' => 'services', 'title' => $t, 'icon' => $ic, 'body' => $b, 'sort_order' => ($i + 1) * 10]);
    }

    $benefits = [
        ['Descomptes en activitats', 'tag', '<p>Preus reduïts en extraescolars, acollida i activitats organitzades per l\'AFA.</p>'],
        ['Veu i vot', 'megaphone', '<p>Participa a les assemblees i decideix en què s\'inverteixen els recursos de l\'AFA.</p>'],
        ['Activitats per als infants', 'star', '<p>Les quotes financen festes, tallers i activitats culturals per a tots els alumnes.</p>'],
        ['Comunitat', 'users', '<p>Coneix altres famílies, comparteix inquietuds i forma part de la vida de l\'escola.</p>'],
        ['Informació puntual', 'mail', '<p>Rep tots els avisos i novetats de l\'AFA a través de l\'app.</p>'],
        ['Millores a l\'escola', 'heart', '<p>Contribueixes a projectes que milloren els espais i recursos dels nostres fills i filles.</p>'],
    ];
    foreach ($benefits as $i => [$t, $ic, $b]) {
        $db->insert('blocks', ['group_key' => 'benefits', 'title' => $t, 'icon' => $ic, 'body' => $b, 'sort_order' => ($i + 1) * 10]);
    }

    $app = [
        ['Quota de soci/a', 'users', '<p>Alta a l\'AFA i pagament de la quota anual en pocs clics.</p>'],
        ['Pagament de serveis', 'tag', '<p>Contracta i paga l\'acollida, el menjador i altres serveis.</p>'],
        ['Inscripcions', 'calendar', '<p>Inscriu els infants a les extraescolars i activitats.</p>'],
        ['Comunicacions', 'megaphone', '<p>Rep avisos, circulars i notificacions de l\'AFA.</p>'],
    ];
    foreach ($app as $i => [$t, $ic, $b]) {
        $db->insert('blocks', ['group_key' => 'app', 'title' => $t, 'icon' => $ic, 'body' => $b, 'sort_order' => ($i + 1) * 10]);
    }

    $pages = [
        ['que-es-afa', 'L\'AFA', 'Qui som, com ens organitzem i com pots participar.', '<h2>Qui som</h2><p>L\'AFA de l\'Escola Jacint Verdaguer és una associació formada per les famílies de l\'alumnat. Ens organitzem en una <strong>Junta</strong> escollida en assemblea i en <strong>comissions</strong> de treball obertes a totes les famílies.</p><h2>Comissions</h2><ul><li><strong>Extraescolars</strong>: oferta i coordinació d\'activitats.</li><li><strong>Menjador i acollida</strong>: seguiment dels serveis.</li><li><strong>Festes i cultura</strong>: celebracions i activitats per als infants.</li><li><strong>Llibres i material</strong>: compra conjunta i socialització.</li><li><strong>Comunicació</strong>: web, xarxes i app.</li></ul><h2>Com participar</h2><p>Totes les famílies sòcies poden participar a les assemblees i sumar-se a qualsevol comissió. Escriu-nos!</p>', 1, 10],
        ['fes-te-soci', 'Fes-te soci/a', 'Uneix-te a l\'AFA i fes créixer l\'escola.', '<p>Fer-se soci/a de l\'AFA és molt fàcil: descarrega l\'app <strong>Abaco</strong>, registra\'t i dona d\'alta la teva família. La quota és anual i per família.</p><p><a href="/app">Descobreix com funciona l\'app →</a></p>', 1, 20],
        ['privacitat', 'Política de privacitat', '', '<p>Responsable del tractament: AFA Escola Jacint Verdaguer de La Granada.</p><p>Finalitat: gestionar les donacions a campanyes i les inscripcions a activitats, i enviar-ne les confirmacions i entrades. Els pagaments es processen a través de Stripe; l\'AFA no emmagatzema dades de targetes.</p><p>Legitimació: consentiment de la persona interessada i execució de la inscripció o donació.</p><p>Conservació: durant el temps necessari per a la gestió i les obligacions legals.</p><p>Drets: pots exercir els drets d\'accés, rectificació, supressió, oposició, limitació i portabilitat escrivint al correu de contacte de l\'AFA.</p>', 0, 90],
    ];
    foreach ($pages as [$slug, $title, $exc, $content, $menu, $order]) {
        $db->insert('pages', ['slug' => $slug, 'title' => $title, 'excerpt' => $exc ?: null, 'content' => $content, 'show_in_menu' => $menu, 'menu_order' => $order]);
    }
};
