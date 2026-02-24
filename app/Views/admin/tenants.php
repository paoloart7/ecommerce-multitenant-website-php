<?php
$baseUrl = App::baseUrl();
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/tenants.css">

<div class="admin-layout">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <div class="admin-main">
        <?php require __DIR__ . '/partials/topbar.php'; ?>

        <div class="admin-content container-fluid py-4">
            
            <!-- EN-TÊTE -->
            <div class="page-header">
                <div>
                    <h1>Boutiques</h1>
                    <p>Plateforme Multi-tenant &bull; Vue d'ensemble</p>
                </div>
            </div>

            <!-- STATS GRID -->
            <div class="stats-grid">
                <!-- Total -->
                <div class="stat-card">
                    <div class="stat-icon-img">
                        <img src="<?= $baseUrl ?>/assets/images/icon-shop-total.png" alt="Total">
                    </div>
                    <div class="stat-info">
                        <div class="value"><?= $stats['total'] ?? 0 ?></div>
                        <div class="label">Total Boutiques</div>
                    </div>
                </div>
                <!-- Actives -->
                <div class="stat-card">
                    <div class="stat-icon-img">
                        <img src="<?= $baseUrl ?>/assets/images/icon-shop-active.png" alt="Active">
                    </div>
                    <div class="stat-info">
                        <div class="value" style="color: var(--t-success);"><?= $stats['actives'] ?? 0 ?></div>
                        <div class="label">Actives</div>
                    </div>
                </div>
                <!-- En Attente -->
                <div class="stat-card">
                    <div class="stat-icon-img">
                        <img src="<?= $baseUrl ?>/assets/images/icon-shop-pending.png" alt="Pending">
                    </div>
                    <div class="stat-info">
                        <div class="value" style="color: var(--t-warning);"><?= $stats['en_attente'] ?? 0 ?></div>
                        <div class="label">En Attente</div>
                    </div>
                </div>
                <!-- Suspendues -->
                <div class="stat-card">
                    <div class="stat-icon-img">
                        <img src="<?= $baseUrl ?>/assets/images/icon-shop-suspended.png" alt="Suspended">
                    </div>
                    <div class="stat-info">
                        <div class="value" style="color: var(--t-danger);"><?= $stats['suspendues'] ?? 0 ?></div>
                        <div class="label">Suspendues</div>
                    </div>
                </div>
            </div>

            <!-- BARRE DE FILTRES -->
            <div class="filter-bar">
                <form action="" method="GET" class="filter-form">
                    <div class="search-group">
                        <input type="text" name="q" class="search-input" 
                               placeholder="Rechercher une boutique, un propriétaire..." 
                               value="<?= Security::escape($filters['q'] ?? '') ?>">
                        <span class="search-icon"><i class="bi bi-search"></i></span>
                    </div>
                    
                    <div class="filter-select-wrapper">
                        <select name="status" class="filter-select">
                            <option value="" <?= empty($filters['status']) ? 'selected' : '' ?>>Tous les statuts</option>
                            <option value="actif" <?= ($filters['status'] ?? '') === 'actif' ? 'selected' : '' ?>>Actives</option>
                            <option value="en_attente" <?= ($filters['status'] ?? '') === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                            <option value="suspendu" <?= ($filters['status'] ?? '') === 'suspendu' ? 'selected' : '' ?>>Suspendues</option>
                        </select>
                        <i class="bi bi-chevron-down filter-select-arrow"></i>
                    </div>
                    
                    <button type="submit" class="btn-filter">Filtrer</button>
                </form>
            </div>

            <!-- TABLEAU PREMIUM -->
            <div class="table-container">
                <div class="table-responsive">
                    <table class="table-premium">
                        <thead>
                            <tr>
                                <th>Boutique</th>
                                <th>Propriétaire</th>
                                <th>Statut</th>
                                <th>Création</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tenants)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="bi bi-shop display-4 d-block mb-3" style="opacity:0.3"></i>
                                        Aucune boutique ne correspond à vos critères.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($tenants as $t): ?>
                                    <tr class="clickable-row" data-href="<?= $baseUrl ?>/admin/tenant-details?id=<?= $t['idBoutique'] ?? '' ?>">
                                        <!-- Boutique -->
                                        <td>
                                            <div class="shop-info">
                                                <div class="shop-logo-wrapper">
                                                    <?php if (!empty($t['logo'])): ?>
                                                        <img src="<?= $baseUrl . $t['logo'] ?>" alt="Logo">
                                                    <?php else: ?>
                                                        <div class="shop-initials">
                                                            <?= strtoupper(substr($t['nomBoutique'] ?? 'Boutique', 0, 2)) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <span class="shop-name"><?= Security::escape($t['nomBoutique'] ?? 'Boutique sans nom') ?></span>
                                                    <span class="shop-slug">@<?= Security::escape($t['slugBoutique'] ?? 'inconnu') ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        
                                        <!-- Propriétaire -->
                                        <td>
                                            <div class="owner-info">
                                                <span class="name"><?= Security::escape($t['proprietaire'] ?? 'Inconnu') ?></span>
                                                <span class="email"><?= Security::escape($t['emailUtilisateur'] ?? '') ?></span>
                                            </div>
                                        </td>

                                        <!-- Statut -->
                                        <td>
                                            <span class="status-badge status-<?= $t['statut'] ?? 'actif' ?>">
                                                <span class="status-dot"></span>
                                                <?= ucfirst(str_replace('_', ' ', $t['statut'] ?? 'actif')) ?>
                                            </span>
                                        </td>

                                        <!-- Date -->
                                        <td class="text-muted small">
                                            <i class="bi bi-calendar3 me-1"></i>
                                            <?= !empty($t['dateCreation']) ? date('d/m/Y', strtotime($t['dateCreation'])) : 'N/A' ?>
                                        </td>

                                        <!-- Action -->
                                        <td class="text-end">
                                            <div class="btn-action">
                                                <i class="bi bi-arrow-right"></i>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if (($pagination['total'] ?? 0) > 1): ?>
                    <div class="pagination-wrapper">
                        <span class="pagination-info">
                            Affichage page <strong><?= $pagination['current'] ?? 1 ?></strong> sur <?= $pagination['total'] ?? 1 ?>
                        </span>
                        <div class="d-flex gap-2">
                            <?php if (($pagination['current'] ?? 1) > 1): ?>
                                <a href="?page=<?= ($pagination['current'] ?? 1) - 1 ?>" class="btn-page">
                                    <i class="bi bi-chevron-left me-1"></i> Précédent
                                </a>
                            <?php endif; ?>
                            <?php if (($pagination['current'] ?? 1) < ($pagination['total'] ?? 1)): ?>
                                <a href="?page=<?= ($pagination['current'] ?? 1) + 1 ?>" class="btn-page">
                                    Suivant <i class="bi bi-chevron-right ms-1"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- JS -->
<script src="<?= $baseUrl ?>/assets/js/tenants.js"></script>