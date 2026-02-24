    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <?php if(isset($extra_js)): ?>
        <script src="<?= App::baseUrl() ?>/assets/js/<?= $extra_js ?>.js"></script>
    <?php endif; ?>
</body>
</html>