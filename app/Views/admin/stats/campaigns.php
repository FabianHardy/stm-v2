<?php
/**
 * Vue : Statistiques - Par campagne
 *
 * Stats détaillées pour une campagne spécifique
 * Avec graphiques, loader et vue détail représentant
 *
 * @package STM
 * @created 2025/11/25
 * @modified 2025/12/04 - Ajout loader + graphiques Chart.js
 * @modified 2025/12/08 - Légende colonnes représentants
 * @modified 2025/12/09 - Ajout section statistiques fournisseurs
 */

// Variable pour le menu actif
$activeMenu = "stats-campaigns";

ob_start();

// Préparer les campagnes par pays pour Alpine.js
$campaignsByCountry = ["BE" => [], "LU" => [], "all" => []];
foreach ($campaigns as $c) {
    $campaignsByCountry[$c["country"]][] = $c;
    $campaignsByCountry["all"][] = $c;
}
$campaignsJson = json_encode($campaignsByCountry);

// Récupérer le pays sélectionné
$selectedCountry = $_GET["country"] ?? "";
if (!$selectedCountry && $campaignStats) {
    $selectedCountry = $campaignStats["campaign"]["country"] ?? "";
}
?>

<!-- Loader Overlay -->
<div id="page-loader" class="fixed inset-0 bg-white bg-opacity-90 z-50 hidden items-center justify-center">
    <div class="text-center">
        <div class="relative">
            <div class="w-16 h-16 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin mx-auto"></div>
        </div>
        <p class="mt-4 text-gray-600 font-medium">Chargement des statistiques...</p>
        <p class="text-sm text-gray-400 mt-1">Cela peut prendre quelques secondes</p>
    </div>
</div>

<!-- En-tête -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Statistiques - Par campagne</h1>
    <p class="text-gray-600 mt-1">Performances détaillées d'une campagne</p>
</div>

<!-- Sélecteur de campagne avec filtre pays -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-6" x-data="campaignFilter()" x-init="init()">
    <form method="GET" action="/stm/admin/stats/campaigns" class="flex flex-wrap gap-4 items-end" id="campaign-form">

        <!-- Pays -->
        <div class="w-40">
            <label class="block text-sm font-medium text-gray-700 mb-1">Pays</label>
            <select name="country" x-model="selectedCountry" @change="filterCampaigns()"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <option value="">Tous</option>
                <option value="BE">🇧🇪 Belgique</option>
                <option value="LU">🇱🇺 Luxembourg</option>
            </select>
        </div>

        <!-- Campagne -->
        <div class="flex-1 min-w-[300px]">
            <label class="block text-sm font-medium text-gray-700 mb-1">Campagne</label>
            <select name="campaign_id" x-ref="campaignSelect"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <option value="">-- Choisir une campagne --</option>
                <template x-for="c in filteredCampaigns" :key="c.id">
                    <option :value="c.id"
                            :selected="c.id == selectedCampaign"
                            x-text="c.name + ' (' + c.country + ' - ' + getStatusLabel(c.status) + ')'"></option>
                </template>
            </select>
        </div>

        <button type="submit" onclick="showLoader()" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition inline-flex items-center gap-2">
            <i class="fas fa-chart-bar"></i>
            <span>Voir les stats</span>
        </button>
    </form>
</div>

<?php if ($campaignStats): ?>

<?php if ($repDetail): ?>
<!-- ============================================ -->
<!-- VUE DÉTAIL REPRÉSENTANT                      -->
<!-- ============================================ -->

