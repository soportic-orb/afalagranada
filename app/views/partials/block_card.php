<div class="feature">
    <div class="feature-icon"><?= icon($b['icon'] ?: 'star') ?></div>
    <h3><?= e($b['title']) ?></h3>
    <div class="feature-body"><?= $b['body'] ?></div>
    <?php if ($b['link_url']): ?><a class="link-arrow" href="<?= e(str_starts_with($b['link_url'], '/') ? url($b['link_url']) : $b['link_url']) ?>">Més informació →</a><?php endif; ?>
</div>
