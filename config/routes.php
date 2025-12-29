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
 * @version    2.0.0
 * @modified   11/12/2025 - Ajout routes équipe campagne (assignees)
 * @modified   22/12/2025 - Ajout route API check-export-cache
 * @modified   23/12/2025 - Ajout route API customer-orders
 * @modified   29/12/2025 - Ajout route API products/customer-orders
 */

// ============================================
// CHARGEMENT MANUEL DE AUTHMIDDLEWARE
// ============================================
// Pour contourner le cache OPcache, on charge le fichier directement
if (!class_exists("Middleware\AuthMiddleware")) {
    require_once BASE_PATH . "/Middleware/AuthMiddleware.php";
}

// ============================================
// USE STATEMENTS
// ============================================
use Middleware\AuthMiddleware;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\CampaignController;
use App\Controllers\ProductController;
use App\Controllers\CustomerController;
use App\Controllers\PublicCampaignController;
use App\Controllers\OrderController;
use App\Controllers\AuthEntraController;

// ============================================
// ROUTES PUBLIQUES
// ============================================

// Page d'accueil
$router->get("/", function () {
    require __DIR__ . "/../app/Views/public/home.php";
});

// ============================================
// ROUTES D'AUTHENTIFICATION
// ============================================

// Page de login (GET)
$router->get("/admin/login", function () {
    $controller = new AuthController();
    $controller->showLoginForm();
});

// Traiter le login (POST)
$router->post("/admin/login", function () {
    $controller = new AuthController();
    $controller->login();
});

// Déconnexion
$router->get("/admin/logout", function () {
    $controller = new AuthController();
    $controller->logout();
});
// ============================================
// ROUTES MICROSOFT ENTRA (SSO)
// ============================================

// Redirection vers Microsoft pour authentification
$router->get("/auth/microsoft", function () {
    $controller = new AuthEntraController();
    $controller->redirectToMicrosoft();
});

// Callback après authentification Microsoft
$router->get("/auth/callback", function () {
    $controller = new AuthEntraController();
    $controller->handleCallback();
});
// ============================================
// ROUTES ADMIN PROTÉGÉES (AVEC MIDDLEWARE)
// ============================================

// Dashboard principal
$router->get("/admin/dashboard", function () {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new DashboardController();
    $controller->index();
});

// Route par défaut /admin → redirige vers dashboard
$router->get("/admin", function () {
    header("Location: /stm/admin/dashboard");
    exit();
});

// ============================================
// ROUTES CAMPAGNES (CRUD COMPLET)
// ============================================

// Liste des campagnes
$router->get("/admin/campaigns", function () {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new CampaignController();
    $controller->index();
});

// Formulaire de création
$router->get("/admin/campaigns/create", function () {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new CampaignController();
    $controller->create();
});

// Campagnes actives uniquement
$router->get("/admin/campaigns/active", function () {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new CampaignController();
    $controller->active();
});

// Campagnes archivées
$router->get("/admin/campaigns/archives", function () {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new CampaignController();
    $controller->archives();
});

// Enregistrer une nouvelle campagne
$router->post("/admin/campaigns", function () {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new CampaignController();
    $controller->store();
});

// ============================================
// ÉQUIPE CAMPAGNE (ASSIGNEES) - AVANT {id} !
// ============================================

// Ajouter un collaborateur
$router->post("/admin/campaigns/{id}/assignees", function ($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new CampaignController();
    $controller->addAssignee((int) $id);
});

// Retirer un collaborateur
$router->post("/admin/campaigns/{id}/assignees/{userId}/delete", function ($id, $userId) {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new CampaignController();
    $controller->removeAssignee((int) $id, (int) $userId);
});

// ============================================
// ROUTES CAMPAGNES AVEC {id} (GÉNÉRIQUES)
// ============================================

// Détails d'une campagne
$router->get("/admin/campaigns/{id}", function ($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new CampaignController();
    $controller->show((int) $id);
});

