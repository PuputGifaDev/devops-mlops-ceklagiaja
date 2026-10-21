<?php
declare(strict_types=1);
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

$isCli   = PHP_SAPI === 'cli';
$isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);

if (!$isCli && !$isLocal) {
    http_response_code(403);
    exit('Akses ditolak. Jalankan dari lokal/CLI saja.');
}

$count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
if ($count > 0) {
    exit('Akun sudah ada (jumlah: ' . $count . '). '
        . 'Hapus file create_admin.php dari server sekarang.');
}

$name     = 'Administrator';
$username = 'admin';
$email    = 'admin@example.com';
$password = 'GantiPassword-Kuat-2026';

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
    'INSERT INTO users (name, username, email, password_hash, role)
     VALUES (:n, :u, :e, :p, "admin")'
);
$stmt->execute([
    'n' => $name,
    'u' => $username,
    'e' => $email,
    'p' => $hash,
]);

echo "✅ Akun Admin berhasil dibuat.\n\n";
echo "Username : {$username}\n";
echo "Password : {$password}\n\n";
echo "⚠️  HAPUS file create_admin.php SEKARANG dan ganti password.\n";