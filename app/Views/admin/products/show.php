<?php
/**
 * Vue : Détails d'une Promotion
 *
 * Affichage complet des informations d'une Promotion
 *
 * @created 11/11/2025 22:50
 * @modified 16/12/2025 - Ajout filtrage permissions sur boutons
 * @modified 23/12/2025 - Ajout section statistiques de vente
 * @modified 29/12/2025 - Ajout modal détail client + liens stats reps
 */

use Core\Session;
use App\Helpers\PermissionHelper;

// Permissions pour les boutons
$canEdit = PermissionHelper::can('products.edit');
$canDelete = PermissionHelper::can('products.delete');

// Démarrer la capture du contenu pour le layout
ob_start();
?>

<!-- En-tête de la page -->
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">
                <?php echo htmlspecialchars($product['name_fr']); ?>
            </h1>
            <p class="mt-2 text-sm text-gray-600">
                Code: <span class="font-medium"><?php echo htmlspecialchars($product['product_code']); ?></span>
            </p>
        </div>
        <div class="flex gap-2">
            <a href="/stm/admin/products"
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                ← Retour à la liste
            </a>
            <?php if ($canEdit): ?>
            <a href="/stm/admin/products/<?php echo $product['id']; ?>/edit"
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                ✏️ Modifier
            </a>
            <?php endif; ?>
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
            <li>
                <div class="flex items-center">
                    <span class="mx-2 text-gray-400">/</span>
                    <a href="/stm/admin/products" class="text-gray-700 hover:text-gray-900">
                        Promotions
                    </a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <span class="mx-2 text-gray-400">/</span>
                    <span class="text-gray-500"><?php echo htmlspecialchars($product['product_code']); ?></span>
                </div>
            </li>
        </ol>
    </nav>
</div>

