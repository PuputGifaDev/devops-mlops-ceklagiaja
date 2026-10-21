<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');

$pageTitle = 'Jadwal Inspeksi';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/admin-sidebar.php';

// ----- Filter -----
$q        = trim($_GET['q'] ?? '');
$status   = $_GET['status'] ?? '';
$userId   = (int)($_GET['user_id'] ?? 0);
$fromDate = $_GET['from'] ?? '';
$toDate   = $_GET['to']   ?? '';
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 15;

$where  = ['1=1'];
$params = [];

if ($q !== '') {
    $where[] = '(i.inspection_number LIKE :q OR i.title LIKE :q OR b.name LIKE :q)';
    $params['q'] = "%$q%";
}
$validStatuses = ['scheduled','in_progress','pending_review','revision','approved','cancelled'];
if (in_array($status, $validStatuses, true)) {
    $where[] = 'i.status = :status';
    $params['status'] = $status;
}
if ($userId > 0) {
    $where[] = 'i.assigned_user_id = :uid';
    $params['uid'] = $userId;
}
if ($fromDate !== '') {
    $where[] = 'i.scheduled_date >= :from';
    $params['from'] = $fromDate;
}
if ($toDate !== '') {
    $where[] = 'i.scheduled_date <= :to';
    $params['to'] = $toDate;
}
$whereSql = implode(' AND ', $where);

// Hitung total
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM inspections i
    JOIN buildings b ON b.id = i.building_id
    WHERE $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// Ambil data
$sql = "SELECT i.*, b.name AS building_name, b.code AS building_code,
               u.name AS assignee_name, c.name AS creator_name,
               (SELECT COUNT(*) FROM inspection_findings f WHERE f.inspection_id = i.id) AS finding_count
        FROM inspections i
        JOIN buildings b ON b.id = i.building_id
        JOIN users u ON u.id = i.assigned_user_id
        JOIN users c ON c.id = i.created_by
        WHERE $whereSql
        ORDER BY i.scheduled_date DESC, i.id DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$inspections = $stmt->fetchAll();

// Data dropdown filter
$users = $pdo->query("SELECT id, name FROM users WHERE role='user' AND is_active=1 ORDER BY name")->fetchAll();

// Badge warna per status
$statusBadge = [
    'scheduled'      => 'secondary',
    'in_progress'    => 'info',
    'pending_review' => 'warning',
    'revision'       => 'danger',
    'approved'       => 'success',
    'cancelled'      => 'dark',
];
$statusLabel = [
    'scheduled'      => 'Dijadwalkan',
    'in_progress'    => 'Dikerjakan',
    'pending_review' => 'Menunggu Review',
    'revision'       => 'Revisi',
    'approved'       => 'Disetujui',
    'cancelled'      => 'Dibatalkan',
];
?>

<main class="app-content">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 mb-0">Jadwal Inspeksi</h1>
      <p class="text-muted mb-0">Total <?= number_format($total) ?> inspeksi</p>
    </div>
    <a href="<?= BASE_URL ?>/admin/inspections/create.php" class="btn btn-primary">
      <i class="fa-solid fa-plus me-1"></i> Buat Jadwal
    </a>
  </div>

  <?php if ($msg = flash('success')): ?>
    <div class="alert alert-success alert-dismissible-auto"><?= e($msg) ?></div>
  <?php endif; ?>
  <?php if ($msg = flash('error')): ?>
    <div class="alert alert-danger alert-dismissible-auto"><?= e($msg) ?></div>
  <?php endif; ?>

  <div class="card mb-3">
    <div class="card-body">
      <form method="get" class="row g-2">
        <div class="col-md-3">
          <input name="q" value="<?= e($q) ?>" class="form-control" placeholder="Cari nomor / judul / bangunan">
        </div>
        <div class="col-md-2">
          <select name="status" class="form-select">
            <option value="">Semua Status</option>
            <?php foreach ($statusLabel as $k => $v): ?>
              <option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <select name="user_id" class="form-select">
            <option value="">Semua Petugas</option>
            <?php foreach ($users as $u): ?>
              <option value="<?= (int)$u['id'] ?>" <?= $userId === (int)$u['id'] ? 'selected' : '' ?>>
                <?= e($u['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <input type="date" name="from" value="<?= e($fromDate) ?>" class="form-control">
        </div>
        <div class="col-md-2">
          <input type="date" name="to" value="<?= e($toDate) ?>" class="form-control">
        </div>
        <div class="col-12 d-flex gap-2">
          <button class="btn btn-outline-primary">Filter</button>
          <a href="<?= BASE_URL ?>/admin/inspections/index.php" class="btn btn-outline-secondary">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Nomor</th>
            <th>Bangunan</th>
            <th>Judul</th>
            <th>Petugas</th>
            <th>Tanggal</th>
            <th>Status</th>
            <th>Temuan</th>
            <th class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$inspections): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">Belum ada jadwal inspeksi.</td></tr>
          <?php else: foreach ($inspections as $i): ?>
            <tr>
              <td><code><?= e($i['inspection_number']) ?></code></td>
              <td>
                <div class="fw-semibold"><?= e($i['building_name']) ?></div>
                <small class="text-muted"><?= e($i['building_code']) ?></small>
              </td>
              <td><?= e($i['title']) ?></td>
              <td><?= e($i['assignee_name']) ?></td>
              <td><?= format_tanggal($i['scheduled_date']) ?></td>
              <td>
                <span class="badge bg-<?= $statusBadge[$i['status']] ?? 'secondary' ?>">
                  <?= $statusLabel[$i['status']] ?? e($i['status']) ?>
                </span>
              </td>
              <td>
                <?php if ($i['finding_count'] > 0): ?>
                  <span class="badge bg-danger"><?= (int)$i['finding_count'] ?></span>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <a href="<?= BASE_URL ?>/admin/inspections/show.php?id=<?= (int)$i['id'] ?>"
                   class="btn btn-sm btn-outline-primary">Detail</a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
      <div class="card-body border-top">
        <nav>
          <ul class="pagination justify-content-center mb-0">
            <?php
              $qs = $_GET;
              for ($p = 1; $p <= $totalPages; $p++):
                $qs['page'] = $p;
            ?>
              <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                <a class="page-link" href="?<?= e(http_build_query($qs)) ?>"><?= $p ?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>
      </div>
    <?php endif; ?>
  </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>