<?php
declare(strict_types=1);

/** Escape HTML output. */
function e(mixed $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Format tanggal Indonesia. */
function format_tanggal(?string $date, string $fmt = 'd M Y'): string {
    if (!$date || $date === '0000-00-00') return '-';
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

/** Format tanggal + waktu. */
function format_datetime(?string $datetime): string {
    if (!$datetime) return '-';
    try {
        $dt = new DateTime($datetime);
        return strtr($dt->format('d M Y, H:i'), ['May'=>'Mei','Aug'=>'Agu','Oct'=>'Okt','Dec'=>'Des']);
    } catch (Exception) {
        return '-';
    }
}

/** Redirect ke path relatif terhadap BASE_URL. */
function redirect(string $path): never {
    // Jika $path sudah mengandung http (misal redirect ke luar), jangan ditambah BASE_URL
    if (str_starts_with($path, 'http')) {
        header('Location: ' . $path);
    } else {
        header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
    }
    exit;
}

/** Set atau ambil flash message. */
function flash(string $key, ?string $value = null): ?string {
    if ($value !== null) {
        $_SESSION["flash_$key"] = $value;
        return null;
    }
    $v = $_SESSION["flash_$key"] ?? null;
    unset($_SESSION["flash_$key"]);
    return $v;
}

/** Tandai menu aktif berdasarkan segmen URL. */
function is_active_menu(string $needle): string {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    return str_contains($uri, $needle) ? ' active' : '';
}

/** Ambil inisial nama (misal: "Budi Santoso" -> "BS"). */
function initials(string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    $a = mb_substr($parts[0] ?? '', 0, 1);
    $b = mb_substr($parts[count($parts) - 1] ?? '', 0, 1);
    return mb_strtoupper($a . $b);
}

/** Label status inspeksi. */
function status_label(string $status): string {
    return match ($status) {
        'scheduled'      => 'Dijadwalkan',
        'in_progress'    => 'Sedang Dikerjakan',
        'pending_review' => 'Menunggu Review',
        'revision'       => 'Perlu Revisi',
        'approved'       => 'Disetujui',
        'cancelled'      => 'Dibatalkan',
        default          => ucfirst($status),
    };
}

/** Badge Bootstrap untuk status inspeksi. */
function status_badge(string $status): string {
    $color = match ($status) {
        'scheduled'      => 'secondary',
        'in_progress'    => 'info',
        'pending_review' => 'warning',
        'revision'       => 'danger',
        'approved'       => 'success',
        'cancelled'      => 'dark',
        default          => 'light',
    };
    return '<span class="badge bg-' . $color . '">' . e(status_label($status)) . '</span>';
}

/** Badge untuk status tindak lanjut temuan. */
function followup_badge(string $status): string {
    $map = [
        'open'           => ['warning', 'Terbuka'],
        'in_progress'    => ['info', 'Diproses'],
        'completed'      => ['success', 'Selesai'],
        'not_actionable' => ['secondary', 'Tidak Dapat Ditindaklanjuti'],
    ];
    [$color, $label] = $map[$status] ?? ['light', $status];
    return '<span class="badge bg-' . $color . '">' . e($label) . '</span>';
}

/** 
 * Upload gambar dengan aman.
 * Mengembalikan nama file yang disimpan (string) atau null jika gagal.
 */
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

    // PERBAIKAN: Gunakan in_array() karena ALLOWED_MIME adalah array list, bukan associative
    if (!in_array($mime, ALLOWED_MIME, true)) {
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

    // Tentukan ekstensi berdasarkan MIME
    $extMap = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];
    $ext = $extMap[$mime] ?? 'jpg';

    $stored = bin2hex(random_bytes(16)) . '.' . $ext;
    $target = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . $stored;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        error_log('[UPLOAD] move_uploaded_file gagal ke: ' . $target);
        $error = 'Gagal menyimpan file.';
        return null;
    }

    @chmod($target, 0644);
    return $stored;
}