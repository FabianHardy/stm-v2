<?php
/**
 * Sidebar Admin / Menu de Navigation
 *
 * Navigation principale avec :
 * - Logo
 * - Menu hiérarchique
 * - Sous-menus déroulants
 * - Indicateurs actifs
 * - Responsive (collapse mobile)
 *
 * @package STM
 * @version 2.0
 * @modified 10/11/2025 - Utilisation de $activeCampaignsCount au lieu de hardcodé
 * @modified 25/11/2025 - Ajout section Outils Dev (visible uniquement en mode development)
 */

$currentRoute = $_SERVER["REQUEST_URI"] ?? "";

// Détection de l'environnement
$appEnv = $_ENV["APP_ENV"] ?? getenv("APP_ENV") ?: "production";
$isDev = $appEnv === "development";

/**
 * Vérifie si une route est active
 */
function isActive(string $route, string $currentRoute): bool
{
    return str_starts_with($currentRoute, $route);
}

/**
 * Retourne les classes CSS pour un lien actif/inactif
 */
function getNavLinkClass(string $route, string $currentRoute): string
{
    $baseClass = "flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200";

    if (isActive($route, $currentRoute)) {
        return $baseClass . " bg-primary-50 text-primary-700 border-l-4 border-primary-600";
    }

    return $baseClass . " text-gray-600 hover:bg-gray-50 hover:text-primary-600 border-l-4 border-transparent";
}

// Menu items avec structure hiérarchique
$menuItems = [
    [
        "label" => "Dashboard",
        "icon" => "fa-chart-line",
        "route" => "/stm/admin/dashboard",
        "badge" => null,
    ],
    [
        "label" => "Campagnes",
        "icon" => "fa-bullhorn",
        "route" => "/stm/admin/campaigns",
        "badge" => $activeCampaignsCount ?? 0, // ✅ Utilisation de la variable dynamique
        "badgeColor" => "bg-primary-100 text-primary-700",
        "submenu" => [
            ["label" => "Toutes les campagnes", "route" => "/stm/admin/campaigns"],
            ["label" => "Créer une campagne", "route" => "/stm/admin/campaigns/create"],
            ["label" => "Campagnes actives", "route" => "/stm/admin/campaigns/active"],
            ["label" => "Archives", "route" => "/stm/admin/campaigns/archived"],
        ],
    ],
    [
        "label" => "Promotions",
        "icon" => "fa-box",
        "route" => "/stm/admin/products",
        "badge" => null,
        "submenu" => [
            ["label" => "Toutes les Promotions", "route" => "/stm/admin/products"],
            ["label" => "Ajouter une promotion", "route" => "/stm/admin/products/create"],
            ["label" => "Catégories", "route" => "/stm/admin/products/categories"],
            ["label" => "Stock", "route" => "/stm/admin/products/stock"],
        ],
    ],
    [
        "label" => "Clients",
        "icon" => "fa-users",
        "route" => "/stm/admin/customers",
        "badge" => null,
        "submenu" => [
            ["label" => "Tous les clients", "route" => "/stm/admin/customers"],
            ["label" => "Ajouter un client", "route" => "/stm/admin/customers/create"],
            ["label" => "Importer des clients", "route" => "/stm/admin/customers/import"],
            ["label" => "Segmentation", "route" => "/stm/admin/customers/segments"],
        ],
    ],
    [
        "label" => "Commandes",
        "icon" => "fa-shopping-cart",
        "route" => "/stm/admin/orders",
        "badge" => "8",
        "badgeColor" => "bg-green-100 text-green-700",
        "submenu" => [
            ["label" => "Toutes les commandes", "route" => "/stm/admin/orders"],
            ["label" => "Commandes du jour", "route" => "/stm/admin/orders/today"],
            ["label" => "En attente", "route" => "/stm/admin/orders/pending"],
            ["label" => "Export", "route" => "/stm/admin/orders/export"],
        ],
    ],
    [
        "label" => "Statistiques",
        "icon" => "fa-chart-bar",
        "route" => "/stm/admin/stats",
        "badge" => null,
        "submenu" => [
            ["label" => "Vue globale", "route" => "/stm/admin/stats"],
            ["label" => "Par campagne", "route" => "/stm/admin/stats/campaigns"],
            ["label" => "Par commercial", "route" => "/stm/admin/stats/sales"],
            ["label" => "Rapports", "route" => "/stm/admin/stats/reports"],
        ],
    ],
];

