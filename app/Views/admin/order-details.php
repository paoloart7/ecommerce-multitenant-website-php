<?php
$baseUrl = App::baseUrl();
$commande = $commande ?? [];
$items = $items ?? [];
$payments = $payments ?? [];
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/admin-order-detail.css">

<div class="ad-order-layout">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    
    <div class="ad-order-main">
        <?php require __DIR__ . '/partials/topbar.php'; ?>
        
        <div class="ad-order-content">
            
            <!-- En-tête premium -->
            <div class="ad-order-header">
                <div class="ad-order-title-group">
                    <h1 class="ad-order-title">
                        Détail commande <span class="ad-order-number">#<?= Security::escape($commande['numeroCommande'] ?? '') ?></span>
                    </h1>
                    <div class="ad-order-meta">
                        <span class="ad-order-meta-item">
                            <i class="bi bi-calendar3"></i> <?= date('d/m/Y', strtotime($commande['dateCommande'] ?? '')) ?>
                        </span>
                        <span class="ad-order-meta-item">
                            <i class="bi bi-clock"></i> <?= date('H:i', strtotime($commande['dateCommande'] ?? '')) ?>
                        </span>
                    </div>
                </div>
                <a href="<?= $baseUrl ?>/admin/orders" class="ad-order-back-btn">
                    <i class="bi bi-arrow-left"></i>
                    <span>Retour</span>
                </a>
            </div>
            
            <!-- Grille d'informations -->
            <div class="ad-order-grid">
                
                <!-- Carte Client -->
                <div class="ad-order-card">
                    <div class="ad-order-card-header">
                        <div class="ad-order-card-icon">
                            <i class="bi bi-person-circle"></i>
                        </div>
                        <h3 class="ad-order-card-title">Client</h3>
                    </div>
                    <div class="ad-order-card-body">
                        <div class="ad-order-info-row">
                            <span class="ad-order-info-label">Nom complet</span>
                            <span class="ad-order-info-value">
                                <?= Security::escape($commande['prenomUtilisateur'] ?? '') ?> <?= Security::escape($commande['nomUtilisateur'] ?? '') ?>
                            </span>
                        </div>
                        <div class="ad-order-info-row">
                            <span class="ad-order-info-label">Email</span>
                            <span class="ad-order-info-value">
                                <i class="bi bi-envelope-at"></i> <?= Security::escape($commande['emailUtilisateur'] ?? '') ?>
                            </span>
                        </div>
                        <div class="ad-order-info-row">
                            <span class="ad-order-info-label">Téléphone</span>
                            <span class="ad-order-info-value">
                                <i class="bi bi-phone"></i> <?= Security::escape($commande['telephone'] ?? 'N/A') ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Carte Boutique -->
                <div class="ad-order-card">
                    <div class="ad-order-card-header">
                        <div class="ad-order-card-icon">
                            <i class="bi bi-shop"></i>
                        </div>
                        <h3 class="ad-order-card-title">Boutique</h3>
                    </div>
                    <div class="ad-order-card-body">
                        <div class="ad-order-info-row">
                            <span class="ad-order-info-label">Nom</span>
                            <span class="ad-order-info-value"><?= Security::escape($commande['nomBoutique'] ?? '') ?></span>
                        </div>
                        <div class="ad-order-info-row">
                            <span class="ad-order-info-label">Statut</span>
                            <span class="ad-order-info-value">
                                <span class="ad-order-badge ad-order-badge-<?= $commande['statut'] ?? 'en_attente' ?>">
                                    <span class="ad-order-badge-dot"></span>
                                    <?= str_replace('_', ' ', $commande['statut'] ?? 'en_attente') ?>
                                </span>
                            </span>
                        </div>
                        <div class="ad-order-info-row">
                            <span class="ad-order-info-label">Total</span>
                            <span class="ad-order-info-value ad-order-price">
                                <?= number_format($commande['total'] ?? 0, 0, ',', ' ') ?> G
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Carte Résumé -->
                <div class="ad-order-card ad-order-card-summary">
                    <div class="ad-order-card-header">
                        <div class="ad-order-card-icon">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <h3 class="ad-order-card-title">Résumé</h3>
                    </div>
                    <div class="ad-order-card-body">
                        <div class="ad-order-summary-stats">
                            <div class="ad-order-stat">
                                <span class="ad-order-stat-value"><?= count($items) ?></span>
                                <span class="ad-order-stat-label">Articles</span>
                            </div>
                            <div class="ad-order-stat">
                                <span class="ad-order-stat-value"><?= $payments ? count($payments) : 0 ?></span>
                                <span class="ad-order-stat-label">Paiements</span>
                            </div>
                            <div class="ad-order-stat">
                                <span class="ad-order-stat-value"><?= number_format($commande['sousTotal'] ?? 0, 0, ',', ' ') ?></span>
                                <span class="ad-order-stat-label">Sous-total</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Section Produits -->
            <div class="ad-order-section">
                <div class="ad-order-section-header">
                    <div class="ad-order-section-icon">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <h2 class="ad-order-section-title">Produits commandés</h2>
                </div>
                
                <div class="ad-order-table-wrapper">
                    <table class="ad-order-table">
                        <thead class="ad-order-table-head">
                            <tr>
                                <th>Produit</th>
                                <th>Quantité</th>
                                <th>Prix unitaire</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody class="ad-order-table-body">
                            <?php foreach ($items as $item): ?>
                            <tr class="ad-order-table-row">
                                <td class="ad-order-product-name"><?= Security::escape($item['nomProduitSnapshot'] ?? '') ?></td>
                                <td class="ad-order-product-qty"><?= $item['quantite'] ?? 0 ?></td>
                                <td class="ad-order-product-price"><?= number_format($item['prixUnitaire'] ?? 0, 0, ',', ' ') ?> G</td>
                                <td class="ad-order-product-total text-end"><?= number_format($item['totalLigne'] ?? 0, 0, ',', ' ') ?> G</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="ad-order-table-foot">
                            <tr>
                                <td colspan="3" class="text-end ad-order-total-label">Total</td>
                                <td class="text-end ad-order-total-value"><?= number_format($commande['total'] ?? 0, 0, ',', ' ') ?> G</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            
            <!-- Section Paiements -->
            <?php if (!empty($payments)): ?>
            <div class="ad-order-section">
                <div class="ad-order-section-header">
                    <div class="ad-order-section-icon">
                        <i class="bi bi-credit-card"></i>
                    </div>
                    <h2 class="ad-order-section-title">Historique des paiements</h2>
                </div>
                
                <div class="ad-order-table-wrapper">
                    <table class="ad-order-table">
                        <thead class="ad-order-table-head">
                            <tr>
                                <th>Mode</th>
                                <th>Montant</th>
                                <th>Date</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody class="ad-order-table-body">
                            <?php foreach ($payments as $p): ?>
                            <tr class="ad-order-table-row">
                                <td><?= ucfirst($p['modePaiement'] ?? '') ?></td>
                                <td class="ad-order-product-price"><?= number_format($p['montant'] ?? 0, 0, ',', ' ') ?> G</td>
                                <td><?= date('d/m/Y H:i', strtotime($p['datePaiement'] ?? '')) ?></td>
                                <td>
                                    <span class="ad-order-badge ad-order-badge-<?= $p['statutPaiement'] ?? 'en_attente' ?>">
                                        <span class="ad-order-badge-dot"></span>
                                        <?= $p['statutPaiement'] ?? '' ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
    </div>
</div>