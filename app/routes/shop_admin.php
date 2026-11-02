<?php
declare(strict_types=1);

defined('BASE_PATH') || exit;

/** @var Router $router */

// ---------------------------------------------------------------- Opcions (talles...) i estoc d'un producte
$router->any('/admin/products/{id}/variants', function (string $id) {
    Auth::require('shop');
    $p = db()->one('SELECT * FROM products WHERE id = ?', [(int)$id]) ?? abort(404);
    if (is_post()) {
        verify_csrf();
        $action = input('action');
        if ($action === 'save') {
            foreach ((array)($_POST['v'] ?? []) as $vid => $row) {
                $name = mb_substr(trim((string)($row['name'] ?? '')), 0, 100);
                if ($name === '') {
                    continue;
                }
                $stock = trim((string)($row['stock'] ?? ''));
                db()->run('UPDATE product_variants SET name = ?, stock = ?, active = ?, sort_order = ? WHERE id = ? AND product_id = ?', [
                    $name, $stock === '' ? null : (int)$stock, !empty($row['active']) ? 1 : 0, (int)($row['sort'] ?? 10), (int)$vid, (int)$id,
                ]);
            }
            $new = array_filter(array_map('trim', explode("\n", (string)input('new'))));
            $max = (int)db()->value('SELECT COALESCE(MAX(sort_order),0) FROM product_variants WHERE product_id = ?', [(int)$id]);
            foreach ($new as $line) {
                [$name, $stock] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
                if ($name !== '') {
                    db()->insert('product_variants', ['product_id' => (int)$id, 'name' => mb_substr($name, 0, 100), 'stock' => $stock === '' ? null : (int)$stock, 'sort_order' => $max += 10]);
                }
            }
            if (isset($_POST['product_stock'])) {
                $ps = trim((string)input('product_stock'));
                db()->run('UPDATE products SET stock = ? WHERE id = ?', [$ps === '' ? null : (int)$ps, (int)$id]);
            }
            log_action('product:variants', "#$id");
            flash('success', 'Opcions i estoc desats.');
        } elseif ($action === 'delete') {
            db()->run('DELETE FROM product_variants WHERE id = ? AND product_id = ?', [(int)input('variant_id'), (int)$id]);
            flash('success', 'Opció eliminada.');
        }
        redirect("admin/products/$id/variants");
    }
    admin_view('product_variants', ['title' => 'Opcions i estoc: ' . $p['name'], 'p' => $p, 'variants' => Shop::variants((int)$id, false)]);
});

