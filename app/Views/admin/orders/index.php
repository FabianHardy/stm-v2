<?php
/**
 * Vue : Liste des commandes admin
 *
 * Affiche toutes les commandes avec :
 * - Statistiques (total, aujourd'hui, en attente, erreurs)
 * - Filtres (campagne, statut, pays, source, dates, recherche)
 * - Colonne Source (Client/Rep) dans le tableau
 * - Sélection multiple pour export Excel (Sprint 15)
 * - Pagination
 *
 * @package    App\Views\admin\orders
 * @author     Fabian Hardy
 * @version    1.2.0
 * @created    2025/12/30
 * @modified   2026/01/08 - Ajout filtre Source + colonne Source
 * @modified   2026/01/08 - Sprint 15 : Export Excel, checkboxes sélection, statut "En attente export"
 */

ob_start();

// Variables par défaut
$orders = $orders ?? [];
$stats = $stats ?? ['total_orders' => 0, 'today_count' => 0, 'pending_count' => 0, 'error_count' => 0];
$campaigns = $campaigns ?? [];
$statuses = $statuses ?? [];
$pagination = $pagination ?? ['current_page' => 1, 'per_page' => 50, 'total' => 0, 'total_pages' => 1];
$filters = $filters ?? [];
$pageTitle = $pageTitle ?? 'Toutes les commandes';
$isToday = $isToday ?? false;
$isPending = $isPending ?? false;

// Construire l'URL avec les filtres
function buildFilterUrl($newParams = []) {
    $params = $_GET;
    foreach ($newParams as $key => $value) {
        if ($value === null || $value === '') {
            unset($params[$key]);
        } else {
            $params[$key] = $value;
        }
    }
    return '/stm/admin/orders' . ($params ? '?' . http_build_query($params) : '');
}
?>

<!-- En-tête -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($pageTitle) ?></h1>
    <p class="text-sm text-gray-500">Gestion des commandes clients</p>
</div>

<!-- Cartes statistiques -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <!-- Total commandes -->
    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-indigo-500">
        <div class="text-sm font-medium text-gray-500">Total commandes</div>
        <div class="text-2xl font-bold text-indigo-600"><?= number_format($stats['total_orders'] ?? 0, 0, ',', ' ') ?></div>
    </div>

    <!-- Aujourd'hui -->
    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-blue-500">
        <div class="text-sm font-medium text-gray-500">Aujourd'hui</div>
        <div class="text-2xl font-bold text-blue-600"><?= number_format($stats['today_count'] ?? 0, 0, ',', ' ') ?></div>
    </div>

    <!-- En attente -->
    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-yellow-500">
        <div class="text-sm font-medium text-gray-500">En attente</div>
        <div class="text-2xl font-bold text-yellow-600"><?= number_format($stats['pending_count'] ?? 0, 0, ',', ' ') ?></div>
    </div>

    <!-- Erreurs -->
    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-red-500">
        <div class="text-sm font-medium text-gray-500">Erreurs</div>
        <div class="text-2xl font-bold text-red-600"><?= number_format($stats['error_count'] ?? 0, 0, ',', ' ') ?></div>
    </div>
</div>

