<?php
/**
 * config/config.php
 * Konfigurasi global aplikasi
 */

require_once __DIR__ . '/env.php';

// Zona Waktu
date_default_timezone_set('Asia/Jakarta');

// Informasi Aplikasi
define('APP_NAME', $_ENV['APP_NAME'] ?? 'Sistem Informasi Inspeksi Bangunan');
define('BASE_URL', $_ENV['BASE_URL'] ?? 'http://localhost/inspeksi-bangunan/');

// Konfigurasi Upload
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB

// Error Reporting (Ubah ke 0 saat Production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Memulai Session secara global jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>