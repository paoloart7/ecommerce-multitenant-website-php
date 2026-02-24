<?php
/**
 * Middleware d'authentification
 */

class AuthMiddleware
{
    public function handle(): void
    {
        Session::start();
        
        if (!Session::isLoggedIn()) {
            Session::flash('error', 'Vous devez être connecté pour accéder à cette page');
            Router::redirect('login');
        }
        
        $loginTime = Session::get('login_time');
        $config = require dirname(__DIR__, 2) . '/config/app.php';
        
        if ($loginTime && (time() - $loginTime) > $config['session']['lifetime']) {
            Session::destroy();
            Session::flash('error', 'Votre session a expiré');
            Router::redirect('login');
        }
    }
}