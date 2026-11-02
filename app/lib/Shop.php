<?php
declare(strict_types=1);

defined('BASE_PATH') || exit;

/**
 * Botiga: cistella (a la sessió), comandes, estoc, devolucions i correus.
 */
final class Shop
{
    /** Estats de la comanda (gestió interna i seguiment del client) */
    public const STATUSES = [
        'pending' => 'Pendent de pagament',
        'received' => 'Rebuda',
        'preparing' => 'En preparació',
        'shipped' => 'Enviada / lliurada',
        'cancelled' => 'Cancel·lada',
        'returned' => 'Devolució',
        'expired' => 'No pagada (caducada)',
    ];

    public const PAYMENT_STATUSES = [
        'pending' => 'Pendent',
        'paid' => 'Pagada',
        'partially_refunded' => 'Retornada parcialment',
        'refunded' => 'Retornada',
        'failed' => 'Fallida',
        'expired' => 'Caducada',
    ];

    /** Estats que pot assignar l'administració manualment */
    public const MANUAL_STATUSES = ['received', 'preparing', 'shipped', 'cancelled', 'returned'];

    /** Minuts de reserva de l'estoc mentre el client paga (Stripe Checkout caduca als 60) */
    private const RESERVATION_MINUTES = 70;

    public static function enabled(): bool
    {
        return setting('shop_enabled', '0') === '1';
    }

    public static function statusLabel(string $s): string
    {
        return self::STATUSES[$s] ?? $s;
    }

    public static function statusBadge(string $s): string
    {
        $cls = ['pending' => 'muted', 'received' => 'warn', 'preparing' => 'info', 'shipped' => 'ok',
            'cancelled' => 'bad', 'returned' => 'bad', 'expired' => 'muted'][$s] ?? 'muted';
        return '<span class="badge badge-' . $cls . '">' . e(self::statusLabel($s)) . '</span>';
    }

    public static function paymentLabel(string $s): string
    {
        return self::PAYMENT_STATUSES[$s] ?? $s;
    }

    public static function orderUrl(array $order, bool $absolute = true): string
    {
        $path = 'botiga/comanda/' . $order['code'];
        return $absolute ? abs_url($path) : url($path);
    }

    // ------------------------------------------------------------ Productes i estoc

    public static function variants(int $productId, bool $onlyActive = true): array
    {
        return db()->all('SELECT * FROM product_variants WHERE product_id = ?' . ($onlyActive ? ' AND active = 1' : '') . ' ORDER BY sort_order, id', [$productId]);
    }

    /** Unitats disponibles (null = sense límit) */
    public static function available(array $product, ?array $variant = null): ?int
    {
        $stock = $variant ? $variant['stock'] : $product['stock'];
        return $stock === null ? null : max(0, (int)$stock);
    }

    /** Estoc total d'un producte per mostrar al llistat (null = sense límit) */
    public static function productStock(array $product): ?int
    {
        $variants = self::variants((int)$product['id']);
        if (!$variants) {
            return self::available($product);
        }
        $total = 0;
        foreach ($variants as $v) {
            if ($v['stock'] === null) {
                return null;
            }
            $total += max(0, (int)$v['stock']);
        }
        return $total;
    }

    // ------------------------------------------------------------ Cistella (sessió)

    public static function cart(): array
    {
        return is_array($_SESSION['cart'] ?? null) ? $_SESSION['cart'] : [];
    }

    private static function cartKey(int $productId, ?int $variantId): string
    {
        return $productId . ':' . ($variantId ?: 0);
    }

    public static function cartAdd(int $productId, ?int $variantId, int $qty): void
    {
        $cart = self::cart();
        $key = self::cartKey($productId, $variantId);
        $cart[$key] = min(99, ($cart[$key] ?? 0) + max(1, $qty));
        $_SESSION['cart'] = $cart;
    }

    public static function cartSet(string $key, int $qty): void
    {
        $cart = self::cart();
        if (!isset($cart[$key])) {
            return;
        }
        if ($qty <= 0) {
            unset($cart[$key]);
        } else {
            $cart[$key] = min(99, $qty);
        }
        $_SESSION['cart'] = $cart;
    }

