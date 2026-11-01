<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';

// Bisa satu laporan (?id=X) atau filter (from/to/building_id/status/priority)
$id = (int)($_GET['id'] ?? 0);

$where = '1=1'; $params = [];
if ($id > 0) {
    $where = 'i.id = :id'; $params['id'] = $id;
} else {
    if (!empty($_GET['from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'])) { $where .= " AND i.scheduled_date >= :from"; $params['from'] = $_GET['from']; }
    if (!empty($_GET['to'])   && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to']))   { $where .= " AND i.scheduled_date <= :to";   $params['to']   = $_GET['to']; }
    if (!empty($_GET['building_id'])) { $where .= " AND i.building_id = :bid"; $params['bid'] = (int)$_GET['building_id']; }
    if (!empty($_GET['status']))      { $where .= " AND i.status = :st";       $params['st']  = $_GET['status']; }
}

$stmt = $pdo->prepare(
    "SELECT i.*, b.code AS building_code, b.name AS building_name, b.building_type,
            b.address, b.city, b.province, u.name AS assignee_name
     FROM inspections i
     JOIN buildings b ON b.id = i.building_id
     JOIN users u     ON u.id = i.assigned_user_id
     WHERE $where
     ORDER BY i.scheduled_date DESC"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Detail + temuan hanya jika $id tunggal
$details = $findings = [];
if ($id > 0 && $rows) {
    $stmt = $pdo->prepare(
        "SELECT d.*, c.name AS category_name, ci.item_name
         FROM inspection_details d
         JOIN checklist_items ci ON ci.id = d.checklist_item_id
         JOIN inspection_categories c ON c.id = ci.category_id
         WHERE d.inspection_id = :id
         ORDER BY c.sort_order, ci.sort_order"
    );
    $stmt->execute(['id' => $id]);
    $details = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM inspection_findings WHERE inspection_id = :id
                           ORDER BY FIELD(priority,'critical','high','medium','low')");
    $stmt->execute(['id' => $id]);
    $findings = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Cetak Laporan — <?= e(APP_NAME) ?></title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/print.css">
  <style>
    body { font-family: 'Times New Roman', serif; font-size: 11pt; color:#000; margin: 0; padding: 15mm; }
    h1 { font-size: 14pt; text-align:center; margin: 0 0 4px; }
    h2 { font-size: 12pt; margin: 18px 0 6px; }
    .meta { text-align:center; font-size:10pt; color:#444; margin-bottom:14px; }
    table { width:100%; border-collapse:collapse; margin-bottom:12px; }
    th, td { border:1px solid #333; padding:5px 7px; vertical-align:top; text-align:left; }
    th { background:#f2f2f2; font-weight:bold; }
    .no-print { text-align:center; margin-bottom:14px; }
    .sign { margin-top:40px; display:flex; justify-content:space-between; }
    .sign div { width:45%; text-align:center; }
    .sign .line { margin-top:60px; border-top:1px solid #000; padding-top:4px; }
    @page { size: A4; margin: 15mm; }
    @media print {
      .no-print { display:none !important; }
      body { padding: 0; }
    }
  </style>
</head>
<body>
  <div class="no-print">
    <button onclick="window.print()" style="padding:8px 16px;background:#2563eb;color:#fff;border:none;border-radius:6px;cursor:pointer;">
      🖨️ Cetak / Simpan PDF
    </button>
  </div>

  <h1>LAPORAN INSPEKSI BANGUNAN</h1>
  <div class="meta"><?= e(APP_NAME) ?> · Dicetak: <?= date('d F Y H:i') ?></div>

  <?php if (!$rows): ?>
    <p style="text-align:center;color:#666;">Tidak ada data sesuai filter.</p>
  <?php elseif ($id === 0): ?>
    <h2>Rekap Inspeksi (<?= count($rows) ?> data)</h2>
    <table>
      <thead>
        <tr><th>No</th><th>No Inspeksi</th><th>Bangunan</th><th>Petugas</th><th>Tanggal</th><th>Status</th></tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $i => $r): ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><?= e($r['inspection_number']) ?></td>
          <td><?= e($r['building_code'] . ' — ' . $r['building_name']) ?></td>
          <td><?= e($r['assignee_name']) ?></td>
          <td><?= e(format_tanggal($r['scheduled_date'], 'd/m/Y')) ?></td>
          <td><?= e(str_replace('_',' ',$r['status'])) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: $ins = $rows[0]; ?>
    <h2>Identitas Bangunan &amp; Inspeksi</h2>
    <table>
      <tr><th style="width:28%">Nomor Inspeksi</th><td><?= e($ins['inspection_number']) ?></td></tr>
      <tr><th>Judul</th><td><?= e($ins['title']) ?></td></tr>
      <tr><th>Bangunan</th><td><?= e($ins['building_code']) ?> — <?= e($ins['building_name']) ?> (<?= e($ins['building_type']) ?>)</td></tr>
      <tr><th>Alamat</th><td><?= e($ins['address']) ?>, <?= e($ins['city']) ?>, <?= e($ins['province']) ?></td></tr>
      <tr><th>Petugas</th><td><?= e($ins['assignee_name']) ?></td></tr>
      <tr><th>Tanggal Inspeksi</th><td><?= e(format_tanggal($ins['scheduled_date'], 'd F Y')) ?></td></tr>
      <tr><th>Status</th><td><?= e(str_replace('_',' ',$ins['status'])) ?></td></tr>
    </table>

    <h2>Hasil Checklist</h2>
    <table>
      <thead><tr><th>Kategori</th><th>Item</th><th>Kondisi</th><th>Catatan</th></tr></thead>
      <tbody>
      <?php if (!$details): ?>
        <tr><td colspan="4" style="text-align:center;color:#666;">Belum ada detail.</td></tr>
      <?php else: foreach ($details as $d): ?>
        <tr>
          <td><?= e($d['category_name']) ?></td>
          <td><?= e($d['item_name']) ?></td>
          <td><?= e(str_replace('_',' ',$d['condition_status'])) ?></td>
          <td><?= e($d['notes'] ?? '-') ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>

    <h2>Temuan Kerusakan</h2>
    <table>
      <thead><tr><th>#</th><th>Judul &amp; Deskripsi</th><th>Lokasi</th><th>Prioritas</th><th>Status</th></tr></thead>
      <tbody>
      <?php if (!$findings): ?>
        <tr><td colspan="5" style="text-align:center;color:#666;">Tidak ada temuan.</td></tr>
      <?php else: foreach ($findings as $i => $f): ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><?= e($f['title']) ?><br><small><?= e($f['description']) ?></small></td>
          <td><?= e($f['location_description']) ?></td>
          <td><?= strtoupper($f['priority']) ?></td>
          <td><?= e(str_replace('_',' ',$f['follow_up_status'])) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>

    <div class="sign">
      <div>
        <p>Petugas Inspeksi</p>
        <div class="line"><?= e($ins['assignee_name']) ?></div>
      </div>
      <div>
        <p>Reviewer / Admin</p>
        <div class="line">( ____________________ )</div>
      </div>
    </div>
  <?php endif; ?>
</body>
</html>