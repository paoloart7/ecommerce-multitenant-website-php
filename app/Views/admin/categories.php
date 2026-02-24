<?php $baseUrl = App::baseUrl(); ?>
<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/users.css">

<div class="admin-layout">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <div class="admin-main">
        <?php require __DIR__ . '/partials/topbar.php'; ?>

        <div class="admin-content container-fluid py-4">
            <nav class="mb-4">
                <ol class="breadcrumb bg-transparent p-0 m-0">
                    <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/admin/categories" class="text-decoration-none">Catégories</a></li>
                    <?php if($isSubView): ?>
                        <li class="breadcrumb-item active">@<?= Security::escape($parent['nomCategorie']) ?></li>
                    <?php endif; ?>
                </ol>
            </nav>

            <!-- HEADER -->
            <div class="users-page-header">
                <div class="page-title-group">
                    <h1><?= $pageTitle ?></h1>
                    <p>Gestion hiérarchique du catalogue ShopXPao</p>
                </div>
                <button class="btn-users-primary" data-bs-toggle="modal" data-bs-target="#catModal" onclick="resetForm()">
                    <i class="bi bi-plus-circle-fill"></i>
                    <span>Ajouter <?= $isSubView ? 'sous-catégorie' : 'catégorie' ?></span>
                </button>
            </div>

            <!-- ALERTES NOTIFICATIONS -->
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> Opération réussie !
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= Security::escape($_GET['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- TABLEAU -->
            <div class="users-table-card">
                <div class="table-responsive">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th class="ps-4">Boutique</th>
                                <th>Nom Catégorie</th>
                                <th>Slug</th>
                                <th>Statut</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted">Aucun élément trouvé.</td></tr>
                            <?php else: ?>
                                <?php foreach($categories as $c): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="<?= $baseUrl . ($c['boutiqueLogo'] ?? '/assets/images/default-shop.png') ?>" width="28" height="28" class="rounded-circle border">
                                                <span class="small fw-bold"><?= Security::escape($c['nomBoutique']) ?></span>
                                            </div>
                                        </td>
                                        <td><div class="fw-bold"><?= Security::escape($c['nomCategorie']) ?></div></td>
                                        <td><span class="text-primary small">@<?= $c['slugCategorie'] ?></span></td>
                                        <td>
                                            <span class="users-badge <?= $c['actif'] ? 'users-badge-actif' : 'users-badge-bloque' ?>">
                                                <?= $c['actif'] ? 'Actif' : 'Inactif' ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="users-actions">
                                                <?php if(!$isSubView): ?>
                                                    <a href="<?= $baseUrl ?>/admin/sub-categories?parent=<?= $c['idCategorie'] ?>" class="users-action-btn edit" title="Voir les enfants">
                                                        <i class="bi bi-diagram-3"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <button class="users-action-btn edit" onclick='editCat(<?= json_encode($c) ?>)'><i class="bi bi-pencil"></i></button>
                                                <button class="users-action-btn delete" onclick="confirmDelete(<?= $c['idCategorie'] ?>)"><i class="bi bi-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION -->
                <?php if ($pagination['total'] > 1): ?>
                    <div class="users-pagination-wrapper">
                        <span class="users-pagination-info">
                            Page <strong><?= $pagination['current'] ?></strong> sur <strong><?= $pagination['total'] ?></strong> 
                            (<?= $pagination['totalItems'] ?> catégories)
                        </span>
                        <nav class="users-pagination">
                            <ul class="pagination">
                                <?php for($i=1; $i<=$pagination['total']; $i++): ?>
                                    <li class="page-item <?= $i == $pagination['current'] ? 'active' : '' ?>">
                                        <?php 
                                            $url = $isSubView ? "?parent=".$parent['idCategorie']."&page=".$i : "?page=".$i; 
                                        ?>
                                        <a class="page-link" href="<?= $url ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div> 
    </div> 
</div>

<!-- MODAL AJOUT / MODIF -->
<div class="modal fade users-modal" id="catModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow-lg" action="<?= $baseUrl ?>/admin/category/save" method="POST">
            <?= CSRF::field() ?>
            <input type="hidden" name="id" id="field-id">
            <input type="hidden" name="idCategorieParent" value="<?= $isSubView ? $parent['idCategorie'] : '' ?>">
            
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modal-title-text">Gestion Catégorie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label small fw-bold">Boutique propriétaire</label>
                    <select name="idBoutique" id="field-boutique" class="form-select" required>
                        <option value="">Sélectionner une boutique...</option>
                        <?php foreach($boutiques as $b): ?>
                            <option value="<?= $b['idBoutique'] ?>"><?= $b['nomBoutique'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Nom de la catégorie</label>
                    <input type="text" name="nom" id="field-nom" class="form-control" onkeyup="generateSlug(this.value)" required>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Slug (URL)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted">@</span>
                        <input type="text" name="slug" id="field-slug" class="form-control" required>
                    </div>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-bold">Ordre d'affichage</label>
                    <input type="number" name="ordre" id="field-ordre" class="form-control" value="0">
                </div>
                <div class="col-6">
                    <label class="form-label small fw-bold">Statut</label>
                    <select name="statut" id="field-statut" class="form-select">
                        <option value="1">Actif</option>
                        <option value="0">Inactif</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-primary px-4 shadow">Enregistrer</button>
            </div>
        </form>
    </div>
</div>
<!-- MODAL SUPPRESSION -->
<div class="modal fade" id="deleteCatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <form action="<?= $baseUrl ?>/admin/category/delete" method="POST" class="modal-content border-0 shadow-lg">
            <?= CSRF::field() ?>
            <input type="hidden" name="id_delete" id="delete-cat-id">
            <div class="modal-body text-center p-4">
                <i class="bi bi-exclamation-triangle text-danger display-4 mb-3"></i>
                <h5 class="fw-bold">Supprimer ?</h5>
                <p class="text-muted small">Cette action est irréversible. Les sous-catégories seront conservées mais n'auront plus de parent.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function confirmDelete(id) {
    document.getElementById('delete-cat-id').value = id;
    new bootstrap.Modal(document.getElementById('deleteCatModal')).show();
}
</script>
<script>
function generateSlug(text) {
    const slug = text.toLowerCase()
        .normalize("NFD").replace(/[\u0300-\u036f]/g, "") 
        .replace(/[^\w ]+/g, '') 
        .replace(/ +/g, '-'); 
    document.getElementById('field-slug').value = slug;
}

function resetForm() {
    document.getElementById('modal-title-text').innerText = "Nouvelle catégorie";
    document.getElementById('field-id').value = "";
    document.getElementById('field-nom').value = "";
    document.getElementById('field-slug').value = "";
    document.getElementById('field-ordre').value = "0";
}

function editCat(data) {
    document.getElementById('modal-title-text').innerText = "Modifier catégorie";
    document.getElementById('field-id').value = data.idCategorie;
    document.getElementById('field-nom').value = data.nomCategorie;
    document.getElementById('field-slug').value = data.slugCategorie;
    document.getElementById('field-boutique').value = data.idBoutique;
    document.getElementById('field-statut').value = data.actif;
    document.getElementById('field-ordre').value = data.ordre;
    new bootstrap.Modal(document.getElementById('catModal')).show();
}
</script>