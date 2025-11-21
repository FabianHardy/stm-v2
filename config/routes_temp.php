<?php
/**
 * Fichier de configuration des routes - VERSION TEMPORAIRE SANS MIDDLEWARE
 * 
 * ⚠️ Cette version permet d'accéder au dashboard SANS authentification
 * pour diagnostiquer le problème. À remplacer par la version complète après.
 * 
 * @package    Config
 * @author     Fabian Hardy
 * @version    1.0.0 (temp)
 */

// Importer les classes nécessaires
use Core\Router;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;

// Créer l'instance du routeur
$router = new Router();

// ============================================
// ROUTES PUBLIQUES
// ============================================

// Page d'accueil - NE PAS REDIRIGER AUTOMATIQUEMENT
$router->get('/', function() {
    echo "<h1>STM v2 - Page d'accueil</h1>";
    echo "<p><a href='/stm/admin/login'>Aller vers le login</a></p>";
    echo "<p><a href='/stm/admin/dashboard'>Aller vers le dashboard (test)</a></p>";
});

// ============================================
// ROUTES D'AUTHENTIFICATION
// ============================================

// Page de login (GET)
$router->get('/admin/login', function() {
    echo "<!-- DEBUG: Route /admin/login appelée -->\n";
    $controller = new AuthController();
    $controller->showLoginForm();
});

// Traiter le login (POST)
$router->post('/admin/login', function() {
    echo "<!-- DEBUG: Route POST /admin/login appelée -->\n";
    $controller = new AuthController();
    $controller->login();
});

// Déconnexion
$router->get('/admin/logout', function() {
    $controller = new AuthController();
    $controller->logout();
});

// ============================================
// ROUTES ADMIN (TEMPORAIREMENT SANS MIDDLEWARE)
// ============================================

// Dashboard principal - SANS MIDDLEWARE TEMPORAIREMENT
$router->get('/admin/dashboard', function() {
    echo "<!-- DEBUG: Route /admin/dashboard appelée SANS middleware -->\n";
    $controller = new DashboardController();
    $controller->index();
});

// Route /admin - NE PAS REDIRIGER AUTOMATIQUEMENT
$router->get('/admin', function() {
    echo "<h1>STM v2 - Administration</h1>";
    echo "<p><a href='/stm/admin/login'>Se connecter</a></p>";
    echo "<p><a href='/stm/admin/dashboard'>Dashboard (sans auth pour test)</a></p>";
});

// ============================================
// ROUTES CAMPAGNES (À VENIR)
// ============================================

// Ces routes seront ajoutées lors du développement du CRUD Campagnes

// Retourner le routeur configuré
return $router;