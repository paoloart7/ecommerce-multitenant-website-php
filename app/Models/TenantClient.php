<?php

require_once dirname(__DIR__, 2) . '/core/Database.php';

class TenantClient
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Récupère tous les clients d'une boutique avec pagination et filtres
     */
public function getByBoutique(int $idBoutique, array $filters = [], int $page = 1, int $limit = 15): array
{
    $offset = ($page - 1) * $limit;
    
    $sql = "SELECT 
                u.idUtilisateur,
                u.nomUtilisateur,
                u.prenomUtilisateur,
                u.emailUtilisateur,
                u.telephone,
                u.dateCreation,
                u.avatar,
                COUNT(DISTINCT c.idCommande) as nb_commandes,
                COALESCE(SUM(c.total), 0) as total_depenses,
                MAX(c.dateCommande) as derniere_commande,
                COALESCE(AVG(c.total), 0) as panier_moyen,
                SUM(CASE WHEN c.statut = 'en_attente' THEN 1 ELSE 0 END) as commandes_encours
            FROM commande c
            INNER JOIN utilisateur u ON c.idClient = u.idUtilisateur
            WHERE c.idBoutique = :idBoutique
            AND u.role = 'client'";
    
    $countSql = "SELECT COUNT(DISTINCT c.idClient) as total
                FROM commande c
                WHERE c.idBoutique = :idBoutique";
    
    $params = ['idBoutique' => $idBoutique];
    $countParams = ['idBoutique' => $idBoutique];
    
    if (!empty($filters['search'])) {
        $sql .= " AND (u.nomUtilisateur LIKE :search OR u.prenomUtilisateur LIKE :search OR u.emailUtilisateur LIKE :search)";
        $countSql .= " AND c.idClient IN (SELECT idUtilisateur FROM utilisateur WHERE nomUtilisateur LIKE :search OR prenomUtilisateur LIKE :search OR emailUtilisateur LIKE :search)";
        $params['search'] = '%' . $filters['search'] . '%';
        $countParams['search'] = '%' . $filters['search'] . '%';
    }
    
    if (!empty($filters['date_debut'])) {
        $sql .= " AND DATE(c.dateCommande) >= :date_debut";
        $countSql .= " AND DATE(dateCommande) >= :date_debut";
        $params['date_debut'] = $filters['date_debut'];
        $countParams['date_debut'] = $filters['date_debut'];
    }
    
    if (!empty($filters['date_fin'])) {
        $sql .= " AND DATE(c.dateCommande) <= :date_fin";
        $countSql .= " AND DATE(dateCommande) <= :date_fin";
        $params['date_fin'] = $filters['date_fin'];
        $countParams['date_fin'] = $filters['date_fin'];
    }
    
    $sql .= " GROUP BY u.idUtilisateur";
    
    if (!empty($filters['min_commandes'])) {
        $sql .= " HAVING nb_commandes >= :min_cmd";
        $params['min_cmd'] = $filters['min_commandes'];
    }
    
    $sql .= " ORDER BY total_depenses DESC, nb_commandes DESC LIMIT :limit OFFSET :offset";
    
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
     * Récupère les détails complets d'un client
     */
    public function getDetails(int $idClient, int $idBoutique): ?array
    {
        $sqlClient = "SELECT 
                        u.idUtilisateur,
                        u.nomUtilisateur,
                        u.prenomUtilisateur,
                        u.emailUtilisateur,
                        u.telephone,
                        u.dateCreation,
                        u.avatar,
                        u.statut
                      FROM utilisateur u
                      WHERE u.idUtilisateur = :idClient AND u.role = 'client'";
        
        $client = $this->db->fetch($sqlClient, ['idClient' => $idClient]);
        
        if (!$client) return null;
        
        $sqlStats = "SELECT 
                        COUNT(*) as nb_commandes,
                        COALESCE(SUM(total), 0) as total_depenses,
                        COALESCE(AVG(total), 0) as panier_moyen,
                        MIN(dateCommande) as premiere_commande,
                        MAX(dateCommande) as derniere_commande,
                        SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as commandes_encours,
                        SUM(CASE WHEN statut = 'livree' THEN 1 ELSE 0 END) as commandes_livrees,
                        SUM(CASE WHEN statut = 'annulee' THEN 1 ELSE 0 END) as commandes_annulees
                     FROM commande 
                     WHERE idClient = :idClient AND idBoutique = :idBoutique";
        
        $stats = $this->db->fetch($sqlStats, [
            'idClient' => $idClient,
            'idBoutique' => $idBoutique
        ]);
        
        $sqlCommandes = "SELECT 
                            c.idCommande,
                            c.numeroCommande,
                            c.dateCommande,
                            c.total,
                            c.statut,
                            COUNT(cp.idProduit) as nb_articles,
                            GROUP_CONCAT(p.nomProduit SEPARATOR ', ') as produits
                         FROM commande c
                         LEFT JOIN commande_produit cp ON c.idCommande = cp.idCommande
                         LEFT JOIN produit p ON cp.idProduit = p.idProduit
                         WHERE c.idClient = :idClient AND c.idBoutique = :idBoutique
                         GROUP BY c.idCommande
                         ORDER BY c.dateCommande DESC";
        
        $commandes = $this->db->fetchAll($sqlCommandes, [
            'idClient' => $idClient,
            'idBoutique' => $idBoutique
        ]);
        
        $sqlAdresses = "SELECT * FROM adresse 
                        WHERE idUtilisateur = :idClient 
                        ORDER BY estDefaut DESC, dateCreation DESC";
        
        $adresses = $this->db->fetchAll($sqlAdresses, ['idClient' => $idClient]);
        
        return [
            'client' => $client,
            'stats' => $stats,
            'commandes' => $commandes,
            'adresses' => $adresses
        ];
    }

    /**
     * Récupère les statistiques globales des clients
     */
public function getStats(int $idBoutique): array
{
    $sql = "SELECT 
                COUNT(DISTINCT c.idClient) as total_clients,
                COUNT(DISTINCT CASE WHEN c.dateCommande >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN c.idClient END) as clients_actifs_30j,
                COUNT(DISTINCT CASE WHEN c.dateCommande >= DATE_SUB(NOW(), INTERVAL 90 DAY) THEN c.idClient END) as clients_actifs_90j,
                COALESCE(AVG(c.total), 0) as panier_moyen,
                COUNT(c.idCommande) as total_commandes,
                COALESCE(SUM(c.total), 0) as ca_total,
                COUNT(DISTINCT CASE WHEN DATE(c.dateCommande) = CURDATE() THEN c.idClient END) as nouveaux_aujourdhui
            FROM commande c
            WHERE c.idBoutique = :idBoutique
            AND c.statut NOT IN ('annulee', 'remboursee')";
    
    $result = $this->db->fetch($sql, ['idBoutique' => $idBoutique]);
    
    $sqlNouveaux = "SELECT COUNT(DISTINCT idClient) as nouveaux
                    FROM commande
                    WHERE idBoutique = :idBoutique1
                    AND dateCommande >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    AND idClient NOT IN (
                        SELECT DISTINCT idClient FROM commande 
                        WHERE idBoutique = :idBoutique2 
                        AND dateCommande < DATE_SUB(NOW(), INTERVAL 30 DAY)
                    )";
    
    $nouveaux = $this->db->fetch($sqlNouveaux, [
        'idBoutique1' => $idBoutique,
        'idBoutique2' => $idBoutique
    ]);
    
    return [
        'total_clients' => (int)($result['total_clients'] ?? 0),
        'clients_actifs_30j' => (int)($result['clients_actifs_30j'] ?? 0),
        'clients_actifs_90j' => (int)($result['clients_actifs_90j'] ?? 0),
        'nouveaux_30j' => (int)($nouveaux['nouveaux'] ?? 0),
        'nouveaux_aujourdhui' => (int)($result['nouveaux_aujourdhui'] ?? 0),
        'panier_moyen' => round($result['panier_moyen'] ?? 0, 2),
        'total_commandes' => (int)($result['total_commandes'] ?? 0),
        'ca_total' => round($result['ca_total'] ?? 0, 2)
    ];
}
    /**
     * Exporte la liste des clients (pour CSV)
     */
    public function getExportData(int $idBoutique): array
    {
        $sql = "SELECT 
                    u.nomUtilisateur as nom,
                    u.prenomUtilisateur as prenom,
                    u.emailUtilisateur as email,
                    u.telephone,
                    DATE(u.dateCreation) as date_inscription,
                    COUNT(c.idCommande) as nb_commandes,
                    COALESCE(SUM(c.total), 0) as total_depenses,
                    MAX(c.dateCommande) as derniere_commande
                FROM utilisateur u
                LEFT JOIN commande c ON u.idUtilisateur = c.idClient AND c.idBoutique = :idBoutique
                WHERE u.role = 'client'
                GROUP BY u.idUtilisateur
                ORDER BY total_depenses DESC";
        
        return $this->db->fetchAll($sql, ['idBoutique' => $idBoutique]);
    }
}