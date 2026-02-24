<?php $baseUrl = App::baseUrl(); ?>
<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/produits.css">

<div class="admin-layout">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    <div class="admin-main">
        <?php require __DIR__ . '/partials/topbar.php'; ?>
        
        <div class="admin-content container-fluid py-4">

            <!-- 🏆 HEADER ELITE -->
            <div class="premium-header-box">
                <div class="header-title-group">
                    <h1>Surveillance Catalogue</h1>
                    <p class="text-muted mb-0">Contrôle de conformité et marketing global</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary px-4 py-2 rounded-pill fw-bold shadow">
                        <i class="bi bi-shield-check me-2"></i> Audit complet
                    </button>
                </div>
            </div>

            <!-- 📊 STATS CARDS -->
            <div class="produits-stats">
                <div class="stat-card-premium">
                    <span class="label">Total Articles</span>
                    <span class="value"><?= $pagination['totalItems'] ?? count($products) ?></span>
                </div>
                <div class="stat-card-premium" style="border-bottom: 4px solid #f59e0b;">
                    <span class="label">En Vedette</span>
                    <?php $featCount = count(array_filter($products, fn($p) => $p['misEnAvant'] == 1)); ?>
                    <span class="value text-warning"><?= $featuredCount ?? 0 ?></span>
                </div>
                <div class="stat-card-premium"><span class="label">Boutiques</span><span class="value text-primary">--</span></div>
                <div class="stat-card-premium"><span class="label">Alertes</span><span class="value text-danger">0</span></div>
            </div>

            <!-- 🔍 SEARCH -->
            <div class="mb-4 bg-white p-3 rounded-4 border shadow-sm">
                <form action="" method="GET" class="row align-items-center">
                    <div class="col-md-9">
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-0 text-muted"><i class="bi bi-search fs-5"></i></span>
                            <input type="text" name="q" class="form-control border-0 fs-5 shadow-none" placeholder="Produit ou boutique..." value="<?= Security::escape($filters['q'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-3 text-end"><button type="submit" class="btn btn-dark w-100 py-2 fw-bold rounded-3">Rechercher</button></div>
                </form>
            </div>

            <!-- 🛒 TABLEAU -->
            <div class="table-responsive">
                <table class="table-premium">
                    <thead>
                        <tr>
                            <th class="ps-4">Information Produit</th>
                            <th>Boutique</th>
                            <th>Prix</th>
                            <th class="text-center">Vedette</th>
                            <th>Statut</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($products as $p): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="product-hero">
                                    <div class="img-container">
                                        <?php 
                                            $imgUrl = !empty($p['image_principale']) 
                                                    ? $baseUrl . '/' . ltrim($p['image_principale'], '/') 
                                                    : $baseUrl . '/assets/images/default-product.png';
                                        ?>
                                        <img src="<?= $imgUrl ?>" 
                                            onerror="this.onerror=null; this.src='<?= $baseUrl ?>/assets/images/default-product.png';">
                                    </div>
                                    <div>
                                        <h6 class="product-name"><?= Security::escape($p['nomProduit']) ?></h6>
                                        <span class="product-cat"><?= $p['nomCategorie'] ?? 'Général' ?></span>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark border">@<?= Security::escape($p['nomBoutique']) ?></span></td>
                            <td class="fw-bold"><?= number_format($p['prix'], 2) ?> G</td>
                            <td class="text-center">
                                <form action="<?= $baseUrl ?>/admin/product/featured" method="POST">
                                    <?= CSRF::field() ?>
                                    <input type="hidden" name="id" value="<?= $p['idProduit'] ?>">
                                    <input type="hidden" name="featured" value="<?= $p['misEnAvant'] ? 0 : 1 ?>">
                                    <button class="btn-star-elite <?= $p['misEnAvant'] ? 'active' : '' ?>"><i class="bi <?= $p['misEnAvant'] ? 'bi-star-fill' : 'bi-star' ?>"></i></button>
                                </form>
                            </td>
                            <td><span class="badge-p badge-p-<?= $p['statutProduit'] ?>"><?= $p['statutProduit'] ?></span></td>
                            <td class="text-end pe-4">
                                <div class="d-flex gap-2 justify-content-end">
                                    <button class="btn-elite-view" onclick="viewProduct(<?= $p['idProduit'] ?>)">Voir</button>
                                    
                                    <form action="<?= $baseUrl ?>/admin/product/status" method="POST">
                                        <?= CSRF::field() ?>
                                        <input type="hidden" name="id" value="<?= $p['idProduit'] ?>">
                                        <input type="hidden" name="status" value="<?= ($p['statutProduit'] === 'archive') ? 'disponible' : 'archive' ?>">
                                        <button class="btn btn-sm btn-outline-<?= ($p['statutProduit'] === 'archive') ? 'success' : 'danger' ?> border-0">
                                            <i class="bi bi-<?= ($p['statutProduit'] === 'archive') ? 'check-circle-fill' : 'slash-circle-fill' ?>"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <!-- PAGINATION -->
            <?php if (($pagination['total'] ?? 0) > 1): ?>
            <div class="pagination-premium-wrapper">
                <div class="pagination-info">
                    Affichage <strong><?= count($products) ?></strong> produits sur <strong><?= $pagination['totalItems'] ?? 0 ?></strong> au total
                </div>
                <nav class="pagination-premium">
                    <ul class="pagination">
                        <!-- Page précédente -->
                        <li class="page-item <?= $pagination['current'] <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $pagination['current'] - 1 ?><?= !empty($filters['q']) ? '&q=' . urlencode($filters['q']) : '' ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                        
                        <!-- Pages numérotées -->
                        <?php 
                        $totalPages = $pagination['total'];
                        $currentPage = $pagination['current'];
                        $start = max(1, $currentPage - 2);
                        $end = min($totalPages, $currentPage + 2);
                        
                        for ($i = $start; $i <= $end; $i++): 
                        ?>
                            <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?><?= !empty($filters['q']) ? '&q=' . urlencode($filters['q']) : '' ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <!-- Page suivante -->
                        <li class="page-item <?= $pagination['current'] >= $pagination['total'] ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $pagination['current'] + 1 ?><?= !empty($filters['q']) ? '&q=' . urlencode($filters['q']) : '' ?>">
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

