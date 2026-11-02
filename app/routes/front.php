<?php
declare(strict_types=1);

defined('BASE_PATH') || exit;

/** @var Router $router */

function front_page(string $slug): void
{
    $page = db()->one('SELECT * FROM pages WHERE slug = ? AND published = 1', [$slug]);
    if (!$page) {
        abort(404);
    }
    view('front/page', ['title' => $page['title'], 'page' => $page, 'metaDescription' => $page['excerpt'] ?: strip_to_text($page['content'])], 'front/layout');
}

function campaign_stats(array $c): array
{
    $row = db()->one("SELECT COALESCE(SUM(amount_cents),0) AS total, COUNT(*) AS n FROM donations WHERE campaign_id = ? AND status = 'paid'", [$c['id']]);
    $raised = (int)$row['total'] + (int)$c['offline_cents'];
    $goal = (int)$c['goal_cents'];
    $daysLeft = null;
    if ($c['ends_at']) {
        $daysLeft = (int)floor((strtotime($c['ends_at'] . ' 23:59:59') - time()) / 86400);
    }
    return [
        'raised' => $raised,
        'count' => (int)$row['n'],
        'goal' => $goal,
        'percent' => $goal > 0 ? min(100, (int)floor($raised * 100 / $goal)) : 0,
        'percent_real' => $goal > 0 ? (int)floor($raised * 100 / $goal) : 0,
        'days_left' => $daysLeft,
    ];
}

function campaign_is_open(array $c): bool
{
    if ($c['status'] !== 'active') {
        return false;
    }
    $today = date('Y-m-d');
    if ($c['starts_at'] && $c['starts_at'] > $today) {
        return false;
    }
    if ($c['ends_at'] && $c['ends_at'] < $today) {
        return false;
    }
    return true;
}

function activity_state(array $a): array
{
    $now = date('Y-m-d H:i:s');
    $taken = Tickets::seatsTaken((int)$a['id']);
    $cap = (int)$a['capacity'];
    $left = $cap > 0 ? max(0, $cap - $taken) : null;
    $open = $a['status'] === 'published';
    $reason = '';
    if ($a['registration_opens'] && $a['registration_opens'] > $now) {
        $open = false;
        $reason = 'Les inscripcions s\'obriran el ' . fmt_date($a['registration_opens'], true) . '.';
    } elseif ($a['registration_closes'] && $a['registration_closes'] < $now) {
        $open = false;
        $reason = 'Les inscripcions estan tancades.';
    } elseif ($a['ends_at'] && $a['ends_at'] < $now || (!$a['ends_at'] && $a['starts_at'] && $a['starts_at'] < $now)) {
        $open = false;
        $reason = 'Aquesta activitat ja s\'ha realitzat.';
    } elseif ($left === 0) {
        $open = false;
        $reason = 'No queden places disponibles.';
    } elseif ($a['status'] !== 'published') {
        $reason = 'Inscripcions no disponibles.';
    }
    return ['taken' => $taken, 'left' => $left, 'open' => $open, 'reason' => $reason];
}

// ---------------------------------------------------------------- Portada
$router->get('/', function () {
    $benefits = db()->all("SELECT * FROM blocks WHERE group_key = 'benefits' AND published = 1 ORDER BY sort_order, id");
    $services = db()->all("SELECT * FROM blocks WHERE group_key = 'services' AND published = 1 ORDER BY sort_order, id");
    $campaigns = db()->all("SELECT * FROM campaigns WHERE status = 'active' ORDER BY featured DESC, created_at DESC LIMIT 3");
    $activities = db()->all("SELECT * FROM activities WHERE status = 'published' AND (starts_at IS NULL OR starts_at >= ?) ORDER BY starts_at IS NULL, starts_at LIMIT 3", [date('Y-m-d 00:00:00')]);
    $posts = db()->all('SELECT * FROM posts WHERE published = 1 AND published_at <= NOW() ORDER BY published_at DESC LIMIT 3');
    view('front/home', compact('benefits', 'services', 'campaigns', 'activities', 'posts') + ['title' => ''], 'front/layout');
});

