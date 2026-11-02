<?php defined('BASE_PATH') || exit; ?>
<section class="page-hero small">
    <div class="container narrow">
        <p class="eyebrow"><a href="<?= url('noticies') ?>">← Notícies</a> · <?= e(fmt_date($post['published_at'])) ?></p>
        <h1><?= e($post['title']) ?></h1>
        <?php if ($post['excerpt']): ?><p class="lead"><?= e($post['excerpt']) ?></p><?php endif; ?>
    </div>
</section>
<section class="section">
    <div class="container narrow prose">
        <?php if ($post['image']): ?><img class="rounded" src="<?= e(upload_url($post['image'])) ?>" alt=""><?php endif; ?>
        <?= $post['content'] ?>
    </div>
</section>
