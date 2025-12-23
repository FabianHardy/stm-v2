<?php
/**
 * Helper : PermissionHelper
 *
 * Gestion centralisée des permissions et du scope d'accès
 *
 * Structure normalisée :
 * - Table `permissions` : liste des permissions disponibles (code, name, description, category)
 * - Table `role_permissions` : liaison role <-> permission_id avec granted (0/1)
 * - Table `permission_audit_log` : historique des modifications
 *
 * @package STM
 * @created 2025/12/10
 * @modified 2025/12/12 - Permissions dynamiques en base de données (structure normalisée)
 */

namespace App\Helpers;

use Core\Database;
use Core\Session;

class PermissionHelper
{
    /**
     * Cache des permissions chargées depuis la DB
     * Structure: ['role' => ['permission.code' => bool, ...], ...]
     */
    private static ?array $permissionsCache = null;

    /**
     * Cache des campagnes accessibles
     */
    private static ?array $campaignsCache = null;

    /**
     * Hiérarchie des rôles (1 = plus haut niveau)
     * Un utilisateur ne peut gérer que les rôles de niveau SUPÉRIEUR au sien
     */
    private const ROLE_HIERARCHY = [
        'superadmin' => 1,
        'admin' => 2,
        'createur' => 3,
        'manager_reps' => 4,
        'rep' => 5,
    ];

    // ========================================
    // MÉTHODES DE BASE
    // ========================================

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

        // Superadmin a TOUJOURS toutes les permissions (protection absolue)
        if ($role === 'superadmin') {
            return true;
        }

        // Charger les permissions depuis le cache ou la DB
        $permissions = self::loadPermissionsFromDB();

