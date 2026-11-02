<?php defined('BASE_PATH') || exit; ?>
<?php partial('shop_bar'); ?>
<section class="section">
    <div class="container">
        <h1 class="h2">La teva cistella</h1>
        <?php if (input('cancel')): ?><div class="alert alert-info">Has cancel·lat el pagament. Els productes continuen a la cistella.</div><?php endif; ?>
        <?php if (!$cart['lines']): ?>
            <p class="empty">La cistella és buida. <a href="<?= url('botiga') ?>">Veure productes</a></p>
        <?php else: ?>
        <form method="post" action="<?= url('botiga/cistella') ?>" class="cart-layout">
            <?= csrf_field() ?>
            <div class="card pad cart-lines">
                <?php foreach ($cart['lines'] as $l): ?>
                    <div class="cart-line">
                        <a href="<?= url('botiga/producte/' . $l['product']['slug']) ?>" class="cart-thumb">
                            <?php if ($l['product']['image']): ?><img src="<?= e(upload_url($l['product']['image'])) ?>" alt=""><?php else: ?><span><?= icon('gift', 'icon') ?></span><?php endif; ?>
                        </a>
                        <div class="cart-desc">
                            <strong><?= e($l['product']['name']) ?></strong>
                            <?php if ($l['variant']): ?><small><?= e(($l['product']['variant_label'] ?: 'Opció') . ': ' . $l['variant']['name']) ?></small><?php endif; ?>
                            <small><?= e(money($l['unit'])) ?> / unitat</small>
                        </div>
                        <input type="number" name="qty[<?= e($l['key']) ?>]" value="<?= (int)$l['qty'] ?>" min="0" max="<?= (int)$l['limit'] ?>" aria-label="Quantitat" class="cart-qty">
                        <span class="cart-total"><?= e(money($l['total'])) ?></span>
                        <button class="cart-remove" name="remove" value="<?= e($l['key']) ?>" aria-label="Treure">×</button>
                    </div>
                <?php endforeach; ?>
                <button class="btn btn-ghost btn-small" type="submit">Actualitzar quantitats</button>
            </div>
            <aside class="card pad cart-summary">
                <div class="sum-row"><span>Subtotal</span><strong><?= e(money($cart['subtotal'])) ?></strong></div>
                <p class="muted small">L'enviament o la recollida es trien al pas següent.</p>
                <button class="btn btn-accent btn-block btn-lg" type="submit" name="checkout" value="1">Finalitzar la comanda</button>
                <a class="btn btn-ghost btn-block" href="<?= url('botiga') ?>">Continuar comprant</a>
            </aside>
        </form>
        <?php endif; ?>
    </div>
</section>
