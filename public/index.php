<?php
/**
 * ShopXPao - Point d'entrée
 */

// Définir le chemin racine
define('ROOT_PATH', dirname(__DIR__));

// Charger l'application
require_once ROOT_PATH . '/core/App.php';

// Démarrer l'application
$app = new App();
$app->run();