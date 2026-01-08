<?php
/**
 * Vue : Statistiques - Vue globale
 *
 * Dashboard statistiques avec KPIs, graphiques et filtres
 * Filtres dynamiques : Pays → Campagne (Alpine.js)
 *
 * @package STM
 * @created 2025/11/25
 * @modified 2025/11/26 - Correction scripts + filtres dynamiques
 * @modified 2025/12/17 - Ajout filtrage automatique pays selon rôle
 * @modified 2026/01/08 - Ajout KPI et graphiques origine (client vs rep)
 */

use App\Helpers\StatsAccessHelper;

ob_start();

// Récupérer les pays accessibles selon le rôle
$accessibleCountries = StatsAccessHelper::getAccessibleCountries();
$defaultCountry = StatsAccessHelper::getDefaultCountry();
$hasFullAccess = StatsAccessHelper::hasFullAccess();

// Si un seul pays accessible, forcer ce pays
if ($accessibleCountries !== null && count($accessibleCountries) === 1) {
    $country = $accessibleCountries[0];
}

// Préparer les campagnes par pays pour le filtre dynamique
$campaignsByCountry = [
    "BE" => [],
    "LU" => [],
    "all" => [],
];

foreach ($campaigns as $c) {
    $campaignsByCountry["all"][] = $c;
    if (isset($c["country"])) {
        $campaignsByCountry[$c["country"]][] = $c;
    }
}
$campaignsJson = json_encode($campaignsByCountry);
?>

<!-- En-tête -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Statistiques - Vue globale</h1>
    <p class="text-gray-600 mt-1">Aperçu des performances sur la période sélectionnée</p>
</div>

