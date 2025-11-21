<?php
/**
 * Vue : Campagnes archivées
 * 
 * Affiche les campagnes terminées et inactives
 * 
 * @created  08/11/2025 15:30
 * @modified 08/11/2025 15:40
 */

// Démarrer la capture du contenu pour le layout
ob_start();
?>

<!-- En-tête de la page -->
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Campagnes archivées</h1>
            <p class="mt-2 text-sm text-gray-600">
                Campagnes terminées ou désactivées
            </p>
        </div>
        <a href="/stm/admin/campaigns" 
           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
            </svg>
            Toutes les campagnes
        </a>
    </div>
</div>

<!-- Statistiques -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Archivées</dt>
                        <dd class="text-lg font-semibold text-gray-900"><?php echo count($campaigns); ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

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
                        <dd class="text-lg font-semibold text-green-600"><?php echo $stats['active']; ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <span class="text-2xl">🇧🇪</span>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Belgique</dt>
                        <dd class="text-lg font-semibold text-gray-900"><?php echo $stats['be']; ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <span class="text-2xl">🇱🇺</span>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Luxembourg</dt>
                        <dd class="text-lg font-semibold text-gray-900"><?php echo $stats['lu']; ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Liste des campagnes archivées -->
<?php if (empty($campaigns)): ?>
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Aucune campagne archivée</h3>
            <p class="mt-1 text-sm text-gray-500">
                Il n'y a actuellement aucune campagne archivée.
            </p>
        </div>
    </div>
<?php else: ?>
    <!-- Table des campagnes -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campagne</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pays</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($campaigns as $campaign): ?>
                <?php
                // Déterminer le statut
                $now = new DateTime();
                $endDate = new DateTime($campaign['end_date']);
                $isEnded = $now > $endDate;
                ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($campaign['name']); ?></div>
                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($campaign['title_fr'] ?? ''); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm"><?php echo $campaign['country'] === 'BE' ? '🇧🇪' : '🇱🇺'; ?> <?php echo $campaign['country']; ?></span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo date('d/m/Y', strtotime($campaign['start_date'])); ?> - <?php echo date('d/m/Y', strtotime($campaign['end_date'])); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($isEnded): ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                ⏱️ Terminée
                            </span>
                        <?php else: ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                Inactive
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="/stm/admin/campaigns/<?php echo $campaign['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">Voir</a>
                        <a href="/stm/admin/campaigns/<?php echo $campaign['id']; ?>/edit" class="text-indigo-600 hover:text-indigo-900">Modifier</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php
// Capturer le contenu
$content = ob_get_clean();

// Définir le titre de la page (variable attendue par le layout)
$title = 'Campagnes archivées';

// Inclure le layout du dashboard (celui avec le beau design)
require __DIR__ . '/../../layouts/admin.php';
?>