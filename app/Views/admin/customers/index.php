<?php
/**
 * Vue : Liste des clients (consultation)
 *
 * Affiche la liste des clients depuis la DB externe avec filtres en cascade
 * et statistiques de commandes enrichies depuis la base locale.
 *
 * @package STM/Views/Admin/Customers
 * @version 3.0
 * @created 12/11/2025 19:30
 * @modified 29/12/2025 - Refonte complète en mode consultation
 */

use Core\Session;

$pageTitle = 'Clients';
ob_start();
?>

<!-- En-tête -->
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Clients</h1>
            <p class="mt-2 text-sm text-gray-600">
                Consultation des clients depuis la base externe
            </p>
        </div>
    </div>

    <!-- Breadcrumb -->
    <nav class="mt-4 flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="/stm/admin/dashboard" class="text-gray-700 hover:text-gray-900">
                    🏠 Dashboard
                </a>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <span class="mx-2 text-gray-400">/</span>
                    <span class="text-gray-500">Clients</span>
                </div>
            </li>
        </ol>
    </nav>
</div>

<!-- Messages flash -->
<?php if ($flashSuccess = Session::getFlash('success')): ?>
    <div class="mb-4 rounded-md bg-green-50 p-4">
        <p class="text-sm font-medium text-green-800"><?= htmlspecialchars($flashSuccess) ?></p>
    </div>
<?php endif; ?>

<?php if ($flashError = Session::getFlash('error')): ?>
    <div class="mb-4 rounded-md bg-red-50 p-4">
        <p class="text-sm font-medium text-red-800"><?= htmlspecialchars($flashError) ?></p>
    </div>
<?php endif; ?>

