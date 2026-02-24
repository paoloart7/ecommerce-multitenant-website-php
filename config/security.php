<?php
/**
 * Configuration de sécurité
 */

return [
    // CSRF Token
    'csrf' => [
        'token_name' => '_csrf_token',
        'token_length' => 64,
        'expire' => 3600
    ],
    
    // Rate Limiting
    'rate_limit' => [
        'enabled' => true,
        'max_attempts' => 5,
        'decay_minutes' => 1
    ],
    
    // Password
    'password' => [
        'algo' => PASSWORD_ARGON2ID,
        'options' => [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ],
        'min_length' => 8
    ],
    
    // Session
    'session' => [
        'fingerprint' => true,
        'regenerate_id' => true
    ],
    
    // Headers HTTP
    'headers' => [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin'
    ],
    
    // IPs bannies
    'banned_ips' => []
];