<!--  MODALE DE DÉTAILS -->
<div class="modal fade" id="modalViewProduct" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 24px; overflow: hidden;">
            <div class="modal-header modal-product-header">
                <h5 class="modal-title fw-bold">Audit du Produit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body modal-product-body">
                <div class="row g-4">
                    <div class="col-md-5">
                        <div class="product-gallery-main"><img id="m-img" src=""></div>
                    </div>
                    <div class="col-md-7">
                        <span class="badge bg-primary-subtle text-primary mb-2" id="m-cat">-</span>
                        <h2 class="fw-black text-slate-900 mb-1" id="m-title">-</h2>
                        <p class="text-muted small">Vendu par <span class="fw-bold text-dark" id="m-shop">-</span></p>
                        
                        <div class="product-info-grid">
                            <div class="detail-item"><div class="detail-label">Prix</div><div class="detail-value text-primary" id="m-price">-</div></div>
                            <div class="detail-item"><div class="detail-label">Stock</div><div class="detail-value" id="m-stock">-</div></div>
                            <div class="detail-item"><div class="detail-label">Référence</div><div class="detail-value" id="m-sku">-</div></div>
                            <div class="detail-item"><div class="detail-label">Date Ajout</div><div class="detail-value" id="m-date">-</div></div>
                        </div>
                        <div class="description-box"><div class="detail-label mb-2">Description complète</div><div id="m-desc" class="small">-</div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= $baseUrl ?>/assets/js/produits.js"></script>