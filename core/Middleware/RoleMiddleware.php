<?php
/**
 * Middleware de vérification des rôles
 */

class RoleMiddleware
{
    private string $requiredRole;
    
    public function __construct(string $role = '')
    {
        $this->requiredRole = $role;
    }
    
    public function handle(): void
    {
        Session::start();
        
        $userRole = Session::get('user_role');
        
        if (!$userRole) {
            Router::redirect('login');
        }
        
        if ($this->requiredRole && $userRole !== $this->requiredRole) {
            http_response_code(403);
            $errorPage = dirname(__DIR__, 2) . '/app/Views/errors/403.php';
            
            if (file_exists($errorPage)) {
                require_once $errorPage;
            } else {
                echo '<h1>403 - Accès refusé</h1>';
            }
            exit;
        }
    }
}