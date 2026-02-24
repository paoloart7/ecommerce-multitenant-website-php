<?php 
$baseUrl = App::baseUrl();
$user = Session::user();
$q = $q ?? '';
$produits = $produits ?? [];
$boutiques = $boutiques ?? [];
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/recherche.css">

<div class="recherche-container">
    <!-- En-tête -->
    <div class="recherche-header">
        <h1>Résultats de recherche pour "<?= Security::escape($q) ?>"</h1>
        <p><?= count($produits) + count($boutiques) ?> résultat(s) trouvé(s)</p>
    </div>

    <!-- Filtres rapides -->
    <div class="recherche-filters">
        <button class="filter-btn active" data-filter="all">Tous</button>
        <button class="filter-btn" data-filter="produits">Produits (<?= count($produits) ?>)</button>
        <button class="filter-btn" data-filter="boutiques">Boutiques (<?= count($boutiques) ?>)</button>
    </div>

    <!-- Résultats -->
    <div class="recherche-results">
        <?php if (empty($produits) && empty($boutiques)): ?>
            <!-- Aucun résultat -->
            <div class="no-results">
                <i class="bi bi-search"></i>
                <h3>Aucun résultat trouvé</h3>
                <p>Essayez avec d'autres mots-clés ou parcourez nos catégories</p>
                <a href="<?= $baseUrl ?>/boutiques" class="btn-primary">
                    Voir toutes les boutiques
                </a>
            </div>
        <?php else: ?>
            <!-- Section Produits -->
            <?php if (!empty($produits)): ?>
                <div class="results-section produits-section">
                    <h2 class="section-title">
                        <i class="bi bi-box-seam"></i>
                        Produits (<?= count($produits) ?>)
                    </h2>
                    <div class="produits-grid">
                        <?php foreach ($produits as $produit): ?>
                            <div class="produit-card">
                                <!-- Lien sur l'image -->
                                <a href="<?= $baseUrl ?>/boutique/produit?id=<?= $produit['idProduit'] ?>" class="produit-image-link">
                                    <div class="produit-image">
                                        <?php if (!empty($produit['image'])): ?>
                                            <img src="<?= $baseUrl . $produit['image'] ?>" 
                                                alt="<?= Security::escape($produit['nomProduit']) ?>">
                                        <?php else: ?>
                                            <div class="produit-image-placeholder">
                                                <i class="bi bi-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                                
                                <div class="produit-info">
                                    <!-- Lien sur la boutique -->
                                    <a href="<?= $baseUrl ?>/boutique/<?= $produit['slugBoutique'] ?>" class="produit-boutique">
                                        <?= Security::escape($produit['nomBoutique']) ?>
                                    </a>
                                    
                                    <!-- Lien sur le nom du produit -->
                                    <a href="<?= $baseUrl ?>/boutique/<?= $produit['slugBoutique'] ?>/produit/<?= $produit['idProduit'] ?>" class="produit-nom">
                                        <?= Security::escape($produit['nomProduit']) ?>
                                    </a>
                                    
                                    <div class="produit-prix">
                                        <?php if (!empty($produit['prixPromo'])): ?>
                                            <span class="prix-promo"><?= number_format($produit['prixPromo'], 0, ',', ' ') ?> G</span>
                                            <span class="prix-original"><?= number_format($produit['prix'], 0, ',', ' ') ?> G</span>
                                        <?php else: ?>
                                            <span class="prix-normal"><?= number_format($produit['prix'], 0, ',', ' ') ?> G</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- ✅ BOUTON SIMPLE EN DESSOUS -->
                                    <button class="btn-add-to-cart-simple" 
                                            onclick="addToCart(<?= $produit['idProduit'] ?>, 
                                                            '<?= addslashes($produit['nomProduit']) ?>', 
                                                            <?= $produit['prixPromo'] ?: $produit['prix'] ?>,
                                                            '<?= addslashes($produit['image'] ?? '') ?>',
                                                            '<?= addslashes($produit['nomBoutique']) ?>')">
                                        <i class="bi bi-cart-plus"></i>
                                        Ajouter au panier
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Section Boutiques -->
            <?php if (!empty($boutiques)): ?>
                <div class="results-section boutiques-section">
                    <h2 class="section-title">
                        <i class="bi bi-shop"></i>
                        Boutiques (<?= count($boutiques) ?>)
                    </h2>
                    <div class="boutiques-grid">
                        <?php foreach ($boutiques as $boutique): ?>
                            <a href="<?= $baseUrl ?>/boutique/<?= $boutique['slugBoutique'] ?>" 
                               class="boutique-card">
                                <div class="boutique-logo">
                                    <?php if (!empty($boutique['logo'])): ?>
                                        <img src="<?= $baseUrl . $boutique['logo'] ?>" 
                                             alt="<?= Security::escape($boutique['nomBoutique']) ?>">
                                    <?php else: ?>
                                        <div class="boutique-logo-placeholder">
                                            <i class="bi bi-shop"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="boutique-info">
                                    <h3 class="boutique-nom"><?= Security::escape($boutique['nomBoutique']) ?></h3>
                                    <?php if (!empty($boutique['description'])): ?>
                                        <p class="boutique-description"><?= Security::escape($boutique['description']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script src="<?= $baseUrl ?>/assets/js/recherche.js"></script>