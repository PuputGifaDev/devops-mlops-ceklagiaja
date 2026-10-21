<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/csrf.php';

$findingId = (int)($_GET['finding_id'] ?? $_POST['finding_id'] ?? 0);
if ($findingId <= 0) redirect('/admin/follow-ups/index.php');

$stmt = $pdo->prepare(
    "SELECT f.*, i.inspection_number, i.title AS inspection_title, b.name AS building_name
     FROM inspection_findings f
     JOIN inspections i ON i.id = f.inspection_id
     JOIN buildings b   ON b.id = i.building_id
     WHERE f.id = :id"
);
$stmt->execute(['id' => $findingId]);
$finding = $stmt->fetch();
if (!$finding) { http_response_code(404); exit('Temuan tidak ditemukan.'); }

$stmt = $pdo->prepare(
    "SELECT fu.*, u.name AS creator_name
     FROM follow_ups fu JOIN users u ON u.id = fu.created_by
     WHERE fu.finding_id = :fid ORDER BY fu.created_at DESC"
);
$stmt->execute(['fid' => $findingId]);
$history = $stmt->fetchAll();

$pageTitle = 'Kelola Tindak Lanjut';
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
  <div class="d-flex justify-content-between mb-3">
    <h1 class="h4 mb-0">Kelola Tindak Lanjut</h1>
    <a href="<?= BASE_URL ?>/admin/follow-ups/index.php" class="btn btn-light">Kembali</a>
  </div>

  <?php if ($m = flash('success')): ?><div class="alert alert-success alert-dismissible-auto"><?= e($m) ?></div><?php endif; ?>
  <?php if ($m = flash('error')): ?><div class="alert alert-danger alert-dismissible-auto"><?= e($m) ?></div><?php endif; ?>

  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-body">
          <h6><?= e($finding['title']) ?></h6>
          <div class="mb-2">
            <span class="badge bg-secondary"><?= e($finding['inspection_number']) ?></span>
            <span class="badge bg-info text-dark"><?= strtoupper($finding['priority']) ?></span>
            <span class="stat-pill pill-<?= e($finding['follow_up_status']) ?>"><?= e(str_replace('_',' ',$finding['follow_up_status'])) ?></span>
          </div>
          <p class="mb-1"><strong>Bangunan:</strong> <?= e($finding['building_name']) ?></p>
          <p class="mb-1"><strong>Lokasi:</strong> <?= e($finding['location_description']) ?></p>
          <p class="mb-0"><strong>Deskripsi:</strong><br><?= nl2br(e($finding['description'])) ?></p>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <form method="post" action="<?= BASE_URL ?>/actions/follow-up-action.php" class="card shadow-sm p-4">
        <?= csrf_field() ?>
        <input type="hidden" name="finding_id" value="<?= (int)$finding['id'] ?>">

        <div class="mb-3">
          <label class="form-label">Status Baru <span class="text-danger">*</span></label>
          <select name="status" class="form-select" required>
            <?php foreach (['open','in_progress','completed','not_actionable'] as $st): ?>
              <option value="<?= $st ?>" <?= $finding['follow_up_status'] === $st ? 'selected' : '' ?>>
                <?= str_replace('_',' ',$st) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Catatan <span class="text-danger">*</span></label>
          <textarea name="notes" rows="4" class="form-control" required></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Target Selesai</label>
          <input type="date" name="target_date" class="form-control">
        </div>
        <button class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
      </form>
    </div>

    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-body">
          <h6 class="card-title">Riwayat Tindak Lanjut</h6>
          <?php if (!$history): ?>
            <p class="text-muted mb-0">Belum ada riwayat.</p>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Tanggal</th><th>Status</th><th>Catatan</th><th>Target</th><th>Oleh</th></tr></thead>
                <tbody>
                <?php foreach ($history as $h): ?>
                  <tr>
                    <td><?= e(format_tanggal($h['created_at'], 'd M Y H:i')) ?></td>
                    <td><span class="stat-pill pill-<?= e($h['status']) ?>"><?= e(str_replace('_',' ',$h['status'])) ?></span></td>
                    <td><?= nl2br(e($h['notes'])) ?></td>
                    <td><?= $h['target_date'] ? e(format_tanggal($h['target_date'])) : '-' ?></td>
                    <td><?= e($h['creator_name']) ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>