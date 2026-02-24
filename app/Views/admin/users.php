<?php

$baseUrl = App::baseUrl();
$statsTotal     = $userStats['total'] ?? 0;
$statsActifs    = $userStats['actifs'] ?? 0;
$statsEnAttente = $userStats['en_attente'] ?? 0;
$statsBloques   = $userStats['bloques'] ?? 0;
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/users.css">

<div class="admin-layout">
    <!-- Sidebar -->
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <div class="admin-main">
        <!-- Topbar -->
        <?php require __DIR__ . '/partials/topbar.php'; ?>

        <div class="admin-content container-fluid py-4">

            <!-- 1. EN-TÊTE & BOUTON -->
            <div class="users-page-header">
                <div class="page-title-group">
                    <h1>Utilisateurs</h1>
                    <p>Gérez les comptes clients, vendeurs et administrateurs.</p>
                </div>
                <button class="btn-users-primary" data-bs-toggle="modal" data-bs-target="#userModal" onclick="resetForm()">
                    <i class="bi bi-person-plus-fill"></i>
                    <span>Nouvel utilisateur</span>
                </button>
            </div>

            <!-- 2. STATISTIQUES GLOBALES (Connectées à la BDD) -->
            <div class="users-stats-bar">
                <!-- Total -->
                <div class="users-stat-item">
                    <div class="users-stat-icon all">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="users-stat-text">
                        <div class="stat-value"><?= $statsTotal ?></div>
                        <div class="stat-label">Total utilisateurs</div>
                    </div>
                </div>
                <!-- Actifs -->
                <div class="users-stat-item">
                    <div class="users-stat-icon active">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div class="users-stat-text">
                        <div class="stat-value"><?= $statsActifs ?></div>
                        <div class="stat-label">Actifs</div>
                    </div>
                </div>
                <!-- En attente -->
                <div class="users-stat-item">
                    <div class="users-stat-icon pending">
                        <i class="bi bi-clock-fill"></i>
                    </div>
                    <div class="users-stat-text">
                        <div class="stat-value"><?= $statsEnAttente ?></div>
                        <div class="stat-label">En attente</div>
                    </div>
                </div>
                <!-- Bloqués -->
                <div class="users-stat-item">
                    <div class="users-stat-icon blocked">
                        <i class="bi bi-slash-circle-fill"></i>
                    </div>
                    <div class="users-stat-text">
                        <div class="stat-value"><?= $statsBloques ?></div>
                        <div class="stat-label">Bloqués</div>
                    </div>
                </div>
            </div>

            <!-- 3. FILTRES -->
            <div class="users-filter-card">
                <form action="" method="GET" class="row g-3 align-items-end">
                    <div class="col-12 col-md-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="q" class="form-control" 
                                   placeholder="Rechercher (Nom, Email)..." 
                                   value="<?= Security::escape($filters['q'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <select name="role" class="form-select">
                            <option value="">Tous les rôles</option>
                            <option value="client" <?= ($filters['role'] ?? '') === 'client' ? 'selected' : '' ?>>Client</option>
                            <option value="tenant" <?= ($filters['role'] ?? '') === 'tenant' ? 'selected' : '' ?>>Vendeur (Tenant)</option>
                            <option value="admin" <?= ($filters['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrateur</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <select name="status" class="form-select">
                            <option value="">Tous les statuts</option>
                            <option value="actif" <?= ($filters['status'] ?? '') === 'actif' ? 'selected' : '' ?>>Actif</option>
                            <option value="en_attente" <?= ($filters['status'] ?? '') === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                            <option value="bloque" <?= ($filters['status'] ?? '') === 'bloque' ? 'selected' : '' ?>>Bloqué</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2 d-grid">
                        <button type="submit" class="btn-users-filter">
                            <i class="bi bi-funnel me-1"></i> Filtrer
                        </button>
                    </div>
                </form>
            </div>

            <!-- 4. MESSAGES FLASH -->
            <?php if (isset($_GET['success'])): ?>
                <div class="users-alert users-alert-success">
                    <span><?= Security::escape($_GET['success']) ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="users-alert users-alert-danger">
                    <span><?= Security::escape($_GET['error']) ?></span>
                </div>
            <?php endif; ?>

            <!-- 5. TABLEAU -->
            <div class="users-table-card">
                <div class="table-responsive">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th class="ps-4">Utilisateur</th>
                                <th>Rôle</th>
                                <th>Statut</th>
                                <th>Date création</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="users-empty-state">
                                            <div class="empty-icon">
                                                <i class="bi bi-people"></i>
                                            </div>
                                            <h6>Aucun utilisateur trouvé</h6>
                                            <p>Modifiez vos filtres ou ajoutez un nouvel utilisateur.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <!-- Info User -->
                                        <td class="ps-4">
                                            <div class="users-info">
                                                <div class="users-avatar">
                                                    <?php if (!empty($u['avatar'])): ?>
                                                        <img src="<?= $baseUrl . $u['avatar'] ?>" alt="Avatar">
                                                    <?php else: ?>
                                                        <div class="users-avatar-placeholder users-avatar-<?= $u['role'] ?? 'client' ?>">
                                                            <?= strtoupper(substr($u['prenomUtilisateur'] ?? '', 0, 1) . substr($u['nomUtilisateur'] ?? '', 0, 1)) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="users-info-text">
                                                    <h6><?= Security::escape(($u['prenomUtilisateur'] ?? '') . ' ' . ($u['nomUtilisateur'] ?? '')) ?></h6>
                                                    <small><?= Security::escape($u['emailUtilisateur'] ?? '') ?></small>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Rôle -->
                                        <td>
                                            <span class="users-badge users-badge-<?= $u['role'] ?? 'client' ?>">
                                                <?= ucfirst($u['role'] ?? 'client') ?>
                                            </span>
                                        </td>

                                        <!-- Statut -->
                                        <td>
                                            <span class="users-badge users-badge-<?= $u['statut'] ?? 'en_attente' ?>">
                                                <?= ucfirst($u['statut'] ?? 'en_attente') ?>
                                            </span>
                                        </td>

                                        <!-- Date -->
                                        <td>
                                            <span class="users-date">
                                                <i class="bi bi-calendar3 me-1"></i>
                                                <?= !empty($u['dateCreation']) ? date('d/m/Y', strtotime($u['dateCreation'])) : '---' ?>
                                            </span>
                                        </td>

                                        <!-- Actions -->
                                        <td>
                                            <div class="users-actions">
                                                <button class="users-action-btn edit"
                                                        onclick="editUser(<?= htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8') ?>)"
                                                        title="Modifier">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="users-action-btn delete"
                                                        onclick="confirmDelete(<?= $u['idUtilisateur'] ?>)"
                                                        title="Supprimer">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- 6. PAGINATION -->
                <?php if (isset($pagination) && ($pagination['total'] ?? 0) > 1): ?>
                    <div class="users-pagination-wrapper">
                        <span class="users-pagination-info">
                            Page <strong><?= $pagination['current'] ?></strong> sur <strong><?= $pagination['total'] ?></strong>
                        </span>
                        <nav class="users-pagination">
                            <ul class="pagination">
                                <!-- Précédent -->
                                <?php if ($pagination['current'] > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $pagination['current'] - 1 ?>&q=<?= Security::escape($filters['q'] ?? '') ?>&role=<?= Security::escape($filters['role'] ?? '') ?>&status=<?= Security::escape($filters['status'] ?? '') ?>">
                                            <i class="bi bi-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <!-- Numéros de page -->
                                <?php for ($i = 1; $i <= $pagination['total']; $i++): ?>
                                    <li class="page-item <?= $i == $pagination['current'] ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&q=<?= Security::escape($filters['q'] ?? '') ?>&role=<?= Security::escape($filters['role'] ?? '') ?>&status=<?= Security::escape($filters['status'] ?? '') ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <!-- Suivant -->
                                <?php if ($pagination['current'] < $pagination['total']): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $pagination['current'] + 1 ?>&q=<?= Security::escape($filters['q'] ?? '') ?>&role=<?= Security::escape($filters['role'] ?? '') ?>&status=<?= Security::escape($filters['status'] ?? '') ?>">
                                            <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>

        </div> 
    </div> 
</div> 


<!-- ══════════════════════════════════════════════════════════
     MODAL : AJOUT / MODIFICATION
     ══════════════════════════════════════════════════════════ -->
<div class="modal fade users-modal" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" action="<?= $baseUrl ?>/admin/users/save" method="POST">
            <?= CSRF::field() ?>
            <input type="hidden" name="idUtilisateur" id="userId">

            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Nouvel Utilisateur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Prénom</label>
                        <input type="text" name="prenom" id="userPrenom" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nom</label>
                        <input type="text" name="nom" id="userNom" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="userEmail" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Rôle</label>
                        <select name="role" id="userRole" class="form-select">
                            <option value="client">Client</option>
                            <option value="tenant">Vendeur (Tenant)</option>
                            <option value="admin">Administrateur</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Statut</label>
                        <select name="statut" id="userStatut" class="form-select">
                            <option value="actif">Actif</option>
                            <option value="en_attente">En attente</option>
                            <option value="bloque">Bloqué</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">
                            Mot de passe 
                            <span id="pwdHelp" style="color: var(--u-primary); font-size: 0.75rem;">(Requis pour création)</span>
                        </label>
                        <div class="password-toggle-wrapper">
                            <input type="password" name="password" class="form-control" placeholder="••••••••" autocomplete="new-password">
                            <button type="button" class="password-toggle-btn" tabindex="-1">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-users-cancel" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn-users-save">Enregistrer</button>
            </div>
        </form>
    </div>
</div>


<!-- ══════════════════════════════════════════════════════════
     MODAL : SUPPRESSION
     ══════════════════════════════════════════════════════════ -->
<div class="modal fade users-delete-modal" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form class="modal-content" action="<?= $baseUrl ?>/admin/users/delete" method="POST">
            <?= CSRF::field() ?>
            <input type="hidden" name="id_delete" id="deleteId">

            <div class="modal-body text-center py-4">
                <div class="users-delete-icon">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <h5 class="mb-2 fw-bold">Êtes-vous sûr ?</h5>
                <p class="text-muted small mb-4">
                    Cette action est <strong class="text-danger">irréversible</strong>.
                </p>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn-users-cancel" data-bs-dismiss="modal">Non, retour</button>
                    <button type="submit" class="btn-users-delete">Oui, supprimer</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- JS Spécifique -->
<script src="<?= $baseUrl ?>/assets/js/users.js"></script>