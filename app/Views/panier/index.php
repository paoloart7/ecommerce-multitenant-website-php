<?php 
$baseUrl = App::baseUrl();
$items = $items ?? [];
$total = $total ?? 0;
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/panier.css">

<div class="panier-container">
    <h1 class="panier-titre">🛒 Mon panier</h1>

    <?php if (empty($items)): ?>
        <div class="panier-vide">
            <i class="bi bi-cart-x"></i>
            <h3>Votre panier est vide</h3>
            <p>Découvrez nos boutiques et trouvez des produits à ajouter</p>
            <a href="<?= $baseUrl ?>/boutiques" class="btn-continuer">
                Continuer mes achats
            </a>
        </div>
    <?php else: ?>
        <div class="panier-content">
            <!-- Liste des articles -->
            <div class="panier-items">
                <?php foreach ($items as $id => $item): ?>
                    <div class="panier-item" data-id="<?= $id ?>">
                        <!-- Image -->
                        <div class="item-image">
                            <?php if (!empty($item['image'])): ?>
                                <img src="<?= $baseUrl . $item['image'] ?>" 
                                     alt="<?= Security::escape($item['nom']) ?>">
                            <?php else: ?>
                                <div class="item-image-placeholder">
                                    <i class="bi bi-image"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Infos -->
                        <div class="item-info">
                            <div class="item-boutique">
                                <?= Security::escape($item['boutique']) ?>
                            </div>
                            <div class="item-nom">
                                <?= Security::escape($item['nom']) ?>
                            </div>
                            <div class="item-prix">
                                <?= number_format($item['prix'], 0, ',', ' ') ?> G
                            </div>
                        </div>

                        <!-- Quantité et actions -->
                        <div class="item-actions">
                            <div class="quantite-control">
                                <button class="qte-btn" onclick="updateQuantite(<?= $id ?>, -1)">-</button>
                                <span class="qte-valeur" id="qte-<?= $id ?>"><?= $item['quantite'] ?></span>
                                <button class="qte-btn" onclick="updateQuantite(<?= $id ?>, 1)">+</button>
                            </div>
                            <div class="item-total">
                                <?= number_format($item['prix'] * $item['quantite'], 0, ',', ' ') ?> G
                            </div>
                            <button class="btn-supprimer" onclick="removeItem(<?= $id ?>)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <!-- Récapitulatif -->
            <div class="panier-recap">
                <h3>Récapitulatif</h3>
                <div class="recap-lignes">
                    <div class="recap-ligne">
                        <span>Sous-total</span>
                        <span id="sous-total"><?= number_format($total, 0, ',', ' ') ?> G</span>
                    </div>
                    <div class="recap-ligne">
                        <span>Taxe (<?= $taxe ?>%)</span>
                        <span id="montant-taxe"><?= number_format($montantTaxe, 0, ',', ' ') ?> G</span>
                    </div>
                    <div class="recap-ligne">
                        <span>Remise</span>
                        <span id="remise"><?= number_format($remise, 0, ',', ' ') ?> G</span>
                    </div>
                    <div class="recap-ligne">
                        <span>Livraison</span>
                        <span id="livraison"><?= number_format($livraison, 0, ',', ' ') ?> G</span>
                    </div>
                    <div class="recap-ligne total">
                        <span>Total</span>
                        <span id="total-general"><?= number_format($totalFinal, 0, ',', ' ') ?> G</span>
                    </div>
                </div>
                <button class="btn-commander" onclick="checkout()">
                    <i class="bi bi-lightning"></i>
                    Passer la commande
                </button>
                <button class="btn-vider" onclick="viderPanier()">
                    <i class="bi bi-cart-x"></i>
                    Vider le panier
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="<?= $baseUrl ?>/assets/js/panier.js"></script>