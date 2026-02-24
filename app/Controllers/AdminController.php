<?php

require_once dirname(__DIR__) . '/Controllers/Controller.php';
require_once dirname(__DIR__, 2) . '/Core/CSRF.php';

// CHARGEMENT DES MODÈLES
require_once dirname(__DIR__) . '/Models/User.php';
require_once dirname(__DIR__) . '/Models/Tenant.php';
require_once dirname(__DIR__) . '/Models/Order.php';
require_once dirname(__DIR__) . '/Models/Category.php';
require_once dirname(__DIR__) . '/Models/Product.php';

class AdminController extends Controller
{
    private User $userModel;
    private Tenant $tenantModel;
    private Order $orderModel;
    private Category $categoryModel;
    private Product $productModel;

public function __construct()
    {
        $this->userModel = new User();
        $this->tenantModel = new Tenant();
        $this->orderModel = new Order();
        $this->categoryModel = new Category();
        $this->productModel = new Product();
    }

    /**
     * Affiche une vue admin SANS le header public, mais avec head.php et foot.php
     */
private function renderAdmin(string $viewPath, array $data = []): void
    {
        extract($data);
        $baseUrl = App::baseUrl();

        // Correction du chemin pour Windows et Linux
        $ds = DIRECTORY_SEPARATOR;
        $viewsRootDir = dirname(__DIR__) . $ds . 'Views' . $ds;

        // Détection auto du nom de page pour CSS/JS (ex: 'users', 'tenants')
        $parts = explode('/', $viewPath);
        $pageName = end($parts);
        $extra_css = $pageName;
        $extra_js = $pageName;

        // 1. Inclusion du Head technique (Bootstrap, admin.css)
        require $viewsRootDir . 'admin' . $ds . 'partials' . $ds . 'head.php';

        // 2. Inclusion de la Vue de contenu
        require $viewsRootDir . str_replace('/', $ds, $viewPath) . '.php';

        // 3. Inclusion du Foot technique (Bootstrap JS)
        require $viewsRootDir . 'admin' . $ds . 'partials' . $ds . 'foot.php';
    }

    /**
     * DASHBOARD
     */
public function dashboard(): void
{
    $db = Database::getInstance();
    
    $statsUsers = $db->fetch("SELECT COUNT(*) as total, SUM(CASE WHEN role = 'client' THEN 1 ELSE 0 END) as clients, SUM(CASE WHEN role = 'tenant' THEN 1 ELSE 0 END) as tenants, SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins FROM utilisateur");
    
    $statsShops = $db->fetch("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN b.statut = 'actif' AND (
                SELECT COUNT(*) FROM commande WHERE idBoutique = b.idBoutique
            ) > 0 THEN 1 ELSE 0 END) as actives,
            SUM(CASE WHEN b.statut = 'en_attente' THEN 1 ELSE 0 END) as enAttente,
            SUM(CASE WHEN b.statut = 'actif' AND (
                SELECT COUNT(*) FROM commande WHERE idBoutique = b.idBoutique
            ) = 0 THEN 1 ELSE 0 END) as sansCommandes
        FROM boutique b
    ");
    
    $statsOrders = $db->fetch("SELECT COUNT(*) as total, SUM(CASE WHEN DATE(dateCommande) = CURDATE() THEN 1 ELSE 0 END) as aujourdhui, COALESCE(SUM(CASE WHEN statut NOT IN ('annulee', 'remboursee') THEN total ELSE 0 END), 0) as volumeTotal FROM commande");
    
    $stats = [
        'utilisateurs' => $statsUsers, 
        'boutiques' => $statsShops, 
        'commandes' => $statsOrders
    ];
    
