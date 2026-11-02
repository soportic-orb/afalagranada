<?php
declare(strict_types=1);

defined('BASE_PATH') || exit;

/** @var Router $router */

function shop_guard(): void
{
    if (!Shop::enabled() && !(Auth::check() && Auth::can('shop'))) {
        abort(404);
    }
    Shop::expireStale();
}

function shop_view(string $template, array $data): void
{
    $data['shopNav'] = true;
    view('front/shop/' . $template, $data, 'front/layout');
}

// ---------------------------------------------------------------- Catàleg
$router->get('/botiga', function () {
    shop_guard();
    $products = db()->all("SELECT * FROM products WHERE status = 'active' ORDER BY featured DESC, sort_order, name");
    shop_view('index', ['title' => setting('shop_title', 'Botiga de l\'AFA'), 'products' => $products, 'metaDescription' => setting('shop_intro')]);
});

$router->get('/botiga/producte/{slug}', function (string $slug) {
    shop_guard();
    $p = db()->one("SELECT * FROM products WHERE slug = ? AND status = 'active'", [$slug]) ?? abort(404);
    shop_view('product', [
        'title' => $p['name'], 'p' => $p, 'variants' => Shop::variants((int)$p['id']),
        'metaDescription' => $p['summary'], 'ogImage' => $p['image'] ? abs_url('uploads/' . $p['image']) : null,
    ]);
});

// ---------------------------------------------------------------- Cistella
$router->post('/botiga/cistella/afegir', function () {
    shop_guard();
    verify_csrf();
    $p = db()->one("SELECT * FROM products WHERE id = ? AND status = 'active'", [(int)input('product_id')]) ?? abort(404);
    $variants = Shop::variants((int)$p['id']);
    $variant = null;
    if ($variants) {
        foreach ($variants as $v) {
            if ((int)$v['id'] === (int)input('variant_id')) {
                $variant = $v;
            }
        }
        if (!$variant) {
            flash('error', 'Tria ' . mb_strtolower($p['variant_label'] ?: 'una opció') . '.');
            redirect('botiga/producte/' . $p['slug']);
        }
    }
    $qty = max(1, (int)input('qty', 1));
    $avail = Shop::available($p, $variant);
    $inCart = Shop::cart()[$p['id'] . ':' . ($variant['id'] ?? 0)] ?? 0;
    $limit = min((int)$p['max_per_order'] ?: 99, $avail ?? 99);
    if ($limit <= 0) {
        flash('error', 'Aquest producte està esgotat.');
        redirect('botiga/producte/' . $p['slug']);
    }
    if ($inCart + $qty > $limit) {
        $qty = max(0, $limit - $inCart);
        if ($qty === 0) {
            flash('error', 'Ja tens a la cistella el màxim d\'unitats disponibles d\'aquest producte.');
            redirect('botiga/cistella');
        }
        flash('info', 'Només s\'han pogut afegir ' . $qty . ' unitats (màxim disponible).');
    }
    Shop::cartAdd((int)$p['id'], $variant ? (int)$variant['id'] : null, $qty);
    flash('success', '«' . $p['name'] . ($variant ? ' (' . $variant['name'] . ')' : '') . '» afegit a la cistella.');
    redirect(input('then') === 'cart' ? 'botiga/cistella' : 'botiga/producte/' . $p['slug']);
});

$router->post('/botiga/cistella', function () {
    shop_guard();
    verify_csrf();
    foreach ((array)($_POST['qty'] ?? []) as $key => $qty) {
        Shop::cartSet((string)$key, (int)$qty);
    }
    if ($remove = (string)input('remove')) {
        Shop::cartSet($remove, 0);
    }
    redirect(input('checkout') ? 'botiga/comanda' : 'botiga/cistella');
});

$router->get('/botiga/cistella', function () {
    shop_guard();
    $cart = Shop::cartLines();
    foreach ($cart['notices'] as $n) {
        flash('info', $n);
    }
    shop_view('cart', ['title' => 'Cistella', 'cart' => $cart, 'noindex' => true]);
});

// ---------------------------------------------------------------- Comanda i pagament
$router->get('/botiga/comanda', function () {
    shop_guard();
    $cart = Shop::cartLines();
    if (!$cart['lines']) {
        redirect('botiga/cistella');
    }
    foreach ($cart['notices'] as $n) {
        flash('info', $n);
    }
    shop_view('checkout', ['title' => 'Finalitzar la comanda', 'cart' => $cart, 'methods' => Shop::deliveryMethods(), 'noindex' => true]);
});

