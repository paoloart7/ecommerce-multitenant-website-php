<?php $baseUrl = App::baseUrl(); ?>
<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/orders.css">

<div class="admin-layout">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    <div class="admin-main">
        <?php require __DIR__ . '/partials/topbar.php'; ?>

        <div class="admin-content container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 fw-bold">Supervision des Commandes</h1>
            </div>

            <!-- FILTRES -->
            <div class="card border-0 shadow-sm mb-4 p-3">
                <form action="" method="GET" class="row g-2">
                    <div class="col-md-5">
                        <input type="text" name="q" class="form-control" placeholder="Numéro, boutique, client..." value="<?= $filters['q'] ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">Tous les statuts</option>
                            <option value="en_attente">En attente</option>
                            <option value="payee">Payée</option>
                            <option value="livree">Livrée</option>
                            <option value="annulee">Annulée</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-primary">Filtrer</button>
                    </div>
                </form>
            </div>

            <!-- TABLEAU -->
            <div class="card border-0 shadow-sm overflow-hidden">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">N° Commande</th>
                            <th>Boutique</th>
                            <th>Client</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Statut</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($orders as $o): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-primary">#<?= $o['numeroCommande'] ?></td>
                            <td><a href="<?= $baseUrl ?>/admin/tenant-details?id=<?= $o['idBoutique'] ?>" class="text-dark text-decoration-none fw-semibold">@<?= $o['slugBoutique'] ?></a></td>
                            <td>
                                <div class="small fw-bold"><?= $o['clientNom'] ?></div>
                                <div class="text-muted" style="font-size:0.7rem;"><?= $o['clientEmail'] ?></div>
                            </td>
                            <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($o['dateCommande'])) ?></td>
                            <td class="fw-bold"><?= number_format($o['total'], 2) ?> G</td>
                            <td><span class="badge-status status-<?= $o['statut'] ?>"><?= ucfirst($o['statut']) ?></span></td>
                            <td class="text-end pe-4">
                                <a href="<?= $baseUrl ?>/admin/order-details?id=<?= $o['idCommande'] ?>" class="btn btn-sm btn-light border">Voir</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>