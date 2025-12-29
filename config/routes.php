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
 * @modified   08/11/2025 15:30 - Ajout routes active/archives
 * @modified   29/12/2025 16:00 - Ajout routes complètes commandes admin
 */

// ============================================
// CHARGEMENT MANUEL DE AUTHMIDDLEWARE
// ============================================
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
use App\Controllers\CategoryController;
use App\Controllers\ProductController;
use App\Controllers\CustomerController;
use App\Controllers\OrderController;

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
    $middleware = new AuthMiddleware();
    $middleware->handle();

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
    $middleware->handle();

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
// ============================================

$router->get('/admin/categories', function() {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new CategoryController();
    $controller->index();
});

$router->get('/admin/categories/create', function() {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new CategoryController();
    $controller->create();
});

$router->post('/admin/categories', function() {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new CategoryController();
    $controller->store();
});

$router->get('/admin/categories/{id}/edit', function($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new CategoryController();
    $controller->edit((int)$id);
});

$router->post('/admin/categories/{id}', function($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new CategoryController();
    $controller->update((int)$id);
});

$router->post('/admin/categories/{id}/delete', function($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new CategoryController();
    $controller->destroy((int)$id);
});

// ============================================
// ROUTES PRODUITS / PROMOTIONS
// ============================================

$router->get('/admin/products', function() {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new ProductController();
    $controller->index();
});

$router->get('/admin/products/create', function() {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new ProductController();
    $controller->create();
});

$router->post('/admin/products', function() {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new ProductController();
    $controller->store();
});

$router->get('/admin/products/{id}', function($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new ProductController();
    $controller->show((int)$id);
});

$router->get('/admin/products/{id}/edit', function($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new ProductController();
    $controller->edit((int)$id);
});

$router->post('/admin/products/{id}', function($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new ProductController();
    $controller->update((int)$id);
});

$router->post('/admin/products/{id}/delete', function($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new ProductController();
    $controller->destroy((int)$id);
});

// ============================================
// ROUTES CLIENTS
// ============================================

$router->get('/admin/customers', function() {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new CustomerController();
    $controller->index();
});

// API pour filtres cascade (clusters)
$router->get('/admin/customers/api/clusters', function() {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new CustomerController();
    $controller->getClusters();
});

// API pour filtres cascade (représentants)
$router->get('/admin/customers/api/representatives', function() {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new CustomerController();
    $controller->getRepresentatives();
});

$router->get('/admin/customers/show', function() {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new CustomerController();
    $controller->show();
});

// ============================================
// ROUTES COMMANDES (ADMIN)
// ============================================

// Liste de toutes les commandes
$router->get('/admin/orders', function() {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new OrderController();
    $controller->index();
});

// Commandes du jour
$router->get('/admin/orders/today', function() {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new OrderController();
    $controller->today();
});

// Commandes en attente de synchronisation
$router->get('/admin/orders/pending', function() {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new OrderController();
    $controller->pending();
});

// Page export fichiers TXT
$router->get('/admin/orders/export', function() {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new OrderController();
    $controller->export();
});

// Télécharger fichier TXT existant (GET avec ?id=)
$router->get('/admin/orders/download', function() {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new OrderController();
    $controller->downloadFile();
});

// Mettre à jour le statut de synchronisation (POST)
$router->post('/admin/orders/status', function() {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new OrderController();
    $controller->updateStatus();
});

// Régénérer le fichier TXT (POST)
$router->post('/admin/orders/regenerate', function() {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new OrderController();
    $controller->regenerateFile();
});

// Détails d'une commande (avec ID en URL)
// ⚠️ DOIT ÊTRE APRÈS les routes spécifiques !
$router->get('/admin/orders/{id}', function($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new OrderController();
    $controller->show((int)$id);
});

// Export TXT direct (téléchargement immédiat)
$router->get('/admin/orders/{id}/export-txt', function($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new OrderController();
    $controller->exportTxt((int)$id);
});

// ============================================
// ROUTES PUBLIQUES CAMPAGNES (INTERFACE CLIENT)
// ============================================

// Accès campagne par UUID
$router->get('/campaign/{uuid}', function($uuid) {
    $controller = new \App\Controllers\PublicCampaignController();
    $controller->show($uuid);
});

// Identification client
$router->post('/campaign/{uuid}/identify', function($uuid) {
    $controller = new \App\Controllers\PublicCampaignController();
    $controller->identify($uuid);
});

// Catalogue produits
$router->get('/campaign/{uuid}/catalog', function($uuid) {
    $controller = new \App\Controllers\PublicCampaignController();
    $controller->catalog($uuid);
});

// Checkout / Panier
$router->get('/campaign/{uuid}/checkout', function($uuid) {
    $controller = new \App\Controllers\PublicCampaignController();
    $controller->checkout($uuid);
});

// Soumettre commande
$router->post('/campaign/{uuid}/submit', function($uuid) {
    $controller = new \App\Controllers\PublicCampaignController();
    $controller->submitOrder($uuid);
});

// Page confirmation
$router->get('/campaign/{uuid}/confirmation/{orderUuid}', function($uuid, $orderUuid) {
    $controller = new \App\Controllers\PublicCampaignController();
    $controller->confirmation($uuid, $orderUuid);
});