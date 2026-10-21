<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../../config/database.php';

$q        = trim($_GET['q'] ?? '');
$priority = $_GET['priority'] ?? '';
$status   = $_GET['status'] ?? '';
$allowedPriority = ['low','medium','high','critical'];
$allowedStatus   = ['open','in_progress','completed','not_actionable'];
if ($priority !== '' && !in_array($priority, $allowedPriority, true)) $priority = '';
if ($status !== '' && !in_array($status, $allowedStatus, true)) $status = '';

$sql = "SELECT f.*, i.inspection_number, i.title AS inspection_title,
               b.name AS building_name, u.name AS assignee_name
        FROM inspection_findings f
        JOIN inspections i ON i.id = f.inspection_id
        JOIN buildings b   ON b.id = i.building_id
        JOIN users u       ON u.id = i.assigned_user_id
        WHERE 1=1";
$params = [];
if ($q !== '') {
    $sql .= " AND (f.title LIKE :q OR f.location_description LIKE :q OR b.name LIKE :q)";
    $params['q'] = "%$q%";
}
if ($priority !== '') { $sql .= " AND f.priority = :pr"; $params['pr'] = $priority; }
if ($status !== '')   { $sql .= " AND f.follow_up_status = :st"; $params['st'] = $status; }
$sql .= " ORDER BY FIELD(f.priority,'critical','high','medium','low'), f.created_at DESC LIMIT 500";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Semua Temuan';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/admin-sidebar.php';
?>
<style>
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

<div class="container-fluid py-4">
  <h1 class="h4 mb-3">Semua Temuan Kerusakan</h1>

  <form method="get" class="row g-2 mb-3">
    <div class="col-md-4">
      <input name="q" value="<?= e($q) ?>" class="form-control" placeholder="Cari judul / lokasi / bangunan">
    </div>
    <div class="col-md-3">
      <select name="priority" class="form-select">
        <option value="">— Semua prioritas —</option>
        <?php foreach ($allowedPriority as $p): ?>
          <option value="<?= $p ?>" <?= $priority === $p ? 'selected' : '' ?>><?= strtoupper($p) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <select name="status" class="form-select">
        <option value="">— Semua status —</option>
        <?php foreach ($allowedStatus as $st): ?>
          <option value="<?= $st ?>" <?= $status === $st ? 'selected' : '' ?>><?= str_replace('_',' ',$st) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <button class="btn btn-primary w-100"><i class="fas fa-filter"></i> Filter</button>
    </div>
  </form>

  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th>Inspeksi</th><th>Bangunan</th><th>Petugas</th><th>Judul</th>
            <th>Lokasi</th><th>Prioritas</th><th>Status</th><th></th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">Tidak ada data.</td></tr>
        <?php else: foreach ($rows as $r): ?>
          <tr>
            <td><?= e($r['inspection_number']) ?></td>
            <td><?= e($r['building_name']) ?></td>
            <td><?= e($r['assignee_name']) ?></td>
            <td><?= e($r['title']) ?></td>
            <td><?= e($r['location_description']) ?></td>
            <td><span class="badge badge-<?= e($r['priority']) ?>"><?= strtoupper($r['priority']) ?></span></td>
            <td><span class="stat-pill pill-<?= e($r['follow_up_status']) ?>"><?= e(str_replace('_',' ',$r['follow_up_status'])) ?></span></td>
            <td class="text-end">
              <a href="<?= BASE_URL ?>/admin/findings/show.php?id=<?= (int)$r['id'] ?>"
                 class="btn btn-sm btn-outline-primary">Detail</a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>