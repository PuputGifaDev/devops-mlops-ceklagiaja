<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    flash('error', 'ID inspeksi tidak valid.');
    redirect('/admin/inspections/index.php');
}

// ----- Aksi: batalkan (POST) -----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    verify_csrf();
    $reason = trim($_POST['cancel_reason'] ?? '');

    $stmt = $pdo->prepare("SELECT * FROM inspections WHERE id = ?");
    $stmt->execute([$id]);
    $ins = $stmt->fetch();
    if (!$ins) {
        flash('error', 'Inspeksi tidak ditemukan.');
        redirect('/admin/inspections/index.php');
    }
    if (!in_array($ins['status'], ['scheduled', 'in_progress', 'revision'], true)) {
        flash('error', 'Inspeksi dengan status ini tidak dapat dibatalkan.');
        redirect('/admin/inspections/show.php?id=' . $id);
    }
    if ($reason === '') {
        flash('error', 'Alasan pembatalan wajib diisi.');
        redirect('/admin/inspections/show.php?id=' . $id);
    }

    try {
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE inspections SET status='cancelled', review_notes=CONCAT(IFNULL(review_notes,''), ?) WHERE id=?")
            ->execute(["[Dibatalkan: $reason] ", $id]);
        $pdo->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address)
                       VALUES (?, 'inspection_cancel', 'inspection', ?, ?, ?)")
            ->execute([(int)$_SESSION['user_id'], $id, "Batalkan inspeksi: $reason", $_SERVER['REMOTE_ADDR'] ?? null]);
        $pdo->commit();
        flash('success', 'Inspeksi berhasil dibatalkan.');
    } catch (Throwable $ex) {
        $pdo->rollBack();
        error_log($ex->getMessage());
        flash('error', 'Gagal membatalkan inspeksi.');
    }
    redirect('/admin/inspections/show.php?id=' . $id);
}

// ----- Ambil data inspeksi -----
$stmt = $pdo->prepare(
    "SELECT i.*, b.name AS building_name, b.code AS building_code, b.address,
            b.building_type, b.city, b.province,
            u.name AS assignee_name, u.username AS assignee_username, u.email AS assignee_email,
            c.name AS creator_name
     FROM inspections i
     JOIN buildings b ON b.id = i.building_id
     JOIN users u ON u.id = i.assigned_user_id
     JOIN users c ON c.id = i.created_by
     WHERE i.id = ?"
);
$stmt->execute([$id]);
$ins = $stmt->fetch();
if (!$ins) {
    flash('error', 'Inspeksi tidak ditemukan.');
    redirect('/admin/inspections/index.php');
}

// Detail checklist + item + kategori
$details = $pdo->prepare(
    "SELECT d.*, ci.item_name, ci.description AS item_desc,
            cat.name AS category_name, cat.id AS category_id
     FROM inspection_details d
     JOIN checklist_items ci ON ci.id = d.checklist_item_id
     JOIN inspection_categories cat ON cat.id = ci.category_id
     WHERE d.inspection_id = ?
     ORDER BY cat.sort_order, ci.sort_order"
);
$details->execute([$id]);
$detailRows = $details->fetchAll();

// Temuan
$findStmt = $pdo->prepare(
    "SELECT f.*, (SELECT COUNT(*) FROM finding_photos fp WHERE fp.finding_id = f.id) AS photo_count
     FROM inspection_findings f WHERE f.inspection_id = ?
     ORDER BY FIELD(f.priority,'critical','high','medium','low'), f.id DESC"
);
$findStmt->execute([$id]);
$findings = $findStmt->fetchAll();

// Riwayat review
$revStmt = $pdo->prepare(
    "SELECT r.*, u.name AS reviewer_name
     FROM inspection_reviews r JOIN users u ON u.id = r.reviewer_id
     WHERE r.inspection_id = ? ORDER BY r.created_at DESC"
);
$revStmt->execute([$id]);
$reviews = $revStmt->fetchAll();