// Formulaire d'édition
$router->get("/admin/campaigns/{id}/edit", function ($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new CampaignController();
    $controller->edit((int) $id);
});

// Mettre à jour une campagne
$router->post("/admin/campaigns/{id}", function ($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new CampaignController();
    $controller->update((int) $id);
});

// Supprimer une campagne
$router->post("/admin/campaigns/{id}/delete", function ($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new CampaignController();
    $controller->destroy((int) $id);
});

// Toggle active/inactive
$router->post("/admin/campaigns/{id}/toggle", function ($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new CampaignController();
    $controller->toggleActive((int) $id);
});

// ============================================
// ROUTES CATÉGORIES
// Sous-menu de Promotions : /admin/products/categories
// ============================================

// Liste des catégories
$router->get("/admin/products/categories", function () {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\CategoryController();
    $controller->index();
});

// Formulaire de création
$router->get("/admin/products/categories/create", function () {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\CategoryController();
    $controller->create();
});

// Enregistrer une nouvelle catégorie
$router->post("/admin/products/categories", function () {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\CategoryController();
    $controller->store();
});

// Voir une catégorie spécifique
$router->get("/admin/products/categories/{id}", function ($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\CategoryController();
    $controller->show((int) $id);
});

// Formulaire de modification
$router->get("/admin/products/categories/{id}/edit", function ($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\CategoryController();
    $controller->edit((int) $id);
});

// Mettre à jour une catégorie
$router->post("/admin/products/categories/{id}", function ($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\CategoryController();
    $controller->update((int) $id);
});

// Supprimer une catégorie
$router->post("/admin/products/categories/{id}/delete", function ($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\CategoryController();
    $controller->destroy((int) $id);
});

// Activer/Désactiver une catégorie
$router->post("/admin/products/categories/{id}/toggle", function ($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\CategoryController();
    $controller->toggleActive((int) $id);
});

// ============================================
// ROUTES PROMOTIONS (CRUD COMPLET)
// ============================================

// Liste des Promotions
$router->get("/admin/products", function () {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new ProductController();
    $controller->index();
});

// Formulaire de création
$router->get("/admin/products/create", function () {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new ProductController();
    $controller->create();
});

// Enregistrer un nouveau Promotion
$router->post("/admin/products", function () {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new ProductController();
    $controller->store();
});

// API - Détail commandes d'un client pour un produit (AJAX)
// ⚠️ DOIT être AVANT /admin/products/{id}
$router->get('/admin/products/customer-orders', function () {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new ProductController();
    $controller->getProductCustomerOrdersApi();
});

// Détails d'un Promotion
$router->get("/admin/products/{id}", function ($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new ProductController();
    $controller->show((int) $id);
});

// Formulaire d'édition
$router->get("/admin/products/{id}/edit", function ($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new ProductController();
    $controller->edit((int) $id);
});

// Mettre à jour un Promotion
$router->post("/admin/products/{id}", function ($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new ProductController();
    $controller->update((int) $id);
});

// Supprimer un Promotion
$router->post("/admin/products/{id}/delete", function ($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new ProductController();
    $controller->destroy((int) $id);
});

// ============================================
// ROUTES CLIENTS (CRUD COMPLET)
// ============================================
// ROUTES CLIENTS (Consultation uniquement)
// ============================================

// Liste des clients (depuis DB externe)
$router->get("/admin/customers", function () {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new CustomerController();
    $controller->index();
});

// Détail d'un client (via query params: customer_number + country)
$router->get("/admin/customers/show", function () {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new CustomerController();
    $controller->show();
});

// API - Détail d'une commande (AJAX)
$router->get("/admin/customers/order-detail", function () {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new CustomerController();
    $controller->getOrderDetailApi();
});

// ============================================
// ROUTES COMMANDES ADMIN
// ============================================

// Liste des commandes (placeholder pour l'instant)
$router->get("/admin/orders", function () {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new OrderController();
    $controller->index();
});

