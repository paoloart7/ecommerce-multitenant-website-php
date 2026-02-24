-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mar. 24 fév. 2026 à 18:16
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `ecommerce_multitenant`
--

DELIMITER $$
--
-- Procédures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_creer_commande_depuis_panier` (IN `p_idUtilisateur` INT UNSIGNED, IN `p_idBoutique` INT UNSIGNED, IN `p_idAdresseLivraison` INT UNSIGNED, IN `p_fraisLivraison` DECIMAL(10,2), IN `p_methodeLivraison` VARCHAR(20), OUT `p_idCommande` INT UNSIGNED, OUT `p_message` VARCHAR(500))   BEGIN
    DECLARE v_idPanier INT UNSIGNED;
    DECLARE v_nb_produits INT;
    DECLARE v_stock_insuffisant BOOLEAN DEFAULT FALSE;
    DECLARE v_devise VARCHAR(3);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_idCommande = NULL;
        SET p_message = 'Erreur lors de la création de la commande';
    END;

    START TRANSACTION;

    SELECT idPanier INTO v_idPanier
    FROM panier WHERE idUtilisateur = p_idUtilisateur AND idBoutique = p_idBoutique;

    IF v_idPanier IS NULL THEN
        SET p_idCommande = NULL;
        SET p_message = 'Aucun panier trouvé';
        ROLLBACK;
    ELSE
        SELECT COUNT(*) INTO v_nb_produits FROM panier_produit WHERE idPanier = v_idPanier;

        IF v_nb_produits = 0 THEN
            SET p_idCommande = NULL;
            SET p_message = 'Le panier est vide';
            ROLLBACK;
        ELSE
            SELECT COUNT(*) > 0 INTO v_stock_insuffisant
            FROM panier_produit pp
            JOIN produit p ON pp.idProduit = p.idProduit
            WHERE pp.idPanier = v_idPanier AND pp.quantite > p.stock;

            IF v_stock_insuffisant THEN
                SET p_idCommande = NULL;
                SET p_message = 'Stock insuffisant pour un ou plusieurs produits';
                ROLLBACK;
            ELSE
                SELECT devise INTO v_devise FROM parametre_boutique WHERE idBoutique = p_idBoutique;
                SET v_devise = COALESCE(v_devise, 'HTG');

                INSERT INTO commande (
                    idBoutique, idClient, numeroCommande, sousTotal,
                    fraisLivraison, total, devise, statut
                ) VALUES (
                    p_idBoutique, p_idUtilisateur, '', 0,
                    COALESCE(p_fraisLivraison, 0), 0, v_devise, 'en_attente'
                );

                SET p_idCommande = LAST_INSERT_ID();

                INSERT INTO commande_produit (idCommande, idProduit, nomProduitSnapshot, quantite, prixUnitaire)
                SELECT
                    p_idCommande, pp.idProduit, p.nomProduit, pp.quantite,
                    COALESCE(
                        CASE
                            WHEN p.prixPromo IS NOT NULL
                            AND CURRENT_DATE BETWEEN COALESCE(p.dateDebutPromo, CURRENT_DATE) AND COALESCE(p.dateFinPromo, CURRENT_DATE)
                            THEN p.prixPromo
                            ELSE p.prix
                        END, p.prix
                    )
                FROM panier_produit pp
                JOIN produit p ON pp.idProduit = p.idProduit
                WHERE pp.idPanier = v_idPanier;

                INSERT INTO livraison (idCommande, idBoutique, idAdresseLivraison, methodeLivraison)
                VALUES (p_idCommande, p_idBoutique, p_idAdresseLivraison, COALESCE(p_methodeLivraison, 'standard'));

                DELETE FROM panier_produit WHERE idPanier = v_idPanier;

                SET p_message = 'Commande créée avec succès';
                COMMIT;
            END IF;
        END IF;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_nettoyer_sessions` ()   BEGIN
    DECLARE v_count INT;

    DELETE FROM session
    WHERE dateExpiration < CURRENT_TIMESTAMP OR estValide = FALSE;

    SET v_count = ROW_COUNT();

    INSERT INTO audit_log (typeAction, action, details)
    VALUES ('AUTRE', 'Nettoyage sessions', JSON_OBJECT('sessionsSuprimees', v_count));

    SELECT v_count AS sessions_supprimees;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_rapport_ventes_boutique` (IN `p_idBoutique` INT UNSIGNED, IN `p_dateDebut` DATE, IN `p_dateFin` DATE)   BEGIN
    SELECT
        'RÉSUMÉ GLOBAL' AS section,
        COUNT(DISTINCT c.idCommande) AS nombre_commandes,
        COUNT(DISTINCT c.idClient) AS clients_uniques,
        COALESCE(SUM(c.total), 0) AS chiffre_affaires,
        COALESCE(AVG(c.total), 0) AS panier_moyen,
        COALESCE(SUM(c.montantTaxe), 0) AS taxes_collectees
    FROM commande c
    WHERE c.idBoutique = p_idBoutique
    AND DATE(c.dateCommande) BETWEEN p_dateDebut AND p_dateFin
    AND c.statut NOT IN ('annulee', 'remboursee');

    SELECT
        DATE(c.dateCommande) AS date_vente,
        COUNT(*) AS nombre_commandes,
        SUM(c.total) AS chiffre_affaires
    FROM commande c
    WHERE c.idBoutique = p_idBoutique
    AND DATE(c.dateCommande) BETWEEN p_dateDebut AND p_dateFin
    AND c.statut NOT IN ('annulee', 'remboursee')
    GROUP BY DATE(c.dateCommande)
    ORDER BY date_vente;

    SELECT
        p.nomProduit,
        SUM(cp.quantite) AS quantite_vendue,
        SUM(cp.totalLigne) AS chiffre_affaires
    FROM commande_produit cp
    JOIN commande c ON cp.idCommande = c.idCommande
    JOIN produit p ON cp.idProduit = p.idProduit
    WHERE c.idBoutique = p_idBoutique
    AND DATE(c.dateCommande) BETWEEN p_dateDebut AND p_dateFin
    AND c.statut NOT IN ('annulee', 'remboursee')
    GROUP BY p.idProduit, p.nomProduit
    ORDER BY quantite_vendue DESC
    LIMIT 10;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_recalculer_total_commande` (IN `p_idCommande` INT UNSIGNED)   BEGIN
    DECLARE v_sous_total DECIMAL(12,2);
    DECLARE v_taux_taxe DECIMAL(5,2);
    DECLARE v_taxe DECIMAL(10,2);
    DECLARE v_frais_livraison DECIMAL(10,2);
    DECLARE v_remise DECIMAL(10,2);
    DECLARE v_total DECIMAL(12,2);

    SELECT COALESCE(SUM(totalLigne), 0) INTO v_sous_total
    FROM commande_produit WHERE idCommande = p_idCommande;

    SELECT tauxTaxe, fraisLivraison, remise
    INTO v_taux_taxe, v_frais_livraison, v_remise
    FROM commande WHERE idCommande = p_idCommande;

    SET v_taxe = ROUND(v_sous_total * (v_taux_taxe / 100), 2);
    SET v_total = v_sous_total + v_taxe + COALESCE(v_frais_livraison, 0) - COALESCE(v_remise, 0);

    UPDATE commande
    SET sousTotal = v_sous_total, montantTaxe = v_taxe, total = v_total
    WHERE idCommande = p_idCommande;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_verifier_paiement_complet` (IN `p_idCommande` INT UNSIGNED)   BEGIN
    DECLARE v_total_commande DECIMAL(12,2);
    DECLARE v_total_paye DECIMAL(12,2);

    SELECT total INTO v_total_commande FROM commande WHERE idCommande = p_idCommande;

    SELECT COALESCE(SUM(montant), 0) INTO v_total_paye
    FROM paiement WHERE idCommande = p_idCommande AND statutPaiement = 'valide';

    IF v_total_paye >= v_total_commande THEN
        UPDATE commande
        SET statut = 'payee', dateConfirmation = CURRENT_TIMESTAMP
        WHERE idCommande = p_idCommande AND statut IN ('en_attente', 'confirmee');
    END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `abonnement`
--

CREATE TABLE `abonnement` (
  `idAbonnement` int(10) UNSIGNED NOT NULL,
  `idBoutique` int(10) UNSIGNED NOT NULL,
  `typeAbonnement` enum('gratuit','basique','premium','entreprise') NOT NULL DEFAULT 'gratuit',
  `prixMensuel` decimal(10,2) NOT NULL DEFAULT 0.00,
  `limitesProduits` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL = illimité',
  `limitesCommandes` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL = illimité',
  `fonctionnalites` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Liste des fonctionnalités activées' CHECK (json_valid(`fonctionnalites`)),
  `dateDebut` date NOT NULL,
  `dateFin` date DEFAULT NULL COMMENT 'NULL si abonnement continu',
  `statut` enum('actif','expire','suspendu','annule') NOT NULL DEFAULT 'actif',
  `renouvellementAuto` tinyint(1) NOT NULL DEFAULT 1,
  `dateCreation` datetime NOT NULL DEFAULT current_timestamp()
) ;

--
-- Déchargement des données de la table `abonnement`
--

INSERT INTO `abonnement` (`idAbonnement`, `idBoutique`, `typeAbonnement`, `prixMensuel`, `limitesProduits`, `limitesCommandes`, `fonctionnalites`, `dateDebut`, `dateFin`, `statut`, `renouvellementAuto`, `dateCreation`) VALUES
(1, 1, 'gratuit', 0.00, NULL, NULL, NULL, '2026-02-12', NULL, 'actif', 1, '2026-02-12 16:08:32'),
(2, 2, 'gratuit', 0.00, NULL, NULL, NULL, '2026-02-12', NULL, 'actif', 1, '2026-02-12 16:08:32'),
(3, 3, 'gratuit', 0.00, NULL, NULL, NULL, '2026-02-12', NULL, 'actif', 1, '2026-02-12 16:08:32'),
(4, 5, 'gratuit', 0.00, NULL, NULL, NULL, '2026-02-16', NULL, 'actif', 1, '2026-02-16 09:20:24'),
(5, 6, 'gratuit', 0.00, NULL, NULL, NULL, '2026-02-16', NULL, 'actif', 1, '2026-02-16 09:22:02'),
(6, 7, 'gratuit', 0.00, NULL, NULL, NULL, '2026-02-16', NULL, 'actif', 1, '2026-02-16 09:23:49'),
(7, 8, 'gratuit', 0.00, NULL, NULL, NULL, '2026-02-16', NULL, 'actif', 1, '2026-02-16 09:25:14'),
(8, 9, 'gratuit', 0.00, NULL, NULL, NULL, '2026-02-16', NULL, 'actif', 1, '2026-02-16 09:27:33'),
(9, 10, 'gratuit', 0.00, NULL, NULL, NULL, '2026-02-16', NULL, 'actif', 1, '2026-02-16 09:28:35'),
(10, 11, 'gratuit', 0.00, NULL, NULL, NULL, '2026-02-16', NULL, 'actif', 1, '2026-02-16 09:29:57'),
(11, 4, 'gratuit', 0.00, NULL, NULL, NULL, '2026-02-16', NULL, 'actif', 1, '2026-02-16 11:24:45'),
(14, 15, 'gratuit', 0.00, NULL, NULL, NULL, '2026-02-19', NULL, 'actif', 1, '2026-02-19 15:47:17'),
(15, 16, 'gratuit', 0.00, NULL, NULL, NULL, '2026-02-19', NULL, 'actif', 1, '2026-02-19 16:05:50'),
(16, 17, 'gratuit', 0.00, NULL, NULL, NULL, '2026-02-19', NULL, 'actif', 1, '2026-02-19 16:21:31'),
(17, 18, 'gratuit', 0.00, NULL, NULL, NULL, '2026-02-20', NULL, 'actif', 1, '2026-02-20 13:06:06');

-- --------------------------------------------------------

--
-- Structure de la table `adresse`
--

CREATE TABLE `adresse` (
  `idAdresse` int(10) UNSIGNED NOT NULL,
  `idUtilisateur` int(10) UNSIGNED NOT NULL,
  `idBoutique` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL = adresse personnelle globale',
  `typeAdresse` enum('personnelle','livraison','facturation') NOT NULL DEFAULT 'personnelle',
  `nomDestinataire` varchar(200) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `rue` varchar(255) NOT NULL,
  `complement` varchar(255) DEFAULT NULL,
  `quartier` varchar(100) DEFAULT NULL,
  `ville` varchar(100) NOT NULL,
  `departement` varchar(100) DEFAULT NULL,
  `codePostal` varchar(20) DEFAULT NULL,
  `pays` varchar(100) NOT NULL DEFAULT 'Haiti',
  `instructions` text DEFAULT NULL COMMENT 'Instructions de livraison',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `estDefaut` tinyint(1) NOT NULL DEFAULT 0,
  `dateCreation` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Adresses des utilisateurs';

--
-- Déchargement des données de la table `adresse`
--

INSERT INTO `adresse` (`idAdresse`, `idUtilisateur`, `idBoutique`, `typeAdresse`, `nomDestinataire`, `telephone`, `rue`, `complement`, `quartier`, `ville`, `departement`, `codePostal`, `pays`, `instructions`, `latitude`, `longitude`, `estDefaut`, `dateCreation`) VALUES
(1, 6, NULL, 'personnelle', NULL, NULL, '123 Rue Principale', NULL, NULL, 'Port-au-Prince', NULL, NULL, 'Haiti', NULL, NULL, NULL, 1, '2026-02-12 16:08:32'),
(2, 7, NULL, 'personnelle', NULL, NULL, '456 Avenue des Fleurs', NULL, NULL, 'Pétion-Ville', NULL, NULL, 'Haiti', NULL, NULL, NULL, 1, '2026-02-12 16:08:32'),
(3, 8, NULL, 'personnelle', NULL, NULL, '789 Boulevard Central', NULL, NULL, 'Cap-Haïtien', NULL, NULL, 'Haiti', NULL, NULL, NULL, 1, '2026-02-12 16:08:32'),
(4, 19, NULL, 'personnelle', 'Paolo Art', '+509 3742 78 05', '#108 Avenue Panamericaine', 'Petion-Ville, Haiti', 'Bouk Chanpay', 'Port-au-Prince', NULL, 'HT-3410', 'Haiti', NULL, NULL, NULL, 1, '2026-02-22 17:27:02');

-- --------------------------------------------------------

--
-- Structure de la table `audit_log`
--

CREATE TABLE `audit_log` (
  `idLog` bigint(20) UNSIGNED NOT NULL,
  `idBoutique` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL si action admin plateforme',
  `idUtilisateur` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL si action système',
  `typeAction` enum('CREATE','READ','UPDATE','DELETE','LOGIN','LOGOUT','EXPORT','IMPORT','AUTRE') NOT NULL,
  `action` varchar(100) NOT NULL,
  `tableConcernee` varchar(100) DEFAULT NULL,
  `idEnregistrement` int(10) UNSIGNED DEFAULT NULL,
  `anciennesValeurs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`anciennesValeurs`)),
  `nouvellesValeurs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`nouvellesValeurs`)),
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `ipAddress` varchar(45) DEFAULT NULL,
  `userAgent` text DEFAULT NULL,
  `dateAction` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Journal d''audit des actions';

--
-- Déchargement des données de la table `audit_log`
--

INSERT INTO `audit_log` (`idLog`, `idBoutique`, `idUtilisateur`, `typeAction`, `action`, `tableConcernee`, `idEnregistrement`, `anciennesValeurs`, `nouvellesValeurs`, `details`, `ipAddress`, `userAgent`, `dateAction`) VALUES
(1, 1, 3, 'CREATE', 'Création boutique', 'boutique', 1, NULL, '{\"nomBoutique\": \"Mode Haiti\", \"slugBoutique\": \"mode-haiti\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-12 16:08:32'),
(2, 2, 4, 'CREATE', 'Création boutique', 'boutique', 2, NULL, '{\"nomBoutique\": \"Tech Store Haiti\", \"slugBoutique\": \"tech-store-haiti\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-12 16:08:32'),
(3, 3, 5, 'CREATE', 'Création boutique', 'boutique', 3, NULL, '{\"nomBoutique\": \"Épicerie Fine\", \"slugBoutique\": \"epicerie-fine\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-12 16:08:32'),
(4, NULL, NULL, 'AUTRE', 'Nettoyage sessions', NULL, NULL, NULL, NULL, '{\"sessionsSuprimees\": \"0\"}', NULL, NULL, '2026-02-12 16:08:32'),
(5, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"ADMIN\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"ADMIN\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-15 22:40:19'),
(6, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"ADMIN\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"ADMIN\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-15 22:40:19'),
(7, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"ADMIN\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"ADMIN\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-16 07:47:14'),
(8, NULL, 2, 'UPDATE', 'Modification utilisateur', 'utilisateur', 2, '{\"nom\": \"Larack\", \"prenom\": \"WolfJunior\", \"email\": \"larackwolfjr@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Larack\", \"prenom\": \"Wolf Junior\", \"email\": \"larackwolfjr@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-16 07:47:52'),
(9, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"ADMIN\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-16 09:05:23'),
(10, 5, 2, 'CREATE', 'Création boutique', 'boutique', 5, NULL, '{\"nomBoutique\": \"Duo Multi-services\", \"slugBoutique\": \"duo-multi-services\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-16 09:20:24'),
(11, 6, 12, 'CREATE', 'Création boutique', 'boutique', 6, NULL, '{\"nomBoutique\": \"Rikado\", \"slugBoutique\": \"rikado\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-16 09:22:02'),
(12, 7, 13, 'CREATE', 'Création boutique', 'boutique', 7, NULL, '{\"nomBoutique\": \"BozQuincaille\", \"slugBoutique\": \"bzquincaille\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-16 09:23:49'),
(13, 8, 14, 'CREATE', 'Création boutique', 'boutique', 8, NULL, '{\"nomBoutique\": \"Cazi Boiserie\", \"slugBoutique\": \"cdzboisiere\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-16 09:25:14'),
(14, 9, 15, 'CREATE', 'Création boutique', 'boutique', 9, NULL, '{\"nomBoutique\": \"Carl Sounds Selections\", \"slugBoutique\": \"carlsoundselection\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-16 09:27:33'),
(15, 10, 16, 'CREATE', 'Création boutique', 'boutique', 10, NULL, '{\"nomBoutique\": \"Maamou Perles\", \"slugBoutique\": \"maamouperles\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-16 09:28:35'),
(16, 11, 17, 'CREATE', 'Création boutique', 'boutique', 11, NULL, '{\"nomBoutique\": \"Old Payas Store\", \"slugBoutique\": \"oldpayastore\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-16 09:29:57'),
(17, 4, 18, 'CREATE', 'Création boutique', 'boutique', 4, NULL, '{\"nomBoutique\": \"Henry Deschamps\", \"slugBoutique\": \"henry-deschamps\", \"statut\": \"en_attente\"}', NULL, NULL, NULL, '2026-02-16 11:24:45'),
(18, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-17 05:35:53'),
(19, NULL, NULL, 'UPDATE', 'Modification utilisateur', 'utilisateur', 10, '{\"nom\": \"Doe\", \"prenom\": \"John\", \"email\": \"johndoe@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Doe\", \"prenom\": \"John\", \"email\": \"johndoe@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-17 05:50:05'),
(20, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-17 06:20:08'),
(21, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-17 08:01:55'),
(22, NULL, 18, 'UPDATE', 'Modification utilisateur', 'utilisateur', 18, '{\"nom\": \"Celestin\", \"prenom\": \"Junior\", \"email\": \"juniorcelestin@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Celestin\", \"prenom\": \"Junior\", \"email\": \"juniorcelestin@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-17 08:02:25'),
(23, NULL, 17, 'UPDATE', 'Modification utilisateur', 'utilisateur', 17, '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-17 09:16:39'),
(24, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-17 09:55:29'),
(25, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-17 09:56:11'),
(26, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-17 09:56:23'),
(27, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-17 09:58:30'),
(28, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-17 17:44:39'),
(29, NULL, 19, 'UPDATE', 'Modification utilisateur', 'utilisateur', 19, '{\"nom\": \"Jean\", \"prenom\": \"Chantale\", \"email\": \"chantalejean@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-17 18:04:24'),
(30, NULL, 20, 'CREATE', 'Création boutique', 'boutique', 13, NULL, '{\"nomBoutique\": \"Tica Akasan\", \"slugBoutique\": \"tica-akasan\", \"statut\": \"en_attente\"}', NULL, NULL, NULL, '2026-02-18 07:01:47'),
(31, NULL, 17, 'UPDATE', 'Modification utilisateur', 'utilisateur', 17, '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-18 16:38:44'),
(32, NULL, 17, 'UPDATE', 'Modification utilisateur', 'utilisateur', 17, '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-18 16:38:44'),
(33, NULL, 17, 'UPDATE', 'Modification utilisateur', 'utilisateur', 17, '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-18 17:05:35'),
(34, NULL, 17, 'UPDATE', 'Modification utilisateur', 'utilisateur', 17, '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-18 17:10:11'),
(35, NULL, 17, 'UPDATE', 'Modification utilisateur', 'utilisateur', 17, '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-18 17:21:25'),
(36, NULL, 17, 'UPDATE', 'Modification utilisateur', 'utilisateur', 17, '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-18 17:38:05'),
(37, NULL, 17, 'UPDATE', 'Modification utilisateur', 'utilisateur', 17, '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-18 17:38:12'),
(38, NULL, 16, 'UPDATE', 'Modification utilisateur', 'utilisateur', 16, '{\"nom\": \"Paul\", \"prenom\": \"Maheva\", \"email\": \"mahevapaul@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Paul\", \"prenom\": \"Maheva\", \"email\": \"mahevapaul@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-18 17:38:55'),
(39, NULL, 16, 'UPDATE', 'Modification utilisateur', 'utilisateur', 16, '{\"nom\": \"Paul\", \"prenom\": \"Maheva\", \"email\": \"mahevapaul@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Paul\", \"prenom\": \"Maheva\", \"email\": \"mahevapaul@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-18 17:38:55'),
(40, NULL, 16, 'UPDATE', 'Modification utilisateur', 'utilisateur', 16, '{\"nom\": \"Paul\", \"prenom\": \"Maheva\", \"email\": \"mahevapaul@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Paul\", \"prenom\": \"Maheva\", \"email\": \"mahevapaul@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-18 17:48:38'),
(41, NULL, 17, 'UPDATE', 'Modification utilisateur', 'utilisateur', 17, '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-18 17:49:02'),
(42, NULL, 17, 'UPDATE', 'Modification utilisateur', 'utilisateur', 17, '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-18 17:59:33'),
(43, NULL, 17, 'UPDATE', 'Modification utilisateur', 'utilisateur', 17, '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-18 19:21:14'),
(44, NULL, 17, 'UPDATE', 'Modification utilisateur', 'utilisateur', 17, '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-18 19:21:19'),
(45, NULL, 17, 'UPDATE', 'Modification utilisateur', 'utilisateur', 17, '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-18 19:35:00'),
(46, NULL, 17, 'UPDATE', 'Modification utilisateur', 'utilisateur', 17, '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-18 19:36:11'),
(47, NULL, 17, 'UPDATE', 'Modification utilisateur', 'utilisateur', 17, '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-18 19:36:51'),
(48, NULL, 17, 'UPDATE', 'Modification utilisateur', 'utilisateur', 17, '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-18 19:36:58'),
(49, NULL, 17, 'UPDATE', 'Modification utilisateur', 'utilisateur', 17, '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-18 19:39:53'),
(50, NULL, 17, 'UPDATE', 'Modification utilisateur', 'utilisateur', 17, '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-18 20:00:04'),
(51, NULL, 17, 'UPDATE', 'Modification utilisateur', 'utilisateur', 17, '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-18 20:00:35'),
(52, NULL, 17, 'UPDATE', 'Modification utilisateur', 'utilisateur', 17, '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-18 20:02:49'),
(53, NULL, 17, 'UPDATE', 'Modification utilisateur', 'utilisateur', 17, '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-18 20:02:56'),
(54, NULL, 16, 'UPDATE', 'Modification utilisateur', 'utilisateur', 16, '{\"nom\": \"Paul\", \"prenom\": \"Maheva\", \"email\": \"mahevapaul@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Paul\", \"prenom\": \"Maheva\", \"email\": \"mahevapaul@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-18 20:03:18'),
(55, NULL, 16, 'UPDATE', 'Modification utilisateur', 'utilisateur', 16, '{\"nom\": \"Paul\", \"prenom\": \"Maheva\", \"email\": \"mahevapaul@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Paul\", \"prenom\": \"Maheva\", \"email\": \"mahevapaul@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-18 20:04:51'),
(56, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 06:06:53'),
(57, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 06:08:32'),
(58, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 06:09:36'),
(59, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 06:11:19'),
(60, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 06:11:58'),
(61, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 06:12:05'),
(62, NULL, 16, 'UPDATE', 'Modification utilisateur', 'utilisateur', 16, '{\"nom\": \"Paul\", \"prenom\": \"Maheva\", \"email\": \"mahevapaul@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Paul\", \"prenom\": \"Maheva\", \"email\": \"mahevapaul@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 06:27:06'),
(63, NULL, 19, 'UPDATE', 'Modification utilisateur', 'utilisateur', 19, '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 07:43:26'),
(64, NULL, 19, 'UPDATE', 'Modification utilisateur', 'utilisateur', 19, '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 07:57:30'),
(65, NULL, 16, 'UPDATE', 'Modification utilisateur', 'utilisateur', 16, '{\"nom\": \"Paul\", \"prenom\": \"Maheva\", \"email\": \"mahevapaul@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Paul\", \"prenom\": \"Maheva\", \"email\": \"mahevapaul@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 07:57:50'),
(66, NULL, 16, 'UPDATE', 'Modification utilisateur', 'utilisateur', 16, '{\"nom\": \"Paul\", \"prenom\": \"Maheva\", \"email\": \"mahevapaul@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Paul\", \"prenom\": \"Maheva\", \"email\": \"mahevapaul@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 08:07:34'),
(67, NULL, 16, 'UPDATE', 'Modification utilisateur', 'utilisateur', 16, '{\"nom\": \"Paul\", \"prenom\": \"Maheva\", \"email\": \"mahevapaul@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Paul\", \"prenom\": \"Maheva\", \"email\": \"mahevapaul@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 08:13:59'),
(68, NULL, 16, 'UPDATE', 'Modification utilisateur', 'utilisateur', 16, '{\"nom\": \"Paul\", \"prenom\": \"Maheva\", \"email\": \"mahevapaul@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Paul\", \"prenom\": \"Maheva\", \"email\": \"mahevapaul@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 13:45:58'),
(69, NULL, 16, 'UPDATE', 'Modification utilisateur', 'utilisateur', 16, '{\"nom\": \"Paul\", \"prenom\": \"Maheva\", \"email\": \"mahevapaul@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Paul\", \"prenom\": \"Maheva\", \"email\": \"mahevapaul@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 14:15:26'),
(70, NULL, 16, 'UPDATE', 'Modification utilisateur', 'utilisateur', 16, '{\"nom\": \"Paul\", \"prenom\": \"Maheva\", \"email\": \"mahevapaul@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Paul\", \"prenom\": \"Maheva\", \"email\": \"mahevapaul@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 14:40:01'),
(71, NULL, 16, 'UPDATE', 'Modification utilisateur', 'utilisateur', 16, '{\"nom\": \"Paul\", \"prenom\": \"Maheva\", \"email\": \"mahevapaul@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Paul\", \"prenom\": \"Maheva\", \"email\": \"mahevapaul@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 14:40:36'),
(72, NULL, 21, 'UPDATE', 'Modification utilisateur', 'utilisateur', 21, '{\"nom\": \"Robert\", \"prenom\": \"Cassy\", \"email\": \"cassyrobert@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Robert\", \"prenom\": \"Cassy\", \"email\": \"cassyrobert@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 14:49:09'),
(73, NULL, 21, 'CREATE', 'Création boutique', 'boutique', 14, NULL, '{\"nomBoutique\": \"Fantasy of Cassy\", \"slugBoutique\": \"fantasy-of-cassy\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 14:50:09'),
(74, NULL, 21, 'UPDATE', 'Modification utilisateur', 'utilisateur', 21, '{\"nom\": \"Robert\", \"prenom\": \"Cassy\", \"email\": \"cassyrobert@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Robert\", \"prenom\": \"Cassy\", \"email\": \"cassyrobert@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 14:50:13'),
(75, NULL, 21, 'UPDATE', 'Modification utilisateur', 'utilisateur', 21, '{\"nom\": \"Robert\", \"prenom\": \"Cassy\", \"email\": \"cassyrobert@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Robert\", \"prenom\": \"Cassy\", \"email\": \"cassyrobert@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 15:02:28'),
(76, NULL, 21, 'UPDATE', 'Modification utilisateur', 'utilisateur', 21, '{\"nom\": \"Robert\", \"prenom\": \"Cassy\", \"email\": \"cassyrobert@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Robert\", \"prenom\": \"Cassy\", \"email\": \"cassyrobert@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 15:11:21'),
(77, NULL, 21, 'UPDATE', 'Modification utilisateur', 'utilisateur', 21, '{\"nom\": \"Robert\", \"prenom\": \"Cassy\", \"email\": \"cassyrobert@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Robert\", \"prenom\": \"Cassy\", \"email\": \"cassyrobert@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 15:12:39'),
(78, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 15:15:39'),
(79, NULL, 21, 'UPDATE', 'Modification utilisateur', 'utilisateur', 21, '{\"nom\": \"Robert\", \"prenom\": \"Cassy\", \"email\": \"cassyrobert@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Robert\", \"prenom\": \"Cassy\", \"email\": \"cassyrobert@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 15:19:33'),
(80, 15, 22, 'CREATE', 'Création boutique', 'boutique', 15, NULL, '{\"nomBoutique\": \"Fantasy of Cassy\", \"slugBoutique\": \"fantasy-of-cassy\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 15:47:17'),
(81, NULL, 22, 'UPDATE', 'Modification utilisateur', 'utilisateur', 22, '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Guerda\", \"email\": \"guerdapierre-saint@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Guerda\", \"email\": \"guerdapierre-saint@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 15:47:25'),
(82, NULL, 23, 'UPDATE', 'Modification utilisateur', 'utilisateur', 23, '{\"nom\": \"Felix\", \"prenom\": \"Kendy\", \"email\": \"kendyfelix@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Felix\", \"prenom\": \"Kendy\", \"email\": \"kendyfelix@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 16:05:01'),
(83, 16, 23, 'CREATE', 'Création boutique', 'boutique', 16, NULL, '{\"nomBoutique\": \"Feed Accessories\", \"slugBoutique\": \"feed-accessories\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 16:05:50'),
(84, NULL, 24, 'UPDATE', 'Modification utilisateur', 'utilisateur', 24, '{\"nom\": \"Szoboszlai\", \"prenom\": \"Dominic\", \"email\": \"dominicszoboszlai@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Szoboszlai\", \"prenom\": \"Dominic\", \"email\": \"dominicszoboszlai@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 16:21:08'),
(85, 17, 24, 'CREATE', 'Création boutique', 'boutique', 17, NULL, '{\"nomBoutique\": \"Liverpool\", \"slugBoutique\": \"liverpool\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 16:21:31'),
(86, NULL, 3, 'UPDATE', 'Modification utilisateur', 'utilisateur', 3, '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 16:38:17'),
(87, NULL, 3, 'UPDATE', 'Modification utilisateur', 'utilisateur', 3, '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 16:38:35'),
(88, NULL, 17, 'UPDATE', 'Modification utilisateur', 'utilisateur', 17, '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 17:26:15'),
(89, NULL, 17, 'UPDATE', 'Modification utilisateur', 'utilisateur', 17, '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 17:32:16'),
(90, NULL, 17, 'UPDATE', 'Modification utilisateur', 'utilisateur', 17, '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 17:49:53'),
(91, NULL, 17, 'UPDATE', 'Modification utilisateur', 'utilisateur', 17, '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 17:50:04'),
(92, NULL, 17, 'UPDATE', 'Modification utilisateur', 'utilisateur', 17, '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 17:56:24'),
(93, NULL, 3, 'UPDATE', 'Modification utilisateur', 'utilisateur', 3, '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 18:09:53'),
(94, NULL, 3, 'UPDATE', 'Modification utilisateur', 'utilisateur', 3, '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-19 19:11:38'),
(95, NULL, 25, 'UPDATE', 'Modification utilisateur', 'utilisateur', 25, '{\"nom\": \"Dorleans\", \"prenom\": \"Olivier\", \"email\": \"olivierdorleans@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Dorleans\", \"prenom\": \"Olivier\", \"email\": \"olivierdorleans@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-20 13:05:21'),
(96, 18, 25, 'CREATE', 'Création boutique', 'boutique', 18, NULL, '{\"nomBoutique\": \"OLI SOLUTIONS\", \"slugBoutique\": \"oli-solutions\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-20 13:06:06'),
(97, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-20 13:08:48'),
(98, NULL, 3, 'UPDATE', 'Modification utilisateur', 'utilisateur', 3, '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-20 13:11:57'),
(99, NULL, 3, 'UPDATE', 'Modification utilisateur', 'utilisateur', 3, '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-20 14:36:35'),
(100, NULL, 9, 'UPDATE', 'Modification utilisateur', 'utilisateur', 9, '{\"nom\": \"Charles\", \"prenom\": \"Anne\", \"email\": \"anne.charles@email.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Charles\", \"prenom\": \"Anne\", \"email\": \"anne.charles@email.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-20 19:23:21'),
(101, NULL, 22, 'UPDATE', 'Modification utilisateur', 'utilisateur', 22, '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Guerda\", \"email\": \"guerdapierre-saint@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Guerda\", \"email\": \"guerdapierre-saint@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-20 20:15:49'),
(102, NULL, 19, 'UPDATE', 'Modification utilisateur', 'utilisateur', 19, '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-20 20:15:59'),
(103, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-21 05:00:26'),
(104, NULL, 19, 'UPDATE', 'Modification utilisateur', 'utilisateur', 19, '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-21 05:17:33'),
(105, NULL, 19, 'UPDATE', 'Modification utilisateur', 'utilisateur', 19, '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-21 05:42:53'),
(106, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-21 05:49:49'),
(107, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-21 05:49:58'),
(108, NULL, 19, 'UPDATE', 'Modification utilisateur', 'utilisateur', 19, '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-21 06:11:48'),
(109, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-21 06:15:06'),
(110, NULL, 3, 'UPDATE', 'Modification utilisateur', 'utilisateur', 3, '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-21 06:17:16'),
(111, NULL, 19, 'UPDATE', 'Modification utilisateur', 'utilisateur', 19, '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-21 06:42:50'),
(112, NULL, 19, 'UPDATE', 'Modification utilisateur', 'utilisateur', 19, '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-21 09:09:38'),
(113, NULL, 19, 'UPDATE', 'Modification utilisateur', 'utilisateur', 19, '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-21 09:15:24'),
(114, NULL, 4, 'UPDATE', 'Modification utilisateur', 'utilisateur', 4, '{\"nom\": \"Louis\", \"prenom\": \"Marie\", \"email\": \"marie.louis@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Louis\", \"prenom\": \"Marie\", \"email\": \"marie.louis@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-21 11:52:02'),
(115, NULL, 9, 'UPDATE', 'Modification utilisateur', 'utilisateur', 9, '{\"nom\": \"Charles\", \"prenom\": \"Anne\", \"email\": \"anne.charles@email.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Charles\", \"prenom\": \"Anne\", \"email\": \"anne.charles@email.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-21 11:52:14'),
(116, NULL, 9, 'UPDATE', 'Modification utilisateur', 'utilisateur', 9, '{\"nom\": \"Charles\", \"prenom\": \"Anne\", \"email\": \"anne.charles@email.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Charles\", \"prenom\": \"Anne\", \"email\": \"anne.charles@email.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-21 11:52:31'),
(117, NULL, 9, 'UPDATE', 'Modification utilisateur', 'utilisateur', 9, '{\"nom\": \"Charles\", \"prenom\": \"Anne\", \"email\": \"anne.charles@email.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Charles\", \"prenom\": \"Anne\", \"email\": \"anne.charles@email.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-21 18:35:34'),
(118, NULL, 9, 'UPDATE', 'Modification utilisateur', 'utilisateur', 9, '{\"nom\": \"Charles\", \"prenom\": \"Anne\", \"email\": \"anne.charles@email.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Charles\", \"prenom\": \"Anne\", \"email\": \"anne.charles@email.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-21 19:18:37'),
(119, 2, NULL, 'AUTRE', 'ALERTE_STOCK_BAS', 'produit', 5, NULL, NULL, '{\"stockRestant\": 8, \"stockAlerte\": \"10\"}', NULL, NULL, '2026-02-22 08:01:37'),
(120, 2, NULL, 'AUTRE', 'ALERTE_STOCK_BAS', 'produit', 6, NULL, NULL, '{\"stockRestant\": 7, \"stockAlerte\": \"10\"}', NULL, NULL, '2026-02-22 08:01:37'),
(121, 2, NULL, 'AUTRE', 'ALERTE_STOCK_BAS', 'produit', 6, NULL, NULL, '{\"stockRestant\": 5, \"stockAlerte\": \"10\"}', NULL, NULL, '2026-02-22 08:58:15'),
(122, NULL, 19, 'UPDATE', 'Modification utilisateur', 'utilisateur', 19, '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 12:16:36'),
(123, 2, NULL, 'AUTRE', 'ALERTE_STOCK_BAS', 'produit', 5, NULL, NULL, '{\"stockRestant\": 5, \"stockAlerte\": \"10\"}', NULL, NULL, '2026-02-22 12:18:51'),
(124, NULL, 3, 'UPDATE', 'Modification utilisateur', 'utilisateur', 3, '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 12:25:09'),
(125, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 12:51:57'),
(126, NULL, 3, 'UPDATE', 'Modification utilisateur', 'utilisateur', 3, '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 12:56:56'),
(127, NULL, 19, 'UPDATE', 'Modification utilisateur', 'utilisateur', 19, '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 13:08:55'),
(128, 2, NULL, 'AUTRE', 'ALERTE_STOCK_BAS', 'produit', 5, NULL, NULL, '{\"stockRestant\": 3, \"stockAlerte\": \"10\"}', NULL, NULL, '2026-02-22 13:12:09'),
(129, NULL, 19, 'UPDATE', 'Modification utilisateur', 'utilisateur', 19, '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 16:41:36'),
(130, NULL, 19, 'UPDATE', 'Modification utilisateur', 'utilisateur', 19, '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 16:41:45'),
(131, NULL, 19, 'UPDATE', 'Modification utilisateur', 'utilisateur', 19, '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 16:44:13'),
(132, NULL, 19, 'UPDATE', 'Modification utilisateur', 'utilisateur', 19, '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 16:44:17'),
(133, NULL, 19, 'UPDATE', 'Modification utilisateur', 'utilisateur', 19, '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 16:46:43'),
(134, NULL, 19, 'UPDATE', 'Modification utilisateur', 'utilisateur', 19, '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 16:46:53'),
(135, NULL, 19, 'UPDATE', 'Modification utilisateur', 'utilisateur', 19, '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 16:52:23'),
(136, NULL, 19, 'UPDATE', 'Modification utilisateur', 'utilisateur', 19, '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 16:53:58'),
(137, NULL, 19, 'UPDATE', 'Modification utilisateur', 'utilisateur', 19, '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 17:00:46'),
(138, NULL, 19, 'UPDATE', 'Modification utilisateur', 'utilisateur', 19, '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 17:04:47'),
(139, NULL, 8, 'UPDATE', 'Modification utilisateur', 'utilisateur', 8, '{\"nom\": \"Beaumont\", \"prenom\": \"Pierre\", \"email\": \"pierre.beaumont@email.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Beaumont\", \"prenom\": \"Pierre\", \"email\": \"pierre.beaumont@email.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 17:06:34'),
(140, NULL, 2, 'UPDATE', 'Modification utilisateur', 'utilisateur', 2, '{\"nom\": \"Larack\", \"prenom\": \"Wolf Junior\", \"email\": \"larackwolfjr@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Larack\", \"prenom\": \"Wolf Junior\", \"email\": \"larackwolfjr@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 17:07:05'),
(141, NULL, 5, 'UPDATE', 'Modification utilisateur', 'utilisateur', 5, '{\"nom\": \"Baptiste\", \"prenom\": \"Paul\", \"email\": \"paul.baptiste@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Baptiste\", \"prenom\": \"Paul\", \"email\": \"paul.baptiste@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 17:07:34'),
(142, NULL, 6, 'UPDATE', 'Modification utilisateur', 'utilisateur', 6, '{\"nom\": \"Joseph\", \"prenom\": \"Michel\", \"email\": \"michel.joseph@email.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Joseph\", \"prenom\": \"Michel\", \"email\": \"michel.joseph@email.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 17:07:59'),
(143, NULL, 12, 'UPDATE', 'Modification utilisateur', 'utilisateur', 12, '{\"nom\": \"Esta\", \"prenom\": \"Ricardo\", \"email\": \"ricardoesta@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Esta\", \"prenom\": \"Ricardo\", \"email\": \"ricardoesta@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 17:08:32');
INSERT INTO `audit_log` (`idLog`, `idBoutique`, `idUtilisateur`, `typeAction`, `action`, `tableConcernee`, `idEnregistrement`, `anciennesValeurs`, `nouvellesValeurs`, `details`, `ipAddress`, `userAgent`, `dateAction`) VALUES
(144, NULL, 24, 'UPDATE', 'Modification utilisateur', 'utilisateur', 24, '{\"nom\": \"Szoboszlai\", \"prenom\": \"Dominic\", \"email\": \"dominicszoboszlai@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Szoboszlai\", \"prenom\": \"Dominic\", \"email\": \"dominicszoboszlai@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 17:10:20'),
(145, NULL, 20, 'UPDATE', 'Modification utilisateur', 'utilisateur', 20, '{\"nom\": \"Ronaldo\", \"prenom\": \"Cristiano\", \"email\": \"cristianoronaldo@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Ronaldo\", \"prenom\": \"Cristiano\", \"email\": \"cristianoronaldo@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 17:12:37'),
(146, NULL, 1, 'UPDATE', 'Modification utilisateur', 'utilisateur', 1, '{\"nom\": \"Paul\", \"prenom\": \"Karlsen\", \"email\": \"superadmin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Paul\", \"prenom\": \"Karlsen\", \"email\": \"superadmin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 17:13:46'),
(147, NULL, 14, 'UPDATE', 'Modification utilisateur', 'utilisateur', 14, '{\"nom\": \"Cazimir\", \"prenom\": \"Davidson\", \"email\": \"davidsoncazimir@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Cazimir\", \"prenom\": \"Davidson\", \"email\": \"davidsoncazimir@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 17:16:09'),
(148, NULL, 13, 'UPDATE', 'Modification utilisateur', 'utilisateur', 13, '{\"nom\": \"Louis\", \"prenom\": \"Boaz\", \"email\": \"boazlouis@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Louis\", \"prenom\": \"Boaz\", \"email\": \"boazlouis@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 17:16:22'),
(149, NULL, 23, 'UPDATE', 'Modification utilisateur', 'utilisateur', 23, '{\"nom\": \"Felix\", \"prenom\": \"Kendy\", \"email\": \"kendyfelix@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Felix\", \"prenom\": \"Kendy\", \"email\": \"kendyfelix@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 17:17:36'),
(150, NULL, 25, 'UPDATE', 'Modification utilisateur', 'utilisateur', 25, '{\"nom\": \"Dorleans\", \"prenom\": \"Olivier\", \"email\": \"olivierdorleans@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Dorleans\", \"prenom\": \"Olivier\", \"email\": \"olivierdorleans@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 17:18:07'),
(151, NULL, 4, 'UPDATE', 'Modification utilisateur', 'utilisateur', 4, '{\"nom\": \"Louis\", \"prenom\": \"Marie\", \"email\": \"marie.louis@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Louis\", \"prenom\": \"Marie\", \"email\": \"marie.louis@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 17:19:58'),
(152, NULL, 7, 'UPDATE', 'Modification utilisateur', 'utilisateur', 7, '{\"nom\": \"Francois\", \"prenom\": \"Claire\", \"email\": \"claire.francois@email.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Francois\", \"prenom\": \"Claire\", \"email\": \"claire.francois@email.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 17:20:12'),
(153, NULL, 15, 'UPDATE', 'Modification utilisateur', 'utilisateur', 15, '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Carline\", \"email\": \"carlinepst@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Carline\", \"email\": \"carlinepst@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 17:21:03'),
(154, NULL, 21, 'UPDATE', 'Modification utilisateur', 'utilisateur', 21, '{\"nom\": \"Robert\", \"prenom\": \"Cassy\", \"email\": \"cassyrobert@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Robert\", \"prenom\": \"Cassy\", \"email\": \"cassyrobert@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 17:21:40'),
(155, NULL, 22, 'UPDATE', 'Modification utilisateur', 'utilisateur', 22, '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Guerda\", \"email\": \"guerdapierre-saint@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Guerda\", \"email\": \"guerdapierre-saint@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 17:22:19'),
(156, NULL, 19, 'UPDATE', 'Modification utilisateur', 'utilisateur', 19, '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 17:43:18'),
(157, NULL, 19, 'UPDATE', 'Modification utilisateur', 'utilisateur', 19, '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 17:45:21'),
(158, NULL, 19, 'UPDATE', 'Modification utilisateur', 'utilisateur', 19, '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 18:08:52'),
(159, NULL, 19, 'UPDATE', 'Modification utilisateur', 'utilisateur', 19, '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 18:09:45'),
(160, NULL, 19, 'UPDATE', 'Modification utilisateur', 'utilisateur', 19, '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 18:15:32'),
(161, NULL, 19, 'UPDATE', 'Modification utilisateur', 'utilisateur', 19, '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 18:18:29'),
(162, NULL, 19, 'UPDATE', 'Modification utilisateur', 'utilisateur', 19, '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-22 19:22:20'),
(163, NULL, 19, 'UPDATE', 'Modification utilisateur', 'utilisateur', 19, '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 03:48:25'),
(164, 1, NULL, 'AUTRE', 'ALERTE_STOCK_BAS', 'produit', 8, NULL, NULL, '{\"stockRestant\": 5, \"stockAlerte\": \"5\"}', NULL, NULL, '2026-02-23 04:10:30'),
(165, NULL, 3, 'UPDATE', 'Modification utilisateur', 'utilisateur', 3, '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 04:34:36'),
(166, NULL, 3, 'UPDATE', 'Modification utilisateur', 'utilisateur', 3, '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 04:50:50'),
(167, NULL, 3, 'UPDATE', 'Modification utilisateur', 'utilisateur', 3, '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 04:59:08'),
(168, NULL, 3, 'UPDATE', 'Modification utilisateur', 'utilisateur', 3, '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 04:59:30'),
(169, NULL, 3, 'UPDATE', 'Modification utilisateur', 'utilisateur', 3, '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 12:16:43'),
(170, NULL, 3, 'UPDATE', 'Modification utilisateur', 'utilisateur', 3, '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 12:31:15'),
(171, NULL, 3, 'UPDATE', 'Modification utilisateur', 'utilisateur', 3, '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 12:31:39'),
(172, NULL, 3, 'UPDATE', 'Modification utilisateur', 'utilisateur', 3, '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 12:32:47'),
(173, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 12:33:49'),
(174, NULL, 18, 'UPDATE', 'Modification utilisateur', 'utilisateur', 18, '{\"nom\": \"Celestin\", \"prenom\": \"Junior\", \"email\": \"juniorcelestin@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Celestin\", \"prenom\": \"Junior\", \"email\": \"juniorcelestin@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 12:50:00'),
(175, NULL, 17, 'UPDATE', 'Modification utilisateur', 'utilisateur', 17, '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Aneus\", \"prenom\": \"Payas\", \"email\": \"tipaya@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 12:50:13'),
(176, NULL, 16, 'UPDATE', 'Modification utilisateur', 'utilisateur', 16, '{\"nom\": \"Paul\", \"prenom\": \"Maheva\", \"email\": \"mahevapaul@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Paul\", \"prenom\": \"Maheva\", \"email\": \"mahevapaul@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 12:52:25'),
(177, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 13:24:56'),
(178, NULL, 25, 'UPDATE', 'Modification utilisateur', 'utilisateur', 25, '{\"nom\": \"Dorleans\", \"prenom\": \"Olivier\", \"email\": \"olivierdorleans@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Dorleans\", \"prenom\": \"Olivier\", \"email\": \"olivierdorleans@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 15:07:09'),
(179, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 15:16:01'),
(180, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Dev\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 15:16:19'),
(181, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 15:16:34'),
(182, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 15:21:11'),
(183, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 15:22:32'),
(184, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 15:22:40'),
(185, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 15:24:25'),
(186, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 15:25:00'),
(187, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 15:26:10'),
(188, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 15:27:38'),
(189, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 15:32:30'),
(190, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 15:33:02'),
(191, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 15:35:03'),
(192, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 15:38:30'),
(193, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 15:49:13'),
(194, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 16:03:15'),
(195, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 16:04:29'),
(196, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 16:08:29'),
(197, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 16:08:34'),
(198, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 16:09:11'),
(199, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 16:11:42'),
(200, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 16:12:11'),
(201, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 16:12:52'),
(202, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 16:13:59'),
(203, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 16:18:15'),
(204, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 16:36:44'),
(205, NULL, 25, 'UPDATE', 'Modification utilisateur', 'utilisateur', 25, '{\"nom\": \"Dorleans\", \"prenom\": \"Olivier\", \"email\": \"olivierdorleans@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Dorleans\", \"prenom\": \"Olivier\", \"email\": \"olivierdorleans@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 16:41:18'),
(206, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 17:09:32'),
(207, NULL, 25, 'UPDATE', 'Modification utilisateur', 'utilisateur', 25, '{\"nom\": \"Dorleans\", \"prenom\": \"Olivier\", \"email\": \"olivierdorleans@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Dorleans\", \"prenom\": \"Olivier\", \"email\": \"olivierdorleans@gmail.com\", \"role\": \"tenant\", \"statut\": \"en_attente\"}', NULL, NULL, NULL, '2026-02-23 17:19:57'),
(208, NULL, 25, 'UPDATE', 'Modification utilisateur', 'utilisateur', 25, '{\"nom\": \"Dorleans\", \"prenom\": \"Olivier\", \"email\": \"olivierdorleans@gmail.com\", \"role\": \"tenant\", \"statut\": \"en_attente\"}', '{\"nom\": \"Dorleans\", \"prenom\": \"Olivier\", \"email\": \"olivierdorleans@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 17:20:10'),
(209, NULL, 25, 'UPDATE', 'Modification utilisateur', 'utilisateur', 25, '{\"nom\": \"Dorleans\", \"prenom\": \"Olivier\", \"email\": \"olivierdorleans@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Dorleans\", \"prenom\": \"Olivier\", \"email\": \"olivierdorleans@gmail.com\", \"role\": \"tenant\", \"statut\": \"en_attente\"}', NULL, NULL, NULL, '2026-02-23 17:24:08'),
(210, NULL, 25, 'UPDATE', 'Modification utilisateur', 'utilisateur', 25, '{\"nom\": \"Dorleans\", \"prenom\": \"Olivier\", \"email\": \"olivierdorleans@gmail.com\", \"role\": \"tenant\", \"statut\": \"en_attente\"}', '{\"nom\": \"Dorleans\", \"prenom\": \"Olivier\", \"email\": \"olivierdorleans@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 17:24:36'),
(211, NULL, 25, 'UPDATE', 'Modification utilisateur', 'utilisateur', 25, '{\"nom\": \"Dorleans\", \"prenom\": \"Olivier\", \"email\": \"olivierdorleans@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Dorleans\", \"prenom\": \"Olivier\", \"email\": \"olivierdorleans@gmail.com\", \"role\": \"tenant\", \"statut\": \"bloque\"}', NULL, NULL, NULL, '2026-02-23 17:29:39'),
(212, NULL, 25, 'UPDATE', 'Modification utilisateur', 'utilisateur', 25, '{\"nom\": \"Dorleans\", \"prenom\": \"Olivier\", \"email\": \"olivierdorleans@gmail.com\", \"role\": \"tenant\", \"statut\": \"bloque\"}', '{\"nom\": \"Dorleans\", \"prenom\": \"Olivier\", \"email\": \"olivierdorleans@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 17:44:08'),
(213, NULL, 25, 'UPDATE', 'Modification utilisateur', 'utilisateur', 25, '{\"nom\": \"Dorleans\", \"prenom\": \"Olivier\", \"email\": \"olivierdorleans@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Dorleans\", \"prenom\": \"Olivier\", \"email\": \"olivierdorleans@gmail.com\", \"role\": \"tenant\", \"statut\": \"en_attente\"}', NULL, NULL, NULL, '2026-02-23 17:45:06'),
(214, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 17:45:16'),
(215, NULL, 25, 'UPDATE', 'Modification utilisateur', 'utilisateur', 25, '{\"nom\": \"Dorleans\", \"prenom\": \"Olivier\", \"email\": \"olivierdorleans@gmail.com\", \"role\": \"tenant\", \"statut\": \"en_attente\"}', '{\"nom\": \"Dorleans\", \"prenom\": \"Olivier\", \"email\": \"olivierdorleans@gmail.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 17:45:26'),
(216, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 18:15:30'),
(217, NULL, 26, 'UPDATE', 'Modification utilisateur', 'utilisateur', 26, '{\"nom\": \"LOUIS\", \"prenom\": \"Boaz\", \"email\": \"louisboaz29@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"LOUIS\", \"prenom\": \"Boaz\", \"email\": \"louisboaz29@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 18:49:29'),
(218, NULL, 26, 'UPDATE', 'Modification utilisateur', 'utilisateur', 26, '{\"nom\": \"LOUIS\", \"prenom\": \"Boaz\", \"email\": \"louisboaz29@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"LOUIS\", \"prenom\": \"Boaz\", \"email\": \"louisboaz29@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 18:49:29'),
(219, NULL, 3, 'UPDATE', 'Modification utilisateur', 'utilisateur', 3, '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 19:01:03'),
(220, NULL, 26, 'UPDATE', 'Modification utilisateur', 'utilisateur', 26, '{\"nom\": \"LOUIS\", \"prenom\": \"Boaz\", \"email\": \"louisboaz29@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"LOUIS\", \"prenom\": \"Boaz\", \"email\": \"louisboaz29@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 19:07:57'),
(221, NULL, 3, 'UPDATE', 'Modification utilisateur', 'utilisateur', 3, '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 19:14:20'),
(222, NULL, 26, 'UPDATE', 'Modification utilisateur', 'utilisateur', 26, '{\"nom\": \"LOUIS\", \"prenom\": \"Boaz\", \"email\": \"louisboaz29@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"LOUIS\", \"prenom\": \"Boaz\", \"email\": \"louisboaz29@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 19:14:54'),
(223, NULL, 3, 'UPDATE', 'Modification utilisateur', 'utilisateur', 3, '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-23 19:15:46'),
(224, NULL, 3, 'UPDATE', 'Modification utilisateur', 'utilisateur', 3, '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre\", \"prenom\": \"Jean\", \"email\": \"jean.pierre@email.com\", \"role\": \"tenant\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-24 05:11:18'),
(225, NULL, 26, 'UPDATE', 'Modification utilisateur', 'utilisateur', 26, '{\"nom\": \"LOUIS\", \"prenom\": \"Boaz\", \"email\": \"louisboaz29@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Louis\", \"prenom\": \"Boaz\", \"email\": \"louisboaz29@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-24 05:13:20'),
(226, NULL, 11, 'UPDATE', 'Modification utilisateur', 'utilisateur', 11, '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', '{\"nom\": \"Admin\", \"prenom\": \"Developper\", \"email\": \"admin@shopxpao.ht\", \"role\": \"admin\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-24 06:34:58'),
(227, NULL, 19, 'UPDATE', 'Modification utilisateur', 'utilisateur', 19, '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', '{\"nom\": \"Pierre-Saint\", \"prenom\": \"Chantale\", \"email\": \"chantalepierre-saint@gmail.com\", \"role\": \"client\", \"statut\": \"actif\"}', NULL, NULL, NULL, '2026-02-24 11:56:51');

-- --------------------------------------------------------

--
-- Structure de la table `boutique`
--

CREATE TABLE `boutique` (
  `idBoutique` int(10) UNSIGNED NOT NULL,
  `idProprietaire` int(10) UNSIGNED NOT NULL COMMENT 'FK vers utilisateur avec role=tenant',
  `nomBoutique` varchar(150) NOT NULL,
  `slugBoutique` varchar(150) NOT NULL COMMENT 'URL-friendly unique',
  `description` text DEFAULT NULL,
  `statut` enum('actif','suspendu','en_attente','ferme') NOT NULL DEFAULT 'en_attente',
  `dateCreation` datetime NOT NULL DEFAULT current_timestamp(),
  `dateModification` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Déchargement des données de la table `boutique`
--

INSERT INTO `boutique` (`idBoutique`, `idProprietaire`, `nomBoutique`, `slugBoutique`, `description`, `statut`, `dateCreation`, `dateModification`) VALUES
(1, 3, 'Mode Haiti', 'mode-haiti', 'La meilleure boutique de mode en Haiti.', 'actif', '2026-02-12 16:08:32', '2026-02-22 13:06:34'),
(2, 4, 'Tech Store Haiti', 'tech-store-haiti', 'Technologie et gadgets', 'actif', '2026-02-12 16:08:32', '2026-02-12 16:08:32'),
(3, 5, 'Épicerie Fine', 'epicerie-fine', 'Produits alimentaires de qualité', 'actif', '2026-02-12 16:08:32', '2026-02-12 16:08:32'),
(4, 18, 'Henry Deschamps', 'henry-deschamps', 'Livres, Cahiers', 'actif', '2026-02-16 11:24:45', '2026-02-18 07:40:58'),
(5, 2, 'Duo Multi-services', 'duo-multi-services', 'Tout', 'actif', '2026-02-16 09:20:24', '2026-02-16 09:20:24'),
(6, 12, 'Rikado', 'rikado', 'Articles Electromenagers', 'actif', '2026-02-16 09:22:02', '2026-02-16 09:22:02'),
(7, 13, 'BozQuincaille', 'boz-quincaille', 'Quincaillerie', 'actif', '2026-02-16 09:23:49', '2026-02-16 09:30:59'),
(8, 14, 'Ti Balde Boiserie', 'cdz-boisiere', 'Boiserie', 'actif', '2026-02-16 09:25:14', '2026-02-16 09:31:28'),
(9, 15, 'Carl Sounds Selections', 'carl-sounds-selection', 'Tout pour la musique', 'actif', '2026-02-16 09:27:33', '2026-02-16 09:32:10'),
(10, 16, 'Maamou Perles', 'maamou-perles', 'Bijoux unique', 'actif', '2026-02-16 09:28:35', '2026-02-16 09:32:23'),
(11, 17, 'Old Payas Store', 'old-payas-store', 'bijoux et autres', 'actif', '2026-02-16 09:29:57', '2026-02-16 09:31:53'),
(15, 22, 'Fantasy of Cassy', 'fantasy-of-cassy', 'Fantasy', 'actif', '2026-02-19 15:47:17', '2026-02-19 15:47:17'),
(16, 23, 'Feed Accessories', 'feed-accessories', 'Technology Accessories', 'actif', '2026-02-19 16:05:50', '2026-02-19 16:05:50'),
(17, 24, 'Liverpool Store', 'liverpool-store', 'You\'ll Never Walk Alone', 'actif', '2026-02-19 16:21:31', '2026-02-22 15:52:24'),
(18, 25, 'OLI SOLUTIONS', 'oli-solutions', 'Vente de Delco. generatrice', 'actif', '2026-02-20 13:06:06', '2026-02-23 14:42:04');

--
-- Déclencheurs `boutique`
--
DELIMITER $$
CREATE TRIGGER `trg_boutique_after_insert` AFTER INSERT ON `boutique` FOR EACH ROW BEGIN
    INSERT INTO parametre_boutique (idBoutique, devise, taxe)
    VALUES (NEW.idBoutique, 'HTG', 10.00);

    INSERT INTO abonnement (idBoutique, typeAbonnement, dateDebut, statut)
    VALUES (NEW.idBoutique, 'gratuit', CURRENT_DATE, 'actif');

    INSERT INTO audit_log (idBoutique, idUtilisateur, typeAction, action, tableConcernee, idEnregistrement, nouvellesValeurs)
    VALUES (
        NEW.idBoutique, NEW.idProprietaire, 'CREATE', 'Création boutique', 'boutique', NEW.idBoutique,
        JSON_OBJECT('nomBoutique', NEW.nomBoutique, 'slugBoutique', NEW.slugBoutique, 'statut', NEW.statut)
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_boutique_before_insert` BEFORE INSERT ON `boutique` FOR EACH ROW BEGIN
    DECLARE v_role VARCHAR(20);

    SELECT role INTO v_role FROM utilisateur WHERE idUtilisateur = NEW.idProprietaire;

    IF v_role != 'tenant' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Seuls les utilisateurs avec role=tenant peuvent créer une boutique';
    END IF;

    IF NEW.slugBoutique IS NULL OR NEW.slugBoutique = '' THEN
        SET NEW.slugBoutique = LOWER(REPLACE(REPLACE(TRIM(NEW.nomBoutique), ' ', '-'), '''', ''));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `categorie`
--

CREATE TABLE `categorie` (
  `idCategorie` int(10) UNSIGNED NOT NULL,
  `idBoutique` int(10) UNSIGNED NOT NULL,
  `idCategorieParent` int(10) UNSIGNED DEFAULT NULL COMMENT 'Pour sous-catégories',
  `nomCategorie` varchar(100) NOT NULL,
  `slugCategorie` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(500) DEFAULT NULL,
  `ordre` int(10) UNSIGNED DEFAULT 0,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `dateCreation` datetime NOT NULL DEFAULT current_timestamp()
) ;

--
-- Déchargement des données de la table `categorie`
--

INSERT INTO `categorie` (`idCategorie`, `idBoutique`, `idCategorieParent`, `nomCategorie`, `slugCategorie`, `description`, `image`, `ordre`, `actif`, `dateCreation`) VALUES
(1, 1, 8, 'Vêtements Homme', 'vetements-homme', 'Mode masculine', NULL, 0, 1, '2026-02-12 16:08:32'),
(2, 1, 8, 'Vêtements Femme', 'vetements-femme', 'Mode féminine', NULL, 0, 1, '2026-02-12 16:08:32'),
(3, 1, NULL, 'Accessoires', 'accessoires', 'Sacs, bijoux', NULL, 0, 1, '2026-02-12 16:08:32'),
(4, 2, 7, 'Smartphones', 'smartphones', 'Téléphones intelligents', NULL, 0, 1, '2026-02-12 16:08:32'),
(5, 2, 7, 'Ordinateurs', 'ordinateurs', 'Laptops et desktops', NULL, 0, 1, '2026-02-12 16:08:32'),
(6, 2, 7, 'Accessoires Tech', 'accessoires-tech', 'Câbles, étuis, etc.', NULL, 0, 1, '2026-02-12 16:08:32'),
(7, 3, NULL, 'Electronique grand public', 'electronique', 'objets connectés', NULL, 0, 1, '2026-02-15 23:03:59'),
(8, 1, NULL, 'Vêtements et accessoires', 'vêtaccessoires', 'prêt a porter', NULL, 0, 1, '2026-02-15 23:09:30'),
(9, 11, 8, 'Lunettes et Montres', 'lunettes-montres', 'Good Watches & Glasses', NULL, 0, 1, '2026-02-16 09:41:05'),
(10, 11, 8, 'Chaussures et Accessoires', 'chaussures-accessoires', 'Chaussures, chaussettes et autres', NULL, 0, 1, '2026-02-16 09:45:47'),
(11, 2, 7, 'Tablettes et Liseuses Electronique', 'tablettes', 'Tablettes pour tous', NULL, 0, 1, '2026-02-16 09:51:14'),
(12, 2, 7, 'Domotiques et Objets Connectés', 'objets-connectés ', 'Souris, Clavier, Camera', NULL, 0, 1, '2026-02-16 09:53:54'),
(13, 2, 7, 'Television et Smart TV', 'smart-tv', 'Television, Radio', NULL, 0, 1, '2026-02-16 09:57:12'),
(14, 2, 7, 'Audio', 'audio', 'casques, ecouteurs, enceintes, bluetooth', NULL, 0, 1, '2026-02-16 09:58:16'),
(15, 2, 7, 'Console et jeux Video', 'console-jeux', 'PS5, XBOX, Manette', NULL, 0, 1, '2026-02-16 09:59:08'),
(16, 2, 7, 'Appareil photo et caméras', 'appareil-photo', 'caméras professionnelles', NULL, 0, 1, '2026-02-16 10:04:15'),
(17, 1, 8, 'Vêtements enfant', 'vêtements-enfant', 'layette', NULL, 0, 1, '2026-02-16 10:07:45'),
(18, 1, 8, 'Sac et Maroquinerie', 'sac-et-maroquinerie', 'sac a main artisanale', NULL, 0, 1, '2026-02-16 10:09:02'),
(19, 1, NULL, 'Chapeaux et casquettes', 'chapeaux-casquettes', 'couvre-chefs', NULL, 0, 1, '2026-02-16 10:09:55'),
(20, 1, 8, 'Echarpe, foulards et gants', 'echarpe-foulard-gant', 'style', NULL, 0, 1, '2026-02-16 10:10:37'),
(21, 3, NULL, 'Produits Gastronomiques', 'prod-gastro', 'huile, epice, produits bio', NULL, 0, 1, '2026-02-16 10:15:48'),
(22, 3, 21, 'Huiles et vinaigres artisanaux', 'huiles-et-vinaigres-artisanaux', 'Huiles et vinaigres de qualité supérieure pour la cuisine gourmet', NULL, 0, 1, '2026-02-16 10:45:11'),
(23, 3, 21, 'Épices et condiments rares', 'epices-et-condiments-rares', 'Épices et condiments fins pour rehausser vos plats', NULL, 0, 1, '2026-02-16 10:45:11'),
(24, 3, 21, 'Confitures et miels premium', 'confitures-et-miels-premium', 'Confitures artisanales et miels raffinés', NULL, 0, 1, '2026-02-16 10:45:11'),
(25, 3, 21, 'Chocolats et confiseries fines', 'chocolats-et-confiseries-fines', 'Chocolats et douceurs de qualité supérieure', NULL, 0, 1, '2026-02-16 10:45:11'),
(26, 3, 21, 'Thés et cafés haut de gamme', 'thes-et-cafes-haut-de-gamme', 'Sélection de thés et cafés rares et aromatiques', NULL, 0, 1, '2026-02-16 10:45:11'),
(27, 3, 21, 'Pâtes, riz et légumineuses spéciales', 'pates-riz-et-legumineuses-speciales', 'Pâtes, riz et légumineuses sélectionnés pour leur qualité', NULL, 0, 1, '2026-02-16 10:45:11'),
(28, 3, 21, 'Biscuits et viennoiseries de luxe', 'biscuits-et-viennoiseries-de-luxe', 'Biscuits et viennoiseries raffinés pour le plaisir gourmand', NULL, 0, 1, '2026-02-16 10:45:12'),
(29, 3, 21, 'Charcuterie et produits séchés', 'charcuterie-et-produits-seches', 'Charcuteries fines et produits séchés de qualité', NULL, 0, 1, '2026-02-16 10:45:12'),
(30, 3, 21, 'Fromages affinés', 'fromages-affines', 'Fromages sélectionnés et affinés pour un goût exceptionnel', NULL, 0, 1, '2026-02-16 10:45:12'),
(31, 3, 21, 'Produits bio et gourmets locaux', 'produits-bio-et-gourmets-locaux', 'Produits bio et spécialités locales de grande qualité', NULL, 0, 1, '2026-02-16 10:45:12'),
(32, 6, NULL, 'Électroménager', 'articles-electromenagers', 'four, refrigerateur', NULL, 0, 1, '2026-02-16 11:02:10'),
(37, 6, 32, 'Réfrigérateurs', 'refrigerateurs', 'Réfrigérateurs et congélateurs etc', NULL, 0, 1, '2026-02-16 11:07:24'),
(38, 6, 32, 'Lave-linge et sèche-linge', 'lave-linge-et-seche-linge', 'Machines à laver et sèche-linge de qualité domestique', NULL, 0, 1, '2026-02-16 11:07:24'),
(39, 6, 32, 'Cuisinières et plaques de cuisson', 'cuisinieres-et-plaques-de-cuisson', 'Cuisinières, fours et plaques pour toutes vos préparations', NULL, 0, 1, '2026-02-16 11:07:24'),
(40, 6, 32, 'Micro-ondes', 'micro-ondes', 'Fours à micro-ondes pour réchauffer et cuisiner rapidement', NULL, 0, 1, '2026-02-16 11:07:24'),
(41, 6, 32, 'Aspirateurs', 'aspirateurs', 'Aspirateurs et nettoyeurs pour garder votre maison propre', NULL, 0, 1, '2026-02-16 11:07:24'),
(42, 6, 32, 'Petits électroménagers de cuisine', 'petits-electromenagers-de-cuisine', 'Blenders, mixeurs, robots culinaires et accessoires de cuisine', NULL, 0, 1, '2026-02-16 11:07:24'),
(43, 6, 32, 'Climatiseurs et ventilateurs', 'climatiseurs-et-ventilateurs', 'Appareils pour rafraîchir et ventiler votre intérieur', NULL, 0, 1, '2026-02-16 11:07:24'),
(44, 6, 32, 'Bouilloires et cafetières', 'bouilloires-et-cafeteres', 'Bouilloires électriques et cafetières pour boissons chaudes', NULL, 0, 1, '2026-02-16 11:07:24'),
(45, 6, 32, 'Fers à repasser et centrales vapeur', 'fers-a-repasser-et-centrales-vapeur', 'Fers et centrales vapeur pour un repassage facile et efficace', NULL, 0, 1, '2026-02-16 11:07:24'),
(46, 6, 32, 'Grille-pains et toasteurs', 'grille-pains-et-toasteurs', 'Grille-pains et toasteurs pour vos petits-déjeuners', NULL, 0, 1, '2026-02-16 11:07:24'),
(47, 5, NULL, 'Emballages et fournitures d’impression', 'emballages-impression', NULL, NULL, 0, 1, '2026-02-16 11:14:41'),
(48, 5, 47, 'Emballages alimentaires', 'emballages-alimentaires', 'Boîtes, sachets et contenants pour denrées alimentaires', NULL, 0, 1, '2026-02-16 11:18:21'),
(49, 5, 47, 'Emballages industriels', 'emballages-industriels', 'Cartons, palettes et films de protection pour produits industriels', NULL, 0, 1, '2026-02-16 11:18:21'),
(50, 5, 47, 'Sacs et pochettes personnalisés', 'sacs-et-pochettes-personnalises', 'Sacs papier, plastique ou textile imprimés pour entreprises', NULL, 0, 1, '2026-02-16 11:18:21'),
(51, 5, 47, 'Rubans adhésifs et scellants', 'rubans-adhesifs-et-scellants', 'Rubans d’emballage sécurisés ou personnalisés', NULL, 0, 1, '2026-02-16 11:18:21'),
(52, 5, 47, 'Étiquettes et autocollants', 'etiquettes-et-autocollants', 'Étiquettes pour produits, prix, promotions ou branding', NULL, 0, 1, '2026-02-16 11:18:21'),
(53, 5, 47, 'Papeterie personnalisée', 'papeterie-personnalisee', 'Carnets, blocs-notes et papiers à en-tête imprimés', NULL, 0, 1, '2026-02-16 11:18:21'),
(54, 5, 47, 'Cartes de visite et flyers', 'cartes-de-visite-et-flyers', 'Supports marketing imprimés pour entreprises et événements', NULL, 0, 1, '2026-02-16 11:18:21'),
(55, 5, 47, 'Brochures et catalogues', 'brochures-et-catalogues', 'Impression professionnelle de supports commerciaux', NULL, 0, 1, '2026-02-16 11:18:21'),
(56, 5, 47, 'Emballages cadeaux et décoratifs', 'emballages-cadeaux-et-decoratifs', 'Papiers, boîtes et rubans pour présentations soignées', NULL, 0, 1, '2026-02-16 11:18:21'),
(57, 5, 47, 'Supports de communication grand format', 'supports-de-communication-grand-format', 'Bannières, posters, kakemonos et roll-ups pour la publicité', NULL, 0, 1, '2026-02-16 11:18:21'),
(58, 4, NULL, 'Fournitures scolaires et de bureau', 'fourniture-scolaire', 'Livres, cahiers', NULL, 0, 1, '2026-02-16 11:28:40'),
(59, 4, 58, 'Papeterie', 'papeterie', 'Cahiers, blocs-notes, feuilles et papiers pour le bureau et l’école', NULL, 0, 1, '2026-02-16 11:31:07'),
(60, 4, 58, 'Stylos et instruments d’écriture', 'stylos-et-instruments-decriture', 'Stylos, crayons, feutres et marqueurs pour toutes vos écritures', NULL, 0, 1, '2026-02-16 11:31:07'),
(61, 4, 58, 'Fournitures pour dessin et arts', 'fournitures-pour-dessin-et-arts', 'Crayons de couleur, pinceaux, peintures et fournitures créatives', NULL, 0, 1, '2026-02-16 11:31:07'),
(62, 4, 58, 'Classeurs et rangements', 'classeurs-et-rangements', 'Classeurs, chemises et solutions de rangement pour documents', NULL, 0, 1, '2026-02-16 11:31:07'),
(63, 4, 58, 'Accessoires de bureau', 'accessoires-de-bureau', 'Agrafes, trombones, ciseaux, règles et petits accessoires utiles', NULL, 0, 1, '2026-02-16 11:31:07'),
(64, 4, 58, 'Informatique et fournitures tech', 'informatique-et-fournitures-tech', 'Cartouches d’encre, papiers spéciaux, clés USB et accessoires informatiques', NULL, 0, 1, '2026-02-16 11:31:07'),
(65, 4, 58, 'Sacs et cartables', 'sacs-et-cartables', 'Sacs d’école, cartables et accessoires de transport pour élèves et étudiants', NULL, 0, 1, '2026-02-16 11:31:07'),
(66, 4, 58, 'Adhésifs et rubans', 'adhesifs-et-rubans', 'Rubans adhésifs, correcteurs et scotch pour usage scolaire et bureau', NULL, 0, 1, '2026-02-16 11:31:08'),
(67, 4, 58, 'Calendriers et agendas', 'calendriers-et-agendas', 'Calendriers, planners et agendas pour organiser votre temps', NULL, 0, 1, '2026-02-16 11:31:08'),
(68, 4, 58, 'Étiquettes et fournitures d’identification', 'etiquettes-et-fournitures-didentification', 'Étiquettes, tampons et accessoires pour identifier documents et affaires', NULL, 0, 1, '2026-02-16 11:31:08'),
(69, 1, NULL, 'Sandales Artisanales', 'sandales-artisanales', 'Originale', NULL, 0, 1, '2026-02-18 12:17:29'),
(70, 11, NULL, 'Sports Wear', 'sports-wear', '', NULL, 1, 1, '2026-02-18 12:19:39'),
(72, 4, NULL, 'Livres pour enfants', 'livres-pour-enfants', 'BD, Histoires enfants', NULL, 0, 1, '2026-02-18 12:24:30'),
(73, 5, NULL, 'Impressions Couleurs', 'impressions-couleurs', '', NULL, 0, 1, '2026-02-18 12:27:12');

-- --------------------------------------------------------

--
-- Structure de la table `commande`
--

CREATE TABLE `commande` (
  `idCommande` int(10) UNSIGNED NOT NULL,
  `idBoutique` int(10) UNSIGNED NOT NULL COMMENT 'Boutique concernée',
  `idClient` int(10) UNSIGNED NOT NULL COMMENT 'Client (role=client)',
  `numeroCommande` varchar(50) NOT NULL COMMENT 'Numéro lisible unique par boutique',
  `sousTotal` decimal(12,2) NOT NULL COMMENT 'Total avant taxes/frais',
  `montantTaxe` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tauxTaxe` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Taux appliqué',
  `fraisLivraison` decimal(10,2) NOT NULL DEFAULT 0.00,
  `remise` decimal(10,2) NOT NULL DEFAULT 0.00,
  `codePromo` varchar(50) DEFAULT NULL,
  `total` decimal(12,2) NOT NULL COMMENT 'Total final',
  `devise` varchar(3) NOT NULL DEFAULT 'HTG',
  `statut` enum('en_attente','confirmee','payee','en_preparation','expediee','livree','annulee','remboursee') NOT NULL DEFAULT 'en_attente',
  `notesClient` text DEFAULT NULL,
  `notesInternes` text DEFAULT NULL COMMENT 'Visible seulement par le propriétaire',
  `dateCommande` datetime NOT NULL DEFAULT current_timestamp(),
  `dateConfirmation` datetime DEFAULT NULL,
  `dateModification` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Déchargement des données de la table `commande`
--

INSERT INTO `commande` (`idCommande`, `idBoutique`, `idClient`, `numeroCommande`, `sousTotal`, `montantTaxe`, `tauxTaxe`, `fraisLivraison`, `remise`, `codePromo`, `total`, `devise`, `statut`, `notesClient`, `notesInternes`, `dateCommande`, `dateConfirmation`, `dateModification`) VALUES
(1, 1, 9, 'CMD-20260222-CD450B', 1000.00, 0.00, 0.00, 0.00, 0.00, NULL, 1000.00, 'HTG', 'payee', NULL, 'Paiement: carte - ', '2026-02-18 08:45:28', NULL, '2026-02-23 04:11:36'),
(2, 1, 9, 'CMD-20260222-CD870B', 6029.99, 0.00, 0.00, 0.00, 0.00, NULL, 6029.99, 'HTG', 'payee', NULL, 'Paiement: moncash - 39950754', '2026-02-22 07:56:12', NULL, '2026-02-22 07:56:12'),
(3, 1, 9, 'CMD-20260222-1B3B07', 14797.00, 0.00, 0.00, 0.00, 0.00, NULL, 14797.00, 'HTG', 'payee', NULL, 'Paiement: natcash - 42155989', '2026-02-22 08:01:37', NULL, '2026-02-22 08:01:37'),
(4, 1, 9, 'CMD-20260222-F93542', 3049.99, 0.00, 0.00, 0.00, 0.00, NULL, 3049.99, 'HTG', 'payee', NULL, 'Paiement: carte - ', '2026-02-22 08:56:47', NULL, '2026-02-22 08:56:47'),
(5, 2, 9, 'CMD-20260222-7E3E05', 1299.00, 0.00, 0.00, 0.00, 0.00, NULL, 1299.00, 'HTG', 'payee', NULL, 'Paiement: moncash - 39950754', '2026-02-22 08:58:15', NULL, '2026-02-22 08:58:15'),
(6, 1, 19, 'CMD-20260222-BE83D4', 3199.00, 0.00, 0.00, 0.00, 0.00, NULL, 3199.00, 'HTG', 'payee', NULL, 'Paiement: moncash - 39950754', '2026-02-22 12:18:51', NULL, '2026-02-22 12:18:51'),
(7, 1, 19, 'CMD-20260222-5EA659', 359.88, 0.00, 0.00, 0.00, 0.00, NULL, 359.88, 'HTG', 'payee', NULL, 'Paiement: natcash - 42145989', '2026-02-22 12:23:17', NULL, '2026-02-22 12:23:17'),
(8, 2, 19, 'CMD-20260222-9E05E9', 1199.00, 0.00, 0.00, 0.00, 0.00, NULL, 1199.00, 'HTG', 'payee', NULL, 'Paiement: carte - ', '2026-02-22 13:12:09', NULL, '2026-02-22 13:12:09'),
(9, 1, 19, 'CMD-20260223-641BF8', 119.96, 0.00, 0.00, 0.00, 0.00, NULL, 119.96, 'HTG', 'payee', NULL, 'Paiement: carte - carte', '2026-02-23 04:10:30', NULL, '2026-02-23 04:10:30'),
(10, 1, 26, 'CMD-20260223-67D1F6', 3000.00, 0.00, 0.00, 0.00, 0.00, NULL, 3000.00, 'HTG', 'payee', NULL, 'Paiement: moncash - 39992015', '2026-02-23 18:53:42', NULL, '2026-02-23 18:53:42'),
(11, 1, 26, 'CMD-20260223-4101AB', 2000.00, 0.00, 0.00, 0.00, 0.00, NULL, 2000.00, 'HTG', 'payee', NULL, 'Paiement: moncash - 39950754', '2026-02-23 19:11:00', NULL, '2026-02-23 19:11:00');

--
-- Déclencheurs `commande`
--
DELIMITER $$
CREATE TRIGGER `trg_commande_after_update` AFTER UPDATE ON `commande` FOR EACH ROW BEGIN
    IF NEW.statut = 'annulee' AND OLD.statut != 'annulee' THEN
        UPDATE produit p
        INNER JOIN commande_produit cp ON p.idProduit = cp.idProduit
        SET p.stock = p.stock + cp.quantite,
            p.statutProduit = CASE
                WHEN (p.stock + cp.quantite) > 0 AND p.statutProduit = 'non_disponible'
                THEN 'disponible'
                ELSE p.statutProduit
            END
        WHERE cp.idCommande = NEW.idCommande;

        INSERT INTO audit_log (idBoutique, idUtilisateur, typeAction, action, tableConcernee, idEnregistrement, details)
        VALUES (
            NEW.idBoutique, NULL, 'UPDATE', 'Annulation commande - Stock restauré', 'commande', NEW.idCommande,
            JSON_OBJECT('ancienStatut', OLD.statut, 'nouveauStatut', NEW.statut)
        );
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `commande_produit`
--

CREATE TABLE `commande_produit` (
  `idCommande` int(10) UNSIGNED NOT NULL,
  `idProduit` int(10) UNSIGNED NOT NULL,
  `nomProduitSnapshot` varchar(255) NOT NULL COMMENT 'Nom du produit au moment de la commande',
  `quantite` int(10) UNSIGNED NOT NULL,
  `prixUnitaire` decimal(12,2) NOT NULL COMMENT 'Prix au moment de la commande',
  `remiseLigne` decimal(10,2) NOT NULL DEFAULT 0.00,
  `totalLigne` decimal(12,2) GENERATED ALWAYS AS (`quantite` * `prixUnitaire` - `remiseLigne`) STORED
) ;

--
-- Déchargement des données de la table `commande_produit`
--

INSERT INTO `commande_produit` (`idCommande`, `idProduit`, `nomProduitSnapshot`, `quantite`, `prixUnitaire`, `remiseLigne`) VALUES
(2, 1, 'Chemise Lin Blanche', 3, 2000.00, 0.00),
(2, 8, 'Chemise Casual Homme Manches Longues', 1, 29.99, 0.00),
(3, 3, 'Robe été Fleurie', 2, 2800.00, 0.00),
(3, 4, 'Sac à Main Cuir', 1, 5500.00, 0.00),
(3, 5, 'iPhone 15 Pro', 2, 1199.00, 0.00),
(3, 6, 'Samsung Galaxy S24', 1, 1299.00, 0.00),
(4, 2, 'Pantalon Chino Beige', 1, 3000.00, 0.00),
(4, 9, 'Pantalon Chino Homme Tissu Stretch', 1, 49.99, 0.00),
(5, 6, 'Samsung Galaxy S24', 1, 1299.00, 0.00),
(6, 1, 'Chemise Lin Blanche', 1, 2000.00, 0.00),
(6, 5, 'iPhone 15 Pro', 1, 1199.00, 0.00),
(7, 8, 'Chemise Casual Homme Manches Longues', 12, 29.99, 0.00),
(8, 5, 'iPhone 15 Pro', 1, 1199.00, 0.00),
(9, 8, 'Chemise Casual Homme Manches Longues', 4, 29.99, 0.00),
(10, 2, 'Pantalon Chino Beige', 1, 3000.00, 0.00),
(11, 1, 'Chemise Lin Blanche', 1, 2000.00, 0.00);

--
-- Déclencheurs `commande_produit`
--
DELIMITER $$
CREATE TRIGGER `trg_commande_produit_after_delete_total` AFTER DELETE ON `commande_produit` FOR EACH ROW BEGIN
    CALL sp_recalculer_total_commande(OLD.idCommande);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_commande_produit_after_insert` AFTER INSERT ON `commande_produit` FOR EACH ROW BEGIN
    DECLARE v_stock_actuel INT;
    DECLARE v_stock_alerte INT;
    DECLARE v_id_boutique INT UNSIGNED;

    SELECT stock, stockAlerte, idBoutique
    INTO v_stock_actuel, v_stock_alerte, v_id_boutique
    FROM produit
    WHERE idProduit = NEW.idProduit;

    UPDATE produit
    SET stock = stock - NEW.quantite,
        statutProduit = CASE
            WHEN (stock - NEW.quantite) <= 0 THEN 'non_disponible'
            ELSE statutProduit
        END
    WHERE idProduit = NEW.idProduit;

    IF (v_stock_actuel - NEW.quantite) <= v_stock_alerte AND (v_stock_actuel - NEW.quantite) > 0 THEN
        INSERT INTO audit_log (idBoutique, typeAction, action, tableConcernee, idEnregistrement, details)
        VALUES (
            v_id_boutique, 'AUTRE', 'ALERTE_STOCK_BAS', 'produit', NEW.idProduit,
            JSON_OBJECT('stockRestant', v_stock_actuel - NEW.quantite, 'stockAlerte', v_stock_alerte)
        );
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_commande_produit_after_insert_total` AFTER INSERT ON `commande_produit` FOR EACH ROW BEGIN
    CALL sp_recalculer_total_commande(NEW.idCommande);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_commande_produit_after_update_total` AFTER UPDATE ON `commande_produit` FOR EACH ROW BEGIN
    CALL sp_recalculer_total_commande(NEW.idCommande);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `image_produit`
--

CREATE TABLE `image_produit` (
  `idImage` int(10) UNSIGNED NOT NULL,
  `idProduit` int(10) UNSIGNED NOT NULL,
  `urlImage` varchar(500) NOT NULL,
  `urlThumbnail` varchar(500) DEFAULT NULL,
  `altText` varchar(255) DEFAULT NULL,
  `ordre` int(10) UNSIGNED DEFAULT 0,
  `estPrincipale` tinyint(1) NOT NULL DEFAULT 0,
  `dateAjout` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Images des produits';

--
-- Déchargement des données de la table `image_produit`
--

INSERT INTO `image_produit` (`idImage`, `idProduit`, `urlImage`, `urlThumbnail`, `altText`, `ordre`, `estPrincipale`, `dateAjout`) VALUES
(1, 1, '/assets/images/produits/lin.jpg', NULL, NULL, 0, 1, '2026-02-12 16:08:32'),
(2, 2, '/assets/images/produits/pantalon-chino-beige.jpg', NULL, NULL, 0, 1, '2026-02-12 16:08:32'),
(3, 3, '/assets/images/produits/robe-ete-fleurie-000.jpg', NULL, NULL, 0, 1, '2026-02-12 16:08:32'),
(4, 5, '/assets/images/produits/iphone-15-PRO-000.jpg', NULL, NULL, 0, 1, '2026-02-12 16:08:32'),
(5, 4, '/assets/images/produits/sac-a-main-cuir-pu.jpg', NULL, NULL, 0, 1, '2026-02-18 15:16:52'),
(6, 6, '/assets/images/produits/samsungalaxys24.png', NULL, NULL, 0, 1, '2026-02-18 15:19:19'),
(7, 7, '/assets/images/produits/mackbookpro14.jpg', NULL, NULL, 0, 1, '2026-02-18 15:27:56'),
(8, 8, '/assets/images/produits/chemise000.jpg', NULL, NULL, 0, 1, '2026-02-18 16:22:20'),
(9, 9, '/assets/images/produits/PantalonChinoHommeTissuStretch.png', NULL, NULL, 0, 1, '2026-02-19 20:10:10'),
(11, 28, '/assets/images/produits/chemise-casual-manches-longues.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(12, 29, '/assets/images/produits/chemise-slim-fit.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(13, 30, '/assets/images/produits/pantalon-chino-beige.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(14, 31, '/assets/images/produits/jean-slim-noir.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(15, 32, '/assets/images/produits/tshirt-col-rond-uni.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(16, 33, '/assets/images/produits/tshirt-imprime.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(17, 34, '/assets/images/produits/polo-classique.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(18, 35, '/assets/images/produits/veste-jeans-homme.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(19, 36, '/assets/images/produits/short-de-sport.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(20, 37, '/assets/images/produits/blouson-aviateur.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(21, 38, '/assets/images/produits/robe-fleurie.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(22, 39, '/assets/images/produits/robe-de-soiree-longue.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(23, 40, '/assets/images/produits/top-en-dentelle.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(24, 41, '/assets/images/produits/tshirt-basique-femme.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(25, 42, '/assets/images/produits/pantalon-cigarette.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(26, 43, '/assets/images/produits/pantalon-large-palazzo.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(27, 44, '/assets/images/produits/jupe-plissee.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(28, 45, '/assets/images/produits/veste-blazer-femme.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(29, 46, '/assets/images/produits/pull-en-maille.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(30, 47, '/assets/images/produits/combinaison-pantalon.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(31, 48, '/assets/images/produits/ecouteurs-airpods-pro-2.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(32, 49, '/assets/images/produits/samsung-galaxy-watch-7.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(33, 50, '/assets/images/produits/chargeur-sans-fil-3en1.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(34, 51, '/assets/images/produits/clavier-mecanique-gaming.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(35, 52, '/assets/images/produits/souris-logitech-mx.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(36, 53, '/assets/images/produits/iphone-15-pro-max.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(37, 54, '/assets/images/produits/samsung-galaxy-s25-ultra.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(38, 55, '/assets/images/produits/google-pixel-9-pro.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(39, 56, '/assets/images/produits/xiaomi-14-ultra.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(40, 57, '/assets/images/produits/oneplus-12.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(41, 58, '/assets/images/produits/macbook-pro-16-m4.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(42, 59, '/assets/images/produits/dell-xps-15.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(43, 60, '/assets/images/produits/asus-rog-strix.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(44, 61, '/assets/images/produits/hp-spectre-x360.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(45, 62, '/assets/images/produits/microsoft-surface-laptop-7.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(46, 63, '/assets/images/produits/refrigerateur-samsung-320l.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(47, 64, '/assets/images/produits/refrigerateur-lg-280l.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(48, 65, '/assets/images/produits/refrigerateur-hisense-400l.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(49, 66, '/assets/images/produits/refrigerateur-beko-250l.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(50, 67, '/assets/images/produits/congelateur-armoire-200l.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(51, 68, '/assets/images/produits/refrigerateur-americain-600l.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(52, 69, '/assets/images/produits/micro-ondes-samsung-23l.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(53, 70, '/assets/images/produits/micro-ondes-lg-20l.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(54, 71, '/assets/images/produits/micro-ondes-panasonic-27l.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(55, 72, '/assets/images/produits/micro-ondes-whirlpool-25l.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(56, 73, '/assets/images/produits/micro-ondes-compact-17l.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(57, 74, '/assets/images/produits/micro-ondes-sharp-30l.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(58, 75, '/assets/images/produits/aspirateur-dyson-v15-detect.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(59, 76, '/assets/images/produits/aspirateur-philips-powerpro.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(60, 77, '/assets/images/produits/aspirateur-robot-xiaomi-s20.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(61, 78, '/assets/images/produits/aspirateur-sans-fil-rowenta.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(62, 79, '/assets/images/produits/aspirateur-bosch-series-8.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57'),
(63, 80, '/assets/images/produits/aspirateur-karcher-eau.jpg', NULL, NULL, 0, 1, '2026-02-24 06:33:57');

-- --------------------------------------------------------

--
-- Structure de la table `livraison`
--

CREATE TABLE `livraison` (
  `idLivraison` int(10) UNSIGNED NOT NULL,
  `idCommande` int(10) UNSIGNED NOT NULL,
  `idBoutique` int(10) UNSIGNED NOT NULL,
  `idAdresseLivraison` int(10) UNSIGNED NOT NULL,
  `methodeLivraison` enum('standard','express','retrait','coursier') NOT NULL DEFAULT 'standard',
  `transporteur` varchar(100) DEFAULT NULL,
  `numeroSuivi` varchar(100) DEFAULT NULL,
  `lienSuivi` varchar(500) DEFAULT NULL,
  `poidsTotal` decimal(8,3) DEFAULT NULL,
  `statutLivraison` enum('en_attente','en_preparation','prete','expediee','en_transit','livree','echec','retournee') NOT NULL DEFAULT 'en_attente',
  `dateLivraisonPrevue` date DEFAULT NULL,
  `dateLivraisonReelle` datetime DEFAULT NULL,
  `instructionsLivraison` text DEFAULT NULL,
  `notesLivreur` text DEFAULT NULL,
  `dateCreation` datetime NOT NULL DEFAULT current_timestamp(),
  `dateModification` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Livraisons des commandes';

--
-- Déclencheurs `livraison`
--
DELIMITER $$
CREATE TRIGGER `trg_livraison_after_update` AFTER UPDATE ON `livraison` FOR EACH ROW BEGIN
    IF NEW.statutLivraison = 'livree' AND OLD.statutLivraison != 'livree' THEN
        UPDATE commande SET statut = 'livree' WHERE idCommande = NEW.idCommande;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_livraison_before_update` BEFORE UPDATE ON `livraison` FOR EACH ROW BEGIN
    IF NEW.statutLivraison = 'livree' AND OLD.statutLivraison != 'livree' THEN
        SET NEW.dateLivraisonReelle = CURRENT_TIMESTAMP;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `paiement`
--

CREATE TABLE `paiement` (
  `idPaiement` int(10) UNSIGNED NOT NULL,
  `idCommande` int(10) UNSIGNED NOT NULL,
  `idBoutique` int(10) UNSIGNED NOT NULL,
  `modePaiement` enum('moncash','natcash','debit','credit') NOT NULL,
  `montant` decimal(12,2) NOT NULL,
  `devise` varchar(3) NOT NULL DEFAULT 'HTG',
  `referenceExterne` varchar(100) DEFAULT NULL COMMENT 'ID transaction provider',
  `statutPaiement` enum('en_attente','valide','refuse','annule','rembourse') NOT NULL DEFAULT 'en_attente',
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Infos supplémentaires du provider' CHECK (json_valid(`details`)),
  `messageErreur` varchar(500) DEFAULT NULL,
  `datePaiement` datetime DEFAULT NULL COMMENT 'Date de validation',
  `dateCreation` datetime NOT NULL DEFAULT current_timestamp()
) ;

--
-- Déchargement des données de la table `paiement`
--

INSERT INTO `paiement` (`idPaiement`, `idCommande`, `idBoutique`, `modePaiement`, `montant`, `devise`, `referenceExterne`, `statutPaiement`, `details`, `messageErreur`, `datePaiement`, `dateCreation`) VALUES
(1, 2, 1, 'moncash', 6029.99, 'HTG', 'SIMU1771764972', 'valide', '{\"numero\":\"39950754\",\"code_otp\":\"123456\"}', NULL, '2026-02-22 07:56:12', '2026-02-22 07:56:12'),
(2, 3, 1, 'natcash', 14797.00, 'HTG', 'SIMU1771765297', 'valide', '{\"numero\":\"42155989\",\"code_otp\":\"123456\"}', NULL, '2026-02-22 08:01:37', '2026-02-22 08:01:37'),
(3, 4, 1, '', 3049.99, 'HTG', 'SIMU1771768607', 'valide', '{\"numero\":\"\",\"code_otp\":\"123456\"}', NULL, '2026-02-22 08:56:47', '2026-02-22 08:56:47'),
(4, 5, 2, 'moncash', 1299.00, 'HTG', 'SIMU1771768695', 'valide', '{\"numero\":\"39950754\",\"code_otp\":\"123456\"}', NULL, '2026-02-22 08:58:15', '2026-02-22 08:58:15'),
(5, 6, 1, 'moncash', 3199.00, 'HTG', 'SIMU1771780731', 'valide', '{\"numero\":\"39950754\",\"code_otp\":\"999999\"}', NULL, '2026-02-22 12:18:51', '2026-02-22 12:18:51'),
(6, 7, 1, 'natcash', 359.88, 'HTG', 'SIMU1771780997', 'valide', '{\"numero\":\"42145989\",\"code_otp\":\"000000\"}', NULL, '2026-02-22 12:23:17', '2026-02-22 12:23:17'),
(7, 8, 2, '', 1199.00, 'HTG', 'SIMU1771783929', 'valide', '{\"numero\":\"\",\"code_otp\":\"871209\"}', NULL, '2026-02-22 13:12:09', '2026-02-22 13:12:09'),
(8, 9, 1, 'credit', 119.96, 'HTG', 'SIMU1771837830', 'valide', '{\"numero_carte\":\"3456\",\"exp_mois\":\"01\",\"exp_annee\":\"26\",\"nom_titulaire\":\"PK7 Goat\",\"code_otp\":\"123456\"}', NULL, '2026-02-23 04:10:30', '2026-02-23 04:10:30'),
(9, 10, 1, 'moncash', 3000.00, 'HTG', 'SIMU1771890822', 'valide', '{\"numero\":\"39992015\",\"code_otp\":\"242424\"}', NULL, '2026-02-23 18:53:42', '2026-02-23 18:53:42'),
(10, 11, 1, 'moncash', 2000.00, 'HTG', 'SIMU1771891860', 'valide', '{\"numero\":\"39950754\",\"code_otp\":\"123457\"}', NULL, '2026-02-23 19:11:00', '2026-02-23 19:11:00');

--
-- Déclencheurs `paiement`
--
DELIMITER $$
CREATE TRIGGER `trg_paiement_after_insert` AFTER INSERT ON `paiement` FOR EACH ROW BEGIN
    IF NEW.statutPaiement = 'valide' THEN
        CALL sp_verifier_paiement_complet(NEW.idCommande);
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_paiement_after_update` AFTER UPDATE ON `paiement` FOR EACH ROW BEGIN
    IF NEW.statutPaiement = 'valide' AND OLD.statutPaiement != 'valide' THEN
        UPDATE paiement SET datePaiement = CURRENT_TIMESTAMP WHERE idPaiement = NEW.idPaiement;
        CALL sp_verifier_paiement_complet(NEW.idCommande);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `panier`
--

CREATE TABLE `panier` (
  `idPanier` int(10) UNSIGNED NOT NULL,
  `idUtilisateur` int(10) UNSIGNED NOT NULL COMMENT 'Client (role=client)',
  `idBoutique` int(10) UNSIGNED NOT NULL COMMENT 'Boutique concernée',
  `dateCreation` datetime NOT NULL DEFAULT current_timestamp(),
  `dateModification` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Paniers d''achat (1 par client par boutique)';

--
-- Déchargement des données de la table `panier`
--

INSERT INTO `panier` (`idPanier`, `idUtilisateur`, `idBoutique`, `dateCreation`, `dateModification`) VALUES
(1, 6, 1, '2026-02-12 16:08:32', '2026-02-12 16:08:32'),
(2, 6, 2, '2026-02-12 16:08:32', '2026-02-12 16:08:32'),
(3, 7, 1, '2026-02-12 16:08:32', '2026-02-12 16:08:32'),
(4, 9, 1, '2026-02-22 05:23:22', '2026-02-22 05:23:22'),
(5, 19, 1, '2026-02-22 12:17:14', '2026-02-22 12:17:14'),
(6, 3, 1, '2026-02-23 12:32:10', '2026-02-23 12:32:10'),
(7, 26, 1, '2026-02-23 18:50:21', '2026-02-23 18:50:21');

-- --------------------------------------------------------

--
-- Structure de la table `panier_produit`
--

CREATE TABLE `panier_produit` (
  `idPanier` int(10) UNSIGNED NOT NULL,
  `idProduit` int(10) UNSIGNED NOT NULL,
  `quantite` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `prixAuMoment` decimal(12,2) NOT NULL COMMENT 'Prix capturé lors de l''ajout',
  `dateAjout` datetime NOT NULL DEFAULT current_timestamp()
) ;

--
-- Déchargement des données de la table `panier_produit`
--

INSERT INTO `panier_produit` (`idPanier`, `idProduit`, `quantite`, `prixAuMoment`, `dateAjout`) VALUES
(1, 1, 2, 2000.00, '2026-02-12 16:08:32'),
(1, 3, 1, 2800.00, '2026-02-12 16:08:32'),
(2, 5, 1, 1199.00, '2026-02-12 16:08:32'),
(3, 4, 1, 5500.00, '2026-02-12 16:08:32'),
(6, 2, 1, 3000.00, '2026-02-23 12:32:10');

-- --------------------------------------------------------

--
-- Structure de la table `parametre_boutique`
--

CREATE TABLE `parametre_boutique` (
  `idBoutique` int(10) UNSIGNED NOT NULL,
  `devise` varchar(3) NOT NULL DEFAULT 'HTG' COMMENT 'Gourde haïtienne par défaut',
  `taxe` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Taux de taxe en %',
  `logo` varchar(500) DEFAULT NULL,
  `favicon` varchar(500) DEFAULT NULL,
  `banniere` varchar(500) DEFAULT NULL,
  `descriptionBoutique` text DEFAULT NULL,
  `couleurPrimaire` varchar(7) DEFAULT '#007bff',
  `couleurSecondaire` varchar(7) DEFAULT '#6c757d',
  `emailContact` varchar(255) DEFAULT NULL,
  `telephoneContact` varchar(20) DEFAULT NULL,
  `adressePhysique` text DEFAULT NULL,
  `reseauxSociaux` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '{"facebook":"...", "instagram":"..."}' CHECK (json_valid(`reseauxSociaux`)),
  `politiqueRetour` text DEFAULT NULL,
  `conditionsVente` text DEFAULT NULL,
  `dateModification` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Déchargement des données de la table `parametre_boutique`
--

INSERT INTO `parametre_boutique` (`idBoutique`, `devise`, `taxe`, `logo`, `favicon`, `banniere`, `descriptionBoutique`, `couleurPrimaire`, `couleurSecondaire`, `emailContact`, `telephoneContact`, `adressePhysique`, `reseauxSociaux`, `politiqueRetour`, `conditionsVente`, `dateModification`) VALUES
(1, 'HTG', 10.00, '/assets/images/modehaiti.png', NULL, '/assets/images/shops/banner_1_1771615192.jpg', NULL, '#007bff', '#6c757d', 'contact@modehaiti.ht', '+509 3456 7890', '108,  Avenue Panamericaine, Petion-Ville, Haiti', '{\"facebook\":\"https:\\/\\/facebook.com\\/modehaiti509\",\"instagram\":\"https:\\/\\/instagram.com\\/modehaiti509\",\"twitter\":\"https:\\/\\/x.com\\/modehaiti509\",\"whatsapp\":\"+509 34 43 5566\"}', NULL, NULL, '2026-02-22 13:05:15'),
(2, 'HTG', 8.00, '/assets/images/techstorehaiti.png', NULL, NULL, NULL, '#007bff', '#6c757d', 'contact@techstore.ht', NULL, NULL, NULL, NULL, NULL, '2026-02-22 15:50:46'),
(3, 'HTG', 10.00, '/assets/images/epiceriefine.png', NULL, NULL, NULL, '#007bff', '#6c757d', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-12 17:59:56'),
(4, 'HTG', 10.00, '/assets/images/henrydeschamps.png', NULL, NULL, NULL, '#007bff', '#6c757d', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-16 18:04:24'),
(5, 'HTG', 10.00, '/assets/images/duomultiservices.png', NULL, NULL, NULL, '#007bff', '#6c757d', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-16 18:10:15'),
(6, 'HTG', 10.00, '/assets/images/rikado.png', NULL, NULL, NULL, '#007bff', '#6c757d', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-16 18:00:43'),
(7, 'HTG', 10.00, '/assets/images/bozquincaille.png', NULL, NULL, NULL, '#007bff', '#6c757d', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-16 18:02:15'),
(8, 'HTG', 10.00, '/assets/images/tibaldeboiserie.png', NULL, NULL, NULL, '#007bff', '#6c757d', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-16 18:13:09'),
(9, 'HTG', 10.00, '/assets/images/carlsoundselections.png', NULL, NULL, NULL, '#007bff', '#6c757d', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-16 18:02:46'),
(10, 'HTG', 10.00, '/assets/images/maamouperles.png', NULL, NULL, NULL, '#007bff', '#6c757d', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-16 18:14:08'),
(11, 'HTG', 10.00, '/assets/images/oldpayastore.png', NULL, NULL, NULL, '#007bff', '#6c757d', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-16 17:37:26'),
(15, 'HTG', 10.00, '/assets/images/fantasyofcassy.png', NULL, NULL, NULL, '#007bff', '#6c757d', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-22 15:51:20'),
(16, 'HTG', 10.00, '/assets/images/feedaccessories.png', NULL, NULL, NULL, '#007bff', '#6c757d', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-22 15:51:42'),
(17, 'HTG', 10.00, '/assets/images/liverpoolstore.jpg', NULL, NULL, NULL, '#007bff', '#6c757d', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-22 15:59:05'),
(18, 'HTG', 10.00, '/assets/images/oli-solutions.png', NULL, NULL, NULL, '#007bff', '#6c757d', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-22 15:53:20');

-- --------------------------------------------------------

--
-- Structure de la table `produit`
--

CREATE TABLE `produit` (
  `idProduit` int(10) UNSIGNED NOT NULL,
  `idBoutique` int(10) UNSIGNED NOT NULL,
  `idCategorie` int(10) UNSIGNED DEFAULT NULL,
  `nomProduit` varchar(255) NOT NULL,
  `slugProduit` varchar(255) NOT NULL,
  `descriptionCourte` varchar(500) DEFAULT NULL,
  `descriptionComplete` text DEFAULT NULL,
  `prix` decimal(12,2) NOT NULL,
  `prixPromo` decimal(12,2) DEFAULT NULL,
  `dateDebutPromo` date DEFAULT NULL,
  `dateFinPromo` date DEFAULT NULL,
  `cout` decimal(12,2) DEFAULT NULL COMMENT 'Coût d''achat pour calcul marge',
  `stock` int(11) NOT NULL DEFAULT 0,
  `stockAlerte` int(11) DEFAULT 10 COMMENT 'Seuil alerte stock bas',
  `sku` varchar(100) DEFAULT NULL COMMENT 'Stock Keeping Unit',
  `codeBarres` varchar(50) DEFAULT NULL,
  `poids` decimal(8,3) DEFAULT NULL COMMENT 'En kg',
  `dimensions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '{"longueur":x, "largeur":y, "hauteur":z}' CHECK (json_valid(`dimensions`)),
  `statutProduit` enum('brouillon','disponible','non_disponible','archive') NOT NULL DEFAULT 'brouillon',
  `misEnAvant` tinyint(1) NOT NULL DEFAULT 0,
  `nouveaute` tinyint(1) NOT NULL DEFAULT 0,
  `dateAjout` datetime NOT NULL DEFAULT current_timestamp(),
  `dateModification` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Déchargement des données de la table `produit`
--

INSERT INTO `produit` (`idProduit`, `idBoutique`, `idCategorie`, `nomProduit`, `slugProduit`, `descriptionCourte`, `descriptionComplete`, `prix`, `prixPromo`, `dateDebutPromo`, `dateFinPromo`, `cout`, `stock`, `stockAlerte`, `sku`, `codeBarres`, `poids`, `dimensions`, `statutProduit`, `misEnAvant`, `nouveaute`, `dateAjout`, `dateModification`) VALUES
(1, 1, 1, 'Chemise Lin Blanche', 'chemise-lin-blanche', 'Chemise en lin premium', 'Chemise Originale pour homme', 2500.00, 2000.00, NULL, NULL, NULL, 40, 10, 'CHM-LIN-BLC', NULL, NULL, NULL, 'disponible', 1, 0, '2026-02-12 16:08:32', '2026-02-24 12:11:35'),
(2, 1, 1, 'Pantalon Chino Beige', 'pantalon-chino-beige', 'Pantalon chino coupe droite', 'Pantalon Resan', 3000.00, NULL, NULL, NULL, NULL, 26, 10, 'PNT-CHI-BGE', NULL, NULL, NULL, 'disponible', 1, 0, '2026-02-12 16:08:32', '2026-02-24 12:11:47'),
(3, 1, 2, 'Robe été Fleurie', 'robe-ete-fleurie', 'Robe légère motif floral', 'Robe byen klasik', 3500.00, 2800.00, NULL, NULL, NULL, 21, 10, 'ROB-ETE-FLR', NULL, NULL, NULL, 'disponible', 1, 0, '2026-02-12 16:08:32', '2026-02-24 12:12:00'),
(4, 1, 8, 'Sac à Main Cuir', 'sac-main-cuir', 'Sac en cuir véritable', 'Sac original', 5500.00, NULL, NULL, NULL, NULL, 18, 10, 'SAC-CUI-NOR', NULL, NULL, NULL, 'disponible', 0, 0, '2026-02-12 16:08:32', '2026-02-24 12:12:10'),
(5, 2, 4, 'iPhone 15 Pro', 'iphone-15-pro', 'Apple iPhone 15 Pro 256GB', 'Iphone avec batterie 100%', 1199.00, NULL, NULL, NULL, NULL, 2, 10, 'IPH-15P-256', NULL, NULL, NULL, 'disponible', 0, 0, '2026-02-12 16:08:32', '2026-02-24 12:12:27'),
(6, 2, 4, 'Samsung Galaxy S24', 'samsung-galaxy-s24', 'Samsung Galaxy S24 Ultra', 'Samsung venant tout droit de Samsung', 1299.00, NULL, NULL, NULL, NULL, 4, 10, 'SAM-S24U', NULL, NULL, NULL, 'disponible', 0, 0, '2026-02-12 16:08:32', '2026-02-24 12:12:54'),
(7, 2, 5, 'MacBook Pro 14\"', 'macbook-pro-14', 'Apple MacBook Pro M3', 'Mackbook Pro', 1999.00, NULL, NULL, NULL, NULL, 20, 10, 'MBP-14-M3', NULL, NULL, NULL, 'disponible', 0, 0, '2026-02-12 16:08:32', '2026-02-24 12:13:06'),
(8, 1, 1, 'Chemise Casual Homme Manches Longues', 'chemise-casual-homme-manches-longues', 'Chemise décontractée en coton, coupe classique.', 'Chemise pour homme en pur coton, idéale pour un look casual ou bureau. Tissu respirant, entretien facile, disponible en plusieurs coloris. Col classique, poignets ajustables.', 39.99, 29.99, '2025-04-01', '2025-05-01', 18.50, 1, 5, 'CHM001-BL', '3761234567890', 0.350, '{\"longueur\":30,\"largeur\":25,\"hauteur\":3}', 'disponible', 1, 1, '2026-02-18 16:19:59', '2026-02-23 04:10:30'),
(9, 1, 1, 'Pantalon Chino Homme Tissu Stretch', 'pantalon-chino-homme-tissu-stretch', 'Pantalon chino slim avec stretch pour plus de confort', 'Pantalon chino coupe slim, idéal pour un look moderne et élégant. Tissu extensible qui épouse parfaitement vos mouvements. Deux poches latérales et deux poches arrière. Couleur beige et bleu marine disponibles.', 59.99, 49.99, '2025-04-15', '2025-05-15', 32.00, 18, 3, 'PAN002-BE', '3769876543210', 0.450, '{\"longueur\":35,\"largeur\":30,\"hauteur\":5}', 'disponible', 0, 1, '2026-02-18 16:19:59', '2026-02-22 12:59:24'),
(28, 1, 1, 'Chemise casual manches longues', 'chemise-casual-homme', 'Chemise décontractée en coton, idéale pour le quotidien', 'Chemise homme en pur coton, coupe classique. Disponible en bleu, blanc et gris. Lavage en machine à 30°C.', 4500.00, 3800.00, '2025-03-01', '2025-04-01', 1800.00, 40, 5, 'VH-CHE-001', '3761234568001', 0.350, NULL, 'disponible', 1, 1, '2026-02-24 06:03:45', '2026-02-24 06:03:45'),
(29, 1, 1, 'Chemise slim fit', 'chemise-slim-fit-homme', 'Chemise coupe slim, moderne et élégante', 'Chemise homme slim fit en coton stretch. Idéale pour le bureau ou les soirées. Coloris: bleu marine, blanc, noir.', 5500.00, NULL, NULL, NULL, 2200.00, 40, 5, 'VH-CHE-002', '3761234568002', 0.350, NULL, 'disponible', 1, 0, '2026-02-24 06:03:45', '2026-02-24 06:03:45'),
(30, 1, 1, 'Pantalon chino beige', 'pantalon-chino-beige-homme', 'Pantalon chino coupe droite, intemporel', 'Pantalon chino pour homme, coupe droite. Tissu confortable, 2 poches latérales et 2 poches arrière. Idéal pour un look casual chic.', 6500.00, 5500.00, '2025-03-15', '2025-04-15', 2600.00, 40, 5, 'VH-PAN-001', '3761234568003', 0.450, NULL, 'disponible', 0, 1, '2026-02-24 06:03:45', '2026-02-24 06:03:45'),
(31, 1, 1, 'Jean slim noir', 'jean-slim-noir-homme', 'Jean slim noir stretch, confortable et tendance', 'Jean homme coupe slim, couleur noire. Tissu stretch pour un confort optimal. 5 poches, fermeture zippée.', 7500.00, 6500.00, '2025-04-01', '2025-05-01', 3000.00, 40, 5, 'VH-JEA-001', '3761234568004', 0.600, NULL, 'disponible', 1, 0, '2026-02-24 06:03:45', '2026-02-24 06:03:45'),
(32, 1, 1, 'T-shirt col rond uni', 'tshirt-col-rond-homme', 'T-shirt basique en coton, pack de 3', 'Lot de 3 t-shirts homme col rond en coton bio. Disponible en noir, blanc et gris. Coupe classique, confortable.', 3500.00, 2900.00, '2025-03-01', '2025-04-01', 1400.00, 40, 5, 'VH-TSH-001', '3761234568005', 0.500, NULL, 'disponible', 0, 1, '2026-02-24 06:03:45', '2026-02-24 06:03:45'),
(33, 1, 1, 'T-shirt imprimé', 'tshirt-imprime-homme', 'T-shirt avec motif graphique tendance', 'T-shirt homme en coton, motif graphique original. Col rond, manches courtes. Idéal pour un look décontracté.', 2800.00, NULL, NULL, NULL, 1100.00, 40, 5, 'VH-TSH-002', '3761234568006', 0.250, NULL, 'disponible', 0, 0, '2026-02-24 06:03:45', '2026-02-24 06:03:45'),
(34, 1, 1, 'Polo classique', 'polo-classique-homme', 'Polo en coton piqué, coupe regular', 'Polo homme en coton piqué de qualité. Col et poignets côtelés. Fermeture par 2 boutons. Coloris: marine, blanc, rouge.', 5200.00, 4500.00, '2025-04-15', '2025-05-15', 2100.00, 40, 5, 'VH-POL-001', '3761234568007', 0.300, NULL, 'disponible', 1, 1, '2026-02-24 06:03:45', '2026-02-24 06:03:45'),
(35, 1, 1, 'Veste jeans homme', 'veste-jeans-homme', 'Veste en jean, style américain', 'Veste homme en jean, coupe classique. Col chemise, fermeture boutonnée. 2 poches poitrine. Coloris bleu clair.', 12000.00, 9900.00, '2025-03-01', '2025-04-01', 4800.00, 40, 5, 'VH-VES-001', '3761234568008', 0.900, NULL, 'disponible', 0, 0, '2026-02-24 06:03:45', '2026-02-24 06:03:45'),
(36, 1, 1, 'Short de sport', 'short-sport-homme', 'Short de sport en tissu respirant', 'Short homme pour le sport ou la détente. Tissu léger et respirant, taille élastique avec cordon. 2 poches latérales.', 2800.00, 2200.00, '2025-05-01', '2025-06-01', 1100.00, 40, 5, 'VH-SHO-001', '3761234568009', 0.200, NULL, 'disponible', 0, 1, '2026-02-24 06:03:45', '2026-02-24 06:03:45'),
(37, 1, 1, 'Blouson aviateur', 'blouson-aviateur-homme', 'Blouson style aviateur en simili cuir', 'Blouson homme style aviateur, en simili cuir. Doublure chaude, col en fourrure synthétique. Fermeture zippée.', 18500.00, 15900.00, '2025-02-01', '2025-03-31', 7400.00, 40, 5, 'VH-BLO-001', '3761234568010', 1.200, NULL, 'disponible', 1, 0, '2026-02-24 06:03:45', '2026-02-24 06:03:45'),
(38, 1, 2, 'Robe fleurie', 'robe-fleurie-femme', 'Robe légère à motif floral, idéale pour l\'été', 'Robe femme en viscose, imprimé floral. Coupe évasée, manches courtes. Parfaite pour les journées ensoleillées.', 6500.00, 5500.00, '2025-03-01', '2025-04-01', 2600.00, 40, 5, 'VF-ROB-001', '3761234568011', 0.250, NULL, 'disponible', 1, 1, '2026-02-24 06:04:08', '2026-02-24 06:04:08'),
(39, 1, 2, 'Robe de soirée longue', 'robe-soiree-longue', 'Robe longue élégante pour les occasions spéciales', 'Robe de soirée longue, tissu satiné. Dos nu, bretelles fines. Disponible en noir, rouge et bleu nuit.', 12500.00, 10900.00, '2025-03-15', '2025-04-15', 5000.00, 40, 5, 'VF-ROB-002', '3761234568012', 0.400, NULL, 'disponible', 1, 0, '2026-02-24 06:04:08', '2026-02-24 06:04:08'),
(40, 1, 2, 'Top en dentelle', 'top-dentelle-femme', 'Top délicat en dentelle, doublé', 'Top femme en dentelle élastique, doublé. Manches courtes, encolure ronde. Idéal pour un look romantique.', 3800.00, NULL, NULL, NULL, 1500.00, 40, 5, 'VF-TOP-001', '3761234568013', 0.150, NULL, 'disponible', 0, 1, '2026-02-24 06:04:08', '2026-02-24 06:04:08'),
(41, 1, 2, 'T-shirt basique femme', 'tshirt-basique-femme', 'T-shirt coupe ajustée, manches courtes', 'T-shirt femme coupe ajustée, en coton doux. Disponible en plusieurs coloris. Idéal pour le quotidien.', 2200.00, 1800.00, '2025-04-01', '2025-05-01', 900.00, 40, 5, 'VF-TSH-001', '3761234568014', 0.150, NULL, 'disponible', 0, 0, '2026-02-24 06:04:08', '2026-02-24 06:04:08'),
(42, 1, 2, 'Pantalon cigarette', 'pantalon-cigarette-femme', 'Pantalon coupe cigarette, intemporel', 'Pantalon femme coupe cigarette, taille haute. Tissu stretch, fermeture zippée. Parfait pour le bureau.', 7200.00, 6200.00, '2025-04-15', '2025-05-15', 2900.00, 40, 5, 'VF-PAN-001', '3761234568015', 0.350, NULL, 'disponible', 1, 1, '2026-02-24 06:04:08', '2026-02-24 06:04:08'),
(43, 1, 2, 'Pantalon large palazzo', 'pantalon-palazzo-femme', 'Pantalon large et fluide, tendance', 'Pantalon femme large coupe palazzo, en tissu fluide. Taille élastique, idéal pour un look chic et confortable.', 6800.00, NULL, NULL, NULL, 2700.00, 40, 5, 'VF-PAN-002', '3761234568016', 0.300, NULL, 'disponible', 0, 1, '2026-02-24 06:04:08', '2026-02-24 06:04:08'),
(44, 1, 2, 'Jupe plissée', 'jupe-plissee-femme', 'Jupe plissée courte, style rétro', 'Jupe plissée taille haute, longueur au-dessus du genou. Tissu léger, parfaite pour un look girly.', 4200.00, 3500.00, '2025-03-01', '2025-04-01', 1700.00, 40, 5, 'VF-JUP-001', '3761234568017', 0.200, NULL, 'disponible', 0, 0, '2026-02-24 06:04:08', '2026-02-24 06:04:08'),
(45, 1, 2, 'Veste blazer femme', 'veste-blazer-femme', 'Blazer élégant coupe cintrée', 'Blazer femme coupe cintrée, 2 boutons. Tissu stretch, poches latérales. Idéal pour le travail ou les sorties.', 13500.00, 11500.00, '2025-04-01', '2025-05-01', 5400.00, 40, 5, 'VF-VES-001', '3761234568018', 0.500, NULL, 'disponible', 1, 0, '2026-02-24 06:04:08', '2026-02-24 06:04:08'),
(46, 1, 2, 'Pull en maille', 'pull-maille-femme', 'Pull doux en maille, col V', 'Pull femme en maille de coton, col V. Manches longues, coupe confortable. Disponible en plusieurs couleurs.', 5500.00, 4800.00, '2025-02-15', '2025-03-31', 2200.00, 40, 5, 'VF-PUL-001', '3761234568019', 0.400, NULL, 'disponible', 0, 1, '2026-02-24 06:04:08', '2026-02-24 06:04:08'),
(47, 1, 2, 'Combinaison pantalon', 'combinaison-pantalon-femme', 'Combinaison élégante, parfaire pour les soirées', 'Combinaison femme, pantalon large, bustier. Tissu satiné, fermeture dos. Idéale pour les occasions spéciales.', 14500.00, 12900.00, '2025-03-15', '2025-04-15', 5800.00, 40, 5, 'VF-COM-001', '3761234568020', 0.450, NULL, 'disponible', 1, 1, '2026-02-24 06:04:08', '2026-02-24 06:04:08'),
(48, 2, 6, 'Écouteurs AirPods Pro 2', 'airpods-pro-2', 'Apple AirPods Pro 2, réduction de bruit active', 'Écouteurs sans fil Apple avec réduction active du bruit, boîtier de charge MagSafe, audio spatial personnalisé. Autonomie 30h.', 299.00, 279.00, '2026-03-01', '2026-04-01', 180.00, 40, 5, 'ACC-APP-001', '3761234568301', 0.050, '{\"longueur\":4.5, \"largeur\":5.0, \"hauteur\":2.0}', 'disponible', 1, 1, '2026-02-24 06:12:29', '2026-02-24 06:12:29'),
(49, 2, 6, 'Samsung Galaxy Watch 7', 'galaxy-watch-7', 'Montre connectée Samsung Galaxy Watch 7', 'Montre connectée Samsung avec suivi santé avancé, GPS, écran AMOLED, compatible Android. Autonomie 40h.', 399.00, 359.00, '2026-03-15', '2026-04-15', 240.00, 40, 5, 'ACC-SAM-002', '3761234568302', 0.060, '{\"longueur\":4.4, \"largeur\":4.4, \"hauteur\":1.2}', 'disponible', 1, 1, '2026-02-24 06:12:29', '2026-02-24 06:12:29'),
(50, 2, 6, 'Chargeur sans fil 3-en-1', 'chargeur-sans-fil-3en1', 'Station de charge sans fil pour Apple', 'Chargeur 3-en-1 compatible iPhone, Apple Watch et AirPods. Charge rapide, design compact et élégant.', 79.00, 69.00, '2026-04-01', '2026-05-01', 35.00, 40, 5, 'ACC-CHA-003', '3761234568303', 0.200, '{\"longueur\":18.0, \"largeur\":10.0, \"hauteur\":2.0}', 'disponible', 0, 1, '2026-02-24 06:12:29', '2026-02-24 06:12:29'),
(51, 2, 6, 'Clavier mécanique gaming', 'clavier-mecanique-gaming', 'Clavier mécanique RGB pour gamers', 'Clavier gaming mécanique avec switches bleus, rétroéclairage RGB personnalisable, repose-poignet. Idéal pour les joueurs.', 129.00, 99.00, '2026-03-01', '2026-04-01', 60.00, 40, 5, 'ACC-CLA-004', '3761234568304', 1.100, '{\"longueur\":44.0, \"largeur\":13.0, \"hauteur\":3.5}', 'disponible', 1, 0, '2026-02-24 06:12:29', '2026-02-24 06:12:29'),
(52, 2, 6, 'Souris sans fil Logitech MX', 'souris-logitech-mx', 'Souris ergonomique sans fil Logitech MX Master', 'Souris Logitech MX Master 3S, capteur 8K DPI, molette de défilement magnétique, connectivité Bluetooth/2.4GHz. Idéale pour le travail.', 99.00, NULL, NULL, NULL, 50.00, 40, 5, 'ACC-SOU-005', '3761234568305', 0.140, '{\"longueur\":12.5, \"largeur\":8.5, \"hauteur\":5.0}', 'disponible', 0, 0, '2026-02-24 06:12:29', '2026-02-24 06:12:29'),
(53, 2, 4, 'iPhone 15 Pro Max', 'iphone-15-pro-max', 'Apple iPhone 15 Pro Max, écran 6.7\", 256GB', 'Dernier flagship d\'Apple avec puce A17 Pro, écran Super Retina XDR, appareil photo pro 48MP, titane. Déverrouillé, compatible toutes box.', 1899.00, 1699.00, '2026-03-01', '2026-04-01', 1200.00, 40, 5, 'SPH-IPH-001', '3761234568101', 0.221, '{\"longueur\":16.0, \"largeur\":7.6, \"hauteur\":0.8}', 'disponible', 1, 1, '2026-02-24 06:12:56', '2026-02-24 06:12:56'),
(54, 2, 4, 'Samsung Galaxy S25 Ultra', 'samsung-galaxy-s25-ultra', 'Samsung Galaxy S25 Ultra, 12GB RAM, 512GB', 'Smartphone Samsung haut de gamme avec écran Dynamic AMOLED 6.8\", stylet S-Pin intégré, appareil photo 200MP. Version internationale.', 1599.00, 1499.00, '2026-03-15', '2026-04-15', 1050.00, 40, 5, 'SPH-SAM-002', '3761234568102', 0.233, '{\"longueur\":16.3, \"largeur\":7.9, \"hauteur\":0.9}', 'disponible', 1, 1, '2026-02-24 06:12:56', '2026-02-24 06:12:56'),
(55, 2, 4, 'Google Pixel 9 Pro', 'google-pixel-9-pro', 'Google Pixel 9 Pro, excellent appareil photo', 'Smartphone Google avec IA intégrée, écran 6.7\", triple appareil photo 50MP, Android 15. Version débloquée.', 999.00, 899.00, '2026-04-01', '2026-05-01', 650.00, 40, 5, 'SPH-GOO-003', '3761234568103', 0.213, '{\"longueur\":16.2, \"largeur\":7.6, \"hauteur\":0.9}', 'disponible', 0, 1, '2026-02-24 06:12:56', '2026-02-24 06:12:56'),
(56, 2, 4, 'Xiaomi 14 Ultra', 'xiaomi-14-ultra', 'Xiaomi 14 Ultra, appareil photo Leica', 'Smartphone Xiaomi avec partenariat Leica, quadruple appareil photo 50MP, écran AMOLED 6.7\", 512GB.', 899.00, 799.00, '2026-03-01', '2026-04-01', 580.00, 40, 5, 'SPH-XIA-004', '3761234568104', 0.220, '{\"longueur\":16.1, \"largeur\":7.5, \"hauteur\":0.9}', 'disponible', 0, 0, '2026-02-24 06:12:56', '2026-02-24 06:12:56'),
(57, 2, 4, 'OnePlus 12', 'oneplus-12', 'OnePlus 12, charge rapide 100W', 'Smartphone OnePlus avec écran Fluid AMOLED 6.8\", charge ultra-rapide 100W, 16GB RAM, 256GB stockage.', 799.00, NULL, NULL, NULL, 520.00, 40, 5, 'SPH-ONE-005', '3761234568105', 0.215, '{\"longueur\":16.4, \"largeur\":7.5, \"hauteur\":0.9}', 'disponible', 1, 0, '2026-02-24 06:12:56', '2026-02-24 06:12:56'),
(58, 2, 5, 'MacBook Pro 16\" M4', 'macbook-pro-16-m4', 'Apple MacBook Pro, puce M4 Pro, 32GB RAM', 'Ordinateur portable Apple avec puce M4 Pro (16 cœurs), écran Liquid Retina XDR 16\", 1TB SSD, autonomie 22h. Idéal pour les pros.', 3499.00, 3299.00, '2026-03-01', '2026-04-01', 2400.00, 40, 5, 'ORD-APP-001', '3761234568201', 2.150, '{\"longueur\":35.6, \"largeur\":24.8, \"hauteur\":1.7}', 'disponible', 1, 1, '2026-02-24 06:13:17', '2026-02-24 06:13:17'),
(59, 2, 5, 'Dell XPS 15', 'dell-xps-15', 'Dell XPS 15, écran OLED, processeur Intel i9', 'PC portable Dell XPS 15 avec écran OLED InfinityEdge 15.6\", Intel Core i9, 32GB RAM, 1TB SSD, carte graphique NVIDIA RTX.', 2599.00, 2399.00, '2026-03-15', '2026-04-15', 1750.00, 40, 5, 'ORD-DEL-002', '3761234568202', 1.920, '{\"longueur\":34.4, \"largeur\":23.0, \"hauteur\":1.8}', 'disponible', 1, 1, '2026-02-24 06:13:17', '2026-02-24 06:13:17'),
(60, 2, 5, 'ASUS ROG Strix', 'asus-rog-strix', 'PC gamer ASUS ROG Strix, RTX 4080', 'Ordinateur portable gaming ASUS ROG Strix, écran 17.3\" 240Hz, Intel i9, 32GB RAM, 2TB SSD, NVIDIA RTX 4080.', 2999.00, 2799.00, '2026-04-01', '2026-05-01', 2000.00, 40, 5, 'ORD-ASU-003', '3761234568203', 2.800, '{\"longueur\":39.5, \"largeur\":28.2, \"hauteur\":2.3}', 'disponible', 1, 0, '2026-02-24 06:13:17', '2026-02-24 06:13:17'),
(61, 2, 5, 'HP Spectre x360', 'hp-spectre-x360', 'HP Spectre x360, convertible 2-en-1', 'PC portable convertible HP Spectre x360, écran tactile 13.5\", Intel i7, 16GB RAM, 1TB SSD, design élégant couleur or.', 1699.00, 1599.00, '2026-03-01', '2026-04-01', 1100.00, 40, 5, 'ORD-HP-004', '3761234568204', 1.300, '{\"longueur\":30.6, \"largeur\":19.4, \"hauteur\":1.7}', 'disponible', 0, 1, '2026-02-24 06:13:17', '2026-02-24 06:13:17'),
(62, 2, 5, 'Microsoft Surface Laptop 7', 'surface-laptop-7', 'Microsoft Surface Laptop 7, écran tactile', 'Microsoft Surface Laptop 7 avec écran PixelSense 15\", processeur Intel Core Ultra 7, 16GB RAM, 512GB SSD. Design fin et élégant.', 1499.00, NULL, NULL, NULL, 1000.00, 40, 5, 'ORD-MIC-005', '3761234568205', 1.560, '{\"longueur\":34.0, \"largeur\":24.4, \"hauteur\":1.7}', 'disponible', 0, 0, '2026-02-24 06:13:17', '2026-02-24 06:13:17'),
(63, 6, 37, 'Réfrigérateur Samsung 320L', 'refrigerateur-samsung-320l', 'Réfrigérateur 2 portes, capacité 320L, classe A++', 'Réfrigérateur-congélateur Samsung avec technologie No Frost, éclairage LED, clayettes en verre trempé. Dimensions: 178x60x65cm. Couleur inox.', 45000.00, 39900.00, '2026-03-01', '2026-04-15', 28000.00, 6, 2, 'REF-SAM-001', '3761234568401', 65.000, '{\"longueur\":65, \"largeur\":60, \"hauteur\":178}', 'disponible', 1, 1, '2026-02-24 06:19:25', '2026-02-24 06:19:25'),
(64, 6, 37, 'Réfrigérateur LG 280L', 'refrigerateur-lg-280l', 'Réfrigérateur compact 280L, idéal pour petites familles', 'Réfrigérateur LG avec compartiment congélateur haut, technologie Linear Cooling, garantie 10 ans sur compresseur. Dimensions: 165x55x60cm.', 38000.00, 34900.00, '2026-03-15', '2026-04-30', 23000.00, 6, 2, 'REF-LG-002', '3761234568402', 58.000, '{\"longueur\":60, \"largeur\":55, \"hauteur\":165}', 'disponible', 1, 1, '2026-02-24 06:19:25', '2026-02-24 06:19:25'),
(65, 6, 37, 'Réfrigérateur Hisense 400L', 'refrigerateur-hisense-400l', 'Grand réfrigérateur 400L, multi-portes, design moderne', 'Réfrigérateur multi-portes Hisense, capacité 400L, distributeur d\'eau, zone de fraîcheur, technologie Total No Frost. Finition inox.', 65000.00, 59900.00, '2026-04-01', '2026-05-15', 40000.00, 6, 2, 'REF-HIS-003', '3761234568403', 72.000, '{\"longueur\":70, \"largeur\":65, \"hauteur\":185}', 'disponible', 1, 0, '2026-02-24 06:19:25', '2026-02-24 06:19:25'),
(66, 6, 37, 'Réfrigérateur Beko 250L', 'refrigerateur-beko-250l', 'Réfrigérateur 1 porte, 250L, économique', 'Réfrigérateur Beko 1 porte, capacité 250L, classe énergétique A+, idéal pour studio ou petite cuisine. Dimensions: 140x54x55cm.', 28000.00, 24900.00, '2026-04-15', '2026-05-30', 17000.00, 6, 2, 'REF-BEK-004', '3761234568404', 48.000, '{\"longueur\":55, \"largeur\":54, \"hauteur\":140}', 'disponible', 0, 1, '2026-02-24 06:19:25', '2026-02-24 06:19:25'),
(67, 6, 37, 'Congélateur armoire 200L', 'congelateur-armoire-200l', 'Congélateur armoire 200L, 4 tiroirs', 'Congélateur armoire Brandt, capacité 200L, 4 tiroirs de rangement, classe énergétique A+, dégivrage manuel. Dimensions: 130x54x55cm.', 32000.00, 28900.00, '2026-05-01', '2026-06-15', 19000.00, 6, 2, 'REF-CON-005', '3761234568405', 52.000, '{\"longueur\":55, \"largeur\":54, \"hauteur\":130}', 'disponible', 0, 0, '2026-02-24 06:19:25', '2026-02-24 06:19:25'),
(68, 6, 37, 'Réfrigérateur américain 600L', 'refrigerateur-americain-600l', 'Réfrigérateur américain côte-à-côte, distributeur de glaçons', 'Réfrigérateur américain Samsung, capacité 600L, distributeur d\'eau et de glaçons, écran digital, technologie Twin Cooling. Finition inox noir.', 120000.00, 109000.00, '2026-03-01', '2026-04-30', 75000.00, 6, 2, 'REF-AME-006', '3761234568406', 98.000, '{\"longueur\":85, \"largeur\":70, \"hauteur\":185}', 'disponible', 1, 1, '2026-02-24 06:19:25', '2026-02-24 06:19:25'),
(69, 6, 40, 'Micro-ondes Samsung 23L', 'micro-ondes-samsung-23l', 'Four micro-ondes 23L, grill, design noir', 'Micro-ondes Samsung avec fonction grill, capacité 23L, puissance 1150W, 5 niveaux de puissance, commandes électroniques. Dimensions: 30x50x40cm.', 9500.00, 8500.00, '2026-03-01', '2026-04-01', 5700.00, 6, 2, 'MIC-SAM-001', '3761234568501', 12.500, '{\"longueur\":40, \"largeur\":50, \"hauteur\":30}', 'disponible', 1, 1, '2026-02-24 06:19:51', '2026-02-24 06:19:51'),
(70, 6, 40, 'Micro-ondes LG 20L', 'micro-ondes-lg-20l', 'Micro-ondes compact 20L, design blanc', 'Micro-ondes LG capacité 20L, puissance 1000W, 6 programmes automatiques, fonction décongélation rapide. Dimensions: 27x45x33cm.', 7200.00, 6500.00, '2026-03-15', '2026-04-15', 4300.00, 6, 2, 'MIC-LG-002', '3761234568502', 10.500, '{\"longueur\":33, \"largeur\":45, \"hauteur\":27}', 'disponible', 0, 1, '2026-02-24 06:19:51', '2026-02-24 06:19:51'),
(71, 6, 40, 'Micro-ondes Panasonic 27L', 'micro-ondes-panasonic-27l', 'Micro-ondes inverter 27L, technologie Inverter', 'Micro-ondes Panasonic avec technologie Inverter, capacité 27L, puissance 1200W, cuisson homogène, 14 programmes automatiques.', 12500.00, 10900.00, '2026-04-01', '2026-05-01', 7500.00, 6, 2, 'MIC-PAN-003', '3761234568503', 14.000, '{\"longueur\":42, \"largeur\":52, \"hauteur\":32}', 'disponible', 1, 1, '2026-02-24 06:19:51', '2026-02-24 06:19:51'),
(72, 6, 40, 'Micro-ondes Whirlpool 25L', 'micro-ondes-whirlpool-25l', 'Micro-ondes 25L avec grill 1500W', 'Micro-ondes Whirlpool capacité 25L, puissance 1200W, grill 1500W, 8 programmes automatiques, finition inox.', 8900.00, 7900.00, '2026-04-15', '2026-05-15', 5300.00, 6, 2, 'MIC-WHI-004', '3761234568504', 13.000, '{\"longueur\":35, \"largeur\":48, \"hauteur\":31}', 'disponible', 0, 0, '2026-02-24 06:19:51', '2026-02-24 06:19:51'),
(73, 6, 40, 'Micro-ondes compact 17L', 'micro-ondes-compact-17l', 'Petit micro-ondes 17L, idéal pour studio', 'Micro-ondes compact capacité 17L, puissance 800W, 5 niveaux de puissance, commandes mécaniques simples. Dimensions: 24x42x30cm.', 5500.00, 4900.00, '2026-05-01', '2026-06-01', 3300.00, 6, 2, 'MIC-COM-005', '3761234568505', 9.000, '{\"longueur\":30, \"largeur\":42, \"hauteur\":24}', 'disponible', 0, 1, '2026-02-24 06:19:51', '2026-02-24 06:19:51'),
(74, 6, 40, 'Micro-ondes Sharp 30L', 'micro-ondes-sharp-30l', 'Grand micro-ondes 30L, convection, grill', 'Micro-ondes Sharp avec fonction convection et grill, capacité 30L, puissance 1400W, écran digital, 12 programmes automatiques.', 14500.00, 12900.00, '2026-03-01', '2026-04-15', 8700.00, 6, 2, 'MIC-SHA-006', '3761234568506', 16.000, '{\"longueur\":45, \"largeur\":55, \"hauteur\":35}', 'disponible', 1, 0, '2026-02-24 06:19:51', '2026-02-24 06:19:51'),
(75, 6, 41, 'Aspirateur Dyson V15 Detect', 'aspirateur-dyson-v15', 'Aspirateur balai Dyson, technologie laser detect', 'Aspirateur balai Dyson V15 Detect avec écran LCD, puissance suction 230AW, autonomie 60min, tête laser Detect, filtre lavable.', 45000.00, 39900.00, '2026-03-01', '2026-04-15', 27000.00, 6, 2, 'ASP-DYS-001', '3761234568601', 3.500, '{\"longueur\":120, \"largeur\":25, \"hauteur\":30}', 'disponible', 1, 1, '2026-02-24 06:20:18', '2026-02-24 06:20:18'),
(76, 6, 41, 'Aspirateur Philips PowerPro', 'aspirateur-philips-powerpro', 'Aspirateur traîneau Philips, puissance 2100W', 'Aspirateur traîneau Philips PowerPro, technologie PowerCyclone, filtration HEPA, sac 4L, tuyau télescopique, 5 accessoires.', 12500.00, 10900.00, '2026-03-15', '2026-04-30', 7500.00, 6, 2, 'ASP-PHI-002', '3761234568602', 7.500, '{\"longueur\":50, \"largeur\":35, \"hauteur\":40}', 'disponible', 1, 1, '2026-02-24 06:20:18', '2026-02-24 06:20:18'),
(77, 6, 41, 'Aspirateur robot Xiaomi S20', 'aspirateur-robot-xiaomi-s20', 'Robot aspirateur Xiaomi, navigation laser', 'Robot aspirateur Xiaomi S20 avec navigation laser, aspiration 4000Pa, 4 modes de nettoyage, application mobile, cartographie.', 18000.00, 15900.00, '2026-04-01', '2026-05-15', 10800.00, 6, 2, 'ASP-XIA-003', '3761234568603', 3.800, '{\"longueur\":35, \"largeur\":35, \"hauteur\":10}', 'disponible', 1, 1, '2026-02-24 06:20:18', '2026-02-24 06:20:18'),
(78, 6, 41, 'Aspirateur sans fil Rowenta', 'aspirateur-sans-fil-rowenta', 'Aspirateur balai Rowenta, autonomie 45min', 'Aspirateur balai Rowenta Air Force, puissance 180W, autonomie 45min, station de charge murale, 2 vitesses, bac à poussière 0.6L.', 22000.00, 19900.00, '2026-04-15', '2026-05-30', 13200.00, 6, 2, 'ASP-ROW-004', '3761234568604', 3.200, '{\"longueur\":115, \"largeur\":25, \"hauteur\":28}', 'disponible', 0, 0, '2026-02-24 06:20:18', '2026-02-24 06:20:18'),
(79, 6, 41, 'Aspirateur Bosch Series 8', 'aspirateur-bosch-series8', 'Aspirateur traîneau Bosch, sac 4L, filtration HEPA', 'Aspirateur traîneau Bosch Series 8, puissance 2500W, technologie Silence, filtration HEPA, sac 4L, accessoires complets.', 14500.00, 12900.00, '2026-05-01', '2026-06-15', 8700.00, 6, 2, 'ASP-BOS-005', '3761234568605', 6.800, '{\"longueur\":48, \"largeur\":32, \"hauteur\":38}', 'disponible', 0, 1, '2026-02-24 06:20:18', '2026-02-24 06:20:18'),
(80, 6, 41, 'Aspirateur eau et poussière Karcher', 'aspirateur-karcher-eau', 'Aspirateur eau et poussière Karcher WD3', 'Aspirateur Karcher WD3, eau et poussière, capacité 17L, puissance 1100W, soufflerie, 5 accessoires. Idéal pour atelier.', 9500.00, 8500.00, '2026-03-01', '2026-04-15', 5700.00, 6, 2, 'ASP-KAR-006', '3761234568606', 5.200, '{\"longueur\":38, \"largeur\":38, \"hauteur\":52}', 'disponible', 1, 0, '2026-02-24 06:20:18', '2026-02-24 06:20:18');

-- --------------------------------------------------------

--
-- Structure de la table `session`
--

CREATE TABLE `session` (
  `idSession` int(10) UNSIGNED NOT NULL,
  `idUtilisateur` int(10) UNSIGNED NOT NULL,
  `token` varchar(500) NOT NULL,
  `refreshToken` varchar(500) DEFAULT NULL,
  `ipAddress` varchar(45) DEFAULT NULL,
  `userAgent` text DEFAULT NULL,
  `deviceInfo` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`deviceInfo`)),
  `dateCreation` datetime NOT NULL DEFAULT current_timestamp(),
  `dateExpiration` datetime NOT NULL,
  `derniereActivite` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `estValide` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sessions utilisateur actives';

-- --------------------------------------------------------

--
-- Structure de la table `site_settings`
--

CREATE TABLE `site_settings` (
  `cle` varchar(100) NOT NULL,
  `valeur` text DEFAULT NULL,
  `type_valeur` enum('text','image','json','boolean','number') NOT NULL DEFAULT 'text',
  `description` varchar(255) DEFAULT NULL,
  `dateModification` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Paramètres globaux de la plateforme SaaS';

--
-- Déchargement des données de la table `site_settings`
--

INSERT INTO `site_settings` (`cle`, `valeur`, `type_valeur`, `description`, `dateModification`) VALUES
('contact_address', 'Port-au-Prince, Haïti', 'text', 'Adresse physique', '2026-02-12 16:08:32'),
('contact_email', 'contact@shopxpao.ht', 'text', 'Email de contact', '2026-02-12 16:08:32'),
('contact_phone', '+509 3995 0754', 'text', 'Téléphone de contact', '2026-02-12 16:08:32'),
('footer_text', '© 2026 ShopXPao - Tous droits réservés', 'text', 'Texte du footer', '2026-02-12 16:08:32'),
('hero_banners', '[]', 'json', 'Bannières du slider', '2026-02-12 16:08:32'),
('maintenance_mode', '0', 'boolean', 'Mode maintenance activé', '2026-02-12 16:08:32'),
('site_description', 'La première plateforme qui a révolutionné le e-commerce', 'text', 'Description du site', '2026-02-12 16:08:32'),
('site_favicon', '/assets/images/favicon.png', 'image', 'Favicon', '2026-02-12 16:08:32'),
('site_logo', '/assets/images/shopxpaologo.png', 'image', 'Logo du site', '2026-02-12 16:08:32'),
('site_name', 'ShopXPao', 'text', 'Nom du site', '2026-02-12 16:08:32'),
('social_facebook', 'https://facebook.com/shopxpao', 'text', 'Lien Facebook', '2026-02-12 16:08:32'),
('social_instagram', 'https://instagram.com/shopxpao', 'text', 'Lien Instagram', '2026-02-12 16:08:32'),
('social_twitter', 'https://x.com/shopxpao', 'text', 'Lien X (Twitter)', '2026-02-12 16:08:32'),
('social_whatsapp', '+50939950754', 'text', 'Numéro WhatsApp', '2026-02-12 16:08:32');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur`
--

CREATE TABLE `utilisateur` (
  `idUtilisateur` int(10) UNSIGNED NOT NULL,
  `nomUtilisateur` varchar(100) NOT NULL,
  `prenomUtilisateur` varchar(100) NOT NULL,
  `emailUtilisateur` varchar(255) NOT NULL,
  `motDePasse` varchar(255) NOT NULL COMMENT 'Hash bcrypt ou argon2id',
  `role` enum('admin','tenant','client') NOT NULL DEFAULT 'client',
  `statut` enum('actif','bloque','en_attente') NOT NULL DEFAULT 'actif',
  `telephone` varchar(20) DEFAULT NULL,
  `avatar` varchar(500) DEFAULT NULL,
  `derniereConnexion` datetime DEFAULT NULL,
  `dateCreation` datetime NOT NULL DEFAULT current_timestamp(),
  `dateModification` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Déchargement des données de la table `utilisateur`
--

INSERT INTO `utilisateur` (`idUtilisateur`, `nomUtilisateur`, `prenomUtilisateur`, `emailUtilisateur`, `motDePasse`, `role`, `statut`, `telephone`, `avatar`, `derniereConnexion`, `dateCreation`, `dateModification`) VALUES
(1, 'Paul', 'Karlsen', 'superadmin@shopxpao.ht', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'actif', '+509 41 14 5988', '/assets/images/paolo.jpg', NULL, '2026-02-15 23:28:11', '2026-02-22 17:13:46'),
(2, 'Larack', 'Wolf Junior', 'larackwolfjr@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tenant', 'actif', '+509 32 14 5676', '/assets/images/larackwolf.jpg', NULL, '2026-02-16 07:46:19', '2026-02-22 17:07:05'),
(3, 'Pierre', 'Jean', 'jean.pierre@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tenant', 'actif', NULL, '/assets/images/brad-pitt.jpg', '2026-02-24 05:11:18', '2026-02-12 16:08:32', '2026-02-24 05:11:18'),
(4, 'Louis', 'Marie', 'marie.louis@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tenant', 'actif', NULL, '/assets/images/marielouis.jpg', NULL, '2026-02-12 16:08:32', '2026-02-22 17:19:58'),
(5, 'Baptiste', 'Paul', 'paul.baptiste@email.com', '$2a$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/X.VQ8xeEaXPxOvDWy', 'tenant', 'actif', NULL, '/assets/images/jbpaul.jpg', NULL, '2026-02-12 16:08:32', '2026-02-22 17:07:34'),
(6, 'Joseph', 'Michel', 'michel.joseph@email.com', '$2a$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/X.VQ8xeEaXPxOvDWy', 'client', 'actif', NULL, '/assets/images/josephmichel.jpg', NULL, '2026-02-12 16:08:32', '2026-02-22 17:07:59'),
(7, 'Francois', 'Claire', 'claire.francois@email.com', '$2a$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/X.VQ8xeEaXPxOvDWy', 'client', 'actif', NULL, '/assets/images/claire.jpg', NULL, '2026-02-12 16:08:32', '2026-02-22 17:20:12'),
(8, 'Beaumont', 'Pierre', 'pierre.beaumont@email.com', '$2a$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/X.VQ8xeEaXPxOvDWy', 'client', 'actif', NULL, '/assets/images/beaumontpierre.jpg', NULL, '2026-02-12 16:08:32', '2026-02-22 17:06:34'),
(9, 'Charles', 'Anne', 'anne.charles@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'actif', NULL, '/assets/images/anne-charles.jpg', '2026-02-21 19:18:37', '2026-02-12 16:08:32', '2026-02-21 19:18:37'),
(11, 'Admin', 'Developper', 'admin@shopxpao.ht', '$argon2id$v=19$m=65536,t=4,p=3$dk9jdTV2dWxaRnhMSXlMMA$xmc+P0hFQvc7UVxvsldfX6n2RWJ4aMaYk2Km9ghgWn8', 'admin', 'actif', '+509 0000 0000', '/assets/images/devadmin.jpg', '2026-02-24 06:34:58', '2026-02-15 22:39:56', '2026-02-24 06:34:58'),
(12, 'Esta', 'Ricardo', 'ricardoesta@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tenant', 'actif', '+509 55 66 77 88', '/assets/images/ricardoesta.jpg', NULL, '2026-02-16 07:52:34', '2026-02-22 17:08:32'),
(13, 'Louis', 'Boaz', 'boazlouis@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tenant', 'actif', '+509 35 62 37 40', '/assets/images/boaz.jpg', NULL, '2026-02-16 08:00:30', '2026-02-22 17:16:22'),
(14, 'Cazimir', 'Davidson', 'davidsoncazimir@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tenant', 'actif', '+509 44 62 37 40', '/assets/images/davidsoncazimir.jpg', NULL, '2026-02-16 08:01:26', '2026-02-22 17:16:09'),
(15, 'Pierre-Saint', 'Carline', 'carlinepst@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tenant', 'actif', '+509 44 62 17 10', '/assets/images/carline.jpg', NULL, '2026-02-16 09:02:20', '2026-02-22 17:21:03'),
(16, 'Paul', 'Maheva', 'mahevapaul@gmail.com', '$argon2id$v=19$m=65536,t=4,p=3$ZU14MnNaMmU3OEU4cXgybQ$AfVbq5qw3YWEzzcNKLxdiAzTmPlYKvwGSujsjUPuOOA', 'tenant', 'actif', '+509 33 22 27 22', '/assets/images/maheva.jpg', '2026-02-19 14:40:36', '2026-02-16 09:03:24', '2026-02-23 12:52:25'),
(17, 'Aneus', 'Payas', 'tipaya@gmail.com', '$argon2id$v=19$m=65536,t=4,p=3$QWlOZ3pIQ09pTnBnSFJ1dQ$N3M/UcNXlFzsg6sL4e0dqv2a0rw3drYIPuv3R/tLtZU', 'tenant', 'actif', '+509 45 66 77 88', '/assets/images/tipaya.jpg', '2026-02-19 17:56:24', '2026-02-16 09:04:41', '2026-02-23 12:50:13'),
(18, 'Celestin', 'Junior', 'juniorcelestin@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tenant', 'actif', '+509 47 50 2273', '/assets/images/junior.jpg', NULL, '2026-02-16 11:23:35', '2026-02-23 12:50:00'),
(19, 'Pierre-Saint', 'Chantale', 'chantalepierre-saint@gmail.com', '$2y$10$9T5JoV1Kva2TiqaIW4ZoyO.tddgYMlMEQYDbf21tBKQZhgahSiZB.', 'client', 'actif', '+509 3742 78 01', '/assets/images/chantale.png', '2026-02-24 11:56:51', '2026-02-17 16:17:11', '2026-02-24 11:56:51'),
(20, 'Ronaldo', 'Cristiano', 'cristianoronaldo@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tenant', 'actif', '+509 32 24 6788', '/assets/images/cr7ronaldo.jpg', NULL, '2026-02-18 07:00:57', '2026-02-22 17:12:37'),
(21, 'Robert', 'Cassy', 'cassyrobert@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tenant', 'actif', '+509 39 98 1212', '/assets/images/cassy.png', '2026-02-19 15:19:33', '2026-02-19 14:48:43', '2026-02-22 17:21:40'),
(22, 'Pierre-Saint', 'Guerda', 'guerdapierre-saint@gmail.com', '$argon2id$v=19$m=65536,t=4,p=3$b2NId2o3WHUuTGZYUk50Tg$SWrw7HJZvzGKW/Zc5nNqyYestsv9hRYKGFJcCPfO7kA', 'tenant', 'actif', NULL, '/assets/images/guerda.jpg', '2026-02-20 20:15:49', '2026-02-19 15:46:32', '2026-02-22 17:22:19'),
(23, 'Felix', 'Kendy', 'kendyfelix@gmail.com', '$argon2id$v=19$m=65536,t=4,p=3$c1dJTkJUMXAuVGtMbFRvMw$W6NdC0Hr1rKeX50bXymp7ipL0AriDJWdXQIFKDs0j+4', 'tenant', 'actif', NULL, '/assets/images/kendy.jpg', '2026-02-19 16:05:01', '2026-02-19 16:04:13', '2026-02-22 17:17:36'),
(24, 'Szoboszlai', 'Dominic', 'dominicszoboszlai@gmail.com', '$argon2id$v=19$m=65536,t=4,p=3$N2xGSzYyanJ4andORU5YWA$nXFASIE5MioCnYZrciVvIsoxHQbLozAM0N5gDI0nz1Q', 'tenant', 'actif', NULL, '/assets/images/Szoboszlai.jpg', '2026-02-19 16:21:08', '2026-02-19 16:20:47', '2026-02-22 17:10:20'),
(25, 'Dorleans', 'Olivier', 'olivierdorleans@gmail.com', '$argon2id$v=19$m=65536,t=4,p=3$czRmRWI4NTVYeXk1SHlOTA$Q8P1/NzNh0399bgywA9GMhkChbEcYdXBVVdr4IC3K08', 'tenant', 'actif', '+509 45 67 8888', '/assets/images/olivier.jpg', '2026-02-23 16:41:18', '2026-02-20 13:04:56', '2026-02-23 17:45:26'),
(26, 'Louis', 'Boaz', 'louisboaz29@gmail.com', '$argon2id$v=19$m=65536,t=4,p=3$cEJoL3NrRjYyNVBGY2d0Vw$XbFsi8e29KRYu/rrkqcGsgKcD1OiCRs6Pgsjs6T7UhU', 'client', 'actif', NULL, NULL, '2026-02-23 19:14:54', '2026-02-23 18:47:38', '2026-02-24 05:13:20');

--
-- Déclencheurs `utilisateur`
--
DELIMITER $$
CREATE TRIGGER `trg_utilisateur_after_update_audit` AFTER UPDATE ON `utilisateur` FOR EACH ROW BEGIN
    INSERT INTO audit_log (
        idBoutique, idUtilisateur, typeAction, action, tableConcernee, idEnregistrement,
        anciennesValeurs, nouvellesValeurs
    )
    VALUES (
        NULL, NEW.idUtilisateur, 'UPDATE', 'Modification utilisateur', 'utilisateur', NEW.idUtilisateur,
        JSON_OBJECT('nom', OLD.nomUtilisateur, 'prenom', OLD.prenomUtilisateur, 'email', OLD.emailUtilisateur, 'role', OLD.role, 'statut', OLD.statut),
        JSON_OBJECT('nom', NEW.nomUtilisateur, 'prenom', NEW.prenomUtilisateur, 'email', NEW.emailUtilisateur, 'role', NEW.role, 'statut', NEW.statut)
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `vue_chiffre_affaires`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `vue_chiffre_affaires` (
`idBoutique` int(10) unsigned
,`nomBoutique` varchar(150)
,`devise` varchar(3)
,`caTotal` decimal(34,2)
,`nombreCommandes` bigint(21)
,`panierMoyen` decimal(16,6)
,`caAujourdhui` decimal(34,2)
,`caSemaine` decimal(34,2)
,`caMois` decimal(34,2)
,`caAnnee` decimal(34,2)
,`commandesEnAttente` decimal(22,0)
,`commandesPayees` decimal(22,0)
,`commandesLivrees` decimal(22,0)
,`commandesAnnulees` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `vue_commandes_par_boutique`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `vue_commandes_par_boutique` (
`idCommande` int(10) unsigned
,`numeroCommande` varchar(50)
,`idBoutique` int(10) unsigned
,`nomBoutique` varchar(150)
,`idClient` int(10) unsigned
,`nomClient` varchar(201)
,`emailClient` varchar(255)
,`sousTotal` decimal(12,2)
,`montantTaxe` decimal(10,2)
,`fraisLivraison` decimal(10,2)
,`remise` decimal(10,2)
,`total` decimal(12,2)
,`devise` varchar(3)
,`statut` enum('en_attente','confirmee','payee','en_preparation','expediee','livree','annulee','remboursee')
,`dateCommande` datetime
,`nombreArticles` bigint(21)
,`quantiteTotale` decimal(32,0)
,`dernierStatutPaiement` varchar(10)
,`statutLivraison` varchar(14)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `vue_dashboard_admin`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `vue_dashboard_admin` (
`idBoutique` int(10) unsigned
,`nomBoutique` varchar(150)
,`slugBoutique` varchar(150)
,`statutBoutique` enum('actif','suspendu','en_attente','ferme')
,`dateCreation` datetime
,`proprietaire` varchar(201)
,`emailProprietaire` varchar(255)
,`abonnementActuel` varchar(10)
,`totalProduits` bigint(21)
,`produitsDisponibles` bigint(21)
,`totalCommandes` bigint(21)
,`commandesEnAttente` bigint(21)
,`chiffreAffairesTotal` decimal(34,2)
,`commandesMoisCourant` bigint(21)
,`caMoisCourant` decimal(34,2)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `vue_produits_populaires`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `vue_produits_populaires` (
`idProduit` int(10) unsigned
,`idBoutique` int(10) unsigned
,`nomBoutique` varchar(150)
,`nomProduit` varchar(255)
,`prix` decimal(12,2)
,`prixPromo` decimal(12,2)
,`stock` int(11)
,`statutProduit` enum('brouillon','disponible','non_disponible','archive')
,`nomCategorie` varchar(100)
,`imagePrincipale` varchar(500)
,`totalVendu` decimal(32,0)
,`chiffreAffaires` decimal(34,2)
,`ventesDernier30Jours` decimal(32,0)
);

-- --------------------------------------------------------

--
-- Structure de la vue `vue_chiffre_affaires`
--
DROP TABLE IF EXISTS `vue_chiffre_affaires`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vue_chiffre_affaires`  AS SELECT `b`.`idBoutique` AS `idBoutique`, `b`.`nomBoutique` AS `nomBoutique`, `pb`.`devise` AS `devise`, coalesce(sum(`c`.`total`),0) AS `caTotal`, count(distinct `c`.`idCommande`) AS `nombreCommandes`, coalesce(avg(`c`.`total`),0) AS `panierMoyen`, coalesce(sum(case when cast(`c`.`dateCommande` as date) = curdate() then `c`.`total` else 0 end),0) AS `caAujourdhui`, coalesce(sum(case when yearweek(`c`.`dateCommande`,0) = yearweek(curdate(),0) then `c`.`total` else 0 end),0) AS `caSemaine`, coalesce(sum(case when month(`c`.`dateCommande`) = month(curdate()) and year(`c`.`dateCommande`) = year(curdate()) then `c`.`total` else 0 end),0) AS `caMois`, coalesce(sum(case when year(`c`.`dateCommande`) = year(curdate()) then `c`.`total` else 0 end),0) AS `caAnnee`, sum(case when `c`.`statut` = 'en_attente' then 1 else 0 end) AS `commandesEnAttente`, sum(case when `c`.`statut` = 'payee' then 1 else 0 end) AS `commandesPayees`, sum(case when `c`.`statut` = 'livree' then 1 else 0 end) AS `commandesLivrees`, sum(case when `c`.`statut` = 'annulee' then 1 else 0 end) AS `commandesAnnulees` FROM ((`boutique` `b` left join `parametre_boutique` `pb` on(`b`.`idBoutique` = `pb`.`idBoutique`)) left join `commande` `c` on(`b`.`idBoutique` = `c`.`idBoutique` and `c`.`statut` not in ('annulee','remboursee'))) WHERE `b`.`statut` = 'actif' GROUP BY `b`.`idBoutique`, `b`.`nomBoutique`, `pb`.`devise` ;

-- --------------------------------------------------------

--
-- Structure de la vue `vue_commandes_par_boutique`
--
DROP TABLE IF EXISTS `vue_commandes_par_boutique`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vue_commandes_par_boutique`  AS SELECT `c`.`idCommande` AS `idCommande`, `c`.`numeroCommande` AS `numeroCommande`, `c`.`idBoutique` AS `idBoutique`, `b`.`nomBoutique` AS `nomBoutique`, `c`.`idClient` AS `idClient`, concat(`u`.`prenomUtilisateur`,' ',`u`.`nomUtilisateur`) AS `nomClient`, `u`.`emailUtilisateur` AS `emailClient`, `c`.`sousTotal` AS `sousTotal`, `c`.`montantTaxe` AS `montantTaxe`, `c`.`fraisLivraison` AS `fraisLivraison`, `c`.`remise` AS `remise`, `c`.`total` AS `total`, `c`.`devise` AS `devise`, `c`.`statut` AS `statut`, `c`.`dateCommande` AS `dateCommande`, (select count(0) from `commande_produit` `cp` where `cp`.`idCommande` = `c`.`idCommande`) AS `nombreArticles`, (select sum(`cp`.`quantite`) from `commande_produit` `cp` where `cp`.`idCommande` = `c`.`idCommande`) AS `quantiteTotale`, coalesce((select `p`.`statutPaiement` from `paiement` `p` where `p`.`idCommande` = `c`.`idCommande` order by `p`.`dateCreation` desc limit 1),'aucun') AS `dernierStatutPaiement`, coalesce((select `l`.`statutLivraison` from `livraison` `l` where `l`.`idCommande` = `c`.`idCommande`),'non_creee') AS `statutLivraison` FROM ((`commande` `c` join `boutique` `b` on(`c`.`idBoutique` = `b`.`idBoutique`)) join `utilisateur` `u` on(`c`.`idClient` = `u`.`idUtilisateur`)) ;

-- --------------------------------------------------------

--
-- Structure de la vue `vue_dashboard_admin`
--
DROP TABLE IF EXISTS `vue_dashboard_admin`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vue_dashboard_admin`  AS SELECT `b`.`idBoutique` AS `idBoutique`, `b`.`nomBoutique` AS `nomBoutique`, `b`.`slugBoutique` AS `slugBoutique`, `b`.`statut` AS `statutBoutique`, `b`.`dateCreation` AS `dateCreation`, concat(`u`.`prenomUtilisateur`,' ',`u`.`nomUtilisateur`) AS `proprietaire`, `u`.`emailUtilisateur` AS `emailProprietaire`, coalesce((select `ab`.`typeAbonnement` from `abonnement` `ab` where `ab`.`idBoutique` = `b`.`idBoutique` and `ab`.`statut` = 'actif' order by `ab`.`dateDebut` desc limit 1),'aucun') AS `abonnementActuel`, (select count(0) from `produit` `p` where `p`.`idBoutique` = `b`.`idBoutique`) AS `totalProduits`, (select count(0) from `produit` `p` where `p`.`idBoutique` = `b`.`idBoutique` and `p`.`statutProduit` = 'disponible') AS `produitsDisponibles`, (select count(0) from `commande` `c` where `c`.`idBoutique` = `b`.`idBoutique`) AS `totalCommandes`, (select count(0) from `commande` `c` where `c`.`idBoutique` = `b`.`idBoutique` and `c`.`statut` = 'en_attente') AS `commandesEnAttente`, (select coalesce(sum(`c`.`total`),0) from `commande` `c` where `c`.`idBoutique` = `b`.`idBoutique` and `c`.`statut` not in ('annulee','remboursee')) AS `chiffreAffairesTotal`, (select count(0) from `commande` `c` where `c`.`idBoutique` = `b`.`idBoutique` and month(`c`.`dateCommande`) = month(curdate()) and year(`c`.`dateCommande`) = year(curdate())) AS `commandesMoisCourant`, (select coalesce(sum(`c`.`total`),0) from `commande` `c` where `c`.`idBoutique` = `b`.`idBoutique` and month(`c`.`dateCommande`) = month(curdate()) and year(`c`.`dateCommande`) = year(curdate()) and `c`.`statut` not in ('annulee','remboursee')) AS `caMoisCourant` FROM (`boutique` `b` join `utilisateur` `u` on(`b`.`idProprietaire` = `u`.`idUtilisateur`)) ORDER BY `b`.`dateCreation` DESC ;

-- --------------------------------------------------------

--
-- Structure de la vue `vue_produits_populaires`
--
DROP TABLE IF EXISTS `vue_produits_populaires`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vue_produits_populaires`  AS SELECT `p`.`idProduit` AS `idProduit`, `p`.`idBoutique` AS `idBoutique`, `b`.`nomBoutique` AS `nomBoutique`, `p`.`nomProduit` AS `nomProduit`, `p`.`prix` AS `prix`, `p`.`prixPromo` AS `prixPromo`, `p`.`stock` AS `stock`, `p`.`statutProduit` AS `statutProduit`, `c`.`nomCategorie` AS `nomCategorie`, (select `ip`.`urlImage` from `image_produit` `ip` where `ip`.`idProduit` = `p`.`idProduit` and `ip`.`estPrincipale` = 1 limit 1) AS `imagePrincipale`, coalesce((select sum(`cp`.`quantite`) from (`commande_produit` `cp` join `commande` `cmd` on(`cp`.`idCommande` = `cmd`.`idCommande`)) where `cp`.`idProduit` = `p`.`idProduit` and `cmd`.`statut` not in ('annulee','remboursee')),0) AS `totalVendu`, coalesce((select sum(`cp`.`totalLigne`) from (`commande_produit` `cp` join `commande` `cmd` on(`cp`.`idCommande` = `cmd`.`idCommande`)) where `cp`.`idProduit` = `p`.`idProduit` and `cmd`.`statut` not in ('annulee','remboursee')),0) AS `chiffreAffaires`, coalesce((select sum(`cp`.`quantite`) from (`commande_produit` `cp` join `commande` `cmd` on(`cp`.`idCommande` = `cmd`.`idCommande`)) where `cp`.`idProduit` = `p`.`idProduit` and `cmd`.`statut` not in ('annulee','remboursee') and `cmd`.`dateCommande` >= curdate() - interval 30 day),0) AS `ventesDernier30Jours` FROM ((`produit` `p` join `boutique` `b` on(`p`.`idBoutique` = `b`.`idBoutique`)) left join `categorie` `c` on(`p`.`idCategorie` = `c`.`idCategorie`)) WHERE `p`.`statutProduit` <> 'archive' ;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `abonnement`
--
ALTER TABLE `abonnement`
  ADD PRIMARY KEY (`idAbonnement`),
  ADD KEY `idx_abonnement_boutique` (`idBoutique`),
  ADD KEY `idx_abonnement_statut` (`statut`),
  ADD KEY `idx_abonnement_type` (`typeAbonnement`),
  ADD KEY `idx_abonnement_dates` (`dateDebut`,`dateFin`);

--
-- Index pour la table `adresse`
--
ALTER TABLE `adresse`
  ADD PRIMARY KEY (`idAdresse`),
  ADD KEY `idx_adresse_utilisateur` (`idUtilisateur`),
  ADD KEY `idx_adresse_boutique` (`idBoutique`),
  ADD KEY `idx_adresse_type` (`typeAdresse`),
  ADD KEY `idx_adresse_defaut` (`idUtilisateur`,`estDefaut`);

--
-- Index pour la table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`idLog`),
  ADD KEY `idx_audit_log_boutique` (`idBoutique`),
  ADD KEY `idx_audit_log_utilisateur` (`idUtilisateur`),
  ADD KEY `idx_audit_log_action` (`action`),
  ADD KEY `idx_audit_log_type` (`typeAction`),
  ADD KEY `idx_audit_log_date` (`dateAction`),
  ADD KEY `idx_audit_log_table` (`tableConcernee`);

--
-- Index pour la table `boutique`
--
ALTER TABLE `boutique`
  ADD PRIMARY KEY (`idBoutique`),
  ADD UNIQUE KEY `uq_boutique_slug` (`slugBoutique`),
  ADD UNIQUE KEY `uq_boutique_proprietaire` (`idProprietaire`),
  ADD KEY `idx_boutique_statut` (`statut`),
  ADD KEY `idx_boutique_proprietaire` (`idProprietaire`);

--
-- Index pour la table `categorie`
--
ALTER TABLE `categorie`
  ADD PRIMARY KEY (`idCategorie`),
  ADD UNIQUE KEY `uq_categorie_nom_boutique` (`idBoutique`,`nomCategorie`),
  ADD UNIQUE KEY `uq_categorie_slug_boutique` (`idBoutique`,`slugCategorie`),
  ADD KEY `idx_categorie_boutique` (`idBoutique`),
  ADD KEY `idx_categorie_parent` (`idCategorieParent`),
  ADD KEY `idx_categorie_actif` (`actif`),
  ADD KEY `idx_categorie_boutique_actif` (`idBoutique`,`actif`);

--
-- Index pour la table `commande`
--
ALTER TABLE `commande`
  ADD PRIMARY KEY (`idCommande`),
  ADD UNIQUE KEY `uq_commande_numero_boutique` (`idBoutique`,`numeroCommande`),
  ADD KEY `idx_commande_boutique` (`idBoutique`),
  ADD KEY `idx_commande_client` (`idClient`),
  ADD KEY `idx_commande_statut` (`statut`),
  ADD KEY `idx_commande_date` (`dateCommande`),
  ADD KEY `idx_commande_boutique_statut` (`idBoutique`,`statut`),
  ADD KEY `idx_commande_boutique_date` (`idBoutique`,`dateCommande`),
  ADD KEY `idx_commande_client_date` (`idClient`,`dateCommande`);

--
-- Index pour la table `commande_produit`
--
ALTER TABLE `commande_produit`
  ADD PRIMARY KEY (`idCommande`,`idProduit`),
  ADD KEY `fk_commande_produit_produit` (`idProduit`);

--
-- Index pour la table `image_produit`
--
ALTER TABLE `image_produit`
  ADD PRIMARY KEY (`idImage`),
  ADD KEY `idx_image_produit` (`idProduit`),
  ADD KEY `idx_image_principale` (`idProduit`,`estPrincipale`);

--
-- Index pour la table `livraison`
--
ALTER TABLE `livraison`
  ADD PRIMARY KEY (`idLivraison`),
  ADD UNIQUE KEY `uq_livraison_commande` (`idCommande`),
  ADD KEY `fk_livraison_adresse` (`idAdresseLivraison`),
  ADD KEY `idx_livraison_boutique` (`idBoutique`),
  ADD KEY `idx_livraison_statut` (`statutLivraison`),
  ADD KEY `idx_livraison_date_prevue` (`dateLivraisonPrevue`);

--
-- Index pour la table `paiement`
--
ALTER TABLE `paiement`
  ADD PRIMARY KEY (`idPaiement`),
  ADD KEY `idx_paiement_commande` (`idCommande`),
  ADD KEY `idx_paiement_boutique` (`idBoutique`),
  ADD KEY `idx_paiement_statut` (`statutPaiement`),
  ADD KEY `idx_paiement_mode` (`modePaiement`),
  ADD KEY `idx_paiement_date` (`datePaiement`);

--
-- Index pour la table `panier`
--
ALTER TABLE `panier`
  ADD PRIMARY KEY (`idPanier`),
  ADD UNIQUE KEY `uq_panier_client_boutique` (`idUtilisateur`,`idBoutique`),
  ADD KEY `idx_panier_utilisateur` (`idUtilisateur`),
  ADD KEY `idx_panier_boutique` (`idBoutique`);

--
-- Index pour la table `panier_produit`
--
ALTER TABLE `panier_produit`
  ADD PRIMARY KEY (`idPanier`,`idProduit`),
  ADD KEY `fk_panier_produit_produit` (`idProduit`);

--
-- Index pour la table `parametre_boutique`
--
ALTER TABLE `parametre_boutique`
  ADD PRIMARY KEY (`idBoutique`);

--
-- Index pour la table `produit`
--
ALTER TABLE `produit`
  ADD PRIMARY KEY (`idProduit`),
  ADD UNIQUE KEY `uq_produit_slug_boutique` (`idBoutique`,`slugProduit`),
  ADD UNIQUE KEY `uq_produit_sku_boutique` (`idBoutique`,`sku`),
  ADD KEY `idx_produit_boutique` (`idBoutique`),
  ADD KEY `idx_produit_categorie` (`idCategorie`),
  ADD KEY `idx_produit_statut` (`statutProduit`),
  ADD KEY `idx_produit_boutique_statut` (`idBoutique`,`statutProduit`),
  ADD KEY `idx_produit_prix` (`prix`),
  ADD KEY `idx_produit_stock` (`stock`),
  ADD KEY `idx_produit_mis_en_avant` (`misEnAvant`),
  ADD KEY `idx_produit_nouveaute` (`nouveaute`),
  ADD KEY `idx_produit_recherche` (`idBoutique`,`statutProduit`,`idCategorie`);
ALTER TABLE `produit` ADD FULLTEXT KEY `idx_produit_fulltext` (`nomProduit`,`descriptionCourte`,`descriptionComplete`);

--
-- Index pour la table `session`
--
ALTER TABLE `session`
  ADD PRIMARY KEY (`idSession`),
  ADD UNIQUE KEY `uq_session_token` (`token`),
  ADD KEY `idx_session_utilisateur` (`idUtilisateur`),
  ADD KEY `idx_session_expiration` (`dateExpiration`),
  ADD KEY `idx_session_valide` (`estValide`);

--
-- Index pour la table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`cle`);

--
-- Index pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  ADD PRIMARY KEY (`idUtilisateur`),
  ADD UNIQUE KEY `uq_utilisateur_email` (`emailUtilisateur`),
  ADD KEY `idx_utilisateur_role` (`role`),
  ADD KEY `idx_utilisateur_statut` (`statut`),
  ADD KEY `idx_utilisateur_email` (`emailUtilisateur`),
  ADD KEY `idx_utilisateur_role_statut` (`role`,`statut`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `abonnement`
--
ALTER TABLE `abonnement`
  MODIFY `idAbonnement` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `adresse`
--
ALTER TABLE `adresse`
  MODIFY `idAdresse` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `idLog` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=228;

--
-- AUTO_INCREMENT pour la table `boutique`
--
ALTER TABLE `boutique`
  MODIFY `idBoutique` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `categorie`
--
ALTER TABLE `categorie`
  MODIFY `idCategorie` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `commande`
--
ALTER TABLE `commande`
  MODIFY `idCommande` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `image_produit`
--
ALTER TABLE `image_produit`
  MODIFY `idImage` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT pour la table `livraison`
--
ALTER TABLE `livraison`
  MODIFY `idLivraison` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `paiement`
--
ALTER TABLE `paiement`
  MODIFY `idPaiement` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `panier`
--
ALTER TABLE `panier`
  MODIFY `idPanier` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `produit`
--
ALTER TABLE `produit`
  MODIFY `idProduit` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `session`
--
ALTER TABLE `session`
  MODIFY `idSession` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  MODIFY `idUtilisateur` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `abonnement`
--
ALTER TABLE `abonnement`
  ADD CONSTRAINT `fk_abonnement_boutique` FOREIGN KEY (`idBoutique`) REFERENCES `boutique` (`idBoutique`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `adresse`
--
ALTER TABLE `adresse`
  ADD CONSTRAINT `fk_adresse_boutique` FOREIGN KEY (`idBoutique`) REFERENCES `boutique` (`idBoutique`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_adresse_utilisateur` FOREIGN KEY (`idUtilisateur`) REFERENCES `utilisateur` (`idUtilisateur`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `fk_audit_log_boutique` FOREIGN KEY (`idBoutique`) REFERENCES `boutique` (`idBoutique`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_audit_log_utilisateur` FOREIGN KEY (`idUtilisateur`) REFERENCES `utilisateur` (`idUtilisateur`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `boutique`
--
ALTER TABLE `boutique`
  ADD CONSTRAINT `fk_boutique_proprietaire` FOREIGN KEY (`idProprietaire`) REFERENCES `utilisateur` (`idUtilisateur`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `categorie`
--
ALTER TABLE `categorie`
  ADD CONSTRAINT `fk_categorie_boutique` FOREIGN KEY (`idBoutique`) REFERENCES `boutique` (`idBoutique`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_categorie_parent` FOREIGN KEY (`idCategorieParent`) REFERENCES `categorie` (`idCategorie`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `commande`
--
ALTER TABLE `commande`
  ADD CONSTRAINT `fk_commande_boutique` FOREIGN KEY (`idBoutique`) REFERENCES `boutique` (`idBoutique`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_commande_client` FOREIGN KEY (`idClient`) REFERENCES `utilisateur` (`idUtilisateur`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `commande_produit`
--
ALTER TABLE `commande_produit`
  ADD CONSTRAINT `fk_commande_produit_commande` FOREIGN KEY (`idCommande`) REFERENCES `commande` (`idCommande`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_commande_produit_produit` FOREIGN KEY (`idProduit`) REFERENCES `produit` (`idProduit`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `image_produit`
--
ALTER TABLE `image_produit`
  ADD CONSTRAINT `fk_image_produit` FOREIGN KEY (`idProduit`) REFERENCES `produit` (`idProduit`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `livraison`
--
ALTER TABLE `livraison`
  ADD CONSTRAINT `fk_livraison_adresse` FOREIGN KEY (`idAdresseLivraison`) REFERENCES `adresse` (`idAdresse`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_livraison_boutique` FOREIGN KEY (`idBoutique`) REFERENCES `boutique` (`idBoutique`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_livraison_commande` FOREIGN KEY (`idCommande`) REFERENCES `commande` (`idCommande`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `paiement`
--
ALTER TABLE `paiement`
  ADD CONSTRAINT `fk_paiement_boutique` FOREIGN KEY (`idBoutique`) REFERENCES `boutique` (`idBoutique`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_paiement_commande` FOREIGN KEY (`idCommande`) REFERENCES `commande` (`idCommande`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `panier`
--
ALTER TABLE `panier`
  ADD CONSTRAINT `fk_panier_boutique` FOREIGN KEY (`idBoutique`) REFERENCES `boutique` (`idBoutique`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_panier_utilisateur` FOREIGN KEY (`idUtilisateur`) REFERENCES `utilisateur` (`idUtilisateur`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `panier_produit`
--
ALTER TABLE `panier_produit`
  ADD CONSTRAINT `fk_panier_produit_panier` FOREIGN KEY (`idPanier`) REFERENCES `panier` (`idPanier`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_panier_produit_produit` FOREIGN KEY (`idProduit`) REFERENCES `produit` (`idProduit`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `parametre_boutique`
--
ALTER TABLE `parametre_boutique`
  ADD CONSTRAINT `fk_parametre_boutique` FOREIGN KEY (`idBoutique`) REFERENCES `boutique` (`idBoutique`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `produit`
--
ALTER TABLE `produit`
  ADD CONSTRAINT `fk_produit_boutique` FOREIGN KEY (`idBoutique`) REFERENCES `boutique` (`idBoutique`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_produit_categorie` FOREIGN KEY (`idCategorie`) REFERENCES `categorie` (`idCategorie`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `session`
--
ALTER TABLE `session`
  ADD CONSTRAINT `fk_session_utilisateur` FOREIGN KEY (`idUtilisateur`) REFERENCES `utilisateur` (`idUtilisateur`) ON DELETE CASCADE ON UPDATE CASCADE;

DELIMITER $$
--
-- Évènements
--
CREATE DEFINER=`root`@`localhost` EVENT `evt_nettoyer_sessions` ON SCHEDULE EVERY 1 DAY STARTS '2026-02-12 16:08:32' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    CALL sp_nettoyer_sessions();
END$$

CREATE DEFINER=`root`@`localhost` EVENT `evt_verifier_abonnements` ON SCHEDULE EVERY 1 DAY STARTS '2026-02-12 16:08:32' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    UPDATE abonnement
    SET statut = 'expire'
    WHERE dateFin IS NOT NULL
    AND dateFin < CURRENT_DATE
    AND statut = 'actif';
END$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
