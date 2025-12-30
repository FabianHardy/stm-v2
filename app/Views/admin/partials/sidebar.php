<?php
/**
 * Sidebar Admin / Menu de Navigation
 *
 * Navigation principale avec :
 * - Logo
 * - Menu hiérarchique filtré par permissions
 * - Sous-menus déroulants
 * - Indicateurs actifs
 * - Responsive (collapse mobile)
 *
 * @package STM
 * @version 2.0
 * @modified 10/11/2025 - Utilisation de $activeCampaignsCount au lieu de hardcodé
 * @modified 25/11/2025 - Ajout section Outils Dev (visible uniquement en mode development)
 * @modified 10/12/2025 - Protection function_exists pour éviter redéclaration
 * @modified 12/12/2025 - Intégration système permissions (masquage menus selon rôle)
 * @modified 15/12/2025 - Ajout permission agent.view pour Agent STM
 * @modified 15/12/2025 - Correction logique filtrage : menu parent affiché si sous-menu accessible
 * @modified 29/12/2025 - Menu Clients simplifié (consultation uniquement, suppression création/import)
 * @modified 30/12/2025 - Ajout menu Templates emails (Sprint 8)
 */

use App\Helpers\PermissionHelper;

$currentRoute = $_SERVER["REQUEST_URI"] ?? "";

// Détection de l'environnement
$appEnv = $_ENV["APP_ENV"] ?? $_SERVER["APP_ENV"] ?? getenv("APP_ENV") ?: "production";
$isDev = $appEnv === "development";

/**
 * Vérifie si une route est active
 */
if (!function_exists('isActive')) {
    function isActive(string $route, string $currentRoute): bool
    {
        // Cas spécial : /settings ne doit pas matcher /settings/agent
        if ($route === '/stm/admin/settings' && str_starts_with($currentRoute, '/stm/admin/settings/')) {
            return false;
        }
        return str_starts_with($currentRoute, $route);
    }
}

/**
 * Retourne les classes CSS pour un lien actif/inactif
 */
if (!function_exists('getNavLinkClass')) {
    function getNavLinkClass(string $route, string $currentRoute): string
    {
        $baseClass = "flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200";

        if (isActive($route, $currentRoute)) {
            return $baseClass . " bg-primary-50 text-primary-700 border-l-4 border-primary-600";
        }

        return $baseClass . " text-gray-600 hover:bg-gray-50 hover:text-primary-600 border-l-4 border-transparent";
    }
}

/**
 * Vérifie si un item de menu est accessible selon la permission
 * @param array $item
 * @return bool
 */
if (!function_exists('canAccessMenuItem')) {
    function canAccessMenuItem(array $item): bool
    {
        // Si pas de permission définie, accessible à tous
        if (!isset($item['permission'])) {
            return true;
        }

        return PermissionHelper::can($item['permission']);
    }
}

/**
 * Vérifie si un menu parent avec sous-menus doit être affiché
 * Le menu parent s'affiche si :
 * - L'utilisateur a la permission du parent OU
 * - L'utilisateur a la permission d'au moins un sous-menu
 *
 * @param array $item Menu parent avec potentiellement des sous-menus
 * @return bool
 */
if (!function_exists('canAccessMenuWithSubmenu')) {
    function canAccessMenuWithSubmenu(array $item): bool
    {
        // Si l'utilisateur a la permission du parent, c'est OK
        if (canAccessMenuItem($item)) {
            return true;
        }

        // Sinon, vérifier si au moins un sous-menu est accessible
        if (isset($item['submenu']) && is_array($item['submenu'])) {
            foreach ($item['submenu'] as $subItem) {
                if (canAccessMenuItem($subItem)) {
                    return true;
                }
            }
        }

        return false;
    }
}

// ============================================================
// DÉFINITION DES MENUS AVEC PERMISSIONS
// ============================================================

