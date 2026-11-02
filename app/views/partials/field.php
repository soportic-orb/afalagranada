<?php defined('BASE_PATH') || exit; ?>
<?php
$type = $f['type'] ?? 'text';
$name = $f['name'] ?? '';
$id = 'f-' . $name;
$req = !empty($f['required']) ? 'required' : '';
if ($type === 'heading'): ?>
    <h2 class="form-heading"><?= e($f['label']) ?></h2>
<?php elseif ($type === 'info'): ?>
    <p class="help-box"><?= $f['label'] ?></p>
<?php elseif ($type === 'checkbox'): ?>
    <label class="check"><input type="checkbox" name="<?= e($name) ?>" value="1" <?= $value ? 'checked' : '' ?>> <?= e($f['label']) ?></label>
<?php else: ?>
<div class="field">
    <label for="<?= e($id) ?>"><?= e($f['label']) ?><?= $req ? ' *' : '' ?></label>
    <?php switch ($type):
        case 'textarea': ?>
            <textarea id="<?= e($id) ?>" name="<?= e($name) ?>" rows="3" <?= $req ?>><?= e($value) ?></textarea>
        <?php break; case 'html': ?>
            <div class="editor <?= !empty($f['small']) ? 'small' : '' ?>" data-target="<?= e($id) ?>"></div>
            <textarea id="<?= e($id) ?>" name="<?= e($name) ?>" class="editor-source" hidden><?= e($value) ?></textarea>
            <?php if (empty($f['small'])): ?><p class="editor-hint">Cada salt de línia es mostra igual a la web. Per separar paràgrafs, deixa una línia en blanc.</p><?php endif; ?>
        <?php break; case 'image': ?>
            <?php if ($value): ?><div class="img-preview"><img src="<?= e(upload_url($value)) ?>" alt=""><label class="check small"><input type="checkbox" name="<?= e($name) ?>__remove" value="1"> Treure la imatge</label></div><?php endif; ?>
            <input type="file" id="<?= e($id) ?>" name="<?= e($name) ?>" accept="image/jpeg,image/png,image/webp,image/gif">
        <?php break; case 'select': ?>
            <select id="<?= e($id) ?>" name="<?= e($name) ?>">
                <?php foreach ($f['options'] as $k => $lbl): ?><option value="<?= e($k) ?>" <?= (string)$value === (string)$k ? 'selected' : '' ?>><?= e($lbl) ?></option><?php endforeach; ?>
            </select>
        <?php break; case 'icon': ?>
            <div class="icon-picker">
                <?php foreach (icon_names() as $ic): ?>
                    <label title="<?= e($ic) ?>"><input type="radio" name="<?= e($name) ?>" value="<?= e($ic) ?>" <?= ($value ?: 'star') === $ic ? 'checked' : '' ?>><span><?= icon($ic, 'icon icon-sm') ?></span></label>
                <?php endforeach; ?>
            </div>
        <?php break; case 'money': ?>
            <div class="input-suffix"><input type="text" inputmode="decimal" id="<?= e($id) ?>" name="<?= e($name) ?>" value="<?= e(($value === null || $value === '') ? '' : cents_to_input((int)$value)) ?>" <?= $req ?>><span>€</span></div>
        <?php break; case 'number': ?>
            <input type="number" id="<?= e($id) ?>" name="<?= e($name) ?>" value="<?= e($value) ?>" <?= $req ?>>
        <?php break; case 'date': ?>
            <input type="date" id="<?= e($id) ?>" name="<?= e($name) ?>" value="<?= e($value ? substr((string)$value, 0, 10) : '') ?>" <?= $req ?>>
        <?php break; case 'datetime': ?>
            <input type="datetime-local" id="<?= e($id) ?>" name="<?= e($name) ?>" value="<?= e(dt_input($value)) ?>" <?= $req ?>>
        <?php break; case 'password': ?>
            <input type="password" id="<?= e($id) ?>" name="<?= e($name) ?>" autocomplete="new-password" minlength="10">
        <?php break; case 'secret': ?>
            <input type="password" id="<?= e($id) ?>" name="<?= e($name) ?>" autocomplete="off" placeholder="<?= $value ? '•••••••• (desat — deixa en blanc per mantenir-lo)' : '' ?>">
        <?php break; case 'slug': ?>
            <input type="text" id="<?= e($id) ?>" name="<?= e($name) ?>" value="<?= e($value) ?>" pattern="[a-z0-9\-]*" placeholder="automàtic">
        <?php break; default: ?>
            <input type="<?= in_array($type, ['email', 'url'], true) ? ($type === 'url' ? 'text' : 'email') : 'text' ?>" id="<?= e($id) ?>" name="<?= e($name) ?>" value="<?= e($value) ?>" <?= $req ?> <?= isset($f['maxlength']) ? 'maxlength="' . (int)$f['maxlength'] . '"' : '' ?>>
    <?php endswitch; ?>
    <?php if (!empty($f['help'])): ?><small class="help"><?= e($f['help']) ?></small><?php endif; ?>
</div>
<?php endif; ?>
