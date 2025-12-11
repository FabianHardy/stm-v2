<?php
/**
 * Helper : PermissionHelper
 *
 * Gestion centralisée des permissions et du scope d'accès
 *
 * Niveau 1 : Permissions (par rôle) - ce que l'utilisateur peut FAIRE
 * Niveau 2 : Scope (par assignation) - sur QUOI il peut agir
 *
 * @package STM
 * @created 2025/12/10
 * @modified 2025/12/10 - Lecture/écriture depuis la base de données
 */

namespace App\Helpers;

use Core\Database;
use Core\Session;

class PermissionHelper
{
    /**
     * Cache des permissions par rôle
     */
    private static ?array $permissionsCache = null;

    /**
     * Cache des campagnes accessibles
     */
    private static ?array $campaignsCache = null;

    /**
     * Rôles protégés (ne peuvent pas être modifiés via l'interface)
     */
    private const PROTECTED_ROLES = ['superadmin'];

    /**
     * Permissions protégées (ne peuvent pas être retirées au superadmin)
     */
    private const PROTECTED_PERMISSIONS = ['permissions.manage'];

    /**
     * Catégories de permissions pour l'affichage
     */
    private const CATEGORIES = [
        'dashboard' => ['label' => 'Dashboard', 'icon' => 'fa-chart-line'],
        'campaigns' => ['label' => 'Campagnes', 'icon' => 'fa-bullhorn'],
        'categories' => ['label' => 'Catégories', 'icon' => 'fa-folder'],
        'products' => ['label' => 'Promotions', 'icon' => 'fa-box'],
        'customers' => ['label' => 'Clients', 'icon' => 'fa-users'],
        'orders' => ['label' => 'Commandes', 'icon' => 'fa-shopping-cart'],
        'stats' => ['label' => 'Statistiques', 'icon' => 'fa-chart-bar'],
        'admin' => ['label' => 'Administration', 'icon' => 'fa-cog'],
    ];

    /**
     * Vérifie si l'utilisateur courant a une permission
     *
     * @param string $permission Code de la permission (ex: 'campaigns.create')
     * @return bool
     */
    public static function can(string $permission): bool
    {
        $user = self::getCurrentUser();

        if (!$user) {
            return false;
        }

        $role = $user['role'] ?? null;

        if (!$role) {
            return false;
        }

        // Superadmin a toujours toutes les permissions
        if ($role === 'superadmin') {
            return true;
        }

        // Charger les permissions depuis la DB
        $permissions = self::getPermissionsForRole($role);

        return in_array($permission, $permissions);
    }

    /**
     * Vérifie si l'utilisateur ne peut PAS faire une action
     *
     * @param string $permission
     * @return bool
     */
    public static function cannot(string $permission): bool
    {
        return !self::can($permission);
    }

