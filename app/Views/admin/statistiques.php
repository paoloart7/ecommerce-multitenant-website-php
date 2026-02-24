<?php
$baseUrl = App::baseUrl();
$globalStats = $globalStats ?? [];
$inscriptions = $inscriptions ?? [];
$commandesStatut = $commandesStatut ?? [];
$topBoutiques = $topBoutiques ?? [];
$topProduits = $topProduits ?? [];
$caMensuel = $caMensuel ?? [];
$nouveauxUtilisateurs = $nouveauxUtilisateurs ?? [];
$roles = $roles ?? [];
$paiements = $paiements ?? [];
$conversion = $conversion ?? [];
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/admin-statistiques.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="admin-layout">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    
    <div class="admin-main">
        <?php require __DIR__ . '/partials/topbar.php'; ?>
        
        <div class="stats-container">
            
            <!-- En-tête -->
            <div class="stats-header">
                <div>
                    <h1 class="stats-title">📊 Statistiques</h1>
                    <p class="stats-subtitle">Analyse détaillée de la plateforme</p>
                </div>
                <div class="stats-period">
                    <span class="stats-badge">Mise à jour en temps réel</span>
                </div>
            </div>

            <!-- Cartes KPIs -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon bg-primary-soft">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">Utilisateurs</span>
                        <span class="stat-value"><?= number_format($globalStats['total_users'] ?? 0) ?></span>
                        <div class="stat-detail">
                            <span class="text-success"><?= $globalStats['total_clients'] ?? 0 ?> clients</span>
                            <span class="mx-1">•</span>
                            <span class="text-info"><?= $globalStats['total_tenants'] ?? 0 ?> vendeurs</span>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-success-soft">
                        <i class="bi bi-shop"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">Boutiques</span>
                        <span class="stat-value"><?= number_format($globalStats['total_boutiques'] ?? 0) ?></span>
                        <div class="stat-detail">
                            <span class="text-success"><?= $globalStats['boutiques_actives'] ?? 0 ?> actives</span>
                            <span class="mx-1">•</span>
                            <span class="text-warning"><?= $globalStats['boutiques_attente'] ?? 0 ?> en attente</span>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-warning-soft">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">Produits</span>
                        <span class="stat-value"><?= number_format($globalStats['total_produits'] ?? 0) ?></span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-info-soft">
                        <i class="bi bi-credit-card"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">Chiffre d'affaires</span>
                        <span class="stat-value"><?= number_format($globalStats['ca_total'] ?? 0, 0, ',', ' ') ?> G</span>
                        <div class="stat-detail">
                            <span><?= number_format($globalStats['total_commandes'] ?? 0) ?> commandes</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Graphiques ligne 1 -->
            <div class="charts-row">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Évolution des inscriptions (30 jours)</h3>
                    </div>
                    <div class="chart-body">
                        <canvas id="inscriptionsChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Chiffre d'affaires mensuel</h3>
                    </div>
                    <div class="chart-body">
                        <canvas id="caChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Graphiques ligne 2 -->
            <div class="charts-row">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Répartition des commandes</h3>
                    </div>
                    <div class="chart-body">
                        <canvas id="commandesChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Nouveaux utilisateurs par mois</h3>
                    </div>
                    <div class="chart-body">
                        <canvas id="usersChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Top boutiques et produits -->
            <div class="tables-row">
                <div class="table-card">
                    <div class="table-header">
                        <h3><i class="bi bi-trophy"></i> Top 10 boutiques par CA</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th>Boutique</th>
                                    <th>Commandes</th>
                                    <th>CA</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topBoutiques as $b): ?>
                                <tr>
                                    <td class="shop-cell">
                                        <div class="shop-info">
                                            <?php if (!empty($b['logo'])): ?>
                                                <img src="<?= $baseUrl . $b['logo'] ?>" class="shop-logo-mini">
                                            <?php else: ?>
                                                <div class="shop-logo-placeholder"><?= strtoupper(substr($b['nomBoutique'], 0, 1)) ?></div>
                                            <?php endif; ?>
                                            <span><?= Security::escape($b['nomBoutique']) ?></span>
                                        </div>
                                    </td>
                                    <td><?= number_format($b['nb_commandes'] ?? 0) ?></td>
                                    <td class="text-success"><?= number_format($b['ca'] ?? 0, 0, ',', ' ') ?> G</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="table-card">
                    <div class="table-header">
                        <h3><i class="bi bi-star"></i> Top 10 produits</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th>Boutique</th>
                                    <th>Ventes</th>
                                    <th>CA</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topProduits as $p): ?>
                                <tr>
                                    <td><?= Security::escape($p['nomProduit']) ?></td>
                                    <td><?= Security::escape($p['nomBoutique']) ?></td>
                                    <td><?= number_format($p['quantite_vendue'] ?? 0) ?></td>
                                    <td class="text-success"><?= number_format($p['chiffre'] ?? 0, 0, ',', ' ') ?> G</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Stats complémentaires -->
            <div class="stats-footer-grid">
                <div class="info-card">
                    <h4>Répartition des rôles</h4>
                    <div class="roles-list">
                        <?php foreach ($roles as $role): ?>
                        <div class="role-item">
                            <span class="role-name"><?= ucfirst($role['role']) ?></span>
                            <span class="role-count"><?= number_format($role['total']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="info-card">
                    <h4>Paiements par mode</h4>
                    <div class="payment-list">
                        <?php foreach ($paiements as $p): ?>
                        <div class="payment-item">
                            <span class="payment-mode"><?= ucfirst($p['modePaiement']) ?></span>
                            <span class="payment-count"><?= number_format($p['total']) ?> opérations</span>
                            <span class="payment-amount"><?= number_format($p['montant_total'], 0, ',', ' ') ?> G</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="info-card">
                    <h4>Conversion</h4>
                    <div class="conversion-stats">
                        <div class="conversion-item">
                            <span class="conversion-label">Paniers créés</span>
                            <span class="conversion-value"><?= number_format($conversion['paniers'] ?? 0) ?></span>
                        </div>
                        <div class="conversion-item">
                            <span class="conversion-label">Commandes validées</span>
                            <span class="conversion-value"><?= number_format($conversion['commandes'] ?? 0) ?></span>
                        </div>
                        <div class="conversion-rate">
                            <span class="rate-label">Taux de conversion</span>
                            <span class="rate-value"><?= $conversion['taux'] ?? 0 ?>%</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Graphique inscriptions
    const ctx1 = document.getElementById('inscriptionsChart').getContext('2d');
    new Chart(ctx1, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($inscriptions, 'date')) ?>,
            datasets: [{
                label: 'Inscriptions',
                data: <?= json_encode(array_column($inscriptions, 'total')) ?>,
                borderColor: '#04BF9D',
                backgroundColor: 'rgba(4, 191, 157, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } }
        }
    });

    // Graphique CA mensuel
    const ctx2 = document.getElementById('caChart').getContext('2d');
    new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_map(function($m) { 
                return date('F', mktime(0, 0, 0, $m['mois'], 1)); 
            }, $caMensuel)) ?>,
            datasets: [{
                label: 'Chiffre d\'affaires (G)',
                data: <?= json_encode(array_column($caMensuel, 'ca')) ?>,
                backgroundColor: '#04BF9D',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } }
        }
    });

    // Graphique commandes
    const ctx3 = document.getElementById('commandesChart').getContext('2d');
    new Chart(ctx3, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($commandesStatut, 'statut')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($commandesStatut, 'total')) ?>,
                backgroundColor: ['#10b981', '#f59e0b', '#3b82f6', '#ef4444', '#94a3b8']
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // Graphique nouveaux utilisateurs
    const ctx4 = document.getElementById('usersChart').getContext('2d');
    new Chart(ctx4, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_map(function($m) { 
                return date('F', mktime(0, 0, 0, $m['mois'], 1)); 
            }, $nouveauxUtilisateurs)) ?>,
            datasets: [{
                label: 'Nouveaux utilisateurs',
                data: <?= json_encode(array_column($nouveauxUtilisateurs, 'total')) ?>,
                backgroundColor: '#6366f1',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } }
        }
    });
});
</script>