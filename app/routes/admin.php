<?php
declare(strict_types=1);

/** @var Router $router */

function admin_view(string $template, array $data = []): void
{
    view('admin/' . $template, $data, 'admin/layout');
}

// ---------------------------------------------------------------- Accés
$router->any('/admin/login', function () {
    if (Auth::check()) {
        redirect('admin');
    }
    $error = null;
    if (is_post()) {
        verify_csrf();
        try {
            if (Auth::attempt((string)input('email'), (string)($_POST['password'] ?? ''))) {
                log_action('login');
                $to = $_SESSION['intended'] ?? null;
                unset($_SESSION['intended']);
                if ($to && str_starts_with($to, base_path() . '/admin')) {
                    header('Location: ' . $to);
                    exit;
                }
                redirect('admin');
            }
            $error = 'Correu o contrasenya incorrectes.';
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }
    }
    view('admin/login', ['title' => 'Accés', 'error' => $error]);
});

$router->post('/admin/logout', function () {
    verify_csrf();
    Auth::logout();
    redirect('admin/login');
});

// ---------------------------------------------------------------- Tauler
$router->get('/admin', function () {
    Auth::require('dashboard');
    if (Auth::role() === 'checkin') {
        redirect('admin/checkin');
    }
    $stats = [
        'donations_total' => (int)db()->value("SELECT COALESCE(SUM(amount_cents),0) FROM donations WHERE status = 'paid'"),
        'donations_count' => (int)db()->value("SELECT COUNT(*) FROM donations WHERE status = 'paid'"),
        'registrations_count' => (int)db()->value("SELECT COALESCE(SUM(quantity),0) FROM registrations WHERE status IN ('paid','free')"),
        'registrations_total' => (int)db()->value("SELECT COALESCE(SUM(amount_cents),0) FROM registrations WHERE status = 'paid'"),
        'active_campaigns' => (int)db()->value("SELECT COUNT(*) FROM campaigns WHERE status = 'active'"),
        'upcoming_activities' => (int)db()->value("SELECT COUNT(*) FROM activities WHERE status = 'published' AND (starts_at IS NULL OR starts_at >= NOW())"),
    ];
    $lastDonations = db()->all("SELECT d.*, c.title AS campaign FROM donations d JOIN campaigns c ON c.id = d.campaign_id WHERE d.status = 'paid' ORDER BY d.paid_at DESC LIMIT 6");
    $lastRegs = db()->all("SELECT r.*, a.title AS activity FROM registrations r JOIN activities a ON a.id = r.activity_id WHERE r.status IN ('paid','free') ORDER BY r.created_at DESC LIMIT 6");
    $warnings = [];
    if (!Stripe::enabled()) {
        $warnings[] = 'Stripe no està configurat: no es poden rebre pagaments. <a href="' . url('admin/settings') . '">Configura\'l</a>.';
    } elseif (!setting('stripe_webhook_secret')) {
        $warnings[] = 'Falta el secret del webhook de Stripe (recomanat per confirmar pagaments). <a href="' . url('admin/settings') . '">Configura\'l</a>.';
    }
    if (Migrator::pending(db())) {
        $warnings[] = 'Hi ha migracions de base de dades pendents. <a href="' . url('admin/updates') . '">Executa-les</a>.';
    }
    if (is_dir(BASE_PATH . '/install') && is_file(BASE_PATH . '/install/index.php') && !is_file(STORAGE_PATH . '/installed.lock')) {
        $warnings[] = 'La instal·lació no està bloquejada.';
    }
    admin_view('dashboard', ['title' => 'Tauler', 'stats' => $stats, 'lastDonations' => $lastDonations, 'lastRegs' => $lastRegs, 'warnings' => $warnings]);
});

// ---------------------------------------------------------------- Pujada d'imatges per a l'editor
$router->post('/admin/upload', function () {
    Auth::require('content');
    verify_csrf();
    try {
        $path = Uploader::image($_FILES['image'] ?? null);
        if (!$path) {
            json_response(['error' => 'Cap fitxer'], 400);
        }
        json_response(['url' => upload_url($path)]);
    } catch (RuntimeException $e) {
        json_response(['error' => $e->getMessage()], 400);
    }
});

