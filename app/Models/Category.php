<?php

class Category
{
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getParentsPaginated(int $limit, int $offset) {
        $sql = "SELECT c.*, b.nomBoutique, pb.logo as boutiqueLogo 
                FROM categorie c
                JOIN boutique b ON c.idBoutique = b.idBoutique
                LEFT JOIN parametre_boutique pb ON b.idBoutique = pb.idBoutique
                WHERE c.idCategorieParent IS NULL
                ORDER BY c.dateCreation DESC LIMIT $limit OFFSET $offset";
        return $this->db->fetchAll($sql);
    }

    public function getChildrenPaginated(int $parentId, int $limit, int $offset) {
        $sql = "SELECT c.*, b.nomBoutique, pb.logo as boutiqueLogo 
                FROM categorie c
                JOIN boutique b ON c.idBoutique = b.idBoutique
                LEFT JOIN parametre_boutique pb ON b.idBoutique = pb.idBoutique
                WHERE c.idCategorieParent = :pid
                ORDER BY c.dateCreation DESC LIMIT $limit OFFSET $offset";
        return $this->db->fetchAll($sql, [':pid' => $parentId]);
    }

    public function countParents() {
        $res = $this->db->fetch("SELECT COUNT(*) as total FROM categorie WHERE idCategorieParent IS NULL");
        return (int)($res['total'] ?? 0);
    }

    public function countChildren(int $parentId) {
        $res = $this->db->fetch("SELECT COUNT(*) as total FROM categorie WHERE idCategorieParent = ?", [$parentId]);
        return (int)($res['total'] ?? 0);
    }

    public function getById(int $id) {
        return $this->db->fetch("SELECT * FROM categorie WHERE idCategorie = ?", [$id]);
    }

    public function getBoutiques() {
        return $this->db->fetchAll("SELECT idBoutique, nomBoutique FROM boutique");
    }

    public function save(array $data) {
        $params = [
            ':idB'    => $data['idBoutique'],
            ':idP'    => !empty($data['idCategorieParent']) ? $data['idCategorieParent'] : null,
            ':nom'    => $data['nomCategorie'],
            ':slug'   => $data['slugCategorie'],
            ':desc'   => $data['description'],
            ':img'    => $data['image'] ?? null,
            ':ordre'  => (int)$data['ordre'],
            ':actif'  => (int)$data['actif']
        ];
        if (!empty($data['idCategorie'])) {
            $params[':id'] = $data['idCategorie'];
            $sql = "UPDATE categorie SET idBoutique = :idB, idCategorieParent = :idP, nomCategorie = :nom, slugCategorie = :slug, description = :desc, image = :img, ordre = :ordre, actif = :actif WHERE idCategorie = :id";
        } else {
            $sql = "INSERT INTO categorie (idBoutique, idCategorieParent, nomCategorie, slugCategorie, description, image, ordre, actif) VALUES (:idB, :idP, :nom, :slug, :desc, :img, :ordre, :actif)";
        }
        return $this->db->execute($sql, $params);
    }

    public function delete(int $id)
    {
        $this->db->execute("UPDATE categorie SET idCategorieParent = NULL WHERE idCategorieParent = ?", [$id]);
        
        $sql = "DELETE FROM categorie WHERE idCategorie = ?";
        return $this->db->execute($sql, [$id]);
    }
}