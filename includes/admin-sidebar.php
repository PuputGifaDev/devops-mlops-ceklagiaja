<aside class="app-sidebar bg-dark shadow" data-bs-theme="dark">
    <div class="sidebar-brand d-flex align-items-center px-3 py-3 border-bottom border-secondary border-opacity-25">
        <a href="<?= BASE_URL ?>admin/dashboard.php" class="brand-link text-decoration-none d-flex align-items-center">
            <span class="brand-icon bg-primary text-white rounded-3 p-2 me-2 d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 36px; height: 36px;">
                <i class="bi bi-building fs-5"></i>
            </span>
            <div>
                <span class="brand-text fw-bold text-white fs-5">CeklagiAja</span>
                <span class="badge bg-primary bg-opacity-25 text-primary-emphasis border border-primary-subtle d-block small mt-1" style="font-size: 0.65rem;">ADMIN PANEL</span>
            </div>
        </a>
    </div>
    <div class="sidebar-wrapper py-2">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column gap-1" role="menu">
                
                <li class="nav-header text-uppercase text-secondary small fw-bold px-3 py-1" style="font-size: 0.7rem; letter-spacing: 0.05em;">Utama</li>
                
                <!-- Menu Dashboard -->
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>admin/dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-speedometer2 text-info"></i> <p>Dashboard</p>
                    </a>
                </li>
                
                <li class="nav-header text-uppercase text-secondary small fw-bold px-3 py-1 mt-2" style="font-size: 0.7rem; letter-spacing: 0.05em;">Operasional Inspeksi</li>
                
                <!-- Menu Data Bangunan -->
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>admin/buildings/index.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'buildings') !== false ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-building text-primary"></i> <p>Data Bangunan</p>
                    </a>
                </li>
                
                <!-- Menu Jadwal Inspeksi -->
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>admin/inspections/index.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'inspections') !== false ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-calendar-check text-warning"></i> <p>Jadwal Inspeksi</p>
                    </a>
                </li>

                <!-- Menu Temuan Kerusakan -->
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>admin/findings/index.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'findings') !== false ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-exclamation-triangle text-danger"></i> <p>Temuan Kerusakan</p>
                    </a>
                </li>

                <!-- Menu Tindak Lanjut -->
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>admin/follow-ups/index.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'follow-ups') !== false ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-tools text-success"></i> <p>Tindak Lanjut</p>
                    </a>
                </li>

                <!-- Menu Laporan Inspeksi -->
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>admin/reports/index.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'reports') !== false ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-file-earmark-bar-graph text-light"></i> <p>Laporan Resmi</p>
                    </a>
                </li>

                <li class="nav-header text-uppercase text-secondary small fw-bold px-3 py-1 mt-2" style="font-size: 0.7rem; letter-spacing: 0.05em;">Data Master & Sistem</li>

                <!-- Menu Kategori -->
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>admin/categories/index.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'categories') !== false ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-folder2-open"></i> <p>Kategori Checklist</p>
                    </a>
                </li>

                <!-- Menu Master Checklist -->
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>admin/checklists/index.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'checklists') !== false ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-list-check"></i> <p>Item Checklist</p>
                    </a>
                </li>

                <!-- Menu Manajemen User -->
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>admin/users/index.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'users') !== false ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-people"></i> <p>Manajemen User</p>
                    </a>
                </li>

                <li class="nav-header text-uppercase text-secondary small fw-bold px-3 py-1 mt-2" style="font-size: 0.7rem; letter-spacing: 0.05em;">Navigasi Publik</li>

                <!-- Portal Publik -->
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>public/buildings/index.php" target="_blank" class="nav-link">
                        <i class="nav-icon bi bi-box-arrow-up-right text-info"></i> <p>Lihat Portal Publik</p>
                    </a>
                </li>
                
            </ul>
        </nav>
    </div>
</aside>
