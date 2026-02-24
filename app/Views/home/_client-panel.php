<?php
// Mini dashboard client sur la page d'accueil
$user        = $user        ?? Session::user();
$isClient    = $isClient    ?? ($user && ($user['role'] ?? null) === 'client');
$clientStats = $clientStats ?? null;
$lastOrders  = $lastOrders  ?? [];

if (!$isClient) {
    return;
}

// Générer les initiales
$prenom = $user['prenom'] ?? $user['prenomUtilisateur'] ?? '';
$nom = $user['nom'] ?? $user['nomUtilisateur'] ?? '';
$initials = strtoupper(substr($prenom, 0, 1) . substr($nom, 0, 1)) ?: 'U';
$avatar = $user['avatar'] ?? null;
$avatarUrl = !empty($avatar) ? App::baseUrl() . $avatar : null;

$totalCmd    = (int) ($clientStats['totalCommandes']   ?? 0);
$cmdEnCours  = (int) ($clientStats['commandesEnCours'] ?? 0);
$totalDepense= (float)($clientStats['totalDepense']    ?? 0.0);
$derniere    = $clientStats['derniereCommande']        ?? null;
?>

<section class="container my-4">
  <div class="client-panel-card">
    <div class="row g-0">
      <!-- Colonne gauche : Profil -->
      <div class="col-lg-4 panel-profile">
        <div class="d-flex align-items-center gap-3 mb-3">
          <div class="panel-avatar position-relative">
            <?php if ($avatarUrl): ?>
              <img src="<?= $avatarUrl ?>" alt="<?= Security::escape($prenom) ?>" 
                   class="panel-avatar-img">
            <?php else: ?>
              <div class="panel-avatar-initials">
                <?= $initials ?>
              </div>
            <?php endif; ?>
            <span class="panel-online-dot"></span>
          </div>
          <div>
            <div class="panel-welcome">Bienvenue</div>
            <h3 class="panel-name"><?= Security::escape($prenom . ' ' . $nom) ?></h3>
            <div class="panel-role">Client</div>
          </div>
        </div>
        
        <div class="panel-stats-grid">
          <div class="panel-stat-item">
            <span class="panel-stat-value"><?= $totalCmd ?></span>
            <span class="panel-stat-label">Commandes</span>
          </div>
          <div class="panel-stat-item">
            <span class="panel-stat-value"><?= $cmdEnCours ?></span>
            <span class="panel-stat-label">En cours</span>
          </div>
          <div class="panel-stat-item">
            <span class="panel-stat-value"><?= number_format($totalDepense, 0, ',', ' ') ?></span>
            <span class="panel-stat-label">Dépensé (G)</span>
          </div>
        </div>
      </div>

      <!-- Colonne droite : Dernières commandes -->
      <div class="col-lg-8 panel-orders">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="panel-orders-title">📦 Dernières commandes</h4>
          <a href="<?= App::baseUrl() ?>/mes-commandes" class="panel-view-all">
            Voir tout <i class="bi bi-arrow-right"></i>
          </a>
        </div>

        <?php if (!empty($lastOrders)): ?>
          <div class="orders-list">
            <?php foreach ($lastOrders as $index => $order): ?>
              <a href="<?= App::baseUrl() ?>/commande/details?id=<?= $order['idCommande'] ?>" 
                 class="order-item <?= $index === 0 ? 'first' : '' ?>">
                <div class="order-icon">
                  <i class="bi bi-receipt"></i>
                </div>
                <div class="order-info">
                  <div class="order-header">
                    <span class="order-number">#<?= Security::escape($order['numeroCommande']) ?></span>
                    <span class="order-badge badge-<?= $order['statut'] ?? '' ?>">
                      <?= str_replace('_', ' ', $order['statut'] ?? '') ?>
                    </span>
                  </div>
                  <div class="order-details">
                    <span><i class="bi bi-shop"></i> <?= Security::escape($order['nomBoutique']) ?></span>
                    <span><i class="bi bi-calendar"></i> <?= date('d/m/Y', strtotime($order['dateCommande'])) ?></span>
                  </div>
                </div>
                <div class="order-amount">
                  <?= number_format($order['total'], 0, ',', ' ') ?> G
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="panel-empty">
            <i class="bi bi-inbox"></i>
            <p>Aucune commande pour le moment</p>
            <a href="<?= App::baseUrl() ?>/boutiques" class="panel-empty-link">
              Découvrir les boutiques
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>