$router->post('/botiga/comanda', function () {
    shop_guard();
    verify_csrf();
    remember_input();
    if (!empty($_POST['website'])) {
        redirect('botiga');
    }
    $cart = Shop::cartLines();
    if (!$cart['lines']) {
        redirect('botiga/cistella');
    }
    if ($cart['notices']) {
        foreach ($cart['notices'] as $n) {
            flash('info', $n);
        }
        redirect('botiga/comanda');
    }
    $methods = Shop::deliveryMethods();
    $method = array_key_exists((string)input('delivery'), $methods) ? (string)input('delivery') : (string)array_key_first($methods);
    $c = [
        'name' => mb_substr(trim((string)input('name')), 0, 150),
        'email' => mb_strtolower(trim((string)input('email'))),
        'phone' => mb_substr(trim((string)input('phone')), 0, 40),
        'address' => mb_substr(trim((string)input('address')), 0, 255),
        'postal_code' => mb_substr(trim((string)input('postal_code')), 0, 20),
        'city' => mb_substr(trim((string)input('city')), 0, 100),
        'notes' => mb_substr(trim((string)input('notes')), 0, 1000),
    ];
    $errors = [];
    if ($c['name'] === '') {
        $errors[] = 'Indica el teu nom.';
    }
    if (!filter_var($c['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Indica un correu electrònic vàlid.';
    }
    if ($method === 'shipping' && ($c['address'] === '' || $c['postal_code'] === '' || $c['city'] === '')) {
        $errors[] = 'Completa l\'adreça d\'enviament.';
    }
    if ($method === 'shipping' && $c['phone'] === '') {
        $errors[] = 'Per als enviaments cal un telèfon de contacte.';
    }
    if (!input('accept')) {
        $errors[] = 'Cal acceptar les condicions de venda i la política de privacitat.';
    }
    if (!Stripe::enabled()) {
        $errors[] = 'Els pagaments en línia no estan configurats. Contacta amb l\'AFA.';
    }
    if ($errors) {
        flash('error', implode(' ', $errors));
        redirect('botiga/comanda');
    }
    try {
        $order = Shop::createOrder($c, $cart, $method);
    } catch (RuntimeException $e) {
        flash('error', $e->getMessage());
        redirect('botiga/cistella');
    }
    try {
        $session = Stripe::createOrderCheckout($order, Shop::items((int)$order['id']));
    } catch (Throwable $e) {
        error_log($e->getMessage());
        Shop::expire($order);
        flash('error', 'No s\'ha pogut iniciar el pagament. Torna-ho a provar més tard.');
        redirect('botiga/comanda');
    }
    db()->run('UPDATE orders SET stripe_session_id = ? WHERE id = ?', [$session['id'], $order['id']]);
    Shop::addEvent((int)$order['id'], 'status', 'Comanda creada. Pendent de pagament.', false);
    $_SESSION['pending_order'] = $order['code'];
    clear_old();
    redirect($session['url'], 303);
});

// ---------------------------------------------------------------- Seguiment de la comanda (enllaç privat)
$router->get('/botiga/comanda/{code}', function (string $code) {
    if (!preg_match('/^[a-f0-9]{40}$/', $code)) {
        abort(404);
    }
    $order = db()->one('SELECT * FROM orders WHERE code = ?', [$code]) ?? abort(404);
    $sid = (string)input('session_id');
    if ($order['payment_status'] === 'pending' && $sid !== '' && $sid === $order['stripe_session_id'] && Stripe::enabled()) {
        try {
            Stripe::handleCompletedSession(Stripe::retrieveCheckout($sid));
        } catch (Throwable $e) {
            error_log($e->getMessage());
        }
        $order = Shop::find((int)$order['id']);
    }
    if ($order['payment_status'] === 'paid' && ($_SESSION['pending_order'] ?? '') === $code) {
        Shop::cartClear();
        unset($_SESSION['pending_order']);
    }
    shop_view('order', [
        'title' => 'Comanda ' . $order['number'], 'o' => $order, 'items' => Shop::items((int)$order['id']),
        'events' => Shop::events((int)$order['id'], true), 'noindex' => true, 'justPaid' => $sid !== '',
    ]);
});

$router->post('/botiga/comanda/{code}/cancellar', function (string $code) {
    verify_csrf();
    $order = db()->one('SELECT * FROM orders WHERE code = ?', [$code]) ?? abort(404);
    if (setting('shop_allow_cancel', '1') !== '1' || $order['status'] !== 'received' || $order['payment_status'] !== 'paid') {
        flash('error', 'Aquesta comanda ja no es pot cancel·lar des de la web. Contacta amb nosaltres.');
        redirect('botiga/comanda/' . $code);
    }
    try {
        Shop::addEvent((int)$order['id'], 'status', 'Cancel·lació sol·licitada pel client.');
        Shop::refund($order, (int)$order['total_cents'] - (int)$order['refunded_cents'], 'Cancel·lació sol·licitada pel client', true, false);
        $order = Shop::find((int)$order['id']);
        db()->run("UPDATE orders SET status = 'cancelled' WHERE id = ?", [$order['id']]);
        Shop::sendStatusEmail(Shop::find((int)$order['id']), 'Hem retornat l\'import íntegre a la teva targeta. Pot trigar entre 5 i 10 dies a aparèixer al teu compte.');
        flash('success', 'Comanda cancel·lada. Hem retornat l\'import a la teva targeta.');
    } catch (Throwable $e) {
        error_log($e->getMessage());
        flash('error', 'No s\'ha pogut cancel·lar automàticament. Contacta amb nosaltres i ho gestionarem.');
    }
    redirect('botiga/comanda/' . $code);
});

// «Seguir la meva comanda»: rep per correu els enllaços de les teves comandes
$router->get('/botiga/seguiment', function () {
    shop_guard();
    shop_view('track', ['title' => 'Seguir la meva comanda', 'sent' => !empty($_SESSION['track_sent']), 'noindex' => true]);
    unset($_SESSION['track_sent']);
});

$router->post('/botiga/seguiment', function () {
    shop_guard();
    verify_csrf();
    $email = mb_strtolower(trim((string)input('email')));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Indica un correu electrònic vàlid.');
        redirect('botiga/seguiment');
    }
    $key = 'trk:' . client_ip();
    $recent = (int)db()->value('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND created_at > ?', [$key, date('Y-m-d H:i:s', time() - 3600)]);
    if ($recent >= 5) {
        flash('error', 'Has fet massa peticions. Torna-ho a provar d\'aquí a una estona.');
        redirect('botiga/seguiment');
    }
    db()->run('INSERT INTO login_attempts (ip) VALUES (?)', [$key]);
    if (empty($_POST['website'])) {
        Shop::sendTrackingLinks($email);
    }
    // Sempre la mateixa resposta, per no revelar si hi ha comandes amb aquest correu
    $_SESSION['track_sent'] = true;
    redirect('botiga/seguiment');
});
