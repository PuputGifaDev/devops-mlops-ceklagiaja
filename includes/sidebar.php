<?php
declare(strict_types=1);

/**
 * Sidebar Router
 * File ini otomatis mendeteksi role user yang sedang login,
 * lalu memuat sidebar yang sesuai (Admin atau User).
 *
 * Pemakaian di halaman konten:
 *   require_once __DIR__ . '/../includes/sidebar.php';
 */

require_once __DIR__ . '/auth.php';

if (!is_logged_in()) {
    // Jika belum login, jangan tampilkan apa-apa
    return;
}

if ($_SESSION['role'] === 'admin') {
    require_once __DIR__ . '/admin-sidebar.php';
} elseif ($_SESSION['role'] === 'user') {
    require_once __DIR__ . '/user-sidebar.php';
} else {
    // Fallback keamanan jika role tidak dikenali
    http_response_code(403);
    exit('Role tidak dikenali.');
}