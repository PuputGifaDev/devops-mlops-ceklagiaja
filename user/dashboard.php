<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_role('user');

$uid = (int)$_SESSION['user_id'];

// ----- Statistik pribadi -----
// Tugas aktif (scheduled + in_progress)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM inspections
                       WHERE assigned_user_id = ? AND status IN ('scheduled','in_progress')");
$stmt->execute([$uid]);
$statAktif = (int)$stmt->fetchColumn();

// Menunggu review (sudah dikirim, tunggu admin)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM inspections
                       WHERE assigned_user_id = ? AND status = 'pending_review'");
$stmt->execute([$uid]);
$statPending = (int)$stmt->fetchColumn();

// Perlu revisi
$stmt = $pdo->prepare("SELECT COUNT(*) FROM inspections
                       WHERE assigned_user_id = ? AND status = 'revision'");
$stmt->execute([$uid]);
$statRevisi = (int)$stmt->fetchColumn();

// Disetujui (bulan ini)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM inspections
                       WHERE assigned_user_id = ? AND status = 'approved'
                       AND approved_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");
$stmt->execute([$uid]);
$statApprovedBulanIni = (int)$stmt->fetchColumn();

// ----- Tugas terdekat (7 hari ke depan, belum selesai) -----
$stmt = $pdo->prepare(
    "SELECT i.*, b.name AS building_name, b.code AS building_code, b.address
     FROM inspections i
     JOIN buildings b ON b.id = i.building_id
     WHERE i.assigned_user_id = ?
       AND i.status IN ('scheduled','in_progress')
       AND i.scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
     ORDER BY i.scheduled_date ASC
     LIMIT 5"
);
$stmt->execute([$uid]);
$tugasTerdekat = $stmt->fetchAll();

// ----- Tugas revisi (prioritas tindakan) -----
$stmt = $pdo->prepare(
    "SELECT i.*, b.name AS building_name, b.code AS building_code
     FROM inspections i
     JOIN buildings b ON b.id = i.building_id
     WHERE i.assigned_user_id = ? AND i.status = 'revision'
     ORDER BY i.updated_at DESC
     LIMIT 5"
);
$stmt->execute([$uid]);
$tugasRevisi = $stmt->fetchAll();

// ----- Grafik 6 bulan terakhir: jumlah tugas diselesaikan per bulan -----
$stmt = $pdo->prepare(
    "SELECT DATE_FORMAT(approved_at, '%Y-%m') AS ym, COUNT(*) AS total
     FROM inspections
     WHERE assigned_user_id = ?
       AND status = 'approved'
       AND approved_at >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 5 MONTH)
     GROUP BY ym
     ORDER BY ym ASC"
);
$stmt->execute([$uid]);
$monthly = $stmt->fetchAll();
$map = array_column($monthly, 'total', 'ym');

$labels = [];
$values = [];
for ($i = 5; $i >= 0; $i--) {
    $ym = date('Y-m', strtotime("-$i months"));
    $labels[] = date('M Y', strtotime($ym . '-01'));
    $values[] = (int)($map[$ym] ?? 0);
}

// ----- Ringkasan temuan yang user buat (bisa dilihat, tapi bukan wewenang untuk mengubah follow-up) -----
$stmt = $pdo->prepare(
    "SELECT f.follow_up_status, COUNT(*) AS total
     FROM inspection_findings f
     JOIN inspections i ON i.id = f.inspection_id
     WHERE i.assigned_user_id = ?
     GROUP BY f.follow_up_status"
);
$stmt->execute([$uid]);
$findingsBreakdown = array_column($stmt->fetchAll(), 'total', 'follow_up_status');

$statusBadge = [
    'scheduled'=>'secondary','in_progress'=>'info','pending_review'=>'warning',
    'revision'=>'danger','approved'=>'success','cancelled'=>'dark',
];
$statusLabel = [
    'scheduled'=>'Dijadwalkan','in_progress'=>'Dikerjakan','pending_review'=>'Menunggu Review',
    'revision'=>'Perlu Revisi','approved'=>'Disetujui','cancelled'=>'Dibatalkan',
];

$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-sidebar.php';
?>

