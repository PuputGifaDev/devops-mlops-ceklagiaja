<?php
/**
 * includes/helpers.php
 * Kumpulan fungsi bantuan (utility)
 */

// Menyimpan pesan sementara (flash message)
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

// Menampilkan dan menghapus pesan flash
function displayFlash(): void {
    if (isset($_SESSION['flash'])) {
        $type = $_SESSION['flash']['type'];
        $msg = $_SESSION['flash']['message'];
        
        echo "<div class='alert alert-{$type} alert-dismissible fade show shadow-sm' role='alert'>
                {$msg}
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
              </div>";
        
        unset($_SESSION['flash']);
    }
}

// Sanitasi output untuk mencegah XSS
function esc($string): string {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}
?>