<!-- Filtres avec Alpine.js pour dynamisme -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-6" x-data="statsFilters()">
    <form method="GET" action="/stm/admin/stats" class="flex flex-wrap gap-4 items-end">

        <!-- Période -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Période</label>
            <select name="period" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <option value="7" <?= ($period ?? "7") === "7" ? "selected" : "" ?>>7 derniers jours</option>
                <option value="14" <?= ($period ?? "7") === "14" ? "selected" : "" ?>>14 derniers jours</option>
                <option value="30" <?= ($period ?? "7") === "30" ? "selected" : "" ?>>30 derniers jours</option>
                <option value="month" <?= ($period ?? "7") === "month" ? "selected" : "" ?>>Ce mois</option>
            </select>
        </div>

        <!-- Pays (filtre principal) - Masqué si un seul pays accessible -->
        <?php if ($accessibleCountries === null || count($accessibleCountries) > 1): ?>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Pays</label>
            <select name="country" x-model="selectedCountry" @change="filterCampaigns()"
                    class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <?php if ($accessibleCountries === null): ?>
                <option value="">Tous</option>
                <option value="BE" <?= ($country ?? "") === "BE" ? "selected" : "" ?>>🇧🇪 Belgique</option>
                <option value="LU" <?= ($country ?? "") === "LU" ? "selected" : "" ?>>🇱🇺 Luxembourg</option>
                <?php else: ?>
                <option value="">Tous</option>
                <?php if (in_array("BE", $accessibleCountries)): ?>
                <option value="BE" <?= ($country ?? "") === "BE" ? "selected" : "" ?>>🇧🇪 Belgique</option>
                <?php endif; ?>
                <?php if (in_array("LU", $accessibleCountries)): ?>
                <option value="LU" <?= ($country ?? "") === "LU" ? "selected" : "" ?>>🇱🇺 Luxembourg</option>
                <?php endif; ?>
                <?php endif; ?>
            </select>
        </div>
        <?php else: ?>
        <!-- Pays unique - Champ caché -->
        <input type="hidden" name="country" value="<?= $country ?>">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Pays</label>
            <div class="px-4 py-2 bg-gray-100 rounded-lg text-gray-700">
                <?= $country === "BE" ? "🇧🇪 Belgique" : "🇱🇺 Luxembourg" ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Campagne (filtrée par pays) -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Campagne</label>
            <select name="campaign_id" x-model="selectedCampaign"
                    class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent min-w-[200px]">
                <option value="">Toutes les campagnes</option>
                <template x-for="c in filteredCampaigns" :key="c.id">
                    <option :value="c.id" x-text="c.name + ' (' + c.country + ')'"></option>
                </template>
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
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-gray-500">Commandes</p>
            <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                <i class="fas fa-shopping-cart text-indigo-600 text-lg"></i>
            </div>
        </div>
        <p class="text-3xl font-bold text-gray-900 mb-2"><?= number_format(
            $kpis["total_orders"] ?? 0,
            0,
            ",",
            " ",
        ) ?></p>
        <?php if (empty($country)): ?>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-2 py-0.5 bg-blue-100 text-blue-800 rounded text-xs font-medium">
                🇧🇪 <?= number_format($kpis["orders_by_country"]["BE"] ?? 0, 0, ",", " ") ?>
            </span>
            <span class="inline-flex items-center px-2 py-0.5 bg-yellow-100 text-yellow-800 rounded text-xs font-medium">
                🇱🇺 <?= number_format($kpis["orders_by_country"]["LU"] ?? 0, 0, ",", " ") ?>
            </span>
        </div>
        <?php else: ?>
        <p class="text-xs text-gray-400"><?= $country === "BE" ? "🇧🇪 Belgique" : "🇱🇺 Luxembourg" ?></p>
        <?php endif; ?>
    </div>

    <!-- Clients uniques -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-gray-500">Clients</p>
            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-users text-green-600 text-lg"></i>
            </div>
        </div>
        <p class="text-3xl font-bold text-gray-900 mb-2"><?= number_format(
            $kpis["unique_customers"] ?? 0,
            0,
            ",",
            " ",
        ) ?></p>
        <?php if (empty($country)): ?>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-2 py-0.5 bg-blue-100 text-blue-800 rounded text-xs font-medium">
                🇧🇪 <?= number_format($kpis["customers_by_country"]["BE"] ?? 0, 0, ",", " ") ?>
            </span>
            <span class="inline-flex items-center px-2 py-0.5 bg-yellow-100 text-yellow-800 rounded text-xs font-medium">
                🇱🇺 <?= number_format($kpis["customers_by_country"]["LU"] ?? 0, 0, ",", " ") ?>
            </span>
        </div>
        <?php else: ?>
        <p class="text-xs text-gray-400"><?= $country === "BE" ? "🇧🇪 Belgique" : "🇱🇺 Luxembourg" ?></p>
        <?php endif; ?>
    </div>

    <!-- Total quantités -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-gray-500">Promos vendues</p>
            <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                <i class="fas fa-box text-orange-600 text-lg"></i>
            </div>
        </div>
        <p class="text-3xl font-bold text-gray-900 mb-2"><?= number_format(
            $kpis["total_quantity"] ?? 0,
            0,
            ",",
            " ",
        ) ?></p>
        <?php if (empty($country)): ?>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-2 py-0.5 bg-blue-100 text-blue-800 rounded text-xs font-medium">
                🇧🇪 <?= number_format($kpis["quantity_by_country"]["BE"] ?? 0, 0, ",", " ") ?>
            </span>
            <span class="inline-flex items-center px-2 py-0.5 bg-yellow-100 text-yellow-800 rounded text-xs font-medium">
                🇱🇺 <?= number_format($kpis["quantity_by_country"]["LU"] ?? 0, 0, ",", " ") ?>
            </span>
        </div>
        <?php else: ?>
        <p class="text-xs text-gray-400"><?= $country === "BE" ? "🇧🇪 Belgique" : "🇱🇺 Luxembourg" ?></p>
        <?php endif; ?>
    </div>

    <!-- Origine des commandes -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-gray-500">Origine</p>
            <div class="w-10 h-10 bg-violet-100 rounded-full flex items-center justify-center">
                <i class="fas fa-code-branch text-violet-600 text-lg"></i>
            </div>
        </div>
        <p class="text-3xl font-bold text-gray-900 mb-2"><?= number_format(
            ($originStats["client_orders"] ?? 0) + ($originStats["rep_orders"] ?? 0),
            0,
            ",",
            " ",
        ) ?></p>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-2 py-0.5 bg-blue-100 text-blue-800 rounded text-xs font-medium">
                <i class="fas fa-user mr-1"></i> <?= number_format($originStats["client_orders"] ?? 0, 0, ",", " ") ?>
            </span>
            <span class="inline-flex items-center px-2 py-0.5 bg-violet-100 text-violet-800 rounded text-xs font-medium">
                <i class="fas fa-user-tie mr-1"></i> <?= number_format($originStats["rep_orders"] ?? 0, 0, ",", " ") ?>
            </span>
        </div>
    </div>
</div>

