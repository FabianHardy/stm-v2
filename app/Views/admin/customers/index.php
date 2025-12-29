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
<?php
$currentCountry = $filters['country'] ?? 'BE';
$currentCluster = $filters['cluster'] ?? '';
$currentRepId = (string)($filters['rep_id'] ?? '');
$currentSort = $filters['sort'] ?? 'company_name';
$currentOrder = $filters['order'] ?? 'asc';

// Clusters et reps pour le pays actuel
$currentClusters = $allClusters[$currentCountry] ?? [];
$currentReps = $allRepresentatives[$currentCountry] ?? [];

// JSON pour la cascade JavaScript
$allClustersJson = json_encode($allClusters, JSON_HEX_APOS | JSON_HEX_QUOT);
$allRepsJson = json_encode([
    'BE' => array_map(fn($r) => ['rep_id' => (string)$r['rep_id'], 'rep_name' => $r['rep_name'], 'cluster' => $r['cluster'] ?? ''], $allRepresentatives['BE']),
    'LU' => array_map(fn($r) => ['rep_id' => (string)$r['rep_id'], 'rep_name' => $r['rep_name'], 'cluster' => $r['cluster'] ?? ''], $allRepresentatives['LU'])
], JSON_HEX_APOS | JSON_HEX_QUOT);

// Fonction pour générer l'URL de tri
function sortUrl($column, $currentSort, $currentOrder, $filters, $pagination) {
    $newOrder = ($currentSort === $column && $currentOrder === 'asc') ? 'desc' : 'asc';
    $params = [
        'country' => $filters['country'] ?? 'BE',
        'cluster' => $filters['cluster'] ?? '',
        'rep_id' => $filters['rep_id'] ?? '',
        'search' => $filters['search'] ?? '',
        'sort' => $column,
        'order' => $newOrder,
        'per_page' => $pagination['per_page']
    ];
    return '?' . http_build_query(array_filter($params, fn($v) => $v !== ''));
}

// Fonction pour l'icône de tri
function sortIcon($column, $currentSort, $currentOrder) {
    if ($currentSort !== $column) {
        return '<i class="fas fa-sort text-gray-300 ml-1"></i>';
    }
    return $currentOrder === 'asc'
        ? '<i class="fas fa-sort-up text-indigo-600 ml-1"></i>'
        : '<i class="fas fa-sort-down text-indigo-600 ml-1"></i>';
}
?>
<div class="bg-white shadow rounded-lg p-4 mb-6">
    <form method="GET" action="/stm/admin/customers" id="filterForm">
        <input type="hidden" name="sort" value="<?= htmlspecialchars($currentSort) ?>">
        <input type="hidden" name="order" value="<?= htmlspecialchars($currentOrder) ?>">
        <input type="hidden" name="per_page" id="hidden_per_page" value="<?= $pagination['per_page'] ?>">

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <!-- Pays -->
            <div>
                <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Pays</label>
                <select id="country" name="country"
                        onchange="updateFiltersOnCountryChange()"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="BE" <?= $currentCountry === 'BE' ? 'selected' : '' ?>>🇧🇪 Belgique</option>
                    <option value="LU" <?= $currentCountry === 'LU' ? 'selected' : '' ?>>🇱🇺 Luxembourg</option>
                </select>
            </div>

            <!-- Cluster -->
            <div>
                <label for="cluster" class="block text-sm font-medium text-gray-700 mb-1">Cluster</label>
                <select id="cluster" name="cluster"
                        onchange="updateFiltersOnClusterChange()"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">Tous les clusters</option>
                    <?php foreach ($currentClusters as $cl): ?>
                        <option value="<?= htmlspecialchars($cl) ?>" <?= $currentCluster === $cl ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cl) ?>
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
                    <?php foreach ($currentReps as $rep): ?>
                        <option value="<?= htmlspecialchars($rep['rep_id']) ?>"
                                data-cluster="<?= htmlspecialchars($rep['cluster'] ?? '') ?>"
                                <?= $currentRepId === (string)$rep['rep_id'] ? 'selected' : '' ?>>
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
                <a href="/stm/admin/customers"
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-times mr-2"></i>
                    Reset
                </a>
            </div>
        </div>
    </form>
</div>

<!-- JavaScript pour la cascade des filtres -->
<script>
const allClusters = <?= $allClustersJson ?>;
const allReps = <?= $allRepsJson ?>;