    public static function cartClear(): void
    {
        unset($_SESSION['cart']);
    }

    public static function cartCount(): int
    {
        return array_sum(self::cart());
    }

    /**
     * Resol la cistella amb preus i disponibilitat actuals.
     * Ajusta les quantitats que superen l'estoc o el màxim per comanda.
     */
    public static function cartLines(): array
    {
        $lines = [];
        $subtotal = 0;
        $notices = [];
        $cart = self::cart();
        foreach ($cart as $key => $qty) {
            [$pid, $vid] = array_map('intval', explode(':', (string)$key) + [1 => 0]);
            $p = db()->one("SELECT * FROM products WHERE id = ? AND status = 'active'", [$pid]);
            $v = $vid ? db()->one('SELECT * FROM product_variants WHERE id = ? AND product_id = ? AND active = 1', [$vid, $pid]) : null;
            if (!$p || ($vid && !$v) || (!$vid && self::variants($pid))) {
                unset($cart[$key]);
                $notices[] = 'Un producte de la cistella ja no està disponible i s\'ha tret.';
                continue;
            }
            $avail = self::available($p, $v);
            $max = (int)$p['max_per_order'] ?: 99;
            $limit = $avail === null ? $max : min($max, $avail);
            if ($limit <= 0) {
                unset($cart[$key]);
                $notices[] = '«' . $p['name'] . ($v ? ' (' . $v['name'] . ')' : '') . '» s\'ha esgotat i s\'ha tret de la cistella.';
                continue;
            }
            if ($qty > $limit) {
                $qty = $limit;
                $cart[$key] = $qty;
                $notices[] = 'S\'ha ajustat la quantitat de «' . $p['name'] . '» a les unitats disponibles (' . $limit . ').';
            }
            $lineTotal = (int)$p['price_cents'] * $qty;
            $subtotal += $lineTotal;
            $lines[] = ['key' => $key, 'product' => $p, 'variant' => $v, 'qty' => $qty, 'limit' => $limit, 'unit' => (int)$p['price_cents'], 'total' => $lineTotal];
        }
        $_SESSION['cart'] = $cart;
        return ['lines' => $lines, 'subtotal' => $subtotal, 'notices' => $notices];
    }

    // ------------------------------------------------------------ Lliurament

    public static function deliveryMethods(): array
    {
        $m = [];
        if (setting('shop_pickup_enabled', '1') === '1') {
            $m['pickup'] = setting('shop_pickup_text', 'Recollida a l\'escola');
        }
        if (setting('shop_shipping_enabled', '0') === '1') {
            $m['shipping'] = 'Enviament a domicili';
        }
        return $m ?: ['pickup' => 'Recollida a l\'escola'];
    }

    public static function shippingCost(string $method, int $subtotal): int
    {
        if ($method !== 'shipping') {
            return 0;
        }
        $free = (string)setting('shop_free_shipping_cents', '');
        if ($free !== '' && (int)$free > 0 && $subtotal >= (int)$free) {
            return 0;
        }
        return (int)setting('shop_shipping_cents', '0');
    }

    // ------------------------------------------------------------ Comandes

