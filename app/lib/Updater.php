<?php
declare(strict_types=1);

defined('BASE_PATH') || exit;

/**
 * Actualitzacions OTA des de GitHub.
 * Descarrega la branca configurada (per defecte "main"), fa còpia de seguretat del codi actual,
 * substitueix els fitxers (mantenint config.php, uploads/ i storage/) i executa les migracions.
 */
final class Updater
{
    /** Camins que mai s'han de sobreescriure ni esborrar */
    private const PROTECTED = ['config.php', 'uploads', 'storage', '.git', '.user.ini', 'php.ini'];
    /** Carpetes gestionades completament pel repositori (s'eliminen fitxers obsolets) */
    private const MANAGED = ['app', 'assets', 'vendor', 'install'];

    public static function repo(): string
    {
        return (string)setting('github_repo', config('updates.repo', 'soportic-orb/afalagranada'));
    }

    public static function branch(): string
    {
        return (string)setting('github_branch', config('updates.branch', 'main'));
    }

    public static function currentVersion(): string
    {
        return trim((string)@file_get_contents(BASE_PATH . '/VERSION')) ?: '0.0.0';
    }

    public static function installedCommit(): string
    {
        return (string)setting('installed_commit', '');
    }

    private static function http(string $url, ?string $saveTo = null): array
    {
        $ch = curl_init($url);
        $headers = ['User-Agent: afalagranada-updater', 'Accept: application/vnd.github+json'];
        $token = (string)setting('github_token', '');
        if ($token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        $fp = null;
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 180,
            CURLOPT_CONNECTTIMEOUT => 20,
        ]);
        if ($saveTo) {
            $fp = fopen($saveTo, 'wb');
            curl_setopt($ch, CURLOPT_FILE, $fp);
        } else {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        }
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($fp) {
            fclose($fp);
        }
        if ($body === false) {
            throw new RuntimeException('Error de connexió amb GitHub: ' . $err);
        }
        if ($code >= 400) {
            $msg = match ($code) {
                404 => 'Repositori o branca no trobats (si el repositori és privat cal un token).',
                409 => 'El repositori encara està buit.',
                401, 403 => 'Accés denegat (revisa el token o el límit de peticions).',
                default => "HTTP $code",
            };
            throw new RuntimeException('GitHub: ' . $msg);
        }
        return ['code' => $code, 'body' => $saveTo ? '' : (string)$body];
    }

    /** Consulta l'últim commit de la branca */
    public static function check(): array
    {
        $res = self::http('https://api.github.com/repos/' . self::repo() . '/commits/' . rawurlencode(self::branch()));
        $data = json_decode($res['body'], true);
        if (!is_array($data) || empty($data['sha'])) {
            throw new RuntimeException('Resposta inesperada de GitHub.');
        }
        $remoteVersion = null;
        try {
            $v = self::http('https://api.github.com/repos/' . self::repo() . '/contents/VERSION?ref=' . rawurlencode(self::branch()));
            $vj = json_decode($v['body'], true);
            if (!empty($vj['content'])) {
                $remoteVersion = trim((string)base64_decode($vj['content']));
            }
        } catch (Throwable $e) {
            // opcional
        }
        $info = [
            'sha' => $data['sha'],
            'short' => substr($data['sha'], 0, 7),
            'message' => $data['commit']['message'] ?? '',
            'date' => $data['commit']['committer']['date'] ?? ($data['commit']['author']['date'] ?? ''),
            'author' => $data['commit']['author']['name'] ?? '',
            'url' => $data['html_url'] ?? '',
            'version' => $remoteVersion,
            'update_available' => $data['sha'] !== self::installedCommit(),
            'checked_at' => date('Y-m-d H:i:s'),
        ];
        set_setting('update_last_check', json_encode($info, JSON_UNESCAPED_UNICODE));
        return $info;
    }

    public static function lastCheck(): ?array
    {
        $j = (string)setting('update_last_check', '');
        return $j ? json_decode($j, true) : null;
    }

    /** Executa l'actualització completa. Retorna un registre de passos. */
    public static function update(): array
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('L\'extensió ZIP de PHP no està disponible al servidor.');
        }
        @set_time_limit(600);
        @ini_set('memory_limit', '512M');
        $log = [];
        $tmp = STORAGE_PATH . '/tmp/update-' . date('YmdHis');
        if (!is_dir($tmp) && !mkdir($tmp, 0755, true)) {
            throw new RuntimeException('No es pot crear la carpeta temporal.');
        }

        $info = self::check();
        $log[] = 'Últim commit a ' . self::branch() . ': ' . $info['short'] . ' — ' . strtok($info['message'], "\n");

        $zipFile = $tmp . '/release.zip';
        self::http('https://api.github.com/repos/' . self::repo() . '/zipball/' . rawurlencode($info['sha']), $zipFile);
        $log[] = 'Descarregat paquet (' . round(filesize($zipFile) / 1024) . ' KB)';

        try {
            $log = array_merge($log, self::applyZip($zipFile, $tmp));
        } finally {
            self::rrmdir($tmp);
        }
        set_setting('installed_commit', $info['sha']);
        set_setting('installed_at', date('Y-m-d H:i:s'));
        $log[] = 'Actualització completada. Versió: ' . self::currentVersion() . ' (' . $info['short'] . ')';
        return $log;
    }

    /** Aplica un paquet ZIP (format zipball de GitHub: una carpeta arrel amb el projecte) */
    public static function applyZip(string $zipFile, string $tmp): array
    {
        $log = [];
        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== true) {
            throw new RuntimeException('El paquet descarregat no és un ZIP vàlid.');
        }
        $extract = $tmp . '/src';
        $zip->extractTo($extract);
        $zip->close();
        $dirs = glob($extract . '/*', GLOB_ONLYDIR);
        if (count($dirs) !== 1) {
            throw new RuntimeException('Estructura del paquet inesperada.');
        }
        $src = $dirs[0];
        if (!is_file($src . '/index.php') || !is_file($src . '/app/bootstrap.php') || !is_file($src . '/vendor/autoload.php')) {
            throw new RuntimeException('El paquet no conté una versió vàlida de l\'aplicació.');
        }
        $log[] = 'Paquet extret i validat';

        $backup = self::backup();
        $log[] = 'Còpia de seguretat creada: ' . basename($backup);

        self::setMaintenance(true);
        try {
            $count = self::copyTree($src, BASE_PATH);
            $log[] = "Fitxers actualitzats: $count";
            $removed = self::removeObsolete($src);
            if ($removed) {
                $log[] = "Fitxers obsolets eliminats: $removed";
            }
            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }
        } finally {
            self::setMaintenance(false);
        }
        return $log;
    }

    /** Migracions s'executen en una petició nova (amb el codi ja actualitzat) */
    public static function runMigrations(): array
    {
        return Migrator::run(db());
    }

    public static function backup(): string
    {
        $dir = STORAGE_PATH . '/backups';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $file = $dir . '/code-' . date('Ymd-His') . '-' . substr(self::installedCommit() ?: 'local', 0, 7) . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No es pot crear la còpia de seguretat.');
        }
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(BASE_PATH, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            $rel = ltrim(str_replace('\\', '/', substr($f->getPathname(), strlen(BASE_PATH))), '/');
            if ($f->isDir() || self::isProtected($rel)) {
                continue;
            }
            $zip->addFile($f->getPathname(), $rel);
        }
        $zip->setArchiveComment(self::installedCommit());
        $zip->close();
        // conserva només les 5 últimes còpies
        $all = glob($dir . '/code-*.zip') ?: [];
        rsort($all);
        foreach (array_slice($all, 5) as $old) {
            @unlink($old);
        }
        return $file;
    }

    public static function backups(): array
    {
        $all = glob(STORAGE_PATH . '/backups/code-*.zip') ?: [];
        rsort($all);
        return array_map(fn($f) => ['name' => basename($f), 'size' => filesize($f), 'date' => date('Y-m-d H:i:s', filemtime($f))], $all);
    }

    public static function restore(string $name): void
    {
        if (!preg_match('/^code-[\w-]+\.zip$/', $name)) {
            throw new RuntimeException('Nom de còpia invàlid.');
        }
        $file = STORAGE_PATH . '/backups/' . $name;
        if (!is_file($file)) {
            throw new RuntimeException('Còpia no trobada.');
        }
        $zip = new ZipArchive();
        if ($zip->open($file) !== true) {
            throw new RuntimeException('No es pot obrir la còpia.');
        }
        $commit = (string)$zip->getArchiveComment();
        self::setMaintenance(true);
        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $rel = $zip->getNameIndex($i);
                if (str_contains($rel, '..') || self::isProtected($rel)) {
                    continue;
                }
                $dest = BASE_PATH . '/' . $rel;
                if (!is_dir(dirname($dest))) {
                    mkdir(dirname($dest), 0755, true);
                }
                file_put_contents($dest, $zip->getFromIndex($i));
            }
        } finally {
            self::setMaintenance(false);
            $zip->close();
        }
        if ($commit !== '') {
            set_setting('installed_commit', $commit);
        }
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
    }

    private static function isProtected(string $rel): bool
    {
        $rel = ltrim($rel, '/');
        foreach (self::PROTECTED as $p) {
            if ($rel === $p || str_starts_with($rel, $p . '/')) {
                return true;
            }
        }
        return false;
    }

    private static function copyTree(string $src, string $dst): int
    {
        $count = 0;
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($it as $f) {
            $rel = ltrim(str_replace('\\', '/', substr($f->getPathname(), strlen($src))), '/');
            if (self::isProtected($rel) || str_starts_with($rel, '.github')) {
                continue;
            }
            $target = $dst . '/' . $rel;
            if ($f->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target, 0755, true);
                }
                continue;
            }
            if (is_file($target) && md5_file($target) === md5_file($f->getPathname())) {
                continue;
            }
            if (!@copy($f->getPathname(), $target)) {
                throw new RuntimeException('No es pot escriure ' . $rel . '. Revisa els permisos.');
            }
            $count++;
        }
        return $count;
    }

    private static function removeObsolete(string $src): int
    {
        $removed = 0;
        foreach (self::MANAGED as $dir) {
            $base = BASE_PATH . '/' . $dir;
            if (!is_dir($base) || !is_dir($src . '/' . $dir)) {
                continue;
            }
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($it as $f) {
                $rel = ltrim(str_replace('\\', '/', substr($f->getPathname(), strlen(BASE_PATH))), '/');
                if (file_exists($src . '/' . $rel)) {
                    continue;
                }
                if ($f->isDir()) {
                    @rmdir($f->getPathname());
                } elseif (@unlink($f->getPathname())) {
                    $removed++;
                }
            }
        }
        return $removed;
    }

    public static function setMaintenance(bool $on): void
    {
        $flag = STORAGE_PATH . '/maintenance.flag';
        $on ? @file_put_contents($flag, (string)time()) : @unlink($flag);
    }

    public static function inMaintenance(): bool
    {
        $flag = STORAGE_PATH . '/maintenance.flag';
        if (!is_file($flag)) {
            return false;
        }
        // seguretat: si fa més de 15 min, ignora el flag
        return (time() - (int)@file_get_contents($flag)) < 900;
    }

    private static function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $f) {
            $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        }
        @rmdir($dir);
    }
}
