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
// Préparer les données pour Alpine.js
$clustersJson = json_encode($clusters, JSON_HEX_APOS | JSON_HEX_QUOT);
$representativesJson = json_encode(array_map(function($r) {
    return [
        'rep_id' => (string)$r['rep_id'],
        'rep_name' => $r['rep_name'],
        'cluster' => $r['cluster'] ?? ''
    ];
}, $representatives), JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<div class="bg-white shadow rounded-lg p-4 mb-6"
     x-data="customerFilters()"
     x-init="init()">
    <form method="GET" action="/stm/admin/customers" id="filterForm">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <!-- Pays -->
            <div>
                <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Pays</label>
                <select id="country" name="country"
                        x-model="country"
                        @change="onCountryChange()"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="BE">🇧🇪 Belgique</option>
                    <option value="LU">🇱🇺 Luxembourg</option>
                </select>
            </div>

            <!-- Cluster -->
            <div>
                <label for="cluster" class="block text-sm font-medium text-gray-700 mb-1">Cluster</label>
                <select id="cluster" name="cluster"
                        x-model="cluster"
                        @change="onClusterChange()"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">Tous les clusters</option>
                    <template x-for="c in availableClusters" :key="c">
                        <option :value="c" x-text="c"></option>
                    </template>
                </select>
            </div>

            <!-- Représentant -->
            <div>
                <label for="rep_id" class="block text-sm font-medium text-gray-700 mb-1">Représentant</label>
                <select id="rep_id" name="rep_id"
                        x-model="repId"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">Tous les représentants</option>
                    <template x-for="rep in availableReps" :key="rep.rep_id">
                        <option :value="rep.rep_id" x-text="rep.rep_name + (rep.cluster && !cluster ? ' (' + rep.cluster + ')' : '')"></option>
                    </template>
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

<script>
function customerFilters() {
    return {
        // Valeurs sélectionnées (depuis PHP/URL)
        country: '<?= htmlspecialchars($filters['country'] ?? 'BE', ENT_QUOTES) ?>',
        cluster: '<?= htmlspecialchars($filters['cluster'] ?? '', ENT_QUOTES) ?>',
        repId: '<?= htmlspecialchars($filters['rep_id'] ?? '', ENT_QUOTES) ?>',

        // Données brutes (chargées depuis PHP)
        allClusters: <?= $clustersJson ?>,
        allReps: <?= $representativesJson ?>,

        // Listes filtrées
        availableClusters: [],
        availableReps: [],

        init() {
            this.updateAvailableClusters();
            this.updateAvailableReps();
        },

        // Quand le pays change → recharger clusters et reps via AJAX
        onCountryChange() {
            // Réinitialiser les sélections
            this.cluster = '';
            this.repId = '';

            // Charger les nouveaux clusters
            fetch('/stm/admin/customers/api/clusters?country=' + this.country)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        this.allClusters = data.clusters;
                        this.updateAvailableClusters();
                    }
                });

            // Charger les nouveaux représentants
            fetch('/stm/admin/customers/api/representatives?country=' + this.country)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        this.allReps = data.representatives.map(r => ({
                            rep_id: String(r.rep_id),
                            rep_name: r.rep_name,
                            cluster: r.cluster || ''
                        }));
                        this.updateAvailableReps();
                    }
                });
        },

        // Quand le cluster change → filtrer les reps
        onClusterChange() {
            // Vérifier si le rep actuel est encore valide
            if (this.repId && !this.availableReps.find(r => r.rep_id === this.repId)) {
                this.repId = '';
            }
            this.updateAvailableReps();
        },

        // Mettre à jour les clusters disponibles
        updateAvailableClusters() {
            this.availableClusters = this.allClusters;
        },

        // Mettre à jour les représentants disponibles selon le cluster
        updateAvailableReps() {
            if (this.cluster) {
                this.availableReps = this.allReps.filter(r => r.cluster === this.cluster);
            } else {
                this.availableReps = this.allReps;
            }

            // Trier par prénom
            this.availableReps.sort((a, b) => a.rep_name.localeCompare(b.rep_name));
        }
    };
}
</script>

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
require __DIR__ . '/../../layouts/admin.php';
?>