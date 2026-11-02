<?php
/**
 * includes/sidebar.php
 * Fallback sidebar (memanggil sidebar berdasarkan role)
 */
if (currentUserRole() === 'admin') {
    include __DIR__ . '/admin-sidebar.php';
} else {
    include __DIR__ . '/user-sidebar.php';
}
?>