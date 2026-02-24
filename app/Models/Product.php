<?php

class Product {
    private $db;

    public function __construct() { 
        $this->db = Database::getInstance(); 
    }

/**
 * Récupère la liste globale des produits avec leur image (Tableau principal)
 */
public function getPaginatedGlobal($limit, $offset, $filters = []) {
    // ✅ On utilise LEFT JOIN pour les deux tables
    $sql = "SELECT p.*, b.nomBoutique, c.nomCategorie, 
            (SELECT urlImage FROM image_produit WHERE idProduit = p.idProduit ORDER BY estPrincipale DESC LIMIT 1) as image_principale
            FROM produit p 
            LEFT JOIN boutique b ON p.idBoutique = b.idBoutique
            LEFT JOIN categorie c ON p.idCategorie = c.idCategorie
            WHERE 1=1";
    
    $params = [];

    if (!empty($filters['q'])) {
        $sql .= " AND (p.nomProduit LIKE :s1 OR b.nomBoutique LIKE :s2)";
        $search = "%" . $filters['q'] . "%";
        $params[':s1'] = $search;
        $params[':s2'] = $search;
    }
    
    $sql .= " ORDER BY p.dateAjout DESC LIMIT $limit OFFSET $offset";
    return $this->db->fetchAll($sql, $params);
}

    /**
     * Récupère les détails complets d'un produit (pour la future modale)
     */
    public function getFullDetails(int $id) {
        $sql = "SELECT p.*, b.nomBoutique, b.slugBoutique, c.nomCategorie,
                (SELECT urlImage FROM image_produit WHERE idProduit = p.idProduit ORDER BY estPrincipale DESC LIMIT 1) as image_principale
                FROM produit p 
                JOIN boutique b ON p.idBoutique = b.idBoutique
                LEFT JOIN categorie c ON p.idCategorie = c.idCategorie
                WHERE p.idProduit = ?";
        
        $product = $this->db->fetch($sql, [$id]);

        if ($product) {
            // On récupère toutes les images au cas où il y en aurait plusieurs
            $product['images'] = $this->db->fetchAll("SELECT urlImage FROM image_produit WHERE idProduit = ?", [$id]);
        }
        
        return $product;
    }

/**
 * Compte le nombre total de produits (pour la pagination)
 */
public function countAll($filters = []) {
    $sql = "SELECT COUNT(*) as total FROM produit p 
            LEFT JOIN boutique b ON p.idBoutique = b.idBoutique 
            WHERE 1=1";
    $params = [];

    if (!empty($filters['q'])) {
        $sql .= " AND (p.nomProduit LIKE :s1 OR b.nomBoutique LIKE :s2)";
        $search = "%" . $filters['q'] . "%";
        $params[':s1'] = $search;
        $params[':s2'] = $search;
    }

    $res = $this->db->fetch($sql, $params);
    return (int)($res['total'] ?? 0);
}

    /**
     * Met à jour le statut (Bannir / Rétablir)
     */
    public function updateStatus($id, $status) {
        $sql = "UPDATE produit SET statutProduit = ? WHERE idProduit = ?";
        return $this->db->execute($sql, [$status, (int)$id]);
    }

    /**
     * Active/Désactive la mise en avant (Vedette)
     */
    public function toggleFeatured($id, $value) {
        $sql = "UPDATE produit SET misEnAvant = ? WHERE idProduit = ?";
        return $this->db->execute($sql, [(int)$value, (int)$id]);
    }


}