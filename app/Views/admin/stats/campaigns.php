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
<div class="bg-white rounded-lg shadow-sm p-4 mb-6" x-data="campaignFilter()">
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
            <select name="campaign_id" x-model="selectedCampaign"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <option value="">-- Choisir une campagne --</option>
                <template x-for="c in filteredCampaigns" :key="c.id">
                    <option :value="c.id" x-text="c.name + ' (' + c.country + ' - ' + getStatusLabel(c.status) + ')'"></option>
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

<!-- Stats par pays -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

    <!-- Belgique -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                <span class="text-lg">🇧🇪</span>
            </div>
            <h3 class="text-lg font-semibold">Belgique</h3>
        </div>

        <div class="grid grid-cols-3 gap-4 text-center">
            <div>
                <p class="text-2xl font-bold text-gray-900"><?= $campaignStats["by_country"]["BE"]["orders"] ?></p>
                <p class="text-xs text-gray-500">Commandes</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900"><?= $campaignStats["by_country"]["BE"]["customers"] ?></p>
                <p class="text-xs text-gray-500">Clients</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-blue-600"><?= number_format(
                    $campaignStats["by_country"]["BE"]["quantity"],
                    0,
                    ",",
                    " ",
                ) ?></p>
                <p class="text-xs text-gray-500">Quantités</p>
            </div>
        </div>
    </div>

    <!-- Luxembourg -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                <span class="text-lg">🇱🇺</span>
            </div>
            <h3 class="text-lg font-semibold">Luxembourg</h3>
        </div>

        <div class="grid grid-cols-3 gap-4 text-center">
            <div>
                <p class="text-2xl font-bold text-gray-900"><?= $campaignStats["by_country"]["LU"]["orders"] ?></p>
                <p class="text-xs text-gray-500">Commandes</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900"><?= $campaignStats["by_country"]["LU"]["customers"] ?></p>
                <p class="text-xs text-gray-500">Clients</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-yellow-600"><?= number_format(
                    $campaignStats["by_country"]["LU"]["quantity"],
                    0,
                    ",",
                    " ",
                ) ?></p>
                <p class="text-xs text-gray-500">Quantités</p>
            </div>
        </div>
    </div>
</div>

<!-- Produits et Clients sans commande -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- Produits de la campagne -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Produits de la campagne</h3>

        <?php if (empty($campaignProducts)): ?>
        <p class="text-gray-500 text-center py-4">Aucun produit</p>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="text-left text-xs text-gray-500 uppercase">
                        <th class="pb-2">Produit</th>
                        <th class="pb-2 text-right">Qté vendue</th>
                        <th class="pb-2 text-right">Commandes</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    <?php foreach ($campaignProducts as $product): ?>
                    <tr class="border-t border-gray-100">
                        <td class="py-2">
                            <p class="font-medium"><?= htmlspecialchars($product["product_name"]) ?></p>
                            <p class="text-xs text-gray-500"><?= htmlspecialchars($product["product_code"]) ?></p>
                        </td>
                        <td class="py-2 text-right font-bold"><?= number_format(
                            $product["quantity_sold"],
                            0,
                            ",",
                            " ",
                        ) ?></td>
                        <td class="py-2 text-right text-gray-600"><?= $product["orders_count"] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Clients n'ayant pas commandé -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Clients sans commande</h3>
            <?php if (!empty($customersNotOrdered)): ?>
            <form method="POST" action="/stm/admin/stats/export" class="inline">
                <input type="hidden" name="type" value="not_ordered">
                <input type="hidden" name="campaign_id" value="<?= $campaignId ?>">
                <input type="hidden" name="format" value="csv">
                <button type="submit" class="text-sm text-indigo-600 hover:text-indigo-800">
                    <i class="fas fa-download mr-1"></i>Exporter
                </button>
            </form>
            <?php endif; ?>
        </div>

        <?php if (empty($customersNotOrdered)): ?>
        <p class="text-gray-500 text-center py-4">Tous les clients ont commandé ! 🎉</p>
        <?php else: ?>
        <div class="overflow-x-auto max-h-96">
            <table class="min-w-full">
                <thead class="sticky top-0 bg-white">
                    <tr class="text-left text-xs text-gray-500 uppercase">
                        <th class="pb-2">Client</th>
                        <th class="pb-2">Pays</th>
                        <th class="pb-2">Représentant</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    <?php foreach ($customersNotOrdered as $customer): ?>
                    <tr class="border-t border-gray-100">
                        <td class="py-2">
                            <p class="font-medium"><?= htmlspecialchars($customer["company_name"] ?? "-") ?></p>
                            <p class="text-xs text-gray-500"><?= htmlspecialchars($customer["customer_number"]) ?></p>
                        </td>
                        <td class="py-2">
                            <span class="px-2 py-1 rounded text-xs <?= $customer["country"] === "BE"
                                ? "bg-blue-100 text-blue-800"
                                : "bg-yellow-100 text-yellow-800" ?>">
                                <?= $customer["country"] ?>
                            </span>
                        </td>
                        <td class="py-2 text-gray-600"><?= htmlspecialchars($customer["rep_name"] ?? "-") ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="text-xs text-gray-400 mt-2">Affichage limité à 50 clients. Exportez pour la liste complète.</p>
        <?php endif; ?>
    </div>
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
            // Restaurer la sélection après le filtrage
            this.\$nextTick(() => {
                if (currentCampaignId) {
                    this.selectedCampaign = currentCampaignId;
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
