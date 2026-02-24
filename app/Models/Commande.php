<?php

require_once dirname(__DIR__, 2) . '/core/Database.php';

class Commande
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getEnAttenteValidation($boutiqueId)
    {
        $sql = "SELECT c.*, 
                       u.nomUtilisateur, u.prenomUtilisateur,
                       p.modePaiement, p.statutPaiement,
                       COUNT(cp.idProduit) as nb_articles
                FROM commande c
                JOIN utilisateur u ON c.idClient = u.idUtilisateur
                LEFT JOIN paiement p ON c.idCommande = p.idCommande
                LEFT JOIN commande_produit cp ON c.idCommande = cp.idCommande
                WHERE c.idBoutique = ? AND c.statut = 'payee'
                GROUP BY c.idCommande
                ORDER BY c.dateCommande DESC";
        
        return $this->db->fetchAll($sql, [$boutiqueId]);
    }

    public function valider($idCommande)
    {
        $sql = "UPDATE commande SET statut = 'confirmee', dateConfirmation = NOW() WHERE idCommande = ?";
        return $this->db->execute($sql, [$idCommande]);
    }
}