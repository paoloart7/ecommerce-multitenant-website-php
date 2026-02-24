<?php

require_once dirname(__DIR__, 2) . '/core/Database.php';

class TenantCategory
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Récupère toutes les catégories d'une boutique
     */
    public function getByBoutique(int $idBoutique): array
    {
        $sql = "SELECT c.*, 
                       (SELECT COUNT(*) FROM produit WHERE idCategorie = c.idCategorie) as nombreProduits,
                       parent.nomCategorie as parentNom
                FROM categorie c
                LEFT JOIN categorie parent ON c.idCategorieParent = parent.idCategorie
                WHERE c.idBoutique = :idBoutique
                ORDER BY c.ordre ASC, c.nomCategorie ASC";
        
        return $this->db->fetchAll($sql, ['idBoutique' => $idBoutique]);
    }

    /**
     * Récupère uniquement les catégories parentes
     */
    public function getParents(int $idBoutique): array
    {
        $sql = "SELECT * FROM categorie 
                WHERE idBoutique = :idBoutique 
                AND idCategorieParent IS NULL 
                ORDER BY nomCategorie ASC";
        
        return $this->db->fetchAll($sql, ['idBoutique' => $idBoutique]);
    }


    /**
 * Récupère uniquement les catégories parentes avec statistiques complètes
 */
