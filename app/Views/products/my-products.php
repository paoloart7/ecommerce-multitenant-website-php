<?php 
$baseUrl = App::baseUrl();
$user = Session::user();

$products = $products ?? [];
$pagination = $pagination ?? ['pages' => 1, 'current' => 1];
$filters = $filters ?? [];
$stats = $stats ?? ['total' => 0, 'disponibles' => 0, 'brouillons' => 0, 'rupture' => 0];
$categories = $categories ?? [];
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/my-products.css">

<div class="tenant-layout">
    <?php require dirname(__DIR__) . '/tenant/partials/sidebar.php'; ?>
    
    <div class="tenant-main">
        <?php require dirname(__DIR__) . '/tenant/partials/topbar.php'; ?>

        <div class="tenant-content container-fluid p-4">
            
            <!-- EN-TÊTE -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold h4 m-0">📦 Mes Produits</h2>
                    <p class="text-muted small mb-0">Gérez votre catalogue de produits</p>
                </div>
                <a href="<?= $baseUrl ?>/vendeur/produit/ajouter" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Nouveau produit
                </a>
            </div>
            
            <!-- ✅ MESSAGES FLASH -->
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

            <!-- STATS CARDS -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary-soft text-primary">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Total produits</span>
                            <span class="stat-value"><?= $stats['total'] ?? 0 ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-success-soft text-success">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Disponibles</span>
                            <span class="stat-value"><?= $stats['disponibles'] ?? 0 ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning-soft text-warning">
                            <i class="bi bi-pencil"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Brouillons</span>
                            <span class="stat-value"><?= $stats['brouillons'] ?? 0 ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-danger-soft text-danger">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Rupture</span>
                            <span class="stat-value"><?= $stats['rupture'] ?? 0 ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FILTRES -->
            <div class="filters-card mb-4">
                <form method="GET" action="<?= $baseUrl ?>/vendeur/mes-produits" class="filters-form">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small">Catégorie</label>
                            <select name="categorie" class="form-select">
                                <option value="">Toutes</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['idCategorie'] ?>" <?= ($filters['categorie'] ?? '') == $cat['idCategorie'] ? 'selected' : '' ?>>
                                        <?= Security::escape($cat['nomCategorie']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Statut</label>
                            <select name="statut" class="form-select">
                                <option value="">Tous</option>
                                <option value="disponible" <?= ($filters['statut'] ?? '') == 'disponible' ? 'selected' : '' ?>>Disponible</option>
                                <option value="brouillon" <?= ($filters['statut'] ?? '') == 'brouillon' ? 'selected' : '' ?>>Brouillon</option>
                                <option value="non_disponible" <?= ($filters['statut'] ?? '') == 'non_disponible' ? 'selected' : '' ?>>Non disponible</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Recherche</label>
                            <input type="text" name="search" class="form-control" placeholder="Nom ou SKU..." value="<?= $filters['search'] ?? '' ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-filter"></i> Filtrer
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- TABLEAU DES PRODUITS -->
            <div class="products-table-card">
                <div class="table-responsive">
                    <table class="table products-table">
                        <thead>
                            <tr>
                                <th style="width: 60px">Image</th>
                                <th>Produit</th>
                                <th>Catégorie</th>
                                <th>Prix</th>
                                <th>Stock</th>
                                <th>Statut</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($products)): ?>
                                <?php foreach ($products as $product): ?>
                                <tr class="product-row">
                                    <td>
                                        <div class="product-image">
                                            <?php if (!empty($product['imagePrincipale'])): ?>
                                                <img src="<?= $baseUrl . $product['imagePrincipale'] ?>" alt="<?= Security::escape($product['nomProduit']) ?>">
                                            <?php else: ?>
                                                <div class="no-image">
                                                    <i class="bi bi-image"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="product-info">
                                            <span class="product-name"><?= Security::escape($product['nomProduit']) ?></span>
                                            <?php if (!empty($product['sku'])): ?>
                                                <small class="product-sku">SKU: <?= Security::escape($product['sku']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?= Security::escape($product['nomCategorie'] ?? 'Non catégorisé') ?></td>
                                    <td>
                                        <span class="product-price">
                                            <?= number_format($product['prix'], 0, ',', ' ') ?> G
                                            <?php if (!empty($product['prixPromo'])): ?>
                                                <small class="text-muted text-decoration-line-through d-block">
                                                    <?= number_format($product['prixPromo'], 0, ',', ' ') ?> G
                                                </small>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $stockClass = 'bg-success';
                                        if ($product['stock'] == 0) $stockClass = 'bg-danger';
                                        elseif ($product['stock'] <= $product['stockAlerte']) $stockClass = 'bg-warning';
                                        ?>
                                        <span class="badge <?= $stockClass ?>"><?= $product['stock'] ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = match($product['statutProduit']) {
                                            'disponible' => 'success',
                                            'brouillon' => 'secondary',
                                            'non_disponible' => 'warning',
                                            default => 'secondary'
                                        };
                                        $statusText = match($product['statutProduit']) {
                                            'disponible' => 'Disponible',
                                            'brouillon' => 'Brouillon',
                                            'non_disponible' => 'Non dispo',
                                            default => $product['statutProduit']
                                        };
                                        ?>
                                        <span class="badge bg-<?= $statusClass ?>"><?= $statusText ?></span>
                                    </td>
                                    <td class="text-end">
                                        <div class="action-buttons">
                                            <!-- Bouton Modifier -->
                                            <a href="<?= $baseUrl ?>/vendeur/produit/modifier?id=<?= $product['idProduit'] ?>" 
                                            class="btn btn-sm btn-outline-primary" title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            
                                            <!-- Bouton Supprimer - LIEN DIRECT avec confirmation -->
                                            <a href="<?= $baseUrl ?>/vendeur/produit/supprimer?id=<?= $product['idProduit'] ?>" 
                                            class="btn btn-sm btn-outline-danger" 
                                            onclick="return confirm('Supprimer <?= addslashes($product['nomProduit']) ?> ?')"
                                            title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>                               
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="empty-state">
                                            <i class="bi bi-box-seam display-1 text-muted"></i>
                                            <h5 class="mt-3">Aucun produit trouvé</h5>
                                            <p class="text-muted">Commencez par ajouter votre premier produit</p>
                                            <a href="<?= $baseUrl ?>/vendeur/produit/ajouter" class="btn btn-primary mt-2">
                                                <i class="bi bi-plus-lg"></i> Ajouter un produit
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION -->
                <?php if ($pagination['pages'] > 1): ?>
                <div class="pagination-wrapper">
                    <nav>
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= $pagination['current'] <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $pagination['current'] - 1])) ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $pagination['pages']; $i++): ?>
                                <li class="page-item <?= $i == $pagination['current'] ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $i])) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?= $pagination['current'] >= $pagination['pages'] ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $pagination['current'] + 1])) ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
window.onload = function() {
   
    // Animation stats cards
    document.querySelectorAll('.stat-card').forEach((card, i) => {
        card.style.animationDelay = `${i * 0.1}s`;
    });
    
    // Auto-hide alerts
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 3000);
};
</script>