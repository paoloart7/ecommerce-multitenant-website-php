<?php
// app/Controllers/ClientController.php

require_once __DIR__ . '/Controller.php';
require_once dirname(__DIR__) . '/Models/Order.php';
require_once dirname(__DIR__) . '/Models/Client.php'; 

class ClientController extends Controller
{
    private Order $orderModel;
    private Client $clientModel;

    public function __construct()
    {
        $this->orderModel = new Order();
        $this->clientModel = new Client();
        
        $user = Session::user();
        if (!$user || $user['role'] !== 'client') {
            App::redirect('/login');
        }
    }

    /**
     * Mes commandes
     */
    public function commandes()
    {
        $user = Session::user();
        $userId = $user['id'] ?? $user['idUtilisateur'] ?? null;
        
        if (!$userId) {
            App::redirect('/login');
            return;
        }

        require_once dirname(__DIR__) . '/Models/Order.php';
        $orderModel = new Order();
        
        $commandes = $orderModel->getByClient($userId);
        
        $stats = [
            'total' => count($commandes),
            'en_cours' => count(array_filter($commandes, function($c) {
                return in_array($c['statut'], ['en_attente', 'confirmee', 'payee', 'en_preparation', 'expediee']);
            })),
            'livrees' => count(array_filter($commandes, function($c) {
                return $c['statut'] === 'livree';
            })),
            'annulees' => count(array_filter($commandes, function($c) {
                return in_array($c['statut'], ['annulee', 'remboursee']);
            })),
            'total_depense' => array_sum(array_column($commandes, 'total'))
        ];

        $this->view('client/commandes', [
            'pageTitle' => 'Mes commandes',
            'commandes' => $commandes,
            'stats' => $stats
        ]);
    }

    /**
     * Détail d'une commande
     */
    public function commandeDetails()
    {
        $idCommande = $_GET['id'] ?? null;
        if (!$idCommande) {
            App::redirect('/mes-commandes');
            return;
        }

        $user = Session::user();
        $userId = $user['id'] ?? $user['idUtilisateur'] ?? null;

        $details = $this->orderModel->getFullDetails((int)$idCommande);
        
        if (!$details || $details['idClient'] != $userId) {
            App::redirect('/mes-commandes');
            return;
        }

        $items = $this->orderModel->getItems((int)$idCommande);
        $payments = $this->orderModel->getPayments((int)$idCommande);

        $this->view('client/commande-details', [
            'pageTitle' => 'Détail commande',
            'commande' => $details,
            'items' => $items,
            'payments' => $payments
        ]);
    }

    // ========== PROFIL ==========

    /**
     * Page profil
     */
    public function profil()
    {
        $user = Session::user();
        $userId = $user['id'] ?? $user['idUtilisateur'] ?? null;
        
        $client = $this->clientModel->getById($userId);
        
        $this->view('client/profil', [
            'pageTitle' => 'Mon profil',
            'client' => $client,
            'stats' => $this->clientModel->getStats($userId)
        ]);
    }

    /**
     * Formulaire modification profil
     */
    public function profilEdit()
    {
        $user = Session::user();
        $userId = $user['id'] ?? $user['idUtilisateur'] ?? null;
        
        $client = $this->clientModel->getById($userId);
        
        $this->view('client/profil-edit', [
            'pageTitle' => 'Modifier mon profil',
            'client' => $client
        ]);
    }

/**
 * Mettre à jour le profil
 */
public function updateProfil()
{
    CSRF::check();
    
    $user = Session::user();
    $userId = $user['id'] ?? $user['idUtilisateur'] ?? null;
        // DEBUG
    error_log("=== AVANT MODIFICATION ===");
    error_log("User: " . print_r($user, true));
    
    $data = [
        'nom' => $_POST['nom'] ?? '',
        'prenom' => $_POST['prenom'] ?? '',
        'email' => $_POST['email'] ?? '',
        'telephone' => $_POST['telephone'] ?? ''
    ];
    
    if ($this->clientModel->update($userId, $data)) {
        $updatedUser = $this->clientModel->getById($userId);

        $newUser = [
            'id' => $userId,
            'idUtilisateur' => $userId,
            'nom' => $updatedUser['nom'],
            'prenom' => $updatedUser['prenom'],
            'nomUtilisateur' => $updatedUser['nom'],
            'prenomUtilisateur' => $updatedUser['prenom'],
            'email' => $updatedUser['email'],
            'emailUtilisateur' => $updatedUser['email'],
            'telephone' => $updatedUser['telephone'],
            'avatar' => $updatedUser['avatar'] ?? $user['avatar'],
            'role' => 'client'
        ];
        
        Session::login($newUser);
        $_SESSION['flash_success'] = 'Profil mis à jour avec succès !';
    } else {
        $_SESSION['flash_error'] = 'Erreur lors de la mise à jour.';
    }
    
    App::redirect('/mon-profil');
}
    /**
     * Page changement mot de passe
     */
    public function password()
    {
        $this->view('client/password', [
            'pageTitle' => 'Changer mon mot de passe'
        ]);
    }