$settingsItems = [
    [
        "label" => "Mon profil",
        "icon" => "fa-user-circle",
        "route" => "/stm/admin/profile",
    ],
    [
        "label" => "Utilisateurs",
        "icon" => "fa-users-cog",
        "route" => "/stm/admin/users",
    ],
    [
        "label" => "Comptes internes",
        "icon" => "fa-user-shield",
        "route" => "/stm/admin/config/internal-customers",
    ],
    [
        "label" => "Configuration",
        "icon" => "fa-cog",
        "route" => "/stm/admin/settings",
    ],
];

// Menu Outils Dev (visible uniquement en mode development)
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

            <?php foreach ($menuItems as $item): ?>

                <?php if (isset($item["submenu"])): ?>
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
                                <?php if ($item["badge"] !== null && $item["badge"] > 0): ?>
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $item["badgeColor"] ?>">
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
                               class="flex items-center px-4 py-2 text-sm text-gray-600 hover:text-primary-600 hover:bg-gray-50 rounded-lg transition-colors <?= isActive(
                                   $subItem["route"],
                                   $currentRoute,
                               )
                                   ? "text-primary-600 bg-gray-50 font-medium"
                                   : "" ?>">
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
                        <?php if ($item["badge"] !== null && $item["badge"] > 0): ?>
                        <span class="ml-auto px-2 py-0.5 text-xs font-semibold rounded-full <?= $item["badgeColor"] ?>">
                            <?= $item["badge"] ?>
                        </span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>

            <?php endforeach; ?>

            <!-- Séparateur -->
            <div class="py-3">
                <div class="border-t border-gray-200"></div>
            </div>

            <!-- Section Paramètres -->
            <div class="space-y-1">
                <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    Paramètres
                </p>

                <?php foreach ($settingsItems as $item): ?>
                <a href="<?= $item["route"] ?>"
                   class="<?= getNavLinkClass($item["route"], $currentRoute) ?>">
                    <i class="fas <?= $item["icon"] ?> w-5 text-center"></i>
                    <span><?= $item["label"] ?></span>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- =============================================
                 SECTION OUTILS DEV (visible uniquement en mode dev)
                 ============================================= -->
            <?php if ($isDev): ?>
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

        <!-- Navigation (même structure que desktop) -->
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">

            <?php foreach ($menuItems as $item): ?>

                <?php if (isset($item["submenu"])): ?>
                    <div x-data="{ open: <?= isActive($item["route"], $currentRoute) ? "true" : "false" ?> }">
                        <button @click="open = !open"
                                class="<?= getNavLinkClass($item["route"], $currentRoute) ?> w-full justify-between">
                            <div class="flex items-center gap-3">
                                <i class="fas <?= $item["icon"] ?> w-5 text-center"></i>
                                <span><?= $item["label"] ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <?php if ($item["badge"] !== null && $item["badge"] > 0): ?>
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $item["badgeColor"] ?>">
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
                               class="flex items-center px-4 py-2 text-sm text-gray-600 hover:text-primary-600 hover:bg-gray-50 rounded-lg transition-colors <?= isActive(
                                   $subItem["route"],
                                   $currentRoute,
                               )
                                   ? "text-primary-600 bg-gray-50 font-medium"
                                   : "" ?>">
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
                        <?php if ($item["badge"] !== null && $item["badge"] > 0): ?>
                        <span class="ml-auto px-2 py-0.5 text-xs font-semibold rounded-full <?= $item["badgeColor"] ?>">
                            <?= $item["badge"] ?>
                        </span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>

            <?php endforeach; ?>

            <div class="py-3">
                <div class="border-t border-gray-200"></div>
            </div>

            <div class="space-y-1">
                <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    Paramètres
                </p>
                <?php foreach ($settingsItems as $item): ?>
                <a href="<?= $item["route"] ?>"
                   class="<?= getNavLinkClass($item["route"], $currentRoute) ?>">
                    <i class="fas <?= $item["icon"] ?> w-5 text-center"></i>
                    <span><?= $item["label"] ?></span>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Section Outils Dev (mobile) -->
            <?php if ($isDev): ?>
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