// ---------------------------------------------------------------- CRUD genèric
$router->get('/admin/r/{res}', function (string $key) {
    $res = Crud::get($key);
    Auth::require($res['area']);
    $where = '';
    $params = [];
    $filter = null;
    if (isset($res['filter'])) {
        $filter = (string)input('f');
        if (isset($res['filter']['options'][$filter])) {
            $where = 'WHERE `' . $res['filter']['column'] . '` = ?';
            $params[] = $filter;
        }
    }
    $q = trim((string)input('q'));
    if ($q !== '') {
        $col = $key === 'users' ? 'name' : 'title';
        $where .= ($where ? ' AND ' : 'WHERE ') . "`$col` LIKE ?";
        $params[] = '%' . $q . '%';
    }
    $rows = db()->all("SELECT * FROM `{$res['table']}` $where ORDER BY {$res['order']} LIMIT 500", $params);
    admin_view('crud/index', ['title' => $res['title'], 'res' => $res, 'rows' => $rows, 'filter' => $filter, 'q' => $q]);
});

$router->any('/admin/r/{res}/new', function (string $key) {
    $res = Crud::get($key);
    Auth::require($res['area']);
    $row = Crud::defaults($res);
    if ($key === 'blocks' && isset($res['filter']['options'][(string)input('f')])) {
        $row['group_key'] = (string)input('f');
    }
    $errors = [];
    if (is_post()) {
        verify_csrf();
        [$data, $errors] = Crud::fromRequest($res, null);
        if (!$errors) {
            $id = db()->insert($res['table'], $data);
            log_action("create:$key", "#$id");
            flash('success', ucfirst($res['singular']) . ' creada correctament.');
            redirect("admin/r/$key/$id");
        }
        $row = array_merge($row, $data, array_intersect_key($_POST, $row));
    }
    admin_view('crud/form', ['title' => 'Nova ' . $res['singular'], 'res' => $res, 'row' => $row, 'errors' => $errors, 'isNew' => true]);
});

$router->any('/admin/r/{res}/{id}', function (string $key, string $id) {
    $res = Crud::get($key);
    Auth::require($res['area']);
    $row = Crud::find($res, (int)$id);
    $errors = [];
    if (is_post()) {
        verify_csrf();
        [$data, $errors] = Crud::fromRequest($res, $row);
        if (!$errors) {
            db()->update($res['table'], $data, (int)$id);
            log_action("update:$key", "#$id");
            flash('success', 'Canvis desats.');
            redirect("admin/r/$key/$id");
        }
        $row = array_merge($row, $data);
    }
    admin_view('crud/form', ['title' => 'Editar ' . $res['singular'], 'res' => $res, 'row' => $row, 'errors' => $errors, 'isNew' => false]);
});

$router->post('/admin/r/{res}/{id}/delete', function (string $key, string $id) {
    $res = Crud::get($key);
    Auth::require($res['area']);
    verify_csrf();
    $row = Crud::find($res, (int)$id);
    if ($key === 'users' && (int)$id === Auth::id()) {
        flash('error', 'No pots eliminar el teu propi usuari.');
        redirect("admin/r/$key");
    }
    if ($key === 'campaigns' && db()->value("SELECT COUNT(*) FROM donations WHERE campaign_id = ? AND status = 'paid'", [$id])) {
        flash('error', 'Aquesta campanya té donacions pagades. Tanca-la en lloc d\'eliminar-la.');
        redirect("admin/r/$key/$id");
    }
    if ($key === 'activities' && db()->value("SELECT COUNT(*) FROM registrations WHERE activity_id = ? AND status = 'paid'", [$id])) {
        flash('error', 'Aquesta activitat té inscripcions pagades. Cancel·la-la o tanca-la en lloc d\'eliminar-la.');
        redirect("admin/r/$key/$id");
    }
    foreach ($res['fields'] as $f) {
        if (($f['type'] ?? '') === 'image') {
            Uploader::delete($row[$f['name']] ?? null);
        }
    }
    if ($key === 'campaigns') {
        foreach (db()->all('SELECT path FROM campaign_images WHERE campaign_id = ?', [$id]) as $img) {
            Uploader::delete($img['path']);
        }
    }
    db()->delete($res['table'], (int)$id);
    log_action("delete:$key", "#$id");
    flash('success', 'Element eliminat.');
    redirect("admin/r/$key");
});