    /**
     * Crea la comanda i reserva l'estoc. Llança RuntimeException si algun producte s'ha esgotat.
     */
    public static function createOrder(array $customer, array $cart, string $method): array
    {
        $shipping = self::shippingCost($method, $cart['subtotal']);
        return db()->transaction(function (Database $db) use ($customer, $cart, $method, $shipping) {
            foreach ($cart['lines'] as $l) {
                $table = $l['variant'] ? 'product_variants' : 'products';
                $id = $l['variant'] ? (int)$l['variant']['id'] : (int)$l['product']['id'];
                $st = $db->run("UPDATE `$table` SET stock = stock - ? WHERE id = ? AND stock IS NOT NULL AND stock >= ?", [$l['qty'], $id, $l['qty']]);
                if ($st->rowCount() === 0 && $db->value("SELECT stock FROM `$table` WHERE id = ?", [$id]) !== null) {
                    throw new RuntimeException('«' . $l['product']['name'] . '» no té prou unitats disponibles.');
                }
            }
            $orderId = $db->insert('orders', [
                'code' => bin2hex(random_bytes(20)),
                'name' => $customer['name'],
                'email' => $customer['email'],
                'phone' => $customer['phone'] ?: null,
                'delivery_method' => $method,
                'address' => $method === 'shipping' ? $customer['address'] : null,
                'postal_code' => $method === 'shipping' ? $customer['postal_code'] : null,
                'city' => $method === 'shipping' ? $customer['city'] : null,
                'notes' => $customer['notes'] ?: null,
                'subtotal_cents' => $cart['subtotal'],
                'shipping_cents' => $shipping,
                'total_cents' => $cart['subtotal'] + $shipping,
                'status' => 'pending',
                'payment_status' => 'pending',
            ]);
            $number = 'AFA-' . date('y') . '-' . str_pad((string)$orderId, 4, '0', STR_PAD_LEFT);
            $db->run('UPDATE orders SET number = ? WHERE id = ?', [$number, $orderId]);
            foreach ($cart['lines'] as $l) {
                $db->insert('order_items', [
                    'order_id' => $orderId,
                    'product_id' => $l['product']['id'],
                    'variant_id' => $l['variant']['id'] ?? null,
                    'name' => $l['product']['name'],
                    'variant_name' => $l['variant'] ? trim(($l['product']['variant_label'] ? $l['product']['variant_label'] . ': ' : '') . $l['variant']['name']) : null,
                    'unit_cents' => $l['unit'],
                    'quantity' => $l['qty'],
                    'total_cents' => $l['total'],
                ]);
            }
            return $db->one('SELECT * FROM orders WHERE id = ?', [$orderId]);
        });
    }

    public static function find(int $id): ?array
    {
        return db()->one('SELECT * FROM orders WHERE id = ?', [$id]);
    }

    public static function items(int $orderId): array
    {
        return db()->all('SELECT * FROM order_items WHERE order_id = ? ORDER BY id', [$orderId]);
    }

    public static function events(int $orderId, bool $publicOnly = false): array
    {
        return db()->all('SELECT e.*, u.name AS user_name FROM order_events e LEFT JOIN users u ON u.id = e.user_id WHERE e.order_id = ?' . ($publicOnly ? ' AND e.public = 1' : '') . ' ORDER BY e.id', [$orderId]);
    }

    public static function addEvent(int $orderId, string $type, string $message, bool $public = true): void
    {
        db()->insert('order_events', ['order_id' => $orderId, 'type' => $type, 'message' => $message, 'public' => $public ? 1 : 0, 'user_id' => Auth::id()]);
    }

    /** Retorna l'estoc reservat (una sola vegada per comanda) */
    public static function releaseStock(array $order): void
    {
        $st = db()->run('UPDATE orders SET stock_released = 1 WHERE id = ? AND stock_released = 0', [$order['id']]);
        if ($st->rowCount() === 0) {
            return;
        }
        foreach (self::items((int)$order['id']) as $it) {
            if ($it['variant_id']) {
                db()->run('UPDATE product_variants SET stock = stock + ? WHERE id = ? AND stock IS NOT NULL', [$it['quantity'], $it['variant_id']]);
            } elseif ($it['product_id']) {
                db()->run('UPDATE products SET stock = stock + ? WHERE id = ? AND stock IS NOT NULL', [$it['quantity'], $it['product_id']]);
            }
        }
    }

    /** Marca com a pagada (des del webhook o en tornar de Stripe). Idempotent. */
    public static function markPaid(array $order, ?string $paymentIntent): void
    {
        $st = db()->run("UPDATE orders SET payment_status = 'paid', status = 'received', paid_at = NOW(), stripe_payment_intent = ? WHERE id = ? AND payment_status IN ('pending','expired','failed')", [$paymentIntent, $order['id']]);
        if ($st->rowCount() === 0) {
            return;
        }
        $order = self::find((int)$order['id']);
        if ((int)$order['stock_released'] === 1) {
            // La reserva havia caducat però el client ha acabat pagant: es torna a descomptar l'estoc
            db()->run('UPDATE orders SET stock_released = 0 WHERE id = ?', [$order['id']]);
            foreach (self::items((int)$order['id']) as $it) {
                $table = $it['variant_id'] ? 'product_variants' : 'products';
                $id = $it['variant_id'] ?: $it['product_id'];
                if ($id) {
                    db()->run("UPDATE `$table` SET stock = GREATEST(stock - ?, 0) WHERE id = ? AND stock IS NOT NULL", [$it['quantity'], $id]);
                }
            }
        }
        self::addEvent((int)$order['id'], 'status', 'Pagament rebut. Comanda confirmada.');
        self::sendConfirmation($order);
    }

