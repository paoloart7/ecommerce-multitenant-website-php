<?php
/**
 * Classe Security - Protection globale
 */

class Security
{
    private static array $config;
    
    public static function init(): void
    {
        self::$config = require dirname(__DIR__) . '/config/security.php';
        self::setSecurityHeaders();
        self::checkBannedIP();
    }
    
    private static function setSecurityHeaders(): void
    {
        foreach (self::$config['headers'] as $header => $value) {
            header("$header: $value");
        }
        header_remove('X-Powered-By');
    }
    
    private static function checkBannedIP(): void
    {
        $ip = self::getClientIP();
        if (in_array($ip, self::$config['banned_ips'])) {
            http_response_code(403);
            exit('Accès refusé');
        }
    }
    
    public static function getClientIP(): string
    {
        $headers = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = trim(explode(',', $_SERVER[$header])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }
    
    public static function hashPassword(string $password): string
    {
        return password_hash($password, self::$config['password']['algo'], self::$config['password']['options']);
    }
    
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
    
    public static function passwordNeedsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, self::$config['password']['algo'], self::$config['password']['options']);
    }
    
    public static function generateToken(int $length = 64): string
    {
        return bin2hex(random_bytes($length / 2));
    }
    
    public static function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    public static function escapeArray(array $data): array
    {
        return array_map(function ($value) {
            if (is_string($value)) {
                return self::escape($value);
            } elseif (is_array($value)) {
                return self::escapeArray($value);
            }
            return $value;
        }, $data);
    }
    
    public static function sanitizeEmail(string $email): ?string
    {
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }
    
    public static function sanitizeInt($value): int
    {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }
    
    public static function sanitizeString(string $string): string
    {
        return htmlspecialchars(strip_tags(trim($string)), ENT_QUOTES, 'UTF-8');
    }
    
    public static function generateFingerprint(): string
    {
        $data = [
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            self::getClientIP()
        ];
        return hash('sha256', implode('|', $data));
    }
    
    public static function isStrongPassword(string $password): array
    {
        $errors = [];
        $minLength = self::$config['password']['min_length'];
        
        if (strlen($password) < $minLength) {
            $errors[] = "Le mot de passe doit contenir au moins $minLength caractères";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins une majuscule";
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins une minuscule";
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins un chiffre";
        }
        
        return $errors;
    }
}