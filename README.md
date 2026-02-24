# ShopXPao - Plateforme E-commerce Multi-boutiques

## 📋 Description du projet
ShopXPao est une plateforme e-commerce innovante permettant à plusieurs vendeurs de créer et gérer leurs propres boutiques en ligne. Le projet suit une architecture MVC (Modèle-Vue-Contrôleur) et propose trois interfaces distinctes : Administration, Vendeurs et Clients.

## 🏗️ Architecture technique

### Stack utilisée
- **Langage** : PHP 8.2 (POO)
- **Base de données** : MySQL 8.0
- **Serveur** : Apache
- **Architecture** : MVC maison
- **Frontend** : Bootstrap 5, JavaScript vanilla, Chart.js
- **Sécurité** : Sessions, CSRF tokens, validation des entrées

### Structure du projet



## 👥 Les 3 interfaces

### 1. Interface Administrateur (`/admin`)
- **Supervision globale** de la plateforme
- **Gestion des utilisateurs** (CRUD complet)
- **Gestion des boutiques** (validation, suspension)
- **Modération des produits** (blocage, mise en avant)
- **Tableau de bord** avec statistiques globales
- **Gestion des commandes** (supervision)

### 2. Interface Vendeur (Tenant) - `/vendeur`
- **Dashboard** avec chiffre d'affaires et statistiques
- **Gestion des produits** (CRUD complet avec images)
- **Gestion des catégories** (hiérarchie parent/enfant)
- **Gestion des commandes** (liste, détail, changement de statut)
- **Gestion des clients** de la boutique
- **Statistiques** détaillées (top produits, évolution)
- **Paramètres de la boutique** (logo, description, couleurs)

### 3. Interface Client - site public
- **Page d'accueil** avec produits et boutiques en vedette
- **Catalogue** avec recherche et filtres
- **Détail produit** avec images
- **Panier** (AJAX) avec gestion des quantités
- **Paiement simulé** (MonCash, NatCash, Carte)
- **Commandes** (liste et détail)
- **Profil utilisateur** avec gestion des adresses

## ⚙️ Fonctionnalités principales

### Gestion des utilisateurs
- Inscription / Connexion sécurisée
- 3 rôles : Admin, Tenant (vendeur), Client
- Profil modifiable
- Upload d'avatar

### Gestion des boutiques
- Création de boutique pour les vendeurs
- Paramétrage (logo, couleurs, description)
- Statistiques par boutique

### Gestion des produits
- CRUD complet avec images multiples
- Catégorisation hiérarchique
- Gestion des stocks
- Prix et promotions

### Gestion des commandes
- Processus complet (panier → paiement → confirmation)
- Historique des commandes
- Changement de statut
- Validation par le vendeur

### Paiement simulé
- 3 modes : MonCash, NatCash, Carte bancaire
- Formulaire avec numéro et PIN
- Validation OTP simulée
- Page de succès

## 🔐 Sécurité
- Routes protégées par middleware (Auth, Role)
- Tokens CSRF sur tous les formulaires
- Hachage des mots de passe (password_hash)
- Sessions sécurisées avec fingerprint
- Validation des entrées

## 📊 Base de données
- Structure relationnelle optimisée
- Contraintes d'intégrité (clés étrangères)
- Triggers pour l'audit et les mises à jour automatiques
- Vues pour les statistiques

## 🚀 Installation et configuration

### Prérequis
- PHP 8.0+
- MySQL 5.7+
- Apache avec mod_rewrite

### Installation
1. Cloner le projet dans `htdocs`
2. Importer la base de données (`ecommerce_multitenant.sql`)
3. Configurer `config/database.php`
4. Lancer le serveur Apache
5. Accéder à `http://localhost/ShopXPao/public`

### Comptes de test
- **Admin** : admin@shopxpao.ht / password
- **Vendeur** : jean.pierre@email.com / password
- **Client** : michel.joseph@email.com / password

## 🎯 Points forts du projet
- ✅ Architecture MVC propre et extensible
- ✅ Design responsive et moderne
- ✅ Séparation claire des rôles
- ✅ Gestion multi-tenant complète
- ✅ Interface utilisateur premium
- ✅ Code commenté et structuré

## 📝 Auteur
Karlsen PAUL - Projet pour le cours TDS / Démonstration

## 📅 Date
Février 2026