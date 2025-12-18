<?php
/**
 * Vue : Dashboard Admin
 * Description : Page principale du dashboard avec KPI et statistiques
 * Layout : layouts/admin.php
 *
 * @modified 2025/11/27 - Fix htmlspecialchars null + Graphique 7 jours + Lien détail commande
 * @modified 2025/12/15 - Intégration permissions : masquage éléments selon droits (structure originale conservée)
 * @modified 2025/12/16 - Ajout filtrage hiérarchique stats selon rôle (createur, manager_reps)
 */

use Core\Database;
use App\Helpers\PermissionHelper;
use App\Helpers\StatsAccessHelper;

// Démarrer le buffering de sortie pour capturer le contenu
ob_start();

// Récupération de l'instance Database
$db = Database::getInstance();

// ============================================================
// VÉRIFICATION DES PERMISSIONS POUR AFFICHAGE CONDITIONNEL
// ============================================================
$canViewCampaigns = PermissionHelper::can('campaigns.view');
$canCreateCampaigns = PermissionHelper::can('campaigns.create');
$canViewProducts = PermissionHelper::can('products.view');
$canViewCustomers = PermissionHelper::can('customers.view');
$canViewOrders = PermissionHelper::can('orders.view');
$canViewStats = PermissionHelper::can('stats.view');

// ============================================================
// FILTRAGE HIÉRARCHIQUE SELON LE RÔLE
// ============================================================
$accessibleCampaignIds = StatsAccessHelper::getAccessibleCampaignIds();
$accessibleCustomerNumbers = StatsAccessHelper::getAccessibleCustomerNumbersOnly();
$hasFullAccess = StatsAccessHelper::hasFullAccess();

// ============================================================
// PÉRIODE DE RÉFÉRENCE : 14 DERNIERS JOURS
// ============================================================
$periodDays = 14;
$dateFrom = date("Y-m-d", strtotime("-{$periodDays} days"));
$dateTo = date("Y-m-d");
$periodLabel = "14 derniers jours";

// Initialisation des variables par défaut
$stats = [
    "total_campaigns" => 0,
    "active_campaigns" => 0,
    "total_customers" => 0,
    "total_orders" => 0,
    "total_promos" => 0,
    "total_quantity" => 0,
];

$recent_orders = [];
$campaign_stats = [];
$product_categories = [];
$daily_orders = [];

// KPI 1: Campagnes totales et actives (seulement si permission)
if ($canViewCampaigns) {
    try {
        // Filtrer selon les campagnes accessibles
        if ($accessibleCampaignIds === null) {
            // Accès complet
            $results = $db->query("
                SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN CURDATE() BETWEEN start_date AND end_date THEN 1 ELSE 0 END) as active
                FROM campaigns
            ");
        } elseif (!empty($accessibleCampaignIds)) {
            // Filtrer par IDs accessibles
            $placeholders = implode(",", array_fill(0, count($accessibleCampaignIds), "?"));
            $results = $db->query("
                SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN CURDATE() BETWEEN start_date AND end_date THEN 1 ELSE 0 END) as active
                FROM campaigns
                WHERE id IN ({$placeholders})
            ", $accessibleCampaignIds);
        } else {
            $results = [["total" => 0, "active" => 0]];
        }

        if (!empty($results)) {
            $stats["total_campaigns"] = (int) ($results[0]["total"] ?? 0);
            $stats["active_campaigns"] = (int) ($results[0]["active"] ?? 0);
        }
    } catch (\PDOException $e) {
        error_log("Erreur récupération stats campagnes: " . $e->getMessage());
    }
}

