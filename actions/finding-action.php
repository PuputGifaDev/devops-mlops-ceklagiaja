<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role('user');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/user/findings.php');
verify_csrf();

$userId      = (int)$_SESSION['user_id'];
$id          = (int)($_POST['id'] ?? 0);
$inspectionId = (int)($_POST['inspection_id'] ?? 0);
$detailId    = ($_POST['detail_id'] ?? '') !== '' ? (int)$_POST['detail_id'] : null;
$title       = trim($_POST['title'] ?? '');
$location    = trim($_POST['location_description'] ?? '');
$description = trim($_POST['description'] ?? '');
$priority    = $_POST['priority'] ?? 'medium';
$recommend   = trim($_POST['recommendation'] ?? '');

$errors = [];
if ($inspectionId <= 0) $errors[] = 'Inspeksi wajib dipilih.';
if ($title === '' || mb_strlen($title) > 180) $errors[] = 'Judul wajib diisi (maks 180 karakter).';
if ($location === '' || mb_strlen($location) > 255) $errors[] = 'Lokasi wajib diisi (maks 255 karakter).';
if ($description === '') $errors[] = 'Deskripsi wajib diisi.';
if (!in_array($priority, ['low','medium','high','critical'], true)) $errors[] = 'Prioritas tidak valid.';

if ($errors) {
    flash('error', implode(' ', $errors));
    redirect('/user/finding-form.php' . ($id ? "?id=$id" : "?inspection_id=$inspectionId"));
}

// Verifikasi kepemilikan inspeksi & status mengizinkan
$stmt = $pdo->prepare(
    "SELECT id, status FROM inspections
     WHERE id = :iid AND assigned_user_id = :uid"
);
$stmt->execute(['iid' => $inspectionId, 'uid' => $userId]);
$inspection = $stmt->fetch();
if (!$inspection) {
    flash('error', 'Inspeksi tidak ditemukan atau bukan milik Anda.');
    redirect('/user/findings.php');
}
if (in_array($inspection['status'], ['pending_review','approved','cancelled'], true)) {
    flash('error', 'Inspeksi berstatus ' . $inspection['status'] . '; temuan tidak dapat diubah.');
    redirect('/user/findings.php');
}

// Validasi file upload
$files = $_FILES['photos'] ?? null;
$uploadedTmp = [];
if ($files && !empty($files['name'][0])) {
    if (count($files['name']) > 5) {
        flash('error', 'Maksimal 5 foto per simpan.');
        redirect('/user/finding-form.php' . ($id ? "?id=$id" : "?inspection_id=$inspectionId"));
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    foreach ($files['name'] as $i => $name) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
        if ($files['size'][$i] > MAX_UPLOAD_SIZE) {
            flash('error', "File {$name} melebihi 5 MB.");
            redirect('/user/finding-form.php' . ($id ? "?id=$id" : "?inspection_id=$inspectionId"));
        }
        $mime = $finfo->file($files['tmp_name'][$i]);
        if (!in_array($mime, ALLOWED_MIME, true)) {
            flash('error', "File {$name} bukan gambar JPEG/PNG/WebP.");
            redirect('/user/finding-form.php' . ($id ? "?id=$id" : "?inspection_id=$inspectionId"));
        }
        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'bin',
        };
        $uploadedTmp[] = [
            'tmp'  => $files['tmp_name'][$i],
            'name' => $name,
            'ext'  => $ext,
        ];
    }
}

// Transaksi: temuan + foto
$pdo->beginTransaction();
try {
    if ($id > 0) {
        // Pastikan temuan milik user
        $stmt = $pdo->prepare(
            "SELECT f.id FROM inspection_findings f
             JOIN inspections i ON i.id = f.inspection_id
             WHERE f.id = :fid AND i.assigned_user_id = :uid"
        );
        $stmt->execute(['fid' => $id, 'uid' => $userId]);
        if (!$stmt->fetch()) throw new RuntimeException('Temuan tidak ditemukan.');

        $stmt = $pdo->prepare(
            "UPDATE inspection_findings
             SET inspection_id=:iid, detail_id=:did, title=:t, location_description=:loc,
                 description=:d, priority=:p, recommendation=:r
             WHERE id=:id"
        );
        $stmt->execute([
            'iid'=>$inspectionId,'did'=>$detailId,'t'=>$title,'loc'=>$location,
            'd'=>$description,'p'=>$priority,'r'=>$recommend,'id'=>$id,
        ]);
        $findingId = $id;
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO inspection_findings
             (inspection_id, detail_id, title, location_description, description, priority, recommendation)
             VALUES (:iid,:did,:t,:loc,:d,:p,:r)"
        );
        $stmt->execute([
            'iid'=>$inspectionId,'did'=>$detailId,'t'=>$title,'loc'=>$location,
            'd'=>$description,'p'=>$priority,'r'=>$recommend,
        ]);
        $findingId = (int)$pdo->lastInsertId();
    }

    // Simpan foto
    if ($uploadedTmp) {
        if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0775, true);
        $stmt = $pdo->prepare(
            "INSERT INTO finding_photos (finding_id, file_path, original_name, uploaded_by)
             VALUES (:fid, :path, :orig, :uid)"
        );
        foreach ($uploadedTmp as $f) {
            $filename = bin2hex(random_bytes(16)) . '.' . $f['ext'];
            $dest = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . $filename;
            if (!move_uploaded_file($f['tmp'], $dest)) {
                throw new RuntimeException('Gagal menyimpan file: ' . $f['name']);
            }
            $stmt->execute([
                'fid'  => $findingId,
                'path' => 'uploads/inspeksi/' . $filename,
                'orig' => $f['name'],
                'uid'  => $userId,
            ]);
        }
        // Cegah eksekusi PHP di folder upload
        $htaccess = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . '.htaccess';
        if (!file_exists($htaccess)) {
            @file_put_contents($htaccess, "php_flag engine off\n<FilesMatch \"\\.(php|phtml|phar)$\">\n  Require all denied\n</FilesMatch>\n");
        }
    }

    $pdo->commit();
    flash('success', 'Temuan berhasil disimpan.');
} catch (Throwable $ex) {
    $pdo->rollBack();
    error_log($ex->getMessage());
    flash('error', 'Gagal menyimpan temuan.');
}
redirect('/user/findings.php');