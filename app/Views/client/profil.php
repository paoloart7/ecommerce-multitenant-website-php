<?php 
$baseUrl = App::baseUrl();
$client = $client ?? [];
$stats = $stats ?? [];
$user = Session::user();

// Générer les initiales
$prenom = $client['prenom'] ?? $user['prenom'] ?? '';
$nom = $client['nom'] ?? $user['nom'] ?? '';
$initials = strtoupper(substr($prenom, 0, 1) . substr($nom, 0, 1)) ?: 'U';
$avatar = $client['avatar'] ?? $user['avatar'] ?? null;
$avatarUrl = $avatar ? $baseUrl . $avatar : null;
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/profil.css">

<div class="profil-container">
    <!-- En-tête -->
    <div class="profil-header">
        <h1>Mon profil</h1>
        <p>Gérez vos informations personnelles et vos préférences</p>
    </div>
    <!-- Messages flash avec durée de 4 secondes -->
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

    <div class="profil-grid">
        <!-- Colonne gauche : Avatar et infos rapides -->
        <div class="profil-sidebar">
            <div class="avatar-card">
                <div class="avatar-container">
                    <?php if ($avatarUrl): ?>
                        <img src="<?= $avatarUrl ?>" alt="Avatar" id="profileAvatar">
                    <?php else: ?>
                        <div class="avatar-initials"><?= $initials ?></div>
                    <?php endif; ?>
                    
                    <button class="avatar-upload-btn" onclick="document.getElementById('avatarInput').click()">
                        <i class="bi bi-camera"></i>
                    </button>
                    <input type="file" id="avatarInput" accept="image/jpeg,image/png,image/webp" style="display: none;">
                </div>
                
                <h3><?= Security::escape($prenom . ' ' . $nom) ?></h3>
                <p class="client-email"><?= Security::escape($client['email'] ?? $user['email'] ?? '') ?></p>
                
                <div class="profil-stats-mini">
                    <div class="stat-item">
                        <span class="stat-value"><?= $stats['total_commandes'] ?? 0 ?></span>
                        <span class="stat-label">Commandes</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?= number_format($stats['total_depense'] ?? 0, 0, ',', ' ') ?></span>
                        <span class="stat-label">Dépensé (G)</span>
                    </div>
                </div>
            </div>

            <!-- Navigation rapide -->
            <div class="profil-nav">
                <a href="<?= $baseUrl ?>/mon-profil" class="nav-link active">
                    <i class="bi bi-person"></i>
                    Mon profil
                </a>
                <a href="<?= $baseUrl ?>/profil/modifier" class="nav-link">
                    <i class="bi bi-pencil"></i>
                    Modifier mes informations
                </a>
                <a href="<?= $baseUrl ?>/mes-adresses" class="nav-link">
                    <i class="bi bi-geo-alt"></i>
                    Mes adresses
                </a>
                <a href="<?= $baseUrl ?>/profil/mot-de-passe" class="nav-link">
                    <i class="bi bi-key"></i>
                    Changer mon mot de passe
                </a>
                <a href="<?= $baseUrl ?>/mes-commandes" class="nav-link">
                    <i class="bi bi-bag"></i>
                    Mes commandes
                </a>
            </div>
        </div>

        <!-- Colonne droite : Informations détaillées -->
        <div class="profil-content">
            <div class="info-card">
                <h2>Informations personnelles</h2>
                
                <div class="info-grid">
                    <div class="info-row">
                        <span class="info-label">Prénom</span>
                        <span class="info-value"><?= Security::escape($prenom) ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Nom</span>
                        <span class="info-value"><?= Security::escape($nom) ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Email</span>
                        <span class="info-value"><?= Security::escape($client['email'] ?? $user['email'] ?? '') ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Téléphone</span>
                        <span class="info-value"><?= Security::escape($client['telephone'] ?? $user['telephone'] ?? 'Non renseigné') ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Membre depuis</span>
                        <span class="info-value"><?= date('d/m/Y', strtotime($client['dateCreation'] ?? $user['dateCreation'] ?? '')) ?></span>
                    </div>
                </div>
                
                <a href="<?= $baseUrl ?>/profil/modifier" class="btn-edit-profile">
                    <i class="bi bi-pencil"></i>
                    Modifier
                </a>
            </div>

            <div class="stats-card">
                <h2>Statistiques</h2>
                
                <div class="stats-grid">
                    <div class="stat-large">
                        <span class="stat-large-value"><?= $stats['total_commandes'] ?? 0 ?></span>
                        <span class="stat-large-label">Commandes totales</span>
                    </div>
                    
                    <div class="stat-large">
                        <span class="stat-large-value"><?= $stats['commandes_en_cours'] ?? 0 ?></span>
                        <span class="stat-large-label">En cours</span>
                    </div>
                    
                    <div class="stat-large">
                        <span class="stat-large-value"><?= $stats['commandes_livrees'] ?? 0 ?></span>
                        <span class="stat-large-label">Livrées</span>
                    </div>
                    
                    <div class="stat-large">
                        <span class="stat-large-value"><?= number_format($stats['total_depense'] ?? 0, 0, ',', ' ') ?></span>
                        <span class="stat-large-label">Total dépensé (G)</span>
                    </div>
                    
                    <div class="stat-large">
                        <span class="stat-large-value"><?= number_format($stats['panier_moyen'] ?? 0, 0, ',', ' ') ?></span>
                        <span class="stat-large-label">Panier moyen (G)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= $baseUrl ?>/assets/js/profil.js"></script>

<script>
    // Version avec animation et progression
    document.addEventListener('DOMContentLoaded', function() {
        const flashMessages = document.querySelectorAll('.flash-message');
        
        flashMessages.forEach(message => {
            const duration = parseInt(message.dataset.duration) || 4000;
            
            // Ajouter une barre de progression
            const progress = document.createElement('div');
            progress.className = 'flash-progress';
            message.appendChild(progress);
            
            // Animation de la barre
            progress.style.animation = `shrink ${duration}ms linear forwards`;
            
            // Disparition du message
            setTimeout(() => {
                message.style.animation = 'slideOut 0.3s ease forwards';
                setTimeout(() => message.remove(), 300);
            }, duration);
        });
    });
</script>