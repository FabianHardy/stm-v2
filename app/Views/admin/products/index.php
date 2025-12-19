<?php
/**
 * Vue : Liste des Promotions
 *
 * Affichage de tous les Promotions avec filtres, recherche et statistiques
 *
 * @created 11/11/2025 22:30
 * @modified 16/12/2025 - Ajout filtrage permissions sur boutons
 * @modified 19/12/2025 - Filtre par statut campagne + affichage statut basé sur campagne
 */

use Core\Session;
use App\Helpers\PermissionHelper;

// Permissions pour les boutons
$canCreate = PermissionHelper::can('products.create');
$canEdit = PermissionHelper::can('products.edit');
$canDelete = PermissionHelper::can('products.delete');

// Démarrer la capture du contenu pour le layout
ob_start();
?>

<!-- En-tête de la page -->
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Promotions</h1>
            <p class="mt-2 text-sm text-gray-600">Gestion du catalogue de Promotions</p>
        </div>
        <?php if ($canCreate): ?>
        <a href="/stm/admin/products/create"
           class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
            ➕ Nouvelle Promotion
        </a>
        <?php endif; ?>
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
                    <span class="text-gray-500">Promotions</span>
                </div>
            </li>
        </ol>
    </nav>
</div>

<!-- Statistiques -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
    <!-- Total -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <span class="text-3xl">📦</span>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Promotions affichées</dt>
                        <dd class="text-2xl font-bold text-gray-900"><?php echo $stats['total']; ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Actives (campagne active + promo active) -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <span class="text-3xl">✅</span>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Réellement actives</dt>
                        <dd class="text-2xl font-bold text-green-600"><?php echo $stats['active']; ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Non actives -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <span class="text-3xl">⏸️</span>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Non actives</dt>
                        <dd class="text-2xl font-bold text-gray-600"><?php echo $stats['inactive']; ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Catégories -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <span class="text-3xl">📁</span>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Catégories</dt>
                        <dd class="text-2xl font-bold text-indigo-600"><?php echo isset($stats['categories']) ? $stats['categories'] : count($categories); ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtres et recherche -->
