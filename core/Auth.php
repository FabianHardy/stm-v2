<?php
/**
 * Classe Auth
 * 
 * Gestion de l'authentification des utilisateurs.
 * 
 * Fonctionnalités :
 * - Connexion (avec brute force protection)
 * - Déconnexion
 * - Vérification de connexion
 * - Remember me (cookies sécurisés)
 * - Verrouillage de compte
 * 
 * @package STM
 * @version 2.0
 */

namespace Core;

class Auth
{
    /**
     * Nom de la session pour l'utilisateur connecté
     */
    private const SESSION_USER_KEY = 'user_id';
    
    /**
     * Nom du cookie "Remember me"
     */
    private const REMEMBER_COOKIE_NAME = 'remember_token';
    
    /**
     * Durée du cookie "Remember me" (30 jours)
     */
    private const REMEMBER_COOKIE_LIFETIME = 60 * 60 * 24 * 30;
    
    /**
     * Nombre maximum de tentatives de connexion
     */
    private const MAX_LOGIN_ATTEMPTS = 5;
    
    /**
     * Durée de verrouillage en minutes
     */
    private const LOCKOUT_TIME = 15;
    
    /**
     * Tente de connecter un utilisateur
     * 
     * @param string $username Nom d'utilisateur
     * @param string $password Mot de passe
     * @param bool $remember Remember me
     * @return array Résultat avec ['success' => bool, 'message' => string]
     */
    public static function attempt(string $username, string $password, bool $remember = false): array
    {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Récupérer l'utilisateur
            $stmt = $db->prepare("
                SELECT id, username, password, role, active, login_attempts, locked_until
                FROM users 
                WHERE username = ?
                LIMIT 1
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            // Utilisateur n'existe pas
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Identifiants incorrects.'
                ];
            }
            
