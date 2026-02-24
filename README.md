# ShopXPao - Plateforme E-commerce Multi-boutiques

## 📋 Description du projet
ShopXPao est une plateforme e-commerce qui consiste à concevoir et développer un système e-commerce **multi-tenant** (SaaS) où chaque membre dispose de sa propre boutique indépendante. Les vendeurs (tenants) peuvent gérer leur boutique et leur catalogue, tandis que les clients peuvent parcourir, acheter et gérer leur profil.

**Objectif** : Créer une plateforme scalable où chaque boutique est isolée mais partage la même infrastructure.

## 🏗️ Architecture technique

### Stack utilisée
- **Frontend** : HTML5, CSS3, JavaScript, Bootstrap 5, Chart.js
- **Backend** : PHP 8.2 (POO, MVC maison)
- **Base de données** : MySQL 8.0 avec isolation multi-tenant (tenant_id)
- **Serveur** : Apache
- **Sécurité** : Sessions, CSRF tokens, hachage des mots de passe

## 👥 Les 3 interfaces

### 1. Interface Administrateur (`/admin`)
- Supervision globale de la plateforme
- Gestion des utilisateurs et boutiques
- Validation des inscriptions tenant

### 2. Interface Vendeur (Tenant) - `/vendeur`
- Gestion de boutique (EF-010)
- Gestion du catalogue produits (EF-040)
- Suivi des commandes et statistiques

### 3. Interface Client - site public
- Parcours et recherche de produits
- Création et gestion de profil (EF-020, EF-030)
- Panier d'achat (EF-050)
- Paiements (EF-060, EF-070, EF-080)
  - Wallet MonCash
  - Wallet NatCash
  - Cartes de crédit
  - Cartes de débit

## ⚙️ Fonctionnalités (Exigences)

### ✅ EF-010 : Création de boutique
- Formulaire d'inscription pour les vendeurs
- Génération automatique du tenant_id
- Isolation des données par boutique

### ✅ EF-020 / EF-030 : Gestion des utilisateurs
- Inscription / Connexion sécurisée
- 3 rôles : Admin, Tenant, Client
- Profil modifiable avec avatar
- Gestion des adresses de livraison

### ✅ EF-040 : Gestion des produits
- CRUD complet avec images multiples
- Catégorisation hiérarchique
- Gestion des stocks et prix
- Produits en vedette

### ✅ EF-050 : Panier de commande
- Ajout/suppression de produits
- Gestion des quantités en AJAX
- Persistance en session/base de données

### ✅ EF-060 / EF-070 / EF-080 : Paiements
- **Wallet MonCash** : simulation avec numéro + PIN
- **Wallet NatCash** : simulation avec numéro + PIN
- **Cartes de crédit/débit** : formulaire complet (numéro, date, CVV)
- Validation OTP simulée
- Enregistrement des transactions

## 🔐 Sécurité multi-tenant (ENF-010)
- Isolation stricte des données par `tenant_id`
- Vérification systématique dans les requêtes
- Middleware de contrôle d'accès par rôle
- Tokens CSRF sur tous les formulaires
- Sessions sécurisées avec fingerprint

## 📊 Qualité et Performance (ENF-020, ENF-030)
- Architecture MVC propre et extensible
- Code commenté et structuré
- Optimisation des requêtes SQL
- Pagination des résultats
- Design responsive (mobile-first)

## 🚀 Installation et configuration

### Prérequis
- PHP 8.0+
- MySQL 5.7+
- Apache avec mod_rewrite
- Git

### Installation
1. Cloner le projet dans `htdocs`
2. Importer la base de données (`ecommerce_multitenant.sql`) dans phpMyAdmin
3. Configurer `config/database.php`
4. Lancer le serveur Apache
5. Accéder à `http://localhost/ShopXPao/public`

### Comptes de test
- **Admin** : admin@shopxpao.ht / password
- **Vendeur** : jean.pierre@email.com / password
- **Client** : chantalepierre-saint@gmail.com/Chantoutou820

## 🎯 Points forts du projet
- ✅ Architecture MVC propre et extensible
- ✅ Design responsive et moderne
- ✅ Séparation claire des rôles
- ✅ Gestion multi-tenant complète
- ✅ Interface utilisateur premium
- ✅ Code commenté et structuré

## 📝 Auteur
Karlsen PAUL - Projet pour le cours Technique de Développement des Systèmes (TDS) - INF322
Université Quisqueya - Faculté des Sciences de Génie et d'Architecture (FSGA)
Professeur : Jean Andris ADAM

## 📅 Date
Février 2026

🔗 Lien du projet
https://github.com/paoloart7/ecommerce-multitenant-website-php.git
