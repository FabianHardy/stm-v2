<?php
/**
 * Vue : Dashboard Admin
 * Description : Page principale du dashboard avec KPI et statistiques
 * Layout : layouts/admin.php
 */

use Core\Database;

// Démarrer le buffering de sortie pour capturer le contenu
ob_start();

// Récupération de l'instance Database
$db = Database::getInstance();

// Initialisation des variables par défaut
$stats = [
    'total_campaigns' => 0,
    'active_campaigns' => 0,
    'total_customers' => 0,
    'total_orders' => 0,
    'total_products' => 0
];

$recent_orders = [];
$campaign_stats = [];
$product_categories = [];
$monthly_orders = [];

// KPI 1: Campagnes totales et actives
try {
    $results = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'active' AND NOW() BETWEEN start_date AND end_date THEN 1 ELSE 0 END) as active
        FROM campaigns
    ");
    
    if (!empty($results)) {
        $stats['total_campaigns'] = $results[0]['total'] ?? 0;
        $stats['active_campaigns'] = $results[0]['active'] ?? 0;
    }
} catch (\PDOException $e) {
    error_log("Erreur récupération stats campagnes: " . $e->getMessage());
}

// KPI 2: Clients totaux
try {
    $results = $db->query("SELECT COUNT(*) as total FROM customers");
    if (!empty($results)) {
        $stats['total_customers'] = $results[0]['total'] ?? 0;
    }
} catch (\PDOException $e) {
    error_log("Erreur récupération stats clients: " . $e->getMessage());
}

// KPI 3: Commandes totales
try {
    $results = $db->query("SELECT COUNT(*) as total FROM orders");
    if (!empty($results)) {
        $stats['total_orders'] = $results[0]['total'] ?? 0;
    }
} catch (\PDOException $e) {
    error_log("Erreur récupération stats commandes: " . $e->getMessage());
}