<!-- Badges de statut et catégorie -->
<div class="flex items-center gap-3 mb-6">
    <!-- Badge statut -->
    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $product['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
        <?php echo $product['is_active'] ? '✓ Actif' : '✗ Inactif'; ?>
    </span>

    <!-- Badge catégorie -->
    <?php if (!empty($product['category_name'])): ?>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800">
            📁 <?php echo htmlspecialchars($product['category_name']); ?>
        </span>
    <?php endif; ?>

    <!-- Badge ordre d'affichage -->
    <?php if ($product['display_order'] > 0): ?>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
            #️⃣ Ordre: <?php echo $product['display_order']; ?>
        </span>
    <?php endif; ?>
</div>

<!-- Section : Statistiques de vente -->
<?php if (isset($productStats)): ?>
<div class="bg-white shadow rounded-lg mb-6">
    <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            📊 Statistiques de vente
        </h3>
    </div>
    <div class="px-4 py-5 sm:p-6">
        <!-- Cards résumé -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-6">
            <!-- Total vendu -->
            <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg p-4 text-center border border-orange-200">
                <p class="text-3xl font-bold text-orange-600"><?php echo number_format($productStats['total_sold'], 0, ',', ' '); ?></p>
                <p class="text-sm text-orange-700 mt-1">Promos vendues</p>
            </div>
            <!-- Commandes -->
            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 text-center border border-green-200">
                <p class="text-3xl font-bold text-green-600"><?php echo number_format($productStats['orders_count'], 0, ',', ' '); ?></p>
                <p class="text-sm text-green-700 mt-1">Commandes</p>
            </div>
            <!-- Clients -->
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 text-center border border-blue-200">
                <p class="text-3xl font-bold text-blue-600"><?php echo number_format($productStats['customers_count'], 0, ',', ' '); ?></p>
                <p class="text-sm text-blue-700 mt-1">Clients</p>
            </div>
        </div>

        <!-- Barre de progression quota si défini -->
        <?php if (!empty($product['max_total']) && $product['max_total'] > 0): ?>
        <?php
            $quotaPercent = min(100, round(($productStats['total_sold'] / $product['max_total']) * 100));
            $quotaColor = $quotaPercent >= 90 ? 'red' : ($quotaPercent >= 70 ? 'yellow' : 'green');
        ?>
        <div class="mb-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700">Progression quota global</span>
                <span class="text-sm font-bold text-<?php echo $quotaColor; ?>-600">
                    <?php echo number_format($productStats['total_sold'], 0, ',', ' '); ?> / <?php echo number_format($product['max_total'], 0, ',', ' '); ?>
                    (<?php echo $quotaPercent; ?>%)
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-4">
                <div class="bg-<?php echo $quotaColor; ?>-500 h-4 rounded-full transition-all duration-300" style="width: <?php echo $quotaPercent; ?>%"></div>
            </div>
            <?php if ($quotaPercent >= 90): ?>
            <p class="text-xs text-red-600 mt-1">⚠️ Quota presque atteint !</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Top clients et Top reps côte à côte -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Top 5 Clients -->
            <div>
                <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                    <span class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center mr-2">
                        <i class="fas fa-building text-blue-600 text-xs"></i>
                    </span>
                    Top 5 Clients
                </h4>
                <?php if (empty($productStats['top_customers'])): ?>
                    <p class="text-gray-500 text-sm italic">Aucune vente enregistrée</p>
                <?php else: ?>
                    <div class="overflow-hidden rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Qté</th>
                                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <?php foreach ($productStats['top_customers'] as $i => $customer): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 text-sm">
                                        <?php if ($i < 3): ?>
                                            <span class="text-lg"><?php echo ['🥇', '🥈', '🥉'][$i]; ?></span>
                                        <?php else: ?>
                                            <span class="text-gray-500"><?php echo $i + 1; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 py-2">
                                        <p class="text-sm font-medium text-gray-900 truncate" title="<?php echo htmlspecialchars($customer['company_name']); ?>">
                                            <?php echo htmlspecialchars(mb_substr($customer['company_name'], 0, 25)) . (mb_strlen($customer['company_name']) > 25 ? '...' : ''); ?>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            <?php echo $customer['country'] === 'BE' ? '🇧🇪' : '🇱🇺'; ?>
                                            <?php echo htmlspecialchars($customer['customer_number']); ?>
                                        </p>
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-orange-100 text-orange-700">
                                            <?php echo number_format($customer['total_quantity'], 0, ',', ' '); ?>
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <button type="button"
                                                onclick="openProductCustomerModal('<?php echo htmlspecialchars($customer['customer_number']); ?>', '<?php echo $customer['country']; ?>', '<?php echo htmlspecialchars(addslashes($customer['company_name'])); ?>')"
                                                class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Top 5 Représentants -->
            <div>
                <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                    <span class="w-6 h-6 bg-indigo-100 rounded-full flex items-center justify-center mr-2">
                        <i class="fas fa-user-tie text-indigo-600 text-xs"></i>
                    </span>
                    Top 5 Représentants
                </h4>
                <?php if (empty($productStats['top_reps'])): ?>
                    <p class="text-gray-500 text-sm italic">Aucune vente enregistrée</p>
                <?php else: ?>
                    <div class="overflow-hidden rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Représentant</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Clients</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Qté</th>
                                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <?php foreach ($productStats['top_reps'] as $i => $rep): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 text-sm">
                                        <?php if ($i < 3): ?>
                                            <span class="text-lg"><?php echo ['🥇', '🥈', '🥉'][$i]; ?></span>
                                        <?php else: ?>
                                            <span class="text-gray-500"><?php echo $i + 1; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 py-2">
                                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($rep['rep_name']); ?></p>
                                        <p class="text-xs text-gray-500">
                                            <?php echo ($rep['rep_country'] ?? 'BE') === 'BE' ? '🇧🇪' : '🇱🇺'; ?>
                                            <?php echo htmlspecialchars($rep['rep_id']); ?>
                                        </p>
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">
                                            <?php echo $rep['customers_count']; ?>
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-orange-100 text-orange-700">
                                            <?php echo number_format($rep['total_quantity'], 0, ',', ' '); ?>
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <?php if (!empty($rep['user_id']) && !empty($product['campaign_id'])): ?>
                                        <a href="/stm/admin/stats/campaigns?campaign_id=<?php echo $product['campaign_id']; ?>&rep_id=<?php echo $rep['user_id']; ?>&rep_country=<?php echo $rep['rep_country'] ?? 'BE'; ?>&country=<?php echo $rep['rep_country'] ?? 'BE'; ?>"
                                           class="text-indigo-600 hover:text-indigo-800 text-xs font-medium"
                                           title="Voir les stats de ce commercial">
                                            <i class="fas fa-chart-bar"></i>
                                        </a>
                                        <?php else: ?>
                                        <span class="text-gray-300 text-xs"><i class="fas fa-chart-bar"></i></span>
                                        <?php endif; ?>
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
</div>

<!-- Modal détail commandes client pour ce produit -->
<div id="productCustomerModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <!-- Overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeProductCustomerModal()"></div>

        <!-- Modal content -->
        <div class="relative bg-white rounded-lg shadow-xl transform transition-all sm:max-w-lg sm:w-full mx-auto">
            <!-- Header -->
            <div class="bg-indigo-600 px-6 py-4 rounded-t-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-white" id="pcModalCustomerName">Détail client</h3>
                        <p class="text-indigo-200 text-sm" id="pcModalProductName"></p>
                    </div>
                    <button type="button" onclick="closeProductCustomerModal()" class="text-white hover:text-indigo-200 transition">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>

            <!-- Body -->
            <div class="px-6 py-4 max-h-[60vh] overflow-y-auto">
                <!-- Loading -->
                <div id="pcModalLoading" class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-indigo-600 text-2xl"></i>
                    <p class="text-gray-500 mt-2">Chargement...</p>
                </div>

                <!-- Error -->
                <div id="pcModalError" class="hidden text-center py-8">
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-exclamation-triangle text-red-500"></i>
                    </div>
                    <p class="text-red-600" id="pcModalErrorText">Erreur</p>
                </div>

                <!-- Content -->
                <div id="pcModalContent" class="hidden">
                    <!-- Stats résumé -->
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="bg-green-50 rounded-lg p-3 text-center">
                            <p class="text-xl font-bold text-green-600" id="pcModalTotalOrders">0</p>
                            <p class="text-xs text-green-700">Commandes</p>
                        </div>
                        <div class="bg-orange-50 rounded-lg p-3 text-center">
                            <p class="text-xl font-bold text-orange-600" id="pcModalTotalQty">0</p>
                            <p class="text-xs text-orange-700">Quantité totale</p>
                        </div>
                    </div>

                    <!-- Liste des commandes -->
                    <div id="pcModalOrdersList" class="space-y-2">
                        <!-- Rempli par JavaScript -->
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 px-6 py-3 rounded-b-lg flex justify-end">
                <button type="button" onclick="closeProductCustomerModal()"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
                    Fermer
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

    <!-- Colonne gauche -->
    <div class="space-y-6">

        <!-- Section : Informations de base -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    📋 Informations de base
                </h3>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Code Promotion</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-medium">
                            <?php echo htmlspecialchars($product['product_code']); ?>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Catégorie</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <?php echo !empty($product['category_name']) ? htmlspecialchars($product['category_name']) : '<span class="text-gray-400">Non catégorisé</span>'; ?>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Section : Images -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    🖼️ Images de la Promotion
                </h3>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <!-- Image FR -->
                    <div>
                        <p class="text-sm font-medium text-gray-700 mb-2">🇫🇷 Français</p>
                        <?php if (!empty($product['image_fr'])): ?>
                            <img src="<?php echo htmlspecialchars($product['image_fr']); ?>"
                                 alt="Image FR"
                                 class="w-full h-48 object-cover rounded-lg border-2 border-gray-200 shadow-sm">
                        <?php else: ?>
                            <div class="w-full h-48 flex items-center justify-center bg-gray-100 rounded-lg border-2 border-gray-200">
                                <span class="text-gray-400 text-sm">Aucune image</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Image NL -->
                    <div>
                        <p class="text-sm font-medium text-gray-700 mb-2">🇳🇱 Nederlands</p>
                        <?php if (!empty($product['image_nl'])): ?>
                            <img src="<?php echo htmlspecialchars($product['image_nl']); ?>"
                                 alt="Image NL"
                                 class="w-full h-48 object-cover rounded-lg border-2 border-gray-200 shadow-sm">
                        <?php else: ?>
                            <div class="w-full h-48 flex items-center justify-center bg-gray-100 rounded-lg border-2 border-gray-200">
                                <span class="text-gray-400 text-sm">Geen afbeelding</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Colonne droite -->
    <div class="space-y-6">

        <!-- Section : Contenu français -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    🇫🇷 Contenu en français
                </h3>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Nom du Promotion</dt>
                        <dd class="mt-1 text-base text-gray-900 font-medium">
                            <?php echo htmlspecialchars($product['name_fr']); ?>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Description</dt>
                        <dd class="mt-1 text-sm text-gray-900 whitespace-pre-line">
                            <?php
                            if (!empty($product['description_fr'])) {
                                echo htmlspecialchars($product['description_fr']);
                            } else {
                                echo '<span class="text-gray-400 italic">Aucune description</span>';
                            }
                            ?>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Section : Contenu néerlandais -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    🇳🇱 Inhoud in het Nederlands
                </h3>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Productnaam</dt>
                        <dd class="mt-1 text-base text-gray-900">
                            <?php
                            if (!empty($product['name_nl'])) {
                                echo htmlspecialchars($product['name_nl']);
                            } else {
                                echo '<span class="text-gray-400 italic">Identique au français</span>';
                            }
                            ?>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Beschrijving</dt>
                        <dd class="mt-1 text-sm text-gray-900 whitespace-pre-line">
                            <?php
                            if (!empty($product['description_nl'])) {
                                echo htmlspecialchars($product['description_nl']);
                            } else {
                                echo '<span class="text-gray-400 italic">Geen beschrijving</span>';
                            }
                            ?>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Section : Paramètres -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    ⚙️ Paramètres
                </h3>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Statut</dt>
                        <dd class="mt-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $product['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $product['is_active'] ? '✓ Actif' : '✗ Inactif'; ?>
                            </span>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Ordre d'affichage</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <?php echo $product['display_order']; ?>
                            <span class="text-gray-500 text-xs ml-2">(Plus petit = apparaît en premier)</span>
                        </dd>
                    </div>

                    <!-- Quotas -->
                    <?php if (!empty($product['max_total']) || !empty($product['max_per_customer'])): ?>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">
                                📊 Quotas
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <div class="space-y-2">
                                    <?php if (!empty($product['max_total'])): ?>
                                        <div>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                🌍 Global : <?php echo number_format($product['max_total'], 0, ',', ' '); ?> unités max
                                            </span>
                                            <p class="text-xs text-gray-500 mt-1">Stock total limité (tous clients confondus)</p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($product['max_per_customer'])): ?>
                                        <div>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                👤 Par client : <?php echo $product['max_per_customer']; ?> unités max
                                            </span>
                                            <p class="text-xs text-gray-500 mt-1">Limite individuelle par client</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </dd>
                        </div>
                    <?php else: ?>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">
                                📊 Quotas
                            </dt>
                            <dd class="mt-1 text-sm text-gray-500">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    ∞ Illimité
                                </span>
                            </dd>
                        </div>
                    <?php endif; ?>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Date de création</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <?php
                            $date = new DateTime($product['created_at']);
                            echo $date->format('d/m/Y à H:i');
                            ?>
                        </dd>
                    </div>

                    <?php if ($product['updated_at'] !== $product['created_at']): ?>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Dernière modification</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <?php
                                $date = new DateTime($product['updated_at']);
                                echo $date->format('d/m/Y à H:i');
                                ?>
                            </dd>
                        </div>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

    </div>
