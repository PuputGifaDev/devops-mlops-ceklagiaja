<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    flash('error', 'ID inspeksi tidak valid.');
    redirect('/admin/inspections/index.php');
}

// ----- Ambil data inspeksi -----
$stmt = $pdo->prepare(
    "SELECT i.*, b.name AS building_name, b.code AS building_code, b.address, b.building_type,
            u.name AS assignee_name, u.username AS assignee_username
     FROM inspections i
     JOIN buildings b ON b.id = i.building_id
     JOIN users u ON u.id = i.assigned_user_id
     WHERE i.id = ?"
);
$stmt->execute([$id]);
$ins = $stmt->fetch();

if (!$ins) {
    flash('error', 'Inspeksi tidak ditemukan.');
    redirect('/admin/inspections/index.php');
}

// Hanya pending_review yang bisa direview
if ($ins['status'] !== 'pending_review') {
    flash('error', 'Inspeksi ini tidak dalam status menunggu review (status: ' . $ins['status'] . ').');
    redirect('/admin/inspections/show.php?id=' . $id);
}

// ----- Proses submit review -----
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $decision = $_POST['decision'] ?? '';
    $notes    = trim($_POST['notes'] ?? '');

    if (!in_array($decision, ['approved', 'revision'], true)) {
        $errors[] = 'Keputusan review tidak valid.';
    }
    if ($decision === 'revision' && $notes === '') {
        $errors[] = 'Alasan revisi wajib diisi agar petugas tahu apa yang harus diperbaiki.';
    }
    if (mb_strlen($notes) > 2000) {
        $errors[] = 'Catatan maksimal 2000 karakter.';
    }

    // Validasi kelengkapan checklist: semua item harus terisi (bukan not_checked)
    if ($decision === 'approved') {
        $unchecked = (int)$pdo->prepare(
            "SELECT COUNT(*) FROM inspection_details
             WHERE inspection_id = ? AND condition_status = 'not_checked'"
        )->execute([$id]) ?: 0;
        // Ambil ulang dengan cara benar (execute tidak mengembalikan count)
        $checkStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM inspection_details
             WHERE inspection_id = ? AND condition_status = 'not_checked'"
        );
        $checkStmt->execute([$id]);
        $unchecked = (int)$checkStmt->fetchColumn();

        if ($unchecked > 0) {
            $errors[] = "Tidak dapat menyetujui: masih ada $unchecked item checklist yang belum diisi (status 'Belum Diperiksa'). Minta petugas melengkapi atau kembalikan untuk revisi.";
        }
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            // Kunci baris untuk mencegah race (admin kembar submit)
            $lock = $pdo->prepare("SELECT status FROM inspections WHERE id = ? FOR UPDATE");
            $lock->execute([$id]);
            $currentStatus = $lock->fetchColumn();

            if ($currentStatus !== 'pending_review') {
                throw new RuntimeException('Status inspeksi sudah berubah. Muat ulang halaman.');
            }

            if ($decision === 'approved') {
                $pdo->prepare(
                    "UPDATE inspections
                     SET status = 'approved', approved_at = NOW(), review_notes = ?
                     WHERE id = ?"
                )->execute([$notes !== '' ? $notes : null, $id]);
            } else {
                $pdo->prepare(
                    "UPDATE inspections
                     SET status = 'revision', review_notes = ?
                     WHERE id = ?"
                )->execute([$notes, $id]);
            }

            // Simpan keputusan ke riwayat
            $pdo->prepare(
                "INSERT INTO inspection_reviews (inspection_id, reviewer_id, decision, notes)
                 VALUES (?, ?, ?, ?)"
            )->execute([$id, (int)$_SESSION['user_id'], $decision, $notes !== '' ? $notes : null]);

            // Log aktivitas
            $actionLabel = $decision === 'approved' ? 'approve' : 'request_revision';
            $pdo->prepare(
                "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address)
                 VALUES (?, ?, 'inspection', ?, ?, ?)"
            )->execute([
                (int)$_SESSION['user_id'],
                "inspection_$actionLabel",
                $id,
                ($decision === 'approved' ? 'Setujui' : 'Minta revisi') . " inspeksi {$ins['inspection_number']}",
                $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            $pdo->commit();
            flash('success', $decision === 'approved'
                ? 'Inspeksi berhasil disetujui dan menjadi laporan resmi.'
                : 'Inspeksi dikembalikan ke petugas untuk revisi.');
            redirect('/admin/inspections/show.php?id=' . $id);
        } catch (Throwable $ex) {
            $pdo->rollBack();
            error_log($ex->getMessage());
            $errors[] = $ex instanceof RuntimeException
                ? $ex->getMessage()
                : 'Gagal menyimpan keputusan review. Silakan coba lagi.';
        }
    }
}

