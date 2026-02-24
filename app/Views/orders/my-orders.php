<?php 
$baseUrl = App::baseUrl();
$user = Session::user();

// Récupération des données
$orders = $orders ?? [];
$pagination = $pagination ?? ['pages' => 1, 'current' => 1];
$filters = $filters ?? [];
$stats = $stats ?? ['total' => 0, 'en_attente' => 0, 'payees' => 0, 'livrees' => 0];
?>

<!-- Chargement des CSS -->
<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/my-orders.css">

<div class="tenant-layout">
    
    <!-- SIDEBAR -->
    <?php require dirname(__DIR__) . '/tenant/partials/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="tenant-main">
        
        <!-- TOPBAR -->
        <?php require dirname(__DIR__) . '/tenant/partials/topbar.php'; ?>

        <!-- CONTENU -->
        <div class="tenant-content container-fluid p-4">

            <!-- EN-TÊTE DE PAGE -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold h4 m-0">📦 Mes Commandes</h2>
                    <p class="text-muted small mb-0">Gérez et suivez toutes vos commandes</p>
                </div>
                <div>
                    <span class="badge bg-primary-soft text-primary px-3 py-2">
                        <i class="bi bi-truck me-1"></i> <?= $stats['total'] ?> commandes
                    </span>
                </div>
            </div>

            <!-- STATS CARDS -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning-soft text-warning">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">En attente</span>
                            <span class="stat-value"><?= $stats['en_attente'] ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-success-soft text-success">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Payées</span>
                            <span class="stat-value"><?= $stats['payees'] ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-info-soft text-info">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Livrées</span>
                            <span class="stat-value"><?= $stats['livrees'] ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary-soft text-primary">
                            <i class="bi bi-cart-check"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Total</span>
                            <span class="stat-value"><?= $stats['total'] ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FILTRES -->
            <div class="filters-card mb-4">
                <form method="GET" action="<?= $baseUrl ?>/vendeur/mes-commandes" class="filters-form">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Statut</label>
                            <select name="statut" class="form-select form-select-sm">
                                <option value="">Tous les statuts</option>
                                <option value="en_attente" <?= ($filters['statut'] ?? '') == 'en_attente' ? 'selected' : '' ?>>En attente</option>
                                <option value="confirmee" <?= ($filters['statut'] ?? '') == 'confirmee' ? 'selected' : '' ?>>Confirmée</option>
                                <option value="payee" <?= ($filters['statut'] ?? '') == 'payee' ? 'selected' : '' ?>>Payée</option>
                                <option value="en_preparation" <?= ($filters['statut'] ?? '') == 'en_preparation' ? 'selected' : '' ?>>En préparation</option>
                                <option value="expediee" <?= ($filters['statut'] ?? '') == 'expediee' ? 'selected' : '' ?>>Expédiée</option>
                                <option value="livree" <?= ($filters['statut'] ?? '') == 'livree' ? 'selected' : '' ?>>Livrée</option>
                                <option value="annulee" <?= ($filters['statut'] ?? '') == 'annulee' ? 'selected' : '' ?>>Annulée</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">Du</label>
                            <input type="date" name="date_debut" class="form-control form-control-sm" value="<?= $filters['date_debut'] ?? '' ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">Au</label>
                            <input type="date" name="date_fin" class="form-control form-control-sm" value="<?= $filters['date_fin'] ?? '' ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Client</label>
                            <input type="text" name="client" class="form-control form-control-sm" placeholder="Nom ou email" value="<?= $filters['client'] ?? '' ?>">
                        </div>
                        <div class="col-md-2">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="bi bi-filter"></i> Filtrer
                                </button>
                                <a href="<?= $baseUrl ?>/vendeur/mes-commandes" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-x"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- TABLEAU DES COMMANDES -->
            <div class="orders-table-card">
                <div class="table-responsive">
                    <table class="table orders-table">
                        <thead>
                            <tr>
                                <th>N° Commande</th>
                                <th>Date</th>
                                <th>Client</th>
                                <th>Articles</th>
                                <th>Total</th>
                                <th>Statut</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($orders)): ?>
                                <?php foreach ($orders as $order): ?>
                                <tr class="order-row">
                                    <td class="fw-bold">#<?= Security::escape($order['numeroCommande'] ?? 'N/A') ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($order['dateCommande'] ?? '')) ?></td>
                                    <td>
                                        <div class="client-info">
                                            <span class="client-name"><?= Security::escape($order['nomClient'] ?? 'Client') ?></span>
                                            <?php if (!empty($order['emailClient'])): ?>
                                                <small class="client-email"><?= Security::escape($order['emailClient']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark"><?= (int)($order['nombreArticles'] ?? 0) ?></span>
                                    </td>
                                    <td class="fw-bold text-success">
                                        <?= number_format($order['total'] ?? 0, 0, ',', ' ') ?> <?= $order['devise'] ?? 'G' ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $badgeClass = match($order['statut'] ?? '') {
                                            'en_attente' => 'warning',
                                            'confirmee' => 'info',
                                            'payee' => 'success',
                                            'en_preparation' => 'primary',
                                            'expediee' => 'dark',
                                            'livree' => 'success',
                                            'annulee' => 'danger',
                                            'remboursee' => 'secondary',
                                            default => 'secondary'
                                        };
                                        $badgeText = match($order['statut'] ?? '') {
                                            'en_attente' => 'En attente',
                                            'confirmee' => 'Confirmée',
                                            'payee' => 'Payée',
                                            'en_preparation' => 'En préparation',
                                            'expediee' => 'Expédiée',
                                            'livree' => 'Livrée',
                                            'annulee' => 'Annulée',
                                            'remboursee' => 'Remboursée',
                                            default => $order['statut'] ?? 'Inconnu'
                                        };
                                        ?>
                                        <span class="badge bg-<?= $badgeClass ?>"><?= $badgeText ?></span>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= $baseUrl ?>/vendeur/commande-details?id=<?= $order['idCommande'] ?>" 
                                           class="btn btn-sm btn-outline-primary view-order-btn">
                                            <i class="bi bi-eye"></i> Détails
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="empty-state">
                                            <i class="bi bi-inbox display-1 text-muted"></i>
                                            <h5 class="mt-3">Aucune commande trouvée</h5>
                                            <p class="text-muted">Les commandes de vos clients apparaîtront ici</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION -->
                <?php if ($pagination['pages'] > 1): ?>
                <div class="pagination-wrapper">
                    <nav aria-label="Pagination">
                        <ul class="pagination justify-content-center">
                            <!-- Page précédente -->
                            <li class="page-item <?= $pagination['current'] <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $pagination['current'] - 1])) ?>" tabindex="-1">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            
                            <!-- Pages -->
                            <?php for ($i = 1; $i <= $pagination['pages']; $i++): ?>
                                <li class="page-item <?= $i == $pagination['current'] ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $i])) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <!-- Page suivante -->
                            <li class="page-item <?= $pagination['current'] >= $pagination['pages'] ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $pagination['current'] + 1])) ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <div class="pagination-info text-center text-muted small mt-2">
                        Affichage de <?= count($orders) ?> commandes sur <?= $pagination['total'] ?> au total
                    </div>
                </div>
                <?php endif; ?>
            </div>

        </div>

    </div> 
</div>
<script src="<?= $baseUrl ?>/assets/js/my-orders.js"></script>