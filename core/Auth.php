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
 * @modified 2025/12/10 - Ajout stockage utilisateur complet en session pour PermissionHelper
 * @modified 2026/01/08 - Message plus explicite pour compte désactivé
 */

namespace Core;

class Auth
{
    /**
     * Nom de la session pour l'utilisateur connectÃ©
     */
    private const SESSION_USER_KEY = 'user_id';

    /**
     * Nom du cookie "Remember me"
     */
    private const REMEMBER_COOKIE_NAME = 'remember_token';

    /**
     * DurÃ©e du cookie "Remember me" (30 jours)
     */
    private const REMEMBER_COOKIE_LIFETIME = 60 * 60 * 24 * 30;

    /**
     * Nombre maximum de tentatives de connexion
     */
    private const MAX_LOGIN_ATTEMPTS = 5;

    /**
     * DurÃ©e de verrouillage en minutes
     */
    private const LOCKOUT_TIME = 15;

    /**
     * Tente de connecter un utilisateur
     *
     * @param string $username Nom d'utilisateur
     * @param string $password Mot de passe
     * @param bool $remember Remember me
     * @return array RÃ©sultat avec ['success' => bool, 'message' => string]
     */
    public static function attempt(string $username, string $password, bool $remember = false): array
    {
        try {
            $db = Database::getInstance()->getConnection();

            // RÃ©cupÃ©rer l'utilisateur (avec tous les champs nÃ©cessaires)
            $stmt = $db->prepare("
                SELECT id, username, email, name, password, role, is_active,
                       rep_id, rep_country, microsoft_id,
                       login_attempts, locked_until
                FROM users
                WHERE username = ? OR email = ?
                LIMIT 1
            ");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Utilisateur n'existe pas
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Identifiants incorrects.'
                ];
            }

            // VÃ©rifier si le compte est verrouillÃ©
            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                $minutesLeft = ceil((strtotime($user['locked_until']) - time()) / 60);
                return [
                    'success' => false,
                    'message' => "Compte verrouillÃ©. RÃ©essayez dans $minutesLeft minute(s)."
                ];
            }

            // Vérifier si le compte est actif (supporte 'active' et 'is_active')
            $isActive = $user['is_active'] ?? $user['active'] ?? 1;
            if (!$isActive) {
                return [
                    'success' => false,
                    'message' => 'Ce compte a été désactivé. Veuillez contacter un administrateur.'
                ];
            }

            // VÃ©rifier le mot de passe
            if (!password_verify($password, $user['password'])) {
                // IncrÃ©menter les tentatives
                self::incrementLoginAttempts($user['id'], $user['login_attempts'] ?? 0);

                $attemptsLeft = self::MAX_LOGIN_ATTEMPTS - (($user['login_attempts'] ?? 0) + 1);

                if ($attemptsLeft <= 0) {
                    return [
                        'success' => false,
                        'message' => 'Trop de tentatives. Compte verrouillÃ© pour ' . self::LOCKOUT_TIME . ' minutes.'
                    ];
                }

                return [
                    'success' => false,
                    'message' => "Identifiants incorrects. Il vous reste $attemptsLeft tentative(s)."
                ];
            }

            // Connexion rÃ©ussie : rÃ©initialiser les tentatives
            self::resetLoginAttempts($user['id']);

            // ============================================
            // STOCKER L'UTILISATEUR EN SESSION
            // ============================================

            // Anciennes clÃ©s (compatibilitÃ©)
            Session::set(self::SESSION_USER_KEY, $user['id']);
            Session::set('user_username', $user['username']);
            Session::set('user_role', $user['role']);

            // NOUVEAU : Stocker l'utilisateur complet pour PermissionHelper
            Session::set('user', [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'name' => $user['name'],
                'role' => $user['role'],
                'rep_id' => $user['rep_id'] ?? null,
                'rep_country' => $user['rep_country'] ?? null,
                'microsoft_id' => $user['microsoft_id'] ?? null
            ]);

