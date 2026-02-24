<?php

class Order
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // 1. Liste globale paginée avec jointures (Boutique et Client)
    public function getAllPaginated(int $limit, int $offset, array $filters = [])
    {
        $sql = "SELECT c.*, 
                       b.nomBoutique, b.slugBoutique,
                       CONCAT(u.prenomUtilisateur, ' ', u.nomUtilisateur) as clientNom,
                       u.emailUtilisateur as clientEmail
                FROM commande c
                JOIN boutique b ON c.idBoutique = b.idBoutique
                JOIN utilisateur u ON c.idClient = u.idUtilisateur
                WHERE 1=1";
        
        $params = [];

        if (!empty($filters['q'])) {
            $sql .= " AND (c.numeroCommande LIKE :q OR b.nomBoutique LIKE :q OR u.nomUtilisateur LIKE :q)";
            $params[':q'] = '%' . $filters['q'] . '%';
        }
        if (!empty($filters['status'])) {
            $sql .= " AND c.statut = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['boutique'])) {
            $sql .= " AND c.idBoutique = :bid";
            $params[':bid'] = $filters['boutique'];
        }

        $sql .= " ORDER BY c.dateCommande DESC LIMIT $limit OFFSET $offset";

        return $this->db->fetchAll($sql, $params);
    }

    public function countAll(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) as total FROM commande c 
                JOIN boutique b ON c.idBoutique = b.idBoutique
                JOIN utilisateur u ON c.idClient = u.idUtilisateur
                WHERE 1=1";
        $params = [];
        if(!empty($filters['q'])) { $sql .= " AND (c.numeroCommande LIKE :q OR b.nomBoutique LIKE :q)"; $params[':q'] = '%'.$filters['q'].'%'; }
        if(!empty($filters['status'])) { $sql .= " AND c.statut = :status"; $params[':status'] = $filters['status']; }
        
        $res = $this->db->fetch($sql, $params);
        return (int)($res['total'] ?? 0);
    }

    // 2. Détails complets d'une commande
    public function getFullDetails(int $id)
    {
        $sql = "SELECT c.*, 
                       b.nomBoutique, b.slugBoutique, b.idBoutique,
                       u.prenomUtilisateur, u.nomUtilisateur, u.emailUtilisateur, u.telephone as clientTel,
                       a.rue, a.ville, a.pays, a.codePostal -- Adresse livraison
                FROM commande c
                JOIN boutique b ON c.idBoutique = b.idBoutique
                JOIN utilisateur u ON c.idClient = u.idUtilisateur
                LEFT JOIN livraison l ON c.idCommande = l.idCommande
                LEFT JOIN adresse a ON l.idAdresseLivraison = a.idAdresse
                WHERE c.idCommande = :id";
        
        return $this->db->fetch($sql, [':id' => $id]);
    }

    // 3. Produits de la commande
    public function getItems(int $id)
    {
        return $this->db->fetchAll("SELECT * FROM commande_produit WHERE idCommande = ?", [$id]);
    }

    // 4. Historique de paiement
    public function getPayments(int $id)
    {
        return $this->db->fetchAll("SELECT * FROM paiement WHERE idCommande = ? ORDER BY dateCreation DESC", [$id]);
    }

    // 5. Changer statut (Annuler / Rembourser)
    public function updateStatus(int $id, string $status)
    {
        return $this->db->execute("UPDATE commande SET statut = :status WHERE idCommande = :id", [
            ':status' => $status,
            ':id' => $id
        ]);
    }

        /**
     * Récupère les dernières commandes d'une boutique spécifique
     * @param int 
     * @param int 
     * @return array
     */
    public function getRecentByBoutique(int $idBoutique, int $limit = 5): array
    {
        $sql = "SELECT 
                    c.idCommande,
                    c.numeroCommande,
                    c.dateCommande,
                    c.total,
                    c.statut,
                    CONCAT(u.prenomUtilisateur, ' ', u.nomUtilisateur) AS nomClient
                FROM commande c
                JOIN utilisateur u ON c.idClient = u.idUtilisateur
                WHERE c.idBoutique = :idBoutique
                ORDER BY c.dateCommande DESC
                LIMIT :limit";

        return $this->db->fetchAll($sql, [
            'idBoutique' => $idBoutique,
            'limit' => $limit
        ]);
    }

    /**
     * Compte le nombre total de commandes d'une boutique
     * @param int 
     * @return int
     */
    public function countByBoutique(int $idBoutique): int
    {
        $sql = "SELECT COUNT(*) as total FROM commande WHERE idBoutique = :idBoutique";
        $result = $this->db->fetch($sql, ['idBoutique' => $idBoutique]);
        return $result['total'] ?? 0;
    }

        /**
     * Récupère les commandes d'une boutique avec filtres et pagination
     * @param int 
     * @param array 
     * @param int 
     * @param int 
     * @return array 
     */
    public function getByBoutique(int $idBoutique, array $filters = [], int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT 
                    c.idCommande,
                    c.numeroCommande,
                    c.dateCommande,
                    c.total,
                    c.devise,
                    c.statut,
                    CONCAT(u.prenomUtilisateur, ' ', u.nomUtilisateur) AS nomClient,
                    u.emailUtilisateur AS emailClient,
                    u.telephone AS telephoneClient,
                    COUNT(cp.idProduit) AS nombreArticles
                FROM commande c
                JOIN utilisateur u ON c.idClient = u.idUtilisateur
                LEFT JOIN commande_produit cp ON c.idCommande = cp.idCommande
                WHERE c.idBoutique = :idBoutique";
        
        $countSql = "SELECT COUNT(DISTINCT c.idCommande) as total 
                     FROM commande c 
                     WHERE c.idBoutique = :idBoutique";
        
        $params = ['idBoutique' => $idBoutique];
        $countParams = ['idBoutique' => $idBoutique];
        
        if (!empty($filters['statut'])) {
            $sql .= " AND c.statut = :statut";
            $countSql .= " AND c.statut = :statut";
            $params['statut'] = $filters['statut'];
            $countParams['statut'] = $filters['statut'];
        }
        
        if (!empty($filters['date_debut'])) {
            $sql .= " AND DATE(c.dateCommande) >= :date_debut";
            $countSql .= " AND DATE(c.dateCommande) >= :date_debut";
            $params['date_debut'] = $filters['date_debut'];
            $countParams['date_debut'] = $filters['date_debut'];
        }
        
        if (!empty($filters['date_fin'])) {
            $sql .= " AND DATE(c.dateCommande) <= :date_fin";
            $countSql .= " AND DATE(c.dateCommande) <= :date_fin";
            $params['date_fin'] = $filters['date_fin'];
            $countParams['date_fin'] = $filters['date_fin'];
        }
        
        if (!empty($filters['client'])) {
            $sql .= " AND (u.nomUtilisateur LIKE :client OR u.prenomUtilisateur LIKE :client OR u.emailUtilisateur LIKE :client)";
            $countSql .= " AND (u.nomUtilisateur LIKE :client OR u.prenomUtilisateur LIKE :client OR u.emailUtilisateur LIKE :client)";
            $params['client'] = '%' . $filters['client'] . '%';
            $countParams['client'] = '%' . $filters['client'] . '%';
        }
        
        $sql .= " GROUP BY c.idCommande ORDER BY c.dateCommande DESC LIMIT :limit OFFSET :offset";
        $totalResult = $this->db->fetch($countSql, $countParams);
        $total = $totalResult['total'] ?? 0;        
        $params['limit'] = $limit;
        $params['offset'] = $offset;
        $data = $this->db->fetchAll($sql, $params);
        
        return [
            'data' => $data,
            'total' => $total,
            'pages' => ceil($total / $limit),
            'current_page' => $page,
            'limit' => $limit
        ];
    }

    /**
     * Compte les commandes d'une boutique par statut
     */
    public function countByBoutiqueAndStatus(int $idBoutique, string $statut): int
    {
        $sql = "SELECT COUNT(*) as total FROM commande WHERE idBoutique = :idBoutique AND statut = :statut";
        $result = $this->db->fetch($sql, [
            'idBoutique' => $idBoutique,
            'statut' => $statut
        ]);
        return $result['total'] ?? 0;
    }