            // Vérifier si le compte est verrouillé
            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                $minutesLeft = ceil((strtotime($user['locked_until']) - time()) / 60);
                return [
                    'success' => false,
                    'message' => "Compte verrouillé. Réessayez dans $minutesLeft minute(s)."
                ];
            }
            
            // Vérifier si le compte est actif
            if (!$user['active']) {
                return [
                    'success' => false,
                    'message' => 'Ce compte est désactivé.'
                ];
            }
            
            // Vérifier le mot de passe
            if (!password_verify($password, $user['password'])) {
                // Incrémenter les tentatives
                self::incrementLoginAttempts($user['id'], $user['login_attempts']);
                
                $attemptsLeft = self::MAX_LOGIN_ATTEMPTS - ($user['login_attempts'] + 1);
                
                if ($attemptsLeft <= 0) {
                    return [
                        'success' => false,
                        'message' => 'Trop de tentatives. Compte verrouillé pour ' . self::LOCKOUT_TIME . ' minutes.'
                    ];
                }
                
                return [
                    'success' => false,
                    'message' => "Identifiants incorrects. Il vous reste $attemptsLeft tentative(s)."
                ];
            }
            
            // Connexion réussie : réinitialiser les tentatives
            self::resetLoginAttempts($user['id']);
            
            // Stocker l'utilisateur en session
            Session::set(self::SESSION_USER_KEY, $user['id']);
            Session::set('user_username', $user['username']);
            Session::set('user_role', $user['role']);
            
            // Régénérer l'ID de session (sécurité)
            Session::regenerate();
            
            // Gestion du "Remember me"
            if ($remember) {
                self::setRememberToken($user['id']);
            }
            
            return [
                'success' => true,
                'message' => 'Connexion réussie !'
            ];
            
        } catch (\Exception $e) {
            // Logger l'erreur
            error_log('Erreur Auth::attempt() : ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Une erreur est survenue. Veuillez réessayer.'
            ];
        }
    }
    
    /**
     * Incrémente les tentatives de connexion
     * 
     * @param int $userId ID de l'utilisateur
     * @param int $currentAttempts Tentatives actuelles
     * @return void
     */
    private static function incrementLoginAttempts(int $userId, int $currentAttempts): void
    {
        $db = Database::getInstance()->getConnection();
        $newAttempts = $currentAttempts + 1;
        
        // Si maximum atteint, verrouiller le compte
        if ($newAttempts >= self::MAX_LOGIN_ATTEMPTS) {
            $lockedUntil = date('Y-m-d H:i:s', time() + (self::LOCKOUT_TIME * 60));
            $stmt = $db->prepare("
                UPDATE users 
                SET login_attempts = ?, locked_until = ?
                WHERE id = ?
            ");
            $stmt->execute([$newAttempts, $lockedUntil, $userId]);
        } else {
            $stmt = $db->prepare("
                UPDATE users 
                SET login_attempts = ?
                WHERE id = ?
            ");
            $stmt->execute([$newAttempts, $userId]);
        }
    }
    
    /**
     * Réinitialise les tentatives de connexion
     * 
     * @param int $userId ID de l'utilisateur
     * @return void
     */
    private static function resetLoginAttempts(int $userId): void
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            UPDATE users 
            SET login_attempts = 0, locked_until = NULL
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
    }
    
    /**
     * Crée un token "Remember me"
     * 
     * @param int $userId ID de l'utilisateur
     * @return void
     */
    private static function setRememberToken(int $userId): void
    {
        // Générer un token aléatoire
        $token = bin2hex(random_bytes(32));
        
        // Hasher le token pour la BDD
        $hashedToken = password_hash($token, PASSWORD_DEFAULT);
        
        // Stocker dans la BDD
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            UPDATE users 
            SET remember_token = ?
            WHERE id = ?
        ");
        $stmt->execute([$hashedToken, $userId]);
        
        // Créer le cookie (token en clair)
        setcookie(
            self::REMEMBER_COOKIE_NAME,
            $token,
            time() + self::REMEMBER_COOKIE_LIFETIME,
            '/',
            '',
            isset($_ENV['SESSION_SECURE']) && $_ENV['SESSION_SECURE'] === 'true',
            true
        );
    }
    
    /**
     * Vérifie si l'utilisateur est connecté
     * 
     * @return bool
     */
    public static function check(): bool
    {
        // Vérifier la session
        if (Session::has(self::SESSION_USER_KEY)) {
            return true;
        }
        
        // Vérifier le cookie "Remember me"
        if (isset($_COOKIE[self::REMEMBER_COOKIE_NAME])) {
            return self::loginFromRememberToken($_COOKIE[self::REMEMBER_COOKIE_NAME]);
        }
        
        return false;
    }
    
    /**
     * Connexion via le token "Remember me"
     * 
     * @param string $token Token du cookie
     * @return bool
     */
    private static function loginFromRememberToken(string $token): bool
    {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Récupérer tous les utilisateurs avec un token
            $stmt = $db->prepare("
                SELECT id, username, role, remember_token
                FROM users 
                WHERE remember_token IS NOT NULL
                AND active = 1
            ");
            $stmt->execute();
            $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Vérifier chaque token (car hashé)
            foreach ($users as $user) {
                if (password_verify($token, $user['remember_token'])) {
                    // Token valide : connecter l'utilisateur
                    Session::set(self::SESSION_USER_KEY, $user['id']);
                    Session::set('user_username', $user['username']);
                    Session::set('user_role', $user['role']);
                    Session::regenerate();
                    
                    return true;
                }
            }
            
            // Token invalide : supprimer le cookie
            setcookie(self::REMEMBER_COOKIE_NAME, '', time() - 3600, '/');
            
            return false;
            
        } catch (\Exception $e) {
            error_log('Erreur loginFromRememberToken() : ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère l'ID de l'utilisateur connecté
     * 
     * @return int|null
     */
    public static function id(): ?int
    {
        return Session::get(self::SESSION_USER_KEY);
    }
    
    /**
     * Récupère les informations de l'utilisateur connecté
     * 
     * @return array|null
     */
    public static function user(): ?array
    {
        $userId = self::id();
        
        if (!$userId) {
            return null;
        }
        
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                SELECT id, username, email, role, created_at
                FROM users 
                WHERE id = ?
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
            
        } catch (\Exception $e) {
            error_log('Erreur Auth::user() : ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Déconnecte l'utilisateur
     * 
     * @return void
     */
    public static function logout(): void
    {
        // Supprimer le token "Remember me" de la BDD
        $userId = self::id();
        if ($userId) {
            try {
                $db = Database::getInstance()->getConnection();
                $stmt = $db->prepare("
                    UPDATE users 
                    SET remember_token = NULL
                    WHERE id = ?
                ");
                $stmt->execute([$userId]);
            } catch (\Exception $e) {
                error_log('Erreur logout() : ' . $e->getMessage());
            }
        }
        
        // Supprimer le cookie
        if (isset($_COOKIE[self::REMEMBER_COOKIE_NAME])) {
            setcookie(self::REMEMBER_COOKIE_NAME, '', time() - 3600, '/');
        }
        
        // Détruire la session
        Session::destroy();
    }
}
