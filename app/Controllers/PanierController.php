<?php
// app/Controllers/PanierController.php

require_once __DIR__ . '/Controller.php';
require_once dirname(__DIR__) . '/Models/Panier.php';

class PanierController extends Controller
{
    private $panier;

    public function __construct()
    {
        $this->panier = new Panier();
    }

/**
 * Afficher le panier
 */
public function index()
{
    $user = Session::user();
    $userId = $user['id'] ?? null;
    
    $contenu = $this->panier->getPanier($userId);    
    $taxe = 10;
    $remise = 0;
    $livraison = 0;
    $montantTaxe = $contenu['total'] * ($taxe / 100);
    $totalFinal = $contenu['total'] + $montantTaxe + $livraison - $remise;
    
    $this->view('panier/index', [
        'pageTitle' => 'Mon panier',
        'items' => $contenu['items'],
        'total' => $contenu['total'],
        'taxe' => $taxe,
        'remise' => $remise,
        'livraison' => $livraison,
        'montantTaxe' => $montantTaxe,
        'totalFinal' => $totalFinal
    ]);
}

/**
 * Mettre à jour la quantité (AJAX)
 */
public function update()
{
    header('Content-Type: application/json');
    
    error_log("=== PANIER UPDATE ===");
    error_log("POST data: " . print_r($_POST, true));
    
    $id = $_POST['id'] ?? null;
    $quantite = $_POST['quantite'] ?? 1;
    
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'ID manquant']);
        return;
    }
    
    $user = Session::user();
    $userId = $user['id'] ?? null;
    
    if ($this->panier->mettreAJour($id, $quantite, $userId)) {
        $panier = $this->panier->getPanier($userId);
        
        echo json_encode([
            'success' => true,
            'total' => $panier['total'],
            'count' => $this->panier->compter($userId)
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Échec mise à jour']);
    }
}
/**
 * Supprimer un article (AJAX)
 */
public function remove()
{
    header('Content-Type: application/json');
    
    $id = $_POST['id'] ?? null;
    $user = Session::user();
    $userId = $user['id'] ?? null;
    
    if ($id && $this->panier->supprimer($id, $userId)) {
        $panier = $this->panier->getPanier($userId);
        
        $taxe = 10;
        $remise = 0;
        $livraison = 0;
        
        echo json_encode([
            'success' => true,
            'total' => $panier['total'],
            'taxe' => $taxe,
            'remise' => $remise,
            'livraison' => $livraison,
            'count' => $this->panier->compter($userId)
        ]);
    } else {
        echo json_encode(['success' => false]);
    }
}

    /**
     * Vider le panier (AJAX)
     */
    public function clear()
    {
        header('Content-Type: application/json');
        
        $user = Session::user();
        $userId = $user['id'] ?? null;
        
        if ($this->panier->vider($userId)) {
            echo json_encode([
                'success' => true,
                'total' => 0,
                'count' => 0
            ]);
        } else {
            echo json_encode(['success' => false]);
        }
    }
}