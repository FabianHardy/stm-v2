<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
/**
 * Point d'entrée principal de l'application STM v2
 * 
 * Ce fichier :
 * - Charge les dépendances (Composer autoloader)
 * - Initialise l'application (config, session, router)
 * - Gère le routage des requêtes
 * - Dispatche les routes vers les controllers
 * 
 * @package STM
 * @version 2.0
 */

// Définir le chemin de base de l'application
define('BASE_PATH', dirname(__DIR__));

// =============================================
// 1. CHARGER L'AUTOLOADER COMPOSER
// =============================================

require_once BASE_PATH . '/vendor/autoload.php';

// =============================================
// 2. CHARGER LES VARIABLES D'ENVIRONNEMENT
// =============================================

try {
    $dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
    $dotenv->load();
} catch (Exception $e) {
    die('Erreur : Impossible de charger le fichier .env. Vérifiez qu\'il existe à la racine de /stm/');
}

// =============================================
// 3. CONFIGURATION DE L'APPLICATION
// =============================================

// Mode debug
$debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);

if ($debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set('Europe/Brussels');

// =============================================
// 4. DÉMARRER LA SESSION
// =============================================

use Core\Session;
Session::start();

// =============================================
// 5. CRÉER LE ROUTER
// =============================================

use Core\Router;
$router = new Router();

// =============================================
// 6. CHARGER LES ROUTES
// =============================================

// Le fichier routes.php va utiliser la variable $router
require_once BASE_PATH . '/config/routes.php';

// =============================================
// 7. GÉRER LA REQUÊTE
// =============================================

use Core\Request;
$request = new Request();

// Récupérer l'URI
$uri = $request->uri();

// Nettoyer l'URI (retirer le préfixe /stm si présent)
$baseFolder = '/stm';
if (str_starts_with($uri, $baseFolder)) {
    $uri = substr($uri, strlen($baseFolder));
}

// Si URI vide, rediriger vers /admin
if (empty($uri) || $uri === '/') {
    header('Location: /stm/admin/login');
    exit;
}

// =============================================
// 8. DISPATCHER LA ROUTE
// =============================================

try {
    $router->dispatch($uri, $request->method());
} catch (Exception $e) {
    // Gestion des erreurs
    if ($debug) {
        // En mode debug, afficher l'erreur complète
        echo '<!DOCTYPE html>';
        echo '<html lang="fr">';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<title>Erreur - STM v2</title>';
        echo '<style>';
        echo 'body { font-family: system-ui, sans-serif; background: #1a1a1a; color: #fff; padding: 20px; }';
        echo '.error-box { background: #2d2d2d; border-left: 4px solid #ef4444; padding: 20px; border-radius: 8px; }';
        echo 'h1 { color: #ef4444; margin-top: 0; }';
        echo 'pre { background: #1a1a1a; padding: 15px; border-radius: 5px; overflow-x: auto; }';
        echo 'code { color: #60a5fa; }';
        echo '</style>';
        echo '</head>';
        echo '<body>';
        echo '<div class="error-box">';
        echo '<h1>⚠️ Erreur de l\'application</h1>';
        echo '<p><strong>Message :</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p><strong>Fichier :</strong> ' . htmlspecialchars($e->getFile()) . '</p>';
        echo '<p><strong>Ligne :</strong> ' . $e->getLine() . '</p>';
        echo '<details>';
        echo '<summary style="cursor: pointer; padding: 10px 0;"><strong>Voir la trace complète</strong></summary>';
        echo '<pre><code>' . htmlspecialchars($e->getTraceAsString()) . '</code></pre>';
        echo '</details>';
        echo '</div>';
        echo '</body>';
        echo '</html>';
    } else {
        // En production, afficher une page d'erreur générique
        http_response_code(500);
        echo '<!DOCTYPE html>';
        echo '<html lang="fr">';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<title>Erreur - STM v2</title>';
        echo '<style>';
        echo 'body { font-family: system-ui, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); ';
        echo 'display: flex; align-items: center; justify-center; min-height: 100vh; margin: 0; }';
        echo '.error-card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); max-width: 500px; text-align: center; }';
        echo 'h1 { color: #667eea; margin: 0 0 20px 0; font-size: 24px; }';
        echo 'p { color: #666; line-height: 1.6; margin: 0 0 20px 0; }';
        echo 'a { display: inline-block; background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; }';
        echo 'a:hover { background: #5568d3; }';
        echo '</style>';
        echo '</head>';
        echo '<body>';
        echo '<div class="error-card">';
        echo '<h1>❌ Une erreur est survenue</h1>';
        echo '<p>Nous sommes désolés, mais une erreur inattendue s\'est produite.</p>';
        echo '<p>Veuillez réessayer dans quelques instants.</p>';
        echo '<a href="/stm/admin/login">← Retour à la page de connexion</a>';
        echo '</div>';
        echo '</body>';
        echo '</html>';
    }
    
    // Logger l'erreur (optionnel)
    error_log(sprintf(
        "[STM v2] Erreur: %s dans %s ligne %d",
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));
    
    exit;
}
