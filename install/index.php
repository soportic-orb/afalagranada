<?php
declare(strict_types=1);

/**
 * Instal·lador web de la web de l'AFA.
 * Es bloqueja automàticament un cop existeix config.php.
 */

$base = dirname(__DIR__);
if (is_file($base . '/config.php')) {
    http_response_code(403);
    exit('<!doctype html><meta charset="utf-8"><body style="font-family:sans-serif;padding:3rem;text-align:center"><h1>La web ja està instal·lada</h1><p><a href="../admin">Anar al panell d\'administració</a></p>');
}

$checks = [
    'PHP 8.1 o superior' => version_compare(PHP_VERSION, '8.1.0', '>='),
    'Extensió PDO MySQL' => extension_loaded('pdo_mysql'),
    'Extensió mbstring' => extension_loaded('mbstring'),
    'Extensió cURL' => extension_loaded('curl'),
    'Extensió ZIP (actualitzacions)' => class_exists('ZipArchive'),
    'Extensió GD (codis QR i imatges)' => extension_loaded('gd'),
    'Extensió DOM' => class_exists('DOMDocument'),
    'Extensió fileinfo' => class_exists('finfo'),
    'Carpeta arrel escrivible (config.php)' => is_writable($base),
    'Carpeta uploads/ escrivible' => is_writable($base . '/uploads'),
    'Carpeta storage/ escrivible' => is_writable($base . '/storage'),
    'Carpeta vendor/ present' => is_file($base . '/vendor/autoload.php'),
];
$ok = !in_array(false, $checks, true);
$errors = [];
$done = false;

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$guessUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'afalagranada.cat') . rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');

$v = fn($k, $d = '') => htmlspecialchars((string)($_POST[$k] ?? $d), ENT_QUOTES, 'UTF-8');