// ----- Ambil data pendukung review -----
// Checklist terkelompok kategori
$detailStmt = $pdo->prepare(
    "SELECT d.*, ci.item_name, ci.requires_note, ci.requires_photo,
            cat.name AS category_name, cat.sort_order AS cat_order
     FROM inspection_details d
     JOIN checklist_items ci ON ci.id = d.checklist_item_id
     JOIN inspection_categories cat ON cat.id = ci.category_id
     WHERE d.inspection_id = ?
     ORDER BY cat.sort_order, ci.sort_order"
);
$detailStmt->execute([$id]);
$details = $detailStmt->fetchAll();

// Group by category
$byCategory = [];
foreach ($details as $d) {
    $byCategory[$d['category_name']][] = $d;
}

// Temuan + foto
$findStmt = $pdo->prepare(
    "SELECT f.*, u.name AS creator_name
     FROM inspection_findings f
     LEFT JOIN users u ON u.id = (SELECT u2.id FROM users u2 WHERE u2.id = (SELECT assigned_user_id FROM inspections WHERE id = f.inspection_id))
     WHERE f.inspection_id = ?
     ORDER BY FIELD(f.priority,'critical','high','medium','low'), f.id"
);
$findStmt->execute([$id]);
$findings = $findStmt->fetchAll();

// Foto per temuan
$photosByFinding = [];
if ($findings) {
    $findingIds = array_column($findings, 'id');
    $placeholders = implode(',', array_fill(0, count($findingIds), '?'));
    $photoStmt = $pdo->prepare(
        "SELECT * FROM finding_photos WHERE finding_id IN ($placeholders) ORDER BY id"
    );
    $photoStmt->execute($findingIds);
    foreach ($photoStmt->fetchAll() as $p) {
        $photosByFinding[$p['finding_id']][] = $p;
    }
}

// Statistik
$totalItems   = count($details);
$checkedItems = count(array_filter($details, fn($r) => $r['condition_status'] !== 'not_checked'));
$progress     = $totalItems > 0 ? round($checkedItems / $totalItems * 100) : 0;
$damagedCount = count(array_filter($details, fn($r) => $r['condition_status'] === 'damaged'));
$attentionCount = count(array_filter($details, fn($r) => $r['condition_status'] === 'attention'));

$condLabel = ['good'=>'Baik','attention'=>'Perlu Perhatian','damaged'=>'Rusak','not_checked'=>'Belum Diperiksa'];
$condBadge = ['good'=>'success','attention'=>'warning','damaged'=>'danger','not_checked'=>'secondary'];
$prioBadge = ['critical'=>'danger','high'=>'warning','medium'=>'info','low'=>'secondary'];
$prioLabel = ['critical'=>'Kritis','high'=>'Tinggi','medium'=>'Sedang','low'=>'Rendah'];

$pageTitle = 'Review Inspeksi — ' . $ins['inspection_number'];
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/admin-sidebar.php';
?>

