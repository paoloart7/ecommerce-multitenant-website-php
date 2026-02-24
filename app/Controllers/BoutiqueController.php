<?php
// app/Controllers/BoutiqueController.php

require_once __DIR__ . '/Controller.php';
require_once dirname(__DIR__) . '/Models/Boutique.php';

class BoutiqueController extends Controller
{
    private $boutiqueModel;

    public function __construct()
    {
        $this->boutiqueModel = new Boutique();
    }

    /**
     * Liste de toutes les boutiques
     */
    public function index()
    {
        $boutiques = $this->boutiqueModel->getActivesAvecVentes();
        
        $this->view('boutique/index', [
            'pageTitle' => 'Toutes les boutiques',
            'boutiques' => $boutiques
        ]);
    }

    /**
     * Détail d'une boutique avec ses produits
     */
    public function detail()
    {
        $slug = $_GET['slug'] ?? '';
        $boutique = $this->boutiqueModel->getBySlug($slug);
        
        if (!$boutique) {
            $this->view('erreur/404', ['message' => 'Boutique non trouvée']);
            return;
        }
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $categorie = isset($_GET['categorie']) ? (int)$_GET['categorie'] : null;
        $produits = $this->boutiqueModel->getProduits($boutique['idBoutique'], $categorie, $page);
        $totalProduits = $this->boutiqueModel->countProduits($boutique['idBoutique'], $categorie);
        $categories = $this->boutiqueModel->getCategories($boutique['idBoutique']);
        
        $this->view('boutique/detail', [
            'pageTitle' => $boutique['nomBoutique'],
            'boutique' => $boutique,
            'produits' => $produits,
            'categories' => $categories,
            'pagination' => [
                'current' => $page,
                'total' => ceil($totalProduits / 12),
                'count' => $totalProduits
            ],
            'filtre_categorie' => $categorie
        ]);
    }
}