<?php 
$baseUrl = App::baseUrl();
$user = Session::user();
$commandes = $commandes ?? [];
$stats = $stats ?? [];
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/client-commandes.css">

<div class="commandes-container">
    <!-- En-tête -->
    <div class="page-header">
        <h1>📦 Mes commandes</h1>
        <a href="<?= $baseUrl ?>/" class="btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Retour à l'accueil
        </a>
    </div>

    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-label">Total commandes</span>
            <span class="stat-value"><?= $stats['total'] ?? 0 ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-label">En cours</span>
            <span class="stat-value"><?= $stats['en_cours'] ?? 0 ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Livrées</span>
            <span class="stat-value"><?= $stats['livrees'] ?? 0 ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Total dépensé</span>
            <span class="stat-value total"><?= number_format($stats['total_depense'] ?? 0, 0, ',', ' ') ?> G</span>
        </div>
    </div>

    <!-- Liste des commandes -->
    <?php if (empty($commandes)): ?>
        <div class="empty-state">
            <i class="bi bi-bag-x"></i>
            <h3>Aucune commande pour le moment</h3>
            <p>Découvrez nos boutiques et faites vos premiers achats !</p>
            <a href="<?= $baseUrl ?>/boutiques" class="btn-primary">
                <i class="bi bi-shop"></i> Explorer les boutiques
            </a>
        </div>
    <?php else: ?>
        <div class="commandes-list">
            <?php foreach ($commandes as $cmd): ?>
                <a href="<?= $baseUrl ?>/commande/details?id=<?= $cmd['idCommande'] ?>" class="commande-item">
                    <div class="commande-icon">
                        <i class="bi bi-receipt"></i>
                    </div>
                    
                    <div class="commande-info">
                        <div class="commande-header">
                            <span class="commande-numero">#<?= Security::escape($cmd['numeroCommande'] ?? '') ?></span>
                            <span class="commande-badge badge-<?= $cmd['statut'] ?? '' ?>">
                                <?= str_replace('_', ' ', $cmd['statut'] ?? '') ?>
                            </span>
                        </div>
                        
                        <div class="commande-details">
                            <span><i class="bi bi-calendar"></i> <?= date('d/m/Y', strtotime($cmd['dateCommande'] ?? '')) ?></span>
                            <span><i class="bi bi-shop"></i> <?= Security::escape($cmd['nomBoutique'] ?? '') ?></span>
                            <span><i class="bi bi-box"></i> <?= $cmd['nb_articles'] ?? 0 ?> article(s)</span>
                        </div>
                    </div>
                    
                    <div class="commande-total">
                        <?= number_format($cmd['total'] ?? 0, 0, ',', ' ') ?> G
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>