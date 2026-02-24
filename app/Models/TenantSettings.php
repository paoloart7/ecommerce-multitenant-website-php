<?php

require_once dirname(__DIR__, 2) . '/core/Database.php';

class TenantSettings
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Récupère les paramètres d'une boutique
     */
    public function getByBoutique(int $idBoutique): array
    {
        $sql = "SELECT pb.*, b.nomBoutique, b.slugBoutique, b.statut, b.description as descriptionBoutique,
                       ab.typeAbonnement, ab.dateFin as dateFinAbonnement
                FROM parametre_boutique pb
                JOIN boutique b ON pb.idBoutique = b.idBoutique
                LEFT JOIN abonnement ab ON b.idBoutique = ab.idBoutique AND ab.statut = 'actif'
                WHERE pb.idBoutique = :idBoutique";
        
        $settings = $this->db->fetch($sql, ['idBoutique' => $idBoutique]);
        
        // Valeurs par défaut si aucun paramètre trouvé
        if (!$settings) {
            $settings = [
                'idBoutique' => $idBoutique,
                'nomBoutique' => '',
                'slugBoutique' => '',
                'descriptionBoutique' => '',
                'statut' => 'en_attente',
                'devise' => 'HTG',
                'taxe' => 10.00,
                'logo' => null,
                'banniere' => null,
                'favicon' => null,
                'couleurPrimaire' => '#6366f1',
                'couleurSecondaire' => '#4f46e5',
                'emailContact' => null,
                'telephoneContact' => null,
                'adressePhysique' => null,
                'reseauxSociaux' => null,
                'politiqueRetour' => null,
                'conditionsVente' => null,
                'typeAbonnement' => 'gratuit',
                'dateFinAbonnement' => null
            ];
        }
        
        // Décoder JSON des réseaux sociaux
        if (!empty($settings['reseauxSociaux'])) {
            $settings['reseauxSociaux'] = json_decode($settings['reseauxSociaux'], true);
        } else {
            $settings['reseauxSociaux'] = [
                'facebook' => '',
                'instagram' => '',
                'twitter' => '',
                'whatsapp' => ''
            ];
        }
        
        return $settings;
    }

    /**
     * Met à jour les paramètres généraux
     */
    public function updateGeneral(int $idBoutique, array $data): bool
    {
        $sql = "UPDATE parametre_boutique SET
                    emailContact = :emailContact,
                    telephoneContact = :telephoneContact,
                    adressePhysique = :adressePhysique,
                    devise = :devise,
                    taxe = :taxe,
                    couleurPrimaire = :couleurPrimaire,
                    couleurSecondaire = :couleurSecondaire
                WHERE idBoutique = :idBoutique";
        
        return $this->db->execute($sql, [
            'emailContact' => $data['emailContact'] ?? null,
            'telephoneContact' => $data['telephoneContact'] ?? null,
            'adressePhysique' => $data['adressePhysique'] ?? null,
            'devise' => $data['devise'] ?? 'HTG',
            'taxe' => $data['taxe'] ?? 10.00,
            'couleurPrimaire' => $data['couleurPrimaire'] ?? '#6366f1',
            'couleurSecondaire' => $data['couleurSecondaire'] ?? '#4f46e5',
            'idBoutique' => $idBoutique
        ]);
    }

    /**
     * Met à jour la description de la boutique
     */
    public function updateDescription(int $idBoutique, string $description): bool
    {
        $sql = "UPDATE boutique SET description = :description WHERE idBoutique = :idBoutique";
        return $this->db->execute($sql, [
            'description' => $description,
            'idBoutique' => $idBoutique
        ]);
    }

    /**
     * Met à jour les réseaux sociaux
     */
    public function updateSocial(int $idBoutique, array $social): bool
    {
        $sql = "UPDATE parametre_boutique SET reseauxSociaux = :reseauxSociaux WHERE idBoutique = :idBoutique";
        return $this->db->execute($sql, [
            'reseauxSociaux' => json_encode($social),
            'idBoutique' => $idBoutique
        ]);
    }

    /**
     * Met à jour le logo
     */
    public function updateLogo(int $idBoutique, string $logoPath): bool
    {
        $sql = "UPDATE parametre_boutique SET logo = :logo WHERE idBoutique = :idBoutique";
        return $this->db->execute($sql, [
            'logo' => $logoPath,
            'idBoutique' => $idBoutique
        ]);
    }

    /**
     * Met à jour la bannière
     */
    public function updateBanner(int $idBoutique, string $bannerPath): bool
    {
        $sql = "UPDATE parametre_boutique SET banniere = :banniere WHERE idBoutique = :idBoutique";
        return $this->db->execute($sql, [
            'banniere' => $bannerPath,
            'idBoutique' => $idBoutique
        ]);
    }

    /**
     * Met à jour les politiques
     */
    public function updatePolicies(int $idBoutique, array $data): bool
    {
        $sql = "UPDATE parametre_boutique SET
                    politiqueRetour = :politiqueRetour,
                    conditionsVente = :conditionsVente
                WHERE idBoutique = :idBoutique";
        
        return $this->db->execute($sql, [
            'politiqueRetour' => $data['politiqueRetour'] ?? null,
            'conditionsVente' => $data['conditionsVente'] ?? null,
            'idBoutique' => $idBoutique
        ]);
    }
}