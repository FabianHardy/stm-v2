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
 */

namespace App\Helpers;

use Core\Database;
use Core\Session;

class PermissionHelper
{
    /**
     * Cache des permissions de l'utilisateur courant
     */
    private static ?array $permissionsCache = null;

    /**
     * Cache des campagnes accessibles
     */
    private static ?array $campaignsCache = null;

    /**
     * Matrice des permissions par rôle
     * true = permission accordée
     */
    private const ROLE_PERMISSIONS = [
        'superadmin' => [
            // Dashboard
            'dashboard.view' => true,
            'dashboard.stats_full' => true,

            // Campagnes
            'campaigns.view' => true,
            'campaigns.view_all' => true,
            'campaigns.create' => true,
            'campaigns.edit' => true,
            'campaigns.edit_all' => true,
            'campaigns.delete' => true,
            'campaigns.assign' => true,

            // Catégories
            'categories.view' => true,
            'categories.create' => true,
            'categories.edit' => true,
            'categories.delete' => true,

            // Produits/Promotions
            'products.view' => true,
            'products.create' => true,
            'products.edit' => true,
            'products.delete' => true,

            // Clients
            'customers.view' => true,
            'customers.view_all' => true,
            'customers.create' => true,
            'customers.edit' => true,
            'customers.delete' => true,
            'customers.import' => true,

            // Commandes
            'orders.view' => true,
            'orders.view_all' => true,
            'orders.export' => true,

            // Statistiques
            'stats.view' => true,
            'stats.view_all' => true,
            'stats.export' => true,

            // Administration
            'users.view' => true,
            'users.manage' => true,
            'settings.view' => true,
            'settings.manage' => true,
            'agent.view' => true,
        ],

        'admin' => [
            // Dashboard
            'dashboard.view' => true,
            'dashboard.stats_full' => true,

            // Campagnes
            'campaigns.view' => true,
            'campaigns.view_all' => true,
            'campaigns.create' => true,
            'campaigns.edit' => true,
            'campaigns.edit_all' => true,
            'campaigns.delete' => true,
            'campaigns.assign' => true,

            // Catégories
            'categories.view' => true,
            'categories.create' => true,
            'categories.edit' => true,
            'categories.delete' => true,

            // Produits/Promotions
            'products.view' => true,
            'products.create' => true,
            'products.edit' => true,
            'products.delete' => true,

            // Clients
            'customers.view' => true,
            'customers.view_all' => true,
            'customers.create' => true,
            'customers.edit' => true,
            'customers.delete' => true,
            'customers.import' => true,

            // Commandes
            'orders.view' => true,
            'orders.view_all' => true,
            'orders.export' => true,

            // Statistiques
            'stats.view' => true,
            'stats.view_all' => true,
            'stats.export' => true,

            // Administration (limité)
            'users.view' => false,
            'users.manage' => false,
            'settings.view' => true,
            'settings.manage' => false,
            'agent.view' => true,
        ],

        'createur' => [
            // Dashboard
            'dashboard.view' => true,
            'dashboard.stats_full' => false,

            // Campagnes (ses assignations uniquement)
            'campaigns.view' => true,
            'campaigns.view_all' => false,
            'campaigns.create' => true,
            'campaigns.edit' => true,
            'campaigns.edit_all' => false,
            'campaigns.delete' => false,
            'campaigns.assign' => true, // Peut ajouter des collaborateurs sur ses campagnes

            // Catégories
            'categories.view' => true,
            'categories.create' => true,
            'categories.edit' => true,
            'categories.delete' => false,

            // Produits/Promotions
            'products.view' => true,
            'products.create' => true,
            'products.edit' => true,
            'products.delete' => false,

            // Clients (lecture seule)
            'customers.view' => true,
            'customers.view_all' => false,
            'customers.create' => false,
            'customers.edit' => false,
            'customers.delete' => false,
            'customers.import' => false,

            // Commandes (lecture seule sur ses campagnes)
            'orders.view' => true,
            'orders.view_all' => false,
            'orders.export' => true,

            // Statistiques (ses campagnes)
            'stats.view' => true,
            'stats.view_all' => false,
            'stats.export' => true,

            // Administration
            'users.view' => false,
            'users.manage' => false,
            'settings.view' => false,
            'settings.manage' => false,
            'agent.view' => false,
        ],

        'manager_reps' => [
            // Dashboard
            'dashboard.view' => true,
            'dashboard.stats_full' => false,

            // Campagnes (lecture seule)
            'campaigns.view' => true,
            'campaigns.view_all' => false,
            'campaigns.create' => false,
            'campaigns.edit' => false,
            'campaigns.edit_all' => false,
            'campaigns.delete' => false,
            'campaigns.assign' => false,

            // Catégories (lecture seule)
            'categories.view' => true,
            'categories.create' => false,
            'categories.edit' => false,
            'categories.delete' => false,

            // Produits (lecture seule)
            'products.view' => true,
            'products.create' => false,
            'products.edit' => false,
            'products.delete' => false,

            // Clients (ses reps uniquement)
            'customers.view' => true,
            'customers.view_all' => false,
            'customers.create' => false,
            'customers.edit' => false,
            'customers.delete' => false,
            'customers.import' => false,

            // Commandes (ses reps)
            'orders.view' => true,
            'orders.view_all' => false,
            'orders.export' => true,

            // Statistiques (ses reps)
            'stats.view' => true,
            'stats.view_all' => false,
            'stats.export' => true,

            // Administration
            'users.view' => false,
            'users.manage' => false,
            'settings.view' => false,
            'settings.manage' => false,
            'agent.view' => false,
        ],

        'rep' => [
            // Dashboard
            'dashboard.view' => true,
            'dashboard.stats_full' => false,

            // Campagnes (lecture seule)
            'campaigns.view' => true,
            'campaigns.view_all' => false,
            'campaigns.create' => false,
            'campaigns.edit' => false,
            'campaigns.edit_all' => false,
            'campaigns.delete' => false,
            'campaigns.assign' => false,

            // Catégories (lecture seule)
            'categories.view' => true,
            'categories.create' => false,
            'categories.edit' => false,
            'categories.delete' => false,

            // Produits (lecture seule)
            'products.view' => true,
            'products.create' => false,
            'products.edit' => false,
            'products.delete' => false,

            // Clients (les siens uniquement)
            'customers.view' => true,
            'customers.view_all' => false,
            'customers.create' => false,
            'customers.edit' => false,
            'customers.delete' => false,
            'customers.import' => false,

            // Commandes (les siennes)
            'orders.view' => true,
            'orders.view_all' => false,
            'orders.export' => false,

            // Statistiques (les siennes)
            'stats.view' => true,
            'stats.view_all' => false,
            'stats.export' => false,

            // Administration
            'users.view' => false,
            'users.manage' => false,
            'settings.view' => false,
            'settings.manage' => false,
            'agent.view' => false,
        ],
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

        if (!$role || !isset(self::ROLE_PERMISSIONS[$role])) {
            return false;
        }

        return self::ROLE_PERMISSIONS[$role][$permission] ?? false;
    }

