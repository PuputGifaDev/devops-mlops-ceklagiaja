<?php
require_once __DIR__ . '/config/app.php';

// Kredensial admin default (UBAH SEBELUM JALANKAN JIKA PERLU)
$name = 'Administrator';
$username = 'admin';
$email = 'admin@example.com';
$password = 'Admin123!'; // Gunakan password yang kuat

$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    // Cek apakah username sudah ada
    $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $check->execute([$username]);
    
    if ($check->fetch()) {
        die("Akun admin sudah ada. Silakan hapus file ini.");
    }

    // Simpan ke database
    $stmt = $pdo->prepare("INSERT INTO users (name, username, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, 'admin', 1)");
    $stmt->execute([$name, $username, $email, $hash]);
    
    echo "<h1>Akun Admin Berhasil Dibuat!</h1>";
    echo "<p>Username: <b>{$username}</b></p>";
    echo "<p>Password: <b>{$password}</b></p>";
    echo "<p style='color:red; font-weight:bold;'>SEGERA HAPUS FILE create_admin.php INI!</p>";
    echo "<a href='login.php'>Klik di sini untuk Login</a>";
    
} catch (PDOException $e) {
    die("Gagal membuat akun admin: " . $e->getMessage());
}
?>