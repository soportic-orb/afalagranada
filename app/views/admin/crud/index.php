<div class="toolbar">
    <form class="search" method="get">
        <?php if ($filter !== null): ?><input type="hidden" name="f" value="<?= e($filter) ?>"><?php endif; ?>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cercar...">
    </form>
    <?php if (isset($res['filter'])): ?>
        <div class="tabs">
            <a href="?" class="<?= !$filter || !isset($res['filter']['options'][$filter]) ? 'active' : '' ?>">Tots</a>
            <?php foreach ($res['filter']['options'] as $k => $lbl): ?><a href="?f=<?= e($k) ?>" class="<?= $filter === $k ? 'active' : '' ?>"><?= e($lbl) ?></a><?php endforeach; ?>
        </div>
    <?php endif; ?>
    <a class="btn btn-primary" href="<?= url('admin/r/' . $res['key'] . '/new') . ($filter ? '?f=' . e($filter) : '') ?>">+ Nova <?= e($res['singular']) ?></a>
</div>
<?php if (!empty($res['intro'])): ?><p class="muted"><?= e($res['intro']) ?></p><?php endif; ?>
<div class="panel flush">
    <table class="table list">
        <thead><tr>
            <?php foreach ($res['columns'] as $col): ?><th><?= e($col['label']) ?></th><?php endforeach; ?>
            <th></th>
        </tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr><td colspan="<?= count($res['columns']) + 1 ?>" class="muted center">No hi ha cap element.</td></tr><?php endif; ?>
        <?php foreach ($rows as $row): ?>
            <tr>
                <?php foreach ($res['columns'] as $i => $col): ?>
                    <td data-label="<?= e($col['label']) ?>"><?php if ($i === 1 || ($i === 0 && ($col['type'] ?? '') !== 'image' && !isset($col['render']))): ?><a href="<?= url('admin/r/' . $res['key'] . '/' . $row['id']) ?>"><strong><?= Crud::formatCell($col, $row) ?></strong></a><?php else: ?><?= Crud::formatCell($col, $row) ?><?php endif; ?></td>
                <?php endforeach; ?>
                <td class="actions">
                    <?php foreach ($res['row_actions'] ?? [] as $ra): ?><a class="btn btn-small" href="<?= e(($ra['url'])($row)) ?>"><?= e($ra['label']) ?></a><?php endforeach; ?>
                    <a class="btn btn-small" href="<?= url('admin/r/' . $res['key'] . '/' . $row['id']) ?>">Editar</a>
                    <?php if (isset($res['view_url'])): ?><a class="btn btn-small btn-light" href="<?= e(($res['view_url'])($row)) ?>" target="_blank" title="Veure a la web">↗</a><?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