    /**
     * Vérifie si l'utilisateur ne peut PAS faire une action
     * (inverse de can, pour la lisibilité)
     *
     * @param string $permission
     * @return bool
     */
    public static function cannot(string $permission): bool
    {
        return !self::can($permission);
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

        if (!$role || !isset(self::ROLE_PERMISSIONS[$role])) {
            return [];
        }

        return self::ROLE_PERMISSIONS[$role];
    }

    /**
     * Récupère la matrice complète des permissions (pour la page config)
     *
     * @return array
     */
    public static function getPermissionMatrix(): array
    {
        return self::ROLE_PERMISSIONS;
    }

    /**
     * Récupère les labels des permissions (pour affichage)
     *
     * @return array
     */
    public static function getPermissionLabels(): array
    {
        return [
            // Dashboard
            'dashboard.view' => 'Voir le dashboard',
            'dashboard.stats_full' => 'Statistiques complètes',

            // Campagnes
            'campaigns.view' => 'Voir les campagnes',
            'campaigns.view_all' => 'Voir toutes les campagnes',
            'campaigns.create' => 'Créer des campagnes',
            'campaigns.edit' => 'Modifier ses campagnes',
            'campaigns.edit_all' => 'Modifier toutes les campagnes',
            'campaigns.delete' => 'Supprimer des campagnes',
            'campaigns.assign' => 'Assigner des collaborateurs',

            // Catégories
            'categories.view' => 'Voir les catégories',
            'categories.create' => 'Créer des catégories',
            'categories.edit' => 'Modifier des catégories',
            'categories.delete' => 'Supprimer des catégories',

            // Produits
            'products.view' => 'Voir les promotions',
            'products.create' => 'Créer des promotions',
            'products.edit' => 'Modifier des promotions',
            'products.delete' => 'Supprimer des promotions',

            // Clients
            'customers.view' => 'Voir les clients',
            'customers.view_all' => 'Voir tous les clients',
            'customers.create' => 'Créer des clients',
            'customers.edit' => 'Modifier des clients',
            'customers.delete' => 'Supprimer des clients',
            'customers.import' => 'Importer des clients',

            // Commandes
            'orders.view' => 'Voir les commandes',
            'orders.view_all' => 'Voir toutes les commandes',
            'orders.export' => 'Exporter les commandes',

            // Statistiques
            'stats.view' => 'Voir les statistiques',
            'stats.view_all' => 'Voir toutes les statistiques',
            'stats.export' => 'Exporter les statistiques',

            // Administration
            'users.view' => 'Voir les utilisateurs',
            'users.manage' => 'Gérer les utilisateurs',
            'settings.view' => 'Voir la configuration',
            'settings.manage' => 'Modifier la configuration',
        ];
    }