<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
                <i class="fas fa-user text-indigo-600 text-xl"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($repDetail["name"]) ?></h2>
                <p class="text-sm text-gray-500">
                    <span class="inline-flex items-center px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-xs mr-2">
                        <?= htmlspecialchars($repDetail["cluster"]) ?>
                    </span>
                    <span class="inline-flex items-center px-2 py-0.5 <?= $repDetail["country"] === "BE" ? "bg-blue-100 text-blue-700" : "bg-yellow-100 text-yellow-700" ?> rounded text-xs mr-2">
                        <?= $repDetail["country"] === "BE" ? "🇧🇪" : "🇱🇺" ?> <?= $repDetail["country"] ?>
                    </span>
                    <span class="text-gray-400">•</span>
                    <span class="ml-2 text-indigo-600"><?= htmlspecialchars($campaignStats["campaign"]["name"]) ?></span>
                </p>
            </div>
        </div>

        <?php
        $backUrl = "/stm/admin/stats/campaigns?campaign_id=" . $campaignId;
        if (!empty($selectedCountry)) {
            $backUrl .= "&country=" . $selectedCountry;
        }
        ?>
        <a href="<?= $backUrl ?>" onclick="showLoader()" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
            <i class="fas fa-arrow-left mr-2"></i> Retour à la campagne
        </a>
    </div>

    <!-- Stats du rep -->
    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-gray-50 rounded-lg p-4 text-center">
            <p class="text-2xl font-bold text-gray-900"><?= $repDetail["total_clients"] ?></p>
            <p class="text-xs text-gray-500">Total clients</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-4 text-center">
            <p class="text-2xl font-bold text-green-600"><?= $repDetail["stats"]["customers_ordered"] ?></p>
            <p class="text-xs text-gray-500">Ont commandé</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-4 text-center">
            <?php $convRate = $repDetail["total_clients"] > 0 ? round(($repDetail["stats"]["customers_ordered"] / $repDetail["total_clients"]) * 100, 1) : 0; ?>
            <p class="text-2xl font-bold text-indigo-600"><?= $convRate ?>%</p>
            <p class="text-xs text-gray-500">Taux participation</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-4 text-center">
            <p class="text-2xl font-bold text-orange-600"><?= number_format($repDetail["stats"]["total_quantity"], 0, ",", " ") ?></p>
            <p class="text-xs text-gray-500">Promos vendues</p>
        </div>
    </div>

    <!-- Liste des clients du rep -->
    <h3 class="font-semibold text-gray-900 mb-3">
        Clients (<?= count($repClients) ?>)
        <span class="text-sm font-normal text-gray-500">- Triés par quantité commandée</span>
    </h3>

    <?php if (empty($repClients)): ?>
    <p class="text-gray-500 text-center py-8">Aucun client trouvé pour ce représentant</p>
    <?php else: ?>
    <div class="overflow-x-auto max-h-[500px]">
        <table class="min-w-full">
            <thead class="sticky top-0 bg-gray-50">
                <tr class="text-left text-xs text-gray-500 uppercase border-b">
                    <th class="py-3 px-4">Client</th>
                    <th class="py-3 px-4">Ville</th>
                    <th class="py-3 px-4 text-center">Statut</th>
                    <th class="py-3 px-4 text-right">Commandes</th>
                    <th class="py-3 px-4 text-right">Promos</th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-gray-100">
                <?php usort($repClients, function ($a, $b) {
                    return ($b["total_quantity"] ?? 0) - ($a["total_quantity"] ?? 0);
                }); ?>
                <?php foreach ($repClients as $client): ?>
                <tr class="hover:bg-gray-50">
                    <td class="py-3 px-4">
                        <p class="font-medium text-gray-900"><?= htmlspecialchars($client["company_name"] ?? "-") ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars($client["customer_number"] ?? "") ?></p>
                    </td>
                    <td class="py-3 px-4 text-gray-600"><?= htmlspecialchars($client["city"] ?? "-") ?></td>
                    <td class="py-3 px-4 text-center">
                        <?php if ($client["has_ordered"] ?? false): ?>
                            <span class="inline-flex items-center px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs">
                                <i class="fas fa-check mr-1"></i> Commandé
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2 py-0.5 bg-gray-100 text-gray-500 rounded text-xs">
                                Pas encore
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="py-3 px-4 text-right font-medium"><?= $client["orders_count"] ?? 0 ?></td>
                    <td class="py-3 px-4 text-right font-bold text-orange-600"><?= number_format($client["total_quantity"] ?? 0, 0, ",", " ") ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php else: ?>
<!-- ============================================ -->
<!-- VUE CAMPAGNE (sans détail rep)               -->
<!-- ============================================ -->

