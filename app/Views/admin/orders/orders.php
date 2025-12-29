<?php
/**
 * Routes à ajouter dans /config/routes.php
 * 
 * Section : Admin Orders
 * À ajouter après les autres routes admin
 */

// ============================================
// ROUTES ADMIN - COMMANDES
// ============================================

// Liste des commandes
$router->get('/admin/orders', 'OrderController@index');

// Commandes du jour
$router->get('/admin/orders/today', 'OrderController@today');

// Commandes en attente
$router->get('/admin/orders/pending', 'OrderController@pending');

// Export fichiers TXT
$router->get('/admin/orders/export', 'OrderController@export');

// Détails d'une commande
$router->get('/admin/orders/show', 'OrderController@show');

// Télécharger le fichier TXT
$router->get('/admin/orders/download', 'OrderController@downloadFile');

// Mettre à jour le statut
$router->post('/admin/orders/status', 'OrderController@updateStatus');

// Régénérer le fichier TXT
$router->post('/admin/orders/regenerate', 'OrderController@regenerateFile');
