<?php defined('BASE_PATH') || exit; ?>
<h2 style="margin:0 0 12px;color:#078838;"><?= e($heading) ?></h2>
<p>Hola <?= e($o['name']) ?>, <?= e($intro) ?></p>
<?php if (!empty($message)): ?><div style="border-left:4px solid #fbdc08;padding:8px 14px;margin:14px 0;white-space:pre-wrap;"><?= e($message) ?></div><?php endif; ?>
<?php if ($o['tracking_number'] && $o['status'] === 'shipped'): ?><p>Número de seguiment: <strong><?= e($o['tracking_number']) ?></strong><?= $o['tracking_url'] ? ' — <a href="' . e($o['tracking_url']) . '" style="color:#078838;">consultar l\'enviament</a>' : '' ?></p><?php endif; ?>
<table role="presentation" cellpadding="6" cellspacing="0" style="width:100%;border-collapse:collapse;margin:14px 0;">
<tr><td colspan="2" style="background:#f4f5f5;font-weight:bold;">Comanda <?= e($o['number']) ?> · <?= e(Shop::statusLabel($o['status'])) ?></td></tr>
<?php foreach ($items as $it): ?>
<tr><td style="border-bottom:1px solid #eee;"><?= (int)$it['quantity'] ?> × <?= e($it['name']) ?><?= $it['variant_name'] ? ' (' . e($it['variant_name']) . ')' : '' ?></td><td align="right" style="border-bottom:1px solid #eee;"><?= e(money((int)$it['total_cents'])) ?></td></tr>
<?php endforeach; ?>
<tr><td>Lliurament: <?= $o['delivery_method'] === 'shipping' ? 'enviament a ' . e($o['address'] . ', ' . $o['postal_code'] . ' ' . $o['city']) : e(setting('shop_pickup_text', 'Recollida a l\'escola')) ?></td><td align="right"><?= (int)$o['shipping_cents'] ? e(money((int)$o['shipping_cents'])) : 'Gratuït' ?></td></tr>
<tr><td><strong>Total</strong></td><td align="right"><strong><?= e(money((int)$o['total_cents'])) ?></strong></td></tr>
<?php if ((int)$o['refunded_cents']): ?><tr><td>Retornat</td><td align="right">−<?= e(money((int)$o['refunded_cents'])) ?></td></tr><?php endif; ?>
</table>
<?php if (!empty($admin)): ?>
<p><a href="<?= e(abs_url('admin/orders/' . $o['id'])) ?>" style="display:inline-block;background:#078838;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:999px;font-weight:bold;">Gestionar la comanda</a></p>
<?php else: ?>
<p><a href="<?= e(Shop::orderUrl($o)) ?>" style="display:inline-block;background:#fbdc08;color:#1d1d1b;text-decoration:none;padding:12px 20px;border-radius:999px;font-weight:bold;">Seguir la meva comanda</a></p>
<p style="color:#5d605f;font-size:13px;">Aquest enllaç és personal: dona accés a la teva comanda. No el comparteixis.</p>
<?php endif; ?>
