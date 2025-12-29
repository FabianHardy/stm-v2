<?php
/**
 * Vue : Détail d'un client
 *
 * Affiche la fiche complète d'un client avec ses commandes
 * et modal de détail commande.
 *
 * @package STM/Views/Admin/Customers
 * @version 3.0
 * @created 12/11/2025 19:30
 * @modified 29/12/2025 - Refonte complète en mode consultation
 */

use Core\Session;

$pageTitle = 'Détail client';
ob_start();
?>

<!-- En-tête -->
<div class="mb-6">
    <div class="flex items-start justify-between gap-4">
        <div class="flex-1 min-w-0">
            <h1 class="text-3xl font-bold text-gray-900">
                <?= htmlspecialchars($customer['company_name'] ?? 'Client') ?>
            </h1>
            <p class="mt-2 text-sm text-gray-600">
                <?= $customer['country'] === 'BE' ? '🇧🇪 Belgique' : '🇱🇺 Luxembourg' ?>
                • N° <?= htmlspecialchars($customer['customer_number']) ?>
            </p>
        </div>
        <div class="flex gap-2 flex-shrink-0">
            <a href="/stm/admin/customers?country=<?= $customer['country'] ?>"
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 whitespace-nowrap">
                ← Retour à la liste
            </a>
        </div>
    </div>

    <!-- Breadcrumb -->
    <nav class="mt-4 flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="/stm/admin/dashboard" class="text-gray-700 hover:text-gray-900">🏠 Dashboard</a>
            </li>
            <li>
                <div class="flex items-center">
                    <span class="mx-2 text-gray-400">/</span>
                    <a href="/stm/admin/customers?country=<?= $customer['country'] ?>" class="text-gray-700 hover:text-gray-900">Clients</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <span class="mx-2 text-gray-400">/</span>
                    <span class="text-gray-500"><?= htmlspecialchars($customer['customer_number']) ?></span>
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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Colonne gauche : Fiche client -->
    <div class="lg:col-span-1 space-y-6">
        <!-- Informations générales -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-4 py-3 bg-gradient-to-r from-indigo-500 to-indigo-600">
                <h3 class="text-lg font-semibold text-white flex items-center">
                    <i class="fas fa-building mr-2"></i>
                    Fiche client
                </h3>
            </div>
            <div class="p-4 space-y-4">
                <!-- Nom entreprise -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Entreprise</label>
                    <p class="mt-1 text-sm font-medium text-gray-900">
                        <?= htmlspecialchars($customer['company_name'] ?? '-') ?>
                    </p>
                </div>

                <!-- Numéro client -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">N° Client</label>
                    <p class="mt-1 text-sm font-mono text-gray-900">
                        <?= htmlspecialchars($customer['customer_number']) ?>
                    </p>
                </div>

                <!-- Contact -->
                <?php if (!empty($customer['contact_name'])): ?>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Contact</label>
                    <p class="mt-1 text-sm text-gray-900">
                        <?= htmlspecialchars($customer['contact_name']) ?>
                    </p>
                </div>
                <?php endif; ?>

                <!-- Adresse -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Adresse</label>
                    <p class="mt-1 text-sm text-gray-900">
                        <?= htmlspecialchars($customer['address1'] ?? '-') ?>
                        <?php if (!empty($customer['address2'])): ?>
                            <br><?= htmlspecialchars($customer['address2']) ?>
                        <?php endif; ?>
                    </p>
                </div>

                <!-- Code postal / Localité -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Localité</label>
                    <p class="mt-1 text-sm text-gray-900">
                        <?= htmlspecialchars($customer['postal_code'] ?? '') ?>
                        <?= htmlspecialchars($customer['city'] ?? '-') ?>
                    </p>
                </div>

                <!-- Pays -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Pays</label>
                    <p class="mt-1">
                        <?php if ($customer['country'] === 'BE'): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                🇧🇪 Belgique
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                🇱🇺 Luxembourg
                            </span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Représentant -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-4 py-3 bg-gradient-to-r from-green-500 to-green-600">
                <h3 class="text-lg font-semibold text-white flex items-center">
                    <i class="fas fa-user-tie mr-2"></i>
                    Représentant
                </h3>
            </div>
            <div class="p-4 space-y-4">
                <!-- Nom représentant -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Nom</label>
                    <p class="mt-1 text-sm font-medium text-gray-900">
                        <?= htmlspecialchars($customer['rep_name'] ?? '-') ?>
                    </p>
                </div>

                <!-- Code rep -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Code</label>
                    <p class="mt-1 text-sm font-mono text-gray-900">
                        <?= htmlspecialchars($customer['rep_id'] ?? '-') ?>
                    </p>
                </div>

                <!-- Cluster -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Cluster</label>
                    <p class="mt-1">
                        <?php if (!empty($customer['cluster'])): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                <?= htmlspecialchars($customer['cluster']) ?>
                            </span>
                        <?php else: ?>
                            <span class="text-sm text-gray-400">-</span>
                        <?php endif; ?>
                    </p>
                </div>

                <!-- Email rep -->
                <?php if (!empty($customer['rep_email'])): ?>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Email</label>
                    <p class="mt-1 text-sm text-gray-900">
                        <a href="mailto:<?= htmlspecialchars($customer['rep_email']) ?>" class="text-indigo-600 hover:text-indigo-800">
                            <?= htmlspecialchars($customer['rep_email']) ?>
                        </a>
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Colonne droite : Stats + Commandes -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Statistiques -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Commandes -->
            <div class="bg-white shadow rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 bg-green-100 rounded-lg">
                        <i class="fas fa-shopping-cart text-green-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Commandes</p>
                        <p class="text-2xl font-bold text-gray-900"><?= number_format($customerStats['orders_count'] ?? 0, 0, ',', ' ') ?></p>
                    </div>
                </div>
            </div>

            <!-- Campagnes -->
            <div class="bg-white shadow rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 bg-purple-100 rounded-lg">
                        <i class="fas fa-bullhorn text-purple-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Campagnes</p>
                        <p class="text-2xl font-bold text-gray-900"><?= number_format($customerStats['campaigns_count'] ?? 0, 0, ',', ' ') ?></p>
                    </div>
                </div>
            </div>

            <!-- Promos achetées -->
            <div class="bg-white shadow rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 bg-orange-100 rounded-lg">
                        <i class="fas fa-tags text-orange-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Promos achetées</p>
                        <p class="text-2xl font-bold text-orange-600"><?= number_format($customerStats['total_quantity'] ?? 0, 0, ',', ' ') ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Période d'activité -->
        <?php if (!empty($customerStats['first_order_date'])): ?>
        <div class="bg-indigo-50 rounded-lg p-4 flex items-center justify-between">
            <div>
                <p class="text-sm text-indigo-700">
                    <i class="fas fa-calendar-alt mr-2"></i>
                    Première commande : <strong><?= date('d/m/Y', strtotime($customerStats['first_order_date'])) ?></strong>
                </p>
            </div>
            <div>
                <p class="text-sm text-indigo-700">
                    Dernière commande : <strong><?= date('d/m/Y', strtotime($customerStats['last_order_date'])) ?></strong>
                </p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Liste des commandes -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-4 py-3 bg-gradient-to-r from-gray-700 to-gray-800 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-white flex items-center">
                    <i class="fas fa-list mr-2"></i>
                    Historique des commandes
                </h3>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-white text-gray-800">
                    <?= count($orders) ?> commande(s)
                </span>
            </div>

            <?php if (empty($orders)): ?>
                <div class="p-8 text-center">
                    <div class="text-gray-400">
                        <i class="fas fa-inbox text-4xl mb-3"></i>
                        <p class="text-lg font-medium">Aucune commande</p>
                        <p class="text-sm">Ce client n'a pas encore passé de commande</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">N° Cmd</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campagne</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Articles</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Promos</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($orders as $order): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-mono text-gray-900">
                                        #<?= $order['id'] ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <?php if (!empty($order['campaign_id'])): ?>
                                            <a href="/stm/admin/campaigns/<?= $order['campaign_id'] ?>" class="text-indigo-600 hover:text-indigo-800">
                                                <?= htmlspecialchars($order['campaign_name'] ?? 'Campagne #' . $order['campaign_id']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">
                                            <?= $order['total_items'] ?? 0 ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-orange-100 text-orange-700">
                                            <?= number_format($order['total_quantity'] ?? 0, 0, ',', ' ') ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button type="button"
                                                onclick="openOrderModal(<?= $order['id'] ?>)"
                                                class="inline-flex items-center px-2 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs transition">
                                            <i class="fas fa-eye mr-1"></i>
                                            Détail
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal détail commande -->
<div id="orderModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <!-- Overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeOrderModal()"></div>

        <!-- Modal content -->
        <div class="relative bg-white rounded-lg shadow-xl transform transition-all sm:max-w-4xl sm:w-full mx-auto">
            <!-- Header -->
            <div class="bg-indigo-600 px-6 py-4 rounded-t-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-white" id="orderModalTitle">Détail commande</h3>
                        <p class="text-indigo-200 text-sm" id="orderModalSubtitle"></p>
                    </div>
                    <button type="button" onclick="closeOrderModal()" class="text-white hover:text-indigo-200 transition">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>

            <!-- Body -->
            <div class="px-6 py-4 max-h-[70vh] overflow-y-auto">
                <!-- Loading -->
                <div id="orderModalLoading" class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-indigo-600 text-2xl"></i>
                    <p class="text-gray-500 mt-2">Chargement des détails...</p>
                </div>

                <!-- Error -->
                <div id="orderModalError" class="hidden text-center py-8">
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-exclamation-triangle text-red-500"></i>
                    </div>
                    <p class="text-red-600" id="orderModalErrorText">Erreur lors du chargement</p>
                </div>

                <!-- Content -->
                <div id="orderModalContent" class="hidden">
                    <!-- Stats résumé -->
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="bg-blue-50 rounded-lg p-4 text-center">
                            <p class="text-2xl font-bold text-blue-600" id="orderModalItems">0</p>
                            <p class="text-sm text-blue-700">Produits</p>
                        </div>
                        <div class="bg-orange-50 rounded-lg p-4 text-center">
                            <p class="text-2xl font-bold text-orange-600" id="orderModalQty">0</p>
                            <p class="text-sm text-orange-700">Promos</p>
                        </div>
                    </div>

                    <!-- Tableau des produits -->
                    <div id="orderModalProducts">
                        <!-- Rempli par JavaScript -->
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 px-6 py-3 rounded-b-lg flex justify-end">
                <button type="button" onclick="closeOrderModal()"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
                    Fermer
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$title = 'Détail client - ' . htmlspecialchars($customer['company_name'] ?? '');

$pageScripts = <<<SCRIPTS
<script>
function formatNumberFr(num) {
    return new Intl.NumberFormat('fr-FR').format(num);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function openOrderModal(orderId) {
    const modal = document.getElementById('orderModal');
    const modalLoading = document.getElementById('orderModalLoading');
    const modalError = document.getElementById('orderModalError');
    const modalContent = document.getElementById('orderModalContent');

    // Afficher le modal
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    // Reset état
    modalLoading.classList.remove('hidden');
    modalError.classList.add('hidden');
    modalContent.classList.add('hidden');

    // Charger les données via API
    fetch('/stm/admin/customers/order-detail?order_id=' + orderId)
        .then(response => response.json())
        .then(data => {
            modalLoading.classList.add('hidden');

            if (!data.success) {
                modalError.classList.remove('hidden');
                document.getElementById('orderModalErrorText').textContent = data.error || 'Erreur inconnue';
                return;
            }

            // Afficher le contenu
            modalContent.classList.remove('hidden');

            // Header
            document.getElementById('orderModalTitle').textContent = 'Commande #' + data.order.id;
            const orderDate = new Date(data.order.created_at);
            document.getElementById('orderModalSubtitle').textContent = data.order.campaign_name + ' • ' + orderDate.toLocaleDateString('fr-FR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            // Stats
            document.getElementById('orderModalItems').textContent = data.order.lines.length;
            document.getElementById('orderModalQty').textContent = formatNumberFr(data.order.total_quantity);

            // Tableau des produits
            let productsHtml = '<table class="min-w-full table-fixed">' +
                '<thead class="bg-gray-100">' +
                    '<tr class="text-left text-xs text-gray-500 uppercase">' +
                        '<th class="py-2 px-3 w-16">Image</th>' +
                        '<th class="py-2 px-3 text-left">Promotion</th>' +
                        '<th class="py-2 px-3 w-28">Code</th>' +
                        '<th class="py-2 px-3 w-24 text-right">Quantité</th>' +
                    '</tr>' +
                '</thead>' +
                '<tbody class="text-sm divide-y divide-gray-100">';

            data.order.lines.forEach(line => {
                const imageUrl = line.product_image || '/stm/assets/images/no-image.png';
                productsHtml += '<tr class="hover:bg-gray-50">' +
                    '<td class="py-2 px-3 w-16">' +
                        '<img src="' + imageUrl + '" alt="" class="w-12 h-12 object-contain rounded border border-gray-200 bg-white">' +
                    '</td>' +
                    '<td class="py-2 px-3 text-left">' +
                        '<p class="font-medium text-gray-900 text-left">' + escapeHtml(line.product_name) + '</p>' +
                    '</td>' +
                    '<td class="py-2 px-3 w-28">' +
                        '<span class="text-gray-500 font-mono text-xs">' + escapeHtml(line.product_code) + '</span>' +
                    '</td>' +
                    '<td class="py-2 px-3 w-24 text-right">' +
                        '<span class="inline-flex items-center px-2.5 py-1 bg-orange-100 text-orange-700 rounded font-bold">' + formatNumberFr(line.quantity) + '</span>' +
                    '</td>' +
                '</tr>';
            });

            productsHtml += '</tbody></table>';
            document.getElementById('orderModalProducts').innerHTML = productsHtml;
        })
        .catch(error => {
            console.error('Erreur chargement commande:', error);
            modalLoading.classList.add('hidden');
            modalError.classList.remove('hidden');
            document.getElementById('orderModalErrorText').textContent = 'Erreur de connexion';
        });
}

function closeOrderModal() {
    document.getElementById('orderModal').classList.add('hidden');
    document.body.style.overflow = '';
}

// Fermer avec Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeOrderModal();
    }
});
</script>
SCRIPTS;

require __DIR__ . '/../../layouts/admin.php';
?>