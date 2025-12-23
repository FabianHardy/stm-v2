<?php
/**
 * Vue : Statistiques - Par campagne
 *
 * Stats détaillées pour une campagne spécifique
 * Avec graphiques, loader et système d'onglets
 *
 * @package STM
 * @created 2025/11/25
 * @modified 2025/12/04 - Ajout loader + graphiques Chart.js
 * @modified 2025/12/08 - Légende colonnes représentants
 * @modified 2025/12/09 - Système d'onglets (Produits/Reps/Fournisseurs)
 * @modified 2025/12/09 - Ajout fournisseur dans Produits, accordion dans Fournisseurs, tri par colonnes
 * @modified 2025/12/17 - Ajout filtrage automatique pays selon rôle
 * @modified 2025/12/22 - Ajout overlay de chargement pour export Excel avec timer
 * @modified 2025/12/22 - Système de cache intelligent pour exports Excel
 */

use App\Helpers\StatsAccessHelper;

// Variable pour le menu actif
$activeMenu = "stats-campaigns";

// Récupérer les pays accessibles selon le rôle
$accessibleCountries = StatsAccessHelper::getAccessibleCountries();
$defaultCountry = StatsAccessHelper::getDefaultCountry();

ob_start();

// Préparer les campagnes par pays pour Alpine.js
$campaignsByCountry = ["BE" => [], "LU" => [], "all" => []];
foreach ($campaigns as $c) {
    $campaignsByCountry[$c["country"]][] = $c;
    $campaignsByCountry["all"][] = $c;
}
$campaignsJson = json_encode($campaignsByCountry);

// Récupérer le pays sélectionné
$selectedCountry = $_GET["country"] ?? $defaultCountry ?? "";
if (!$selectedCountry && $campaignStats) {
    $selectedCountry = $campaignStats["campaign"]["country"] ?? "";
}

// Si un seul pays accessible, forcer ce pays
if ($accessibleCountries !== null && count($accessibleCountries) === 1) {
    $selectedCountry = $accessibleCountries[0];
}

// Encoder les stats fournisseurs en JSON pour le tri Alpine.js
$supplierStatsJson = json_encode($supplierStats ?? []);
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

<!-- Export Loader Overlay -->
<div id="export-loader" class="fixed inset-0 bg-gray-900 bg-opacity-75 z-50 hidden items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl p-8 max-w-md mx-4 text-center">
        <div class="relative mb-4">
            <div id="export-spinner" class="w-20 h-20 border-4 border-green-200 border-t-green-600 rounded-full animate-spin mx-auto"></div>
            <i class="fas fa-file-excel text-green-600 text-2xl absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2"></i>
        </div>
        <h3 id="export-title" class="text-xl font-bold text-gray-900 mb-2">Génération de l'export Excel</h3>
        <p id="export-message" class="text-gray-600 mb-4">Cette opération peut prendre plusieurs minutes selon le volume de données.</p>
        <div id="export-warning" class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-sm text-amber-800">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <strong>Ne quittez pas cette page</strong> pendant la génération.
        </div>
        <p class="text-xs text-gray-400 mt-4">Temps écoulé : <span id="export-timer">0:00</span></p>
    </div>
</div>

<!-- En-tête -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Statistiques - Par campagne</h1>
    <p class="text-gray-600 mt-1">Performances détaillées d'une campagne</p>
</div>

