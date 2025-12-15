<?php
/**
 * AuthMiddleware - Middleware de protection des routes admin
 * 
 * Ce middleware vérifie si l'utilisateur est authentifié avant de lui permettre
 * d'accéder aux routes protégées de l'interface d'administration.
 * Si l'utilisateur n'est pas connecté, il est redirigé vers la page de login.
 * 
 * @package    Middleware
 * @author     Fabian Hardy
 * @version    1.1.0
 */

namespace Middleware;

use Core\Auth;
use Core\Session;

/**
 * Class AuthMiddleware
 * 
 * Middleware d'authentification pour protéger les routes admin
 */
class AuthMiddleware
{
    /**
     * Gestionnaire du middleware
     * 
     * Vérifie si l'utilisateur est authentifié.
     * Si non authentifié, redirige vers la page de login avec un message flash.
     * Si authentifié, retourne true pour continuer.
     * 
     * @return bool True si authentifié, sinon redirige
     */
    public function handle(): bool
    {
        // Démarrer la session si ce n'est pas déjà fait
        if (session_status() === PHP_SESSION_NONE) {
            Session::start();
        }

        // Vérifier si l'utilisateur est authentifié
        if (!Auth::check()) {
            // Ajouter un message flash d'erreur
            Session::flash('error', 'Vous devez être connecté pour accéder à cette page.');
            
            // Rediriger vers la page de login avec le bon préfixe /stm/
            header('Location: /stm/admin/login');
            exit;
        }

        // Vérifier si le compte n'est pas verrouillé (tentatives de login)
        $user = Auth::user();
        if ($user && isset($user['locked_until'])) {
            $lockedUntil = strtotime($user['locked_until']);
            if ($lockedUntil > time()) {
                // Le compte est toujours verrouillé
                Session::flash('error', 'Votre compte est temporairement verrouillé. Réessayez plus tard.');
                Auth::logout();
                header('Location: /stm/admin/login');
                exit;
            }
        }

        // Vérifier si le compte est actif
        if ($user && isset($user['active']) && !$user['active']) {
            Session::flash('error', 'Votre compte a été désactivé. Contactez un administrateur.');
            Auth::logout();
            header('Location: /stm/admin/login');
            exit;
        }

        // Rafraîchir l'activité de l'utilisateur dans la session
        Session::set('last_activity', time());

        // L'utilisateur est authentifié et actif
        return true;
    }

    /**
     * Vérifier si l'utilisateur a un rôle spécifique
     * 
     * Méthode optionnelle pour vérifier les permissions selon le rôle
     * Peut être utilisée pour des routes nécessitant des rôles spécifiques
     * 
     * @param string $role Rôle requis (ex: 'admin', 'super_admin')
     * @return bool True si l'utilisateur a le rôle, false sinon
     */
    public static function hasRole(string $role): bool
    {
        $user = Auth::user();
        
        if (!$user || !isset($user['role'])) {
            return false;
        }

        return $user['role'] === $role;
    }

    /**
     * Middleware pour vérifier un rôle spécifique
     * 
     * Peut être utilisé pour créer des middlewares de rôle spécifiques
     * 
     * @param string $requiredRole Rôle requis
     * @return bool True si l'utilisateur a le rôle requis
     */
    public static function requireRole(string $requiredRole): bool
    {
        // Vérifier d'abord l'authentification
        $middleware = new self();
        if (!$middleware->handle()) {
            return false;
        }
        
        // Vérifier le rôle
        if (!self::hasRole($requiredRole)) {
            Session::flash('error', 'Vous n\'avez pas les permissions nécessaires pour accéder à cette page.');
            header('Location: /stm/admin/dashboard');
            exit;
        }
        
        return true;
    }
}