<?php
// Statuts
$statusLabels = [
    "draft" => "Brouillon",
    "scheduled" => "Programmée",
    "active" => "En cours",
    "ended" => "Terminée",
    "cancelled" => "Annulée",
];
$statusColors = [
    "draft" => "bg-gray-100 text-gray-800",
    "scheduled" => "bg-blue-100 text-blue-800",
    "active" => "bg-green-100 text-green-800",
    "ended" => "bg-orange-100 text-orange-800",
    "cancelled" => "bg-red-100 text-red-800",
];
$currentStatus = $campaignStats["campaign"]["status"] ?? "draft";
$statusLabel = $statusLabels[$currentStatus] ?? ucfirst($currentStatus);
$statusColor = $statusColors[$currentStatus] ?? "bg-gray-100 text-gray-800";
?>

<!-- Infos campagne -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <div class="flex items-start justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($campaignStats["campaign"]["name"]) ?></h2>
            <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($campaignStats["campaign"]["title_fr"] ?? "") ?></p>
            <div class="flex items-center gap-3 mt-2">
                <span class="inline-flex items-center px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs">
                    <i class="fas fa-calendar mr-1"></i>
                    <?= date("d/m/Y", strtotime($campaignStats["campaign"]["start_date"])) ?> - <?= date("d/m/Y", strtotime($campaignStats["campaign"]["end_date"])) ?>
                </span>
                <span class="inline-flex items-center px-2 py-1 <?= $campaignStats["campaign"]["country"] === "BE" ? "bg-blue-100 text-blue-700" : "bg-yellow-100 text-yellow-700" ?> rounded text-xs">
                    <?= $campaignStats["campaign"]["country"] === "BE" ? "🇧🇪 Belgique" : "🇱🇺 Luxembourg" ?>
                </span>
                <span class="inline-flex items-center px-2 py-1 <?= $statusColor ?> rounded text-xs">
                    <?= $statusLabel ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- KPIs -->
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Clients éligibles</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">
            <?= is_numeric($campaignStats["eligible_customers"])
                ? number_format($campaignStats["eligible_customers"], 0, ",", " ")
                : ($campaignStats["eligible_customers"] ?? 0) ?>
        </p>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Clients ayant commandé</p>
        <p class="text-2xl font-bold text-green-600 mt-1"><?= number_format($campaignStats["customers_ordered"] ?? 0, 0, ",", " ") ?></p>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Taux de participation</p>
        <p class="text-2xl font-bold text-indigo-600 mt-1"><?= $campaignStats["participation_rate"] ?? 0 ?>%</p>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Total commandes</p>
        <p class="text-2xl font-bold text-gray-900 mt-1"><?= number_format($campaignStats["total_orders"] ?? 0, 0, ",", " ") ?></p>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Promos vendues</p>
        <p class="text-2xl font-bold text-orange-600 mt-1"><?= number_format($campaignStats["total_quantity"] ?? 0, 0, ",", " ") ?></p>
    </div>
</div>

<!-- Graphiques -->
<?php if (!empty($chartLabels)): ?>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    <!-- Évolution des ventes -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-chart-line text-indigo-500 mr-2"></i>
            Évolution des ventes
        </h3>
        <div class="h-64">
            <canvas id="evolutionChart"></canvas>
        </div>
    </div>

    <!-- Répartition par catégorie -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-chart-pie text-purple-500 mr-2"></i>
            Ventes par catégorie
        </h3>
        <?php if (!empty($categoryData)): ?>
        <div class="h-64 flex items-center justify-center">
            <canvas id="categoryChart"></canvas>
        </div>
        <?php else: ?>
        <div class="h-64 flex items-center justify-center text-gray-400">
            <p>Aucune donnée de catégorie disponible</p>
        </div>
        <?php endif; ?>
    </div>

</div>
<?php endif; ?>

