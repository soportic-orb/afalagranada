<?php
declare(strict_types=1);

function config(string $key, $default = null)
{
    $cfg = $GLOBALS['config'] ?? null;
    if (!is_array($cfg)) {
        return $default;
    }
    $cur = $cfg;
    foreach (explode('.', $key) as $part) {
        if (!is_array($cur) || !array_key_exists($part, $cur)) {
            return $default;
        }
        $cur = $cur[$part];
    }
    return $cur;
}

function db(): Database
{
    static $db = null;
    if ($db === null) {
        $db = new Database(config('db'));
    }
    return $db;
}

/** Valors de configuració desats a la BD (taula settings) */
function setting(string $key, $default = '')
{
    static $cache = null;
    if ($key === '__reset__') {
        $cache = null;
        return null;
    }
    if ($cache === null) {
        $cache = [];
        try {
            foreach (db()->all('SELECT `key`, `value` FROM settings') as $row) {
                $cache[$row['key']] = $row['value'];
            }
        } catch (Throwable $e) {
            $cache = [];
        }
    }
    return (isset($cache[$key]) && $cache[$key] !== '') ? $cache[$key] : $default;
}

function set_setting(string $key, ?string $value): void
{
    db()->run('REPLACE INTO settings (`key`, `value`) VALUES (?, ?)', [$key, $value]);
    setting('__reset__');
}

function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_url(): string
{
    $url = rtrim((string)config('app.url', ''), '/');
    if ($url === '' && isset($_SERVER['HTTP_HOST'])) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $url = $scheme . '://' . $_SERVER['HTTP_HOST'] . base_path();
    }
    return $url;
}

/** Subcarpeta on està instal·lada l'aplicació (buit si és a l'arrel) */
function base_path(): string
{
    static $path = null;
    if ($path === null) {
        $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $script = preg_replace('#/install$#', '', $script);
        $path = rtrim($script, '/');
    }
    return $path;
}

function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}

function abs_url(string $path = ''): string
{
    return base_url() . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = BASE_PATH . '/assets/' . ltrim($path, '/');
    $v = is_file($file) ? filemtime($file) : 1;
    return url('assets/' . ltrim($path, '/')) . '?v=' . $v;
}

function upload_url(?string $path): string
{
    if (!$path) {
        return '';
    }
    if (preg_match('#^https?://#', $path)) {
        return $path;
    }
    return url('uploads/' . ltrim($path, '/'));
}

function redirect(string $path, int $code = 302): never
{
    if (!preg_match('#^https?://#', $path)) {
        $path = url($path);
    }
    header('Location: ' . $path, true, $code);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('La sessió ha caducat. Torna enrere i recarrega la pàgina.');
    }
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function input(string $key, $default = '')
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

function old(string $key, $default = '')
{
    return $_SESSION['old'][$key] ?? $default;
}

function remember_input(): void
{
    $_SESSION['old'] = array_map(fn($v) => is_string($v) ? $v : '', $_POST);
    unset($_SESSION['old']['_csrf']);
}

function clear_old(): void
{
    unset($_SESSION['old']);
}

function slugify(string $text): string
{
    $text = mb_strtolower(trim($text));
    $map = ['à'=>'a','á'=>'a','è'=>'e','é'=>'e','í'=>'i','ï'=>'i','ò'=>'o','ó'=>'o','ú'=>'u','ü'=>'u','ç'=>'c','ñ'=>'n','l·l'=>'ll','·'=>''];
    $text = strtr($text, $map);
    if (function_exists('iconv')) {
        $conv = @iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        if ($conv !== false) {
            $text = $conv;
        }
    }
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim((string)$text, '-');
    return $text !== '' ? $text : 'element-' . bin2hex(random_bytes(3));
}

function unique_slug(string $table, string $base, ?int $ignoreId = null): string
{
    $slug = slugify($base);
    $candidate = $slug;
    $i = 2;
    while (true) {
        $sql = "SELECT id FROM `$table` WHERE slug = ?" . ($ignoreId ? ' AND id <> ?' : '');
        $params = $ignoreId ? [$candidate, $ignoreId] : [$candidate];
        if (!db()->value($sql, $params)) {
            return $candidate;
        }
        $candidate = $slug . '-' . $i++;
    }
}

