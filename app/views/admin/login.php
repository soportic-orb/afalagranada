<!doctype html>
<html lang="ca"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>Accés · Gestió AFA</title><link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml"><link rel="stylesheet" href="<?= asset('css/admin.css') ?>"></head>
<body class="login-page">
<form method="post" class="login-card">
    <img src="<?= asset('img/logo.svg') ?>" alt="" width="64" height="64">
    <h1>Gestió AFA</h1>
    <p class="muted"><?= e(setting('site_name', 'AFA Escola Jacint Verdaguer')) ?></p>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <?php foreach (take_flashes() as $f): ?><div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div><?php endforeach; ?>
    <?= csrf_field() ?>
    <label>Correu electrònic<input type="email" name="email" required autofocus autocomplete="username" value="<?= e(input('email')) ?>"></label>
    <label>Contrasenya<input type="password" name="password" required autocomplete="current-password"></label>
    <button class="btn btn-primary btn-block">Entrar</button>
    <a href="<?= url('/') ?>" class="muted small">← Tornar a la web</a>
</form>
</body></html>
