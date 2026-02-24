<?php 
$baseUrl = App::baseUrl();
$boutique = $boutique ?? [];
$produits = $produits ?? [];
$categories = $categories ?? [];
$pagination = $pagination ?? ['current' => 1, 'total' => 1];
$filtre_categorie = $filtre_categorie ?? null;
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/boutique.css">

<div class="boutique-detail-container">
    <!-- Fil d'Ariane -->
    <div class="breadcrumb">
        <a href="<?= $baseUrl ?>/boutiques">Boutiques</a>
        <i class="bi bi-chevron-right"></i>
        <span><?= Security::escape($boutique['nomBoutique']) ?></span>
    </div>
    <!-- En-tête de la boutique -->
    <div class="boutique-header" 
        style="<?= !empty($boutique['banniere']) ? 'background-image: url(' . $baseUrl . $boutique['banniere'] . ');' : '' ?>">
        <div class="header-overlay"></div>
        
        <div class="header-content">
            <div class="boutique-logo-large">
                <?php if (!empty($boutique['logo'])): ?>
                    <img src="<?= $baseUrl . $boutique['logo'] ?>" alt="<?= Security::escape($boutique['nomBoutique']) ?>">
                <?php else: ?>
                    <div class="logo-placeholder-large">
                        <i class="bi bi-shop"></i>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="boutique-infos">
                <h1><?= Security::escape($boutique['nomBoutique']) ?></h1>
                
                <?php if (!empty($boutique['descriptionBoutique'])): ?>
                    <p class="boutique-description-large"><?= Security::escape($boutique['descriptionBoutique']) ?></p>
                <?php elseif (!empty($boutique['description'])): ?>
                    <p class="boutique-description-large"><?= Security::escape($boutique['description']) ?></p>
                <?php endif; ?>
                
                <div class="boutique-meta">
                    <span class="meta-item">
                        <i class="bi bi-person"></i>
                        Vendeur: <?= Security::escape($boutique['prenomUtilisateur'] . ' ' . $boutique['nomUtilisateur']) ?>
                    </span>
                    <span class="meta-item">
                        <i class="bi bi-calendar"></i>
                        Inscrit le <?= date('d/m/Y', strtotime($boutique['dateCreation'] ?? '')) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres par catégorie -->
    <?php if (!empty($categories)): ?>
        <div class="categories-filtres">
            <a href="<?= $baseUrl ?>/boutique/<?= $boutique['slugBoutique'] ?>" 
               class="filtre-categorie <?= !$filtre_categorie ? 'active' : '' ?>">
                Tous les produits
            </a>
            <?php foreach ($categories as $cat): ?>
                <?php if ($cat['nb_produits'] > 0): ?>
                    <a href="<?= $baseUrl ?>/boutique/<?= $boutique['slugBoutique'] ?>?categorie=<?= $cat['idCategorie'] ?>" 
                       class="filtre-categorie <?= $filtre_categorie == $cat['idCategorie'] ? 'active' : '' ?>">
                        <?= Security::escape($cat['nomCategorie']) ?>
                        <span class="count">(<?= $cat['nb_produits'] ?>)</span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Grille des produits -->
    <div class="produits-section">
        <h2 class="section-title">Nos produits</h2>
        
        <?php if (empty($produits)): ?>
            <div class="empty-produits">
                <i class="bi bi-box-seam"></i>
                <h3>Aucun produit trouvé</h3>
                <p>Cette boutique n'a pas encore de produits dans cette catégorie.</p>
            </div>
        <?php else: ?>
            <div class="produits-grid">
                <?php foreach ($produits as $p): ?>
                    <div class="produit-card">
                        <a href="<?= $baseUrl ?>/boutique/produit?id=<?= $p['idProduit'] ?>" class="produit-image-link">
                            <div class="produit-image">
                                <?php if (!empty($p['image'])): ?>
                                    <img src="<?= $baseUrl . $p['image'] ?>" alt="<?= Security::escape($p['nomProduit']) ?>">
                                <?php else: ?>
                                    <div class="image-placeholder">
                                        <i class="bi bi-image"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                        <div class="produits-grid">
                            <?php foreach ($produits as $p): ?>
                                <div class="produit-card">
                                    <a href="<?= $baseUrl ?>/boutique/produit?id=<?= $p['idProduit'] ?>" class="produit-image-link">
                                        <div class="produit-image">
                                            <?php if (!empty($p['image'])): ?>
                                                <img src="<?= $baseUrl . $p['image'] ?>" alt="<?= Security::escape($p['nomProduit']) ?>">
                                            <?php else: ?>
                                                <div class="image-placeholder">
                                                    <i class="bi bi-image"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                    
                                    <div class="produit-info">
                                        <!-- ✅ LIEN VERS LA BOUTIQUE AJOUTÉ -->
                                        <a href="<?= $baseUrl ?>/boutique?slug=<?= urlencode($p['slugBoutique']) ?>" class="produit-boutique-link">
                                            <i class="bi bi-shop"></i>
                                            <?= Security::escape($p['nomBoutique']) ?>
                                        </a>
                                            
                                        <h3 class="produit-nom">
                                            <a href="<?= $baseUrl ?>/boutique/produit?id=<?= $p['idProduit'] ?>">
                                                <?= Security::escape($p['nomProduit']) ?>
                                            </a>
                                        </h3>
                                        
                                        <div class="produit-prix">
                                            <?php if (!empty($p['prixPromo'])): ?>
                                                <span class="prix-promo"><?= number_format($p['prixPromo'], 0, ',', ' ') ?> G</span>
                                                <span class="prix-original"><?= number_format($p['prix'], 0, ',', ' ') ?> G</span>
                                            <?php else: ?>
                                                <span class="prix-normal"><?= number_format($p['prix'], 0, ',', ' ') ?> G</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <button class="btn-add-to-cart-simple" 
                                                onclick="addToCart(<?= $p['idProduit'] ?>, 
                                                                '<?= addslashes($p['nomProduit']) ?>', 
                                                                <?= $p['prixPromo'] ?: $p['prix'] ?>,
                                                                '<?= addslashes($p['image'] ?? '') ?>',
                                                                '<?= addslashes($p['nomBoutique']) ?>')">
                                            <i class="bi bi-cart-plus"></i>
                                            Ajouter au panier
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <!-- Pagination -->
            <?php if ($pagination['total'] > 1): ?>
                <div class="pagination">
                    <?php if ($pagination['current'] > 1): ?>
                        <a href="?page=<?= $pagination['current'] - 1 ?><?= $filtre_categorie ? '&categorie=' . $filtre_categorie : '' ?>" 
                           class="page-link">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $pagination['total']; $i++): ?>
                        <a href="?page=<?= $i ?><?= $filtre_categorie ? '&categorie=' . $filtre_categorie : '' ?>" 
                           class="page-link <?= $i == $pagination['current'] ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($pagination['current'] < $pagination['total']): ?>
                        <a href="?page=<?= $pagination['current'] + 1 ?><?= $filtre_categorie ? '&categorie=' . $filtre_categorie : '' ?>" 
                           class="page-link">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script src="<?= $baseUrl ?>/assets/js/boutique.js"></script>