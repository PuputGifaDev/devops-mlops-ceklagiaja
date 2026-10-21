<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in(): bool {
    return isset($_SESSION['user_id'], $_SESSION['role']);
}

function current_user(): ?array {
    if (!is_logged_in()) return null;
    return [
        'id'       => (int)$_SESSION['user_id'],
        'name'     => $_SESSION['name']     ?? '',
        'username' => $_SESSION['username'] ?? '',
        'role'     => $_SESSION['role'],
    ];
}

function require_login(): void {
    if (!is_logged_in()) {
        flash('error', 'Silakan login terlebih dahulu.');
        redirect('login.php');
    }
}

function require_role(string ...$roles): void {
    require_login();
    if (!in_array($_SESSION['role'], $roles, true)) {
        http_response_code(403);
        exit('Akses ditolak. Anda tidak memiliki izin untuk halaman ini.');
    }
}

function require_inspection_ownership(PDO $pdo, int $inspectionId): array {
    require_login();
    $uid  = (int)$_SESSION['user_id'];
    $role = $_SESSION['role'];

    if ($role === 'admin') {
        $stmt = $pdo->prepare('SELECT * FROM inspections WHERE id = :id');
        $stmt->execute(['id' => $inspectionId]);
    } else {
        $stmt = $pdo->prepare(
            'SELECT * FROM inspections WHERE id = :id AND assigned_user_id = :uid'
        );
        $stmt->execute(['id' => $inspectionId, 'uid' => $uid]);
    }
    $row = $stmt->fetch();
    if (!$row) {
        http_response_code(403);
        exit('Anda tidak berhak mengakses inspeksi ini.');
    }
    return $row;
}

function log_activity(PDO $pdo, string $action, ?string $entityType = null,
                      ?int $entityId = null, ?string $description = null): void {
    try {
        $pdo->prepare(
            'INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address)
             VALUES (:uid, :action, :etype, :eid, :desc, :ip)'
        )->execute([
            'uid'    => $_SESSION['user_id'] ?? null,
            'action' => $action,
            'etype'  => $entityType,
            'eid'    => $entityId,
            'desc'   => $description,
            'ip'     => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Throwable $e) {
        error_log('[LOG ERROR] ' . $e->getMessage());
    }
}