<?php
/**
 * Middleware Multi-Tenant
 */

class TenantMiddleware
{
    public function handle(): void
    {
        Session::start();
        
        $userRole = Session::get('user_role');
        
        if (!in_array($userRole, ['tenant', 'employee'])) {
            return;
        }
        
        $tenantId = Session::get('tenant_id');
        
        if (!$tenantId) {
            Session::flash('error', 'Aucune boutique associée à votre compte');
            Router::redirect('login');
        }
        
        $db = Database::getInstance();
        $tenant = $db->fetch(
            "SELECT idTenant, nomBoutique, slugBoutique, statut FROM tenant WHERE idTenant = ?",
            [$tenantId]
        );
        
        if (!$tenant) {
            Session::destroy();
            Session::flash('error', 'Boutique introuvable');
            Router::redirect('login');
        }
        
        if ($tenant['statut'] !== 'actif') {
            Session::flash('error', 'Votre boutique est ' . $tenant['statut']);
            Router::redirect('login');
        }
        
        Session::set('tenant_nom', $tenant['nomBoutique']);
        Session::set('tenant_slug', $tenant['slugBoutique']);
    }
}