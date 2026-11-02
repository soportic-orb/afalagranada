<?php
declare(strict_types=1);

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QROutputInterface;

final class Tickets
{
    public static function qrPng(string $data, int $scale = 8): string
    {
        $opts = new QROptions([
            'outputType' => QROutputInterface::GDIMAGE_PNG,
            'scale' => $scale,
            'outputBase64' => false,
            'quietzoneSize' => 2,
        ]);
        return (new QRCode($opts))->render($data);
    }

    public static function qrSvg(string $data): string
    {
        $opts = new QROptions([
            'outputType' => QROutputInterface::MARKUP_SVG,
            'outputBase64' => false,
            'quietzoneSize' => 2,
            'svgAddXmlHeader' => false,
            'drawLightModules' => false,
        ]);
        return (new QRCode($opts))->render($data);
    }

    /** Text codificat al QR: URL de validació per al control d'accés */
    public static function qrPayload(string $ticketCode): string
    {
        return abs_url('entrada/' . $ticketCode);
    }

    /** Genera (si cal) i envia per correu les entrades d'una inscripció */
    public static function send(int $registrationId): bool
    {
        $reg = db()->one('SELECT r.*, a.title AS activity_title, a.starts_at, a.ends_at, a.location, a.slug AS activity_slug
            FROM registrations r JOIN activities a ON a.id = r.activity_id WHERE r.id = ?', [$registrationId]);
        if (!$reg || !in_array($reg['status'], ['paid', 'free'], true)) {
            return false;
        }
        $tickets = db()->all('SELECT * FROM tickets WHERE registration_id = ? ORDER BY id', [$registrationId]);
        $embeds = [];
        foreach ($tickets as $i => $t) {
            $cid = 'qr' . $t['id'];
            $tickets[$i]['cid'] = $cid;
            $embeds[] = [$cid, self::qrPng(self::qrPayload($t['code']), 6), 'entrada-' . $t['code'] . '.png'];
        }
        $html = render('emails/tickets', ['reg' => $reg, 'tickets' => $tickets]);
        $ok = Mailer::send($reg['email'], 'Les teves entrades: ' . $reg['activity_title'], $html, $embeds);
        if ($ok) {
            db()->run('UPDATE registrations SET tickets_sent_at = NOW() WHERE id = ?', [$registrationId]);
        }
        $notify = (string)setting('notify_email', '');
        if ($notify !== '' && !$reg['tickets_sent_at']) {
            Mailer::send($notify, 'Nova inscripció: ' . $reg['activity_title'],
                '<p>Nova inscripció de <strong>' . e($reg['name']) . '</strong> (' . e($reg['email']) . ') a <strong>' . e($reg['activity_title']) . '</strong>: ' . (int)$reg['quantity'] . ' entrada(es), ' . e(money((int)$reg['amount_cents'])) . '.</p>');
        }
        return $ok;
    }

    /** Places ocupades (pagades, gratuïtes o pendents recents) */
    public static function seatsTaken(int $activityId): int
    {
        return (int)db()->value("SELECT COALESCE(SUM(quantity),0) FROM registrations
            WHERE activity_id = ? AND (status IN ('paid','free') OR (status = 'pending' AND created_at > ?))",
            [$activityId, date('Y-m-d H:i:s', time() - 3600)]);
    }
}
