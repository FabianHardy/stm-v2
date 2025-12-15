<?php
/**
 * Middleware : PermissionMiddleware
 *
 * Vérifie les permissions avant d'exécuter une action
 * Redirige vers dashboard avec message d'erreur si non autorisé
 *
 * @package STM
 * @created 2025/12/12
 * @modified 2025/12/15 - Correction namespace + Session::setFlash
 */

namespace Middleware;

use App\Helpers\PermissionHelper;
use Core\Session;

class PermissionMiddleware
{
    /**
     * Vérifie si l'utilisateur a la permission requise
     * Redirige vers le dashboard si non autorisé
     *
     * @param string $permission Permission requise (ex: 'campaigns.create')
     * @param string|null $redirectUrl URL de redirection (défaut: dashboard)
     * @return bool true si autorisé
     */
    public static function require(string $permission, ?string $redirectUrl = null): bool
    {
        if (PermissionHelper::can($permission)) {
            return true;
        }

        // Message d'erreur (utiliser setFlash, pas flash)
        Session::setFlash('error', 'Vous n\'avez pas la permission d\'accéder à cette page.');

        // Redirection
        $url = $redirectUrl ?? '/stm/admin/dashboard';
        header('Location: ' . $url);
        exit;
    }

    /**
     * Vérifie si l'utilisateur a une des permissions (OR)
     *
     * @param array $permissions Liste de permissions
     * @param string|null $redirectUrl
     * @return bool
     */
    public static function requireAny(array $permissions, ?string $redirectUrl = null): bool
    {
        foreach ($permissions as $permission) {
            if (PermissionHelper::can($permission)) {
                return true;
            }
        }

        Session::setFlash('error', 'Vous n\'avez pas la permission d\'accéder à cette page.');

        $url = $redirectUrl ?? '/stm/admin/dashboard';
        header('Location: ' . $url);
        exit;
    }

    /**
     * Vérifie si l'utilisateur a toutes les permissions (AND)
     *
     * @param array $permissions Liste de permissions
     * @param string|null $redirectUrl
     * @return bool
     */
    public static function requireAll(array $permissions, ?string $redirectUrl = null): bool
    {
        foreach ($permissions as $permission) {
            if (!PermissionHelper::can($permission)) {
                Session::setFlash('error', 'Vous n\'avez pas la permission d\'accéder à cette page.');

                $url = $redirectUrl ?? '/stm/admin/dashboard';
                header('Location: ' . $url);
                exit;
            }
        }

        return true;
    }

    /**
     * Vérifie l'accès à une campagne spécifique
     *
     * @param int $campaignId
     * @param string $action 'view', 'edit', 'assign'
     * @param string|null $redirectUrl
     * @return bool
     */
    public static function requireCampaignAccess(int $campaignId, string $action = 'view', ?string $redirectUrl = null): bool
    {
        $hasAccess = false;

        switch ($action) {
            case 'view':
                $hasAccess = PermissionHelper::canViewCampaign($campaignId);
                break;
            case 'edit':
                $hasAccess = PermissionHelper::canEditCampaign($campaignId);
                break;
            case 'assign':
                $hasAccess = PermissionHelper::canAssignToCampaign($campaignId);
                break;
            default:
                $hasAccess = false;
        }

        if ($hasAccess) {
            return true;
        }

        Session::setFlash('error', 'Vous n\'avez pas accès à cette campagne.');

        $url = $redirectUrl ?? '/stm/admin/campaigns';
        header('Location: ' . $url);
        exit;
    }

    /**
     * Vérifie un rôle spécifique
     *
     * @param array $roles Rôles autorisés
     * @param string|null $redirectUrl
     * @return bool
     */
    public static function requireRole(array $roles, ?string $redirectUrl = null): bool
    {
        if (PermissionHelper::hasRole($roles)) {
            return true;
        }

        Session::setFlash('error', 'Vous n\'avez pas la permission d\'accéder à cette page.');

        $url = $redirectUrl ?? '/stm/admin/dashboard';
        header('Location: ' . $url);
        exit;
    }
}