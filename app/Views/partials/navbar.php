<?php
$baseUrl  = App::baseUrl();
$siteName = App::setting('site_name', 'ShopXPao');
$authLogo = $baseUrl . '/assets/images/shupxpaologo.png';
$user     = Session::user();

if ($user && empty($user['avatar'])) {
    $db = Database::getInstance();
    $freshUser = $db->fetch("SELECT avatar FROM utilisateur WHERE idUtilisateur = ?", 
        [$user['id'] ?? $user['idUtilisateur'] ?? null]);
    if ($freshUser && !empty($freshUser['avatar'])) {
        $user['avatar'] = $freshUser['avatar'];
    }
}

// Générer les initiales pour l'avatar
$prenom = $user['prenom'] ?? $user['prenomUtilisateur'] ?? '';
$nom = $user['nom'] ?? $user['nomUtilisateur'] ?? '';
$initials = strtoupper(substr($prenom, 0, 1) . substr($nom, 0, 1)) ?: 'U';
$avatar = $user['avatar'] ?? null;
$avatarUrl = !empty($avatar) ? $baseUrl . $avatar : null;
?>

<nav class="navbar navbar-expand-lg bg-white border-bottom sx-nav" id="mainNavbar">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="<?= $baseUrl ?>/">
      <img src="<?= $authLogo ?>" alt="Logo <?= Security::escape($siteName) ?>" class="main-logo-img">
      <span class="sx-brand"><?= Security::escape($siteName) ?></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sxNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div id="sxNav" class="collapse navbar-collapse">
      <form class="d-flex ms-lg-4 my-3 my-lg-0 flex-grow-1" action="<?= $baseUrl ?>/recherche" method="GET">
        <div class="input-group sx-search">
          <span class="input-group-text bg-transparent border-end-0">
            <i class="bi bi-search"></i>
          </span>
          <input class="form-control border-start-0" type="search" name="q" placeholder="Rechercher produits, boutiques...">
          <button class="btn btn-sx-primary" type="submit">Rechercher</button>
        </div>
      </form>

      <ul class="navbar-nav ms-lg-3 align-items-lg-center gap-lg-2">
        <li class="nav-item">
          <a class="nav-link" href="<?= $baseUrl ?>/boutiques"><i class="bi bi-shop me-1"></i>Boutiques</a>
        </li>
        <li class="nav-item position-relative">
          <a class="nav-link" href="<?= $baseUrl ?>/panier">
              <i class="bi bi-cart3"></i>Panier
              <span class="cart-counter" id="cartCounter" style="display: none;">0</span>
          </a>
        </li>
        <?php if ($user): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown" aria-expanded="false">
              <div class="topbar-avatar position-relative me-2">
                <?php if ($avatarUrl): ?>
                  <img src="<?= $avatarUrl ?>" alt="<?= Security::escape($prenom) ?>" 
                      style="width: 36px; height: 36px; object-fit: cover; border-radius: 8px; border: 2px solid #e2e8f0;">
                <?php else: ?>
                  <div class="avatar-initials d-flex align-items-center justify-content-center bg-primary text-white fw-bold" 
                      style="width: 36px; height: 36px; border-radius: 8px; font-size: 14px; background: linear-gradient(135deg, #6366f1, #4f46e5);">
                    <?= $initials ?>
                  </div>
                <?php endif; ?>
                <span class="topbar-online-dot" 
                      style="position: absolute; bottom: 2px; right: 2px; width: 8px; height: 8px; background: #10b981; border: 2px solid white; border-radius: 50%; z-index: 2;"></span>
              </div>
              <span class="d-none d-lg-inline ms-1"><?= Security::escape($prenom ?: 'Compte') ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="<?= $baseUrl ?>/profil">Mon profil</a></li>
              <li><a class="dropdown-item" href="<?= $baseUrl ?>/mes-commandes">Mes commandes</a></li>
              <?php if (($user['role'] ?? '') === 'admin'): ?>
                <li><a class="dropdown-item" href="<?= $baseUrl ?>/admin">Administration</a></li>
              <?php endif; ?>
              <?php if (($user['role'] ?? '') === 'tenant'): ?>
                <li><a class="dropdown-item" href="<?= $baseUrl ?>/vendeur/tableau-de-bord">Ma boutique</a></li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="<?= $baseUrl ?>/logout">Déconnexion</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="btn btn-outline-sx ms-lg-2" href="<?= $baseUrl ?>/login">Connexion</a>
          </li>
          <li class="nav-item">
            <a class="btn btn-sx-primary ms-lg-2" href="<?= $baseUrl ?>/register">Inscription</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<!-- BARRE CATÉGORIES (sous la navbar) -->
<?php if (empty($isMinimalNav)): ?>
  <div class="sx-category-bar">
    <div class="container-fluid px-0">
      <div class="sx-mega-wrapper">
        <button id="btnAllCategories" class="sx-btn-categories">
          <i class="bi bi-grid-3x3-gap-fill me-2"></i>
          Toutes les catégories
          <i class="bi bi-chevron-down ms-2 sx-chevron"></i>
        </button>

        <!-- MEGA MENU -->
        <div id="sxMegaMenu" class="sx-mega-menu">
          <!-- Colonne 1 : Catégories principales -->
          <div class="sx-mega-col sx-mega-col-1">
            <div class="sx-mega-col-header">Catégories</div>
            <ul id="col-principale" class="sx-mega-list">
              <?php if (!empty($categoriesPrincipales)): ?>
                <?php foreach ($categoriesPrincipales as $cat): ?>
                  <li class="sx-menu-item" data-id="<?= (int)$cat['idCategorie'] ?>">
                    <?php 
                    // Générer une couleur aléatoire basée sur le nom
                    $colors = ['#04BF9D', '#027373', '#5FCDD9', '#F29F05', '#F28705'];
                    $color = $colors[array_rand($colors)];
                    ?>
                    <span class="sx-category-dot" style="background: <?= $color ?>;"></span>
                    <span><?= Security::escape($cat['nomCategorie']) ?></span>
                    <i class="bi bi-chevron-right"></i>
                </li>
                <?php endforeach; ?>
              <?php else: ?>
                <li class="sx-menu-empty">Aucune catégorie</li>
              <?php endif; ?>
            </ul>
          </div>

          <!-- Colonne 2 : Sous-catégories -->
          <div class="sx-mega-col sx-mega-col-2">
            <div class="sx-mega-col-header">Sous-catégories</div>
            <ul id="col-secondaire" class="sx-mega-list">
              <li class="sx-menu-empty">
                <i class="bi bi-arrow-left-circle me-2"></i>Sélectionnez une catégorie
              </li>
            </ul>
          </div>

          <!-- Colonne 3 : Grille de produits -->
          <div class="sx-mega-col sx-mega-col-3">
            <div class="sx-mega-col-header">Produits</div>
            <div id="col-tertiaire" class="sx-products-container">
              <div class="sx-products-empty">
                <i class="bi bi-arrow-left-circle me-2"></i>Sélectionnez une sous-catégorie
              </div>
            </div>
          </div>
        </div>
        <!-- Overlay -->
        <div id="sxMegaOverlay" class="sx-mega-overlay"></div>
      </div>
    </div>
  </div>
<?php endif; ?>