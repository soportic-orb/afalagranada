<!doctype html>
<html lang="ca"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title><?= e($subject) ?></title></head>
<body style="margin:0;padding:0;background:#f4f1ea;font-family:Arial,Helvetica,sans-serif;color:#1d2b2a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f1ea;padding:24px 12px;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:14px;overflow:hidden;">
<tr><td style="background:#1f6f5c;color:#ffffff;padding:22px 28px;font-size:20px;font-weight:bold;">
<?= e(setting('site_name', 'AFA Escola Jacint Verdaguer')) ?><br><span style="font-size:13px;font-weight:normal;opacity:.85;"><?= e(setting('site_tagline', 'La Granada')) ?></span>
</td></tr>
<tr><td style="padding:28px;font-size:15px;line-height:1.55;"><?= $body ?></td></tr>
<tr><td style="padding:18px 28px;background:#faf8f3;color:#6b7775;font-size:12px;line-height:1.5;">
<?= e(setting('legal_name', setting('site_name', 'AFA Escola Jacint Verdaguer'))) ?><?= setting('legal_nif') ? ' · NIF ' . e(setting('legal_nif')) : '' ?><br>
<a href="<?= e(base_url()) ?>" style="color:#1f6f5c;"><?= e(preg_replace('#^https?://#', '', base_url())) ?></a>
<?= setting('contact_email') ? ' · ' . e(setting('contact_email')) : '' ?>
</td></tr>
</table>
</td></tr></table>
</body></html>
