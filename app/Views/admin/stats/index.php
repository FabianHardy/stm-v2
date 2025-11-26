<?php
/**
 * Vue : Statistiques - Vue globale
 * 
 * Dashboard statistiques avec KPIs, graphiques et filtres
 * 
 * @package STM
 * @created 2025/11/25
 */

ob_start();
?>

<!-- En-tête -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Statistiques - Vue globale</h1>
    <p class="text-gray-600 mt-1">Aperçu des performances sur la période sélectionnée</p>
</div>

<!-- Filtres -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <form method="GET" action="/stm/admin/stats" class="flex flex-wrap gap-4 items-end">
        
        <!-- Période -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Période</label>
            <select name="period" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <option value="7" <?= ($period ?? '14') === '7' ? 'selected' : '' ?>>7 derniers jours</option>
                <option value="14" <?= ($period ?? '14') === '14' ? 'selected' : '' ?>>14 derniers jours</option>
                <option value="30" <?= ($period ?? '14') === '30' ? 'selected' : '' ?>>30 derniers jours</option>
                <option value="month" <?= ($period ?? '14') === 'month' ? 'selected' : '' ?>>Ce mois</option>
            </select>
        </div>
        
        <!-- Campagne -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Campagne</label>
            <select name="campaign_id" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <option value="">Toutes les campagnes</option>
                <?php foreach ($campaigns as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ($campaignId ?? null) == $c['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Pays -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Pays</label>
            <select name="country" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <option value="">Tous</option>
                <option value="BE" <?= ($country ?? '') === 'BE' ? 'selected' : '' ?>>Belgique</option>
                <option value="LU" <?= ($country ?? '') === 'LU' ? 'selected' : '' ?>>Luxembourg</option>
            </select>
        </div>
        
        <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition">
            <i class="fas fa-filter mr-2"></i>Filtrer
        </button>
    </form>
</div>

<!-- KPIs -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    
    <!-- Total commandes -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Commandes</p>
                <p class="text-3xl font-bold text-gray-900"><?= number_format($kpis['total_orders'], 0, ',', ' ') ?></p>
            </div>
            <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
                <i class="fas fa-shopping-cart text-indigo-600 text-xl"></i>
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-2"><?= $periodLabel ?? '14 derniers jours' ?></p>
    </div>
    
    <!-- Clients uniques -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Clients</p>
                <p class="text-3xl font-bold text-gray-900"><?= number_format($kpis['unique_customers'], 0, ',', ' ') ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-users text-green-600 text-xl"></i>
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-2">Clients distincts</p>
    </div>
    
    <!-- Total quantités -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Promos vendues</p>
                <p class="text-3xl font-bold text-gray-900"><?= number_format($kpis['total_quantity'], 0, ',', ' ') ?></p>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                <i class="fas fa-box text-orange-600 text-xl"></i>
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-2">Quantité totale</p>
    </div>
    
    <!-- Répartition BE/LU -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Répartition</p>
                <div class="flex items-center gap-4 mt-1">
                    <div>
                        <span class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-medium">
                            BE: <?= $kpis['orders_by_country']['BE'] ?? 0 ?>
                        </span>
                    </div>
                    <div>
                        <span class="inline-flex items-center px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs font-medium">
                            LU: <?= $kpis['orders_by_country']['LU'] ?? 0 ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                <i class="fas fa-globe-europe text-purple-600 text-xl"></i>
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-2">Commandes par pays</p>
    </div>
</div>

<!-- Graphiques -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    
    <!-- Évolution quotidienne -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Évolution des commandes</h3>
        <canvas id="evolutionChart" height="200"></canvas>
    </div>
    
    <!-- Répartition par pays (pie) -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quantités par pays</h3>
        <canvas id="countryChart" height="200"></canvas>
    </div>
</div>

<!-- Top produits et Stats par cluster -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    
    <!-- Top 10 produits -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Top 10 produits</h3>
        
        <?php if (empty($topProducts)): ?>
        <p class="text-gray-500 text-center py-8">Aucune donnée pour cette période</p>
        <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($topProducts as $i => $product): ?>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="w-6 h-6 bg-indigo-100 text-indigo-800 rounded-full flex items-center justify-center text-sm font-medium">
                        <?= $i + 1 ?>
                    </span>
                    <div>
                        <p class="font-medium text-gray-900 text-sm"><?= htmlspecialchars($product['product_name']) ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars($product['product_code']) ?></p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="font-bold text-gray-900"><?= number_format($product['total_quantity'], 0, ',', ' ') ?></p>
                    <p class="text-xs text-gray-500"><?= $product['orders_count'] ?> cmd</p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Stats par cluster -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Par cluster commercial</h3>
        
        <?php if (empty($clusterGroups)): ?>
        <p class="text-gray-500 text-center py-8">Aucune donnée pour cette période</p>
        <?php else: ?>
        <div class="space-y-3">
            <?php 
            // Trier par quantité décroissante
            arsort($clusterGroups);
            $rank = 1;
            foreach ($clusterGroups as $cluster => $stats): 
            ?>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="w-6 h-6 bg-green-100 text-green-800 rounded-full flex items-center justify-center text-sm font-medium">
                        <?= $rank++ ?>
                    </span>
                    <p class="font-medium text-gray-900"><?= htmlspecialchars($cluster) ?></p>
                </div>
                <div class="text-right">
                    <p class="font-bold text-gray-900"><?= number_format($stats['quantity'], 0, ',', ' ') ?></p>
                    <p class="text-xs text-gray-500"><?= $stats['customers'] ?> clients</p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();

// Préparer les données JSON pour les graphiques
$chartLabelsJson = json_encode($chartLabels);
$chartOrdersJson = json_encode($chartOrders);
$chartQuantityJson = json_encode($chartQuantity);
$countryBEQty = $kpis['quantity_by_country']['BE'] ?? 0;
$countryLUQty = $kpis['quantity_by_country']['LU'] ?? 0;

// Scripts pour les graphiques
$pageScripts = "
// Données pour les graphiques
const chartLabels = {$chartLabelsJson};
const chartOrders = {$chartOrdersJson};
const chartQuantity = {$chartQuantityJson};
const countryBE = {$countryBEQty};
const countryLU = {$countryLUQty};

// Graphique évolution
const ctxEvolution = document.getElementById('evolutionChart').getContext('2d');
new Chart(ctxEvolution, {
    type: 'line',
    data: {
        labels: chartLabels,
        datasets: [{
            label: 'Commandes',
            data: chartOrders,
            borderColor: '#6366F1',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            fill: true,
            tension: 0.3
        }, {
            label: 'Quantités',
            data: chartQuantity,
            borderColor: '#F59E0B',
            backgroundColor: 'rgba(245, 158, 11, 0.1)',
            fill: true,
            tension: 0.3,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        scales: {
            y: { type: 'linear', display: true, position: 'left', title: { display: true, text: 'Commandes' } },
            y1: { type: 'linear', display: true, position: 'right', title: { display: true, text: 'Quantités' }, grid: { drawOnChartArea: false } }
        }
    }
});

// Graphique répartition pays
const ctxCountry = document.getElementById('countryChart').getContext('2d');
new Chart(ctxCountry, {
    type: 'doughnut',
    data: {
        labels: ['Belgique', 'Luxembourg'],
        datasets: [{ data: [countryBE, countryLU], backgroundColor: ['#3B82F6', '#F59E0B'], borderWidth: 0 }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});
";

require __DIR__ . '/../../layouts/admin.php';
?>