<!-- Graphiques -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    <!-- Évolution quotidienne -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Évolution des commandes</h3>
        <div class="h-64">
            <canvas id="evolutionChart"></canvas>
        </div>
    </div>

    <!-- Répartition par pays - seulement si pas de filtre pays -->
    <?php if (empty($country)): ?>
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Répartition par pays</h3>

        <!-- Stats textuelles -->
        <div class="grid grid-cols-2 gap-4 mb-4">
            <!-- Belgique -->
            <div class="bg-blue-50 rounded-lg p-4 text-center">
                <span class="text-2xl">🇧🇪</span>
                <p class="font-semibold text-blue-900 mt-1">Belgique</p>
                <div class="mt-2 space-y-1">
                    <p class="text-xl font-bold text-blue-700"><?= number_format(
                        $kpis["orders_by_country"]["BE"] ?? 0,
                        0,
                        ",",
                        " ",
                    ) ?></p>
                    <p class="text-xs text-blue-600">commandes</p>
                    <p class="text-lg font-bold text-blue-700"><?= number_format(
                        $kpis["quantity_by_country"]["BE"] ?? 0,
                        0,
                        ",",
                        " ",
                    ) ?></p>
                    <p class="text-xs text-blue-600">promos vendues</p>
                </div>
            </div>
            <!-- Luxembourg -->
            <div class="bg-yellow-50 rounded-lg p-4 text-center">
                <span class="text-2xl">🇱🇺</span>
                <p class="font-semibold text-yellow-900 mt-1">Luxembourg</p>
                <div class="mt-2 space-y-1">
                    <p class="text-xl font-bold text-yellow-700"><?= number_format(
                        $kpis["orders_by_country"]["LU"] ?? 0,
                        0,
                        ",",
                        " ",
                    ) ?></p>
                    <p class="text-xs text-yellow-600">commandes</p>
                    <p class="text-lg font-bold text-yellow-700"><?= number_format(
                        $kpis["quantity_by_country"]["LU"] ?? 0,
                        0,
                        ",",
                        " ",
                    ) ?></p>
                    <p class="text-xs text-yellow-600">promos vendues</p>
                </div>
            </div>
        </div>

        <!-- Graphique donut -->
        <div class="h-32">
            <canvas id="countryChart"></canvas>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Filtre pays actif</h3>
        <div class="h-64 flex items-center justify-center">
            <div class="text-center">
                <div class="w-20 h-20 bg-<?= $country === "BE"
                    ? "blue"
                    : "yellow" ?>-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="text-4xl"><?= $country === "BE" ? "🇧🇪" : "🇱🇺" ?></span>
                </div>
                <p class="text-xl font-bold text-gray-900"><?= $country === "BE" ? "Belgique" : "Luxembourg" ?></p>
                <div class="grid grid-cols-2 gap-6 mt-4">
                    <div>
                        <p class="text-3xl font-bold text-indigo-600"><?= number_format(
                            $kpis["total_orders"] ?? 0,
                            0,
                            ",",
                            " ",
                        ) ?></p>
                        <p class="text-gray-500 text-sm">commandes</p>
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-orange-600"><?= number_format(
                            $kpis["total_quantity"] ?? 0,
                            0,
                            ",",
                            " ",
                        ) ?></p>
                        <p class="text-gray-500 text-sm">promos vendues</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Répartition par origine -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Bar chart Commandes par origine -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Commandes par origine</h3>

        <!-- Stats textuelles -->
        <div class="grid grid-cols-2 gap-4 mb-4">
            <!-- Clients -->
            <div class="bg-blue-50 rounded-lg p-4 text-center">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-2">
                    <i class="fas fa-user text-blue-600 text-xl"></i>
                </div>
                <p class="font-semibold text-blue-900">Clients</p>
                <div class="mt-2 space-y-1">
                    <p class="text-xl font-bold text-blue-700"><?= number_format($originStats["client_orders"] ?? 0, 0, ",", " ") ?></p>
                    <p class="text-xs text-blue-600">commandes</p>
                    <p class="text-lg font-bold text-blue-700"><?= number_format($originStats["client_quantity"] ?? 0, 0, ",", " ") ?></p>
                    <p class="text-xs text-blue-600">promos vendues</p>
                </div>
            </div>
            <!-- Représentants -->
            <div class="bg-violet-50 rounded-lg p-4 text-center">
                <div class="w-12 h-12 bg-violet-100 rounded-full flex items-center justify-center mx-auto mb-2">
                    <i class="fas fa-user-tie text-violet-600 text-xl"></i>
                </div>
                <p class="font-semibold text-violet-900">Représentants</p>
                <div class="mt-2 space-y-1">
                    <p class="text-xl font-bold text-violet-700"><?= number_format($originStats["rep_orders"] ?? 0, 0, ",", " ") ?></p>
                    <p class="text-xs text-violet-600">commandes</p>
                    <p class="text-lg font-bold text-violet-700"><?= number_format($originStats["rep_quantity"] ?? 0, 0, ",", " ") ?></p>
                    <p class="text-xs text-violet-600">promos vendues</p>
                </div>
            </div>
        </div>

        <!-- Graphique bar -->
        <div class="h-40">
            <canvas id="originChart"></canvas>
        </div>
    </div>

    <!-- Pourcentages origine -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Analyse origine</h3>

        <?php
        $totalOrders = ($originStats["client_orders"] ?? 0) + ($originStats["rep_orders"] ?? 0);
        $totalQty = ($originStats["client_quantity"] ?? 0) + ($originStats["rep_quantity"] ?? 0);
        $clientOrdersPct = $totalOrders > 0 ? round(($originStats["client_orders"] ?? 0) / $totalOrders * 100) : 0;
        $repOrdersPct = $totalOrders > 0 ? round(($originStats["rep_orders"] ?? 0) / $totalOrders * 100) : 0;
        $clientQtyPct = $totalQty > 0 ? round(($originStats["client_quantity"] ?? 0) / $totalQty * 100) : 0;
        $repQtyPct = $totalQty > 0 ? round(($originStats["rep_quantity"] ?? 0) / $totalQty * 100) : 0;
        ?>

        <!-- Barres de progression -->
        <div class="space-y-6">
            <!-- Commandes -->
            <div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-medium text-gray-700">Commandes</span>
                    <span class="text-sm text-gray-500"><?= $totalOrders ?> total</span>
                </div>
                <div class="flex h-6 rounded-full overflow-hidden bg-gray-100">
                    <?php if ($clientOrdersPct > 0): ?>
                    <div class="bg-blue-500 flex items-center justify-center text-white text-xs font-medium" style="width: <?= $clientOrdersPct ?>%">
                        <?= $clientOrdersPct ?>%
                    </div>
                    <?php endif; ?>
                    <?php if ($repOrdersPct > 0): ?>
                    <div class="bg-violet-500 flex items-center justify-center text-white text-xs font-medium" style="width: <?= $repOrdersPct ?>%">
                        <?= $repOrdersPct ?>%
                    </div>
                    <?php endif; ?>
                </div>
                <div class="flex justify-between mt-1 text-xs text-gray-500">
                    <span><i class="fas fa-user text-blue-500 mr-1"></i> Clients: <?= $originStats["client_orders"] ?? 0 ?></span>
                    <span><i class="fas fa-user-tie text-violet-500 mr-1"></i> Reps: <?= $originStats["rep_orders"] ?? 0 ?></span>
                </div>
            </div>

            <!-- Quantités -->
            <div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-medium text-gray-700">Promos vendues</span>
                    <span class="text-sm text-gray-500"><?= $totalQty ?> total</span>
                </div>
                <div class="flex h-6 rounded-full overflow-hidden bg-gray-100">
                    <?php if ($clientQtyPct > 0): ?>
                    <div class="bg-blue-500 flex items-center justify-center text-white text-xs font-medium" style="width: <?= $clientQtyPct ?>%">
                        <?= $clientQtyPct ?>%
                    </div>
                    <?php endif; ?>
                    <?php if ($repQtyPct > 0): ?>
                    <div class="bg-violet-500 flex items-center justify-center text-white text-xs font-medium" style="width: <?= $repQtyPct ?>%">
                        <?= $repQtyPct ?>%
                    </div>
                    <?php endif; ?>
                </div>
                <div class="flex justify-between mt-1 text-xs text-gray-500">
                    <span><i class="fas fa-user text-blue-500 mr-1"></i> Clients: <?= $originStats["client_quantity"] ?? 0 ?></span>
                    <span><i class="fas fa-user-tie text-violet-500 mr-1"></i> Reps: <?= $originStats["rep_quantity"] ?? 0 ?></span>
                </div>
            </div>
        </div>

        <!-- Légende -->
        <div class="flex justify-center gap-6 mt-6 pt-4 border-t border-gray-200">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                <span class="text-sm text-gray-600">Clients</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-violet-500"></span>
                <span class="text-sm text-gray-600">Représentants</span>
            </div>
        </div>
    </div>