// ---------------------------------------------------------------- Notícies
$router->get('/noticies', function () {
    $posts = db()->all('SELECT * FROM posts WHERE published = 1 AND published_at <= NOW() ORDER BY published_at DESC LIMIT 50');
    view('front/posts', ['title' => 'Notícies', 'posts' => $posts], 'front/layout');
});

$router->get('/noticies/{slug}', function (string $slug) {
    $post = db()->one('SELECT * FROM posts WHERE slug = ? AND published = 1', [$slug]);
    if (!$post) {
        abort(404);
    }
    view('front/post', ['title' => $post['title'], 'post' => $post, 'metaDescription' => $post['excerpt']], 'front/layout');
});

// ---------------------------------------------------------------- App AFA
$router->get('/aplicacio', function () {
    $features = db()->all("SELECT * FROM blocks WHERE group_key = 'app' AND published = 1 ORDER BY sort_order, id");
    view('front/app', ['title' => 'L\'app de l\'AFA', 'features' => $features], 'front/layout');
});

// ---------------------------------------------------------------- Crowdfunding
$router->get('/campanyes', function () {
    $campaigns = db()->all("SELECT * FROM campaigns WHERE status IN ('active','closed') ORDER BY status = 'active' DESC, featured DESC, created_at DESC");
    view('front/campaigns', ['title' => 'Campanyes de micromecenatge', 'campaigns' => $campaigns], 'front/layout');
});

$router->get('/campanyes/{slug}', function (string $slug) {
    $c = db()->one("SELECT * FROM campaigns WHERE slug = ? AND status IN ('active','closed')", [$slug]);
    if (!$c) {
        abort(404);
    }
    $images = db()->all('SELECT * FROM campaign_images WHERE campaign_id = ? ORDER BY sort_order, id', [$c['id']]);
    $donors = db()->all("SELECT donor_name, anonymous, amount_cents, message, paid_at FROM donations WHERE campaign_id = ? AND status = 'paid' ORDER BY paid_at DESC LIMIT 12", [$c['id']]);
    view('front/campaign', [
        'title' => $c['title'], 'c' => $c, 'images' => $images, 'donors' => $donors,
        'stats' => campaign_stats($c), 'open' => campaign_is_open($c),
        'metaDescription' => $c['summary'], 'ogImage' => $c['image'] ? abs_url('uploads/' . $c['image']) : null,
    ], 'front/layout');
});

$router->post('/campanyes/{slug}/donar', function (string $slug) {
    verify_csrf();
    $c = db()->one("SELECT * FROM campaigns WHERE slug = ? AND status = 'active'", [$slug]);
    if (!$c || !campaign_is_open($c)) {
        abort(404);
    }
    remember_input();
    $amount = input('amount') === 'other' ? to_cents(input('amount_other')) : to_cents(input('amount'));
    $email = filter_var(input('email'), FILTER_VALIDATE_EMAIL);
    $min = max(100, (int)$c['min_donation_cents']);
    if (!empty($_POST['website'])) { // parany anti-spam
        redirect('campanyes/' . $slug);
    }
    if ($amount < $min || $amount > 1000000) {
        flash('error', 'L\'import mínim és ' . money($min) . '.');
        redirect('campanyes/' . $slug . '#donar');
    }
    if (!$email) {
        flash('error', 'Cal indicar un correu electrònic vàlid.');
        redirect('campanyes/' . $slug . '#donar');
    }
    if (!Stripe::enabled()) {
        flash('error', 'Els pagaments en línia no estan configurats. Contacta amb l\'AFA.');
        redirect('campanyes/' . $slug . '#donar');
    }
    $id = db()->insert('donations', [
        'campaign_id' => $c['id'],
        'amount_cents' => $amount,
        'donor_name' => mb_substr(input('name'), 0, 150) ?: null,
        'donor_email' => mb_strtolower($email),
        'message' => mb_substr(input('message'), 0, 500) ?: null,
        'anonymous' => input('anonymous') ? 1 : 0,
        'status' => 'pending',
    ]);
    try {
        $session = Stripe::createCheckout([
            'name' => 'Donació: ' . $c['title'],
            'description' => setting('site_name', 'AFA La Granada'),
            'amount_cents' => $amount,
            'email' => $email,
            'success_url' => abs_url('campanyes/' . $slug . '/gracies') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => abs_url('campanyes/' . $slug) . '?cancel=1#donar',
            'metadata' => ['type' => 'donation', 'donation_id' => (string)$id, 'campaign_id' => (string)$c['id']],
        ]);
    } catch (Throwable $e) {
        error_log($e->getMessage());
        db()->run("UPDATE donations SET status = 'failed' WHERE id = ?", [$id]);
        flash('error', 'No s\'ha pogut iniciar el pagament. Torna-ho a provar més tard.');
        redirect('campanyes/' . $slug . '#donar');
    }
    db()->run('UPDATE donations SET stripe_session_id = ? WHERE id = ?', [$session['id'], $id]);
    clear_old();
    redirect($session['url'], 303);
});