            // Mettre Ã  jour last_login_at
            $stmt = $db->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);

            // RÃ©gÃ©nÃ©rer l'ID de session (sÃ©curitÃ©)
            Session::regenerate();

            // Gestion du "Remember me"
            if ($remember) {
                self::setRememberToken($user['id']);
            }

            return [
                'success' => true,
                'message' => 'Connexion rÃ©ussie !'
            ];

        } catch (\Exception $e) {
            // Logger l'erreur
            error_log('Erreur Auth::attempt() : ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Une erreur est survenue. Veuillez rÃ©essayer.'
            ];
        }
    }

    /**
     * IncrÃ©mente les tentatives de connexion
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
     * RÃ©initialise les tentatives de connexion
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
     * CrÃ©e un token "Remember me"
     *
     * @param int $userId ID de l'utilisateur
     * @return void
     */
    private static function setRememberToken(int $userId): void
    {
        // GÃ©nÃ©rer un token alÃ©atoire
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

        // CrÃ©er le cookie (token en clair)
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
     * VÃ©rifie si l'utilisateur est connectÃ©
     *
     * @return bool
     */
    public static function check(): bool
    {
        // VÃ©rifier la session
        if (Session::has(self::SESSION_USER_KEY)) {
            return true;
        }

        // VÃ©rifier le cookie "Remember me"
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

            // RÃ©cupÃ©rer tous les utilisateurs avec un token (avec tous les champs)
            $stmt = $db->prepare("
                SELECT id, username, email, name, role, remember_token,
                       rep_id, rep_country, microsoft_id
                FROM users
                WHERE remember_token IS NOT NULL
                AND is_active = 1
            ");
            $stmt->execute();
            $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // VÃ©rifier chaque token (car hashÃ©)
            foreach ($users as $user) {
                if (password_verify($token, $user['remember_token'])) {
                    // Token valide : connecter l'utilisateur

                    // Anciennes clÃ©s (compatibilitÃ©)
                    Session::set(self::SESSION_USER_KEY, $user['id']);
                    Session::set('user_username', $user['username']);
                    Session::set('user_role', $user['role']);

                    // NOUVEAU : Stocker l'utilisateur complet
                    Session::set('user', [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'name' => $user['name'],
                        'role' => $user['role'],
                        'rep_id' => $user['rep_id'] ?? null,
                        'rep_country' => $user['rep_country'] ?? null,
                        'microsoft_id' => $user['microsoft_id'] ?? null
                    ]);

                    // Mettre Ã  jour last_login_at
                    $stmt = $db->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);

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
     * RÃ©cupÃ¨re l'ID de l'utilisateur connectÃ©
     *
     * @return int|null
     */
    public static function id(): ?int
    {
        return Session::get(self::SESSION_USER_KEY);
    }

    /**
     * RÃ©cupÃ¨re les informations de l'utilisateur connectÃ©
     *
     * @return array|null
     */
    public static function user(): ?array
    {
        // D'abord essayer la session (plus rapide)
        $sessionUser = Session::get('user');
        if ($sessionUser) {
            return $sessionUser;
        }

        // Sinon charger depuis la DB
        $userId = self::id();

        if (!$userId) {
            return null;
        }

        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                SELECT id, username, email, name, role, rep_id, rep_country, microsoft_id, created_at
                FROM users
                WHERE id = ?
                LIMIT 1
            ");
            $stmt->execute([$userId]);

            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($user) {
                // Stocker en session pour les prochains appels
                Session::set('user', $user);
            }

            return $user ?: null;

        } catch (\Exception $e) {
            error_log('Erreur Auth::user() : ' . $e->getMessage());
            return null;
        }
    }

    /**
     * DÃ©connecte l'utilisateur
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

        // DÃ©truire la session
        Session::destroy();
    }
}