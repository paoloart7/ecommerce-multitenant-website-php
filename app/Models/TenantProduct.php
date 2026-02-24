<?php

require_once dirname(__DIR__, 2) . '/core/Database.php';
require_once 'ProductImage.php';

class TenantProduct
{
    private $db;
    private $imageModel;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->imageModel = new ProductImage();
    }

    /**
     * Récupère les produits d'une boutique avec pagination et filtres
     */
    public function getByBoutique(int $idBoutique, array $filters = [], int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT p.*, 
                c.nomCategorie,
                   COALESCE(
                       (SELECT urlImage FROM image_produit 
                        WHERE idProduit = p.idProduit AND estPrincipale = 1 
                        LIMIT 1),
                       (SELECT urlImage FROM image_produit 
                        WHERE idProduit = p.idProduit 
                        ORDER BY ordre ASC 
                        LIMIT 1)
                   ) as imagePrincipale
            FROM produit p
            LEFT JOIN categorie c ON p.idCategorie = c.idCategorie
            WHERE p.idBoutique = :idBoutique";        
        $countSql = "SELECT COUNT(*) as total FROM produit WHERE idBoutique = :idBoutique";
        $params = ['idBoutique' => $idBoutique];
        
        if (!empty($filters['categorie'])) {
            $sql .= " AND p.idCategorie = :categorie";
            $countSql .= " AND idCategorie = :categorie";
            $params['categorie'] = $filters['categorie'];
        }
        
        if (!empty($filters['statut'])) {
            $sql .= " AND p.statutProduit = :statut";
            $countSql .= " AND statutProduit = :statut";
            $params['statut'] = $filters['statut'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (p.nomProduit LIKE :search OR p.sku LIKE :search)";
            $countSql .= " AND (nomProduit LIKE :search OR sku LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        $sql .= " ORDER BY p.dateAjout DESC LIMIT :limit OFFSET :offset";
        
        $totalResult = $this->db->fetch($countSql, $params);
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
     * Récupère un produit avec toutes ses infos
     */
    public function getFullDetails(int $idProduit): ?array
    {
        $sql = "SELECT p.*, c.nomCategorie
                FROM produit p
                LEFT JOIN categorie c ON p.idCategorie = c.idCategorie
                WHERE p.idProduit = :idProduit";
        
        $product = $this->db->fetch($sql, ['idProduit' => $idProduit]);
        
        if ($product) {
            $product['images'] = $this->imageModel->getByProduct($idProduit);
        }
        
        return $product;
    }

    /**
     * Crée un nouveau produit
     */
    public function create(int $idBoutique, array $data): int|false
    {
        $sql = "INSERT INTO produit (
                    idBoutique, idCategorie, nomProduit, slugProduit,
                    descriptionCourte, descriptionComplete,
                    prix, prixPromo, dateDebutPromo, dateFinPromo,
                    cout, stock, stockAlerte, sku, codeBarres,
                    poids, dimensions, statutProduit, misEnAvant, nouveaute
                ) VALUES (
                    :idBoutique, :idCategorie, :nomProduit, :slugProduit,
                    :descriptionCourte, :descriptionComplete,
                    :prix, :prixPromo, :dateDebutPromo, :dateFinPromo,
                    :cout, :stock, :stockAlerte, :sku, :codeBarres,
                    :poids, :dimensions, :statutProduit, :misEnAvant, :nouveaute
                )";
        
        $slug = $data['slugProduit'] ?? $this->generateSlug($data['nomProduit']);
        
        $result = $this->db->execute($sql, [
            'idBoutique' => $idBoutique,
            'idCategorie' => $data['idCategorie'] ?? null,
            'nomProduit' => $data['nomProduit'],
            'slugProduit' => $slug,
            'descriptionCourte' => $data['descriptionCourte'] ?? null,
            'descriptionComplete' => $data['descriptionComplete'] ?? null,
            'prix' => $data['prix'],
            'prixPromo' => $data['prixPromo'] ?? null,
            'dateDebutPromo' => $data['dateDebutPromo'] ?? null,
            'dateFinPromo' => $data['dateFinPromo'] ?? null,
            'cout' => $data['cout'] ?? null,
            'stock' => $data['stock'] ?? 0,
            'stockAlerte' => $data['stockAlerte'] ?? 10,
            'sku' => $data['sku'] ?? null,
            'codeBarres' => $data['codeBarres'] ?? null,
            'poids' => $data['poids'] ?? null,
            'dimensions' => $data['dimensions'] ?? null,
            'statutProduit' => $data['statutProduit'] ?? 'brouillon',
            'misEnAvant' => $data['misEnAvant'] ?? 0,
            'nouveaute' => $data['nouveaute'] ?? 0
        ]);
        
        return $result ? $this->db->lastInsertId() : false;
    }

/**
 * Met à jour un produit
 */
public function update(int $idProduit, array $data): bool
{
    $sql = "UPDATE produit SET
                idCategorie = :idCategorie,
                nomProduit = :nomProduit,
                slugProduit = :slugProduit,
                descriptionCourte = :descriptionCourte,
                descriptionComplete = :descriptionComplete,
                prix = :prix,
                prixPromo = :prixPromo,
                dateDebutPromo = :dateDebutPromo,
                dateFinPromo = :dateFinPromo,
                cout = :cout,
                stock = :stock,
                stockAlerte = :stockAlerte,
                sku = :sku,
                codeBarres = :codeBarres,
                poids = :poids,
                dimensions = :dimensions,
                statutProduit = :statutProduit,
                misEnAvant = :misEnAvant,
                nouveaute = :nouveaute,
                dateModification = NOW()
            WHERE idProduit = :idProduit";
    
    $slug = $data['slugProduit'] ?? $this->generateSlug($data['nomProduit']);
    
    return $this->db->execute($sql, [
        'idCategorie' => $data['idCategorie'] ?? null,
        'nomProduit' => $data['nomProduit'],
        'slugProduit' => $slug,
        'descriptionCourte' => $data['descriptionCourte'] ?? null,
        'descriptionComplete' => $data['descriptionComplete'] ?? null,
        'prix' => $data['prix'],
        'prixPromo' => $data['prixPromo'] ?? null,
        'dateDebutPromo' => $data['dateDebutPromo'] ?? null,
        'dateFinPromo' => $data['dateFinPromo'] ?? null,
        'cout' => $data['cout'] ?? null,
        'stock' => $data['stock'] ?? 0,
        'stockAlerte' => $data['stockAlerte'] ?? 10,
        'sku' => $data['sku'] ?? null,
        'codeBarres' => $data['codeBarres'] ?? null,
        'poids' => $data['poids'] ?? null,
        'dimensions' => $data['dimensions'] ?? null,
        'statutProduit' => $data['statutProduit'] ?? 'brouillon',
        'misEnAvant' => $data['misEnAvant'] ?? 0,
        'nouveaute' => $data['nouveaute'] ?? 0,
        'idProduit' => $idProduit
    ]);
}


 /* Supprime un produit et ses images
 */
public function delete(int $idProduit): bool
{
    $images = $this->imageModel->getByProduct($idProduit);
    
    foreach ($images as $image) {
        $filePath = dirname(__DIR__, 2) . '/public' . $image['urlImage'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        if (!empty($image['urlThumbnail'])) {
            $thumbPath = dirname(__DIR__, 2) . '/public' . $image['urlThumbnail'];
            if (file_exists($thumbPath)) {
                unlink($thumbPath);
            }
        }
    }
    
    $sql = "DELETE FROM produit WHERE idProduit = :idProduit";
    return $this->db->execute($sql, ['idProduit' => $idProduit]);
}

    /**
     * Archive un produit
     */
    public function archive(int $idProduit): bool
    {
        $sql = "UPDATE produit SET statutProduit = 'archive' WHERE idProduit = :idProduit";
        return $this->db->execute($sql, ['idProduit' => $idProduit]);
    }

    /**
     * Statistiques produits
     */
    public function getStats(int $idBoutique): array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN statutProduit = 'disponible' THEN 1 ELSE 0 END) as disponibles,
                    SUM(CASE WHEN statutProduit = 'brouillon' THEN 1 ELSE 0 END) as brouillons,
                    SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as rupture,
                    SUM(CASE WHEN stock <= stockAlerte AND stock > 0 THEN 1 ELSE 0 END) as stockFaible
                FROM produit 
                WHERE idBoutique = :idBoutique";
        
        return $this->db->fetch($sql, ['idBoutique' => $idBoutique]) ?? [];
    }

    /**
     * Génère un slug
     */
    private function generateSlug(string $nom): string
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nom)));
    }
}