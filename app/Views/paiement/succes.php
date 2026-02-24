<?php 
$baseUrl = App::baseUrl();
$numCommande = $numCommande ?? '';
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/paiement.css">

<div class="paiement-succes-container">
    <div class="succes-card">
        <!-- Checkmark animé -->
        <div class="checkmark-container">
            <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
                <path class="checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
            </svg>
        </div>
        
        <h1>Paiement réussi !</h1>
        
        <div class="commande-info">
            <p>Votre commande a été enregistrée avec succès.</p>
            <p class="num-commande">N° <?= htmlspecialchars($numCommande) ?></p>
        </div>
        
        <div class="redirect-message">
            <p>Redirection automatique dans <span id="countdown">3</span> secondes...</p>
        </div>
        
        <div class="actions">
            <a href="<?= $baseUrl ?>/mes-commandes" class="btn-primary" id="btn-voir-commandes">
                Voir mes commandes
            </a>
            <a href="<?= $baseUrl ?>/" class="btn-secondary">
                Retour à l'accueil
            </a>
        </div>
    </div>
</div>

<script>
let secondes = 7;
const countdownElement = document.getElementById('countdown');
const redirectUrl = '<?= $baseUrl ?>/mes-commandes';

const timer = setInterval(function() {
    secondes--;
    countdownElement.textContent = secondes;
    
    if (secondes <= 0) {
        clearInterval(timer);
        window.location.href = redirectUrl;
    }
}, 1000);

document.getElementById('btn-voir-commandes').addEventListener('click', function() {
    clearInterval(timer);
});
</script>