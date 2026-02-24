<?php
$baseUrl = App::baseUrl();
$siteName = App::setting('site_name', 'ShopXPao');
$user = Session::user();
$stats = $stats ?? [];
$recentTenants = $recentTenants ?? [];
$recentUsers = $recentUsers ?? [];
$recentOrders = $recentOrders ?? [];
?>
<div class="admin-layout">
  <?php require dirname(__DIR__) . '/admin/partials/sidebar.php'; ?>
  
  <div class="admin-main">
    <?php require __DIR__ . '/partials/topbar.php'; ?>
    <!-- CONTENU -->
    <div class="admin-content container-fluid py-4">
      
      <!-- 1. STATISTIQUES GLOBALES (Cartes) -->
      <div class="row g-3 mb-4">
        
        <!-- Carte Utilisateurs -->
        <div class="col-12 col-md-6 col-xl-3">
          <div class="admin-stat-card">
            <!-- Image icône -->
            <div class="admin-stat-img">
                <img src="<?= $baseUrl ?>/assets/images/icon-users.png" alt="Users">
            </div>
            <div class="admin-stat-info">
              <div class="admin-stat-label">Utilisateurs</div>
              <div class="admin-stat-value"><?= $stats['utilisateurs']['total'] ?? 0 ?></div>
              <div class="admin-stat-sub">
                Clients: <strong><?= $stats['utilisateurs']['clients'] ?? 0 ?></strong> &bull; 
                Vendeurs: <strong><?= $stats['utilisateurs']['tenants'] ?? 0 ?></strong>
              </div>
            </div>
          </div>
        </div>

        <!-- Carte Boutiques -->
        <div class="col-12 col-md-6 col-xl-3">
            <div class="admin-stat-card">
                <div class="admin-stat-img">
                    <img src="<?= $baseUrl ?>/assets/images/icon-shops.png" alt="Shops">
                </div>
                <div class="admin-stat-info">
                    <div class="admin-stat-label">Boutiques</div>
                    <div class="admin-stat-value"><?= $stats['boutiques']['total'] ?? 0 ?></div>
                    <div class="admin-stat-sub">
                        <?php 
                        $actives = $stats['boutiques']['actives'] ?? 0;
                        $enAttente = $stats['boutiques']['enAttente'] ?? 0;
                        $sansCommandes = $stats['boutiques']['sansCommandes'] ?? 0;
                        ?>
                        Actives <span class="badge bg-success-soft text-success"><?= $actives ?></span>
                        <?php if ($sansCommandes > 0): ?>
                            <br><small class="text-muted"><?= $sansCommandes ?> boutique(s) sans commande</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Carte Commandes -->
        <div class="col-12 col-md-6 col-xl-3">
          <div class="admin-stat-card">
            <div class="admin-stat-img">
                <img src="<?= $baseUrl ?>/assets/images/icon-orders.png" alt="Orders">
            </div>
            <div class="admin-stat-info">
              <div class="admin-stat-label">Commandes</div>
              <div class="admin-stat-value"><?= $stats['commandes']['total'] ?? 0 ?></div>
              <div class="admin-stat-sub">
                Aujourd'hui : <strong><?= $stats['commandes']['aujourdhui'] ?? 0 ?></strong>
              </div>
            </div>
          </div>
        </div>

        <!-- Carte CA -->
        <div class="col-12 col-md-6 col-xl-3">
          <div class="admin-stat-card">
            <div class="admin-stat-img">
                <img src="<?= $baseUrl ?>/assets/images/icon-money.png" alt="Money">
            </div>
            <div class="admin-stat-info">
              <div class="admin-stat-label">Chiffre d'affaires</div>
              <div class="admin-stat-value text-success">
                <?= number_format($stats['commandes']['volumeTotal'] ?? 0, 0, ',', ' ') ?> <small>G</small>
              </div>
              <div class="admin-stat-sub">Volume total validé</div>
            </div>
          </div>
        </div>

      </div>

      <!-- 2. GRILLE PRINCIPALE -->
      <div class="row g-3 mb-4">
        
        <!-- Dernières Boutiques -->
        <div class="col-12 col-lg-7">
          <div class="card admin-card h-100-card"> 
            <div class="card-header d-flex justify-content-between align-items-center">
              <h2 class="h6 mb-0">Dernières boutiques</h2>
              <a href="<?= $baseUrl ?>/admin/tenants" class="small link-sx">Voir tout &rarr;</a>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table mb-0 align-middle table-hover">
                  <thead class="table-light">
                    <tr>
                      <th style="padding-left: 1.5rem;">Boutique</th>
                      <th>Propriétaire</th>
                      <th>Statut</th>
                      <th>Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!empty($recentTenants)): ?>
                      <?php foreach ($recentTenants as $t): ?>
                        <tr class="clickable-row" onclick="window.location='<?= $baseUrl ?>/admin/tenant-details?id=<?= $t['idBoutique'] ?>'">
                          <td style="padding-left: 1.5rem;">
                            <div class="d-flex align-items-center gap-3">
                                <!-- LOGO BOUTIQUE -->
                                <div class="avatar-shop">
                                    <?php if(!empty($t['logo'])): ?>
                                        <img src="<?= $baseUrl . $t['logo'] ?>" alt="Logo">
                                    <?php else: ?>
                                        <span class="initials"><?= strtoupper(substr($t['nomBoutique'], 0, 1)) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="fw-semibold text-dark"><?= Security::escape($t['nomBoutique']) ?></div>
                                    <div class="text-muted small"><?= Security::escape($t['slugBoutique']) ?></div>
                                </div>
                            </div>
                          </td>
                          <td><?= Security::escape($t['proprietaire'] ?? 'Inconnu') ?></td>
                          <td>
                            <?php 
                              $st = $t['statut'] ?? 'en_attente';
                              $badgeClass = match($st) {
                                  'actif' => 'bg-success-soft text-success',
                                  'suspendu' => 'bg-danger-soft text-danger',
                                  'en_attente' => 'bg-warning-soft text-warning',
                                  default => 'bg-secondary-soft text-muted'
                              };
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= $st ?></span>
                          </td>
                          <td class="text-muted small">
                            <?= !empty($t['dateCreation']) ? date('d/m/Y', strtotime($t['dateCreation'])) : '' ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr><td colspan="4" class="text-center text-muted py-4">Aucune boutique récente.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Derniers Utilisateurs -->
        <div class="col-12 col-lg-5">
          <div class="card admin-card"> 
            <div class="card-header d-flex justify-content-between align-items-center">
              <h2 class="h6 mb-0">Derniers utilisateurs</h2>
              <a href="<?= $baseUrl ?>/admin/users" class="small link-sx">Voir tout &rarr;</a>
            </div>
            <div class="card-body p-0">
              <ul class="list-group list-group-flush">
                <?php if (!empty($recentUsers)): ?>
                  <?php foreach ($recentUsers as $u): ?>
                    <li class="list-group-item d-flex align-items-center justify-content-between py-3 px-4">
                      <div class="d-flex align-items-center gap-3">
                        
                        <!-- AVATAR UTILISATEUR -->
                        <div class="admin-user-avatar-sm">
                            <?php if(!empty($u['avatar'])): ?>
                                <img src="<?= $baseUrl . $u['avatar'] ?>" alt="Avatar" class="img-fluid rounded-circle" style="width:100%; height:100%; object-fit:cover;">
                            <?php else: ?>
                                <span><?= strtoupper(substr($u['prenomUtilisateur'], 0, 1) . substr($u['nomUtilisateur'], 0, 1)) ?></span>
                            <?php endif; ?>
                        </div>

                        <div>
                          <div class="fw-semibold text-dark small">
                            <?= Security::escape($u['prenomUtilisateur'] . ' ' . $u['nomUtilisateur']) ?>
                          </div>
                          <div class="text-muted small" style="font-size: 0.75rem;">
                            <?= Security::escape($u['emailUtilisateur']) ?>
                          </div>
                        </div>
                      </div>
                      <div class="text-end">
                        <?php 
                            $role = $u['role'] ?? 'client';
                            $badgeClass = match($role) {
                                'admin' => 'bg-primary-soft text-primary',
                                'tenant' => 'bg-success-soft text-success',
                                'employee'=> 'bg-info-soft text-info',
                                default => 'bg-secondary-soft text-muted'
                            };
                        ?>
                        <span class="badge <?= $badgeClass ?>"><?= ucfirst($role) ?></span>
                      </div>
                    </li>
                  <?php endforeach; ?>
                <?php else: ?>
                  <li class="list-group-item text-center text-muted small py-3">Aucun utilisateur récent.</li>
                <?php endif; ?>
              </ul>
            </div>
          </div>
        </div>

      </div>

      <!-- 3. ACTIONS RAPIDES -->
      <div class="row g-3">
        <div class="col-12 col-xl-4">
          <div class="card admin-card h-100">
            <div class="card-header">
              <h2 class="h6 mb-0">Actions rapides</h2>
            </div>
            <div class="card-body p-3">
              <div class="d-flex flex-column gap-2">
                <a href="<?= $baseUrl ?>/admin/tenants?statut=en_attente" class="admin-quick-action">
                  <div class="icon bg-warning-soft text-warning"><i class="bi bi-hourglass-split"></i></div>
                  <div>
                    <div class="label">Valider les boutiques</div>
                    <div class="sub">Gérer les demandes en attente</div>
                  </div>
                </a>
                <a href="<?= $baseUrl ?>/admin/users" class="admin-quick-action">
                  <div class="icon bg-primary-soft text-primary"><i class="bi bi-person-plus"></i></div>
                  <div>
                    <div class="label">Gérer les utilisateurs</div>
                    <div class="sub">Ajouter, modifier ou bloquer</div>
                  </div>
                </a>
                <a href="<?= $baseUrl ?>/admin/orders" class="admin-quick-action">
                  <div class="icon bg-success-soft text-success"><i class="bi bi-cart-check"></i></div>
                  <div>
                    <div class="label">Suivi des commandes</div>
                    <div class="sub">Analyser les ventes récentes</div>
                  </div>
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Commandes Récentes -->
        <div class="col-12 col-xl-8">
          <div class="card admin-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h2 class="h6 mb-0">Dernières commandes</h2>
              <a href="<?= $baseUrl ?>/admin/orders" class="small link-sx">Voir tout &rarr;</a>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table mb-0 align-middle">
                  <thead class="table-light">
                    <tr>
                      <th style="padding-left: 1.5rem;">Réf.</th>
                      <th>Boutique</th>
                      <th>Client</th>
                      <th>Total</th>
                      <th>Statut</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!empty($recentOrders)): ?>
                      <?php foreach ($recentOrders as $o): ?>
                        <tr>
                          <td class="fw-bold text-dark small" style="padding-left: 1.5rem;">#<?= Security::escape($o['numeroCommande'] ?? '---') ?></td>
                          <td class="small"><?= Security::escape($o['nomBoutique'] ?? '---') ?></td>
                          <td class="small"><?= Security::escape($o['nomClient'] ?? '---') ?></td>
                          <td class="fw-bold text-success small"><?= number_format($o['total'] ?? 0, 0, ',', ' ') ?> G</td>
                          <td><span class="badge bg-secondary-soft text-dark"><?= $o['statut'] ?? 'N/A' ?></span></td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr><td colspan="5" class="text-center text-muted py-3 small">Aucune commande.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>