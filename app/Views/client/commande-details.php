<?php 
$baseUrl = App::baseUrl();
$commande = $commande ?? [];
$items = $items ?? [];
$payments = $payments ?? [];
$user = Session::user();
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/commande-details.css">


<div class="container py-5">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold">Détail commande #<?= Security::escape($commande['numeroCommande'] ?? '') ?></h1>
            <p class="text-muted">Passée le <?= date('d/m/Y à H:i', strtotime($commande['dateCommande'] ?? '')) ?></p>
        </div>
        <a href="<?= $baseUrl ?>/mes-commandes" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
    </div>

    <!-- Statut et boutique -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Statut de la commande</h5>
                    <?php
                    $badgeClass = match($commande['statut'] ?? '') {
                        'en_attente' => 'warning',
                        'confirmee' => 'info',
                        'payee' => 'primary',
                        'en_preparation' => 'secondary',
                        'expediee' => 'dark',
                        'livree' => 'success',
                        'annulee' => 'danger',
                        default => 'secondary'
                    };
                    ?>
                    <span class="badge bg-<?= $badgeClass ?> fs-6 p-2">
                        <?= str_replace('_', ' ', $commande['statut'] ?? 'inconnu') ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Boutique</h5>
                    <a href="<?= $baseUrl ?>/boutique?slug=<?= $commande['slugBoutique'] ?>" class="text-decoration-none">
                        <i class="bi bi-shop"></i> <?= Security::escape($commande['nomBoutique'] ?? '') ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Adresse de livraison -->
    <?php if (!empty($commande['rue'])): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title">Adresse de livraison</h5>
            <p class="mb-0"><?= Security::escape($commande['prenomUtilisateur'] ?? '') ?> <?= Security::escape($commande['nomUtilisateur'] ?? '') ?></p>
            <p class="mb-0"><?= Security::escape($commande['rue'] ?? '') ?></p>
            <?php if (!empty($commande['complement'])): ?>
                <p class="mb-0"><?= Security::escape($commande['complement']) ?></p>
            <?php endif; ?>
            <p class="mb-0"><?= Security::escape($commande['ville'] ?? '') ?></p>
            <p class="mb-0"><?= Security::escape($commande['pays'] ?? 'Haiti') ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Produits commandés -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Articles commandés</h5>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="bg-light">
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
                        <td><?= number_format($item['prixUnitaire'] ?? 0, 0, ',', ' ') ?> G</td>
                        <td class="text-end fw-bold"><?= number_format($item['totalLigne'] ?? 0, 0, ',', ' ') ?> G</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-light">
                    <tr>
                        <td colspan="3" class="text-end fw-bold">Sous-total</td>
                        <td class="text-end"><?= number_format($commande['sousTotal'] ?? 0, 0, ',', ' ') ?> G</td>
                    </tr>
                    <?php if (($commande['montantTaxe'] ?? 0) > 0): ?>
                    <tr>
                        <td colspan="3" class="text-end fw-bold">Taxe (<?= $commande['tauxTaxe'] ?? 0 ?>%)</td>
                        <td class="text-end"><?= number_format($commande['montantTaxe'] ?? 0, 0, ',', ' ') ?> G</td>
                    </tr>
                    <?php endif; ?>
                    <?php if (($commande['fraisLivraison'] ?? 0) > 0): ?>
                    <tr>
                        <td colspan="3" class="text-end fw-bold">Frais de livraison</td>
                        <td class="text-end"><?= number_format($commande['fraisLivraison'] ?? 0, 0, ',', ' ') ?> G</td>
                    </tr>
                    <?php endif; ?>
                    <?php if (($commande['remise'] ?? 0) > 0): ?>
                    <tr>
                        <td colspan="3" class="text-end fw-bold text-danger">Remise</td>
                        <td class="text-end text-danger">- <?= number_format($commande['remise'] ?? 0, 0, ',', ' ') ?> G</td>
                    </tr>
                    <?php endif; ?>
                    <tr class="fw-bold fs-5">
                        <td colspan="3" class="text-end">Total</td>
                        <td class="text-end text-success"><?= number_format($commande['total'] ?? 0, 0, ',', ' ') ?> G</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Historique des paiements -->
    <?php if (!empty($payments)): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Paiements</h5>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Mode de paiement</th>
                        <th>Montant</th>
                        <th>Date</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?= ucfirst($p['modePaiement'] ?? '') ?></td>
                        <td><?= number_format($p['montant'] ?? 0, 0, ',', ' ') ?> G</td>
                        <td><?= date('d/m/Y H:i', strtotime($p['datePaiement'] ?? '')) ?></td>
                        <td>
                            <span class="badge bg-<?= $p['statutPaiement'] == 'valide' ? 'success' : 'secondary' ?>">
                                <?= $p['statutPaiement'] ?? '' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Actions -->
    <?php if (in_array($commande['statut'] ?? '', ['en_attente', 'confirmee', 'payee'])): ?>
    <div class="mt-4 text-end">
        <button class="btn btn-outline-danger" onclick="annulerCommande(<?= $commande['idCommande'] ?>)">
            Annuler la commande
        </button>
    </div>
    <?php endif; ?>
</div>

<script>
function annulerCommande(id) {
    if (confirm('Êtes-vous sûr de vouloir annuler cette commande ?')) {
        fetch('<?= $baseUrl ?>/commande/annuler', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }
}
</script>