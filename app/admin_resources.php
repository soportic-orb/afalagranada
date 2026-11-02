<?php
declare(strict_types=1);

/**
 * Definició dels recursos editables des del panell d'administració.
 * Tipus de camp: text, textarea, html (editor visual), image, number, money, date, datetime,
 * checkbox, select, slug, email, password, url, icon, heading, info.
 */

$blockGroups = [
    'benefits' => 'Avantatges de ser soci/a',
    'services' => 'Què fa l\'AFA (serveis i activitats)',
    'app' => 'Funcionalitats de l\'app',
];

return [
    'pages' => [
        'table' => 'pages',
        'title' => 'Pàgines',
        'singular' => 'pàgina',
        'area' => 'content',
        'order' => 'menu_order, title',
        'view_url' => fn($r) => url($r['slug']),
        'columns' => [
            ['name' => 'title', 'label' => 'Títol'],
            ['name' => 'slug', 'label' => 'URL', 'render' => fn($r) => '<code>/' . e($r['slug']) . '</code>'],
            ['name' => 'show_in_menu', 'label' => 'Al menú', 'type' => 'bool'],
            ['name' => 'menu_order', 'label' => 'Ordre'],
            ['name' => 'published', 'label' => 'Publicada', 'type' => 'bool'],
        ],
        'fields' => [
            ['name' => 'title', 'label' => 'Títol', 'required' => true, 'maxlength' => 200],
            ['name' => 'slug', 'label' => 'URL (slug)', 'type' => 'slug', 'from' => 'title', 'help' => 'Es genera automàticament a partir del títol si es deixa en blanc.'],
            ['name' => 'excerpt', 'label' => 'Resum / descripció curta', 'type' => 'textarea', 'help' => 'Es mostra a la capçalera i als cercadors.'],
            ['name' => 'content', 'label' => 'Contingut', 'type' => 'html'],
            ['name' => 'image', 'label' => 'Imatge de capçalera', 'type' => 'image'],
            ['name' => 'show_in_menu', 'label' => 'Mostrar al menú principal', 'type' => 'checkbox', 'default' => 1, 'side' => true],
            ['name' => 'menu_order', 'label' => 'Ordre al menú', 'type' => 'number', 'default' => 10, 'side' => true],
            ['name' => 'published', 'label' => 'Publicada', 'type' => 'checkbox', 'default' => 1, 'side' => true],
        ],
    ],

    'blocks' => [
        'table' => 'blocks',
        'title' => 'Blocs de contingut',
        'singular' => 'bloc',
        'area' => 'content',
        'order' => 'group_key, sort_order, id',
        'filter' => ['column' => 'group_key', 'options' => $blockGroups],
        'intro' => 'Els blocs són les targetes que es mostren a la portada (avantatges de ser soci/a, serveis de l\'AFA) i a la pàgina de l\'app.',
        'columns' => [
            ['name' => 'icon', 'label' => '', 'render' => fn($r) => icon($r['icon'] ?: 'star', 'icon icon-sm')],
            ['name' => 'title', 'label' => 'Títol'],
            ['name' => 'group_key', 'label' => 'Secció', 'type' => 'select', 'options' => $blockGroups],
            ['name' => 'sort_order', 'label' => 'Ordre'],
            ['name' => 'published', 'label' => 'Visible', 'type' => 'bool'],
        ],
        'fields' => [
            ['name' => 'title', 'label' => 'Títol', 'required' => true, 'maxlength' => 200],
            ['name' => 'body', 'label' => 'Text', 'type' => 'html', 'small' => true],
            ['name' => 'link_url', 'label' => 'Enllaç (opcional)', 'type' => 'url', 'help' => 'Ex.: /activitats o https://...'],
            ['name' => 'image', 'label' => 'Imatge (opcional)', 'type' => 'image'],
            ['name' => 'group_key', 'label' => 'Secció', 'type' => 'select', 'options' => $blockGroups, 'side' => true],
            ['name' => 'icon', 'label' => 'Icona', 'type' => 'icon', 'default' => 'star', 'side' => true],
            ['name' => 'sort_order', 'label' => 'Ordre', 'type' => 'number', 'default' => 10, 'side' => true],
            ['name' => 'published', 'label' => 'Visible', 'type' => 'checkbox', 'default' => 1, 'side' => true],
        ],
    ],

    'posts' => [
        'table' => 'posts',
        'title' => 'Notícies',
        'singular' => 'notícia',
        'area' => 'content',
        'order' => 'published_at DESC',
        'view_url' => fn($r) => url('noticies/' . $r['slug']),
        'columns' => [
            ['name' => 'image', 'label' => '', 'type' => 'image'],
            ['name' => 'title', 'label' => 'Títol'],
            ['name' => 'published_at', 'label' => 'Data', 'type' => 'date'],
            ['name' => 'published', 'label' => 'Publicada', 'type' => 'bool'],
        ],
        'fields' => [
            ['name' => 'title', 'label' => 'Títol', 'required' => true, 'maxlength' => 200],
            ['name' => 'slug', 'label' => 'URL (slug)', 'type' => 'slug', 'from' => 'title'],
            ['name' => 'excerpt', 'label' => 'Resum', 'type' => 'textarea'],
            ['name' => 'content', 'label' => 'Contingut', 'type' => 'html'],
            ['name' => 'image', 'label' => 'Imatge destacada', 'type' => 'image'],
            ['name' => 'published_at', 'label' => 'Data de publicació', 'type' => 'datetime', 'default' => date('Y-m-d H:i:s'), 'side' => true],
            ['name' => 'published', 'label' => 'Publicada', 'type' => 'checkbox', 'default' => 1, 'side' => true],
        ],
    ],

    'campaigns' => [
        'table' => 'campaigns',
        'title' => 'Campanyes',
        'singular' => 'campanya',
        'area' => 'campaigns',
        'order' => 'created_at DESC',
        'view_url' => fn($r) => url('campanyes/' . $r['slug']),
        'columns' => [
            ['name' => 'image', 'label' => '', 'type' => 'image'],
            ['name' => 'title', 'label' => 'Campanya'],
            ['name' => 'goal_cents', 'label' => 'Fita', 'type' => 'money'],
            ['name' => 'raised', 'label' => 'Recaptat', 'render' => function ($r) {
                $s = campaign_stats($r);
                return e(money($s['raised'])) . ' <small class="muted">(' . $s['percent_real'] . '% · ' . $s['count'] . ' donacions)</small>';
            }],
            ['name' => 'ends_at', 'label' => 'Finalitza', 'type' => 'date'],
            ['name' => 'status', 'label' => 'Estat', 'type' => 'status'],
        ],
        'row_actions' => [
            ['label' => 'Donacions', 'url' => fn($r) => url('admin/campaigns/' . $r['id'] . '/donations')],
            ['label' => 'Galeria', 'url' => fn($r) => url('admin/campaigns/' . $r['id'] . '/gallery')],
        ],
        'fields' => [
            ['name' => 'title', 'label' => 'Títol de la campanya', 'required' => true, 'maxlength' => 200],
            ['name' => 'slug', 'label' => 'URL (slug)', 'type' => 'slug', 'from' => 'title'],
            ['name' => 'summary', 'label' => 'Resum breu', 'type' => 'textarea', 'help' => 'Es mostra al llistat de campanyes.'],
            ['name' => 'description', 'label' => 'Descripció completa', 'type' => 'html'],
            ['name' => 'image', 'label' => 'Imatge principal', 'type' => 'image'],
            ['name' => 'status', 'label' => 'Estat', 'type' => 'select', 'options' => ['draft' => 'Esborrany', 'active' => 'Activa (accepta donacions)', 'closed' => 'Tancada (visible, sense donacions)'], 'side' => true],
            ['name' => 'featured', 'label' => 'Destacada a la portada', 'type' => 'checkbox', 'side' => true],
            ['name' => 'goal_cents', 'label' => 'Fita de recaptació (€)', 'type' => 'money', 'required' => true, 'side' => true],
            ['name' => 'min_donation_cents', 'label' => 'Donació mínima (€)', 'type' => 'money', 'default' => 500, 'side' => true],
            ['name' => 'suggested_amounts', 'label' => 'Imports suggerits (€)', 'default' => '10,20,50,100', 'help' => 'Separats per comes', 'side' => true],
            ['name' => 'offline_cents', 'label' => 'Aportacions fora de la web (€)', 'type' => 'money', 'help' => 'Diners recaptats per altres vies (efectiu, transferència...) que sumen a la barra.', 'side' => true],
            ['name' => 'starts_at', 'label' => 'Data d\'inici', 'type' => 'date', 'side' => true],
            ['name' => 'ends_at', 'label' => 'Data de finalització', 'type' => 'date', 'side' => true],
        ],
    ],

    'activities' => [
        'table' => 'activities',
        'title' => 'Activitats',
        'singular' => 'activitat',
        'area' => 'activities',
        'order' => 'starts_at IS NULL, starts_at DESC',
        'view_url' => fn($r) => url('activitats/' . $r['slug']),
        'columns' => [
            ['name' => 'image', 'label' => '', 'type' => 'image'],
            ['name' => 'title', 'label' => 'Activitat'],
            ['name' => 'starts_at', 'label' => 'Data', 'type' => 'datetime'],
            ['name' => 'seats', 'label' => 'Inscrits', 'render' => function ($r) {
                $n = (int)db()->value("SELECT COALESCE(SUM(quantity),0) FROM registrations WHERE activity_id = ? AND status IN ('paid','free')", [$r['id']]);
                return $n . ($r['capacity'] ? ' / ' . (int)$r['capacity'] : '');
            }],
            ['name' => 'price_cents', 'label' => 'Preu', 'render' => fn($r) => $r['price_cents'] ? e(money((int)$r['price_cents'])) . ($r['member_price_cents'] !== null ? ' <small class="muted">(socis ' . e(money((int)$r['member_price_cents'])) . ')</small>' : '') : 'Gratuïta'],
            ['name' => 'status', 'label' => 'Estat', 'type' => 'status'],
        ],
        'row_actions' => [
            ['label' => 'Inscripcions', 'url' => fn($r) => url('admin/activities/' . $r['id'] . '/registrations')],
        ],
        'fields' => [
            ['name' => 'title', 'label' => 'Títol de l\'activitat', 'required' => true, 'maxlength' => 200],
            ['name' => 'slug', 'label' => 'URL (slug)', 'type' => 'slug', 'from' => 'title'],
            ['name' => 'summary', 'label' => 'Resum breu', 'type' => 'textarea'],
            ['name' => 'description', 'label' => 'Descripció completa', 'type' => 'html'],
            ['name' => 'image', 'label' => 'Imatge', 'type' => 'image'],
            ['name' => 'location', 'label' => 'Lloc', 'maxlength' => 200, 'default' => 'Escola Jacint Verdaguer, La Granada'],
            ['name' => 'status', 'label' => 'Estat', 'type' => 'select', 'options' => ['draft' => 'Esborrany', 'published' => 'Publicada (inscripcions obertes)', 'closed' => 'Tancada (visible, sense inscripcions)', 'cancelled' => 'Cancel·lada (oculta)'], 'side' => true],
            ['name' => 'starts_at', 'label' => 'Inici', 'type' => 'datetime', 'side' => true],
            ['name' => 'ends_at', 'label' => 'Final', 'type' => 'datetime', 'side' => true],
            ['name' => 'capacity', 'label' => 'Places totals', 'type' => 'number', 'help' => '0 = sense límit', 'side' => true],
            ['name' => 'max_per_registration', 'label' => 'Màx. participants per inscripció', 'type' => 'number', 'default' => 4, 'side' => true],
            ['name' => 'price_cents', 'label' => 'Preu per participant (€)', 'type' => 'money', 'help' => '0 = gratuïta', 'side' => true],
            ['name' => 'member_price_cents', 'label' => 'Preu socis/es AFA (€)', 'type' => 'money', 'nullable' => true, 'help' => 'Deixa en blanc si no hi ha preu especial', 'side' => true],
            ['name' => 'registration_opens', 'label' => 'Obertura inscripcions', 'type' => 'datetime', 'side' => true],
            ['name' => 'registration_closes', 'label' => 'Tancament inscripcions', 'type' => 'datetime', 'side' => true],
            ['name' => 'ask_course', 'label' => 'Demanar el curs de l\'infant', 'type' => 'checkbox', 'default' => 1, 'side' => true],
        ],
    ],

    'users' => [
        'table' => 'users',
        'title' => 'Usuaris',
        'singular' => 'usuari',
        'area' => 'users',
        'order' => 'name',
        'columns' => [
            ['name' => 'name', 'label' => 'Nom'],
            ['name' => 'email', 'label' => 'Correu'],
            ['name' => 'role', 'label' => 'Rol', 'type' => 'select', 'options' => Auth::ROLES],
            ['name' => 'last_login_at', 'label' => 'Últim accés', 'type' => 'datetime'],
            ['name' => 'active', 'label' => 'Actiu', 'type' => 'bool'],
        ],
        'fields' => [
            ['name' => 'name', 'label' => 'Nom', 'required' => true, 'maxlength' => 150],
            ['name' => 'email', 'label' => 'Correu electrònic', 'type' => 'email', 'required' => true],
            ['name' => 'password', 'label' => 'Contrasenya', 'type' => 'password', 'column' => 'password_hash', 'help' => 'Mínim 10 caràcters. Deixa-ho en blanc per no canviar-la.'],
            ['name' => 'role', 'label' => 'Rol', 'type' => 'select', 'options' => Auth::ROLES, 'side' => true,
                'help' => 'Editor/a: contingut, campanyes i activitats. Control d\'accés: només validar entrades.'],
            ['name' => 'active', 'label' => 'Actiu', 'type' => 'checkbox', 'default' => 1, 'side' => true],
        ],
        'validate' => function (array $d, ?array $existing) {
            $dup = db()->value('SELECT id FROM users WHERE email = ? AND id <> ?', [$d['email'], $existing['id'] ?? 0]);
            $err = $dup ? ['Ja existeix un usuari amb aquest correu.'] : [];
            if ($existing && (int)$existing['id'] === Auth::id() && ($d['role'] !== 'admin' || !$d['active'])) {
                $err[] = 'No et pots treure el rol d\'administrador ni desactivar el teu propi usuari.';
            }
            return $err;
        },
    ],
];
