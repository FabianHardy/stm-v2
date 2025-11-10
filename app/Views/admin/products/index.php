<?php
/**
 * Vue : Liste des produits
 * 
 * Affiche la liste de tous les produits avec filtres et statistiques
 * 
 * @created 11/11/2025 21:40
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
</div>

<!-- Messages flash -->
<?php if ($success): ?>
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded relative">
        <span class="block sm:inline"><?php echo htmlspecialchars($success); ?></span>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded relative">
        <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
    </div>
<?php endif; ?>

<!-- Statistiques -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <!-- Total produits -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                    <span class="text-2xl">📦</span>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Total</dt>
                        <dd class="text-lg font-medium text-gray-900"><?php echo $stats['total']; ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Produits actifs -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                    <span class="text-2xl">✅</span>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Actifs</dt>
                        <dd class="text-lg font-medium text-gray-900"><?php echo $stats['active']; ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Produits inactifs -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-gray-500 rounded-md p-3">
                    <span class="text-2xl">❌</span>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Inactifs</dt>
                        <dd class="text-lg font-medium text-gray-900"><?php echo $stats['inactive']; ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Sans catégorie -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                    <span class="text-2xl">⚠️</span>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Sans catégorie</dt>
                        <dd class="text-lg font-medium text-gray-900"><?php echo $stats['without_category']; ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="bg-white shadow rounded-lg p-4 mb-6">
    <form method="GET" action="/stm/admin/products" class="flex flex-wrap gap-4">
        
        <!-- Recherche -->
        <div class="flex-1 min-w-64">
            <input type="text" 
                   name="search" 
                   placeholder="Rechercher (nom, code, n° colis...)" 
                   value="<?php echo htmlspecialchars($filters['search']); ?>"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md">
        </div>

        <!-- Catégorie -->
        <div class="w-48">
            <select name="category_id" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                <option value="">Toutes les catégories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" 
                            <?php echo ($filters['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name_fr']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Statut -->
        <div class="w-40">
            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                <option value="">Tous les statuts</option>
                <option value="active" <?php echo ($filters['status'] === 'active') ? 'selected' : ''; ?>>Actifs</option>
                <option value="inactive" <?php echo ($filters['status'] === 'inactive') ? 'selected' : ''; ?>>Inactifs</option>
            </select>
        </div>

        <!-- Boutons -->
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                🔍 Filtrer
            </button>
            <a href="/stm/admin/products" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                ↻ Réinitialiser
            </a>
        </div>
    </form>
</div>

<!-- Table des produits -->
<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produit</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Codes</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Catégorie</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            
            <?php if (empty($products)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                        Aucun produit trouvé
                    </td>
                </tr>
            <?php else: ?>
                
                <?php foreach ($products as $product): ?>
                <tr class="hover:bg-gray-50">
                    
                    <!-- Image -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if (!empty($product['image_fr'])): ?>
                            <img src="<?php echo htmlspecialchars($product['image_fr']); ?>" 
                                 alt="Image produit" 
                                 class="h-12 w-12 rounded object-cover">
                        <?php else: ?>
                            <div class="h-12 w-12 rounded bg-gray-200 flex items-center justify-center">
                                <span class="text-gray-400">📦</span>
                            </div>
                        <?php endif; ?>
                    </td>

                    <!-- Produit -->
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($product['name_fr']); ?>
                        </div>
                        <?php if (!empty($product['name_nl']) && $product['name_nl'] !== $product['name_fr']): ?>
                            <div class="text-sm text-gray-500">
                                🇳🇱 <?php echo htmlspecialchars($product['name_nl']); ?>
                            </div>
                        <?php endif; ?>
                    </td>

                    <!-- Codes -->
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">
                            <span class="font-mono"><?php echo htmlspecialchars($product['product_code']); ?></span>
                        </div>
                        <div class="text-xs text-gray-500">
                            N° colis: <?php echo htmlspecialchars($product['package_number']); ?>
                        </div>
                    </td>

                    <!-- Catégorie -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if (!empty($product['category_name'])): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                  style="background-color: <?php echo htmlspecialchars($product['category_color']); ?>20; color: <?php echo htmlspecialchars($product['category_color']); ?>;">
                                <?php echo htmlspecialchars($product['category_name']); ?>
                            </span>
                        <?php else: ?>
                            <span class="text-sm text-gray-400 italic">Sans catégorie</span>
                        <?php endif; ?>
                    </td>

                    <!-- Statut -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($product['is_active']): ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                Actif
                            </span>
                        <?php else: ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                Inactif
                            </span>
                        <?php endif; ?>
                    </td>

                    <!-- Actions -->
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex justify-end gap-2">
                            <a href="/stm/admin/products/<?php echo $product['id']; ?>" 
                               class="text-indigo-600 hover:text-indigo-900"
                               title="Voir">
                                👁️
                            </a>
                            <a href="/stm/admin/products/<?php echo $product['id']; ?>/edit" 
                               class="text-blue-600 hover:text-blue-900"
                               title="Modifier">
                                ✏️
                            </a>
                            <form method="POST" 
                                  action="/stm/admin/products/<?php echo $product['id']; ?>/delete" 
                                  onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ?');"
                                  class="inline">
                                <input type="hidden" name="_token" value="<?php echo Session::get('csrf_token'); ?>">
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

<?php if (!empty($products)): ?>
    <div class="mt-4 text-sm text-gray-600 text-center">
        <?php echo count($products); ?> produit<?php echo count($products) > 1 ? 's' : ''; ?> affiché<?php echo count($products) > 1 ? 's' : ''; ?>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
?>