<!-- Produits de la campagne -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
        <i class="fas fa-box text-green-500 mr-2"></i>
        Produits de la campagne
    </h3>

    <?php if (empty($campaignProducts)): ?>
    <p class="text-gray-500 text-center py-8">Aucun produit dans cette campagne</p>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr class="text-left text-xs text-gray-500 uppercase">
                    <th class="py-3 px-4">#</th>
                    <th class="py-3 px-4">Produit</th>
                    <th class="py-3 px-4 text-right">CMD</th>
                    <th class="py-3 px-4 text-right">Promos</th>
                    <th class="py-3 px-4 w-32"></th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-gray-100">
                <?php
                $rank = 0;
                $qtys = array_filter(array_map(function($p) {
                    return $p["quantity_sold"] ?? 0;
                }, $campaignProducts));
                $maxQty = !empty($qtys) ? max($qtys) : 1;

                foreach ($campaignProducts as $product):
                    $rank++;
                    $productName = $product["product_name"] ?? "-";
                    $productCode = $product["product_code"] ?? "-";
                    $ordersCount = $product["orders_count"] ?? 0;
                    $totalQty = $product["quantity_sold"] ?? 0;
                    $percent = $maxQty > 0 ? ($totalQty / $maxQty) * 100 : 0;
                ?>
                <tr class="hover:bg-gray-50">
                    <td class="py-3 px-4">
                        <span class="inline-flex items-center justify-center w-6 h-6 <?= $rank <= 3 ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-500' ?> rounded-full text-xs font-medium">
                            <?= $rank ?>
                        </span>
                    </td>
                    <td class="py-3 px-4">
                        <p class="font-medium text-gray-900"><?= htmlspecialchars($productName) ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars($productCode) ?></p>
                    </td>
                    <td class="py-3 px-4 text-right font-medium"><?= $ordersCount ?></td>
                    <td class="py-3 px-4 text-right font-bold text-orange-600"><?= number_format($totalQty, 0, ",", " ") ?></td>
                    <td class="py-3 px-4">
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-indigo-500 h-2 rounded-full" style="width: <?= $percent ?>%"></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Performance par représentant -->
<?php if (!empty($reps)): ?>
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
        <i class="fas fa-users text-blue-500 mr-2"></i>
        Performance par représentant
    </h3>

    <?php
    // Grouper par cluster
    $clusters = [];
    foreach ($reps as $rep) {
        // Ignorer les reps sans clients
        if (($rep["total_clients"] ?? 0) == 0) {
            continue;
        }
        $clusterName = $rep["cluster"] ?? "Sans cluster";
        if (!isset($clusters[$clusterName])) {
            $clusters[$clusterName] = [
                "reps" => [],
                "totals" => ["clients" => 0, "ordered" => 0, "quantity" => 0]
            ];
        }
        $clusters[$clusterName]["reps"][] = $rep;
        $clusters[$clusterName]["totals"]["clients"] += $rep["total_clients"] ?? 0;
        $clusters[$clusterName]["totals"]["ordered"] += $rep["stats"]["customers_ordered"] ?? 0;
        $clusters[$clusterName]["totals"]["quantity"] += $rep["stats"]["total_quantity"] ?? 0;
    }

    // Supprimer les clusters vides
    $clusters = array_filter($clusters, function($c) {
        return !empty($c["reps"]);
    });

    // Trier par quantité
    uasort($clusters, function($a, $b) {
        return $b["totals"]["quantity"] - $a["totals"]["quantity"];
    });
    ?>

    <?php if (empty($clusters)): ?>
    <div class="text-center py-8">
        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
            <i class="fas fa-users text-gray-400"></i>
        </div>
        <p class="text-gray-500">Aucune donnée représentant</p>
        <p class="text-xs text-gray-400 mt-1">Vérifiez la connexion à la base externe</p>
    </div>
    <?php else: ?>

    <!-- Légende des colonnes (en haut) -->
    <p class="text-xs text-gray-500 mb-4 text-right">
        <span class="inline-flex items-center gap-1">
            <span class="font-medium">Format :</span>
            <span class="text-gray-700">Total clients</span>
            <span class="text-gray-400">/</span>
            <span class="text-green-600">Clients ayant commandé</span>
            <span class="text-gray-400">|</span>
            <span class="text-orange-600">Promos vendues</span>
        </span>
    </p>

    <div x-data="{ openClusters: {} }">
        <?php foreach ($clusters as $clusterName => $clusterData):
            $clusterId = md5($clusterName);
        ?>
        <div class="border border-gray-200 rounded-lg mb-2 overflow-hidden">
            <!-- En-tête cluster -->
            <div class="bg-gray-50 px-4 py-3 cursor-pointer flex items-center justify-between hover:bg-gray-100 transition"
                 @click="openClusters['<?= $clusterId ?>'] = !openClusters['<?= $clusterId ?>']">
                <div class="flex items-center gap-3">
                    <i class="fas fa-chevron-right text-gray-400 text-sm transition-transform duration-200"
                       :class="{ 'rotate-90': openClusters['<?= $clusterId ?>'] }"></i>
                    <div>
                        <span class="font-medium text-gray-900"><?= htmlspecialchars($clusterName) ?></span>
                        <span class="text-xs text-gray-500 ml-2"><?= count($clusterData["reps"]) ?> rep.</span>
                    </div>
                </div>

                <div class="flex items-center gap-4 text-sm">
                    <div class="text-center">
                        <span class="font-bold text-gray-900"><?= $clusterData["totals"]["clients"] ?></span>
                        <span class="text-gray-400">/</span>
                        <span class="font-bold text-green-600"><?= $clusterData["totals"]["ordered"] ?></span>
                    </div>
                    <div class="w-16 text-right">
                        <span class="font-bold text-orange-600"><?= number_format($clusterData["totals"]["quantity"], 0, ",", " ") ?></span>
                    </div>
                </div>
            </div>

            <!-- Liste des représentants -->
            <div x-show="openClusters['<?= $clusterId ?>']"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100">
                <div class="divide-y divide-gray-100">
                    <?php usort($clusterData["reps"], function ($a, $b) {
                        return ($b["stats"]["total_quantity"] ?? 0) - ($a["stats"]["total_quantity"] ?? 0);
                    }); ?>
                    <?php foreach ($clusterData["reps"] as $rep): ?>
                    <?php
                    $repRate = ($rep["total_clients"] ?? 0) > 0
                        ? round((($rep["stats"]["customers_ordered"] ?? 0) / $rep["total_clients"]) * 100, 1)
                        : 0;
                    $repDetailUrl = "/stm/admin/stats/campaigns?campaign_id=" . $campaignId . "&rep_id=" . urlencode($rep["id"]) . "&rep_country=" . $rep["country"];
                    if (!empty($selectedCountry)) {
                        $repDetailUrl .= "&country=" . $selectedCountry;
                    }
                    ?>
                    <div class="px-4 py-2 flex items-center justify-between hover:bg-gray-50">
                        <div class="flex items-center gap-2 pl-6">
                            <div class="w-6 h-6 bg-indigo-100 rounded-full flex items-center justify-center">
                                <span class="text-xs font-medium text-indigo-600"><?= strtoupper(substr($rep["name"] ?? "", 0, 2)) ?></span>
                            </div>
                            <span class="text-sm font-medium text-gray-900"><?= htmlspecialchars($rep["name"] ?? "") ?></span>
                        </div>

                        <div class="flex items-center gap-4 text-sm">
                            <div class="text-center">
                                <span class="text-gray-700"><?= $rep["total_clients"] ?? 0 ?></span>
                                <span class="text-gray-400">/</span>
                                <span class="text-green-600"><?= $rep["stats"]["customers_ordered"] ?? 0 ?></span>
                            </div>
                            <div class="w-16 text-right">
                                <span class="font-bold text-orange-600"><?= number_format($rep["stats"]["total_quantity"] ?? 0, 0, ",", " ") ?></span>
                            </div>
                            <a href="<?= $repDetailUrl ?>" onclick="showLoader()"
                               class="inline-flex items-center px-2 py-1 bg-indigo-50 text-indigo-600 rounded hover:bg-indigo-100 transition text-xs">
                                <i class="fas fa-eye mr-1"></i> Voir
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <p class="text-xs text-gray-400 mt-3 text-center">
        Format: Total clients / Clients ayant commandé | Promos vendues
    </p>

    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ============================================ -->
