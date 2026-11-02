<?php defined('BASE_PATH') || exit; ?>
<?php $stats = campaign_stats($c); ?>
<article class="card campaign-card">
    <a href="<?= url('campanyes/' . $c['slug']) ?>" class="card-media">
        <?php if ($c['image']): ?><img src="<?= e(upload_url($c['image'])) ?>" alt="" loading="lazy"><?php else: ?><div class="media-placeholder"><?= icon('heart', 'icon icon-xl') ?></div><?php endif; ?>
        <?php if ($c['status'] === 'closed'): ?><span class="chip chip-dark">Finalitzada</span><?php endif; ?>
    </a>
    <div class="card-body">
        <h3><a href="<?= url('campanyes/' . $c['slug']) ?>"><?= e($c['title']) ?></a></h3>
        <p><?= e(strip_to_text($c['summary'], 140)) ?></p>
        <?php partial('progress', ['stats' => $stats, 'compact' => true]); ?>
        <a class="btn btn-red btn-block" href="<?= url('campanyes/' . $c['slug']) ?>"><?= $c['status'] === 'active' ? 'Col·labora-hi' : 'Veure campanya' ?></a>
    </div>
</article>
