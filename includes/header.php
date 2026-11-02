<?php
/**
 * includes/header.php
 */
require_once __DIR__ . '/auth.php';
requireLogin();

$pageTitle = $pageTitle ?? 'Dashboard';
$role = currentUserRole();

// Deteksi path untuk CSS & JS agar tidak error saat dijalankan di folder mana pun
$baseUrl = BASE_URL; 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle) ?> — <?= APP_NAME ?></title>
    
    <!-- AdminLTE 4 & Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.10.0/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= $baseUrl ?>assets/css/app.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>assets/css/sidebar.css">
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">
    
    <!-- Navbar Top -->
    <nav class="app-header navbar navbar-expand bg-white border-bottom shadow-sm">
        <div class="container-fluid">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                        <i class="bi bi-list fs-5"></i>
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle fs-5 me-2"></i>
                        <span class="d-none d-md-inline"><?= esc(currentUserName()) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><a class="dropdown-item" href="<?= $baseUrl ?>logout.php"><i class="bi bi-box-arrow-right text-danger me-2"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Sidebar Dinamis berdasarkan Role -->
    <?php if ($role === 'admin'): ?>
        <?php include __DIR__ . '/admin-sidebar.php'; ?>
    <?php else: ?>
        <?php include __DIR__ . '/user-sidebar.php'; ?>
    <?php endif; ?>

    <!-- Main Content Wrapper -->
    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-6">
                        <h3 class="mb-0 fw-bold"><?= esc($pageTitle) ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="app-content">
            <div class="container-fluid">
                <?php displayFlash(); // Menampilkan notifikasi sukses/gagal ?>