<?php
/**
 * Vue : Statistiques - Par représentant
 *
 * Vue hiérarchique Cluster → Représentant avec détails
 *
 * @package STM
 * @created 2025/11/25
 * @modified 2025/11/26 - Refonte vue hiérarchique par cluster
 * @modified 2025/12/17 - Ajout filtrage automatique pays selon rôle
 * @modified 2026/01/08 - Ajout colonne % Via Rep (origine commandes)
 */

use App\Helpers\StatsAccessHelper;

// Variable pour le menu actif
$activeMenu = "stats-sales";

// Récupérer les pays accessibles selon le rôle
$accessibleCountries = StatsAccessHelper::getAccessibleCountries();
$defaultCountry = StatsAccessHelper::getDefaultCountry();

// Si un seul pays accessible, forcer ce pays
if ($accessibleCountries !== null && count($accessibleCountries) === 1) {
    $country = $accessibleCountries[0];
}

ob_start();
?>

<!-- En-tête -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Statistiques - Par représentant</h1>
    <p class="text-gray-600 mt-1">Performance par cluster et représentant</p>
</div>

<!-- Filtres -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <form method="GET" action="/stm/admin/stats/sales" class="flex flex-wrap gap-4 items-end">

        <!-- Pays - Masqué si un seul pays accessible -->
        <?php if ($accessibleCountries === null || count($accessibleCountries) > 1): ?>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Pays</label>
            <select name="country" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <?php if ($accessibleCountries === null): ?>
                <option value="">Tous</option>
                <option value="BE" <?php echo ($country ?? "") === "BE" ? "selected" : ""; ?>>🇧🇪 Belgique</option>
                <option value="LU" <?php echo ($country ?? "") === "LU" ? "selected" : ""; ?>>🇱🇺 Luxembourg</option>
                <?php else: ?>
                <option value="">Tous</option>
                <?php if (in_array("BE", $accessibleCountries)): ?>
                <option value="BE" <?php echo ($country ?? "") === "BE" ? "selected" : ""; ?>>🇧🇪 Belgique</option>
                <?php endif; ?>
                <?php if (in_array("LU", $accessibleCountries)): ?>
                <option value="LU" <?php echo ($country ?? "") === "LU" ? "selected" : ""; ?>>🇱🇺 Luxembourg</option>
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

        <!-- Campagne -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Campagne</label>
            <select name="campaign_id" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <option value="">Toutes</option>
                <?php foreach ($campaigns as $c): ?>
                <option value="<?php echo $c["id"]; ?>" <?php echo ($campaignId ?? null) == $c["id"]
    ? "selected"
    : ""; ?>>
                    <?php echo htmlspecialchars($c["name"]); ?> (<?php echo $c["country"]; ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition">
            <i class="fas fa-filter mr-2"></i>Filtrer
        </button>
    </form>
</div>

<?php if ($repDetail): ?>

<!-- Détail d'un représentant -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
                <i class="fas fa-user text-indigo-600 text-xl"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($repDetail["name"]); ?></h2>
                <p class="text-sm text-gray-500">
                    <span class="inline-flex items-center px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-xs mr-2">
                        <?php echo htmlspecialchars($repDetail["cluster"]); ?>
                    </span>
                    <span class="inline-flex items-center px-2 py-0.5 <?php echo $repDetail["country"] === "BE"
                        ? "bg-blue-100 text-blue-700"
                        : "bg-yellow-100 text-yellow-700"; ?> rounded text-xs">
                        <?php echo $repDetail["country"] === "BE" ? "🇧🇪" : "🇱🇺"; ?> <?php echo $repDetail["country"]; ?>
                    </span>
                </p>
            </div>
        </div>

        <?php
        $backUrl = "/stm/admin/stats/sales";
        $backParams = [];
        if (!empty($country)) {
            $backParams[] = "country=" . $country;
        }
        if (!empty($campaignId)) {
            $backParams[] = "campaign_id=" . $campaignId;
        }
        if (!empty($backParams)) {
            $backUrl .= "?" . implode("&", $backParams);
        }
        ?>
        <a href="<?php echo $backUrl; ?>" class="text-gray-500 hover:text-gray-700 p-2">
            <i class="fas fa-arrow-left mr-1"></i> Retour
        </a>
    </div>

    <!-- Stats du rep -->
    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-gray-50 rounded-lg p-4 text-center">
            <p class="text-2xl font-bold text-gray-900"><?php echo $repDetail["total_clients"]; ?></p>
            <p class="text-xs text-gray-500">Total clients</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-4 text-center">
            <p class="text-2xl font-bold text-green-600"><?php echo $repDetail["stats"]["customers_ordered"]; ?></p>
            <p class="text-xs text-gray-500">Ont commandé</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-4 text-center">
            <?php $convRate =
                $repDetail["total_clients"] > 0
                    ? round(($repDetail["stats"]["customers_ordered"] / $repDetail["total_clients"]) * 100, 1)
                    : 0; ?>
            <p class="text-2xl font-bold text-indigo-600"><?php echo $convRate; ?>%</p>
            <p class="text-xs text-gray-500">Taux participation</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-4 text-center">
            <p class="text-2xl font-bold text-orange-600"><?php echo number_format(
                $repDetail["stats"]["total_quantity"],
                0,
                ",",
                " ",
            ); ?></p>
            <p class="text-xs text-gray-500">Promos vendues</p>
        </div>
    </div>

    <!-- Liste des clients du rep -->
    <h3 class="font-semibold text-gray-900 mb-3">
        Clients (<?php echo count($repClients); ?>)
        <span class="text-sm font-normal text-gray-500">- Cliquez sur les en-têtes pour trier</span>
    </h3>

    <?php
    // Préparer les données JSON pour Alpine.js
    $clientsData = [];
    foreach ($repClients as $client) {
        $customerNumber = $client["customer_number"] ?? "";
        $origin = $clientOrigins[$customerNumber] ?? null;
        $clientsData[] = [
            'company_name' => $client["company_name"] ?? "-",
            'customer_number' => $customerNumber,
            'city' => $client["city"] ?? "-",
            'has_ordered' => $client["has_ordered"] ? 1 : 0,
            'orders_count' => (int)($client["orders_count"] ?? 0),
            'total_quantity' => (int)($client["total_quantity"] ?? 0),
            'origin' => $origin
        ];
    }
    ?>

    <div class="overflow-x-auto max-h-[500px]"
         x-data="clientsTable(<?= htmlspecialchars(json_encode($clientsData), ENT_QUOTES) ?>)">
        <table class="min-w-full">
            <thead class="sticky top-0 bg-gray-50 z-10">
                <tr class="text-left text-xs text-gray-500 uppercase border-b">
                    <th class="py-3 px-4 cursor-pointer hover:bg-gray-100 select-none" @click="sortBy('company_name')">
                        <span class="flex items-center gap-1">
                            Client
                            <template x-if="sortColumn === 'company_name'">
                                <i :class="sortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'" class="text-indigo-600"></i>
                            </template>
                        </span>
                    </th>
                    <th class="py-3 px-4 cursor-pointer hover:bg-gray-100 select-none" @click="sortBy('city')">
                        <span class="flex items-center gap-1">
                            Ville
                            <template x-if="sortColumn === 'city'">
                                <i :class="sortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'" class="text-indigo-600"></i>
                            </template>
                        </span>
                    </th>
                    <th class="py-3 px-4 text-center cursor-pointer hover:bg-gray-100 select-none" @click="sortBy('has_ordered')">
                        <span class="flex items-center justify-center gap-1">
                            Statut
                            <template x-if="sortColumn === 'has_ordered'">
                                <i :class="sortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'" class="text-indigo-600"></i>
                            </template>
                        </span>
                    </th>
                    <th class="py-3 px-4 text-right cursor-pointer hover:bg-gray-100 select-none" @click="sortBy('orders_count')">
                        <span class="flex items-center justify-end gap-1">
                            Commandes
                            <template x-if="sortColumn === 'orders_count'">
                                <i :class="sortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'" class="text-indigo-600"></i>
                            </template>
                        </span>
                    </th>
                    <th class="py-3 px-4 text-right cursor-pointer hover:bg-gray-100 select-none" @click="sortBy('total_quantity')">
                        <span class="flex items-center justify-end gap-1">
                            Promos
                            <template x-if="sortColumn === 'total_quantity'">
                                <i :class="sortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'" class="text-indigo-600"></i>
                            </template>
                        </span>
                    </th>
                    <?php if (!empty($campaignId)): ?>
                    <th class="py-3 px-4 text-center cursor-pointer hover:bg-gray-100 select-none" @click="sortBy('origin')">
                        <span class="flex items-center justify-center gap-1">
                            Origine
                            <template x-if="sortColumn === 'origin'">
                                <i :class="sortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'" class="text-indigo-600"></i>
                            </template>
                        </span>
                    </th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-gray-100">
                <template x-for="client in sortedClients" :key="client.customer_number">
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 px-4">
                            <p class="font-medium text-gray-900" x-text="client.company_name"></p>
                            <p class="text-xs text-gray-500" x-text="client.customer_number"></p>
                        </td>
                        <td class="py-3 px-4 text-gray-600" x-text="client.city"></td>
                        <td class="py-3 px-4 text-center">
                            <template x-if="client.has_ordered">
                                <span class="inline-flex items-center px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">
                                    <i class="fas fa-check mr-1"></i>Commandé
                                </span>
                            </template>
                            <template x-if="!client.has_ordered">
                                <span class="inline-flex items-center px-2 py-1 bg-gray-100 text-gray-600 rounded-full text-xs">
                                    <i class="fas fa-minus mr-1"></i>Non
                                </span>
                            </template>
                        </td>
                        <td class="py-3 px-4 text-right font-medium" x-text="client.orders_count"></td>
                        <td class="py-3 px-4 text-right font-bold text-orange-600" x-text="formatNumber(client.total_quantity)"></td>
                        <?php if (!empty($campaignId)): ?>
                        <td class="py-3 px-4 text-center">
                            <template x-if="client.origin === 'client'">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">
                                    <i class="fas fa-user mr-1"></i>Client
                                </span>
                            </template>
                            <template x-if="client.origin === 'rep'">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-violet-100 text-violet-700">
                                    <i class="fas fa-user-tie mr-1"></i>Rep
                                </span>
                            </template>
                            <template x-if="client.origin === 'both'">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700">
                                    <i class="fas fa-exchange-alt mr-1"></i>Les deux
                                </span>
                            </template>
                            <template x-if="!client.origin">
                                <span class="text-gray-400">-</span>
                            </template>
                        </td>
                        <?php endif; ?>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>

<?php
    // Grouper les représentants par cluster
    // Ignorer les représentants sans clients

    // Supprimer les clusters vides

    // Trier les clusters par quantité décroissante
    // Trier les reps par quantité décroissante

    else: ?>

<!-- Vue hiérarchique Cluster → Représentant -->
<?php
$repsByCluster = [];
$totals = ["clients" => 0, "ordered" => 0, "quantity" => 0];

foreach ($reps as $rep) {
    if ($rep["total_clients"] == 0) {
        continue;
    }

    $cluster = $rep["cluster"] ?: "Non défini";
    if (!isset($repsByCluster[$cluster])) {
        $repsByCluster[$cluster] = [
            "reps" => [],
            "totals" => ["clients" => 0, "ordered" => 0, "quantity" => 0],
        ];
    }
    $repsByCluster[$cluster]["reps"][] = $rep;
    $repsByCluster[$cluster]["totals"]["clients"] += $rep["total_clients"];
    $repsByCluster[$cluster]["totals"]["ordered"] += $rep["stats"]["customers_ordered"];
    $repsByCluster[$cluster]["totals"]["quantity"] += $rep["stats"]["total_quantity"];

    $totals["clients"] += $rep["total_clients"];
    $totals["ordered"] += $rep["stats"]["customers_ordered"];
    $totals["quantity"] += $rep["stats"]["total_quantity"];
}

$repsByCluster = array_filter($repsByCluster, function ($c) {
    return $c["totals"]["clients"] > 0;
});

uasort($repsByCluster, function ($a, $b) {
    return $b["totals"]["quantity"] - $a["totals"]["quantity"];
});
?>

<?php if (empty($reps)): ?>
<div class="bg-white rounded-lg shadow-sm p-12 text-center">
    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="fas fa-users text-gray-400 text-2xl"></i>
    </div>
    <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucun représentant trouvé</h3>
    <p class="text-gray-500">Vérifiez la connexion à la base externe ou les filtres appliqués.</p>
</div>
<?php else: ?>

<!-- Totaux globaux -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-8">
            <div>
                <span class="text-sm text-gray-500">Total clients</span>
                <p class="text-xl font-bold text-gray-900"><?php echo number_format(
                    $totals["clients"],
                    0,
                    ",",
                    " ",
                ); ?></p>
            </div>
            <div>
                <span class="text-sm text-gray-500">Ont commandé</span>
                <p class="text-xl font-bold text-green-600"><?php echo number_format(
                    $totals["ordered"],
                    0,
                    ",",
                    " ",
                ); ?></p>
            </div>
            <div>
                <span class="text-sm text-gray-500">Taux participation</span>
                <p class="text-xl font-bold text-indigo-600">
                    <?php echo $totals["clients"] > 0
                        ? round(($totals["ordered"] / $totals["clients"]) * 100, 1)
                        : 0; ?>%
                </p>
            </div>
            <div>
                <span class="text-sm text-gray-500">Promos vendues</span>
                <p class="text-xl font-bold text-orange-600"><?php echo number_format(
                    $totals["quantity"],
                    0,
                    ",",
                    " ",
                ); ?></p>
            </div>
        </div>
        <div class="text-sm text-gray-500">
            <?php echo count($repsByCluster); ?> clusters • <?php echo count($reps); ?> représentants
        </div>
    </div>
</div>

<!-- Liste par cluster -->
<div class="space-y-4" x-data="{ openClusters: {} }">
    <?php foreach ($repsByCluster as $clusterName => $clusterData): ?>
    <?php
    $clusterRate =
        $clusterData["totals"]["clients"] > 0
            ? round(($clusterData["totals"]["ordered"] / $clusterData["totals"]["clients"]) * 100, 1)
            : 0;
    $clusterId = md5($clusterName);
    ?>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <!-- En-tête cluster (cliquable) -->
        <div class="bg-gray-50 px-6 py-4 cursor-pointer flex items-center justify-between hover:bg-gray-100 transition"
             @click="openClusters['<?php echo $clusterId; ?>'] = !openClusters['<?php echo $clusterId; ?>']">
            <div class="flex items-center gap-4">
                <i class="fas fa-chevron-right text-gray-400 transition-transform duration-200"
                   :class="{ 'rotate-90': openClusters['<?php echo $clusterId; ?>'] }"></i>
                <div>
                    <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($clusterName); ?></h3>
                    <p class="text-sm text-gray-500"><?php echo count(
                        $clusterData["reps"],
                    ); ?> représentant<?php echo count($clusterData["reps"]) > 1 ? "s" : ""; ?></p>
                </div>
            </div>

            <div class="flex items-center gap-6 text-sm">
                <div class="text-center">
                    <p class="font-bold text-gray-900"><?php echo number_format(
                        $clusterData["totals"]["clients"],
                        0,
                        ",",
                        " ",
                    ); ?></p>
                    <p class="text-xs text-gray-500">Clients</p>
                </div>
                <div class="text-center">
                    <p class="font-bold text-green-600"><?php echo number_format(
                        $clusterData["totals"]["ordered"],
                        0,
                        ",",
                        " ",
                    ); ?></p>
                    <p class="text-xs text-gray-500">Commandé</p>
                </div>
                <div class="text-center">
                    <?php
                    $rateClass = "text-red-500";
                    if ($clusterRate >= 50) {
                        $rateClass = "text-green-600";
                    } elseif ($clusterRate >= 25) {
                        $rateClass = "text-orange-500";
                    }
                    ?>
                    <p class="font-bold <?php echo $rateClass; ?>"><?php echo $clusterRate; ?>%</p>
                    <p class="text-xs text-gray-500">Taux</p>
                </div>
                <div class="text-center min-w-[80px]">
                    <p class="font-bold text-orange-600"><?php echo number_format(
                        $clusterData["totals"]["quantity"],
                        0,
                        ",",
                        " ",
                    ); ?></p>
                    <p class="text-xs text-gray-500">Promos</p>
                </div>
            </div>
        </div>

        <!-- Liste des représentants du cluster -->
        <div x-show="openClusters['<?php echo $clusterId; ?>']"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100">
            <table class="min-w-full">
                <thead class="bg-gray-50 border-t border-b border-gray-200">
                    <tr class="text-left text-xs text-gray-500 uppercase">
                        <th class="px-6 py-2 pl-14">Représentant</th>
                        <th class="px-6 py-2 text-right">Total clients</th>
                        <th class="px-6 py-2 text-right">Ont commandé</th>
                        <th class="px-6 py-2 text-right">Taux</th>
                        <th class="px-6 py-2 text-right">Promos vendues</th>
                        <?php if (!empty($campaignId)): ?>
                        <th class="px-6 py-2 text-center">Via Rep</th>
                        <?php endif; ?>
                        <th class="px-6 py-2 text-center">Détail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php usort($clusterData["reps"], function ($a, $b) {
                        return $b["stats"]["total_quantity"] - $a["stats"]["total_quantity"];
                    }); ?>
                    <?php foreach ($clusterData["reps"] as $rep): ?>
                    <?php
                    $repRate =
                        $rep["total_clients"] > 0
                            ? round(($rep["stats"]["customers_ordered"] / $rep["total_clients"]) * 100, 1)
                            : 0;
                    $repRateClass = "text-red-500";
                    if ($repRate >= 50) {
                        $repRateClass = "text-green-600";
                    } elseif ($repRate >= 25) {
                        $repRateClass = "text-orange-500";
                    }
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 pl-14">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center">
                                    <span class="text-xs font-medium text-indigo-600">
                                        <?php echo strtoupper(substr($rep["name"], 0, 2)); ?>
                                    </span>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900"><?php echo htmlspecialchars(
                                        $rep["name"],
                                    ); ?></p>
                                    <p class="text-xs text-gray-500">
                                        <?php echo $rep["country"] === "BE" ? "🇧🇪" : "🇱🇺"; ?>
                                        <?php echo $rep["country"]; ?>
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-3 text-right font-medium"><?php echo number_format(
                            $rep["total_clients"],
                            0,
                            ",",
                            " ",
                        ); ?></td>
                        <td class="px-6 py-3 text-right font-medium text-green-600"><?php echo $rep["stats"][
                            "customers_ordered"
                        ]; ?></td>
                        <td class="px-6 py-3 text-right font-bold <?php echo $repRateClass; ?>">
                            <?php echo $repRate; ?>%
                        </td>
                        <td class="px-6 py-3 text-right font-bold text-orange-600"><?php echo number_format(
                            $rep["stats"]["total_quantity"],
                            0,
                            ",",
                            " ",
                        ); ?></td>
                        <?php if (!empty($campaignId)): ?>
                        <td class="px-6 py-3 text-center">
                            <?php
                            $repOrigin = $originStatsByRep[$rep["id"]] ?? null;
                            $repClientOrders = $repOrigin['client_orders'] ?? 0;
                            $repRepOrders = $repOrigin['rep_orders'] ?? 0;
                            $repTotalOrigin = $repClientOrders + $repRepOrders;
                            $pctViaReps = $repTotalOrigin > 0 ? round(($repRepOrders / $repTotalOrigin) * 100) : null;
                            ?>
                            <?php if ($pctViaReps !== null): ?>
                                <?php if ($pctViaReps >= 75): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-violet-100 text-violet-700" title="<?= $repRepOrders ?> cmd via rep / <?= $repTotalOrigin ?> total">
                                    <i class="fas fa-user-tie mr-1"></i><?= $pctViaReps ?>%
                                </span>
                                <?php elseif ($pctViaReps >= 25): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700" title="<?= $repRepOrders ?> cmd via rep / <?= $repTotalOrigin ?> total">
                                    <i class="fas fa-exchange-alt mr-1"></i><?= $pctViaReps ?>%
                                </span>
                                <?php else: ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700" title="<?= $repClientOrders ?> cmd via clients / <?= $repTotalOrigin ?> total">
                                    <i class="fas fa-user mr-1"></i><?= 100 - $pctViaReps ?>%
                                </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        <td class="px-6 py-3 text-center">
                            <?php
                            $detailUrl =
                                "/stm/admin/stats/sales?rep_id=" .
                                urlencode($rep["id"]) .
                                "&rep_country=" .
                                $rep["country"];
                            if (!empty($campaignId)) {
                                $detailUrl .= "&campaign_id=" . $campaignId;
                            }
                            if (!empty($country)) {
                                $detailUrl .= "&country=" . $country;
                            }
                            ?>
                            <a href="<?php echo $detailUrl; ?>"
                               class="inline-flex items-center px-3 py-1 bg-indigo-50 text-indigo-600 rounded hover:bg-indigo-100 transition text-sm">
                                <i class="fas fa-eye mr-1"></i> Voir
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php endif; ?>
<?php endif; ?>

