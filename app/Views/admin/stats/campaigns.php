<?php
/**
 * Vue : Statistiques - Par campagne
 *
 * Stats détaillées pour une campagne spécifique
 *
 * @package STM
 * @created 2025/11/25
 * @modified 2025/11/26 - Ajout filtre pays → campagne
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

// Récupérer le pays sélectionné (depuis l'URL ou la campagne sélectionnée)
$selectedCountry = $_GET["country"] ?? "";
if (!$selectedCountry && $campaignStats) {
    $selectedCountry = $campaignStats["campaign"]["country"] ?? "";
}
?>

<!-- En-tête -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Statistiques - Par campagne</h1>
    <p class="text-gray-600 mt-1">Performances détaillées d'une campagne</p>
</div>

<!-- Sélecteur de campagne avec filtre pays -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-6" x-data="campaignFilter()" x-init="init()">
    <form method="GET" action="/stm/admin/stats/campaigns" class="flex flex-wrap gap-4 items-end">

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

        <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition">
            <i class="fas fa-chart-bar mr-2"></i>Voir les stats
        </button>
    </form>
</div>

<?php if ($campaignStats): ?>

<!-- Infos campagne -->
<?php
// Traduction et couleur du statut
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
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <div class="flex items-start justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($campaignStats["campaign"]["name"]) ?></h2>
            <p class="text-gray-600 mt-1"><?= htmlspecialchars($campaignStats["campaign"]["title_fr"]) ?></p>

            <div class="flex items-center gap-4 mt-3 text-sm text-gray-500">
                <span><i class="fas fa-calendar mr-1"></i> <?= date(
                    "d/m/Y",
                    strtotime($campaignStats["campaign"]["start_date"]),
                ) ?> - <?= date("d/m/Y", strtotime($campaignStats["campaign"]["end_date"])) ?></span>
                <span><i class="fas fa-globe mr-1"></i> <?= $campaignStats["campaign"]["country"] === "BE"
                    ? "🇧🇪 Belgique"
                    : "🇱🇺 Luxembourg" ?></span>
                <span class="px-2 py-1 rounded-full text-xs font-medium <?= $statusColor ?>">
                    <?= $statusLabel ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- KPIs campagne -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">

    <div class="bg-white rounded-lg shadow-sm p-5">
        <p class="text-sm text-gray-500">Clients éligibles</p>
        <p class="text-2xl font-bold text-gray-900">
            <?= is_numeric($campaignStats["eligible_customers"])
                ? number_format($campaignStats["eligible_customers"], 0, ",", " ")
                : $campaignStats["eligible_customers"] ?>
        </p>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-5">
        <p class="text-sm text-gray-500">Clients ayant commandé</p>
        <p class="text-2xl font-bold text-green-600"><?= number_format(
            $campaignStats["customers_ordered"],
            0,
            ",",
            " ",
        ) ?></p>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-5">
        <p class="text-sm text-gray-500">Taux de participation</p>
        <p class="text-2xl font-bold text-indigo-600"><?= $campaignStats["participation_rate"] ?>%</p>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-5">
        <p class="text-sm text-gray-500">Total commandes</p>
        <p class="text-2xl font-bold text-gray-900"><?= number_format(
            $campaignStats["total_orders"],
            0,
            ",",
            " ",
        ) ?></p>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-5">
        <p class="text-sm text-gray-500">Promos vendues</p>
        <p class="text-2xl font-bold text-orange-600"><?= number_format(
            $campaignStats["total_quantity"],
            0,
            ",",
            " ",
        ) ?></p>
    </div>
</div>

<!-- Produits et Clients sans commande -->
<!-- Produits de la campagne -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Produits de la campagne</h3>

    <?php if (empty($campaignProducts)): ?>
    <p class="text-gray-500 text-center py-4">Aucun produit</p>
    <?php else: ?>

    <!-- En-tête tableau -->
    <div class="flex items-center justify-between text-xs text-gray-500 uppercase tracking-wider pb-2 border-b border-gray-200 mb-2">
        <span>Produit</span>
        <div class="flex gap-6">
            <span class="w-16 text-center">Cmd</span>
            <span class="w-20 text-center">Promos</span>
        </div>
    </div>

    <div class="space-y-2 max-h-80 overflow-y-auto">
        <?php foreach ($campaignProducts as $i => $product): ?>
        <div class="flex items-center justify-between py-2 <?= $i > 0 ? "border-t border-gray-50" : "" ?>">
            <div class="flex items-center gap-3">
                <span class="w-6 h-6 bg-indigo-100 text-indigo-800 rounded-full flex items-center justify-center text-sm font-medium">
                    <?= $i + 1 ?>
                </span>
                <div>
                    <p class="font-medium text-gray-900 text-sm"><?= htmlspecialchars($product["product_name"]) ?></p>
                    <p class="text-xs text-gray-500"><?= htmlspecialchars($product["product_code"]) ?></p>
                </div>
            </div>
            <div class="flex gap-6">
                <div class="w-16 text-center">
                    <p class="font-semibold text-indigo-600"><?= $product["orders_count"] ?></p>
                </div>
                <div class="w-20 text-center">
                    <p class="font-bold text-orange-600"><?= number_format(
                        $product["quantity_sold"],
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

<!-- Performance par représentant -->
<div class="bg-white rounded-lg shadow-sm p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Performance par représentant</h3>

    <?php if (empty($reps)): ?>
    <div class="text-center py-8">
        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
            <i class="fas fa-users text-gray-400"></i>
        </div>
        <p class="text-gray-500">Aucune donnée représentant</p>
        <p class="text-xs text-gray-400 mt-1">Vérifiez la connexion à la base externe</p>
    </div>
    <?php
        // Grouper les représentants par cluster
        // Ignorer les représentants sans clients
        // Supprimer les clusters vides (au cas où)
        // Trier par quantité
        // Grouper les représentants par cluster
        // Ignorer les représentants sans clients
        // Supprimer les clusters vides (au cas où)
        // Trier par quantité
        else: ?>

    <?php
    $repsByCluster = [];
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
    }
    $repsByCluster = array_filter($repsByCluster, fn($c) => $c["totals"]["clients"] > 0);
    uasort($repsByCluster, fn($a, $b) => $b["totals"]["quantity"] - $a["totals"]["quantity"]);
    ?>

    <div class="space-y-3" x-data="{ openClusters: {} }">
        <?php foreach ($repsByCluster as $clusterName => $clusterData): ?>
        <?php
        $clusterRate =
            $clusterData["totals"]["clients"] > 0
                ? round(($clusterData["totals"]["ordered"] / $clusterData["totals"]["clients"]) * 100, 1)
                : 0;
        $clusterId = md5($clusterName);
        ?>

        <div class="border border-gray-200 rounded-lg overflow-hidden">
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
                        <span class="font-bold text-orange-600"><?= number_format(
                            $clusterData["totals"]["quantity"],
                            0,
                            ",",
                            " ",
                        ) ?></span>
                    </div>
                </div>
            </div>

            <!-- Liste des représentants -->
            <div x-show="openClusters['<?= $clusterId ?>']"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100">
                <div class="divide-y divide-gray-100">
                    <?php usort(
                        $clusterData["reps"],
                        fn($a, $b) => $b["stats"]["total_quantity"] - $a["stats"]["total_quantity"],
                    ); ?>
                    <?php foreach ($clusterData["reps"] as $rep): ?>
                    <?php $repRate =
                        $rep["total_clients"] > 0
                            ? round(($rep["stats"]["customers_ordered"] / $rep["total_clients"]) * 100, 1)
                            : 0; ?>
                    <div class="px-4 py-2 flex items-center justify-between hover:bg-gray-50">
                        <div class="flex items-center gap-2 pl-6">
                            <div class="w-6 h-6 bg-indigo-100 rounded-full flex items-center justify-center">
                                <span class="text-xs font-medium text-indigo-600"><?= strtoupper(
                                    substr($rep["name"], 0, 2),
                                ) ?></span>
                            </div>
                            <span class="text-sm font-medium text-gray-900"><?= htmlspecialchars($rep["name"]) ?></span>
                        </div>

                        <div class="flex items-center gap-4 text-sm">
                            <div class="text-center">
                                <span class="text-gray-700"><?= $rep["total_clients"] ?></span>
                                <span class="text-gray-400">/</span>
                                <span class="text-green-600"><?= $rep["stats"]["customers_ordered"] ?></span>
                            </div>
                            <div class="w-16 text-right">
                                <span class="font-bold text-orange-600"><?= number_format(
                                    $rep["stats"]["total_quantity"],
                                    0,
                                    ",",
                                    " ",
                                ) ?></span>
                            </div>
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
$pageScripts = <<<SCRIPTS
<script>
function campaignFilter() {
    const allCampaigns = {$campaignsJson};
    const currentCampaignId = '{$campaignId}';
    const currentCountry = '{$selectedCountry}';

    // Traduction des statuts
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
            // Forcer la sélection après que le DOM soit mis à jour
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

            // Reset campaign si pas dans la liste filtrée
            const campaignIds = this.filteredCampaigns.map(c => c.id.toString());
            if (this.selectedCampaign && !campaignIds.includes(this.selectedCampaign.toString())) {
                this.selectedCampaign = '';
            }
        }
    }
}
</script>
SCRIPTS;

require __DIR__ . "/../../layouts/admin.php";

?>
