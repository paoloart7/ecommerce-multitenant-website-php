<?php 
$baseUrl = App::baseUrl();
$boutiques = $boutiques ?? [];
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/boutique.css">

<div class="boutiques-container">
    <!-- En-tête -->
    <div class="boutiques-header">
        <h1>Nos boutiques</h1>
        <p>Découvrez tous nos vendeurs et leurs produits</p>
    </div>

    <?php if (empty($boutiques)): ?>
        <div class="empty-state">
            <i class="bi bi-shop"></i>
            <h3>Aucune boutique pour le moment</h3>
            <p>Revenez plus tard, de nouvelles boutiques ouvrent bientôt !</p>
        </div>
    <?php else: ?>
        <div class="boutiques-grid">
            <?php foreach ($boutiques as $b): ?>
                <a href="<?= $baseUrl ?>/boutique?slug=<?= $b['slugBoutique'] ?>" class="boutique-card">
                    <div class="boutique-logo">
                        <?php if (!empty($b['logo'])): ?>
                            <img src="<?= $baseUrl . $b['logo'] ?>" alt="<?= Security::escape($b['nomBoutique']) ?>">
                        <?php else: ?>
                            <div class="logo-placeholder">
                                <i class="bi bi-shop"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="boutique-info">
                        <h3 class="boutique-nom"><?= Security::escape($b['nomBoutique']) ?></h3>
                        
                        <?php if (!empty($b['descriptionBoutique'])): ?>
                            <p class="boutique-description">
                                <?= Security::escape(substr($b['descriptionBoutique'], 0, 100)) ?>...
                            </p>
                        <?php elseif (!empty($b['description'])): ?>
                            <p class="boutique-description">
                                <?= Security::escape(substr($b['description'], 0, 100)) ?>...
                            </p>
                        <?php endif; ?>
                        <div class="boutique-stats">
                            <span class="stat">
                                <i class="bi bi-box-seam"></i>
                                <?= $b['nb_produits'] ?? 0 ?> produits
                            </span>
                            <span class="stat">
                                <i class="bi bi-cart-check"></i>
                                <?= $b['nb_commandes'] ?? 0 ?> ventes
                            </span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>