<!-- STATISTIQUES PAR FOURNISSEUR (NOUVEAU)       -->
<!-- ============================================ -->
<?php if (!empty($supplierStats)): ?>
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
        <i class="fas fa-truck text-orange-500 mr-2"></i>
        Statistiques par fournisseur
    </h3>

    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr class="text-left text-xs text-gray-500 uppercase">
                    <th class="py-3 px-4">#</th>
                    <th class="py-3 px-4">N° Fournisseur</th>
                    <th class="py-3 px-4">Nom</th>
                    <th class="py-3 px-4 text-center">
                        <span title="Clients distincts ayant commandé">Clients</span>
                    </th>
                    <th class="py-3 px-4 text-center">
                        <span title="Nombre de commandes">Commandes</span>
                    </th>
                    <th class="py-3 px-4 text-center">
                        <span title="Nombre de promotions de ce fournisseur">Promos</span>
                    </th>
                    <th class="py-3 px-4 text-right">
                        <span title="Quantité totale vendue">Qté vendue</span>
                    </th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-gray-100">
                <?php
                $rank = 0;
                foreach ($supplierStats as $supplier):
                    $rank++;
                    $isTop3 = $rank <= 3;
                ?>
                <tr class="hover:bg-gray-50 <?= $isTop3 ? 'bg-yellow-50' : '' ?>">
                    <td class="py-3 px-4">
                        <?php if ($isTop3): ?>
                            <span class="text-lg"><?= ['🥇', '🥈', '🥉'][$rank - 1] ?></span>
                        <?php else: ?>
                            <span class="inline-flex items-center justify-center w-6 h-6 bg-gray-100 text-gray-500 rounded-full text-xs font-medium">
                                <?= $rank ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="py-3 px-4 font-mono text-gray-900">
                        <?= htmlspecialchars($supplier['supplier_number']) ?>
                    </td>
                    <td class="py-3 px-4">
                        <p class="font-medium text-gray-900"><?= htmlspecialchars($supplier['supplier_name']) ?></p>
                    </td>
                    <td class="py-3 px-4 text-center">
                        <span class="inline-flex items-center px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs font-medium">
                            <?= number_format($supplier['customers_count'], 0, ',', ' ') ?>
                        </span>
                    </td>
                    <td class="py-3 px-4 text-center">
                        <span class="inline-flex items-center px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs font-medium">
                            <?= number_format($supplier['orders_count'], 0, ',', ' ') ?>
                        </span>
                    </td>
                    <td class="py-3 px-4 text-center">
                        <span class="inline-flex items-center px-2 py-0.5 bg-purple-100 text-purple-700 rounded text-xs font-medium">
                            <?= number_format($supplier['promos_count'], 0, ',', ' ') ?>
                        </span>
                    </td>
                    <td class="py-3 px-4 text-right font-bold text-orange-600">
                        <?= number_format($supplier['total_quantity'], 0, ',', ' ') ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Légende -->
    <div class="mt-4 pt-4 border-t border-gray-100">
        <p class="text-xs text-gray-500">
            <strong>Clients</strong> : Clients distincts ayant commandé un produit de ce fournisseur •
            <strong>Commandes</strong> : Nombre de commandes contenant un produit de ce fournisseur •
            <strong>Promos</strong> : Nombre de promotions de ce fournisseur dans la campagne
        </p>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>