// Menu items avec structure hiérarchique + permissions
$menuItems = [
    [
        "label" => "Dashboard",
        "icon" => "fa-chart-line",
        "route" => "/stm/admin/dashboard",
        "badge" => null,
        "permission" => "dashboard.view",
    ],
    [
        "label" => "Campagnes",
        "icon" => "fa-bullhorn",
        "route" => "/stm/admin/campaigns",
        "badge" => $activeCampaignsCount ?? 0,
        "badgeColor" => "bg-primary-100 text-primary-700",
        "permission" => "campaigns.view",
        "submenu" => [
            ["label" => "Toutes les campagnes", "route" => "/stm/admin/campaigns", "permission" => "campaigns.view"],
            ["label" => "Créer une campagne", "route" => "/stm/admin/campaigns/create", "permission" => "campaigns.create"],
            ["label" => "Campagnes actives", "route" => "/stm/admin/campaigns/active", "permission" => "campaigns.view"],
            ["label" => "Terminées", "route" => "/stm/admin/campaigns?status=ended", "permission" => "campaigns.view"],
        ],
    ],
    [
        "label" => "Promotions",
        "icon" => "fa-box",
        "route" => "/stm/admin/products",
        "badge" => null,
        "permission" => "products.view", // Permission du parent
        "submenu" => [
            ["label" => "Toutes les Promotions", "route" => "/stm/admin/products", "permission" => "products.view"],
            ["label" => "Ajouter une promotion", "route" => "/stm/admin/products/create", "permission" => "products.create"],
            ["label" => "Catégories", "route" => "/stm/admin/products/categories", "permission" => "categories.view"],
        ],
    ],
    [
        "label" => "Clients",
        "icon" => "fa-users",
        "route" => "/stm/admin/customers",
        "badge" => null,
        "permission" => "customers.view",
        // Pas de sous-menu : consultation uniquement
    ],
    [
        "label" => "Commandes",
        "icon" => "fa-shopping-cart",
        "route" => "/stm/admin/orders",
        "badge" => null,
        "badgeColor" => "bg-green-100 text-green-700",
        "permission" => "orders.view",
        "submenu" => [
            ["label" => "Toutes les commandes", "route" => "/stm/admin/orders", "permission" => "orders.view"],
            ["label" => "Commandes du jour", "route" => "/stm/admin/orders/today", "permission" => "orders.view"],
            ["label" => "En attente", "route" => "/stm/admin/orders/pending", "permission" => "orders.view"],
            ["label" => "Export", "route" => "/stm/admin/orders/export", "permission" => "orders.export"],
        ],
    ],
    [
        "label" => "Statistiques",
        "icon" => "fa-chart-bar",
        "route" => "/stm/admin/stats",
        "badge" => null,
        "permission" => "stats.view",
        "submenu" => [
            ["label" => "Vue globale", "route" => "/stm/admin/stats", "permission" => "stats.view"],
            ["label" => "Par campagne", "route" => "/stm/admin/stats/campaigns", "permission" => "stats.view"],
            ["label" => "Par commercial", "route" => "/stm/admin/stats/sales", "permission" => "stats.view"],
            ["label" => "Rapports", "route" => "/stm/admin/stats/reports", "permission" => "stats.export"],
        ],
    ],
];

// Section Paramètres avec permissions
$settingsItems = [
    [
        "label" => "Mon profil",
        "icon" => "fa-user-circle",
        "route" => "/stm/admin/profile",
        // Pas de permission = accessible à tous
    ],
    [
        "label" => "Utilisateurs",
        "icon" => "fa-users-cog",
        "route" => "/stm/admin/users",
        "permission" => "users.view",
    ],
    [
        "label" => "Comptes internes",
        "icon" => "fa-user-shield",
        "route" => "/stm/admin/config/internal-customers",
        "permission" => "settings.view",
    ],
    [
        "label" => "Agent STM",
        "icon" => "fa-robot",
        "route" => "/stm/admin/settings/agent",
        "permission" => "agent.view", // Permission ajoutée
    ],
    [
        "label" => "Agent STM",
        "icon" => "fa-robot",
        "route" => "/stm/admin/settings/agent",
        "permission" => "agent.view",
    ],
    [
        "label" => "Templates emails",
        "icon" => "fa-envelope",
        "route" => "/stm/admin/email-templates",
        "permission" => "settings.view",
    ],
    [
        "label" => "Configuration",
        "icon" => "fa-cog",
        "route" => "/stm/admin/settings",
        "permission" => "settings.view",
    ],
];

