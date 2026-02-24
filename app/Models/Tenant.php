<?php

class Tenant
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ══════════════════════════════════════════
    // LECTURE & LISTING (POUR L'ADMIN)
    // ══════════════════════════════════════════

/**
 * Liste paginée des boutiques avec infos propriétaires et logos
 */
public function getPaginated(int $limit, int $offset, array $filters = [])
{
    // Requête principale
    $sql = "SELECT b.*, 
                   CONCAT(u.prenomUtilisateur, ' ', u.nomUtilisateur) as proprietaire,
                   u.emailUtilisateur,
                   pb.logo
            FROM boutique b
            LEFT JOIN utilisateur u ON b.idProprietaire = u.idUtilisateur
            LEFT JOIN parametre_boutique pb ON b.idBoutique = pb.idBoutique
            WHERE 1=1";
    
    // Requête count
    $countSql = "SELECT COUNT(*) as total FROM boutique WHERE 1=1";
    
    $params = [];
    $countParams = [];
    
    // Filtre recherche
    if (!empty($filters['q'])) {
        $searchTerm = '%' . $filters['q'] . '%';
        
        // Requête principale
        $sql .= " AND (b.nomBoutique LIKE :search1 
                      OR b.slugBoutique LIKE :search2 
                      OR u.nomUtilisateur LIKE :search3 
                      OR u.prenomUtilisateur LIKE :search4)";
        
        // Requête count
        $countSql .= " AND (nomBoutique LIKE :search1 
                           OR slugBoutique LIKE :search2
                           OR idProprietaire IN (
                               SELECT idUtilisateur FROM utilisateur 
                               WHERE nomUtilisateur LIKE :search3 
                               OR prenomUtilisateur LIKE :search4
                           ))";
        
        // ✅ MÊMES NOMS DE PARAMÈTRES dans les deux tableaux
        $params['search1'] = $searchTerm;
        $params['search2'] = $searchTerm;
        $params['search3'] = $searchTerm;
        $params['search4'] = $searchTerm;
        
        $countParams['search1'] = $searchTerm;
        $countParams['search2'] = $searchTerm;
        $countParams['search3'] = $searchTerm;
        $countParams['search4'] = $searchTerm;
    }
    
    // Filtre statut
    if (!empty($filters['status'])) {
        $sql .= " AND b.statut = :status";
        $countSql .= " AND statut = :status";
        
        $params['status'] = $filters['status'];
        $countParams['status'] = $filters['status'];
    }
    
    $sql .= " ORDER BY b.dateCreation DESC LIMIT :limit OFFSET :offset";
    
    // Compter le total
    $totalResult = $this->db->fetch($countSql, $countParams);
    $total = $totalResult['total'] ?? 0;
    
    // Ajouter les paramètres de pagination
    $params['limit'] = $limit;
    $params['offset'] = $offset;
    
    // Récupérer les données
    $data = $this->db->fetchAll($sql, $params);
    
    return [
        'data' => $data,
        'total' => $total,
        'pages' => ceil($total / $limit),
        'current_page' => ($offset / $limit) + 1,
        'limit' => $limit
    ];
}

    /**
     * Compte le nombre de boutiques selon les filtres (pour la pagination)
     */
    public function countFiltered(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) as total 
                FROM boutique b
                LEFT JOIN utilisateur u ON b.idProprietaire = u.idUtilisateur
                WHERE 1=1";
        $params = [];

        if (!empty($filters['q'])) {
            $sql .= " AND (b.nomBoutique LIKE :q OR b.slugBoutique LIKE :q OR u.nomUtilisateur LIKE :q)";
            $params[':q'] = '%' . $filters['q'] . '%';
        }

        if (!empty($filters['status'])) {
            $sql .= " AND b.statut = :status";
            $params[':status'] = $filters['status'];
        }

        $res = $this->db->fetch($sql, $params);
        return isset($res['total']) ? (int)$res['total'] : 0;
    }

    // ══════════════════════════════════════════
    // STATISTIQUES & AUDIT
    // ══════════════════════════════════════════

    /**
     * Statistiques globales (pour le dashboard Admin)
     */
    public function getGlobalStats()
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN statut = 'actif' THEN 1 ELSE 0 END) as actives,
                    SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as en_attente,
                    SUM(CASE WHEN statut = 'suspendu' THEN 1 ELSE 0 END) as suspendues
                FROM boutique";
        
        $res = $this->db->fetch($sql);
        return $res ?: ['total' => 0, 'actives' => 0, 'en_attente' => 0, 'suspendues' => 0];
    }

    /**
     * Récupère les derniers logs d'activité d'une boutique
     */
    public function getLogs(int $id)
    {
        $sql = "SELECT * FROM audit_log 
                WHERE idBoutique = :id 
                ORDER BY dateAction DESC 
                LIMIT 50";
        return $this->db->fetchAll($sql, [':id' => $id]);
    }


    /**
     * Mise à jour du statut par l'Administrateur
     */
    public function updateStatus(int $id, string $status)
    {
        $sql = "UPDATE boutique SET statut = :status WHERE idBoutique = :id";
        return $this->db->execute($sql, [':status' => $status, ':id' => $id]);
    }

    /**
     * Mise à jour manuelle de l'abonnement par l'Administrateur
     */
    public function updateSubscription(int $idBoutique, string $type)
    {
        // 1. On expire les abonnements actuels
        $this->db->execute("UPDATE abonnement SET statut = 'expire' WHERE idBoutique = :id", [':id' => $idBoutique]);
        
        // 2. On crée le nouveau forfait
        $sql = "INSERT INTO abonnement (idBoutique, typeAbonnement, dateDebut, statut) 
                VALUES (:id, :type, NOW(), 'actif')";
        
        return $this->db->execute($sql, [
            ':id' => $idBoutique,
            ':type' => $type
        ]);
    }

    /**
     * Suppression définitive d'une boutique
     */
    public function delete(int $id)
    {
        $sql = "DELETE FROM boutique WHERE idBoutique = :id";
        return $this->db->execute($sql, [':id' => $id]);
    }


        /**
     * Trouve la boutique par l'ID du propriétaire
     */
    public function getByOwnerId(int $userId)
    {
        $sql = "SELECT * FROM boutique WHERE idProprietaire = :id LIMIT 1";
        return $this->db->fetch($sql, [':id' => $userId]);
    }

    /**
     * Récupère les infos d'une boutique par son ID (pour le dashboard)
     * Inclut les paramètres (logo, couleurs, etc.)
     */
