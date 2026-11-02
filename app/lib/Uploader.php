<?php
declare(strict_types=1);

defined('BASE_PATH') || exit;

final class Uploader
{
    private const TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    /** Desa una imatge pujada i retorna el camí relatiu dins de /uploads, o null */
    public static function image(?array $file, int $maxWidth = 1920): ?string
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Error pujant el fitxer (codi ' . $file['error'] . ').');
        }
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new RuntimeException('La imatge supera els 10 MB.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset(self::TYPES[$mime]) || @getimagesize($file['tmp_name']) === false) {
            throw new RuntimeException('Format no permès. Utilitza JPG, PNG, WEBP o GIF.');
        }
        $dir = date('Y/m');
        $abs = UPLOAD_PATH . '/' . $dir;
        if (!is_dir($abs) && !mkdir($abs, 0755, true)) {
            throw new RuntimeException('No es pot crear la carpeta de pujades.');
        }
        $name = bin2hex(random_bytes(8)) . '.' . self::TYPES[$mime];
        $dest = $abs . '/' . $name;
        if (!self::resize($file['tmp_name'], $dest, $mime, $maxWidth)) {
            if (!move_uploaded_file($file['tmp_name'], $dest) && !copy($file['tmp_name'], $dest)) {
                throw new RuntimeException('No s\'ha pogut desar la imatge.');
            }
        }
        @chmod($dest, 0644);
        return $dir . '/' . $name;
    }

    /** Puja múltiples imatges (input name="x[]") */
    public static function images(?array $files): array
    {
        $out = [];
        if (!$files || !is_array($files['name'] ?? null)) {
            return $out;
        }
        foreach ($files['name'] as $i => $n) {
            $p = self::image([
                'name' => $n,
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i],
            ]);
            if ($p) {
                $out[] = $p;
            }
        }
        return $out;
    }

    private static function resize(string $src, string $dest, string $mime, int $maxWidth): bool
    {
        if (!function_exists('imagecreatetruecolor') || $mime === 'image/gif') {
            return false;
        }
        [$w, $h] = getimagesize($src);
        if ($w <= $maxWidth) {
            return false;
        }
        $img = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($src),
            'image/png' => @imagecreatefrompng($src),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src) : false,
            default => false,
        };
        if (!$img) {
            return false;
        }
        $nh = (int)round($h * $maxWidth / $w);
        $out = imagecreatetruecolor($maxWidth, $nh);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagecopyresampled($out, $img, 0, 0, 0, 0, $maxWidth, $nh, $w, $h);
        $ok = match ($mime) {
            'image/jpeg' => imagejpeg($out, $dest, 85),
            'image/png' => imagepng($out, $dest, 6),
            'image/webp' => imagewebp($out, $dest, 85),
        };
        imagedestroy($img);
        imagedestroy($out);
        return $ok;
    }

    public static function delete(?string $path): void
    {
        if (!$path || str_contains($path, '..')) {
            return;
        }
        $abs = UPLOAD_PATH . '/' . $path;
        if (is_file($abs)) {
            @unlink($abs);
        }
    }
}
