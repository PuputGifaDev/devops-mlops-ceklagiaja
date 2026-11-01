<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../../config/database.php';

$fromDate    = $_GET['from'] ?? '';
$toDate      = $_GET['to'] ?? '';
$buildingId  = (int)($_GET['building_id'] ?? 0);
$status      = $_GET['status'] ?? '';
$priority    = $_GET['priority'] ?? '';
$allowedStatus   = ['scheduled','in_progress','pending_review','revision','approved','cancelled'];
$allowedPriority = ['low','medium','high','critical'];
if ($status !== '' && !in_array($status, $allowedStatus, true)) $status = '';
if ($priority !== '' && !in_array($priority, $allowedPriority, true)) $priority = '';
if ($fromDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) $fromDate = '';
if ($toDate   !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate))   $toDate = '';

$sql = "SELECT i.id, i.inspection_number, i.title, i.scheduled_date, i.status,
               i.approved_at, b.name AS building_name, b.code AS building_code,
               u.name AS assignee_name,
               (SELECT COUNT(*) FROM inspection_findings f WHERE f.inspection_id = i.id) AS findings_count,
               (SELECT COUNT(*) FROM inspection_findings f
                 WHERE f.inspection_id = i.id AND f.priority IN ('high','critical')
                   AND f.follow_up_status IN ('open','in_progress')) AS priority_findings
        FROM inspections i
        JOIN buildings b ON b.id = i.building_id
        JOIN users u     ON u.id = i.assigned_user_id
        WHERE 1=1";
$params = [];
if ($fromDate)   { $sql .= " AND i.scheduled_date >= :from"; $params['from'] = $fromDate; }
if ($toDate)     { $sql .= " AND i.scheduled_date <= :to";   $params['to']   = $toDate; }
if ($buildingId) { $sql .= " AND i.building_id = :bid";      $params['bid']  = $buildingId; }
if ($status)     { $sql .= " AND i.status = :st";            $params['st']   = $status; }
if ($priority) {
    $sql .= " AND EXISTS (SELECT 1 FROM inspection_findings f
                          WHERE f.inspection_id = i.id AND f.priority = :pr)";
    $params['pr'] = $priority;
}
$sql .= " ORDER BY i.scheduled_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$buildings = $pdo->query("SELECT id, code, name FROM buildings WHERE status='active' ORDER BY name")->fetchAll();

// Ekspor CSV jika diminta
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="laporan-inspeksi-' . date('Ymd-His') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel baca UTF-8
    fputcsv($out, ['No Inspeksi','Bangunan','Kode','Petugas','Judul','Tanggal','Status','Total Temuan','Temuan Prioritas Tinggi','Disetujui']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['inspection_number'], $r['building_name'], $r['building_code'],
            $r['assignee_name'], $r['title'], $r['scheduled_date'], $r['status'],
            $r['findings_count'], $r['priority_findings'],
            $r['approved_at'] ? format_tanggal($r['approved_at'], 'Y-m-d H:i') : '',
        ]);
    }
    fclose($out);
    exit;
}

$pageTitle = 'Laporan Inspeksi';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/admin-sidebar.php';
?>
<div class="container-fluid py-4">
  <h1 class="h4 mb-3">Laporan Inspeksi</h1>

  <form method="get" class="card shadow-sm p-3 mb-3">
    <div class="row g-2">
      <div class="col-md-2"><label class="form-label small">Dari</label>
        <input type="date" name="from" value="<?= e($fromDate) ?>" class="form-control"></div>
      <div class="col-md-2"><label class="form-label small">Sampai</label>
        <input type="date" name="to" value="<?= e($toDate) ?>" class="form-control"></div>
      <div class="col-md-3"><label class="form-label small">Bangunan</label>
        <select name="building_id" class="form-select">
          <option value="">— Semua —</option>
          <?php foreach ($buildings as $b): ?>
            <option value="<?= (int)$b['id'] ?>" <?= $buildingId === (int)$b['id'] ? 'selected' : '' ?>>
              <?= e($b['code'] . ' — ' . $b['name']) ?>
            </option>
          <?php endforeach; ?>
        </select></div>
      <div class="col-md-2"><label class="form-label small">Status</label>
        <select name="status" class="form-select">
          <option value="">— Semua —</option>
          <?php foreach ($allowedStatus as $st): ?>
            <option value="<?= $st ?>" <?= $status === $st ? 'selected' : '' ?>><?= str_replace('_',' ',$st) ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="col-md-2"><label class="form-label small">Prioritas Ada</label>
        <select name="priority" class="form-select">
          <option value="">— Semua —</option>
          <?php foreach ($allowedPriority as $p): ?>
            <option value="<?= $p ?>" <?= $priority === $p ? 'selected' : '' ?>><?= strtoupper($p) ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="col-md-1 d-flex align-items-end">
        <button class="btn btn-primary w-100"><i class="fas fa-search"></i></button>
      </div>
    </div>
    <div class="mt-2 d-flex gap-2">
      <a class="btn btn-sm btn-outline-success"
         href="?<?= e(http_build_query(array_merge($_GET, ['export'=>'csv']))) ?>">
        <i class="fas fa-file-csv"></i> Ekspor CSV
      </a>
      <a class="btn btn-sm btn-outline-secondary"
         href="<?= BASE_URL ?>/admin/reports/print.php?<?= e(http_build_query($_GET)) ?>"
         target="_blank">
        <i class="fas fa-print"></i> Cetak Laporan
      </a>
    </div>
  </form>

  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
          <tr><th>No Inspeksi</th><th>Bangunan</th><th>Petugas</th><th>Judul</th>
              <th>Tanggal</th><th>Status</th><th>#Temuan</th><th>Prioritas</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="9" class="text-center text-muted py-4">Tidak ada data sesuai filter.</td></tr>
        <?php else: foreach ($rows as $r): ?>
          <tr>
            <td><?= e($r['inspection_number']) ?></td>
            <td><?= e($r['building_name']) ?></td>
            <td><?= e($r['assignee_name']) ?></td>
            <td><?= e($r['title']) ?></td>
            <td><?= e(format_tanggal($r['scheduled_date'])) ?></td>
            <td><?= e(str_replace('_',' ',$r['status'])) ?></td>
            <td><?= (int)$r['findings_count'] ?></td>
            <td><?= (int)$r['priority_findings'] ?></td>
            <td class="text-end">
              <a href="<?= BASE_URL ?>/admin/reports/detail.php?id=<?= (int)$r['id'] ?>"
                 class="btn btn-sm btn-outline-primary">Detail</a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>