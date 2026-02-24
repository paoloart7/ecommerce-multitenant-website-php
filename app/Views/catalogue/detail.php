<?php 
$baseUrl = App::baseUrl();
$produit = $produit ?? [];
$images = $images ?? [];
$similaires = $similaires ?? [];
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/detail-produit.css">

<div class="detail-container">
    <!-- Fil d'Ariane -->
    <div class="breadcrumb">
        <a href="<?= $baseUrl ?>/">Accueil</a>
        <i class="bi bi-chevron-right"></i>
        <a href="<?= $baseUrl ?>/boutique/<?= $produit['slugBoutique'] ?>"><?= Security::escape($produit['nomBoutique']) ?></a>
        <i class="bi bi-chevron-right"></i>
        <span><?= Security::escape($produit['nomProduit']) ?></span>
    </div>

    <div class="produit-wrapper">
        <!-- Colonne gauche : Images -->
        <div class="produit-images">
            <!-- Image principale -->
            <div class="image-principale">
                <?php 
                $imagePrincipale = !empty($images) ? $images[0]['urlImage'] : null;
                ?>
                <?php if ($imagePrincipale): ?>
                    <img src="<?= $baseUrl . $imagePrincipale ?>" 
                         alt="<?= Security::escape($produit['nomProduit']) ?>"
                         id="mainImage">
                <?php else: ?>
                    <div class="image-placeholder">
                        <i class="bi bi-image"></i>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Miniatures -->
            <?php if (count($images) > 1): ?>
                <div class="image-miniatures">
                    <?php foreach ($images as $index => $img): ?>
                        <div class="miniature <?= $index === 0 ? 'active' : '' ?>" 
                             onclick="changeMainImage('<?= $baseUrl . $img['urlImage'] ?>', this)">
                            <img src="<?= $baseUrl . $img['urlImage'] ?>" 
                                 alt="<?= Security::escape($produit['nomProduit']) ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Colonne droite : Infos produit -->
        <div class="produit-infos">
            <!-- Boutique -->
            <a href="<?= $baseUrl ?>/boutique/<?= $produit['slugBoutique'] ?>" class="produit-boutique">
                <?php if (!empty($produit['logoBoutique'])): ?>
                    <img src="<?= $baseUrl . $produit['logoBoutique'] ?>" 
                         alt="<?= Security::escape($produit['nomBoutique']) ?>">
                <?php else: ?>
                    <i class="bi bi-shop"></i>
                <?php endif; ?>
                <span><?= Security::escape($produit['nomBoutique']) ?></span>
            </a>

            <!-- Nom produit -->
            <h1 class="produit-titre"><?= Security::escape($produit['nomProduit']) ?></h1>

            <!-- Prix -->
            <div class="produit-prix">
                <?php if (!empty($produit['prixPromo'])): ?>
                    <span class="prix-promo"><?= number_format($produit['prixPromo'], 0, ',', ' ') ?> G</span>
                    <span class="prix-original"><?= number_format($produit['prix'], 0, ',', ' ') ?> G</span>
                    <?php 
                    $economie = $produit['prix'] - $produit['prixPromo'];
                    $pourcentage = round(($economie / $produit['prix']) * 100);
                    ?>
                    <span class="badge-promo">-<?= $pourcentage ?>%</span>
                <?php else: ?>
                    <span class="prix-normal"><?= number_format($produit['prix'], 0, ',', ' ') ?> G</span>
                <?php endif; ?>
            </div>

            <!-- Stock -->
            <div class="produit-stock">
                <?php if ($produit['stock'] > 0): ?>
                    <i class="bi bi-check-circle-fill text-success"></i>
                    <span>En stock (<?= $produit['stock'] ?> disponibles)</span>
                <?php else: ?>
                    <i class="bi bi-x-circle-fill text-danger"></i>
                    <span>Rupture de stock</span>
                <?php endif; ?>
            </div>

            <!-- Quantité et bouton ajouter -->
            <?php if ($produit['stock'] > 0): ?>
                <div class="produit-actions">
                    <div class="quantite-selector">
                        <button class="qte-btn" onclick="changeQuantite(-1)">-</button>
                        <input type="number" id="quantite" value="1" min="1" max="<?= $produit['stock'] ?>" readonly>
                        <button class="qte-btn" onclick="changeQuantite(1)">+</button>
                    </div>
                    <button class="btn-ajouter-panier" 
                            onclick="addToCart(<?= $produit['idProduit'] ?>, 
                                              '<?= addslashes($produit['nomProduit']) ?>', 
                                              <?= $produit['prixPromo'] ?: $produit['prix'] ?>,
                                              '<?= addslashes($images[0]['urlImage'] ?? '') ?>',
                                              '<?= addslashes($produit['nomBoutique']) ?>')">
                        <i class="bi bi-cart-plus"></i>
                        Ajouter au panier
                    </button>
                </div>
            <?php endif; ?>

            <!-- Description courte -->
            <?php if (!empty($produit['descriptionCourte'])): ?>
                <div class="produit-description-courte">
                    <?= nl2br(Security::escape($produit['descriptionCourte'])) ?>
                </div>
            <?php endif; ?>

            <!-- Description complète (avec onglet) -->
            <?php if (!empty($produit['descriptionComplete'])): ?>
                <div class="produit-description-complete">
                    <h3>Description détaillée</h3>
                    <div class="description-content">
                        <?= nl2br(Security::escape($produit['descriptionComplete'])) ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Caractéristiques -->
            <?php if ($produit['poids'] || $produit['dimensions']): ?>
                <div class="produit-caracteristiques">
                    <h3>Caractéristiques</h3>
                    <table>
                        <?php if ($produit['poids']): ?>
                            <tr>
                                <td>Poids</td>
                                <td><?= $produit['poids'] ?> kg</td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($produit['dimensions']): 
                            $dim = json_decode($produit['dimensions'], true);
                        ?>
                            <tr>
                                <td>Dimensions</td>
                                <td>
                                    <?= $dim['longueur'] ?? 0 ?> x <?= $dim['largeur'] ?? 0 ?> x <?= $dim['hauteur'] ?? 0 ?> cm
                                </td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Produits similaires -->
    <?php if (!empty($similaires)): ?>
        <div class="produits-similaires">
            <h2>Produits similaires</h2>
            <div class="similaires-grid">
                <?php foreach ($similaires as $sim): ?>
                    <a href="<?= $baseUrl ?>/produit/<?= $sim['idProduit'] ?>" class="similaire-card">
                        <div class="similaire-image">
                            <?php if (!empty($sim['image'])): ?>
                                <img src="<?= $baseUrl . $sim['image'] ?>" alt="<?= Security::escape($sim['nomProduit']) ?>">
                            <?php else: ?>
                                <div class="similaire-placeholder">
                                    <i class="bi bi-image"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="similaire-info">
                            <div class="similaire-nom"><?= Security::escape($sim['nomProduit']) ?></div>
                            <div class="similaire-prix">
                                <?= number_format($sim['prixPromo'] ?: $sim['prix'], 0, ',', ' ') ?> G
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="<?= $baseUrl ?>/assets/js/detail-produit.js"></script>