// KPI 2: Clients ayant commandé (14 derniers jours)
if ($canViewCustomers) {
    try {
        // Construire le filtre clients
        $clientParams = [$dateFrom, $dateTo];
        $customerFilter = "";
        if ($accessibleCustomerNumbers !== null) {
            if (!empty($accessibleCustomerNumbers)) {
                $placeholders = implode(",", array_fill(0, count($accessibleCustomerNumbers), "?"));
                $customerFilter = " AND cu.customer_number IN ({$placeholders})";
                $clientParams = array_merge($clientParams, $accessibleCustomerNumbers);
            } else {
                $stats["total_customers"] = 0;
                goto skip_customers_kpi;
            }
        }

        // Construire le filtre campagnes
        $campaignFilterClients = "";
        if ($accessibleCampaignIds !== null && !empty($accessibleCampaignIds)) {
            $placeholders2 = implode(",", array_fill(0, count($accessibleCampaignIds), "?"));
            $campaignFilterClients = " AND o.campaign_id IN ({$placeholders2})";
            $clientParams = array_merge($clientParams, $accessibleCampaignIds);
        }

        $results = $db->query("
            SELECT COUNT(DISTINCT o.customer_id) as total
            FROM orders o
            INNER JOIN customers cu ON o.customer_id = cu.id
            WHERE o.status = 'validated'
            AND DATE(o.created_at) BETWEEN ? AND ?
            {$customerFilter}
            {$campaignFilterClients}
        ", $clientParams);
        if (!empty($results)) {
            $stats["total_customers"] = (int) ($results[0]["total"] ?? 0);
        }
    } catch (\PDOException $e) {
        error_log("Erreur récupération stats clients: " . $e->getMessage());
    }
    skip_customers_kpi:
}

// KPI 3: Commandes validées et quantités totales (14 derniers jours)
if ($canViewOrders) {
    try {
        // Construire les filtres
        $orderParams = [$dateFrom, $dateTo];
        $campaignFilter = "";
        $customerFilterOrders = "";

        // Filtre par campagnes accessibles
        if ($accessibleCampaignIds !== null) {
            if (!empty($accessibleCampaignIds)) {
                $placeholders = implode(",", array_fill(0, count($accessibleCampaignIds), "?"));
                $campaignFilter = " AND o.campaign_id IN ({$placeholders})";
                $orderParams = array_merge($orderParams, $accessibleCampaignIds);
            } else {
                // Aucun accès aux campagnes
                $stats["total_orders"] = 0;
                $stats["total_quantity"] = 0;
                goto skip_orders_kpi;
            }
        }

        // Filtre par clients accessibles
        if ($accessibleCustomerNumbers !== null) {
            if (!empty($accessibleCustomerNumbers)) {
                $placeholders2 = implode(",", array_fill(0, count($accessibleCustomerNumbers), "?"));
                $customerFilterOrders = " AND cu.customer_number IN ({$placeholders2})";
                $orderParams = array_merge($orderParams, $accessibleCustomerNumbers);
            } else {
                // Aucun client accessible
                $stats["total_orders"] = 0;
                $stats["total_quantity"] = 0;
                goto skip_orders_kpi;
            }
        }

        $results = $db->query("
            SELECT
                COUNT(DISTINCT o.id) as total_orders,
                COALESCE(SUM(ol.quantity), 0) as total_quantity
            FROM orders o
            INNER JOIN customers cu ON o.customer_id = cu.id
            LEFT JOIN order_lines ol ON o.id = ol.order_id
            WHERE o.status = 'validated'
            AND DATE(o.created_at) BETWEEN ? AND ?
            {$campaignFilter}
            {$customerFilterOrders}
        ", $orderParams);
        if (!empty($results)) {
            $stats["total_orders"] = (int) ($results[0]["total_orders"] ?? 0);
            $stats["total_quantity"] = (int) ($results[0]["total_quantity"] ?? 0);
        }
    } catch (\PDOException $e) {
        error_log("Erreur récupération stats commandes: " . $e->getMessage());
    }
    skip_orders_kpi:
}

// KPI 4: Promotions des campagnes actives uniquement
if ($canViewProducts) {
    try {
        $promoParams = [];
        $promoCampaignFilter = "";

        if ($accessibleCampaignIds !== null) {
            if (!empty($accessibleCampaignIds)) {
                $placeholders = implode(",", array_fill(0, count($accessibleCampaignIds), "?"));
                $promoCampaignFilter = " AND p.campaign_id IN ({$placeholders})";
                $promoParams = $accessibleCampaignIds;
            } else {
                $stats["total_promos"] = 0;
                goto skip_promos_kpi;
            }
        }

        // Compter uniquement les promos des campagnes actives (entre start_date et end_date)
        $results = $db->query("
            SELECT COUNT(*) as total
            FROM products p
            INNER JOIN campaigns c ON p.campaign_id = c.id
            WHERE p.is_active = 1
            AND CURDATE() BETWEEN c.start_date AND c.end_date
            {$promoCampaignFilter}
        ", $promoParams);
        if (!empty($results)) {
            $stats["total_promos"] = (int) ($results[0]["total"] ?? 0);
        }
    } catch (\PDOException $e) {
        error_log("Erreur récupération stats Promotions: " . $e->getMessage());
    }
    skip_promos_kpi:
}

// Dernières commandes (14 derniers jours)
if ($canViewOrders) {
    try {
        // Construire les filtres - commencer avec les dates
        $recentOrderParams = [$dateFrom, $dateTo];
        $campaignFilterRecent = "";
        $customerFilterRecent = "";

        // Filtre par campagnes accessibles
        if ($accessibleCampaignIds !== null) {
            if (!empty($accessibleCampaignIds)) {
                $placeholders = implode(",", array_fill(0, count($accessibleCampaignIds), "?"));
                $campaignFilterRecent = " AND o.campaign_id IN ({$placeholders})";
                $recentOrderParams = array_merge($recentOrderParams, $accessibleCampaignIds);
            } else {
                $recent_orders = [];
                goto skip_recent_orders;
            }
        }

        // Filtre par clients accessibles
        if ($accessibleCustomerNumbers !== null) {
            if (!empty($accessibleCustomerNumbers)) {
                $placeholders2 = implode(",", array_fill(0, count($accessibleCustomerNumbers), "?"));
                $customerFilterRecent = " AND cu.customer_number IN ({$placeholders2})";
                $recentOrderParams = array_merge($recentOrderParams, $accessibleCustomerNumbers);
            } else {
                $recent_orders = [];
                goto skip_recent_orders;
            }
        }

        $recent_orders = $db->query("
            SELECT
                o.id,
                o.order_number,
                c.name as campaign_name,
                cu.company_name,
                cu.country,
                o.status,
                o.created_at,
                COALESCE(SUM(ol.quantity), 0) as items_count
            FROM orders o
            LEFT JOIN campaigns c ON o.campaign_id = c.id
            INNER JOIN customers cu ON o.customer_id = cu.id
            LEFT JOIN order_lines ol ON o.id = ol.order_id
            WHERE DATE(o.created_at) BETWEEN ? AND ?
            {$campaignFilterRecent}
            {$customerFilterRecent}
            GROUP BY o.id, o.order_number, c.name, cu.company_name, cu.country, o.status, o.created_at
            ORDER BY o.created_at DESC
            LIMIT 10
        ", $recentOrderParams);
    } catch (\PDOException $e) {
        error_log("Erreur récupération dernières commandes: " . $e->getMessage());
        $recent_orders = [];
    }
    skip_recent_orders:
}

// Stats par campagne active (14 derniers jours)
if ($canViewStats && $canViewCampaigns) {
    try {
        // Construire les filtres - commencer avec les dates
        $campaignStatsParams = [$dateFrom, $dateTo];
        $campaignStatsFilter = "";
        $customerStatsFilter = "";

        // Filtre par campagnes accessibles
        if ($accessibleCampaignIds !== null) {
            if (!empty($accessibleCampaignIds)) {
                $placeholders = implode(",", array_fill(0, count($accessibleCampaignIds), "?"));
                $campaignStatsFilter = " AND c.id IN ({$placeholders})";
                $campaignStatsParams = array_merge($campaignStatsParams, $accessibleCampaignIds);
            } else {
                $campaign_stats = [];
                goto skip_campaign_stats;
            }
        }

        // Filtre par clients accessibles
        if ($accessibleCustomerNumbers !== null) {
            if (!empty($accessibleCustomerNumbers)) {
                $placeholders2 = implode(",", array_fill(0, count($accessibleCustomerNumbers), "?"));
                $customerStatsFilter = " AND cu.customer_number IN ({$placeholders2})";
                $campaignStatsParams = array_merge($campaignStatsParams, $accessibleCustomerNumbers);
            } else {
                $campaign_stats = [];
                goto skip_campaign_stats;
            }
        }

        $campaign_stats = $db->query("
            SELECT
                c.name as campaign_name,
                c.country,
                COUNT(DISTINCT o.id) as orders_count,
                COALESCE(SUM(ol.quantity), 0) as quantity_count
            FROM campaigns c
            LEFT JOIN orders o ON c.id = o.campaign_id AND o.status = 'validated' AND DATE(o.created_at) BETWEEN ? AND ?
            LEFT JOIN customers cu ON o.customer_id = cu.id
            LEFT JOIN order_lines ol ON o.id = ol.order_id
            WHERE CURDATE() BETWEEN c.start_date AND c.end_date
            {$campaignStatsFilter}
            {$customerStatsFilter}
            GROUP BY c.id, c.name, c.country
            ORDER BY quantity_count DESC
            LIMIT 5
        ", $campaignStatsParams);
    } catch (\PDOException $e) {
        error_log("Erreur récupération stats campagnes: " . $e->getMessage());
        $campaign_stats = [];
    }
    skip_campaign_stats:
}

// Répartition par catégorie (14 derniers jours)
if ($canViewStats && $canViewProducts) {
    try {
        // Construire les filtres - commencer avec les dates
        $catParams = [$dateFrom, $dateTo];
        $catCampaignFilter = "";
        $catCustomerFilter = "";

        // Filtre par campagnes accessibles
        if ($accessibleCampaignIds !== null) {
            if (!empty($accessibleCampaignIds)) {
                $placeholders = implode(",", array_fill(0, count($accessibleCampaignIds), "?"));
                $catCampaignFilter = " AND p.campaign_id IN ({$placeholders})";
                $catParams = array_merge($catParams, $accessibleCampaignIds);
            } else {
                $product_categories = [];
                goto skip_categories;
            }
        }

        // Filtre par clients accessibles
        if ($accessibleCustomerNumbers !== null) {
            if (!empty($accessibleCustomerNumbers)) {
                $placeholders2 = implode(",", array_fill(0, count($accessibleCustomerNumbers), "?"));
                $catCustomerFilter = " AND cu.customer_number IN ({$placeholders2})";
                $catParams = array_merge($catParams, $accessibleCustomerNumbers);
            } else {
                $product_categories = [];
                goto skip_categories;
            }
        }

        $product_categories = $db->query("
            SELECT
                cat.name_fr as category_name,
                cat.color,
                COUNT(DISTINCT p.id) as products_count,
                COALESCE(SUM(ol.quantity), 0) as quantity_sold
            FROM categories cat
            LEFT JOIN products p ON cat.id = p.category_id AND p.is_active = 1
            LEFT JOIN order_lines ol ON p.id = ol.product_id
            LEFT JOIN orders o ON ol.order_id = o.id AND o.status = 'validated' AND DATE(o.created_at) BETWEEN ? AND ?
            LEFT JOIN customers cu ON o.customer_id = cu.id
            WHERE 1=1
            {$catCampaignFilter}
            {$catCustomerFilter}
            GROUP BY cat.id, cat.name_fr, cat.color
            HAVING quantity_sold > 0
            ORDER BY quantity_sold DESC
        ", $catParams);
    } catch (\PDOException $e) {
        error_log("Erreur récupération catégories: " . $e->getMessage());
        $product_categories = [];
    }
    skip_categories:
}

// Évolution des ventes (14 derniers jours)
if ($canViewStats && $canViewOrders) {
    try {
        // Construire les filtres - commencer avec les dates
        $dailyParams = [$dateFrom, $dateTo];
        $dailyCampaignFilter = "";
        $dailyCustomerFilter = "";

        // Filtre par campagnes accessibles
        if ($accessibleCampaignIds !== null) {
            if (!empty($accessibleCampaignIds)) {
                $placeholders = implode(",", array_fill(0, count($accessibleCampaignIds), "?"));
                $dailyCampaignFilter = " AND o.campaign_id IN ({$placeholders})";
                $dailyParams = array_merge($dailyParams, $accessibleCampaignIds);
            } else {
                $daily_orders = [];
                goto skip_daily_orders;
            }
        }

        // Filtre par clients accessibles
        if ($accessibleCustomerNumbers !== null) {
            if (!empty($accessibleCustomerNumbers)) {
                $placeholders2 = implode(",", array_fill(0, count($accessibleCustomerNumbers), "?"));
                $dailyCustomerFilter = " AND cu.customer_number IN ({$placeholders2})";
                $dailyParams = array_merge($dailyParams, $accessibleCustomerNumbers);
            } else {
                $daily_orders = [];
                goto skip_daily_orders;
            }
        }

        $daily_orders = $db->query("
            SELECT
                DATE(o.created_at) as day,
                DATE_FORMAT(o.created_at, '%a %d/%m') as day_label,
                COUNT(DISTINCT o.id) as orders_count,
                COALESCE(SUM(ol.quantity), 0) as quantity_count
            FROM orders o
            INNER JOIN customers cu ON o.customer_id = cu.id
            LEFT JOIN order_lines ol ON o.id = ol.order_id
            WHERE DATE(o.created_at) BETWEEN ? AND ?
            AND o.status = 'validated'
            {$dailyCampaignFilter}
            {$dailyCustomerFilter}
            GROUP BY DATE(o.created_at), DATE_FORMAT(o.created_at, '%a %d/%m')
            ORDER BY day ASC
        ", $dailyParams);
    } catch (\PDOException $e) {
        error_log("Erreur récupération commandes quotidiennes: " . $e->getMessage());
        $daily_orders = [];
    }
    skip_daily_orders:
}

// Préparation des données pour Chart.js
$chart_campaign_labels = json_encode(
    array_map(function ($c) {
        return $c["campaign_name"] . " (" . $c["country"] . ")";
    }, $campaign_stats),
);
$chart_campaign_orders = json_encode(array_column($campaign_stats, "orders_count"));
$chart_campaign_quantity = json_encode(array_column($campaign_stats, "quantity_count"));

$chart_category_labels = json_encode(array_column($product_categories, "category_name"));
$chart_category_counts = json_encode(array_column($product_categories, "quantity_sold"));
$chart_category_colors = json_encode(
    array_map(function ($cat) {
        return $cat["color"] ?? "#6366F1";
    }, $product_categories),
);

$chart_day_labels = json_encode(array_column($daily_orders, "day_label"));
$chart_day_counts = json_encode(array_column($daily_orders, "orders_count"));
$chart_day_quantity = json_encode(array_column($daily_orders, "quantity_count"));

// Compter combien de KPI cards seront affichées pour la grille
$kpiCount = 0;
if ($canViewCampaigns) $kpiCount++;
if ($canViewCustomers) $kpiCount++;
if ($canViewOrders) $kpiCount += 2; // Commandes + Quantité
if ($canViewProducts) $kpiCount++;

// Déterminer les classes de grille selon le nombre de KPI
$kpiGridClass = match(true) {
    $kpiCount >= 5 => 'lg:grid-cols-5',
    $kpiCount === 4 => 'lg:grid-cols-4',
    $kpiCount === 3 => 'lg:grid-cols-3',
    $kpiCount === 2 => 'lg:grid-cols-2',
    default => 'lg:grid-cols-1',
};

// Compter les actions rapides disponibles
$quickActionsCount = 0;
if ($canCreateCampaigns) $quickActionsCount++;
if ($canViewProducts) $quickActionsCount++;
if ($canViewCustomers) $quickActionsCount++;
if ($canViewStats) $quickActionsCount++;

$quickActionsGridClass = match(true) {
    $quickActionsCount >= 4 => 'lg:grid-cols-4',
    $quickActionsCount === 3 => 'lg:grid-cols-3',
    $quickActionsCount === 2 => 'lg:grid-cols-2',
    default => 'lg:grid-cols-1',
};
?>

<!-- En-tête de page -->
<div class="mb-8">
    <div class="md:flex md:items-center md:justify-between">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                Dashboard
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Vue d'ensemble de vos campagnes et statistiques <span class="text-indigo-600 font-medium">(<?= $periodLabel ?>)</span>
            </p>
        </div>
        <div class="mt-4 flex md:ml-4 md:mt-0">
            <?php if ($canViewStats): ?>
            <a href="/stm/admin/stats" class="inline-flex items-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 mr-3">
                <i class="fas fa-chart-bar mr-2"></i>
                Statistiques
            </a>
            <?php endif; ?>
            <?php if ($canCreateCampaigns): ?>
            <a href="/stm/admin/campaigns/create" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                <i class="fas fa-plus mr-2"></i>
                Nouvelle campagne
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- KPI Cards (affichées conditionnellement) -->
<?php if ($kpiCount > 0): ?>
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 <?= $kpiGridClass ?> mb-8">

    <?php if ($canViewCampaigns): ?>
    <!-- Campagnes -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-bullhorn text-indigo-600"></i>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Campagnes</dt>
                        <dd class="flex items-baseline">
                            <span class="text-2xl font-bold text-indigo-600"><?= $stats["active_campaigns"] ?></span>
                            <span class="ml-2 text-sm text-gray-500">/ <?= $stats["total_campaigns"] ?> actives</span>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($canViewCustomers): ?>
    <!-- Clients -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-users text-green-600"></i>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Clients actifs</dt>
                        <dd class="text-2xl font-bold text-green-600"><?= number_format($stats["total_customers"], 0, ",", " ") ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($canViewOrders): ?>
    <!-- Commandes -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-shopping-cart text-blue-600"></i>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Commandes</dt>
                        <dd class="text-2xl font-bold text-blue-600"><?= number_format($stats["total_orders"], 0, ",", " ") ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Quantité totale -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-box text-orange-600"></i>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Promos vendues</dt>
                        <dd class="text-2xl font-bold text-orange-600"><?= number_format($stats["total_quantity"], 0, ",", " ") ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($canViewProducts): ?>
    <!-- Promos actives -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-tags text-purple-600"></i>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Promos actives</dt>
                        <dd class="text-2xl font-bold text-purple-600"><?= number_format($stats["total_promos"], 0, ",", " ") ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>
<?php endif; ?>

<!-- Graphiques (seulement si permission stats) -->
<?php if ($canViewStats): ?>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Graphique par campagne -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Promos vendues par campagne active <span class="text-sm font-normal text-gray-500">(<?= $periodLabel ?>)</span></h3>
        <?php if (empty($campaign_stats)): ?>
        <div class="text-center py-8 text-gray-500">
            <i class="fas fa-chart-bar text-4xl text-gray-300 mb-3"></i>
            <p>Aucune campagne active</p>
        </div>
        <?php else: ?>
        <canvas id="campaignChart" height="200"></canvas>
        <?php endif; ?>
    </div>

    <!-- Graphique par catégorie -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Ventes par catégorie <span class="text-sm font-normal text-gray-500">(<?= $periodLabel ?>)</span></h3>
        <?php if (empty($product_categories) || array_sum(array_column($product_categories, "quantity_sold")) == 0): ?>
        <div class="text-center py-8 text-gray-500">
            <i class="fas fa-chart-pie text-4xl text-gray-300 mb-3"></i>
            <p>Aucune donnée de vente</p>
        </div>
        <?php else: ?>
        <canvas id="categoryChart" height="200"></canvas>
        <?php endif; ?>
    </div>
</div>

<!-- Évolution des ventes -->
<div class="bg-white shadow rounded-lg p-6 mb-8">
    <h3 class="text-lg font-medium text-gray-900 mb-4">Évolution des ventes <span class="text-sm font-normal text-gray-500">(<?= $periodLabel ?>)</span></h3>
    <?php if (empty($daily_orders)): ?>
    <div class="text-center py-8 text-gray-500">
        <i class="fas fa-chart-line text-4xl text-gray-300 mb-3"></i>
        <p>Aucune donnée sur la période</p>
    </div>
    <?php else: ?>
    <canvas id="dailyChart" height="80"></canvas>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Dernières commandes (14 derniers jours) -->
<?php if ($canViewOrders): ?>
<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-900">Dernières commandes <span class="text-sm font-normal text-gray-500">(<?= $periodLabel ?>)</span></h3>
        <a href="/stm/admin/orders" class="text-sm text-indigo-600 hover:text-indigo-800">
            Voir tout <i class="fas fa-arrow-right ml-1"></i>
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campagne</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pays</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantité</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($recent_orders)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">
                            <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                            <p>Aucune commande pour le moment</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_orders as $order): ?>
                    <?php
                        $orderId = $order["id"] ?? 0;
                        $orderNumber = htmlspecialchars($order["order_number"] ?? "N/A");
                        $campaignName = htmlspecialchars($order["campaign_name"] ?? "N/A");
                        $companyName = htmlspecialchars($order["company_name"] ?? "N/A");
                        $country = htmlspecialchars($order["country"] ?? "");
                        $status = $order["status"] ?? "pending";
                        $itemsCount = (int) ($order["items_count"] ?? 0);
                        $createdAt = $order["created_at"] ?? date("Y-m-d H:i:s");

                        $statusColors = [
                            "pending" => "bg-yellow-100 text-yellow-800",
                            "validated" => "bg-green-100 text-green-800",
                            "cancelled" => "bg-red-100 text-red-800",
                            "exported" => "bg-blue-100 text-blue-800",
                        ];
                        $statusLabels = [
                            "pending" => "En attente",
                            "validated" => "Validée",
                            "cancelled" => "Annulée",
                            "exported" => "Exportée",
                        ];
                    ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900"><?= $campaignName ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900"><?= $companyName ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($country): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    <?= $country ?>
                                </span>
                                <?php else: ?>
                                <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusColors[$status] ?? 'bg-gray-100 text-gray-800' ?>">
                                    <?= $statusLabels[$status] ?? ucfirst($status) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-orange-600">
                                <?= number_format($itemsCount, 0, ",", " ") ?> promo<?= $itemsCount > 1 ? "s" : "" ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= date("d/m/Y H:i", strtotime($createdAt)) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                <a href="/stm/admin/orders/<?= $orderId ?>" class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded-md hover:bg-indigo-100 transition-colors">
                                    <i class="fas fa-eye mr-1"></i>
                                    Voir
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Actions rapides (affichées conditionnellement) -->
<?php if ($quickActionsCount > 0): ?>
<div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 <?= $quickActionsGridClass ?>">

    <?php if ($canCreateCampaigns): ?>
    <a href="/stm/admin/campaigns/create" class="relative block rounded-lg border-2 border-dashed border-gray-300 p-6 text-center hover:border-indigo-500 focus:outline-none transition-colors">
        <i class="fas fa-bullhorn text-4xl text-gray-400 mb-2"></i>
        <span class="mt-2 block text-sm font-medium text-gray-900">Créer une campagne</span>
    </a>
    <?php endif; ?>

    <?php if ($canViewProducts): ?>
    <a href="/stm/admin/products" class="relative block rounded-lg border-2 border-dashed border-gray-300 p-6 text-center hover:border-green-500 focus:outline-none transition-colors">
        <i class="fas fa-box text-4xl text-gray-400 mb-2"></i>
        <span class="mt-2 block text-sm font-medium text-gray-900">Gérer les Promotions</span>
    </a>
    <?php endif; ?>

    <?php if ($canViewCustomers): ?>
    <a href="/stm/admin/customers" class="relative block rounded-lg border-2 border-dashed border-gray-300 p-6 text-center hover:border-yellow-500 focus:outline-none transition-colors">
        <i class="fas fa-users text-4xl text-gray-400 mb-2"></i>
        <span class="mt-2 block text-sm font-medium text-gray-900">Gérer les clients</span>
    </a>
    <?php endif; ?>

    <?php if ($canViewStats): ?>
    <a href="/stm/admin/stats" class="relative block rounded-lg border-2 border-dashed border-gray-300 p-6 text-center hover:border-blue-500 focus:outline-none transition-colors">
        <i class="fas fa-chart-bar text-4xl text-gray-400 mb-2"></i>
        <span class="mt-2 block text-sm font-medium text-gray-900">Voir les statistiques</span>
    </a>
    <?php endif; ?>

</div>
<?php endif; ?>

<?php
// Capturer le contenu
$content = ob_get_clean();

// Définir le titre de la page
$title = "Dashboard";

// Scripts spécifiques à cette page (seulement si permission stats)
$pageScripts = "";

if ($canViewStats) {
    $pageScripts = "
<script>
// Configuration Chart.js globale
Chart.defaults.font.family = \"'Inter', sans-serif\";
Chart.defaults.color = '#6B7280';
";

    // Graphique campagnes (seulement si données)
    if (!empty($campaign_stats)) {
        $pageScripts .= "
// Graphique des promos par campagne (Barres)
const ctxCampaign = document.getElementById('campaignChart');
if (ctxCampaign) {
    new Chart(ctxCampaign.getContext('2d'), {
        type: 'bar',
        data: {
            labels: {$chart_campaign_labels},
            datasets: [
                {
                    label: 'Commandes',
                    data: {$chart_campaign_orders},
                    backgroundColor: 'rgba(99, 102, 241, 0.8)',
                    borderColor: 'rgba(99, 102, 241, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Promos vendues',
                    data: {$chart_campaign_quantity},
                    backgroundColor: 'rgba(249, 115, 22, 0.8)',
                    borderColor: 'rgba(249, 115, 22, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
}
";
    }

    // Graphique catégories (seulement si données)
    if (!empty($product_categories) && array_sum(array_column($product_categories, "quantity_sold")) > 0) {
        $pageScripts .= "
// Graphique des catégories (Donut)
const ctxCategory = document.getElementById('categoryChart');
if (ctxCategory) {
    new Chart(ctxCategory.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: {$chart_category_labels},
            datasets: [{
                data: {$chart_category_counts},
                backgroundColor: {$chart_category_colors},
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}
";
    }

    // Graphique quotidien (seulement si données)
    if (!empty($daily_orders)) {
        $pageScripts .= "
// Graphique quotidien (Ligne) - 14 derniers jours
const ctxDaily = document.getElementById('dailyChart');
if (ctxDaily) {
    new Chart(ctxDaily.getContext('2d'), {
        type: 'line',
        data: {
            labels: {$chart_day_labels},
            datasets: [
                {
                    label: 'Commandes',
                    data: {$chart_day_counts},
                    borderColor: 'rgba(99, 102, 241, 1)',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4,
                    fill: false,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    yAxisID: 'y'
                },
                {
                    label: 'Promos vendues',
                    data: {$chart_day_quantity},
                    borderColor: 'rgba(249, 115, 22, 1)',
                    backgroundColor: 'rgba(249, 115, 22, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    beginAtZero: true,
                    ticks: { precision: 0 },
                    title: { display: true, text: 'Commandes' }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    beginAtZero: true,
                    ticks: { precision: 0 },
                    title: { display: true, text: 'Promos' },
                    grid: { drawOnChartArea: false }
                }
            }
        }
    });
}
";
    }

    $pageScripts .= "</script>";
}

// Inclure le layout admin
require __DIR__ . "/../layouts/admin.php";
?>