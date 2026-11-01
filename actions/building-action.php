<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';

require_role('admin');
verify_csrf();

$op = $_GET['op'] ?? '';
$redirect = BASE_URL . '/admin/buildings/index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: $redirect"); exit; }

try {
    if ($op === 'create') {
        $data = [
            'code' => trim($_POST['code'] ?? ''),
            'name' => trim($_POST['name'] ?? ''),
            'building_type' => trim($_POST['building_type'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'city' => trim($_POST['city'] ?? '') ?: null,
            'province' => trim($_POST['province'] ?? '') ?: null,
            'status' => in_array($_POST['status'] ?? '', ['active','inactive']) ? $_POST['status'] : 'active',
            'created_by' => $_SESSION['user_id']
        ];
        
        if ($data['code'] === '' || $data['name'] === '') throw new Exception('Kode dan Nama wajib diisi.');
        
        $cols = implode(',', array_keys($data));
        $ph   = ':' . implode(',:', array_keys($data));
        $pdo->prepare("INSERT INTO buildings ($cols) VALUES ($ph)")->execute($data);
        flash('success', 'Bangunan berhasil ditambahkan.');
        
    } elseif ($op === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'code' => trim($_POST['code'] ?? ''),
            'name' => trim($_POST['name'] ?? ''),
            'building_type' => trim($_POST['building_type'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'city' => trim($_POST['city'] ?? '') ?: null,
            'province' => trim($_POST['province'] ?? '') ?: null,
            'status' => in_array($_POST['status'] ?? '', ['active','inactive']) ? $_POST['status'] : 'active',
        ];
        
        $set = implode(',', array_map(fn($k) => "$k = :$k", array_keys($data)));
        $data['id'] = $id;
        $pdo->prepare("UPDATE buildings SET $set WHERE id = :id")->execute($data);
        flash('success', 'Bangunan berhasil diperbarui.');
        
    } elseif ($op === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE buildings SET status = 'inactive' WHERE id = ?")->execute([$id]);
        flash('success', 'Bangunan dinonaktifkan.');
    }
} catch (PDOException $e) {
    flash('error', $e->getCode() === '23000' ? 'Kode bangunan sudah dipakai.' : 'Sistem Error.');
    header("Location: " . ($op === 'create' ? BASE_URL.'/admin/buildings/create.php' : BASE_URL.'/admin/buildings/edit.php?id='.$_POST['id']));
    exit;
} catch (Exception $e) {
    flash('error', $e->getMessage());
    header("Location: " . ($op === 'create' ? BASE_URL.'/admin/buildings/create.php' : BASE_URL.'/admin/buildings/edit.php?id='.$_POST['id']));
    exit;
}

header("Location: $redirect");
exit;