// Exporter le fichier TXT d'une commande (AVANT la route générique {id})
$router->get("/admin/orders/{id}/export-txt", function ($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new OrderController();
    $controller->exportTxt((int) $id);
});

// Détails d'une commande
$router->get("/admin/orders/{id}", function ($id) {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new OrderController();
    $controller->show((int) $id);
});

// ============================================
// ROUTES PUBLIQUES - CAMPAGNES (SPRINT 7)
// ============================================

// Page d'accès campagne via UUID (identification client)
$router->get("/c/{uuid}", function ($uuid) {
    $controller = new PublicCampaignController();
    $controller->show($uuid);
});

// Traiter l'identification client
$router->post("/c/{uuid}/identify", function ($uuid) {
    $controller = new PublicCampaignController();
    $controller->identify($uuid);
});

// Afficher le catalogue de produits
$router->get("/c/{uuid}/catalog", function ($uuid) {
    $controller = new PublicCampaignController();
    $controller->catalog($uuid);
});

// Ajouter un produit au panier (AJAX)
$router->post("/c/{uuid}/cart/add", function ($uuid) {
    $controller = new PublicCampaignController();
    $controller->addToCart($uuid);
});

// Modifier quantité panier (AJAX)
$router->post("/c/{uuid}/cart/update", function ($uuid) {
    $controller = new PublicCampaignController();
    $controller->updateCart($uuid);
});

// Retirer produit du panier (AJAX)
$router->post("/c/{uuid}/cart/remove", function ($uuid) {
    $controller = new PublicCampaignController();
    $controller->removeFromCart($uuid);
});

// Vider le panier (AJAX)
$router->post("/c/{uuid}/cart/clear", function ($uuid) {
    $controller = new PublicCampaignController();
    $controller->clearCart($uuid);
});

// ============================================
// ROUTES VALIDATION COMMANDE (SPRINT 7 - SOUS-TÂCHE 3)
// ============================================

// Page de validation commande (checkout)
$router->get("/c/{uuid}/checkout", function ($uuid) {
    $controller = new PublicCampaignController();
    $controller->checkout($uuid);
});

// Traiter la soumission de commande
$router->post("/c/{uuid}/order/submit", function ($uuid) {
    $controller = new PublicCampaignController();
    $controller->submitOrder($uuid);
});

// Page de confirmation après validation
$router->get("/c/{uuid}/order/confirmation", function ($uuid) {
    $controller = new PublicCampaignController();
    $controller->orderConfirmation($uuid);
});

// =============================================
// OUTILS DE DÉVELOPPEMENT (Mode DEV uniquement)
// =============================================

// Synchronisation Base de données
$router->get("/admin/dev-tools/sync-db", function () {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\Admin\DevToolsController();
    $controller->syncDatabase();
});

$router->post("/admin/dev-tools/sync-db", function () {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\Admin\DevToolsController();
    $controller->executeSyncDatabase();
});

// Synchronisation Fichiers
$router->get("/admin/dev-tools/sync-files", function () {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\Admin\DevToolsController();
    $controller->syncFiles();
});

$router->post("/admin/dev-tools/sync-files", function () {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\Admin\DevToolsController();
    $controller->executeSyncFiles();
});

// ============================================
// ROUTES STATISTIQUES
// ============================================

// Vue globale
$router->get("/admin/stats", function () {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\StatsController();
    $controller->index();
});

// Par campagne
$router->get("/admin/stats/campaigns", function () {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\StatsController();
    $controller->campaigns();
});

// Par commercial
$router->get("/admin/stats/sales", function () {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\StatsController();
    $controller->sales();
});

// Rapports
$router->get("/admin/stats/reports", function () {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\StatsController();
    $controller->reports();
});

// Export (POST)
$router->post("/admin/stats/export", function () {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\StatsController();
    $controller->export();
});

// Export Excel Représentants (POST)
$router->post('/admin/stats/export-reps-excel', function () {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\StatsController();
    $controller->exportRepsExcel();
});

// API - Vérification cache export (AJAX)
$router->get('/admin/stats/check-export-cache', function () {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\StatsController();
    $controller->checkExportCache();
});

