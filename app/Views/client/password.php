<?php 
$baseUrl = App::baseUrl();
$user = Session::user();
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/profil.css">

<div class="profil-container">
    <!-- En-tête avec retour -->
    <div class="profil-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1>Changer mon mot de passe</h1>
                <p>Sécurisez votre compte en modifiant régulièrement votre mot de passe</p>
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

    <!-- Formulaire de changement de mot de passe -->
    <div class="password-card">
        <div class="password-icon">
            <i class="bi bi-shield-lock"></i>
        </div>
        
        <form method="POST" action="<?= $baseUrl ?>/profil/update-password" id="passwordForm">
            <?= CSRF::field() ?>
            
            <div class="form-group">
                <label for="ancien_mot_de_passe">
                    <i class="bi bi-key"></i>
                    Ancien mot de passe
                </label>
                <input type="password" 
                       id="ancien_mot_de_passe" 
                       name="ancien_mot_de_passe" 
                       required
                       class="form-control"
                       placeholder="Votre mot de passe actuel"
                       autocomplete="current-password">
            </div>

            <div class="form-group">
                <label for="nouveau_mot_de_passe">
                    <i class="bi bi-lock"></i>
                    Nouveau mot de passe
                </label>
                <input type="password" 
                       id="nouveau_mot_de_passe" 
                       name="nouveau_mot_de_passe" 
                       required
                       class="form-control"
                       placeholder="8 caractères minimum"
                       minlength="8"
                       autocomplete="new-password">
                <div class="password-strength" id="passwordStrength">
                    <div class="strength-bar"></div>
                </div>
                <small class="password-hint">
                    Au moins 8 caractères, une majuscule, une minuscule et un chiffre
                </small>
            </div>

            <div class="form-group">
                <label for="confirmation">
                    <i class="bi bi-check-circle"></i>
                    Confirmer le nouveau mot de passe
                </label>
                <input type="password" 
                       id="confirmation" 
                       name="confirmation" 
                       required
                       class="form-control"
                       placeholder="Retapez votre nouveau mot de passe"
                       autocomplete="new-password">
            </div>

            <div class="password-requirements">
                <h4>Exigences :</h4>
                <ul>
                    <li id="req-length" class="requirement unmet">
                        <i class="bi bi-x-circle"></i> Au moins 8 caractères
                    </li>
                    <li id="req-uppercase" class="requirement unmet">
                        <i class="bi bi-x-circle"></i> Au moins une majuscule
                    </li>
                    <li id="req-lowercase" class="requirement unmet">
                        <i class="bi bi-x-circle"></i> Au moins une minuscule
                    </li>
                    <li id="req-number" class="requirement unmet">
                        <i class="bi bi-x-circle"></i> Au moins un chiffre
                    </li>
                    <li id="req-match" class="requirement unmet">
                        <i class="bi bi-x-circle"></i> Les mots de passe correspondent
                    </li>
                </ul>
            </div>

            <div class="form-actions">
                <a href="<?= $baseUrl ?>/mon-profil" class="btn-cancel">Annuler</a>
                <button type="submit" class="btn-save" id="submitBtn" disabled>
                    <i class="bi bi-check-lg"></i>
                    Changer le mot de passe
                </button>
            </div>
        </form>
    </div>
</div>

<script src="<?= $baseUrl ?>/assets/js/password.js"></script>