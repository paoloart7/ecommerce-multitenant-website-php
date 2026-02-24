<?php
// app/Controllers/UserController.php

require_once dirname(__DIR__) . '/Models/User.php';

class UserController
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
        
        $user = Session::user();
        if (!$user) {
            App::redirect('/login');
        }
    }

    public function index()
    {
        // 1. Filtres
        $filters = [
            'q'      => $_GET['q'] ?? '',
            'role'   => $_GET['role'] ?? '',
            'status' => $_GET['status'] ?? '' 
        ];

        // 2. Pagination
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        // 3. Récupération des données via le Modèle
        $users = $this->userModel->getPaginated($limit, $offset, $filters);
        
        // Pour la pagination (combien de pages ?)
        $totalRecords = $this->userModel->countFiltered($filters);
        
        // POUR LES STATS (Ce que tu voulais)
        $userStats = $this->userModel->getGlobalStats();

        // 4. Préparation pagination
        $pagination = [
            'current' => $page,
            'total'   => ceil($totalRecords / $limit),
            'totalRecords' => $totalRecords
        ];

        // 5. Affichage de la vue
        $pageTitle = "Utilisateurs";
        $baseUrl = App::baseUrl();
        
        require dirname(__DIR__) . '/Views/admin/users.php';
    }

}