<aside class="app-sidebar bg-dark shadow" data-bs-theme="dark">
    <div class="sidebar-brand d-flex align-items-center px-3 py-3 border-bottom border-secondary border-opacity-25">
        <a href="<?= BASE_URL ?>user/dashboard.php" class="brand-link text-decoration-none d-flex align-items-center">
            <span class="brand-icon bg-info text-dark rounded-3 p-2 me-2 d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 36px; height: 36px;">
                <i class="bi bi-shield-check fs-5"></i>
            </span>
            <div>
                <span class="brand-text fw-bold text-white fs-5">CeklagiAja</span>
                <span class="badge bg-info text-dark d-block small mt-1" style="font-size: 0.65rem;">PETUGAS INSPEKSI</span>
            </div>
        </a>
    </div>
    <div class="sidebar-wrapper py-2">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column gap-1" role="menu">
                
                <li class="nav-header text-uppercase text-secondary small fw-bold px-3 py-1" style="font-size: 0.7rem; letter-spacing: 0.05em;">Menu Petugas</li>
                
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>user/dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-speedometer2 text-info"></i> <p>Dashboard Tugas</p>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>user/schedules.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'schedules.php' || basename($_SERVER['PHP_SELF']) == 'schedule-detail.php' || basename($_SERVER['PHP_SELF']) == 'inspection-form.php') ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-calendar-event text-warning"></i> <p>Jadwal Inspeksi</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= BASE_URL ?>user/inspection-history.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'inspection-history.php' ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-clock-history text-success"></i> <p>Riwayat Kerja</p>
                    </a>
                </li>

                <li class="nav-header text-uppercase text-secondary small fw-bold px-3 py-1 mt-2" style="font-size: 0.7rem; letter-spacing: 0.05em;">Lainnya</li>

                <li class="nav-item">
                    <a href="<?= BASE_URL ?>public/buildings/index.php" target="_blank" class="nav-link">
                        <i class="nav-icon bi bi-box-arrow-up-right text-light"></i> <p>Portal Bangunan Publik</p>
                    </a>
                </li>
                
            </ul>
        </nav>
    </div>
</aside>
