<?php

require_once dirname(__DIR__, 2) . '/core/Database.php';

class Panier
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Récupère le panier de l'utilisateur
     */
    public function getPanier($userId = null)
    {
        if ($userId) {
            return $this->getPanierBD($userId);
        } else {
            return $this->getPanierSession();
        }
    }

    /**
     * Ajouter un produit au panier
     */
    public function ajouter($produitId, $quantite = 1, $userId = null)
    {
        if ($userId) {
            return $this->ajouterBD($userId, $produitId, $quantite);
        } else {
            return $this->ajouterSession($produitId, $quantite);
        }
    }

    /**
     * Mettre à jour la quantité
     */
    public function mettreAJour($produitId, $quantite, $userId = null)
    {
        if ($userId) {
            return $this->mettreAJourBD($userId, $produitId, $quantite);
        } else {
            return $this->mettreAJourSession($produitId, $quantite);
        }
    }

    /**
     * Supprimer un produit
     */
    public function supprimer($produitId, $userId = null)
    {
        if ($userId) {
            return $this->supprimerBD($userId, $produitId);
        } else {
            return $this->supprimerSession($produitId);
        }
    }

    /**
     * Vider le panier
     */
    public function vider($userId = null)
    {
        if ($userId) {
            return $this->viderBD($userId);
        } else {
            return $this->viderSession();
        }
    }

    /**
     * Compter le nombre d'articles
     */
    public function compter($userId = null)
    {
        $panier = $this->getPanier($userId);
        return array_sum(array_column($panier['items'], 'quantite'));
    }

    // ===== MÉTHODES POUR LA SESSION =====

    private function getPanierSession()
    {
        if (!isset($_SESSION['panier']) || !is_array($_SESSION['panier'])) {
            $_SESSION['panier'] = [
                'items' => [],
                'total' => 0
            ];
        }
        return $_SESSION['panier'];
    }

    private function ajouterSession($produitId, $quantite)
    {
        $panier = $this->getPanierSession();
        
        if (isset($panier['items'][$produitId])) {
            $panier['items'][$produitId]['quantite'] += $quantite;
        } else {
            $produit = $this->getProduitInfos($produitId);
            if (!$produit) return false;
            
            $panier['items'][$produitId] = [
                'id' => $produitId,
                'nom' => $produit['nomProduit'],
                'prix' => $produit['prixPromo'] ?: $produit['prix'],
                'image' => $produit['image'],
                'boutique' => $produit['nomBoutique'],
                'quantite' => $quantite
            ];
        }
        
        $this->recalculerTotalSession($panier);
        $_SESSION['panier'] = $panier;
        return true;
    }

    private function mettreAJourSession($produitId, $quantite)
    {
        $panier = $this->getPanierSession();
        
        if ($quantite <= 0) {
            unset($panier['items'][$produitId]);
        } else {
            $panier['items'][$produitId]['quantite'] = $quantite;
        }
        
        $this->recalculerTotalSession($panier);
        $_SESSION['panier'] = $panier;
        return true;
    }

    private function supprimerSession($produitId)
    {
        $panier = $this->getPanierSession();
        unset($panier['items'][$produitId]);
        $this->recalculerTotalSession($panier);
        $_SESSION['panier'] = $panier;
        return true;
    }

    private function viderSession()
    {
        $_SESSION['panier'] = ['items' => [], 'total' => 0];
        return true;
    }

    private function recalculerTotalSession(&$panier)
    {
        $total = 0;
        foreach ($panier['items'] as $item) {
            $total += $item['prix'] * $item['quantite'];
        }
        $panier['total'] = $total;
    }

    // ===== MÉTHODES POUR LA BASE DE DONNÉES =====

    private function getPanierBD($userId)
    {
        // Récupérer le panier de l'utilisateur
        $panier = $this->db->fetch(
            "SELECT * FROM panier WHERE idUtilisateur = ?",
            [$userId]
        );
        
        if (!$panier) {
            return ['items' => [], 'total' => 0];
        }
        
        // Récupérer les articles du panier
        $items = $this->db->fetchAll(
            "SELECT pp.*, p.nomProduit, p.prix, p.prixPromo,
                    b.nomBoutique,
                    (SELECT urlImage FROM image_produit 
                     WHERE idProduit = p.idProduit AND estPrincipale = 1 LIMIT 1) as image
             FROM panier_produit pp
             JOIN produit p ON pp.idProduit = p.idProduit
             JOIN boutique b ON p.idBoutique = b.idBoutique
             WHERE pp.idPanier = ?",
            [$panier['idPanier']]
        );
        
        $result = ['items' => [], 'total' => 0];
        
        foreach ($items as $item) {
            $prix = $item['prixPromo'] ?: $item['prix'];
            $result['items'][$item['idProduit']] = [
                'id' => $item['idProduit'],
                'nom' => $item['nomProduit'],
                'prix' => $prix,
                'image' => $item['image'],
                'boutique' => $item['nomBoutique'],
                'quantite' => $item['quantite']
            ];
            $result['total'] += $prix * $item['quantite'];
        }
        
        return $result;
    }

    private function ajouterBD($userId, $produitId, $quantite)
    {
        // Vérifier si l'utilisateur a déjà un panier
        $panier = $this->db->fetch(
            "SELECT idPanier FROM panier WHERE idUtilisateur = ?",
            [$userId]
        );
        
        if (!$panier) {
            // Créer un nouveau panier
            $this->db->execute(
                "INSERT INTO panier (idUtilisateur, idBoutique) VALUES (?, ?)",
                [$userId, $this->getBoutiqueId($produitId)]
            );
            $panierId = $this->db->lastInsertId();
        } else {
            $panierId = $panier['idPanier'];
        }
        
        // Vérifier si le produit est déjà dans le panier
        $existant = $this->db->fetch(
            "SELECT * FROM panier_produit WHERE idPanier = ? AND idProduit = ?",
            [$panierId, $produitId]
        );
        
        if ($existant) {
            // Mettre à jour la quantité
            return $this->db->execute(
                "UPDATE panier_produit SET quantite = quantite + ? WHERE idPanier = ? AND idProduit = ?",
                [$quantite, $panierId, $produitId]
            );
        } else {
            // Ajouter le produit
            $produit = $this->getProduitInfos($produitId);
            return $this->db->execute(
                "INSERT INTO panier_produit (idPanier, idProduit, quantite, prixAuMoment) VALUES (?, ?, ?, ?)",
                [$panierId, $produitId, $quantite, $produit['prixPromo'] ?: $produit['prix']]
            );
        }
    }

    private function mettreAJourBD($userId, $produitId, $quantite)
    {
        $panier = $this->db->fetch(
            "SELECT idPanier FROM panier WHERE idUtilisateur = ?",
            [$userId]
        );
        
        if (!$panier) return false;
        
        if ($quantite <= 0) {
            return $this->supprimerBD($userId, $produitId);
        }
        
        return $this->db->execute(
            "UPDATE panier_produit SET quantite = ? WHERE idPanier = ? AND idProduit = ?",
            [$quantite, $panier['idPanier'], $produitId]
        );
    }

    private function supprimerBD($userId, $produitId)
    {
        $panier = $this->db->fetch(
            "SELECT idPanier FROM panier WHERE idUtilisateur = ?",
            [$userId]
        );
        
        if (!$panier) return false;
        
        return $this->db->execute(
            "DELETE FROM panier_produit WHERE idPanier = ? AND idProduit = ?",
            [$panier['idPanier'], $produitId]
        );
    }

    private function viderBD($userId)
    {
        $panier = $this->db->fetch(
            "SELECT idPanier FROM panier WHERE idUtilisateur = ?",
            [$userId]
        );
        
        if (!$panier) return false;
        
        return $this->db->execute(
            "DELETE FROM panier_produit WHERE idPanier = ?",
            [$panier['idPanier']]
        );
    }

    private function getBoutiqueId($produitId)
    {
        $produit = $this->db->fetch(
            "SELECT idBoutique FROM produit WHERE idProduit = ?",
            [$produitId]
        );
        return $produit['idBoutique'] ?? null;
    }

    private function getProduitInfos($produitId)
    {
        $sql = "SELECT p.*, 
                       b.nomBoutique,
                       (SELECT urlImage FROM image_produit 
                        WHERE idProduit = p.idProduit AND estPrincipale = 1 LIMIT 1) as image
                FROM produit p
                JOIN boutique b ON b.idBoutique = p.idBoutique
                WHERE p.idProduit = ?";
        
        return $this->db->fetch($sql, [$produitId]);
    }
}