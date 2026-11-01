<?php
declare(strict_types=1);

/**
 * Generate atau ambil token CSRF dari session.
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Cetak input hidden untuk form.
 * Pemakaian: <?= csrf_field() ?>
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Validasi token CSRF.
 * Hanya jalankan fungsi ini saat memproses data POST.
 */
function verify_csrf(): void {
    // Pengecekan method (opsional, sebagai perlindungan ganda)
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Metode tidak diizinkan.');
    }

    $token = $_POST['csrf_token'] ?? '';
    
    // Pastikan token ada, bertipe string, dan cocok dengan yang di session
    if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(419); // 419: Page Expired (standar Laravel)
        exit('Token CSRF tidak valid. Muat ulang halaman lalu coba lagi.');
    }
}