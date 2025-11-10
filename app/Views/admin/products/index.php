<?php
/**
 * Vue : Liste des produits
 * 
 * Affichage de tous les produits avec filtres, recherche et statistiques
 * 
 * @created 11/11/2025 22:30
 * @modified 11/11/2025 23:10 - Vérification cohérence style
 */

use Core\Session;

// Démarrer la capture du contenu pour le layout
ob_start();
?>

<!-- En-tête de la page -->
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Produits</h1>
            <p class="mt-2 text-sm text-gray-600">Gestion du catalogue de produits</p>
        </div>
        <a href="/stm/admin/products/create" 
           class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
            ➕ Nouveau produit
        </a>
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
                    <span class="text-gray-500">Produits</span>
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
                        <dt class="text-sm font-medium text-gray-500 truncate">Total produits</dt>
                        <dd class="text-2xl font-bold text-gray-900"><?php echo $stats['total']; ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Actifs -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <span class="text-3xl">✅</span>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Produits actifs</dt>
                        <dd class="text-2xl font-bold text-green-600"><?php echo $stats['active']; ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Inactifs -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <span class="text-3xl">❌</span>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Produits inactifs</dt>
                        <dd class="text-2xl font-bold text-red-600"><?php echo $stats['inactive']; ?></dd>
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
                        <dd class="text-2xl font-bold text-indigo-600"><?php echo $stats['categories']; ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtres et recherche -->
<div class="bg-white shadow rounded-lg mb-6">
    <div class="px-4 py-5 sm:p-6">
        <form method="GET" action="/stm/admin/products" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                
                <!-- Recherche -->
                <div class="sm:col-span-2">
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">
                        🔍 Recherche
                    </label>
                    <input type="text" 
                           name="search" 
                           id="search"
                           value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                           placeholder="Code, nom, EAN..."
                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>

                <!-- Catégorie -->
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

                <!-- Statut -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                        ⚡ Statut
                    </label>
                    <select name="status" 
                            id="status"
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="">Tous</option>
                        <option value="active" <?php echo (isset($_GET['status']) && $_GET['status'] === 'active') ? 'selected' : ''; ?>>Actifs</option>
                        <option value="inactive" <?php echo (isset($_GET['status']) && $_GET['status'] === 'inactive') ? 'selected' : ''; ?>>Inactifs</option>
                    </select>
                </div>

            </div>

            <!-- Boutons -->
            <div class="flex items-center gap-2">
                <button type="submit" 
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                    🔎 Filtrer
                </button>
                <a href="/stm/admin/products" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    🔄 Réinitialiser
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Liste des produits -->
<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            📋 Liste des produits
            <span class="text-sm text-gray-500 font-normal ml-2">(<?php echo count($products); ?> résultat<?php echo count($products) > 1 ? 's' : ''; ?>)</span>
        </h3>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Image
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Produit
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Codes
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
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="text-gray-400">
                                <span class="text-4xl mb-2 block">📦</span>
                                <p class="text-sm">Aucun produit trouvé</p>
                                <a href="/stm/admin/products/create" 
                                   class="mt-3 inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200">
                                    ➕ Créer le premier produit
                                </a>
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

                            <!-- Produit -->
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

                            <!-- Codes -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 font-medium">
                                    <?php echo htmlspecialchars($product['product_code']); ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    Colis: <?php echo htmlspecialchars($product['package_number']); ?>
                                </div>
                                <?php if ($product['ean']): ?>
                                    <div class="text-xs text-gray-500 font-mono">
                                        EAN: <?php echo htmlspecialchars($product['ean']); ?>
                                    </div>
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

                            <!-- Statut -->
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $product['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo $product['is_active'] ? '✓ Actif' : '✗ Inactif'; ?>
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
                                    <a href="/stm/admin/products/<?php echo $product['id']; ?>/edit" 
                                       class="text-gray-600 hover:text-gray-900"
                                       title="Modifier">
                                        ✏️
                                    </a>
                                    <form method="POST" 
                                          action="/stm/admin/products/<?php echo $product['id']; ?>/delete" 
                                          onsubmit="return confirm('Supprimer ce produit ?');"
                                          class="inline">
                                        <input type="hidden" name="_token" value="<?php echo Session::get('csrf_token'); ?>">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <button type="submit" 
                                                class="text-red-600 hover:text-red-900"
                                                title="Supprimer">
                                            🗑️
                                        </button>
                                    </form>
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