public function getParentsWithStats(int $idBoutique): array
{
    $sql = "SELECT 
                c.*,
                (SELECT COUNT(*) FROM categorie WHERE idCategorieParent = c.idCategorie) as nbSousCategories,
                (SELECT COUNT(*) FROM produit WHERE idCategorie = c.idCategorie) as nbProduitsDirects,
                (
                    SELECT COUNT(*) FROM produit p 
                    WHERE p.idCategorie IN (
                        SELECT idCategorie FROM categorie 
                        WHERE idCategorieParent = c.idCategorie
                    )
                ) as nbProduitsSousCategories,
                (
                    SELECT COUNT(*) FROM produit WHERE idCategorie = c.idCategorie
                ) + (
                    SELECT COUNT(*) FROM produit p 
                    WHERE p.idCategorie IN (
                        SELECT idCategorie FROM categorie 
                        WHERE idCategorieParent = c.idCategorie
                    )
                ) as totalProduits
            FROM categorie c
            WHERE c.idBoutique = :idBoutique 
            AND c.idCategorieParent IS NULL
            ORDER BY c.ordre ASC, c.nomCategorie ASC";
    
    return $this->db->fetchAll($sql, ['idBoutique' => $idBoutique]);
}

    /**
     * Récupère les sous-catégories d'une catégorie
     */
    public function getSubCategories(int $idCategorieParent): array
    {
        $sql = "SELECT c.*, 
                       (SELECT COUNT(*) FROM produit WHERE idCategorie = c.idCategorie) as nombreProduits
                FROM categorie c
                WHERE c.idCategorieParent = :idParent
                ORDER BY c.ordre ASC, c.nomCategorie ASC";
        
        return $this->db->fetchAll($sql, ['idParent' => $idCategorieParent]);
    }

    /**
     * Récupère une catégorie par son ID
     */
    public function getById(int $idCategorie): ?array
    {
        $sql = "SELECT * FROM categorie WHERE idCategorie = :idCategorie";
        return $this->db->fetch($sql, ['idCategorie' => $idCategorie]);
    }

    /**
     * Crée une nouvelle catégorie
     */
    public function create(int $idBoutique, array $data): int|false
    {
        // Vérifier l'unicité du nom
        if ($this->nomExists($idBoutique, $data['nomCategorie'])) {
            return false;
        }
        
        $sql = "INSERT INTO categorie (
                    idBoutique, idCategorieParent, nomCategorie, slugCategorie,
                    description, image, ordre, actif
                ) VALUES (
                    :idBoutique, :idCategorieParent, :nomCategorie, :slugCategorie,
                    :description, :image, :ordre, :actif
                )";
        
        $result = $this->db->execute($sql, [
            'idBoutique' => $idBoutique,
            'idCategorieParent' => $data['idCategorieParent'] ?? null,
            'nomCategorie' => $data['nomCategorie'],
            'slugCategorie' => $data['slugCategorie'] ?? $this->generateSlug($data['nomCategorie']),
            'description' => $data['description'] ?? null,
            'image' => $data['image'] ?? null,
            'ordre' => $data['ordre'] ?? 0,
            'actif' => $data['actif'] ?? 1
        ]);
        
        return $result ? $this->db->lastInsertId() : false;
    }

    /**
     * Met à jour une catégorie
     */
    public function update(int $idCategorie, array $data): bool
    {
        $sql = "UPDATE categorie SET
                    idCategorieParent = :idCategorieParent,
                    nomCategorie = :nomCategorie,
                    slugCategorie = :slugCategorie,
                    description = :description,
                    image = :image,
                    ordre = :ordre,
                    actif = :actif
                WHERE idCategorie = :idCategorie";
        
        return $this->db->execute($sql, [
            'idCategorieParent' => $data['idCategorieParent'] ?? null,
            'nomCategorie' => $data['nomCategorie'],
            'slugCategorie' => $data['slugCategorie'] ?? $this->generateSlug($data['nomCategorie']),
            'description' => $data['description'] ?? null,
            'image' => $data['image'] ?? null,
            'ordre' => $data['ordre'] ?? 0,
            'actif' => $data['actif'] ?? 1,
            'idCategorie' => $idCategorie
        ]);
    }

    /**
     * Supprime une catégorie
     */
    public function delete(int $idCategorie): bool
    {
        // Vérifier si la catégorie a des produits
        $sql = "SELECT COUNT(*) as total FROM produit WHERE idCategorie = :idCategorie";
        $result = $this->db->fetch($sql, ['idCategorie' => $idCategorie]);
        
        if ($result['total'] > 0) {
            return false; // Ne pas supprimer si des produits existent
        }
        
        // Mettre à null les sous-catégories qui référencent cette catégorie
        $sql = "UPDATE categorie SET idCategorieParent = NULL WHERE idCategorieParent = :idCategorie";
        $this->db->execute($sql, ['idCategorie' => $idCategorie]);
        
        // Supprimer la catégorie
        $sql = "DELETE FROM categorie WHERE idCategorie = :idCategorie";
        return $this->db->execute($sql, ['idCategorie' => $idCategorie]);
    }

    /**
     * Vérifie si un nom de catégorie existe déjà
     */
    public function nomExists(int $idBoutique, string $nom, ?int $ignoreId = null): bool
    {
        $sql = "SELECT COUNT(*) as total FROM categorie 
                WHERE idBoutique = :idBoutique AND nomCategorie = :nom";
        $params = ['idBoutique' => $idBoutique, 'nom' => $nom];
        
        if ($ignoreId) {
            $sql .= " AND idCategorie != :idCategorie";
            $params['idCategorie'] = $ignoreId;
        }
        
        $result = $this->db->fetch($sql, $params);
        return ($result['total'] ?? 0) > 0;
    }

    /**
     * Génère un slug unique
     */
    private function generateSlug(string $nom): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nom)));
        return $slug;
    }

    /**
     * Récupère l'arborescence complète des catégories
     */
    public function getTree(int $idBoutique): array
    {
        $categories = $this->getByBoutique($idBoutique);
        $tree = [];
        
        foreach ($categories as $cat) {
            if (is_null($cat['idCategorieParent'])) {
                $cat['sous_categories'] = $this->buildTree($categories, $cat['idCategorie']);
                $tree[] = $cat;
            }
        }
        
        return $tree;
    }

    /**
     * Construit l'arbre des sous-catégories
     */
    private function buildTree(array &$categories, int $parentId): array
    {
        $branch = [];
        foreach ($categories as $cat) {
            if ($cat['idCategorieParent'] == $parentId) {
                $cat['sous_categories'] = $this->buildTree($categories, $cat['idCategorie']);
                $branch[] = $cat;
            }
        }
        return $branch;
    }

/**
 * Statistiques complètes des catégories
 */
public function getStats(int $idBoutique): array
{
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN idCategorieParent IS NULL THEN 1 ELSE 0 END) as parents,
                SUM(CASE WHEN idCategorieParent IS NOT NULL THEN 1 ELSE 0 END) as enfants,
                SUM(CASE WHEN actif = 1 THEN 1 ELSE 0 END) as actifs,
                (SELECT COUNT(*) FROM produit WHERE idBoutique = :idBoutique2) as totalProduits
            FROM categorie 
            WHERE idBoutique = :idBoutique";
    
    $result = $this->db->fetch($sql, [
        'idBoutique' => $idBoutique,
        'idBoutique2' => $idBoutique
    ]);
    
    return $result ?: [
        'total' => 0,
        'parents' => 0,
        'enfants' => 0,
        'actifs' => 0,
        'totalProduits' => 0
    ];
}
}