</div>

<!-- Top produits et Stats par cluster -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- Top 10 produits -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Top 10 produits</h3>

        <?php if (empty($topProducts)): ?>
        <p class="text-gray-500 text-center py-8">Aucune donnée pour cette période</p>
        <?php // Pas de filtre campagne → afficher campagne
            // Pas de filtre pays → afficher pays
            // Pas de filtre campagne → afficher campagne
            // Pas de filtre pays → afficher pays
            else: ?>

        <!-- En-tête -->
        <div class="flex items-center justify-between text-xs text-gray-500 uppercase tracking-wider pb-2 border-b border-gray-200 mb-2">
            <span>Produit</span>
            <div class="flex gap-4">
                <span class="w-16 text-center">Cmd</span>
                <span class="w-16 text-center">Promos</span>
            </div>
        </div>

        <div class="space-y-2">
            <?php foreach ($topProducts as $i => $product): ?>
            <div class="flex items-center justify-between py-2 <?= $i > 0 ? "border-t border-gray-50" : "" ?>">
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    <span class="w-6 h-6 bg-indigo-100 text-indigo-800 rounded-full flex items-center justify-center text-sm font-medium flex-shrink-0">
                        <?= $i + 1 ?>
                    </span>
                    <div class="min-w-0">
                        <p class="font-medium text-gray-900 text-sm truncate"><?= htmlspecialchars(
                            $product["product_name"] ?? "",
                        ) ?></p>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-xs text-gray-500"><?= htmlspecialchars(
                                $product["product_code"] ?? "",
                            ) ?></span>
                            <?php if (!$campaignId): ?>
                            <span class="text-xs px-1.5 py-0.5 bg-gray-100 text-gray-600 rounded">
                                <?= htmlspecialchars($product["campaign_name"] ?? "") ?>
                            </span>
                            <?php endif; ?>
                            <?php if (!$country): ?>
                            <span class="text-xs px-1.5 py-0.5 <?= ($product["campaign_country"] ?? "") === "BE"
                                ? "bg-blue-100 text-blue-700"
                                : "bg-yellow-100 text-yellow-700" ?> rounded">
                                <?= ($product["campaign_country"] ?? "") === "BE" ? "🇧🇪" : "🇱🇺" ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="flex gap-4 flex-shrink-0 ml-2">
                    <div class="w-16 text-center">
                        <p class="font-semibold text-indigo-600"><?= $product["orders_count"] ?? 0 ?></p>
                    </div>
                    <div class="w-16 text-center">
                        <p class="font-bold text-orange-600"><?= number_format(
                            $product["total_quantity"] ?? 0,
                            0,
                            ",",
                            " ",
                        ) ?></p>
                    </div>
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
        <div class="text-center py-8">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-users text-gray-400 text-2xl"></i>
            </div>
            <p class="text-gray-500">Aucune donnée cluster pour cette période</p>
            <p class="text-xs text-gray-400 mt-2">Les stats par cluster nécessitent la connexion à la DB externe</p>
        </div>
        <?php
            // Trier par quantité décroissante
            // Trier par quantité décroissante
            else: ?>

        <!-- En-tête -->
        <div class="flex items-center justify-between text-xs text-gray-500 uppercase tracking-wider pb-2 border-b border-gray-200 mb-2">
            <span>Cluster</span>
            <div class="flex gap-4">
                <span class="w-16 text-center">Cmd</span>
                <span class="w-16 text-center">Promos</span>
            </div>
        </div>

        <div class="space-y-2">
            <?php
            uasort($clusterGroups, function ($a, $b) {
                return ($b["quantity"] ?? 0) - ($a["quantity"] ?? 0);
            });
            $rank = 1;
            foreach ($clusterGroups as $cluster => $stats): ?>
            <div class="flex items-center justify-between py-2 <?= $rank > 1 ? "border-t border-gray-50" : "" ?>">
                <div class="flex items-center gap-3">
                    <span class="w-6 h-6 bg-green-100 text-green-800 rounded-full flex items-center justify-center text-sm font-medium">
                        <?= $rank++ ?>
                    </span>
                    <div>
                        <p class="font-medium text-gray-900"><?= htmlspecialchars($cluster ?: "Non défini") ?></p>
                        <p class="text-xs text-gray-500"><?= $stats["customers"] ?? 0 ?> clients</p>
                    </div>
                </div>
                <div class="flex gap-4">
                    <div class="w-16 text-center">
                        <p class="font-semibold text-indigo-600"><?= $stats["orders"] ?? 0 ?></p>
                    </div>
                    <div class="w-16 text-center">
                        <p class="font-bold text-orange-600"><?= number_format(
                            $stats["quantity"] ?? 0,
                            0,
                            ",",
                            " ",
                        ) ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach;
            ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();

