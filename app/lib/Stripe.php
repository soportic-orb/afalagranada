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
        $url = self::API . ltrim($path, '/');
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
    }
}
