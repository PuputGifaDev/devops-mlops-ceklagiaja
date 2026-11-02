/**
 * app.js
 * Core JavaScript untuk Sistem Informasi Inspeksi Bangunan
 */

document.addEventListener('DOMContentLoaded', function () {
    
    // 1. Animasi Auto-hide untuk Flash Message (Alert)
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            // Gunakan Bootstrap Alert API untuk menutup dengan animasi fade
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000); // Tertutup otomatis setelah 5 detik
    });

    // 2. Inisialisasi Tooltips Bootstrap (Jika ada elemen dengan data-bs-toggle="tooltip")
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // 3. Konfirmasi Aksi Hapus/Nonaktifkan
    const deleteButtons = document.querySelectorAll('.btn-confirm-delete');
    deleteButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const message = this.getAttribute('data-message') || 'Apakah Anda yakin ingin memproses data ini?';
            const form = this.closest('form');
            
            if (confirm(message)) {
                if (form) {
                    form.submit();
                } else {
                    window.location.href = this.getAttribute('href');
                }
            }
        });
    });

    // 4. Efek Transisi Sidebar di Mobile
    const sidebarToggle = document.querySelector('[data-lte-toggle="sidebar"]');
    const sidebar = document.querySelector('.app-sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            // Menambahkan kelas animasi CSS kustom (lihat app.css)
            sidebar.classList.toggle('sidebar-animating');
        });
    }
});