// Préparer les données JSON pour les graphiques
$chartLabelsJson = json_encode($chartLabels ?? []);
$chartOrdersJson = json_encode($chartOrders ?? []);
$chartQuantityJson = json_encode($chartQuantity ?? []);
$countryBEQty = $kpis["quantity_by_country"]["BE"] ?? 0;
$countryLUQty = $kpis["quantity_by_country"]["LU"] ?? 0;
$showCountryChart = empty($country) ? "true" : "false";

// Données origine pour le graphique
$originClientOrders = $originStats["client_orders"] ?? 0;
$originRepOrders = $originStats["rep_orders"] ?? 0;
$originClientQty = $originStats["client_quantity"] ?? 0;
$originRepQty = $originStats["rep_quantity"] ?? 0;

// Valeurs actuelles pour les filtres
$currentCountry = $country ?? "";
$currentCampaignId = $campaignId ?? "";

// Scripts pour les graphiques ET les filtres dynamiques
$pageScripts = <<<SCRIPTS
<script>
// ========================================
// FILTRES DYNAMIQUES (Alpine.js)
// ========================================
function statsFilters() {
    return {
        selectedCountry: '{$currentCountry}',
        selectedCampaign: '{$currentCampaignId}',
        allCampaigns: {$campaignsJson},
        filteredCampaigns: [],

        init() {
            this.filterCampaigns();
            // Restaurer la sélection de campagne après le filtrage
            this.\$nextTick(() => {
                this.selectedCampaign = '{$currentCampaignId}';
            });
        },

        filterCampaigns() {
            if (this.selectedCountry === '') {
                this.filteredCampaigns = this.allCampaigns.all || [];
            } else {
                this.filteredCampaigns = this.allCampaigns[this.selectedCountry] || [];
            }
            // Réinitialiser la campagne sélectionnée si elle n'est plus dans la liste
            const ids = this.filteredCampaigns.map(c => String(c.id));
            if (!ids.includes(String(this.selectedCampaign))) {
                this.selectedCampaign = '';
            }
        }
    }
}