<!-- Fin condition repDetail -->

<?php else: ?>

<!-- Message si pas de campagne sélectionnée -->
<div class="bg-white rounded-lg shadow-sm p-12 text-center">
    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="fas fa-chart-pie text-gray-400 text-2xl"></i>
    </div>
    <h3 class="text-lg font-semibold text-gray-900 mb-2">Sélectionnez une campagne</h3>
    <p class="text-gray-500">Choisissez d'abord un pays, puis une campagne pour voir ses statistiques détaillées.</p>
</div>

<?php endif; ?>

<?php
$content = ob_get_clean();

// Script Alpine.js pour le filtrage pays → campagne
$campaignIdJs = $campaignId ?? "";
$selectedCountryJs = $selectedCountry ?? "";

// Données des graphiques (passées depuis le controller)
$chartLabelsJson = $chartLabelsJson ?? "[]";
$chartOrdersJson = $chartOrdersJson ?? "[]";
$chartQuantityJson = $chartQuantityJson ?? "[]";
$categoryLabelsJson = $categoryLabelsJson ?? "[]";
$categoryDataJson = $categoryDataJson ?? "[]";
$categoryColorsJson = $categoryColorsJson ?? "[]";

$pageScripts = <<<SCRIPTS
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// ============================================
// LOADER
// ============================================
function showLoader() {
    document.getElementById('page-loader').classList.remove('hidden');
    document.getElementById('page-loader').classList.add('flex');
}