    $recentTenants = $db->fetchAll("SELECT b.idBoutique, b.nomBoutique, b.slugBoutique, b.statut, b.dateCreation, CONCAT(u.prenomUtilisateur, ' ', u.nomUtilisateur) as proprietaire, pb.logo FROM boutique b LEFT JOIN utilisateur u ON b.idProprietaire = u.idUtilisateur LEFT JOIN parametre_boutique pb ON b.idBoutique = pb.idBoutique ORDER BY b.dateCreation DESC LIMIT 5");
    $recentUsers = $db->fetchAll("SELECT idUtilisateur, nomUtilisateur, prenomUtilisateur, emailUtilisateur, role, dateCreation, avatar FROM utilisateur ORDER BY dateCreation DESC LIMIT 5");
    $recentOrders = $db->fetchAll("SELECT c.idCommande, c.numeroCommande, c.total, c.statut, c.dateCommande, b.nomBoutique, CONCAT(u.prenomUtilisateur, ' ', u.nomUtilisateur) as nomClient FROM commande c LEFT JOIN boutique b ON c.idBoutique = b.idBoutique LEFT JOIN utilisateur u ON c.idClient = u.idUtilisateur ORDER BY c.dateCommande DESC LIMIT 5");

    $this->renderAdmin('admin/dashboard', [
        'pageTitle' => 'Tableau de bord',
        'stats' => $stats,
        'recentTenants' => $recentTenants,
        'recentUsers' => $recentUsers,
        'recentOrders' => $recentOrders
    ]);
}

    // ══════════════════════════════════════════
    // CATALOGUE (CATÉGORIES & PRODUITS)
    // ══════════════════════════════════════════

public function productsList(): void
{
    $filters = ['q' => $_GET['q'] ?? ''];
    $page = (int)($_GET['page'] ?? 1);
    $limit = 10; 
    $offset = ($page - 1) * $limit;

    $products = $this->productModel->getPaginatedGlobal($limit, $offset, $filters);
    $total = $this->productModel->countAll($filters);
    
    // ✅ Requête directe pour les vedettes
    $db = Database::getInstance();
    $result = $db->fetch("SELECT COUNT(*) as total FROM produit WHERE misEnAvant = 1");
    $featuredCount = $result['total'] ?? 0;

    $this->renderAdmin('admin/produits', [
        'pageTitle' => 'Modération Produits',
        'products' => $products,
        'filters' => $filters,
        'featuredCount' => $featuredCount,
        'pagination' => [
            'current' => $page, 
            'total' => ceil($total / $limit),
            'totalItems' => $total
        ]
    ]);
}

    public function updateProductStatus(): void
    {
        CSRF::check();
        if(isset($_POST['id'], $_POST['status'])) {
            $this->productModel->updateStatus((int)$_POST['id'], $_POST['status']);
            App::redirect('/admin/produits?success=status_updated');
        }
    }

    public function toggleProductFeatured(): void
    {
        CSRF::check();
        if(isset($_POST['id'], $_POST['featured'])) {
            $this->productModel->toggleFeatured((int)$_POST['id'], (int)$_POST['featured']);
            App::redirect('/admin/produits?success=featured_updated');
        }
    }

    // ══════════════════════════════════════════
    // GESTION DES UTILISATEURS
    // ══════════════════════════════════════════

    public function usersList(): void
    {
        $filters = ['q' => $_GET['q'] ?? '', 'role' => $_GET['role'] ?? '', 'status' => $_GET['status'] ?? ''];
        $page = (int)($_GET['page'] ?? 1);
        $limit = 10; $offset = ($page - 1) * $limit;

        $users = $this->userModel->getPaginated($limit, $offset, $filters);
        $totalRecords = $this->userModel->countFiltered($filters);
        $userStats = $this->userModel->getGlobalStats();

        $this->renderAdmin('admin/users', [
            'pageTitle' => 'Gestion Utilisateurs',
            'users' => $users,
            'pagination' => ['current' => $page, 'total' => ceil($totalRecords / $limit), 'totalRecords' => $totalRecords],
            'filters' => $filters,
            'userStats' => $userStats
        ]);
    }

    public function saveUser(): void
    {
        CSRF::check();
        $id = $_POST['idUtilisateur'] ?? null;
        $data = [
                    'role' => $_POST['role'] ?? 'client',
                    'statut' => $_POST['statut'] ?? 'actif',
                    'password' => $_POST['password'] ?? ''
                ];
        if ($id) $this->userModel->update((int)$id, $data); else $this->userModel->create($data);
        App::redirect('/admin/users?success=1');
    }

    public function deleteUser(): void
    {
        CSRF::check();
        if ($id = $_POST['id_delete'] ?? null) $this->userModel->delete((int)$id);
        App::redirect('/admin/users?success=deleted');
    }

    // ══════════════════════════════════════════
    // BOUTIQUES (TENANTS)
    // ══════════════════════════════════════════

