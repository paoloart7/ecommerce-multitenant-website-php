<?php 
$baseUrl = App::baseUrl();
$user = Session::user();
$details = $details ?? [];
$client = $details['client'] ?? [];
$stats = $details['stats'] ?? [];
$commandes = $details['commandes'] ?? [];
$adresses = $details['adresses'] ?? [];
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/tenant-clients.css">

<div class="tenant-layout">
    <?php require dirname(__DIR__) . '/partials/sidebar.php'; ?>
    
    <div class="tenant-main">
        <?php require dirname(__DIR__) . '/partials/topbar.php'; ?>

        <div class="tenant-content container-fluid p-4">
            
            <!-- EN-TÊTE -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold h4 m-0">👤 Détail client</h2>
                    <p class="text-muted small mb-0">
                        <?= Security::escape($client['prenomUtilisateur'] ?? '') ?> <?= Security::escape($client['nomUtilisateur'] ?? '') ?>
                    </p>
                </div>
                <a href="<?= $baseUrl ?>/vendeur/clients" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
            </div>

            <!-- INFORMATIONS CLIENT -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="client-profile-card">
                        <div class="profile-header">
                            <div class="profile-avatar">
                                <?php if (!empty($client['avatar'])): ?>
                                    <img src="<?= $baseUrl . $client['avatar'] ?>" alt="">
                                <?php else: ?>
                                    <span class="avatar-initials-large">
                                        <?= strtoupper(substr($client['prenomUtilisateur'] ?? '', 0, 1) . substr($client['nomUtilisateur'] ?? '', 0, 1)) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <h4><?= Security::escape($client['prenomUtilisateur'] ?? '') ?> <?= Security::escape($client['nomUtilisateur'] ?? '') ?></h4>
                            <p class="text-muted">Client depuis <?= date('d/m/Y', strtotime($client['dateCreation'] ?? '')) ?></p>
                        </div>
                        <div class="profile-contact">
                            <div class="contact-item">
                                <i class="bi bi-envelope"></i>
                                <span><?= Security::escape($client['emailUtilisateur'] ?? '') ?></span>
                            </div>
                            <?php if (!empty($client['telephone'])): ?>
                            <div class="contact-item">
                                <i class="bi bi-telephone"></i>
                                <span><?= Security::escape($client['telephone']) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="stat-mini-card">
                                <span class="stat-label">Commandes</span>
                                <span class="stat-value"><?= $stats['nb_commandes'] ?? 0 ?></span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-mini-card">
                                <span class="stat-label">Total dépensé</span>
                                <span class="stat-value"><?= number_format($stats['total_depenses'] ?? 0, 0, ',', ' ') ?> G</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-mini-card">
                                <span class="stat-label">Panier moyen</span>
                                <span class="stat-value"><?= number_format($stats['panier_moyen'] ?? 0, 0, ',', ' ') ?> G</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-mini-card">
                                <span class="stat-label">Dernière commande</span>
                                <span class="stat-value-small"><?= !empty($stats['derniere_commande']) ? date('d/m/Y', strtotime($stats['derniere_commande'])) : 'Jamais' ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mt-2">
                        <div class="col-md-4">
                            <div class="stat-mini-card">
                                <span class="stat-label">Commandes en cours</span>
                                <span class="stat-value"><?= $stats['commandes_encours'] ?? 0 ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-mini-card">
                                <span class="stat-label">Commandes livrées</span>
                                <span class="stat-value"><?= $stats['commandes_livrees'] ?? 0 ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-mini-card">
                                <span class="stat-label">Commandes annulées</span>
                                <span class="stat-value"><?= $stats['commandes_annulees'] ?? 0 ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- HISTORIQUE DES COMMANDES -->
            <div class="client-orders-card mb-4">
                <div class="card-header">
                    <h5>📦 Historique des commandes</h5>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>N° commande</th>
                                <th>Date</th>
                                <th>Articles</th>
                                <th>Total</th>
                                <th>Statut</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($commandes)): ?>
                                <?php foreach ($commandes as $cmd): ?>
                                <tr>
                                    <td class="fw-bold">#<?= Security::escape($cmd['numeroCommande'] ?? '') ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($cmd['dateCommande'] ?? '')) ?></td>
                                    <td><?= $cmd['nb_articles'] ?? 0 ?> article(s)</td>
                                    <td class="fw-bold text-success"><?= number_format($cmd['total'] ?? 0, 0, ',', ' ') ?> G</td>
                                    <td>
                                        <?php
                                        $badgeClass = match($cmd['statut'] ?? '') {
                                            'en_attente' => 'warning',
                                            'confirmee', 'payee' => 'info',
                                            'livree' => 'success',
                                            'annulee' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?= $badgeClass ?>"><?= $cmd['statut'] ?? '' ?></span>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= $baseUrl ?>/vendeur/commande-details?id=<?= $cmd['idCommande'] ?? 0 ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> Voir
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <p class="text-muted mb-0">Aucune commande pour ce client</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ADRESSES -->
            <?php if (!empty($adresses)): ?>
            <div class="client-addresses-card">
                <div class="card-header">
                    <h5>🏠 Adresses enregistrées</h5>
                </div>
                <div class="row g-3 p-3">
                    <?php foreach ($adresses as $adresse): ?>
                    <div class="col-md-6">
                        <div class="address-card <?= !empty($adresse['estDefaut']) ? 'default' : '' ?>">
                            <?php if (!empty($adresse['estDefaut'])): ?>
                                <span class="default-badge">Adresse par défaut</span>
                            <?php endif; ?>
                            <div class="address-content">
                                <p class="mb-1"><strong><?= Security::escape($adresse['nomDestinataire'] ?? $client['prenomUtilisateur'] . ' ' . $client['nomUtilisateur']) ?></strong></p>
                                <p class="mb-1"><?= Security::escape($adresse['rue'] ?? '') ?></p>
                                <?php if (!empty($adresse['complement'])): ?>
                                    <p class="mb-1"><?= Security::escape($adresse['complement']) ?></p>
                                <?php endif; ?>
                                <p class="mb-1">
                                    <?= Security::escape($adresse['codePostal'] ?? '') ?> <?= Security::escape($adresse['ville'] ?? '') ?>
                                    <?php if (!empty($adresse['quartier'])): ?>, <?= Security::escape($adresse['quartier']) ?><?php endif; ?>
                                </p>
                                <p class="mb-0"><?= Security::escape($adresse['pays'] ?? 'Haiti') ?></p>
                                <?php if (!empty($adresse['telephone'])): ?>
                                    <p class="mb-0 mt-2"><i class="bi bi-telephone"></i> <?= Security::escape($adresse['telephone']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>