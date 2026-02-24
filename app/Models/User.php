<?php

class User
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // --- LECTURE (READ) ---

    public function getPaginated($limit, $offset, $filters = [])
    {
        $sql = "SELECT * FROM utilisateur WHERE 1=1";
        $params = [];

        if (!empty($filters['q'])) {
            // Utilisation de paramètres nommés uniques pour éviter les conflits
            $sql .= " AND (nomUtilisateur LIKE :s1 OR prenomUtilisateur LIKE :s2 OR emailUtilisateur LIKE :s3)";
            $params[':s1'] = '%' . $filters['q'] . '%';
            $params[':s2'] = '%' . $filters['q'] . '%';
            $params[':s3'] = '%' . $filters['q'] . '%';
        }
        if (!empty($filters['role'])) {
            $sql .= " AND role = :role";
            $params[':role'] = $filters['role'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND statut = :status";
            $params[':status'] = $filters['status'];
        }

        $sql .= " ORDER BY dateCreation DESC LIMIT $limit OFFSET $offset";

        return $this->db->fetchAll($sql, $params);
    }

    public function countFiltered($filters = [])
    {
        $sql = "SELECT COUNT(*) as total FROM utilisateur WHERE 1=1";
        $params = [];

        if (!empty($filters['q'])) {
            $sql .= " AND (nomUtilisateur LIKE :s1 OR prenomUtilisateur LIKE :s2 OR emailUtilisateur LIKE :s3)";
            $params[':s1'] = '%' . $filters['q'] . '%';
            $params[':s2'] = '%' . $filters['q'] . '%';
            $params[':s3'] = '%' . $filters['q'] . '%';
        }
        if (!empty($filters['role'])) {
            $sql .= " AND role = :role";
            $params[':role'] = $filters['role'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND statut = :status";
            $params[':status'] = $filters['status'];
        }

        $res = $this->db->fetch($sql, $params);
        return isset($res['total']) ? (int)$res['total'] : 0;
    }

    public function getGlobalStats()
    {
        return [
            'total'      => $this->countStatut(null),
            'actifs'     => $this->countStatut('actif'),
            'en_attente' => $this->countStatut('en_attente'),
            'bloques'    => $this->countStatut('bloque')
        ];
    }

    private function countStatut($statut)
    {
        if ($statut) {
            $sql = "SELECT COUNT(*) as total FROM utilisateur WHERE statut = ?";
            $res = $this->db->fetch($sql, [$statut]);
        } else {
            $sql = "SELECT COUNT(*) as total FROM utilisateur";
            $res = $this->db->fetch($sql);
        }
        return isset($res['total']) ? (int)$res['total'] : 0;
    }

    // --- ÉCRITURE (CREATE / UPDATE / DELETE) ---

    public function create(array $data)
    {
        $sql = "INSERT INTO utilisateur (nomUtilisateur, prenomUtilisateur, emailUtilisateur, motDePasse, role, statut, dateCreation) 
                VALUES (:nom, :prenom, :email, :pwd, :role, :statut, NOW())";
        
        $params = [
            ':nom'    => $data['nom'],
            ':prenom' => $data['prenom'],
            ':email'  => $data['email'],
            ':pwd'    => password_hash($data['password'], PASSWORD_BCRYPT),
            ':role'   => $data['role'],
            ':statut' => $data['statut']
        ];

        return $this->executeRequest($sql, $params);
    }


public function update($id, $data)
{
    $sql = "UPDATE utilisateur SET role = ?, statut = ?";
    $params = [$data['role'], $data['statut']];
    
    if (!empty($data['password'])) {
        $sql .= ", motDePasse = ?";
        $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
    }
    
    $sql .= " WHERE idUtilisateur = ?";
    $params[] = $id;
    
    return $this->executeRequest($sql, $params);
}


    public function delete(int $id)
    {
        $sql = "DELETE FROM utilisateur WHERE idUtilisateur = :id";
        return $this->executeRequest($sql, [':id' => $id]);
    }

    private function executeRequest($sql, $params)
    {
        if (method_exists($this->db, 'execute')) {
            return $this->db->execute($sql, $params); 
        } elseif (method_exists($this->db, 'query')) {
            return $this->db->query($sql, $params); 
        }
        return $this->db->fetch($sql, $params);
    }
}