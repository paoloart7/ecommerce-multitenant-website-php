<?php 
$baseUrl = App::baseUrl();
$user = Session::user();
$category = $category ?? [];
$parent = $parent ?? [];
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/categories.css">

<div class="tenant-layout">
    <?php require dirname(__DIR__) . '/partials/sidebar.php'; ?>
    
    <div class="tenant-main">
        <?php require dirname(__DIR__) . '/partials/topbar.php'; ?>

        <div class="tenant-content container-fluid p-4">
            
            <!-- EN-TÊTE -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold h4 m-0">✏️ Modifier la sous-catégorie</h2>
                    <p class="text-muted small mb-0">
                        Catégorie parente : <span class="text-primary fw-bold"><?= Security::escape($parent['nomCategorie'] ?? '') ?></span>
                    </p>
                </div>
                <a href="<?= $baseUrl ?>/vendeur/sous-categories?parent=<?= $parent['idCategorie'] ?? 0 ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Retour aux sous-catégories
                </a>
            </div>

            <!-- FORMULAIRE -->
            <div class="categories-card">
                <form method="POST" action="<?= $baseUrl ?>/vendeur/categorie/update">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="idCategorie" value="<?= $category['idCategorie'] ?? '' ?>">
                    <input type="hidden" name="idCategorieParent" value="<?= $parent['idCategorie'] ?? '' ?>">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-section">
                                <h5>Informations de la sous-catégorie</h5>
                                
                                <div class="mb-3">
                                    <label class="form-label">Nom de la sous-catégorie <span class="text-danger">*</span></label>
                                    <input type="text" name="nomCategorie" id="nomCategorie" class="form-control" 
                                           value="<?= Security::escape($category['nomCategorie'] ?? '') ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Slug (URL)</label>
                                    <input type="text" name="slugCategorie" id="slugCategorie" class="form-control" 
                                           value="<?= Security::escape($category['slugCategorie'] ?? '') ?>">
                                    <small class="text-muted">Généré automatiquement à partir du nom</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" rows="3"><?= Security::escape($category['description'] ?? '') ?></textarea>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Ordre d'affichage</label>
                                        <input type="number" name="ordre" class="form-control" value="<?= $category['ordre'] ?? 0 ?>" min="0">
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mt-4">
                                            <input type="checkbox" name="actif" class="form-check-input" id="actif" 
                                                   <?= ($category['actif'] ?? 1) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="actif">Sous-catégorie active</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Informations supplémentaires (lecture seule) -->
                                <div class="alert alert-info mt-3">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Cette sous-catégorie appartient à <strong><?= Security::escape($parent['nomCategorie'] ?? '') ?></strong>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-section">
                                <h5>Image (optionnel)</h5>
                                <div class="upload-area" id="imageUploadArea">
                                    <i class="bi bi-cloud-upload fs-1"></i>
                                    <p class="mb-1">Glissez une image ou <span class="text-primary">cliquez</span></p>
                                    <input type="file" id="imageInput" accept="image/*" style="display: none;">
                                    <small class="text-muted">PNG, JPG, WEBP (max 2MB)</small>
                                </div>
                                <div class="image-preview mt-3" id="imagePreview" style="display: none;"></div>
                            </div>

                            <div class="form-section">
                                <button type="submit" class="btn btn-primary w-100 py-2">
                                    <i class="bi bi-check-lg"></i> Mettre à jour
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('nomCategorie')?.addEventListener('input', function() {
    const slug = this.value
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');
    document.getElementById('slugCategorie').value = slug;
});
</script>