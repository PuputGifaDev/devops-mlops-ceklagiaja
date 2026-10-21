<?php
declare(strict_types=1);
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (isset($_SESSION['user_id'])) {
    try {
        require_once __DIR__ . '/config/database.php';
        require_once __DIR__ . '/includes/auth.php';
        log_activity($pdo, 'logout', 'user', (int)$_SESSION['user_id'], 'Logout');
    } catch (Throwable $e) {
        error_log('[LOGOUT LOG] ' . $e->getMessage());
    }
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 42000,
        'path'     => $params['path'],
        'domain'   => $params['domain'],
        'secure'   => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Lax',
    ]);
}
session_destroy();

session_start();
flash('success', 'Anda telah logout.');
redirect('login.php');