        return $permissions[$role][$permission] ?? false;
    }

    /**
     * Vérifie si l'utilisateur n'a PAS une permission (inverse de can)
     *
     * @param string $permission
     * @return bool
     */
    public static function cannot(string $permission): bool
    {
        return !self::can($permission);
    }

    /**
     * Charge les permissions depuis la base de données
     * Utilise un cache pour éviter les requêtes multiples
     *
     * @return array
     */
    private static function loadPermissionsFromDB(): array
    {
        if (self::$permissionsCache !== null) {
            return self::$permissionsCache;
        }

        try {
            $db = Database::getInstance();

            // JOIN entre role_permissions et permissions pour récupérer le code
            $results = $db->query(
                "SELECT rp.role, p.code, rp.granted
                 FROM role_permissions rp
                 INNER JOIN permissions p ON rp.permission_id = p.id"
            );

            self::$permissionsCache = [];

            foreach ($results as $row) {
                $role = $row['role'];
                $code = $row['code'];
                $granted = (bool) $row['granted'];

                if (!isset(self::$permissionsCache[$role])) {
                    self::$permissionsCache[$role] = [];
                }

                self::$permissionsCache[$role][$code] = $granted;
            }

            return self::$permissionsCache;

        } catch (\PDOException $e) {
            error_log("PermissionHelper::loadPermissionsFromDB - Erreur : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère toutes les permissions de l'utilisateur courant
     *
     * @return array
     */
    public static function getPermissions(): array
    {
        $user = self::getCurrentUser();

        if (!$user) {
            return [];
        }

        $role = $user['role'] ?? null;

        if (!$role) {
            return [];
        }

        // Superadmin : toutes les permissions
        if ($role === 'superadmin') {
            return self::getAllPermissionCodes(true);
        }

        $permissions = self::loadPermissionsFromDB();
        return $permissions[$role] ?? [];
    }

    /**
     * Récupère tous les codes de permissions depuis la DB
     *
     * @param bool $allGranted Si true, retourne tous les codes avec granted=true
     * @return array
     */
    private static function getAllPermissionCodes(bool $allGranted = false): array
    {
        try {
            $db = Database::getInstance();
            $results = $db->query("SELECT code FROM permissions ORDER BY sort_order");

            $perms = [];
            foreach ($results as $row) {
                $perms[$row['code']] = $allGranted ? true : false;
            }
            return $perms;
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Récupère les labels des permissions depuis la DB
     *
     * @return array ['code' => 'name', ...]
     */
    public static function getPermissionLabels(): array
    {
        try {
            $db = Database::getInstance();
            $results = $db->query("SELECT code, name FROM permissions ORDER BY sort_order");

            $labels = [];
            foreach ($results as $row) {
                $labels[$row['code']] = $row['name'];
            }
            return $labels;
        } catch (\PDOException $e) {
            return [];
        }
    }

    // ========================================
    // MÉTHODES POUR SETTINGSCONTROLLER
    // ========================================

    /**
     * Vérifie si un rôle est protégé (ne peut pas être modifié)
     *
     * @param string $role
     * @return bool
     */
    public static function isProtectedRole(string $role): bool
    {
        return $role === 'superadmin';
    }

    /**
     * Récupère la matrice des permissions pour l'affichage
     *
     * @return array ['roles' => [...], 'permissions' => [...], 'matrix' => [...]]
     */
    public static function getPermissionMatrix(): array
    {
        $roles = ['superadmin', 'admin', 'createur', 'manager_reps', 'rep'];
        $categories = self::getCategoryMapping();

        try {
            $db = Database::getInstance();

            // Récupérer toutes les permissions avec leurs métadonnées
            $permResults = $db->query(
                "SELECT id, code, name, description, category, sort_order
                 FROM permissions
                 ORDER BY sort_order"
            );

            // Construire la liste des permissions
            $permissions = [];
            $permissionIds = []; // code => id mapping
            foreach ($permResults as $row) {
                $permissions[] = [
                    'id' => $row['id'],
                    'code' => $row['code'],
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'category' => $row['category']
                ];
                $permissionIds[$row['code']] = $row['id'];
            }

            // Charger les permissions par rôle
            $dbPermissions = self::loadPermissionsFromDB();

            // Construire la matrice role => permissions
            $matrix = [];
            foreach ($roles as $role) {
                $matrix[$role] = [];
                foreach ($permissions as $perm) {
                    // Superadmin a toujours tout
                    if ($role === 'superadmin') {
                        $matrix[$role][$perm['code']] = true;
                    } else {
                        $matrix[$role][$perm['code']] = $dbPermissions[$role][$perm['code']] ?? false;
                    }
                }
            }

            return [
                'roles' => $roles,
                'permissions' => $permissions,
                'matrix' => $matrix,
                'permission_ids' => $permissionIds
            ];

        } catch (\PDOException $e) {
            error_log("PermissionHelper::getPermissionMatrix - Erreur : " . $e->getMessage());
            return [
                'roles' => $roles,
                'permissions' => [],
                'matrix' => [],
                'permission_ids' => []
            ];
        }
    }

    /**
     * Récupère les catégories de permissions pour l'affichage groupé
     *
     * @return array
     */
    public static function getCategories(): array
    {
        return self::getCategoryMapping();
    }

    /**
     * Mapping des catégories avec labels et icônes
     *
     * @return array
     */
    private static function getCategoryMapping(): array
    {
        return [
            'dashboard' => [
                'label' => 'Dashboard',
                'icon' => 'fa-tachometer-alt'
            ],
            'campaigns' => [
                'label' => 'Campagnes',
                'icon' => 'fa-bullhorn'
            ],
            'categories' => [
                'label' => 'Catégories',
                'icon' => 'fa-folder'
            ],
            'products' => [
                'label' => 'Promotions',
                'icon' => 'fa-tags'
            ],
            'customers' => [
                'label' => 'Clients',
                'icon' => 'fa-users'
            ],
            'orders' => [
                'label' => 'Commandes',
                'icon' => 'fa-shopping-cart'
            ],
            'stats' => [
                'label' => 'Statistiques',
                'icon' => 'fa-chart-bar'
            ],
            'admin' => [
                'label' => 'Administration',
                'icon' => 'fa-user-cog'
            ]
        ];
    }

    /**
     * Sauvegarde la matrice de permissions en base de données
     * Avec journalisation dans la table d'audit
     *
     * @param array $matrix ['role' => ['permission_code' => bool, ...], ...]
     * @return bool
     */
    public static function savePermissionMatrix(array $matrix): bool
    {
        $user = self::getCurrentUser();
        $userId = $user['id'] ?? null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

        try {
            $db = Database::getInstance();

            // Récupérer le mapping code => id des permissions
            $permissionIds = [];
            $results = $db->query("SELECT id, code FROM permissions");
            foreach ($results as $row) {
                $permissionIds[$row['code']] = $row['id'];
            }

            // Charger les valeurs actuelles pour comparaison (audit)
            $currentPermissions = self::loadPermissionsFromDB();

            foreach ($matrix as $role => $permissions) {
                // Ne pas modifier superadmin
                if (self::isProtectedRole($role)) {
                    continue;
                }

                foreach ($permissions as $permCode => $granted) {
                    // Vérifier que la permission existe
                    if (!isset($permissionIds[$permCode])) {
                        continue;
                    }

                    $permissionId = $permissionIds[$permCode];
                    $grantedValue = $granted ? 1 : 0;
                    $oldValue = isset($currentPermissions[$role][$permCode])
                        ? ($currentPermissions[$role][$permCode] ? 1 : 0)
                        : null;

                    // Ne rien faire si la valeur n'a pas changé
                    if ($oldValue === $grantedValue) {
                        continue;
                    }

                    // Mettre à jour ou insérer la permission
                    $db->query(
                        "INSERT INTO role_permissions (role, permission_id, granted, updated_by, updated_at)
                         VALUES (:role, :permission_id, :granted, :updated_by, NOW())
                         ON DUPLICATE KEY UPDATE
                            granted = VALUES(granted),
                            updated_by = VALUES(updated_by),
                            updated_at = NOW()",
                        [
                            ':role' => $role,
                            ':permission_id' => $permissionId,
                            ':granted' => $grantedValue,
                            ':updated_by' => $userId
                        ]
                    );

                    // Enregistrer dans l'audit log
                    $db->query(
                        "INSERT INTO permission_audit_log
                            (role, permission_code, old_value, new_value, changed_by, changed_at, ip_address)
                         VALUES
                            (:role, :permission_code, :old_value, :new_value, :changed_by, NOW(), :ip_address)",
                        [
                            ':role' => $role,
                            ':permission_code' => $permCode,
                            ':old_value' => $oldValue,
                            ':new_value' => $grantedValue,
                            ':changed_by' => $userId,
                            ':ip_address' => $ipAddress
                        ]
                    );
                }
            }

            // Vider le cache pour forcer le rechargement
            self::clearCache();

            return true;

        } catch (\PDOException $e) {
            error_log("PermissionHelper::savePermissionMatrix - Erreur : " . $e->getMessage());
            return false;
        }
    }

    // ========================================
    // GESTION HIÉRARCHIQUE DES PERMISSIONS
    // ========================================

    /**
     * Récupère le niveau hiérarchique d'un rôle
     *
     * @param string $role
     * @return int
     */
    public static function getRoleLevel(string $role): int
    {
        return self::ROLE_HIERARCHY[$role] ?? 99;
    }

    /**
     * Vérifie si l'utilisateur courant peut gérer un rôle cible
     *
     * @param string $targetRole
     * @return bool
     */
    public static function canManageRole(string $targetRole): bool
    {
        $user = self::getCurrentUser();

        if (!$user) {
            return false;
        }

        $userLevel = self::getRoleLevel($user['role']);
        $targetLevel = self::getRoleLevel($targetRole);

        return $targetLevel > $userLevel;
    }

    /**
     * Vérifie si l'utilisateur peut accorder une permission
     *
     * @param string $permission
     * @return bool
     */
    public static function canGrantPermission(string $permission): bool
    {
        return self::can($permission);
    }

    /**
     * Filtre les modifications de permissions autorisées
     *
     * @param array $requestedChanges
     * @return array
     */
    public static function filterAllowedPermissionChanges(array $requestedChanges): array
    {
        $user = self::getCurrentUser();
        $allowed = [];
        $denied = [];
        $errors = [];

        if (!$user) {
            return [
                'allowed' => [],
                'denied' => $requestedChanges,
                'errors' => ['Utilisateur non connecté']
            ];
        }

        foreach ($requestedChanges as $role => $permissions) {
            if (self::isProtectedRole($role)) {
                $denied[$role] = $permissions;
                $errors[] = "Le rôle '$role' est protégé";
                continue;
            }

            if (!self::canManageRole($role)) {
                $denied[$role] = $permissions;
                $errors[] = "Vous ne pouvez pas modifier le rôle '$role'";
                continue;
            }

            $allowed[$role] = [];
            foreach ($permissions as $permCode => $value) {
                $wantsToGrant = (bool) $value;

                if ($wantsToGrant && !self::canGrantPermission($permCode)) {
                    $denied[$role][$permCode] = $value;
                    $errors[] = "Vous ne pouvez pas accorder '$permCode'";
                    continue;
                }

                if (!$wantsToGrant && !self::canGrantPermission($permCode)) {
                    $denied[$role][$permCode] = $value;
                    $errors[] = "Vous ne pouvez pas retirer '$permCode'";
                    continue;
                }

                $allowed[$role][$permCode] = $wantsToGrant;
            }
        }

        return [
            'allowed' => $allowed,
            'denied' => $denied,
            'errors' => $errors
        ];
    }

    /**
     * Récupère les rôles gérables par l'utilisateur courant
     *
     * @return array
     */
    public static function getManageableRoles(): array
    {
        $user = self::getCurrentUser();

        if (!$user) {
            return [];
        }

        $userLevel = self::getRoleLevel($user['role']);
        $manageableRoles = [];

        foreach (self::ROLE_HIERARCHY as $role => $level) {
            if ($level > $userLevel) {
                $manageableRoles[] = $role;
            }
        }

        return $manageableRoles;
    }

    // ========================================
    // SCOPE : ACCÈS AUX CAMPAGNES
    // ========================================

    /**
     * Vérifie si l'utilisateur peut voir une campagne
     *
     * @param int $campaignId
     * @return bool
     */
    public static function canViewCampaign(int $campaignId): bool
    {
        $user = self::getCurrentUser();

        if (!$user) {
            return false;
        }

        if (in_array($user['role'], ['superadmin', 'admin'])) {
            return true;
        }

        if ($user['role'] === 'createur') {
            return self::isAssignedToCampaign($user['id'], $campaignId);
        }

        if (in_array($user['role'], ['manager_reps', 'rep'])) {
            return self::isCampaignActive($campaignId);
        }

        return false;
    }

    /**
     * Vérifie si l'utilisateur peut modifier une campagne
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

        if (!self::can('campaigns.edit')) {
            return false;
        }

        if (self::can('campaigns.edit_all')) {
            return true;
        }

        return self::isAssignedToCampaign($user['id'], $campaignId);
    }

    /**
     * Vérifie si l'utilisateur peut assigner sur une campagne
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

        if (!self::can('campaigns.assign')) {
            return false;
        }

        if (in_array($user['role'], ['superadmin', 'admin'])) {
            return true;
        }

        if ($user['role'] === 'createur') {
            return self::isOwnerOfCampaign($user['id'], $campaignId);
        }

        return false;
    }

    /**
     * Récupère les IDs des campagnes accessibles
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

        $db = Database::getInstance();

        if (in_array($user['role'], ['superadmin', 'admin'])) {
            $campaigns = $db->query("SELECT id FROM campaigns");
            self::$campaignsCache = array_column($campaigns, 'id');
            return self::$campaignsCache;
        }

        if ($user['role'] === 'createur') {
            $campaigns = $db->query(
                "SELECT campaign_id FROM campaign_assignees WHERE user_id = :user_id",
                [':user_id' => $user['id']]
            );
            self::$campaignsCache = array_column($campaigns, 'campaign_id');
            return self::$campaignsCache;
        }

        if (in_array($user['role'], ['manager_reps', 'rep'])) {
            $campaigns = $db->query("SELECT id FROM campaigns WHERE status = 'active'");
            self::$campaignsCache = array_column($campaigns, 'id');
            return self::$campaignsCache;
        }

        return [];
    }

    /**
     * Vérifie si l'utilisateur est assigné à une campagne
     */
    public static function isAssignedToCampaign(int $userId, int $campaignId): bool
    {
        $db = Database::getInstance();
        $result = $db->query(
            "SELECT id FROM campaign_assignees WHERE user_id = :user_id AND campaign_id = :campaign_id LIMIT 1",
            [':user_id' => $userId, ':campaign_id' => $campaignId]
        );
        return !empty($result);
    }

    /**
     * Vérifie si l'utilisateur est owner d'une campagne
     */
    public static function isOwnerOfCampaign(int $userId, int $campaignId): bool
    {
        $db = Database::getInstance();
        $result = $db->query(
            "SELECT id FROM campaign_assignees WHERE user_id = :user_id AND campaign_id = :campaign_id AND role = 'owner' LIMIT 1",
            [':user_id' => $userId, ':campaign_id' => $campaignId]
        );
        return !empty($result);
    }

    /**
     * Vérifie si une campagne est active
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

    private static function getCurrentUser(): ?array
    {
        return Session::get('user');
    }

    public static function clearCache(): void
    {
        self::$permissionsCache = null;
        self::$campaignsCache = null;
    }

    public static function getCurrentRole(): ?string
    {
        $user = self::getCurrentUser();
        return $user['role'] ?? null;
    }

    public static function hasRole(array $roles): bool
    {
        $currentRole = self::getCurrentRole();
        return $currentRole && in_array($currentRole, $roles);
    }

    public static function linkClasses(string $permission, string $enabledClasses = '', string $disabledClasses = 'opacity-50 cursor-not-allowed pointer-events-none'): string
    {
        return self::can($permission) ? $enabledClasses : $disabledClasses;
    }

    public static function linkUrl(string $permission, string $url): string
    {
        return self::can($permission) ? $url : '#';
    }
}