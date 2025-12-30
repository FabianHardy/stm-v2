<?php
/**
 * Vue Admin - Liste des templates d'emails
 *
 * @package    App\Views\admin\email_templates
 * @author     Fabian Hardy
 * @version    1.1.0
 * @created    2025/12/30
 * @modified   2025/12/30 - Ajout modal pour prévisualisation
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
<div class="bg-white shadow rounded-lg overflow-hidden" x-data="{
    previewOpen: false,
    previewUrl: '',
    previewTitle: '',
    openPreview(id, lang) {
        this.previewUrl = '/stm/admin/email-templates/' + id + '/preview?lang=' + lang;
        this.previewTitle = lang === 'fr' ? 'Aperçu Français' : 'Aperçu Nederlands';
        this.previewOpen = true;
    }
}">
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
                        <!-- Prévisualiser FR -->
                        <button type="button"
                                @click="openPreview(<?= $template['id'] ?>, 'fr')"
                                class="inline-flex items-center px-2.5 py-1.5 border border-gray-300 rounded text-xs font-medium text-gray-700 bg-white hover:bg-gray-50"
                                title="Prévisualiser (FR)">
                            <i class="fas fa-eye mr-1"></i> FR
                        </button>
                        <!-- Prévisualiser NL -->
                        <button type="button"
                                @click="openPreview(<?= $template['id'] ?>, 'nl')"
                                class="inline-flex items-center px-2.5 py-1.5 border border-gray-300 rounded text-xs font-medium text-gray-700 bg-white hover:bg-gray-50"
                                title="Prévisualiser (NL)">
                            <i class="fas fa-eye mr-1"></i> NL
                        </button>
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

    <!-- Modal Prévisualisation -->
    <div x-show="previewOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-50" @click="previewOpen = false"></div>

        <!-- Modal Content -->
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="relative bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95"
                 @click.away="previewOpen = false">

                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <h3 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-eye text-indigo-500 mr-2"></i>
                        <span x-text="previewTitle"></span>
                    </h3>
                    <button @click="previewOpen = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Body (iframe) -->
                <div class="flex-1 overflow-hidden p-4 bg-gray-100">
                    <iframe :src="previewUrl"
                            class="w-full h-full min-h-[600px] bg-white rounded border border-gray-300"
                            frameborder="0"></iframe>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 flex-shrink-0">
                    <a :href="previewUrl"
                       target="_blank"
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-external-link-alt mr-2"></i>
                        Ouvrir dans un nouvel onglet
                    </a>
                    <button @click="previewOpen = false"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                        Fermer
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
[x-cloak] { display: none !important; }
</style>

<?php
$content = ob_get_clean();
$title = $pageTitle;

require __DIR__ . '/../../layouts/admin.php';
?>