<?php
// app/Controllers/ApiController.php
require_once __DIR__ . '/Controller.php';
require_once dirname(__DIR__) . '/Models/Panier.php';

class ApiController extends Controller
{
    private $panier;

    public function __construct()
    {
        $this->panier = new Panier();
    }

    /**
     * Récupérer le contenu du panier
     */
    public function cart()
    {
        $user = Session::user();
        $userId = $user['id'] ?? null;
        
        $panier = $this->panier->getPanier($userId);
        
        header('Content-Type: application/json');
        echo json_encode([
            'items' => array_values($panier['items']),
            'total' => $panier['total'],
            'count' => $this->panier->compter($userId)
        ]);
    }

    /**
     * Ajouter au panier
     */
    public function cartAdd()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $produitId = $data['id'] ?? null;
        $quantite = $data['quantite'] ?? 1;
        
        $user = Session::user();
        $userId = $user['id'] ?? null;
        
        if ($produitId && $this->panier->ajouter($produitId, $quantite, $userId)) {
            http_response_code(200);
            echo json_encode(['success' => true, 'count' => $this->panier->compter($userId)]);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false]);
        }
    }

    /**
     * Mettre à jour la quantité
     */
    public function cartUpdate()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $produitId = $data['id'] ?? null;
        $quantite = $data['quantite'] ?? 1;
        
        $user = Session::user();
        $userId = $user['id'] ?? null;
        
        if ($produitId && $this->panier->mettreAJour($produitId, $quantite, $userId)) {
            $panier = $this->panier->getPanier($userId);
            echo json_encode(['success' => true, 'total' => $panier['total']]);
        } else {
            echo json_encode(['success' => false]);
        }
    }

    /**
     * Supprimer du panier
     */
    public function cartRemove()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $produitId = $data['id'] ?? null;
        
        $user = Session::user();
        $userId = $user['id'] ?? null;
        
        if ($produitId && $this->panier->supprimer($produitId, $userId)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
    }

    /**
     * Compter les articles
     */
    public function cartCount()
    {
        $user = Session::user();
        $userId = $user['id'] ?? null;
        
        echo json_encode(['count' => $this->panier->compter($userId)]);
    }
}