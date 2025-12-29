<?php
/**
 * Vue Admin - Liste des commandes
 * 
 * @created  2025/12/29 15:00
 */

use App\Models\Order;

ob_start();
?>

<!-- En-tête avec stats -->
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($pageTitle) ?></h1>
            <p class="mt-1 text-sm text-gray-500">Gestion des commandes clients</p>
        </div>
    </div>
</div>

<!-- Cartes statistiques -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <!-- Total commandes -->
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-indigo-100 rounded-lg p-3">
                <i class="fas fa-shopping-cart text-indigo-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Total commandes</p>
                <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['total_orders'], 0, ',', ' ') ?></p>
            </div>
        </div>
    </div>

    <!-- Aujourd'hui -->
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                <i class="fas fa-calendar-day text-blue-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Aujourd'hui</p>
                <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['today_count'], 0, ',', ' ') ?></p>
            </div>
        </div>
    </div>

    <!-- En attente -->
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-yellow-100 rounded-lg p-3">
                <i class="fas fa-clock text-yellow-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">En attente</p>
                <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['pending_count'], 0, ',', ' ') ?></p>
            </div>
        </div>
    </div>

    <!-- Erreurs -->
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-red-100 rounded-lg p-3">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Erreurs</p>
                <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['error_count'], 0, ',', ' ') ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Filtres -->