<!-- Filtres -->
<?php if (!$isToday && !$isPending): ?>
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <form method="GET" action="/stm/admin/orders" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
        <!-- Campagne -->
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Campagne</label>
            <select name="campaign_id" class="w-full rounded-md border-gray-300 text-sm">
                <option value="">Toutes</option>
                <?php foreach ($campaigns as $camp): ?>
                <option value="<?= $camp['id'] ?>" <?= ($filters['campaign_id'] ?? '') == $camp['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($camp['name']) ?> (<?= $camp['country'] ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Statut -->
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Statut</label>
            <select name="status" class="w-full rounded-md border-gray-300 text-sm">
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
            <label class="block text-xs font-medium text-gray-700 mb-1">Pays</label>
            <select name="country" class="w-full rounded-md border-gray-300 text-sm">
                <option value="">Tous</option>
                <option value="BE" <?= ($filters['country'] ?? '') === 'BE' ? 'selected' : '' ?>>🇧🇪 Belgique</option>
                <option value="LU" <?= ($filters['country'] ?? '') === 'LU' ? 'selected' : '' ?>>🇱🇺 Luxembourg</option>
            </select>
        </div>

        <!-- Source -->
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Source</label>
            <select name="source" class="w-full rounded-md border-gray-300 text-sm">
                <option value="">Toutes</option>
                <option value="client" <?= ($filters['source'] ?? '') === 'client' ? 'selected' : '' ?>>👤 Clients</option>
                <option value="rep" <?= ($filters['source'] ?? '') === 'rep' ? 'selected' : '' ?>>👔 Reps</option>
            </select>
        </div>

        <!-- Date début -->
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Du</label>
            <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>"
                   class="w-full rounded-md border-gray-300 text-sm">
        </div>

        <!-- Date fin -->
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Au</label>
            <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>"
                   class="w-full rounded-md border-gray-300 text-sm">
        </div>

        <!-- Recherche -->
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Recherche</label>
            <input type="text" name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                   placeholder="N° commande, client..."
                   class="w-full rounded-md border-gray-300 text-sm">
        </div>

        <!-- Boutons -->
        <div class="col-span-2 md:col-span-4 lg:col-span-7 flex gap-2">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700">
                <i class="fas fa-search mr-1"></i> Filtrer
            </button>
            <a href="/stm/admin/orders" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm hover:bg-gray-300">
                Reset
            </a>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Info pagination -->
<div class="flex justify-between items-center mb-4">
    <div class="text-sm text-gray-500">
        Affichage de <?= (($pagination['current_page'] - 1) * $pagination['per_page']) + 1 ?>
        à <?= min($pagination['current_page'] * $pagination['per_page'], $pagination['total']) ?>
        sur <?= number_format($pagination['total'], 0, ',', ' ') ?> commande(s)
    </div>
    <div>
        <label class="text-sm text-gray-500 mr-2">Afficher</label>
        <select onchange="window.location.href=this.value" class="rounded-md border-gray-300 text-sm">
            <?php foreach ([10, 25, 50, 100] as $pp): ?>
            <option value="<?= buildFilterUrl(['per_page' => $pp, 'page' => 1]) ?>" <?= $pagination['per_page'] == $pp ? 'selected' : '' ?>>
                <?= $pp ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<!-- SPRINT 15 : Boutons d'export Excel -->
<div class="flex items-center gap-3 mb-4" x-data="{ selectedOrders: [] }">
    <form id="exportForm" method="POST" action="/stm/admin/orders/export-excel" class="flex items-center gap-3">
        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        <input type="hidden" name="campaign_id" value="<?= htmlspecialchars($filters['campaign_id'] ?? '') ?>">

        <!-- Bouton export sélection -->
        <button type="submit"
                id="btnExportSelected"
                disabled
                class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md text-sm hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed">
            <i class="fas fa-file-excel mr-2"></i>
            Exporter sélection (<span id="selectedCount">0</span>)
        </button>
    </form>

    <?php if (!empty($filters['campaign_id'])): ?>
    <!-- Bouton export toutes les commandes en attente de la campagne -->
    <form method="POST" action="/stm/admin/orders/export-excel">
        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        <input type="hidden" name="campaign_id" value="<?= htmlspecialchars($filters['campaign_id']) ?>">
        <input type="hidden" name="export_all" value="1">
        <button type="submit"
                class="inline-flex items-center px-4 py-2 bg-orange-600 text-white rounded-md text-sm hover:bg-orange-700">
            <i class="fas fa-file-export mr-2"></i>
            Exporter toutes "En attente"
        </button>
    </form>
    <?php endif; ?>

    <span class="text-sm text-gray-500">
        <i class="fas fa-info-circle mr-1"></i>
        Sélectionnez des commandes pour exporter en Excel
    </span>
</div>

<!-- Tableau des commandes -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <!-- SPRINT 15 : Checkbox tout sélectionner -->
                <th class="px-3 py-3 text-center">
                    <input type="checkbox"
                           id="selectAll"
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                           onchange="toggleAllCheckboxes(this)">
                </th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Pays</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campagne</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Source</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Articles</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Statut</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($orders)): ?>
            <tr>
                <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                    <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                    <p>Aucune commande trouvée</p>
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($orders as $order): ?>
            <?php
                $country = $order['customer_country'] ?? ($order['campaign_country'] ?? 'BE');
                $statusColors = [
                    'pending_sync' => 'bg-yellow-100 text-yellow-800',
                    'synced' => 'bg-green-100 text-green-800',
                    'error' => 'bg-red-100 text-red-800',
                    // Rétrocompatibilité
                    'pending' => 'bg-yellow-100 text-yellow-800',
                    'validated' => 'bg-orange-100 text-orange-800', // Sprint 15 : Orange pour distinguer
                    'cancelled' => 'bg-red-100 text-red-800',
                ];
                $statusLabels = [
                    'pending_sync' => 'En attente',
                    'synced' => 'Traité (TXT)',
                    'error' => 'Erreur',
                    'pending' => 'En attente',
                    'validated' => 'En attente export', // Sprint 15 : Plus clair
                    'cancelled' => 'Annulée',
                ];
                $statusClass = $statusColors[$order['status']] ?? 'bg-gray-100 text-gray-800';
                $statusLabel = $statusLabels[$order['status']] ?? $order['status'];
            ?>
            <tr class="hover:bg-gray-50">
                <!-- SPRINT 15 : Checkbox de sélection -->
                <td class="px-3 py-4 text-center">
                    <input type="checkbox"
                           name="order_ids[]"
                           form="exportForm"
                           value="<?= $order['id'] ?>"
                           class="order-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                           onchange="updateSelectedCount()">
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                    <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium <?= $country === 'BE' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800' ?>">
                        <?= $country === 'BE' ? '🇧🇪 BE' : '🇱🇺 LU' ?>
                    </span>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($order['company_name'] ?? 'N/A') ?></div>
                    <div class="text-xs text-gray-500 font-mono"><?= htmlspecialchars($order['customer_number'] ?? '') ?></div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                    <?= htmlspecialchars($order['campaign_name'] ?? 'N/A') ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                    <?php if (($order['order_source'] ?? 'client') === 'rep'): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-violet-100 text-violet-800">
                            <i class="fas fa-user-tie mr-1"></i> Rep
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <i class="fas fa-user mr-1"></i> Client
                        </span>
                    <?php endif; ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                        <?= (int)($order['total_items'] ?? 0) ?>
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusClass ?>">
                        <?= $statusLabel ?>
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                    <a href="/stm/admin/orders/<?= $order['id'] ?>"
                       class="inline-flex items-center px-3 py-1 border border-indigo-300 rounded text-xs font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100">
                        <i class="fas fa-eye mr-1"></i> Voir
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($pagination['total_pages'] > 1): ?>
<div class="mt-4 flex justify-center">
    <nav class="inline-flex rounded-md shadow-sm -space-x-px">
        <?php if ($pagination['current_page'] > 1): ?>
        <a href="<?= buildFilterUrl(['page' => 1]) ?>" class="px-3 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
            <i class="fas fa-angle-double-left"></i>
        </a>
        <a href="<?= buildFilterUrl(['page' => $pagination['current_page'] - 1]) ?>" class="px-3 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
            <i class="fas fa-angle-left"></i>
        </a>
        <?php endif; ?>

        <?php
        $start = max(1, $pagination['current_page'] - 2);
        $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
        for ($i = $start; $i <= $end; $i++):
        ?>
        <a href="<?= buildFilterUrl(['page' => $i]) ?>"
           class="px-4 py-2 border border-gray-300 text-sm font-medium <?= $i === $pagination['current_page'] ? 'bg-indigo-50 text-indigo-600 border-indigo-500' : 'bg-white text-gray-700 hover:bg-gray-50' ?>">
            <?= $i ?>
        </a>
        <?php endfor; ?>

        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
        <a href="<?= buildFilterUrl(['page' => $pagination['current_page'] + 1]) ?>" class="px-3 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
            <i class="fas fa-angle-right"></i>
        </a>
        <a href="<?= buildFilterUrl(['page' => $pagination['total_pages']]) ?>" class="px-3 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
            <i class="fas fa-angle-double-right"></i>
        </a>
        <?php endif; ?>
    </nav>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
$title = $pageTitle;
$pageScripts = <<<JS
<script>
// SPRINT 15 : Gestion des checkboxes pour export Excel
function toggleAllCheckboxes(source) {
    const checkboxes = document.querySelectorAll('.order-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = source.checked;
    });
    updateSelectedCount();
}

function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.order-checkbox:checked');
    const count = checkboxes.length;
    const countSpan = document.getElementById('selectedCount');
    const btnExport = document.getElementById('btnExportSelected');

    if (countSpan) countSpan.textContent = count;
    if (btnExport) btnExport.disabled = (count === 0);

    // Mettre à jour le checkbox "tout sélectionner"
    const allCheckboxes = document.querySelectorAll('.order-checkbox');
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.checked = (allCheckboxes.length > 0 && checkboxes.length === allCheckboxes.length);
        selectAll.indeterminate = (checkboxes.length > 0 && checkboxes.length < allCheckboxes.length);
    }
}

// Initialisation au chargement
document.addEventListener('DOMContentLoaded', updateSelectedCount);
</script>
JS;
require __DIR__ . '/../../layouts/admin.php';
?>