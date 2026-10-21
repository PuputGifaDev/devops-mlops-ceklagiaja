<?php declare(strict_types=1); ?>
</div><!-- /.app-layout -->

<div class="sidebar-backdrop"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<?php if (!empty($extraScripts)): foreach ($extraScripts as $src): ?>
<script src="<?= e($src) ?>"></script>
<?php endforeach; endif; ?>

<?php if ($m = flash('success')): ?>
    <div class="app-toast alert alert-success alert-dismissible-auto" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i><?= e($m) ?>
    </div>
<?php endif; ?>
<?php if ($m = flash('error')): ?>
    <div class="app-toast alert alert-danger alert-dismissible-auto" role="alert">
        <i class="fa-solid fa-circle-exclamation me-2"></i><?= e($m) ?>
    </div>
<?php endif; ?>
</body>
</html>