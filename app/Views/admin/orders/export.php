<?php
/**
 * Vue Admin - Export fichiers TXT
 *
 * @created  2025/12/29 15:00
 */

use App\Models\Order;

ob_start();
?>

<!-- En-tête -->
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($pageTitle ?? 'Export fichiers TXT') ?></h1>
            <p class="mt-1 text-sm text-gray-500">Télécharger les fichiers TXT pour l'ERP</p>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="bg-white shadow rounded-lg p-4 mb-6">
    <form method="GET" action="/stm/admin/orders/export" id="filterForm">
        <input type="hidden" name="per_page" id="hidden_per_page" value="<?= $pagination['per_page'] ?? 50 ?>">

        <!-- Ligne 1 : Filtres principaux -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-4">
            <!-- Campagne -->
            <div class="col-span-2 md:col-span-1">
                <label for="campaign_id" class="block text-xs font-medium text-gray-700 mb-1">Campagne</label>
                <select id="campaign_id" name="campaign_id"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <option value="">Toutes</option>
                    <?php foreach ($campaigns as $campaign): ?>
                        <option value="<?= $campaign['id'] ?>" <?= ($filters['campaign_id'] ?? '') == $campaign['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($campaign['name'] ?? '') ?> (<?= $campaign['country'] ?? '' ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Statut -->
            <div>
                <label for="status" class="block text-xs font-medium text-gray-700 mb-1">Statut synchro</label>
                <select id="status" name="status"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <option value="">Tous</option>
                    <?php foreach ($statuses as $key => $label): ?>
                        <option value="<?= $key ?>" <?= ($filters['status'] ?? '') === $key ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label ?? '') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Pays -->
            <div>
                <label for="country" class="block text-xs font-medium text-gray-700 mb-1">Pays</label>
                <select id="country" name="country"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <option value="">Tous</option>
                    <option value="BE" <?= ($filters['country'] ?? '') === 'BE' ? 'selected' : '' ?>>🇧🇪 Belgique</option>
                    <option value="LU" <?= ($filters['country'] ?? '') === 'LU' ? 'selected' : '' ?>>🇱🇺 Luxembourg</option>
                </select>
            </div>

            <!-- Date début -->
            <div>
                <label for="date_from" class="block text-xs font-medium text-gray-700 mb-1">Du</label>
                <input type="date" id="date_from" name="date_from"
                       value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
            </div>

            <!-- Date fin -->
            <div>
                <label for="date_to" class="block text-xs font-medium text-gray-700 mb-1">Au</label>
                <input type="date" id="date_to" name="date_to"
                       value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
            </div>

            <!-- Recherche -->
            <div>
                <label for="search" class="block text-xs font-medium text-gray-700 mb-1">Recherche</label>
                <input type="text" id="search" name="search"
                       value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                       placeholder="N° commande, client..."
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
            </div>
        </div>

        <!-- Ligne 2 : Boutons -->
        <div class="flex items-center gap-2">
            <button type="submit"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                <i class="fas fa-search mr-2"></i>
                Filtrer
            </button>
            <a href="/stm/admin/orders/export"
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-times mr-2"></i>
                Reset
            </a>
        </div>
    </form>
</div>

<!-- Tableau des fichiers -->
<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
        <span class="text-sm text-gray-600">
            <?php if ($pagination['total'] > 0): ?>
                <?= number_format($pagination['total'], 0, ',', ' ') ?> fichier(s) disponible(s)
            <?php else: ?>
                Aucun fichier trouvé
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
                        <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        N° Commande
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Client
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Fichier
                    </th>
                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Généré le
                    </th>
                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Statut
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
                            <i class="fas fa-file-excel text-4xl mb-2"></i>
                            <p>Aucune commande trouvée</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order):
                        // Fichier disponible si path existe OU contenu stocké en DB
                        $hasFile = !empty($order['file_path']) || !empty($order['file_content']);
                    ?>
                        <tr class="hover:bg-gray-50">
                            <!-- Checkbox -->
                            <td class="px-4 py-3">
                                <?php if ($hasFile): ?>
                                    <input type="checkbox" name="order_ids[]" value="<?= $order['id'] ?>"
                                           class="order-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <?php endif; ?>
                            </td>

                            <!-- N° Commande -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-bold
                                        <?= $order['customer_country'] === 'BE' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $order['customer_country'] ?? 'BE' ?>
                                    </span>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($order['order_number'] ?? '') ?>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
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

                            <!-- Fichier -->
                            <td class="px-4 py-3">
                                <?php if ($hasFile): ?>
                                    <p class="text-xs text-gray-600 font-mono">
                                        <?= htmlspecialchars(basename($order['file_path'] ?? '')) ?>
                                    </p>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400">Non généré</span>
                                <?php endif; ?>
                            </td>

                            <!-- Généré le -->
                            <td class="px-4 py-3 text-center">
                                <?php if (!empty($order['file_generated_at'])): ?>
                                    <p class="text-sm text-gray-900">
                                        <?= date('d/m/Y', strtotime($order['file_generated_at'])) ?>
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        <?= date('H:i', strtotime($order['file_generated_at'])) ?>
                                    </p>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400">-</span>
                                <?php endif; ?>
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

                            <!-- Actions -->
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <?php if ($hasFile): ?>
                                        <a href="/stm/admin/orders/download?id=<?= $order['id'] ?>"
                                           class="inline-flex items-center px-2 py-1 bg-green-600 hover:bg-green-700 text-white rounded text-xs transition"
                                           title="Télécharger">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="/stm/admin/orders/<?= $order['id'] ?>"
                                       class="inline-flex items-center px-2 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs transition"
                                       title="Voir détails">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
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
    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-gray-700">
                Page <span class="font-medium"><?= $pagination['current_page'] ?></span>
                sur <span class="font-medium"><?= $pagination['total_pages'] ?></span>
            </p>
        </div>
        <div>
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <?php if ($pagination['current_page'] > 1): ?>
                    <a href="?page=<?= $pagination['current_page'] - 1 ?><?= $filterParams ? '&' . $filterParams : '' ?>"
                       class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <i class="fas fa-angle-left"></i>
                    </a>
                <?php endif; ?>

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

                <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                    <a href="?page=<?= $pagination['current_page'] + 1 ?><?= $filterParams ? '&' . $filterParams : '' ?>"
                       class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <i class="fas fa-angle-right"></i>
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

// Sélectionner/Désélectionner tout
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.order-checkbox').forEach(cb => cb.checked = this.checked);
});
</script>

<?php
$content = ob_get_clean();
$title = $pageTitle;

require __DIR__ . '/../../layouts/admin.php';
?>