// Valeurs actuelles pour la restauration
const currentCluster = '<?= htmlspecialchars($currentCluster, ENT_QUOTES) ?>';
const currentRepId = '<?= htmlspecialchars($currentRepId, ENT_QUOTES) ?>';

function updateFiltersOnCountryChange() {
    const country = document.getElementById('country').value;
    const clusterSelect = document.getElementById('cluster');
    const repSelect = document.getElementById('rep_id');

    // Mettre à jour les clusters
    const clusters = allClusters[country] || [];
    clusterSelect.innerHTML = '<option value="">Tous les clusters</option>';
    clusters.forEach(cl => {
        const option = document.createElement('option');
        option.value = cl;
        option.textContent = cl;
        clusterSelect.appendChild(option);
    });

    // Mettre à jour les représentants
    const reps = allReps[country] || [];
    repSelect.innerHTML = '<option value="">Tous les représentants</option>';
    reps.forEach(rep => {
        const option = document.createElement('option');
        option.value = rep.rep_id;
        option.textContent = rep.rep_name + (rep.cluster ? ' (' + rep.cluster + ')' : '');
        option.setAttribute('data-cluster', rep.cluster || '');
        repSelect.appendChild(option);
    });
}

function updateFiltersOnClusterChange() {
    const country = document.getElementById('country').value;
    const cluster = document.getElementById('cluster').value;
    const repSelect = document.getElementById('rep_id');

    // Filtrer les représentants par cluster
    const reps = allReps[country] || [];
    repSelect.innerHTML = '<option value="">Tous les représentants</option>';

    reps.forEach(rep => {
        if (!cluster || rep.cluster === cluster) {
            const option = document.createElement('option');
            option.value = rep.rep_id;
            option.textContent = rep.rep_name + (!cluster && rep.cluster ? ' (' + rep.cluster + ')' : '');
            option.setAttribute('data-cluster', rep.cluster || '');
            repSelect.appendChild(option);
        }
    });
}

function changePerPage(value) {
    // Mettre à jour le champ hidden et soumettre
    document.getElementById('hidden_per_page').value = value;
    document.getElementById('filterForm').submit();
}

// Fonction pour restaurer les filtres
function restoreFilters() {
    if (currentCluster) {
        document.getElementById('cluster').value = currentCluster;
    }
    if (currentRepId) {
        document.getElementById('rep_id').value = currentRepId;
    }
}

// Désactiver filters-persist pour cette page
window.skipFiltersPersist = true;

// Restaurer immédiatement
restoreFilters();

// Restaurer après délais croissants (pour contrer tout script externe)
setTimeout(restoreFilters, 10);
setTimeout(restoreFilters, 50);
setTimeout(restoreFilters, 100);
setTimeout(restoreFilters, 200);
setTimeout(restoreFilters, 500);
</script>

<!-- Tableau des clients -->
<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
        <span class="text-sm text-gray-600">
            <?php if ($pagination['total'] > 0): ?>
                Affichage de <?= number_format((($pagination['current_page'] - 1) * $pagination['per_page']) + 1, 0, ',', ' ') ?>
                à <?= number_format(min($pagination['current_page'] * $pagination['per_page'], $pagination['total']), 0, ',', ' ') ?>
                sur <?= number_format($pagination['total'], 0, ',', ' ') ?> client(s)
            <?php else: ?>
                Aucun client trouvé
            <?php endif; ?>
        </span>
        <div class="flex items-center gap-2">
            <label for="per_page_select" class="text-sm text-gray-600">Afficher :</label>
            <select id="per_page_select" onchange="changePerPage(this.value)"
                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-1">
                <?php foreach ([10, 25, 50, 100] as $pp): ?>
                    <option value="<?= $pp ?>" <?= $pagination['per_page'] == $pp ? 'selected' : '' ?>><?= $pp ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <a href="<?= sortUrl('company_name', $currentSort, $currentOrder, $filters, $pagination) ?>" class="flex items-center hover:text-indigo-600">
                            Client <?= sortIcon('company_name', $currentSort, $currentOrder) ?>
                        </a>
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <a href="<?= sortUrl('rep_name', $currentSort, $currentOrder, $filters, $pagination) ?>" class="flex items-center hover:text-indigo-600">
                            Représentant <?= sortIcon('rep_name', $currentSort, $currentOrder) ?>
                        </a>
                    </th>
                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <a href="<?= sortUrl('last_order_date', $currentSort, $currentOrder, $filters, $pagination) ?>" class="flex items-center justify-center hover:text-indigo-600">
                            Dernière CMD <?= sortIcon('last_order_date', $currentSort, $currentOrder) ?>
                        </a>
                    </th>
                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <a href="<?= sortUrl('campaigns_count', $currentSort, $currentOrder, $filters, $pagination) ?>" class="flex items-center justify-center hover:text-indigo-600">
                            Campagnes <?= sortIcon('campaigns_count', $currentSort, $currentOrder) ?>
                        </a>
                    </th>
                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <a href="<?= sortUrl('orders_count', $currentSort, $currentOrder, $filters, $pagination) ?>" class="flex items-center justify-center hover:text-indigo-600">
                            Commandes <?= sortIcon('orders_count', $currentSort, $currentOrder) ?>
                        </a>
                    </th>
                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <a href="<?= sortUrl('total_quantity', $currentSort, $currentOrder, $filters, $pagination) ?>" class="flex items-center justify-center hover:text-indigo-600">
                            Promos <?= sortIcon('total_quantity', $currentSort, $currentOrder) ?>
                        </a>
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

