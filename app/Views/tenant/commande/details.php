<?php 
$baseUrl = App::baseUrl();
$commande = $commande ?? [];
$items = $items ?? [];
$payments = $payments ?? [];
$tenant = $tenant ?? [];
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/tenant-commandes.css">

<div class="commande-detail">
    <!-- En-tête -->
    <div class="commande-header">
        <div class="commande-titre">
            <h1>Détail commande #<?= Security::escape($commande['numeroCommande'] ?? '') ?></h1>
            <div class="commande-meta">
                <span>
                    <i class="bi bi-calendar3"></i>
                    <?= date('d/m/Y', strtotime($commande['dateCommande'] ?? '')) ?>
                </span>
                <span>
                    <i class="bi bi-clock"></i>
                    <?= date('H:i', strtotime($commande['dateCommande'] ?? '')) ?>
                </span>
            </div>
        </div>
        <a href="<?= $baseUrl ?>/vendeur/commandes" class="btn-retour">
            <i class="bi bi-arrow-left"></i>
            Retour aux commandes
        </a>
    </div>

    <!-- Grille d'informations -->
    <div class="info-grid">
        <!-- Info client -->
        <div class="info-card">
            <div class="info-card-header">
                <i class="bi bi-person-circle"></i>
                <h5>Client</h5>
            </div>
            <div class="info-card-content">
                <div class="info-row">
                    <span class="info-label">Nom complet</span>
                    <span class="info-value">
                        <?= Security::escape($commande['prenomUtilisateur'] ?? '') ?> <?= Security::escape($commande['nomUtilisateur'] ?? '') ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email</span>
                    <span class="info-value"><?= Security::escape($commande['emailUtilisateur'] ?? '') ?></span>
                </div>
                <?php if (!empty($commande['telephone'])): ?>
                <div class="info-row">
                    <span class="info-label">Téléphone</span>
                    <span class="info-value"><?= Security::escape($commande['telephone']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Info commande -->
        <div class="info-card">
            <div class="info-card-header">
                <i class="bi bi-receipt"></i>
                <h5>Commande</h5>
            </div>
            <div class="info-card-content">
                <div class="info-row">
                    <span class="info-label">Statut</span>
                    <span class="info-value">
                        <span class="statut-badge <?= $commande['statut'] ?? '' ?>">
                            <?= str_replace('_', ' ', $commande['statut'] ?? '') ?>
                        </span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Total</span>
                    <span class="info-value highlight">
                        <?= number_format($commande['total'] ?? 0, 0, ',', ' ') ?> G
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Paiement</span>
                    <span class="info-value"><?= $payments[0]['modePaiement'] ?? 'N/A' ?></span>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="actions-card">
            <div class="info-card-header">
                <i class="bi bi-gear"></i>
                <h5>Actions</h5>
            </div>
            <div class="actions-grid">
                <button class="btn-action confirmer" onclick="changerStatut('confirmee')">
                    <i class="bi bi-check-circle"></i>
                    Confirmer
                </button>
                <button class="btn-action preparer" onclick="changerStatut('en_preparation')">
                    <i class="bi bi-box"></i>
                    Préparer
                </button>
                <button class="btn-action expedier" onclick="changerStatut('expediee')">
                    <i class="bi bi-truck"></i>
                    Expédier
                </button>
                <button class="btn-action livrer" onclick="changerStatut('livree')">
                    <i class="bi bi-check2-all"></i>
                    Livrer
                </button>
                <button class="btn-action annuler" onclick="annulerCommande()">
                    <i class="bi bi-x-circle"></i>
                    Annuler
                </button>
            </div>
        </div>
    </div>

    <!-- Produits commandés -->
    <div class="table-card">
        <div class="table-card-header">
            <h5>
                <i class="bi bi-box-seam"></i>
                Produits commandés
            </h5>
        </div>
        <div class="table-responsive">
            <table class="table-premium">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Quantité</th>
                        <th>Prix unitaire</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= Security::escape($item['nomProduitSnapshot'] ?? '') ?></td>
                        <td><?= $item['quantite'] ?? 0 ?></td>
                        <td class="montant"><?= number_format($item['prixUnitaire'] ?? 0, 0, ',', ' ') ?> G</td>
                        <td class="text-end montant"><?= number_format($item['totalLigne'] ?? 0, 0, ',', ' ') ?> G</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="3" class="text-end">Sous-total</td>
                        <td class="text-end montant"><?= number_format($commande['sousTotal'] ?? 0, 0, ',', ' ') ?> G</td>
                    </tr>
                    <?php if (($commande['montantTaxe'] ?? 0) > 0): ?>
                    <tr>
                        <td colspan="3" class="text-end">Taxe (<?= $commande['tauxTaxe'] ?? 0 ?>%)</td>
                        <td class="text-end montant"><?= number_format($commande['montantTaxe'] ?? 0, 0, ',', ' ') ?> G</td>
                    </tr>
                    <?php endif; ?>
                    <tr class="total-row">
                        <td colspan="3" class="text-end fw-bold">Total</td>
                        <td class="text-end montant-total"><?= number_format($commande['total'] ?? 0, 0, ',', ' ') ?> G</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Paiements -->
    <?php if (!empty($payments)): ?>
    <div class="table-card">
        <div class="table-card-header">
            <h5>
                <i class="bi bi-credit-card"></i>
                Historique des paiements
            </h5>
        </div>
        <div class="table-responsive">
            <table class="table-premium">
                <thead>
                    <tr>
                        <th>Mode</th>
                        <th>Montant</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th>Référence</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?= ucfirst($p['modePaiement'] ?? '') ?></td>
                        <td class="montant"><?= number_format($p['montant'] ?? 0, 0, ',', ' ') ?> G</td>
                        <td><?= date('d/m/Y H:i', strtotime($p['datePaiement'] ?? '')) ?></td>
                        <td>
                            <span class="statut-badge <?= $p['statutPaiement'] ?? '' ?>">
                                <?= $p['statutPaiement'] ?? '' ?>
                            </span>
                        </td>
                        <td><small><?= $p['referenceExterne'] ?? '-' ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function changerStatut(nouveauStatut) {
    if (confirm('Changer le statut de cette commande ?')) {

    console.log('Changement vers:', nouveauStatut);
    }
}

function annulerCommande() {
    if (confirm('Êtes-vous sûr de vouloir annuler cette commande ?')) {
        
    console.log('Annulation commande');
    }
}
</script>

<script src="<?= $baseUrl ?>/assets/js/tenant-commandes.js"></script>