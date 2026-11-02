<?php
declare(strict_types=1);

defined('BASE_PATH') || exit;

/**
 * Gestor genèric de llistats i formularis del panell d'administració.
 * Cada recurs es defineix a app/admin_resources.php.
 */
final class Crud
{
    public static function resources(): array
    {
        static $r = null;
        return $r ??= require APP_PATH . '/admin_resources.php';
    }

    public static function get(string $key): array
    {
        $r = self::resources()[$key] ?? null;
        if (!$r) {
            abort(404);
        }
        $r['key'] = $key;
        return $r;
    }

    public static function find(array $res, int $id): array
    {
        $row = db()->one("SELECT * FROM `{$res['table']}` WHERE id = ?", [$id]);
        if (!$row) {
            abort(404);
        }
        return $row;
    }

    public static function defaults(array $res): array
    {
        $row = [];
        foreach ($res['fields'] as $f) {
            $row[$f['name']] = $f['default'] ?? null;
        }
        return $row;
    }

    /** Llegeix el formulari i retorna [dades, errors] */
    public static function fromRequest(array $res, ?array $existing): array
    {
        $data = [];
        $errors = [];
        foreach ($res['fields'] as $f) {
            $name = $f['name'];
            $type = $f['type'] ?? 'text';
            if (in_array($type, ['heading', 'info'], true)) {
                continue;
            }
            // Camps desactivats per una altra opció (p. ex. inscripcions externes): es conserva el valor desat
            if (isset($f['disabled_when']) && !empty($_POST[$f['disabled_when']])) {
                continue;
            }
            $raw = $_POST[$name] ?? null;
            switch ($type) {
                case 'checkbox':
                    $val = !empty($raw) ? 1 : 0;
                    break;
                case 'number':
                    $val = ($raw === '' || $raw === null) ? ($f['nullable'] ?? false ? null : 0) : (int)$raw;
                    break;
                case 'money':
                    $val = ($raw === '' || $raw === null) ? (($f['nullable'] ?? false) ? null : 0) : to_cents($raw);
                    break;
                case 'date':
                    $val = nullable_date($raw);
                    break;
                case 'datetime':
                    $val = nullable_dt($raw);
                    break;
                case 'html':
                    $val = Sanitizer::html((string)$raw);
                    break;
                case 'image':
                    $val = $existing[$name] ?? null;
                    if (!empty($_POST[$name . '__remove'])) {
                        Uploader::delete($val);
                        $val = null;
                    }
                    try {
                        $new = Uploader::image($_FILES[$name] ?? null);
                        if ($new) {
                            Uploader::delete($val);
                            $val = $new;
                        }
                    } catch (RuntimeException $e) {
                        $errors[] = $f['label'] . ': ' . $e->getMessage();
                    }
                    break;
                case 'select':
                    $val = (string)$raw;
                    if (!array_key_exists($val, $f['options'])) {
                        $val = (string)array_key_first($f['options']);
                    }
                    break;
                case 'slug':
                    $val = trim((string)$raw);
                    $source = trim((string)($_POST[$f['from']] ?? ''));
                    $val = unique_slug($res['table'], $val !== '' ? $val : $source, $existing['id'] ?? null);
                    break;
                case 'password':
                    if ((string)$raw === '') {
                        if (!$existing) {
                            $errors[] = 'Cal indicar una contrasenya.';
                        }
                        continue 2;
                    }
                    if (mb_strlen((string)$raw) < 10) {
                        $errors[] = 'La contrasenya ha de tenir com a mínim 10 caràcters.';
                        continue 2;
                    }
                    $data[$f['column'] ?? $name] = password_hash((string)$raw, PASSWORD_DEFAULT);
                    continue 2;
                case 'email':
                    $val = mb_strtolower(trim((string)$raw));
                    if ($val !== '' && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = $f['label'] . ': correu invàlid.';
                    }
                    break;
                default:
                    $val = trim((string)$raw);
                    if (isset($f['maxlength'])) {
                        $val = mb_substr($val, 0, $f['maxlength']);
                    }
                    if ($val === '' && ($f['nullable'] ?? true)) {
                        $val = ($f['required'] ?? false) ? '' : null;
                    }
            }
            if (($f['required'] ?? false) && ($val === null || $val === '')) {
                $errors[] = 'El camp «' . $f['label'] . '» és obligatori.';
            }
            $data[$name] = $val;
        }
        if (isset($res['validate'])) {
            $errors = array_merge($errors, (array)($res['validate'])($data, $existing));
        }
        return [$data, $errors];
    }

    public static function formatCell(array $col, array $row): string
    {
        $v = $row[$col['name']] ?? null;
        if (isset($col['render'])) {
            return ($col['render'])($row);
        }
        return match ($col['type'] ?? 'text') {
            'bool' => $v ? '<span class="badge badge-ok">Sí</span>' : '<span class="badge badge-muted">No</span>',
            'money' => e(money((int)$v)),
            'date' => e(fmt_date($v)),
            'datetime' => e(fmt_date($v, true)),
            'status' => status_badge((string)$v),
            'image' => $v ? '<img class="thumb" src="' . e(upload_url($v)) . '" alt="">' : '',
            'select' => e($col['options'][$v] ?? $v),
            default => e(strip_to_text((string)$v, 80)),
        };
    }
}