<!-- Statistiques rapides -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-3 mb-6">
    <!-- Total clients DB externe -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <span class="text-3xl">👥</span>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">
                            Clients <?= $filters['country'] ?>
                        </dt>
                        <dd class="text-2xl font-bold text-gray-900">
                            <?= number_format($stats['total_external'] ?? 0, 0, ',', ' ') ?>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Clients avec commandes -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <span class="text-3xl">🛒</span>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Clients avec commandes</dt>
                        <dd class="text-2xl font-bold text-green-600">
                            <?= number_format($stats['total_with_orders'] ?? 0, 0, ',', ' ') ?>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Total commandes -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <span class="text-3xl">📦</span>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Total commandes</dt>
                        <dd class="text-2xl font-bold text-indigo-600">
                            <?= number_format($stats['total_orders'] ?? 0, 0, ',', ' ') ?>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="bg-white shadow rounded-lg p-4 mb-6">
    <form method="GET" action="/stm/admin/customers" id="filterForm">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <!-- Pays -->
            <div>
                <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Pays</label>
                <select id="country" name="country"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        onchange="updateCascadeFilters()">
                    <option value="BE" <?= ($filters['country'] ?? 'BE') === 'BE' ? 'selected' : '' ?>>🇧🇪 Belgique</option>
                    <option value="LU" <?= ($filters['country'] ?? '') === 'LU' ? 'selected' : '' ?>>🇱🇺 Luxembourg</option>
                </select>
            </div>

            <!-- Cluster -->
            <div>
                <label for="cluster" class="block text-sm font-medium text-gray-700 mb-1">Cluster</label>
                <select id="cluster" name="cluster"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        onchange="updateRepresentatives()">
                    <option value="">Tous les clusters</option>
                    <?php foreach ($clusters as $cluster): ?>
                        <option value="<?= htmlspecialchars($cluster) ?>" <?= (string)($filters['cluster'] ?? '') === (string)$cluster ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cluster) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Représentant -->
            <div>
                <label for="rep_id" class="block text-sm font-medium text-gray-700 mb-1">Représentant</label>
                <select id="rep_id" name="rep_id"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">Tous les représentants</option>
                    <?php foreach ($representatives as $rep): ?>
                        <option value="<?= htmlspecialchars($rep['rep_id']) ?>" <?= (string)($filters['rep_id'] ?? '') === (string)$rep['rep_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($rep['rep_name']) ?>
                            <?php if (!empty($rep['cluster'])): ?>
                                (<?= htmlspecialchars($rep['cluster']) ?>)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Recherche -->
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Recherche</label>
                <input type="text" id="search" name="search"
                       value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                       placeholder="N° client ou nom..."
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>

            <!-- Boutons -->
            <div class="flex items-end gap-2">
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                    <i class="fas fa-search mr-2"></i>
                    Rechercher
                </button>
                <a href="/stm/admin/customers?country=<?= $filters['country'] ?? 'BE' ?>"
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-times mr-2"></i>
                    Reset
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Tableau des clients -->
<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
        <span class="text-sm text-gray-600">
            <?= count($customers) ?> client(s) affiché(s)
            <?php if (count($customers) >= 500): ?>
                <span class="text-orange-600">(limité à 500 résultats)</span>
            <?php endif; ?>
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Client
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Représentant
                    </th>
                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Dernière CMD
                    </th>
                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Campagnes
                    </th>
                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Commandes
                    </th>
                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Promos
                    </th>
                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="text-gray-400">
                                <i class="fas fa-users text-4xl mb-3"></i>
                                <p class="text-lg font-medium">Aucun client trouvé</p>
                                <p class="text-sm">Modifiez vos filtres pour voir des résultats</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($customers as $customer): ?>
                        <tr class="hover:bg-gray-50">
                            <!-- Client -->
                            <td class="px-4 py-3">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                        <span class="text-indigo-600 font-medium text-sm">
                                            <?= strtoupper(substr($customer['company_name'] ?? '?', 0, 2)) ?>
                                        </span>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($customer['company_name'] ?? 'N/A') ?>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            <?= $customer['country'] === 'BE' ? '🇧🇪' : '🇱🇺' ?>
                                            <?= htmlspecialchars($customer['customer_number']) ?>
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <!-- Représentant -->
                            <td class="px-4 py-3">
                                <p class="text-sm text-gray-900"><?= htmlspecialchars($customer['rep_name'] ?? '-') ?></p>
                                <?php if (!empty($customer['cluster'])): ?>
                                    <p class="text-xs text-gray-500"><?= htmlspecialchars($customer['cluster']) ?></p>
                                <?php endif; ?>
                            </td>

                            <!-- Dernière commande -->
                            <td class="px-4 py-3 text-center">
                                <?php if ($customer['last_order_date']): ?>
                                    <span class="text-sm text-gray-900">
                                        <?= date('d/m/Y', strtotime($customer['last_order_date'])) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400">-</span>
                                <?php endif; ?>
                            </td>

                            <!-- Campagnes -->
                            <td class="px-4 py-3 text-center">
                                <?php if ($customer['campaigns_count'] > 0): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700">
                                        <?= $customer['campaigns_count'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400">0</span>
                                <?php endif; ?>
                            </td>

                            <!-- Commandes -->
                            <td class="px-4 py-3 text-center">
                                <?php if ($customer['orders_count'] > 0): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">
                                        <?= $customer['orders_count'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400">0</span>
                                <?php endif; ?>
                            </td>

                            <!-- Promos -->
                            <td class="px-4 py-3 text-center">
                                <?php if ($customer['total_quantity'] > 0): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-orange-100 text-orange-700">
                                        <?= number_format($customer['total_quantity'], 0, ',', ' ') ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400">0</span>
                                <?php endif; ?>
                            </td>

                            <!-- Actions -->
                            <td class="px-4 py-3 text-center">
                                <a href="/stm/admin/customers/show?customer_number=<?= urlencode($customer['customer_number']) ?>&country=<?= $customer['country'] ?>"
                                   class="inline-flex items-center px-2 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs transition">
                                    <i class="fas fa-eye mr-1"></i>
                                    Détail
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
$title = 'Clients';

// JavaScript pour les filtres en cascade
$currentCluster = htmlspecialchars($filters['cluster'] ?? '', ENT_QUOTES);
$currentRepId = htmlspecialchars($filters['rep_id'] ?? '', ENT_QUOTES);

$pageScripts = <<<SCRIPTS
<script>
// Valeurs actuelles des filtres (depuis PHP/URL)
const currentCluster = "{$currentCluster}";
const currentRepId = "{$currentRepId}";

/**
 * Force la restauration des filtres après un court délai
 * pour contrer d'éventuels scripts qui écrasent les valeurs
 */
setTimeout(function() {
    if (currentCluster) {
        document.getElementById('cluster').value = currentCluster;
    }
    if (currentRepId) {
        document.getElementById('rep_id').value = currentRepId;
    }
}, 100);

/**
 * Met à jour les clusters ET les représentants quand le pays change
 * Réinitialise les filtres cluster et rep
 */
function updateCascadeFilters() {
    const country = document.getElementById('country').value;

    // Reset et désactiver les selects pendant le chargement
    const clusterSelect = document.getElementById('cluster');
    const repSelect = document.getElementById('rep_id');

    clusterSelect.innerHTML = '<option value="">Chargement...</option>';
    clusterSelect.disabled = true;
    repSelect.innerHTML = '<option value="">Chargement...</option>';
    repSelect.disabled = true;

    // Charger les clusters
    fetch('/stm/admin/customers/api/clusters?country=' + country)
        .then(response => response.json())
        .then(data => {
            clusterSelect.innerHTML = '<option value="">Tous les clusters</option>';
            if (data.success && data.clusters) {
                data.clusters.forEach(cluster => {
                    clusterSelect.innerHTML += '<option value="' + escapeHtml(cluster) + '">' + escapeHtml(cluster) + '</option>';
                });
            }
            clusterSelect.disabled = false;
        })
        .catch(error => {
            console.error('Erreur chargement clusters:', error);
            clusterSelect.innerHTML = '<option value="">Tous les clusters</option>';
            clusterSelect.disabled = false;
        });

    // Charger les représentants (tous, sans filtre cluster)
    fetch('/stm/admin/customers/api/representatives?country=' + country)
        .then(response => response.json())
        .then(data => {
            repSelect.innerHTML = '<option value="">Tous les représentants</option>';
            if (data.success && data.representatives) {
                data.representatives.forEach(rep => {
                    let label = rep.rep_name;
                    if (rep.cluster) {
                        label += ' (' + rep.cluster + ')';
                    }
                    repSelect.innerHTML += '<option value="' + escapeHtml(rep.rep_id) + '">' + escapeHtml(label) + '</option>';
                });
            }
            repSelect.disabled = false;
        })
        .catch(error => {
            console.error('Erreur chargement représentants:', error);
            repSelect.innerHTML = '<option value="">Tous les représentants</option>';
            repSelect.disabled = false;
        });
}

/**
 * Met à jour les représentants quand le cluster change
 * Conserve le rep_id actuel si possible
 */
function updateRepresentatives() {
    const country = document.getElementById('country').value;
    const cluster = document.getElementById('cluster').value;
    const repSelect = document.getElementById('rep_id');
    const previousRepId = repSelect.value; // Garder la valeur actuelle

    repSelect.innerHTML = '<option value="">Chargement...</option>';
    repSelect.disabled = true;

    let url = '/stm/admin/customers/api/representatives?country=' + country;
    if (cluster) {
        url += '&cluster=' + encodeURIComponent(cluster);
    }

    fetch(url)
        .then(response => response.json())
        .then(data => {
            repSelect.innerHTML = '<option value="">Tous les représentants</option>';

            if (data.success && data.representatives) {
                data.representatives.forEach(rep => {
                    let label = rep.rep_name;
                    if (rep.cluster && !cluster) {
                        label += ' (' + rep.cluster + ')';
                    }
                    const selected = (rep.rep_id === previousRepId) ? ' selected' : '';
                    repSelect.innerHTML += '<option value="' + escapeHtml(rep.rep_id) + '"' + selected + '>' + escapeHtml(label) + '</option>';
                });
            }
            repSelect.disabled = false;
        })
        .catch(error => {
            console.error('Erreur chargement représentants:', error);
            repSelect.innerHTML = '<option value="">Tous les représentants</option>';
            repSelect.disabled = false;
        });
}

/**
 * Échappe les caractères HTML
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
SCRIPTS;

require __DIR__ . '/../../layouts/admin.php';
?>