<?php
// app/Controllers/TenantController.php

require_once __DIR__ . '/Controller.php';
require_once dirname(__DIR__) . '/Models/Tenant.php';
require_once dirname(__DIR__) . '/Models/Order.php'; 
require_once dirname(__DIR__, 2) . '/core/CSRF.php'; 
class TenantController extends Controller
{
    private Tenant $tenantModel;
    private Order $orderModel;

    public function __construct()
    {
        $this->tenantModel = new Tenant();
        $this->orderModel = new Order();
        
        $user = Session::user();
        if (!$user || $user['role'] !== 'tenant') {
            App::redirect('/login');
        }
    }

public function dashboard()
{
    $user = Session::user();
    if (!$user) App::redirect('/login');

    $userId = $user['id'] ?? $user['idUtilisateur'] ?? null;

    if (empty($user['idBoutique']) && empty($user['tenant_id'])) {
        $checkShop = $this->tenantModel->getByOwnerId((int)$userId);
        
        if ($checkShop) {
            $userForLogin = [
                'idUtilisateur' => $userId,
                'emailUtilisateur' => $user['email'] ?? $user['emailUtilisateur'] ?? '',
                'nomUtilisateur' => $user['nom'] ?? $user['nomUtilisateur'] ?? '',
                'prenomUtilisateur' => $user['prenom'] ?? $user['prenomUtilisateur'] ?? '',
                'role' => 'tenant',
                'avatar' => $user['avatar'] ?? null,
                'tenant_id' => $checkShop['idBoutique'],
                'idBoutique' => $checkShop['idBoutique'],
            ];
            
            Session::login($userForLogin);
            $user = $userForLogin;
        } else {
            App::redirect('/vendeur/configuration');
            return;
        }
    }
    
    // S'assurer que les deux clés existent
    if (empty($user['idBoutique']) && !empty($user['tenant_id'])) {
        $user['idBoutique'] = $user['tenant_id'];
    }
    if (empty($user['tenant_id']) && !empty($user['idBoutique'])) {
        $user['tenant_id'] = $user['idBoutique'];
    }

    // Récupération des données
    $idB = $user['idBoutique'] ?? $user['tenant_id'];
    
    if (!$idB) {
        App::redirect('/vendeur/configuration');
        return;
    }
    
    $tenantInfo = $this->tenantModel->getById((int)$idB);
    
    // ========== STATISTIQUES RÉELLES ==========
    $db = Database::getInstance();
    
    // 1. Chiffre d'affaires total (commandes validées)
    $ca = $db->fetch(
        "SELECT COALESCE(SUM(total), 0) as ca 
         FROM commande 
         WHERE idBoutique = ? AND statut NOT IN ('annulee', 'remboursee')",
        [$idB]
    )['ca'] ?? 0;
    
    // 2. Nombre de commandes
    $commandes = $db->fetch(
        "SELECT COUNT(*) as total FROM commande WHERE idBoutique = ?",
        [$idB]
    )['total'] ?? 0;
    
    // 3. Produits actifs
    $produits = $db->fetch(
        "SELECT COUNT(*) as total FROM produit WHERE idBoutique = ? AND statutProduit = 'disponible'",
        [$idB]
    )['total'] ?? 0;
    
    // 4. Calcul de la croissance (comparaison mois précédent)
    $caMoisActuel = $db->fetch(
        "SELECT COALESCE(SUM(total), 0) as ca 
         FROM commande 
         WHERE idBoutique = ? 
         AND MONTH(dateCommande) = MONTH(CURDATE()) 
         AND YEAR(dateCommande) = YEAR(CURDATE())
         AND statut NOT IN ('annulee', 'remboursee')",
        [$idB]
    )['ca'] ?? 0;
    
    $caMoisPrecedent = $db->fetch(
        "SELECT COALESCE(SUM(total), 0) as ca 
         FROM commande 
         WHERE idBoutique = ? 
         AND MONTH(dateCommande) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
         AND YEAR(dateCommande) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
         AND statut NOT IN ('annulee', 'remboursee')",
        [$idB]
    )['ca'] ?? 0;
    
    $croissance = 0;
    if ($caMoisPrecedent > 0) {
        $croissance = round(($caMoisActuel - $caMoisPrecedent) / $caMoisPrecedent * 100);
    }
    
    // 5. Top 5 produits les plus vendus
    $topProduits = $db->fetchAll(
        "SELECT p.nomProduit, 
                SUM(cp.quantite) as ventes, 
                SUM(cp.totalLigne) as chiffre
         FROM produit p
         JOIN commande_produit cp ON p.idProduit = cp.idProduit
         JOIN commande c ON cp.idCommande = c.idCommande
         WHERE p.idBoutique = ? 
           AND c.statut NOT IN ('annulee', 'remboursee')
         GROUP BY p.idProduit
         ORDER BY ventes DESC
         LIMIT 5",
        [$idB]
    );
    
    // 6. Activité récente (5 dernières commandes)
    $activiteRecente = $db->fetchAll(
        "SELECT c.numeroCommande, 
                c.total, 
                c.statut, 
                c.dateCommande,
                CONCAT(u.prenomUtilisateur, ' ', u.nomUtilisateur) as client
         FROM commande c
         JOIN utilisateur u ON c.idClient = u.idUtilisateur
         WHERE c.idBoutique = ?
         ORDER BY c.dateCommande DESC
         LIMIT 5",
        [$idB]
    );
    
    // 7. Commandes récentes (pour la sidebar)
    $commandesRecentes = $this->orderModel->getRecentByBoutique((int)$idB, 5);
    
    // Assemblage des stats
    $stats = [
        'ca' => $ca,
        'commandes' => $commandes,
        'produits' => $produits,
        'croissance' => $croissance
    ];

    // Rafraîchir l'avatar
    $db = Database::getInstance();
    $freshUser = $db->fetch("SELECT avatar, prenomUtilisateur as prenom, nomUtilisateur as nom FROM utilisateur WHERE idUtilisateur = ?", [$userId]);
    
    if ($freshUser) {
        $userForLogin = [
            'idUtilisateur' => $userId,
            'emailUtilisateur' => $user['email'] ?? $user['emailUtilisateur'] ?? '',
            'nomUtilisateur' => $freshUser['nom'],
            'prenomUtilisateur' => $freshUser['prenom'],
            'role' => 'tenant',
            'avatar' => $freshUser['avatar'],
            'tenant_id' => $idB,
            'idBoutique' => $idB,
        ];
        
        Session::login($userForLogin);
        $user = $userForLogin;
    }

    // RENDU
    $this->view('tenant/dashboard', [
        'pageTitle'         => 'Tableau de Bord',
        'isMinimalNav'      => true,
        'tenant'            => $tenantInfo,
        'stats'             => $stats,
        'topProduits'       => $topProduits,        // ← NOUVEAU
        'activiteRecente'   => $activiteRecente,    // ← NOUVEAU
        'user'              => $user,
        'commandesRecentes' => $commandesRecentes
    ]);
}