// Menu Outils Dev (visible uniquement en mode development + superadmin)
$devToolsItems = [
    [
        "label" => "Sync Base de données",
        "icon" => "fa-database",
        "route" => "/stm/admin/dev-tools/sync-db",
        "iconColor" => "text-orange-500",
    ],
    [
        "label" => "Sync Fichiers",
        "icon" => "fa-folder-open",
        "route" => "/stm/admin/dev-tools/sync-files",
        "iconColor" => "text-purple-500",
    ],
];

// ============================================================
// FILTRAGE DES MENUS (LOGIQUE AMÉLIORÉE)
// ============================================================

// Filtrer les menus avec logique pour sous-menus
// Un menu parent s'affiche si :
// - L'utilisateur a la permission du parent OU
// - L'utilisateur a la permission d'au moins un sous-menu
$filteredMenuItems = array_filter($menuItems, 'canAccessMenuWithSubmenu');

// Filtrer les sous-menus
foreach ($filteredMenuItems as &$item) {
    if (isset($item['submenu'])) {
        $item['submenu'] = array_filter($item['submenu'], 'canAccessMenuItem');
    }
}
unset($item);

// Filtrer les paramètres (pas de sous-menus, logique simple)
$filteredSettingsItems = array_filter($settingsItems, 'canAccessMenuItem');
?>

