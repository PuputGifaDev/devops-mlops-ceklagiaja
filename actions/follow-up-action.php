<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role('admin');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/admin/follow-ups/index.php');
verify_csrf();

$adminId   = (int)$_SESSION['user_id'];
$findingId = (int)($_POST['finding_id'] ?? 0);
$status    = $_POST['status'] ?? '';
$notes     = trim($_POST['notes'] ?? '');
$target    = ($_POST['target_date'] ?? '') !== '' ? $_POST['target_date'] : null;

$allowed = ['open','in_progress','completed','not_actionable'];
if ($findingId <= 0 || !in_array($status, $allowed, true) || $notes === '') {
    flash('error', 'Data tindak lanjut tidak valid.');
    redirect('/admin/follow-ups/index.php');
}
if ($target !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $target)) {
    $target = null;
}

$stmt = $pdo->prepare("SELECT id FROM inspection_findings WHERE id = ?");
$stmt->execute([$findingId]);
if (!$stmt->fetch()) {
    flash('error', 'Temuan tidak ditemukan.');
    redirect('/admin/follow-ups/index.php');
}

$pdo->beginTransaction();
try {
    // Catat riwayat
    $stmt = $pdo->prepare(
        "INSERT INTO follow_ups (finding_id, created_by, status, notes, target_date, completed_at)
         VALUES (:fid, :uid, :st, :notes, :target, :done)"
    );
    $stmt->execute([
        'fid'    => $findingId,
        'uid'    => $adminId,
        'st'     => $status,
        'notes'  => $notes,
        'target' => $target,
        'done'   => $status === 'completed' ? date('Y-m-d H:i:s') : null,
    ]);

    // Sinkronkan status utama temuan
    $stmt = $pdo->prepare(
        "UPDATE inspection_findings
         SET follow_up_status = :st,
             resolution_notes = :notes,
             resolved_at = :done
         WHERE id = :id"
    );
    $stmt->execute([
        'st'    => $status,
        'notes' => $notes,
        'done'  => $status === 'completed' ? date('Y-m-d H:i:s') : null,
        'id'    => $findingId,
    ]);

    // Log
    $pdo->prepare(
        "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address)
         VALUES (?, 'follow_up_update', 'inspection_finding', ?, ?, ?)"
    )->execute([
        $adminId, $findingId,
        "Tindak lanjut diubah ke status $status",
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);

    $pdo->commit();
    flash('success', 'Tindak lanjut berhasil disimpan.');
} catch (Throwable $ex) {
    $pdo->rollBack();
    error_log($ex->getMessage());
    flash('error', 'Gagal menyimpan tindak lanjut.');
}
redirect('/admin/follow-ups/update.php?finding_id=' . $findingId);