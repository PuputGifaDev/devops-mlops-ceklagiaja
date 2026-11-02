/**
 * inspection.js
 * Logika untuk formulir inspeksi dinamis (User)
 */

document.addEventListener('DOMContentLoaded', function () {
    
    // Mencari semua select kondisi di dalam form inspeksi
    const conditionSelects = document.querySelectorAll('.condition-select');

    conditionSelects.forEach(function (select) {
        // Fungsi untuk mengecek nilai dan menampilkan/menyembunyikan input terkait
        function handleConditionChange() {
            const itemId = select.getAttribute('data-id');
            const value = select.value;
            
            const noteWrapper = document.getElementById(`note-wrapper-${itemId}`);
            const photoWrapper = document.getElementById(`photo-wrapper-${itemId}`);
            const noteInput = document.getElementById(`note-input-${itemId}`);
            
            // Aturan bisnis: Jika 'damaged' atau 'attention', wajib isi catatan
            if (value === 'damaged' || value === 'attention') {
                if (noteWrapper) {
                    noteWrapper.classList.remove('d-none');
                    // Animasi slide down sederhana
                    noteWrapper.style.animation = 'fadeInUp 0.3s ease-out forwards';
                }
                if (noteInput) noteInput.setAttribute('required', 'required');
            } else {
                if (noteWrapper) noteWrapper.classList.add('d-none');
                if (noteInput) noteInput.removeAttribute('required');
            }
            
            // Logika untuk foto bisa disesuaikan (misal jika damaged wajib foto)
            if (value === 'damaged' && photoWrapper) {
                 photoWrapper.classList.remove('d-none');
            } else if (photoWrapper) {
                 photoWrapper.classList.add('d-none');
            }
        }

        // Jalankan saat pertama kali load
        handleConditionChange();
        
        // Jalankan setiap kali dropdown berubah
        select.addEventListener('change', handleConditionChange);
    });

    // --- Logika Preview Foto (Jika ada fitur upload foto) ---
    const photoInputs = document.querySelectorAll('.photo-upload-input');
    photoInputs.forEach(function(input) {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const previewId = this.getAttribute('data-preview');
            const previewImg = document.getElementById(previewId);
            
            if (file && previewImg) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewImg.classList.remove('d-none');
                }
                reader.readAsDataURL(file);
            }
        });
    });
    
    // --- Indikator Progress Pengisian Form ---
    const form = document.getElementById('inspectionForm');
    if (form) {
        const totalItems = document.querySelectorAll('.condition-select').length;
        const progressBar = document.getElementById('formProgressBar');
        
        form.addEventListener('change', function() {
            let filled = 0;
            document.querySelectorAll('.condition-select').forEach(sel => {
                if (sel.value !== 'not_checked' && sel.value !== '') filled++;
            });
            
            if (progressBar) {
                const percentage = Math.round((filled / totalItems) * 100);
                progressBar.style.width = percentage + '%';
                progressBar.setAttribute('aria-valuenow', percentage);
                progressBar.innerHTML = percentage + '%';
            }
        });
    }
});