<?php
/**
 * Fichier : Router.php
 * Description : Classe de gestion des routes de l'application
 *              Analyse les URLs et dispatche vers les contrôleurs appropriés
 * 
 * Auteur : Fabian Hardy
 * Date : 04/11/2025
 * Version : 1.0
 */

declare(strict_types=1);

namespace Core;

use Exception;

/**
 * Classe Router
 * 
 * Gère le routage des requêtes HTTP vers les contrôleurs
 * Support des routes GET, POST, PUT, DELETE
 * Support des paramètres dynamiques dans les URLs
 */
class Router
{
    /**
     * Routes enregistrées
     */
    private array $routes = [
        'GET'    => [],
        'POST'   => [],
        'PUT'    => [],
        'DELETE' => []
    ];

    /**
     * Préfixe de groupe de routes
     */
    private string $groupPrefix = '';

    /**
     * Middlewares de groupe
     */
    private array $groupMiddlewares = [];

    /**
     * Route actuelle
     */
    private ?array $currentRoute = null;

    /**
     * Enregistrer une route GET
     * 
     * @param string $path Chemin de la route
     * @param callable|array $handler Callable ou [Controller, 'method']
     * @param array $middlewares Middlewares à appliquer
     * @return self Pour le chaînage
     */
    public function get(string $path, callable|array $handler, array $middlewares = []): self
    {
        return $this->addRoute('GET', $path, $handler, $middlewares);
    }

    /**
     * Enregistrer une route POST
     * 
     * @param string $path Chemin de la route
     * @param callable|array $handler Callable ou [Controller, 'method']
     * @param array $middlewares Middlewares à appliquer
     * @return self Pour le chaînage
     */
    public function post(string $path, callable|array $handler, array $middlewares = []): self
    {
        return $this->addRoute('POST', $path, $handler, $middlewares);
    }

    /**
     * Enregistrer une route PUT
     * 
     * @param string $path Chemin de la route
     * @param callable|array $handler Callable ou [Controller, 'method']
     * @param array $middlewares Middlewares à appliquer
     * @return self Pour le chaînage
     */
    public function put(string $path, callable|array $handler, array $middlewares = []): self
    {
        return $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    /**
     * Enregistrer une route DELETE
     * 
     * @param string $path Chemin de la route
     * @param callable|array $handler Callable ou [Controller, 'method']
     * @param array $middlewares Middlewares à appliquer
     * @return self Pour le chaînage
     */
    public function delete(string $path, callable|array $handler, array $middlewares = []): self
    {
        return $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    /**
     * Créer un groupe de routes avec préfixe
     * 
     * @param string $prefix Préfixe du groupe
     * @param callable $callback Fonction contenant les routes
     * @param array $middlewares Middlewares du groupe
     * @return self Pour le chaînage
     */
    public function group(string $prefix, callable $callback, array $middlewares = []): self
    {
        $previousPrefix = $this->groupPrefix;
        $previousMiddlewares = $this->groupMiddlewares;

        $this->groupPrefix = $previousPrefix . $prefix;
        $this->groupMiddlewares = array_merge($previousMiddlewares, $middlewares);

        $callback($this);

        $this->groupPrefix = $previousPrefix;
        $this->groupMiddlewares = $previousMiddlewares;

        return $this;
    }

    /**
     * Ajouter une route
     * 
     * @param string $method Méthode HTTP
     * @param string $path Chemin de la route
     * @param callable|array $handler Handler de la route
     * @param array $middlewares Middlewares
     * @return self Pour le chaînage
     */
    private function addRoute(
        string $method,
        string $path,
        callable|array $handler,
        array $middlewares = []
    ): self {
        $path = $this->groupPrefix . $path;
        $middlewares = array_merge($this->groupMiddlewares, $middlewares);

        $this->routes[$method][$path] = [
            'handler' => $handler,
            'middlewares' => $middlewares,
            'pattern' => $this->compilePattern($path)
        ];

        return $this;
    }

    /**
     * Compiler un pattern de route en regex
     * 
     * @param string $path Chemin de la route
     * @return string Pattern regex
     */
    private function compilePattern(string $path): string
    {
        // Remplacer {param} par un groupe de capture
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $path);
        
        // Échapper les slashes et ajouter les délimiteurs
        $pattern = '#^' . str_replace('/', '\/', $pattern) . '$#';
        
        return $pattern;
    }

    /**
     * Dispatcher la requête vers le handler approprié
     * 
     * @param string $uri URI de la requête
     * @param string $method Méthode HTTP
     * @return mixed Résultat du handler
     * @throws Exception Si aucune route ne correspond
     */
    public function dispatch(string $uri, string $method = 'GET'): mixed
    {
        // Nettoyer l'URI
        $uri = $this->cleanUri($uri);
        $method = strtoupper($method);

        // Chercher une route correspondante
        $route = $this->findRoute($uri, $method);

        if ($route === null) {
            throw new Exception("Route non trouvée : {$method} {$uri}", 404);
        }

        $this->currentRoute = $route;

        // Exécuter les middlewares
        $middlewareResult = $this->executeMiddlewares($route['middlewares']);
        
        if ($middlewareResult !== true) {
            return $middlewareResult;
        }

        // Exécuter le handler
        return $this->executeHandler($route['handler'], $route['params']);
    }

    /**
     * Trouver une route correspondante
     * 
     * @param string $uri URI de la requête
     * @param string $method Méthode HTTP
     * @return array|null Route trouvée ou null
     */
    private function findRoute(string $uri, string $method): ?array
    {
        if (!isset($this->routes[$method])) {
            return null;
        }

        foreach ($this->routes[$method] as $path => $route) {
            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extraire les paramètres nommés
                $params = array_filter(
                    $matches,
                    fn($key) => is_string($key),
                    ARRAY_FILTER_USE_KEY
                );

                return [
                    'handler' => $route['handler'],
                    'middlewares' => $route['middlewares'],
                    'params' => $params,
                    'path' => $path
                ];
            }
        }

        return null;
    }

