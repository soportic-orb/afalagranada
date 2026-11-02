<?php defined('BASE_PATH') || exit; ?>
<?php
$u = Auth::user();
$nav = [
    ['admin', 'Tauler', 'dashboard', 'star'],
    ['_h', 'Contingut', 'content'],
    ['admin/home', 'Portada i textos', 'content', 'sun'],
    ['admin/headers', 'Imatges de capçalera', 'content', 'image'],
    ['admin/r/pages', 'Pàgines', 'content', 'book'],
    ['admin/r/blocks', 'Blocs (serveis, avantatges)', 'content', 'palette'],
    ['admin/r/posts', 'Notícies', 'content', 'megaphone'],
    ['admin/app', 'App de l\'AFA', 'content', 'phone'],
    ['_h', 'Gestió', 'campaigns'],
    ['admin/r/campaigns', 'Campanyes', 'campaigns', 'heart'],
    ['admin/r/activities', 'Activitats', 'activities', 'calendar'],
    ['admin/checkin', 'Control d\'accés (QR)', 'checkin', 'check'],
    ['_h', 'Botiga', 'shop'],
    ['admin/orders', 'Comandes', 'shop', 'tag'],
    ['admin/r/products', 'Productes i estoc', 'shop', 'gift'],
    ['admin/shop-settings', 'Configuració de la botiga', 'shop', 'shield'],
    ['admin/messages', 'Missatges de contacte', 'messages', 'mail'],
    ['_h', 'Sistema', 'settings'],
    ['admin/settings', 'Configuració', 'settings', 'shield'],
    ['admin/r/users', 'Usuaris', 'users', 'users'],
    ['admin/updates', 'Actualitzacions', 'updates', 'leaf'],
    ['admin/log', 'Registre', 'users', 'clock'],
];
$cur = trim(current_path(), '/');
?><!doctype html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= e($title ?? 'Administració') ?> · Gestió AFA</title>
    <link rel="icon" href="<?= asset('img/favicon.png') ?>" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700&family=Nunito:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body class="admin">
<aside class="sidebar" id="sidebar">
    <a class="sb-brand" href="<?= url('admin') ?>"><img src="<?= asset('img/logo-afa.png') ?>" alt="AFA Jacint Verdaguer"> <span>Panell de gestió</span></a>
    <nav>
        <?php foreach ($nav as $item):
            if (!Auth::can($item[2])) continue;
            if ($item[0] === '_h'): ?>
                <div class="sb-head"><?= e($item[1]) ?></div>
            <?php else:
                $active = $item[0] === 'admin' ? $cur === 'admin' : str_starts_with($cur, $item[0]) ||
                    ($item[0] === 'admin/r/campaigns' && str_starts_with($cur, 'admin/campaigns')) ||
                    ($item[0] === 'admin/r/activities' && str_starts_with($cur, 'admin/activities')) ||
                    ($item[0] === 'admin/r/products' && str_starts_with($cur, 'admin/products')); ?>
                <a href="<?= url($item[0]) ?>" class="<?= $active ? 'active' : '' ?>"><?= icon($item[3], 'icon icon-sm') ?> <?= e($item[1]) ?><?php if ($item[0] === 'admin/messages' && ($nUnread = unread_messages())): ?><span class="nav-count"><?= $nUnread ?></span><?php endif; ?><?php if ($item[0] === 'admin/orders' && ($nOrd = orders_to_prepare())): ?><span class="nav-count"><?= $nOrd ?></span><?php endif; ?></a>
            <?php endif; endforeach; ?>
    </nav>
    <div class="sb-foot">
        <a href="<?= url('/') ?>" target="_blank">Veure la web ↗</a>
        <span class="muted">v<?= e(Updater::currentVersion()) ?></span>
    </div>
</aside>
<div class="main">
    <header class="topbar">
        <button class="sb-toggle" onclick="document.body.classList.toggle('sb-open')" aria-label="Menú">☰</button>
        <h1><?= e($title ?? '') ?></h1>
        <div class="user">
            <span><?= e($u['name'] ?? '') ?> <small class="muted">(<?= e(Auth::ROLES[$u['role']] ?? '') ?>)</small></span>
            <form method="post" action="<?= url('admin/logout') ?>"><?= csrf_field() ?><button class="btn btn-small btn-light">Sortir</button></form>
        </div>
    </header>
    <div class="content">
        <?php foreach (take_flashes() as $f): ?>
            <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
        <?php endforeach; ?>
        <?= $content ?>
    </div>
</div>
<script>window.AFA = {csrf: <?= json_encode(csrf_token()) ?>, uploadUrl: <?= json_encode(url('admin/upload')) ?>};</script>
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script src="<?= asset('js/admin.js') ?>"></script>
</body>
</html>
