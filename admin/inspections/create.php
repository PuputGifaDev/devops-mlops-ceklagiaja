<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');

$errors = [];

// Ambil data dropdown
$buildings = $pdo->query("SELECT id, code, name FROM buildings WHERE status='active' ORDER BY name")->fetchAll();
$users     = $pdo->query("SELECT id, name, username FROM users WHERE role='user' AND is_active=1 ORDER BY name")->fetchAll();

// ----- Proses submit -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $buildingId  = (int)($_POST['building_id'] ?? 0);
    $assignedUid = (int)($_POST['assigned_user_id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $type        = trim($_POST['inspection_type'] ?? '');
    $schedDate   = $_POST['scheduled_date'] ?? '';
    $generalNote = trim($_POST['general_notes'] ?? '');

    // Validasi
    if ($buildingId <= 0) {
        $errors[] = 'Bangunan wajib dipilih.';
    } else {
        $cek = $pdo->prepare("SELECT id FROM buildings WHERE id = ? AND status = 'active'");
        $cek->execute([$buildingId]);
        if (!$cek->fetch()) $errors[] = 'Bangunan tidak valid atau tidak aktif.';
    }

    if ($assignedUid <= 0) {
        $errors[] = 'Petugas wajib dipilih.';
    } else {
        $cek = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'user' AND is_active = 1");
        $cek->execute([$assignedUid]);
        if (!$cek->fetch()) $errors[] = 'Petugas tidak valid atau tidak aktif.';
    }

    if ($title === '' || mb_strlen($title) > 180) {
        $errors[] = 'Judul wajib diisi (maks 180 karakter).';
    }

    $d = DateTime::createFromFormat('Y-m-d', $schedDate);
    if (!$d || $d->format('Y-m-d') !== $schedDate) {
        $errors[] = 'Tanggal inspeksi tidak valid.';
    }

    // Pastikan ada checklist aktif
    $activeChecklistCount = (int)$pdo->query(
        "SELECT COUNT(*) FROM checklist_items WHERE is_active = 1"
    )->fetchColumn();
    if ($activeChecklistCount === 0) {
        $errors[] = 'Belum ada checklist aktif. Hubungi admin untuk menambahkan checklist terlebih dahulu.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            // Generate nomor inspeksi: INS/YYYYMM/NNN (reset per bulan)
            $prefix = 'INS/' . date('Ym') . '/';
            $stmt = $pdo->prepare(
                "SELECT inspection_number FROM inspections
                 WHERE inspection_number LIKE :p
                 ORDER BY inspection_number DESC LIMIT 1 FOR UPDATE"
            );
            $stmt->execute(['p' => $prefix . '%']);
            $lastNum = $stmt->fetchColumn();
            $nextSeq = 1;
            if ($lastNum) {
                $nextSeq = (int)substr($lastNum, -3) + 1;
            }
            $inspectionNumber = $prefix . str_pad((string)$nextSeq, 3, '0', STR_PAD_LEFT);

            // Insert inspeksi
            $ins = $pdo->prepare(
                "INSERT INTO inspections
                 (inspection_number, building_id, assigned_user_id, created_by,
                  title, inspection_type, scheduled_date, status, general_notes)
                 VALUES (:num, :b, :u, :c, :t, :ty, :sd, 'scheduled', :gn)"
            );
            $ins->execute([
                'num' => $inspectionNumber,
                'b'   => $buildingId,
                'u'   => $assignedUid,
                'c'   => (int)$_SESSION['user_id'],
                't'   => $title,
                'ty'  => $type !== '' ? $type : null,
                'sd'  => $schedDate,
                'gn'  => $generalNote !== '' ? $generalNote : null,
            ]);
            $inspectionId = (int)$pdo->lastInsertId();

            // Snapshot: insert semua checklist aktif ke inspection_details
            // TODO(snapshot): kolom item_name_snapshot belum ada di skema.
            //   Jika ditambahkan, isi dengan item_name dari master.
            $insertDetail = $pdo->prepare(
                "INSERT INTO inspection_details (inspection_id, checklist_item_id, condition_status)
                 SELECT :iid, id, 'not_checked'
                 FROM checklist_items
                 WHERE is_active = 1
                 ORDER BY category_id, sort_order"
            );
            $insertDetail->execute(['iid' => $inspectionId]);

            // Log aktivitas
            $pdo->prepare(
                "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address)
                 VALUES (?, 'inspection_create', 'inspection', ?, ?, ?)"
            )->execute([
                (int)$_SESSION['user_id'],
                $inspectionId,
                "Membuat inspeksi $inspectionNumber",
                $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            $pdo->commit();
            flash('success', "Jadwal $inspectionNumber berhasil dibuat untuk {$assignedUid} tugas.");
            redirect('/admin/inspections/show.php?id=' . $inspectionId);
        } catch (Throwable $ex) {
            $pdo->rollBack();
            error_log($ex->getMessage());
            $errors[] = 'Gagal menyimpan jadwal. Silakan coba lagi.';
        }
    }
}

$pageTitle = 'Buat Jadwal Inspeksi';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/admin-sidebar.php';
?>

<main class="app-content">
  <div class="mb-3">
    <a href="<?= BASE_URL ?>/admin/inspections/index.php" class="text-decoration-none">
      <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke daftar
    </a>
    <h1 class="h3 mt-2 mb-0">Buat Jadwal Inspeksi</h1>
  </div>

  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="post" class="row g-3">
    <?= csrf_field() ?>
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body">
          <h2 class="h6 text-muted mb-3">Informasi Inspeksi</h2>

          <div class="mb-3">
            <label class="form-label">Bangunan <span class="text-danger">*</span></label>
            <select name="building_id" class="form-select" required>
              <option value="">— Pilih Bangunan —</option>
              <?php foreach ($buildings as $b): ?>
                <option value="<?= (int)$b['id'] ?>"
                  <?= (int)($_POST['building_id'] ?? 0) === (int)$b['id'] ? 'selected' : '' ?>>
                  [<?= e($b['code']) ?>] <?= e($b['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (!$buildings): ?>
              <small class="text-danger">Belum ada bangunan aktif. <a href="<?= BASE_URL ?>/admin/buildings/create.php">Tambah bangunan dulu</a>.</small>
            <?php endif; ?>
          </div>

          <div class="mb-3">
            <label class="form-label">Judul Inspeksi <span class="text-danger">*</span></label>
            <input name="title" value="<?= e($_POST['title'] ?? '') ?>"
                   class="form-control" maxlength="180" required
                   placeholder="Contoh: Inspeksi Rutin Q4 2026">
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label">Jenis Inspeksi</label>
              <input name="inspection_type" value="<?= e($_POST['inspection_type'] ?? '') ?>"
                     class="form-control" maxlength="100"
                     placeholder="Contoh: Rutin / Berkala / Darurat">
            </div>
            <div class="col-md-6">
              <label class="form-label">Tanggal Inspeksi <span class="text-danger">*</span></label>
              <input type="date" name="scheduled_date" required
                     value="<?= e($_POST['scheduled_date'] ?? date('Y-m-d')) ?>"
                     class="form-control">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Petugas <span class="text-danger">*</span></label>
            <select name="assigned_user_id" class="form-select" required>
              <option value="">— Pilih Petugas —</option>
              <?php foreach ($users as $u): ?>
                <option value="<?= (int)$u['id'] ?>"
                  <?= (int)($_POST['assigned_user_id'] ?? 0) === (int)$u['id'] ? 'selected' : '' ?>>
                  <?= e($u['name']) ?> (<?= e($u['username']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (!$users): ?>
              <small class="text-danger">Belum ada user aktif. <a href="<?= BASE_URL ?>/admin/users/create.php">Tambah user dulu</a>.</small>
            <?php endif; ?>
          </div>

          <div class="mb-3">
            <label class="form-label">Catatan Umum</label>
            <textarea name="general_notes" rows="3" class="form-control"
                      placeholder="Instruksi tambahan untuk petugas"><?= e($_POST['general_notes'] ?? '') ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card bg-light">
        <div class="card-body">
          <h2 class="h6 mb-3"><i class="fa-solid fa-circle-info text-primary me-1"></i> Yang akan terjadi</h2>
          <ol class="small mb-0 ps-3">
            <li>Nomor inspeksi dibuat otomatis (format <code>INS/YYYYMM/NNN</code>).</li>
            <li>Semua checklist aktif (<strong><?= (int)$activeChecklistCount ?></strong> item) disalin sebagai titik pemeriksaan.</li>
            <li>Jadwal berstatus <span class="badge bg-secondary">Dijadwalkan</span>.</li>
            <li>Petugas akan melihat tugas ini di dashboard-nya.</li>
          </ol>
        </div>
      </div>

      <div class="d-grid gap-2 mt-3">
        <button class="btn btn-primary" <?= (!$buildings || !$users || $activeChecklistCount === 0) ? 'disabled' : '' ?>>
          <i class="fa-solid fa-save me-1"></i> Simpan Jadwal
        </button>
        <a href="<?= BASE_URL ?>/admin/inspections/index.php" class="btn btn-outline-secondary">Batal</a>
      </div>
    </div>
  </form>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>