<!-- Sélecteur de campagne avec filtre pays -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-6" x-data="campaignFilter()" x-init="init()">
    <div class="flex flex-wrap gap-4 items-center">

        <!-- Partie gauche : Sélecteur -->
        <form method="GET" action="/stm/admin/stats/campaigns" class="flex flex-wrap gap-4 items-end <?= $campaignStats ? 'flex-shrink-0' : 'flex-1' ?>" id="campaign-form">

            <!-- Pays - Masqué si un seul pays accessible -->
            <?php if ($accessibleCountries === null || count($accessibleCountries) > 1): ?>
            <div class="w-40">
                <label class="block text-sm font-medium text-gray-700 mb-1">Pays</label>
                <select name="country" x-model="selectedCountry" @change="filterCampaigns()"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <?php if ($accessibleCountries === null): ?>
                    <option value="">Tous</option>
                    <option value="BE">🇧🇪 Belgique</option>
                    <option value="LU">🇱🇺 Luxembourg</option>
                    <?php else: ?>
                    <option value="">Tous</option>
                    <?php if (in_array("BE", $accessibleCountries)): ?>
                    <option value="BE">🇧🇪 Belgique</option>
                    <?php endif; ?>
                    <?php if (in_array("LU", $accessibleCountries)): ?>
                    <option value="LU">🇱🇺 Luxembourg</option>
                    <?php endif; ?>
                    <?php endif; ?>
                </select>
            </div>
            <?php else: ?>
            <!-- Pays unique - Champ caché -->
            <input type="hidden" name="country" value="<?= $selectedCountry ?>">
            <div class="w-40">
                <label class="block text-sm font-medium text-gray-700 mb-1">Pays</label>
                <div class="px-4 py-2 bg-gray-100 rounded-lg text-gray-700">
                    <?= $selectedCountry === "BE" ? "🇧🇪 Belgique" : "🇱🇺 Luxembourg" ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Campagne -->
            <div class="<?= $campaignStats ? 'w-64' : 'flex-1 min-w-[300px]' ?>">
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

        <?php if ($campaignStats): ?>
        <!-- Partie droite : Infos campagne -->
        <div class="flex-1 flex items-center justify-end gap-4 pl-4 border-l border-gray-200">
            <div class="text-right">
                <h2 class="text-lg font-bold text-gray-900"><?= htmlspecialchars($campaignStats["campaign"]["name"]) ?></h2>
                <p class="text-xs text-gray-500"><?= htmlspecialchars($campaignStats["campaign"]["title_fr"] ?? "") ?></p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <span class="inline-flex items-center px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs">
                    <i class="fas fa-calendar mr-1"></i>
                    <?= date("d/m/Y", strtotime($campaignStats["campaign"]["start_date"])) ?> - <?= date("d/m/Y", strtotime($campaignStats["campaign"]["end_date"])) ?>
                </span>
                <span class="inline-flex items-center px-2 py-1 <?= $campaignStats["campaign"]["country"] === "BE" ? "bg-blue-100 text-blue-700" : "bg-yellow-100 text-yellow-700" ?> rounded text-xs">
                    <?= $campaignStats["campaign"]["country"] === "BE" ? "🇧🇪" : "🇱🇺" ?>
                </span>
                <?php
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
                <span class="inline-flex items-center px-2 py-1 <?= $statusColor ?> rounded text-xs">
                    <?= $statusLabel ?>
                </span>
            </div>
        </div>
        <?php endif; ?>

    </div>
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

<!-- KPIs -->
<div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
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
    <div class="bg-white rounded-lg shadow-sm p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Moy. / commande</p>
        <?php
        $totalOrders = $campaignStats["total_orders"] ?? 0;
        $totalQuantity = $campaignStats["total_quantity"] ?? 0;
        $avgPerOrder = $totalOrders > 0 ? round($totalQuantity / $totalOrders, 1) : 0;
        ?>
        <p class="text-2xl font-bold text-purple-600 mt-1"><?= number_format($avgPerOrder, 1, ",", " ") ?></p>
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

<!-- ============================================ -->
<!-- SYSTÈME D'ONGLETS                            -->
<!-- ============================================ -->
<?php
// Compter les éléments pour les badges
$productsCount = count($campaignProducts ?? []);
$repsCount = 0;
if (!empty($reps)) {
    foreach ($reps as $rep) {
        if (($rep["total_clients"] ?? 0) > 0) {
            $repsCount++;
        }
    }
}
$suppliersCount = count($supplierStats ?? []);
?>

