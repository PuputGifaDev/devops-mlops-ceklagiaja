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

if (!$b) { flash('error', 'Data tidak ditemukan.'); header('Location: index.php'); exit; }

$pageTitle = 'Edit Bangunan';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/admin-sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Edit Bangunan</h1>
  <a href="index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
</div>

<?php if ($m = flash('error')): ?><div class="alert alert-danger"><?= e($m) ?></div><?php endif; ?>

<form method="post" action="<?= BASE_URL ?>/actions/building-action.php?op=update">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= $b['id'] ?>">
  <div class="card">
    <div class="card-body row g-3">
      <div class="col-md-4">
        <label class="form-label">Kode Bangunan</label>
        <input name="code" class="form-control" required value="<?= e($b['code']) ?>">
      </div>
      <div class="col-md-8">
        <label class="form-label">Nama Bangunan</label>
        <input name="name" class="form-control" required value="<?= e($b['name']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Tipe</label>
        <input name="building_type" class="form-control" value="<?= e($b['building_type']) ?>">
      </div>
      <div class="col-md-8">
        <label class="form-label">Alamat</label>
        <input name="address" class="form-control" required value="<?= e($b['address']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Kota</label>
        <input name="city" class="form-control" value="<?= e($b['city'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Provinsi</label>
        <input name="province" class="form-control" value="<?= e($b['province'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="active" <?= $b['status']==='active'?'selected':'' ?>>Aktif</option>
          <option value="inactive" <?= $b['status']==='inactive'?'selected':'' ?>>Nonaktif</option>
        </select>
      </div>
    </div>
    <div class="card-footer d-flex gap-2">
      <button class="btn btn-primary"><i class="fa-solid fa-save"></i> Simpan Perubahan</button>
      <a href="index.php" class="btn btn-secondary">Batal</a>
    </div>
  </div>
</form>

<form method="post" action="<?= BASE_URL ?>/actions/building-action.php?op=delete" class="mt-3" onsubmit="return confirm('Nonaktifkan bangunan ini?');">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= $b['id'] ?>">
  <button class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-ban"></i> Nonaktifkan Bangunan</button>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>