    /** Caduca les comandes no pagades i allibera l'estoc */
    public static function expireStale(): void
    {
        try {
            $rows = db()->all("SELECT * FROM orders WHERE payment_status = 'pending' AND created_at < ?", [date('Y-m-d H:i:s', time() - self::RESERVATION_MINUTES * 60)]);
        } catch (Throwable $e) {
            return;
        }
        foreach ($rows as $o) {
            self::expire($o);
        }
    }

    public static function expire(array $order): void
    {
        $st = db()->run("UPDATE orders SET payment_status = 'expired', status = 'expired' WHERE id = ? AND payment_status = 'pending'", [$order['id']]);
        if ($st->rowCount()) {
            self::releaseStock($order);
        }
    }

    /** Canvia l'estat de la comanda, opcionalment avisant el client */
    public static function setStatus(array $order, string $status, string $message = '', bool $notify = true, array $tracking = []): void
    {
        $data = ['status' => $status];
        if (array_key_exists('tracking_number', $tracking)) {
            $data['tracking_number'] = $tracking['tracking_number'] ?: null;
            $data['tracking_url'] = $tracking['tracking_url'] ?: null;
        }
        db()->update('orders', $data, (int)$order['id']);
        $text = 'Estat: ' . self::statusLabel($status) . ($message !== '' ? ' — ' . $message : '');
        self::addEvent((int)$order['id'], 'status', $text);
        if ($notify) {
            self::sendStatusEmail(self::find((int)$order['id']), $message);
        }
    }

    /**
     * Retorna diners al client a través de Stripe.
     * @return int import retornat en cèntims
     */
    public static function refund(array $order, int $amount, string $reason = '', bool $restock = false, bool $notify = true): int
    {
        $refundable = (int)$order['total_cents'] - (int)$order['refunded_cents'];
        if (!in_array($order['payment_status'], ['paid', 'partially_refunded'], true)) {
            throw new RuntimeException('Aquesta comanda no té cap pagament per retornar.');
        }
        if ($amount <= 0 || $amount > $refundable) {
            throw new RuntimeException('L\'import ha d\'estar entre 0,01 € i ' . money($refundable) . '.');
        }
        if (!$order['stripe_payment_intent']) {
            throw new RuntimeException('No hi ha cap pagament de Stripe associat a la comanda.');
        }
        $refund = Stripe::refund($order['stripe_payment_intent'], $amount, ['order' => $order['number'], 'reason' => mb_substr($reason, 0, 400)]);
        $newRefunded = (int)$order['refunded_cents'] + $amount;
        $full = $newRefunded >= (int)$order['total_cents'];
        $data = ['refunded_cents' => $newRefunded, 'payment_status' => $full ? 'refunded' : 'partially_refunded'];
        if ($full && !in_array($order['status'], ['cancelled', 'returned'], true)) {
            $data['status'] = $order['status'] === 'shipped' ? 'returned' : 'cancelled';
        }
        db()->update('orders', $data, (int)$order['id']);
        self::addEvent((int)$order['id'], 'refund', 'Devolució de ' . money($amount) . ($reason !== '' ? ' — ' . $reason : '') . ' (Stripe ' . ($refund['id'] ?? '') . ')');
        // L'estoc només es retorna automàticament en devolucions totals (en les parcials s'ajusta a mà)
        if ($full && ($restock || in_array($order['status'], ['received', 'preparing'], true))) {
            self::releaseStock($order);
        }
        if ($notify) {
            self::sendRefundEmail(self::find((int)$order['id']), $amount, $reason);
        }
        return $amount;
    }