/**
 * Récupère les commandes d'un client avec détails
 */
public function getByClient(int $idClient): array
{
    $sql = "SELECT 
                c.idCommande,
                c.numeroCommande,
                c.dateCommande,
                c.total,
                c.statut,
                b.idBoutique,
                b.nomBoutique,
                b.slugBoutique,
                COUNT(cp.idProduit) as nb_articles,
                GROUP_CONCAT(p.nomProduit SEPARATOR '||') as produits
            FROM commande c
            JOIN boutique b ON c.idBoutique = b.idBoutique
            LEFT JOIN commande_produit cp ON c.idCommande = cp.idCommande
            LEFT JOIN produit p ON cp.idProduit = p.idProduit
            WHERE c.idClient = :idClient
            GROUP BY c.idCommande
            ORDER BY c.dateCommande DESC";
    
    $result = $this->db->fetchAll($sql, ['idClient' => $idClient]);
    
    // Traiter les produits pour chaque commande
    foreach ($result as &$commande) {
        if (!empty($commande['produits'])) {
            $commande['produits_liste'] = explode('||', $commande['produits']);
            $commande['produits_liste'] = array_slice($commande['produits_liste'], 0, 3); // Garder 3 premiers
        } else {
            $commande['produits_liste'] = [];
        }
    }
    
    return $result;
}

/**
 * Annuler une commande et restaurer le stock
 */
public function annulerCommande($idCommande)
{
    $this->db->beginTransaction();
    
    try {
        $items = $this->getItemsWithProducts($idCommande);
        
        foreach ($items as $item) {
            $this->db->execute(
                "UPDATE produit SET stock = stock + ? WHERE idProduit = ?",
                [$item['quantite'], $item['idProduit']]
            );
        }
        
        $this->db->execute(
            "UPDATE commande SET statut = 'annulee' WHERE idCommande = ?",
            [$idCommande]
        );
        
        $this->db->commit();
        return true;
        
    } catch (Exception $e) {
        $this->db->rollBack();
        error_log("Erreur annulation commande: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupérer les items d'une commande avec les IDs produits
 */
public function getItemsWithProducts($idCommande)
{
    $sql = "SELECT cp.*, p.idProduit 
            FROM commande_produit cp
            JOIN produit p ON cp.idProduit = p.idProduit
            WHERE cp.idCommande = ?";
    return $this->db->fetchAll($sql, [$idCommande]);
}

}