public function tenantsList(): void
{
    $filters = [
        'q' => $_GET['q'] ?? '', 
        'status' => $_GET['status'] ?? ''
    ];
    $page = (int)($_GET['page'] ?? 1);
    $limit = 10; 
    $offset = ($page - 1) * $limit;

    // ✅ Récupère le résultat complet
    $result = $this->tenantModel->getPaginated($limit, $offset, $filters);
    
    // ✅ Extrait les données
    $tenants = $result['data'] ?? [];
    $total = $result['total'] ?? 0;
    $pages = $result['pages'] ?? 1;
    
    // Stats globales
    $stats = $this->tenantModel->getGlobalStats();

    $this->renderAdmin('admin/tenants', [
        'pageTitle' => 'Gestion Boutiques',
        'tenants' => $tenants,  // ← Maintenant c'est un tableau simple
        'stats' => $stats,
        'pagination' => [
            'current' => $page, 
            'total' => $pages,
            'totalRecords' => $total
        ],
        'filters' => $filters
    ]);
}
    

public function tenantDetails(): void
    {
        $id = $_GET['id'] ?? null;
        if (!$id) App::redirect('/admin/tenants');
        $tenant = $this->tenantModel->getById((int)$id);
        $stats = $this->tenantModel->getTenantStats((int)$id);
        $logs = $this->tenantModel->getLogs((int)$id);
        $this->renderAdmin('admin/tenant-details', ['pageTitle' => $tenant['nomBoutique'], 't' => $tenant, 'stats' => $stats, 'logs' => $logs]);
    }

    
public function updateTenantStatus(): void
    {
        CSRF::check();
        if ($id = $_POST['idBoutique'] ?? null) {
            $this->tenantModel->updateStatus((int)$id, $_POST['statut']);
            App::redirect("/admin/tenant-details?id=$id&success=updated");
        }
    }


public function updateTenantSubscription(): void
    {
        CSRF::check();
        if ($id = $_POST['idBoutique'] ?? null) {
            $this->tenantModel->updateSubscription((int)$id, $_POST['plan']);
            App::redirect("/admin/tenant-details?id=$id&success=abo_updated");
        }
    }

    public function deleteTenant(): void
    {
        CSRF::check();
        if ($id = $_POST['id_delete'] ?? null) {
            try { $this->tenantModel->delete((int)$id); App::redirect('/admin/tenants?success=deleted'); }
            catch (Exception $e) { App::redirect("/admin/tenant-details?id=$id&error=Erreur"); }
        }
    }

    // ══════════════════════════════════════════
    // COMMANDES
    // ══════════════════════════════════════════

    public function ordersList(): void
    {
        $filters = ['q' => $_GET['q'] ?? '', 'status' => $_GET['status'] ?? ''];
        $page = (int)($_GET['page'] ?? 1);
        $limit = 15; $offset = ($page - 1) * $limit;
        $orders = $this->orderModel->getAllPaginated($limit, $offset, $filters);
        $total = $this->orderModel->countAll($filters);
        $this->renderAdmin('admin/orders', ['pageTitle' => 'Supervision Commandes', 'orders' => $orders, 'filters' => $filters, 'pagination' => ['current' => $page, 'total' => ceil($total / $limit)]]);
    }



