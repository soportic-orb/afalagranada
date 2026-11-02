<?php defined('BASE_PATH') || exit; ?>
<h2 style="margin:0 0 12px;color:#078838;">Les teves comandes</h2>
<p>Hem rebut una petició per consultar les comandes fetes amb aquest correu. Fes clic a la comanda que vulguis veure:</p>
<table role="presentation" cellpadding="8" cellspacing="0" style="width:100%;border-collapse:collapse;margin:12px 0;">
<?php foreach ($orders as $o): ?>
<tr>
<td style="border-bottom:1px solid #eee;"><strong><?= e($o['number']) ?></strong><br><span style="color:#5d605f;font-size:13px;"><?= e(date('d/m/Y', strtotime($o['created_at']))) ?> · <?= e(money((int)$o['total_cents'])) ?> · <?= e(Shop::statusLabel($o['status'])) ?></span></td>
<td align="right" style="border-bottom:1px solid #eee;"><a href="<?= e(Shop::orderUrl($o)) ?>" style="display:inline-block;background:#fbdc08;color:#1d1d1b;text-decoration:none;padding:8px 14px;border-radius:999px;font-weight:bold;">Veure</a></td>
</tr>
<?php endforeach; ?>
</table>
<p style="color:#5d605f;font-size:13px;">Si no has fet aquesta petició, pots ignorar aquest correu.</p>
