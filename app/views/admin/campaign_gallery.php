<?php defined('BASE_PATH') || exit; ?>
<p><a href="<?= url('admin/r/campaigns/' . $c['id']) ?>">← Tornar a la campanya</a></p>
<div class="panel">
    <h2>Afegir imatges</h2>
    <form method="post" enctype="multipart/form-data" class="inline-form">
        <?= csrf_field() ?><input type="hidden" name="action" value="upload">
        <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp,image/gif" required>
        <button class="btn btn-primary">Pujar</button>
    </form>
</div>
<?php if ($images): ?>
<form method="post" class="panel">
    <?= csrf_field() ?><input type="hidden" name="action" value="save">
    <div class="gallery-admin">
        <?php foreach ($images as $img): ?>
            <div class="gitem">
                <img src="<?= e(upload_url($img['path'])) ?>" alt="">
                <input name="caption[<?= (int)$img['id'] ?>]" value="<?= e($img['caption']) ?>" placeholder="Peu de foto">
                <div class="row"><label>Ordre <input type="number" name="sort[<?= (int)$img['id'] ?>]" value="<?= (int)$img['sort_order'] ?>"></label>
                <button class="btn btn-small btn-danger" form="del-<?= (int)$img['id'] ?>">Eliminar</button></div>
            </div>
        <?php endforeach; ?>
    </div>
    <button class="btn btn-primary">Desar canvis</button>
</form>
<?php foreach ($images as $img): ?>
    <form id="del-<?= (int)$img['id'] ?>" method="post" onsubmit="return confirm('Eliminar la imatge?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="image_id" value="<?= (int)$img['id'] ?>"></form>
<?php endforeach; ?>
<?php else: ?><p class="muted">Encara no hi ha imatges a la galeria.</p><?php endif; ?>
