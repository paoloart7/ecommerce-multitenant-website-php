<?php
$baseUrl = App::baseUrl();
$t = $t ?? [];
$initials = strtoupper(substr($t['nomBoutique'] ?? 'Boutique', 0, 2));
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/tenants.css">
<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/tenant-details.css">

<!-- Styles correctifs immédiats pour le logo et les boutons -->
<style>
    .shop-avatar-lg {
        width: 100px; height: 100px;
        border-radius: 20px;
        background: #fff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 8px 20px rgba(0,0,0,0.06);
        overflow: hidden;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .shop-avatar-lg img {
        width: 100%; height: 100%;
        object-fit: cover;
    }
    .btn-group-actions {
        display: flex !important;
        flex-direction: row !important;
        align-items: center;
        gap: 0.75rem;
    }
    .btn-group-actions form { display: inline-block; margin: 0; }
</style>

<div class="admin-layout">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <div class="admin-main">
        <?php require __DIR__ . '/partials/topbar.php'; ?>

        <div class="admin-content container-fluid py-4">

            <!-- FIL D'ARIANE / RETOUR -->
            <div class="mb-4">
                <a href="<?= $baseUrl ?>/admin/tenants" class="btn btn-sm btn-light border-0 px-3">
                    <i class="bi bi-arrow-left me-2"></i> Retour aux boutiques
                </a>
            </div>

            <!-- HEADER DE LA BOUTIQUE -->
            <div class="detail-header d-flex flex-column flex-md-row justify-content-between align-items-center gap-4">
                <div class="d-flex align-items-center gap-4">
                    <div class="shop-avatar-lg">
                        <?php if(!empty($t['logo'])): ?>
                            <img src="<?= $baseUrl . $t['logo'] ?>" alt="Logo">
                        <?php else: ?>
                            <div class="fw-bold" style="font-size: 2.5rem; color: var(--indigo-600);"><?= $initials ?></div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-3 mb-1">
                            <h1 class="h2 mb-0 fw-bolder text-slate-900"><?= Security::escape($t['nomBoutique'] ?? 'Boutique sans nom') ?></h1>
                            <span class="status-badge status-<?= trim($t['statut'] ?? 'actif') ?>">
                                <span class="status-dot"></span>
                                <?= ucfirst($t['statut'] ?? 'Actif') ?>
                            </span>
                        </div>
                        <p class="text-muted mb-0">
                             Boutique de <span class="text-dark fw-bold"><?= Security::escape($t['proprietaire'] ?? 'Inconnu') ?></span> 
                             &bull; Créée le <?= !empty($t['dateCreation']) ? date('d M Y', strtotime($t['dateCreation'])) : 'N/A' ?>
                        </p>
                    </div>
                </div>

                <!-- ACTIONS RAPIDES -->
                <div class="btn-group-actions">
                    <?php $status = trim(strtolower($t['statut'] ?? 'actif')); ?>

                    <?php if($status === 'en_attente'): ?>
                        <form action="<?= $baseUrl ?>/admin/tenant/update-status" method="POST">
                            <?= CSRF::field() ?>
                            <input type="hidden" name="idBoutique" value="<?= $t['idBoutique'] ?? '' ?>">
                            <input type="hidden" name="statut" value="actif">
                            <button type="submit" class="btn btn-success shadow-sm px-4">
                                <i class="bi bi-check-lg me-2"></i> Activer
                            </button>
                        </form>

                    <?php elseif($status === 'actif'): ?>
                        <form action="<?= $baseUrl ?>/admin/tenant/update-status" method="POST">
                            <?= CSRF::field() ?>
                            <input type="hidden" name="idBoutique" value="<?= $t['idBoutique'] ?? '' ?>">
                            <input type="hidden" name="statut" value="suspendu">
                            <button type="submit" class="btn btn-warning shadow-sm px-4 text-white" style="background:#f59e0b; border:none;">
                                <i class="bi bi-pause-fill me-2"></i> Suspendre
                            </button>
                        </form>
                    <?php else: ?>
                        <form action="<?= $baseUrl ?>/admin/tenant/update-status" method="POST">
                            <?= CSRF::field() ?>
                            <input type="hidden" name="idBoutique" value="<?= $t['idBoutique'] ?? '' ?>">
                            <input type="hidden" name="statut" value="actif">
                            <button type="submit" class="btn btn-outline-success px-4">
                                <i class="bi bi-play-fill me-2"></i> Rétablir
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <!-- Bouton Supprimer (Toujours présent) -->
                    <button class="btn btn-outline-danger px-3" data-bs-toggle="modal" data-bs-target="#deleteModal">
                        <i class="bi bi-trash3"></i>
                    </button>
                </div>
            </div>

            <!-- STATISTIQUES -->
            <div class="stats-grid mb-5">
                <div class="stat-card">
                    <div class="stat-icon-img"><img src="<?= $baseUrl ?>/assets/images/icon-money.png"></div>
                    <div class="stat-info">
                        <div class="value currency"><?= (float)($stats['ca'] ?? 0) ?></div>
                        <div class="label">Chiffre d'Affaires</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon-img"><img src="<?= $baseUrl ?>/assets/images/icon-orders.png"></div>
                    <div class="stat-info">
                        <div class="value"><?= (int)($stats['commandes'] ?? 0) ?></div>
                        <div class="label">Commandes</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon-img"><img src="<?= $baseUrl ?>/assets/images/icon-products.png"></div>
                    <div class="stat-info">
                        <div class="value"><?= (int)($stats['produits'] ?? 0) ?></div>
                        <div class="label">Produits</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon-img"><img src="<?= $baseUrl ?>/assets/images/icon-abo.png"></div>
                    <div class="stat-info">
                        <div class="value" style="color: var(--indigo-600);"><?= ucfirst($t['typeAbonnement'] ?? 'Gratuit') ?></div>
                        <div class="label">Forfait actuel</div>
                    </div>
                </div>
            </div>

            <!-- ONGLETS -->
            <div class="text-center mb-4">
                <ul class="nav nav-tabs nav-tabs-premium" id="tenantTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#overview">Vue d'ensemble</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#abo">Abonnement</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#logs">Journal (Audit)</button>
                    </li>
                </ul>
            </div>

            <div class="tab-content" id="tenantTabsContent">
                
                <!-- TAB 1 : VUE D'ENSEMBLE -->
                <div class="tab-pane fade show active" id="overview" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="info-card">
                                <div class="section-header">
                                    <i class="bi bi-person-badge"></i>
                                    <h5>Coordonnées du vendeur</h5>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Nom complet</div>
                                    <div class="info-value"><?= Security::escape($t['proprietaire'] ?? 'Inconnu') ?></div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Email</div>
                                    <div class="info-value"><i class="bi bi-envelope-at text-muted"></i> <?= Security::escape($t['emailUtilisateur'] ?? '') ?></div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Téléphone</div>
                                    <div class="info-value"><i class="bi bi-phone text-muted"></i> <?= Security::escape($t['telProprietaire'] ?? 'N/A') ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-card">
                                <div class="section-header">
                                    <i class="bi bi-gear-wide-connected"></i>
                                    <h5>Configuration technique</h5>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Slug de la boutique</div>
                                    <div class="info-value url" style="color:var(--indigo-600)">@<?= $t['slugBoutique'] ?? 'inconnu' ?></div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Devise active</div>
                                    <div class="info-value"><?= $t['devise'] ?? 'HTG' ?></div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Description</div>
                                    <p class="small text-muted mb-0"><?= nl2br(Security::escape($t['description'] ?? 'Aucune description.')) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2 : ABONNEMENT -->
                <div class="tab-pane fade" id="abo" role="tabpanel">
                    <div class="info-card">
                        <div class="section-header"><i class="bi bi-star"></i><h5>Plan commercial</h5></div>
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <p>Modifier manuellement le forfait de cette boutique.</p>
                                <div class="d-flex gap-3">
                                    <div class="p-3 border rounded-4 text-center bg-light">
                                        <div class="info-label">Plan Actuel</div>
                                        <div class="fw-bold text-primary"><?= strtoupper($t['typeAbonnement'] ?? 'GRATUIT') ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <form action="<?= $baseUrl ?>/admin/tenant/update-abo" method="POST" class="p-3 bg-light rounded-4">
                                    <?= CSRF::field() ?>
                                    <input type="hidden" name="idBoutique" value="<?= $t['idBoutique'] ?? '' ?>">
                                    <select name="plan" class="form-select form-select-sm mb-3">
                                        <option value="gratuit" <?= ($t['typeAbonnement'] ?? '') == 'gratuit' ? 'selected' : '' ?>>Gratuit</option>
                                        <option value="basique" <?= ($t['typeAbonnement'] ?? '') == 'basique' ? 'selected' : '' ?>>Basique</option>
                                        <option value="premium" <?= ($t['typeAbonnement'] ?? '') == 'premium' ? 'selected' : '' ?>>Premium</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm w-100">Mettre à jour</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3 : LOGS -->
                <div class="tab-pane fade" id="logs" role="tabpanel">
                    <div class="info-card p-0 overflow-hidden">
                        <table class="table logs-table mb-0">
                            <thead><tr><th>Date</th><th>Action</th><th>Type</th><th>Détails</th></tr></thead>
                            <tbody>
                                <?php if(empty($logs)): ?>
                                    <tr><td colspan="4" class="text-center py-5">Aucune activité.</td></tr>
                                <?php else: ?>
                                    <?php foreach($logs as $log): ?>
                                        <tr>
                                            <td class="small text-muted"><?= !empty($log['dateAction']) ? date('d/m/Y H:i', strtotime($log['dateAction'])) : 'N/A' ?></td>
                                            <td class="fw-bold small"><?= $log['action'] ?? '' ?></td>
                                            <td><span class="badge bg-light text-secondary border"><?= $log['typeAction'] ?? '' ?></span></td>
                                            <td class="small text-muted"><?= Security::escape($log['details'] ?? '-') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL SUPPRESSION -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <form action="<?= $baseUrl ?>/admin/tenant/delete" method="POST" class="modal-content border-0 shadow-lg">
            <?= CSRF::field() ?>
            <input type="hidden" name="id_delete" value="<?= $t['idBoutique'] ?? '' ?>">
            <div class="modal-body text-center p-4">
                <i class="bi bi-exclamation-triangle text-danger display-4 mb-3"></i>
                <h5 class="fw-bold">Supprimer la boutique ?</h5>
                <p class="text-muted small">Action irréversible.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">Confirmer</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="<?= $baseUrl ?>/assets/js/tenant-details.js"></script>