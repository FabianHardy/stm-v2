<?php
/**
 * Liste des comptes internes
 * 
 * @created 2025/12/03 14:00
 */

ob_start();
?>

<!-- En-tête -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Comptes internes</h1>
        <p class="text-gray-600 mt-1">Comptes automatiquement ajoutés aux campagnes en mode manual</p>
    </div>
    <a href="/stm/admin/config/internal-customers/create" 
       class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Ajouter un compte
    </a>
</div>

<!-- Statistiques -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Total actifs</p>
                <p class="text-2xl font-semibold text-gray-900"><?= $stats['total'] ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                <span class="text-lg font-bold">BE</span>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Belgique</p>
                <p class="text-2xl font-semibold text-gray-900"><?= $stats['BE'] ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600">
                <span class="text-lg font-bold">LU</span>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Luxembourg</p>
                <p class="text-2xl font-semibold text-gray-900"><?= $stats['LU'] ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="p-4 border-b border-gray-200">
        <form method="GET" class="flex flex-wrap gap-4 items-center">
            <div>
                <label class="sr-only">Pays</label>
                <select name="country" onchange="this.form.submit()" 
                        class="rounded-lg border-gray-300 text-sm focus:ring-red-500 focus:border-red-500">
                    <option value="">Tous les pays</option>
                    <option value="BE" <?= ($_GET['country'] ?? '') === 'BE' ? 'selected' : '' ?>>🇧🇪 Belgique</option>
                    <option value="LU" <?= ($_GET['country'] ?? '') === 'LU' ? 'selected' : '' ?>>🇱🇺 Luxembourg</option>
                </select>
            </div>
            
            <?php if (!empty($_GET['country'])): ?>
            <a href="/stm/admin/config/internal-customers" class="text-sm text-gray-500 hover:text-gray-700">
                ✕ Réinitialiser
            </a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Tableau -->
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Numéro client</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pays</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Créé le</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($customers)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <p class="text-lg font-medium">Aucun compte interne</p>
                        <p class="mt-1">Ajoutez des comptes qui seront automatiquement inclus dans les campagnes manual.</p>
                        <a href="/stm/admin/config/internal-customers/create" class="mt-4 inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg">
                            Ajouter un compte
                        </a>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($customers as $customer): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-mono font-medium text-gray-900"><?= htmlspecialchars($customer['customer_number']) ?></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($customer['country'] === 'BE'): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    🇧🇪 Belgique
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    🇱🇺 Luxembourg
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-gray-600"><?= htmlspecialchars($customer['description'] ?? '-') ?></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($customer['is_active']): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    ✓ Actif
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    Inactif
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= date('d/m/Y', strtotime($customer['created_at'])) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end gap-2">
                                <a href="/stm/admin/config/internal-customers/<?= $customer['id'] ?>/edit" 
                                   class="text-blue-600 hover:text-blue-900" title="Modifier">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <form method="POST" action="/stm/admin/config/internal-customers/<?= $customer['id'] ?>/delete" 
                                      class="inline" onsubmit="return confirm('Supprimer ce compte interne ?');">
                                    <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900" title="Supprimer">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
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
</div>

<!-- Info box -->
<div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium text-blue-800">Comment ça fonctionne ?</h3>
            <p class="mt-1 text-sm text-blue-700">
                Les comptes internes actifs sont <strong>automatiquement ajoutés</strong> à chaque nouvelle campagne en mode <strong>manual</strong>, 
                selon leur pays (BE ou LU). Cela permet aux employés et testeurs d'accéder à toutes les actions sans les ajouter manuellement.
            </p>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$title = 'Comptes internes - Configuration';

require __DIR__ . '/../../layouts/admin.php';
?>
