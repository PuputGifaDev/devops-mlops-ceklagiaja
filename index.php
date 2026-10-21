<?php
declare(strict_types=1);
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}
redirect($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php');