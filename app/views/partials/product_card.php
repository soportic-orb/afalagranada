<?php defined('BASE_PATH') || exit; ?>
<?php $stock = Shop::productStock($p); ?>
<article class="card product-card">
    <a href="<?= url('botiga/producte/' . $p['slug']) ?>" class="card-media square">
        <?php if ($p['image']): ?><img src="<?= e(upload_url($p['image'])) ?>" alt="" loading="lazy"><?php else: ?><div class="media-placeholder"><?= icon('gift', 'icon icon-xl') ?></div><?php endif; ?>
        <?php if ($stock === 0): ?><span class="chip chip-dark">Esgotat</span><?php elseif ($stock !== null && $stock <= 5): ?><span class="chip chip-red">Últimes unitats</span><?php endif; ?>
    </a>
    <div class="card-body">
        <h3><a href="<?= url('botiga/producte/' . $p['slug']) ?>"><?= e($p['name']) ?></a></h3>
        <?php if ($p['summary']): ?><p><?= e(strip_to_text($p['summary'], 90)) ?></p><?php endif; ?>
        <div class="product-foot">
            <span class="price"><?= e(money((int)$p['price_cents'])) ?></span>
            <a class="btn btn-primary btn-small" href="<?= url('botiga/producte/' . $p['slug']) ?>"><?= $stock === 0 ? 'Veure' : 'Comprar' ?></a>
        </div>
    </div>
</article>
