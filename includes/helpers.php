<?php
declare(strict_types=1);

function e(mixed $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function format_tanggal(?string $date, string $fmt = 'd M Y'): string {
    if (!$date) return '-';
    try {
        $d = new DateTime($date);
    } catch (Exception) {
        return '-';
    }
    return strtr($d->format($fmt), [
        'May' => 'Mei',
        'Aug' => 'Agu',
        'Oct' => 'Okt',
        'Dec' => 'Des',
    ]);
}

function redirect(string $path): never {
    header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
    exit;
}

function flash(string $key, ?string $value = null): ?string {
    if ($value !== null) {
        $_SESSION["flash_$key"] = $value;
        return null;
    }
    $v = $_SESSION["flash_$key"] ?? null;
    unset($_SESSION["flash_$key"]);
    return $v;
}

function is_active_menu(string $needle): string {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    return str_contains($uri, $needle) ? ' active' : '';
}

function initials(string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    $a = mb_substr($parts[0] ?? '', 0, 1);
    $b = mb_substr($parts[count($parts) - 1] ?? '', 0, 1);
    return mb_strtoupper($a . $b);
}

function safe_upload_image(array $file, ?string &$error = null): ?string {
    $error = null;

    if (!isset($file['tmp_name'], $file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Upload gagal (kode: ' . ($file['error'] ?? '?') . ').';
        return null;
    }

    if ($file['size'] > MAX_UPLOAD_SIZE) {
        $error = 'Ukuran file melebihi ' . round(MAX_UPLOAD_SIZE / 1024 / 1024) . ' MB.';
        return null;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset(ALLOWED_MIME[$mime])) {
        $error = 'Format file harus JPEG, PNG, atau WebP.';
        return null;
    }

    if (!is_dir(UPLOAD_DIR)) {
        if (!@mkdir(UPLOAD_DIR, 0755, true) && !is_dir(UPLOAD_DIR)) {
            error_log('[UPLOAD] Gagal membuat folder: ' . UPLOAD_DIR);
            $error = 'Folder upload tidak dapat dibuat.';
            return null;
        }
    }

    $stored = bin2hex(random_bytes(16)) . '.' . ALLOWED_MIME[$mime];
    $target = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . $stored;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        error_log('[UPLOAD] move_uploaded_file gagal ke: ' . $target);
        $error = 'Gagal menyimpan file.';
        return null;
    }

    @chmod($target, 0644);
    return $stored;
}