function money(int|float|null $cents, bool $decimals = true): string
{
    $v = ((int)$cents) / 100;
    $dec = ($decimals && fmod($v, 1.0) != 0.0) ? 2 : 0;
    return number_format($v, $dec, ',', '.') . ' €';
}

/** Converteix "12,50" o "12.50" a cèntims */
function to_cents($value): int
{
    $value = str_replace([' ', '€'], '', (string)$value);
    if (str_contains($value, ',') && str_contains($value, '.')) {
        $value = str_replace('.', '', $value);
    } elseif (!str_contains($value, ',') && preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
        $value = str_replace('.', '', $value); // "3.000" = tres mil
    }
    $value = str_replace(',', '.', $value);
    return (int)round(((float)$value) * 100);
}

function cents_to_input(?int $cents): string
{
    if ($cents === null) {
        return '';
    }
    return rtrim(rtrim(number_format($cents / 100, 2, ',', ''), '0'), ',');
}

function fmt_date(?string $date, bool $withTime = false): string
{
    if (!$date) {
        return '';
    }
    $ts = strtotime($date);
    if ($ts === false) {
        return '';
    }
    $months = ['gener','febrer','març','abril','maig','juny','juliol','agost','setembre','octubre','novembre','desembre'];
    $m = $months[(int)date('n', $ts) - 1];
    $prep = in_array($m[0], ['a','o'], true) ? "d'" : 'de ';
    $out = date('j', $ts) . ' ' . $prep . $m . ' de ' . date('Y', $ts);
    if ($withTime && date('H:i', $ts) !== '00:00') {
        $out .= ', ' . date('H:i', $ts) . ' h';
    }
    return $out;
}

function dt_input(?string $date): string
{
    return $date ? date('Y-m-d\TH:i', strtotime($date)) : '';
}

function nullable_dt($value): ?string
{
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }
    $ts = strtotime($value);
    return $ts ? date('Y-m-d H:i:s', $ts) : null;
}

function nullable_date($value): ?string
{
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }
    $ts = strtotime($value);
    return $ts ? date('Y-m-d', $ts) : null;
}

function random_code(int $bytes = 10): string
{
    return strtoupper(bin2hex(random_bytes($bytes)));
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/** Renderitza una vista dins d'un layout */
function view(string $template, array $data = [], ?string $layout = null): void
{
    echo render($template, $data, $layout);
}

function render(string $template, array $data = [], ?string $layout = null): string
{
    $file = APP_PATH . '/views/' . $template . '.php';
    if (!is_file($file)) {
        throw new RuntimeException("Vista no trobada: $template");
    }
    extract($data, EXTR_SKIP);
    ob_start();
    require $file;
    $content = ob_get_clean();
    if ($layout) {
        $data['content'] = $content;
        return render($layout, $data);
    }
    return $content;
}

function partial(string $template, array $data = []): void
{
    echo render('partials/' . $template, $data);
}

function abort(int $code = 404, string $message = ''): never
{
    http_response_code($code);
    $title = $code === 404 ? 'Pàgina no trobada' : 'Error';
    view('front/error', ['code' => $code, 'title' => $title, 'message' => $message], 'front/layout');
    exit;
}

function json_response($data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function log_action(string $action, string $details = ''): void
{
    try {
        db()->run('INSERT INTO activity_log (user_id, action, details) VALUES (?, ?, ?)', [
            Auth::id(), $action, mb_substr($details, 0, 2000),
        ]);
    } catch (Throwable $e) {
        // no bloquejar per errors de registre
    }
}

function status_label(string $status): string
{
    return [
        'draft' => 'Esborrany',
        'active' => 'Activa',
        'published' => 'Publicada',
        'closed' => 'Tancada',
        'cancelled' => 'Cancel·lada',
        'pending' => 'Pendent',
        'paid' => 'Pagada',
        'free' => 'Gratuïta',
        'failed' => 'Fallida',
        'refunded' => 'Retornada',
        'expired' => 'Caducada',
    ][$status] ?? ucfirst($status);
}

function status_badge(string $status): string
{
    $cls = [
        'active' => 'ok', 'published' => 'ok', 'paid' => 'ok', 'free' => 'ok',
        'pending' => 'warn', 'draft' => 'muted',
        'closed' => 'muted', 'cancelled' => 'bad', 'failed' => 'bad', 'refunded' => 'bad', 'expired' => 'muted',
    ][$status] ?? 'muted';
    return '<span class="badge badge-' . $cls . '">' . e(status_label($status)) . '</span>';
}

function strip_to_text(?string $html, int $len = 160): string
{
    $text = trim(preg_replace('/\s+/', ' ', strip_tags((string)$html)));
    return mb_strlen($text) > $len ? mb_substr($text, 0, $len - 1) . '…' : $text;
}

function current_path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = base_path();
    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base));
    }
    return '/' . trim($uri, '/');
}