// ---------------------------------------------------------------- Campanyes: galeria i donacions
$router->any('/admin/campaigns/{id}/gallery', function (string $id) {
    Auth::require('campaigns');
    $c = db()->one('SELECT * FROM campaigns WHERE id = ?', [$id]) ?? abort(404);
    if (is_post()) {
        verify_csrf();
        $action = input('action');
        if ($action === 'upload') {
            try {
                $max = (int)db()->value('SELECT COALESCE(MAX(sort_order),0) FROM campaign_images WHERE campaign_id = ?', [$id]);
                foreach (Uploader::images($_FILES['images'] ?? null) as $p) {
                    db()->insert('campaign_images', ['campaign_id' => (int)$id, 'path' => $p, 'sort_order' => ++$max]);
                }
                flash('success', 'Imatges afegides.');
            } catch (RuntimeException $e) {
                flash('error', $e->getMessage());
            }
        } elseif ($action === 'save') {
            foreach ((array)($_POST['caption'] ?? []) as $imgId => $cap) {
                db()->run('UPDATE campaign_images SET caption = ?, sort_order = ? WHERE id = ? AND campaign_id = ?', [
                    mb_substr(trim((string)$cap), 0, 255) ?: null, (int)($_POST['sort'][$imgId] ?? 0), (int)$imgId, (int)$id,
                ]);
            }
            flash('success', 'Canvis desats.');
        } elseif ($action === 'delete') {
            $img = db()->one('SELECT * FROM campaign_images WHERE id = ? AND campaign_id = ?', [(int)input('image_id'), (int)$id]);
            if ($img) {
                Uploader::delete($img['path']);
                db()->delete('campaign_images', (int)$img['id']);
                flash('success', 'Imatge eliminada.');
            }
        }
        redirect("admin/campaigns/$id/gallery");
    }
    $images = db()->all('SELECT * FROM campaign_images WHERE campaign_id = ? ORDER BY sort_order, id', [$id]);
    admin_view('campaign_gallery', ['title' => 'Galeria: ' . $c['title'], 'c' => $c, 'images' => $images]);
});

$router->any('/admin/campaigns/{id}/donations', function (string $id) {
    Auth::require('campaigns');
    $c = db()->one('SELECT * FROM campaigns WHERE id = ?', [$id]) ?? abort(404);
    if (is_post()) {
        verify_csrf();
        $action = input('action');
        if ($action === 'manual') {
            $amount = to_cents(input('amount'));
            if ($amount > 0) {
                db()->insert('donations', [
                    'campaign_id' => (int)$id, 'amount_cents' => $amount,
                    'donor_name' => input('name') ?: null, 'donor_email' => input('email') ?: null,
                    'message' => input('message') ?: null, 'anonymous' => input('anonymous') ? 1 : 0,
                    'status' => 'paid', 'method' => input('method') === 'transfer' ? 'transfer' : 'cash', 'paid_at' => date('Y-m-d H:i:s'),
                ]);
                log_action('donation:manual', "campaign #$id " . $amount);
                flash('success', 'Donació registrada.');
            }
        } elseif ($action === 'status') {
            $st = input('status');
            if (in_array($st, ['paid', 'refunded', 'failed'], true)) {
                db()->run('UPDATE donations SET status = ?, paid_at = COALESCE(paid_at, IF(? = \'paid\', NOW(), NULL)) WHERE id = ? AND campaign_id = ?', [$st, $st, (int)input('donation_id'), (int)$id]);
                flash('success', 'Estat actualitzat.');
            }
        } elseif ($action === 'resend') {
            Mailer::donationThanks((int)input('donation_id'));
            flash('success', 'Correu d\'agraïment reenviat.');
        }
        redirect("admin/campaigns/$id/donations");
    }
    $status = (string)input('status', 'paid');
    $sql = 'SELECT * FROM donations WHERE campaign_id = ?' . ($status !== 'all' ? ' AND status = ?' : '') . ' ORDER BY COALESCE(paid_at, created_at) DESC';
    $rows = db()->all($sql, $status !== 'all' ? [$id, $status] : [$id]);
    if (input('export') === 'csv') {
        csv_download('donacions-' . $c['slug'] . '.csv',
            ['ID', 'Data', 'Import (€)', 'Nom', 'Correu', 'Anònim', 'Missatge', 'Estat', 'Mètode', 'Stripe PI'],
            array_map(fn($d) => [$d['id'], $d['paid_at'] ?: $d['created_at'], number_format($d['amount_cents'] / 100, 2, ',', ''), $d['donor_name'], $d['donor_email'], $d['anonymous'] ? 'Sí' : 'No', $d['message'], status_label($d['status']), $d['method'], $d['stripe_payment_intent']], $rows));
    }
    admin_view('donations', ['title' => 'Donacions: ' . $c['title'], 'c' => $c, 'rows' => $rows, 'status' => $status, 'stats' => campaign_stats($c)]);
});

