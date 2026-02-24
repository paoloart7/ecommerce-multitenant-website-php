<?php

require_once dirname(__DIR__, 2) . '/core/Database.php';

class Client
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Récupère un client par son ID
     */
    public function getById($id)
    {
        $sql = "SELECT idUtilisateur, nomUtilisateur as nom, prenomUtilisateur as prenom,
                       emailUtilisateur as email, telephone, avatar, dateCreation
                FROM utilisateur 
                WHERE idUtilisateur = ? AND role = 'client'";
        
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Met à jour les informations du client
     */
    public function update($id, $data)
    {
        $sql = "UPDATE utilisateur 
                SET nomUtilisateur = ?, prenomUtilisateur = ?, 
                    emailUtilisateur = ?, telephone = ?
                WHERE idUtilisateur = ? AND role = 'client'";
        
        return $this->db->execute($sql, [
            $data['nom'],
            $data['prenom'],
            $data['email'],
            $data['telephone'],
            $id
        ]);
    }

    /**
     * Met à jour le mot de passe
     */
    public function updatePassword($id, $ancien, $nouveau)
    {
        $user = $this->db->fetch(
            "SELECT motDePasse FROM utilisateur WHERE idUtilisateur = ?",
            [$id]
        );
        
        if (!password_verify($ancien, $user['motDePasse'])) {
            return false;
        }
        
        $hash = password_hash($nouveau, PASSWORD_DEFAULT);
        return $this->db->execute(
            "UPDATE utilisateur SET motDePasse = ? WHERE idUtilisateur = ?",
            [$hash, $id]
        );
    }

    /**
     * Met à jour l'avatar
     */
    public function updateAvatar($id, $avatarPath)
    {
        return $this->db->execute(
            "UPDATE utilisateur SET avatar = ? WHERE idUtilisateur = ?",
            [$avatarPath, $id]
        );
    }

    /**
     * Récupère les adresses du client
     */
    public function getAdresses($userId)
    {
        $sql = "SELECT * FROM adresse 
                WHERE idUtilisateur = ? 
                ORDER BY estDefaut DESC, dateCreation DESC";
        
        return $this->db->fetchAll($sql, [$userId]);
    }

    /**
     * Sauvegarde une adresse (ajout ou modification)
     */
    public function saveAdresse($userId, $data)
    {
        if (!empty($data['estDefaut'])) {
            $this->db->execute(
                "UPDATE adresse SET estDefaut = 0 WHERE idUtilisateur = ?",
                [$userId]
            );
        }
        
        if (!empty($data['idAdresse'])) {
            $sql = "UPDATE adresse SET
                    nomDestinataire = ?, telephone = ?, rue = ?,
                    complement = ?, quartier = ?, ville = ?,
                    codePostal = ?, estDefaut = ?
                    WHERE idAdresse = ? AND idUtilisateur = ?";
            
            return $this->db->execute($sql, [
                $data['nomDestinataire'],
                $data['telephone'],
                $data['rue'],
                $data['complement'],
                $data['quartier'],
                $data['ville'],
                $data['codePostal'],
                $data['estDefaut'],
                $data['idAdresse'],
                $userId
            ]);
        } else {
            $sql = "INSERT INTO adresse (
                        idUtilisateur, nomDestinataire, telephone,
                        rue, complement, quartier, ville,
                        codePostal, estDefaut, pays
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Haiti')";
            
            return $this->db->execute($sql, [
                $userId,
                $data['nomDestinataire'],
                $data['telephone'],
                $data['rue'],
                $data['complement'],
                $data['quartier'],
                $data['ville'],
                $data['codePostal'],
                $data['estDefaut']
            ]);
        }
    }

    /**
     * Supprime une adresse
     */
    public function deleteAdresse($idAdresse)
    {
        return $this->db->execute(
            "DELETE FROM adresse WHERE idAdresse = ?",
            [$idAdresse]
        );
    }

    /**
     * Récupère les statistiques du client
     */
    public function getStats($userId)
    {
        $sql = "SELECT 
                    COUNT(*) as total_commandes,
                    COUNT(CASE WHEN statut = 'en_attente' THEN 1 END) as commandes_en_cours,
                    COUNT(CASE WHEN statut = 'livree' THEN 1 END) as commandes_livrees,
                    COALESCE(SUM(total), 0) as total_depense,
                    COALESCE(AVG(total), 0) as panier_moyen
                FROM commande 
                WHERE idClient = ?";
        
        return $this->db->fetch($sql, [$userId]) ?: [
            'total_commandes' => 0,
            'commandes_en_cours' => 0,
            'commandes_livrees' => 0,
            'total_depense' => 0,
            'panier_moyen' => 0
        ];
    }

    /**
     * Vérifie si l'email existe déjà
     */
    public function emailExists($email, $ignoreId = null)
    {
        $sql = "SELECT COUNT(*) as total FROM utilisateur WHERE emailUtilisateur = ?";
        $params = [$email];
        
        if ($ignoreId) {
            $sql .= " AND idUtilisateur != ?";
            $params[] = $ignoreId;
        }
        
        $result = $this->db->fetch($sql, $params);
        return ($result['total'] ?? 0) > 0;
    }
}