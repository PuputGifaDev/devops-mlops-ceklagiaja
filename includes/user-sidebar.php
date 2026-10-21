<?php
declare(strict_types=1);
$u = current_user();
?>
<aside class="sidebar" id="appSidebar">
    <div class="sidebar-brand">
        <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Logo" width="32" height="32">
        <span><?= e(APP_SHORT) ?></span>
    </div>

    <nav class="sidebar-nav">
        <a class="nav-item<?= is_active_menu('/user/dashboard') ?>" href="<?= BASE_URL ?>/user/dashboard.php">
            <i class="fa-solid fa-gauge-high"></i> Dashboard
        </a>
        <a class="nav-item<?= is_active_menu('/user/schedules') ?>" href="<?= BASE_URL ?>/user/schedules.php">
            <i class="fa-solid fa-calendar-day"></i> Jadwal Saya
        </a>
        <a class="nav-item<?= is_active_menu('/user/findings') ?>" href="<?= BASE_URL ?>/user/findings.php">
            <i class="fa-solid fa-triangle-exclamation"></i> Temuan
        </a>
        <a class="nav-item<?= is_active_menu('/user/inspection-history') ?>" href="<?= BASE_URL ?>/user/inspection-history.php">
            <i class="fa-solid fa-clock-rotate-left"></i> Riwayat Inspeksi
        </a>
        <a class="nav-item<?= is_active_menu('/user/profile') ?>" href="<?= BASE_URL ?>/user/profile.php">
            <i class="fa-solid fa-user-gear"></i> Profil
        </a>
    </nav>

    <div class="sidebar-user">
        <div class="avatar"><?= e(initials($u['name'] ?? '?')) ?></div>
        <div class="meta">
            <small><?= e($u['name'] ?? '') ?></small>
            <span class="badge bg-success">Petugas</span>
        </div>
    </div>
</aside>