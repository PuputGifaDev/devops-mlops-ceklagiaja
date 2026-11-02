<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

verifyCsrf();

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    setFlash('danger', 'Username dan password wajib diisi.');
    header('Location: ../login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE (username = :u OR email = :e) AND is_active = 1 LIMIT 1");
$stmt->execute(['u' => $username, 'e' => $username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password_hash'])) {
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['name']    = $user['name'];
    $_SESSION['role']    = $user['role'];
    $_SESSION['last_login'] = time();

    // Update last login
    $update = $pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
    $update->execute([$user['id']]);

    if ($user['role'] === 'admin') {
        header('Location: ../admin/dashboard.php');
    } else {
        header('Location: ../user/dashboard.php');
    }
    exit;
}

setFlash('danger', 'Username/email atau password salah.');
header('Location: ../login.php');
exit;
?>