<div class="bg-white shadow rounded-lg mb-6">
    <div class="px-4 py-5 sm:p-6">
        <form method="GET" action="/stm/admin/products" class="space-y-4"
              x-data="{
                  campaignStatus: '<?php echo htmlspecialchars($_GET['campaign_status'] ?? 'active'); ?>',
                  country: '<?php echo htmlspecialchars($_GET['country'] ?? ''); ?>',
                  campaignId: '<?php echo htmlspecialchars($_GET['campaign_id'] ?? ''); ?>',
                  campaigns: <?php echo json_encode(array_map(function($c) {
                      return [
                          'id' => $c['id'],
                          'name' => $c['name'],
                          'country' => $c['country'],
                          'status' => $c['computed_status']
                      ];
                  }, $campaigns)); ?>,
                  get filteredCampaigns() {
                      return this.campaigns.filter(c => {
                          // Filtre par statut
                          if (this.campaignStatus && this.campaignStatus !== 'all') {
                              if (c.status !== this.campaignStatus) return false;
                          }
                          // Filtre par pays
                          if (this.country && c.country !== this.country) return false;
                          return true;
                      });
                  },
                  resetCampaignIfInvalid() {
                      const validIds = this.filteredCampaigns.map(c => String(c.id));
                      if (this.campaignId && !validIds.includes(this.campaignId)) {
                          this.campaignId = '';
                      }
                  }
              }"
              x-init="$watch('campaignStatus', () => resetCampaignIfInvalid()); $watch('country', () => resetCampaignIfInvalid());">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6">

                <!-- 1. Statut Campagne -->
                <div>
                    <label for="campaign_status" class="block text-sm font-medium text-gray-700 mb-1">
                        📊 Statut
                    </label>
                    <select name="campaign_status"
                            id="campaign_status"
                            x-model="campaignStatus"
                            autocomplete="off"
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="active">Actives</option>
                        <option value="upcoming">À venir</option>
                        <option value="ended">Terminées</option>
                        <option value="all">Toutes</option>
                    </select>
                </div>

                <!-- 2. Pays -->
                <div>
                    <label for="country" class="block text-sm font-medium text-gray-700 mb-1">
                        🌍 Pays
                    </label>
                    <select name="country"
                            id="country"
                            x-model="country"
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="">Tous</option>
                        <option value="BE">🇧🇪 Belgique</option>
                        <option value="LU">🇱🇺 Luxembourg</option>
                    </select>
                </div>

                <!-- 3. Campagne spécifique (filtré dynamiquement) -->
                <div>
                    <label for="campaign_id" class="block text-sm font-medium text-gray-700 mb-1">
                        📢 Campagne
                    </label>
                    <select name="campaign_id"
                            id="campaign_id"
                            x-model="campaignId"
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="">Toutes</option>
                        <template x-for="camp in filteredCampaigns" :key="camp.id">
                            <option :value="camp.id" x-text="camp.name + ' (' + camp.country + ')'"></option>
                        </template>
                    </select>
                </div>

                <!-- 4. Catégorie -->
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-1">
                        📁 Catégorie
                    </label>
                    <select name="category"
                            id="category"
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="">Toutes</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"
                                    <?php echo (isset($_GET['category']) && $_GET['category'] == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name_fr']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 5. Recherche -->
                <div class="lg:col-span-2">
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">
                        🔍 Recherche
                    </label>
                    <input type="text"
                           name="search"
                           id="search"
                           value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                           placeholder="Code, nom..."
                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                    🔍 Filtrer
                </button>
                <a href="/stm/admin/products?campaign_status=all"
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    📋 Voir tout
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tableau des Promotions -->
<div class="bg-white shadow overflow-hidden rounded-lg">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Image
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Promotion
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Code
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Campagne
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Catégorie
                    </th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Statut
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="text-gray-400">
                                <span class="text-4xl mb-2 block">📦</span>
                                <p class="text-sm">Aucune Promotion trouvé</p>
                                <?php if ($canCreate): ?>
                                <a href="/stm/admin/products/create"
                                   class="mt-3 inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200">
                                    ➕ Créer la première Promotion
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <tr class="hover:bg-gray-50">
                            <!-- Image -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (!empty($product['image_fr'])): ?>
                                    <img src="<?php echo htmlspecialchars($product['image_fr']); ?>"
                                         alt="<?php echo htmlspecialchars($product['name_fr']); ?>"
                                         class="h-12 w-12 object-cover rounded border border-gray-200">
                                <?php else: ?>
                                    <div class="h-12 w-12 bg-gray-100 rounded border border-gray-200 flex items-center justify-center">
                                        <span class="text-gray-400 text-xs">📷</span>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- Promotion -->
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($product['name_fr']); ?>
                                </div>
                                <?php if (!empty($product['name_nl'])): ?>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($product['name_nl']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- Code -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 font-mono font-medium">
                                    <?php echo htmlspecialchars($product['product_code']); ?>
                                </div>
                            </td>

                            <!-- Campagne -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (!empty($product['campaign_name'])): ?>
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($product['campaign_name']); ?></div>
                                    <div class="text-xs text-gray-500">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium <?php echo $product['campaign_country'] === 'BE' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo $product['campaign_country']; ?>
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400">-</span>
                                <?php endif; ?>
                            </td>

                            <!-- Catégorie -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (!empty($product['category_name'])): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                        <?php echo htmlspecialchars($product['category_name']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400">Non catégorisé</span>
                                <?php endif; ?>
                            </td>

                            <!-- Statut (basé sur la campagne) -->
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <?php
                                // Calculer le statut en fonction de la campagne
                                $today = date('Y-m-d');
                                $campaignActive = $product['campaign_is_active'] ?? 0;
                                $campaignStart = $product['campaign_start_date'] ?? null;
                                $campaignEnd = $product['campaign_end_date'] ?? null;
                                $promoActive = $product['is_active'];

                                if (!$campaignActive) {
                                    // Campagne désactivée
                                    $statusClass = 'bg-red-100 text-red-800';
                                    $statusLabel = '✗ Inactive';
                                } elseif (!$promoActive) {
                                    // Promo désactivée
                                    $statusClass = 'bg-red-100 text-red-800';
                                    $statusLabel = '✗ Inactive';
                                } elseif ($campaignStart && $today < $campaignStart) {
                                    // Campagne à venir
                                    $statusClass = 'bg-blue-100 text-blue-800';
                                    $statusLabel = '⏳ À venir';
                                } elseif ($campaignEnd && $today > $campaignEnd) {
                                    // Campagne terminée
                                    $statusClass = 'bg-gray-100 text-gray-800';
                                    $statusLabel = '⏹ Terminée';
                                } else {
                                    // Active
                                    $statusClass = 'bg-green-100 text-green-800';
                                    $statusLabel = '✓ Active';
                                }
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                    <?php echo $statusLabel; ?>
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="/stm/admin/products/<?php echo $product['id']; ?>"
                                       class="text-indigo-600 hover:text-indigo-900"
                                       title="Voir les détails">
                                        👁️
                                    </a>
                                    <?php if ($canEdit): ?>
                                    <a href="/stm/admin/products/<?php echo $product['id']; ?>/edit"
                                       class="text-gray-600 hover:text-gray-900"
                                       title="Modifier">
                                        ✏️
                                    </a>
                                    <?php endif; ?>
                                    <?php if ($canDelete): ?>
                                    <form method="POST"
                                          action="/stm/admin/products/<?php echo $product['id']; ?>/delete"
                                          onsubmit="return confirm('Supprimer cette Promotion ?');"
                                          class="inline">
                                        <input type="hidden" name="_token" value="<?php echo Session::get('csrf_token'); ?>">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <button type="submit"
                                                class="text-red-600 hover:text-red-900"
                                                title="Supprimer">
                                            🗑️
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination (si nécessaire) -->
<?php if (!empty($pagination) && $pagination['total_pages'] > 1): ?>
    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6 mt-6">
        <div class="flex-1 flex justify-between sm:hidden">
            <?php if ($pagination['current_page'] > 1): ?>
                <a href="?page=<?php echo $pagination['current_page'] - 1; ?><?php echo !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo !empty($_GET['category']) ? '&category=' . $_GET['category'] : ''; ?><?php echo !empty($_GET['status']) ? '&status=' . $_GET['status'] : ''; ?>"
                   class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Précédent
                </a>
            <?php endif; ?>
            <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                <a href="?page=<?php echo $pagination['current_page'] + 1; ?><?php echo !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo !empty($_GET['category']) ? '&category=' . $_GET['category'] : ''; ?><?php echo !empty($_GET['status']) ? '&status=' . $_GET['status'] : ''; ?>"
                   class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Suivant
                </a>
            <?php endif; ?>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700">
                    Affichage de
                    <span class="font-medium"><?php echo (($pagination['current_page'] - 1) * $pagination['per_page']) + 1; ?></span>
                    à
                    <span class="font-medium"><?php echo min($pagination['current_page'] * $pagination['per_page'], $pagination['total']); ?></span>
                    sur
                    <span class="font-medium"><?php echo $pagination['total']; ?></span>
                    résultats
                </p>
            </div>
            <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo !empty($_GET['category']) ? '&category=' . $_GET['category'] : ''; ?><?php echo !empty($_GET['status']) ? '&status=' . $_GET['status'] : ''; ?>"
                           class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo $i === $pagination['current_page'] ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </nav>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
?>