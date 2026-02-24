<?php 
$baseUrl = App::baseUrl();
$user = Session::user();
$clients = $clients ?? [];
$pagination = $pagination ?? ['pages' => 1, 'current' => 1];
$filters = $filters ?? [];
$globalStats = $globalStats ?? [];
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/tenant-clients.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="tenant-layout">
    <?php require dirname(__DIR__) . '/partials/sidebar.php'; ?>
    
    <div class="tenant-main">
        <?php require dirname(__DIR__) . '/partials/topbar.php'; ?>

        <div class="tenant-content container-fluid p-4">
            
            <!-- Messages flash -->
            <?php if (isset($_SESSION['flash_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['flash_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?= $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- EN-TÊTE -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold h4 m-0">👥 Mes clients</h2>
                    <p class="text-muted small mb-0">Gérez votre relation client</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= $baseUrl ?>/vendeur/clients/export" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-download"></i> Exporter
                    </a>
                </div>
            </div>

            <!-- STATISTIQUES RAPIDES -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon bg-primary-soft text-primary">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="stats-info">
                            <span class="stats-label">Total clients</span>
                            <span class="stats-value"><?= $globalStats['total_clients'] ?? 0 ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon bg-success-soft text-success">
                            <i class="bi bi-person-check"></i>
                        </div>
                        <div class="stats-info">
                            <span class="stats-label">Clients actifs</span>
                            <span class="stats-value"><?= $globalStats['clients_actifs'] ?? 0 ?></span>
                            <span class="stats-text">ont passé commande</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon bg-info-soft text-info">
                            <i class="bi bi-calendar-plus"></i>
                        </div>
                        <div class="stats-info">
                            <span class="stats-label">Nouveaux (30j)</span>
                            <span class="stats-value"><?= $globalStats['nouveaux_30j'] ?? 0 ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon bg-warning-soft text-warning">
                            <i class="bi bi-cart"></i>
                        </div>
                        <div class="stats-info">
                            <span class="stats-label">Panier moyen</span>
                            <span class="stats-value"><?= number_format($globalStats['panier_moyen_global'] ?? 0, 0, ',', ' ') ?> G</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FILTRES -->
            <div class="filters-card mb-4">
                <form method="GET" action="<?= $baseUrl ?>/vendeur/clients" class="filters-form">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Recherche</label>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Nom, prénom, email..." 
                                   value="<?= $filters['search'] ?? '' ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Min commandes</label>
                            <input type="number" name="min_commandes" class="form-control" 
                                   placeholder="≥" min="0"
                                   value="<?= $filters['min_commandes'] ?? '' ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date début</label>
                            <input type="date" name="date_debut" class="form-control" 
                                   value="<?= $filters['date_debut'] ?? '' ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date fin</label>
                            <input type="date" name="date_fin" class="form-control" 
                                   value="<?= $filters['date_fin'] ?? '' ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-filter"></i> Filtrer
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- TABLEAU DES CLIENTS -->
            <div class="clients-card">
                <div class="table-responsive">
                    <table class="table clients-table">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Contact</th>
                                <th>Commandes</th>
                                <th>Total dépensé</th>
                                <th>Dernière commande</th>
                                <th>Panier moyen</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($clients)): ?>
                                <?php foreach ($clients as $client): ?>
                                <tr class="client-row">
                                    <td>
                                        <div class="client-info">
                                            <div class="client-avatar">
                                                <?php if (!empty($client['avatar'])): ?>
                                                    <img src="<?= $baseUrl . $client['avatar'] ?>" alt="">
                                                <?php else: ?>
                                                    <div class="avatar-initials">
                                                        <?= strtoupper(substr($client['prenomUtilisateur'] ?? '', 0, 1) . substr($client['nomUtilisateur'] ?? '', 0, 1)) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="client-name">
                                                <span class="fw-semibold">
                                                    <?= Security::escape($client['prenomUtilisateur'] ?? '') ?> <?= Security::escape($client['nomUtilisateur'] ?? '') ?>
                                                </span>
                                                <small class="text-muted d-block">
                                                    Client depuis <?= date('d/m/Y', strtotime($client['dateCreation'] ?? '')) ?>
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="contact-info">
                                            <div><i class="bi bi-envelope"></i> <?= Security::escape($client['emailUtilisateur'] ?? '') ?></div>
                                            <?php if (!empty($client['telephone'])): ?>
                                                <div><i class="bi bi-telephone"></i> <?= Security::escape($client['telephone']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-soft">
                                            <?= $client['nb_commandes'] ?? 0 ?>
                                            <?= ($client['commandes_encours'] ?? 0) > 0 ? " (dont {$client['commandes_encours']} en cours)" : '' ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold text-success">
                                        <?= number_format($client['total_depenses'] ?? 0, 0, ',', ' ') ?> G
                                    </td>
                                    <td>
                                        <?php if (!empty($client['derniere_commande'])): ?>
                                            <span class="text-muted small">
                                                <?= date('d/m/Y', strtotime($client['derniere_commande'])) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-soft">Jamais</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= number_format($client['panier_moyen'] ?? 0, 0, ',', ' ') ?> G
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= $baseUrl ?>/vendeur/client/details?id=<?= $client['idUtilisateur'] ?>" 
                                           class="btn btn-sm btn-outline-primary" title="Voir détails">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="empty-state">
                                            <i class="bi bi-people display-1 text-muted"></i>
                                            <h5 class="mt-3">Aucun client</h5>
                                            <p class="text-muted">Les clients apparaîtront quand ils passeront commande</p>
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
                    <nav>
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= $pagination['current'] <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $pagination['current'] - 1])) ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $pagination['pages']; $i++): ?>
                                <li class="page-item <?= $i == $pagination['current'] ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $i])) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?= $pagination['current'] >= $pagination['pages'] ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $pagination['current'] + 1])) ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <div class="text-center text-muted small mt-2">
                        Total: <?= $pagination['total'] ?> clients
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="<?= $baseUrl ?>/assets/js/tenant-clients.js"></script>