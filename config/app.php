<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

/* ===== 1. IDENTITAS ===== */
define('APP_NAME',    (string) env('APP_NAME', 'Sistem Informasi Inspeksi Bangunan'));
define('APP_SHORT',   'SI-Inspeksi');
define('APP_VERSION', '1.1');
define('APP_ENV',     (string) env('APP_ENV', 'production'));
define('APP_DEBUG',   env_bool('APP_DEBUG', false));

/* ===== 2. ERROR REPORTING ===== */
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    $__logDir = dirname(__DIR__) . '/logs';
    if (is_dir($__logDir) && is_writable($__logDir)) {
        ini_set('error_log', $__logDir . '/php-error.log');
    }
    unset($__logDir);
}

/* ===== 3. BASE URL ===== */
$__baseUrl = env('APP_BASE_URL');
if (!$__baseUrl) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    if (str_ends_with($dir, '/actions')) {
        $dir = substr($dir, 0, -8);
    }
    $__baseUrl = $scheme . '://' . $host . $dir;
}
define('BASE_URL', rtrim((string)$__baseUrl, '/'));
unset($__baseUrl, $scheme, $host, $dir);

/* ===== 4. UPLOAD ===== */
$__uploadEnv = env('UPLOAD_DIR');
$__uploadDef = dirname(__DIR__, 2) . '/storage/uploads/inspeksi/';
define('UPLOAD_DIR', is_string($__uploadEnv) && $__uploadEnv !== ''
    ? rtrim($__uploadEnv, '/\\')
    : $__uploadDef
);
unset($__uploadEnv, $__uploadDef);

define('UPLOAD_URL', BASE_URL . '/serve-upload.php?f=');

define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);
define('ALLOWED_MIME', [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
]);

/* ===== 5. TIMEZONE ===== */
date_default_timezone_set('Asia/Jakarta');

/* ===== 6. SESSION HARDENING ===== */
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if (env_bool('SESSION_SECURE', false)) {
        ini_set('session.cookie_secure', '1');
    }

    session_set_cookie_params([
        'lifetime' => env_int('SESSION_LIFETIME', 0),
        'path'     => '/',
        'domain'   => '',
        'secure'   => env_bool('SESSION_SECURE', false),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}