<!-- Sidebar Desktop -->
<aside class="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-64 lg:flex-col bg-white border-r border-gray-200">
    <div class="flex flex-col flex-1 min-h-0">

        <!-- Logo / Header -->
        <div class="flex items-center justify-center h-16 px-4 border-b border-gray-200">
            <a href="/stm/admin/dashboard" class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-gradient-to-br from-primary-600 to-primary-700 flex items-center justify-center text-white font-bold text-xl shadow-lg">
                    S
                </div>
                <div>
                    <p class="text-lg font-bold text-gray-900">STM Admin</p>
                    <p class="text-xs text-gray-500">v2.0</p>
                </div>
            </a>
        </div>

        <!-- Navigation principale -->
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">

            <?php foreach ($filteredMenuItems as $item): ?>

                <?php if (isset($item["submenu"]) && !empty($item["submenu"])): ?>
                    <!-- Menu avec sous-menu -->
                    <div x-data="{ open: <?= isActive($item["route"], $currentRoute) ? "true" : "false" ?> }">

                        <!-- Menu parent -->
                        <button @click="open = !open"
                                class="<?= getNavLinkClass($item["route"], $currentRoute) ?> w-full justify-between">
                            <div class="flex items-center gap-3">
                                <i class="fas <?= $item["icon"] ?> w-5 text-center"></i>
                                <span><?= $item["label"] ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <?php if (isset($item["badge"]) && $item["badge"] !== null && $item["badge"] > 0): ?>
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $item["badgeColor"] ?? 'bg-gray-100 text-gray-700' ?>">
                                    <?= $item["badge"] ?>
                                </span>
                                <?php endif; ?>
                                <i class="fas fa-chevron-down text-xs transition-transform duration-200"
                                   :class="open ? 'rotate-180' : ''"></i>
                            </div>
                        </button>

                        <!-- Sous-menu -->
                        <div x-show="open"
                             x-transition
                             class="ml-8 mt-1 space-y-1"
                             style="display: none;">
                            <?php foreach ($item["submenu"] as $subItem): ?>
                            <a href="<?= $subItem["route"] ?>"
                               class="flex items-center px-4 py-2 text-sm text-gray-600 hover:text-primary-600 hover:bg-gray-50 rounded-lg transition-colors <?= isActive($subItem["route"], $currentRoute) ? "text-primary-600 bg-gray-50 font-medium" : "" ?>">
                                <?= $subItem["label"] ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Menu simple -->
                    <a href="<?= $item["route"] ?>"
                       class="<?= getNavLinkClass($item["route"], $currentRoute) ?>">
                        <i class="fas <?= $item["icon"] ?> w-5 text-center"></i>
                        <span><?= $item["label"] ?></span>
                        <?php if (isset($item["badge"]) && $item["badge"] !== null && $item["badge"] > 0): ?>
                        <span class="ml-auto px-2 py-0.5 text-xs font-semibold rounded-full <?= $item["badgeColor"] ?? 'bg-gray-100 text-gray-700' ?>">
                            <?= $item["badge"] ?>
                        </span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>

            <?php endforeach; ?>

            <!-- Séparateur (affiché seulement si des items paramètres sont visibles) -->
            <?php if (!empty($filteredSettingsItems)): ?>
            <div class="py-3">
                <div class="border-t border-gray-200"></div>
            </div>

            <!-- Section Paramètres -->
            <div class="space-y-1">
                <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    Paramètres
                </p>

                <?php foreach ($filteredSettingsItems as $item): ?>
                <a href="<?= $item["route"] ?>"
                   class="<?= getNavLinkClass($item["route"], $currentRoute) ?>">
                    <i class="fas <?= $item["icon"] ?> w-5 text-center"></i>
                    <span><?= $item["label"] ?></span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- =============================================
                 SECTION OUTILS DEV (visible uniquement en mode dev + superadmin)
                 ============================================= -->
            <?php if ($isDev && PermissionHelper::hasRole(['superadmin'])): ?>
            <div class="py-3">
                <div class="border-t border-orange-200"></div>
            </div>

            <div class="space-y-1">
                <p class="px-4 text-xs font-semibold text-orange-500 uppercase tracking-wider">
                    🔧 Outils Dev
                </p>

                <?php foreach ($devToolsItems as $item): ?>
                <a href="<?= $item["route"] ?>"
                   class="<?= getNavLinkClass($item["route"], $currentRoute) ?>">
                    <i class="fas <?= $item["icon"] ?> w-5 text-center <?= $item["iconColor"] ?? "" ?>"></i>
                    <span><?= $item["label"] ?></span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </nav>

        <!-- Footer sidebar -->
        <div class="flex-shrink-0 border-t border-gray-200 p-4">
            <div class="flex items-center gap-3 px-4 py-3 bg-primary-50 rounded-lg">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-primary-600 text-xl"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-primary-900">Besoin d'aide ?</p>
                    <a href="#" class="text-xs text-primary-600 hover:text-primary-700 font-medium">
                        Consulter le guide →
                    </a>
                </div>
            </div>
        </div>

    </div>
</aside>