<?php if (empty($isToday) && empty($isPending)): ?>
<div class="bg-white shadow rounded-lg p-4 mb-6">
    <form method="GET" action="" id="filterForm">
        <input type="hidden" name="per_page" id="hidden_per_page" value="<?= $pagination['per_page'] ?>">
        
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-7">
            <!-- Campagne -->
            <div>
                <label for="campaign_id" class="block text-sm font-medium text-gray-700 mb-1">Campagne</label>
                <select id="campaign_id" name="campaign_id" 
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">Toutes</option>
                    <?php foreach ($campaigns as $campaign): ?>
                        <option value="<?= $campaign['id'] ?>" <?= ($filters['campaign_id'] ?? '') == $campaign['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($campaign['name']) ?> (<?= $campaign['country'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Statut -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                <select id="status" name="status" 
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">Tous</option>
                    <?php foreach ($statuses as $key => $label): ?>
                        <option value="<?= $key ?>" <?= ($filters['status'] ?? '') === $key ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Pays -->
            <div>
                <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Pays</label>
                <select id="country" name="country" 
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">Tous</option>
                    <option value="BE" <?= ($filters['country'] ?? '') === 'BE' ? 'selected' : '' ?>>🇧🇪 Belgique</option>
                    <option value="LU" <?= ($filters['country'] ?? '') === 'LU' ? 'selected' : '' ?>>🇱🇺 Luxembourg</option>
                </select>
            </div>

            <!-- Date début -->
            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Du</label>
                <input type="date" id="date_from" name="date_from" 
                       value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>

            <!-- Date fin -->
            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Au</label>
                <input type="date" id="date_to" name="date_to" 
                       value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>

            <!-- Recherche -->
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Recherche</label>
                <input type="text" id="search" name="search" 
                       value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                       placeholder="N° commande, client..."
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>

            <!-- Boutons -->
            <div class="flex items-end gap-2">
                <button type="submit" 
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                    <i class="fas fa-search mr-2"></i>
                    Filtrer
                </button>
                <a href="<?= $_SERVER['PHP_SELF'] ?>" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-times mr-2"></i>
                    Reset
                </a>
            </div>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Tableau des commandes -->
<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
        <span class="text-sm text-gray-600">
            <?php if ($pagination['total'] > 0): ?>
                Affichage de <?= number_format((($pagination['current_page'] - 1) * $pagination['per_page']) + 1, 0, ',', ' ') ?>
                à <?= number_format(min($pagination['current_page'] * $pagination['per_page'], $pagination['total']), 0, ',', ' ') ?>
                sur <?= number_format($pagination['total'], 0, ',', ' ') ?> commande(s)
            <?php else: ?>
                Aucune commande trouvée
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
                        N° Commande
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Client
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Campagne
                    </th>
                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Articles
                    </th>
                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Statut
                    </th>
                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Date
                    </th>
                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-2"></i>
                            <p>Aucune commande trouvée</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <tr class="hover:bg-gray-50">
                            <!-- N° Commande -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-bold
                                        <?= $order['customer_country'] === 'BE' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $order['customer_country'] ?? 'BE' ?>
                                    </span>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($order['order_number']) ?>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            #<?= $order['id'] ?>
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <!-- Client -->
                            <td class="px-4 py-3">
                                <p class="text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($order['company_name'] ?? '-') ?>
                                </p>
                                <p class="text-xs text-gray-500">
                                    <?= htmlspecialchars($order['customer_number'] ?? '-') ?>
                                </p>
                            </td>

                            <!-- Campagne -->
                            <td class="px-4 py-3">
                                <p class="text-sm text-gray-900">
                                    <?= htmlspecialchars($order['campaign_name'] ?? '-') ?>
                                </p>
                            </td>

                            <!-- Articles -->
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-indigo-100 text-indigo-700">
                                    <?= number_format($order['total_items'], 0, ',', ' ') ?>
                                </span>
                            </td>

                            <!-- Statut -->
                            <td class="px-4 py-3 text-center">
                                <?php
                                $statusColor = Order::STATUS_COLORS[$order['status']] ?? 'gray';
                                $statusLabel = Order::STATUSES[$order['status']] ?? $order['status'];
                                ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    <?php if ($statusColor === 'green'): ?>bg-green-100 text-green-800
                                    <?php elseif ($statusColor === 'yellow'): ?>bg-yellow-100 text-yellow-800
                                    <?php elseif ($statusColor === 'red'): ?>bg-red-100 text-red-800
                                    <?php else: ?>bg-gray-100 text-gray-800<?php endif; ?>">
                                    <?= htmlspecialchars($statusLabel) ?>
                                </span>
                            </td>

                            <!-- Date -->
                            <td class="px-4 py-3 text-center">
                                <p class="text-sm text-gray-900">
                                    <?= date('d/m/Y', strtotime($order['created_at'])) ?>
                                </p>
                                <p class="text-xs text-gray-500">
                                    <?= date('H:i', strtotime($order['created_at'])) ?>
                                </p>
                            </td>

                            <!-- Actions -->
                            <td class="px-4 py-3 text-center">
                                <a href="/stm/admin/orders/show?id=<?= $order['id'] ?>"
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
    $filterParams = http_build_query(array_filter([
        'campaign_id' => $filters['campaign_id'] ?? '',
        'status' => $filters['status'] ?? '',
        'country' => $filters['country'] ?? '',
        'date_from' => $filters['date_from'] ?? '',
        'date_to' => $filters['date_to'] ?? '',
        'search' => $filters['search'] ?? '',
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
            </p>
        </div>
        <div>
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <!-- Première page -->
                <?php if ($pagination['current_page'] > 2): ?>
                    <a href="?page=1<?= $filterParams ? '&' . $filterParams : '' ?>"
                       class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                <?php endif; ?>

                <!-- Précédent -->
                <?php if ($pagination['current_page'] > 1): ?>
                    <a href="?page=<?= $pagination['current_page'] - 1 ?><?= $filterParams ? '&' . $filterParams : '' ?>"
                       class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <i class="fas fa-angle-left"></i>
                    </a>
                <?php endif; ?>

                <!-- Pages -->
                <?php
                $start = max(1, $pagination['current_page'] - 2);
                $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
                for ($i = $start; $i <= $end; $i++):
                ?>
                    <a href="?page=<?= $i ?><?= $filterParams ? '&' . $filterParams : '' ?>"
                       class="relative inline-flex items-center px-4 py-2 border text-sm font-medium
                           <?= $i === $pagination['current_page'] 
                               ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' 
                               : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <!-- Suivant -->
                <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                    <a href="?page=<?= $pagination['current_page'] + 1 ?><?= $filterParams ? '&' . $filterParams : '' ?>"
                       class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <i class="fas fa-angle-right"></i>
                    </a>
                <?php endif; ?>

                <!-- Dernière page -->
                <?php if ($pagination['current_page'] < $pagination['total_pages'] - 1): ?>
                    <a href="?page=<?= $pagination['total_pages'] ?><?= $filterParams ? '&' . $filterParams : '' ?>"
                       class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function changePerPage(value) {
    document.getElementById('hidden_per_page').value = value;
    document.getElementById('filterForm').submit();
}
</script>

<?php
$content = ob_get_clean();
$title = $pageTitle;

require __DIR__ . '/../../layouts/admin.php';
?>
