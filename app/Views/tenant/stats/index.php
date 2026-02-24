<?php 
$baseUrl = App::baseUrl();
$user = Session::user();
$stats = $stats ?? [];
$global = $stats['global'] ?? [];
$evolution = $stats['evolution'] ?? [];
$topProduits = $stats['topProduits'] ?? [];
$topClients = $stats['topClients'] ?? [];
$commandesParStatut = $stats['commandesParStatut'] ?? [];
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/stats.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="tenant-layout">
    <?php require dirname(__DIR__) . '/partials/sidebar.php'; ?>
    
    <div class="tenant-main">
        <?php require dirname(__DIR__) . '/partials/topbar.php'; ?>

        <div class="tenant-content container-fluid p-4">
            
            <!-- EN-TÊTE -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold h4 m-0">📊 Statistiques</h2>
                    <p class="text-muted small mb-0">Analysez les performances de votre boutique</p>
                </div>
                <div class="d-flex gap-2">
                    <select class="form-select form-select-sm" style="width: auto;" id="periodeSelect">
                        <option value="7">7 derniers jours</option>
                        <option value="30" selected>30 derniers jours</option>
                        <option value="90">90 derniers jours</option>
                        <option value="365">Cette année</option>
                    </select>
                    <a href="<?= $baseUrl ?>/vendeur/rapports" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-download"></i> Rapport
                    </a>
                </div>
            </div>

            <!-- KPIS CARTES -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon bg-primary-soft text-primary">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div class="stats-info">
                            <span class="stats-label">Chiffre d'affaires</span>
                            <span class="stats-value"><?= $global['ca_format'] ?? '0 G' ?></span>
                            <span class="stats-trend up">+<?= rand(5, 20) ?>%</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon bg-success-soft text-success">
                            <i class="bi bi-bag-check"></i>
                        </div>
                        <div class="stats-info">
                            <span class="stats-label">Commandes</span>
                            <span class="stats-value"><?= $global['commandes']['total'] ?? 0 ?></span>
                            <span class="stats-text"><?= $global['commandes']['aujourdhui'] ?? 0 ?> aujourd'hui</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon bg-info-soft text-info">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="stats-info">
                            <span class="stats-label">Clients</span>
                            <span class="stats-value"><?= $global['clients']['total'] ?? 0 ?></span>
                            <span class="stats-text"><?= $global['clients']['actifs'] ?? 0 ?> actifs</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon bg-warning-soft text-warning">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div class="stats-info">
                            <span class="stats-label">Panier moyen</span>
                            <span class="stats-value"><?= number_format($global['panier_moyen'] ?? 0, 0, ',', ' ') ?> G</span>
                            <span class="stats-text"><?= $global['produits']['actifs'] ?? 0 ?> produits actifs</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- GRAPHIQUE ÉVOLUTION -->
            <div class="row g-3 mb-4">
                <div class="col-md-8">
                    <div class="chart-card">
                        <div class="card-header">
                            <h5>Évolution des ventes</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="evolutionChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="chart-card">
                        <div class="card-header">
                            <h5>Répartition des commandes</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="statutChart" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TOP PRODUITS ET CLIENTS -->
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="stats-table-card">
                        <div class="card-header">
                            <h5>🏆 Top 10 produits</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Produit</th>
                                        <th>Ventes</th>
                                        <th>CA</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topProduits as $p): ?>
                                    <tr>
                                        <td><?= Security::escape($p['nomProduit'] ?? '') ?></td>
                                        <td><span class="badge bg-primary-soft"><?= $p['quantite_vendue'] ?? 0 ?></span></td>
                                        <td class="fw-bold text-success"><?= number_format($p['chiffre_affaires'] ?? 0, 0, ',', ' ') ?> G</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stats-table-card">
                        <div class="card-header">
                            <h5>👥 Top 10 clients</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Client</th>
                                        <th>Commandes</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topClients as $c): ?>
                                    <tr>
                                        <td>
                                            <?= Security::escape($c['prenomUtilisateur'] ?? '') ?> <?= Security::escape($c['nomUtilisateur'] ?? '') ?>
                                            <small class="d-block text-muted"><?= Security::escape($c['emailUtilisateur'] ?? '') ?></small>
                                        </td>
                                        <td><?= $c['nb_commandes'] ?? 0 ?></td>
                                        <td class="fw-bold text-success"><?= number_format($c['total_depenses'] ?? 0, 0, ',', ' ') ?> G</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Graphique d'évolution
    const ctx1 = document.getElementById('evolutionChart').getContext('2d');
    const evolutionData = <?= json_encode($evolution) ?>;
    
    new Chart(ctx1, {
        type: 'line',
        data: {
            labels: evolutionData.map(d => d.date),
            datasets: [{
                label: 'Chiffre d\'affaires (G)',
                data: evolutionData.map(d => d.ca),
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            }
        }
    });

    // Graphique des statuts
    const ctx2 = document.getElementById('statutChart').getContext('2d');
    const statutData = <?= json_encode($commandesParStatut) ?>;
    
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: statutData.map(s => s.statut),
            datasets: [{
                data: statutData.map(s => s.total),
                backgroundColor: ['#10b981', '#f59e0b', '#3b82f6', '#ef4444', '#6b7280']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
});
</script>