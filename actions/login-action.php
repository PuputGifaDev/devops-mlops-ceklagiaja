<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/login.php');
verify_csrf();

$identifier = trim($_POST['username'] ?? '');
$password   = $_POST['password'] ?? '';

if ($identifier === '' || $password === '') {
    flash('error', 'Username/email dan password wajib diisi.');
    flash('old_username', $identifier); redirect('/login.php');
}

$stmt = $pdo->prepare('SELECT id, name, username, email, password_hash, role, is_active FROM users WHERE username = :i OR email = :i LIMIT 1');
$stmt->execute(['i' => $identifier]);
$user = $stmt->fetch();

$valid = $user && password_verify($password, $user['password_hash']);

if (!$valid) {
    flash('error', 'Username/email atau password salah.');
    flash('old_username', $identifier); redirect('/login.php');
}

if ((int)$user['is_active'] !== 1) {
    flash('error', 'Akun Anda dinonaktifkan.'); redirect('/login.php');
}

session_regenerate_id(true);
$_SESSION['user_id']  = (int) $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['name']     = $user['name'];
$_SESSION['role']     = $user['role'];

$pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$user['id']]);
log_activity($pdo, 'login', 'user', (int)$user['id'], 'Berhasil login ke sistem');

flash('success', 'Selamat datang, ' . $user['name'] . '.');
if ($user['role'] === 'admin') redirect('/admin/dashboard.php');
redirect('/user/dashboard.php');