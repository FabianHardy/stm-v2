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
 * HÉRITAGE DES DONNÉES (SCOPE) :
 * - superadmin : Voit TOUT
 * - admin : Voit TOUT
 * - createur : Voit uniquement SES campagnes et données liées
 * - manager_reps : Voit SES reps + leurs clients
 * - rep : Voit uniquement SES clients
 *
 * @package STM
 * @created 2025/12/10
 * @modified 2025/12/12 - Permissions dynamiques en base de données (structure normalisée)
 * @modified 2025/12/30 - Ajout méthodes de scope pour Orders, refactoring général
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
     * Cache des clients accessibles
     */
    private static ?array $customersCache = null;

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
            return ['roles' => $roles, 'permissions' => [], 'matrix' => [], 'permission_ids' => []];
        }
    }

    /**
     * Sauvegarde la matrice des permissions
     *
     * @param array $matrix ['role' => ['permission_code' => bool, ...], ...]
     * @return bool
     */
    public static function savePermissionMatrix(array $matrix): bool
    {
        try {
            $db = Database::getInstance();

            // Récupérer le mapping code => id des permissions
            $permResults = $db->query("SELECT id, code FROM permissions");
            $permissionIds = [];
            foreach ($permResults as $row) {
                $permissionIds[$row['code']] = $row['id'];
            }

            // Pour chaque rôle et permission, mettre à jour
            foreach ($matrix as $role => $permissions) {
                // Superadmin ne doit pas être modifié (toujours tout)
                if ($role === 'superadmin') {
                    continue;
                }

                foreach ($permissions as $permCode => $granted) {
                    if (!isset($permissionIds[$permCode])) {
                        continue; // Permission inconnue
                    }

                    $permId = $permissionIds[$permCode];
                    $grantedValue = $granted ? 1 : 0;

                    // Vérifier si l'entrée existe
                    $existing = $db->query(
                        "SELECT id FROM role_permissions WHERE role = ? AND permission_id = ?",
                        [$role, $permId]
                    );

                    if (!empty($existing)) {
                        // Update
                        $db->query(
                            "UPDATE role_permissions SET granted = ?, updated_at = NOW() WHERE role = ? AND permission_id = ?",
                            [$grantedValue, $role, $permId]
                        );
                    } else {
                        // Insert
                        $db->query(
                            "INSERT INTO role_permissions (role, permission_id, granted, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())",
                            [$role, $permId, $grantedValue]
                        );
                    }
                }
            }

            // Vider le cache des permissions
            self::$permissionsCache = null;

            return true;

        } catch (\PDOException $e) {
            error_log("PermissionHelper::savePermissionMatrix - Erreur : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mapping des catégories (code => label)
     * Alias: getCategories() pour compatibilité avec SettingsController
     *
     * @return array
     */
    public static function getCategoryMapping(): array
    {
        return [
            'agent' => 'Agent / Chatbot',
            'dashboard' => 'Dashboard',
            'campaigns' => 'Campagnes',
            'categories' => 'Catégories',
            'products' => 'Promotions',
            'customers' => 'Clients',
            'stats' => 'Statistiques',
            'orders' => 'Commandes',
            'admin' => 'Administration',
            'translations' => 'Traductions',
            'static_pages' => 'Pages statiques',
            'email_templates' => 'Email templates',
            'internal_accounts' => 'Comptes internes',
        ];
    }

    /**
     * Alias de getCategoryMapping() pour compatibilité
     *
     * @return array
     */
    public static function getCategories(): array
    {
        return self::getCategoryMapping();
    }

    /**
     * Récupère le niveau d'un rôle
     *
     * @param string $role
     * @return int
     */
    public static function getRoleLevel(string $role): int
    {
        return self::ROLE_HIERARCHY[$role] ?? 999;
    }

    /**
     * Vérifie si l'utilisateur peut gérer un rôle
     *
     * @param string $role
     * @return bool
     */
    public static function canManageRole(string $role): bool
    {
        $user = self::getCurrentUser();

        if (!$user) {
            return false;
        }

        $userLevel = self::getRoleLevel($user['role']);
        $targetLevel = self::getRoleLevel($role);

        return $targetLevel > $userLevel;
    }

    /**
     * Vérifie si l'utilisateur peut accorder une permission
     *
     * @param string $permissionCode
     * @return bool
     */
    public static function canGrantPermission(string $permissionCode): bool
    {
        return self::can($permissionCode);
    }

    /**
     * Valide les changements de permissions demandés
     *
     * @param array $requestedChanges
     * @return array
     */
    public static function validatePermissionChanges(array $requestedChanges): array
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
    // SCOPE : ACCÈS AUX COMMANDES
    // ========================================

    /**
     * Vérifie si l'utilisateur peut voir une commande
     *
     * @param int $orderId
     * @return bool
     */
    public static function canViewOrder(int $orderId): bool
    {
        $user = self::getCurrentUser();

        if (!$user) {
            return false;
        }

        // Admin et superadmin voient tout
        if (in_array($user['role'], ['superadmin', 'admin'])) {
            return true;
        }

        $db = Database::getInstance();

        // Récupérer la commande avec infos client et campagne
        $order = $db->queryOne("
            SELECT o.campaign_id, cu.customer_number, cu.country
            FROM orders o
            INNER JOIN customers cu ON o.customer_id = cu.id
            WHERE o.id = :id
        ", [':id' => $orderId]);

        if (!$order) {
            return false;
        }

        // Créateur : vérifier qu'il est assigné à la campagne
        if ($user['role'] === 'createur') {
            return self::isAssignedToCampaign($user['id'], $order['campaign_id']);
        }

        // Manager/Rep : vérifier l'accès au client
        if (in_array($user['role'], ['manager_reps', 'rep'])) {
            $accessibleCustomers = self::getAccessibleCustomerNumbers();
            if ($accessibleCustomers === null) {
                return true; // Accès illimité
            }
            return in_array($order['customer_number'], $accessibleCustomers);
        }

        return false;
    }

    /**
     * Récupère les numéros clients accessibles par l'utilisateur
     * Délègue à StatsAccessHelper pour cohérence avec l'existant
     *
     * @return array|null Liste des customer_number accessibles ou null si tout accessible
     */
    public static function getAccessibleCustomerNumbers(): ?array
    {
        if (self::$customersCache !== null) {
            return self::$customersCache;
        }

        $user = self::getCurrentUser();

        if (!$user) {
            return [];
        }

        // Admin et superadmin : pas de restriction
        if (in_array($user['role'], ['superadmin', 'admin'])) {
            self::$customersCache = null; // null = tout accessible
            return null;
        }

        // Utiliser StatsAccessHelper qui gère déjà toute la logique
        // pour créateur, manager_reps et rep
        if (class_exists('App\Helpers\StatsAccessHelper')) {
            $result = \App\Helpers\StatsAccessHelper::getAccessibleCustomerNumbersOnly();
            self::$customersCache = $result;
            return $result;
        }

        // Fallback si StatsAccessHelper n'est pas disponible
        return self::getAccessibleCustomerNumbersFallback();
    }

    /**
     * Fallback pour récupérer les clients accessibles si StatsAccessHelper n'est pas disponible
     *
     * @return array
     */
    private static function getAccessibleCustomerNumbersFallback(): array
    {
        $user = self::getCurrentUser();
        $db = Database::getInstance();

        // Créateur : clients des campagnes auxquelles il est assigné
        if ($user['role'] === 'createur') {
            $campaignIds = self::getAccessibleCampaignIds();
            if (empty($campaignIds)) {
                return [];
            }

            $placeholders = implode(',', array_fill(0, count($campaignIds), '?'));
            $result = $db->query("
                SELECT DISTINCT cu.customer_number
                FROM customers cu
                INNER JOIN orders o ON cu.id = o.customer_id
                WHERE o.campaign_id IN ({$placeholders})
            ", $campaignIds);

            return array_column($result, 'customer_number');
        }

        // Manager_reps : clients de ses reps
        if ($user['role'] === 'manager_reps') {
            $reps = $db->query("
                SELECT rep_id, rep_country
                FROM manager_reps
                WHERE manager_id = :manager_id
            ", [':manager_id' => $user['id']]);

            if (empty($reps)) {
                return [];
            }

            $customerNumbers = [];
            foreach ($reps as $rep) {
                $repCustomers = self::getCustomersForRep($rep['rep_id'], $rep['rep_country']);
                $customerNumbers = array_merge($customerNumbers, $repCustomers);
            }

            return array_unique($customerNumbers);
        }

        // Rep : ses propres clients
        if ($user['role'] === 'rep') {
            $repInfo = $db->queryOne("
                SELECT rep_id, rep_country
                FROM users
                WHERE id = :user_id
            ", [':user_id' => $user['id']]);

            if (!$repInfo || empty($repInfo['rep_id'])) {
                return [];
            }

            return self::getCustomersForRep($repInfo['rep_id'], $repInfo['rep_country']);
        }

        return [];
    }

    /**
     * Récupère les clients d'un représentant depuis la DB externe (BE_CLL/LU_CLL)
     *
     * @param string $repId IDE_REP dans la table BE_REP/LU_REP
     * @param string $country 'BE' ou 'LU'
     * @return array Liste des CLL_NCLIXX
     */
    private static function getCustomersForRep(string $repId, string $country): array
    {
        try {
            $extDb = \Core\ExternalDatabase::getInstance();
            $table = $country === 'BE' ? 'BE_CLL' : 'LU_CLL';

            $result = $extDb->query("
                SELECT CLL_NCLIXX as customer_number
                FROM {$table}
                WHERE IDE_REP = ?
            ", [$repId]);

            return array_column($result, 'customer_number');
        } catch (\Exception $e) {
            error_log("PermissionHelper::getCustomersForRep - Erreur : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Vérifie si l'utilisateur a accès illimité aux données
     * (superadmin ou admin)
     *
     * @return bool
     */
    public static function hasFullAccess(): bool
    {
        $user = self::getCurrentUser();
        return $user && in_array($user['role'], ['superadmin', 'admin']);
    }

    /**
     * Retourne la clause SQL WHERE pour filtrer les commandes selon le scope
     *
     * @param string $orderAlias Alias de la table orders (ex: 'o')
     * @param string $customerAlias Alias de la table customers (ex: 'cu')
     * @return array ['sql' => string, 'params' => array]
     */
    public static function getOrderScopeFilter(string $orderAlias = 'o', string $customerAlias = 'cu'): array
    {
        $user = self::getCurrentUser();

        if (!$user) {
            return ['sql' => '1=0', 'params' => []]; // Aucun accès
        }

        // Admin et superadmin : pas de restriction
        if (in_array($user['role'], ['superadmin', 'admin'])) {
            return ['sql' => '1=1', 'params' => []];
        }

        // Créateur : ses campagnes
        if ($user['role'] === 'createur') {
            $campaignIds = self::getAccessibleCampaignIds();
            if (empty($campaignIds)) {
                return ['sql' => '1=0', 'params' => []];
            }
            $placeholders = implode(',', array_fill(0, count($campaignIds), '?'));
            return [
                'sql' => "{$orderAlias}.campaign_id IN ({$placeholders})",
                'params' => $campaignIds
            ];
        }

        // Manager_reps et rep : leurs clients
        if (in_array($user['role'], ['manager_reps', 'rep'])) {
            $customerNumbers = self::getAccessibleCustomerNumbers();
            if ($customerNumbers === null) {
                return ['sql' => '1=1', 'params' => []]; // Accès illimité
            }
            if (empty($customerNumbers)) {
                return ['sql' => '1=0', 'params' => []];
            }
            $placeholders = implode(',', array_fill(0, count($customerNumbers), '?'));
            return [
                'sql' => "{$customerAlias}.customer_number IN ({$placeholders})",
                'params' => $customerNumbers
            ];
        }

        return ['sql' => '1=0', 'params' => []];
    }

    // ========================================
    // HELPERS INTERNES
    // ========================================

    /**
     * Récupère l'utilisateur courant (prend en compte l'impersonation)
     *
     * @return array|null
     */
    private static function getCurrentUser(): ?array
    {
        // Si impersonation active, utiliser l'utilisateur impersoné
        if (Session::get('impersonate_original_user') !== null) {
            return Session::get('user');
        }
        return Session::get('user');
    }

    /**
     * Récupère l'ID de l'utilisateur courant
     *
     * @return int|null
     */
    public static function getUserId(): ?int
    {
        $user = self::getCurrentUser();
        return $user['id'] ?? null;
    }

    /**
     * Récupère les données de l'utilisateur courant (méthode publique)
     *
     * @return array|null
     */
    public static function getUser(): ?array
    {
        return self::getCurrentUser();
    }

    /**
     * Efface tous les caches
     *
     * @return void
     */
    public static function clearCache(): void
    {
        self::$permissionsCache = null;
        self::$campaignsCache = null;
        self::$customersCache = null;
    }

    /**
     * Récupère le rôle courant
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
     * Retourne les classes CSS selon la permission
     *
     * @param string $permission
     * @param string $enabledClasses
     * @param string $disabledClasses
     * @return string
     */
    public static function linkClasses(string $permission, string $enabledClasses = '', string $disabledClasses = 'opacity-50 cursor-not-allowed pointer-events-none'): string
    {
        return self::can($permission) ? $enabledClasses : $disabledClasses;
    }

    /**
     * Retourne l'URL ou # selon la permission
     *
     * @param string $permission
     * @param string $url
     * @return string
     */
    public static function linkUrl(string $permission, string $url): string
    {
        return self::can($permission) ? $url : '#';
    }

    /**
     * Filtre les changements de permissions autorisés
     * Un utilisateur ne peut modifier que les permissions des rôles de niveau inférieur
     * et ne peut accorder que les permissions qu'il possède lui-même
     *
     * @param array $changes Array de changements ['role' => ['permission' => bool, ...], ...]
     * @return array ['allowed' => [...], 'denied' => [...], 'errors' => [...]]
     */
    public static function filterAllowedPermissionChanges(array $changes): array
    {
        $user = self::getCurrentUser();
        $result = [
            'allowed' => [],
            'denied' => [],
            'errors' => []
        ];

        if (!$user) {
            $result['errors'][] = 'Utilisateur non connecté';
            return $result;
        }

        $userRole = $user['role'] ?? null;
        $manageableRoles = self::getManageableRoles();

        foreach ($changes as $role => $permissions) {
            // Vérifier si l'utilisateur peut gérer ce rôle
            if (!in_array($role, $manageableRoles)) {
                $result['denied'][$role] = $permissions;
                $result['errors'][] = "Vous ne pouvez pas modifier les permissions du rôle '{$role}'";
                continue;
            }

            $allowedPerms = [];
            $deniedPerms = [];

            foreach ($permissions as $permCode => $value) {
                // Superadmin peut tout modifier
                if ($userRole === 'superadmin') {
                    $allowedPerms[$permCode] = $value;
                    continue;
                }

                // Admin peut modifier si ce n'est pas pour superadmin
                if ($userRole === 'admin') {
                    // Un admin ne peut accorder que les permissions qu'il possède
                    if ($value && !self::can($permCode)) {
                        $deniedPerms[$permCode] = $value;
                        $result['errors'][] = "Vous ne pouvez pas accorder la permission '{$permCode}' que vous ne possédez pas";
                    } else {
                        $allowedPerms[$permCode] = $value;
                    }
                    continue;
                }

                // Autres rôles : refuser
                $deniedPerms[$permCode] = $value;
            }

            if (!empty($allowedPerms)) {
                $result['allowed'][$role] = $allowedPerms;
            }
            if (!empty($deniedPerms)) {
                $result['denied'][$role] = $deniedPerms;
            }
        }

        return $result;
    }

    /**
     * Vérifie si l'utilisateur peut modifier les permissions d'un rôle
     *
     * @param string $targetRole Le rôle dont on veut modifier les permissions
     * @return bool
     */
    public static function canEditRolePermissions(string $targetRole): bool
    {
        $user = self::getCurrentUser();

        if (!$user) {
            return false;
        }

        $userRole = $user['role'] ?? null;

        // Superadmin peut tout modifier
        if ($userRole === 'superadmin') {
            return true;
        }

        // Admin peut modifier tous sauf superadmin
        if ($userRole === 'admin' && $targetRole !== 'superadmin') {
            return true;
        }

        return false;
    }
}