// ---------------------------------------------------------------- Comandes
$router->get('/admin/orders', function () {
    Auth::require('shop');
    Shop::expireStale();
    $status = (string)input('status', 'open');
    $q = trim((string)input('q'));
    $where = [];
    $params = [];
    if ($status === 'open') {
        $where[] = "o.status IN ('received','preparing')";
    } elseif ($status === 'all') {
        $where[] = "o.status <> 'expired'";
    } elseif (isset(Shop::STATUSES[$status])) {
        $where[] = 'o.status = ?';
        $params[] = $status;
    }
    if ($q !== '') {
        $where[] = '(o.number LIKE ? OR o.email LIKE ? OR o.name LIKE ?)';
        array_push($params, "%$q%", "%$q%", "%$q%");
    }
    $sql = 'SELECT o.*, (SELECT COALESCE(SUM(quantity),0) FROM order_items WHERE order_id = o.id) AS units FROM orders o'
        . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY o.created_at DESC LIMIT 500';
    $rows = db()->all($sql, $params);
    if (input('export') === 'csv') {
        $out = [];
        foreach ($rows as $o) {
            foreach (Shop::items((int)$o['id']) as $it) {
                $out[] = [$o['number'], $o['created_at'], $o['name'], $o['email'], $o['phone'], $o['delivery_method'] === 'shipping' ? 'Enviament' : 'Recollida',
                    trim($o['address'] . ' ' . $o['postal_code'] . ' ' . $o['city']), $it['name'], $it['variant_name'], $it['quantity'],
                    number_format($it['total_cents'] / 100, 2, ',', ''), number_format($o['total_cents'] / 100, 2, ',', ''),
                    Shop::statusLabel($o['status']), Shop::paymentLabel($o['payment_status']), number_format($o['refunded_cents'] / 100, 2, ',', ''), $o['notes']];
            }
        }
        csv_download('comandes.csv', ['Comanda', 'Data', 'Nom', 'Correu', 'Telèfon', 'Lliurament', 'Adreça', 'Producte', 'Opció', 'Unitats', 'Import línia (€)', 'Total comanda (€)', 'Estat', 'Pagament', 'Retornat (€)', 'Observacions'], $out);
    }
    $stats = db()->one("SELECT
        COALESCE(SUM(CASE WHEN payment_status IN ('paid','partially_refunded','refunded') THEN total_cents - refunded_cents END),0) AS revenue,
        COUNT(CASE WHEN payment_status IN ('paid','partially_refunded','refunded') THEN 1 END) AS orders,
        COUNT(CASE WHEN status = 'received' THEN 1 END) AS to_prepare,
        COUNT(CASE WHEN status = 'preparing' THEN 1 END) AS preparing
        FROM orders");
    admin_view('orders', ['title' => 'Comandes', 'rows' => $rows, 'status' => $status, 'q' => $q, 'stats' => $stats]);
});

$router->get('/admin/orders/{id}', function (string $id) {
    Auth::require('shop');
    $o = Shop::find((int)$id) ?? abort(404);
    admin_view('order', ['title' => 'Comanda ' . $o['number'], 'o' => $o, 'items' => Shop::items((int)$id), 'events' => Shop::events((int)$id)]);
});

$router->post('/admin/orders/{id}', function (string $id) {
    Auth::require('shop');
    verify_csrf();
    $o = Shop::find((int)$id) ?? abort(404);
    $action = input('action');
    try {
        if ($action === 'status') {
            $status = (string)input('status');
            if (!in_array($status, Shop::MANUAL_STATUSES, true)) {
                throw new RuntimeException('Estat no vàlid.');
            }
            if ($o['payment_status'] === 'pending' || $o['status'] === 'expired') {
                throw new RuntimeException('No es pot canviar l\'estat d\'una comanda no pagada.');
            }
            Shop::setStatus($o, $status, trim((string)input('message')), (bool)input('notify'), [
                'tracking_number' => mb_substr(trim((string)input('tracking_number')), 0, 100),
                'tracking_url' => filter_var(trim((string)input('tracking_url')), FILTER_VALIDATE_URL) ?: '',
            ]);
            if (in_array($status, ['cancelled', 'returned'], true) && input('restock')) {
                Shop::releaseStock($o);
            }
            log_action('order:status', $o['number'] . ' → ' . $status);
            flash('success', 'Estat actualitzat' . (input('notify') ? ' i client avisat per correu.' : '.'));
        } elseif ($action === 'refund') {
            $amount = input('refund_type') === 'full' ? (int)$o['total_cents'] - (int)$o['refunded_cents'] : to_cents(input('amount'));
            $done = Shop::refund($o, $amount, trim((string)input('reason')), (bool)input('restock'), (bool)input('notify'));
            log_action('order:refund', $o['number'] . ' ' . $done);
            flash('success', 'Devolució de ' . money($done) . ' feta a Stripe.');
        } elseif ($action === 'note') {
            $note = trim((string)input('note'));
            if ($note !== '') {
                Shop::addEvent((int)$o['id'], 'note', $note, false);
                flash('success', 'Nota afegida.');
            }
        } elseif ($action === 'resend') {
            Shop::sendConfirmation($o);
            flash('success', 'Confirmació reenviada a ' . $o['email'] . '.');
        }
    } catch (Throwable $e) {
        error_log((string)$e);
        flash('error', $e->getMessage());
    }
    redirect('admin/orders/' . (int)$id);
});
