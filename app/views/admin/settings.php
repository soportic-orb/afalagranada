<?php defined('BASE_PATH') || exit; ?>
<form method="post" enctype="multipart/form-data" class="panel settings-form">
    <?= csrf_field() ?>
    <?php foreach ($form['fields'] as $f):
        $val = isset($f['name']) ? setting($f['name'], $f['default'] ?? '') : null;
        partial('field', ['f' => $f, 'value' => $val]);
    endforeach; ?>
    <div class="form-actions">
        <button class="btn btn-primary" type="submit">Desar</button>
        <?php if ($formKey === 'settings'): ?><button class="btn" type="submit" name="test_email" value="1">Desar i enviar correu de prova</button><?php endif; ?>
        <?php if ($formKey === 'home'): ?><a class="btn btn-light" href="<?= url('/') ?>" target="_blank">Veure portada ↗</a> <a class="btn btn-light" href="<?= url('admin/r/blocks') ?>">Editar blocs de serveis i avantatges</a><?php endif; ?>
        <?php if ($formKey === 'app'): ?><a class="btn btn-light" href="<?= url('aplicacio') ?>" target="_blank">Veure pàgina ↗</a> <a class="btn btn-light" href="<?= url('admin/r/blocks?f=app') ?>">Editar funcionalitats de l'app</a><?php endif; ?>
    </div>
</form>