function csv_download(string $filename, array $header, array $rows): never
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM per a Excel
    fputcsv($out, $header, ';');
    foreach ($rows as $r) {
        fputcsv($out, array_map(fn($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v, $r), ';');
    }
    fclose($out);
    exit;
}

// ---------------------------------------------------------------- Activitats: inscripcions
$router->any('/admin/activities/{id}/registrations', function (string $id) {
    Auth::require('activities');
    $a = db()->one('SELECT * FROM activities WHERE id = ?', [$id]) ?? abort(404);
    if (is_post()) {
        verify_csrf();
        $action = input('action');
        if ($action === 'manual') {
            $names = array_values(array_filter(array_map('trim', explode("\n", (string)input('attendees')))));
            $email = filter_var(input('email'), FILTER_VALIDATE_EMAIL);
            if ($names && $email && input('name') !== '') {
                $amount = to_cents(input('amount'));
                $regId = db()->transaction(function (Database $db) use ($a, $names, $email, $amount) {
                    $rid = $db->insert('registrations', [
                        'activity_id' => $a['id'], 'code' => random_code(8), 'name' => input('name'), 'email' => mb_strtolower((string)$email),
                        'phone' => input('phone') ?: null, 'is_member' => input('is_member') ? 1 : 0, 'quantity' => count($names),
                        'amount_cents' => $amount, 'status' => $amount > 0 ? 'paid' : 'free', 'paid_at' => $amount > 0 ? date('Y-m-d H:i:s') : null,
                        'notes' => 'Inscripció manual' . (input('notes') ? ': ' . input('notes') : ''),
                    ]);
                    foreach ($names as $n) {
                        $parts = array_map('trim', explode('|', $n, 2));
                        $db->insert('tickets', ['registration_id' => $rid, 'code' => random_code(8), 'attendee_name' => mb_substr($parts[0], 0, 150), 'attendee_course' => $parts[1] ?? null]);
                    }
                    return $rid;
                });
                if (input('send')) {
                    Tickets::send($regId);
                }
                log_action('registration:manual', "activity #$id reg #$regId");
                flash('success', 'Inscripció afegida.');
            } else {
                flash('error', 'Cal nom, correu vàlid i com a mínim un participant.');
            }
        } else {
            $reg = db()->one('SELECT * FROM registrations WHERE id = ? AND activity_id = ?', [(int)input('registration_id'), (int)$id]);
            if ($reg) {
                if ($action === 'resend') {
                    Tickets::send((int)$reg['id']) ? flash('success', 'Entrades reenviades a ' . $reg['email'] . '.') : flash('error', 'No s\'han pogut enviar (revisa l\'estat i la configuració de correu).');
                } elseif ($action === 'mark_paid') {
                    db()->run("UPDATE registrations SET status = 'paid', paid_at = NOW() WHERE id = ?", [$reg['id']]);
                    Tickets::send((int)$reg['id']);
                    flash('success', 'Marcada com a pagada i entrades enviades.');
                } elseif ($action === 'cancel') {
                    db()->run("UPDATE registrations SET status = 'cancelled' WHERE id = ?", [$reg['id']]);
                    flash('success', 'Inscripció cancel·lada. Si cal, fes el retorn des del panell de Stripe.');
                }
                log_action("registration:$action", "#{$reg['id']}");
            }
        }
        redirect("admin/activities/$id/registrations");
    }
    $status = (string)input('status', 'valid');
    $cond = match ($status) {
        'valid' => " AND r.status IN ('paid','free')",
        'all' => '',
        default => ' AND r.status = ' . db()->pdo->quote($status),
    };
    $regs = db()->all("SELECT r.* FROM registrations r WHERE r.activity_id = ? $cond ORDER BY r.created_at DESC", [$id]);
    $tickets = [];
    if ($regs) {
        $ids = implode(',', array_map(fn($r) => (int)$r['id'], $regs));
        foreach (db()->all("SELECT * FROM tickets WHERE registration_id IN ($ids) ORDER BY id") as $t) {
            $tickets[$t['registration_id']][] = $t;
        }
    }
    if (input('export') === 'csv') {
        $rows = [];
        foreach ($regs as $r) {
            foreach ($tickets[$r['id']] ?? [] as $t) {
                $rows[] = [$r['code'], $r['created_at'], $t['attendee_name'], $t['attendee_course'], $r['name'], $r['email'], $r['phone'], $r['is_member'] ? 'Sí' : 'No', number_format($r['amount_cents'] / 100, 2, ',', ''), status_label($r['status']), $t['code'], $t['checked_in_at'], $r['notes']];
            }
        }
        csv_download('inscripcions-' . $a['slug'] . '.csv', ['Inscripció', 'Data', 'Participant', 'Curs', 'Responsable', 'Correu', 'Telèfon', 'Soci/a', 'Import (€)', 'Estat', 'Entrada', 'Validada', 'Observacions'], $rows);
    }
    $summary = db()->one("SELECT COALESCE(SUM(CASE WHEN status IN ('paid','free') THEN quantity END),0) AS seats,
        COALESCE(SUM(CASE WHEN status = 'paid' THEN amount_cents END),0) AS revenue,
        COUNT(CASE WHEN status IN ('paid','free') THEN 1 END) AS regs FROM registrations WHERE activity_id = ?", [$id]);
    $checked = (int)db()->value("SELECT COUNT(*) FROM tickets t JOIN registrations r ON r.id = t.registration_id WHERE r.activity_id = ? AND t.checked_in_at IS NOT NULL AND r.status IN ('paid','free')", [$id]);
    admin_view('registrations', ['title' => 'Inscripcions: ' . $a['title'], 'a' => $a, 'regs' => $regs, 'tickets' => $tickets, 'status' => $status, 'summary' => $summary, 'checked' => $checked]);
});

// ---------------------------------------------------------------- Control d'accés (QR)
$router->get('/admin/checkin', function () {
    Auth::require('checkin');
    $activities = db()->all("SELECT id, title, starts_at FROM activities WHERE status IN ('published','closed') ORDER BY ABS(TIMESTAMPDIFF(HOUR, NOW(), COALESCE(starts_at, NOW())))  LIMIT 20");
    admin_view('checkin', ['title' => 'Control d\'accés', 'activities' => $activities]);
});

$router->post('/admin/checkin/validate', function () {
    Auth::require('checkin');
    verify_csrf();
    $code = (string)input('code');
    if (preg_match('#/entrada/([A-F0-9]+)#i', $code, $m)) {
        $code = $m[1];
    }
    $code = strtoupper(preg_replace('/[^A-Fa-f0-9]/', '', $code));
    $t = db()->one('SELECT t.*, r.status AS reg_status, r.name AS buyer, r.activity_id, a.title AS activity
        FROM tickets t JOIN registrations r ON r.id = t.registration_id JOIN activities a ON a.id = r.activity_id WHERE t.code = ?', [$code]);
    $activityId = (int)input('activity_id');
    if (!$t) {
        json_response(['ok' => false, 'level' => 'bad', 'message' => 'Entrada no trobada.']);
    }
    $info = ['attendee' => $t['attendee_name'], 'course' => $t['attendee_course'], 'activity' => $t['activity'], 'buyer' => $t['buyer']];
    if ($activityId && $activityId !== (int)$t['activity_id']) {
        json_response(['ok' => false, 'level' => 'bad', 'message' => 'Aquesta entrada és d\'una altra activitat.'] + $info);
    }
    if (!in_array($t['reg_status'], ['paid', 'free'], true)) {
        json_response(['ok' => false, 'level' => 'bad', 'message' => 'Inscripció no vàlida (' . status_label($t['reg_status']) . ').'] + $info);
    }
    if ($t['checked_in_at']) {
        json_response(['ok' => false, 'level' => 'warn', 'message' => 'Entrada ja validada el ' . date('d/m/Y H:i', strtotime($t['checked_in_at'])) . '.'] + $info);
    }
    if (input('undo')) {
        json_response(['ok' => false, 'level' => 'warn', 'message' => 'Res a desfer.'] + $info);
    }
    db()->run('UPDATE tickets SET checked_in_at = NOW() WHERE id = ?', [$t['id']]);
    json_response(['ok' => true, 'level' => 'ok', 'message' => 'Entrada vàlida. Benvingut/da!'] + $info);
});

$router->post('/admin/checkin/undo', function () {
    Auth::require('checkin');
    verify_csrf();
    db()->run('UPDATE tickets SET checked_in_at = NULL WHERE code = ?', [strtoupper((string)input('code'))]);
    if (input('back')) {
        redirect('entrada/' . strtoupper((string)input('code')));
    }
    json_response(['ok' => true]);
});

// ---------------------------------------------------------------- Portada i app (textos)
$settingsForms = [
    'home' => [
        'title' => 'Portada i textos generals',
        'area' => 'content',
        'fields' => [
            ['name' => 'hero_title', 'label' => 'Títol principal (portada)', 'default' => 'Famílies que fan escola'],
            ['name' => 'hero_subtitle', 'label' => 'Subtítol (portada)', 'type' => 'textarea'],
            ['name' => 'hero_image', 'label' => 'Imatge de la portada', 'type' => 'image'],
            ['name' => 'hero_cta_label', 'label' => 'Text del botó principal', 'default' => 'Fes-te soci/a'],
            ['name' => 'hero_cta_url', 'label' => 'Enllaç del botó principal', 'default' => '/fes-te-soci'],
            ['name' => 'about_title', 'label' => 'Títol de «Què és l\'AFA»', 'default' => 'Què és l\'AFA?'],
            ['name' => 'about_text', 'label' => 'Text de «Què és l\'AFA»', 'type' => 'html'],
            ['name' => 'about_image', 'label' => 'Imatge de «Què és l\'AFA»', 'type' => 'image'],
            ['name' => 'member_title', 'label' => 'Títol secció avantatges', 'default' => 'Per què fer-se soci/a?'],
            ['name' => 'member_text', 'label' => 'Text introductori avantatges', 'type' => 'textarea'],
            ['name' => 'member_fee', 'label' => 'Quota anual (text visible)', 'help' => 'Ex.: 30 € per família i curs'],
            ['name' => 'services_title', 'label' => 'Títol secció serveis', 'default' => 'Què fem des de l\'AFA'],
            ['name' => 'services_text', 'label' => 'Text introductori serveis', 'type' => 'textarea'],
        ],
    ],
    'app' => [
        'title' => 'Pàgina de l\'app (Abaco)',
        'area' => 'content',
        'fields' => [
            ['name' => 'app_name', 'label' => 'Nom de l\'app', 'default' => 'Abaco'],
            ['name' => 'app_tagline', 'label' => 'Frase destacada', 'default' => 'Tota la gestió de l\'AFA, al teu mòbil'],
            ['name' => 'app_intro', 'label' => 'Descripció', 'type' => 'html'],
            ['name' => 'app_image', 'label' => 'Imatge / captura de l\'app', 'type' => 'image'],
            ['name' => 'app_ios_url', 'label' => 'Enllaç App Store', 'type' => 'url'],
            ['name' => 'app_android_url', 'label' => 'Enllaç Google Play', 'type' => 'url'],
            ['name' => 'app_web_url', 'label' => 'Enllaç versió web (opcional)', 'type' => 'url'],
            ['name' => 'app_help', 'label' => 'Com començar / preguntes freqüents', 'type' => 'html'],
        ],
    ],
    'settings' => [
        'title' => 'Configuració',
        'area' => 'settings',
        'fields' => [
            ['type' => 'heading', 'label' => 'Dades de l\'AFA'],
            ['name' => 'site_name', 'label' => 'Nom del lloc', 'default' => 'AFA Escola Jacint Verdaguer'],
            ['name' => 'site_tagline', 'label' => 'Subtítol', 'default' => 'La Granada'],
            ['name' => 'logo', 'label' => 'Logotip', 'type' => 'image'],
            ['name' => 'contact_email', 'label' => 'Correu de contacte', 'type' => 'email'],
            ['name' => 'contact_phone', 'label' => 'Telèfon'],
            ['name' => 'contact_address', 'label' => 'Adreça', 'default' => 'Escola Jacint Verdaguer, La Granada (Alt Penedès)'],
            ['name' => 'legal_name', 'label' => 'Raó social', 'help' => 'Per a avisos legals i rebuts'],
            ['name' => 'legal_nif', 'label' => 'NIF'],
            ['name' => 'social_instagram', 'label' => 'Instagram (URL)', 'type' => 'url'],
            ['name' => 'social_facebook', 'label' => 'Facebook (URL)', 'type' => 'url'],
            ['name' => 'privacy_url', 'label' => 'URL política de privacitat', 'default' => '/privacitat'],
            ['type' => 'heading', 'label' => 'Stripe (pagaments)'],
            ['type' => 'info', 'label' => 'Crea un compte a stripe.com i copia les claus des de Developers → API keys. Configura un webhook cap a <code>' . e(abs_url('stripe/webhook')) . '</code> amb els esdeveniments <code>checkout.session.completed</code>, <code>checkout.session.expired</code>, <code>checkout.session.async_payment_succeeded</code>, <code>checkout.session.async_payment_failed</code> i <code>charge.refunded</code>.'],
            ['name' => 'stripe_publishable_key', 'label' => 'Clau publicable (pk_...)'],
            ['name' => 'stripe_secret_key', 'label' => 'Clau secreta (sk_...)', 'type' => 'secret'],
            ['name' => 'stripe_webhook_secret', 'label' => 'Secret del webhook (whsec_...)', 'type' => 'secret'],
            ['type' => 'heading', 'label' => 'Correu electrònic'],
            ['type' => 'info', 'label' => 'Si no s\'indica servidor SMTP s\'utilitzarà la funció mail() del servidor. Es recomana SMTP (el compte de correu del hosting).'],
            ['name' => 'mail_from', 'label' => 'Remitent (correu)', 'type' => 'email', 'default' => 'no-reply@afalagranada.cat'],
            ['name' => 'mail_from_name', 'label' => 'Remitent (nom)', 'default' => 'AFA La Granada'],
            ['name' => 'notify_email', 'label' => 'Avisar de noves donacions i inscripcions a', 'type' => 'email'],
            ['name' => 'smtp_host', 'label' => 'Servidor SMTP'],
            ['name' => 'smtp_port', 'label' => 'Port SMTP', 'default' => '587'],
            ['name' => 'smtp_encryption', 'label' => 'Xifratge', 'type' => 'select', 'options' => ['tls' => 'STARTTLS (587)', 'ssl' => 'SSL/TLS (465)', 'none' => 'Cap']],
            ['name' => 'smtp_user', 'label' => 'Usuari SMTP'],
            ['name' => 'smtp_password', 'label' => 'Contrasenya SMTP', 'type' => 'secret'],
            ['type' => 'heading', 'label' => 'Actualitzacions (GitHub)'],
            ['name' => 'github_repo', 'label' => 'Repositori', 'default' => 'soportic-orb/afalagranada'],
            ['name' => 'github_branch', 'label' => 'Branca', 'default' => 'main'],
            ['name' => 'github_token', 'label' => 'Token d\'accés (només si el repositori és privat)', 'type' => 'secret', 'help' => 'Fine-grained token amb permís de lectura de «Contents».'],
            ['type' => 'heading', 'label' => 'Altres'],
            ['name' => 'analytics_code', 'label' => 'Codi d\'analítica (opcional)', 'type' => 'textarea', 'help' => 'S\'insereix al final de cada pàgina pública (p. ex. Plausible, Matomo).'],
        ],
    ],
];

foreach (['home' => '/admin/home', 'app' => '/admin/app', 'settings' => '/admin/settings'] as $formKey => $route) {
    $router->any($route, function () use ($formKey, $settingsForms, $route) {
        $form = $settingsForms[$formKey];
        Auth::require($form['area']);
        if (is_post()) {
            verify_csrf();
            foreach ($form['fields'] as $f) {
                if (empty($f['name'])) {
                    continue;
                }
                $name = $f['name'];
                $type = $f['type'] ?? 'text';
                if ($type === 'image') {
                    $cur = (string)setting($name, '');
                    if (!empty($_POST[$name . '__remove'])) {
                        Uploader::delete($cur);
                        set_setting($name, null);
                    }
                    try {
                        if ($new = Uploader::image($_FILES[$name] ?? null)) {
                            Uploader::delete($cur);
                            set_setting($name, $new);
                        }
                    } catch (RuntimeException $e) {
                        flash('error', $f['label'] . ': ' . $e->getMessage());
                    }
                    continue;
                }
                $val = (string)($_POST[$name] ?? '');
                if ($type === 'secret' && $val === '') {
                    continue; // no sobreescriure secrets si es deixen en blanc
                }
                if ($type === 'html') {
                    $val = Sanitizer::html($val);
                } elseif ($type !== 'textarea') {
                    $val = trim($val);
                }
                set_setting($name, $val);
            }
            log_action('settings:' . $formKey);
            flash('success', 'Configuració desada.');
            if ($formKey === 'settings' && input('test_email') && setting('contact_email')) {
                Mailer::send((string)setting('contact_email'), 'Prova de correu — ' . setting('site_name'), '<p>Si reps aquest correu, la configuració funciona correctament.</p>')
                    ? flash('success', 'Correu de prova enviat a ' . setting('contact_email') . '.')
                    : flash('error', 'No s\'ha pogut enviar el correu de prova. Revisa la configuració SMTP i el registre d\'errors.');
            }
            redirect(ltrim($route, '/'));
        }
        admin_view('settings', ['title' => $form['title'], 'form' => $form, 'formKey' => $formKey]);
    });
}

// ---------------------------------------------------------------- Actualitzacions OTA
$router->get('/admin/updates', function () {
    Auth::require('updates');
    admin_view('updates', [
        'title' => 'Actualitzacions',
        'last' => Updater::lastCheck(),
        'pending' => Migrator::pending(db()),
        'backups' => Updater::backups(),
        'log' => $_SESSION['update_log'] ?? null,
    ]);
    unset($_SESSION['update_log']);
});

$router->post('/admin/updates/check', function () {
    Auth::require('updates');
    verify_csrf();
    try {
        $info = Updater::check();
        flash($info['update_available'] ? 'success' : 'info', $info['update_available'] ? 'Hi ha una nova versió disponible.' : 'Ja tens la darrera versió.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('admin/updates');
});

$router->post('/admin/updates/run', function () {
    Auth::require('updates');
    verify_csrf();
    try {
        $_SESSION['update_log'] = Updater::update();
        log_action('update', implode(' | ', $_SESSION['update_log']));
        // Les migracions s'executen en una petició nova amb el codi actualitzat
        redirect('admin/updates/migrate?auto=1&_csrf=' . urlencode(csrf_token()));
    } catch (Throwable $e) {
        error_log((string)$e);
        flash('error', 'Error durant l\'actualització: ' . $e->getMessage());
        redirect('admin/updates');
    }
});

$router->any('/admin/updates/migrate', function () {
    Auth::require('updates');
    if (!hash_equals(csrf_token(), (string)($_POST['_csrf'] ?? $_GET['_csrf'] ?? ''))) {
        abort(419, 'Sessió caducada.');
    }
    try {
        $applied = Updater::runMigrations();
        $_SESSION['update_log'][] = $applied ? 'Migracions aplicades: ' . implode(', ', $applied) : 'Base de dades al dia.';
        flash('success', input('auto') ? 'Actualització completada correctament.' : 'Base de dades actualitzada.');
    } catch (Throwable $e) {
        error_log((string)$e);
        flash('error', 'Error executant migracions: ' . $e->getMessage());
    }
    redirect('admin/updates');
});

$router->post('/admin/updates/restore', function () {
    Auth::require('updates');
    verify_csrf();
    try {
        Updater::restore((string)input('backup'));
        log_action('update:restore', (string)input('backup'));
        flash('success', 'Còpia restaurada. Revisa que tot funcioni correctament.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('admin/updates');
});

$router->get('/admin/log', function () {
    Auth::require('users');
    $rows = db()->all('SELECT l.*, u.name FROM activity_log l LEFT JOIN users u ON u.id = l.user_id ORDER BY l.id DESC LIMIT 300');
    admin_view('log', ['title' => 'Registre d\'activitat', 'rows' => $rows]);
});