$router->get('/campanyes/{slug}/gracies', function (string $slug) {
    $c = db()->one('SELECT * FROM campaigns WHERE slug = ?', [$slug]);
    if (!$c) {
        abort(404);
    }
    $sid = (string)input('session_id');
    $donation = null;
    if ($sid !== '' && Stripe::enabled()) {
        try {
            Stripe::handleCompletedSession(Stripe::retrieveCheckout($sid));
        } catch (Throwable $e) {
            error_log($e->getMessage());
        }
        $donation = db()->one('SELECT * FROM donations WHERE stripe_session_id = ? AND campaign_id = ?', [$sid, $c['id']]);
    }
    view('front/thanks', ['title' => 'Gràcies!', 'c' => $c, 'donation' => $donation, 'stats' => campaign_stats($c)], 'front/layout');
});

// ---------------------------------------------------------------- Activitats
$router->get('/activitats', function () {
    $upcoming = db()->all("SELECT * FROM activities WHERE status IN ('published','closed') AND (COALESCE(ends_at, starts_at) IS NULL OR COALESCE(ends_at, starts_at) >= ?) ORDER BY starts_at IS NULL, starts_at", [date('Y-m-d 00:00:00')]);
    $past = db()->all("SELECT * FROM activities WHERE status IN ('published','closed') AND COALESCE(ends_at, starts_at) < ? ORDER BY starts_at DESC LIMIT 6", [date('Y-m-d 00:00:00')]);
    view('front/activities', ['title' => 'Activitats', 'upcoming' => $upcoming, 'past' => $past], 'front/layout');
});

$router->get('/activitats/{slug}', function (string $slug) {
    $a = db()->one("SELECT * FROM activities WHERE slug = ? AND status IN ('published','closed')", [$slug]);
    if (!$a) {
        abort(404);
    }
    view('front/activity', [
        'title' => $a['title'], 'a' => $a, 'state' => activity_state($a),
        'metaDescription' => $a['summary'], 'ogImage' => $a['image'] ? abs_url('uploads/' . $a['image']) : null,
    ], 'front/layout');
});

