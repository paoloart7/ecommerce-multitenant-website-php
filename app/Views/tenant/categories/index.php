<?php 
$baseUrl = App::baseUrl();
$user = Session::user();
$parents = $parents ?? [];
$stats = $stats ?? ['total' => 0, 'parents' => 0, 'enfants' => 0, 'actifs' => 0];
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/categories.css">
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
                    <h2 class="fw-bold h4 m-0">📁 Catégories principales</h2>
                    <p class="text-muted small mb-0">Gérez vos catégories et sous-catégories</p>
                </div>
                <a href="<?= $baseUrl ?>/vendeur/categorie/ajouter" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Nouvelle catégorie
                </a>
            </div>

            <!-- STATS CARDS -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary-soft text-primary">
                            <i class="bi bi-folder"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Catégories</span>
                            <span class="stat-value"><?= $stats['parents'] ?? 0 ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-info-soft text-info">
                            <i class="bi bi-folder-symlink"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Sous-catégories</span>
                            <span class="stat-value"><?= $stats['enfants'] ?? 0 ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-success-soft text-success">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Produits</span>
                            <span class="stat-value"><?= $stats['totalProduits'] ?? 0 ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning-soft text-warning">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Actives</span>
                            <span class="stat-value"><?= $stats['actifs'] ?? 0 ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABLEAU DES CATÉGORIES PARENTES -->
            <div class="categories-card">
                <div class="table-responsive">
                    <table class="table categories-table">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Sous-catégories</th>
                                <th>Produits</th>
                                <th>Ordre</th>
                                <th>Statut</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($parents)): ?>
                                <?php foreach ($parents as $cat): ?>
                                <tr class="category-row">
                                    <td>
                                        <div class="category-name">
                                            <i class="bi bi-folder<?= $cat['actif'] ? '-fill' : '' ?> text-warning me-2"></i>
                                            <?= Security::escape($cat['nomCategorie']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info-soft">
                                            <?= $cat['nbSousCategories'] ?? 0 ?> sous-catégorie(s)
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-soft">
                                            <?= $cat['totalProduits'] ?? 0 ?> produit(s)
                                        </span>
                                    </td>
                                    <td><?= $cat['ordre'] ?? 0 ?></td>
                                    <td>
                                        <?php if ($cat['actif']): ?>
                                            <span class="badge bg-success-soft text-success">Actif</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-soft text-muted">Inactif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="action-buttons">
                                            <!-- 👁️ Voir sous-catégories -->
                                            <a href="<?= $baseUrl ?>/vendeur/sous-categories?parent=<?= $cat['idCategorie'] ?>" 
                                               class="btn btn-sm btn-outline-info" title="Voir les sous-catégories">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            
                                            <!-- ✏️ Modifier -->
                                            <a href="<?= $baseUrl ?>/vendeur/categorie/modifier?id=<?= $cat['idCategorie'] ?>" 
                                               class="btn btn-sm btn-outline-primary" title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            
                                            <!-- 🗑️ Supprimer -->
                                            <form method="POST" action="<?= $baseUrl ?>/vendeur/categorie/supprimer" 
                                                  class="d-inline delete-form"
                                                  data-name="<?= Security::escape($cat['nomCategorie']) ?>">
                                                <?= CSRF::field() ?>
                                                <input type="hidden" name="idCategorie" value="<?= $cat['idCategorie'] ?>">
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-btn" title="Supprimer">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="empty-state">
                                            <i class="bi bi-folder-x display-1 text-muted"></i>
                                            <h5 class="mt-3">Aucune catégorie</h5>
                                            <p class="text-muted">Créez votre première catégorie parente</p>
                                            <a href="<?= $baseUrl ?>/vendeur/categorie/ajouter" class="btn btn-primary mt-2">
                                                <i class="bi bi-plus-lg"></i> Ajouter une catégorie
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= $baseUrl ?>/assets/js/categories.js"></script>