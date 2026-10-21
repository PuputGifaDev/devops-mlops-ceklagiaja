<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');

$pageTitle = 'Tambah Bangunan';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/admin-sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Tambah Bangunan</h1>
  <a href="index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
</div>

<?php if ($m = flash('error')): ?><div class="alert alert-danger"><?= e($m) ?></div><?php endif; ?>

<form method="post" action="<?= BASE_URL ?>/actions/building-action.php?op=create">
  <?= csrf_field() ?>
  <div class="card">
    <div class="card-body row g-3">
      <div class="col-md-4">
        <label class="form-label">Kode Bangunan <span class="text-danger">*</span></label>
        <input name="code" class="form-control" required placeholder="cth: GD-A001" value="<?= e($_POST['code'] ?? '') ?>">
      </div>
      <div class="col-md-8">
        <label class="form-label">Nama Bangunan <span class="text-danger">*</span></label>
        <input name="name" class="form-control" required value="<?= e($_POST['name'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Tipe <span class="text-danger">*</span></label>
        <select name="building_type" class="form-select" required>
          <?php foreach(['Perkantoran','Sekolah','Tempat Ibadah','Rumah Sakit','Gudang','Rumah Tinggal','Lainnya'] as $t): ?>
            <option <?= ($_POST['building_type']??'')===$t?'selected':'' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-8">
        <label class="form-label">Alamat Lengkap <span class="text-danger">*</span></label>
        <input name="address" class="form-control" required value="<?= e($_POST['address'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Kota/Kabupaten</label>
        <input name="city" class="form-control" value="<?= e($_POST['city'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Provinsi</label>
        <input name="province" class="form-control" value="<?= e($_POST['province'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="active">Aktif</option>
          <option value="inactive">Nonaktif</option>
        </select>
      </div>
    </div>
    <div class="card-footer d-flex gap-2">
      <button class="btn btn-primary"><i class="fa-solid fa-save"></i> Simpan</button>
      <a href="index.php" class="btn btn-secondary">Batal</a>
    </div>
  </div>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>