<div class="bg-white rounded-lg shadow-sm mb-6" x-data="{ activeTab: 'products' }">

    <!-- Navigation onglets -->
    <div class="border-b border-gray-200">
        <nav class="flex -mb-px">
            <!-- Onglet Produits -->
            <button @click="activeTab = 'products'"
                    :class="activeTab === 'products' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="flex items-center gap-2 px-6 py-4 border-b-2 font-medium text-sm transition-colors">
                <i class="fas fa-box"></i>
                <span>Produits</span>
                <span class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium rounded-full"
                      :class="activeTab === 'products' ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-500'">
                    <?= $productsCount ?>
                </span>
            </button>

            <!-- Onglet Représentants -->
            <button @click="activeTab = 'reps'"
                    :class="activeTab === 'reps' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="flex items-center gap-2 px-6 py-4 border-b-2 font-medium text-sm transition-colors">
                <i class="fas fa-users"></i>
                <span>Représentants</span>
                <span class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium rounded-full"
                      :class="activeTab === 'reps' ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-500'">
                    <?= $repsCount ?>
                </span>
            </button>

            <!-- Onglet Fournisseurs -->
            <button @click="activeTab = 'suppliers'"
                    :class="activeTab === 'suppliers' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="flex items-center gap-2 px-6 py-4 border-b-2 font-medium text-sm transition-colors">
                <i class="fas fa-truck"></i>
                <span>Fournisseurs</span>
                <span class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium rounded-full"
                      :class="activeTab === 'suppliers' ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-500'">
                    <?= $suppliersCount ?>
                </span>
            </button>
        </nav>
    </div>

    <!-- Contenu des onglets -->
    <div class="p-6">

        <!-- ============================================ -->
        <!-- ONGLET PRODUITS (avec fournisseur)           -->
        <!-- ============================================ -->
        <div x-show="activeTab === 'products'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
            <?php if (empty($campaignProducts)): ?>
            <div class="text-center py-8">
                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-box text-gray-400"></i>
                </div>
                <p class="text-gray-500">Aucun produit dans cette campagne</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs text-gray-500 uppercase">
                            <th class="py-3 px-4">#</th>
                            <th class="py-3 px-4">Produit</th>
                            <th class="py-3 px-4">Fournisseur</th>
                            <th class="py-3 px-4 text-right">CMD</th>
                            <th class="py-3 px-4 text-right">Promos</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-gray-100">
                        <?php
                        $rank = 0;

                        foreach ($campaignProducts as $product):
                            $rank++;
                            $productName = $product["product_name"] ?? "-";
                            $productCode = $product["product_code"] ?? "-";
                            $ordersCount = $product["orders_count"] ?? 0;
                            $totalQty = $product["quantity_sold"] ?? 0;

                            // Récupérer le fournisseur
                            $supplierName = $productSuppliers[$productCode]['supplier_name'] ?? 'Non référencé';
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
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 bg-orange-100 text-orange-700 rounded text-xs">
                                    <i class="fas fa-truck mr-1 text-[10px]"></i>
                                    <?= htmlspecialchars($supplierName) ?>
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right font-medium"><?= $ordersCount ?></td>
                            <td class="py-3 px-4 text-right font-bold text-orange-600"><?= number_format($totalQty, 0, ",", " ") ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- ============================================ -->
        <!-- ONGLET REPRÉSENTANTS                         -->
        <!-- ============================================ -->
        <div x-show="activeTab === 'reps'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">

            <?php if (empty($reps)): ?>
            <div class="text-center py-8">
                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-users text-gray-400"></i>
                </div>
                <p class="text-gray-500">Aucune donnée représentant</p>
                <p class="text-xs text-gray-400 mt-1">Vérifiez la connexion à la base externe</p>
            </div>
            <?php else: ?>
            <?php
            // Grouper par cluster
            $clusters = [];
            foreach ($reps as $rep) {
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

            $clusters = array_filter($clusters, function($c) {
                return !empty($c["reps"]);
            });

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
            </div>
            <?php else: ?>

            <!-- Bouton Export Excel Représentants avec statut cache -->
            <div class="flex justify-end items-center gap-3 mb-4" x-data="exportCache()" x-init="checkCache()">
                <!-- Indicateur de statut du cache -->
                <div class="text-xs text-right">
                    <template x-if="cacheStatus === 'checking'">
                        <span class="text-gray-400"><i class="fas fa-spinner fa-spin mr-1"></i>Vérification...</span>
                    </template>
                    <template x-if="cacheStatus === 'valid'">
                        <span class="text-green-600" :title="'Généré le ' + cachedAt">
                            <i class="fas fa-check-circle mr-1"></i>En cache
                            <span class="text-gray-400">(<span x-text="formatFileSize(fileSize)"></span>)</span>
                        </span>
                    </template>
                    <template x-if="cacheStatus === 'outdated'">
                        <span class="text-amber-600" :title="'Fichier du ' + cachedAt + ' - nouvelles données disponibles'">
                            <i class="fas fa-exclamation-circle mr-1"></i>Mise à jour requise
                        </span>
                    </template>
                    <template x-if="cacheStatus === 'no_cache'">
                        <span class="text-gray-400">
                            <i class="fas fa-file-excel mr-1"></i>Non généré
                        </span>
                    </template>
                </div>

                <form method="POST" action="/stm/admin/stats/export-reps-excel" class="inline" id="export-form" @submit="return startExport(cacheStatus)">
                    <input type="hidden" name="campaign_id" value="<?= $campaignId ?? '' ?>">
                    <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="download_token" id="download_token" value="">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 text-white text-sm font-medium rounded-lg transition shadow-sm"
                            :class="cacheStatus === 'valid' ? 'bg-blue-600 hover:bg-blue-700' : 'bg-green-600 hover:bg-green-700'">
                        <i class="fas fa-file-excel"></i>
                        <span x-text="cacheStatus === 'valid' ? 'Télécharger' : 'Générer'"></span>
                    </button>
                </form>
            </div>

            <!-- Légende des colonnes -->
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
            <?php endif; ?>
        </div>

        <!-- ============================================ -->
        <!-- ONGLET FOURNISSEURS (accordion + tri)        -->
        <!-- ============================================ -->
        <div x-show="activeTab === 'suppliers'"
             x-data="supplierTable()"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100">

            <template x-if="suppliers.length === 0">
                <div class="text-center py-8">
                    <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-truck text-gray-400"></i>
                    </div>
                    <p class="text-gray-500">Aucune donnée fournisseur</p>
                    <p class="text-xs text-gray-400 mt-1">Vérifiez que les codes produits correspondent à BE_COLIS</p>
                </div>
            </template>

            <template x-if="suppliers.length > 0">
                <div>
                    <!-- En-tête avec tri -->
                    <div class="bg-gray-50 rounded-t-lg border border-gray-200 px-4 py-3">
                        <div class="flex items-center justify-between text-xs text-gray-500 uppercase font-medium">
                            <div class="w-8">#</div>
                            <button @click="sortBy('supplier_name')" class="flex-1 min-w-[200px] text-left hover:text-indigo-600 transition flex items-center gap-1">
                                Fournisseur
                                <i class="fas" :class="sortField === 'supplier_name' ? (sortDir === 'desc' ? 'fa-sort-down' : 'fa-sort-up') : 'fa-sort text-gray-300'"></i>
                            </button>
                            <button @click="sortBy('customers_count')" class="w-20 text-center hover:text-indigo-600 transition flex items-center justify-center gap-1">
                                Clients
                                <i class="fas" :class="sortField === 'customers_count' ? (sortDir === 'desc' ? 'fa-sort-down' : 'fa-sort-up') : 'fa-sort text-gray-300'"></i>
                            </button>
                            <button @click="sortBy('orders_count')" class="w-20 text-center hover:text-indigo-600 transition flex items-center justify-center gap-1">
                                CMD
                                <i class="fas" :class="sortField === 'orders_count' ? (sortDir === 'desc' ? 'fa-sort-down' : 'fa-sort-up') : 'fa-sort text-gray-300'"></i>
                            </button>
                            <button @click="sortBy('promos_count')" class="w-20 text-center hover:text-indigo-600 transition flex items-center justify-center gap-1">
                                Promos
                                <i class="fas" :class="sortField === 'promos_count' ? (sortDir === 'desc' ? 'fa-sort-down' : 'fa-sort-up') : 'fa-sort text-gray-300'"></i>
                            </button>
                            <button @click="sortBy('total_quantity')" class="w-24 text-right hover:text-indigo-600 transition flex items-center justify-end gap-1">
                                Qté
                                <i class="fas" :class="sortField === 'total_quantity' ? (sortDir === 'desc' ? 'fa-sort-down' : 'fa-sort-up') : 'fa-sort text-gray-300'"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Liste des fournisseurs -->
                    <div class="border-x border-b border-gray-200 rounded-b-lg divide-y divide-gray-100">
                        <template x-for="(supplier, index) in sortedSuppliers" :key="supplier.supplier_id">
                            <div>
                                <!-- Ligne fournisseur -->
                                <div class="px-4 py-3 flex items-center justify-between cursor-pointer hover:bg-gray-50 transition"
                                     :class="{ 'bg-yellow-50': index < 3 }"
                                     @click="toggleSupplier(supplier.supplier_id)">
                                    <div class="flex items-center gap-3 w-8">
                                        <template x-if="index < 3">
                                            <span class="text-lg" x-text="['🥇', '🥈', '🥉'][index]"></span>
                                        </template>
                                        <template x-if="index >= 3">
                                            <span class="inline-flex items-center justify-center w-6 h-6 bg-gray-100 text-gray-500 rounded-full text-xs font-medium" x-text="index + 1"></span>
                                        </template>
                                    </div>
                                    <div class="flex-1 min-w-[200px] flex items-center gap-3">
                                        <i class="fas fa-chevron-right text-gray-400 text-sm transition-transform duration-200"
                                           :class="{ 'rotate-90': openSuppliers[supplier.supplier_id] }"></i>
                                        <div>
                                            <p class="font-medium text-gray-900" x-text="supplier.supplier_name"></p>
                                            <p class="text-xs text-gray-500 font-mono" x-text="supplier.supplier_number"></p>
                                        </div>
                                    </div>
                                    <div class="w-20 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs font-medium" x-text="supplier.customers_count"></span>
                                    </div>
                                    <div class="w-20 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs font-medium" x-text="supplier.orders_count"></span>
                                    </div>
                                    <div class="w-20 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 bg-purple-100 text-purple-700 rounded text-xs font-medium" x-text="supplier.promos_count"></span>
                                    </div>
                                    <div class="w-24 text-right font-bold text-orange-600" x-text="formatNumber(supplier.total_quantity)"></div>
                                </div>

                                <!-- Détail produits du fournisseur -->
                                <div x-show="openSuppliers[supplier.supplier_id]"
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0"
                                     x-transition:enter-end="opacity-100"
                                     class="bg-gray-50 px-4 py-3 border-t border-gray-100">
                                    <p class="text-xs font-medium text-gray-500 mb-2">
                                        <i class="fas fa-box mr-1"></i>
                                        Produits de ce fournisseur (<span x-text="supplier.products ? supplier.products.length : 0"></span>)
                                    </p>
                                    <div class="space-y-1 pl-4">
                                        <template x-for="prod in supplier.products" :key="prod.product_id">
                                            <div class="flex items-center justify-between text-sm py-1 border-b border-gray-100 last:border-0">
                                                <div class="flex-1">
                                                    <span class="text-gray-900" x-text="prod.product_name"></span>
                                                    <span class="text-xs text-gray-400 ml-2" x-text="prod.product_code"></span>
                                                </div>
                                                <div class="flex items-center gap-4">
                                                    <span class="text-xs text-gray-500">
                                                        <span x-text="prod.orders_count"></span> cmd
                                                    </span>
                                                    <span class="font-medium text-orange-600" x-text="formatNumber(prod.quantity_sold)"></span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Légende -->
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-xs text-gray-500">
                            <strong>Clients</strong> : Clients distincts ayant commandé •
                            <strong>CMD</strong> : Nombre de commandes •
                            <strong>Promos</strong> : Nombre de produits du fournisseur •
                            <i class="fas fa-sort mx-1"></i> Cliquez sur les en-têtes pour trier
                        </p>
                    </div>
                </div>
            </template>
        </div>

    </div>
</div>

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
$campaignIdInt = isset($campaignId) ? (int)$campaignId : 0;
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
// EXPORT CACHE - Vérifie l'état du cache
// ============================================
function exportCache() {
    return {
        cacheStatus: 'checking',
        cachedAt: null,
        fileSize: 0,

        checkCache() {
            const campaignId = {$campaignIdInt};
            if (!campaignId) {
                this.cacheStatus = 'no_cache';
                return;
            }

            fetch('/stm/admin/stats/check-export-cache?campaign_id=' + campaignId)
                .then(response => response.json())
                .then(data => {
                    this.cacheStatus = data.status || 'no_cache';
                    this.cachedAt = data.cached_at ? this.formatDate(data.cached_at) : null;
                    this.fileSize = data.file_size || 0;
                })
                .catch(error => {
                    console.error('Erreur vérification cache:', error);
                    this.cacheStatus = 'no_cache';
                });
        },

        formatDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('fr-FR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        formatFileSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        }
    }
}

// ============================================
// EXPORT LOADER AVEC TIMER ET COOKIE TOKEN
// ============================================
let exportTimerInterval = null;
let exportStartTime = null;
let downloadCheckInterval = null;

function startExport(cacheStatus) {
    // Générer un token unique pour ce téléchargement
    const downloadToken = 'download_' + Date.now();

    // Ajouter le token au formulaire
    document.getElementById('download_token').value = downloadToken;

    // Adapter le message selon le statut du cache
    const titleEl = document.getElementById('export-title');
    const messageEl = document.getElementById('export-message');
    const warningEl = document.getElementById('export-warning');
    const spinnerEl = document.getElementById('export-spinner');

    if (cacheStatus === 'valid') {
        titleEl.textContent = 'Téléchargement en cours...';
        messageEl.textContent = 'Le fichier est en cache, téléchargement immédiat.';
        warningEl.classList.add('hidden');
        spinnerEl.className = 'w-20 h-20 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mx-auto';
    } else if (cacheStatus === 'outdated') {
        titleEl.textContent = 'Mise à jour en cours...';
        messageEl.textContent = 'De nouvelles données ont été détectées. Régénération du fichier en cours.';
        warningEl.classList.remove('hidden');
        spinnerEl.className = 'w-20 h-20 border-4 border-amber-200 border-t-amber-600 rounded-full animate-spin mx-auto';
    } else {
        titleEl.textContent = 'Génération Excel en cours...';
        messageEl.textContent = 'Cette opération peut prendre plusieurs minutes selon le volume de données.';
        warningEl.classList.remove('hidden');
        spinnerEl.className = 'w-20 h-20 border-4 border-green-200 border-t-green-600 rounded-full animate-spin mx-auto';
    }

    // Afficher l'overlay
    document.getElementById('export-loader').classList.remove('hidden');
    document.getElementById('export-loader').classList.add('flex');

    // Démarrer le timer
    exportStartTime = Date.now();
    updateExportTimer();
    exportTimerInterval = setInterval(updateExportTimer, 1000);

    // Vérifier périodiquement si le cookie de téléchargement existe
    downloadCheckInterval = setInterval(function() {
        if (getCookie('download_complete') === downloadToken) {
            hideExportLoader();
            document.cookie = 'download_complete=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
        }
    }, 500);

    // Timeout de sécurité : masquer le loader après 10 minutes max
    setTimeout(function() {
        hideExportLoader();
    }, 600000);

    return true;
}

function hideExportLoader() {
    document.getElementById('export-loader').classList.add('hidden');
    document.getElementById('export-loader').classList.remove('flex');

    if (exportTimerInterval) {
        clearInterval(exportTimerInterval);
        exportTimerInterval = null;
    }
    if (downloadCheckInterval) {
        clearInterval(downloadCheckInterval);
        downloadCheckInterval = null;
    }
}

function updateExportTimer() {
    if (!exportStartTime) return;

    const elapsed = Math.floor((Date.now() - exportStartTime) / 1000);
    const minutes = Math.floor(elapsed / 60);
    const seconds = elapsed % 60;

    const timerEl = document.getElementById('export-timer');
    if (timerEl) {
        timerEl.textContent = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
    }
}

function getCookie(name) {
    const value = '; ' + document.cookie;
    const parts = value.split('; ' + name + '=');
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
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
// TABLEAU FOURNISSEURS AVEC TRI
// ============================================
function supplierTable() {
    return {
        suppliers: {$supplierStatsJson},
        sortField: 'total_quantity',
        sortDir: 'desc',
        openSuppliers: {},

        get sortedSuppliers() {
            return [...this.suppliers].sort((a, b) => {
                let valA = a[this.sortField];
                let valB = b[this.sortField];

                // Tri alphabétique pour supplier_name
                if (this.sortField === 'supplier_name') {
                    valA = (valA || '').toLowerCase();
                    valB = (valB || '').toLowerCase();

                    if (this.sortDir === 'asc') {
                        return valA.localeCompare(valB, 'fr');
                    }
                    return valB.localeCompare(valA, 'fr');
                }

                // Tri numérique pour les autres colonnes
                valA = valA || 0;
                valB = valB || 0;

                if (this.sortDir === 'asc') {
                    return valA - valB;
                }
                return valB - valA;
            });
        },

        sortBy(field) {
            if (this.sortField === field) {
                this.sortDir = this.sortDir === 'desc' ? 'asc' : 'desc';
            } else {
                this.sortField = field;
                // Tri par nom : A-Z par défaut, autres : desc par défaut
                this.sortDir = field === 'supplier_name' ? 'asc' : 'desc';
            }
        },

        toggleSupplier(supplierId) {
            this.openSuppliers[supplierId] = !this.openSuppliers[supplierId];
        },

        formatNumber(num) {
            return new Intl.NumberFormat('fr-FR').format(num || 0);
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