// API - Détail commandes d'un client pour une campagne (AJAX)
$router->get('/admin/stats/customer-orders', function () {
    $middleware = new AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\StatsController();
    $controller->getCustomerOrdersApi();
});

// ============================================
// ROUTES CONFIGURATION - COMPTES INTERNES
// ============================================

// Liste des comptes internes
$router->get("/admin/config/internal-customers", function () {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\InternalCustomerController();
    $controller->index();
});

// Formulaire création
$router->get("/admin/config/internal-customers/create", function () {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\InternalCustomerController();
    $controller->create();
});

// Enregistrer nouveau (POST)
$router->post("/admin/config/internal-customers", function () {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\InternalCustomerController();
    $controller->store();
});

// Formulaire modification
$router->get("/admin/config/internal-customers/{id}/edit", function ($id) {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\InternalCustomerController();
    $controller->edit((int) $id);
});

// Mettre à jour (POST)
$router->post("/admin/config/internal-customers/{id}", function ($id) {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\InternalCustomerController();
    $controller->update((int) $id);
});

// Supprimer (POST)
$router->post("/admin/config/internal-customers/{id}/delete", function ($id) {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\InternalCustomerController();
    $controller->destroy((int) $id);
});

// Toggle actif (AJAX)
$router->post("/admin/config/internal-customers/{id}/toggle", function ($id) {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\InternalCustomerController();
    $controller->toggleActive((int) $id);
});

// ========================================
// AGENT (Chatbot IA)
// ========================================

// Page d'accueil agent
$router->get('/admin/agent', function () {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\AgentController();
    $controller->index();
});

// Endpoint chat (POST)
$router->post('/admin/agent/chat', function () {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\AgentController();
    $controller->chat();
});

// Historique des conversations
$router->get('/admin/agent/history', function () {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\AgentController();
    $controller->history();
});

// Voir une conversation spécifique
$router->get('/admin/agent/conversation/{session_id}', function ($session_id) {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\AgentController();
    $controller->conversation($session_id);
});

// Charger une conversation dans le widget (AJAX)
$router->get('/admin/agent/load/{session_id}', function ($session_id) {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\AgentController();
    $controller->load($session_id);
});

// Supprimer une conversation (AJAX)
$router->post('/admin/agent/delete/{session_id}', function ($session_id) {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\AgentController();
    $controller->delete($session_id);
});

// ============================================
// ROUTES UTILISATEURS (Admin)
// ============================================

// Liste des utilisateurs
$router->get("/admin/users", function () {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\UserController();
    $controller->index();
});

// Formulaire de création
$router->get("/admin/users/create", function () {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\UserController();
    $controller->create();
});

// Enregistrer un nouvel utilisateur (POST)
$router->post("/admin/users", function () {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\UserController();
    $controller->store();
});

// Formulaire de modification
$router->get("/admin/users/{id}/edit", function ($id) {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\UserController();
    $controller->edit((int) $id);
});

// Mettre à jour un utilisateur (POST)
$router->post("/admin/users/{id}", function ($id) {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\UserController();
    $controller->update((int) $id);
});

// Supprimer un utilisateur (POST)
$router->post("/admin/users/{id}/delete", function ($id) {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\UserController();
    $controller->destroy((int) $id);
});

// Toggle activation (AJAX)
$router->post("/admin/users/{id}/toggle", function ($id) {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\UserController();
    $controller->toggle((int) $id);
});

// ============================================
// ROUTES IMPERSONATE (Se connecter en tant que)
// ============================================

// Se connecter en tant qu'un autre utilisateur (superadmin uniquement)
$router->get("/admin/users/{id}/impersonate", function ($id) {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\UserController();
    $controller->impersonate((int) $id);
});

// Revenir à son compte original
$router->get("/admin/impersonate/stop", function () {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\UserController();
    $controller->stopImpersonate();
});

// ============================================
// CONFIGURATION / SETTINGS
// ============================================

