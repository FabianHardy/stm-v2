<?php
/**
 * Fichier de configuration des routes
 * 
 * Définit toutes les routes de l'application avec leurs contrôleurs associés.
 * Les routes admin sont protégées par le middleware d'authentification.
 * 
 * NOTE : AuthMiddleware est chargé manuellement pour contourner les problèmes de cache OPcache
 * 
 * @package    Config
 * @author     Fabian Hardy
 * @version    1.5.0
 * @modified   08/11/2025 15:30 - Ajout routes active/archives
 */

// ============================================
// CHARGEMENT MANUEL DE AUTHMIDDLEWARE
// ============================================
// Pour contourner le cache OPcache, on charge le fichier directement
if (!class_exists('Middleware\AuthMiddleware')) {
    require_once BASE_PATH . '/Middleware/AuthMiddleware.php';
}

// ============================================
// USE STATEMENTS
// ============================================
use Middleware\AuthMiddleware;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\CampaignController;

// ============================================
// ROUTES PUBLIQUES
// ============================================

// Page d'accueil
$router->get('/', function() {
    header('Location: /stm/admin/login');
    exit;
});

// ============================================
// ROUTES D'AUTHENTIFICATION
// ============================================

// Page de login (GET)
$router->get('/admin/login', function() {
    $controller = new AuthController();
    $controller->showLoginForm();
});

// Traiter le login (POST)
$router->post('/admin/login', function() {
    $controller = new AuthController();
    $controller->login();
});

// Déconnexion
$router->get('/admin/logout', function() {
    $controller = new AuthController();
    $controller->logout();
});

// ============================================
// ROUTES ADMIN PROTÉGÉES (AVEC MIDDLEWARE)
// ============================================

// Dashboard principal
$router->get('/admin/dashboard', function() {
    // Créer une instance du middleware
    $middleware = new AuthMiddleware();
    
    // Vérifier l'authentification
    $middleware->handle();
    
    // Si on arrive ici, l'utilisateur est authentifié
    $controller = new DashboardController();
    $controller->index();
});

// Route par défaut /admin → redirige vers dashboard
$router->get('/admin', function() {
    header('Location: /stm/admin/dashboard');
    exit;
});

// ============================================
// ROUTES CAMPAGNES (CRUD COMPLET)
// ============================================

// Liste des campagnes
$router->get('/admin/campaigns', function() {
    $middleware = new AuthMiddleware();
    $middleware->handle(); // ✅ Vérifier auth
    
    // ✅ Si on arrive ici = authentifié
    $controller = new CampaignController();
    $controller->index();
});

// Formulaire de création
$router->get('/admin/campaigns/create', function() {
    $middleware = new AuthMiddleware();
    $middleware->handle();
    
    $controller = new CampaignController();
    $controller->create();
});

// 🔥 NOUVEAU - Campagnes actives uniquement
$router->get('/admin/campaigns/active', function() {
    $middleware = new AuthMiddleware();
    $middleware->handle();
    
    $controller = new CampaignController();
    $controller->active();
});

// 🔥 NOUVEAU - Campagnes archivées
$router->get('/admin/campaigns/archives', function() {
    $middleware = new AuthMiddleware();
    $middleware->handle();
    
    $controller = new CampaignController();
    $controller->archives();
});

// Enregistrer une nouvelle campagne
$router->post('/admin/campaigns', function() {
    $middleware = new AuthMiddleware();
    $middleware->handle();
    
    $controller = new CampaignController();
    $controller->store();
});

// Détails d'une campagne
$router->get('/admin/campaigns/{id}', function($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();
    
    $controller = new CampaignController();
    $controller->show((int)$id);
});

// Formulaire d'édition
$router->get('/admin/campaigns/{id}/edit', function($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();
    
    $controller = new CampaignController();
    $controller->edit((int)$id);
});

// Mettre à jour une campagne
$router->post('/admin/campaigns/{id}', function($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();
    
    $controller = new CampaignController();
    $controller->update((int)$id);
});

// Supprimer une campagne
$router->post('/admin/campaigns/{id}/delete', function($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();
    
    $controller = new CampaignController();
    $controller->destroy((int)$id);
});

// Toggle active/inactive
$router->post('/admin/campaigns/{id}/toggle', function($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();
    
    $controller = new CampaignController();
    $controller->toggleActive((int)$id);
});