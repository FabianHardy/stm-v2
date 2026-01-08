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

        <form method="POST" action="/stm/admin/stats/export" class="space-y-4" onsubmit="return startExport(this)">
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

        <form method="POST" action="/stm/admin/stats/export" class="space-y-4" onsubmit="return startExport(this)">
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

        <form method="POST" action="/stm/admin/stats/export" class="space-y-4" onsubmit="return startExport(this)">
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

        <form method="POST" action="/stm/admin/stats/export" class="space-y-4" onsubmit="return startExport(this)">
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

<!-- Export Loader Overlay -->
<div id="export-loader" class="fixed inset-0 bg-gray-900 bg-opacity-75 z-50 hidden items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl p-8 max-w-md mx-4 text-center">
        <div class="relative mb-4">
            <div id="export-spinner" class="w-20 h-20 border-4 border-green-200 border-t-green-600 rounded-full animate-spin mx-auto"></div>
            <i class="fas fa-file-excel text-green-600 text-2xl absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2"></i>
        </div>
        <h3 id="export-title" class="text-xl font-bold text-gray-900 mb-2">Génération de l'export Excel</h3>
        <p id="export-message" class="text-gray-600 mb-4">Cette opération peut prendre plusieurs secondes...</p>
        <div id="export-warning" class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-sm text-amber-800">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <strong>Ne quittez pas cette page</strong> pendant la génération.
        </div>
        <p class="text-xs text-gray-400 mt-4">Temps écoulé : <span id="export-timer">0:00</span></p>
    </div>
</div>

<?php endif; ?>

<?php
$content = ob_get_clean();
$pageScripts = <<<'JS'
<script>
// ============================================
// FILTRAGE CAMPAGNES PAR PAYS
// ============================================
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

// ============================================
// EXPORT LOADER AVEC TIMER ET COOKIE TOKEN
// ============================================
let exportTimerInterval = null;
let exportStartTime = null;
let downloadCheckInterval = null;

function startExport(form) {
    // Générer un token unique pour ce téléchargement
    const downloadToken = 'download_' + Date.now();

    // Ajouter le token au formulaire
    let tokenInput = form.querySelector('input[name="download_token"]');
    if (!tokenInput) {
        tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = 'download_token';
        form.appendChild(tokenInput);
    }
    tokenInput.value = downloadToken;

    // Adapter le message
    const titleEl = document.getElementById('export-title');
    const messageEl = document.getElementById('export-message');
    const warningEl = document.getElementById('export-warning');
    const spinnerEl = document.getElementById('export-spinner');

    titleEl.textContent = 'Génération Excel en cours...';
    messageEl.textContent = 'Cette opération peut prendre plusieurs secondes...';
    warningEl.classList.remove('hidden');
    spinnerEl.className = 'w-20 h-20 border-4 border-green-200 border-t-green-600 rounded-full animate-spin mx-auto';

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

    // Timeout de sécurité : masquer le loader après 2 minutes max
    setTimeout(function() {
        hideExportLoader();
    }, 120000);

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
</script>
JS;
require __DIR__ . '/../../layouts/admin.php';
?>