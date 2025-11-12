<?php
/**
 * Vue : Détails d'un client
 * 
 * Affiche les informations complètes d'un client, ses campagnes et commandes
 * 
 * @package STM/Views/Admin/Customers
 * @version 2.0
 * @created 12/11/2025 19:30
 */

$pageTitle = 'Détails client';
ob_start();
?>

<div class="max-w-7xl mx-auto">
    <!-- En-tête -->
    <div class="mb-6">
        <div class="flex items-center gap-4 mb-4">
            <a href="/stm/admin/customers" 
               class="text-gray-400 hover:text-gray-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
            </a>
            <div class="flex-1">
                <div class="flex items-center gap-3">
                    <h2 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($customer['name']) ?></h2>
                    <?php if ($customer['is_active']): ?>
                        <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                            ✓ Actif
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                            ○ Inactif
                        </span>
                    <?php endif; ?>
                    <?php if ($customer['country'] === 'BE'): ?>
                        <span class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">
                            🇧🇪 BE
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-800 ring-1 ring-inset ring-blue-600/20">
                            🇱🇺 LU
                        </span>
                    <?php endif; ?>
                </div>
                <p class="mt-1 text-sm text-gray-500">
                    Client #<?= htmlspecialchars($customer['customer_number']) ?>
                </p>
            </div>
            <div class="flex gap-2">
                <a href="/stm/admin/customers/<?= $customer['id'] ?>/edit" 
                   class="inline-flex items-center gap-x-2 rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                    <svg class="-ml-0.5 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                    </svg>
                    Modifier
                </a>
                <form method="POST" 
                      action="/stm/admin/customers/<?= $customer['id'] ?>/delete" 
                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce client ? Cette action est irréversible.');">
                    <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <button type="submit" 
                            class="inline-flex items-center gap-x-2 rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700">
                        <svg class="-ml-0.5 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                        Supprimer
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Colonne principale -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Informations générales -->
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Informations générales</h3>
                
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Numéro client</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($customer['customer_number']) ?></dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Nom</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($customer['name']) ?></dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Pays</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <?= $customer['country'] === 'BE' ? '🇧🇪 Belgique' : '🇱🇺 Luxembourg' ?>
                        </dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Représentant</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($customer['representative'] ?? '-') ?></dd>
                    </div>
                </dl>
            </div>

            <!-- Coordonnées -->
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Coordonnées</h3>
                
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <?php if ($customer['email']): ?>
                                <a href="mailto:<?= htmlspecialchars($customer['email']) ?>" 
                                   class="text-indigo-600 hover:text-indigo-900">
                                    <?= htmlspecialchars($customer['email']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-gray-400">Non renseigné</span>
                            <?php endif; ?>
                        </dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Téléphone</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <?php if ($customer['phone']): ?>
                                <a href="tel:<?= htmlspecialchars($customer['phone']) ?>" 
                                   class="text-indigo-600 hover:text-indigo-900">
                                    <?= htmlspecialchars($customer['phone']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-gray-400">Non renseigné</span>
                            <?php endif; ?>
                        </dd>
                    </div>
                    
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Adresse</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <?php if ($customer['address'] || $customer['postal_code'] || $customer['city']): ?>
                                <?= htmlspecialchars($customer['address'] ?? '') ?><br>
                                <?= htmlspecialchars($customer['postal_code'] ?? '') ?> <?= htmlspecialchars($customer['city'] ?? '') ?>
                            <?php else: ?>
                                <span class="text-gray-400">Non renseignée</span>
                            <?php endif; ?>
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Campagnes attribuées -->
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Campagnes attribuées</h3>
                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">
                        <?= count($campaigns) ?> campagne<?= count($campaigns) > 1 ? 's' : '' ?>
                    </span>
                </div>

                <?php if (empty($campaigns)): ?>
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="mt-2 text-sm font-medium text-gray-900">Aucune campagne</p>
                        <p class="mt-1 text-sm text-gray-500">Ce client n'est attribué à aucune campagne pour le moment</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($campaigns as $campaign): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <a href="/stm/admin/campaigns/<?= $campaign['id'] ?>" 
                                           class="text-sm font-medium text-gray-900 hover:text-indigo-600">
                                            <?= htmlspecialchars($campaign['name']) ?>
                                        </a>
                                        <?php if ($campaign['is_active']): ?>
                                            <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                                Active
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">
                                                Inactive
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">
                                        <?= date('d/m/Y', strtotime($campaign['start_date'])) ?> → <?= date('d/m/Y', strtotime($campaign['end_date'])) ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Historique des commandes -->
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Historique des commandes</h3>
                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">
                        <?= count($orders) ?> commande<?= count($orders) > 1 ? 's' : '' ?>
                    </span>
                </div>

                <?php if (empty($orders)): ?>
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        <p class="mt-2 text-sm font-medium text-gray-900">Aucune commande</p>
                        <p class="mt-1 text-sm text-gray-500">Ce client n'a pas encore passé de commande</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Campagne</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Articles</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($orders as $order): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">
                                            <?= date('d/m/Y', strtotime($order['created_at'])) ?>
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">
                                            <a href="/stm/admin/campaigns/<?= $order['campaign_id'] ?>" 
                                               class="text-indigo-600 hover:text-indigo-900">
                                                <?= htmlspecialchars($order['campaign_name']) ?>
                                            </a>
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 text-right">
                                            <?= $order['total_items'] ?>
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-sm">
                                            <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700">
                                                <?= htmlspecialchars($order['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Catégorisation -->
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Catégorisation</h3>
                
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Type</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($customer['type'] ?? '-') ?></dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Segment</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($customer['segment'] ?? '-') ?></dd>
                    </div>
                </dl>
            </div>

            <!-- Métadonnées -->
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Informations système</h3>
                
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Date de création</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <?= date('d/m/Y à H:i', strtotime($customer['created_at'])) ?>
                        </dd>
                    </div>
                    
                    <?php if ($customer['updated_at']): ?>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Dernière modification</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <?= date('d/m/Y à H:i', strtotime($customer['updated_at'])) ?>
                            </dd>
                        </div>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$title = 'Client : ' . htmlspecialchars($customer['name']);
require __DIR__ . '/../../layouts/admin.php';
?>
