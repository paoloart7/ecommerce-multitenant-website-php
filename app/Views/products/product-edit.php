<?php 
$baseUrl = App::baseUrl();
$user = Session::user();
$categories = $categories ?? [];
$product = $product ?? [];

$dimensions = [];
if (!empty($product['dimensions'])) {
    $dimensions = json_decode($product['dimensions'], true);
}
$longueur = $dimensions['longueur'] ?? '';
$largeur = $dimensions['largeur'] ?? '';
$hauteur = $dimensions['hauteur'] ?? '';
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/my-products.css">

<div class="tenant-layout">
    <?php require dirname(__DIR__) . '/tenant/partials/sidebar.php'; ?>
    
    <div class="tenant-main">
        <?php require dirname(__DIR__) . '/tenant/partials/topbar.php'; ?>

        <div class="tenant-content container-fluid p-4">
            
            <!-- Message de succès flash -->
            <?php if (isset($_SESSION['flash_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Message d'erreur flash -->
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
                    <h2 class="fw-bold h4 m-0">✏️ Modifier le produit</h2>
                    <p class="text-muted small mb-0"><?= Security::escape($product['nomProduit'] ?? '') ?></p>
                </div>
                <a href="<?= $baseUrl ?>/vendeur/mes-produits" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
            </div>

            <!-- FORMULAIRE DE MODIFICATION -->
            <div class="product-form-card">
                <form method="POST" action="<?= $baseUrl ?>/vendeur/produit/update" enctype="multipart/form-data" id="productForm">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="idProduit" value="<?= $product['idProduit'] ?? '' ?>">
                    
                    <div class="row">
                        <!-- Colonne gauche (8 colonnes) -->
                        <div class="col-md-8">
                            <!-- Section Informations générales -->
                            <div class="form-section">
                                <h5>📝 Informations générales</h5>
                                
                                <div class="mb-3">
                                    <label class="form-label">Nom du produit <span class="text-danger">*</span></label>
                                    <input type="text" name="nomProduit" id="nomProduit" class="form-control" 
                                           value="<?= Security::escape($product['nomProduit'] ?? '') ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Slug (URL)</label>
                                    <input type="text" name="slugProduit" id="slugProduit" class="form-control" 
                                           value="<?= Security::escape($product['slugProduit'] ?? '') ?>">
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Catégorie</label>
                                        <select name="idCategorie" class="form-select">
                                            <option value="">-- Sélectionner une catégorie --</option>
                                            <?php if (!empty($categories)): ?>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?= $cat['idCategorie'] ?>" 
                                                        <?= ($product['idCategorie'] ?? '') == $cat['idCategorie'] ? 'selected' : '' ?>>
                                                        <?= Security::escape($cat['nomCategorie']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">SKU (Référence)</label>
                                        <input type="text" name="sku" class="form-control" 
                                               value="<?= Security::escape($product['sku'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Description courte</label>
                                    <textarea name="descriptionCourte" class="form-control" rows="2"><?= Security::escape($product['descriptionCourte'] ?? '') ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Description complète</label>
                                    <textarea name="descriptionComplete" class="form-control" rows="4"><?= Security::escape($product['descriptionComplete'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <!-- Section Prix et stock -->
                            <div class="form-section">
                                <h5>💰 Prix et stock</h5>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Prix (G) <span class="text-danger">*</span></label>
                                        <input type="number" name="prix" class="form-control" step="0.01" min="0" 
                                               value="<?= $product['prix'] ?? '' ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Prix promo</label>
                                        <input type="number" name="prixPromo" class="form-control" step="0.01" min="0" 
                                               value="<?= $product['prixPromo'] ?? '' ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Coût d'achat</label>
                                        <input type="number" name="cout" class="form-control" step="0.01" min="0" 
                                               value="<?= $product['cout'] ?? '' ?>">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Début promotion</label>
                                        <input type="date" name="dateDebutPromo" class="form-control" 
                                               value="<?= $product['dateDebutPromo'] ?? '' ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Fin promotion</label>
                                        <input type="date" name="dateFinPromo" class="form-control" 
                                               value="<?= $product['dateFinPromo'] ?? '' ?>">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Stock <span class="text-danger">*</span></label>
                                        <input type="number" name="stock" class="form-control" min="0" 
                                               value="<?= $product['stock'] ?? 0 ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Stock alerte</label>
                                        <input type="number" name="stockAlerte" class="form-control" min="0" 
                                               value="<?= $product['stockAlerte'] ?? 10 ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Code-barres</label>
                                        <input type="text" name="codeBarres" class="form-control" 
                                               value="<?= Security::escape($product['codeBarres'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Section Options avec DIMENSIONS CORRIGÉES -->
                            <div class="form-section">
                                <h5>⚙️ Options</h5>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Poids (kg)</label>
                                        <input type="number" name="poids" class="form-control" step="0.001" min="0" 
                                               value="<?= $product['poids'] ?? '' ?>">
                                    </div>
                                    <div class="col-md-8">
                                        <div class="row">
                                            <div class="col-4">
                                                <label class="form-label">Longueur (cm)</label>
                                                <input type="number" name="longueur" class="form-control" step="0.1" min="0" 
                                                       value="<?= $longueur ?>">
                                            </div>
                                            <div class="col-4">
                                                <label class="form-label">Largeur (cm)</label>
                                                <input type="number" name="largeur" class="form-control" step="0.1" min="0" 
                                                       value="<?= $largeur ?>">
                                            </div>
                                            <div class="col-4">
                                                <label class="form-label">Hauteur (cm)</label>
                                                <input type="number" name="hauteur" class="form-control" step="0.1" min="0" 
                                                       value="<?= $hauteur ?>">
                                            </div>
                                        </div>
                                        <small class="text-muted">Laissez vide si non applicable</small>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input type="checkbox" name="misEnAvant" class="form-check-input" id="featured" value="1"
                                                   <?= ($product['misEnAvant'] ?? 0) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="featured">⭐ Mis en avant</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input type="checkbox" name="nouveaute" class="form-check-input" id="new" value="1"
                                                   <?= ($product['nouveaute'] ?? 0) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="new">✨ Nouveauté</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Statut</label>
                                        <select name="statutProduit" class="form-select">
                                            <option value="brouillon" <?= ($product['statutProduit'] ?? '') == 'brouillon' ? 'selected' : '' ?>>Brouillon</option>
                                            <option value="disponible" <?= ($product['statutProduit'] ?? '') == 'disponible' ? 'selected' : '' ?>>Disponible</option>
                                            <option value="non_disponible" <?= ($product['statutProduit'] ?? '') == 'non_disponible' ? 'selected' : '' ?>>Non disponible</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Colonne droite (4 colonnes) - Images CORRIGÉES -->
                        <div class="col-md-4">
                            <div class="form-section">
                                <h5>🖼️ Images du produit</h5>
                                
                                <!-- Images existantes -->
                                <?php if (!empty($product['images'])): ?>
                                <div class="existing-images mb-3">
                                    <label class="form-label small fw-bold">Images actuelles</label>
                                    <div class="row g-2">
                                        <?php foreach ($product['images'] as $image): ?>
                                        <div class="col-6">
                                            <div class="image-item position-relative <?= $image['estPrincipale'] ? 'border border-warning' : '' ?>" 
                                                 data-id="<?= $image['idImage'] ?>">
                                                <img src="<?= $baseUrl . $image['urlImage'] ?>" 
                                                     alt="Product" 
                                                     class="img-fluid rounded"
                                                     style="width: 100%; height: 100px; object-fit: cover;">
                                                <div class="image-actions mt-1 d-flex justify-content-center gap-2">
                                                    <?php if (!$image['estPrincipale']): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-warning btn-set-principal" 
                                                            data-id="<?= $image['idImage'] ?>" title="Définir comme principale">
                                                        <i class="bi bi-star"></i>
                                                    </button>
                                                    <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Principale</span>
                                                    <?php endif; ?>
                                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-image" 
                                                            data-id="<?= $image['idImage'] ?>" title="Supprimer">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Upload nouvelles images -->
                                <div class="upload-area" id="dropZone">
                                    <i class="bi bi-cloud-upload fs-1"></i>
                                    <p class="mb-1">Glissez vos images ici ou <span class="text-primary">cliquez pour sélectionner</span></p>
                                    <input type="file" name="images[]" id="fileInput" multiple accept="image/jpeg,image/png,image/webp" style="display: none;">
                                    <small class="text-muted">Formats: JPG, PNG, WEBP (max 2MB)</small>
                                </div>

                                <!-- Aperçu des nouvelles images -->
                                <div class="row g-2 mt-3" id="imagesPreview"></div>
                            </div>

                            <!-- Bouton de soumission -->
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
// Auto-génération du slug
document.getElementById('nomProduit')?.addEventListener('input', function() {
    const slug = this.value
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');
    document.getElementById('slugProduit').value = slug;
});

// Upload d'images
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const preview = document.getElementById('imagesPreview');
const productId = <?= $product['idProduit'] ?? 0 ?>;

if (dropZone && fileInput) {
    dropZone.addEventListener('click', () => fileInput.click());

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('dragover');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        handleFiles(e.dataTransfer.files);
    });

    fileInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });
}

function handleFiles(files) {
    Array.from(files).forEach(file => {
        if (!file.type.match('image.*')) return;
        
        const reader = new FileReader();
        reader.onload = (e) => {
            const col = document.createElement('div');
            col.className = 'col-6';
            col.innerHTML = `
                <div class="preview-item position-relative">
                    <img src="${e.target.result}" class="img-fluid rounded" style="width: 100%; height: 80px; object-fit: cover;">
                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0" 
                            onclick="this.closest('.col-6').remove()" style="font-size: 10px; padding: 2px 5px;">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            `;
            preview.appendChild(col);
        };
        reader.readAsDataURL(file);
    });
}

document.querySelectorAll('.btn-delete-image').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const item = this.closest('.col-6');
        
        if (confirm('Supprimer cette image ?')) {
            fetch('<?= $baseUrl ?>/vendeur/produit/delete-image', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'csrf_token=<?= CSRF::getToken() ?>&idImage=' + id
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    item.remove();
                }
            });
        }
    });
});

// Définir comme image principale
document.querySelectorAll('.btn-set-principal').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        
        fetch('<?= $baseUrl ?>/vendeur/produit/set-principal', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'csrf_token=<?= CSRF::getToken() ?>&idImage=' + id + '&idProduit=' + productId
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    });
});
</script>