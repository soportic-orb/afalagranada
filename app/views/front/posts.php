<?php defined('BASE_PATH') || exit; ?>
<section class="page-hero small"><div class="container"><h1>Notícies</h1><p class="lead">El que passa a l'AFA i a l'escola.</p></div></section>
<section class="section">
    <div class="container">
        <?php if (!$posts): ?><p class="empty">Encara no hi ha notícies publicades.</p><?php endif; ?>
        <div class="grid grid-3">
            <?php foreach ($posts as $p): ?>
                <article class="card post-card">
                    <?php if ($p['image']): ?><a class="card-media" href="<?= url('noticies/' . $p['slug']) ?>"><img src="<?= e(upload_url($p['image'])) ?>" alt="" loading="lazy"></a><?php endif; ?>
                    <div class="card-body">
                        <time class="muted"><?= e(fmt_date($p['published_at'])) ?></time>
                        <h3><a href="<?= url('noticies/' . $p['slug']) ?>"><?= e($p['title']) ?></a></h3>
                        <p><?= e($p['excerpt'] ?: strip_to_text($p['content'], 140)) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
