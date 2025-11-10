<?php
/**
 * Classe Session
 * 
 * Gestion sécurisée des sessions PHP.
 * 
 * Fonctionnalités :
 * - Démarrage sécurisé de session
 * - Gestion des données de session
 * - Messages flash
 * - Tokens CSRF
 * - Régénération d'ID
 * 
 * @package STM
 * @version 2.0
 * @modified 10/11/2025 - Ajout setFlash() et amélioration getFlash()
 */

namespace Core;

class Session
{
    /**
     * Démarre la session si elle n'est pas déjà démarrée
     * 
     * @return void
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Configuration sécurisée de la session
            ini_set('session.cookie_httponly', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_samesite', 'Lax');
            
            // En production (HTTPS), activer le cookie secure
            if (isset($_ENV['SESSION_SECURE']) && $_ENV['SESSION_SECURE'] === 'true') {
                ini_set('session.cookie_secure', '1');
            }
            
            session_start();
            
            // Générer un token CSRF si absent
            if (!self::has('csrf_token')) {
                self::set('csrf_token', self::generateCsrfToken());
            }
        }
    }
    
    /**
     * Récupère une valeur de session
     * 
     * @param string $key Clé à récupérer
     * @param mixed $default Valeur par défaut si la clé n'existe pas
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Définit une valeur en session
     * 
     * @param string $key Clé
     * @param mixed $value Valeur
     * @return void
     */
    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }
    
    /**
     * Vérifie si une clé existe en session
     * 
     * @param string $key Clé à vérifier
     * @return bool
     */
    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }
    
    /**
     * Supprime une clé de la session
     * 
     * @param string $key Clé à supprimer
     * @return void
     */
    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }
    
    /**
     * Détruit complètement la session
     * 
     * @return void
     */
    public static function destroy(): void
    {
        $_SESSION = [];
        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        session_destroy();
    }
    
    /**
     * Définit un message flash (disponible une seule fois)
     * Compatible avec strings uniquement (legacy)
     * 
     * @param string $key Clé du message (success, error, warning, info)
     * @param string $message Message
     * @return void
     */
    public static function flash(string $key, string $message): void
    {
        $_SESSION["flash_$key"] = $message;
    }
    
    /**
     * Définit une valeur flash (disponible une seule fois)
     * Supporte tous les types : string, array, object, etc.
     * 
     * @param string $key Clé du message (success, error, errors, old, etc.)
     * @param mixed $value Valeur à stocker (string, array, object...)
     * @return void
     * @created 10/11/2025
     */
    public static function setFlash(string $key, $value): void
    {
        $_SESSION["flash_$key"] = $value;
    }
    
    /**
     * Récupère une valeur flash et la supprime
     * Supporte une valeur par défaut si la clé n'existe pas
     * 
     * @param string $key Clé du message
     * @param mixed $default Valeur par défaut si la clé n'existe pas
     * @return mixed
     * @modified 10/11/2025 - Ajout support valeur par défaut
     */
    public static function getFlash(string $key, $default = null)
    {
        $value = $_SESSION["flash_$key"] ?? $default;
        
        if (isset($_SESSION["flash_$key"])) {
            unset($_SESSION["flash_$key"]);
        }
        
        return $value;
    }
    
    /**
     * Vérifie si un message flash existe
     * 
     * @param string $key Clé du message
     * @return bool
     * @created 10/11/2025
     */
    public static function hasFlash(string $key): bool
    {
        return isset($_SESSION["flash_$key"]);
    }
    
    /**
     * Régénère l'ID de session (sécurité contre session fixation)
     * 
     * @param bool $deleteOldSession Supprimer l'ancienne session
     * @return void
     */
    public static function regenerate(bool $deleteOldSession = true): void
    {
        session_regenerate_id($deleteOldSession);
    }
    
    /**
     * Génère un token CSRF
     * 
     * @return string
     */
    public static function generateCsrfToken(): string
    {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Récupère le token CSRF actuel
     * 
     * @return string
     */
    public static function getCsrfToken(): string
    {
        if (!self::has('csrf_token')) {
            self::set('csrf_token', self::generateCsrfToken());
        }
        
        return self::get('csrf_token');
    }
    
    /**
     * Valide un token CSRF
     * 
     * @param string $token Token à valider
     * @return bool
     */
    public static function validateCsrfToken(string $token): bool
    {
        $sessionToken = self::get('csrf_token');
        
        if (empty($sessionToken) || empty($token)) {
            return false;
        }
        
        return hash_equals($sessionToken, $token);
    }
    
    /**
     * Vérifie si la session a expiré (timeout)
     * 
     * @param int $lifetime Durée de vie en secondes (par défaut: 7200 = 2 heures)
     * @return bool
     */
    public static function isExpired(int $lifetime = 7200): bool
    {
        if (!self::has('last_activity')) {
            self::set('last_activity', time());
            return false;
        }
        
        $lastActivity = self::get('last_activity');
        
        if (time() - $lastActivity > $lifetime) {
            return true;
        }
        
        // Mettre à jour le timestamp d'activité
        self::set('last_activity', time());
        
        return false;
    }
    
    /**
     * Récupère toutes les données de session
     * 
     * @return array
     */
    public static function all(): array
    {
        return $_SESSION;
    }
    
    /**
     * Vide toutes les données de session sans détruire la session
     * 
     * @return void
     */
    public static function flush(): void
    {
        $_SESSION = [];
    }
    
    /**
     * Récupère et supprime une valeur (similaire à pull dans Laravel)
     * 
     * @param string $key Clé
     * @param mixed $default Valeur par défaut
     * @return mixed
     */
    public static function pull(string $key, $default = null)
    {
        $value = self::get($key, $default);
        self::remove($key);
        return $value;
    }
}