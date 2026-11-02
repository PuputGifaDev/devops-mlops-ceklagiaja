<?php
/**
 * config/app.php
 * Bootstrap file
 */
require_once __DIR__ . '/config.php';     // Berisi BASE_URL, nama app, dll
require_once __DIR__ . '/database.php';   // Berisi koneksi $pdo

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
?>