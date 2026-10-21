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
        <a class="nav-item<?= is_active_menu('/admin/dashboard') ?>" href="<?= BASE_URL ?>/admin/dashboard.php">
            <i class="fa-solid fa-gauge-high"></i> Dashboard
        </a>

        <div class="nav-group">Master Data</div>
        <a class="nav-item<?= is_active_menu('/admin/buildings') ?>" href="<?= BASE_URL ?>/admin/buildings/index.php">
            <i class="fa-solid fa-building"></i> Bangunan
        </a>
        <a class="nav-item<?= is_active_menu('/admin/users') ?>" href="<?= BASE_URL ?>/admin/users/index.php">
            <i class="fa-solid fa-users"></i> Pengguna
        </a>
        <a class="nav-item<?= is_active_menu('/admin/categories') ?>" href="<?= BASE_URL ?>/admin/categories/index.php">
            <i class="fa-solid fa-layer-group"></i> Kategori
        </a>
        <a class="nav-item<?= is_active_menu('/admin/checklists') ?>" href="<?= BASE_URL ?>/admin/checklists/index.php">
            <i class="fa-solid fa-list-check"></i> Checklist
        </a>

        <div class="nav-group">Inspeksi</div>
        <a class="nav-item<?= is_active_menu('/admin/inspections') ?>" href="<?= BASE_URL ?>/admin/inspections/index.php">
            <i class="fa-solid fa-calendar-check"></i> Jadwal Inspeksi
        </a>
        <a class="nav-item<?= is_active_menu('/admin/findings') ?>" href="<?= BASE_URL ?>/admin/findings/index.php">
            <i class="fa-solid fa-triangle-exclamation"></i> Temuan
        </a>
        <a class="nav-item<?= is_active_menu('/admin/follow-ups') ?>" href="<?= BASE_URL ?>/admin/follow-ups/index.php">
            <i class="fa-solid fa-clipboard-check"></i> Tindak Lanjut
        </a>
        <a class="nav-item<?= is_active_menu('/admin/reports') ?>" href="<?= BASE_URL ?>/admin/reports/index.php">
            <i class="fa-solid fa-file-lines"></i> Laporan
        </a>
    </nav>

    <div class="sidebar-user">
        <div class="avatar"><?= e(initials($u['name'] ?? '?')) ?></div>
        <div class="meta">
            <small><?= e($u['name'] ?? '') ?></small>
            <span class="badge bg-primary">Admin</span>
        </div>
    </div>
</aside>