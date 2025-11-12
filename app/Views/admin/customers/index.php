<?php
/**
 * Vue : Liste des clients
 * 
 * Affiche la liste de tous les clients avec filtres et statistiques
 * 
 * @package STM/Views/Admin/Customers
 * @version 2.0
 * @created 12/11/2025 19:30
 */

$pageTitle = 'Clients';
ob_start();
?>

<!-- En-tête avec actions -->
<div class="sm:flex sm:items-center sm:justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Gestion des clients</h2>
        <p class="mt-1 text-sm text-gray-500">
            <?= count($customers) ?> client<?= count($customers) > 1 ? 's' : '' ?> trouvé<?= count($customers) > 1 ? 's' : '' ?>
        </p>
    </div>
    <div class="mt-4 sm:mt-0 flex gap-3">
        <a href="/stm/admin/customers/import" 
           class="inline-flex items-center gap-x-2 rounded-md bg-white px-4 py-2.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
            <svg class="-ml-0.5 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
            Importer depuis DB
        </a>
        <a href="/stm/admin/customers/create" 
           class="inline-flex items-center gap-x-2 rounded-md bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
            <svg class="-ml-0.5 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Nouveau client
        </a>
    </div>
</div>

<!-- Statistiques rapides -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
    <!-- Total -->
    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Total</dt>
                        <dd class="text-lg font-semibold text-gray-900"><?= $stats['total'] ?? 0 ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Belgique -->
    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <span class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">
                        BE
                    </span>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Belgique</dt>
                        <dd class="text-lg font-semibold text-gray-900"><?= $stats['be'] ?? 0 ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Luxembourg -->
    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-800 ring-1 ring-inset ring-blue-600/20">
                        LU
                    </span>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Luxembourg</dt>
                        <dd class="text-lg font-semibold text-gray-900"><?= $stats['lu'] ?? 0 ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Actifs -->
    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Actifs</dt>
                        <dd class="text-lg font-semibold text-gray-900"><?= $stats['active'] ?? 0 ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="bg-white shadow-sm rounded-lg p-4 mb-6">
    <form method="GET" action="/stm/admin/customers" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <!-- Recherche -->
        <div>
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Recherche</label>
            <input type="text" 
                   id="search" 
                   name="search" 
                   value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                   placeholder="Numéro, nom, email..."
                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>

        <!-- Pays -->
        <div>
            <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Pays</label>
            <select id="country" 
                    name="country"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">Tous les pays</option>
                <option value="BE" <?= ($filters['country'] ?? '') === 'BE' ? 'selected' : '' ?>>Belgique</option>
                <option value="LU" <?= ($filters['country'] ?? '') === 'LU' ? 'selected' : '' ?>>Luxembourg</option>
            </select>
        </div>

        <!-- Représentant -->
        <div>
            <label for="representative" class="block text-sm font-medium text-gray-700 mb-1">Représentant</label>
            <select id="representative" 
                    name="representative"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">Tous les représentants</option>
                <?php foreach ($representatives['BE'] ?? [] as $rep): ?>
                    <option value="<?= htmlspecialchars($rep) ?>" 
                            <?= ($filters['representative'] ?? '') === $rep ? 'selected' : '' ?>>
                        <?= htmlspecialchars($rep) ?> (BE)
                    </option>
                <?php endforeach; ?>
                <?php foreach ($representatives['LU'] ?? [] as $rep): ?>
                    <option value="<?= htmlspecialchars($rep) ?>" 
                            <?= ($filters['representative'] ?? '') === $rep ? 'selected' : '' ?>>
                        <?= htmlspecialchars($rep) ?> (LU)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Boutons -->
        <div class="sm:col-span-3 flex gap-2">
            <button type="submit" 
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Filtrer
            </button>
            <a href="/stm/admin/customers" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Réinitialiser
            </a>
        </div>
    </form>
</div>

<!-- Tableau des clients -->
<div class="bg-white shadow-sm rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Numéro
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Nom
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Pays
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Représentant
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Email
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
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <p class="mt-2 font-medium">Aucun client trouvé</p>
                            <p class="mt-1 text-gray-400">Commencez par créer un client ou importer depuis la base externe</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($customers as $customer): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?= htmlspecialchars($customer['customer_number']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= htmlspecialchars($customer['name']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($customer['country'] === 'BE'): ?>
                                    <span class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">
                                        🇧🇪 BE
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-800 ring-1 ring-inset ring-blue-600/20">
                                        🇱🇺 LU
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= htmlspecialchars($customer['representative'] ?? '-') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= htmlspecialchars($customer['email'] ?? '-') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($customer['is_active']): ?>
                                    <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                        ✓ Actif
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                                        ○ Inactif
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end gap-2">
                                    <!-- Voir -->
                                    <a href="/stm/admin/customers/<?= $customer['id'] ?>" 
                                       class="text-indigo-600 hover:text-indigo-900"
                                       title="Voir les détails">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </a>
                                    
                                    <!-- Modifier -->
                                    <a href="/stm/admin/customers/<?= $customer['id'] ?>/edit" 
                                       class="text-gray-600 hover:text-gray-900"
                                       title="Modifier">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                        </svg>
                                    </a>
                                    
                                    <!-- Supprimer -->
                                    <form method="POST" 
                                          action="/stm/admin/customers/<?= $customer['id'] ?>/delete" 
                                          class="inline"
                                          onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce client ?');">
                                        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
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
</div>

<?php
$content = ob_get_clean();
$title = 'Clients';
require __DIR__ . '/../../layouts/admin.php';
?>
