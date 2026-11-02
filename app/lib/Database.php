<?php
declare(strict_types=1);

defined('BASE_PATH') || exit;

final class Database
{
    public PDO $pdo;

    public function __construct(?array $cfg)
    {
        if (!$cfg) {
            throw new RuntimeException('Base de dades no configurada.');
        }
        $driver = $cfg['driver'] ?? 'mysql';
        if ($driver === 'sqlite') {
            $dsn = 'sqlite:' . $cfg['database'];
        } else {
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $cfg['host'] ?? 'localhost', (int)($cfg['port'] ?? 3306), $cfg['database']);
        }
        $this->pdo = new PDO($dsn, $cfg['username'] ?? null, $cfg['password'] ?? null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        if ($driver === 'mysql') {
            $this->pdo->exec("SET time_zone = '" . date('P') . "'");
        }
    }

    public function run(string $sql, array $params = []): PDOStatement
    {
        $st = $this->pdo->prepare($sql);
        $st->execute(array_values($params));
        return $st;
    }

    public function all(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll();
    }

    public function one(string $sql, array $params = []): ?array
    {
        $row = $this->run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public function value(string $sql, array $params = [])
    {
        $v = $this->run($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    public function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql = sprintf('INSERT INTO `%s` (%s) VALUES (%s)', $table,
            implode(', ', array_map(fn($c) => "`$c`", $cols)),
            implode(', ', array_fill(0, count($cols), '?')));
        $this->run($sql, array_values($data));
        return (int)$this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, int $id): void
    {
        $set = implode(', ', array_map(fn($c) => "`$c` = ?", array_keys($data)));
        $params = array_values($data);
        $params[] = $id;
        $this->run("UPDATE `$table` SET $set WHERE id = ?", $params);
    }

    public function delete(string $table, int $id): void
    {
        $this->run("DELETE FROM `$table` WHERE id = ?", [$id]);
    }

    public function transaction(callable $fn)
    {
        $this->pdo->beginTransaction();
        try {
            $r = $fn($this);
            $this->pdo->commit();
            return $r;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