    /**
     * Nettoyer l'URI
     * 
     * @param string $uri URI à nettoyer
     * @return string URI nettoyée
     */
    private function cleanUri(string $uri): string
    {
        // Supprimer la query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        // Supprimer les slashes en trop
        $uri = trim($uri, '/');

        return '/' . $uri;
    }

    /**
     * Exécuter les middlewares
     * 
     * @param array $middlewares Liste des middlewares
     * @return mixed true si tous passent, sinon la réponse du middleware
     */
    private function executeMiddlewares(array $middlewares): mixed
    {
        foreach ($middlewares as $middleware) {
            if (is_string($middleware)) {
                // Instancier le middleware
                if (!class_exists($middleware)) {
                    throw new Exception("Middleware non trouvé : {$middleware}");
                }
                $middleware = new $middleware();
            }

            // Exécuter le middleware
            $result = $middleware->handle();

            // Si le middleware retourne quelque chose d'autre que true, on arrête
            if ($result !== true) {
                return $result;
            }
        }

        return true;
    }

    /**
     * Exécuter le handler de la route
     * 
     * @param callable|array $handler Handler à exécuter
     * @param array $params Paramètres extraits de l'URL
     * @return mixed Résultat du handler
     * @throws Exception Si le handler est invalide
     */
    private function executeHandler(callable|array $handler, array $params = []): mixed
    {
        // Si c'est un callable direct
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        }

        // Si c'est un tableau [Controller, 'method']
        if (is_array($handler) && count($handler) === 2) {
            [$controllerClass, $method] = $handler;

            // Vérifier que la classe existe
            if (!class_exists($controllerClass)) {
                throw new Exception("Contrôleur non trouvé : {$controllerClass}");
            }

            // Instancier le contrôleur
            $controller = new $controllerClass();

            // Vérifier que la méthode existe
            if (!method_exists($controller, $method)) {
                throw new Exception(
                    "Méthode non trouvée : {$controllerClass}::{$method}"
                );
            }

            // Exécuter la méthode
            return call_user_func_array([$controller, $method], $params);
        }

        throw new Exception("Handler invalide");
    }

    /**
     * Générer une URL pour une route nommée
     * 
     * @param string $path Chemin de la route
     * @param array $params Paramètres à injecter
     * @return string URL générée
     */
    public function url(string $path, array $params = []): string
    {
        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', (string) $value, $path);
        }

        return $path;
    }

    /**
     * Obtenir la route actuelle
     * 
     * @return array|null Route actuelle
     */
    public function getCurrentRoute(): ?array
    {
        return $this->currentRoute;
    }

    /**
     * Obtenir toutes les routes enregistrées
     * 
     * @return array Routes enregistrées
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}