// ============================================
// FILTRAGE CAMPAGNES
// ============================================
function campaignFilter() {
    const allCampaigns = {$campaignsJson};
    const currentCampaignId = '{$campaignIdJs}';
    const currentCountry = '{$selectedCountryJs}';

    const statusLabels = {
        'draft': 'Brouillon',
        'scheduled': 'Programmée',
        'active': 'En cours',
        'ended': 'Terminée',
        'cancelled': 'Annulée'
    };

    return {
        selectedCountry: currentCountry,
        selectedCampaign: currentCampaignId,
        filteredCampaigns: [],

        init() {
            this.filterCampaigns();
            this.\$nextTick(() => {
                if (currentCampaignId && this.\$refs.campaignSelect) {
                    this.\$refs.campaignSelect.value = currentCampaignId;
                }
            });
        },

        getStatusLabel(status) {
            return statusLabels[status] || status;
        },

        filterCampaigns() {
            if (this.selectedCountry && this.selectedCountry !== '') {
                this.filteredCampaigns = allCampaigns[this.selectedCountry] || [];
            } else {
                this.filteredCampaigns = allCampaigns['all'] || [];
            }

            const campaignIds = this.filteredCampaigns.map(c => c.id.toString());
            if (this.selectedCampaign && !campaignIds.includes(this.selectedCampaign.toString())) {
                this.selectedCampaign = '';
            }
        }
    }
}

// ============================================
// GRAPHIQUES CHART.JS
// ============================================
document.addEventListener('DOMContentLoaded', function() {

    // Graphique évolution
    const evolutionCtx = document.getElementById('evolutionChart');
    if (evolutionCtx) {
        const chartLabels = {$chartLabelsJson};
        const chartOrders = {$chartOrdersJson};
        const chartQuantity = {$chartQuantityJson};

        if (chartLabels.length > 0) {
            new Chart(evolutionCtx, {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [
                        {
                            label: 'Commandes',
                            data: chartOrders,
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99, 102, 241, 0.1)',
                            fill: true,
                            tension: 0.3,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Promos vendues',
                            data: chartQuantity,
                            borderColor: '#f97316',
                            backgroundColor: 'rgba(249, 115, 22, 0.1)',
                            fill: true,
                            tension: 0.3,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 15
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Commandes'
                            },
                            beginAtZero: true
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Promos'
                            },
                            beginAtZero: true,
                            grid: { drawOnChartArea: false }
                        }
                    }
                }
            });
        }
    }

    // Graphique catégories (Donut)
    const categoryCtx = document.getElementById('categoryChart');
    if (categoryCtx) {
        const categoryLabels = {$categoryLabelsJson};
        const categoryData = {$categoryDataJson};
        const categoryColors = {$categoryColorsJson};

        if (categoryLabels.length > 0) {
            new Chart(categoryCtx, {
                type: 'doughnut',
                data: {
                    labels: categoryLabels,
                    datasets: [{
                        data: categoryData,
                        backgroundColor: categoryColors,
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '60%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 10,
                                font: { size: 11 }
                            }
                        }
                    }
                }
            });
        }
    }
});
</script>
SCRIPTS;

require __DIR__ . "/../../layouts/admin.php";
?>