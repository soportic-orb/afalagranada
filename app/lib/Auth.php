<?php
declare(strict_types=1);

defined('BASE_PATH') || exit;

final class Auth
{
    public const ROLES = ['admin' => 'Administrador/a', 'editor' => 'Editor/a', 'checkin' => 'Control d\'accés'];

    public static function user(): ?array
    {
        static $user = false;
        if ($user === false) {
            $user = null;
            if (!empty($_SESSION['uid'])) {
                $user = db()->one('SELECT id, name, email, role FROM users WHERE id = ? AND active = 1', [$_SESSION['uid']]);
            }
        }
        return $user;
    }

    public static function id(): ?int
    {
        return isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function role(): ?string
    {
        return self::user()['role'] ?? null;
    }

    public static function can(string $area): bool
    {
        $role = self::role();
        if ($role === 'admin') {
            return true;
        }
        if ($role === 'editor') {
            return !in_array($area, ['users', 'settings', 'updates'], true);
        }
        if ($role === 'checkin') {
            return in_array($area, ['dashboard', 'checkin'], true);
        }
        return false;
    }

    public static function attempt(string $email, string $password): bool
    {
        $ip = client_ip();
        $recent = (int)db()->value(
            'SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND created_at > ?',
            [$ip, date('Y-m-d H:i:s', time() - 900)]
        );
        if ($recent >= 8) {
            throw new RuntimeException('Massa intents. Torna-ho a provar d\'aquí a 15 minuts.');
        }
        $user = db()->one('SELECT * FROM users WHERE email = ? AND active = 1', [mb_strtolower($email)]);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            db()->run('INSERT INTO login_attempts (ip) VALUES (?)', [$ip]);
            return false;
        }
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            db()->run('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$user['id'];
        db()->run('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$user['id']]);
        db()->run('DELETE FROM login_attempts WHERE ip = ?', [$ip]);
        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }

    public static function require(string $area = 'dashboard'): void
    {
        if (!self::check()) {
            $_SESSION['intended'] = $_SERVER['REQUEST_URI'] ?? null;
            redirect('admin/login');
        }
        if (!self::can($area)) {
            http_response_code(403);
            view('admin/forbidden', ['title' => 'Accés denegat'], 'admin/layout');
            exit;
        }
    }
}