    /** Sincronitza una devolució feta des del tauler de Stripe (webhook charge.refunded) */
    public static function syncRefundFromCharge(array $charge): void
    {
        $pi = $charge['payment_intent'] ?? null;
        if (!$pi) {
            return;
        }
        $order = db()->one('SELECT * FROM orders WHERE stripe_payment_intent = ?', [$pi]);
        if (!$order) {
            return;
        }
        $refunded = (int)($charge['amount_refunded'] ?? 0);
        if ($refunded <= (int)$order['refunded_cents']) {
            return; // ja registrat (p. ex. devolució feta des del panell)
        }
        $full = $refunded >= (int)$order['total_cents'];
        db()->update('orders', ['refunded_cents' => $refunded, 'payment_status' => $full ? 'refunded' : 'partially_refunded'], (int)$order['id']);
        self::addEvent((int)$order['id'], 'refund', 'Devolució registrada des de Stripe: total retornat ' . money($refunded) . '.', false);
    }

    // ------------------------------------------------------------ Correus

    public static function sendConfirmation(array $order): void
    {
        $items = self::items((int)$order['id']);
        Mailer::send($order['email'], 'Comanda ' . $order['number'] . ' confirmada — ' . setting('shop_title', 'Botiga de l\'AFA'),
            render('emails/order', ['o' => $order, 'items' => $items, 'intro' => 'Hem rebut la teva comanda i el pagament. Gràcies per donar suport a l\'AFA!', 'heading' => 'Comanda confirmada']));
        $notify = (string)setting('shop_notify_email', setting('notify_email', ''));
        if ($notify !== '') {
            Mailer::send($notify, 'Nova comanda ' . $order['number'] . ' — ' . money((int)$order['total_cents']),
                render('emails/order', ['o' => $order, 'items' => $items, 'intro' => 'S\'ha rebut una nova comanda a la botiga de ' . $order['name'] . ' (' . $order['email'] . ').', 'heading' => 'Nova comanda', 'admin' => true]),
                [], $order['email'], $order['name']);
        }
    }

    public static function sendStatusEmail(array $order, string $message = ''): void
    {
        $intro = [
            'received' => 'Hem rebut la teva comanda.',
            'preparing' => 'Estem preparant la teva comanda.',
            'shipped' => $order['delivery_method'] === 'shipping' ? 'La teva comanda ha sortit cap a la teva adreça.' : 'La teva comanda ja està a punt / lliurada.',
            'cancelled' => 'La teva comanda s\'ha cancel·lat.',
            'returned' => 'S\'ha registrat la devolució de la teva comanda.',
        ][$order['status']] ?? 'L\'estat de la teva comanda ha canviat.';
        Mailer::send($order['email'], 'Comanda ' . $order['number'] . ': ' . self::statusLabel($order['status']),
            render('emails/order', ['o' => $order, 'items' => self::items((int)$order['id']), 'intro' => $intro, 'message' => $message, 'heading' => self::statusLabel($order['status'])]));
    }

    public static function sendRefundEmail(array $order, int $amount, string $reason): void
    {
        Mailer::send($order['email'], 'Devolució de la comanda ' . $order['number'],
            render('emails/order', ['o' => $order, 'items' => self::items((int)$order['id']),
                'intro' => 'Hem retornat ' . money($amount) . ' a la targeta amb què vas pagar. Pot trigar entre 5 i 10 dies a aparèixer al teu compte.',
                'message' => $reason, 'heading' => 'Devolució']));
    }

    /** Envia per correu els enllaços de seguiment de totes les comandes d'una adreça */
    public static function sendTrackingLinks(string $email): void
    {
        $orders = db()->all("SELECT * FROM orders WHERE email = ? AND payment_status <> 'pending' AND status <> 'expired' ORDER BY created_at DESC LIMIT 20", [mb_strtolower($email)]);
        if (!$orders) {
            return;
        }
        Mailer::send($email, 'Les teves comandes — ' . setting('shop_title', 'Botiga de l\'AFA'), render('emails/order_links', ['orders' => $orders]));
    }
}
