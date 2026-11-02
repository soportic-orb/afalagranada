<?php defined('BASE_PATH') || exit; ?>
<?php
$hasVariants = (bool)$variants;
$stock = $hasVariants ? null : Shop::available($p);
$soldOut = $hasVariants ? Shop::productStock($p) === 0 : $stock === 0;
?>
<?php partial('shop_bar'); ?>
<section class="section product-page">
    <div class="container">
        <p class="eyebrow"><a href="<?= url('botiga') ?>">← Botiga</a></p>
        <div class="product-layout">
            <div class="product-media">
                <?php if ($p['image']): ?><img class="rounded" src="<?= e(upload_url($p['image'])) ?>" alt="<?= e($p['name']) ?>"><?php else: ?><div class="media-placeholder rounded square"><?= icon('gift', 'icon icon-xl') ?></div><?php endif; ?>
            </div>
            <div class="product-info">
                <h1 class="h2"><?= e($p['name']) ?></h1>
                <p class="price big"><?= e(money((int)$p['price_cents'])) ?></p>
                <?php if ($p['summary']): ?><p class="lead"><?= e($p['summary']) ?></p><?php endif; ?>
                <?php if ($soldOut): ?>
                    <div class="alert alert-info">Aquest producte està esgotat.</div>
                <?php else: ?>
                <form method="post" action="<?= url('botiga/cistella/afegir') ?>" class="form add-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                    <?php if ($hasVariants): ?>
                        <fieldset>
                            <legend><?= e($p['variant_label'] ?: 'Opció') ?></legend>
                            <div class="variant-options">
                                <?php foreach ($variants as $v): $vs = Shop::available($p, $v); ?>
                                    <label class="variant<?= $vs === 0 ? ' disabled' : '' ?>">
                                        <input type="radio" name="variant_id" value="<?= (int)$v['id'] ?>" <?= $vs === 0 ? 'disabled' : '' ?> required>
                                        <span><?= e($v['name']) ?><?= $vs === 0 ? ' <small>esgotat</small>' : '' ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>
                    <?php elseif ($stock !== null && $stock <= 5): ?>
                        <p class="stock-low">Només en queden <?= $stock ?>.</p>
                    <?php endif; ?>
                    <div class="add-row">
                        <label class="qty-field">Quantitat <input type="number" name="qty" value="1" min="1" max="<?= (int)min((int)$p['max_per_order'] ?: 99, $stock ?? 99) ?>"></label>
                        <button class="btn btn-accent btn-lg" type="submit">Afegir a la cistella</button>
                    </div>
                    <button class="btn btn-ghost" type="submit" name="then" value="cart">Afegir i anar a la cistella</button>
                </form>
                <?php endif; ?>
                <div class="prose"><?= $p['description'] ?></div>
            </div>
        </div>
    </div>
</section>
