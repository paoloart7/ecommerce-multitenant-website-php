<?php 
$baseUrl = App::baseUrl(); 
$user = Session::user();

// Sécurisation des données
$tenant = $tenant ?? [];
$stats = $stats ?? [];
$commandesRecentes = $commandesRecentes ?? [];

$nomBoutique = $tenant['nomBoutique'] ?? $user['nomBoutique'] ?? 'Ma Boutique';
$statutBoutique = $tenant['statut'] ?? $user['statutBoutique'] ?? 'actif';
$prenom = $user['prenomUtilisateur'] ?? $user['prenom'] ?? 'Vendeur';
?>

<!-- Chargement des CSS -->
<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/tenant-sidebar.css">
<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/tenant-dashboard.css">

<div class="tenant-layout">
    
    <!-- 1. SIDEBAR -->
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <!-- 2. MAIN CONTENT -->
    <div class="tenant-main">
        
        <!-- TOPBAR -->
        <?php require __DIR__ . '/partials/topbar.php'; ?>

        <!-- CONTENU -->
        <div class="tenant-content container-fluid p-4">
            
            <!-- EN-TÊTE -->
            <div class="d-flex justify-content-between align-items-center mb-5 mt-2">
                <div>
                    <h2 class="fw-bold h4 m-0">Bienvenue, <?= Security::escape($prenom) ?> 👋</h2>
                    <p class="text-muted small mb-0">Voici les performances de <strong><?= Security::escape($nomBoutique) ?></strong></p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= $baseUrl ?>/vendeur/produit/ajouter" class="btn btn-primary rounded-3 fw-bold shadow-sm px-4">
                        <i class="bi bi-plus-lg me-2"></i> Nouveau produit
                    </a>
                </div>
            </div>

            <!-- STATS CARDS -->
            <div class="row g-4 mb-5">
                <!-- Ventes -->
                <div class="col-md-3">
                    <div class="card-stat">
                        <span class="label">Ventes (HTG)</span>
                        <div class="d-flex align-items-end justify-content-between mt-2">
                            <h2 class="value counter currency"><?= number_format((float)($stats['ca'] ?? 0), 0, ',', ' ') ?></h2>
                            <span class="trend <?= ($stats['croissance'] ?? 0) >= 0 ? 'up' : 'down' ?>">
                                <i class="bi bi-graph-up-arrow me-1"></i> 
                                <?= ($stats['croissance'] ?? 0) > 0 ? '+' : '' ?><?= $stats['croissance'] ?? 0 ?>%
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Commandes -->
                <div class="col-md-3">
                    <div class="card-stat">
                        <span class="label">Commandes</span>
                        <div class="d-flex align-items-end justify-content-between mt-2">
                            <h2 class="value counter"><?= (int)($stats['commandes'] ?? 0) ?></h2>
                        </div>
                    </div>
                </div>

                <!-- Produits -->
                <div class="col-md-3">
                    <div class="card-stat">
                        <span class="label">Produits actifs</span>
                        <div class="d-flex align-items-end justify-content-between mt-2">
                            <h2 class="value counter"><?= (int)($stats['produits'] ?? 0) ?></h2>
                        </div>
                    </div>
                </div>

                <!-- Statut -->
                <div class="col-md-3">
                    <div class="card-stat highlight text-white">
                        <span class="label text-white-50">Statut Boutique</span>
                        <div class="d-flex align-items-end justify-content-between mt-2">
                            <h2 class="h4 fw-bold m-0 text-uppercase"><?= Security::escape($statutBoutique) ?></h2>
                            <i class="bi bi-shield-check fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABLEAU DES COMMANDES RÉCENTES -->
            <div class="row">
                <div class="col-12">
                    <div class="tenant-card p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold m-0">Commandes récentes</h5>
                            <a href="<?= $baseUrl ?>/vendeur/commandes" class="text-decoration-none small">Tout voir</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="small text-muted text-uppercase bg-light">
                                    <tr>
                                        <th class="ps-3">N° Commande</th>
                                        <th>Date</th>
                                        <th>Client</th>
                                        <th>Montant</th>
                                        <th class="text-end pe-3">Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($commandesRecentes)): ?>
                                        <?php foreach ($commandesRecentes as $cmd): ?>
                                        <tr>
                                            <td class="ps-3 fw-bold">#<?= Security::escape($cmd['numeroCommande'] ?? 'N/A') ?></td>
                                            <td><?= date('d/m/Y', strtotime($cmd['dateCommande'] ?? '')) ?></td>
                                            <td><?= Security::escape($cmd['nomClient'] ?? 'Client') ?></td>
                                            <td class="fw-bold text-success"><?= number_format($cmd['total'] ?? 0, 0, ',', ' ') ?> G</td>
                                            <td class="text-end pe-3">
                                                <?php 
                                                $badgeClass = match($cmd['statut'] ?? '') {
                                                    'payee', 'payé', 'payée' => 'success',
                                                    'en_attente' => 'warning',
                                                    'livree', 'livrée' => 'info',
                                                    'annulee', 'annulée' => 'danger',
                                                    default => 'secondary'
                                                };
                                                $badgeText = match($cmd['statut'] ?? '') {
                                                    'payee', 'payé', 'payée' => 'Payée',
                                                    'en_attente' => 'En attente',
                                                    'livree', 'livrée' => 'Livrée',
                                                    'annulee', 'annulée' => 'Annulée',
                                                    default => $cmd['statut'] ?? 'Inconnu'
                                                };
                                                ?>
                                                <span class="badge bg-<?= $badgeClass ?>"><?= $badgeText ?></span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted small">
                                            <i class="bi bi-inbox display-4 d-block mb-2 opacity-25"></i>
                                            Aucune commande pour le moment.
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION PRODUITS POPULAIRES ET ACTIVITÉ RÉCENTE -->
            <div class="row g-4">
                <!-- Produits les plus vendus -->
                <div class="col-md-6">
                    <div class="tenant-card p-4">
                        <h5 class="fw-bold mb-3">🏆 Produits les plus vendus</h5>
                        <?php if (!empty($topProduits)): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($topProduits as $produit): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 border-0 border-bottom">
                                    <div>
                                        <div class="fw-semibold"><?= Security::escape($produit['nomProduit'] ?? 'Produit') ?></div>
                                        <small class="text-muted">
                                            <i class="bi bi-cart-check"></i> 
                                            <?= (int)($produit['ventes'] ?? 0) ?> vendus
                                        </small>
                                    </div>
                                    <span class="badge bg-primary-soft text-primary fs-6">
                                        <?= number_format($produit['chiffre'] ?? 0, 0, ',', ' ') ?> G
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-bar-chart display-4 text-muted"></i>
                                <p class="text-muted mt-2">Aucune vente pour le moment</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Activité récente -->
                <div class="col-md-6">
                    <div class="tenant-card p-4">
                        <h5 class="fw-bold mb-3">⏱️ Activité récente</h5>
                        <?php if (!empty($activiteRecente)): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($activiteRecente as $act): ?>
                                <div class="list-group-item px-0 border-0 border-bottom">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="fw-semibold">#<?= Security::escape($act['numeroCommande'] ?? '') ?></span>
                                            <span class="text-muted small ms-2">
                                                <i class="bi bi-clock"></i> 
                                                <?= date('d/m/Y H:i', strtotime($act['dateCommande'] ?? '')) ?>
                                            </span>
                                        </div>
                                        <?php
                                        $badgeClass = match($act['statut'] ?? '') {
                                            'livree' => 'success',
                                            'en_attente' => 'warning',
                                            'confirmee', 'payee' => 'info',
                                            'annulee' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?= $badgeClass ?>">
                                            <?= str_replace('_', ' ', $act['statut'] ?? '') ?>
                                        </span>
                                    </div>
                                    <div class="small text-muted mt-1">
                                        <i class="bi bi-person"></i> <?= Security::escape($act['client'] ?? '') ?> - 
                                        <span class="fw-semibold text-success"><?= number_format($act['total'] ?? 0, 0, ',', ' ') ?> G</span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-3 text-end">
                                <a href="<?= $baseUrl ?>/vendeur/commandes" class="small text-decoration-none">
                                    Voir toutes les commandes <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-clock-history display-4 text-muted"></i>
                                <p class="text-muted mt-2">Aucune activité récente</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- JS -->
<script src="<?= $baseUrl ?>/assets/js/tenant-dashboard.js"></script>