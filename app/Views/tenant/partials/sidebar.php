<?php
$baseUrl = App::baseUrl();
$currentUri = $_SERVER['REQUEST_URI'];
$user = Session::user();

// Récupération des données (à passer depuis le contrôleur)
$tenant = $tenant ?? [];
$user = $user ?? [];

// Données Boutique - Priorité: $tenant passé > $user > valeurs par défaut
$shopName = $tenant['nomBoutique'] ?? $user['nomBoutique'] ?? 'Ma Boutique';
$shopSlug = $tenant['slugBoutique'] ?? $user['slugBoutique'] ?? 'vendeur';

// Logo de la boutique (depuis parametre_boutique)
$shopLogo = $tenant['logo'] ?? null;
$logoPath = !empty($shopLogo) ? $baseUrl . $shopLogo : null;

// Initiales pour fallback
$shopInitials = strtoupper(substr($shopName, 0, 2));
if (empty(trim($shopInitials))) $shopInitials = 'SH';
?>

<!-- Structure HTML -->
<aside class="tenant-sidebar" id="tenantSidebar">
    
    <!-- HEADER avec logo boutique -->
    <div class="tenant-sidebar-header">
        <div class="shop-brand">
            <div class="shop-logo-container">
                <?php if($logoPath): ?>
                    <img src="<?= $logoPath ?>" alt="Logo <?= Security::escape($shopName) ?>">
                <?php else: ?>
                    <?= $shopInitials ?>
                <?php endif; ?>
            </div>
            <div class="shop-name-wrapper">
                <span class="shop-title"><?= Security::escape($shopName) ?></span>
                <span class="shop-status">@<?= Security::escape($shopSlug) ?></span>
            </div>
        </div>
    </div>

    <!-- NAVIGATION -->
    <div class="tenant-sidebar-content">
        
        <div class="nav-section">
            <div class="nav-section-title">Principal</div>
            <a href="<?= $baseUrl ?>/vendeur/tableau-de-bord" class="nav-item <?= str_contains($currentUri, 'tableau-de-bord') ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2-fill"></i><span>Vue d'ensemble</span>
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Ventes</div>
            <a href="<?= $baseUrl ?>/vendeur/commandes" class="nav-item <?= str_contains($currentUri, 'commandes') ? 'active' : '' ?>">
                <i class="bi bi-bag-check-fill"></i><span>Commandes</span>
            </a>
            <a href="<?= $baseUrl ?>/vendeur/clients" class="nav-item <?= str_contains($currentUri, 'clients') ? 'active' : '' ?>">
                <i class="bi bi-people-fill"></i><span>Clients</span>
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Catalogue</div>
            <a href="<?= $baseUrl ?>/vendeur/mes-produits" class="nav-item <?= str_contains($currentUri, 'produits') ? 'active' : '' ?>">
                <i class="bi bi-box-seam-fill"></i><span>Produits</span>
            </a>
            <a href="<?= $baseUrl ?>/vendeur/categories" class="nav-item <?= str_contains($currentUri, 'categories') ? 'active' : '' ?>">
                <i class="bi bi-tags-fill"></i><span>Catégories</span>
            </a>
        </div>
        <!-- Dans la navigation, après "Ventes" ou "Catalogue" -->
        <div class="nav-section">
            <div class="nav-section-title">Analyses</div>
            <a href="<?= $baseUrl ?>/vendeur/statistiques" class="nav-item <?= str_contains($currentUri, 'statistiques') ? 'active' : '' ?>">
                <i class="bi bi-bar-chart-line-fill"></i><span>Statistiques</span>
            </a>
            <a href="<?= $baseUrl ?>/vendeur/rapports" class="nav-item <?= str_contains($currentUri, 'rapports') ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-bar-graph-fill"></i><span>Rapports</span>
            </a>
        </div>
        <div class="nav-section">
            <div class="nav-section-title">Réglages</div>
            <a href="<?= $baseUrl ?>/vendeur/parametres" class="nav-item <?= str_contains($currentUri, 'parametres') ? 'active' : '' ?>">
                <i class="bi bi-sliders"></i><span>Boutique</span>
            </a>
        </div>

    </div>
</aside>
<!-- Overlay Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- JS pour Mobile -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('tenantSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggler = document.getElementById('tenantSidebarToggle');

        if(toggler) {
            toggler.addEventListener('click', function(e) {
                e.preventDefault();
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
            });
        }

        if(overlay) {
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            });
        }
    });
</script>