public function updateOrderStatus(): void
    {
        CSRF::check();
        if ($id = $_POST['idCommande'] ?? null) {
            $this->orderModel->updateStatus((int)$id, $_POST['statut']);
            App::redirect("/admin/order-details?id=$id&success=updated");
        }
    }

    public function categoriesList(): void
    {
        $page = (int)($_GET['page'] ?? 1);
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $total = $this->categoryModel->countParents();
        $categories = $this->categoryModel->getParentsPaginated($limit, $offset);

        $this->renderAdmin('admin/categories', [
            'pageTitle'  => 'Taxonomie Globale',
            'categories' => $categories,
            'boutiques'  => $this->categoryModel->getBoutiques(),
            'isSubView'  => false,
            'pagination' => ['current' => $page, 'total' => ceil($total / $limit), 'totalItems' => $total]
        ]);
    }



    public function subCategoriesList(): void
    {
        $parentId = $_GET['parent'] ?? null;
        if (!$parentId) App::redirect('/admin/categories');

        $parent = $this->categoryModel->getById((int)$parentId);
        $page = (int)($_GET['page'] ?? 1);
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $total = $this->categoryModel->countChildren((int)$parentId);
        $categories = $this->categoryModel->getChildrenPaginated((int)$parentId, $limit, $offset);

        $this->renderAdmin('admin/categories', [
            'pageTitle'  => 'Sous-catégories de ' . $parent['nomCategorie'],
            'categories' => $categories,
            'boutiques'  => $this->categoryModel->getBoutiques(),
            'parent'     => $parent,
            'isSubView'  => true,
            'pagination' => ['current' => $page, 'total' => ceil($total / $limit), 'totalItems' => $total]
        ]);
    }



    public function saveCategory(): void
{
    CSRF::check();
    
    // On récupère proprement l'ID du parent (si c'est 0 ou vide, on met NULL)
    $parentId = !empty($_POST['idCategorieParent']) ? (int)$_POST['idCategorieParent'] : null;

    $data = [
        'idCategorie'       => $_POST['id'] ?? null,
        'idBoutique'        => $_POST['idBoutique'],
        'idCategorieParent' => $parentId, // Sera NULL pour une parente
        'nomCategorie'      => trim($_POST['nom']),
        'slugCategorie'     => trim($_POST['slug']),
        'description'       => trim($_POST['description']),
        'image'             => $_POST['existing_image'] ?? null,
        'ordre'             => (int)($_POST['ordre'] ?? 0),
        'actif'             => (int)($_POST['statut'] ?? 1)
    ];

    $this->categoryModel->save($data);
    
    // ✅ LA CORRECTION EST ICI :
    // Si on vient d'ajouter/modifier une sous-catégorie, on retourne vers la liste de son parent
    if ($parentId) {
        App::redirect('/admin/sub-categories?parent=' . $parentId . '&success=1');
    } else {
        // Sinon (si c'est une parente), on reste sur la page principale des catégories
        App::redirect('/admin/categories?success=1');
    }
}


    /**
     * ACTION : SUPPRIMER UNE CATÉGORIE
     */
public function deleteCategory(): void
    {
        CSRF::check();
        $id = $_POST['id_delete'] ?? null;

        if ($id) {
            try {
                $this->categoryModel->delete((int)$id);
                App::redirect('/admin/categories?success=deleted');
            } catch (Exception $e) {
                App::redirect('/admin/categories?error=' . urlencode("Erreur lors de la suppression."));
            }
        }
    }

    /**
     * AJAX : Récupérer les détails d'un produit
     */
public function getProductDetailsJson(): void
{
    $id = $_GET['id'] ?? null;
    $product = $this->productModel->getFullDetails((int)$id);
    
    header('Content-Type: application/json');
    echo json_encode($product);
    exit; // ✅ Indispensable pour stopper tout rendu HTML
}

/**
 * Mettre à jour un utilisateur (statut, rôle, mot de passe)
 */
