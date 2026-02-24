<?php
$baseUrl  = App::baseUrl();
$siteName = App::setting('site_name', 'ShopXPao');
$user     = Session::user();
$currentUri = $_SERVER['REQUEST_URI'];

// Vérification si on est dans le catalogue
$isCatalogue = (str_contains($currentUri, '/categories') || str_contains($currentUri, '/produits'));
?>

<!-- Chargement du CSS spécifique -->
<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/admin-sidebar.css">

<aside class="admin-sidebar" id="adminSidebar">
    <!-- HEADER avec logo -->
    <div class="admin-sidebar-header">
        <a href="<?= $baseUrl ?>/admin" class="admin-logo">
            <div class="admin-logo-icon">
                <i class="bi bi-speedometer2"></i>
            </div>
            <div>
                <div class="admin-logo-name">Administration</div>
                <div class="admin-logo-sub"><?= Security::escape($siteName) ?></div>
            </div>
        </a>
    </div>

    <!-- NAVIGATION -->
    <div class="admin-sidebar-content">
        
        <!-- Section Tableau de bord -->
        <div class="admin-nav-section">
            <div class="admin-nav-section-title">Tableau de bord</div>
            <a href="<?= $baseUrl ?>/admin" 
               class="admin-nav-link <?= str_ends_with($currentUri, '/admin') ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Vue d'ensemble</span>
            </a>
        </div>

        <!-- Section Gestion -->
        <div class="admin-nav-section">
            <div class="admin-nav-section-title">Gestion</div>
            
            <a href="<?= $baseUrl ?>/admin/users" 
               class="admin-nav-link <?= str_contains($currentUri, '/users') ? 'active' : '' ?>">
                <i class="bi bi-people"></i>
                <span>Utilisateurs</span>
            </a>

            <a href="<?= $baseUrl ?>/admin/tenants" 
               class="admin-nav-link <?= (str_contains($currentUri, '/tenants') || str_contains($currentUri, '/tenant-details')) ? 'active' : '' ?>">
                <i class="bi bi-shop"></i>
                <span>Boutiques</span>
            </a>

            <a href="<?= $baseUrl ?>/admin/orders" 
               class="admin-nav-link <?= (str_contains($currentUri, '/orders') || str_contains($currentUri, '/order-details')) ? 'active' : '' ?>">
                <i class="bi bi-bag-check"></i>
                <span>Commandes</span>
            </a>
            
            <!-- Catalogue avec sous-menu -->
            <button class="admin-nav-link has-children <?= $isCatalogue ? 'active' : '' ?>" 
                    type="button" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#sub-menu-cat" 
                    aria-expanded="<?= $isCatalogue ? 'true' : 'false' ?>">
                <i class="bi bi-tags"></i>
                <span>Catalogue</span>
                <i class="bi bi-chevron-down transition-icon"></i>
            </button>
            
            <div class="collapse <?= $isCatalogue ? 'show' : '' ?>" id="sub-menu-cat">
                <div class="admin-submenu-items">
                    <a href="<?= $baseUrl ?>/admin/categories" 
                       class="admin-sub-link <?= str_contains($currentUri, '/categories') ? 'active' : '' ?>">
                        <i class="bi bi-dot"></i> Catégories
                    </a>
                    <a href="<?= $baseUrl ?>/admin/produits" 
                       class="admin-sub-link <?= str_contains($currentUri, '/produits') ? 'active' : '' ?>">
                        <i class="bi bi-dot"></i> Produits
                    </a>
                </div>
            </div>
        </div>

        <!-- Section Finance -->
        <div class="admin-nav-section">
            <div class="admin-nav-section-title">Finance</div>
            
            <a href="<?= $baseUrl ?>/admin/paiements" 
               class="admin-nav-link <?= str_contains($currentUri, '/paiements') ? 'active' : '' ?>">
                <i class="bi bi-credit-card-2-front"></i>
                <span>Paiements</span>
            </a>
            
            <a href="<?= $baseUrl ?>/admin/statistiques" 
               class="admin-nav-link <?= str_contains($currentUri, '/statistiques') ? 'active' : '' ?>">
                <i class="bi bi-bar-chart-line"></i>
                <span>Statistiques</span>
            </a>
        </div>
    </div>
</aside>

<!-- Overlay pour mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du toggle mobile
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggler = document.getElementById('adminSidebarToggle');

    if (toggler) {
        toggler.addEventListener('click', function(e) {
            e.preventDefault();
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    }

    // Fermer avec la touche Echap
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('show')) {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        }
    });
});
</script>