    /**
     * Étape de configuration de la boutique
     */
public function setup()
{
    $user = Session::user();
    $userId = $user['id'] ?? $user['idUtilisateur'] ?? null;

    $checkShop = $this->tenantModel->getByOwnerId((int)$userId);
    
    if ($checkShop || !empty($user['idBoutique']) || !empty($user['tenant_id'])) {
        App::redirect('/vendeur/tableau-de-bord');
        return;
    }

    $this->view('tenant/setup', [
        'pageTitle' => 'Créer ma boutique',
        'isMinimalNav' => true,
        'user' => $user
    ]);
}
public function store()
{
    CSRF::check();
    $user = Session::user();
    
    $userId = $user['id'] ?? $user['idUtilisateur'] ?? null;

    $data = [
        'idProprietaire' => $userId,
        'nomBoutique'    => trim($_POST['nomBoutique']),
        'slugBoutique'   => trim($_POST['slugBoutique']),
        'description'    => trim($_POST['description'])
    ];

    $idBoutique = $this->tenantModel->createShop($data);

    if ($idBoutique) {
        $userForLogin = [
            'idUtilisateur' => $userId,
            'emailUtilisateur' => $user['email'] ?? $user['emailUtilisateur'] ?? '',
            'nomUtilisateur' => $user['nom'] ?? $user['nomUtilisateur'] ?? '',
            'prenomUtilisateur' => $user['prenom'] ?? $user['prenomUtilisateur'] ?? '',
            'role' => 'tenant',
            'avatar' => $user['avatar'] ?? null,
            'tenant_id' => $idBoutique,
            'idBoutique' => $idBoutique,
        ];

        Session::login($userForLogin);
        App::redirect('/vendeur/tableau-de-bord?success=welcome');
    } else {
        App::redirect('/vendeur/configuration?error=creation_failed');
    }
}

    /**
     * Affiche la liste des commandes du vendeur
     */
public function myOrders()
{
    $user = Session::user();
    
    $userForLogin = [
        'idUtilisateur' => $user['id'] ?? $user['idUtilisateur'] ?? null,
        'emailUtilisateur' => $user['email'] ?? $user['emailUtilisateur'] ?? '',
        'nomUtilisateur' => $user['nom'] ?? $user['nomUtilisateur'] ?? '',
        'prenomUtilisateur' => $user['prenom'] ?? $user['prenomUtilisateur'] ?? '',
        'role' => $user['role'] ?? null,
        'avatar' => $user['avatar'] ?? null,
        'tenant_id' => $user['tenant_id'] ?? $user['idBoutique'] ?? null,
        'idBoutique' => $user['idBoutique'] ?? $user['tenant_id'] ?? null,
    ];
    
    Session::login($userForLogin);
    $user = $userForLogin;
    
    $idBoutique = $user['idBoutique'] ?? $user['tenant_id'] ?? null;
    
    if (!$idBoutique) {
        App::redirect('/vendeur/configuration');
        return;
    }
    $tenantInfo = $this->tenantModel->getById((int)$idBoutique);
    
    $filters = [];
    if (!empty($_GET['statut'])) $filters['statut'] = $_GET['statut'];
    if (!empty($_GET['date_debut'])) $filters['date_debut'] = $_GET['date_debut'];
    if (!empty($_GET['date_fin'])) $filters['date_fin'] = $_GET['date_fin'];
    if (!empty($_GET['client'])) $filters['client'] = $_GET['client'];
    
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    
    $ordersData = $this->orderModel->getByBoutique($idBoutique, $filters, $page, $limit);
    
    $stats = [
        'total' => $this->orderModel->countByBoutique($idBoutique),
        'en_attente' => $this->orderModel->countByBoutiqueAndStatus($idBoutique, 'en_attente'),
        'payees' => $this->orderModel->countByBoutiqueAndStatus($idBoutique, 'payee'),
        'livrees' => $this->orderModel->countByBoutiqueAndStatus($idBoutique, 'livree')
    ];
    
    $this->view('orders/my-orders', [
        'pageTitle' => 'Mes Commandes',
        'tenant' => $tenantInfo, 
        'orders' => $ordersData['data'] ?? [],
        'pagination' => [
            'total' => $ordersData['total'] ?? 0,
            'pages' => $ordersData['pages'] ?? 1,
            'current' => $ordersData['current_page'] ?? 1,
            'limit' => $ordersData['limit'] ?? 10
        ],
        'filters' => $filters,
        'stats' => $stats
    ]);
}


    // ==================== PRODUITS ====================

    /**
     * Liste des produits
     */
    public function myProducts()
    {
        $user = $this->normalizeUser(Session::user());
        $idBoutique = $user['idBoutique'] ?? $user['tenant_id'] ?? null;
        
        if (!$idBoutique) {
            App::redirect('/vendeur/configuration');
            return;
        }
        
        // Récupérer les infos de la boutique pour le sidebar
        $tenantInfo = $this->tenantModel->getById((int)$idBoutique);
        
        // Filtres
        $filters = [];
        if (!empty($_GET['categorie'])) $filters['categorie'] = $_GET['categorie'];
        if (!empty($_GET['statut'])) $filters['statut'] = $_GET['statut'];
        if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];
        
        // Pagination
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        
        // Récupération des produits
        require_once dirname(__DIR__) . '/Models/TenantProduct.php';
        $productModel = new TenantProduct();
        $productsData = $productModel->getByBoutique($idBoutique, $filters, $page, $limit);
        $stats = $productModel->getStats($idBoutique);
        
        // Récupérer les catégories pour le filtre
        require_once dirname(__DIR__) . '/Models/TenantCategory.php';
        $categoryModel = new TenantCategory();
        $categories = $categoryModel->getByBoutique($idBoutique);
        
        $this->view('products/my-products', [
            'pageTitle' => 'Mes Produits',
            'tenant' => $tenantInfo,
            'products' => $productsData['data'] ?? [],
            'pagination' => [
                'total' => $productsData['total'] ?? 0,
                'pages' => $productsData['pages'] ?? 1,
                'current' => $productsData['current_page'] ?? 1,
                'limit' => $productsData['limit'] ?? 10
            ],
            'filters' => $filters,
            'stats' => $stats,
            'categories' => $categories
        ]);
    }

    /**
     * Formulaire d'ajout de produit
     */
public function addProduct()
{
    error_log("=== addProduct() appelée ===");
    
    $user = $this->normalizeUser(Session::user());
    $idBoutique = $user['idBoutique'] ?? $user['tenant_id'] ?? null;
    
    if (!$idBoutique) {
        App::redirect('/vendeur/configuration');
        return;
    }
    
    $tenantInfo = $this->tenantModel->getById((int)$idBoutique);
    
    require_once dirname(__DIR__) . '/Models/TenantCategory.php';
    $categoryModel = new TenantCategory();
    $categories = $categoryModel->getByBoutique($idBoutique);
    
    $this->view('products/product-add', [
        'pageTitle' => 'Ajouter un produit',
        'tenant' => $tenantInfo,
        'categories' => $categories,
    ]);
}

    /**
     * Traitement de l'ajout de produit
     */
