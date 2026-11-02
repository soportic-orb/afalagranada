<?php defined('BASE_PATH') || exit; ?>
<div class="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int)$stats['percent'] ?>" aria-label="Percentatge de la fita assolit">
    <div class="progress-bar" style="width: <?= (int)$stats['percent'] ?>%"></div>
</div>
<div class="progress-meta">
    <div><strong><?= e(money($stats['raised'], false)) ?></strong><span>recaptats<?= $stats['goal'] ? ' de ' . e(money($stats['goal'], false)) : '' ?></span></div>
    <div><strong><?= (int)$stats['percent_real'] ?>%</strong><span>de la fita</span></div>
    <div><strong><?= (int)$stats['count'] ?></strong><span><?= $stats['count'] === 1 ? 'donació' : 'donacions' ?></span></div>
    <?php if ($stats['days_left'] !== null && $stats['days_left'] >= 0 && empty($compact)): ?>
        <div><strong><?= (int)$stats['days_left'] ?></strong><span><?= $stats['days_left'] === 1 ? 'dia restant' : 'dies restants' ?></span></div>
    <?php endif; ?>
</div>
