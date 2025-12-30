<?php
/**
 * Vue : Liste des commandes admin
 *
 * Affiche toutes les commandes avec :
 * - Statistiques (total, aujourd'hui, en attente, erreurs)
 * - Filtres (campagne, statut, pays, dates, recherche)
 * - Pagination
 *
 * @package    App\Views\admin\orders
 * @author     Fabian Hardy
 * @version    1.0.0
 * @created    2025/12/30
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
    <form method="GET" action="/stm/admin/orders" class="grid grid-cols-1 md:grid-cols-6 gap-4">
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
        <div class="md:col-span-6 flex gap-2">
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

<!-- Tableau des commandes -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">N° Commande</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campagne</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Articles</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Statut</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($orders)): ?>
            <tr>
                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
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
                    'validated' => 'bg-green-100 text-green-800',
                    'cancelled' => 'bg-red-100 text-red-800',
                ];
                $statusLabels = [
                    'pending_sync' => 'En attente',
                    'synced' => 'Synced',
                    'error' => 'Erreur',
                    'pending' => 'En attente',
                    'validated' => 'Validée',
                    'cancelled' => 'Annulée',
                ];
                $statusClass = $statusColors[$order['status']] ?? 'bg-gray-100 text-gray-800';
                $statusLabel = $statusLabels[$order['status']] ?? $order['status'];
            ?>
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?= $country === 'BE' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800' ?> mr-2">
                            <?= $country ?>
                        </span>
                        <span class="font-mono text-sm"><?= htmlspecialchars($order['order_number'] ?? 'N/A') ?></span>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($order['company_name'] ?? 'N/A') ?></div>
                    <div class="text-xs text-gray-500 font-mono"><?= htmlspecialchars($order['customer_number'] ?? '') ?></div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                    <?= htmlspecialchars($order['campaign_name'] ?? 'N/A') ?>
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
$pageScripts = "";
require __DIR__ . '/../../layouts/admin.php';
?>