<?php

return [
    'GET' => [
        // Page d'accueil publique
        '' => 'HomeController@index',
        'home' => 'HomeController@index',

        'recherche' => 'HomeController@recherche',

        // API (Mega Menu)
        'api/subcategories' => 'HomeController@getSubCategories',
        'api/products-by-category' => 'HomeController@getProductsByCategory',

        // Auth
        'login' => 'AuthController@loginForm',
        'register' => 'AuthController@registerForm',
        'logout' => 'AuthController@logout',
        
        // Dashboard
        'admin' => [
            'action' => 'AdminController@dashboard',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:admin']
        ],

        // 1. UTILISATEURS
        'admin/users' => [
            'action' => 'AdminController@usersList',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:admin']
        ],

        // 2. BOUTIQUES (Tenants)
        'admin/tenants' => [
            'action' => 'AdminController@tenantsList',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:admin']
        ],
        'admin/tenant-details' => [
            'action' => 'AdminController@tenantDetails',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:admin']
        ],
        'admin/order-details' => [
            'action' => 'AdminController@orderDetails',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:admin']
        ],

        // 3. COMMANDES
        'admin/orders' => [
            'action' => 'AdminController@ordersList',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:admin']
        ],

        // 4. CATALOGUE
        'admin/categories' => [
            'action' => 'AdminController@categoriesList', 
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:admin']
        ],
        'admin/produits' => [
            'action' => 'AdminController@productsList', 
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:admin']
        ],

        // 5. FINANCE & PARAMETRES
        'admin/paiements' => [
            'action' => 'AdminController@paymentsList', 
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:admin']
        ],
        'admin/statistiques' => [
            'action' => 'AdminController@stats', 
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:admin']
        ],
        'admin/parametres' => [
            'action' => 'AdminController@settings', 
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:admin']
        ],

        'admin/sub-categories' => [
            'action' => 'AdminController@subCategoriesList',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:admin']
        ],

        'admin/product/details-json' => [
            'action' => 'AdminController@getProductDetailsJson',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:admin']
        ],
        'vendeur/configuration' => 'TenantController@setup',
        'vendeur/tableau-de-bord' => 'TenantController@dashboard',
        
        'vendeur/commandes' => [
            'action' => 'TenantController@myOrders',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:tenant']
        ],
        'vendeur/commande-details' => [
            'action' => 'TenantController@orderDetails',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:tenant']
        ],
        'vendeur/commande/update-statut' => [
            'action' => 'TenantController@updateCommandeStatut',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:tenant']
        ],
        'vendeur/commande/annuler' => [
            'action' => 'TenantController@annulerCommande',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:tenant']
        ],

                // Produits vendeur
        'vendeur/mes-produits' => [
            'action' => 'TenantController@myProducts',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:tenant']
        ],
        'vendeur/produit/ajouter' => [
            'action' => 'TenantController@addProduct',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:tenant']
        ],
        'vendeur/produit/modifier' => [
            'action' => 'TenantController@editProduct',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:tenant']
        ],
        'vendeur/produit/supprimer' => [
            'action' => 'TenantController@deleteProduct',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:tenant']
        ],
        
        'vendeur/parametres' => [
            'action' => 'TenantController@shopSettings',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:tenant']
        ],
        'vendeur/categories' => [
            'action' => 'TenantController@categories',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:tenant']
        ],
        'vendeur/categorie/ajouter' => [
            'action' => 'TenantController@addCategory',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:tenant']
        ],
        'vendeur/categorie/modifier' => [
            'action' => 'TenantController@editCategory',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:tenant']
        ],
        'vendeur/sous-categories' => [
            'action' => 'TenantController@subCategories',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:tenant']
        ],
        'vendeur/sous-categorie/ajouter' => [
            'action' => 'TenantController@addSubCategory',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:tenant']
        ],
        'vendeur/categorie/update' => [
            'action' => 'TenantController@updateCategory',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:tenant']
        ],
        'vendeur/statistiques' => [
            'action' => 'TenantController@statistiques',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:tenant']
        ],
        'vendeur/rapports' => [
            'action' => 'TenantController@rapports',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:tenant']
        ],
        'vendeur/clients' => [
            'action' => 'TenantController@clients',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:tenant']
        ],
        'vendeur/client/details' => [
            'action' => 'TenantController@clientDetails',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:tenant']
        ],
        'mes-commandes' => [
            'action' => 'ClientController@commandes',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:client']
        ],
        'commande/details' => [
            'action' => 'ClientController@commandeDetails',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:client']
        ],
        'mon-profil' => [
            'action' => 'ClientController@profil',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:client']
        ],
            'api/cart' => 'ApiController@cart',
            'api/cart/count' => 'ApiController@cartCount',
            'boutique/produit' => 'HomeController@produitDetail',
            'panier' => 'PanierController@index',
            
        'paiement/choix' => [
            'action' => 'PaiementController@choix',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:client']
        ],
        'paiement/formulaire' => [
            'action' => 'PaiementController@formulaire',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:client']
        ],
        'paiement/otp' => [
            'action' => 'PaiementController@otp',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:client']
        ],
        'paiement/succes' => 'PaiementController@succes',        

        'boutiques' => 'BoutiqueController@index',

        'boutique' => [
            'action' => 'BoutiqueController@detail',
            'middleware' => []
        ],
        'profil' => [
            'action' => 'ClientController@profil',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:client']
        ],
        'profil/modifier' => [
            'action' => 'ClientController@profilEdit',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:client']
        ],
        'mes-adresses' => [
            'action' => 'ClientController@adresses',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:client']
        ],
        'profil/mot-de-passe' => [
            'action' => 'ClientController@password',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:client']
        ],
    ],

    'POST' => [
        // Auth Actions
        'login' => 'AuthController@login',
        'register' => 'AuthController@register',

        // Admin Users Actions
        'admin/users/save' => [
            'action' => 'AdminController@saveUser',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:admin']
        ],
        'admin/users/delete' => [
            'action' => 'AdminController@deleteUser',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:admin']
        ],

        'admin/tenant/update-status' => [
            'action' => 'AdminController@updateTenantStatus',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:admin']
        ],
        'admin/tenant/update-abo' => [
            'action' => 'AdminController@updateTenantSubscription',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:admin']
        ],
        'admin/tenant/delete' => [
            'action' => 'AdminController@deleteTenant',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:admin']
        ],

        'admin/product/status'   => [
            'action' => 'AdminController@updateProductStatus',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:admin']
        ],
        'admin/product/featured' => [
            'action' => 'AdminController@toggleProductFeatured',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:admin']
        ],

        'admin/category/save' => [
        'action' => 'AdminController@saveCategory',
        'middleware' => ['AuthMiddleware', 'RoleMiddleware:admin']
        ],

            'admin/category/delete' => [
            'action' => 'AdminController@deleteCategory',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:admin']
        ],
        
        'vendeur/creer' => 'TenantController@store',

        'vendeur/produit/save' => [
            'action' => 'TenantController@saveProduct',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:tenant']
        ],
        'vendeur/produit/update' => [
            'action' => 'TenantController@updateProduct',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:tenant']
        ],
        'vendeur/produit/supprimer' => [
            'action' => 'TenantController@deleteProduct',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:tenant']
        ],
        'vendeur/produit/upload-image' => [
            'action' => 'TenantController@uploadProductImage',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:tenant']
        ],
        'vendeur/produit/set-principal' => [
            'action' => 'TenantController@setPrincipalImage',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:tenant']
        ],
        'vendeur/produit/delete-image' => [
            'action' => 'TenantController@deleteProductImage',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:tenant']
        ],

        'vendeur/parametres/update' => [
            'action' => 'TenantController@updateShopSettings',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:tenant']
        ],
        'vendeur/parametres/upload-logo' => [
            'action' => 'TenantController@uploadShopLogo',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:tenant']
        ],
        'vendeur/parametres/upload-banner' => [
            'action' => 'TenantController@uploadShopBanner',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:tenant']
        ],
        'vendeur/categorie/save' => [
            'action' => 'TenantController@saveCategory',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:tenant']
        ],
        'vendeur/categorie/update' => [
            'action' => 'TenantController@updateCategory',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:tenant']
        ],
        'vendeur/categorie/supprimer' => [
            'action' => 'TenantController@deleteCategory',
            'middleware' => ['AuthMiddleware', 'RoleMiddleware:tenant']
        ],        
            'api/cart/add' => 'ApiController@cartAdd',
            'api/cart/update' => 'ApiController@cartUpdate',
            'api/cart/remove' => 'ApiController@cartRemove',
            
            'panier/update' => 'PanierController@update',
            'panier/remove' => 'PanierController@remove',
            'panier/clear' => 'PanierController@clear',

            'paiement/traiter' => 'PaiementController@traiter',
            'paiement/valider' => 'PaiementController@valider',
            'paiement/renvoyer-code' => 'PaiementController@renvoyerCode',

            'profil/update' => [
                'action' => 'ClientController@updateProfil',
                'middleware' => ['AuthMiddleware', 'RoleMiddleware:client']
            ],
            'profil/update-password' => [
                'action' => 'ClientController@updatePassword',
                'middleware' => ['AuthMiddleware', 'RoleMiddleware:client']
            ],
            'adresse/save' => [
                'action' => 'ClientController@saveAdresse',
                'middleware' => ['AuthMiddleware', 'RoleMiddleware:client']
            ],
            'adresse/delete' => [
                'action' => 'ClientController@deleteAdresse',
                'middleware' => ['AuthMiddleware', 'RoleMiddleware:client']
            ],
    ],
];