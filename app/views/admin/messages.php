<?php defined('BASE_PATH') || exit; ?>
<div class="toolbar">
    <div class="tabs">
        <a href="?f=all" class="<?= $filter !== 'unread' ? 'active' : '' ?>">Tots</a>
        <a href="?f=unread" class="<?= $filter === 'unread' ? 'active' : '' ?>">No llegits<?= $unread ? ' (' . $unread . ')' : '' ?></a>
    </div>
    <a class="btn" href="?f=<?= e($filter) ?>&export=csv">Exportar CSV</a>
    <a class="btn btn-light" href="<?= url('contacte') ?>" target="_blank">Veure formulari ↗</a>
</div>
<?php $to = setting('contact_form_email', setting('contact_email', '')); ?>
<p class="muted small"><?= $to ? 'Els missatges nous s\'envien a <strong>' . e($to) . '</strong>.' : '<strong>No hi ha cap correu configurat</strong>: els missatges només es desen aquí.' ?>
    <?php if (Auth::can('settings')): ?><a href="<?= url('admin/settings') ?>">Canviar</a><?php endif; ?></p>
<div class="panel flush">
<table class="table list">
    <thead><tr><th>Data</th><th>De</th><th>Motiu</th><th>Missatge</th><th></th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="5" class="center muted">No hi ha missatges.</td></tr><?php endif; ?>
    <?php foreach ($rows as $m): ?>
        <tr class="<?= $m['read_at'] ? '' : 'unread' ?>">
            <td data-label="Data"><?= e(date('d/m/Y H:i', strtotime($m['created_at']))) ?></td>
            <td data-label="De"><a href="<?= url('admin/messages/' . $m['id']) ?>"><?= $m['read_at'] ? '' : '<span class="dot"></span>' ?><strong><?= e($m['name']) ?></strong></a><br><small class="muted"><?= e($m['email']) ?></small></td>
            <td data-label="Motiu"><?= e($m['subject']) ?></td>
            <td data-label="Missatge"><?= e(strip_to_text($m['message'], 90)) ?><?= $m['email_sent'] ? '' : ' <span class="badge badge-warn" title="No s\'ha pogut enviar per correu">no enviat</span>' ?></td>
            <td class="actions"><a class="btn btn-small" href="<?= url('admin/messages/' . $m['id']) ?>">Obrir</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
