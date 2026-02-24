<?php 
$baseUrl = App::baseUrl();
$adresses = $adresses ?? [];
$user = Session::user();
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/profil.css">

<div class="profil-container">
    <!-- En-tête avec retour -->
    <div class="profil-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1>Mes adresses</h1>
                <p>Gérez vos adresses de livraison et de facturation</p>
            </div>
            <a href="<?= $baseUrl ?>/mon-profil" class="btn-back">
                <i class="bi bi-arrow-left"></i> Retour au profil
            </a>
        </div>
    </div>

    <!-- Messages flash -->
    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success flash-message" data-duration="4000">
            <i class="bi bi-check-circle-fill"></i>
            <?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger flash-message" data-duration="4000">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <?= $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?>
        </div>
    <?php endif; ?>

    <!-- Bouton ajouter -->
    <div class="text-end mb-4">
        <button class="btn-add" onclick="openAddressModal()">
            <i class="bi bi-plus-lg"></i>
            Ajouter une adresse
        </button>
    </div>

    <!-- Liste des adresses -->
    <div class="addresses-grid">
        <?php if (empty($adresses)): ?>
            <div class="empty-addresses">
                <i class="bi bi-geo-alt"></i>
                <h3>Aucune adresse enregistrée</h3>
                <p>Ajoutez votre première adresse pour faciliter vos commandes</p>
                <button class="btn-add" onclick="openAddressModal()">
                    <i class="bi bi-plus-lg"></i>
                    Ajouter une adresse
                </button>
            </div>
        <?php else: ?>
            <?php foreach ($adresses as $addr): ?>
                <div class="address-card <?= $addr['estDefaut'] ? 'default' : '' ?>">
                    <?php if ($addr['estDefaut']): ?>
                        <span class="default-badge">Adresse par défaut</span>
                    <?php endif; ?>
                    
                    <div class="address-content">
                        <h3><?= Security::escape($addr['nomDestinataire'] ?? $user['prenom'] . ' ' . $user['nom']) ?></h3>
                        
                        <p class="address-line">
                            <i class="bi bi-geo-alt"></i>
                            <?= Security::escape($addr['rue'] ?? '') ?>
                            <?= !empty($addr['complement']) ? ', ' . Security::escape($addr['complement']) : '' ?>
                        </p>
                        
                        <?php if (!empty($addr['quartier'])): ?>
                            <p class="address-line">
                                <i class="bi bi-building"></i>
                                Quartier: <?= Security::escape($addr['quartier']) ?>
                            </p>
                        <?php endif; ?>
                        
                        <p class="address-line">
                            <i class="bi bi-pin-map"></i>
                            <?= Security::escape($addr['ville'] ?? '') ?>
                            <?= !empty($addr['codePostal']) ? ' - ' . Security::escape($addr['codePostal']) : '' ?>
                        </p>
                        
                        <?php if (!empty($addr['telephone'])): ?>
                            <p class="address-line">
                                <i class="bi bi-telephone"></i>
                                <?= Security::escape($addr['telephone']) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="address-actions">
                        <button class="btn-edit" onclick="editAddress(<?= htmlspecialchars(json_encode($addr)) ?>)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="<?= $baseUrl ?>/adresse/delete" class="d-inline delete-form">
                            <?= CSRF::field() ?>
                            <input type="hidden" name="idAdresse" value="<?= $addr['idAdresse'] ?>">
                            <button type="button" class="btn-delete" onclick="confirmDelete(this)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Ajout/Modification Adresse -->
<div class="modal fade" id="addressModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Ajouter une adresse</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= $baseUrl ?>/adresse/save" id="addressForm">
                <?= CSRF::field() ?>
                <input type="hidden" name="idAdresse" id="addressId">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nomDestinataire">Nom du destinataire</label>
                        <input type="text" class="form-control" id="nomDestinataire" name="nomDestinataire" 
                               placeholder="Jean Dupont">
                    </div>
                    
                    <div class="form-group">
                        <label for="telephone">Téléphone</label>
                        <input type="tel" class="form-control" id="telephone" name="telephone" 
                               placeholder="+509 XX XX XXXX">
                    </div>
                    
                    <div class="form-group">
                        <label for="rue">Rue / Avenue <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="rue" name="rue" required 
                               placeholder="Numéro et nom de rue">
                    </div>
                    
                    <div class="form-group">
                        <label for="complement">Complément d'adresse</label>
                        <input type="text" class="form-control" id="complement" name="complement" 
                               placeholder="Appartement, étage, etc.">
                    </div>
                    
                    <div class="form-group">
                        <label for="quartier">Quartier</label>
                        <input type="text" class="form-control" id="quartier" name="quartier" 
                               placeholder="Nom du quartier">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="ville">Ville <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="ville" name="ville" required 
                                       placeholder="Port-au-Prince">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="codePostal">Code postal</label>
                                <input type="text" class="form-control" id="codePostal" name="codePostal" 
                                       placeholder="HT-0000">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-check mt-3">
                        <input type="checkbox" class="form-check-input" id="estDefaut" name="estDefaut">
                        <label class="form-check-label" for="estDefaut">
                            Définir comme adresse par défaut
                        </label>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn-save">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= $baseUrl ?>/assets/js/adresses.js"></script>