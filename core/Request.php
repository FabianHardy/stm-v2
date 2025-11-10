<?php
/**
 * Fichier : Request.php
 * Description : Classe de gestion des requêtes HTTP
 *              Encapsule $_GET, $_POST, $_SERVER, $_FILES avec validation
 * 
 * Auteur : Fabian Hardy
 * Date : 04/11/2025
 * Version : 1.0
 */

declare(strict_types=1);

namespace Core;

/**
 * Classe Request
 * 
 * Gère les requêtes HTTP entrantes et fournit des méthodes
 * pour accéder aux données de manière sécurisée
 */
class Request
{
    /**
     * Méthode HTTP de la requête
     */
    private string $method;

    /**
     * URI de la requête
     */
    private string $uri;

    /**
     * Paramètres GET
     */
    private array $query;

    /**
     * Paramètres POST
     */
    private array $post;

    /**
     * Fichiers uploadés
     */
    private array $files;

    /**
     * Headers de la requête
     */
    private array $headers;

    /**
     * Corps de la requête (pour PUT/DELETE/PATCH)
     */
    private ?string $body;

    /**
     * Données JSON parsées
     */
    private ?array $json = null;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->method = $this->detectMethod();
        $this->uri = $this->detectUri();
        $this->query = $_GET;
        $this->post = $_POST;
        $this->files = $_FILES;
        $this->headers = $this->parseHeaders();
        $this->body = $this->parseBody();
    }

    /**
     * Détecter la méthode HTTP
     * 
     * @return string Méthode HTTP
     */
    private function detectMethod(): string
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // Support de _method dans POST pour simuler PUT/DELETE
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        return $method;
    }

    /**
     * Détecter l'URI de la requête
     * 
     * @return string URI
     */
    private function detectUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Supprimer la query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        return $uri;
    }

    /**
     * Parser les headers HTTP
     * 
     * @return array Headers
     */
    private function parseHeaders(): array
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $header = str_replace('_', '-', substr($key, 5));
                $headers[$header] = $value;
            }
        }

        return $headers;
    }

    /**
     * Parser le corps de la requête
     * 
     * @return string|null Corps de la requête
     */
    private function parseBody(): ?string
    {
        if (in_array($this->method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return file_get_contents('php://input');
        }

        return null;
    }

    /**
     * Obtenir la méthode HTTP
     * 
     * @return string Méthode HTTP
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * Obtenir l'URI
     * 
     * @return string URI
     */
    public function uri(): string
    {
        return $this->uri;
    }

    /**
     * Obtenir le chemin sans query string
     * 
     * @return string Chemin
     */
    public function path(): string
    {
        return parse_url($this->uri, PHP_URL_PATH);
    }

    /**
     * Vérifier si la méthode est GET
     * 
     * @return bool True si GET
     */
    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    /**
     * Vérifier si la méthode est POST
     * 
     * @return bool True si POST
     */
    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    /**
     * Vérifier si la méthode est PUT
     * 
     * @return bool True si PUT
     */
    public function isPut(): bool
    {
        return $this->method === 'PUT';
    }

    /**
     * Vérifier si la méthode est DELETE
     * 
     * @return bool True si DELETE
     */
    public function isDelete(): bool
    {
        return $this->method === 'DELETE';
    }

    /**
     * Vérifier si la requête est AJAX
     * 
     * @return bool True si AJAX
     */
    public function isAjax(): bool
    {
        return isset($this->headers['X-REQUESTED-WITH']) 
            && strtolower($this->headers['X-REQUESTED-WITH']) === 'xmlhttprequest';
    }

    /**
     * Vérifier si la requête attend du JSON
     * 
     * @return bool True si JSON attendu
     */
    public function wantsJson(): bool
    {
        return $this->isAjax() || 
               str_contains($this->header('Accept', ''), 'application/json');
    }

    /**
     * Obtenir tous les paramètres (GET + POST)
     * 
     * @return array Tous les paramètres
     */
    public function all(): array
    {
        return array_merge($this->query, $this->post, $this->json ?? []);
    }

    /**
     * Obtenir un paramètre spécifique
     * 
     * @param string $key Clé du paramètre
     * @param mixed $default Valeur par défaut
     * @return mixed Valeur du paramètre
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /**
     * Obtenir un paramètre GET
     * 
     * @param string $key Clé du paramètre
     * @param mixed $default Valeur par défaut
     * @return mixed Valeur du paramètre
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Obtenir un paramètre POST
     * 
     * @param string $key Clé du paramètre
     * @param mixed $default Valeur par défaut
     * @return mixed Valeur du paramètre
     */
    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    /**
     * Vérifier si un paramètre existe
     * 
     * @param string $key Clé du paramètre
     * @return bool True si existe
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    /**
     * Vérifier si plusieurs paramètres existent
     * 
     * @param array $keys Clés des paramètres
     * @return bool True si tous existent
     */
    public function hasAll(array $keys): bool
    {
        $all = $this->all();
        
        foreach ($keys as $key) {
            if (!array_key_exists($key, $all)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Obtenir uniquement certains paramètres
     * 
     * @param array $keys Clés à récupérer
     * @return array Paramètres filtrés
     */
    public function only(array $keys): array
    {
        $all = $this->all();
        $result = [];

        foreach ($keys as $key) {
            if (array_key_exists($key, $all)) {
                $result[$key] = $all[$key];
            }
        }

        return $result;
    }

    /**
     * Obtenir tous les paramètres sauf certains
     * 
     * @param array $keys Clés à exclure
     * @return array Paramètres filtrés
     */
    public function except(array $keys): array
    {
        $all = $this->all();

        foreach ($keys as $key) {
            unset($all[$key]);
        }

        return $all;
    }

    /**
     * Obtenir un fichier uploadé
     * 
     * @param string $key Clé du fichier
     * @return array|null Informations du fichier ou null
     */
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Vérifier si un fichier a été uploadé
     * 
     * @param string $key Clé du fichier
     * @return bool True si fichier uploadé
     */
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Obtenir un header HTTP
     * 
     * @param string $key Nom du header
     * @param mixed $default Valeur par défaut
     * @return mixed Valeur du header
     */
    public function header(string $key, mixed $default = null): mixed
    {
        $key = strtoupper(str_replace('-', '_', $key));
        return $this->headers[$key] ?? $default;
    }

    /**
     * Obtenir tous les headers
     * 
     * @return array Headers
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Obtenir l'IP du client
     * 
     * @return string IP du client
     */
    public function ip(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Obtenir le user agent
     * 
     * @return string User agent
     */
    public function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Obtenir les données JSON de la requête
     * 
     * @return array|null Données JSON ou null
     */
    public function json(): ?array
    {
        if ($this->json === null && $this->body !== null) {
            $this->json = json_decode($this->body, true) ?? [];
        }

        return $this->json;
    }

    /**
     * Obtenir le corps brut de la requête
     * 
     * @return string|null Corps de la requête
     */
    public function body(): ?string
    {
        return $this->body;
    }

    /**
     * Nettoyer une valeur (protection XSS)
     * 
     * @param string $value Valeur à nettoyer
     * @return string Valeur nettoyée
     */
    public function clean(string $value): string
    {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Obtenir l'URL complète
     * 
     * @return string URL complète
     */
    public function fullUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host . $this->uri;
    }

    /**
     * Obtenir l'URL précédente (referer)
     * 
     * @return string|null URL précédente ou null
     */
    public function referer(): ?string
    {
        return $_SERVER['HTTP_REFERER'] ?? null;
    }
}