<?php
require_once dirname(__DIR__) . '/Controllers/Controller.php';

class HomeController extends Controller
{
    public function index(): void
    {
        $db = Database::getInstance();

        // 1. NOUVEAU : Récupération des catégories principales (Mega Menu)
        $categoriesPrincipales = $db->fetchAll(
            "SELECT idCategorie, nomCategorie 
             FROM categorie 
             WHERE idCategorieParent IS NULL AND actif = 1 
             ORDER BY ordre ASC, nomCategorie ASC"
        );

        // 2. Récupération des Boutiques actives
        $boutiques = $db->fetchAll(
            "SELECT 
                b.idBoutique, 
                b.nomBoutique, 
                b.slugBoutique, 
                b.description, 
                b.dateCreation,
                pb.logo, 
                pb.couleurPrimaire
             FROM boutique b
             LEFT JOIN parametre_boutique pb ON b.idBoutique = pb.idBoutique
             WHERE b.statut = 'actif'
             ORDER BY b.dateCreation DESC
             LIMIT 8"
        );

        // 3. Produits en vedette
        $produitsVedette = $db->fetchAll(
            "SELECT p.idProduit, p.nomProduit, p.prix, p.prixPromo, p.stock,
                    b.slugBoutique, b.nomBoutique,
                    (SELECT ip.urlImage 
                     FROM image_produit ip 
                     WHERE ip.idProduit = p.idProduit AND ip.estPrincipale = 1
                     LIMIT 1) AS image
             FROM produit p
             JOIN boutique b ON b.idBoutique = p.idBoutique
             WHERE b.statut = 'actif'
               AND p.statutProduit = 'disponible'
               AND p.misEnAvant = 1
             ORDER BY p.dateAjout DESC
             LIMIT 12"
        );

        // 4. Gestion de la session et du Dashboard Client
        $user     = Session::user();
        $isClient = $user && ($user['role'] ?? null) === 'client';
        $clientStats = null;
        $lastOrders  = [];

        if ($isClient) {
            $clientId = (int) $user['id'];

            $clientStats = $db->fetch(
                "SELECT
                    COUNT(*) AS totalCommandes,
                    SUM(CASE WHEN statut IN ('en_attente','confirmee','payee','en_preparation','expediee') THEN 1 ELSE 0 END) AS commandesEnCours,
                    COALESCE(SUM(CASE WHEN statut NOT IN ('annulee','remboursee') THEN total ELSE 0 END), 0) AS totalDepense
                 FROM commande
                 WHERE idClient = ?",
                [$clientId]
            );

            $lastOrders = $db->fetchAll(
                "SELECT c.idCommande, c.numeroCommande, c.total, c.dateCommande, b.nomBoutique, c.statut
                 FROM commande c
                 JOIN boutique b ON b.idBoutique = c.idBoutique
                 WHERE c.idClient = ?
                 ORDER BY c.dateCommande DESC
                 LIMIT 3",
                [$clientId]
            );
        }

        // 5. Envoi à la vue
        $this->view('home/index', [
            'pageTitle'             => App::setting('site_name', 'ShopXPao') . ' - Accueil',
            'categoriesPrincipales' => $categoriesPrincipales,
            'boutiques'             => $boutiques,
            'produitsVedette'       => $produitsVedette,
            'user'                  => $user,
            'isClient'              => $isClient,
            'clientStats'           => $clientStats,
            'lastOrders'            => $lastOrders
        ]);
    }

    /**
     * NOUVEAU : Méthode API pour charger les sous-catégories dynamiquement (AJAX)
     */
    public function getSubCategories(): void
    {
        $parentId = (int)($_GET['parentId'] ?? 0);
        $db = Database::getInstance();

        $subs = $db->fetchAll(
            "SELECT idCategorie, nomCategorie 
             FROM categorie 
             WHERE idCategorieParent = ? AND actif = 1 
             ORDER BY nomCategorie ASC",
            [$parentId]
        );

        header('Content-Type: application/json');
        echo json_encode($subs);
        exit;
    }

