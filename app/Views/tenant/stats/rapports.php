<?php 
$baseUrl = App::baseUrl();
$user = Session::user();
$mois = $mois ?? date('m');
$annee = $annee ?? date('Y');

$moisNoms = [
    '01' => 'Janvier', '02' => 'Février', '03' => 'Mars', '04' => 'Avril',
    '05' => 'Mai', '06' => 'Juin', '07' => 'Juillet', '08' => 'Août',
    '09' => 'Septembre', '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre'
];
$moisNom = $moisNoms[$mois] ?? $mois;
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/stats.css">

<div class="tenant-layout">
    <?php require dirname(__DIR__) . '/partials/sidebar.php'; ?>
    
    <div class="tenant-main">
        <?php require dirname(__DIR__) . '/partials/topbar.php'; ?>

        <div class="tenant-content container-fluid p-4">
            
            <!-- EN-TÊTE -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold h4 m-0">📑 Rapports mensuels</h2>
                    <p class="text-muted small mb-0"><?= $moisNom ?> <?= $annee ?></p>
                </div>
                <div>
                    <a href="<?= $baseUrl ?>/vendeur/statistiques" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                </div>
            </div>

            <!-- SÉLECTEUR DE MOIS -->
            <div class="rapports-card mb-4">
                <form method="GET" action="<?= $baseUrl ?>/vendeur/rapports" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Mois</label>
                        <select name="mois" class="form-select">
                            <?php foreach ($moisNoms as $num => $nom): ?>
                                <option value="<?= $num ?>" <?= $mois == $num ? 'selected' : '' ?>>
                                    <?= $nom ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Année</label>
                        <select name="annee" class="form-select">
                            <?php for ($a = date('Y'); $a >= 2024; $a--): ?>
                                <option value="<?= $a ?>" <?= $annee == $a ? 'selected' : '' ?>>
                                    <?= $a ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Voir le rapport
                        </button>
                    </div>
                </form>
            </div>

            <!-- MESSAGE DE CONSTRUCTION -->
            <div class="rapports-card text-center py-5">
                <i class="bi bi-cone-striped display-1 text-muted"></i>
                <h4 class="mt-3">Rapport en construction</h4>
                <p class="text-muted">Les rapports détaillés seront disponibles prochainement.</p>
                <p class="text-muted small">Fonctionnalités à venir :</p>
                <div class="d-flex justify-content-center gap-4 mt-3">
                    <span class="badge bg-primary-soft"><i class="bi bi-file-pdf"></i> Export PDF</span>
                    <span class="badge bg-success-soft"><i class="bi bi-file-excel"></i> Export Excel</span>
                    <span class="badge bg-info-soft"><i class="bi bi-printer"></i> Impression</span>
                </div>
            </div>
        </div>
    </div>
</div>