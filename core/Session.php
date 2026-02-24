<?php
/**
 * Classe Session - Gestion sécurisée des sessions
 */

class Session
{
    private static bool $started = false;
    
    public static function start(): void
    {
        if (self::$started) {
            return;
        }
        
        $config = require dirname(__DIR__) . '/config/app.php';
        $sessionConfig = $config['session'];
        
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_samesite', $sessionConfig['samesite']);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.gc_maxlifetime', $sessionConfig['lifetime']);
        
        session_name($sessionConfig['name']);
        
        session_set_cookie_params([
            'lifetime' => $sessionConfig['lifetime'],
            'path' => '/',
            'domain' => '',
            'secure' => $sessionConfig['secure'],
            'httponly' => $sessionConfig['httponly'],
            'samesite' => $sessionConfig['samesite']
        ]);
        
        session_start();
        self::$started = true;
        
        self::validateFingerprint();
        self::regenerateIfNeeded();
    }
    
    private static function validateFingerprint(): void
    {
        $fingerprint = Security::generateFingerprint();
        
        if (!isset($_SESSION['_fingerprint'])) {
            $_SESSION['_fingerprint'] = $fingerprint;
        } elseif ($_SESSION['_fingerprint'] !== $fingerprint) {
            self::destroy();
            session_start();
            $_SESSION['_fingerprint'] = $fingerprint;
        }
    }
    
    private static function regenerateIfNeeded(): void
    {
        $regenerateInterval = 1800;
        
        if (!isset($_SESSION['_last_regenerate'])) {
            $_SESSION['_last_regenerate'] = time();
        } elseif (time() - $_SESSION['_last_regenerate'] > $regenerateInterval) {
            self::regenerate();
        }
    }
    
    public static function regenerate(): void
    {
        session_regenerate_id(true);
        $_SESSION['_last_regenerate'] = time();
    }
    
    public static function set(string $key, $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }
    
    public static function get(string $key, $default = null)
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }
    
    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }
    
    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }
    
    public static function flash(string $key, $value): void
    {
        self::set('_flash.' . $key, $value);
    }
    
    public static function getFlash(string $key, $default = null)
    {
        $value = self::get('_flash.' . $key, $default);
        self::remove('_flash.' . $key);
        return $value;
    }
    
    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
            
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
        }
        self::$started = false;
    }
    
    public static function isLoggedIn(): bool
    {
        return self::has('user_id');
    }
    
    public static function user(): ?array
    {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => self::get('user_id'),
            'email' => self::get('user_email'),
            'role' => self::get('user_role'),
            'nom' => self::get('user_nom'),
            'prenom' => self::get('user_prenom'),
            'avatar' => self::get('user_avatar'),
            'tenant_id' => self::get('tenant_id')
        ];
    }
    
    public static function login(array $user): void
    {
        self::regenerate();
        
        self::set('user_id', $user['idUtilisateur']);
        self::set('user_email', $user['emailUtilisateur']);
        self::set('user_role', $user['role']);
        self::set('user_nom', $user['nomUtilisateur']);
        self::set('user_prenom', $user['prenomUtilisateur']);
        self::set('user_avatar', $user['avatar'] ?? null);
        self::set('login_time', time());
        
        if ($user['role'] === 'tenant' && isset($user['tenant_id'])) {
            self::set('tenant_id', $user['tenant_id']);
        }
    }
    
    public static function logout(): void
    {
        self::destroy();
    }
}