public function getById(int $id)
{
    $sql = "SELECT 
                b.*, 
                ab.typeAbonnement,
                pb.logo,
                pb.banniere,
                pb.emailContact,
                pb.telephoneContact,
                pb.descriptionBoutique,
                pb.couleurPrimaire,
                pb.couleurSecondaire,
                pb.devise,
                CONCAT(u.prenomUtilisateur, ' ', u.nomUtilisateur) as proprietaire,
                u.emailUtilisateur,
                u.telephone as telProprietaire
            FROM boutique b 
            LEFT JOIN abonnement ab ON b.idBoutique = ab.idBoutique AND ab.statut = 'actif'
            LEFT JOIN parametre_boutique pb ON b.idBoutique = pb.idBoutique
            LEFT JOIN utilisateur u ON b.idProprietaire = u.idUtilisateur  -- ← AJOUTÉ
            WHERE b.idBoutique = :id";
    
    return $this->db->fetch($sql, [':id' => $id]);
}

    /**
     * Récupère les statistiques d'une boutique
     */
    public function getTenantStats(int $id)
    {
        $sqlCA = "SELECT SUM(total) as ca FROM commande WHERE idBoutique = :id AND statut != 'annulee'";
        $sqlCmd = "SELECT COUNT(*) as nb FROM commande WHERE idBoutique = :id";
        $sqlProd = "SELECT COUNT(*) as nb FROM produit WHERE idBoutique = :id";

        $ca = $this->db->fetch($sqlCA, [':id' => $id]);
        $cmd = $this->db->fetch($sqlCmd, [':id' => $id]);
        $prod = $this->db->fetch($sqlProd, [':id' => $id]);

        return [
            'ca' => $ca['ca'] ?? 0,
            'commandes' => $cmd['nb'] ?? 0,
            'produits' => $prod['nb'] ?? 0
        ];
    }

    /**
     * Créer une boutique
     */
    public function createShop(array $data)
    {
        $sql = "INSERT INTO boutique (idProprietaire, nomBoutique, slugBoutique, description, statut, dateCreation) 
                VALUES (:idProp, :nom, :slug, :desc, 'actif', NOW())";
        
        $this->db->execute($sql, [
            ':idProp' => $data['idProprietaire'],
            ':nom'    => $data['nomBoutique'],
            ':slug'   => $data['slugBoutique'],
            ':desc'   => $data['description']
        ]);

        return $this->db->lastInsertId();
    }

}