// Statistik progress checklist
$totalItems = count($detailRows);
$checkedItems = count(array_filter($detailRows, fn($r) => $r['condition_status'] !== 'not_checked'));
$progress = $totalItems > 0 ? round($checkedItems / $totalItems * 100) : 0;

$statusBadge = [
    'scheduled'=>'secondary','in_progress'=>'info','pending_review'=>'warning',
    'revision'=>'danger','approved'=>'success','cancelled'=>'dark',
];
$statusLabel = [
    'scheduled'=>'Dijadwalkan','in_progress'=>'Dikerjakan','pending_review'=>'Menunggu Review',
    'revision'=>'Revisi','approved'=>'Disetujui','cancelled'=>'Dibatalkan',
];
$condLabel = [
    'good'=>'Baik','attention'=>'Perlu Perhatian','damaged'=>'Rusak','not_checked'=>'Belum Diperiksa',
];
$condBadge = [
    'good'=>'success','attention'=>'warning','damaged'=>'danger','not_checked'=>'secondary',
];
$prioBadge = ['critical'=>'danger','high'=>'warning','medium'=>'info','low'=>'secondary'];
$canCancel = in_array($ins['status'], ['scheduled','in_progress','revision'], true);
$canEdit   = $ins['status'] === 'scheduled';

$pageTitle = 'Detail Inspeksi — ' . $ins['inspection_number'];
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/admin-sidebar.php';
?>

