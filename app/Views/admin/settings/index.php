<?php
/**
 * Vue : Configuration système
 * 
 * Affiche la configuration avec onglets :
 * - Permissions (matrice rôles × permissions)
 * - Général (à venir)
 * 
 * @package STM
 * @created 2025/12/10
 */

use App\Models\User;

$activeMenu = 'settings';
ob_start();
?>

<!-- En-tête -->
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Configuration</h1>
            <p class="text-gray-600 mt-1">Paramètres système et permissions</p>
        </div>
    </div>
</div>

<!-- Onglets -->
<div class="mb-6">
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex gap-6">
            <a href="?tab=permissions" 
               class="py-3 px-1 border-b-2 font-medium text-sm transition <?= $activeTab === 'permissions' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                <i class="fas fa-shield-alt mr-2"></i>Permissions
            </a>
            <a href="?tab=general" 
               class="py-3 px-1 border-b-2 font-medium text-sm transition <?= $activeTab === 'general' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                <i class="fas fa-cog mr-2"></i>Général
            </a>
        </nav>
    </div>
</div>

<?php if ($activeTab === 'permissions'): ?>
<!-- Onglet Permissions -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    
    <!-- En-tête -->
    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Matrice des permissions</h2>
                <p class="text-sm text-gray-500 mt-1">Permissions accordées par rôle</p>
            </div>
            <div class="flex items-center gap-4 text-sm">
                <span class="flex items-center gap-2">
                    <span class="w-5 h-5 bg-green-100 rounded flex items-center justify-center">
                        <i class="fas fa-check text-green-600 text-xs"></i>
                    </span>
                    Autorisé
                </span>
                <span class="flex items-center gap-2">
                    <span class="w-5 h-5 bg-gray-100 rounded flex items-center justify-center">
                        <i class="fas fa-times text-gray-400 text-xs"></i>
                    </span>
                    Non autorisé
                </span>
            </div>
        </div>
    </div>
    
    <!-- Tableau des permissions -->
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider w-64">
                        Permission
                    </th>
                    <?php foreach ($roleLabels as $roleKey => $roleLabel): ?>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider w-28 <?= User::getRoleColor($roleKey) ?>">
                        <?= htmlspecialchars($roleLabel) ?>
                    </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($permissionCategories as $catKey => $category): ?>
                
                <!-- Ligne de catégorie -->
                <tr class="bg-gray-50">
                    <td colspan="<?= count($roleLabels) + 1 ?>" class="px-6 py-3">
                        <div class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                            <i class="fas <?= $category['icon'] ?> text-gray-500"></i>
                            <?= htmlspecialchars($category['label']) ?>
                        </div>
                    </td>
                </tr>
                
                <?php foreach ($category['permissions'] as $permKey): ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-3 text-sm text-gray-700 pl-10">
                        <?= htmlspecialchars($permissionLabels[$permKey] ?? $permKey) ?>
                        <span class="text-xs text-gray-400 ml-2">(<?= $permKey ?>)</span>
                    </td>
                    
                    <?php foreach ($roleLabels as $roleKey => $roleLabel): ?>
                    <td class="px-4 py-3 text-center">
                        <?php $hasPermission = $permissionMatrix[$roleKey][$permKey] ?? false; ?>
                        <?php if ($hasPermission): ?>
                        <span class="inline-flex items-center justify-center w-6 h-6 bg-green-100 rounded-full">
                            <i class="fas fa-check text-green-600 text-xs"></i>
                        </span>
                        <?php else: ?>
                        <span class="inline-flex items-center justify-center w-6 h-6 bg-gray-100 rounded-full">
                            <i class="fas fa-times text-gray-400 text-xs"></i>
                        </span>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
                
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Footer info -->
    <div class="p-4 bg-gray-50 border-t border-gray-200">
        <p class="text-xs text-gray-500">
            <i class="fas fa-info-circle mr-1"></i>
            Les permissions sont définies par rôle. Pour modifier les accès d'un utilisateur, changez son rôle dans la gestion des utilisateurs.
        </p>
    </div>
</div>

<!-- Légende des scopes -->
<div class="mt-6 bg-white rounded-lg shadow-sm p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Scope des données par rôle</h3>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="p-4 bg-red-50 rounded-lg border border-red-100">
            <div class="flex items-center gap-2 mb-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Super Admin</span>
            </div>
            <p class="text-sm text-gray-600">Accès complet à toutes les données et fonctionnalités du système.</p>
        </div>
        
        <div class="p-4 bg-purple-50 rounded-lg border border-purple-100">
            <div class="flex items-center gap-2 mb-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">Administrateur</span>
            </div>
            <p class="text-sm text-gray-600">Accès à toutes les données métier, sans gestion des utilisateurs.</p>
        </div>
        
        <div class="p-4 bg-blue-50 rounded-lg border border-blue-100">
            <div class="flex items-center gap-2 mb-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Créateur</span>
            </div>
            <p class="text-sm text-gray-600">Accès aux campagnes où il est <strong>assigné</strong> (owner ou collaborateur).</p>
        </div>
        
        <div class="p-4 bg-orange-50 rounded-lg border border-orange-100">
            <div class="flex items-center gap-2 mb-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800">Manager Reps</span>
            </div>
            <p class="text-sm text-gray-600">Lecture seule. Voit les données de <strong>ses commerciaux</strong> (hiérarchie Microsoft).</p>
        </div>
        
        <div class="p-4 bg-green-50 rounded-lg border border-green-100">
            <div class="flex items-center gap-2 mb-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Commercial</span>
            </div>
            <p class="text-sm text-gray-600">Lecture seule. Voit uniquement <strong>ses propres clients</strong> et commandes.</p>
        </div>
    </div>
</div>

<?php elseif ($activeTab === 'general'): ?>
<!-- Onglet Général -->
<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="text-center py-12">
        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-cog text-gray-400 text-2xl"></i>
        </div>
        <p class="text-gray-500 font-medium">Paramètres généraux</p>
        <p class="text-sm text-gray-400 mt-1">À venir dans une prochaine version</p>
    </div>
</div>

<?php endif; ?>

<?php
$content = ob_get_clean();
$pageScripts = '';
require __DIR__ . '/../../layouts/admin.php';
?>