<main class="app-content">
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
      <h1 class="h3 mb-0">Halo, <?= e($_SESSION['name']) ?> 👋</h1>
      <p class="text-muted mb-0">Berikut ringkasan tugas inspeksi Anda.</p>
    </div>
    <a href="<?= BASE_URL ?>/user/schedules.php" class="btn btn-primary">
      <i class="fa-solid fa-list-check me-1"></i> Lihat Semua Tugas
    </a>
  </div>

  <?php if ($msg = flash('success')): ?>
    <div class="alert alert-success alert-dismissible-auto"><?= e($msg) ?></div>
  <?php endif; ?>
  <?php if ($msg = flash('error')): ?>
    <div class="alert alert-danger alert-dismissible-auto"><?= e($msg) ?></div>
  <?php endif; ?>

  <!-- Kartu Statistik -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="text-muted small">Tugas Aktif</div>
              <div class="h3 mb-0"><?= $statAktif ?></div>
              <small class="text-muted">dijadwalkan / dikerjakan</small>
            </div>
            <span class="stat-icon bg-primary-subtle text-primary">
              <i class="fa-solid fa-clipboard-list"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="text-muted small">Menunggu Review</div>
              <div class="h3 mb-0"><?= $statPending ?></div>
              <small class="text-muted">sudah dikirim</small>
            </div>
            <span class="stat-icon bg-warning-subtle text-warning">
              <i class="fa-solid fa-hourglass-half"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="text-muted small">Perlu Revisi</div>
              <div class="h3 mb-0 text-danger"><?= $statRevisi ?></div>
              <small class="text-muted">menunggu perbaikan</small>
            </div>
            <span class="stat-icon bg-danger-subtle text-danger">
              <i class="fa-solid fa-rotate-left"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="text-muted small">Disetujui</div>
              <div class="h3 mb-0 text-success"><?= $statApprovedBulanIni ?></div>
              <small class="text-muted">bulan ini</small>
            </div>
            <span class="stat-icon bg-success-subtle text-success">
              <i class="fa-solid fa-circle-check"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <!-- Kolom kiri -->
    <div class="col-lg-8">
      <!-- Tugas terdekat -->
      <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h2 class="h6 mb-0"><i class="fa-solid fa-calendar-day text-primary me-1"></i> Tugas 7 Hari ke Depan</h2>
          <a href="<?= BASE_URL ?>/user/schedules.php?status=scheduled" class="small text-decoration-none">Lihat semua</a>
        </div>
        <div class="card-body p-0">
          <?php if (!$tugasTerdekat): ?>
            <p class="text-muted p-3 mb-0">Tidak ada tugas dalam 7 hari ke depan. 🎉</p>
          <?php else: ?>
            <div class="list-group list-group-flush">
              <?php foreach ($tugasTerdekat as $t): ?>
                <a href="<?= BASE_URL ?>/user/schedule-detail.php?id=<?= (int)$t['id'] ?>"
                   class="list-group-item list-group-item-action">
                  <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="flex-grow-1">
                      <div class="fw-semibold"><?= e($t['title']) ?></div>
                      <small class="text-muted">
                        <code><?= e($t['inspection_number']) ?></code> ·
                        <?= e($t['building_name']) ?> (<?= e($t['building_code']) ?>)
                      </small>
                    </div>
                    <div class="text-end">
                      <span class="badge bg-<?= $statusBadge[$t['status']] ?>">
                        <?= $statusLabel[$t['status']] ?>
                      </span>
                      <div class="small text-muted mt-1"><?= format_tanggal($t['scheduled_date'], 'd M Y') ?></div>
                    </div>
                  </div>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Tugas revisi -->
      <?php if ($tugasRevisi): ?>
      <div class="card mb-3 border-danger">
        <div class="card-header bg-danger-subtle d-flex justify-content-between align-items-center">
          <h2 class="h6 mb-0 text-danger">
            <i class="fa-solid fa-triangle-exclamation me-1"></i> Perlu Revisi (<?= count($tugasRevisi) ?>)
          </h2>
        </div>
        <div class="card-body p-0">
          <div class="list-group list-group-flush">
            <?php foreach ($tugasRevisi as $t): ?>
              <a href="<?= BASE_URL ?>/user/schedule-detail.php?id=<?= (int)$t['id'] ?>"
                 class="list-group-item list-group-item-action">
                <div class="d-flex justify-content-between">
                  <div>
                    <div class="fw-semibold"><?= e($t['title']) ?></div>
                    <small class="text-muted">
                      <?= e($t['building_name']) ?> ·
                      Direview: <?= format_tanggal($t['updated_at'], 'd M Y') ?>
                    </small>
                  </div>
                  <span class="badge bg-danger align-self-start">Perlu diperbaiki</span>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Grafik 6 bulan -->
      <div class="card">
        <div class="card-header"><h2 class="h6 mb-0">Inspeksi Disetujui (6 Bulan Terakhir)</h2></div>
        <div class="card-body">
          <canvas id="chart-user-monthly" height="100"
                  data-labels='<?= json_encode($labels) ?>'
                  data-values='<?= json_encode($values) ?>'></canvas>
        </div>
      </div>
    </div>

    <!-- Kolom kanan -->
    <div class="col-lg-4">
      <!-- Breakdown temuan -->
      <div class="card mb-3">
        <div class="card-header"><h2 class="h6 mb-0">Status Tindak Lanjut Temuan Anda</h2></div>
        <div class="card-body">
          <?php
            $fuLabels = ['open'=>'Terbuka','in_progress'=>'Diproses','completed'=>'Selesai','not_actionable'=>'Tidak Dapat Ditindaklanjuti'];
            $fuBadges = ['open'=>'danger','in_progress'=>'warning','completed'=>'success','not_actionable'=>'secondary'];
            if (!$findingsBreakdown): ?>
              <p class="text-muted small mb-0">Belum ada temuan.</p>
          <?php else: foreach ($fuLabels as $key => $label):
              $count = (int)($findingsBreakdown[$key] ?? 0);
              if ($count === 0) continue; ?>
              <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <span class="small"><?= $label ?></span>
                <span class="badge bg-<?= $fuBadges[$key] ?>"><?= $count ?></span>
              </div>
          <?php endforeach; endif; ?>
        </div>
      </div>

      <!-- Info -->
      <div class="card bg-light">
        <div class="card-body">
          <h3 class="h6 mb-2"><i class="fa-solid fa-lightbulb text-warning me-1"></i> Pengingat</h3>
          <ul class="small text-muted mb-0 ps-3">
            <li>Simpan draft secara berkala saat pengisian.</li>
            <li>Pastikan semua item checklist wajib terisi sebelum kirim.</li>
            <li>Foto temuan sebaiknya jelas dan menyertakan lokasi.</li>
            <li>Hasil yang sudah dikirim tidak bisa diedit kecuali diminta revisi oleh Admin.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/charts.js"></script>
<script>
  // charts.js akan otomatis merender elemen canvas[data-labels][data-values]
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>