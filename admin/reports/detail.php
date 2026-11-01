<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../../config/database.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect('/admin/reports/index.php');

$stmt = $pdo->prepare(
    "SELECT i.*, b.code AS building_code, b.name AS building_name, b.building_type,
            b.address, b.province, b.city, b.district, u.name AS assignee_name,
            r.name AS reviewer_name, rv.decision AS review_decision,
            rv.notes AS review_decision_notes, rv.created_at AS review_date
     FROM inspections i
     JOIN buildings b ON b.id = i.building_id
     JOIN users u     ON u.id = i.assigned_user_id
     LEFT JOIN inspection_reviews rv ON rv.inspection_id = i.id
        AND rv.id = (SELECT MAX(id) FROM inspection_reviews WHERE inspection_id = i.id)
     LEFT JOIN users r ON r.id = rv.reviewer_id
     WHERE i.id = :id"
);
$stmt->execute(['id' => $id]);
$ins = $stmt->fetch();
if (!$ins) { http_response_code(404); exit('Laporan tidak ditemukan.'); }

$stmt = $pdo->prepare(
    "SELECT d.*, c.name AS category_name, ci.item_name, ci.description
     FROM inspection_details d
     JOIN checklist_items ci ON ci.id = d.checklist_item_id
     JOIN inspection_categories c ON c.id = ci.category_id
     WHERE d.inspection_id = :id
     ORDER BY c.sort_order, ci.sort_order"
);
$stmt->execute(['id' => $id]);
$details = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT f.*, (SELECT COUNT(*) FROM finding_photos fp WHERE fp.finding_id=f.id) AS photo_count
     FROM inspection_findings f WHERE f.inspection_id = :id
     ORDER BY FIELD(f.priority,'critical','high','medium','low')"
);
$stmt->execute(['id' => $id]);
$findings = $stmt->fetchAll();

$pageTitle = 'Pratinjau Laporan';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/admin-sidebar.php';
?>
<div class="container-fluid py-4">
  <div class="d-flex justify-content-between mb-3 no-print">
    <h1 class="h4 mb-0">Pratinjau Laporan</h1>
    <div class="d-flex gap-2">
      <a href="<?= BASE_URL ?>/admin/reports/print.php?id=<?= (int)$id ?>"
         target="_blank" class="btn btn-primary"><i class="fas fa-print"></i> Cetak / PDF</a>
      <a href="<?= BASE_URL ?>/admin/reports/index.php" class="btn btn-light">Kembali</a>
    </div>
  </div>

  <div class="card shadow-sm p-4">
    <div class="text-center mb-3">
      <h5 class="mb-0">LAPORAN INSPEKSI BANGUNAN</h5>
      <div class="text-muted"><?= e(APP_NAME) ?></div>
    </div>

    <table class="table table-sm table-bordered">
      <tbody>
        <tr><th style="width:30%">Nomor Inspeksi</th><td><?= e($ins['inspection_number']) ?></td></tr>
        <tr><th>Judul</th><td><?= e($ins['title']) ?></td></tr>
        <tr><th>Bangunan</th><td><?= e($ins['building_code']) ?> — <?= e($ins['building_name']) ?> (<?= e($ins['building_type']) ?>)</td></tr>
        <tr><th>Alamat</th><td><?= e($ins['address']) ?><br>
          <?= e(implode(', ', array_filter([$ins['village'],$ins['district'],$ins['city'],$ins['province']]))) ?></td></tr>
        <tr><th>Petugas</th><td><?= e($ins['assignee_name']) ?></td></tr>
        <tr><th>Tanggal Jadwal</th><td><?= e(format_tanggal($ins['scheduled_date'], 'd F Y')) ?></td></tr>
        <tr><th>Tanggal Kirim</th><td><?= e($ins['submitted_at'] ? format_tanggal($ins['submitted_at'], 'd F Y H:i') : '-') ?></td></tr>
        <tr><th>Status</th><td><?= e(str_replace('_',' ',$ins['status'])) ?></td></tr>
        <?php if ($ins['reviewer_name']): ?>
          <tr><th>Reviewer</th><td><?= e($ins['reviewer_name']) ?> — <?= e($ins['review_decision']) ?></td></tr>
          <tr><th>Tanggal Review</th><td><?= e(format_tanggal($ins['review_date'], 'd F Y H:i')) ?></td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <h6 class="mt-4">Hasil Checklist</h6>
    <table class="table table-sm table-bordered">
      <thead class="table-light">
        <tr><th>Kategori</th><th>Item</th><th>Kondisi</th><th>Catatan</th></tr>
      </thead>
      <tbody>
      <?php if (!$details): ?>
        <tr><td colspan="4" class="text-center text-muted">Belum ada detail.</td></tr>
      <?php else: foreach ($details as $d): ?>
        <tr>
          <td><?= e($d['category_name']) ?></td>
          <td><?= e($d['item_name']) ?></td>
          <td><?= e(str_replace('_',' ',$d['condition_status'])) ?></td>
          <td><?= nl2br(e($d['notes'] ?? '-')) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>

    <h6 class="mt-4">Temuan Kerusakan</h6>
    <table class="table table-sm table-bordered">
      <thead class="table-light">
        <tr><th>#</th><th>Judul</th><th>Lokasi</th><th>Prioritas</th><th>Status</th><th>Foto</th></tr>
      </thead>
      <tbody>
      <?php if (!$findings): ?>
        <tr><td colspan="6" class="text-center text-muted">Tidak ada temuan.</td></tr>
      <?php else: foreach ($findings as $i => $f): ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><?= e($f['title']) ?><br><small><?= nl2br(e($f['description'])) ?></small></td>
          <td><?= e($f['location_description']) ?></td>
          <td><?= strtoupper($f['priority']) ?></td>
          <td><?= e(str_replace('_',' ',$f['follow_up_status'])) ?></td>
          <td><?= (int)$f['photo_count'] ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>

    <?php if ($ins['review_decision_notes']): ?>
      <h6 class="mt-4">Catatan Review</h6>
      <p><?= nl2br(e($ins['review_decision_notes'])) ?></p>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>