<?php defined('BASE_PATH') || exit; ?>
<?php
$refundable = (int)$o['total_cents'] - (int)$o['refunded_cents'];
$paid = in_array($o['payment_status'], ['paid', 'partially_refunded'], true);
$stripeLink = $o['stripe_payment_intent'] ? 'https://dashboard.stripe.com/' . (str_starts_with((string)setting('stripe_secret_key'), 'sk_test') ? 'test/' : '') . 'payments/' . rawurlencode($o['stripe_payment_intent']) : null;
?>
<p><a href="<?= url('admin/orders') ?>">← Comandes</a> · <a href="<?= e(Shop::orderUrl($o)) ?>" target="_blank">Veure la pàgina del client ↗</a></p>
<div class="order-head panel">
    <div><span class="muted small">Estat</span><br><?= Shop::statusBadge($o['status']) ?></div>
    <div><span class="muted small">Pagament</span><br><strong><?= e(Shop::paymentLabel($o['payment_status'])) ?></strong><?= $o['paid_at'] ? ' <small class="muted">' . e(date('d/m/Y H:i', strtotime($o['paid_at']))) . '</small>' : '' ?></div>
    <div><span class="muted small">Total</span><br><strong><?= e(money((int)$o['total_cents'])) ?></strong><?= (int)$o['refunded_cents'] ? ' <small class="muted">(retornat ' . e(money((int)$o['refunded_cents'])) . ')</small>' : '' ?></div>
    <div><span class="muted small">Data</span><br><?= e(date('d/m/Y H:i', strtotime($o['created_at']))) ?></div>
