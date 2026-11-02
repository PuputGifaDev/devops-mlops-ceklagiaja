<?php
require_once __DIR__ . '/config/app.php';

// Jika sudah login, lempar ke index (router utama)
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Regenerasi session ID untuk mencegah Session Fixation
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['role']    = $user['role'];

            // Catat log login
            try {
                $stmtLog = $pdo->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, created_at) VALUES (?, 'LOGIN', 'users', ?, ?, ?, NOW())");
                $stmtLog->execute([$user['id'], $user['id'], "Pengguna {$user['name']} ({$user['role']}) berhasil login.", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
                
                // Update last_login_at
                $pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?")->execute([$user['id']]);
            } catch (Exception $e) {}
            
            header('Location: index.php');
            exit;
        } else {
            $error = 'Username/email atau password salah, atau akun sedang dinonaktifkan.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/app.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/auth.css">
    <style>
        body.login-page {
            background: radial-gradient(circle at 10% 20%, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
            max-width: 440px;
            width: 100%;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .demo-pill {
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.8rem;
        }
        .demo-pill:hover {
            transform: translateY(-2px);
            filter: brightness(0.95);
        }
    </style>
</head>
<body class="login-page">

<div class="login-card p-4 p-md-5">
    <div class="text-center mb-4">
        <div class="bg-primary text-white rounded-4 p-3 d-inline-flex align-items-center justify-content-center shadow mb-3" style="width: 60px; height: 60px;">
            <i class="bi bi-building fs-2"></i>
        </div>
        <h3 class="fw-bold text-dark mb-1">CeklagiAja</h3>
        <p class="text-muted small">Sistem Informasi & Inspeksi Bangunan Gedung</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 px-3 small border-0 shadow-sm mb-4">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= esc($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        
        <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Username atau Email</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                <input type="text" id="usernameInput" name="username" class="form-control border-start-0 bg-light" placeholder="Masukkan username" required autofocus value="<?= esc($_POST['username'] ?? '') ?>">
            </div>
        </div>
        
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label small fw-semibold text-dark mb-0">Kata Sandi</label>
            </div>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                <input type="password" id="passwordInput" name="password" class="form-control border-start-0 bg-light" placeholder="Masukkan password" required>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm mb-4">
            <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Sistem
        </button>
    </form>

    <!-- Quick Demo Accounts -->
    <div class="p-3 bg-light rounded-3 border mb-3">
        <div class="small fw-bold text-dark mb-2 text-center text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">
            <i class="bi bi-key-fill text-warning me-1"></i> Akses Cepat Akun Demo (Klik untuk Isi):
        </div>
        <div class="d-flex flex-column gap-2">
            <button type="button" onclick="fillLogin('admin', 'Admin123!')" class="btn btn-sm btn-dark text-start demo-pill py-1 px-3 d-flex justify-content-between align-items-center">
                <span><b>Admin:</b> admin</span>
                <span class="badge bg-secondary">Admin123!</span>
            </button>
            <button type="button" onclick="fillLogin('layra', 'User123!')" class="btn btn-sm btn-outline-primary text-start demo-pill py-1 px-3 d-flex justify-content-between align-items-center">
                <span><b>Petugas:</b> layra</span>
                <span class="badge bg-primary bg-opacity-25 text-primary">User123!</span>
            </button>
            <button type="button" onclick="fillLogin('agung', 'User123!')" class="btn btn-sm btn-outline-info text-start demo-pill py-1 px-3 d-flex justify-content-between align-items-center">
                <span><b>Petugas:</b> agung</span>
                <span class="badge bg-info bg-opacity-25 text-dark">User123!</span>
            </button>
        </div>
    </div>

    <div class="text-center pt-2">
        <a href="<?= BASE_URL ?>public/buildings/index.php" class="small text-muted text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Portal Publik
        </a>
    </div>
</div>

<script>
function fillLogin(user, pass) {
    document.getElementById('usernameInput').value = user;
    document.getElementById('passwordInput').value = pass;
    document.getElementById('usernameInput').focus();
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
