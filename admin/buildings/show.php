<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../../config/database.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM buildings WHERE id = ?');
$stmt->execute([$id]);
$b = $stmt->fetch();

if (!$b) { flash('error', 'Tidak ditemukan.'); header('Location: index.php'); exit; }

$pageTitle = 'Detail Bangunan';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/admin-sidebar.php';
?>

<div class="d-flex justify-content-between mb-3">
  <h1 class="h4 mb-0"><?= e($b['name']) ?></h1>
  <a href="index.php" class="btn btn-secondary">Kembali</a>
</div>

<div class="card"><div class="card-body">
  <dl class="row mb-0">
    <dt class="col-sm-3">Kode</dt><dd class="col-sm-9"><?= e($b['code']) ?></dd>
    <dt class="col-sm-3">Tipe</dt><dd class="col-sm-9"><?= e($b['building_type']) ?></dd>
    <dt class="col-sm-3">Alamat</dt><dd class="col-sm-9"><?= nl2br(e($b['address'])) ?></dd>
    <dt class="col-sm-3">Kota / Provinsi</dt><dd class="col-sm-9"><?= e($b['city'] ?? '-') ?> / <?= e($b['province'] ?? '-') ?></dd>
    <dt class="col-sm-3">Status</dt>
    <dd class="col-sm-9">
      <span class="badge <?= $b['status']==='active'?'bg-success':'bg-secondary' ?>"><?= $b['status'] ?></span>
    </dd>
  </dl>
</div></div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>