</div>
<div class="form-grid">
    <div>
        <div class="panel">
            <h2>Productes</h2>
            <table class="table">
                <tbody>
                <?php foreach ($items as $it): ?>
                    <tr><td><strong><?= (int)$it['quantity'] ?> ×</strong> <?= e($it['name']) ?><?= $it['variant_name'] ? ' <span class="badge badge-muted">' . e($it['variant_name']) . '</span>' : '' ?></td><td class="num"><?= e(money((int)$it['unit_cents'])) ?></td><td class="num"><strong><?= e(money((int)$it['total_cents'])) ?></strong></td></tr>
                <?php endforeach; ?>
                <tr><td>Lliurament: <?= $o['delivery_method'] === 'shipping' ? 'enviament' : 'recollida a l\'escola' ?></td><td></td><td class="num"><?= e(money((int)$o['shipping_cents'])) ?></td></tr>
                <tr><td><strong>Total</strong></td><td></td><td class="num"><strong><?= e(money((int)$o['total_cents'])) ?></strong></td></tr>
                </tbody>
            </table>
        </div>
        <div class="panel">
            <h2>Client i lliurament</h2>
            <dl class="dl">
                <dt>Nom</dt><dd><?= e($o['name']) ?></dd>
                <dt>Correu</dt><dd><a href="mailto:<?= e($o['email']) ?>"><?= e($o['email']) ?></a></dd>
                <?php if ($o['phone']): ?><dt>Telèfon</dt><dd><a href="tel:<?= e(preg_replace('/\s+/', '', $o['phone'])) ?>"><?= e($o['phone']) ?></a></dd><?php endif; ?>
                <dt>Lliurament</dt><dd><?= $o['delivery_method'] === 'shipping' ? e($o['address']) . '<br>' . e($o['postal_code'] . ' ' . $o['city']) : e(setting('shop_pickup_text', 'Recollida a l\'escola')) ?></dd>
                <?php if ($o['notes']): ?><dt>Observacions</dt><dd><?= nl2br(e($o['notes'])) ?></dd><?php endif; ?>
                <?php if ($stripeLink): ?><dt>Stripe</dt><dd><a href="<?= e($stripeLink) ?>" target="_blank" rel="noopener"><code><?= e($o['stripe_payment_intent']) ?></code> ↗</a></dd><?php endif; ?>
            </dl>
        </div>
        <div class="panel">
            <h2>Historial</h2>
            <ul class="timeline-admin">
                <?php foreach (array_reverse($events) as $ev): ?>
                    <li class="ev-<?= e($ev['type']) ?>"><time><?= e(date('d/m/Y H:i', strtotime($ev['created_at']))) ?></time> <?= e($ev['message']) ?>
                        <small class="muted"><?= $ev['user_name'] ? '· ' . e($ev['user_name']) : '' ?><?= $ev['public'] ? '' : ' · intern' ?></small></li>
                <?php endforeach; ?>
            </ul>
            <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="note"><input name="note" placeholder="Afegir una nota interna..."><button class="btn">Afegir</button></form>
        </div>
    </div>
    <div class="side">
        <?php if ($paid || in_array($o['status'], ['shipped', 'cancelled', 'returned', 'received', 'preparing'], true)): ?>
        <form method="post" class="panel">
            <?= csrf_field() ?><input type="hidden" name="action" value="status">
            <h2>Canviar l'estat</h2>
            <div class="field"><select name="status" id="st-select">
                <?php foreach (Shop::MANUAL_STATUSES as $s): ?><option value="<?= e($s) ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= e(Shop::statusLabel($s)) ?></option><?php endforeach; ?>
            </select></div>
            <div class="field"><label>Número de seguiment</label><input name="tracking_number" value="<?= e($o['tracking_number']) ?>"></div>
            <div class="field"><label>Enllaç de seguiment</label><input name="tracking_url" value="<?= e($o['tracking_url']) ?>" placeholder="https://..."></div>
            <div class="field"><label>Missatge per al client (opcional)</label><textarea name="message" rows="2"></textarea></div>
            <label class="check small"><input type="checkbox" name="notify" value="1" checked> Avisar el client per correu</label>
            <label class="check small"><input type="checkbox" name="restock" value="1"> Retornar les unitats a l'estoc (si es cancel·la o es retorna)</label>
            <button class="btn btn-primary btn-block">Desar estat</button>
        </form>
        <?php endif; ?>
        <?php if ($paid && $refundable > 0): ?>
        <form method="post" class="panel" onsubmit="return confirm('Es farà la devolució a la targeta del client a través de Stripe. Aquesta acció no es pot desfer. Continuar?')">
            <?= csrf_field() ?><input type="hidden" name="action" value="refund">
            <h2>Devolució del pagament</h2>
            <label class="check small"><input type="radio" name="refund_type" value="full" checked> Import total pendent (<?= e(money($refundable)) ?>)</label>
            <label class="check small"><input type="radio" name="refund_type" value="partial"> Import parcial:</label>
            <div class="input-suffix" style="margin-bottom:.8rem"><input name="amount" inputmode="decimal" placeholder="0,00"><span>€</span></div>
            <div class="field"><label>Motiu</label><input name="reason" placeholder="P. ex. talla no disponible"></div>
            <label class="check small"><input type="checkbox" name="restock" value="1"> Retornar les unitats a l'estoc (devolució total)</label>
            <label class="check small"><input type="checkbox" name="notify" value="1" checked> Avisar el client per correu</label>
            <button class="btn btn-danger btn-block">Fer la devolució</button>
            <p class="muted small">En devolucions parcials, si cal, ajusta l'estoc a mà a «Productes i estoc». Si la comanda encara no s'ha enviat, una devolució total la cancel·la i retorna l'estoc automàticament.</p>
            <?php if (!Stripe::enabled()): ?><p class="muted small">Stripe no està configurat.</p><?php endif; ?>
        </form>
        <?php endif; ?>
        <?php if ($paid): ?>
        <form method="post" class="panel"><?= csrf_field() ?><input type="hidden" name="action" value="resend"><button class="btn btn-block">Reenviar confirmació al client</button></form>
        <?php endif; ?>
    </div>
</div>
