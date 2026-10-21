<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../../config/database.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect('/admin/findings/index.php');

$stmt = $pdo->prepare(
    "SELECT f.*, i.inspection_number, i.title AS inspection_title,
            i.status AS inspection_status, b.name AS building_name,
            u.name AS assignee_name
     FROM inspection_findings f
     JOIN inspections i ON i.id = f.inspection_id
     JOIN buildings b   ON b.id = i.building_id
     JOIN users u       ON u.id = i.assigned_user_id
     WHERE f.id = :id"
);
$stmt->execute(['id' => $id]);
$f = $stmt->fetch();
if (!$f) { http_response_code(404); exit('Temuan tidak ditemukan.'); }

$stmt = $pdo->prepare("SELECT * FROM finding_photos WHERE finding_id = ? ORDER BY id");
$stmt->execute([$id]);
$photos = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT fu.*, u.name AS creator_name
     FROM follow_ups fu JOIN users u ON u.id = fu.created_by
     WHERE fu.finding_id = :fid ORDER BY fu.created_at DESC"
);
$stmt->execute(['fid' => $id]);
$followUps = $stmt->fetchAll();

$pageTitle = 'Detail Temuan';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/admin-sidebar.php';
?>
<style>
.photo-thumb { width:120px; height:120px; object-fit:cover; border-radius:8px; cursor:zoom-in; border:1px solid #e5e7eb; }
.stat-pill { display:inline-block; padding:.15rem .55rem; border-radius:999px; font-size:.75rem; font-weight:600; }
.pill-open { background:#fee2e2; color:#991b1b; }
.pill-in_progress { background:#fef3c7; color:#92400e; }
.pill-completed { background:#dcfce7; color:#166534; }
.pill-not_actionable { background:#e5e7eb; color:#374151; }
</style>

<div class="container-fluid py-4">
  <div class="d-flex justify-content-between mb-3">
    <h1 class="h4 mb-0">Detail Temuan</h1>
    <a href="<?= BASE_URL ?>/admin/findings/index.php" class="btn btn-light">Kembali</a>
  </div>

  <?php if ($m = flash('success')): ?><div class="alert alert-success alert-dismissible-auto"><?= e($m) ?></div><?php endif; ?>
  <?php if ($m = flash('error')): ?><div class="alert alert-danger alert-dismissible-auto"><?= e($m) ?></div><?php endif; ?>

  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card shadow-sm mb-3">
        <div class="card-body">
          <h5 class="card-title"><?= e($f['title']) ?></h5>
          <div class="mb-2">
            <span class="badge bg-secondary"><?= e($f['inspection_number']) ?></span>
            <span class="badge bg-info text-dark">Prioritas: <?= strtoupper($f['priority']) ?></span>
            <span class="stat-pill pill-<?= e($f['follow_up_status']) ?>"><?= e(str_replace('_',' ',$f['follow_up_status'])) ?></span>
          </div>
          <dl class="row mb-0">
            <dt class="col-sm-3">Bangunan</dt><dd class="col-sm-9"><?= e($f['building_name']) ?></dd>
            <dt class="col-sm-3">Inspeksi</dt><dd class="col-sm-9"><?= e($f['inspection_title']) ?> (<?= e($f['inspection_status']) ?>)</dd>
            <dt class="col-sm-3">Petugas</dt><dd class="col-sm-9"><?= e($f['assignee_name']) ?></dd>
            <dt class="col-sm-3">Lokasi</dt><dd class="col-sm-9"><?= e($f['location_description']) ?></dd>
            <dt class="col-sm-3">Deskripsi</dt><dd class="col-sm-9"><?= nl2br(e($f['description'])) ?></dd>
            <dt class="col-sm-3">Rekomendasi</dt><dd class="col-sm-9"><?= nl2br(e($f['recommendation'] ?? '-')) ?></dd>
            <dt class="col-sm-3">Dibuat</dt><dd class="col-sm-9"><?= e(format_tanggal($f['created_at'], 'd M Y H:i')) ?></dd>
          </dl>
        </div>
      </div>

      <?php if ($photos): ?>
        <div class="card shadow-sm mb-3">
          <div class="card-body">
            <h6 class="card-title">Foto Dokumentasi</h6>
            <div class="d-flex flex-wrap gap-2">
              <?php foreach ($photos as $p): ?>
                <img class="photo-thumb"
                     src="<?= e(UPLOAD_URL . basename($p['file_path'])) ?>"
                     data-full="<?= e(UPLOAD_URL . basename($p['file_path'])) ?>"
                     alt="<?= e($p['original_name']) ?>">
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <div class="card shadow-sm">
        <div class="card-body">
          <h6 class="card-title">Riwayat Tindak Lanjut</h6>
          <?php if (!$followUps): ?>
            <p class="text-muted mb-0">Belum ada tindak lanjut.</p>
          <?php else: ?>
            <div class="timeline">
              <?php foreach ($followUps as $fu): ?>
                <div class="border-start ps-3 mb-3">
                  <div class="d-flex justify-content-between">
                    <strong><?= e(str_replace('_',' ',$fu['status'])) ?></strong>
                    <small class="text-muted"><?= e(format_tanggal($fu['created_at'], 'd M Y H:i')) ?></small>
                  </div>
                  <div><?= nl2br(e($fu['notes'])) ?></div>
                  <small class="text-muted">oleh <?= e($fu['creator_name']) ?>
                    <?php if ($fu['target_date']): ?> · target <?= e(format_tanggal($fu['target_date'])) ?><?php endif; ?>
                  </small>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <a href="<?= BASE_URL ?>/admin/follow-ups/update.php?finding_id=<?= (int)$f['id'] ?>"
             class="btn btn-primary btn-sm mt-2"><i class="fas fa-plus"></i> Tambah Tindak Lanjut</a>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="photoModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content"><div class="modal-body text-center p-0">
      <img src="" id="photoModalImg" style="max-width:100%;max-height:80vh">
    </div></div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('click', e => {
  const el = e.target.closest('.photo-thumb'); if (!el) return;
  document.getElementById('photoModalImg').src = el.dataset.full;
  new bootstrap.Modal(document.getElementById('photoModal')).show();
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>