public function saveProduct()
{
    CSRF::check();
    
    $user = $this->normalizeUser(Session::user());
    $idBoutique = $user['idBoutique'] ?? $user['tenant_id'] ?? null;
    
    if (!$idBoutique) {
        App::redirect('/vendeur/configuration');
        return;
    }
    
    require_once dirname(__DIR__) . '/Models/TenantProduct.php';
    $productModel = new TenantProduct();
    
    // ✅ Construction du JSON dimensions à partir des 3 champs
    $dimensions = null;
    if (!empty($_POST['longueur']) || !empty($_POST['largeur']) || !empty($_POST['hauteur'])) {
        $dimensions = json_encode([
            'longueur' => !empty($_POST['longueur']) ? (float)$_POST['longueur'] : 0,
            'largeur' => !empty($_POST['largeur']) ? (float)$_POST['largeur'] : 0,
            'hauteur' => !empty($_POST['hauteur']) ? (float)$_POST['hauteur'] : 0
        ], JSON_FORCE_OBJECT);
    }
    
    // Préparer les données
    $data = [
        'idCategorie' => !empty($_POST['idCategorie']) ? (int)$_POST['idCategorie'] : null,
        'nomProduit' => trim($_POST['nomProduit']),
        'slugProduit' => !empty($_POST['slugProduit']) ? trim($_POST['slugProduit']) : null,
        'descriptionCourte' => trim($_POST['descriptionCourte'] ?? ''),
        'descriptionComplete' => trim($_POST['descriptionComplete'] ?? ''),
        'prix' => (float)$_POST['prix'],
        'prixPromo' => !empty($_POST['prixPromo']) ? (float)$_POST['prixPromo'] : null,
        'dateDebutPromo' => !empty($_POST['dateDebutPromo']) ? $_POST['dateDebutPromo'] : null,
        'dateFinPromo' => !empty($_POST['dateFinPromo']) ? $_POST['dateFinPromo'] : null,
        'cout' => !empty($_POST['cout']) ? (float)$_POST['cout'] : null,
        'stock' => (int)$_POST['stock'],
        'stockAlerte' => (int)$_POST['stockAlerte'],
        'sku' => !empty($_POST['sku']) ? trim($_POST['sku']) : null,
        'codeBarres' => !empty($_POST['codeBarres']) ? trim($_POST['codeBarres']) : null,
        'poids' => !empty($_POST['poids']) ? (float)$_POST['poids'] : null,
        'dimensions' => $dimensions, // ✅ Maintenant c'est du JSON valide ou null
        'statutProduit' => $_POST['statutProduit'] ?? 'brouillon',
        'misEnAvant' => isset($_POST['misEnAvant']) ? 1 : 0,
        'nouveaute' => isset($_POST['nouveaute']) ? 1 : 0
    ];
    
    $idProduit = $productModel->create($idBoutique, $data);
    
    if ($idProduit) {
        // ✅ Gestion des images uploadées
        $this->handleProductImages($idProduit);
        
        $_SESSION['flash_success'] = 'Produit créé avec succès !';
        App::redirect('/vendeur/mes-produits');
    } else {
        $_SESSION['flash_error'] = 'Erreur lors de la création du produit.';
        App::redirect('/vendeur/produit/ajouter');
    }
}

/**
 * Gère l'upload multiple des images
 */
