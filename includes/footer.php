<?php declare(strict_types=1); ?>

    <!-- Penutup dari .page-container dan .main-content yang dibuka di sidebar -->
        </div><!-- /.page-container -->
    </main><!-- /.main-content -->

</div><!-- /.app-layout -->

<!-- Backdrop untuk sidebar mobile -->
<div class="sidebar-backdrop"></div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>

<?php if (!empty($extraScripts)): ?>
    <?php foreach ((array)$extraScripts as $src): ?>
        <script src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1060;">
    <?php if ($m = flash('success')): ?>
        <div class="app-toast alert alert-success alert-dismissible-auto shadow-sm" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i><?= e($m) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($m = flash('error')): ?>
        <div class="app-toast alert alert-danger alert-dismissible-auto shadow-sm" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i><?= e($m) ?>
        </div>
    <?php endif; ?>
</div>

<script>
    // Auto-hide Toast (opsional, jika tidak ditangani oleh app.js)
    document.addEventListener('DOMContentLoaded', () => {
        const toasts = document.querySelectorAll('.alert-dismissible-auto');
        toasts.forEach(toast => {
            setTimeout(() => {
                toast.classList.add('fade');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        });
    });
</script>

</body>
</html>