<?php 
$baseUrl = App::baseUrl();
$total = $total ?? 0;
?>
<!-- En haut de choix.php -->
<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/paiement.css">

<div class="paiement-container">
    <!-- ✅ Flèche retour -->
    <div class="retour-header">
        <a href="<?= $baseUrl ?>/panier" class="btn-retour">
            <i class="bi bi-arrow-left"></i> Retour au panier
        </a>
    </div>

    <h1>Paiement sécurisé</h1>
    
    <div class="paiement-modes">
        <h3>Choisissez votre mode de paiement</h3>
        
        <div class="mode-cards">
            <!-- MonCash -->
            <a href="<?= $baseUrl ?>/paiement/formulaire?mode=moncash" class="mode-card moncash">
                <img src="<?= $baseUrl ?>/assets/images/moncash-logo.png" alt="MonCash">
                <span>MonCash</span>
            </a>
            
            <!-- NatCash -->
            <a href="<?= $baseUrl ?>/paiement/formulaire?mode=natcash" class="mode-card natcash">
                <img src="<?= $baseUrl ?>/assets/images/natcash-logo.png" alt="NatCash">
                <span>NatCash</span>
            </a>
            
            <!-- Carte bancaire -->
            <a href="<?= $baseUrl ?>/paiement/formulaire?mode=carte" class="mode-card carte">
                <i class="bi bi-credit-card"></i>
                <span>Carte bancaire</span>
            </a>
        </div>
    </div>
    
    <div class="recap-paiement">
        <h4>Récapitulatif</h4>
        <div class="total">Total à payer: <strong><?= number_format($total, 0, ',', ' ') ?> G</strong></div>
    </div>
</div>