<?php
/**
 * Classe App - Point d'entrée de l'application
 */

class App
{
    private Router $router;
    private static array $config;
    private static array $siteSettings = [];
    
    public function __construct()
    {
        $this->loadConfig();
        $this->init();
        $this->loadSiteSettings();
    }
    
    private function loadConfig(): void
    {
        self::$config = require dirname(__DIR__) . '/config/app.php';
        date_default_timezone_set(self::$config['timezone']);
        
        if (self::$config['debug']) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        } else {
            error_reporting(0);
            ini_set('display_errors', 0);
        }
    }
    
    private function init(): void
    {
        $this->autoload();
        Security::init();
        Session::start();
        $this->router = new Router();
    }
    
    private function autoload(): void
    {
        $coreFiles = ['Security', 'Database', 'Session', 'CSRF', 'Router', 'Validator', 'Sanitizer'];
        
        foreach ($coreFiles as $file) {
            $path = dirname(__DIR__) . '/core/' . $file . '.php';
            if (file_exists($path)) {
                require_once $path;
            }
        }
        
        $middlewareFiles = ['AuthMiddleware', 'RoleMiddleware', 'TenantMiddleware', 'RateLimiter'];
        
        foreach ($middlewareFiles as $file) {
            $path = dirname(__DIR__) . '/core/Middleware/' . $file . '.php';
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }
    
    private function loadSiteSettings(): void
    {
        try {
            $db = Database::getInstance();
            $settings = $db->fetchAll("SELECT cle, valeur, type FROM site_settings");
            
            foreach ($settings as $setting) {
                $value = $setting['valeur'];
                if ($setting['type'] === 'json') {
                    $value = json_decode($value, true);
                } elseif ($setting['type'] === 'boolean') {
                    $value = (bool) $value;
                }
                self::$siteSettings[$setting['cle']] = $value;
            }
        } catch (Exception $e) {
            self::$siteSettings = [];
        }
    }
    
    public function run(): void
    {
        try {
            $url = $_GET['url'] ?? '';
            $this->router->dispatch($url);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
    
    private function handleError(Exception $e): void
    {
        if (self::$config['debug']) {
            echo '<h1>Erreur</h1>';
            echo '<p>' . Security::escape($e->getMessage()) . '</p>';
            echo '<pre>' . Security::escape($e->getTraceAsString()) . '</pre>';
        } else {
            error_log($e->getMessage());
            http_response_code(500);
            require dirname(__DIR__) . '/app/Views/errors/500.php';
        }
    }
    
    public static function config(string $key, $default = null)
    {
        return self::$config[$key] ?? $default;
    }
    
    public static function setting(string $key, $default = null)
    {
        return self::$siteSettings[$key] ?? $default;
    }
    
    public static function baseUrl(): string
    {
        return self::$config['base_url'];
    }
    
    public static function url(string $path = ''): string
    {
        return self::baseUrl() . '/' . ltrim($path, '/');
    }
    
    public static function asset(string $path): string
    {
        return self::baseUrl() . '/assets/' . ltrim($path, '/');
    }

    /**
     * Redirection propre utilisant la configuration de base
     */
    public static function redirect(string $path): void
    {
        $path = ltrim($path, '/');        
        $url = self::baseUrl() . '/' . $path;
        
        header("Location: " . $url);
        exit();
    }
}