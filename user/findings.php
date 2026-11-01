<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_role('user');
require_once __DIR__ . '/../config/database.php';

$userId = (int)($_SESSION['user_id'] ?? 0);

// Filter opsional
$filterInspection = (int)($_GET['inspection_id'] ?? 0);
$filterStatus     = $_GET['status'] ?? '';
$allowedStatus    = ['open','in_progress','completed','not_actionable'];
if ($filterStatus !== '' && !in_array($filterStatus, $allowedStatus, true)) $filterStatus = '';

$sql = "SELECT f.*, i.inspection_number, i.title AS inspection_title,
               b.name AS building_name
        FROM inspection_findings f
        JOIN inspections i ON i.id = f.inspection_id
        JOIN buildings b   ON b.id = i.building_id
        WHERE i.assigned_user_id = :uid";
$params = ['uid' => $userId];
if ($filterInspection > 0) { $sql .= " AND f.inspection_id = :iid"; $params['iid'] = $filterInspection; }
if ($filterStatus !== '')  { $sql .= " AND f.follow_up_status = :st"; $params['st'] = $filterStatus; }
$sql .= " ORDER BY f.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$findings = $stmt->fetchAll();

// Daftar inspeksi user (untuk dropdown filter)
$stmt = $pdo->prepare(
    "SELECT id, inspection_number, title FROM inspections
     WHERE assigned_user_id = :uid ORDER BY scheduled_date DESC"
);
$stmt->execute(['uid' => $userId]);
$myInspections = $stmt->fetchAll();

$pageTitle = 'Temuan Kerusakan';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-sidebar.php';
?>
<style>
.findings-wrap { padding: 1.25rem; }
.stat-pill { display:inline-block; padding:.15rem .55rem; border-radius:999px; font-size:.75rem; font-weight:600; }
.pill-open { background:#fee2e2; color:#991b1b; }
.pill-in_progress { background:#fef3c7; color:#92400e; }
.pill-completed { background:#dcfce7; color:#166534; }
.pill-not_actionable { background:#e5e7eb; color:#374151; }
.badge-low { background:#e0f2fe; color:#075985; }
.badge-medium { background:#fef9c3; color:#854d0e; }
.badge-high { background:#fed7aa; color:#9a3412; }
.badge-critical { background:#fecaca; color:#991b1b; font-weight:700; }
</style>

<div class="findings-wrap">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Temuan Kerusakan</h1>
    <a href="<?= BASE_URL ?>/user/finding-form.php" class="btn btn-primary">
      <i class="fas fa-plus"></i> Catat Temuan Baru
    </a>
  </div>

  <?php if ($m = flash('success')): ?>
    <div class="alert alert-success alert-dismissible-auto"><?= e($m) ?></div>
  <?php endif; ?>
  <?php if ($m = flash('error')): ?>
    <div class="alert alert-danger alert-dismissible-auto"><?= e($m) ?></div>
  <?php endif; ?>

  <form method="get" class="row g-2 mb-3">
    <div class="col-md-5">
      <select name="inspection_id" class="form-select">
        <option value="">— Semua inspeksi —</option>
        <?php foreach ($myInspections as $ins): ?>
          <option value="<?= (int)$ins['id'] ?>" <?= $filterInspection === (int)$ins['id'] ? 'selected' : '' ?>>
            <?= e($ins['inspection_number']) ?> — <?= e($ins['title']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <select name="status" class="form-select">
        <option value="">— Semua status tindak lanjut —</option>
        <?php foreach ($allowedStatus as $st): ?>
          <option value="<?= e($st) ?>" <?= $filterStatus === $st ? 'selected' : '' ?>>
            <?= e(str_replace('_', ' ', $st)) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <button class="btn btn-outline-secondary w-100"><i class="fas fa-filter"></i> Terapkan</button>
    </div>
  </form>

  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th>Inspeksi</th>
            <th>Bangunan</th>
            <th>Judul Temuan</th>
            <th>Lokasi</th>
            <th>Prioritas</th>
            <th>Status</th>
            <th>Tanggal</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$findings): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">Belum ada temuan.</td></tr>
        <?php else: foreach ($findings as $f): ?>
          <tr>
            <td><?= e($f['inspection_number']) ?></td>
            <td><?= e($f['building_name']) ?></td>
            <td><?= e($f['title']) ?></td>
            <td><?= e($f['location_description']) ?></td>
            <td><span class="badge badge-<?= e($f['priority']) ?>"><?= e(strtoupper($f['priority'])) ?></span></td>
            <td><span class="stat-pill pill-<?= e($f['follow_up_status']) ?>"><?= e(str_replace('_',' ',$f['follow_up_status'])) ?></span></td>
            <td><?= e(format_tanggal($f['created_at'], 'd M Y H:i')) ?></td>
            <td class="text-end">
              <a href="<?= BASE_URL ?>/user/finding-form.php?id=<?= (int)$f['id'] ?>"
                 class="btn btn-sm btn-outline-primary">Detail</a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>