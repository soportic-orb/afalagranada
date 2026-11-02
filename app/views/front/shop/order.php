<?php defined('BASE_PATH') || exit; ?>
<?php
$steps = ['received' => 'Rebuda', 'preparing' => 'En preparació', 'shipped' => $o['delivery_method'] === 'shipping' ? 'Enviada' : 'Lliurada'];
$keys = array_keys($steps);
$cur = array_search($o['status'], $keys, true);
$canCancel = setting('shop_allow_cancel', '1') === '1' && $o['status'] === 'received' && $o['payment_status'] === 'paid';
?>
<?php partial('shop_bar'); ?>
<section class="section">
    <div class="container narrow">
        <?php if ($o['payment_status'] === 'paid' && !empty($justPaid)): ?>
            <div class="order-thanks"><span class="sent-emoji small" aria-hidden="true">🎉</span><div><strong>Gràcies per la teva compra!</strong><br>T'hem enviat un correu amb la confirmació i l'enllaç per seguir la comanda.</div></div>
        <?php endif; ?>
        <p class="eyebrow">Comanda</p>
        <h1 class="h2"><?= e($o['number']) ?></h1>
        <p class="muted">Feta el <?= e(fmt_date($o['created_at'], true)) ?> · <?= e($o['name']) ?></p>

        <?php if ($o['status'] === 'pending'): ?>
            <div class="alert alert-info">Encara no hem rebut la confirmació del pagament. Si l'has completat, aquesta pàgina s'actualitzarà en uns minuts.</div>
        <?php elseif ($o['status'] === 'expired'): ?>
            <div class="alert alert-warn">Aquesta comanda no es va arribar a pagar i s'ha anul·lat. Pots tornar a fer-la des de la <a href="<?= url('botiga') ?>">botiga</a>.</div>
        <?php elseif (in_array($o['status'], ['cancelled', 'returned'], true)): ?>
            <div class="alert alert-warn">Estat: <strong><?= e(Shop::statusLabel($o['status'])) ?></strong><?= (int)$o['refunded_cents'] ? ' · Import retornat: ' . e(money((int)$o['refunded_cents'])) : '' ?></div>
        <?php else: ?>
            <ol class="order-steps">
                <?php foreach ($steps as $k => $label): $i = array_search($k, $keys, true); ?>
                    <li class="<?= $cur !== false && $i < $cur ? 'done' : ($i === $cur ? 'current' : '') ?>"><span></span><?= e($label) ?></li>
                <?php endforeach; ?>
            </ol>
            <?php if ($o['tracking_number'] || $o['tracking_url']): ?>
                <p class="tracking">Seguiment de l'enviament: <strong><?= e($o['tracking_number']) ?></strong>
                    <?php if ($o['tracking_url']): ?> · <a href="<?= e($o['tracking_url']) ?>" target="_blank" rel="noopener">Consultar a la web del transportista ↗</a><?php endif; ?></p>
            <?php endif; ?>
        <?php endif; ?>

        <div class="card pad">
            <h2 class="h3">Productes</h2>
            <?php foreach ($items as $it): ?>
                <div class="sum-row"><span><?= (int)$it['quantity'] ?> × <?= e($it['name']) ?><?= $it['variant_name'] ? ' <small class="muted">(' . e($it['variant_name']) . ')</small>' : '' ?></span><span><?= e(money((int)$it['total_cents'])) ?></span></div>
            <?php endforeach; ?>
            <div class="sum-row"><span>Lliurament</span><span><?= (int)$o['shipping_cents'] ? e(money((int)$o['shipping_cents'])) : 'Gratuït' ?></span></div>
            <div class="sum-row total-row"><span>Total</span><strong><?= e(money((int)$o['total_cents'])) ?></strong></div>
            <?php if ((int)$o['refunded_cents']): ?><div class="sum-row"><span>Retornat</span><span>−<?= e(money((int)$o['refunded_cents'])) ?></span></div><?php endif; ?>
        </div>
        <div class="card pad">
            <h2 class="h3">Lliurament</h2>
            <p><?= $o['delivery_method'] === 'shipping'
                ? 'Enviament a: ' . e($o['address']) . ', ' . e($o['postal_code']) . ' ' . e($o['city'])
                : e(setting('shop_pickup_text', 'Recollida a l\'escola')) ?></p>
            <?php if ($o['notes']): ?><p class="muted">Observacions: <?= e($o['notes']) ?></p><?php endif; ?>
        </div>
        <?php if ($events): ?>
        <div class="card pad">
            <h2 class="h3">Historial</h2>
            <ul class="timeline">
                <?php foreach (array_reverse($events) as $ev): ?>
                    <li><time><?= e(date('d/m/Y H:i', strtotime($ev['created_at']))) ?></time> <?= e($ev['message']) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        <div class="order-actions">
            <?php if ($canCancel): ?>
                <form method="post" action="<?= url('botiga/comanda/' . $o['code'] . '/cancellar') ?>" onsubmit="return confirm('Segur que vols cancel·lar la comanda? Et retornarem l\'import íntegre.')">
                    <?= csrf_field() ?><button class="btn btn-ghost">Cancel·lar la comanda</button>
                </form>
            <?php endif; ?>
            <a class="btn btn-ghost" href="<?= url('contacte') ?>">Tens cap dubte? Contacta'ns</a>
        </div>
        <p class="muted small">Guarda aquest enllaç o el correu de confirmació per consultar la comanda quan vulguis. També pots demanar-lo de nou a «<a href="<?= url('botiga/seguiment') ?>">Seguir la meva comanda</a>».</p>
    </div>
</section>
