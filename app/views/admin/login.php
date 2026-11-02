<!doctype html>
<html lang="ca"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>Accés · Gestió AFA</title><link rel="icon" href="<?= asset('img/favicon.png') ?>" type="image/png"><link rel="stylesheet" href="<?= asset('css/admin.css') ?>"></head>
<body class="login-page">
<form method="post" class="login-card">
    <img class="login-logo" src="<?= asset('img/logo-afa.png') ?>" alt="AFA Jacint Verdaguer · La Granada">
    <h1>Panell de gestió</h1>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <?php foreach (take_flashes() as $f): ?><div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div><?php endforeach; ?>
    <?= csrf_field() ?>
    <label>Correu electrònic<input type="email" name="email" required autofocus autocomplete="username" value="<?= e(input('email')) ?>"></label>
    <label>Contrasenya<input type="password" name="password" required autocomplete="current-password"></label>
    <button class="btn btn-primary btn-block">Entrar</button>
    <a href="<?= url('/') ?>" class="muted small">← Tornar a la web</a>
</form>
</body></html>
