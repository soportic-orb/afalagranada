<?php defined('BASE_PATH') || exit; ?>
<?php
$sel = old('delivery', (string)array_key_first($methods));
$shippingCost = Shop::shippingCost('shipping', $cart['subtotal']);
$freeFrom = (int)setting('shop_free_shipping_cents', '0');
?>
<?php partial('shop_bar'); ?>
<section class="section">
    <div class="container">
        <h1 class="h2">Finalitzar la comanda</h1>
        <form method="post" action="<?= url('botiga/comanda') ?>" class="cart-layout form" id="checkout-form"
              data-subtotal="<?= (int)$cart['subtotal'] ?>" data-shipping="<?= (int)$shippingCost ?>">
            <?= csrf_field() ?>
            <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
            <div class="card pad">
                <fieldset>
                    <legend>Les teves dades</legend>
                    <div class="grid-2">
                        <div class="field"><label for="o-name">Nom i cognoms *</label><input id="o-name" name="name" required value="<?= e(old('name')) ?>" autocomplete="name"></div>
                        <div class="field"><label for="o-email">Correu electrònic *</label><input id="o-email" type="email" name="email" required value="<?= e(old('email')) ?>" autocomplete="email"><small>Hi rebràs la confirmació i l'enllaç de seguiment.</small></div>
                        <div class="field"><label for="o-phone">Telèfon</label><input id="o-phone" type="tel" name="phone" value="<?= e(old('phone')) ?>" autocomplete="tel"></div>
                    </div>
                </fieldset>
                <fieldset>
                    <legend>Lliurament</legend>
                    <?php foreach ($methods as $k => $label): ?>
                        <label class="delivery-option">
                            <input type="radio" name="delivery" value="<?= e($k) ?>" <?= $sel === $k ? 'checked' : '' ?>>
                            <span><strong><?= e($label) ?></strong>
                                <small><?= $k === 'shipping' ? ($shippingCost ? e(money($shippingCost)) : 'Gratuït') . ($freeFrom > 0 && $shippingCost ? ' · gratuït a partir de ' . e(money($freeFrom)) : '') : 'Gratuït' ?></small></span>
                        </label>
                    <?php endforeach; ?>
                    <div class="shipping-fields"<?= $sel === 'shipping' ? '' : ' hidden' ?>>
                        <div class="field"><label for="o-address">Adreça *</label><input id="o-address" name="address" value="<?= e(old('address')) ?>" autocomplete="street-address"></div>
                        <div class="grid-2">
                            <div class="field"><label for="o-cp">Codi postal *</label><input id="o-cp" name="postal_code" value="<?= e(old('postal_code')) ?>" autocomplete="postal-code"></div>
                            <div class="field"><label for="o-city">Població *</label><input id="o-city" name="city" value="<?= e(old('city', 'La Granada')) ?>" autocomplete="address-level2"></div>
                        </div>
                    </div>
                    <div class="field"><label for="o-notes">Observacions</label><textarea id="o-notes" name="notes" rows="2" placeholder="P. ex. nom i curs de l'infant per lliurar-ho a l'escola"><?= e(old('notes')) ?></textarea></div>
                </fieldset>
                <?php if ($terms = setting('shop_terms')): ?>
                    <details class="terms"><summary>Condicions de venda, canvis i devolucions</summary><div class="prose"><?= $terms ?></div></details>
                <?php endif; ?>
                <label class="check"><input type="checkbox" name="accept" value="1" required> Accepto les condicions de venda i la <a href="<?= e(url(ltrim(setting('privacy_url', '/privacitat'), '/'))) ?>" target="_blank">política de privacitat</a></label>
            </div>
            <aside class="card pad cart-summary">
                <h2 class="h3">Resum</h2>
                <?php foreach ($cart['lines'] as $l): ?>
                    <div class="sum-row small"><span><?= (int)$l['qty'] ?> × <?= e($l['product']['name']) ?><?= $l['variant'] ? ' (' . e($l['variant']['name']) . ')' : '' ?></span><span><?= e(money($l['total'])) ?></span></div>
                <?php endforeach; ?>
                <div class="sum-row"><span>Subtotal</span><span><?= e(money($cart['subtotal'])) ?></span></div>
                <div class="sum-row"><span>Lliurament</span><span data-ship><?= $sel === 'shipping' && $shippingCost ? e(money($shippingCost)) : 'Gratuït' ?></span></div>
                <div class="sum-row total-row"><span>Total</span><strong data-grand><?= e(money($cart['subtotal'] + ($sel === 'shipping' ? $shippingCost : 0))) ?></strong></div>
                <button class="btn btn-accent btn-block btn-lg" type="submit">Pagar amb targeta</button>
                <p class="secure"><?= icon('shield', 'icon icon-sm') ?> Pagament segur amb Stripe (targeta, Apple Pay, Google Pay).</p>
                <a class="btn btn-ghost btn-block" href="<?= url('botiga/cistella') ?>">← Tornar a la cistella</a>
            </aside>
        </form>
    </div>
</section>