$router->post('/activitats/{slug}/inscripcio', function (string $slug) {
    verify_csrf();
    $a = db()->one("SELECT * FROM activities WHERE slug = ? AND status = 'published'", [$slug]);
    if (!$a) {
        abort(404);
    }
    remember_input();
    $back = 'activitats/' . $slug . '#inscripcio';
    if (!empty($_POST['website'])) {
        redirect('activitats/' . $slug);
    }
    $state = activity_state($a);
    if (!$state['open']) {
        flash('error', $state['reason'] ?: 'Inscripcions tancades.');
        redirect($back);
    }
    $name = mb_substr(input('name'), 0, 150);
    $email = filter_var(input('email'), FILTER_VALIDATE_EMAIL);
    $names = array_values(array_filter(array_map(fn($v) => mb_substr(trim((string)$v), 0, 150), (array)($_POST['attendee'] ?? []))));
    $courses = array_values((array)($_POST['course'] ?? []));
    $qty = count($names);
    $max = max(1, (int)$a['max_per_registration']);
    $errors = [];
    if ($name === '') {
        $errors[] = 'Indica el nom de la persona responsable.';
    }
    if (!$email) {
        $errors[] = 'Indica un correu electrònic vàlid.';
    }
    if ($qty < 1) {
        $errors[] = 'Afegeix com a mínim un/a participant.';
    }
    if ($qty > $max) {
        $errors[] = "Com a màxim es poden fer $max inscripcions alhora.";
    }
    if ($state['left'] !== null && $qty > $state['left']) {
        $errors[] = 'Només queden ' . $state['left'] . ' places disponibles.';
    }
    if (!input('accept')) {
        $errors[] = 'Cal acceptar la política de privacitat.';
    }
    if ($errors) {
        flash('error', implode(' ', $errors));
        redirect($back);
    }
    $isMember = input('is_member') ? 1 : 0;
    $unit = ($isMember && $a['member_price_cents'] !== null) ? (int)$a['member_price_cents'] : (int)$a['price_cents'];
    $total = $unit * $qty;
    if ($total > 0 && !Stripe::enabled()) {
        flash('error', 'Els pagaments en línia no estan configurats. Contacta amb l\'AFA.');
        redirect($back);
    }
    $code = random_code(8);
    $regId = db()->transaction(function (Database $db) use ($a, $code, $name, $email, $isMember, $qty, $total, $names, $courses) {
        $id = $db->insert('registrations', [
            'activity_id' => $a['id'],
            'code' => $code,
            'name' => $name,
            'email' => mb_strtolower((string)$email),
            'phone' => mb_substr(input('phone'), 0, 40) ?: null,
            'is_member' => $isMember,
            'quantity' => $qty,
            'amount_cents' => $total,
            'status' => $total > 0 ? 'pending' : 'free',
            'notes' => mb_substr(input('notes'), 0, 1000) ?: null,
        ]);
        foreach ($names as $i => $n) {
            $db->insert('tickets', [
                'registration_id' => $id,
                'code' => random_code(8),
                'attendee_name' => $n,
                'attendee_course' => mb_substr(trim((string)($courses[$i] ?? '')), 0, 50) ?: null,
            ]);
        }
        return $id;
    });
    clear_old();
    if ($total === 0) {
        Tickets::send($regId);
        redirect('inscripcio/' . $code);
    }
    try {
        $session = Stripe::createCheckout([
            'name' => 'Inscripció: ' . $a['title'],
            'description' => $qty . ' entrada(es)' . ($isMember ? ' — preu soci/a' : ''),
            'amount_cents' => $unit,
            'quantity' => $qty,
            'email' => $email,
            'success_url' => abs_url('inscripcio/' . $code) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => abs_url('activitats/' . $slug) . '?cancel=1#inscripcio',
            'metadata' => ['type' => 'registration', 'registration_id' => (string)$regId, 'activity_id' => (string)$a['id']],
        ]);
    } catch (Throwable $e) {
        error_log($e->getMessage());
        db()->run("UPDATE registrations SET status = 'failed' WHERE id = ?", [$regId]);
        flash('error', 'No s\'ha pogut iniciar el pagament. Torna-ho a provar més tard.');
        redirect($back);
    }
    db()->run('UPDATE registrations SET stripe_session_id = ? WHERE id = ?', [$session['id'], $regId]);
    redirect($session['url'], 303);
});

