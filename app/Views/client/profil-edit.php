<?php 
$baseUrl = App::baseUrl();
$client = $client ?? [];
$user = Session::user();

$prenom = $client['prenom'] ?? $user['prenom'] ?? '';
$nom = $client['nom'] ?? $user['nom'] ?? '';
$email = $client['email'] ?? $user['email'] ?? '';
$telephone = $client['telephone'] ?? $user['telephone'] ?? '';
$avatar = $client['avatar'] ?? $user['avatar'] ?? null;
$avatarUrl = $avatar ? $baseUrl . $avatar : null;
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/profil.css">

<div class="profil-container">
    <!-- En-tête avec retour -->
    <div class="profil-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1>Modifier mon profil</h1>
                <p>Mettez à jour vos informations personnelles</p>
            </div>
            <a href="<?= $baseUrl ?>/mon-profil" class="btn-back">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <!-- Formulaire -->
    <div class="profil-edit-card">
        <form method="POST" action="<?= $baseUrl ?>/profil/update" id="editForm">
            <?= CSRF::field() ?>
            
            <div class="form-grid">
                <!-- Colonne gauche -->
                <div class="form-column">
                    <h3>Informations personnelles</h3>
                    
                    <div class="form-group">
                        <label for="prenom">Prénom</label>
                        <input type="text" 
                               id="prenom" 
                               name="prenom" 
                               value="<?= Security::escape($prenom) ?>" 
                               required
                               class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="nom">Nom</label>
                        <input type="text" 
                               id="nom" 
                               name="nom" 
                               value="<?= Security::escape($nom) ?>" 
                               required
                               class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="<?= Security::escape($email) ?>" 
                               required
                               class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="telephone">Téléphone</label>
                        <input type="tel" 
                               id="telephone" 
                               name="telephone" 
                               value="<?= Security::escape($telephone) ?>" 
                               class="form-control"
                               placeholder="+509 XX XX XXXX">
                    </div>
                </div>

                <!-- Colonne droite : Avatar -->
                <div class="form-column">
                    <h3>Photo de profil</h3>
                    
                    <div class="avatar-edit-container">
                        <div class="avatar-preview">
                            <?php if ($avatarUrl): ?>
                                <img src="<?= $avatarUrl ?>" alt="Avatar" id="avatarPreview">
                            <?php else: ?>
                                <div class="avatar-initials-large">
                                    <?= strtoupper(substr($prenom, 0, 1) . substr($nom, 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="avatar-upload">
                            <button type="button" class="btn-upload" onclick="document.getElementById('avatarInput').click()">
                                <i class="bi bi-camera"></i>
                                Changer la photo
                            </button>
                            <input type="file" id="avatarInput" accept="image/jpeg,image/png,image/webp" style="display: none;">
                            <p class="upload-hint">JPG, PNG ou WEBP. Max 2MB.</p>
                        </div>
                    </div>

                    <div class="info-box">
                        <i class="bi bi-info-circle"></i>
                        <small>Les informations de connexion (mot de passe) peuvent être modifiées dans la page dédiée.</small>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="<?= $baseUrl ?>/mon-profil" class="btn-cancel">Annuler</a>
                <button type="submit" class="btn-save">
                    <i class="bi bi-check-lg"></i>
                    Enregistrer les modifications
                </button>
            </div>
        </form>
    </div>
</div>

<script src="<?= $baseUrl ?>/assets/js/profil-edit.js"></script>