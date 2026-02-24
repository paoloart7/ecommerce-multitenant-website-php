<?php
// app/Controllers/AuthController.php

require_once __DIR__ . '/Controller.php';

class AuthController extends Controller
{
    /**
     * Afficher le formulaire de connexion
     */
    public function loginForm(): void
    {
        $this->view('auth/login', [
            'pageTitle' => 'Connexion - ' . App::setting('site_name', 'ShopXPao'),
            'openRegister' => false,
            'isAuthPage' => true
        ]);
    }


    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') App::redirect('login');

        // 1. Sécurités (CSRF / RateLimit)
        $token = $_POST['_csrf_token'] ?? null;
        if (!CSRF::validate($token)) { Session::flash('error', 'Session expirée.'); App::redirect('login'); }

        $rateLimiter = new RateLimiter();
        $ipKey = 'login_' . Security::getClientIP();
        if ($rateLimiter->tooManyAttempts($ipKey)) { Session::flash('error', 'Trop de tentatives.'); App::redirect('login'); }

        // 2. Données & Validation
        $email = Sanitizer::email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $db = Database::getInstance();
        $user = $db->fetch("SELECT * FROM utilisateur WHERE emailUtilisateur = ?", [$email]);

        // 3. Authentification
        if (!$user || !Security::verifyPassword($password, $user['motDePasse'])) {
            $rateLimiter->hit($ipKey);
            Session::flash('error', 'Email ou mot de passe incorrect');
            App::redirect('login');
        }

        if ($user['statut'] !== 'actif') {
            Session::flash('error', 'Votre compte n\'est pas actif.');
            App::redirect('login');
        }

        $userId = $user['idUtilisateur'] ?? $user['id'];
        $user['idUtilisateur'] = $userId; 
        $user['emailUtilisateur'] = $user['emailUtilisateur'] ?? $user['email'] ?? '';
        $user['nomUtilisateur']   = $user['nomUtilisateur'] ?? $user['nom'] ?? '';
        $user['prenomUtilisateur']= $user['prenomUtilisateur'] ?? $user['prenom'] ?? '';

        // 5. Logique Boutique
        if ($user['role'] === 'tenant') {
            $boutique = $db->fetch("SELECT idBoutique FROM boutique WHERE idProprietaire = ?", [$userId]);
            if ($boutique) {
                $user['idBoutique'] = $boutique['idBoutique'];
                $user['tenant_id'] = $boutique['idBoutique'];
            }
        }

        Session::login($user);

        // Mise à jour BDD & Clean
        $db->execute("UPDATE utilisateur SET derniereConnexion = NOW() WHERE idUtilisateur = ?", [$userId]);
        $rateLimiter->clear($ipKey);

        // 7. Redirection
        if ($user['role'] === 'admin') {
            App::redirect('admin');
        } elseif ($user['role'] === 'tenant') {
            if (empty($user['idBoutique'])) {
                App::redirect('vendeur/configuration');
            } else {
                App::redirect('vendeur/tableau-de-bord');
            }
        } else {
            // Client classique
            App::redirect('');
        }
    }    
    
    /**
     * Afficher le formulaire d'inscription
     */
    public function registerForm(): void
    {
        $this->view('auth/login', [
            'pageTitle' => 'Inscription - ' . App::setting('site_name', 'ShopXPao'),
            'openRegister' => true,
            'isAuthPage' => true
        ]);
    }

    /**
     * Traiter l'inscription
     */
    public function register(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') App::redirect('register');

        $token = $_POST['_csrf_token'] ?? null;
        if (!CSRF::validate($token)) {
            Session::flash('error', 'Session expirée.');
            App::redirect('register');
        }

        $data = [
            'prenom' => Sanitizer::string($_POST['prenom'] ?? ''),
            'nom' => Sanitizer::string($_POST['nom'] ?? ''),
            'email' => Sanitizer::email($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'password_confirm'=> $_POST['password_confirm'] ?? '',
            'type_compte' => Sanitizer::string($_POST['type_compte'] ?? ''),
            'terms' => $_POST['terms'] ?? null
        ];

        $validator = new Validator($data);
        $validator->required('prenom', 'Prénom requis')->min('prenom', 2)
                  ->required('nom', 'Nom requis')->min('nom', 2)
                  ->required('email', 'Email requis')->email('email')
                  ->required('password', 'Mot de passe requis')->strongPassword('password')
                  ->match('password', 'password_confirm', 'Mots de passe différents')
                  ->required('type_compte', 'Profil requis');

        if (empty($data['terms'])) $validator->addError('terms', 'Acceptez les conditions');

        if (!$validator->isValid()) {
            Session::flash('error', $validator->getFirstError());
            App::redirect('register');
        }

        $db = Database::getInstance();
        if ($db->fetch("SELECT idUtilisateur FROM utilisateur WHERE emailUtilisateur = ?", [$data['email']])) {
            Session::flash('error', 'Cet email est déjà utilisé.');
            App::redirect('register');
        }

        $role = match ($data['type_compte']) { 
            'buyer' => 'client', 
            'seller', 'both' => 'tenant', 
            default => 'client' 
        };

        $db->beginTransaction();
        try {
            $db->execute(
                "INSERT INTO utilisateur (nomUtilisateur, prenomUtilisateur, emailUtilisateur, motDePasse, role, statut, dateCreation) 
                 VALUES (?, ?, ?, ?, ?, 'actif', NOW())",
                [$data['nom'], $data['prenom'], $data['email'], Security::hashPassword($data['password']), $role]
            );
            $db->commit();

            $msg = ($role === 'tenant') 
                ? 'Compte vendeur créé ! Connectez-vous pour ouvrir votre boutique.' 
                : 'Inscription réussie ! Connectez-vous.';
            
            Session::flash('success', $msg);
            App::redirect('login');

        } catch (Exception $e) {
            $db->rollback();
            error_log('Erreur register: ' . $e->getMessage());
            Session::flash('error', 'Une erreur est survenue.');
            App::redirect('register');
        }
    }

    /**
     * Déconnexion
     */
    public function logout(): void 
    { 
        Session::logout(); 
        Session::flash('success', 'Vous êtes déconnecté.'); 
        App::redirect('login'); 
    }

    /**
     * Mot de passe oublié
     */
    public function forgotPassword(): void 
    { 
        Session::flash('error', 'Cette fonctionnalité sera disponible prochainement.'); 
        App::redirect('login'); 
    }
}