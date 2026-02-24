<?php 
$baseUrl = App::baseUrl();
$user = Session::user();
$settings = $settings ?? [];
$tenant = $tenant ?? [];
$activeTab = $_GET['tab'] ?? 'general';
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/shop-settings.css">

<div class="tenant-layout">
    <?php require dirname(__DIR__) . '/tenant/partials/sidebar.php'; ?>
    
    <div class="tenant-main">
        <?php require dirname(__DIR__) . '/tenant/partials/topbar.php'; ?>

        <div class="tenant-content container-fluid p-4">
            
            <!-- Messages flash -->
            <?php if (isset($_SESSION['flash_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- EN-TÊTE -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold h4 m-0">⚙️ Paramètres boutique</h2>
                    <p class="text-muted small mb-0">Configurez tous les aspects de votre boutique</p>
                </div>
                <div>
                    <span class="badge bg-primary-soft text-primary px-3 py-2">
                        <i class="bi bi-shop"></i> <?= Security::escape($settings['nomBoutique'] ?? 'Ma boutique') ?>
                    </span>
                </div>
            </div>

            <!-- ONGLETS -->
            <ul class="nav nav-tabs settings-tabs mb-4" id="settingsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $activeTab == 'general' ? 'active' : '' ?>" 
                            id="general-tab" data-bs-toggle="tab" data-bs-target="#general" 
                            type="button" role="tab" aria-controls="general" aria-selected="true">
                        <i class="bi bi-shop"></i> Général
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $activeTab == 'media' ? 'active' : '' ?>" 
                            id="media-tab" data-bs-toggle="tab" data-bs-target="#media" 
                            type="button" role="tab" aria-controls="media" aria-selected="false">
                        <i class="bi bi-images"></i> Médias
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $activeTab == 'social' ? 'active' : '' ?>" 
                            id="social-tab" data-bs-toggle="tab" data-bs-target="#social" 
                            type="button" role="tab" aria-controls="social" aria-selected="false">
                        <i class="bi bi-share"></i> Réseaux sociaux
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $activeTab == 'policies' ? 'active' : '' ?>" 
                            id="policies-tab" data-bs-toggle="tab" data-bs-target="#policies" 
                            type="button" role="tab" aria-controls="policies" aria-selected="false">
                        <i class="bi bi-file-text"></i> Politiques
                    </button>
                </li>
            </ul>

            <!-- CONTENU DES ONGLETS -->
            <div class="tab-content" id="settingsTabContent">
                
                <!-- ONGLET GÉNÉRAL -->
                <div class="tab-pane fade <?= $activeTab == 'general' ? 'show active' : '' ?>" 
                     id="general" role="tabpanel" aria-labelledby="general-tab">
                    
                    <form method="POST" action="<?= $baseUrl ?>/vendeur/parametres/update">
                        <?= CSRF::field() ?>
                        <input type="hidden" name="tab" value="general">
                        
                        <div class="settings-card">
                            <h5 class="card-title">Informations générales</h5>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nom de la boutique</label>
                                    <input type="text" class="form-control" 
                                           value="<?= Security::escape($settings['nomBoutique'] ?? '') ?>" readonly>
                                    <small class="text-muted">Non modifiable - contacter l'admin</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Slug (URL)</label>
                                    <input type="text" class="form-control" 
                                           value="<?= Security::escape($settings['slugBoutique'] ?? '') ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description de la boutique</label>
                                <textarea name="description" class="form-control" rows="4"><?= Security::escape($settings['descriptionBoutique'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Email de contact</label>
                                    <input type="email" name="emailContact" class="form-control" 
                                           value="<?= Security::escape($settings['emailContact'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Téléphone</label>
                                    <input type="text" name="telephoneContact" class="form-control" 
                                           value="<?= Security::escape($settings['telephoneContact'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Adresse physique</label>
                                <textarea name="adressePhysique" class="form-control" rows="2"><?= Security::escape($settings['adressePhysique'] ?? '') ?></textarea>
                            </div>
                            
                            <hr>
                            
                            <h5 class="card-title mt-4">Configuration commerciale</h5>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Devise</label>
                                    <select name="devise" class="form-select">
                                        <option value="HTG" <?= ($settings['devise'] ?? 'HTG') == 'HTG' ? 'selected' : '' ?>>HTG - Gourde</option>
                                        <option value="USD" <?= ($settings['devise'] ?? '') == 'USD' ? 'selected' : '' ?>>USD - Dollar</option>
                                        <option value="EUR" <?= ($settings['devise'] ?? '') == 'EUR' ? 'selected' : '' ?>>EUR - Euro</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Taux de taxe (%)</label>
                                    <input type="number" name="taxe" class="form-control" step="0.1" min="0" max="100"
                                           value="<?= $settings['taxe'] ?? 10 ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Statut</label>
                                    <input type="text" class="form-control" 
                                           value="<?= ucfirst($settings['statut'] ?? 'en_attente') ?>" readonly>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <h5 class="card-title mt-4">Apparence</h5>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Couleur primaire</label>
                                    <input type="color" name="couleurPrimaire" class="form-control form-control-color" 
                                           value="<?= $settings['couleurPrimaire'] ?? '#6366f1' ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Couleur secondaire</label>
                                    <input type="color" name="couleurSecondaire" class="form-control form-control-color" 
                                           value="<?= $settings['couleurSecondaire'] ?? '#4f46e5' ?>">
                                </div>
                            </div>
                            
                            <div class="text-end mt-4">
                                <button type="submit" class="btn btn-primary px-5">
                                    <i class="bi bi-check-lg"></i> Enregistrer
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- ONGLET MÉDIAS -->
                <div class="tab-pane fade <?= $activeTab == 'media' ? 'show active' : '' ?>" 
                     id="media" role="tabpanel" aria-labelledby="media-tab">
                    
                    <div class="settings-card">
                        <h5 class="card-title">Logo de la boutique</h5>
                        
                        <div class="row mb-4">
                            <div class="col-md-8">
                                <div class="upload-area" id="logoUploadArea">
                                    <i class="bi bi-cloud-upload fs-1"></i>
                                    <p class="mb-1">Glissez votre logo ici ou <span class="text-primary">cliquez pour sélectionner</span></p>
                                    <input type="file" id="logoInput" accept="image/jpeg,image/png,image/webp,image/svg+xml" style="display: none;">
                                    <small class="text-muted">Formats: JPG, PNG, WEBP, SVG (max 2MB)</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="logo-preview" id="logoPreview">
                                    <?php if (!empty($settings['logo'])): ?>
                                        <img src="<?= $baseUrl . $settings['logo'] ?>" alt="Logo" class="img-fluid">
                                    <?php else: ?>
                                        <div class="no-logo">
                                            <i class="bi bi-image"></i>
                                            <span>Aucun logo</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h5 class="card-title mt-4">Bannière de la boutique</h5>
                        
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <div class="upload-area" id="bannerUploadArea">
                                    <i class="bi bi-cloud-upload fs-1"></i>
                                    <p class="mb-1">Glissez votre bannière ici ou <span class="text-primary">cliquez pour sélectionner</span></p>
                                    <input type="file" id="bannerInput" accept="image/jpeg,image/png,image/webp" style="display: none;">
                                    <small class="text-muted">Formats: JPG, PNG, WEBP (max 5MB, 1200x300px recommandé)</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="banner-preview" id="bannerPreview">
                                    <?php if (!empty($settings['banniere'])): ?>
                                        <img src="<?= $baseUrl . $settings['banniere'] ?>" alt="Bannière" class="img-fluid">
                                    <?php else: ?>
                                        <div class="no-banner">
                                            <i class="bi bi-image"></i>
                                            <span>Aucune bannière</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- ONGLET RÉSEAUX SOCIAUX -->
                <div class="tab-pane fade <?= $activeTab == 'social' ? 'show active' : '' ?>" 
                     id="social" role="tabpanel" aria-labelledby="social-tab">
                    
                    <form method="POST" action="<?= $baseUrl ?>/vendeur/parametres/update">
                        <?= CSRF::field() ?>
                        <input type="hidden" name="tab" value="social">
                        
                        <div class="settings-card">
                            <h5 class="card-title">Réseaux sociaux</h5>
                            
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="bi bi-facebook text-primary me-2"></i> Facebook
                                </label>
                                <input type="url" name="facebook" class="form-control" 
                                       placeholder="https://facebook.com/votrepage"
                                       value="<?= Security::escape($settings['reseauxSociaux']['facebook'] ?? '') ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="bi bi-instagram text-danger me-2"></i> Instagram
                                </label>
                                <input type="url" name="instagram" class="form-control" 
                                       placeholder="https://instagram.com/votrecompte"
                                       value="<?= Security::escape($settings['reseauxSociaux']['instagram'] ?? '') ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="bi bi-twitter-x text-dark me-2"></i> X (Twitter)
                                </label>
                                <input type="url" name="twitter" class="form-control" 
                                       placeholder="https://twitter.com/votrecompte"
                                       value="<?= Security::escape($settings['reseauxSociaux']['twitter'] ?? '') ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="bi bi-whatsapp text-success me-2"></i> WhatsApp
                                </label>
                                <input type="text" name="whatsapp" class="form-control" 
                                       placeholder="+509 XX XX XXXX"
                                       value="<?= Security::escape($settings['reseauxSociaux']['whatsapp'] ?? '') ?>">
                            </div>
                            
                            <div class="text-end mt-4">
                                <button type="submit" class="btn btn-primary px-5">
                                    <i class="bi bi-check-lg"></i> Enregistrer
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- ONGLET POLITIQUES -->
                <div class="tab-pane fade <?= $activeTab == 'policies' ? 'show active' : '' ?>" 
                     id="policies" role="tabpanel" aria-labelledby="policies-tab">
                    
                    <form method="POST" action="<?= $baseUrl ?>/vendeur/parametres/update">
                        <?= CSRF::field() ?>
                        <input type="hidden" name="tab" value="policies">
                        
                        <div class="settings-card">
                            <h5 class="card-title">Politique de retour</h5>
                            <div class="mb-4">
                                <textarea name="politiqueRetour" class="form-control" rows="6"><?= Security::escape($settings['politiqueRetour'] ?? '') ?></textarea>
                                <small class="text-muted">Expliquez vos conditions de retour aux clients</small>
                            </div>
                            
                            <h5 class="card-title mt-4">Conditions générales de vente</h5>
                            <div class="mb-4">
                                <textarea name="conditionsVente" class="form-control" rows="6"><?= Security::escape($settings['conditionsVente'] ?? '') ?></textarea>
                                <small class="text-muted">Les conditions que les clients acceptent en passant commande</small>
                            </div>
                            
                            <div class="text-end mt-4">
                                <button type="submit" class="btn btn-primary px-5">
                                    <i class="bi bi-check-lg"></i> Enregistrer
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= $baseUrl ?>/assets/js/shop-settings.js"></script>