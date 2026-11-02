<?php defined('BASE_PATH') || exit; ?>
<section class="page-hero small center">
    <div class="container narrow">
        <div class="big-icon"><?= icon('heart', 'icon icon-xl') ?></div>
        <h1>Moltes gràcies!</h1>
        <?php if ($donation && $donation['status'] === 'paid'): ?>
            <p class="lead">Hem rebut la teva aportació de <strong><?= e(money((int)$donation['amount_cents'])) ?></strong> a la campanya «<?= e($c['title']) ?>». T'hem enviat un correu de confirmació.</p>
        <?php else: ?>
            <p class="lead">Estem confirmant el pagament. En uns minuts rebràs un correu de confirmació.</p>
        <?php endif; ?>
        <div class="card pad"><?php partial('progress', ['stats' => $stats]); ?></div>
        <p><a class="btn btn-primary" href="<?= url('campanyes/' . $c['slug']) ?>">Torna a la campanya</a></p>
    </div>
</section>