if ($ok && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $db = [
        'driver' => 'mysql',
        'host' => trim($_POST['db_host'] ?? 'localhost'),
        'port' => (int)($_POST['db_port'] ?? 3306),
        'database' => trim($_POST['db_name'] ?? ''),
        'username' => trim($_POST['db_user'] ?? ''),
        'password' => (string)($_POST['db_pass'] ?? ''),
    ];
    $url = rtrim(trim($_POST['app_url'] ?? ''), '/');
    $name = trim($_POST['admin_name'] ?? '');
    $email = strtolower(trim($_POST['admin_email'] ?? ''));
    $pass = (string)($_POST['admin_pass'] ?? '');

    if (!$db['database'] || !$db['username']) {
        $errors[] = 'Cal indicar el nom i l\'usuari de la base de dades.';
    }
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        $errors[] = 'L\'URL de la web no és vàlida.';
    }
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Cal un nom i un correu vàlid per a l\'administrador/a.';
    }
    if (strlen($pass) < 10) {
        $errors[] = 'La contrasenya ha de tenir com a mínim 10 caràcters.';
    }

    if (!$errors) {
        try {
            $pdo = new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $db['host'], $db['port'], $db['database']), $db['username'], $db['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $pdo = null;
        } catch (Throwable $e) {
            $errors[] = 'No es pot connectar a la base de dades: ' . $e->getMessage();
        }
    }

    if (!$errors) {
        $newConfig = [
            'app' => ['url' => $url, 'key' => bin2hex(random_bytes(16))],
            'db' => $db,
            'debug' => false,
            'updates' => ['repo' => 'soportic-orb/afalagranada', 'branch' => 'main'],
        ];
        $php = "<?php\n// Configuració generada per l'instal·lador el " . date('Y-m-d H:i') . "\nreturn " . var_export($newConfig, true) . ";\n";
        $GLOBALS['config'] = $newConfig;
        try {
            foreach (['logs', 'tmp', 'backups'] as $d) {
                @mkdir($base . '/storage/' . $d, 0755, true);
            }
            // Inicialitza l'aplicació amb la nova configuració (sense escriure encara el fitxer)
            define('AFA_ENTRY', true);
            require_once $base . '/app/bootstrap.php';
            $GLOBALS['config'] = $newConfig;
            Migrator::run(db());
            setting('__reset__');
            $exists = db()->value('SELECT id FROM users WHERE email = ?', [$email]);
            if (!$exists) {
                db()->insert('users', ['name' => $name, 'email' => $email, 'password_hash' => password_hash($pass, PASSWORD_DEFAULT), 'role' => 'admin']);
            }
            if (!setting('contact_email')) {
                set_setting('contact_email', $email);
            }
            if (file_put_contents($base . '/config.php', $php) === false) {
                throw new RuntimeException('No s\'ha pogut escriure config.php');
            }
            @chmod($base . '/config.php', 0640);
            @file_put_contents($base . '/storage/installed.lock', date('c'));
            $done = true;
        } catch (Throwable $e) {
            $errors[] = 'Error durant la instal·lació: ' . $e->getMessage();
        }
    }
}
?><!doctype html>
<html lang="ca">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>Instal·lació · Web AFA</title>
<style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#f4f5f5;color:#1d1d1b;margin:0;padding:2rem 1rem}
    .box{max-width:640px;margin:0 auto;background:#fff;border-radius:16px;padding:2rem;box-shadow:0 10px 30px rgba(0,0,0,.06)}
    h1{margin-top:0;color:#078838} h2{font-size:1.1rem;margin:1.6rem 0 .6rem;border-bottom:1px solid #eee;padding-bottom:.3rem}
    label{display:block;margin:.6rem 0;font-weight:600;font-size:.92rem} input{display:block;width:100%;box-sizing:border-box;padding:.65rem .8rem;border:1px solid #cfd8d6;border-radius:8px;font-size:1rem;margin-top:.25rem}
    .row{display:grid;grid-template-columns:2fr 1fr;gap:1rem} button,.btn{background:#078838;color:#fff;border:0;padding:.85rem 1.4rem;border-radius:999px;font-size:1rem;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block;margin-top:1rem}
    ul.checks{list-style:none;padding:0} ul.checks li{padding:.25rem 0} .ok{color:#1a7f37}.ko{color:#c62828}
    .err{background:#fdecea;color:#8a1c1c;padding:.8rem 1rem;border-radius:8px;margin:.5rem 0} .muted{color:#6b7775;font-size:.88rem}
</style>
</head>
<body>
<div class="box">
<?php if ($done): ?>
    <h1>Instal·lació completada! 🎉</h1>
    <p>La web està llesta. Ara pots entrar al panell d'administració per configurar Stripe, el correu i el contingut.</p>
    <p class="muted">Per seguretat, l'instal·lador ha quedat bloquejat. Pots eliminar la carpeta <code>install/</code> si ho vols (es tornarà a crear en actualitzar, però sempre bloquejada).</p>
    <a class="btn" href="../admin">Anar al panell d'administració</a>
<?php else: ?>
    <h1>Instal·lació de la web de l'AFA</h1>
    <h2>1. Requisits del servidor</h2>
    <ul class="checks">
        <?php foreach ($checks as $label => $pass): ?><li class="<?= $pass ? 'ok' : 'ko' ?>"><?= $pass ? '✔' : '✘' ?> <?= htmlspecialchars($label) ?></li><?php endforeach; ?>
    </ul>
    <?php if (!$ok): ?><div class="err">Corregeix els requisits marcats en vermell i recarrega la pàgina.</div><?php else: ?>
    <?php foreach ($errors as $er): ?><div class="err"><?= htmlspecialchars($er) ?></div><?php endforeach; ?>
    <form method="post" autocomplete="off">
        <h2>2. Base de dades MySQL / MariaDB</h2>
        <p class="muted">Crea abans la base de dades i l'usuari des del panell del hosting (cPanel, Plesk...).</p>
        <div class="row"><label>Servidor<input name="db_host" value="<?= $v('db_host', 'localhost') ?>" required></label><label>Port<input name="db_port" value="<?= $v('db_port', '3306') ?>"></label></div>
        <label>Nom de la base de dades<input name="db_name" value="<?= $v('db_name') ?>" required></label>
        <label>Usuari<input name="db_user" value="<?= $v('db_user') ?>" required></label>
        <label>Contrasenya<input type="password" name="db_pass"></label>
        <h2>3. Web</h2>
        <label>URL pública de la web<input name="app_url" value="<?= $v('app_url', $guessUrl) ?>" required></label>
        <h2>4. Usuari administrador</h2>
        <label>Nom<input name="admin_name" value="<?= $v('admin_name') ?>" required></label>
        <label>Correu electrònic<input type="email" name="admin_email" value="<?= $v('admin_email') ?>" required></label>
        <label>Contrasenya (mínim 10 caràcters)<input type="password" name="admin_pass" minlength="10" required></label>
        <button type="submit">Instal·lar</button>
    </form>
    <?php endif; ?>
<?php endif; ?>
</div>
</body>
</html>
