<?php

require_once dirname(__DIR__, 2) . '/core/Database.php';

class Boutique
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Récupère toutes les boutiques actives
     */
    public function getActives($limit = null, $offset = 0)
    {
        $sql = "SELECT b.*, 
                       pb.logo, pb.banniere, pb.descriptionBoutique,
                       (SELECT COUNT(*) FROM produit WHERE idBoutique = b.idBoutique AND statutProduit = 'disponible') as nb_produits
                FROM boutique b
                LEFT JOIN parametre_boutique pb ON b.idBoutique = pb.idBoutique
                WHERE b.statut = 'actif'
                ORDER BY b.dateCreation DESC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            return $this->db->fetchAll($sql, [$limit, $offset]);
        }
        
        return $this->db->fetchAll($sql);
    }

    /**
     * Récupère une boutique par son slug
     */
    public function getBySlug($slug)
    {
        $sql = "SELECT b.*, 
                       pb.*,
                       u.nomUtilisateur, u.prenomUtilisateur
                FROM boutique b
                LEFT JOIN parametre_boutique pb ON b.idBoutique = pb.idBoutique
                LEFT JOIN utilisateur u ON b.idProprietaire = u.idUtilisateur
                WHERE b.slugBoutique = ? AND b.statut = 'actif'";
        
        return $this->db->fetch($sql, [$slug]);
    }

    /**
     * Récupère les produits d'une boutique avec pagination
     */
    public function getProduits($boutiqueId, $categorieId = null, $page = 1, $limit = 12)
    {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT p.*, 
                       (SELECT urlImage FROM image_produit 
                        WHERE idProduit = p.idProduit AND estPrincipale = 1 LIMIT 1) as image
                FROM produit p
                WHERE p.idBoutique = ? AND p.statutProduit = 'disponible'";
        
        $params = [$boutiqueId];
        
        if ($categorieId) {
            $sql .= " AND p.idCategorie = ?";
            $params[] = $categorieId;
        }
        
        $sql .= " ORDER BY p.misEnAvant DESC, p.dateAjout DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Récupère les catégories d'une boutique avec nombre de produits
     */
    public function getCategories($boutiqueId)
    {
        $sql = "SELECT c.*, 
                       (SELECT COUNT(*) FROM produit 
                        WHERE idCategorie = c.idCategorie AND statutProduit = 'disponible') as nb_produits
                FROM categorie c
                WHERE c.idBoutique = ? AND c.actif = 1
                ORDER BY c.nomCategorie ASC";
        
        return $this->db->fetchAll($sql, [$boutiqueId]);
    }

    /**
     * Compte le nombre total de produits d'une boutique (pour pagination)
     */
    public function countProduits($boutiqueId, $categorieId = null)
    {
        $sql = "SELECT COUNT(*) as total FROM produit 
                WHERE idBoutique = ? AND statutProduit = 'disponible'";
        
        $params = [$boutiqueId];
        
        if ($categorieId) {
            $sql .= " AND idCategorie = ?";
            $params[] = $categorieId;
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['total'] ?? 0;
    }

    /**
 * Récupère les boutiques avec leur nombre de ventes
 */
public function getActivesAvecVentes($limit = null, $offset = 0)
{
    $sql = "SELECT b.*, 
                   pb.logo, pb.banniere, pb.descriptionBoutique,
                   (SELECT COUNT(*) FROM produit WHERE idBoutique = b.idBoutique AND statutProduit = 'disponible') as nb_produits,
                   (SELECT COUNT(*) FROM commande WHERE idBoutique = b.idBoutique) as nb_commandes,
                   (SELECT COALESCE(SUM(total), 0) FROM commande WHERE idBoutique = b.idBoutique) as ca_total
            FROM boutique b
            LEFT JOIN parametre_boutique pb ON b.idBoutique = pb.idBoutique
            WHERE b.statut = 'actif'
            ORDER BY nb_commandes DESC, ca_total DESC";
    
    if ($limit) {
        $sql .= " LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$limit, $offset]);
    }
    
    return $this->db->fetchAll($sql);
}
}