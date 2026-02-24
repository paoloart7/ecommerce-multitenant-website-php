<?php
$baseUrl      = App::baseUrl();
$siteName     = App::setting('site_name', 'ShopXPao');
$openRegister = $openRegister ?? false;
$authLogo     = $baseUrl . '/assets/images/shupxpaologo.png';

$error   = Session::getFlash('error');
$success = Session::getFlash('success');
?>
<div class="auth-page">
  <div class="auth-box <?= $openRegister ? 'right-panel-active' : '' ?>" id="authContainer">
    
    <!-- ========== FORMULAIRE CONNEXION ========== -->
    <div class="form-container sign-in-container">
      <form action="<?= $baseUrl ?>/login" method="POST" id="signInForm">
        <?= CSRF::field() ?>
        <div class="form-greeting">
          <span class="form-greeting-emoji">👋</span>
          <h2>Bon retour <span class="form-greeting-dot">!</span></h2>
          <p class="auth-subtitle">Connectez-vous pour continuer</p>
        </div>

        <?php if ($error && !$openRegister): ?>
          <div class="alert alert-danger p-2 mb-3 small"><?= Security::escape($error) ?></div>
        <?php endif; ?>

        <div class="input-group-auth">
          <div class="input-icon-left">
            <i class="bi bi-envelope"></i>
          </div>
          <input type="email" name="email" placeholder="Adresse email" required>
        </div>

        <div class="input-group-auth">
          <div class="input-icon-left">
            <i class="bi bi-lock"></i>
          </div>
          <input type="password" name="password" id="loginPassword" placeholder="Mot de passe" required>
          <button type="button" class="toggle-password" data-target="loginPassword">
            <i class="bi bi-eye-slash"></i>
          </button>
        </div>

        <div class="login-options">
          <label class="remember-me">
            <input type="checkbox" name="remember">
            Se souvenir de moi
          </label>
          <a href="<?= $baseUrl ?>/mot-de-passe-oublie" class="forgot-password">Mot de passe oublié ?</a>
        </div>

        <button type="submit" class="auth-btn w-100">
          <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
        </button>

        <div class="social-login">
          <div class="social-divider">
            <span>ou continuer avec</span>
          </div>
          <div class="social-icons">
            <a href="#" class="social-icon facebook" title="Facebook">
              <i class="bi bi-facebook"></i>
            </a>
            <a href="#" class="social-icon google" title="Google">
              <i class="bi bi-google"></i>
            </a>
            <a href="#" class="social-icon twitter" title="X">
              <i class="bi bi-twitter-x"></i>
            </a>
          </div>
        </div>

        <div class="form-footer">
          <p>Pas encore de compte ? <a href="#" id="goToSignUp">Créer un compte</a></p>
        </div>
      </form>
    </div>

    <!-- ========== FORMULAIRE INSCRIPTION ========== -->
    <div class="form-container sign-up-container">
      <form action="<?= $baseUrl ?>/register" method="POST" id="signUpForm">
        <?= CSRF::field() ?>
        <div class="form-greeting">
          <span class="form-greeting-emoji"></span>
          <h2>Rejoignez-nous <span class="form-greeting-dot">!</span></h2>
          <p class="auth-subtitle">Créez votre compte en quelques secondes</p>
        </div>

        <?php if ($error && $openRegister): ?>
          <div class="alert alert-danger p-2 mb-3 small"><?= Security::escape($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
          <div class="alert alert-success p-2 mb-3 small"><?= Security::escape($success) ?></div>
        <?php endif; ?>

        <div class="row gx-2">
          <div class="col-6">
            <div class="input-group-auth">
              <div class="input-icon-left">
                <i class="bi bi-person"></i>
              </div>
              <input type="text" name="prenom" placeholder="Prénom" required>
            </div>
          </div>
          <div class="col-6">
            <div class="input-group-auth">
              <div class="input-icon-left">
                <i class="bi bi-person-badge"></i>
              </div>
              <input type="text" name="nom" placeholder="Nom" required>
            </div>
          </div>
        </div>

        <div class="input-group-auth">
          <div class="input-icon-left">
            <i class="bi bi-envelope"></i>
          </div>
          <input type="email" name="email" placeholder="Adresse email" required>
        </div>

        <div class="input-group-auth">
          <div class="input-icon-left">
            <i class="bi bi-lock"></i>
          </div>
          <input type="password" name="password" id="regPassword" placeholder="Mot de passe" required>
          <button type="button" class="toggle-password" data-target="regPassword">
            <i class="bi bi-eye-slash"></i>
          </button>
        </div>

        <div class="input-group-auth">
          <div class="input-icon-left">
            <i class="bi bi-lock-fill"></i>
          </div>
          <input type="password" name="password_confirm" id="regPasswordConfirm" placeholder="Confirmer le mot de passe" required>
          <button type="button" class="toggle-password" data-target="regPasswordConfirm">
            <i class="bi bi-eye-slash"></i>
          </button>
        </div>

        <div class="input-group-auth select-wrapper">
          <div class="input-icon-left">
            <i class="bi bi-person-gear"></i>
          </div>
          <select name="type_compte" class="auth-select" required>
            <option value="" disabled selected>Choisir votre profil</option>
            <option value="buyer">Acheteur — Je veux acheter</option>
            <option value="seller">Vendeur — Je veux vendre</option>
          </select>
          <div class="select-arrow">
            <i class="bi bi-chevron-down"></i>
          </div>
        </div>

        <div class="login-options">
          <label class="remember-me">
            <input type="checkbox" name="terms" required>
            J'accepte les <a href="#" class="link-terms">conditions d'utilisation</a>
          </label>
        </div>

        <button type="submit" class="auth-btn w-100">
          <i class="bi bi-person-plus me-2"></i>Créer mon compte
        </button>

        <div class="social-login">
          <div class="social-divider">
            <span>ou s'inscrire avec</span>
          </div>
          <div class="social-icons">
            <a href="#" class="social-icon facebook"><i class="bi bi-facebook"></i></a>
            <a href="#" class="social-icon google"><i class="bi bi-google"></i></a>
            <a href="#" class="social-icon twitter"><i class="bi bi-twitter-x"></i></a>
          </div>
        </div>

        <div class="form-footer">
          <p>Déjà un compte ? <a href="#" id="goToSignIn">Se connecter</a></p>
        </div>
      </form>
    </div>

    <!-- ========== PANNEAU TOGGLE ========== -->
    <div class="toggle-container">
      <div class="toggle">
        
        <!-- Panneau gauche (visible quand inscription active) -->
        <div class="toggle-panel toggle-left">
          <div class="toggle-logo">
            <img src="<?= $authLogo ?>" alt="<?= Security::escape($siteName) ?>">
          </div>
          <div class="toggle-greeting">
            <span class="greeting-emoji"></span>
            <h2><span class="greeting-dot">...</span></h2>
          </div>
          <p>Retrouvez votre espace, vos commandes et vos favoris.</p>
          <div class="toggle-gallery">
            <div class="gallery-item gallery-large">
              <img src="<?= $baseUrl ?>/assets/images/auth/return-1.jpg" alt="Dashboard">
            </div>
            <div class="gallery-item">
              <img src="<?= $baseUrl ?>/assets/images/auth/return-2.jpg" alt="Boutique">
            </div>
            <div class="gallery-item">
              <img src="<?= $baseUrl ?>/assets/images/auth/return-3.jpg" alt="Clients">
            </div>
          </div>
        </div>

        <!-- Panneau droit (visible quand connexion active) -->
        <div class="toggle-panel toggle-right">
          <div class="toggle-logo">
            <img src="<?= $authLogo ?>" alt="<?= Security::escape($siteName) ?>">
          </div>
          <div class="toggle-greeting">
            <span class="greeting-emoji"></span>
            <h2>Rejoignez-nous<span class="greeting-dot">.</span></h2>
          </div>
          <p class="toggle-brand-line">
            Bienvenue sur <span class="brand-highlight"><?= Security::escape($siteName) ?></span>
          </p>
          <p>La marketplace haïtienne qui connecte vendeurs et acheteurs.</p>
          <ul class="toggle-features">
            <li>
              <div class="feature-icon"><i class="bi bi-shop-window"></i></div>
              <span>Ouvrez votre boutique en quelques clics</span>
            </li>
            <li>
              <div class="feature-icon"><i class="bi bi-phone"></i></div>
              <span>Paiements mobiles sécurisés</span>
            </li>
            <li>
              <div class="feature-icon"><i class="bi bi-globe2"></i></div>
              <span>Clients partout en Haïti</span>
            </li>
          </ul>
        </div>

      </div>
    </div>

  </div>
</div>