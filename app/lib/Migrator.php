<?php
declare(strict_types=1);

/**
 * Executa les migracions pendents de app/migrations (fitxers .sql o .php),
 * en ordre alfabètic. Cada migració s'executa una sola vegada.
 */
final class Migrator
{
    public static function run(Database $db): array
    {
        $db->pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (
            version VARCHAR(190) NOT NULL PRIMARY KEY,
            applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        $done = array_column($db->all('SELECT version FROM schema_migrations'), 'version');
        $files = glob(APP_PATH . '/migrations/*.{sql,php}', GLOB_BRACE) ?: [];
        sort($files);
        $applied = [];
        foreach ($files as $file) {
            $version = basename($file);
            if (in_array($version, $done, true)) {
                continue;
            }
            if (str_ends_with($file, '.sql')) {
                foreach (self::splitSql((string)file_get_contents($file)) as $stmt) {
                    $db->pdo->exec($stmt);
                }
            } else {
                $fn = require $file;
                if (is_callable($fn)) {
                    $fn($db);
                }
            }
            $db->run('INSERT INTO schema_migrations (version) VALUES (?)', [$version]);
            $applied[] = $version;
        }
        return $applied;
    }

    public static function pending(Database $db): array
    {
        try {
            $done = array_column($db->all('SELECT version FROM schema_migrations'), 'version');
        } catch (Throwable $e) {
            $done = [];
        }
        $files = array_map('basename', glob(APP_PATH . '/migrations/*.{sql,php}', GLOB_BRACE) ?: []);
        sort($files);
        return array_values(array_diff($files, $done));
    }

    private static function splitSql(string $sql): array
    {
        $sql = preg_replace('/^\s*--.*$/m', '', $sql);
        return array_values(array_filter(array_map('trim', explode(';', (string)$sql)), fn($s) => $s !== ''));
    }
}
