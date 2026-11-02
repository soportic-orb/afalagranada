<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

final class Mailer
{
    public static function make(): PHPMailer
    {
        $m = new PHPMailer(true);
        $m->CharSet = 'UTF-8';
        $host = (string)setting('smtp_host', '');
        if ($host !== '') {
            $m->isSMTP();
            $m->Host = $host;
            $m->Port = (int)setting('smtp_port', '587');
            $user = (string)setting('smtp_user', '');
            if ($user !== '') {
                $m->SMTPAuth = true;
                $m->Username = $user;
                $m->Password = (string)setting('smtp_password', '');
            }
            $enc = (string)setting('smtp_encryption', 'tls');
            if ($enc === 'ssl') {
                $m->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($enc === 'tls') {
                $m->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $m->SMTPSecure = '';
                $m->SMTPAutoTLS = false;
            }
        } else {
            $m->isMail();
        }
        $from = (string)setting('mail_from', 'no-reply@afalagranada.cat');
        $m->setFrom($from, (string)setting('mail_from_name', setting('site_name', 'AFA La Granada')));
        $reply = (string)setting('contact_email', '');
        if ($reply !== '') {
            $m->addReplyTo($reply);
        }
        return $m;
    }

    /** Envia un correu amb el disseny base. $embeds = [[cid, binari, nom]] */
    public static function send(string $to, string $subject, string $bodyHtml, array $embeds = []): bool
    {
        try {
            $m = self::make();
            $m->addAddress($to);
            $m->Subject = $subject;
            $m->isHTML(true);
            $m->Body = render('emails/layout', ['subject' => $subject, 'body' => $bodyHtml]);
            $m->AltBody = trim(html_entity_decode(strip_tags(str_replace(['<br>', '</p>', '</h2>', '</tr>'], "\n", $bodyHtml))));
            foreach ($embeds as [$cid, $data, $name]) {
                $m->addStringEmbeddedImage($data, $cid, $name, PHPMailer::ENCODING_BASE64, 'image/png');
            }
            $m->send();
            return true;
        } catch (Throwable $e) {
            error_log('Error enviant correu a ' . $to . ': ' . $e->getMessage());
            return false;
        }
    }

    public static function donationThanks(int $donationId): void
    {
        $d = db()->one('SELECT d.*, c.title AS campaign_title, c.slug AS campaign_slug FROM donations d JOIN campaigns c ON c.id = d.campaign_id WHERE d.id = ?', [$donationId]);
        if (!$d || !$d['donor_email']) {
            return;
        }
        $html = render('emails/donation', ['d' => $d]);
        self::send($d['donor_email'], 'Gràcies per la teva aportació! — ' . $d['campaign_title'], $html);
        $notify = (string)setting('notify_email', '');
        if ($notify !== '') {
            self::send($notify, 'Nova donació: ' . money((int)$d['amount_cents']) . ' — ' . $d['campaign_title'],
                '<p>S\'ha rebut una nova donació de <strong>' . e(money((int)$d['amount_cents'])) . '</strong> a la campanya <strong>' . e($d['campaign_title']) . '</strong>.</p><p>Donant: ' . e($d['donor_name'] ?: 'Anònim') . ' (' . e($d['donor_email']) . ')</p>');
        }
    }
}
