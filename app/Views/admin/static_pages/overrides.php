<?php
/**
 * Vue Admin - Surcharges d'une page fixe par campagne
 *
 * @package    App\Views\admin\static_pages
 * @author     Fabian Hardy
 * @version    1.0.0
 * @created    2025/12/30
 */

ob_start();
?>

<!-- En-tête -->
<div class="mb-6">
    <div class="flex items-center gap-4">
        <a href="/stm/admin/static-pages" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Surcharges : <?= htmlspecialchars($page['title_fr']) ?></h1>
            <p class="mt-1 text-sm text-gray-500">
                Slug : <code class="bg-gray-100 px-2 py-0.5 rounded"><?= htmlspecialchars($page['slug']) ?></code>
            </p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

    <!-- Liste des surcharges existantes (2/3) -->
    <div class="xl:col-span-2">
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-layer-group text-orange-500 mr-2"></i>
                    Surcharges par campagne
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Ces versions remplacent la page globale pour les campagnes concernées.
                </p>
            </div>

            <?php if (empty($overrides)): ?>
            <div class="px-6 py-12 text-center text-gray-500">
                <i class="fas fa-layer-group text-4xl text-gray-300 mb-3"></i>
                <p>Aucune surcharge pour cette page</p>
                <p class="text-sm mt-1">La page globale s'affichera pour toutes les campagnes.</p>
            </div>
            <?php else: ?>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campagne</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Modifié</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($overrides as $override): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-8 w-8 bg-orange-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-bullhorn text-orange-600 text-sm"></i>
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($override['campaign_name'] ?? 'Campagne #' . $override['campaign_id']) ?></div>
                                    <div class="text-xs text-gray-500">ID: <?= $override['campaign_id'] ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <?php if ($override['is_active']): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Actif
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                Inactif
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-center text-sm text-gray-500">
                            <?php if (!empty($override['updated_at'])): ?>
                            <?= date('d/m/Y', strtotime($override['updated_at'])) ?>
                            <?php else: ?>
                            -
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="/stm/admin/static-pages/<?= $override['id'] ?>/edit" 
                                   class="inline-flex items-center px-2.5 py-1.5 border border-purple-300 rounded text-xs font-medium text-purple-700 bg-purple-50 hover:bg-purple-100">
                                    <i class="fas fa-edit mr-1"></i> Modifier
                                </a>
                                <a href="/stm/admin/static-pages/<?= $override['id'] ?>/delete" 
                                   onclick="return confirm('Supprimer cette surcharge ?')"
                                   class="inline-flex items-center px-2.5 py-1.5 border border-red-300 rounded text-xs font-medium text-red-700 bg-red-50 hover:bg-red-100">
                                    <i class="fas fa-trash mr-1"></i> Supprimer
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Créer une surcharge (1/3) -->
    <div class="xl:col-span-1">
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-plus-circle text-green-500 mr-2"></i>
                Créer une surcharge
            </h3>
            <p class="text-sm text-gray-500 mb-4">
                Créez une version personnalisée de cette page pour une campagne spécifique.
            </p>

            <form method="POST" action="/stm/admin/static-pages/create-override">
                <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="slug" value="<?= htmlspecialchars($page['slug']) ?>">

                <div class="mb-4">
                    <label for="campaign_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Campagne <span class="text-red-500">*</span>
                    </label>
                    <select id="campaign_id" name="campaign_id" 
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                            required>
                        <option value="">-- Sélectionner --</option>
                        <?php 
                        // Filtrer les campagnes qui n'ont pas déjà une surcharge
                        $existingCampaignIds = array_column($overrides, 'campaign_id');
                        foreach ($campaigns as $campaign): 
                            if (in_array($campaign['id'], $existingCampaignIds)) continue;
                        ?>
                        <option value="<?= $campaign['id'] ?>"><?= htmlspecialchars($campaign['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" 
                        class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                    <i class="fas fa-plus mr-2"></i> Créer la surcharge
                </button>
            </form>
        </div>

        <!-- Page globale -->
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mt-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-globe mr-2"></i>
                Page globale (par défaut)
            </h3>
            <p class="text-xs text-gray-600 mb-3">
                Cette version s'affiche pour les campagnes sans surcharge.
            </p>
            <a href="/stm/admin/static-pages/<?= $page['id'] ?>/edit" 
               class="inline-flex items-center text-sm text-purple-600 hover:text-purple-800">
                <i class="fas fa-edit mr-1"></i> Modifier la page globale
            </a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$title = $pageTitle ?? 'Surcharges';

require __DIR__ . '/../../layouts/admin.php';
?>
