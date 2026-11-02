<?php
/**
 * includes/auth.php
 * Manajemen sesi dan hak akses
 */

// Memastikan user sudah login
function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        setFlash('danger', 'Anda harus login untuk mengakses halaman ini.');
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

// Memastikan user memiliki role tertentu (misal: 'admin' atau 'user')
function requireRole(string $role): void {
    requireLogin();
    if (($_SESSION['role'] ?? '') !== $role) {
        http_response_code(403);
        die('Akses Ditolak: Anda tidak memiliki izin untuk halaman ini.');
    }
}

// Mengambil ID user yang sedang login
function currentUserId(): int {
    return (int) ($_SESSION['user_id'] ?? 0);
}

// Mengambil nama user yang sedang login
function currentUserName(): string {
    return $_SESSION['name'] ?? 'Pengguna';
}

// Mengambil role user yang sedang login
function currentUserRole(): string {
    return $_SESSION['role'] ?? 'user';
}
?>