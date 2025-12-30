<?php
/**
 * Vue Admin - Liste des templates d'emails
 *
 * @package    App\Views\admin\email_templates
 * @author     Fabian Hardy
 * @version    1.0.0
 * @created    2025/12/30
 */

ob_start();
?>

<!-- En-tête -->
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($pageTitle) ?></h1>
            <p class="mt-1 text-sm text-gray-500">Personnalisez les emails envoyés automatiquement par le système</p>
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
                <p>Les templates d'emails utilisent des <strong>variables dynamiques</strong> au format <code class="bg-blue-100 px-1 rounded">{variable}</code>.</p>
                <p class="mt-1">Ces variables sont automatiquement remplacées par les vraies valeurs lors de l'envoi.</p>
            </div>
        </div>
    </div>
</div>

<!-- Liste des templates -->
<div class="bg-white shadow rounded-lg overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Template</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dernière modification</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($templates)): ?>
            <tr>
                <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                    <i class="fas fa-envelope-open text-4xl text-gray-300 mb-3"></i>
                    <p>Aucun template d'email configuré</p>
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($templates as $template): ?>
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-envelope text-indigo-600"></i>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($template['name'] ?? $template['type'] ?? '') ?></div>
                            <div class="text-xs text-gray-500"><?= htmlspecialchars($template['subject_fr'] ?? '') ?></div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <code class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded"><?= htmlspecialchars($template['type'] ?? $template['code'] ?? '') ?></code>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <?php if (!empty($template['updated_at'])): ?>
                    <?= date('d/m/Y à H:i', strtotime($template['updated_at'])) ?>
                    <?php else: ?>
                    -
                    <?php endif; ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                    <div class="flex items-center justify-center gap-2">
                        <!-- Prévisualiser -->
                        <a href="/stm/admin/email-templates/<?= $template['id'] ?>/preview?lang=fr"
                           target="_blank"
                           class="inline-flex items-center px-2.5 py-1.5 border border-gray-300 rounded text-xs font-medium text-gray-700 bg-white hover:bg-gray-50"
                           title="Prévisualiser (FR)">
                            <i class="fas fa-eye mr-1"></i> FR
                        </a>
                        <a href="/stm/admin/email-templates/<?= $template['id'] ?>/preview?lang=nl"
                           target="_blank"
                           class="inline-flex items-center px-2.5 py-1.5 border border-gray-300 rounded text-xs font-medium text-gray-700 bg-white hover:bg-gray-50"
                           title="Prévisualiser (NL)">
                            <i class="fas fa-eye mr-1"></i> NL
                        </a>
                        <!-- Modifier -->
                        <a href="/stm/admin/email-templates/<?= $template['id'] ?>/edit"
                           class="inline-flex items-center px-3 py-1.5 border border-indigo-300 rounded text-xs font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100">
                            <i class="fas fa-edit mr-1"></i> Modifier
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$content = ob_get_clean();
$title = $pageTitle;

require __DIR__ . '/../../layouts/admin.php';
?>