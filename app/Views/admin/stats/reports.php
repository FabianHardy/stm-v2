<?php
/**
 * Vue : Statistiques - Rapports et exports
 *
 * Page d'export Excel des données
 *
 * @package STM
 * @created 2025/11/25
 * @modified 2025/12/16 - Ajout filtrage permissions sur exports
 * @modified 2026/01/08 - Excel uniquement, ajout filtre pays, colonnes Origine et %_Via_Reps
 */

use Core\Session;
use App\Helpers\PermissionHelper;

// Permission pour les exports
$canExport = PermissionHelper::can('stats.export');

$flash = Session::getFlash('error');

ob_start();
?>

<!-- En-tête -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Statistiques - Rapports</h1>
    <p class="text-gray-600 mt-1">Exportez vos données au format Excel</p>
</div>

<?php if ($flash): ?>
<div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
    <div class="flex items-center">
        <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
        <p class="text-red-700"><?= htmlspecialchars($flash) ?></p>
    </div>
</div>
<?php endif; ?>

<?php if (!$canExport): ?>
<!-- Message si pas de permission d'export -->
<div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6">
    <div class="flex items-center">
        <i class="fas fa-lock text-yellow-500 mr-2"></i>
        <p class="text-yellow-700">Vous n'avez pas les permissions nécessaires pour exporter les données.</p>
    </div>
