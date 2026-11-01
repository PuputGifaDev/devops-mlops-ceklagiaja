<?php
declare(strict_types=1);

// Pastikan config & auth dimuat
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/app.php';
}
if (!function_exists('current_user')) {
    require_once __DIR__ . '/auth.php';
}

// Security Headers
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

$__user = current_user() ?? [];
$__pageTitle = $pageTitle ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($__pageTitle) ?> — <?= e(APP_SHORT) ?></title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/logo.png">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>/assets/css/app.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/sidebar.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/print.css" rel="stylesheet" media="print">
</head>
<body class="app-body">
<div class="app-layout">

    <!-- Backdrop untuk Mobile Sidebar -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>