<?php
/**
 * Routes publiques - Campagnes
 * 
 * À ajouter dans le fichier /routes/web.php
 * 
 * @package STM/Routes
 * @version 2.0.0
 * @created 13/11/2025 - Routes publiques campagnes
 */

// ================================================
// ROUTES PUBLIQUES - CAMPAGNES
// ================================================

/**
 * Page principale d'une campagne publique
 * URL: /stm/c/{uuid}
 * 
 * Affiche les détails de la campagne et permet l'accès selon le mode:
 * - Manual: Demande le numéro client
 * - Dynamic: Demande le numéro client (vérifié en temps réel)
 * - Protected: Demande le mot de passe
 */
$router->get('/c/{uuid}', function($uuid) {
    $controller = new \App\Controllers\Public\CampaignController();
    return $controller->show($uuid);
});

/**
 * Page de connexion client pour une campagne
 * URL: /stm/c/{uuid}/login
 * 
 * POST: Authentification client (numéro ou mot de passe selon le mode)
 * Crée une session client et redirige vers /c/{uuid}/promotions
 */
$router->post('/c/{uuid}/login', function($uuid) {
    $controller = new \App\Controllers\Public\CampaignController();
    return $controller->login($uuid);
});

/**
 * Catalogue des promotions d'une campagne (authentifié)
 * URL: /stm/c/{uuid}/promotions
 * 
 * Affiche la liste des produits en promotion
 * Permet l'ajout au panier et la commande
 * Nécessite authentification via /login
 */
$router->get('/c/{uuid}/promotions', function($uuid) {
    $controller = new \App\Controllers\Public\CampaignController();
    return $controller->promotions($uuid);
});

/**
 * Panier de la campagne (authentifié)
 * URL: /stm/c/{uuid}/cart
 * 
 * Affiche le panier du client
 * Permet la modification des quantités et la validation de commande
 */
$router->get('/c/{uuid}/cart', function($uuid) {
    $controller = new \App\Controllers\Public\CampaignController();
    return $controller->cart($uuid);
});

/**
 * Ajout au panier (AJAX)
 * URL: /stm/c/{uuid}/cart/add
 * 
 * POST: {product_id: int, quantity: int}
 * Retourne: JSON {success: bool, message: string, cart_count: int}
 */
$router->post('/c/{uuid}/cart/add', function($uuid) {
    $controller = new \App\Controllers\Public\CampaignController();
    return $controller->addToCart($uuid);
});

/**
 * Validation de commande
 * URL: /stm/c/{uuid}/order
 * 
 * POST: Crée la commande à partir du panier
 * Redirige vers page de confirmation
 */
$router->post('/c/{uuid}/order', function($uuid) {
    $controller = new \App\Controllers\Public\CampaignController();
    return $controller->createOrder($uuid);
});

/**
 * Confirmation de commande
 * URL: /stm/c/{uuid}/order/{orderId}/confirmation
 * 
 * Affiche la confirmation de commande avec récapitulatif
 */
$router->get('/c/{uuid}/order/{orderId}/confirmation', function($uuid, $orderId) {
    $controller = new \App\Controllers\Public\CampaignController();
    return $controller->orderConfirmation($uuid, $orderId);
});

/**
 * Déconnexion client
 * URL: /stm/c/{uuid}/logout
 * 
 * Détruit la session client et redirige vers /c/{uuid}
 */
$router->get('/c/{uuid}/logout', function($uuid) {
    $controller = new \App\Controllers\Public\CampaignController();
    return $controller->logout($uuid);
});