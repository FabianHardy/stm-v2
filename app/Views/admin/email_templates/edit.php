<?php
/**
 * Vue Admin - Édition d'un template d'email
 *
 * Éditeur WYSIWYG Summernote pour les emails HTML
 *
 * @package    App\Views\admin\email_templates
 * @author     Fabian Hardy
 * @version    1.1.0
 * @created    2025/12/30
 * @modified   2025/12/30 - Remplacement CKEditor par Summernote
 */

ob_start();
?>

<!-- CSS Summernote -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
<style>
[x-cloak] { display: none !important; }
.note-editor { border: 1px solid #d1d5db !important; border-radius: 0.375rem !important; }
.note-editor .note-toolbar { background-color: #f9fafb !important; border-bottom: 1px solid #e5e7eb !important; }
.note-editable { min-height: 400px !important; font-family: Arial, Helvetica, sans-serif !important; }
</style>

<!-- En-tête -->
<div class="mb-6">
    <div class="flex items-center gap-4">
        <a href="/stm/admin/email-templates" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($template['type'] ?? '') ?></h1>
            <p class="mt-1 text-sm text-gray-500">
                Type : <code class="bg-gray-100 px-2 py-0.5 rounded"><?= htmlspecialchars($template['type'] ?? '') ?></code>
            </p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-4 gap-6">

    <!-- Formulaire principal (3/4) -->
    <div class="xl:col-span-3">
        <form method="POST" action="/stm/admin/email-templates/<?= $template['id'] ?>/update" id="templateForm">
            <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">

            <!-- Informations générales -->
            <div class="bg-white shadow rounded-lg p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-info-circle text-gray-400 mr-2"></i>
                    Template : <?= htmlspecialchars($template['type'] ?? $template['code'] ?? 'N/A') ?>
                </h2>
                <p class="text-sm text-gray-500">
                    Modifiez les sujets et contenus des emails en français et néerlandais.
                </p>
            </div>

            <!-- Onglets FR / NL -->
            <div class="bg-white shadow rounded-lg overflow-hidden" x-data="{ activeTab: 'fr' }">
                <!-- Tabs -->
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px">
                        <button type="button"
                                @click="activeTab = 'fr'"
                                :class="activeTab === 'fr' ? 'border-indigo-500 text-indigo-600 bg-indigo-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="py-3 px-6 border-b-2 font-medium text-sm transition-colors">
                            🇫🇷 Français
                        </button>
                        <button type="button"
                                @click="activeTab = 'nl'"
                                :class="activeTab === 'nl' ? 'border-indigo-500 text-indigo-600 bg-indigo-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="py-3 px-6 border-b-2 font-medium text-sm transition-colors">
                            🇳🇱 Nederlands
                        </button>
                    </nav>
                </div>

                <!-- Contenu FR -->
                <div x-show="activeTab === 'fr'" class="p-6">
                    <div class="mb-4">
                        <label for="subject_fr" class="block text-sm font-medium text-gray-700 mb-1">
                            Sujet de l'email <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="subject_fr" name="subject_fr"
                               value="<?= htmlspecialchars($template['subject_fr'] ?? '') ?>"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="Ex: Confirmation de votre commande - {campaign_name}"
                               required>
                        <p class="mt-1 text-xs text-gray-500">Utilisez les variables entre accolades : <code>{variable}</code></p>
                    </div>

                    <div>
                        <label for="body_fr" class="block text-sm font-medium text-gray-700 mb-1">
                            Contenu de l'email <span class="text-red-500">*</span>
                        </label>
                        <textarea id="body_fr" name="body_fr" class="summernote-editor"><?= htmlspecialchars($template['body_fr'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Contenu NL -->
                <div x-show="activeTab === 'nl'" x-cloak class="p-6">
                    <div class="mb-4">
                        <label for="subject_nl" class="block text-sm font-medium text-gray-700 mb-1">
                            Sujet de l'email
                        </label>
                        <input type="text" id="subject_nl" name="subject_nl"
                               value="<?= htmlspecialchars($template['subject_nl'] ?? '') ?>"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="Ex: Bevestiging van uw bestelling - {campaign_name}">
                        <p class="mt-1 text-xs text-gray-500">Laissez vide pour utiliser la version française</p>
                    </div>

                    <div>
                        <label for="body_nl" class="block text-sm font-medium text-gray-700 mb-1">
                            Contenu de l'email
                        </label>
                        <textarea id="body_nl" name="body_nl" class="summernote-editor"><?= htmlspecialchars($template['body_nl'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Boutons -->
            <div class="mt-6 flex items-center justify-between">
                <a href="/stm/admin/email-templates"
                   class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-times mr-2"></i> Annuler
                </a>
                <button type="submit"
                        class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <i class="fas fa-save mr-2"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>

    <!-- Sidebar (1/4) -->
    <div class="xl:col-span-1 space-y-6">

        <!-- Variables disponibles -->
        <div class="bg-white shadow rounded-lg p-4">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">
                <i class="fas fa-code text-indigo-500 mr-2"></i>
                Variables disponibles
            </h3>
            <p class="text-xs text-gray-500 mb-3">Cliquez pour copier</p>
            <div class="space-y-2">
                <?php
                $defaultVariables = [
                    'campaign_name' => 'Nom de la campagne',
                    'company_name' => 'Nom de la société',
                    'customer_number' => 'Numéro client',
                    'order_date' => 'Date de la commande',
                    'total_items' => 'Nombre d\'articles',
                    'order_lines' => 'Liste des produits (HTML)',
                    'delivery_date' => 'Date de livraison',
                ];
                $availableVariables = $availableVariables ?? $defaultVariables;
                ?>
                <?php if (!empty($availableVariables)): ?>
                    <?php foreach ($availableVariables as $var => $description): ?>
                    <div class="group">
                        <button type="button"
                                onclick="copyVariable('{<?= $var ?>}')"
                                class="w-full text-left px-2 py-1.5 rounded text-xs bg-gray-50 hover:bg-indigo-50 hover:text-indigo-700 transition-colors">
                            <code class="font-mono text-indigo-600">{<?= $var ?>}</code>
                            <span class="block text-gray-500 text-[10px] mt-0.5"><?= htmlspecialchars($description) ?></span>
                        </button>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-xs text-gray-400">Aucune variable définie</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Prévisualisation -->
        <div class="bg-white shadow rounded-lg p-4">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">
                <i class="fas fa-eye text-green-500 mr-2"></i>
                Prévisualiser
            </h3>
            <div class="space-y-2">
                <a href="/stm/admin/email-templates/<?= $template['id'] ?>/preview?lang=fr"
                   target="_blank"
                   class="w-full inline-flex items-center justify-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    🇫🇷 Aperçu FR
                </a>
                <a href="/stm/admin/email-templates/<?= $template['id'] ?>/preview?lang=nl"
                   target="_blank"
                   class="w-full inline-flex items-center justify-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    🇳🇱 Aperçu NL
                </a>
            </div>
        </div>

        <!-- Envoyer un test -->
        <div class="bg-white shadow rounded-lg p-4">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">
                <i class="fas fa-paper-plane text-orange-500 mr-2"></i>
                Envoyer un test
            </h3>
            <form method="POST" action="/stm/admin/email-templates/<?= $template['id'] ?>/send-test">
                <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="mb-3">
                    <input type="email" name="test_email"
                           placeholder="email@exemple.com"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                           required>
                </div>
                <div class="mb-3">
                    <select name="test_language"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="fr">🇫🇷 Français</option>
                        <option value="nl">🇳🇱 Nederlands</option>
                    </select>
                </div>
                <button type="submit"
                        class="w-full inline-flex items-center justify-center px-3 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-orange-500 hover:bg-orange-600">
                    <i class="fas fa-paper-plane mr-2"></i> Envoyer
                </button>
            </form>
        </div>

        <!-- Aide -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-yellow-800 mb-2">
                <i class="fas fa-lightbulb mr-2"></i>
                Conseils
            </h3>
            <ul class="text-xs text-yellow-700 space-y-1">
                <li>• Utilisez des <strong>tables</strong> pour la mise en page</li>
                <li>• Privilégiez les <strong>styles inline</strong></li>
                <li>• Testez sur <strong>plusieurs clients mail</strong></li>
                <li>• Les conditions <code>{#if var}...{/if}</code> sont supportées</li>
            </ul>
        </div>
    </div>
</div>

<!-- jQuery (requis pour Summernote) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Scripts Summernote -->
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/lang/summernote-fr-FR.min.js"></script>
<script>
$(document).ready(function() {
    $('.summernote-editor').summernote({
        height: 400,
        lang: 'fr-FR',
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
            ['fontsize', ['fontsize']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link', 'picture', 'hr']],
            ['view', ['fullscreen', 'codeview', 'help']]
        ],
        fontSizes: ['8', '10', '12', '14', '16', '18', '20', '24', '28', '32', '36'],
        styleTags: ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'],
        codeviewFilter: false,
        codeviewIframeFilter: true
    });
});

// Copier une variable dans le presse-papier
function copyVariable(variable) {
    navigator.clipboard.writeText(variable).then(function() {
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-4 right-4 bg-gray-800 text-white px-4 py-2 rounded-lg shadow-lg text-sm z-50';
        toast.innerHTML = '<i class="fas fa-check mr-2"></i>Variable copiée !';
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2000);
    });
}
</script>

<?php
$content = ob_get_clean();
$title = $pageTitle ?? 'Modifier le template';

require __DIR__ . '/../../layouts/admin.php';
?>