    /**
 * API pour charger les produits d'une sous-catégorie (colonne 3 du mega menu)
 */
    public function getProductsByCategory(): void
    {
        $categoryId = (int)($_GET['categoryId'] ?? 0);
        $db = Database::getInstance();

        $products = $db->fetchAll(
            "SELECT p.idProduit, p.nomProduit, p.slugProduit, p.prix, p.prixPromo,
                    b.slugBoutique,
                    (SELECT ip.urlImage 
                    FROM image_produit ip 
                    WHERE ip.idProduit = p.idProduit AND ip.estPrincipale = 1
                    LIMIT 1) AS image
            FROM produit p
            JOIN boutique b ON b.idBoutique = p.idBoutique
            WHERE p.idCategorie = ?
            AND p.statutProduit = 'disponible'
            AND b.statut = 'actif'
            ORDER BY p.misEnAvant DESC, p.dateAjout DESC
            LIMIT 12",
            [$categoryId]
        );

        header('Content-Type: application/json');
        echo json_encode($products);
        exit;
    }

/**
 * Recherche de produits
 */
public function recherche()
{
    $q = trim($_GET['q'] ?? '');
    
    if (empty($q)) {
        App::redirect('/');
        return;
    }
    
    $db = Database::getInstance();    
    $produits = $db->fetchAll(
        "SELECT p.*, 
                b.nomBoutique, b.slugBoutique,
                (SELECT urlImage FROM image_produit 
                 WHERE idProduit = p.idProduit AND estPrincipale = 1 LIMIT 1) as image
         FROM produit p
         JOIN boutique b ON b.idBoutique = p.idBoutique
         WHERE b.statut = 'actif'
           AND p.statutProduit = 'disponible'
           AND (p.nomProduit LIKE :search1 OR p.descriptionCourte LIKE :search2)
         ORDER BY p.misEnAvant DESC, p.dateAjout DESC",
        [
            'search1' => '%' . $q . '%',
            'search2' => '%' . $q . '%'
        ]
    );
    
    $boutiques = $db->fetchAll(
        "SELECT b.*, pb.logo
         FROM boutique b
         LEFT JOIN parametre_boutique pb ON b.idBoutique = pb.idBoutique
         WHERE b.statut = 'actif'
           AND (b.nomBoutique LIKE :search3 OR b.description LIKE :search4)
         ORDER BY b.nomBoutique ASC",
        [
            'search3' => '%' . $q . '%',
            'search4' => '%' . $q . '%'
        ]
    );
    
    $this->view('recherche/index', [
        'pageTitle' => 'Recherche : ' . Security::escape($q),
        'q' => $q,
        'produits' => $produits,
        'boutiques' => $boutiques
    ]);
}


/**
 * Détail d'un produit
 */
public function produitDetail()
{
    $id = $_GET['id'] ?? 0;
    $db = Database::getInstance();    
    $produit = $db->fetch(
        "SELECT p.*, 
                b.idBoutique, b.nomBoutique, b.slugBoutique, b.description as descriptionBoutique,
                pb.logo as logoBoutique, pb.couleurPrimaire, pb.couleurSecondaire,
                c.nomCategorie, c.slugCategorie
         FROM produit p
         JOIN boutique b ON b.idBoutique = p.idBoutique
         LEFT JOIN parametre_boutique pb ON pb.idBoutique = b.idBoutique
         LEFT JOIN categorie c ON c.idCategorie = p.idCategorie
         WHERE p.idProduit = ? AND p.statutProduit = 'disponible' AND b.statut = 'actif'",
        [$id]
    );
    
    if (!$produit) {
        $this->view('erreur/404', ['message' => 'Produit non trouvé']);
        return;
    }
    
    // Récupérer les images du produit
    $images = $db->fetchAll(
        "SELECT * FROM image_produit 
         WHERE idProduit = ? 
         ORDER BY estPrincipale DESC, ordre ASC",
        [$id]
    );
    
    // Récupérer les produits similaires (même catégorie)
    $similaires = [];
    if ($produit['idCategorie']) {
        $similaires = $db->fetchAll(
            "SELECT p.idProduit, p.nomProduit, p.prix, p.prixPromo,
                    (SELECT urlImage FROM image_produit 
                     WHERE idProduit = p.idProduit AND estPrincipale = 1 LIMIT 1) as image
             FROM produit p
             WHERE p.idCategorie = ? AND p.idProduit != ? 
               AND p.statutProduit = 'disponible'
             ORDER BY p.dateAjout DESC
             LIMIT 4",
            [$produit['idCategorie'], $id]
        );
    }
    
    $this->view('catalogue/detail', [
        'pageTitle' => $produit['nomProduit'] . ' - ' . $produit['nomBoutique'],
        'produit' => $produit,
        'images' => $images,
        'similaires' => $similaires
    ]);
}
}