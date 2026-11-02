<?php defined('BASE_PATH') || exit; ?>
<h2 style="margin:0 0 12px;color:#078838;">Nou missatge des de la web</h2>
<table role="presentation" cellpadding="8" cellspacing="0" style="background:#f4f5f5;border-radius:10px;margin:12px 0;width:100%;">
<tr><td style="width:110px;color:#5d605f;">Nom</td><td><strong><?= e($m['name']) ?></strong></td></tr>
<tr><td style="color:#5d605f;">Correu</td><td><a href="mailto:<?= e($m['email']) ?>" style="color:#078838;"><?= e($m['email']) ?></a></td></tr>
<?php if ($m['phone']): ?><tr><td style="color:#5d605f;">Telèfon</td><td><?= e($m['phone']) ?></td></tr><?php endif; ?>
<tr><td style="color:#5d605f;">Motiu</td><td><?= e($m['subject']) ?></td></tr>
</table>
<div style="white-space:pre-wrap;border-left:4px solid #fbdc08;padding:8px 14px;margin:16px 0;"><?= e($m['message']) ?></div>
<p style="color:#5d605f;font-size:13px;">Pots respondre directament a aquest correu per contestar a <?= e($m['name']) ?>. Missatge núm. <?= (int)$m['id'] ?>, també disponible a Panell → Missatges.</p>
