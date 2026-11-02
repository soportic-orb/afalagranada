<?php defined('BASE_PATH') || exit; ?>
<h2 style="margin:0 0 12px;color:#078838;">Inscripció confirmada!</h2>
<p>Hola <?= e($reg['name']) ?>, aquí tens les entrades per a <strong><?= e($reg['activity_title']) ?></strong>.</p>
<table role="presentation" cellpadding="6" cellspacing="0" style="background:#f4f5f5;border-radius:10px;margin:12px 0;width:100%;">
<?php if ($reg['starts_at']): ?><tr><td>Quan</td><td align="right"><strong><?= e(fmt_date($reg['starts_at'], true)) ?></strong></td></tr><?php endif; ?>
<?php if ($reg['location']): ?><tr><td>On</td><td align="right"><?= e($reg['location']) ?></td></tr><?php endif; ?>
<tr><td>Inscripció</td><td align="right"><?= e($reg['code']) ?></td></tr>
<tr><td>Import</td><td align="right"><?= $reg['amount_cents'] ? e(money((int)$reg['amount_cents'])) : 'Gratuïta' ?></td></tr>
</table>
<p>Mostra el codi QR de cada participant a l'entrada (des del mòbil o imprès):</p>
<?php foreach ($tickets as $t): ?>
<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;border:2px dashed #cfd8d6;border-radius:12px;margin:14px 0;">
<tr>
<td style="padding:14px;width:170px;" valign="middle"><img src="cid:<?= e($t['cid']) ?>" width="160" height="160" alt="QR <?= e($t['code']) ?>" style="display:block;"></td>
<td style="padding:14px;" valign="middle">
<div style="font-size:18px;font-weight:bold;"><?= e($t['attendee_name']) ?></div>
<?php if ($t['attendee_course']): ?><div style="color:#5d605f;"><?= e($t['attendee_course']) ?></div><?php endif; ?>
<div style="font-family:monospace;margin-top:8px;color:#5d605f;"><?= e($t['code']) ?></div>
<div style="margin-top:8px;"><a href="<?= e(abs_url('entrada/' . $t['code'])) ?>" style="color:#078838;">Veure entrada en línia</a></div>
</td>
</tr>
</table>
<?php endforeach; ?>
<p style="color:#5d605f;font-size:13px;">Pots consultar la inscripció en qualsevol moment a <a href="<?= e(abs_url('inscripcio/' . $reg['code'])) ?>" style="color:#078838;"><?= e(abs_url('inscripcio/' . $reg['code'])) ?></a>.</p>
