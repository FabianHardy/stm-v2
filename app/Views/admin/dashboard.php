<?php
/**
 * Vue : Dashboard Admin
 * Description : Page principale du dashboard avec KPI et statistiques
 * Layout : layouts/admin.php
 *
 * @modified 2025/11/27 - Fix htmlspecialchars null + Graphique 7 jours au lieu de 6 mois
 */

use Core\Database;

// Démarrer le buffering de sortie pour capturer le contenu
ob_start();

// Récupération de l'instance Database
$db = Database::getInstance();

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

// KPI 1: Campagnes totales et actives (calcul dynamique basé sur les dates)
try {
    $results = $db->query("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN CURDATE() BETWEEN start_date AND end_date THEN 1 ELSE 0 END) as active
        FROM campaigns
    ");

    if (!empty($results)) {
        $stats["total_campaigns"] = (int) ($results[0]["total"] ?? 0);
        $stats["active_campaigns"] = (int) ($results[0]["active"] ?? 0);
    }
} catch (\PDOException $e) {
    error_log("Erreur récupération stats campagnes: " . $e->getMessage());
}

// KPI 2: Clients totaux (depuis orders validées uniquement - clients ayant commandé)
try {
    $results = $db->query("SELECT COUNT(DISTINCT customer_id) as total FROM orders WHERE status = 'validated'");
    if (!empty($results)) {
        $stats["total_customers"] = (int) ($results[0]["total"] ?? 0);
    }
} catch (\PDOException $e) {
    error_log("Erreur récupération stats clients: " . $e->getMessage());
}

// KPI 3: Commandes validées et quantités totales
try {
    $results = $db->query("
        SELECT
            COUNT(DISTINCT o.id) as total_orders,
            COALESCE(SUM(ol.quantity), 0) as total_quantity
        FROM orders o
        LEFT JOIN order_lines ol ON o.id = ol.order_id
        WHERE o.status = 'validated'
    ");
    if (!empty($results)) {
        $stats["total_orders"] = (int) ($results[0]["total_orders"] ?? 0);
        $stats["total_quantity"] = (int) ($results[0]["total_quantity"] ?? 0);
    }
} catch (\PDOException $e) {
    error_log("Erreur récupération stats commandes: " . $e->getMessage());
}

// KPI 4: Promotions actives
try {
    $results = $db->query("
        SELECT COUNT(*) as total
        FROM products
        WHERE is_active = 1
    ");
    if (!empty($results)) {
        $stats["total_promos"] = (int) ($results[0]["total"] ?? 0);
    }
} catch (\PDOException $e) {
    error_log("Erreur récupération stats Promotions: " . $e->getMessage());
}

// Dernières commandes (corrigé: utiliser c.name au lieu de c.title)
try {
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
        LEFT JOIN customers cu ON o.customer_id = cu.id
        LEFT JOIN order_lines ol ON o.id = ol.order_id
        GROUP BY o.id, o.order_number, c.name, cu.company_name, cu.country, o.status, o.created_at
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
} catch (\PDOException $e) {
    error_log("Erreur récupération dernières commandes: " . $e->getMessage());
    $recent_orders = [];
}

// Stats par campagne pour le graphique (campagnes actives)
try {
    $campaign_stats = $db->query("
        SELECT
            c.name as campaign_name,
            c.country,
            COUNT(DISTINCT o.id) as orders_count,
            COALESCE(SUM(ol.quantity), 0) as quantity_count
        FROM campaigns c
        LEFT JOIN orders o ON c.id = o.campaign_id AND o.status = 'validated'
        LEFT JOIN order_lines ol ON o.id = ol.order_id
        WHERE CURDATE() BETWEEN c.start_date AND c.end_date
        GROUP BY c.id, c.name, c.country
        ORDER BY quantity_count DESC
        LIMIT 5
    ");
} catch (\PDOException $e) {
    error_log("Erreur récupération stats campagnes: " . $e->getMessage());
    $campaign_stats = [];
}

// Répartition par catégorie de Promotions
try {
    $product_categories = $db->query("
        SELECT
            cat.name_fr as category_name,
            cat.color,
            COUNT(DISTINCT p.id) as products_count,
            COALESCE(SUM(ol.quantity), 0) as quantity_sold
        FROM categories cat
        LEFT JOIN products p ON cat.id = p.category_id AND p.is_active = 1
        LEFT JOIN order_lines ol ON p.id = ol.product_id
        LEFT JOIN orders o ON ol.order_id = o.id AND o.status = 'validated'
        GROUP BY cat.id, cat.name_fr, cat.color
        ORDER BY quantity_sold DESC
    ");
} catch (\PDOException $e) {
    error_log("Erreur récupération catégories: " . $e->getMessage());
    $product_categories = [];
}

// Commandes des 7 derniers jours (uniquement validées)
try {
    $daily_orders = $db->query("
        SELECT
            DATE(o.created_at) as day,
            DATE_FORMAT(o.created_at, '%a %d/%m') as day_label,
            COUNT(DISTINCT o.id) as orders_count,
            COALESCE(SUM(ol.quantity), 0) as quantity_count
        FROM orders o
        LEFT JOIN order_lines ol ON o.id = ol.order_id
        WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        AND o.status = 'validated'
        GROUP BY DATE(o.created_at), DATE_FORMAT(o.created_at, '%a %d/%m')
        ORDER BY day ASC
    ");
} catch (\PDOException $e) {
    error_log("Erreur récupération commandes quotidiennes: " . $e->getMessage());
    $daily_orders = [];
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
?>

<!-- En-tête de page -->
<div class="mb-8">
    <div class="md:flex md:items-center md:justify-between">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                Dashboard
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Vue d'ensemble de vos campagnes et statistiques
            </p>
        </div>
        <div class="mt-4 flex md:ml-4 md:mt-0">
            <a href="/stm/admin/stats" class="inline-flex items-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 mr-3">
                <i class="fas fa-chart-bar mr-2"></i>
                Statistiques
            </a>
            <a href="/stm/admin/campaigns/create" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                <i class="fas fa-plus mr-2"></i>
                Nouvelle campagne
            </a>
        </div>
    </div>
</div>

<!-- KPI Cards -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-5 mb-8">
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
                            <span class="text-2xl font-bold text-indigo-600"><?php echo $stats[
                                "active_campaigns"
                            ]; ?></span>
                            <span class="ml-2 text-sm text-gray-500">/ <?php echo $stats[
                                "total_campaigns"
                            ]; ?> actives</span>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

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
                        <dd class="text-2xl font-bold text-green-600"><?php echo number_format(
                            $stats["total_customers"],
                            0,
                            ",",
                            " ",
                        ); ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

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
                        <dd class="text-2xl font-bold text-blue-600"><?php echo number_format(
                            $stats["total_orders"],
                            0,
                            ",",
                            " ",
                        ); ?></dd>
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
                        <dd class="text-2xl font-bold text-orange-600"><?php echo number_format(
                            $stats["total_quantity"],
                            0,
                            ",",
                            " ",
                        ); ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

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
                        <dd class="text-2xl font-bold text-purple-600"><?php echo number_format(
                            $stats["total_promos"],
                            0,
                            ",",
                            " ",
                        ); ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Graphiques -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Graphique par campagne -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Promos vendues par campagne active</h3>
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
        <h3 class="text-lg font-medium text-gray-900 mb-4">Ventes par catégorie</h3>
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

<!-- Évolution quotidienne (7 derniers jours) -->
<div class="bg-white shadow rounded-lg p-6 mb-8">
    <h3 class="text-lg font-medium text-gray-900 mb-4">Évolution des ventes (7 derniers jours)</h3>
    <?php if (empty($daily_orders)): ?>
    <div class="text-center py-8 text-gray-500">
        <i class="fas fa-chart-line text-4xl text-gray-300 mb-3"></i>
        <p>Aucune donnée sur les 7 derniers jours</p>
    </div>
    <?php else: ?>
    <canvas id="dailyChart" height="80"></canvas>
    <?php endif; ?>
</div>

<!-- Dernières commandes -->
<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-900">Dernières commandes</h3>
        <a href="/stm/admin/orders" class="text-sm text-indigo-600 hover:text-indigo-800">
            Voir tout <i class="fas fa-arrow-right ml-1"></i>
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">N° Commande</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campagne</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pays</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantité</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
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
                <?php
                    // Sécurisation des valeurs nullables
                    // Sécurisation des valeurs nullables
                    else: ?>
                    <?php foreach ($recent_orders as $order): ?>
                        <?php
                        $statusLabels = [
                            "pending" => ["label" => "En attente", "class" => "bg-yellow-100 text-yellow-800"],
                            "validated" => ["label" => "Validée", "class" => "bg-green-100 text-green-800"],
                            "cancelled" => ["label" => "Annulée", "class" => "bg-red-100 text-red-800"],
                        ];
                        $status = $statusLabels[$order["status"] ?? "pending"] ?? [
                            "label" => $order["status"] ?? "Inconnu",
                            "class" => "bg-gray-100 text-gray-800",
                        ];
                        $orderNumber = $order["order_number"] ?? "";
                        $campaignName = $order["campaign_name"] ?? "N/A";
                        $companyName = $order["company_name"] ?? "N/A";
                        $country = $order["country"] ?? "BE";
                        $itemsCount = (int) ($order["items_count"] ?? 0);
                        $createdAt = $order["created_at"] ?? date("Y-m-d H:i:s");
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($orderNumber); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($campaignName); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo htmlspecialchars($companyName); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $country ===
                                "BE"
                                    ? "bg-blue-100 text-blue-800"
                                    : "bg-yellow-100 text-yellow-800"; ?>">
                                    <?php echo $country === "BE" ? "🇧🇪" : "🇱🇺"; ?> <?php echo htmlspecialchars(
     strtoupper($country),
 ); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status[
                                    "class"
                                ]; ?>">
                                    <?php echo htmlspecialchars($status["label"]); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-orange-600">
                                <?php echo number_format($itemsCount, 0, ",", " "); ?> promo<?php echo $itemsCount > 1
     ? "s"
     : ""; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date("d/m/Y H:i", strtotime($createdAt)); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Actions rapides -->
<div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
    <a href="/stm/admin/campaigns/create" class="relative block rounded-lg border-2 border-dashed border-gray-300 p-6 text-center hover:border-indigo-500 focus:outline-none transition-colors">
        <i class="fas fa-bullhorn text-4xl text-gray-400 mb-2"></i>
        <span class="mt-2 block text-sm font-medium text-gray-900">Créer une campagne</span>
    </a>

    <a href="/stm/admin/products" class="relative block rounded-lg border-2 border-dashed border-gray-300 p-6 text-center hover:border-green-500 focus:outline-none transition-colors">
        <i class="fas fa-box text-4xl text-gray-400 mb-2"></i>
        <span class="mt-2 block text-sm font-medium text-gray-900">Gérer les Promotions</span>
    </a>

    <a href="/stm/admin/customers" class="relative block rounded-lg border-2 border-dashed border-gray-300 p-6 text-center hover:border-yellow-500 focus:outline-none transition-colors">
        <i class="fas fa-users text-4xl text-gray-400 mb-2"></i>
        <span class="mt-2 block text-sm font-medium text-gray-900">Gérer les clients</span>
    </a>

    <a href="/stm/admin/stats" class="relative block rounded-lg border-2 border-dashed border-gray-300 p-6 text-center hover:border-blue-500 focus:outline-none transition-colors">
        <i class="fas fa-chart-bar text-4xl text-gray-400 mb-2"></i>
        <span class="mt-2 block text-sm font-medium text-gray-900">Voir les statistiques</span>
    </a>
</div>

<?php
// Capturer le contenu
$content = ob_get_clean();

// Définir le titre de la page
$title = "Dashboard";

// Scripts spécifiques à cette page
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
// Graphique quotidien (Ligne) - 7 derniers jours
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

// Inclure le layout admin
require __DIR__ . "/../layouts/admin.php";


?>