    /**
     * Mettre à jour le mot de passe
     */
    public function updatePassword()
    {
        CSRF::check();
        
        $user = Session::user();
        $userId = $user['id'] ?? $user['idUtilisateur'] ?? null;
        
        $ancien = $_POST['ancien_mot_de_passe'] ?? '';
        $nouveau = $_POST['nouveau_mot_de_passe'] ?? '';
        $confirmation = $_POST['confirmation'] ?? '';
        
        if ($nouveau !== $confirmation) {
            $_SESSION['flash_error'] = 'Les mots de passe ne correspondent pas.';
            App::redirect('/profil/mot-de-passe');
            return;
        }
        
        if ($this->clientModel->updatePassword($userId, $ancien, $nouveau)) {
            $_SESSION['flash_success'] = 'Mot de passe changé avec succès !';
            App::redirect('/mon-profil');
        } else {
            $_SESSION['flash_error'] = 'Ancien mot de passe incorrect.';
            App::redirect('/profil/mot-de-passe');
        }
        
        App::redirect('/profil/mot-de-passe');
    }

    /**
     * Page gestion des adresses
     */
    public function adresses()
    {
        $user = Session::user();
        $userId = $user['id'] ?? $user['idUtilisateur'] ?? null;
        
        $adresses = $this->clientModel->getAdresses($userId);
        
        $this->view('client/adresses', [
            'pageTitle' => 'Mes adresses',
            'adresses' => $adresses
        ]);
    }

    /**
     * Ajouter/modifier une adresse
     */
    public function saveAdresse()
    {
        CSRF::check();
        
        $user = Session::user();
        $userId = $user['id'] ?? $user['idUtilisateur'] ?? null;
        
        $data = [
            'idAdresse' => $_POST['idAdresse'] ?? null,
            'nomDestinataire' => $_POST['nomDestinataire'] ?? '',
            'telephone' => $_POST['telephone'] ?? '',
            'rue' => $_POST['rue'] ?? '',
            'complement' => $_POST['complement'] ?? '',
            'quartier' => $_POST['quartier'] ?? '',
            'ville' => $_POST['ville'] ?? '',
            'codePostal' => $_POST['codePostal'] ?? '',
            'estDefaut' => isset($_POST['estDefaut']) ? 1 : 0
        ];
        
        if ($this->clientModel->saveAdresse($userId, $data)) {
            $_SESSION['flash_success'] = 'Adresse enregistrée !';
        } else {
            $_SESSION['flash_error'] = 'Erreur lors de l\'enregistrement.';
        }
        
        App::redirect('/mes-adresses');
    }

    /**
     * Supprimer une adresse
     */
    public function deleteAdresse()
    {
        CSRF::check();
        
        $idAdresse = $_POST['idAdresse'] ?? null;
        
        if ($idAdresse && $this->clientModel->deleteAdresse($idAdresse)) {
            $_SESSION['flash_success'] = 'Adresse supprimée.';
        } else {
            $_SESSION['flash_error'] = 'Erreur lors de la suppression.';
        }
        
        App::redirect('/mes-adresses');
    }

    /**
     * Upload avatar
     */
    public function uploadAvatar()
    {
        header('Content-Type: application/json');
        
        $user = Session::user();
        $userId = $user['id'] ?? $user['idUtilisateur'] ?? null;
        
        if (!isset($_FILES['avatar'])) {
            echo json_encode(['success' => false, 'error' => 'Aucun fichier']);
            return;
        }
        
        $file = $_FILES['avatar'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        
        if (!in_array($file['type'], $allowed)) {
            echo json_encode(['success' => false, 'error' => 'Format non autorisé']);
            return;
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'avatar_' . $userId . '_' . time() . '.' . $extension;
        $uploadDir = dirname(__DIR__, 2) . '/public/assets/images/avatars/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $destination = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $webPath = '/assets/images/avatars/' . $filename;
            
            if ($this->clientModel->updateAvatar($userId, $webPath)) {
                // Mettre à jour la session
                $user['avatar'] = $webPath;
                Session::login($user);
                
                echo json_encode([
                    'success' => true,
                    'url' => App::baseUrl() . $webPath
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Erreur upload']);
        }
    }
}