// KPI 4: Produits totaux
try {
    $results = $db->query("
        SELECT COUNT(*) as total 
        FROM products 
        WHERE is_active = 1
    ");
    if (!empty($results)) {
        $stats['total_products'] = $results[0]['total'] ?? 0;
    }
} catch (\PDOException $e) {
    error_log("Erreur récupération stats produits: " . $e->getMessage());
}

// Dernières commandes
try {
    $recent_orders = $db->query("
        SELECT 
            o.id,
            o.order_number,
            c.title as campaign_name,
            cu.company_name,
            cu.country,
            o.created_at,
            COUNT(ol.id) as items_count
        FROM orders o
        LEFT JOIN campaigns c ON o.campaign_id = c.id
        LEFT JOIN customers cu ON o.customer_id = cu.id
        LEFT JOIN order_lines ol ON o.id = ol.order_id
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
} catch (\PDOException $e) {
    error_log("Erreur récupération dernières commandes: " . $e->getMessage());
    $recent_orders = [];
}

// Stats par campagne pour le graphique
try {
    $campaign_stats = $db->query("
        SELECT 
            c.title as campaign_name,
            COUNT(DISTINCT o.id) as orders_count,
            COUNT(DISTINCT ol.id) as items_count
        FROM campaigns c
        LEFT JOIN orders o ON c.id = o.campaign_id
        LEFT JOIN order_lines ol ON o.id = ol.order_id
        WHERE c.status = 'active'
        GROUP BY c.id
        ORDER BY orders_count DESC
        LIMIT 5
    ");
} catch (\PDOException $e) {
    error_log("Erreur récupération stats campagnes: " . $e->getMessage());
    $campaign_stats = [];
}

// Répartition par catégorie de produits
try {
    $product_categories = $db->query("
        SELECT 
            cat.name_fr as category_name,
            cat.color,
            COUNT(DISTINCT p.id) as products_count,
            COUNT(DISTINCT ol.id) as orders_count
        FROM categories cat
        LEFT JOIN products p ON cat.id = p.category_id AND p.is_active = 1
        LEFT JOIN order_lines ol ON p.id = ol.product_id
        GROUP BY cat.id
        ORDER BY products_count DESC
    ");
} catch (\PDOException $e) {
    error_log("Erreur récupération catégories: " . $e->getMessage());
    $product_categories = [];
}

// Commandes des 6 derniers mois
try {
    $monthly_orders = $db->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            DATE_FORMAT(created_at, '%M %Y') as month_label,
            COUNT(*) as orders_count
        FROM orders
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
} catch (\PDOException $e) {
    error_log("Erreur récupération commandes mensuelles: " . $e->getMessage());
    $monthly_orders = [];
}

// Préparation des données pour Chart.js
$chart_campaign_labels = json_encode(array_column($campaign_stats, 'campaign_name'));
$chart_campaign_orders = json_encode(array_column($campaign_stats, 'orders_count'));
$chart_campaign_items = json_encode(array_column($campaign_stats, 'items_count'));

$chart_category_labels = json_encode(array_column($product_categories, 'category_name'));
$chart_category_counts = json_encode(array_column($product_categories, 'products_count'));
$chart_category_colors = json_encode(array_map(function($cat) {
    return $cat['color'] ?? '#6366F1';
}, $product_categories));

$chart_month_labels = json_encode(array_column($monthly_orders, 'month_label'));
$chart_month_counts = json_encode(array_column($monthly_orders, 'orders_count'));
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
            <a href="/stm/admin/campaigns/create" class="ml-3 inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                </svg>
                Nouvelle campagne
            </a>
        </div>
    </div>
</div>

<!-- KPI Cards -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
    <!-- Campagnes actives -->
    <div class="overflow-hidden rounded-lg bg-white shadow">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="rounded-md bg-indigo-500 p-3">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="truncate text-sm font-medium text-gray-500">Campagnes actives</dt>
                        <dd class="flex items-baseline">
                            <div class="text-2xl font-semibold text-gray-900">
                                <?= $stats['active_campaigns'] ?>
                            </div>
                            <div class="ml-2 text-sm text-gray-500">
                                / <?= $stats['total_campaigns'] ?> total
                            </div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Clients -->
    <div class="overflow-hidden rounded-lg bg-white shadow">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="rounded-md bg-green-500 p-3">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="truncate text-sm font-medium text-gray-500">Clients</dt>
                        <dd class="flex items-baseline">
                            <div class="text-2xl font-semibold text-gray-900">
                                <?= number_format($stats['total_customers'], 0, ',', ' ') ?>
                            </div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Commandes -->
    <div class="overflow-hidden rounded-lg bg-white shadow">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="rounded-md bg-yellow-500 p-3">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="truncate text-sm font-medium text-gray-500">Commandes</dt>
                        <dd class="flex items-baseline">
                            <div class="text-2xl font-semibold text-gray-900">
                                <?= number_format($stats['total_orders'], 0, ',', ' ') ?>
                            </div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Produits -->
    <div class="overflow-hidden rounded-lg bg-white shadow">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="rounded-md bg-purple-500 p-3">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="truncate text-sm font-medium text-gray-500">Produits actifs</dt>
                        <dd class="flex items-baseline">
                            <div class="text-2xl font-semibold text-gray-900">
                                <?= number_format($stats['total_products'], 0, ',', ' ') ?>
                            </div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Graphiques -->
<div class="grid grid-cols-1 gap-5 lg:grid-cols-2 mb-8">
    <!-- Commandes par campagne -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Commandes par campagne</h3>
        <canvas id="campaignChart" height="200"></canvas>
    </div>

    <!-- Répartition par catégorie -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Produits par catégorie</h3>
        <canvas id="categoryChart" height="200"></canvas>
    </div>
</div>

<!-- Évolution mensuelle -->
<div class="bg-white shadow rounded-lg p-6 mb-8">
    <h3 class="text-lg font-medium text-gray-900 mb-4">Évolution des commandes (6 derniers mois)</h3>
    <canvas id="monthlyChart" height="80"></canvas>
</div>

<!-- Dernières commandes -->
<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Dernières commandes</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">N° Commande</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campagne</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pays</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Articles</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($recent_orders)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">
                            Aucune commande pour le moment
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_orders as $order): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?= htmlspecialchars($order['order_number']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= htmlspecialchars($order['campaign_name'] ?? 'N/A') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= htmlspecialchars($order['company_name'] ?? 'N/A') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <?= strtoupper($order['country']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= $order['items_count'] ?> article<?= $order['items_count'] > 1 ? 's' : '' ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="/admin/orders/<?= $order['id'] ?>" class="text-indigo-600 hover:text-indigo-900">
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

<!-- Actions rapides -->
<div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
    <a href="/admin/campaigns/create" class="relative block rounded-lg border-2 border-dashed border-gray-300 p-6 text-center hover:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span class="mt-2 block text-sm font-medium text-gray-900">Créer une campagne</span>
    </a>

    <a href="/admin/products" class="relative block rounded-lg border-2 border-dashed border-gray-300 p-6 text-center hover:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
        </svg>
        <span class="mt-2 block text-sm font-medium text-gray-900">Gérer les produits</span>
    </a>

    <a href="/admin/customers" class="relative block rounded-lg border-2 border-dashed border-gray-300 p-6 text-center hover:border-yellow-500 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition-colors">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        <span class="mt-2 block text-sm font-medium text-gray-900">Gérer les clients</span>
    </a>
</div>

<?php
// Capturer le contenu
$content = ob_get_clean();

// Définir le titre de la page
$title = 'Dashboard';

// Scripts spécifiques à cette page
$pageScripts = "
<script>
// Configuration Chart.js globale
Chart.defaults.font.family = \"'Inter', sans-serif\";
Chart.defaults.color = '#6B7280';

// Graphique des commandes par campagne (Barres)
const ctxCampaign = document.getElementById('campaignChart').getContext('2d');
new Chart(ctxCampaign, {
    type: 'bar',
    data: {
        labels: {$chart_campaign_labels},
        datasets: [{
            label: 'Commandes',
            data: {$chart_campaign_orders},
            backgroundColor: 'rgba(99, 102, 241, 0.8)',
            borderColor: 'rgba(99, 102, 241, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
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

// Graphique des catégories (Donut)
const ctxCategory = document.getElementById('categoryChart').getContext('2d');
new Chart(ctxCategory, {
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

// Graphique mensuel (Ligne)
const ctxMonthly = document.getElementById('monthlyChart').getContext('2d');
new Chart(ctxMonthly, {
    type: 'line',
    data: {
        labels: {$chart_month_labels},
        datasets: [{
            label: 'Commandes',
            data: {$chart_month_counts},
            borderColor: 'rgba(59, 130, 246, 1)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true,
            pointRadius: 4,
            pointHoverRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
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
</script>
";

// Inclure le layout admin
require __DIR__ . '/../layouts/admin.php';
?>