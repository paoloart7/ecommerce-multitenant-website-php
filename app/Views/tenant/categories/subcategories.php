<?php 
$baseUrl = App::baseUrl();
$user = Session::user();
$parent = $parent ?? [];
$subCategories = $subCategories ?? [];
$stats = $stats ?? ['total' => 0, 'actives' => 0];
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
                    <h2 class="fw-bold h4 m-0">📁 Sous-catégories de <span class="text-primary"><?= Security::escape($parent['nomCategorie'] ?? '') ?></span></h2>
                    <p class="text-muted small mb-0">Gérez les sous-catégories de cette catégorie</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= $baseUrl ?>/vendeur/categories" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                    <a href="<?= $baseUrl ?>/vendeur/sous-categorie/ajouter?parent=<?= $parent['idCategorie'] ?? 0 ?>" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> Nouvelle sous-catégorie
                    </a>
                </div>
            </div>

            <!-- STATS CARDS -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon bg-info-soft text-info">
                            <i class="bi bi-folder-symlink"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Total sous-catégories</span>
                            <span class="stat-value"><?= $stats['total'] ?? 0 ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon bg-success-soft text-success">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Actives</span>
                            <span class="stat-value"><?= $stats['actives'] ?? 0 ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABLEAU DES SOUS-CATÉGORIES -->
            <div class="categories-card">
                <div class="table-responsive">
                    <table class="table categories-table">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Produits</th>
                                <th>Ordre</th>
                                <th>Statut</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($subCategories)): ?>
                                <?php foreach ($subCategories as $cat): ?>
                                <tr class="category-row">
                                    <td>
                                        <div class="category-name">
                                            <i class="bi bi-arrow-return-right text-muted me-2"></i>
                                            <i class="bi bi-folder<?= $cat['actif'] ? '-fill' : '' ?> text-warning me-2"></i>
                                            <?= Security::escape($cat['nomCategorie']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-soft">
                                            <?= $cat['nombreProduits'] ?? 0 ?> produit(s)
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
                                    <td colspan="5" class="text-center py-5">
                                        <div class="empty-state">
                                            <i class="bi bi-folder-x display-1 text-muted"></i>
                                            <h5 class="mt-3">Aucune sous-catégorie</h5>
                                            <p class="text-muted">Cette catégorie n'a pas encore de sous-catégories</p>
                                            <a href="<?= $baseUrl ?>/vendeur/sous-categorie/ajouter?parent=<?= $parent['idCategorie'] ?? 0 ?>" class="btn btn-primary mt-2">
                                                <i class="bi bi-plus-lg"></i> Ajouter une sous-catégorie
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