private function handleProductImages(int $idProduit)
{
    // Vérifier si des fichiers ont été uploadés
    if (empty($_FILES['images']) || empty($_FILES['images']['name'][0])) {
        return;
    }
    
    $uploadDir = dirname(__DIR__, 2) . '/public/assets/images/produits/';
    
    // Créer le dossier s'il n'existe pas
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    require_once dirname(__DIR__) . '/Models/ProductImage.php';
    $imageModel = new ProductImage();
    
    $ordre = 0;
    $files = $_FILES['images'];
    
    foreach ($files['tmp_name'] as $key => $tmpName) {
        // Vérifier s'il n'y a pas d'erreur
        if ($files['error'][$key] !== UPLOAD_ERR_OK) {
            continue;
        }
        
        // Vérifier le type de fichier
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        if (!in_array($files['type'][$key], $allowedTypes)) {
            continue;
        }
        
        // Générer un nom unique
        $extension = pathinfo($files['name'][$key], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $destination = $uploadDir . $filename;
        
        // Déplacer le fichier
        if (move_uploaded_file($tmpName, $destination)) {
            // Chemin pour la base de données (relatif au dossier public)
            $dbPath = '/assets/images/produits/' . $filename;
            
            // Insérer dans image_produit
            $imageModel->create([
                'idProduit' => $idProduit,
                'urlImage' => $dbPath,
                'urlThumbnail' => null, // Optionnel, à générer plus tard
                'altText' => $_POST['nomProduit'] ?? 'Produit',
                'ordre' => $ordre,
                'estPrincipale' => ($ordre === 0) ? 1 : 0 // Première image = principale
            ]);
            
            $ordre++;
        }
    }
}    /**
     * Formulaire de modification
     */
public function editProduct()
{
    error_log("=== editProduct() ===");
    error_log("GET id: " . ($_GET['id'] ?? 'NULL'));
    
    $idProduit = $_GET['id'] ?? null;
    if (!$idProduit) {
        error_log("Pas d'ID → redirection");
        App::redirect('/vendeur/mes-produits');
        return;
    }
    
    $user = $this->normalizeUser(Session::user());
    $idBoutique = $user['idBoutique'] ?? $user['tenant_id'] ?? null;
    error_log("ID Boutique: " . $idBoutique);
    
    require_once dirname(__DIR__) . '/Models/TenantProduct.php';
    $productModel = new TenantProduct();
    $product = $productModel->getFullDetails((int)$idProduit);
    error_log("Produit trouvé: " . ($product ? 'OUI' : 'NON'));
    
    if (!$product || $product['idBoutique'] != $idBoutique) {
        error_log("Produit non autorisé");
        $_SESSION['flash_error'] = 'Produit non trouvé ou accès non autorisé.';
        App::redirect('/vendeur/mes-produits');
        return;
    }
    
    $tenantInfo = $this->tenantModel->getById((int)$idBoutique);
    
    require_once dirname(__DIR__) . '/Models/TenantCategory.php';
    $categoryModel = new TenantCategory();
    $categories = $categoryModel->getByBoutique($idBoutique);
    error_log("Catégories trouvées: " . count($categories));
    
    // Extraire les dimensions
    $dimensions = json_decode($product['dimensions'] ?? '', true);
    $product['longueur'] = $dimensions['longueur'] ?? '';
    $product['largeur'] = $dimensions['largeur'] ?? '';
    $product['hauteur'] = $dimensions['hauteur'] ?? '';
    
    error_log("=== Affichage vue product-edit ===");
    
    $this->view('products/product-edit', [
        'pageTitle' => 'Modifier le produit',
        'tenant' => $tenantInfo,
        'product' => $product,
        'categories' => $categories
    ]);
}/**
 * Traitement de la modification d'un produit
 */
public function updateProduct()
{
    CSRF::check();
    
    $idProduit = $_POST['idProduit'] ?? null;
    if (!$idProduit) {
        App::redirect('/vendeur/mes-produits');
        return;
    }
    
    $user = $this->normalizeUser(Session::user());
    $idBoutique = $user['idBoutique'] ?? $user['tenant_id'] ?? null;
    
    require_once dirname(__DIR__) . '/Models/TenantProduct.php';
    $productModel = new TenantProduct();
    
    // Vérifier que le produit appartient à cette boutique
    $product = $productModel->getFullDetails((int)$idProduit);
    if (!$product || $product['idBoutique'] != $idBoutique) {
        $_SESSION['flash_error'] = 'Action non autorisée.';
        App::redirect('/vendeur/mes-produits');
        return;
    }
    
    // ✅ Construction du JSON dimensions
    $dimensions = null;
    if (!empty($_POST['longueur']) || !empty($_POST['largeur']) || !empty($_POST['hauteur'])) {
        $dimensions = json_encode([
            'longueur' => !empty($_POST['longueur']) ? (float)$_POST['longueur'] : 0,
            'largeur' => !empty($_POST['largeur']) ? (float)$_POST['largeur'] : 0,
            'hauteur' => !empty($_POST['hauteur']) ? (float)$_POST['hauteur'] : 0
        ], JSON_FORCE_OBJECT);
    }
    
    // Préparer les données
    $data = [
        'idCategorie' => !empty($_POST['idCategorie']) ? (int)$_POST['idCategorie'] : null,
        'nomProduit' => trim($_POST['nomProduit']),
        'slugProduit' => !empty($_POST['slugProduit']) ? trim($_POST['slugProduit']) : null,
        'descriptionCourte' => trim($_POST['descriptionCourte'] ?? ''),
        'descriptionComplete' => trim($_POST['descriptionComplete'] ?? ''),
        'prix' => (float)$_POST['prix'],
        'prixPromo' => !empty($_POST['prixPromo']) ? (float)$_POST['prixPromo'] : null,
        'dateDebutPromo' => !empty($_POST['dateDebutPromo']) ? $_POST['dateDebutPromo'] : null,
        'dateFinPromo' => !empty($_POST['dateFinPromo']) ? $_POST['dateFinPromo'] : null,
        'cout' => !empty($_POST['cout']) ? (float)$_POST['cout'] : null,
        'stock' => (int)$_POST['stock'],
        'stockAlerte' => (int)$_POST['stockAlerte'],
        'sku' => !empty($_POST['sku']) ? trim($_POST['sku']) : null,
        'codeBarres' => !empty($_POST['codeBarres']) ? trim($_POST['codeBarres']) : null,
        'poids' => !empty($_POST['poids']) ? (float)$_POST['poids'] : null,
        'dimensions' => $dimensions,
        'statutProduit' => $_POST['statutProduit'] ?? 'brouillon',
        'misEnAvant' => isset($_POST['misEnAvant']) ? 1 : 0,
        'nouveaute' => isset($_POST['nouveaute']) ? 1 : 0
    ];
    
    if ($productModel->update((int)$idProduit, $data)) {
        // ✅ Gestion des nouvelles images uploadées
        if (!empty($_FILES['images']['name'][0])) {
            $this->handleProductImages((int)$idProduit);
        }
        
        $_SESSION['flash_success'] = 'Produit mis à jour avec succès !';
        App::redirect('/vendeur/mes-produits');
    } else {
        $_SESSION['flash_error'] = 'Erreur lors de la mise à jour.';
        App::redirect('/vendeur/produit/modifier?id=' . $idProduit);
    }
}
    /**
     * Supprimer un produit
     */
/**
 * Supprimer un produit
 */


public function deleteProduct()
{
    // Récupérer l'ID depuis l'URL (GET) ou le formulaire (POST)
    $idProduit = $_GET['id'] ?? $_POST['idProduit'] ?? null;
    
    if (!$idProduit) {
        $_SESSION['flash_error'] = 'ID produit manquant';
        App::redirect('/vendeur/mes-produits');
        return;
    }
    
    $user = $this->normalizeUser(Session::user());
    $idBoutique = $user['idBoutique'] ?? $user['tenant_id'] ?? null;
    
    require_once dirname(__DIR__) . '/Models/TenantProduct.php';
    $productModel = new TenantProduct();
    
    // Vérifier que le produit appartient à cette boutique
    $product = $productModel->getFullDetails((int)$idProduit);
    if (!$product || $product['idBoutique'] != $idBoutique) {
        $_SESSION['flash_error'] = 'Action non autorisée.';
        App::redirect('/vendeur/mes-produits');
        return;
    }
    
    if ($productModel->delete((int)$idProduit)) {
        $_SESSION['flash_success'] = 'Produit supprimé avec succès !';
    } else {
        $_SESSION['flash_error'] = 'Erreur lors de la suppression.';
    }
    
    App::redirect('/vendeur/mes-produits');
}
    /**
     * Upload d'image via AJAX
     */
    public function uploadProductImage()
    {
        header('Content-Type: application/json');
        
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'Erreur upload']);
            return;
        }
        
        $idProduit = $_POST['idProduit'] ?? null;
        if (!$idProduit) {
            echo json_encode(['success' => false, 'error' => 'ID produit manquant']);
            return;
        }
        
        $file = $_FILES['image'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        
        if (!in_array($file['type'], $allowed)) {
            echo json_encode(['success' => false, 'error' => 'Format non autorisé']);
            return;
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/products/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $uploadPath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            require_once dirname(__DIR__) . '/Models/ProductImage.php';
            $imageModel = new ProductImage();
            
            $webPath = '/uploads/products/' . $filename;
            
            $idImage = $imageModel->create([
                'idProduit' => $idProduit,
                'urlImage' => $webPath,
                'estPrincipale' => 0
            ]);
            
            if ($idImage) {
                echo json_encode([
                    'success' => true,
                    'idImage' => $idImage,
                    'url' => $webPath
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Erreur sauvegarde']);
        }
    }

    /**
     * Définir une image comme principale
     */
    public function setPrincipalImage()
    {
        header('Content-Type: application/json');
        
        $idImage = $_POST['idImage'] ?? null;
        $idProduit = $_POST['idProduit'] ?? null;
        
        if (!$idImage || !$idProduit) {
            echo json_encode(['success' => false]);
            return;
        }
        
        require_once dirname(__DIR__) . '/Models/ProductImage.php';
        $imageModel = new ProductImage();
        
        if ($imageModel->setPrincipal((int)$idProduit, (int)$idImage)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
    }

    /**
     * Supprimer une image
     */
    public function deleteProductImage()
    {
        header('Content-Type: application/json');
        
        $idImage = $_POST['idImage'] ?? null;
        
        if (!$idImage) {
            echo json_encode(['success' => false]);
            return;
        }
        
        require_once dirname(__DIR__) . '/Models/ProductImage.php';
        $imageModel = new ProductImage();
        
        if ($imageModel->delete((int)$idImage)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
    }

    /**
     * Helper pour normaliser l'utilisateur
     */
    private function normalizeUser($user)
    {
        $normalized = [
            'idUtilisateur' => $user['id'] ?? $user['idUtilisateur'] ?? null,
            'emailUtilisateur' => $user['email'] ?? $user['emailUtilisateur'] ?? '',
            'nomUtilisateur' => $user['nom'] ?? $user['nomUtilisateur'] ?? '',
            'prenomUtilisateur' => $user['prenom'] ?? $user['prenomUtilisateur'] ?? '',
            'role' => $user['role'] ?? null,
            'avatar' => $user['avatar'] ?? null,
            'tenant_id' => $user['tenant_id'] ?? $user['idBoutique'] ?? null,
            'idBoutique' => $user['idBoutique'] ?? $user['tenant_id'] ?? null,
        ];
        
        Session::login($normalized);
        return $normalized;
    }



    // ==================== PARAMÈTRES BOUTIQUE ====================

/**
 * Affiche la page des paramètres
 */
public function shopSettings()
{
    $user = $this->normalizeUser(Session::user());
    $idBoutique = $user['idBoutique'] ?? $user['tenant_id'] ?? null;
    
    if (!$idBoutique) {
        App::redirect('/vendeur/configuration');
        return;
    }
    
    // Récupérer les paramètres
    require_once dirname(__DIR__) . '/Models/TenantSettings.php';
    $settingsModel = new TenantSettings();
    $settings = $settingsModel->getByBoutique((int)$idBoutique);
    
    // Récupérer les infos de la boutique pour le sidebar
    $tenantInfo = $this->tenantModel->getById((int)$idBoutique);
    
    $this->view('tenant/shop-settings', [
        'pageTitle' => 'Paramètres boutique',
        'tenant' => $tenantInfo,
        'settings' => $settings
    ]);
}

/**
 * Sauvegarde les paramètres généraux
 */
public function updateShopSettings()
{
    CSRF::check();
    
    $user = $this->normalizeUser(Session::user());
    $idBoutique = $user['idBoutique'] ?? $user['tenant_id'] ?? null;
    
    if (!$idBoutique) {
        App::redirect('/vendeur/configuration');
        return;
    }
    
    require_once dirname(__DIR__) . '/Models/TenantSettings.php';
    $settingsModel = new TenantSettings();
    
    // Mise à jour selon l'onglet
    $tab = $_POST['tab'] ?? 'general';
    
    switch ($tab) {
        case 'general':
            $settingsModel->updateGeneral($idBoutique, $_POST);
            if (!empty($_POST['description'])) {
                $settingsModel->updateDescription($idBoutique, $_POST['description']);
            }
            break;
            
        case 'social':
            $social = [
                'facebook' => $_POST['facebook'] ?? '',
                'instagram' => $_POST['instagram'] ?? '',
                'twitter' => $_POST['twitter'] ?? '',
                'whatsapp' => $_POST['whatsapp'] ?? ''
            ];
            $settingsModel->updateSocial($idBoutique, $social);
            break;
            
        case 'policies':
            $settingsModel->updatePolicies($idBoutique, $_POST);
            break;
    }
    
    $_SESSION['flash_success'] = 'Paramètres mis à jour avec succès !';
    App::redirect('/vendeur/parametres?tab=' . $tab);
}

/**
 * Upload du logo (AJAX)
 */
public function uploadShopLogo()
{
    header('Content-Type: application/json');
    
    $user = $this->normalizeUser(Session::user());
    $idBoutique = $user['idBoutique'] ?? $user['tenant_id'] ?? null;
    
    if (!$idBoutique || !isset($_FILES['logo'])) {
        echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
        return;
    }
    
    $file = $_FILES['logo'];
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];
    
    if (!in_array($file['type'], $allowed)) {
        echo json_encode(['success' => false, 'error' => 'Format non autorisé']);
        return;
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'logo_' . $idBoutique . '_' . time() . '.' . $extension;
    $uploadDir = dirname(__DIR__, 2) . '/public/assets/images/shops/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $destination = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $webPath = '/assets/images/shops/' . $filename;
        
        require_once dirname(__DIR__) . '/Models/TenantSettings.php';
        $settingsModel = new TenantSettings();
        $settingsModel->updateLogo($idBoutique, $webPath);
        
        echo json_encode([
            'success' => true,
            'url' => App::baseUrl() . $webPath,
            'path' => $webPath
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erreur upload']);
    }
}

/**
 * Upload de la bannière (AJAX)
 */
public function uploadShopBanner()
{
    header('Content-Type: application/json');
    
    $user = $this->normalizeUser(Session::user());
    $idBoutique = $user['idBoutique'] ?? $user['tenant_id'] ?? null;
    
    if (!$idBoutique || !isset($_FILES['banner'])) {
        echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
        return;
    }
    
    $file = $_FILES['banner'];
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    
    if (!in_array($file['type'], $allowed)) {
        echo json_encode(['success' => false, 'error' => 'Format non autorisé']);
        return;
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'banner_' . $idBoutique . '_' . time() . '.' . $extension;
    $uploadDir = dirname(__DIR__, 2) . '/public/assets/images/shops/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $destination = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $webPath = '/assets/images/shops/' . $filename;
        
        require_once dirname(__DIR__) . '/Models/TenantSettings.php';
        $settingsModel = new TenantSettings();
        $settingsModel->updateBanner($idBoutique, $webPath);
        
        echo json_encode([
            'success' => true,
            'url' => App::baseUrl() . $webPath,
            'path' => $webPath
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erreur upload']);
    }
}




// ==================== CATÉGORIES ====================


/**
 * Récupération des sous-catégories (AJAX)
 */
public function getSubCategories()
{
    header('Content-Type: application/json');
    
    $idParent = $_GET['id'] ?? null;
    if (!$idParent) {
        echo json_encode([]);
        return;
    }
    
    $user = $this->normalizeUser(Session::user());
    $idBoutique = $user['idBoutique'] ?? $user['tenant_id'] ?? null;
    
    require_once dirname(__DIR__) . '/Models/TenantCategory.php';
    $categoryModel = new TenantCategory();
    
    $subs = $categoryModel->getSubCategories((int)$idParent);
    
    // Filtrer pour s'assurer que seules les catégories de la boutique sont retournées
    $filtered = array_filter($subs, function($sub) use ($idBoutique) {
        return $sub['idBoutique'] == $idBoutique;
    });
    
    echo json_encode(array_values($filtered));
}



/**
 * Liste des catégories parentes
 */
public function categories()
{
    $user = $this->normalizeUser(Session::user());
    $idBoutique = $user['idBoutique'] ?? $user['tenant_id'] ?? null;
    
    if (!$idBoutique) {
        App::redirect('/vendeur/configuration');
        return;
    }
    
    require_once dirname(__DIR__) . '/Models/TenantCategory.php';
    $categoryModel = new TenantCategory();
    
    // Récupérer uniquement les parents avec stats
    $parents = $categoryModel->getParentsWithStats($idBoutique);
    $stats = $categoryModel->getStats($idBoutique);
    $tenantInfo = $this->tenantModel->getById((int)$idBoutique);
    
    $this->view('tenant/categories/index', [
        'pageTitle' => 'Catégories',
        'tenant' => $tenantInfo,
        'parents' => $parents,
        'stats' => $stats
    ]);
}

/**
 * Formulaire d'ajout de catégorie
 */
public function addCategory()
{
    $user = $this->normalizeUser(Session::user());
    $idBoutique = $user['idBoutique'] ?? $user['tenant_id'] ?? null;
    
    if (!$idBoutique) {
        App::redirect('/vendeur/configuration');
        return;
    }
    
    require_once dirname(__DIR__) . '/Models/TenantCategory.php';
    $categoryModel = new TenantCategory();
    
    // Récupérer les parents pour le select (une catégorie parente peut avoir des sous-catégories)
    $parents = $categoryModel->getParents($idBoutique);
    $tenantInfo = $this->tenantModel->getById((int)$idBoutique);
    
    $this->view('tenant/categories/add', [
        'pageTitle' => 'Ajouter une catégorie',
        'tenant' => $tenantInfo,
        'parents' => $parents
    ]);
}

/**
 * Traitement de l'ajout
 */
public function saveCategory()
{
    CSRF::check();
    
    $user = $this->normalizeUser(Session::user());
    $idBoutique = $user['idBoutique'] ?? $user['tenant_id'] ?? null;
    
    if (!$idBoutique) {
        App::redirect('/vendeur/configuration');
        return;
    }
    
    require_once dirname(__DIR__) . '/Models/TenantCategory.php';
    $categoryModel = new TenantCategory();
    
    // Générer le slug si non fourni
    $slug = !empty($_POST['slugCategorie']) ? 
            trim($_POST['slugCategorie']) : 
            $this->generateSlug($_POST['nomCategorie']);
    
    $data = [
        'idCategorieParent' => !empty($_POST['idCategorieParent']) ? (int)$_POST['idCategorieParent'] : null,
        'nomCategorie' => trim($_POST['nomCategorie']),
        'slugCategorie' => $slug,
        'description' => trim($_POST['description'] ?? ''),
        'image' => null, // À gérer plus tard avec upload
        'ordre' => (int)($_POST['ordre'] ?? 0),
        'actif' => isset($_POST['actif']) ? 1 : 0
    ];
    
    $idCategorie = $categoryModel->create($idBoutique, $data);
    
    if ($idCategorie) {
        $_SESSION['flash_success'] = 'Catégorie créée avec succès !';
        App::redirect('/vendeur/categories');
    } else {
        $_SESSION['flash_error'] = 'Erreur : le nom existe déjà ou données invalides.';
        App::redirect('/vendeur/categorie/ajouter');
    }
}

/**
 * Formulaire de modification
 */
public function editCategory()
{
    $idCategorie = $_GET['id'] ?? null;
    if (!$idCategorie) {
        App::redirect('/vendeur/categories');
        return;
    }
    
    $user = $this->normalizeUser(Session::user());
    $idBoutique = $user['idBoutique'] ?? $user['tenant_id'] ?? null;
    
    require_once dirname(__DIR__) . '/Models/TenantCategory.php';
    $categoryModel = new TenantCategory();
    
    $category = $categoryModel->getById((int)$idCategorie);
    
    if (!$category || $category['idBoutique'] != $idBoutique) {
        $_SESSION['flash_error'] = 'Catégorie non trouvée';
        App::redirect('/vendeur/categories');
        return;
    }
    
    $tenantInfo = $this->tenantModel->getById((int)$idBoutique);
    
    // ✅ Vérifier si c'est une sous-catégorie (idCategorieParent NON NULL)
    if ($category['idCategorieParent'] !== null) {
        // C'est une sous-catégorie
        $parent = $categoryModel->getById((int)$category['idCategorieParent']);
        
        $this->view('tenant/categories/subcategories-edit', [
            'pageTitle' => 'Modifier la sous-catégorie',
            'tenant' => $tenantInfo,
            'category' => $category,
            'parent' => $parent
        ]);
    } else {
        // C'est une catégorie parente (idCategorieParent IS NULL)
        $parents = $categoryModel->getParents($idBoutique);
        
        $this->view('tenant/categories/edit', [
            'pageTitle' => 'Modifier la catégorie',
            'tenant' => $tenantInfo,
            'category' => $category,
            'parents' => $parents
        ]);
    }
}
/**
 * Traitement de la modification
 */

public function updateCategory()
{
    CSRF::check();
    
    $idCategorie = $_POST['idCategorie'] ?? null;
    if (!$idCategorie) {
        App::redirect('/vendeur/categories');
        return;
    }
    
    $user = $this->normalizeUser(Session::user());
    $idBoutique = $user['idBoutique'] ?? $user['tenant_id'] ?? null;
    
    require_once dirname(__DIR__) . '/Models/TenantCategory.php';
    $categoryModel = new TenantCategory();
    
    // Récupérer la catégorie avant modification
    $category = $categoryModel->getById((int)$idCategorie);
    
    if (!$category || $category['idBoutique'] != $idBoutique) {
        $_SESSION['flash_error'] = 'Action non autorisée';
        App::redirect('/vendeur/categories');
        return;
    }
    
    // ✅ Déterminer le parent à conserver
    $idParent = null;
    
    // Si c'était une sous-catégorie avant, elle le reste
    if ($category['idCategorieParent'] !== null) {
        // C'est une sous-catégorie → on garde le même parent
        $idParent = $category['idCategorieParent'];
    } else {
        // C'est une catégorie parente → on peut changer le parent si fourni
        $idParent = !empty($_POST['idCategorieParent']) ? (int)$_POST['idCategorieParent'] : null;
    }
    
    // Générer le slug
    $slug = !empty($_POST['slugCategorie']) ? 
            trim($_POST['slugCategorie']) : 
            $this->generateSlug($_POST['nomCategorie']);
    
    $data = [
        'idCategorieParent' => $idParent,
        'nomCategorie' => trim($_POST['nomCategorie']),
        'slugCategorie' => $slug,
        'description' => trim($_POST['description'] ?? ''),
        'image' => null,
        'ordre' => (int)($_POST['ordre'] ?? 0),
        'actif' => isset($_POST['actif']) ? 1 : 0
    ];
    
    if ($categoryModel->update((int)$idCategorie, $data)) {
        $_SESSION['flash_success'] = 'Catégorie mise à jour avec succès !';
    } else {
        $_SESSION['flash_error'] = 'Erreur lors de la mise à jour';
    }
    
    // ✅ Rediriger vers la bonne page
    if ($category['idCategorieParent'] !== null) {
        // C'était une sous-catégorie → retour aux sous-catégories du parent
        App::redirect('/vendeur/sous-categories?parent=' . $category['idCategorieParent']);
    } else {
        // C'était une catégorie parente → retour aux catégories
        App::redirect('/vendeur/categories');
    }
}

/**
 * Suppression d'une catégorie
 */
public function deleteCategory()
{
    CSRF::check();
    
    $idCategorie = $_POST['idCategorie'] ?? null;
    if (!$idCategorie) {
        App::redirect('/vendeur/categories');
        return;
    }
    
    $user = $this->normalizeUser(Session::user());
    $idBoutique = $user['idBoutique'] ?? $user['tenant_id'] ?? null;
    
    require_once dirname(__DIR__) . '/Models/TenantCategory.php';
    $categoryModel = new TenantCategory();
    
    // Vérifier que la catégorie appartient à cette boutique
    $category = $categoryModel->getById((int)$idCategorie);
    if (!$category || $category['idBoutique'] != $idBoutique) {
        $_SESSION['flash_error'] = 'Action non autorisée';
        App::redirect('/vendeur/categories');
        return;
    }
    
    if ($categoryModel->delete((int)$idCategorie)) {
        $_SESSION['flash_success'] = 'Catégorie supprimée avec succès !';
    } else {
        $_SESSION['flash_error'] = 'Impossible de supprimer : des produits sont liés à cette catégorie.';
    }
    
    App::redirect('/vendeur/categories');
}

/**
 * Liste des sous-catégories d'une catégorie parente
 */
public function subCategories()
{
    $idParent = $_GET['parent'] ?? null;
    if (!$idParent) {
        App::redirect('/vendeur/categories');
        return;
    }
    
    $user = $this->normalizeUser(Session::user());
    $idBoutique = $user['idBoutique'] ?? $user['tenant_id'] ?? null;
    
    require_once dirname(__DIR__) . '/Models/TenantCategory.php';
    $categoryModel = new TenantCategory();
    
    $parent = $categoryModel->getById((int)$idParent);
    if (!$parent || $parent['idBoutique'] != $idBoutique) {
        $_SESSION['flash_error'] = 'Catégorie parente non trouvée';
        App::redirect('/vendeur/categories');
        return;
    }
    
    $subCategories = $categoryModel->getSubCategories((int)$idParent);
    
    $stats = [
        'total' => count($subCategories),
        'actives' => count(array_filter($subCategories, fn($c) => $c['actif']))
    ];
    
    $tenantInfo = $this->tenantModel->getById((int)$idBoutique);
    
    $this->view('tenant/categories/subcategories', [
        'pageTitle' => 'Sous-catégories de ' . $parent['nomCategorie'],
        'tenant' => $tenantInfo,
        'parent' => $parent,
        'subCategories' => $subCategories,
        'stats' => $stats
    ]);
}

/**
 * Formulaire d'ajout de sous-catégorie
 */
public function addSubCategory()
{
    $idParent = $_GET['parent'] ?? null;
    if (!$idParent) {
        App::redirect('/vendeur/categories');
        return;
    }
    
    $user = $this->normalizeUser(Session::user());
    $idBoutique = $user['idBoutique'] ?? $user['tenant_id'] ?? null;
    
    require_once dirname(__DIR__) . '/Models/TenantCategory.php';
    $categoryModel = new TenantCategory();
    
    $parent = $categoryModel->getById((int)$idParent);
    if (!$parent || $parent['idBoutique'] != $idBoutique) {
        $_SESSION['flash_error'] = 'Catégorie parente non trouvée';
        App::redirect('/vendeur/categories');
        return;
    }
    
    $tenantInfo = $this->tenantModel->getById((int)$idBoutique);
    
    $this->view('tenant/categories/add-sub', [
        'pageTitle' => 'Ajouter une sous-catégorie à ' . $parent['nomCategorie'],
        'tenant' => $tenantInfo,
        'parent' => $parent
    ]);
}

/**
 * Helper pour générer un slug
 */
private function generateSlug(string $string): string
{
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

/**
 * Page des statistiques
 */
// ==================== STATISTIQUES ====================


public function statistiques()
{
    $user = $this->normalizeUser(Session::user());
    $idBoutique = $user['idBoutique'] ?? $user['tenant_id'] ?? null;
    
    if (!$idBoutique) {
        App::redirect('/vendeur/configuration');
        return;
    }
    
    $stats = [
        'global' => $this->getGlobalStats($idBoutique),
        'evolution' => $this->getEvolutionVentes($idBoutique),
        'topProduits' => $this->getTopProduits($idBoutique),
        'topClients' => $this->getTopClients($idBoutique),
        'commandesParStatut' => $this->getCommandesParStatut($idBoutique),
        'caParJour' => $this->getCAParJour($idBoutique, 30)
    ];
    
    $tenantInfo = $this->tenantModel->getById((int)$idBoutique);
    
    $this->view('tenant/stats/index', [
        'pageTitle' => 'Statistiques',
        'tenant' => $tenantInfo,
        'stats' => $stats
    ]);
}

/**
 * Rapports mensuels (PDF/Export)
 */
public function rapports()
{
    $user = $this->normalizeUser(Session::user());
    $idBoutique = $user['idBoutique'] ?? $user['tenant_id'] ?? null;
    
    if (!$idBoutique) {
        App::redirect('/vendeur/configuration');
        return;
    }
    
    $mois = $_GET['mois'] ?? date('m');
    $annee = $_GET['annee'] ?? date('Y');
    
    $tenantInfo = $this->tenantModel->getById((int)$idBoutique);
    
    $this->view('tenant/stats/rapports', [
        'pageTitle' => 'Rapports mensuels',
        'tenant' => $tenantInfo,
        'mois' => $mois,
        'annee' => $annee
    ]);
}

/**
 * Statistiques globales
 */
private function getGlobalStats(int $idBoutique): array
{
    $db = Database::getInstance();
    
    $sqlCA = "SELECT COALESCE(SUM(total), 0) as total 
              FROM commande 
              WHERE idBoutique = :idBoutique 
              AND statut NOT IN ('annulee', 'remboursee')";
    $ca = $db->fetch($sqlCA, ['idBoutique' => $idBoutique])['total'] ?? 0;
    
    $sqlCmd = "SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN DATE(dateCommande) = CURDATE() THEN 1 END) as aujourdhui,
                COUNT(CASE WHEN YEARWEEK(dateCommande) = YEARWEEK(CURDATE()) THEN 1 END) as semaine,
                COUNT(CASE WHEN MONTH(dateCommande) = MONTH(CURDATE()) AND YEAR(dateCommande) = YEAR(CURDATE()) THEN 1 END) as mois
               FROM commande 
               WHERE idBoutique = :idBoutique";
    $cmd = $db->fetch($sqlCmd, ['idBoutique' => $idBoutique]);
    
    $sqlClients = "SELECT 
                    COUNT(DISTINCT idClient) as total,
                    COUNT(DISTINCT CASE WHEN dateCommande >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN idClient END) as actifs
                   FROM commande 
                   WHERE idBoutique = :idBoutique";
    $clients = $db->fetch($sqlClients, ['idBoutique' => $idBoutique]);
    
    $sqlProduits = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN statutProduit = 'disponible' THEN 1 ELSE 0 END) as actifs,
                    SUM(stock) as stockTotal
                   FROM produit 
                   WHERE idBoutique = :idBoutique";
    $produits = $db->fetch($sqlProduits, ['idBoutique' => $idBoutique]);
    
    return [
        'ca' => $ca,
        'ca_format' => number_format($ca, 0, ',', ' ') . ' G',
        'commandes' => $cmd,
        'clients' => $clients,
        'produits' => $produits,
        'panier_moyen' => ($cmd['total'] > 0) ? round($ca / $cmd['total'], 2) : 0
    ];
}

/**
 * Évolution des ventes (30 derniers jours)
 */
private function getEvolutionVentes(int $idBoutique): array
{
    $db = Database::getInstance();
    
    $sql = "SELECT 
                DATE(dateCommande) as date,
                COUNT(*) as nb_commandes,
                COALESCE(SUM(total), 0) as ca
            FROM commande 
            WHERE idBoutique = :idBoutique 
            AND dateCommande >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            AND statut NOT IN ('annulee', 'remboursee')
            GROUP BY DATE(dateCommande)
            ORDER BY date ASC";
    
    return $db->fetchAll($sql, ['idBoutique' => $idBoutique]);
}

/**
 * Top 10 des produits les plus vendus
 */
private function getTopProduits(int $idBoutique): array
{
    $db = Database::getInstance();
    
    $sql = "SELECT 
                p.idProduit,
                p.nomProduit,
                p.prix,
                COUNT(cp.idProduit) as nb_ventes,
                SUM(cp.quantite) as quantite_vendue,
                SUM(cp.totalLigne) as chiffre_affaires
            FROM produit p
            LEFT JOIN commande_produit cp ON p.idProduit = cp.idProduit
            LEFT JOIN commande c ON cp.idCommande = c.idCommande AND c.statut NOT IN ('annulee', 'remboursee')
            WHERE p.idBoutique = :idBoutique
            GROUP BY p.idProduit
            ORDER BY quantite_vendue DESC
            LIMIT 10";
    
    return $db->fetchAll($sql, ['idBoutique' => $idBoutique]);
}

/**
 * Top 10 des clients
 */
private function getTopClients(int $idBoutique): array
{
    $db = Database::getInstance();
    
    $sql = "SELECT 
                u.idUtilisateur,
                u.nomUtilisateur,
                u.prenomUtilisateur,
                u.emailUtilisateur,
                COUNT(c.idCommande) as nb_commandes,
                COALESCE(SUM(c.total), 0) as total_depenses,
                MAX(c.dateCommande) as derniere_commande
            FROM commande c
            JOIN utilisateur u ON c.idClient = u.idUtilisateur
            WHERE c.idBoutique = :idBoutique
            AND c.statut NOT IN ('annulee', 'remboursee')
            GROUP BY c.idClient
            ORDER BY total_depenses DESC
            LIMIT 10";
    
    return $db->fetchAll($sql, ['idBoutique' => $idBoutique]);
}

/**
 * Répartition des commandes par statut
 */
private function getCommandesParStatut(int $idBoutique): array
{
    $db = Database::getInstance();
    
    $sql = "SELECT 
                statut,
                COUNT(*) as total,
                COALESCE(SUM(total), 0) as montant
            FROM commande 
            WHERE idBoutique = :idBoutique
            GROUP BY statut";
    
    return $db->fetchAll($sql, ['idBoutique' => $idBoutique]);
}

/**
 * Chiffre d'affaires par jour (pour graphique)
 */
private function getCAParJour(int $idBoutique, int $jours = 30): array
{
    $db = Database::getInstance();
    
    $sql = "SELECT 
                DATE(dateCommande) as date,
                COALESCE(SUM(total), 0) as ca
            FROM commande 
            WHERE idBoutique = :idBoutique 
            AND dateCommande >= DATE_SUB(CURDATE(), INTERVAL :jours DAY)
            AND statut NOT IN ('annulee', 'remboursee')
            GROUP BY DATE(dateCommande)
            ORDER BY date ASC";
    
    return $db->fetchAll($sql, [
        'idBoutique' => $idBoutique,
        'jours' => $jours
    ]);
}

// ==================== CLIENTS ====================

/**
 * Liste des clients
 */
public function clients()
{
    $user = $this->normalizeUser(Session::user());
    $idBoutique = $user['idBoutique'] ?? $user['tenant_id'] ?? null;
    
    if (!$idBoutique) {
        App::redirect('/vendeur/configuration');
        return;
    }
    
    require_once dirname(__DIR__) . '/Models/TenantClient.php';
    $clientModel = new TenantClient();
    
    $filters = [];
    if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];
    if (!empty($_GET['min_commandes'])) $filters['min_commandes'] = (int)$_GET['min_commandes'];
    if (!empty($_GET['date_debut'])) $filters['date_debut'] = $_GET['date_debut'];
    if (!empty($_GET['date_fin'])) $filters['date_fin'] = $_GET['date_fin'];
    
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 15;
    
    $clientsData = $clientModel->getByBoutique($idBoutique, $filters, $page, $limit);
    $globalStats = $clientModel->getStats($idBoutique);
    
    $tenantInfo = $this->tenantModel->getById((int)$idBoutique);
    
    $this->view('tenant/clients/index', [
        'pageTitle' => 'Clients',
        'tenant' => $tenantInfo,
        'clients' => $clientsData['data'],
        'pagination' => [
            'total' => $clientsData['total'],
            'pages' => $clientsData['pages'],
            'current' => $clientsData['current_page'],
            'limit' => $clientsData['limit']
        ],
        'filters' => $filters,
        'globalStats' => $globalStats
    ]);
}

/**
 * Détail d'un client
 */
public function clientDetails()
{
    $idClient = $_GET['id'] ?? null;
    if (!$idClient) {
        App::redirect('/vendeur/clients');
        return;
    }
    
    $user = $this->normalizeUser(Session::user());
    $idBoutique = $user['idBoutique'] ?? $user['tenant_id'] ?? null;
    
    require_once dirname(__DIR__) . '/Models/TenantClient.php';
    $clientModel = new TenantClient();
    
    $details = $clientModel->getDetails((int)$idClient, $idBoutique);
    
    if (!$details) {
        $_SESSION['flash_error'] = 'Client non trouvé';
        App::redirect('/vendeur/clients');
        return;
    }
    
    $tenantInfo = $this->tenantModel->getById((int)$idBoutique);
    
    $this->view('tenant/clients/details', [
        'pageTitle' => 'Détail client',
        'tenant' => $tenantInfo,
        'details' => $details
    ]);
}

// Dans TenantController.php

/**
 * Liste des commandes en attente de validation
 */
public function commandesEnAttente()
{
    $user = $this->normalizeUser(Session::user());
    $idBoutique = $user['idBoutique'] ?? $user['tenant_id'] ?? null;
    
    require_once dirname(__DIR__) . '/Models/Commande.php';
    $commandeModel = new Commande();
    
    $commandes = $commandeModel->getEnAttenteValidation($idBoutique);
    
    $this->view('tenant/commandes/validation', [
        'pageTitle' => 'Commandes à valider',
        'commandes' => $commandes
    ]);
}

/**
 * Valider une commande
 */
public function validerCommande()
{
    CSRF::check();
    
    $idCommande = $_POST['idCommande'] ?? null;
    
    require_once dirname(__DIR__) . '/Models/Commande.php';
    $commandeModel = new Commande();
    
    if ($commandeModel->valider($idCommande)) {
        $_SESSION['flash_success'] = 'Commande confirmée';
    } else {
        $_SESSION['flash_error'] = 'Erreur lors de la confirmation';
    }
    
    App::redirect('/vendeur/commandes/validation');
}

/**
 * Détail d'une commande
 */
public function orderDetails()
{
    $idCommande = $_GET['id'] ?? null;
    if (!$idCommande) {
        App::redirect('/vendeur/commandes');
        return;
    }
    
    $user = Session::user();
    $idBoutique = $user['idBoutique'] ?? $user['tenant_id'] ?? null;
    
    require_once dirname(__DIR__) . '/Models/Order.php';
    $orderModel = new Order();
    
    $commande = $orderModel->getFullDetails((int)$idCommande);
    
    if (!$commande || $commande['idBoutique'] != $idBoutique) {
        $_SESSION['flash_error'] = 'Commande non trouvée';
        App::redirect('/vendeur/commandes');
        return;
    }
    
    $items = $orderModel->getItems((int)$idCommande);
    $payments = $orderModel->getPayments((int)$idCommande);
    
    $tenantInfo = $this->tenantModel->getById((int)$idBoutique);
    
    $this->view('tenant/commandes/details', [
        'pageTitle' => 'Détail commande',
        'tenant' => $tenantInfo,
        'commande' => $commande,
        'items' => $items,
        'payments' => $payments
    ]);
}

/**
 * Annuler une commande (AJAX)
 */
public function annulerCommande()
{
    header('Content-Type: application/json');
    
    $idCommande = $_POST['id'] ?? null;
    
    if (!$idCommande) {
        echo json_encode(['success' => false, 'error' => 'ID commande manquant']);
        return;
    }
    
    $user = Session::user();
    $idBoutique = $user['idBoutique'] ?? $user['tenant_id'] ?? null;
    
    require_once dirname(__DIR__) . '/Models/Order.php';
    $orderModel = new Order();
    
    $commande = $orderModel->getFullDetails((int)$idCommande);
    if (!$commande || $commande['idBoutique'] != $idBoutique) {
        echo json_encode(['success' => false, 'error' => 'Commande non trouvée']);
        return;
    }
    
    if ($orderModel->annulerCommande((int)$idCommande)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'annulation']);
    }
}

/**
 * Changer le statut d'une commande (AJAX)
 */
public function updateCommandeStatut()
{
    header('Content-Type: application/json');
    
    $idCommande = $_POST['id'] ?? null;
    $statut = $_POST['statut'] ?? null;
    
    if (!$idCommande || !$statut) {
        echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
        return;
    }
    
    $user = Session::user();
    $idBoutique = $user['idBoutique'] ?? $user['tenant_id'] ?? null;
    
    require_once dirname(__DIR__) . '/Models/Order.php';
    $orderModel = new Order();
    
    $commande = $orderModel->getFullDetails((int)$idCommande);
    if (!$commande || $commande['idBoutique'] != $idBoutique) {
        echo json_encode(['success' => false, 'error' => 'Commande non trouvée']);
        return;
    }
    
    if ($orderModel->updateStatus((int)$idCommande, $statut)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erreur lors de la mise à jour']);
    }
}

}