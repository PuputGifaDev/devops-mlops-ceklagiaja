<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_role('user');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';

$userId = (int)($_SESSION['user_id'] ?? 0);
$findingId = (int)($_GET['id'] ?? 0);
$inspectionIdParam = (int)($_GET['inspection_id'] ?? 0);

// Ambil daftar inspeksi yang boleh diisi (status mengizinkan edit)
$stmt = $pdo->prepare(
    "SELECT id, inspection_number, title, status
     FROM inspections
     WHERE assigned_user_id = :uid
       AND status IN ('scheduled','in_progress','revision')
     ORDER BY scheduled_date DESC"
);
$stmt->execute(['uid' => $userId]);
$myInspections = $stmt->fetchAll();

$finding = [
    'id' => 0, 'inspection_id' => $inspectionIdParam, 'detail_id' => null,
    'title' => '', 'location_description' => '', 'description' => '',
    'priority' => 'medium', 'recommendation' => '',
];
$photos = [];

if ($findingId > 0) {
    // Pastikan kepemilikan
    $stmt = $pdo->prepare(
        "SELECT f.* FROM inspection_findings f
         JOIN inspections i ON i.id = f.inspection_id
         WHERE f.id = :id AND i.assigned_user_id = :uid"
    );
    $stmt->execute(['id' => $findingId, 'uid' => $userId]);
    $row = $stmt->fetch();
    if (!$row) { http_response_code(403); exit('Temuan tidak ditemukan atau bukan milik Anda.'); }
    $finding = $row;

    // Jika inspeksi sudah submitted/approved, larang edit
    $stmt = $pdo->prepare("SELECT status FROM inspections WHERE id = ?");
    $stmt->execute([(int)$finding['inspection_id']]);
    $insStatus = $stmt->fetchColumn();
    if (in_array($insStatus, ['pending_review','approved','cancelled'], true)) {
        flash('error', 'Inspeksi sudah dikirim. Temuan tidak dapat diubah.');
        redirect('/user/findings.php');
    }

    $stmt = $pdo->prepare("SELECT * FROM finding_photos WHERE finding_id = ? ORDER BY id");
    $stmt->execute([$findingId]);
    $photos = $stmt->fetchAll();
}

$pageTitle = $findingId ? 'Ubah Temuan' : 'Catat Temuan';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-sidebar.php';
?>
<style>
.photo-thumb { width:96px; height:96px; object-fit:cover; border-radius:8px; cursor:zoom-in; border:1px solid #e5e7eb; }
.photo-list { display:flex; gap:.5rem; flex-wrap:wrap; }
.modal-img { max-width:100%; max-height:80vh; }
</style>

<div class="container-fluid py-4">
  <h1 class="h4 mb-3"><?= e($pageTitle) ?></h1>

  <?php if ($m = flash('error')): ?><div class="alert alert-danger"><?= e($m) ?></div><?php endif; ?>

  <form method="post" action="<?= BASE_URL ?>/actions/finding-action.php"
        enctype="multipart/form-data" class="card shadow-sm p-4">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)$finding['id'] ?>">

    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Inspeksi <span class="text-danger">*</span></label>
        <select name="inspection_id" class="form-select" required>
          <option value="">— Pilih inspeksi —</option>
          <?php foreach ($myInspections as $ins): ?>
            <option value="<?= (int)$ins['id'] ?>"
              <?= (int)$finding['inspection_id'] === (int)$ins['id'] ? 'selected' : '' ?>>
              <?= e($ins['inspection_number']) ?> — <?= e($ins['title']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Prioritas <span class="text-danger">*</span></label>
        <select name="priority" class="form-select" required>
          <?php foreach (['low','medium','high','critical'] as $p): ?>
            <option value="<?= $p ?>" <?= $finding['priority'] === $p ? 'selected' : '' ?>>
              <?= strtoupper($p) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Detail Checklist (opsional)</label>
        <input type="number" name="detail_id" class="form-control"
               value="<?= $finding['detail_id'] !== null ? (int)$finding['detail_id'] : '' ?>"
               placeholder="ID detail">
      </div>

      <div class="col-12">
        <label class="form-label">Judul Temuan <span class="text-danger">*</span></label>
        <input name="title" class="form-control" maxlength="180" required
               value="<?= e($finding['title']) ?>">
      </div>
      <div class="col-12">
        <label class="form-label">Lokasi Kerusakan <span class="text-danger">*</span></label>
        <input name="location_description" class="form-control" maxlength="255" required
               value="<?= e($finding['location_description']) ?>"
               placeholder="Contoh: Lantai 2, dinding sisi barat dekat jendela">
      </div>
      <div class="col-12">
        <label class="form-label">Deskripsi <span class="text-danger">*</span></label>
        <textarea name="description" rows="4" class="form-control" required><?= e($finding['description']) ?></textarea>
      </div>
      <div class="col-12">
        <label class="form-label">Rekomendasi</label>
        <textarea name="recommendation" rows="3" class="form-control"><?= e($finding['recommendation']) ?></textarea>
      </div>
      <div class="col-12">
        <label class="form-label">Foto (JPEG/PNG/WebP, maks 5 MB per file)</label>
        <input type="file" name="photos[]" class="form-control" accept="image/jpeg,image/png,image/webp" multiple>
        <small class="text-muted">Maksimal 5 foto per simpan.</small>
      </div>
    </div>

    <?php if ($photos): ?>
      <hr>
      <h6 class="mb-2">Foto saat ini</h6>
      <div class="photo-list">
        <?php foreach ($photos as $p): ?>
          <img src="<?= e(UPLOAD_URL . basename($p['file_path'])) ?>"
               alt="<?= e($p['caption'] ?? '') ?>"
               class="photo-thumb" data-full="<?= e(UPLOAD_URL . basename($p['file_path'])) ?>">
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="mt-4 d-flex gap-2">
      <button class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
      <a href="<?= BASE_URL ?>/user/findings.php" class="btn btn-light">Batal</a>
    </div>
  </form>
</div>

<!-- Modal pratinjau foto -->
<div class="modal fade" id="photoModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-body text-center p-0">
        <img src="" id="photoModalImg" class="modal-img">
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('click', (e) => {
  const img = e.target.closest('.photo-thumb');
  if (!img) return;
  document.getElementById('photoModalImg').src = img.dataset.full;
  new bootstrap.Modal(document.getElementById('photoModal')).show();
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>