$router->get("/admin/settings", function () {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();
    $controller = new \App\Controllers\SettingsController();
    $controller->index();
});

// Sauvegarder les permissions (AJAX)
$router->post("/admin/settings/permissions", function () {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();
    $controller = new \App\Controllers\SettingsController();
    $controller->savePermissions();
});

// ============================================
// PROFIL UTILISATEUR
// ============================================

// Page profil
$router->get("/admin/profile", function () {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();
    $controller = new \App\Controllers\ProfileController();
    $controller->index();
});

// Mettre à jour l'avatar
$router->post("/admin/profile/avatar", function () {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();
    $controller = new \App\Controllers\ProfileController();
    $controller->updateAvatar();
});

// Supprimer l'avatar
$router->get("/admin/profile/avatar/delete", function () {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();
    $controller = new \App\Controllers\ProfileController();
    $controller->deleteAvatarAction();
});

// Mettre à jour le mot de passe
$router->post("/admin/profile/password", function () {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();
    $controller = new \App\Controllers\ProfileController();
    $controller->updatePassword();
});

/**
 * ROUTES AGENT - À ajouter dans config/routes.php
 *
 * Copier ce bloc dans la section des routes admin
 */

// ========================================
// AGENT (Chatbot IA)
// ========================================

// Page d'accueil agent
$router->get('/admin/agent', function () {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\AgentController();
    $controller->index();
});

// Endpoint chat (POST)
$router->post('/admin/agent/chat', function () {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\AgentController();
    $controller->chat();
});

// Historique des conversations
$router->get('/admin/agent/history', function () {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\AgentController();
    $controller->history();
});

// Voir une conversation spécifique
$router->get('/admin/agent/conversation/{session_id}', function ($sessionId) {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\AgentController();
    $controller->conversation($sessionId);
});

// Charger une conversation dans le widget (AJAX)
$router->get('/admin/agent/load/{session_id}', function ($sessionId) {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\AgentController();
    $controller->load($sessionId);
});

// Supprimer une conversation (AJAX)
$router->post('/admin/agent/delete/{session_id}', function ($sessionId) {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\AgentController();
    $controller->delete($sessionId);
});

// ========================================
// CONFIGURATION AGENT
// ========================================

// Page configuration de l'agent
$router->get('/admin/settings/agent', function () {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\AgentConfigController();
    $controller->index();
});

// Sauvegarder la configuration
$router->post('/admin/settings/agent/save', function () {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\AgentConfigController();
    $controller->save();
});

// Réinitialiser la configuration
$router->post('/admin/settings/agent/reset', function () {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\AgentConfigController();
    $controller->reset();
});

// Prévisualisation du prompt (AJAX)
$router->get('/admin/settings/agent/preview', function () {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\AgentConfigController();
    $controller->preview();
});

// Test connexion IA (AJAX - Super Admin)
$router->get('/admin/settings/agent/test', function () {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\AgentConfigController();
    $controller->testConnection();
});;
// ========================================
// TOOLS AGENT (Gestion des outils IA)
// ========================================

// Liste des tools (AJAX)
$router->get('/admin/settings/agent/tools', function () {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\AgentConfigController();
    $controller->listTools();
});

// Toggle activer/désactiver un tool (AJAX)
$router->post('/admin/settings/agent/tools/toggle', function () {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\AgentConfigController();
    $controller->toggleTool();
});

// Créer un tool via IA (AJAX)
$router->post('/admin/settings/agent/tools/create', function () {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\AgentConfigController();
    $controller->createTool();
});

// Mettre à jour un tool (AJAX)
$router->post('/admin/settings/agent/tools/update', function () {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\AgentConfigController();
    $controller->updateTool();
});

// Supprimer un tool (AJAX)
$router->post('/admin/settings/agent/tools/delete', function () {
    $middleware = new \Middleware\AuthMiddleware();
    $middleware->handle();

    $controller = new \App\Controllers\AgentConfigController();
    $controller->deleteTool();
});