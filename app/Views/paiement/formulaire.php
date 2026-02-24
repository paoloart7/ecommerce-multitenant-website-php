<?php 
$baseUrl = App::baseUrl();
$mode = $mode ?? 'moncash';
$total = $total ?? 0;

$config = [
    'moncash' => [
        'nom' => 'MonCash',
        'logo' => '/assets/images/moncash-logo.png',
        'couleur' => '#E30613',
        'type' => 'mobile',
        'placeholder_numero' => 'Numéro MonCash (8 chiffres)',
        'placeholder_pin' => 'Code PIN (4 chiffres)'
    ],
    'natcash' => [
        'nom' => 'NatCash',
        'logo' => '/assets/images/natcash-logo.png',
        'couleur' => '#004AAD',
        'type' => 'mobile',
        'placeholder_numero' => 'Numéro NatCash (8 chiffres)',
        'placeholder_pin' => 'Code PIN (4 chiffres)'
    ],
    'carte' => [
        'nom' => 'Carte bancaire',
        'logo' => '/assets/images/card.png',
        'couleur' => '#6366f1',
        'type' => 'carte',
        'placeholder_numero' => '1234 5678 9012 3456',
        'placeholder_pin' => null
    ]
];

$current = $config[$mode];
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/paiement.css">

<div class="paiement-container">
    <!-- ✅ Flèche retour -->
    <div class="retour-header">
        <a href="<?= $baseUrl ?>/paiement/choix" class="btn-retour">
            <i class="bi bi-arrow-left"></i> Choisir un autre mode
        </a>
    </div>

    <div class="paiement-form" style="border-top: 4px solid <?= $current['couleur'] ?>">
        <div class="form-header">
            <?php if ($mode === 'carte'): ?>
                <i class="bi bi-credit-card" style="font-size: 3rem; color: <?= $current['couleur'] ?>"></i>
            <?php else: ?>
                <img src="<?= $baseUrl . $current['logo'] ?>" alt="<?= $current['nom'] ?>" class="mode-logo">
            <?php endif; ?>
            <h2>Paiement par <?= $current['nom'] ?></h2>
        </div>

        <?php if ($mode === 'carte'): ?>
            <div class="cartes-acceptees">
                <span>Cartes acceptées :</span>
                <div class="cartes-logos">
                    <img src="<?= $baseUrl ?>/assets/images/cartes/visa.png" 
                        alt="Visa" 
                        class="carte-logo">
                    <img src="<?= $baseUrl ?>/assets/images/cartes/mastercard.png" 
                        alt="Mastercard" 
                        class="carte-logo">
                    <img src="<?= $baseUrl ?>/assets/images/cartes/cb.png" 
                        alt="CB" 
                        class="carte-logo">
                </div>
            </div>
        <?php endif; ?>
        
        <div class="boutique-info">
            <span class="badge">Paiement sécurisé</span>
            <div class="total">Total: <strong><?= number_format($total, 0, ',', ' ') ?> G</strong></div>
        </div>
        
        <form method="POST" action="<?= $baseUrl ?>/paiement/traiter" id="paiementForm">
            <input type="hidden" name="mode" value="<?= $mode ?>">
            <input type="hidden" name="total" value="<?= $total ?>">
            
            <?php if ($mode === 'carte'): ?>
                <!-- ===== FORMULAIRE POUR CARTE BANCAIRE ===== -->
                
                <!-- Numéro de carte -->
                <div class="form-group">
                    <label>
                        <i class="bi bi-credit-card-2-front"></i> Numéro de carte *
                    </label>
                    <input type="text" 
                           name="numero_carte" 
                           placeholder="1234 5678 9012 3456"
                           pattern="[0-9 ]+"
                           minlength="16"
                           maxlength="19"
                           autocomplete="cc-number"
                           inputmode="numeric"
                           required
                           class="carte-input">
                </div>
                
                <!-- Date d'expiration et CVV -->
                <div class="form-row">
                    <div class="form-group half">
                        <label>
                            <i class="bi bi-calendar"></i> Date expiration *
                        </label>
                        <div class="expiration-group">
                            <input type="text" 
                                   name="exp_mois" 
                                   placeholder="MM"
                                   pattern="[0-9]{2}"
                                   minlength="2"
                                   maxlength="2"
                                   inputmode="numeric"
                                   required
                                   class="exp-input">
                            <span class="exp-separator">/</span>
                            <input type="text" 
                                   name="exp_annee" 
                                   placeholder="AA"
                                   pattern="[0-9]{2}"
                                   minlength="2"
                                   maxlength="2"
                                   inputmode="numeric"
                                   required
                                   class="exp-input">
                        </div>
                    </div>
                    
                    <div class="form-group half">
                        <label>
                            <i class="bi bi-shield-lock"></i> CVV *
                        </label>
                        <input type="text" 
                               name="cvv" 
                               placeholder="123"
                               pattern="[0-9]{3,4}"
                               minlength="3"
                               maxlength="4"
                               autocomplete="cc-csc"
                               inputmode="numeric"
                               required
                               class="cvv-input">
                    </div>
                </div>
                
                <!-- Nom sur la carte -->
                <div class="form-group">
                    <label>
                        <i class="bi bi-person"></i> Nom sur la carte *
                    </label>
                    <input type="text" 
                           name="nom_titulaire" 
                           placeholder="JEAN DUPONT"
                           autocomplete="cc-name"
                           required
                           class="carte-input">
                </div>
                
            <?php else: ?>
                <!-- ===== FORMULAIRE POUR MONCASH / NATCASH ===== -->
                
                <div class="form-group">
                    <label>
                        <i class="bi bi-phone"></i> Numéro
                    </label>
                    <div class="input-prefix">
                        <span class="prefix">509 |</span>
                        <input type="text" 
                               name="numero" 
                               placeholder="<?= $current['placeholder_numero'] ?>"
                               pattern="[0-9]{8}"
                               maxlength="8"
                               required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        <i class="bi bi-key"></i> PIN
                    </label>
                    <input type="password" 
                           name="pin" 
                           placeholder="<?= $current['placeholder_pin'] ?>"
                           pattern="[0-9]{4}"
                           maxlength="4"
                           required>
                </div>
            <?php endif; ?>
            
            <button type="submit" class="btn-payer" style="background: <?= $current['couleur'] ?>">
                <?php if ($mode === 'carte'): ?>
                    <i class="bi bi-lock"></i> Payer <?= number_format($total, 0, ',', ' ') ?> G
                <?php else: ?>
                    Payer <?= number_format($total, 0, ',', ' ') ?> G
                <?php endif; ?>
            </button>
        </form>
        
        <div class="secure-badge">
            <i class="bi bi-shield-check"></i>
            Paiement 100% sécurisé
            <?php if ($mode === 'carte'): ?>
                - Vos données sont cryptées
            <?php endif; ?>
        </div>
    </div>
</div>