// ========================================
// GRAPHIQUES (Chart.js)
// ========================================
document.addEventListener('DOMContentLoaded', function() {

    // Données
    const chartLabels = {$chartLabelsJson};
    const chartOrders = {$chartOrdersJson};
    const chartQuantity = {$chartQuantityJson};
    const countryBE = {$countryBEQty};
    const countryLU = {$countryLUQty};

    // Graphique évolution des commandes
    const evolutionCanvas = document.getElementById('evolutionChart');
    if (evolutionCanvas && chartLabels.length > 0) {
        const ctxEvolution = evolutionCanvas.getContext('2d');
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
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: { display: true, text: 'Commandes' },
                        beginAtZero: true
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: { display: true, text: 'Quantités' },
                        grid: { drawOnChartArea: false },
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: { position: 'top' }
                }
            }
        });
    }

    // Graphique répartition par pays (seulement si pas de filtre pays)
    const countryCanvas = document.getElementById('countryChart');
    if (countryCanvas && {$showCountryChart}) {
        const ctxCountry = countryCanvas.getContext('2d');
        new Chart(ctxCountry, {
            type: 'doughnut',
            data: {
                labels: ['🇧🇪 Belgique', '🇱🇺 Luxembourg'],
                datasets: [{
                    data: [countryBE, countryLU],
                    backgroundColor: ['#3B82F6', '#F59E0B'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    // Graphique répartition par origine (Client vs Rep)
    const originCanvas = document.getElementById('originChart');
    if (originCanvas) {
        const ctxOrigin = originCanvas.getContext('2d');
        new Chart(ctxOrigin, {
            type: 'bar',
            data: {
                labels: ['Commandes', 'Promos vendues'],
                datasets: [{
                    label: 'Clients',
                    data: [{$originClientOrders}, {$originClientQty}],
                    backgroundColor: '#3B82F6',
                    borderRadius: 4
                }, {
                    label: 'Représentants',
                    data: [{$originRepOrders}, {$originRepQty}],
                    backgroundColor: '#8B5CF6',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                },
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }
});
</script>
SCRIPTS;

$title = "Statistiques - Vue globale";
require __DIR__ . "/../../layouts/admin.php";

?>