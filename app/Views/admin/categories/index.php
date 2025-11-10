<?php
/**
 * Vue : Liste des catégories
 * 
 * Affiche toutes les catégories avec filtres, statistiques et actions CRUD
 * 
 * @package STM
 * @version 1.0
 * @created 11/11/2025
 */

use Core\Session;

$title = 'Catégories de produits';
$categories = $categories ?? [];
$stats = $stats ?? ['total' => 0, 'active' => 0, 'inactive' => 0];

ob_start();
?>

<!-- Header de page -->
<div class="mb-8">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Catégories de produits</h1>
            <p class="mt-2 text-sm text-gray-600">
                Gérer les catégories de votre catalogue produits
            </p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="/stm/admin/categories/create" 
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nouvelle catégorie
            </a>
        </div>
    </div>
</div>

<!-- Messages flash -->
<?php if (Session::hasFlash('success')): ?>
    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 rounded-lg p-4">
        <div class="flex">
            <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="ml-3 text-sm font-medium"><?= htmlspecialchars(Session::getFlash('success')) ?></p>
        </div>
    </div>
<?php endif; ?>

<?php if (Session::hasFlash('error')): ?>
    <div class="mb-6 bg-red-50 border border-red-200 text-red-800 rounded-lg p-4">
        <div class="flex">
            <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="ml-3 text-sm font-medium"><?= htmlspecialchars(Session::getFlash('error')) ?></p>
        </div>
    </div>
<?php endif; ?>

<!-- Statistiques -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-3 mb-6">
    <!-- Total -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Total catégories</dt>
                        <dd class="text-3xl font-bold text-gray-900"><?= $stats['total'] ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Actives -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Actives</dt>
                        <dd class="text-3xl font-bold text-green-600"><?= $stats['active'] ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Inactives -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Inactives</dt>
                        <dd class="text-3xl font-bold text-gray-600"><?= $stats['inactive'] ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="bg-white shadow rounded-lg p-4 mb-6">
    <form method="GET" action="/stm/admin/categories" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        
        <!-- Recherche -->
        <div>
            <label for="search" class="block text-sm font-medium text-gray-700">Rechercher</label>
            <input type="text" 
                   name="search" 
                   id="search" 
                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                   placeholder="Code ou nom..."
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
        </div>

        <!-- Statut -->
        <div>
            <label for="active" class="block text-sm font-medium text-gray-700">Statut</label>
            <select name="active" 
                    id="active"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                <option value="">Tous les statuts</option>
                <option value="1" <?= ($_GET['active'] ?? '') === '1' ? 'selected' : '' ?>>Actives uniquement</option>
                <option value="0" <?= ($_GET['active'] ?? '') === '0' ? 'selected' : '' ?>>Inactives uniquement</option>
            </select>
        </div>

        <!-- Boutons -->
        <div class="flex items-end space-x-2">
            <button type="submit" 
                    class="flex-1 px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700">
                Filtrer
            </button>
            <a href="/stm/admin/categories" 
               class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                Réinitialiser
            </a>
        </div>
        
    </form>
</div>

<!-- Table des catégories -->
<div class="bg-white shadow rounded-lg overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Icône
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Code
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Nom FR / NL
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Ordre
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Statut
                </th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                </th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            
            <?php if (empty($categories)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Aucune catégorie</h3>
                        <p class="mt-1 text-sm text-gray-500">Commencez par créer votre première catégorie.</p>
                        <div class="mt-6">
                            <a href="/stm/admin/categories/create" 
                               class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Nouvelle catégorie
                            </a>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                
                <?php foreach ($categories as $category): ?>
                <tr class="hover:bg-gray-50">
                    
                    <!-- Icône -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($category['icon_path']): ?>
                            <div class="h-10 w-10 rounded flex items-center justify-center" 
                                 style="background-color: <?= htmlspecialchars($category['color']) ?>;">
                                <img src="<?= htmlspecialchars($category['icon_path']) ?>" 
                                     alt="<?= htmlspecialchars($category['name_fr']) ?>" 
                                     class="h-6 w-6 object-contain">
                            </div>
                        <?php else: ?>
                            <div class="h-10 w-10 rounded flex items-center justify-center bg-gray-200">
                                <span class="text-gray-500 text-xs">-</span>
                            </div>
                        <?php endif; ?>
                    </td>

                    <!-- Code -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">
                            <?= htmlspecialchars($category['code']) ?>
                        </div>
                    </td>

                    <!-- Noms FR / NL -->
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">
                            <span class="font-medium">🇫🇷</span> <?= htmlspecialchars($category['name_fr']) ?>
                        </div>
                        <div class="text-sm text-gray-500 mt-1">
                            <span class="font-medium">🇳🇱</span> <?= htmlspecialchars($category['name_nl']) ?>
                        </div>
                    </td>

                    <!-- Ordre -->
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?= htmlspecialchars($category['display_order']) ?>
                    </td>

                    <!-- Statut -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($category['is_active']): ?>
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                <svg class="mr-1.5 h-2 w-2 fill-green-500" viewBox="0 0 6 6">
                                    <circle cx="3" cy="3" r="3" />
                                </svg>
                                Active
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">
                                <svg class="mr-1.5 h-2 w-2 fill-gray-500" viewBox="0 0 6 6">
                                    <circle cx="3" cy="3" r="3" />
                                </svg>
                                Inactive
                            </span>
                        <?php endif; ?>
                    </td>

                    <!-- Actions -->
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex justify-end gap-x-2">
                            <!-- Voir -->
                            <a href="/stm/admin/categories/<?= $category['id'] ?>" 
                               class="text-purple-600 hover:text-purple-900"
                               title="Voir les détails">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </a>
                            <!-- Modifier -->
                            <a href="/stm/admin/categories/<?= $category['id'] ?>/edit" 
                               class="text-blue-600 hover:text-blue-900"
                               title="Modifier">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                </svg>
                            </a>
                            <!-- Supprimer -->
                            <form method="POST" 
                                  action="/stm/admin/categories/<?= $category['id'] ?>/delete" 
                                  onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?')"
                                  class="inline">
                                <input type="hidden" name="_token" value="<?= Session::get('csrf_token') ?>">
                                <button type="submit" 
                                        class="text-red-600 hover:text-red-900"
                                        title="Supprimer">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
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

<?php if (!empty($categories)): ?>
    <div class="mt-4 text-sm text-gray-600 text-center">
        <?= count($categories) ?> catégorie<?= count($categories) > 1 ? 's' : '' ?> affichée<?= count($categories) > 1 ? 's' : '' ?>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
?>