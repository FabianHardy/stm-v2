<?php
/**
 * Vue : Liste des catégories
 * 
 * Affiche toutes les catégories avec filtres, stats et actions.
 * 
 * @modified 11/11/2025 10:00 - Création initiale
 */

// 1. Capturer le contenu
ob_start();
?>

<!-- Breadcrumb -->
<nav class="flex mb-6" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-3">
        <li class="inline-flex items-center">
            <a href="/stm/admin/dashboard" class="text-gray-700 hover:text-indigo-600">
                <i class="fas fa-home mr-2"></i>Tableau de bord
            </a>
        </li>
        <li>
            <div class="flex items-center">
                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                <span class="text-gray-500">Catégories</span>
            </div>
        </li>
    </ol>
</nav>

<!-- En-tête avec statistiques -->
<div class="flex justify-between items-start mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Catégories de produits</h1>
        <p class="text-gray-600">Gestion des catégories pour organiser les produits</p>
    </div>
    <a href="/stm/admin/products/categories/create" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
        <i class="fas fa-plus mr-2"></i>
        Nouvelle catégorie
    </a>
</div>

<!-- Statistiques -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Total</p>
                <p class="text-3xl font-bold text-gray-900 mt-1"><?= $stats['total'] ?></p>
            </div>
            <div class="bg-indigo-100 rounded-full p-3">
                <i class="fas fa-tags text-indigo-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Actives</p>
                <p class="text-3xl font-bold text-green-600 mt-1"><?= $stats['active'] ?></p>
            </div>
            <div class="bg-green-100 rounded-full p-3">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Inactives</p>
                <p class="text-3xl font-bold text-gray-400 mt-1"><?= $stats['inactive'] ?></p>
            </div>
            <div class="bg-gray-100 rounded-full p-3">
                <i class="fas fa-times-circle text-gray-400 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-6 border border-gray-200">
    <form method="GET" action="/stm/admin/products/categories" class="flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                Statut
            </label>
            <select name="status" id="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <option value="">Toutes</option>
                <option value="active" <?= (isset($_GET['status']) && $_GET['status'] === 'active') ? 'selected' : '' ?>>
                    Actives uniquement
                </option>
                <option value="inactive" <?= (isset($_GET['status']) && $_GET['status'] === 'inactive') ? 'selected' : '' ?>>
                    Inactives uniquement
                </option>
            </select>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                <i class="fas fa-filter mr-2"></i>Filtrer
            </button>
            <a href="/stm/admin/products/categories" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                <i class="fas fa-times mr-2"></i>Réinitialiser
            </a>
        </div>
    </form>
</div>

<!-- Messages flash -->
<?php if ($success = \Core\Session::getFlash('success')): ?>
<div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded">
    <div class="flex items-center">
        <i class="fas fa-check-circle text-green-500 mr-3"></i>
        <p class="text-green-700"><?= htmlspecialchars($success) ?></p>
    </div>
</div>
<?php endif; ?>

<?php if ($error = \Core\Session::getFlash('error')): ?>
<div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
    <div class="flex items-center">
        <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
        <p class="text-red-700"><?= htmlspecialchars($error) ?></p>
    </div>
</div>
<?php endif; ?>

<!-- Liste des catégories -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <?php if (empty($categories)): ?>
        <div class="text-center py-12">
            <i class="fas fa-tags text-gray-300 text-5xl mb-4"></i>
            <p class="text-gray-500 text-lg">Aucune catégorie trouvée</p>
            <a href="/stm/admin/products/categories/create" class="inline-block mt-4 px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                Créer la première catégorie
            </a>
        </div>
    <?php else: ?>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Ordre
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Catégorie
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Code
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Couleur
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Statut
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($categories as $category): ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <span class="font-semibold"><?= htmlspecialchars($category['display_order']) ?></span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <?php if (!empty($category['icon_path'])): ?>
                                <img src="<?= htmlspecialchars($category['icon_path']) ?>" 
                                     alt="<?= htmlspecialchars($category['name_fr']) ?>" 
                                     class="h-8 w-8 mr-3 rounded">
                            <?php else: ?>
                                <div class="h-8 w-8 mr-3 rounded flex items-center justify-center" 
                                     style="background-color: <?= htmlspecialchars($category['color']) ?>">
                                    <i class="fas fa-tag text-white text-xs"></i>
                                </div>
                            <?php endif; ?>
                            <div>
                                <div class="font-medium text-gray-900">
                                    <?= htmlspecialchars($category['name_fr']) ?>
                                </div>
                                <?php if (!empty($category['name_nl'])): ?>
                                    <div class="text-sm text-gray-500">
                                        <?= htmlspecialchars($category['name_nl']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <code class="px-2 py-1 bg-gray-100 rounded text-gray-700 font-mono">
                            <?= htmlspecialchars($category['code']) ?>
                        </code>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="h-6 w-6 rounded border border-gray-300 mr-2" 
                                 style="background-color: <?= htmlspecialchars($category['color']) ?>">
                            </div>
                            <code class="text-xs text-gray-600 font-mono">
                                <?= htmlspecialchars($category['color']) ?>
                            </code>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($category['is_active']): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>Active
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                <i class="fas fa-times-circle mr-1"></i>Inactive
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div class="flex items-center gap-2">
                            <a href="/stm/admin/products/categories/<?= $category['id'] ?>" 
                               class="text-indigo-600 hover:text-indigo-900" 
                               title="Voir les détails">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="/stm/admin/products/categories/<?= $category['id'] ?>/edit" 
                               class="text-blue-600 hover:text-blue-900" 
                               title="Modifier">
                                <i class="fas fa-edit"></i>
                            </a>
                            
                            <!-- Toggle Active -->
                            <form method="POST" action="/stm/admin/products/categories/<?= $category['id'] ?>/toggle" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                <button type="submit" 
                                        class="<?= $category['is_active'] ? 'text-yellow-600 hover:text-yellow-900' : 'text-green-600 hover:text-green-900' ?>" 
                                        title="<?= $category['is_active'] ? 'Désactiver' : 'Activer' ?>">
                                    <i class="fas fa-<?= $category['is_active'] ? 'toggle-on' : 'toggle-off' ?>"></i>
                                </button>
                            </form>
                            
                            <!-- Supprimer -->
                            <form method="POST" action="/stm/admin/products/categories/<?= $category['id'] ?>/delete" 
                                  onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?')" 
                                  class="inline">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                <button type="submit" class="text-red-600 hover:text-red-900" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
// 2. Variables pour le layout
$content = ob_get_clean();
$title = 'Catégories de produits - STM';

// 3. Inclure le layout (2 niveaux à remonter depuis categories/)
require __DIR__ . '/../../layouts/admin.php';
?>