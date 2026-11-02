<?php
$main = array_filter($res['fields'], fn($f) => empty($f['side']));
$side = array_filter($res['fields'], fn($f) => !empty($f['side']));
?>
<p><a href="<?= url('admin/r/' . $res['key']) ?>">← <?= e($res['title']) ?></a></p>
<?php if ($errors): ?><div class="alert alert-error"><?= implode('<br>', array_map('e', $errors)) ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="form-grid">
    <?= csrf_field() ?>
    <div class="panel">
        <?php foreach ($main as $f) { partial('field', ['f' => $f, 'value' => $row[$f['name'] ?? ''] ?? null]); } ?>
    </div>
    <div class="side">
        <div class="panel">
            <?php foreach ($side as $f) { partial('field', ['f' => $f, 'value' => $row[$f['name'] ?? ''] ?? null]); } ?>
            <button class="btn btn-primary btn-block" type="submit"><?= $isNew ? 'Crear' : 'Desar canvis' ?></button>
            <?php if (!$isNew && isset($res['view_url'])): ?><a class="btn btn-block btn-light" href="<?= e(($res['view_url'])($row)) ?>" target="_blank">Veure a la web ↗</a><?php endif; ?>
            <?php if (!$isNew): foreach ($res['row_actions'] ?? [] as $ra): ?><a class="btn btn-block" href="<?= e(($ra['url'])($row)) ?>"><?= e($ra['label']) ?></a><?php endforeach; endif; ?>
        </div>
    </div>
</form>
<?php if (!$isNew): ?>
<form method="post" action="<?= url('admin/r/' . $res['key'] . '/' . $row['id'] . '/delete') ?>" class="danger-zone" onsubmit="return confirm('Segur que vols eliminar aquest element? Aquesta acció no es pot desfer.')">
    <?= csrf_field() ?>
    <button class="btn btn-danger btn-small">Eliminar <?= e($res['singular']) ?></button>
</form>
<?php endif; ?>