public function updateUser()
{
    CSRF::check();
    
    $id = $_POST['id'] ?? 0;
    $statut = $_POST['statut'] ?? 'actif';
    $role = $_POST['role'] ?? 'client';
    $nouveauMotDePasse = $_POST['mot_de_passe'] ?? '';
    
    $db = Database::getInstance();
    
    // Si un nouveau mot de passe est fourni, on le hash
    if (!empty($nouveauMotDePasse)) {
        $hash = password_hash($nouveauMotDePasse, PASSWORD_DEFAULT);
        $db->execute(
            "UPDATE utilisateur SET statut = ?, role = ?, motDePasse = ? WHERE idUtilisateur = ?",
            [$statut, $role, $hash, $id]
        );
        
        $_SESSION['flash_success'] = 'Utilisateur mis à jour avec nouveau mot de passe';
    } else {
        $db->execute(
            "UPDATE utilisateur SET statut = ?, role = ? WHERE idUtilisateur = ?",
            [$statut, $role, $id]
        );
        
        $_SESSION['flash_success'] = 'Utilisateur mis à jour';
    }
    
    App::redirect('/admin/users');
}

/**
 * Détail d'une commande (admin)
 */
public function orderDetails()
{
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        App::redirect('/admin/orders');
        return;
    }
    
    require_once dirname(__DIR__) . '/Models/Order.php';
    $orderModel = new Order();
    
    $commande = $orderModel->getFullDetails((int)$id);
    
    if (!$commande) {
        $_SESSION['flash_error'] = 'Commande non trouvée';
        App::redirect('/admin/orders');
        return;
    }
    
    $items = $orderModel->getItems((int)$id);
    $payments = $orderModel->getPayments((int)$id);
    
    $this->renderAdmin('admin/order-details', [
        'pageTitle' => 'Détail commande #' . $commande['numeroCommande'],
        'commande' => $commande,
        'items' => $items,
        'payments' => $payments
    ]);
}

/**
 * Page paiements (en construction)
 */
public function paymentsList()
{
    $this->renderAdmin('admin/coming-soon', [
        'pageTitle' => 'Paiements',
        'pageIcon' => 'bi-credit-card',
        'pageMessage' => 'La gestion des paiements sera bientôt disponible'
    ]);
}

/**
 * Page statistiques détaillées
 */
