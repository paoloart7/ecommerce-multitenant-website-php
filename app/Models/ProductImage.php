<?php

require_once dirname(__DIR__, 2) . '/core/Database.php';

class ProductImage
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Récupère toutes les images d'un produit
     */
    public function getByProduct(int $idProduit): array
    {
        $sql = "SELECT * FROM image_produit 
                WHERE idProduit = :idProduit 
                ORDER BY ordre ASC, estPrincipale DESC";
        
        return $this->db->fetchAll($sql, ['idProduit' => $idProduit]);
    }

    /**
     * Ajoute une image
     */
    public function create(array $data): int|false
    {
        $sql = "INSERT INTO image_produit 
                (idProduit, urlImage, urlThumbnail, altText, ordre, estPrincipale) 
                VALUES 
                (:idProduit, :urlImage, :urlThumbnail, :altText, :ordre, :estPrincipale)";
        
        $result = $this->db->execute($sql, [
            'idProduit' => $data['idProduit'],
            'urlImage' => $data['urlImage'],
            'urlThumbnail' => $data['urlThumbnail'] ?? null,
            'altText' => $data['altText'] ?? null,
            'ordre' => $data['ordre'] ?? 0,
            'estPrincipale' => $data['estPrincipale'] ?? 0
        ]);
        
        return $result ? $this->db->lastInsertId() : false;
    }

    /**
     * Supprime une image
     */
    public function delete(int $idImage): bool
    {
        $image = $this->db->fetch("SELECT urlImage, urlThumbnail FROM image_produit WHERE idImage = ?", [$idImage]);
        
        if ($image) {
            $basePath = dirname(__DIR__, 2) . '/public';
            if (!empty($image['urlImage']) && file_exists($basePath . $image['urlImage'])) {
                unlink($basePath . $image['urlImage']);
            }
            if (!empty($image['urlThumbnail']) && file_exists($basePath . $image['urlThumbnail'])) {
                unlink($basePath . $image['urlThumbnail']);
            }
        }
        
        $sql = "DELETE FROM image_produit WHERE idImage = :idImage";
        return $this->db->execute($sql, ['idImage' => $idImage]);
    }

    /**
     * Supprime toutes les images d'un produit
     */
    public function deleteByProduct(int $idProduit): bool
    {
        $images = $this->getByProduct($idProduit);
        foreach ($images as $image) {
            $this->delete($image['idImage']);
        }
        return true;
    }

    /**
     * Définit une image comme principale
     */
    public function setPrincipal(int $idProduit, int $idImage): bool
    {
        $this->db->execute("UPDATE image_produit SET estPrincipale = 0 WHERE idProduit = ?", [$idProduit]);
        return $this->db->execute("UPDATE image_produit SET estPrincipale = 1 WHERE idImage = ?", [$idImage]);
    }
}