<main class="app-content">
  <div class="mb-3">
    <a href="<?= BASE_URL ?>/admin/inspections/show.php?id=<?= $id ?>" class="text-decoration-none">
      <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke detail
    </a>
  </div>

  <div class="mb-4">
    <h1 class="h3 mb-1">
      Review Inspeksi <code><?= e($ins['inspection_number']) ?></code>
    </h1>
    <p class="text-muted mb-0">
      <?= e($ins['building_name']) ?> (<?= e($ins['building_code']) ?>) ·
      Petugas: <strong><?= e($ins['assignee_name']) ?></strong> ·
      Tanggal: <?= format_tanggal($ins['scheduled_date']) ?>
    </p>
  </div>

  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <!-- Ringkasan -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="text-muted small">Progress Checklist</div>
          <div class="h4 mb-0"><?= $progress ?>%</div>
          <div class="progress mt-2" style="height:6px">
            <div class="progress-bar bg-primary" style="width:<?= $progress ?>%"></div>
          </div>
          <small class="text-muted"><?= $checkedItems ?> / <?= $totalItems ?> item terisi</small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="text-muted small">Item Rusak</div>
          <div class="h4 mb-0 text-danger"><?= $damagedCount ?></div>
          <small class="text-muted">butuh perhatian</small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="text-muted small">Perlu Perhatian</div>
          <div class="h4 mb-0 text-warning"><?= $attentionCount ?></div>
          <small class="text-muted">pantau</small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="text-muted small">Temuan</div>
          <div class="h4 mb-0"><?= count($findings) ?></div>
          <small class="text-muted">tercatat</small>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <!-- Kolom kiri: checklist & temuan -->
    <div class="col-lg-8">
      <!-- Checklist -->
      <div class="card mb-3">
        <div class="card-header"><h2 class="h6 mb-0">Hasil Checklist</h2></div>
        <div class="card-body p-0">
          <?php if (!$byCategory): ?>
            <p class="text-muted p-3 mb-0">Tidak ada checklist.</p>
          <?php else: foreach ($byCategory as $catName => $items): ?>
            <h3 class="h6 px-3 pt-3 pb-2 mb-0 bg-light border-top"><?= e($catName) ?></h3>
            <table class="table table-sm mb-0">
              <tbody>
                <?php foreach ($items as $d): ?>
                  <tr>
                    <td style="width:55%">
                      <div><?= e($d['item_name']) ?></div>
                      <?php if ($d['notes']): ?>
                        <small class="text-muted fst-italic">“<?= e($d['notes']) ?>”</small>
                      <?php endif; ?>
                    </td>
                    <td>
                      <span class="badge bg-<?= $condBadge[$d['condition_status']] ?>">
                        <?= $condLabel[$d['condition_status']] ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endforeach; endif; ?>
        </div>
      </div>

      <!-- Temuan -->
      <div class="card mb-3">
        <div class="card-header"><h2 class="h6 mb-0">Temuan & Dokumentasi Foto (<?= count($findings) ?>)</h2></div>
        <div class="card-body">
          <?php if (!$findings): ?>
            <p class="text-muted mb-0">Petugas tidak mencatat temuan.</p>
          <?php else: foreach ($findings as $f): ?>
            <div class="border rounded p-3 mb-3">
              <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                <div>
                  <h4 class="h6 mb-1"><?= e($f['title']) ?></h4>
                  <small class="text-muted">
                    <i class="fa-solid fa-location-dot"></i> <?= e($f['location_description']) ?>
                  </small>
                </div>
                <div class="text-end">
                  <span class="badge bg-<?= $prioBadge[$f['priority']] ?>"><?= $prioLabel[$f['priority']] ?></span>
                  <div class="small text-muted mt-1"><?= e($f['follow_up_status']) ?></div>
                </div>
              </div>

              <p class="small mb-2"><?= nl2br(e($f['description'])) ?></p>

              <?php if ($f['recommendation']): ?>
                <div class="alert alert-light border py-2 px-3 small mb-2">
                  <strong>Rekomendasi:</strong> <?= nl2br(e($f['recommendation'])) ?>
                </div>
              <?php endif; ?>

              <?php if (!empty($photosByFinding[$f['id']])): ?>
                <div class="d-flex gap-2 flex-wrap">
                  <?php foreach ($photosByFinding[$f['id']] as $p): ?>
                    <a href="<?= e(UPLOAD_URL . basename($p['file_path'])) ?>" target="_blank"
                       class="photo-thumb" data-caption="<?= e($p['caption'] ?? '') ?>">
                      <img src="<?= e(UPLOAD_URL . basename($p['file_path'])) ?>"
                           alt="<?= e($p['caption'] ?? 'Foto temuan') ?>"
                           class="rounded border" style="width:96px;height:96px;object-fit:cover">
                    </a>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <small class="text-muted fst-italic">Tidak ada foto.</small>
              <?php endif; ?>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </div>

    <!-- Kolom kanan: form keputusan -->
    <div class="col-lg-4">
      <div class="card sticky-top" style="top:1rem">
        <div class="card-header bg-white">
          <h2 class="h6 mb-0"><i class="fa-solid fa-gavel text-primary me-1"></i> Keputusan Review</h2>
        </div>
        <div class="card-body">
          <form method="post" id="review-form">
            <?= csrf_field() ?>

            <div class="mb-3">
              <label class="form-label">Keputusan <span class="text-danger">*</span></label>

              <div class="form-check border rounded p-2 mb-2 ps-4">
                <input class="form-check-input" type="radio" name="decision" id="dec-approve"
                       value="approved" <?= ($_POST['decision'] ?? '') === 'approved' ? 'checked' : '' ?> required>
                <label class="form-check-label" for="dec-approve">
                  <span class="badge bg-success">Setujui</span>
                  <div class="small text-muted mt-1">Hasil menjadi laporan resmi.</div>
                </label>
              </div>

              <div class="form-check border rounded p-2 ps-4">
                <input class="form-check-input" type="radio" name="decision" id="dec-revision"
                       value="revision" <?= ($_POST['decision'] ?? '') === 'revision' ? 'checked' : '' ?>>
                <label class="form-check-label" for="dec-revision">
                  <span class="badge bg-danger">Minta Revisi</span>
                  <div class="small text-muted mt-1">Petugas dapat memperbaiki lalu kirim ulang.</div>
                </label>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label" for="notes">
                Catatan Review
                <span class="text-danger" id="notes-required" style="display:none">*</span>
              </label>
              <textarea name="notes" id="notes" class="form-control" rows="5"
                        maxlength="2000"
                        placeholder="Opsional untuk persetujuan. Wajib untuk revisi."><?= e($_POST['notes'] ?? '') ?></textarea>
              <small class="text-muted">
                <span id="notes-hint">Alasan yang jelas akan mempermudah petugas melakukan perbaikan.</span>
              </small>
            </div>

            <div class="alert alert-info small mb-3">
              <i class="fa-solid fa-circle-info me-1"></i>
              Setelah keputusan disimpan, status inspeksi berubah dan <strong>tidak bisa diubah lagi</strong>
              kecuali melalui mekanisme baru.
            </div>

            <div class="d-grid gap-2">
              <button class="btn btn-primary" id="btn-submit"
                      <?= (!$details) ? 'disabled' : '' ?>>
                <i class="fa-solid fa-paper-plane me-1"></i> Simpan Keputusan
              </button>
              <a href="<?= BASE_URL ?>/admin/inspections/show.php?id=<?= $id ?>"
                 class="btn btn-outline-secondary">Batal</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- Modal pratinjau foto -->