    /**
     * Récupère les catégories de permissions (pour regroupement)
     *
     * @return array
     */
    public static function getPermissionCategories(): array
    {
        return [
            'dashboard' => [
                'label' => 'Dashboard',
                'icon' => 'fa-chart-line',
                'permissions' => ['dashboard.view', 'dashboard.stats_full']
            ],
            'campaigns' => [
                'label' => 'Campagnes',
                'icon' => 'fa-bullhorn',
                'permissions' => ['campaigns.view', 'campaigns.view_all', 'campaigns.create', 'campaigns.edit', 'campaigns.edit_all', 'campaigns.delete', 'campaigns.assign']
            ],
            'categories' => [
                'label' => 'Catégories',
                'icon' => 'fa-folder',
                'permissions' => ['categories.view', 'categories.create', 'categories.edit', 'categories.delete']
            ],
            'products' => [
                'label' => 'Promotions',
                'icon' => 'fa-box',
                'permissions' => ['products.view', 'products.create', 'products.edit', 'products.delete']
            ],
            'customers' => [
                'label' => 'Clients',
                'icon' => 'fa-users',
                'permissions' => ['customers.view', 'customers.view_all', 'customers.create', 'customers.edit', 'customers.delete', 'customers.import']
            ],
            'orders' => [
                'label' => 'Commandes',
                'icon' => 'fa-shopping-cart',
                'permissions' => ['orders.view', 'orders.view_all', 'orders.export']
            ],
            'stats' => [
                'label' => 'Statistiques',
                'icon' => 'fa-chart-bar',
                'permissions' => ['stats.view', 'stats.view_all', 'stats.export']
            ],
            'admin' => [
                'label' => 'Administration',
                'icon' => 'fa-cog',
                'permissions' => ['users.view', 'users.manage', 'settings.view', 'settings.manage']
            ],
        ];
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
     * Réinitialise les caches (utile après changement de session)
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