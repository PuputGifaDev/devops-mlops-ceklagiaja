<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../../config/database.php';

$statusFilter = $_GET['status'] ?? '';
$allowed = ['open','in_progress','completed','not_actionable'];
if ($statusFilter !== '' && !in_array($statusFilter, $allowed, true)) $statusFilter = '';

// Ringkasan per status
$summary = [];
foreach ($pdo->query("SELECT follow_up_status, COUNT(*) c FROM inspection_findings GROUP BY follow_up_status") as $r) {
    $summary[$r['follow_up_status']] = (int)$r['c'];
}

$sql = "SELECT f.id, f.title, f.priority, f.follow_up_status, f.created_at,
               b.name AS building_name, i.inspection_number,
               (SELECT COUNT(*) FROM follow_ups fu WHERE fu.finding_id = f.id) AS followup_count
        FROM inspection_findings f
        JOIN inspections i ON i.id = f.inspection_id
        JOIN buildings b   ON b.id = i.building_id";
$params = [];
if ($statusFilter !== '') { $sql .= " WHERE f.follow_up_status = :st"; $params['st'] = $statusFilter; }
$sql .= " ORDER BY FIELD(f.priority,'critical','high','medium','low'), f.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Tindak Lanjut';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/admin-sidebar.php';
?>
<style>
.stat-pill { display:inline-block; padding:.15rem .55rem; border-radius:999px; font-size:.75rem; font-weight:600; }
.pill-open { background:#fee2e2; color:#991b1b; }
.pill-in_progress { background:#fef3c7; color:#92400e; }
.pill-completed { background:#dcfce7; color:#166534; }
.pill-not_actionable { background:#e5e7eb; color:#374151; }
</style>

<div class="container-fluid py-4">
  <h1 class="h4 mb-3">Pemantauan Tindak Lanjut</h1>

  <div class="row g-3 mb-3">
    <?php foreach ($allowed as $st): ?>
      <div class="col-md-3">
        <a href="?status=<?= e($st) ?>" class="text-decoration-none">
          <div class="card shadow-sm">
            <div class="card-body">
              <div class="text-muted small"><?= e(str_replace('_',' ',$st)) ?></div>
              <div class="h4 mb-0"><?= (int)($summary[$st] ?? 0) ?></div>
            </div>
          </div>
        </a>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
          <tr><th>Inspeksi</th><th>Bangunan</th><th>Temuan</th><th>Prioritas</th>
              <th>Status</th><th>Riwayat</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada data.</td></tr>
        <?php else: foreach ($rows as $r): ?>
          <tr>
            <td><?= e($r['inspection_number']) ?></td>
            <td><?= e($r['building_name']) ?></td>
            <td><?= e($r['title']) ?></td>
            <td><?= strtoupper($r['priority']) ?></td>
            <td><span class="stat-pill pill-<?= e($r['follow_up_status']) ?>"><?= e(str_replace('_',' ',$r['follow_up_status'])) ?></span></td>
            <td><?= (int)$r['followup_count'] ?> catatan</td>
            <td class="text-end">
              <a href="<?= BASE_URL ?>/admin/follow-ups/update.php?finding_id=<?= (int)$r['id'] ?>"
                 class="btn btn-sm btn-outline-primary">Kelola</a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>