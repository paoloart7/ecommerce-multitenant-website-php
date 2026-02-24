<?php 
$baseUrl = App::baseUrl();
$paiement = $paiement ?? [];
$mode = $paiement['mode'] ?? 'moncash';

$config = [
    'moncash' => ['couleur' => '#E30613', 'logo' => '/assets/images/moncash-logo.png', 'nom' => 'MonCash'],
    'natcash' => ['couleur' => '#004AAD', 'logo' => '/assets/images/natcash-logo.png', 'nom' => 'NatCash'],
    'carte' => ['couleur' => '#6366f1', 'logo' => '/assets/images/card.png', 'nom' => 'Carte bancaire']
];

$current = $config[$mode];
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/paiement.css">

<div class="paiement-container">
    <!-- ✅ Flèche retour -->
    <div class="retour-header">
        <a href="<?= $baseUrl ?>/paiement/formulaire?mode=<?= $mode ?>" class="btn-retour">
            <i class="bi bi-arrow-left"></i> Modifier les informations
        </a>
    </div>

    <div class="paiement-form otp-form" style="border-top: 4px solid <?= $current['couleur'] ?>">
        <div class="form-header">
            <?php if ($mode === 'carte'): ?>
                <i class="bi bi-credit-card" style="font-size: 3rem; color: <?= $current['couleur'] ?>"></i>
            <?php else: ?>
                <img src="<?= $baseUrl . $current['logo'] ?>" alt="<?= $current['nom'] ?>" class="mode-logo">
            <?php endif; ?>
            <h2>Validation OTP</h2>
        </div>
        
        <div class="otp-message">
            <i class="bi bi-envelope"></i>
            
            <?php if ($mode === 'carte'): ?>
                <p>Un code de validation a été envoyé à votre numéro de téléphone</p>
                <p class="small text-muted">NB: Simulation</p>
            <?php else: ?>
                <p>Un code de validation a été envoyé à votre numéro <?= $current['nom'] ?></p>
                <?php if (!empty($paiement['numero'])): ?>
                    <p class="numero">**** **** <?= substr($paiement['numero'], -4) ?></p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <form method="POST" action="<?= $baseUrl ?>/paiement/valider" id="otpForm">
            <div class="form-group">
                <label>Code de validation</label>
                <div class="otp-inputs">
                    <input type="text" 
                           name="code" 
                           placeholder="123456" 
                           maxlength="6" 
                           pattern="[0-9]{6}"
                           required
                           autocomplete="off"
                           class="otp-code-input">
                </div>
            </div>
            
            <button type="submit" class="btn-payer" style="background: <?= $current['couleur'] ?>">
                <i class="bi bi-check-lg"></i> Valider et payer
            </button>
        </form>
        
        <div class="otp-actions">
            <button class="btn-renvoyer" onclick="renvoyerCode()">
                <i class="bi bi-arrow-repeat"></i>
                Renvoyer le code
            </button>
        </div>
    </div>
</div>

<script>
function renvoyerCode() {
    const btn = event.currentTarget;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass"></i> Envoi en cours...';
    
    fetch('<?= $baseUrl ?>/paiement/renvoyer-code')
        .then(res => res.json())
        .then(data => {
            alert('Nouveau code envoyé ! (Simulation - Code: 123456)');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Renvoyer le code';
        })
        .catch(error => {
            alert('Erreur lors de l\'envoi');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Renvoyer le code';
        });
}
</script>