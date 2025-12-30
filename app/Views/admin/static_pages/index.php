<?php
/**
 * Vue Admin - Liste des pages fixes
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
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Pages fixes</h1>
            <p class="mt-1 text-sm text-gray-500">
                Gérez les pages légales et informatives (CGU, CGV, Mentions légales, etc.)
            </p>
        </div>
    </div>
</div>

<!-- Info Box -->
<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
    <div class="flex">
        <div class="flex-shrink-0">
            <i class="fas fa-info-circle text-blue-400 text-xl"></i>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium text-blue-800">Comment ça marche ?</h3>
            <div class="mt-2 text-sm text-blue-700">
                <p>Les pages fixes sont <strong>globales par défaut</strong>. Elles s'affichent dans le footer de l'interface client.</p>
                <p class="mt-1">Vous pouvez créer des <strong>surcharges par campagne</strong> pour personnaliser le contenu d'une page spécifique à une campagne.</p>
            </div>
        </div>
    </div>
</div>

<!-- Tableau des pages -->
<div class="bg-white shadow rounded-lg overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Page</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Footer</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Surcharges</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($pages)): ?>
            <tr>
                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                    <i class="fas fa-file-alt text-4xl text-gray-300 mb-3"></i>
                    <p>Aucune page configurée</p>
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($pages as $page): ?>
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-file-alt text-purple-600"></i>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($page['title_fr']) ?></div>
                            <?php if (!empty($page['title_nl'])): ?>
                            <div class="text-xs text-gray-500"><?= htmlspecialchars($page['title_nl']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <code class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded"><?= htmlspecialchars($page['slug']) ?></code>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                    <?php if ($page['is_active']): ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <i class="fas fa-check-circle mr-1"></i> Actif
                    </span>
                    <?php else: ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                        <i class="fas fa-pause-circle mr-1"></i> Inactif
                    </span>
                    <?php endif; ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                    <?php if ($page['show_in_footer']): ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        <i class="fas fa-check mr-1"></i> Oui
                    </span>
                    <?php else: ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                        <i class="fas fa-times mr-1"></i> Non
                    </span>
                    <?php endif; ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                    <?php $count = $overrideCounts[$page['slug']] ?? 0; ?>
                    <?php if ($count > 0): ?>
                    <a href="/stm/admin/static-pages/<?= $page['id'] ?>/overrides" 
                       class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 hover:bg-orange-200">
                        <i class="fas fa-layer-group mr-1"></i> <?= $count ?>
                    </a>
                    <?php else: ?>
                    <span class="text-xs text-gray-400">Aucune</span>
                    <?php endif; ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                    <div class="flex items-center justify-center gap-2">
                        <!-- Prévisualiser -->
                        <a href="/stm/admin/static-pages/<?= $page['id'] ?>/preview?lang=fr" 
                           target="_blank"
                           class="inline-flex items-center px-2.5 py-1.5 border border-gray-300 rounded text-xs font-medium text-gray-700 bg-white hover:bg-gray-50"
                           title="Prévisualiser (FR)">
                            <i class="fas fa-eye mr-1"></i> FR
                        </a>
                        <a href="/stm/admin/static-pages/<?= $page['id'] ?>/preview?lang=nl" 
                           target="_blank"
                           class="inline-flex items-center px-2.5 py-1.5 border border-gray-300 rounded text-xs font-medium text-gray-700 bg-white hover:bg-gray-50"
                           title="Prévisualiser (NL)">
                            <i class="fas fa-eye mr-1"></i> NL
                        </a>
                        <!-- Modifier -->
                        <a href="/stm/admin/static-pages/<?= $page['id'] ?>/edit" 
                           class="inline-flex items-center px-3 py-1.5 border border-purple-300 rounded text-xs font-medium text-purple-700 bg-purple-50 hover:bg-purple-100">
                            <i class="fas fa-edit mr-1"></i> Modifier
                        </a>
                        <!-- Surcharges -->
                        <a href="/stm/admin/static-pages/<?= $page['id'] ?>/overrides" 
                           class="inline-flex items-center px-3 py-1.5 border border-orange-300 rounded text-xs font-medium text-orange-700 bg-orange-50 hover:bg-orange-100"
                           title="Gérer les surcharges par campagne">
                            <i class="fas fa-layer-group mr-1"></i> Surcharges
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Légende -->
<div class="mt-6 bg-gray-50 rounded-lg p-4">
    <h3 class="text-sm font-medium text-gray-700 mb-2">Légende</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-gray-600">
        <div><i class="fas fa-check-circle text-green-500 mr-2"></i><strong>Actif</strong> : La page est visible côté client</div>
        <div><i class="fas fa-pause-circle text-gray-400 mr-2"></i><strong>Inactif</strong> : La page est masquée</div>
        <div><i class="fas fa-check text-blue-500 mr-2"></i><strong>Footer</strong> : Lien affiché dans le footer</div>
        <div><i class="fas fa-layer-group text-orange-500 mr-2"></i><strong>Surcharges</strong> : Versions personnalisées par campagne</div>
    </div>
</div>

<?php
$content = ob_get_clean();
$title = $pageTitle ?? 'Pages fixes';

require __DIR__ . '/../../layouts/admin.php';
?>
