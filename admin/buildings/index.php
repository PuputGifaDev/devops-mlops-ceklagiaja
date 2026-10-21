<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../../config/database.php';

$q      = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

$where = ['1=1']; $params = [];
if ($q !== '') {
    $where[] = '(code LIKE :q OR name LIKE :q OR address LIKE :q)';
    $params['q'] = "%$q%";
}
if (in_array($status, ['active', 'inactive'], true)) {
    $where[] = 'status = :status';
    $params['status'] = $status;
}
$whereSql = implode(' AND ', $where);

$cntStmt = $pdo->prepare("SELECT COUNT(*) FROM buildings WHERE $whereSql");
$cntStmt->execute($params);
$total = (int)$cntStmt->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM buildings WHERE $whereSql ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$rows = $stmt->fetchAll();
$totalPages = (int)ceil($total / $perPage);

$pageTitle = 'Data Bangunan';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/admin-sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Data Bangunan (Total: <?= $total ?>)</h1>
  <a href="create.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Tambah Bangunan</a>
</div>

<?php if ($m = flash('success')): ?><div class="alert alert-success alert-dismissible-auto"><?= e($m) ?></div><?php endif; ?>
<?php if ($m = flash('error')): ?><div class="alert alert-danger"><?= e($m) ?></div><?php endif; ?>

<form class="row g-2 mb-3" method="get">
  <div class="col-md-5"><input name="q" value="<?= e($q) ?>" class="form-control" placeholder="Cari kode, nama, alamat..."></div>
  <div class="col-md-3">
    <select name="status" class="form-select">
      <option value="">Semua Status</option>
      <option value="active" <?= $status==='active'?'selected':'' ?>>Aktif</option>
      <option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Nonaktif</option>
    </select>
  </div>
  <div class="col-md-2"><button class="btn btn-outline-primary w-100">Filter</button></div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr><th>Kode</th><th>Nama Bangunan</th><th>Tipe</th><th>Kota/Provinsi</th><th>Status</th><th class="text-end">Aksi</th></tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td><code><?= e($r['code']) ?></code></td>
          <td><?= e($r['name']) ?></td>
          <td><?= e($r['building_type']) ?></td>
          <td><?= e(trim(($r['city'] ?? '') . ', ' . ($r['province'] ?? ''), ', ')) ?: '-' ?></td>
          <td>
            <span class="badge <?= $r['status']==='active'?'bg-success':'bg-secondary' ?>">
              <?= $r['status']==='active'?'Aktif':'Nonaktif' ?>
            </span>
          </td>
          <td class="text-end text-nowrap">
            <a href="show.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-info"><i class="fa-solid fa-eye"></i></a>
            <a href="edit.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-warning"><i class="fa-solid fa-pen"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="6" class="text-center py-4 text-muted">Tidak ada data bangunan.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($totalPages > 1): ?>
<nav class="mt-3">
  <ul class="pagination mb-0">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <li class="page-item <?= $i==$page?'active':'' ?>"><a class="page-link" href="?<?= e(http_build_query(array_merge($_GET, ['page'=>$i]))) ?>"><?= $i ?></a></li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>