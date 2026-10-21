<?php
declare(strict_types=1);
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/csrf.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (isset($_SESSION['user_id'])) {
    redirect($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php');
}

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — <?= e(APP_NAME) ?></title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/auth.css" rel="stylesheet">
</head>
<body class="auth-page">
    <div class="auth-card">
        <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Logo" class="auth-logo">
        <h1 class="auth-title"><?= e(APP_NAME) ?></h1>
        <p class="auth-subtitle">Masuk untuk melanjutkan</p>

        <?php if ($m = flash('error')): ?>
            <div class="alert alert-danger py-2 small"><?= e($m) ?></div>
        <?php endif; ?>
        <?php if ($m = flash('success')): ?>
            <div class="alert alert-success py-2 small"><?= e($m) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= BASE_URL ?>/actions/login-action.php" autocomplete="on">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label" for="username">Username atau Email</label>
                <input id="username" name="username" type="text"
                       class="form-control" required autofocus
                       value="<?= e($_POST['username'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <input id="password" name="password" type="password"
                       class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Masuk</button>
        </form>

        <div class="auth-footer">
            &copy; <?= date('Y') ?> <?= e(APP_NAME) ?>
        </div>
    </div>
</body>
</html>