    /**
     * Récupère les permissions d'un rôle depuis la DB
     *
     * @param string $role
     * @return array Liste des codes de permission
     */
    public static function getPermissionsForRole(string $role): array
    {
        // Vérifier le cache
        if (self::$permissionsCache !== null && isset(self::$permissionsCache[$role])) {
            return self::$permissionsCache[$role];
        }

        try {
            $db = Database::getInstance();

            $permissions = $db->query(
                "SELECT p.code
                 FROM role_permissions rp
                 JOIN permissions p ON p.id = rp.permission_id
                 WHERE rp.role = :role",
                [':role' => $role]
            );

            $result = array_column($permissions, 'code');

            // Mettre en cache
            if (self::$permissionsCache === null) {
                self::$permissionsCache = [];
            }
            self::$permissionsCache[$role] = $result;

            return $result;

        } catch (\Exception $e) {
            error_log("PermissionHelper::getPermissionsForRole error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère toutes les permissions disponibles
     *
     * @return array
     */
    public static function getAllPermissions(): array
    {
        try {
            $db = Database::getInstance();

            return $db->query(
                "SELECT id, code, name, category, description, sort_order
                 FROM permissions
                 ORDER BY sort_order, code"
            );

        } catch (\Exception $e) {
            error_log("PermissionHelper::getAllPermissions error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère la matrice complète des permissions (pour la page config)
     *
     * @return array ['roles' => [...], 'permissions' => [...], 'matrix' => [...]]
     */
    public static function getPermissionMatrix(): array
    {
        $roles = ['superadmin', 'admin', 'createur', 'manager_reps', 'rep'];
        $permissions = self::getAllPermissions();

        // Construire la matrice
        $matrix = [];
        foreach ($roles as $role) {
            $rolePermissions = self::getPermissionsForRole($role);
            foreach ($permissions as $perm) {
                $matrix[$role][$perm['code']] = in_array($perm['code'], $rolePermissions);
            }
        }

        return [
            'roles' => $roles,
            'permissions' => $permissions,
            'matrix' => $matrix
        ];
    }

    /**
     * Récupère les catégories de permissions
     *
     * @return array
     */
    public static function getCategories(): array
    {
        return self::CATEGORIES;
    }

    /**
     * Sauvegarde les permissions d'un rôle
     *
     * @param string $role
     * @param array $permissionCodes Liste des codes de permission à activer
     * @return bool
     */
    public static function saveRolePermissions(string $role, array $permissionCodes): bool
    {
        // Protection : ne pas modifier superadmin
        if (in_array($role, self::PROTECTED_ROLES)) {
            return false;
        }

        try {
            $db = Database::getInstance()->getConnection();

            // Début transaction
            $db->beginTransaction();

            // Supprimer les permissions actuelles du rôle
            $stmt = $db->prepare("DELETE FROM role_permissions WHERE role = ?");
            $stmt->execute([$role]);

            // Insérer les nouvelles permissions
            if (!empty($permissionCodes)) {
                $stmt = $db->prepare(
                    "INSERT INTO role_permissions (role, permission_id)
                     SELECT ?, id FROM permissions WHERE code = ?"
                );

                foreach ($permissionCodes as $code) {
                    $stmt->execute([$role, $code]);
                }
            }

            // Commit
            $db->commit();

            // Vider le cache
            self::clearCache();

            return true;

        } catch (\Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }
            error_log("PermissionHelper::saveRolePermissions error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sauvegarde toute la matrice des permissions
     *
     * @param array $matrix ['role' => ['permission_code' => bool, ...], ...]
     * @return bool
     */
    public static function savePermissionMatrix(array $matrix): bool
    {
        try {
            $db = Database::getInstance()->getConnection();
            $db->beginTransaction();

            foreach ($matrix as $role => $permissions) {
                // Protection : ne pas modifier superadmin
                if (in_array($role, self::PROTECTED_ROLES)) {
                    continue;
                }

                // Supprimer les permissions actuelles
                $stmt = $db->prepare("DELETE FROM role_permissions WHERE role = ?");
                $stmt->execute([$role]);

                // Insérer les nouvelles
                $enabledPermissions = array_keys(array_filter($permissions));

                if (!empty($enabledPermissions)) {
                    $stmt = $db->prepare(
                        "INSERT INTO role_permissions (role, permission_id)
                         SELECT ?, id FROM permissions WHERE code = ?"
                    );

                    foreach ($enabledPermissions as $code) {
                        $stmt->execute([$role, $code]);
                    }
                }
            }

            $db->commit();
            self::clearCache();

            return true;

        } catch (\Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }
            error_log("PermissionHelper::savePermissionMatrix error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie si un rôle est protégé
     *
     * @param string $role
     * @return bool
     */
    public static function isProtectedRole(string $role): bool
    {
        return in_array($role, self::PROTECTED_ROLES);
    }

    // ========================================
    // SCOPE : Accès aux ressources spécifiques
    // ========================================

    /**
     * Vérifie si l'utilisateur peut accéder à une campagne spécifique
     *
     * @param int $campaignId
     * @return bool
     */
    public static function canAccessCampaign(int $campaignId): bool
    {
        $user = self::getCurrentUser();

        if (!$user) {
            return false;
        }

        // Superadmin et Admin peuvent tout voir
        if (in_array($user['role'], ['superadmin', 'admin'])) {
            return true;
        }

        // Créateur : vérifie s'il est assigné
        if ($user['role'] === 'createur') {
            return self::isAssignedToCampaign($user['id'], $campaignId);
        }

        // Manager et Rep : accès aux campagnes actives (lecture seule)
        if (in_array($user['role'], ['manager_reps', 'rep'])) {
            return self::isCampaignActive($campaignId);
        }

        return false;
    }

    /**
     * Vérifie si l'utilisateur peut MODIFIER une campagne spécifique
     *
     * @param int $campaignId
     * @return bool
     */
    public static function canEditCampaign(int $campaignId): bool
    {
        $user = self::getCurrentUser();

        if (!$user) {
            return false;
        }

        // Permission de base requise
        if (!self::can('campaigns.edit')) {
            return false;
        }

        // Peut modifier toutes les campagnes
        if (self::can('campaigns.edit_all')) {
            return true;
        }

        // Sinon, vérifie l'assignation
        return self::isAssignedToCampaign($user['id'], $campaignId);
    }

    /**
     * Vérifie si l'utilisateur peut assigner des collaborateurs à une campagne
     *
     * @param int $campaignId
     * @return bool
     */
    public static function canAssignToCampaign(int $campaignId): bool
    {
        $user = self::getCurrentUser();

        if (!$user) {
            return false;
        }

        // Permission de base requise
        if (!self::can('campaigns.assign')) {
            return false;
        }

        // Superadmin et Admin peuvent assigner sur toutes les campagnes
        if (in_array($user['role'], ['superadmin', 'admin'])) {
            return true;
        }

        // Créateur : peut assigner seulement s'il est owner
        if ($user['role'] === 'createur') {
            return self::isOwnerOfCampaign($user['id'], $campaignId);
        }

        return false;
    }

    /**
     * Récupère les IDs des campagnes accessibles à l'utilisateur
     *
     * @return array
     */
    public static function getAccessibleCampaignIds(): array
    {
        if (self::$campaignsCache !== null) {
            return self::$campaignsCache;
        }

        $user = self::getCurrentUser();

        if (!$user) {
            return [];
        }

        // Superadmin et Admin : toutes les campagnes
        if (in_array($user['role'], ['superadmin', 'admin'])) {
            $db = Database::getInstance();
            $campaigns = $db->query("SELECT id FROM campaigns");
            self::$campaignsCache = array_column($campaigns, 'id');
            return self::$campaignsCache;
        }

        // Créateur : ses campagnes assignées
        if ($user['role'] === 'createur') {
            $db = Database::getInstance();
            $campaigns = $db->query(
                "SELECT campaign_id FROM campaign_assignees WHERE user_id = :user_id",
                [':user_id' => $user['id']]
            );
            self::$campaignsCache = array_column($campaigns, 'campaign_id');
            return self::$campaignsCache;
        }

        // Manager et Rep : campagnes actives
        if (in_array($user['role'], ['manager_reps', 'rep'])) {
            $db = Database::getInstance();
            $campaigns = $db->query(
                "SELECT id FROM campaigns WHERE status = 'active'"
            );
            self::$campaignsCache = array_column($campaigns, 'id');
            return self::$campaignsCache;
        }

        return [];
    }

    /**
     * Vérifie si l'utilisateur est assigné à une campagne
     *
     * @param int $userId
     * @param int $campaignId
     * @return bool
     */
    public static function isAssignedToCampaign(int $userId, int $campaignId): bool
    {
        $db = Database::getInstance();

        $result = $db->query(
            "SELECT id FROM campaign_assignees
             WHERE user_id = :user_id AND campaign_id = :campaign_id
             LIMIT 1",
            [':user_id' => $userId, ':campaign_id' => $campaignId]
        );

        return !empty($result);
    }

    /**
     * Vérifie si l'utilisateur est owner d'une campagne
     *
     * @param int $userId
     * @param int $campaignId
     * @return bool
     */
    public static function isOwnerOfCampaign(int $userId, int $campaignId): bool
    {
        $db = Database::getInstance();

        $result = $db->query(
            "SELECT id FROM campaign_assignees
             WHERE user_id = :user_id AND campaign_id = :campaign_id AND role = 'owner'
             LIMIT 1",
            [':user_id' => $userId, ':campaign_id' => $campaignId]
        );

        return !empty($result);
    }

    /**
     * Vérifie si une campagne est active
     *
     * @param int $campaignId
     * @return bool
     */
    private static function isCampaignActive(int $campaignId): bool
    {
        $db = Database::getInstance();

        $result = $db->query(
            "SELECT id FROM campaigns WHERE id = :id AND status = 'active' LIMIT 1",
            [':id' => $campaignId]
        );

        return !empty($result);
    }

    // ========================================
    // HELPERS INTERNES
    // ========================================

    /**
     * Récupère l'utilisateur courant depuis la session
     *
     * @return array|null
     */
    private static function getCurrentUser(): ?array
    {
        return Session::get('user');
    }

    /**
     * Réinitialise les caches
     */
    public static function clearCache(): void
    {
        self::$permissionsCache = null;
        self::$campaignsCache = null;
    }

    /**
     * Récupère le rôle de l'utilisateur courant
     *
     * @return string|null
     */
    public static function getCurrentRole(): ?string
    {
        $user = self::getCurrentUser();
        return $user['role'] ?? null;
    }

    /**
     * Vérifie si l'utilisateur a un des rôles spécifiés
     *
     * @param array $roles
     * @return bool
     */
    public static function hasRole(array $roles): bool
    {
        $currentRole = self::getCurrentRole();
        return $currentRole && in_array($currentRole, $roles);
    }

    /**
     * Génère les classes CSS pour un lien en fonction de la permission
     *
     * @param string $permission
     * @param string $enabledClasses Classes si permission OK
     * @param string $disabledClasses Classes si permission NOK
     * @return string
     */
    public static function linkClasses(string $permission, string $enabledClasses = '', string $disabledClasses = 'opacity-50 cursor-not-allowed pointer-events-none'): string
    {
        return self::can($permission) ? $enabledClasses : $disabledClasses;
    }

    /**
     * Génère l'URL ou '#' selon la permission
     *
     * @param string $permission
     * @param string $url
     * @return string
     */
    public static function linkUrl(string $permission, string $url): string
    {
        return self::can($permission) ? $url : '#';
    }
}