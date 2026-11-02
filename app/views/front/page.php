<?php defined('BASE_PATH') || exit; ?>
<section class="page-hero<?= hero_bg('pages', $page['image'] ?: null) ?>">
    <div class="container">
        <h1><?= e($page['title']) ?></h1>
        <?php if ($page['excerpt']): ?><p class="lead"><?= e($page['excerpt']) ?></p><?php endif; ?>
    </div>
</section>
<section class="section">
    <div class="container narrow prose"><?= $page['content'] ?></div>
</section>
