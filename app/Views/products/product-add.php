<?php 
$baseUrl = App::baseUrl();
$user = Session::user();
$categories = $categories ?? [];
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
                    <h2 class="fw-bold h4 m-0">➕ Ajouter un produit</h2>
                    <p class="text-muted small mb-0">Créez un nouveau produit dans votre catalogue</p>
                </div>
                <a href="<?= $baseUrl ?>/vendeur/mes-produits" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
            </div>

            <!-- FORMULAIRE -->
            <div class="product-form-card">
                <form method="POST" action="<?= $baseUrl ?>/vendeur/produit/save" enctype="multipart/form-data" id="productForm">
                    <!-- Au lieu de l'input hidden manuel -->
                    <?= CSRF::field() ?>
                    <div class="row">
                        <!-- Colonne gauche (8 colonnes) -->
                        <div class="col-md-8">
                            <!-- Section Informations générales -->
                            <div class="form-section">
                                <h5>📝 Informations générales</h5>
                                
                                <div class="mb-3">
                                    <label class="form-label">Nom du produit <span class="text-danger">*</span></label>
                                    <input type="text" name="nomProduit" id="nomProduit" class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Slug (URL)</label>
                                    <input type="text" name="slugProduit" id="slugProduit" class="form-control">
                                    <small class="text-muted">Généré automatiquement à partir du nom</small>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Catégorie</label>
                                        <select name="idCategorie" class="form-select">
                                            <option value="">-- Sélectionner une catégorie --</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?= $cat['idCategorie'] ?>">
                                                    <?= Security::escape($cat['nomCategorie']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">SKU (Référence)</label>
                                        <input type="text" name="sku" class="form-control">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Description courte</label>
                                    <textarea name="descriptionCourte" class="form-control" rows="2" placeholder="Brève description (max 500 caractères)"></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Description complète</label>
                                    <textarea name="descriptionComplete" class="form-control" rows="4" placeholder="Description détaillée du produit"></textarea>
                                </div>
                            </div>

                            <!-- Section Prix et stock -->
                            <div class="form-section">
                                <h5>💰 Prix et stock</h5>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Prix (G) <span class="text-danger">*</span></label>
                                        <input type="number" name="prix" class="form-control" step="0.01" min="0" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Prix promo</label>
                                        <input type="number" name="prixPromo" class="form-control" step="0.01" min="0">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Coût d'achat</label>
                                        <input type="number" name="cout" class="form-control" step="0.01" min="0">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Début promotion</label>
                                        <input type="date" name="dateDebutPromo" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Fin promotion</label>
                                        <input type="date" name="dateFinPromo" class="form-control">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Stock <span class="text-danger">*</span></label>
                                        <input type="number" name="stock" class="form-control" value="0" min="0" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Stock alerte</label>
                                        <input type="number" name="stockAlerte" class="form-control" value="10" min="0">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Code-barres</label>
                                        <input type="text" name="codeBarres" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <!-- Section Options avancées -->
                            <div class="form-section">
                                <h5>⚙️ Options avancées</h5>
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Poids (kg)</label>
                                        <input type="number" name="poids" class="form-control" step="0.001" min="0">
                                    </div>
                                    <div class="col-md-8">
                                        <div class="row">
                                            <div class="col-4">
                                                <label class="form-label">Longueur (cm)</label>
                                                <input type="number" name="longueur" class="form-control" step="0.1" min="0">
                                            </div>
                                            <div class="col-4">
                                                <label class="form-label">Largeur (cm)</label>
                                                <input type="number" name="largeur" class="form-control" step="0.1" min="0">
                                            </div>
                                            <div class="col-4">
                                                <label class="form-label">Hauteur (cm)</label>
                                                <input type="number" name="hauteur" class="form-control" step="0.1" min="0">
                                            </div>
                                        </div>
                                        <small class="text-muted">Laissez vide si non applicable</small>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input type="checkbox" name="misEnAvant" class="form-check-input" id="featured" value="1">
                                            <label class="form-check-label" for="featured">⭐ Mis en avant</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input type="checkbox" name="nouveaute" class="form-check-input" id="new" value="1">
                                            <label class="form-check-label" for="new">✨ Nouveauté</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Statut</label>
                                        <select name="statutProduit" class="form-select">
                                            <option value="brouillon">Brouillon</option>
                                            <option value="disponible" selected>Disponible</option>
                                            <option value="non_disponible">Non disponible</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Colonne droite (4 colonnes) - Images -->
                        <div class="col-md-4">
                            <div class="form-section">
                                <h5>🖼️ Images du produit</h5>
                                <div class="upload-area" id="dropZone">
                                    <i class="bi bi-cloud-upload"></i>
                                    <p>Glissez vos images ici ou <span>cliquez pour sélectionner</span></p>
                                    <input type="file" name="images[]" id="fileInput" multiple accept="image/jpeg,image/png,image/webp" style="display: none;">
                                    <small class="text-muted">Formats: JPG, PNG, WEBP (max 2MB)</small>
                                </div>
                                <!-- Aperçu des images -->
                                <div class="images-preview" id="imagesPreview"></div>

                                <div class="upload-info mt-2 small text-muted">
                                    <i class="bi bi-info-circle"></i> La première image sera l'image principale
                                </div>
                            </div>

                            <!-- Bouton de soumission -->
                            <div class="form-section">
                                <button type="submit" class="btn btn-primary w-100 py-2">
                                    <i class="bi bi-check-lg"></i> Créer le produit
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
document.getElementById('nomProduit').addEventListener('input', function() {
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
let uploadedFiles = [];

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

function handleFiles(files) {
    Array.from(files).forEach(file => {
        if (!file.type.match('image.*')) return;
        
        uploadedFiles.push(file);
        
        const reader = new FileReader();
        reader.onload = (e) => {
            const previewItem = document.createElement('div');
            previewItem.className = 'preview-item';
            previewItem.innerHTML = `
                <img src="${e.target.result}" alt="Aperçu">
                <button type="button" class="btn-remove" onclick="this.parentElement.remove()">
                    <i class="bi bi-x"></i>
                </button>
            `;
            preview.appendChild(previewItem);
        };
        reader.readAsDataURL(file);
    });
}

// Validation des dates promo
document.querySelector('input[name="dateDebutPromo"]').addEventListener('change', function() {
    const dateFin = document.querySelector('input[name="dateFinPromo"]');
    if (this.value) {
        dateFin.min = this.value;
    }
});
</script>