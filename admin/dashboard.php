<?php
require_once __DIR__ . '/../config/app.php';
requireRole('admin');

$pageTitle = 'Dashboard Utama Administrator';

// Query Statistik
$totalBuildings = (int)$pdo->query("SELECT COUNT(*) FROM buildings WHERE status = 'active'")->fetchColumn();
$totalInspections = (int)$pdo->query("SELECT COUNT(*) FROM inspections")->fetchColumn();
$pendingReviews = (int)$pdo->query("SELECT COUNT(*) FROM inspections WHERE status = 'pending_review'")->fetchColumn();
$openFindings = (int)$pdo->query("SELECT COUNT(*) FROM inspection_findings WHERE follow_up_status IN ('open', 'in_progress')")->fetchColumn();

// Ambil inspeksi yang menunggu review
$stmtPending = $pdo->query("
    SELECT i.*, b.name AS building_name, u.name AS inspector_name
    FROM inspections i
    JOIN buildings b ON b.id = i.building_id
    JOIN users u ON u.id = i.assigned_user_id
    WHERE i.status = 'pending_review'
    ORDER BY i.submitted_at ASC
    LIMIT 5
");
$pendingList = $stmtPending->fetchAll();

// Ambil temuan dengan prioritas kritis / tinggi yang masih terbuka
$stmtUrgent = $pdo->query("
    SELECT f.*, b.name AS building_name, i.inspection_number
    FROM inspection_findings f
    JOIN inspections i ON i.id = f.inspection_id
    JOIN buildings b ON b.id = i.building_id
    WHERE f.priority IN ('critical', 'high') AND f.follow_up_status != 'completed'
    ORDER BY CASE f.priority WHEN 'critical' THEN 1 ELSE 2 END, f.created_at DESC
    LIMIT 5
");
$urgentFindings = $stmtUrgent->fetchAll();

// Ambil 6 log aktivitas terbaru
$stmtLogs = $pdo->query("
    SELECT l.*, u.name AS user_name
    FROM activity_logs l
    LEFT JOIN users u ON u.id = l.user_id
    ORDER BY l.created_at DESC
    LIMIT 6
");
$recentLogs = $stmtLogs->fetchAll();

// Data untuk Chart.js: Distribusi status temuan
$findingsStats = $pdo->query("
    SELECT 
        SUM(CASE WHEN follow_up_status = 'open' THEN 1 ELSE 0 END) as total_open,
        SUM(CASE WHEN follow_up_status = 'in_progress' THEN 1 ELSE 0 END) as total_progress,
        SUM(CASE WHEN follow_up_status = 'completed' THEN 1 ELSE 0 END) as total_completed
    FROM inspection_findings
")->fetch();

$fOpen = (int)($findingsStats['total_open'] ?? 0);
$fProgress = (int)($findingsStats['total_progress'] ?? 0);
$fCompleted = (int)($findingsStats['total_completed'] ?? 0);

include __DIR__ . '/../includes/header.php';
?>

<!-- Banner Selamat Datang -->
<div class="card border-0 shadow-sm mb-4 bg-primary text-white overflow-hidden position-relative">
    <div class="card-body p-4 position-relative" style="z-index: 2;">
        <div class="row align-items-center">
            <div class="col-md-8">
                <span class="badge bg-white text-primary mb-2 fw-semibold px-3 py-1">Sistem Inspeksi Terpadu</span>
                <h3 class="fw-bold mb-1">Selamat Datang, <?= esc(currentUserName()) ?>! 👋</h3>
                <p class="mb-0 text-white-50">Pantau seluruh aset bangunan, jadwal pemeriksaan teknis, dan tindak lanjut temuan kerusakan secara real-time.</p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="<?= BASE_URL ?>admin/inspections/create.php" class="btn btn-light fw-bold px-3 py-2 me-2 shadow-sm">
                    <i class="bi bi-calendar-plus me-1 text-primary"></i> Buat Jadwal
                </a>
                <a href="<?= BASE_URL ?>admin/buildings/create.php" class="btn btn-outline-light px-3 py-2 shadow-sm">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Bangunan
                </a>
            </div>
        </div>
    </div>
</div>

<!-- 4 Kartu Metrik Utama -->
<div class="row g-3 mb-4">
    <!-- Bangunan Terdaftar -->
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Bangunan Terdaftar</div>
                        <div class="fs-2 fw-bold text-dark"><?= $totalBuildings ?></div>
                        <small class="text-success"><i class="bi bi-check-circle me-1"></i>Status Aktif</small>
                    </div>
                    <div class="bg-primary bg-opacity-10 text-primary rounded-4 p-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                        <i class="bi bi-building fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Jadwal Inspeksi -->
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Total Inspeksi</div>
                        <div class="fs-2 fw-bold text-dark"><?= $totalInspections ?></div>
                        <small class="text-muted"><i class="bi bi-clipboard-check me-1"></i>Seluruh Riwayat</small>
                    </div>
                    <div class="bg-info bg-opacity-10 text-info rounded-4 p-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                        <i class="bi bi-calendar-check fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Menunggu Review -->
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Menunggu Review</div>
                        <div class="fs-2 fw-bold text-warning"><?= $pendingReviews ?></div>
                        <small class="text-warning"><i class="bi bi-clock-history me-1"></i>Perlu Verifikasi</small>
                    </div>
                    <div class="bg-warning bg-opacity-10 text-warning rounded-4 p-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                        <i class="bi bi-hourglass-split fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Temuan Perlu Tindak Lanjut -->
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Temuan Terbuka</div>
                        <div class="fs-2 fw-bold text-danger"><?= $openFindings ?></div>
                        <small class="text-danger"><i class="bi bi-exclamation-octagon me-1"></i>Perlu Ditindaklanjuti</small>
                    </div>
                    <div class="bg-danger bg-opacity-10 text-danger rounded-4 p-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                        <i class="bi bi-tools fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Grafik Analisis & Tren -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="bi bi-graph-up text-primary me-2"></i>Tren Frekuensi Inspeksi (Tahun 2026)</h6>
                <span class="badge bg-primary bg-opacity-10 text-primary">Periode Mei - Okt 2026</span>
            </div>
            <div class="card-body">
                <div style="height: 280px; position: relative;">
                    <canvas id="inspectionTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-pie-chart text-primary me-2"></i>Status Tindak Lanjut Temuan</h6>
            </div>
            <div class="card-body d-flex flex-column justify-content-center">
                <div style="height: 200px; position: relative;">
                    <canvas id="findingStatusChart"></canvas>
                </div>
                <div class="d-flex justify-content-around text-center mt-3 pt-2 border-top">
                    <div>
                        <small class="text-muted d-block">Open</small>
                        <span class="fw-bold text-danger"><?= $fOpen ?></span>
                    </div>
                    <div>
                        <small class="text-muted d-block">In Progress</small>
                        <span class="fw-bold text-warning"><?= $fProgress ?></span>
                    </div>
                    <div>
                        <small class="text-muted d-block">Selesai</small>
                        <span class="fw-bold text-success"><?= $fCompleted ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bagian Konten Operasional: Menunggu Review & Temuan Mendesak -->
<div class="row g-4 mb-4">
    <!-- Inspeksi Menunggu Review -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-clipboard-pulse text-warning me-2"></i>Inspeksi Menunggu Review</h6>
                <a href="<?= BASE_URL ?>admin/inspections/index.php?status=pending_review" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">No. Inspeksi</th>
                                <th>Bangunan</th>
                                <th>Petugas</th>
                                <th class="text-end pe-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pendingList)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        <i class="bi bi-check2-all text-success fs-3 d-block mb-1"></i>
                                        Semua laporan inspeksi telah diverifikasi.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pendingList as $p): ?>
                                <tr>
                                    <td class="ps-3 font-monospace small fw-bold text-primary"><?= esc($p['inspection_number']) ?></td>
                                    <td>
                                        <div class="fw-medium text-truncate" style="max-width: 180px;"><?= esc($p['building_name']) ?></div>
                                        <small class="text-muted"><?= date('d M Y', strtotime($p['scheduled_date'])) ?></small>
                                    </td>
                                    <td class="small"><?= esc($p['inspector_name']) ?></td>
                                    <td class="text-end pe-3">
                                        <a href="<?= BASE_URL ?>admin/inspections/review.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-warning fw-semibold shadow-sm">
                                            Review <i class="bi bi-arrow-right ms-1"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Temuan Kritis / Mendesak -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-shield-fill-exclamation text-danger me-2"></i>Temuan Prioritas Tinggi / Kritis</h6>
                <a href="<?= BASE_URL ?>admin/findings/index.php" class="btn btn-sm btn-outline-danger">Semua Temuan</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Prioritas</th>
                                <th>Temuan Kerusakan</th>
                                <th>Bangunan</th>
                                <th class="text-end pe-3">Detail</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($urgentFindings)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        <i class="bi bi-shield-check text-success fs-3 d-block mb-1"></i>
                                        Tidak ada temuan berisiko tinggi yang tertunda.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($urgentFindings as $u): ?>
                                <tr>
                                    <td class="ps-3">
                                        <span class="badge bg-<?= $u['priority'] === 'critical' ? 'danger' : 'warning text-dark' ?>">
                                            <?= strtoupper($u['priority']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-truncate" style="max-width: 220px;"><?= esc($u['title']) ?></div>
                                        <small class="text-muted text-truncate d-block" style="max-width: 220px;"><?= esc($u['location_description']) ?></small>
                                    </td>
                                    <td class="small text-truncate" style="max-width: 140px;"><?= esc($u['building_name']) ?></td>
                                    <td class="text-end pe-3">
                                        <a href="<?= BASE_URL ?>admin/findings/show.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Log Aktivitas Terbaru -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-activity text-primary me-2"></i>Riwayat Log Aktivitas Sistem Terkini</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 140px;">Waktu</th>
                        <th style="width: 180px;">Pengguna</th>
                        <th style="width: 120px;">Aksi</th>
                        <th>Deskripsi Aktivitas</th>
                        <th class="text-end pe-3" style="width: 120px;">IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentLogs)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada riwayat log aktivitas.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentLogs as $log): ?>
                        <tr>
                            <td class="ps-3 small text-muted"><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
                            <td class="fw-medium small"><?= esc($log['user_name'] ?: 'Sistem') ?></td>
                            <td>
                                <?php 
                                    $actBadge = 'secondary';
                                    if ($log['action'] == 'APPROVE') $actBadge = 'success';
                                    elseif ($log['action'] == 'CREATE') $actBadge = 'primary';
                                    elseif ($log['action'] == 'SUBMIT') $actBadge = 'warning text-dark';
                                    elseif ($log['action'] == 'LOGIN') $actBadge = 'info text-dark';
                                ?>
                                <span class="badge bg-<?= $actBadge ?> small"><?= strtoupper($log['action']) ?></span>
                            </td>
                            <td class="small"><?= esc($log['description']) ?></td>
                            <td class="text-end pe-3 small text-muted font-monospace"><?= esc($log['ip_address']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Load Chart.js CDN & Init Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Line Chart: Tren Inspeksi Bulanan
    const trendCtx = document.getElementById('inspectionTrendChart');
    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: ['Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt'],
                datasets: [{
                    label: 'Jumlah Inspeksi',
                    data: [2, 4, 3, 6, 5, 7],
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.08)',
                    borderWidth: 3,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#2563eb',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    tension: 0.35,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 10,
                        titleFont: { size: 13 },
                        bodyFont: { size: 12 }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 },
                        grid: { borderDash: [4, 4], color: '#e2e8f0' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // 2. Doughnut Chart: Status Temuan
    const findingCtx = document.getElementById('findingStatusChart');
    if (findingCtx) {
        new Chart(findingCtx, {
            type: 'doughnut',
            data: {
                labels: ['Open', 'In Progress', 'Completed'],
                datasets: [{
                    data: [<?= $fOpen ?>, <?= $fProgress ?>, <?= $fCompleted ?>],
                    backgroundColor: ['#ef4444', '#f59e0b', '#10b981'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 10
                    }
                }
            }
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
