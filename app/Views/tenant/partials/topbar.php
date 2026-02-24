<?php
// app/Views/tenant/partials/topbar.php
$user    = Session::user();
$baseUrl = App::baseUrl();
$prenom = $user['prenomUtilisateur'] ?? $user['prenom'] ?? '';
$nom = $user['nomUtilisateur'] ?? $user['nom'] ?? '';
$email = $user['emailUtilisateur'] ?? $user['email'] ?? '';
$avatar = $user['avatar'] ?? null;
$avatarPath = !empty($avatar) ? $baseUrl . $avatar : null;

$initials = strtoupper(substr($prenom, 0, 1) . substr($nom, 0, 1));
if (empty(trim($initials))) $initials = 'VD'; 
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/topbar.css">
<header class="admin-topbar">
    <div class="d-flex align-items-center">
        <button class="admin-sidebar-toggle me-3 d-lg-none" id="tenantSidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <div>
            <h1 class="admin-page-title"><?= isset($pageTitle) ? Security::escape($pageTitle) : 'Espace Vendeur' ?></h1>
        </div>
    </div>

    <div class="topbar-right">

        <!-- Date -->
        <div class="topbar-date d-none d-md-flex">
            <i class="bi bi-calendar3"></i>
            <span><?= date('d M Y') ?></span>
        </div>

        <!-- Séparateur -->
        <div class="topbar-divider d-none d-md-block"></div>

        <!-- Profil avec Dropdown -->
        <div class="dropdown">
            <button class="topbar-profile-btn dropdown-toggle" type="button"
                    id="topbarUserDropdown" data-bs-toggle="dropdown" aria-expanded="false">

                <!-- Info texte -->
                <div class="topbar-user-info d-none d-sm-block">
                    <span class="topbar-user-name">
                        <?= Security::escape($prenom . ' ' . $nom) ?>
                    </span>
                    <span class="topbar-user-role">Vendeur</span>
                </div>

                <!-- Avatar -->
                <div class="topbar-avatar">
                    <?php if ($avatarPath): ?>
                        <img src="<?= $avatarPath ?>" 
                             alt="<?= Security::escape($prenom) ?>">
                    <?php else: ?>
                        <span class="topbar-initials"><?= $initials ?></span>
                    <?php endif; ?>
                    <span class="topbar-online-dot"></span>
                </div>
            </button>

            <!-- Menu Dropdown -->
            <ul class="dropdown-menu dropdown-menu-end topbar-dropdown-menu" 
                aria-labelledby="topbarUserDropdown">
                
                <!-- En-tête profil -->
                <li class="topbar-dd-header">
                    <div class="topbar-dd-avatar">
                        <?php if ($avatarPath): ?>
                            <img src="<?= $avatarPath ?>" alt="Avatar">
                        <?php else: ?>
                            <span><?= $initials ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="topbar-dd-info">
                        <div class="topbar-dd-name">
                            <?= Security::escape($prenom . ' ' . $nom) ?>
                        </div>
                        <div class="topbar-dd-email">
                            <?= Security::escape($email) ?>
                        </div>
                    </div>
                </li>

                <li><hr class="dropdown-divider"></li>

                <!-- Liens spécifiques au Vendeur -->
                <li>
                    <a class="dropdown-item topbar-dd-item" href="<?= $baseUrl ?>/vendeur/tableau-de-bord">
                        <i class="bi bi-speedometer2"></i> Tableau de bord
                    </a>
                </li>
                <li>
                    <a class="dropdown-item topbar-dd-item" href="<?= $baseUrl ?>/vendeur/parametres">
                        <i class="bi bi-gear"></i> Paramètres boutique
                    </a>
                </li>
                <li>
                    <a class="dropdown-item topbar-dd-item" href="<?= $baseUrl ?>/profil">
                        <i class="bi bi-person"></i> Mon profil
                    </a>
                </li>

                <li><hr class="dropdown-divider"></li>

                <li>
                    <a class="dropdown-item topbar-dd-item" href="<?= $baseUrl ?>/" target="_blank">
                        <i class="bi bi-box-arrow-up-right"></i> Voir le site
                    </a>
                </li>

                <li><hr class="dropdown-divider"></li>

                <li>
                    <a class="dropdown-item topbar-dd-item topbar-dd-logout" href="<?= $baseUrl ?>/logout">
                        <i class="bi bi-box-arrow-left"></i> Déconnexion
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>