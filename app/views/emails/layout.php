<?php defined('BASE_PATH') || exit; ?>
<!doctype html>
<html lang="ca"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title><?= e($subject) ?></title></head>
<body style="margin:0;padding:0;background:#f4f5f5;font-family:Arial,Helvetica,sans-serif;color:#1d1d1b;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f5;padding:24px 12px;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:14px;overflow:hidden;">
<tr><td style="padding:22px 28px 16px;background:#ffffff;"><img src="cid:afalogo" width="240" alt="<?= e(setting('site_name', 'AFA Jacint Verdaguer')) ?>" style="display:block;width:240px;max-width:100%;height:auto;border:0;"></td></tr>
<tr><td style="font-size:0;line-height:0;height:6px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td height="6" style="background:#d9151c;width:33%;"></td><td height="6" style="background:#078838;width:34%;"></td><td height="6" style="background:#fbdc08;width:33%;"></td></tr></table></td></tr>
<tr><td style="padding:28px;font-size:15px;line-height:1.55;"><?= $body ?></td></tr>
<tr><td style="padding:18px 28px;background:#f7f8f8;color:#5d605f;font-size:12px;line-height:1.5;">
<?= e(setting('legal_name', setting('site_name', 'AFA Escola Jacint Verdaguer'))) ?><?= setting('legal_nif') ? ' · NIF ' . e(setting('legal_nif')) : '' ?><br>
<a href="<?= e(base_url()) ?>" style="color:#078838;"><?= e(preg_replace('#^https?://#', '', base_url())) ?></a>
<?= setting('contact_email') ? ' · ' . e(setting('contact_email')) : '' ?>
</td></tr>
</table>
</td></tr></table>
</body></html>
