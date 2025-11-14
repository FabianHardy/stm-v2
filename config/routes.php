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
 * @version    1.6.0
 * @modified   13/11/2025 - Correction routes publiques campagnes
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

// Campagnes actives uniquement
$router->get('/admin/campaigns/active', function() {
    $middleware = new AuthMiddleware();
    $middleware->handle();
    
    $controller = new CampaignController();
    $controller->active();
});

// Campagnes archivées
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

// ============================================
// ROUTES CATÉGORIES
// Sous-menu de Promotions : /admin/products/categories
// ============================================

// Liste des catégories
$router->get('/admin/products/categories', function() {
    $middleware = new AuthMiddleware();
    $middleware->handle();
    
    $controller = new \App\Controllers\CategoryController();
    $controller->index();
});

// Formulaire de création
$router->get('/admin/products/categories/create', function() {
    $middleware = new AuthMiddleware();
    $middleware->handle();
    
    $controller = new \App\Controllers\CategoryController();
    $controller->create();
});

// Enregistrer une nouvelle catégorie
$router->post('/admin/products/categories', function() {
    $middleware = new AuthMiddleware();
    $middleware->handle();
    
    $controller = new \App\Controllers\CategoryController();
    $controller->store();
});

// Voir une catégorie spécifique
$router->get('/admin/products/categories/{id}', function($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();
    
    $controller = new \App\Controllers\CategoryController();
    $controller->show((int)$id);
});

// Formulaire de modification
$router->get('/admin/products/categories/{id}/edit', function($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();
    
    $controller = new \App\Controllers\CategoryController();
    $controller->edit((int)$id);
});

// Mettre à jour une catégorie
$router->post('/admin/products/categories/{id}', function($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();
    
    $controller = new \App\Controllers\CategoryController();
    $controller->update((int)$id);
});

// Supprimer une catégorie (formulaire POST)
$router->post('/admin/products/categories/{id}/delete', function($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();
    
    $controller = new \App\Controllers\CategoryController();
    $controller->destroy((int)$id);
});

// Activer/Désactiver une catégorie
$router->post('/admin/products/categories/{id}/toggle', function($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();
    
    $controller = new \App\Controllers\CategoryController();
    $controller->toggleActive((int)$id);
});

// ============================================
// ROUTES PROMOTIONS (CRUD COMPLET)
// ============================================
use App\Controllers\ProductController;

// Liste des Promotions
$router->get('/admin/products', function() {
    $middleware = new AuthMiddleware();
    $middleware->handle();
    
    $controller = new ProductController();
    $controller->index();
});

// Formulaire de création
$router->get('/admin/products/create', function() {
    $middleware = new AuthMiddleware();
    $middleware->handle();
    
    $controller = new ProductController();
    $controller->create();
});

// Enregistrer un nouveau Promotion
$router->post('/admin/products', function() {
    $middleware = new AuthMiddleware();
    $middleware->handle();
    
    $controller = new ProductController();
    $controller->store();
});

// Détails d'un Promotion
$router->get('/admin/products/{id}', function($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();
    
    $controller = new ProductController();
    $controller->show((int)$id);
});

// Formulaire d'édition
$router->get('/admin/products/{id}/edit', function($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();
    
    $controller = new ProductController();
    $controller->edit((int)$id);
});

// Mettre à jour un Promotion
$router->post('/admin/products/{id}', function($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();
    
    $controller = new ProductController();
    $controller->update((int)$id);
});

// Supprimer un Promotion
$router->post('/admin/products/{id}/delete', function($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();
    
    $controller = new ProductController();
    $controller->destroy((int)$id);
});

// ============================================
// ROUTES CLIENTS (CRUD COMPLET)
// ============================================
use App\Controllers\CustomerController;

// Liste des clients
$router->get('/admin/customers', function() {
    $middleware = new AuthMiddleware();
    $middleware->handle();
    
    $controller = new CustomerController();
    $controller->index();
});

// Formulaire de création
$router->get('/admin/customers/create', function() {
    $middleware = new AuthMiddleware();
    $middleware->handle();
    
    $controller = new CustomerController();
    $controller->create();
});

// Page d'import depuis DB externe
$router->get('/admin/customers/import', function() {
    $middleware = new AuthMiddleware();
    $middleware->handle();
    
    $controller = new CustomerController();
    $controller->importPreview();
});

// Exécuter l'import
$router->post('/admin/customers/import/execute', function() {
    $middleware = new AuthMiddleware();
    $middleware->handle();
    
    $controller = new CustomerController();
    $controller->importExecute();
});

// Enregistrer un nouveau client
$router->post('/admin/customers', function() {
    $middleware = new AuthMiddleware();
    $middleware->handle();
    
    $controller = new CustomerController();
    $controller->store();
});

// Détails d'un client
$router->get('/admin/customers/{id}', function($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();
    
    $controller = new CustomerController();
    $controller->show((int)$id);
});

// Formulaire d'édition
$router->get('/admin/customers/{id}/edit', function($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();
    
    $controller = new CustomerController();
    $controller->edit((int)$id);
});

// Mettre à jour un client
$router->post('/admin/customers/{id}', function($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();
    
    $controller = new CustomerController();
    $controller->update((int)$id);
});

// Supprimer un client
$router->post('/admin/customers/{id}/delete', function($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();
    
    $controller = new CustomerController();
    $controller->delete((int)$id);
});

// ============================================
// ROUTES PUBLIQUES CAMPAGNES (NOUVEAU - CORRIGÉ)
// ============================================
use App\Controllers\PublicCampaignController;

// Page d'accueil campagne publique (via UUID)
$router->get('/c/{uuid}', function($uuid) {
    $controller = new PublicCampaignController();
    $controller->show($uuid);
});

// Traiter la connexion client (POST)
$router->post('/c/{uuid}/login', function($uuid) {
    $controller = new PublicCampaignController();
    $controller->login($uuid);
});

// Page catalogue promotions (après connexion)
$router->get('/c/{uuid}/promotions', function($uuid) {
    $controller = new PublicCampaignController();
    $controller->promotions($uuid);
});

// Panier
$router->get('/c/{uuid}/cart', function($uuid) {
    $controller = new PublicCampaignController();
    $controller->cart($uuid);
});

// Ajout au panier (AJAX)
$router->post('/c/{uuid}/cart/add', function($uuid) {
    $controller = new PublicCampaignController();
    $controller->addToCart($uuid);
});

// Validation de commande
$router->post('/c/{uuid}/order', function($uuid) {
    $controller = new PublicCampaignController();
    $controller->createOrder($uuid);
});

// Confirmation de commande
$router->get('/c/{uuid}/order/{orderId}/confirmation', function($uuid, $orderId) {
    $controller = new PublicCampaignController();
    $controller->orderConfirmation($uuid, $orderId);
});

// Déconnexion client
$router->get('/c/{uuid}/logout', function($uuid) {
    $controller = new PublicCampaignController();
    $controller->logout($uuid);
});

/**
 * Routes publiques pour le Sprint 7
 * À ajouter dans routes/web.php
 * 
 * @created 2025/11/14
 */

use App\Controllers\PublicCampaignController;

// =====================================================
// ROUTES PUBLIQUES - CAMPAGNES
// =====================================================

// Accès campagne via UUID (page d'identification)
$router->get('/campaign/{uuid}', function($uuid) {
    $controller = new PublicCampaignController();
    $controller->show($uuid);
});

// Identification client
$router->post('/campaign/{uuid}/identify', function($uuid) {
    $controller = new PublicCampaignController();
    $controller->identify($uuid);
});