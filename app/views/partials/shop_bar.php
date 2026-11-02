<?php defined('BASE_PATH') || exit; ?>
<?php $n = Shop::cartCount(); ?>
<div class="shop-bar">
    <div class="container shop-bar-inner">
        <a href="<?= url('botiga') ?>" class="shop-bar-title"><?= icon('gift', 'icon icon-sm') ?> <?= e(setting('shop_title', 'Botiga de l\'AFA')) ?></a>
        <nav>
            <a href="<?= url('botiga/seguiment') ?>" class="<?= is_active_path('/botiga/seguiment') ? 'active' : '' ?>">Seguir la meva comanda</a>
            <a href="<?= url('botiga/cistella') ?>" class="cart-link <?= is_active_path('/botiga/cistella') ? 'active' : '' ?>" aria-label="Cistella, <?= $n ?> productes">
                <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>
                Cistella <span class="cart-count<?= $n ? '' : ' empty' ?>"><?= $n ?></span>
            </a>
        </nav>
    </div>
</div>
