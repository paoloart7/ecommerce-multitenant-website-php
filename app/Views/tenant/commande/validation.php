<?php 
$baseUrl = App::baseUrl();
$commandes = $commandes ?? [];
?>

<h1>Commandes à valider</h1>

<table>
    <thead>
        <tr>
            <th>N° Commande</th>
            <th>Client</th>
            <th>Date</th>
            <th>Montant</th>
            <th>Paiement</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($commandes as $cmd): ?>
        <tr>
            <td><?= $cmd['numeroCommande'] ?></td>
            <td><?= $cmd['prenomUtilisateur'] ?> <?= $cmd['nomUtilisateur'] ?></td>
            <td><?= date('d/m/Y', strtotime($cmd['dateCommande'])) ?></td>
            <td><?= number_format($cmd['total'], 0, ',', ' ') ?> G</td>
            <td><?= $cmd['modePaiement'] ?? 'N/A' ?></td>
            <td>
                <form method="POST" action="<?= $baseUrl ?>/vendeur/commande/valider">
                    <input type="hidden" name="idCommande" value="<?= $cmd['idCommande'] ?>">
                    <button type="submit">Confirmer</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>