public function stats()
{
    $db = Database::getInstance();
    
    // 1. Statistiques globales
    $globalStats = $db->fetch("
        SELECT 
            (SELECT COUNT(*) FROM utilisateur) as total_users,
            (SELECT COUNT(*) FROM utilisateur WHERE role = 'client') as total_clients,
            (SELECT COUNT(*) FROM utilisateur WHERE role = 'tenant') as total_tenants,
            (SELECT COUNT(*) FROM utilisateur WHERE role = 'admin') as total_admins,
            (SELECT COUNT(*) FROM boutique) as total_boutiques,
            (SELECT COUNT(*) FROM boutique WHERE statut = 'actif') as boutiques_actives,
            (SELECT COUNT(*) FROM boutique WHERE statut = 'en_attente') as boutiques_attente,
            (SELECT COUNT(*) FROM produit) as total_produits,
            (SELECT COUNT(*) FROM commande) as total_commandes,
            (SELECT COALESCE(SUM(total), 0) FROM commande WHERE statut NOT IN ('annulee', 'remboursee')) as ca_total
    ");
    
    // 2. Évolution des inscriptions (30 derniers jours)
    $inscriptions = $db->fetchAll("
        SELECT 
            DATE(dateCreation) as date,
            COUNT(*) as total
        FROM utilisateur
        WHERE dateCreation >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(dateCreation)
        ORDER BY date ASC
    ");
    
    // 3. Commandes par statut
    $commandesStatut = $db->fetchAll("
        SELECT 
            statut,
            COUNT(*) as total,
            COALESCE(SUM(total), 0) as montant
        FROM commande
        GROUP BY statut
        ORDER BY total DESC
    ");
    
    // 4. Top 10 boutiques par CA
    $topBoutiques = $db->fetchAll("
        SELECT 
            b.idBoutique,
            b.nomBoutique,
            b.slugBoutique,
            COUNT(c.idCommande) as nb_commandes,
            COALESCE(SUM(c.total), 0) as ca,
            pb.logo
        FROM boutique b
        LEFT JOIN commande c ON b.idBoutique = c.idBoutique AND c.statut NOT IN ('annulee', 'remboursee')
        LEFT JOIN parametre_boutique pb ON b.idBoutique = pb.idBoutique
        GROUP BY b.idBoutique
        ORDER BY ca DESC
        LIMIT 10
    ");
    
    // 5. Top 10 produits les plus vendus
    $topProduits = $db->fetchAll("
        SELECT 
            p.idProduit,
            p.nomProduit,
            p.prix,
            b.nomBoutique,
            COUNT(cp.idProduit) as nb_ventes,
            SUM(cp.quantite) as quantite_vendue,
            COALESCE(SUM(cp.totalLigne), 0) as chiffre
        FROM produit p
        JOIN boutique b ON p.idBoutique = b.idBoutique
        LEFT JOIN commande_produit cp ON p.idProduit = cp.idProduit
        LEFT JOIN commande c ON cp.idCommande = c.idCommande AND c.statut NOT IN ('annulee', 'remboursee')
        GROUP BY p.idProduit
        ORDER BY quantite_vendue DESC
        LIMIT 10
    ");
    
    // 6. CA par mois (année en cours)
    $caMensuel = $db->fetchAll("
        SELECT 
            MONTH(dateCommande) as mois,
            YEAR(dateCommande) as annee,
            COUNT(*) as nb_commandes,
            COALESCE(SUM(total), 0) as ca
        FROM commande
        WHERE YEAR(dateCommande) = YEAR(CURDATE())
            AND statut NOT IN ('annulee', 'remboursee')
        GROUP BY MONTH(dateCommande)
        ORDER BY mois ASC
    ");
    
    // 7. Nouveaux utilisateurs par mois
    $nouveauxUtilisateurs = $db->fetchAll("
        SELECT 
            MONTH(dateCreation) as mois,
            YEAR(dateCreation) as annee,
            COUNT(*) as total
        FROM utilisateur
        WHERE YEAR(dateCreation) = YEAR(CURDATE())
        GROUP BY MONTH(dateCreation)
        ORDER BY mois ASC
    ");
    
    // 8. Répartition des rôles
    $roles = $db->fetchAll("
        SELECT 
            role,
            COUNT(*) as total
        FROM utilisateur
        GROUP BY role
    ");
    
    // 9. Statistiques des paiements
    $paiements = $db->fetchAll("
        SELECT 
            modePaiement,
            COUNT(*) as total,
            COALESCE(SUM(montant), 0) as montant_total
        FROM paiement
        WHERE statutPaiement = 'valide'
        GROUP BY modePaiement
    ");
    
    // 10. Taux de conversion (panier -> commande)
    $conversion = $db->fetch("
        SELECT 
            (SELECT COUNT(*) FROM commande) as commandes,
            (SELECT COUNT(*) FROM panier) as paniers,
            ROUND((SELECT COUNT(*) FROM commande) / NULLIF((SELECT COUNT(*) FROM panier), 0) * 100, 2) as taux
    ");
    
    $this->renderAdmin('admin/statistiques', [
        'pageTitle' => 'Statistiques',
        'globalStats' => $globalStats,
        'inscriptions' => $inscriptions,
        'commandesStatut' => $commandesStatut,
        'topBoutiques' => $topBoutiques,
        'topProduits' => $topProduits,
        'caMensuel' => $caMensuel,
        'nouveauxUtilisateurs' => $nouveauxUtilisateurs,
        'roles' => $roles,
        'paiements' => $paiements,
        'conversion' => $conversion
    ]);
}
/**
 * Page paramètres (en construction)
 */
public function settings()
{
    $this->renderAdmin('admin/coming-soon', [
        'pageTitle' => 'Paramètres',
        'pageIcon' => 'bi-gear-wide-connected',
        'pageMessage' => 'La configuration de la plateforme sera bientôt disponible'
    ]);
}

    
}