$router->get('/inscripcio/{code}', function (string $code) {
    $reg = db()->one('SELECT r.*, a.title AS activity_title, a.slug AS activity_slug, a.starts_at, a.location FROM registrations r JOIN activities a ON a.id = r.activity_id WHERE r.code = ?', [$code]);
    if (!$reg) {
        abort(404);
    }
    $sid = (string)input('session_id');
    if ($reg['status'] === 'pending' && $sid !== '' && $sid === $reg['stripe_session_id'] && Stripe::enabled()) {
        try {
            Stripe::handleCompletedSession(Stripe::retrieveCheckout($sid));
            $reg = db()->one('SELECT r.*, a.title AS activity_title, a.slug AS activity_slug, a.starts_at, a.location FROM registrations r JOIN activities a ON a.id = r.activity_id WHERE r.code = ?', [$code]);
        } catch (Throwable $e) {
            error_log($e->getMessage());
        }
    }
    $tickets = in_array($reg['status'], ['paid', 'free'], true)
        ? db()->all('SELECT * FROM tickets WHERE registration_id = ? ORDER BY id', [$reg['id']]) : [];
    view('front/registration', ['title' => 'La teva inscripció', 'reg' => $reg, 'tickets' => $tickets, 'noindex' => true], 'front/layout');
});

// Entrada individual (el QR apunta aquí). Si qui l'obre és personal autoritzat, pot validar-la.
$router->get('/entrada/{code}', function (string $code) {
    $t = db()->one('SELECT t.*, r.status AS reg_status, r.name AS buyer, r.code AS reg_code, a.title AS activity_title, a.starts_at, a.location
        FROM tickets t JOIN registrations r ON r.id = t.registration_id JOIN activities a ON a.id = r.activity_id WHERE t.code = ?', [$code]);
    if (!$t) {
        abort(404, 'Entrada no trobada.');
    }
    $staff = Auth::check() && Auth::can('checkin');
    view('front/ticket', ['title' => 'Entrada', 't' => $t, 'staff' => $staff, 'noindex' => true], 'front/layout');
});

$router->get('/entrada/{code}/qr.svg', function (string $code) {
    if (!db()->value('SELECT id FROM tickets WHERE code = ?', [$code])) {
        abort(404);
    }
    header('Content-Type: image/svg+xml');
    header('Cache-Control: private, max-age=86400');
    echo Tickets::qrSvg(Tickets::qrPayload($code));
});

// ---------------------------------------------------------------- Stripe webhook
$router->post('/stripe/webhook', function () {
    $payload = (string)file_get_contents('php://input');
    $secret = (string)setting('stripe_webhook_secret', '');
    if ($secret === '') {
        json_response(['error' => 'webhook no configurat'], 400);
    }
    try {
        $event = Stripe::verifyWebhook($payload, $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '', $secret);
    } catch (Throwable $e) {
        json_response(['error' => $e->getMessage()], 400);
    }
    try {
        db()->run('INSERT INTO stripe_events (id, type) VALUES (?, ?)', [$event['id'], $event['type']]);
    } catch (PDOException $e) {
        json_response(['ok' => true, 'duplicate' => true]);
    }
    $obj = $event['data']['object'] ?? [];
    switch ($event['type']) {
        case 'checkout.session.completed':
        case 'checkout.session.async_payment_succeeded':
            Stripe::handleCompletedSession($obj);
            break;
        case 'checkout.session.expired':
        case 'checkout.session.async_payment_failed':
            Stripe::handleExpiredSession($obj);
            break;
        case 'charge.refunded':
            if (!empty($obj['payment_intent'])) {
                db()->run("UPDATE donations SET status = 'refunded' WHERE stripe_payment_intent = ?", [$obj['payment_intent']]);
                db()->run("UPDATE registrations SET status = 'refunded' WHERE stripe_payment_intent = ?", [$obj['payment_intent']]);
            }
            break;
    }
    json_response(['ok' => true]);
});
