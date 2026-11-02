<?php defined('BASE_PATH') || exit; ?>
<div class="panel flush">
<table class="table list">
    <thead><tr><th>Data</th><th>Usuari</th><th>Acció</th><th>Detalls</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr><td><?= e(date('d/m/Y H:i', strtotime($r['created_at']))) ?></td><td><?= e($r['name'] ?? '—') ?></td><td><code><?= e($r['action']) ?></code></td><td><small><?= e(mb_substr((string)$r['details'], 0, 200)) ?></small></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
