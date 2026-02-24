<?php
/**
 * Configuration générale de l'application ShopXPao
 */

return [
    // Nom de l'application
    'name' => 'ShopXPao',
    
    // URL de base
    'base_url' => 'http://localhost/ShopXPao/public',
    
    // Domaine principal (pour production avec sous-domaines)
    'domain' => 'localhost',
    
    // Mode sous-domaine (false en local, true en production)
    'subdomain_mode' => false,
    
    // Environnement: development | production
    'env' => 'development',
    
    // Debug mode (false en production)
    'debug' => true,
    
    // Langue par défaut
    'lang' => 'fr',
    
    // Timezone
    'timezone' => 'America/Port-au-Prince',
    
    // Monnaie
    'currency' => [
        'code' => 'HTG',
        'symbol' => 'G',
        'name' => 'Gourdes',
        'decimals' => 2
    ],
    
    // Session
    'session' => [
        'lifetime' => 86400, // 24 heures en secondes
        'name' => 'SHOPXPAO_SESSION',
        'secure' => false,   // true si HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ],
    
    // Upload
    'upload' => [
        'max_size' => 10485760, // 10MB en bytes
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'path' => 'storage/uploads/'
    ],
    
    // Pagination
    'pagination' => [
        'per_page' => 12,
        'admin_per_page' => 20
    ],
    
    // Couleurs du thème
    'theme' => [
        'primary' => '#04BF9D',
        'primary_light' => '#04BFAD',
        'primary_dark' => '#027373',
        'accent' => '#5FCDD9',
        'dark' => '#172026'
    ]
];