function is_active_path(string $prefix): bool
{
    $p = current_path();
    return $prefix === '/' ? $p === '/' : str_starts_with($p, $prefix);
}

/** Icones SVG senzilles (estil línia) */
function icon(string $name, string $class = 'icon'): string
{
    $paths = [
        'heart' => '<path d="M12 21s-7-4.4-9.5-9A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.5 6c-2.5 4.6-9.5 9-9.5 9z"/>',
        'star' => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'book' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V3H6.5A2.5 2.5 0 0 0 4 5.5z"/><path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20v-5"/>',
        'food' => '<path d="M7 2v20M4 2v6a3 3 0 0 0 6 0V2M17 2c-2 0-3 2-3 5v6h3v9"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
        'ball' => '<circle cx="12" cy="12" r="9"/><path d="M12 3a15 15 0 0 0 0 18M3 12h18M5.6 5.6c3.5 3 9.3 3 12.8 0M5.6 18.4c3.5-3 9.3-3 12.8 0"/>',
        'music' => '<path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>',
        'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>',
        'gift' => '<rect x="3" y="8" width="18" height="4"/><path d="M12 8v13M5 12v9h14v-9M7.5 8a2.5 2.5 0 1 1 0-5C11 3 12 8 12 8s1-5 4.5-5a2.5 2.5 0 1 1 0 5"/>',
        'tag' => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8z"/><circle cx="7" cy="7" r="1.5"/>',
        'megaphone' => '<path d="m3 11 18-8v18L3 13z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>',
        'palette' => '<circle cx="13.5" cy="6.5" r="1"/><circle cx="17.5" cy="10.5" r="1"/><circle cx="8.5" cy="7.5" r="1"/><circle cx="6.5" cy="12.5" r="1"/><path d="M12 2a10 10 0 0 0 0 20c1 0 1.7-.8 1.7-1.7 0-.4-.2-.8-.4-1.1-.3-.3-.4-.7-.4-1.1 0-.9.8-1.7 1.7-1.7h2A5.6 5.6 0 0 0 22 11c0-5-4.5-9-10-9z"/>',
        'phone' => '<rect x="6" y="2" width="12" height="20" rx="2"/><path d="M11 18h2"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'pin' => '<path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'bus' => '<rect x="4" y="3" width="16" height="16" rx="2"/><path d="M4 11h16M8 19v2M16 19v2"/><circle cx="8" cy="15" r="1"/><circle cx="16" cy="15" r="1"/>',
        'leaf' => '<path d="M11 20A7 7 0 0 1 4 13C4 7 9 3 20 3c0 11-4 16-10 16"/><path d="M4 21c4-4 7-7 11-10"/>',
        'mail' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/>',
    ];
    $p = $paths[$name] ?? $paths['star'];
    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

function icon_names(): array
{
    return ['heart','star','sun','book','food','clock','ball','music','users','gift','tag','megaphone','palette','phone','calendar','pin','check','shield','bus','leaf','mail'];
}
