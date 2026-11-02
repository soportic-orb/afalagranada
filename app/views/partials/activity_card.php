<article class="card activity-card">
    <a href="<?= url('activitats/' . $a['slug']) ?>" class="card-media">
        <?php if ($a['image']): ?><img src="<?= e(upload_url($a['image'])) ?>" alt="" loading="lazy"><?php else: ?><div class="media-placeholder alt"><?= icon('calendar', 'icon icon-xl') ?></div><?php endif; ?>
        <?php if ($a['starts_at']): ?>
            <span class="date-badge"><b><?= date('j', strtotime($a['starts_at'])) ?></b><?= e(mb_substr(['gen','feb','març','abr','maig','juny','jul','ag','set','oct','nov','des'][(int)date('n', strtotime($a['starts_at'])) - 1], 0, 4)) ?></span>
        <?php endif; ?>
    </a>
    <div class="card-body">
        <h3><a href="<?= url('activitats/' . $a['slug']) ?>"><?= e($a['title']) ?></a></h3>
        <ul class="meta">
            <?php if ($a['starts_at']): ?><li><?= icon('clock', 'icon icon-sm') ?> <?= e(fmt_date($a['starts_at'], true)) ?></li><?php endif; ?>
            <?php if ($a['location']): ?><li><?= icon('pin', 'icon icon-sm') ?> <?= e($a['location']) ?></li><?php endif; ?>
            <li><?= icon('tag', 'icon icon-sm') ?> <?= $a['price_cents'] ? e(money((int)$a['price_cents'])) . ($a['member_price_cents'] !== null ? ' · socis ' . e(money((int)$a['member_price_cents'])) : '') : 'Gratuïta' ?></li>
        </ul>
        <p><?= e(strip_to_text($a['summary'], 120)) ?></p>
        <a class="btn btn-outline btn-block" href="<?= url('activitats/' . $a['slug']) ?>">Més informació</a>
    </div>
</article>