</div>
<?php else: ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- Export global -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                <i class="fas fa-globe text-indigo-600"></i>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Export global</h3>
                <p class="text-sm text-gray-500">Toutes les commandes sur une période</p>
            </div>
        </div>

        <form method="POST" action="/stm/admin/stats/export" class="space-y-4">
            <input type="hidden" name="type" value="global">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date début</label>
                    <input type="date" name="date_from" value="<?= date('Y-m-d', strtotime('-14 days')) ?>"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date fin</label>
                    <input type="date" name="date_to" value="<?= date('Y-m-d') ?>"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pays</label>
                    <select onchange="filterCampaigns(this, 'global_campaign')" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="">Tous les pays</option>
                        <option value="BE">🇧🇪 Belgique</option>
                        <option value="LU">🇱🇺 Luxembourg</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Campagne</label>
                    <select name="campaign_id" id="global_campaign" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="">Toutes les campagnes</option>
                        <?php foreach ($campaigns as $c): ?>
                        <option value="<?= $c['id'] ?>" data-country="<?= $c['country'] ?>">
                            <?= $c['country'] === 'BE' ? '🇧🇪' : '🇱🇺' ?> <?= htmlspecialchars($c['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700 transition">
                <i class="fas fa-download mr-2"></i>Télécharger Excel
            </button>
        </form>

        <div class="mt-4 text-xs text-gray-500">
            <p><strong>Colonnes:</strong> Num_Client, Nom, Pays, Promo_Art, Nom_Produit, Quantité, Rep_Name, Cluster, Origine, Date_Commande</p>
        </div>
    </div>

    <!-- Export par campagne -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-bullhorn text-green-600"></i>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Export par campagne</h3>
                <p class="text-sm text-gray-500">Commandes d'une campagne spécifique</p>
            </div>
        </div>

        <form method="POST" action="/stm/admin/stats/export" class="space-y-4">
            <input type="hidden" name="type" value="campaign">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pays <span class="text-red-500">*</span></label>
                    <select onchange="filterCampaigns(this, 'campaign_campaign', true)" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="">-- Sélectionner --</option>
                        <option value="BE">🇧🇪 Belgique</option>
                        <option value="LU">🇱🇺 Luxembourg</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Campagne <span class="text-red-500">*</span></label>
                    <select name="campaign_id" id="campaign_campaign" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="">-- Choisir un pays --</option>
                        <?php foreach ($campaigns as $c): ?>
                        <option value="<?= $c['id'] ?>" data-country="<?= $c['country'] ?>" style="display:none;">
                            <?= htmlspecialchars($c['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <button type="submit" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-download mr-2"></i>Télécharger Excel
            </button>
        </form>

        <div class="mt-4 text-xs text-gray-500">
            <p><strong>Colonnes:</strong> Num_Client, Nom, Pays, Promo_Art, Nom_Produit, Quantité, Email, Rep_Name, Origine, Date_Commande</p>
        </div>
    </div>

    <!-- Export représentants -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                <i class="fas fa-users text-orange-600"></i>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Export représentants</h3>
                <p class="text-sm text-gray-500">Stats par commercial</p>
            </div>
        </div>

        <form method="POST" action="/stm/admin/stats/export" class="space-y-4">
            <input type="hidden" name="type" value="reps">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pays</label>
                    <select onchange="filterCampaigns(this, 'reps_campaign')" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="">Tous les pays</option>
                        <option value="BE">🇧🇪 Belgique</option>
                        <option value="LU">🇱🇺 Luxembourg</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Campagne</label>
                    <select name="campaign_id" id="reps_campaign" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="">Toutes les campagnes</option>
                        <?php foreach ($campaigns as $c): ?>
                        <option value="<?= $c['id'] ?>" data-country="<?= $c['country'] ?>">
                            <?= $c['country'] === 'BE' ? '🇧🇪' : '🇱🇺' ?> <?= htmlspecialchars($c['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <button type="submit" class="w-full bg-orange-600 text-white py-2 rounded-lg hover:bg-orange-700 transition">
                <i class="fas fa-download mr-2"></i>Télécharger Excel
            </button>
        </form>

        <div class="mt-4 text-xs text-gray-500">
            <p><strong>Colonnes:</strong> Rep_ID, Rep_Nom, Cluster, Pays, Nb_Clients, Clients_Commandé, Taux_Conv, Total_Quantité, %_Via_Reps</p>
        </div>
    </div>

    <!-- Export clients sans commande -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                <i class="fas fa-user-times text-red-600"></i>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Clients sans commande</h3>
                <p class="text-sm text-gray-500">Liste des clients n'ayant pas commandé</p>
            </div>
        </div>

        <form method="POST" action="/stm/admin/stats/export" class="space-y-4">
            <input type="hidden" name="type" value="not_ordered">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pays <span class="text-red-500">*</span></label>
                    <select onchange="filterCampaigns(this, 'notordered_campaign', true)" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="">-- Sélectionner --</option>
                        <option value="BE">🇧🇪 Belgique</option>
                        <option value="LU">🇱🇺 Luxembourg</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Campagne <span class="text-red-500">*</span></label>
                    <select name="campaign_id" id="notordered_campaign" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="">-- Choisir un pays --</option>
                        <?php foreach ($campaigns as $c): ?>
                        <option value="<?= $c['id'] ?>" data-country="<?= $c['country'] ?>" style="display:none;">
                            <?= htmlspecialchars($c['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <button type="submit" class="w-full bg-red-600 text-white py-2 rounded-lg hover:bg-red-700 transition">
                <i class="fas fa-download mr-2"></i>Télécharger Excel
            </button>
        </form>

        <div class="mt-4 text-xs text-gray-500">
            <p><strong>Colonnes:</strong> Num_Client, Nom, Pays, Rep_Name</p>
        </div>
    </div>
</div>

<?php endif; ?>

<?php
$content = ob_get_clean();
$pageScripts = <<<'JS'
<script>
function filterCampaigns(countrySelect, campaignSelectId, required = false) {
    const country = countrySelect.value;
    const campaignSelect = document.getElementById(campaignSelectId);
    const options = campaignSelect.querySelectorAll('option[data-country]');

    // Reset la sélection
    campaignSelect.value = '';

    // Mettre à jour la première option
    const firstOption = campaignSelect.querySelector('option:first-child');
    if (required) {
        firstOption.textContent = country ? '-- Sélectionner --' : '-- Choisir un pays --';
    }

    // Filtrer les options
    options.forEach(option => {
        const optionCountry = option.getAttribute('data-country');
        if (!country || optionCountry === country) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
        }
    });
}
</script>
JS;
require __DIR__ . '/../../layouts/admin.php';
?>