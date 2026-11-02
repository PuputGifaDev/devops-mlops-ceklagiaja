<?php
/**
 * config/database.php
 * Koneksi database menggunakan PDO
 */

require_once __DIR__ . '/config.php';

$host     = $_ENV['DB_HOST'] ?? 'localhost';
$dbname   = $_ENV['DB_NAME'] ?? 'inspeksi_bangunan';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';

$dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // Jangan tampilkan pesan error asli di production
    if ($_ENV['APP_ENV'] === 'local') {
        die('Koneksi Database Gagal: ' . $e->getMessage());
    } else {
        die('Terjadi kesalahan sistem. Silakan hubungi administrator.');
    }
}
?>