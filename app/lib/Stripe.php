<?php
declare(strict_types=1);

defined('BASE_PATH') || exit;

/**
 * Client mínim de l'API de Stripe (sense SDK, per a servidors compartits).
 * Utilitza Stripe Checkout (pàgina de pagament allotjada per Stripe) i webhooks.
 */
final class Stripe
{
    private const API = 'https://api.stripe.com/v1/';

    public static function enabled(): bool
    {
        return self::secretKey() !== '';
    }

    public static function secretKey(): string
    {
        return (string)setting('stripe_secret_key', '');
    }

    public static function request(string $method, string $path, array $params = []): array
    {
        $ch = curl_init();
        // 'stripe_api_base' a config.php permet apuntar a un servidor de proves (només desenvolupament)
        $url = rtrim((string)config('stripe_api_base', self::API), '/') . '/' . ltrim($path, '/');
        $body = http_build_query($params, '', '&');
        if ($method === 'GET' && $params) {
            $url .= '?' . $body;
        }
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => self::secretKey() . ':',
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ['Stripe-Version: 2024-06-20'],
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $res = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($res === false) {
            throw new RuntimeException('Error de connexió amb Stripe: ' . $err);
        }
        $data = json_decode((string)$res, true) ?: [];
        if ($code >= 400) {
            throw new RuntimeException('Stripe: ' . ($data['error']['message'] ?? "HTTP $code"));
        }
        return $data;
    }

    /**
     * Crea una sessió de Checkout.
     * @param array $opts name, amount_cents, quantity, email, success_url, cancel_url, metadata, description
     */
    public static function createCheckout(array $opts): array
    {
        $params = [
            'mode' => 'payment',
            'locale' => 'auto',
            'success_url' => $opts['success_url'],
            'cancel_url' => $opts['cancel_url'],
            'line_items' => [[
                'quantity' => (int)($opts['quantity'] ?? 1),
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => (int)$opts['amount_cents'],
                    'product_data' => array_filter([
                        'name' => mb_substr($opts['name'], 0, 250),
                        'description' => isset($opts['description']) ? mb_substr($opts['description'], 0, 300) : null,
                    ]),
                ],
            ]],
            'metadata' => $opts['metadata'] ?? [],
            'payment_intent_data' => ['metadata' => $opts['metadata'] ?? []],
            'expires_at' => time() + 3600,
        ];
        if (!empty($opts['email'])) {
            $params['customer_email'] = $opts['email'];
        }
        return self::request('POST', 'checkout/sessions', $params);
    }

    /** Sessió de Checkout per a una comanda de la botiga (diverses línies + enviament) */
    public static function createOrderCheckout(array $order, array $items): array
    {
        $lineItems = [];
        foreach ($items as $it) {
            $lineItems[] = [
                'quantity' => (int)$it['quantity'],
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => (int)$it['unit_cents'],
                    'product_data' => ['name' => mb_substr($it['name'] . ($it['variant_name'] ? ' (' . $it['variant_name'] . ')' : ''), 0, 250)],
                ],
            ];
        }
        if ((int)$order['shipping_cents'] > 0) {
            $lineItems[] = ['quantity' => 1, 'price_data' => ['currency' => 'eur', 'unit_amount' => (int)$order['shipping_cents'], 'product_data' => ['name' => 'Enviament']]];
        }
        $meta = ['type' => 'order', 'order_id' => (string)$order['id'], 'order_number' => (string)$order['number']];
        return self::request('POST', 'checkout/sessions', [
            'mode' => 'payment',
            'locale' => 'auto',
            'customer_email' => $order['email'],
            'client_reference_id' => (string)$order['number'],
            'success_url' => abs_url('botiga/comanda/' . $order['code']) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => abs_url('botiga/cistella') . '?cancel=1',
            'line_items' => $lineItems,
            'metadata' => $meta,
            'payment_intent_data' => ['metadata' => $meta, 'description' => 'Comanda ' . $order['number']],
            'expires_at' => time() + 3600,
        ]);
    }

    /** Devolució (total o parcial) d'un pagament */
    public static function refund(string $paymentIntent, int $amount, array $metadata = []): array
    {
        return self::request('POST', 'refunds', array_filter([
            'payment_intent' => $paymentIntent,
            'amount' => $amount,
            'metadata' => $metadata,
        ]));
    }

    public static function retrieveCheckout(string $id): array
    {
        return self::request('GET', 'checkout/sessions/' . rawurlencode($id));
    }

    /** Verifica la signatura d'un webhook i retorna l'esdeveniment */
    public static function verifyWebhook(string $payload, string $header, string $secret, int $tolerance = 300): array
    {
        $parts = [];
        foreach (explode(',', $header) as $kv) {
            [$k, $v] = array_pad(explode('=', trim($kv), 2), 2, '');
            $parts[$k][] = $v;
        }
        $ts = (int)($parts['t'][0] ?? 0);
        if (!$ts || abs(time() - $ts) > $tolerance) {
            throw new RuntimeException('Marca de temps del webhook invàlida');
        }
        $expected = hash_hmac('sha256', $ts . '.' . $payload, $secret);
        foreach ($parts['v1'] ?? [] as $sig) {
            if (hash_equals($expected, $sig)) {
                $event = json_decode($payload, true);
                if (!is_array($event)) {
                    throw new RuntimeException('JSON invàlid');
                }
                return $event;
            }
        }
        throw new RuntimeException('Signatura del webhook invàlida');
    }

    /**
     * Processa una sessió de Checkout completada (des del webhook o en tornar a la web).
     * És idempotent.
     */
    public static function handleCompletedSession(array $session): void
    {
        if (($session['payment_status'] ?? '') !== 'paid') {
            return;
        }
        $meta = $session['metadata'] ?? [];
        $pi = is_string($session['payment_intent'] ?? null) ? $session['payment_intent'] : null;
        if (($meta['type'] ?? '') === 'donation') {
            $don = db()->one('SELECT * FROM donations WHERE stripe_session_id = ?', [$session['id']]);
            if ($don && $don['status'] !== 'paid') {
                db()->run("UPDATE donations SET status = 'paid', paid_at = NOW(), stripe_payment_intent = ? WHERE id = ?", [$pi, $don['id']]);
                Mailer::donationThanks((int)$don['id']);
            }
        } elseif (($meta['type'] ?? '') === 'order') {
            $order = db()->one('SELECT * FROM orders WHERE stripe_session_id = ?', [$session['id']]);
            if ($order) {
                Shop::markPaid($order, $pi);
            }
        } elseif (($meta['type'] ?? '') === 'registration') {
            $reg = db()->one('SELECT * FROM registrations WHERE stripe_session_id = ?', [$session['id']]);
            if ($reg && $reg['status'] !== 'paid') {
                db()->run("UPDATE registrations SET status = 'paid', paid_at = NOW(), stripe_payment_intent = ? WHERE id = ?", [$pi, $reg['id']]);
                Tickets::send((int)$reg['id']);
            }
        }
    }

    public static function handleExpiredSession(array $session): void
    {
        db()->run("UPDATE donations SET status = 'expired' WHERE stripe_session_id = ? AND status = 'pending'", [$session['id']]);
        db()->run("UPDATE registrations SET status = 'expired' WHERE stripe_session_id = ? AND status = 'pending'", [$session['id']]);
        $order = db()->one("SELECT * FROM orders WHERE stripe_session_id = ? AND payment_status = 'pending'", [$session['id']]);
        if ($order) {
            Shop::expire($order);
        }
    }
}
