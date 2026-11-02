<?php defined('BASE_PATH') || exit; ?>
<section class="page-hero small<?= hero_bg('campaigns') ?>">
    <div class="container">
        <p class="eyebrow">Micromecenatge</p>
        <h1>Campanyes</h1>
        <p class="lead">Projectes que fem realitat entre totes les famílies. Cada aportació compta!</p>
    </div>
</section>
<section class="section">
    <div class="container">
        <?php if (!$campaigns): ?><p class="empty">Ara mateix no hi ha cap campanya activa. Torna aviat!</p><?php endif; ?>
        <div class="grid grid-3">
            <?php foreach ($campaigns as $c) { partial('campaign_card', ['c' => $c]); } ?>
        </div>
    </div>
</section>
