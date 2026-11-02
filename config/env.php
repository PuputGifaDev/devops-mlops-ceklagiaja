<?php
/**
 * config/env.php
 * Fungsi sederhana untuk membaca file .env tanpa library eksternal
 */

function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Abaikan komentar
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Pisahkan KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            // Hapus tanda kutip jika ada
            $value = trim($value, '"\'');

            // Set ke environment jika belum ada
            if (!array_key_exists($name, $_ENV)) {
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
            }
        }
    }
}

// Muat file .env dari root direktori proyek (2 level di atas folder config)
loadEnv(__DIR__ . '/../.env');
?>