<?php
/**
 * includes/csrf.php
 * Manajemen Token CSRF
 */

// Membuat atau mengambil token CSRF yang ada di session
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Memverifikasi token yang dikirim dari form
function verifyCsrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(419);
            die('Sesi tidak valid atau token CSRF kadaluarsa. Silakan kembali dan muat ulang halaman.');
        }
    }
}
?>