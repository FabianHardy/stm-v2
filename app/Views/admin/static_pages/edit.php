<?php
/**
 * Vue Admin - Édition d'une page fixe
 *
 * Éditeur WYSIWYG Summernote pour le contenu HTML
 *
 * @package    App\Views\admin\static_pages
 * @author     Fabian Hardy
 * @version    1.0.0
 * @created    2025/12/30
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
        <a href="/stm/admin/static-pages" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($page['title_fr']) ?></h1>
            <p class="mt-1 text-sm text-gray-500">
                Slug : <code class="bg-gray-100 px-2 py-0.5 rounded"><?= htmlspecialchars($page['slug']) ?></code>
                <?php if ($isOverride): ?>
                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800">
                    <i class="fas fa-layer-group mr-1"></i> Surcharge campagne
                </span>
                <?php endif; ?>
            </p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-4 gap-6">

    <!-- Formulaire principal (3/4) -->
    <div class="xl:col-span-3">
        <form method="POST" action="/stm/admin/static-pages/<?= $page['id'] ?>/update" id="pageForm">
            <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">

            <!-- Informations générales -->
            <div class="bg-white shadow rounded-lg p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-info-circle text-gray-400 mr-2"></i>
                    Informations générales
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="title_fr" class="block text-sm font-medium text-gray-700 mb-1">
                            Titre (FR) <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="title_fr" name="title_fr"
                               value="<?= htmlspecialchars($page['title_fr']) ?>"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                               required>
                    </div>
                    <div>
                        <label for="title_nl" class="block text-sm font-medium text-gray-700 mb-1">
                            Titre (NL)
                        </label>
                        <input type="text" id="title_nl" name="title_nl"
                               value="<?= htmlspecialchars($page['title_nl'] ?? '') ?>"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                               placeholder="Laisser vide pour utiliser le titre FR">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                        <label class="inline-flex items-center mt-2">
                            <input type="checkbox" name="is_active" value="1"
                                   <?= $page['is_active'] ? 'checked' : '' ?>
                                   class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                            <span class="ml-2 text-sm text-gray-600">Page active</span>
                        </label>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Affichage</label>
                        <label class="inline-flex items-center mt-2">
                            <input type="checkbox" name="show_in_footer" value="1"
                                   <?= $page['show_in_footer'] ? 'checked' : '' ?>
                                   class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                            <span class="ml-2 text-sm text-gray-600">Afficher dans le footer</span>
                        </label>
                    </div>
                    <div>
                        <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-1">
                            Ordre d'affichage
                        </label>
                        <input type="number" id="sort_order" name="sort_order"
                               value="<?= (int)($page['sort_order'] ?? 0) ?>"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                               min="0" max="100">
                    </div>
                </div>
            </div>

            <!-- Onglets FR / NL -->
            <div class="bg-white shadow rounded-lg overflow-hidden" x-data="{ activeTab: 'fr' }">
                <!-- Tabs -->
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px">
                        <button type="button"
                                @click="activeTab = 'fr'"
                                :class="activeTab === 'fr' ? 'border-purple-500 text-purple-600 bg-purple-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="py-3 px-6 border-b-2 font-medium text-sm transition-colors">
                            🇫🇷 Contenu FR
                        </button>
                        <button type="button"
                                @click="activeTab = 'nl'"
                                :class="activeTab === 'nl' ? 'border-purple-500 text-purple-600 bg-purple-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="py-3 px-6 border-b-2 font-medium text-sm transition-colors">
                            🇳🇱 Contenu NL
                        </button>
                    </nav>
                </div>

                <!-- Contenu FR -->
                <div x-show="activeTab === 'fr'" class="p-6">
                    <label for="content_fr" class="block text-sm font-medium text-gray-700 mb-2">
                        Contenu de la page (FR) <span class="text-red-500">*</span>
                    </label>
                    <textarea id="content_fr" name="content_fr" class="summernote-editor"><?= htmlspecialchars($page['content_fr'] ?? '') ?></textarea>
                </div>

                <!-- Contenu NL -->
                <div x-show="activeTab === 'nl'" x-cloak class="p-6">
                    <label for="content_nl" class="block text-sm font-medium text-gray-700 mb-2">
                        Contenu de la page (NL)
                    </label>
                    <p class="text-xs text-gray-500 mb-2">Laissez vide pour utiliser le contenu français</p>
                    <textarea id="content_nl" name="content_nl" class="summernote-editor"><?= htmlspecialchars($page['content_nl'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Boutons -->
            <div class="mt-6 flex items-center justify-between">
                <a href="/stm/admin/static-pages"
                   class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-times mr-2"></i> Annuler
                </a>
                <button type="submit"
                        class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                    <i class="fas fa-save mr-2"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>

    <!-- Sidebar (1/4) -->
    <div class="xl:col-span-1 space-y-6">

        <!-- Prévisualisation -->
        <div class="bg-white shadow rounded-lg p-4">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">
                <i class="fas fa-eye text-green-500 mr-2"></i>
                Prévisualiser
            </h3>
            <div class="space-y-2">
                <button type="button"
                        onclick="openPreviewModal('fr')"
                        class="w-full inline-flex items-center justify-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    🇫🇷 Aperçu FR
                </button>
                <button type="button"
                        onclick="openPreviewModal('nl')"
                        class="w-full inline-flex items-center justify-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    🇳🇱 Aperçu NL
                </button>
            </div>
        </div>

        <!-- Informations -->
        <div class="bg-white shadow rounded-lg p-4">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">
                <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                Informations
            </h3>
            <dl class="space-y-2 text-sm">
                <div>
                    <dt class="text-gray-500">Slug</dt>
                    <dd class="font-mono text-gray-900"><?= htmlspecialchars($page['slug']) ?></dd>
                </div>
                <div>
                    <dt class="text-gray-500">Type</dt>
                    <dd class="text-gray-900">
                        <?php if ($isOverride): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800">
                            Surcharge campagne
                        </span>
                        <?php else: ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                            Page globale
                        </span>
                        <?php endif; ?>
                    </dd>
                </div>
                <?php if (!empty($page['updated_at'])): ?>
                <div>
                    <dt class="text-gray-500">Dernière modification</dt>
                    <dd class="text-gray-900"><?= date('d/m/Y à H:i', strtotime($page['updated_at'])) ?></dd>
                </div>
                <?php endif; ?>
            </dl>
        </div>

        <!-- Aide -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-yellow-800 mb-2">
                <i class="fas fa-lightbulb mr-2"></i>
                Conseils
            </h3>
            <ul class="text-xs text-yellow-700 space-y-1">
                <li>• Utilisez les <strong>titres H2/H3</strong> pour structurer le contenu</li>
                <li>• Ajoutez des <strong>listes</strong> pour faciliter la lecture</li>
                <li>• Pensez à mettre à jour la <strong>date</strong> en bas de page</li>
                <li>• Testez l'affichage dans les <strong>deux langues</strong></li>
            </ul>
        </div>

        <?php if ($isOverride): ?>
        <!-- Supprimer surcharge -->
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-red-800 mb-2">
                <i class="fas fa-trash mr-2"></i>
                Supprimer la surcharge
            </h3>
            <p class="text-xs text-red-700 mb-3">
                Supprimer cette surcharge restaurera la page globale pour cette campagne.
            </p>
            <a href="/stm/admin/static-pages/<?= $page['id'] ?>/delete"
               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette surcharge ?')"
               class="w-full inline-flex items-center justify-center px-3 py-2 border border-red-300 rounded-md text-sm font-medium text-red-700 bg-white hover:bg-red-50">
                <i class="fas fa-trash mr-2"></i> Supprimer
            </a>
        </div>
        <?php endif; ?>
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
        styleTags: ['p', 'h2', 'h3', 'h4', 'h5', 'h6'],
        codeviewFilter: false,
        codeviewIframeFilter: true
    });
});

// Modal Aperçu
let currentPreviewLang = 'fr';
const pageId = <?= $page['id'] ?>;

function openPreviewModal(lang) {
    currentPreviewLang = lang;
    updateLangButtons(lang);
    document.getElementById('previewIframe').src = '/stm/admin/static-pages/' + pageId + '/preview?lang=' + lang;
    document.getElementById('previewModal').classList.remove('hidden');
}

function closePreviewModal() {
    document.getElementById('previewModal').classList.add('hidden');
    document.getElementById('previewIframe').src = 'about:blank';
}

function switchPreviewLang(lang) {
    currentPreviewLang = lang;
    updateLangButtons(lang);
    document.getElementById('previewIframe').src = '/stm/admin/static-pages/' + pageId + '/preview?lang=' + lang;
}

function updateLangButtons(lang) {
    const btnFr = document.getElementById('btnPreviewFr');
    const btnNl = document.getElementById('btnPreviewNl');

    if (lang === 'fr') {
        btnFr.className = 'px-3 py-1 text-sm font-medium rounded transition-colors bg-purple-100 text-purple-700';
        btnNl.className = 'px-3 py-1 text-sm font-medium rounded transition-colors text-gray-500 hover:bg-gray-100';
    } else {
        btnFr.className = 'px-3 py-1 text-sm font-medium rounded transition-colors text-gray-500 hover:bg-gray-100';
        btnNl.className = 'px-3 py-1 text-sm font-medium rounded transition-colors bg-purple-100 text-purple-700';
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePreviewModal();
    }
});
</script>

<!-- Modal Aperçu -->
<div id="previewModal" class="fixed inset-0 z-50 hidden">
    <!-- Overlay -->
    <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" onclick="closePreviewModal()"></div>

    <!-- Modal Content -->
    <div class="fixed inset-4 md:inset-10 bg-white rounded-xl shadow-2xl flex flex-col overflow-hidden">
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gray-50">
            <div class="flex items-center gap-4">
                <h3 class="text-lg font-semibold text-gray-900">Aperçu : <?= htmlspecialchars($page['title_fr']) ?></h3>
                <!-- Sélecteur de langue -->
                <div class="flex items-center gap-1 bg-white rounded-lg border border-gray-200 p-1">
                    <button type="button" id="btnPreviewFr" onclick="switchPreviewLang('fr')"
                            class="px-3 py-1 text-sm font-medium rounded transition-colors bg-purple-100 text-purple-700">
                        🇫🇷 FR
                    </button>
                    <button type="button" id="btnPreviewNl" onclick="switchPreviewLang('nl')"
                            class="px-3 py-1 text-sm font-medium rounded transition-colors text-gray-500 hover:bg-gray-100">
                        🇳🇱 NL
                    </button>
                </div>
            </div>
            <button type="button" onclick="closePreviewModal()"
                    class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <!-- Iframe -->
        <div class="flex-1 overflow-hidden bg-gray-100 p-4">
            <iframe id="previewIframe"
                    src="about:blank"
                    class="w-full h-full bg-white rounded-lg shadow-inner border border-gray-200"></iframe>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-end px-6 py-3 border-t border-gray-200 bg-gray-50">
            <button type="button" onclick="closePreviewModal()"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                Fermer
            </button>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$title = $pageTitle ?? 'Modifier la page';

require __DIR__ . '/../../layouts/admin.php';
?>