<main class="app-content">
  <div class="mb-3">
    <a href="<?= BASE_URL ?>/admin/inspections/index.php" class="text-decoration-none">
      <i class="fa-solid fa-arrow-left me-1"></i> Kembali
    </a>
  </div>

  <?php if ($msg = flash('success')): ?>
    <div class="alert alert-success alert-dismissible-auto"><?= e($msg) ?></div>
  <?php endif; ?>
  <?php if ($msg = flash('error')): ?>
    <div class="alert alert-danger alert-dismissible-auto"><?= e($msg) ?></div>
  <?php endif; ?>

  <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
    <div>
      <h1 class="h3 mb-1">
        <code><?= e($ins['inspection_number']) ?></code>
        <span class="badge bg-<?= $statusBadge[$ins['status']] ?> align-middle ms-2">
          <?= $statusLabel[$ins['status']] ?>
        </span>
      </h1>
      <p class="text-muted mb-0"><?= e($ins['title']) ?></p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <?php if ($canEdit): ?>
        <a href="<?= BASE_URL ?>/admin/inspections/edit.php?id=<?= (int)$ins['id'] ?>"
           class="btn btn-outline-primary"><i class="fa-solid fa-pen me-1"></i> Edit Jadwal</a>
      <?php endif; ?>
      <?php if ($ins['status'] === 'pending_review'): ?>
        <a href="<?= BASE_URL ?>/admin/inspections/review.php?id=<?= (int)$ins['id'] ?>"
           class="btn btn-warning"><i class="fa-solid fa-clipboard-check me-1"></i> Review Hasil</a>
      <?php endif; ?>
      <?php if ($canCancel): ?>
        <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalCancel">
          <i class="fa-solid fa-ban me-1"></i> Batalkan
        </button>
      <?php endif; ?>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-8">
      <!-- Info Inspeksi -->
      <div class="card mb-3">
        <div class="card-header"><h2 class="h6 mb-0">Informasi Inspeksi</h2></div>
        <div class="card-body">
          <dl class="row mb-0 small">
            <dt class="col-sm-4 text-muted">Bangunan</dt>
            <dd class="col-sm-8">
              <div class="fw-semibold"><?= e($ins['building_name']) ?></div>
              <div class="text-muted"><?= e($ins['building_code']) ?> · <?= e($ins['building_type']) ?></div>
              <div class="text-muted"><?= e($ins['address']) ?><?= $ins['city'] ? ', ' . e($ins['city']) : '' ?></div>
            </dd>

            <dt class="col-sm-4 text-muted">Petugas</dt>
            <dd class="col-sm-8"><?= e($ins['assignee_name']) ?> <small class="text-muted">(<?= e($ins['assignee_username']) ?>)</small></dd>

            <dt class="col-sm-4 text-muted">Tanggal Dijadwalkan</dt>
            <dd class="col-sm-8"><?= format_tanggal($ins['scheduled_date'], 'd F Y') ?></dd>

            <dt class="col-sm-4 text-muted">Jenis Inspeksi</dt>
            <dd class="col-sm-8"><?= e($ins['inspection_type'] ?: '—') ?></dd>

            <dt class="col-sm-4 text-muted">Dibuat Oleh</dt>
            <dd class="col-sm-8"><?= e($ins['creator_name']) ?> · <?= format_tanggal($ins['created_at'], 'd M Y H:i') ?></dd>

            <?php if ($ins['started_at']): ?>
              <dt class="col-sm-4 text-muted">Mulai Dikerjakan</dt>
              <dd class="col-sm-8"><?= format_tanggal($ins['started_at'], 'd M Y H:i') ?></dd>
            <?php endif; ?>
            <?php if ($ins['submitted_at']): ?>
              <dt class="col-sm-4 text-muted">Dikirim</dt>
              <dd class="col-sm-8"><?= format_tanggal($ins['submitted_at'], 'd M Y H:i') ?></dd>
            <?php endif; ?>
            <?php if ($ins['approved_at']): ?>
              <dt class="col-sm-4 text-muted">Disetujui</dt>
              <dd class="col-sm-8"><?= format_tanggal($ins['approved_at'], 'd M Y H:i') ?></dd>
            <?php endif; ?>

            <?php if ($ins['general_notes']): ?>
              <dt class="col-sm-4 text-muted">Catatan Umum</dt>
              <dd class="col-sm-8"><?= nl2br(e($ins['general_notes'])) ?></dd>
            <?php endif; ?>
            <?php if ($ins['review_notes']): ?>
              <dt class="col-sm-4 text-muted">Catatan Review</dt>
              <dd class="col-sm-8"><?= nl2br(e($ins['review_notes'])) ?></dd>
            <?php endif; ?>
          </dl>
        </div>
      </div>

      <!-- Checklist -->
      <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h2 class="h6 mb-0">Checklist Pemeriksaan (<?= $totalItems ?> item)</h2>
          <span class="text-muted small"><?= $checkedItems ?>/<?= $totalItems ?> terisi (<?= $progress ?>%)</span>
        </div>
        <div class="progress" style="height:4px;border-radius:0">
          <div class="progress-bar bg-primary" style="width:<?= $progress ?>%"></div>
        </div>
        <div class="card-body p-0">
          <?php
            $currentCat = null;
            foreach ($detailRows as $d):
              if ($currentCat !== $d['category_name']):
                if ($currentCat !== null) echo '</tbody></table>';
                $currentCat = $d['category_name'];
          ?>
            <h3 class="h6 px-3 pt-3 pb-2 mb-0 bg-light border-top">
              <?= e($currentCat) ?>
            </h3>
            <table class="table table-sm mb-0">
              <tbody>
          <?php endif; ?>
                <tr>
                  <td style="width:60%">
                    <div><?= e($d['item_name']) ?></div>
                    <?php if ($d['notes']): ?>
                      <small class="text-muted fst-italic">“<?= e($d['notes']) ?>”</small>
                    <?php endif; ?>
                  </td>
                  <td class="text-end">
                    <span class="badge bg-<?= $condBadge[$d['condition_status']] ?>">
                      <?= $condLabel[$d['condition_status']] ?>
                    </span>
                  </td>
                </tr>
          <?php endforeach; ?>
          <?php if ($currentCat !== null) echo '</tbody></table>'; ?>
          <?php if (!$detailRows): ?>
            <p class="p-3 text-muted mb-0">Belum ada checklist.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Temuan -->
      <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h2 class="h6 mb-0">Temuan Kerusakan (<?= count($findings) ?>)</h2>
          <a href="<?= BASE_URL ?>/admin/findings/index.php?inspection_id=<?= (int)$ins['id'] ?>"
             class="btn btn-sm btn-outline-secondary">Kelola Temuan</a>
        </div>
        <div class="card-body">
          <?php if (!$findings): ?>
            <p class="text-muted mb-0">Belum ada temuan.</p>
          <?php else: ?>
            <div class="list-group list-group-flush">
              <?php foreach ($findings as $f): ?>
                <div class="list-group-item px-0">
                  <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                      <div class="fw-semibold"><?= e($f['title']) ?></div>
                      <small class="text-muted">
                        <i class="fa-solid fa-location-dot"></i> <?= e($f['location_description']) ?>
                        · <?= $f['photo_count'] ?> foto
                      </small>
                    </div>
                    <div class="text-end">
                      <span class="badge bg-<?= $prioBadge[$f['priority']] ?>"><?= strtoupper($f['priority']) ?></span>
                      <div><small class="text-muted"><?= e($f['follow_up_status']) ?></small></div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <!-- Ringkasan -->
      <div class="card mb-3">
        <div class="card-header"><h2 class="h6 mb-0">Ringkasan</h2></div>
        <div class="card-body">
          <div class="d-flex justify-content-between py-1">
            <span class="text-muted">Status</span>
            <span class="badge bg-<?= $statusBadge[$ins['status']] ?>"><?= $statusLabel[$ins['status']] ?></span>
          </div>
          <div class="d-flex justify-content-between py-1">
            <span class="text-muted">Progress checklist</span>
            <strong><?= $progress ?>%</strong>
          </div>
          <div class="d-flex justify-content-between py-1">
            <span class="text-muted">Temuan</span>
            <strong><?= count($findings) ?></strong>
          </div>
          <div class="d-flex justify-content-between py-1">
            <span class="text-muted">Riwayat review</span>
            <strong><?= count($reviews) ?></strong>
          </div>
        </div>
      </div>

      <!-- Riwayat Review -->
      <div class="card">
        <div class="card-header"><h2 class="h6 mb-0">Riwayat Review</h2></div>
        <div class="card-body">
          <?php if (!$reviews): ?>
            <p class="text-muted small mb-0">Belum ada review.</p>
          <?php else: ?>
            <ul class="list-unstyled mb-0">
              <?php foreach ($reviews as $r): ?>
                <li class="mb-3 pb-3 border-bottom">
                  <div class="d-flex justify-content-between">
                    <span class="badge bg-<?= $r['decision'] === 'approved' ? 'success' : 'danger' ?>">
                      <?= $r['decision'] === 'approved' ? 'Disetujui' : 'Revisi' ?>
                    </span>
                    <small class="text-muted"><?= format_tanggal($r['created_at'], 'd M Y H:i') ?></small>
                  </div>
                  <div class="small mt-1">oleh <strong><?= e($r['reviewer_name']) ?></strong></div>
                  <?php if ($r['notes']): ?>
                    <div class="small fst-italic text-muted mt-1">“<?= e($r['notes']) ?>”</div>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Batalkan -->
  <?php if ($canCancel): ?>
  <div class="modal fade" id="modalCancel" tabindex="-1">
    <div class="modal-dialog">
      <form method="post" class="modal-content">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="cancel">
        <div class="modal-header">
          <h5 class="modal-title">Batalkan Inspeksi?</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p class="mb-2">Inspeksi <code><?= e($ins['inspection_number']) ?></code> akan dibatalkan. Tindakan ini tidak dapat di-undo.</p>
          <label class="form-label">Alasan Pembatalan <span class="text-danger">*</span></label>
          <textarea name="cancel_reason" class="form-control" rows="3" required maxlength="500"></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tidak</button>
          <button class="btn btn-danger">Ya, Batalkan</button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>