<!-- Sidebar Mobile (slide-over) -->
<div x-show="sidebarOpen"
     x-transition:enter="transition ease-in-out duration-300 transform"
     x-transition:enter-start="-translate-x-full"
     x-transition:enter-end="translate-x-0"
     x-transition:leave="transition ease-in-out duration-300 transform"
     x-transition:leave-start="translate-x-0"
     x-transition:leave-end="-translate-x-full"
     class="lg:hidden fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 animate-slide-in"
     style="display: none;">

    <div class="flex flex-col h-full">

        <!-- Logo / Header -->
        <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200">
            <a href="/stm/admin/dashboard" class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-gradient-to-br from-primary-600 to-primary-700 flex items-center justify-center text-white font-bold text-xl shadow-lg">
                    S
                </div>
                <div>
                    <p class="text-lg font-bold text-gray-900">STM Admin</p>
                    <p class="text-xs text-gray-500">v2.0</p>
                </div>
            </a>

            <!-- Bouton fermeture -->
            <button @click="sidebarOpen = false"
                    class="p-2 text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <!-- Navigation (même structure que desktop avec filtrage) -->
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">

            <?php foreach ($filteredMenuItems as $item): ?>

                <?php if (isset($item["submenu"]) && !empty($item["submenu"])): ?>
                    <div x-data="{ open: <?= isActive($item["route"], $currentRoute) ? "true" : "false" ?> }">
                        <button @click="open = !open"
                                class="<?= getNavLinkClass($item["route"], $currentRoute) ?> w-full justify-between">
                            <div class="flex items-center gap-3">
                                <i class="fas <?= $item["icon"] ?> w-5 text-center"></i>
                                <span><?= $item["label"] ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <?php if (isset($item["badge"]) && $item["badge"] !== null && $item["badge"] > 0): ?>
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $item["badgeColor"] ?? 'bg-gray-100 text-gray-700' ?>">
                                    <?= $item["badge"] ?>
                                </span>
                                <?php endif; ?>
                                <i class="fas fa-chevron-down text-xs transition-transform duration-200"
                                   :class="open ? 'rotate-180' : ''"></i>
                            </div>
                        </button>
                        <div x-show="open" x-transition class="ml-8 mt-1 space-y-1" style="display: none;">
                            <?php foreach ($item["submenu"] as $subItem): ?>
                            <a href="<?= $subItem["route"] ?>"
                               class="flex items-center px-4 py-2 text-sm text-gray-600 hover:text-primary-600 hover:bg-gray-50 rounded-lg transition-colors <?= isActive($subItem["route"], $currentRoute) ? "text-primary-600 bg-gray-50 font-medium" : "" ?>">
                                <?= $subItem["label"] ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?= $item["route"] ?>"
                       class="<?= getNavLinkClass($item["route"], $currentRoute) ?>">
                        <i class="fas <?= $item["icon"] ?> w-5 text-center"></i>
                        <span><?= $item["label"] ?></span>
                        <?php if (isset($item["badge"]) && $item["badge"] !== null && $item["badge"] > 0): ?>
                        <span class="ml-auto px-2 py-0.5 text-xs font-semibold rounded-full <?= $item["badgeColor"] ?? 'bg-gray-100 text-gray-700' ?>">
                            <?= $item["badge"] ?>
                        </span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>

            <?php endforeach; ?>

            <?php if (!empty($filteredSettingsItems)): ?>
            <div class="py-3">
                <div class="border-t border-gray-200"></div>
            </div>

            <div class="space-y-1">
                <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    Paramètres
                </p>
                <?php foreach ($filteredSettingsItems as $item): ?>
                <a href="<?= $item["route"] ?>"
                   class="<?= getNavLinkClass($item["route"], $currentRoute) ?>">
                    <i class="fas <?= $item["icon"] ?> w-5 text-center"></i>
                    <span><?= $item["label"] ?></span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Section Outils Dev (mobile) -->
            <?php if ($isDev && PermissionHelper::hasRole(['superadmin'])): ?>
            <div class="py-3">
                <div class="border-t border-orange-200"></div>
            </div>

            <div class="space-y-1">
                <p class="px-4 text-xs font-semibold text-orange-500 uppercase tracking-wider">
                    🔧 Outils Dev
                </p>

                <?php foreach ($devToolsItems as $item): ?>
                <a href="<?= $item["route"] ?>"
                   class="<?= getNavLinkClass($item["route"], $currentRoute) ?>">
                    <i class="fas <?= $item["icon"] ?> w-5 text-center <?= $item["iconColor"] ?? "" ?>"></i>
                    <span><?= $item["label"] ?></span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </nav>

    </div>
</div>