            </div> <!-- End .container-fluid -->
        </div> <!-- End .app-content -->
    </main>
</div> <!-- End .app-wrapper -->

<!-- Core JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@4.10.0/dist/js/adminlte.min.js"></script>

<!-- Custom JS Global -->
<script src="<?= BASE_URL ?>assets/js/app.js"></script>

<!-- Page Specific JS -->
<?php if (isset($pageScript)): ?>
    <?php foreach ((array)$pageScript as $script): ?>
        <script src="<?= BASE_URL ?>assets/js/<?= $script ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>