<div class="modal fade" id="modalPhoto" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body p-0 text-center bg-dark">
        <img id="modalPhotoImg" src="" alt="" class="img-fluid">
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Toggle wajib/tidaknya catatan saat pilih revisi
  const notes    = document.getElementById('notes');
  const required = document.getElementById('notes-required');
  const hint     = document.getElementById('notes-hint');
  const decApp   = document.getElementById('dec-approve');
  const decRev   = document.getElementById('dec-revision');

  function refreshNotes() {
    const isRevision = decRev.checked;
    notes.required  = isRevision;
    required.style.display = isRevision ? 'inline' : 'none';
    hint.textContent = isRevision
      ? 'Jelaskan bagian mana yang perlu diperbaiki.'
      : 'Opsional. Tambahkan catatan jika perlu.';
  }
  decApp?.addEventListener('change', refreshNotes);
  decRev?.addEventListener('change', refreshNotes);
  refreshNotes();

  // Konfirmasi sebelum submit
  document.getElementById('review-form')?.addEventListener('submit', (e) => {
    const decision = document.querySelector('input[name="decision"]:checked')?.value;
    if (!decision) { e.preventDefault(); return; }
    const label = decision === 'approved' ? 'MENYETUJUI hasil inspeksi' : 'MEMINTA REVISI';
    if (!confirm('Anda yakin ' + label + '?')) {
      e.preventDefault();
    }
  });

  // Pratinjau foto
  const modal = new bootstrap.Modal('#modalPhoto');
  document.querySelectorAll('.photo-thumb').forEach(el => {
    el.addEventListener('click', (ev) => {
      ev.preventDefault();
      document.getElementById('modalPhotoImg').src = el.href;
      modal.show();
    });
  });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>