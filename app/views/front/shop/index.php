<?php defined('BASE_PATH') || exit; ?>
<section class="page-hero small<?= hero_bg('shop') ?>">
    <div class="container">
        <p class="eyebrow">Botiga</p>
        <h1><?= e(setting('shop_title', 'Botiga de l\'AFA')) ?></h1>
        <?php if ($t = setting('shop_intro')): ?><p class="lead"><?= e($t) ?></p><?php endif; ?>
    </div>
</section>
<?php partial('shop_bar'); ?>
<section class="section">
    <div class="container">
        <?php if (!Shop::enabled()): ?><div class="alert alert-warn">La botiga està desactivada: només la veus perquè has iniciat sessió al panell.</div><?php endif; ?>
        <?php if (!$products): ?><p class="empty">Encara no hi ha productes a la botiga. Torna aviat!</p><?php endif; ?>
        <div class="grid grid-products">
            <?php foreach ($products as $p) { partial('product_card', ['p' => $p]); } ?>
        </div>
    </div>
</section>
