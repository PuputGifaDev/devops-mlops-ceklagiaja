<?php
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/includes/auth.php';
require_login();

$file = $_GET['f'] ?? '';
if (!is_string($file) || !preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $file)) {
    http_response_code(400);
    exit('Nama file tidak valid.');
}

$path = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . $file;
$real = realpath($path);
$base = realpath(rtrim(UPLOAD_DIR, '/\\'));

if ($real === false || $base === false || !str_starts_with($real, $base)) {
    http_response_code(404);
    exit('File tidak ditemukan.');
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $real);
finfo_close($finfo);

$allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];
if (!isset($allowed[$mime])) {
    http_response_code(415);
    exit('Tipe file tidak diizinkan.');
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string)filesize($real));
header('Content-Disposition: inline; filename="' . basename($real) . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=86400');

readfile($real);
exit;