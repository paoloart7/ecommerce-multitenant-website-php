<?php
$user    = Session::user();
$baseUrl = App::baseUrl();
$initials = strtoupper(substr($user['prenom'] ?? '', 0, 1) . substr($user['nom'] ?? '', 0, 1));
?>

<header class="admin-topbar">
    <div class="d-flex align-items-center">
        <button class="admin-sidebar-toggle me-3 d-lg-none" id="adminSidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <div>
            <h1 class="admin-page-title">
                <?= isset($pageTitle) ? Security::escape($pageTitle) : 'Administration' ?>
            </h1>
        </div>
    </div>
    <div class="topbar-right">
        <div class="topbar-date d-none d-md-flex">
            <i class="bi bi-calendar3"></i>
            <span><?= date('d M Y') ?></span>
        </div>
        <div class="topbar-divider d-none d-md-block"></div>
        <div class="dropdown">
            <button class="topbar-profile-btn dropdown-toggle" type="button"
                    id="topbarUserDropdown" data-bs-toggle="dropdown" aria-expanded="false">

                <div class="topbar-user-info d-none d-sm-block">
                    <span class="topbar-user-name">
                        <?= Security::escape(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')) ?>
                    </span>
                    <span class="topbar-user-role">
                        <?= ucfirst($user['role'] ?? 'admin') ?>
                    </span>
                </div>
                <div class="topbar-avatar">
                    <?php if (!empty($user['avatar'])): ?>
                        <img src="<?= $baseUrl . $user['avatar'] ?>" 
                             alt="<?= Security::escape($user['prenom'] ?? 'User') ?>">
                    <?php else: ?>
                        <span class="topbar-initials"><?= $initials ?></span>
                    <?php endif; ?>
                    <span class="topbar-online-dot"></span>
                </div>
            </button>
            <ul class="dropdown-menu dropdown-menu-end topbar-dropdown-menu" 
                aria-labelledby="topbarUserDropdown">
                
                <li class="topbar-dd-header">
                    <div class="topbar-dd-avatar">
                        <?php if (!empty($user['avatar'])): ?>
                            <img src="<?= $baseUrl . $user['avatar'] ?>" alt="Avatar">
                        <?php else: ?>
                            <span><?= $initials ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="topbar-dd-info">
                        <div class="topbar-dd-name">
                            <?= Security::escape(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')) ?>
                        </div>
                        <div class="topbar-dd-email">
                            <?= Security::escape($user['email'] ?? '') ?>
                        </div>
                    </div>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item topbar-dd-item" href="<?= $baseUrl ?>/admin">
                        <i class="bi bi-grid-1x2"></i> Tableau de bord
                    </a>
                </li>
                <li>
                    <a class="dropdown-item topbar-dd-item" href="<?= $baseUrl ?>/admin/parametres">
                        <i class="bi bi-gear"></i> Paramètres
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