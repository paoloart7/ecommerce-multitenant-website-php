<?php
/**
 * Classe CSRF - Protection contre les attaques CSRF
 */

class CSRF
{
    private static string $tokenName = '_csrf_token';
    
    public static function generate(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = Security::generateToken(64);
        $_SESSION[self::$tokenName] = [
            'token' => $token,
            'time' => time()
        ];
        
        return $token;
    }
    
    public static function getToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION[self::$tokenName])) {
            return self::generate();
        }
        
        if (time() - $_SESSION[self::$tokenName]['time'] > 3600) {
            return self::generate();
        }
        
        return $_SESSION[self::$tokenName]['token'];
    }
    
    public static function validate(?string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($token) || !isset($_SESSION[self::$tokenName])) {
            return false;
        }
        
        $stored = $_SESSION[self::$tokenName];
        
        if (time() - $stored['time'] > 3600) {
            unset($_SESSION[self::$tokenName]);
            return false;
        }
        
        return hash_equals($stored['token'], $token);
    }
    
    public static function field(): string
    {
        $token = self::getToken();
        return sprintf('<input type="hidden" name="%s" value="%s">', self::$tokenName, Security::escape($token));
    }
    
    public static function check(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST[self::$tokenName] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            
            if (!self::validate($token)) {
                http_response_code(403);
                exit('Token CSRF invalide');
            }
        }
    }
}