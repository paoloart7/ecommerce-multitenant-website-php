<?php
$baseUrl = App::baseUrl();
$siteName = App::setting('site_name', 'ShopXPao');
$favicon = App::setting('site_favicon', '/assets/images/favicon.png');
$pageTitle = $pageTitle ?? $siteName;

// --- DÉTECTION AUTOMATIQUE DU CONTEXTE ---
$uri = $_SERVER['REQUEST_URI'];
$isAuthPage = $isAuthPage ?? false;
$isAdminPage = $isAdminPage ?? str_contains($uri, '/admin');
$isTenantPage = str_contains($uri, '/vendeur/'); 

// Mega Menu (Uniquement pour visiteurs)
if (!$isAuthPage && !$isAdminPage && !$isTenantPage) {
    if (!isset($categoriesPrincipales)) {
        $db = Database::getInstance();
        $categoriesPrincipales = $db->fetchAll(
            "SELECT idCategorie, nomCategorie 
             FROM categorie 
             WHERE idCategorieParent IS NULL AND actif = 1 
             ORDER BY ordre ASC, nomCategorie ASC"
        );
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Security::escape($pageTitle) ?></title>
    <link rel="icon" href="<?= $baseUrl . $favicon ?>">
    
    <!-- ===== STYLES ===== -->
    <link href="<?= $baseUrl ?>/assets/css/bootstrap.min.css" rel="stylesheet">    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= $baseUrl ?>/assets/css/custom.css" rel="stylesheet">
    <link href="<?= $baseUrl ?>/assets/css/home.css" rel="stylesheet">
    <link href="<?= $baseUrl ?>/assets/css/auth.css" rel="stylesheet">
    
    <?php if ($isAdminPage): ?>
        <link href="<?= $baseUrl ?>/assets/css/admin-sidebar.css" rel="stylesheet">
        <link href="<?= $baseUrl ?>/assets/css/admin.css" rel="stylesheet">
    <?php endif; ?>
    
    <?php if ($isTenantPage): ?>
        <link href="<?= $baseUrl ?>/assets/css/tenant-sidebar.css" rel="stylesheet">
    <?php endif; ?>

    <?php if (!$isAuthPage && !$isAdminPage && !$isTenantPage): ?>
        <script src="<?= $baseUrl ?>/assets/js/app.js"></script> 
    <?php endif; ?>
</head>
<body class="sx-body <?= $isTenantPage ? 'tenant-mode' : '' ?>">

<!-- ===== HEADER PUBLIC ===== -->
<?php if (!$isAuthPage && !$isAdminPage && !$isTenantPage): ?>
    <?php require dirname(__DIR__) . '/partials/navbar.php'; ?>
<?php endif; ?>

<!-- ===== CONTENU PRINCIPAL ===== -->
<main class="<?= $isTenantPage ? 'tenant-main' : '' ?>">
    <?php
    if (isset($__view) && file_exists($__view)) {
        require $__view;
    } else {
        echo "<div class='container py-5'><p class='alert alert-danger'>Vue introuvable</p></div>";
    }
    ?>
</main>

<!-- ===== FOOTER PUBLIC ===== -->
<?php if (!$isAuthPage && !$isAdminPage && !$isTenantPage): ?>
    <?php require dirname(__DIR__) . '/partials/footer-public.php'; ?>
<?php endif; ?>

<!-- ===== PANIER OFF-CANVAS ===== -->
<div class="cart-overlay" id="cartOverlay"></div>
<div class="cart-sidebar" id="cartSidebar">
    <div class="cart-header">
        <h3><i class="bi bi-bag"></i> Mon panier</h3>
        <button class="cart-close" id="cartClose"><i class="bi bi-x"></i></button>
    </div>
    <div class="cart-items" id="cartItems">
        <div class="cart-loading">
            <div class="spinner"></div>
            <p>Chargement de votre panier...</p>
        </div>
    </div>
    <div class="cart-footer" id="cartFooter">
        <div class="cart-total">
            <span>Total</span>
            <span class="total-price" id="cartTotal">0 G</span>
        </div>
        <div class="cart-actions">
            <a href="<?= $baseUrl ?>/panier" class="btn-view-cart">Voir le panier</a>
            <a href="<?= $baseUrl ?>/commander" class="btn-checkout">Commander</a>
        </div>
    </div>
</div>

<!-- ===== SCRIPTS ===== -->
<script src="<?= $baseUrl ?>/assets/js/bootstrap.bundle.min.js"></script>
<script src="<?= $baseUrl ?>/assets/js/auth.js"></script>
<script>
    const BASE_URL = '<?= App::baseUrl() ?>';
</script>
<?php if (!$isAuthPage && !$isAdminPage && !$isTenantPage): ?>
    <script src="<?= $baseUrl ?>/assets/js/app.js"></script>
    <script src="<?= $baseUrl ?>/assets/js/mega-menu.js"></script>
<?php endif; ?>

<?php if ($isAdminPage): ?>
    <script src="<?= $baseUrl ?>/assets/js/admin.js"></script>
<?php endif; ?>

<?php if ($isTenantPage): ?>
    <script src="<?= $baseUrl ?>/assets/js/tenant-dashboard.js"></script>
    <script src="<?= $baseUrl ?>/assets/js/tenant-products.js"></script>
    <script src="<?= $baseUrl ?>/assets/js/categories.js"></script>
    <script src="<?= $baseUrl ?>/assets/js/shop-settings.js"></script>
<?php endif; ?>

<!-- Script du compteur panier -->
<script>
    function updateCartCounter() {
        fetch('<?= $baseUrl ?>/api/cart/count')
            .then(res => res.json())
            .then(data => {
                const counter = document.querySelector('.cart-counter');
                if (counter) {
                    counter.textContent = data.count > 0 ? data.count : '';
                    counter.style.display = data.count > 0 ? 'flex' : 'none';
                }
            });
    }
    document.addEventListener('DOMContentLoaded', updateCartCounter);
</script>

</body>
</html>