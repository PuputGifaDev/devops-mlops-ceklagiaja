<?php
declare(strict_types=1);

/**
 * Konfigurasi Aplikasi (Statis)
 */
return [
    'app' => [
        'name'    => 'Sistem Informasi Inspeksi Bangunan',
        'short'   => 'SIIB',
        'version' => '1.1',
        'env'     => env('APP_ENV', 'development'), // development | production
    ],
    'upload' => [
        'max_size'    => 5 * 1024 * 1024, // 5 MB
        'allowed_mime'=> ['image/jpeg', 'image/png', 'image/webp'],
        'dir'         => __DIR__ . '/../uploads/inspeksi/',
        'url'         => env('APP_URL', 'http://localhost/inspeksi-bangunan') . '/uploads/inspeksi/',
    ],
];