</div>

<!-- Section : Actions -->
<?php if ($canEdit || $canDelete): ?>
<div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-6">
    <h3 class="text-lg font-medium text-gray-900 mb-4">⚡ Actions rapides</h3>
    <div class="flex flex-wrap gap-3">
        <?php if ($canEdit): ?>
        <a href="/stm/admin/products/<?php echo $product['id']; ?>/edit"
           class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
            ✏️ Modifier la Promotion
        </a>
        <?php endif; ?>

        <?php if ($canDelete): ?>
        <form method="POST" action="/stm/admin/products/<?php echo $product['id']; ?>/delete"
              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette Promotion ?');"
              class="inline">
            <input type="hidden" name="_token" value="<?php echo Session::get('csrf_token'); ?>">
            <input type="hidden" name="_method" value="DELETE">
            <button type="submit"
                    class="inline-flex items-center px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50">
                🗑️ Supprimer
            </button>
        </form>
        <?php endif; ?>

        <a href="/stm/admin/products"
           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            ← Retour à la liste
        </a>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();

// JavaScript pour le modal
$productId = $product['id'] ?? 0;
$pageScripts = <<<SCRIPTS
<script>
const productId = {$productId};

function openProductCustomerModal(customerNumber, country, companyName) {
    const modal = document.getElementById('productCustomerModal');
    const loading = document.getElementById('pcModalLoading');
    const error = document.getElementById('pcModalError');
    const content = document.getElementById('pcModalContent');

    // Afficher le modal
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    // Reset
    loading.classList.remove('hidden');
    error.classList.add('hidden');
    content.classList.add('hidden');

    // Header
    document.getElementById('pcModalCustomerName').textContent = companyName;
    document.getElementById('pcModalProductName').textContent = customerNumber + ' - ' + (country === 'BE' ? '🇧🇪 Belgique' : '🇱🇺 Luxembourg');

    // Charger les données
    fetch('/stm/admin/products/customer-orders?product_id=' + productId + '&customer_number=' + encodeURIComponent(customerNumber) + '&country=' + country)
        .then(response => response.json())
        .then(data => {
            loading.classList.add('hidden');

            if (!data.success) {
                error.classList.remove('hidden');
                document.getElementById('pcModalErrorText').textContent = data.error || 'Erreur inconnue';
                return;
            }

            content.classList.remove('hidden');

            // Stats
            document.getElementById('pcModalTotalOrders').textContent = data.total_orders;
            document.getElementById('pcModalTotalQty').textContent = new Intl.NumberFormat('fr-FR').format(data.total_quantity);

            // Liste des commandes
            const ordersList = document.getElementById('pcModalOrdersList');
            ordersList.innerHTML = '';

            if (data.orders.length === 0) {
                ordersList.innerHTML = '<p class="text-center text-gray-500 py-4">Aucune commande</p>';
                return;
            }

            data.orders.forEach(order => {
                const date = new Date(order.created_at);
                const formattedDate = date.toLocaleDateString('fr-FR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                ordersList.innerHTML += '<div class="flex items-center justify-between bg-gray-50 rounded-lg px-4 py-2">' +
                    '<div>' +
                        '<span class="font-medium text-gray-900">Commande #' + order.order_id + '</span>' +
                        '<span class="text-xs text-gray-500 ml-2">' + formattedDate + '</span>' +
                    '</div>' +
                    '<span class="inline-flex items-center px-2.5 py-1 bg-orange-100 text-orange-700 rounded font-bold">' +
                        new Intl.NumberFormat('fr-FR').format(order.quantity) +
                    '</span>' +
                '</div>';
            });
        })
        .catch(err => {
            console.error('Erreur:', err);
            loading.classList.add('hidden');
            error.classList.remove('hidden');
            document.getElementById('pcModalErrorText').textContent = 'Erreur de connexion';
        });
}

function closeProductCustomerModal() {
    document.getElementById('productCustomerModal').classList.add('hidden');
    document.body.style.overflow = '';
}

// Fermer avec Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeProductCustomerModal();
    }
});
</script>
SCRIPTS;

require_once __DIR__ . '/../../layouts/admin.php';
?>