<?php
$content = ob_get_clean();
$pageScripts = <<<'SCRIPT'
<script>
function clientsTable(clients) {
    return {
        clients: clients,
        sortColumn: 'orders_count',
        sortDirection: 'desc',

        get sortedClients() {
            return [...this.clients].sort((a, b) => {
                let aVal = a[this.sortColumn];
                let bVal = b[this.sortColumn];

                // Gérer les valeurs null/undefined
                if (aVal === null || aVal === undefined) aVal = '';
                if (bVal === null || bVal === undefined) bVal = '';

                // Comparaison
                let comparison = 0;
                if (typeof aVal === 'string') {
                    comparison = aVal.localeCompare(bVal, 'fr', { sensitivity: 'base' });
                } else {
                    comparison = aVal - bVal;
                }

                return this.sortDirection === 'asc' ? comparison : -comparison;
            });
        },

        sortBy(column) {
            if (this.sortColumn === column) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortColumn = column;
                // Par défaut desc pour les nombres, asc pour le texte
                this.sortDirection = ['orders_count', 'total_quantity', 'has_ordered'].includes(column) ? 'desc' : 'asc';
            }
        },

        formatNumber(num) {
            return new Intl.NumberFormat('fr-FR').format(num);
        }
    }
}
</script>
SCRIPT;
require __DIR__ . "/../../layouts/admin.php";
 ?>