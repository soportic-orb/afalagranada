<?php defined('BASE_PATH') || exit; ?>
<?php
$siteName = setting('site_name', 'AFA Escola Jacint Verdaguer');
$tagline = setting('site_tagline', 'La Granada');
$pageTitle = ($title ?? '') !== '' ? $title . ' · ' . $siteName : $siteName . ' — ' . $tagline;
$desc = $metaDescription ?? setting('hero_subtitle', 'Associació de Famílies d\'Alumnes de l\'Escola Jacint Verdaguer de La Granada.');
$menuPages = [];
try {
    $menuPages = db()->all('SELECT title, slug FROM pages WHERE published = 1 AND show_in_menu = 1 ORDER BY menu_order, title LIMIT 6');
} catch (Throwable $e) {
}
$logo = setting('logo', '');
?><!doctype html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e(strip_to_text($desc, 160)) ?>">
    <?php if (!empty($noindex)): ?><meta name="robots" content="noindex"><?php endif; ?>
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e(strip_to_text($desc, 200)) ?>">
    <meta property="og:type" content="website">
    <meta property="og:image" content="<?= e($ogImage ?? abs_url('assets/img/logo-afa@2x.png')) ?>">
    <link rel="icon" href="<?= asset('img/favicon.png') ?>" type="image/png">
    <link rel="apple-touch-icon" href="<?= asset('img/apple-touch-icon.png') ?>">
    <meta name="theme-color" content="#078838">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/site.css') ?>">
</head>
<body>
<a class="skip" href="#main">Salta al contingut</a>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= url('/') ?>">
            <?php if ($logo): ?>
                <img src="<?= e(upload_url($logo)) ?>" alt="<?= e($siteName) ?>" class="brand-logo custom">
            <?php else: ?>
                <img src="<?= asset('img/logo-afa.png') ?>" srcset="<?= asset('img/logo-afa.png') ?> 1x, <?= asset('img/logo-afa@2x.png') ?> 2x" alt="AFA Jacint Verdaguer · La Granada" class="brand-logo" width="221" height="60">
            <?php endif; ?>
        </a>
        <button class="nav-toggle" aria-expanded="false" aria-controls="nav" aria-label="Menú">
            <span></span><span></span><span></span>
        </button>
        <nav id="nav" class="nav">
            <a href="<?= url('/') ?>" class="<?= is_active_path('/') ? 'active' : '' ?>">Inici</a>
            <?php foreach ($menuPages as $mp): ?>
                <a href="<?= url($mp['slug']) ?>" class="<?= is_active_path('/' . $mp['slug']) ? 'active' : '' ?>"><?= e($mp['title']) ?></a>
            <?php endforeach; ?>
            <a href="<?= url('activitats') ?>" class="<?= is_active_path('/activitats') ? 'active' : '' ?>">Activitats</a>
            <a href="<?= url('campanyes') ?>" class="<?= is_active_path('/campanyes') ? 'active' : '' ?>">Campanyes</a>
            <a href="<?= url('contacte') ?>" class="<?= is_active_path('/contacte') ? 'active' : '' ?>">Contacte</a>
            <a href="<?= url('aplicacio') ?>" class="nav-cta <?= is_active_path('/aplicacio') ? 'active' : '' ?>"><?= icon('phone', 'icon icon-sm') ?> L'app</a>
        </nav>
    </div>
</header>

<main id="main">
    <?php $flashes = take_flashes(); if ($flashes): ?>
        <div class="container flashes">
            <?php foreach ($flashes as $f): ?>
                <div class="alert alert-<?= e($f['type']) ?>" role="status"><?= e($f['message']) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?= $content ?>
</main>

<div class="tricolor" aria-hidden="true"></div>
<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <img class="footer-logo" src="<?= $logo ? e(upload_url($logo)) : asset('img/logo-afa.png') ?>" alt="<?= e($siteName) ?>" loading="lazy">
            <p><?= e(setting('contact_address', 'Escola Jacint Verdaguer, La Granada (Alt Penedès)')) ?></p>
            <?php if ($m = setting('contact_email')): ?><p><a href="mailto:<?= e($m) ?>"><?= e($m) ?></a></p><?php endif; ?>
            <?php if ($p = setting('contact_phone')): ?><p><a href="tel:<?= e(preg_replace('/\s+/', '', $p)) ?>"><?= e($p) ?></a></p><?php endif; ?>
        </div>
        <div>
            <h3>La web</h3>
            <ul>
                <li><a href="<?= url('activitats') ?>">Activitats i inscripcions</a></li>
                <li><a href="<?= url('campanyes') ?>">Campanyes de micromecenatge</a></li>
                <li><a href="<?= url('noticies') ?>">Notícies</a></li>
                <li><a href="<?= url('contacte') ?>">Contacte</a></li>
                <li><a href="<?= url('aplicacio') ?>">L'app de l'AFA</a></li>
            </ul>
        </div>
        <div>
            <h3>Segueix-nos</h3>
            <ul>
                <?php if ($ig = setting('social_instagram')): ?><li><a href="<?= e($ig) ?>" rel="noopener" target="_blank">Instagram</a></li><?php endif; ?>
                <?php if ($fb = setting('social_facebook')): ?><li><a href="<?= e($fb) ?>" rel="noopener" target="_blank">Facebook</a></li><?php endif; ?>
                <li><a href="<?= e(url(ltrim(setting('privacy_url', '/privacitat'), '/'))) ?>">Política de privacitat</a></li>
                <li><a href="<?= url('admin') ?>">Accés gestió</a></li>
            </ul>
        </div>
    </div>
    <div class="container footer-bottom">© <?= date('Y') ?> <?= e(setting('legal_name', $siteName)) ?><?= setting('legal_nif') ? ' · NIF ' . e(setting('legal_nif')) : '' ?></div>
</footer>
<script src="<?= asset('js/site.js') ?>" defer></script>
<?= setting('analytics_code', '') ?>
</body>
</html>
