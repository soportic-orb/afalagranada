<?php defined('BASE_PATH') || exit; ?>
<?php
// Dues captures de l'app: una al davant i una altra al darrere, una mica inclinada.
$front = setting('app_image', '');
$back = setting('app_image_2', '');
$frontSrc = $front ? upload_url($front) : asset('img/phone.svg');
$backSrc = $back ? upload_url($back) : ($front ? '' : asset('img/phone-2.svg'));
$appName = setting('app_name', 'Abaco');
?>
<div class="app-screens<?= $backSrc ? '' : ' single' ?>"<?= !empty($decorative) ? ' aria-hidden="true"' : '' ?>>
    <?php if ($backSrc): ?>
        <img class="screen screen-back" src="<?= e($backSrc) ?>" alt="<?= empty($decorative) ? e('Captura de l\'app ' . $appName) : '' ?>" loading="lazy">
    <?php endif; ?>
    <img class="screen screen-front" src="<?= e($frontSrc) ?>" alt="<?= empty($decorative) ? e('Captura de l\'app ' . $appName) : '' ?>" loading="lazy">
</div>
