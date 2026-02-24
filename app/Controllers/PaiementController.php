<?php
// app/Controllers/PaiementController.php

require_once __DIR__ . '/Controller.php';
require_once dirname(__DIR__) . '/Models/Panier.php';

class PaiementController extends Controller
{
    private $panier;

    public function __construct()
    {
        $this->panier = new Panier();
    }

    /**
     * Page de choix du mode de paiement
     */
    public function choix()
    {
        $user = Session::user();
        $userId = $user['id'] ?? null;
        
        if (!$userId) {
            App::redirect('/login');
            return;
        }
        
        $panier = $this->panier->getPanier($userId);
        
        if (empty($panier['items'])) {
            App::redirect('/panier');
            return;
        }
        
        $this->view('paiement/choix', [
            'pageTitle' => 'Choisir un mode de paiement',
            'total' => $panier['total'],
            'items' => $panier['items'],
            'isMinimalNav' => true  
        ]);
    }

    /**
     * Formulaire de paiement selon le mode
     */
    public function formulaire()
    {
        $mode = $_GET['mode'] ?? 'moncash';
        $user = Session::user();
        $userId = $user['id'] ?? null;
        
        $panier = $this->panier->getPanier($userId);
        
        $this->view('paiement/formulaire', [
            'pageTitle' => 'Paiement ' . ucfirst($mode),
            'mode' => $mode,
            'total' => $panier['total'],
            'items' => $panier['items'],
            'isMinimalNav' => true  
        ]);
    }

/**
 * Traitement du paiement (simulation)
 */
public function traiter()
{
    $mode = $_POST['mode'] ?? 'moncash';
    $user = Session::user();
    $userId = $user['id'] ?? null;    
    if ($mode === 'carte') {
        $_SESSION['paiement'] = [
            'mode' => 'carte',
            'numero_carte' => $_POST['numero_carte'] ?? '',
            'exp_mois' => $_POST['exp_mois'] ?? '',
            'exp_annee' => $_POST['exp_annee'] ?? '',
            'cvv' => $_POST['cvv'] ?? '',
            'nom_titulaire' => $_POST['nom_titulaire'] ?? '',
            'montant' => $_POST['total'] ?? 0
        ];
    } else {
        $_SESSION['paiement'] = [
            'mode' => $mode,
            'numero' => $_POST['numero'] ?? '',
            'pin' => $_POST['pin'] ?? '',
            'montant' => $_POST['total'] ?? 0
        ];
    }
    
    App::redirect('/paiement/otp');
}

    

    /**
     * Page OTP (simulation)
     */
    public function otp()
    {
        if (!isset($_SESSION['paiement'])) {
            App::redirect('/paiement/choix');
            return;
        }
        
        $this->view('paiement/otp', [
            'pageTitle' => 'Validation OTP',
            'paiement' => $_SESSION['paiement'],
            'isMinimalNav' => true  
        ]);
    }

/**
 * Validation OTP et création commande
 */
public function valider()
{
    $code = $_POST['code'] ?? '';
    $user = Session::user();
    $userId = $user['id'] ?? null;
    
    if (!isset($_SESSION['paiement'])) {
        App::redirect('/paiement/choix');
        return;
    }
    
    $paiement = $_SESSION['paiement'];
    $panier = $this->panier->getPanier($userId);
    
    if (empty($panier['items'])) {
        App::redirect('/panier');
        return;
    }
    
    $numCommande = 'CMD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    
    $db = Database::getInstance();
    $db->beginTransaction();
    
    try {
        // 1. Créer la commande
        $db->execute(
            "INSERT INTO commande (
                idBoutique, idClient, numeroCommande, sousTotal, 
                total, devise, statut, notesInternes, dateCommande
            ) VALUES (?, ?, ?, ?, ?, 'HTG', 'payee', ?, NOW())",
            [
                $this->getBoutiqueId($panier['items']),
                $userId,
                $numCommande,
                $panier['total'],
                $panier['total'],
                'Paiement: ' . $paiement['mode'] . ' - ' . 
                ($paiement['mode'] === 'carte' ? 'carte' : ($paiement['numero'] ?? ''))
            ]
        );
        
        $commandeId = $db->lastInsertId();
        
        // 2. Ajouter les produits
        foreach ($panier['items'] as $item) {
            $db->execute(
                "INSERT INTO commande_produit (
                    idCommande, idProduit, nomProduitSnapshot, quantite, prixUnitaire, totalLigne
                ) VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $commandeId,
                    $item['id'],
                    $item['nom'],
                    $item['quantite'],
                    $item['prix'],
                    $item['prix'] * $item['quantite']
                ]
            );
            
            // 3. Mettre à jour le stock
            $db->execute(
                "UPDATE produit SET stock = stock - ? WHERE idProduit = ?",
                [$item['quantite'], $item['id']]
            );
        }
        
        // 4. Déterminer le mode de paiement pour la base
        if ($paiement['mode'] === 'carte') {
            $modeBd = 'credit'; 
            $details = json_encode([
                'numero_carte' => substr($paiement['numero_carte'] ?? '', -4),
                'exp_mois' => $paiement['exp_mois'] ?? '',
                'exp_annee' => $paiement['exp_annee'] ?? '',
                'nom_titulaire' => $paiement['nom_titulaire'] ?? '',
                'code_otp' => $code
            ]);
        } else {
            $modeBd = $paiement['mode'];
            $details = json_encode([
                'numero' => $paiement['numero'] ?? null,
                'code_otp' => $code
            ]);
        }
        
        // 5. Ajouter le paiement
        $db->execute(
            "INSERT INTO paiement (
                idCommande, idBoutique, modePaiement, montant, 
                devise, statutPaiement, referenceExterne, details, datePaiement
            ) VALUES (?, ?, ?, ?, 'HTG', 'valide', ?, ?, NOW())",
            [
                $commandeId,
                $this->getBoutiqueId($panier['items']),
                $modeBd,
                $panier['total'],
                'SIMU' . time(),
                $details
            ]
        );
        
        // 6. Vider le panier
        $this->panier->vider($userId);
        
        $db->commit();
        unset($_SESSION['paiement']);
        App::redirect('/paiement/succes?commande=' . urlencode($numCommande));
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Erreur commande: " . $e->getMessage());
        $_SESSION['flash_error'] = 'Erreur lors de la commande';
        App::redirect('/paiement/choix');
    }
}
    /**
     * Récupérer l'ID de la boutique (premier article)
     */
    private function getBoutiqueId($items)
    {
        reset($items);
        $first = current($items);
        
        $db = Database::getInstance();
        $produit = $db->fetch("SELECT idBoutique FROM produit WHERE idProduit = ?", [$first['id']]);
        return $produit['idBoutique'] ?? 1;
    }

    /**
     * Renvoyer le code OTP (simulation)
     */
    public function renvoyerCode()
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Code envoyé']);
    }

        /**
     * Page de succès du paiement
     */
    public function succes()
    {
        $numCommande = $_GET['commande'] ?? '';
        
        $this->view('paiement/succes', [
            'pageTitle' => 'Paiement réussi',
            'numCommande' => $numCommande,
            'isMinimalNav' => true
        ]);
    }
}