<!-- Pagination -->
<?php if ($pagination['total_pages'] > 1):
    // Construire les paramètres de filtre pour les liens de pagination
    $filterParams = http_build_query(array_filter([
        'country' => $filters['country'] ?? '',
        'cluster' => $filters['cluster'] ?? '',
        'rep_id' => $filters['rep_id'] ?? '',
        'search' => $filters['search'] ?? '',
        'sort' => $filters['sort'] ?? '',
        'order' => $filters['order'] ?? '',
        'per_page' => $pagination['per_page'] != 50 ? $pagination['per_page'] : ''
    ], fn($v) => $v !== ''));
?>
<div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6 mt-6 rounded-lg shadow">
    <!-- Mobile -->
    <div class="flex-1 flex justify-between sm:hidden">
        <?php if ($pagination['current_page'] > 1): ?>
            <a href="?page=<?= $pagination['current_page'] - 1 ?><?= $filterParams ? '&' . $filterParams : '' ?>"
               class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                Précédent
            </a>
        <?php endif; ?>
        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
            <a href="?page=<?= $pagination['current_page'] + 1 ?><?= $filterParams ? '&' . $filterParams : '' ?>"
               class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                Suivant
            </a>
        <?php endif; ?>
    </div>

    <!-- Desktop -->
    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-gray-700">
                Page <span class="font-medium"><?= $pagination['current_page'] ?></span>
                sur <span class="font-medium"><?= $pagination['total_pages'] ?></span>
                (<?= number_format($pagination['total'], 0, ',', ' ') ?> résultats)
            </p>
        </div>
        <div>
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <!-- Première page -->
                <?php if ($pagination['current_page'] > 1): ?>
                    <a href="?page=1<?= $filterParams ? '&' . $filterParams : '' ?>"
                       class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                       title="Première page">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                    <a href="?page=<?= $pagination['current_page'] - 1 ?><?= $filterParams ? '&' . $filterParams : '' ?>"
                       class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                       title="Page précédente">
                        <i class="fas fa-angle-left"></i>
                    </a>
                <?php endif; ?>

                <!-- Numéros de page -->
                <?php
                $startPage = max(1, $pagination['current_page'] - 2);
                $endPage = min($pagination['total_pages'], $pagination['current_page'] + 2);

                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                    <a href="?page=<?= $i ?><?= $filterParams ? '&' . $filterParams : '' ?>"
                       class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?= $i === $pagination['current_page'] ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <!-- Dernière page -->
                <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                    <a href="?page=<?= $pagination['current_page'] + 1 ?><?= $filterParams ? '&' . $filterParams : '' ?>"
                       class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                       title="Page suivante">
                        <i class="fas fa-angle-right"></i>
                    </a>
                    <a href="?page=<?= $pagination['total_pages'] ?><?= $filterParams ? '&' . $filterParams : '' ?>"
                       class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                       title="Dernière page">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
$title = 'Clients';
require __DIR__ . '/../../layouts/admin.php';
?>