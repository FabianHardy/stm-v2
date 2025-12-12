<?php
/**
 * Vue Configuration Agent STM
 *
 * Page de configuration complète du chatbot via l'admin
 * 5 onglets : Configuration, Tools, Statistiques, Historique, Apparence
 *
 * @created  2025/12/11
 * @modified 2025/12/12 - Ajout des 5 onglets
 */

ob_start();

// Onglet actif (par défaut : configuration)
$activeTab = $_GET['tab'] ?? 'configuration';
$validTabs = ['configuration', 'tools', 'stats', 'history', 'appearance'];
if (!in_array($activeTab, $validTabs)) {
    $activeTab = 'configuration';
}
?>

<div x-data="{ activeTab: '<?= $activeTab ?>' }" class="space-y-6">

    <!-- En-tête -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Configuration de l'Agent STM</h1>
            <p class="text-gray-500 mt-1">Gérez le comportement, les outils et l'apparence du chatbot</p>
        </div>
        <div class="flex items-center gap-3">
            <!-- Bouton test agent -->
            <button type="button"
                    onclick="openAgentChat()"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition flex items-center gap-2">
                <i class="fas fa-comments"></i>
                Tester l'Agent
            </button>
        </div>
    </div>

    <!-- Navigation par onglets -->
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
            <button @click="activeTab = 'configuration'; window.history.pushState({}, '', '?tab=configuration')"
                    :class="activeTab === 'configuration' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                <i class="fas fa-cog mr-2"></i>Configuration
            </button>
            <button @click="activeTab = 'tools'; window.history.pushState({}, '', '?tab=tools')"
                    :class="activeTab === 'tools' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                <i class="fas fa-wrench mr-2"></i>Tools
            </button>
            <button @click="activeTab = 'stats'; window.history.pushState({}, '', '?tab=stats')"
                    :class="activeTab === 'stats' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                <i class="fas fa-chart-bar mr-2"></i>Statistiques
            </button>
            <button @click="activeTab = 'history'; window.history.pushState({}, '', '?tab=history')"
                    :class="activeTab === 'history' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                <i class="fas fa-history mr-2"></i>Historique
            </button>
            <button @click="activeTab = 'appearance'; window.history.pushState({}, '', '?tab=appearance')"
                    :class="activeTab === 'appearance' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                <i class="fas fa-palette mr-2"></i>Apparence
            </button>
        </nav>
    </div>

    <!-- ============================================ -->
    <!-- ONGLET CONFIGURATION -->
    <!-- ============================================ -->
    <div x-show="activeTab === 'configuration'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">

        <form action="/stm/admin/settings/agent/save" method="POST" class="space-y-8">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

            <?php if ($isSuperAdmin): ?>
            <!-- Section Fournisseur IA -->
            <div class="bg-gradient-to-r from-violet-50 to-purple-50 rounded-2xl border border-violet-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-violet-200 bg-white/50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-violet-600 flex items-center justify-center">
                            <i class="fas fa-microchip text-white"></i>
                        </div>
                        <div>
                            <h2 class="font-bold text-gray-900">Fournisseur d'Intelligence Artificielle</h2>
                            <p class="text-sm text-gray-500">Configuration réservée aux super administrateurs</p>
                        </div>
                        <span class="ml-auto px-3 py-1 bg-violet-100 text-violet-700 text-xs font-medium rounded-full">
                            <i class="fas fa-lock mr-1"></i> Super Admin
                        </span>
                    </div>
                </div>

                <div class="p-6 space-y-6">
                    <!-- Sélection du provider -->
                    <div class="grid grid-cols-3 gap-4">
                        <?php foreach ($aiProviders as $providerId => $provider):
                            $isSelected = ($configsByKey['ai_provider']['value'] ?? 'openai') === $providerId;
                        ?>
                        <label class="relative cursor-pointer">
                            <input type="radio"
                                   name="ai_provider"
                                   value="<?= $providerId ?>"
                                   class="peer sr-only"
                                   <?= $isSelected ? 'checked' : '' ?>
                                   onchange="updateProviderUI('<?= $providerId ?>')">
                            <div class="p-4 rounded-xl border-2 transition-all
                                        peer-checked:border-violet-500 peer-checked:bg-violet-50
                                        border-gray-200 hover:border-gray-300">
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center">
                                        <i class="fas <?= $provider['icon'] ?> text-gray-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900"><?= $provider['name'] ?></p>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500"><?= $provider['description'] ?></p>
                            </div>
                            <div class="absolute top-3 right-3 hidden peer-checked:block">
                                <i class="fas fa-check-circle text-violet-500"></i>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <!-- Configuration détaillée -->
                    <div class="grid grid-cols-2 gap-6">
                        <!-- Modèle -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-cube mr-1 text-gray-400"></i> Modèle
                            </label>
                            <select name="ai_model" id="ai_model"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
                                <?php
                                $currentProvider = $configsByKey['ai_provider']['value'] ?? 'openai';
                                $currentModel = $configsByKey['ai_model']['value'] ?? 'gpt-4o';
                                foreach ($aiProviders as $providerId => $provider):
                                    foreach ($provider['models'] as $model):
                                        $hidden = $providerId !== $currentProvider ? 'hidden' : '';
                                ?>
                                <option value="<?= $model ?>"
                                        data-provider="<?= $providerId ?>"
                                        class="model-option <?= $hidden ?>"
                                        <?= $model === $currentModel ? 'selected' : '' ?>>
                                    <?= $model ?>
                                </option>
                                <?php endforeach; endforeach; ?>
                            </select>
                        </div>

                        <!-- Température -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-temperature-half mr-1 text-gray-400"></i> Température
                                <span class="text-gray-400 font-normal">(créativité)</span>
                            </label>
                            <div class="flex items-center gap-4">
                                <input type="range" name="ai_temperature" id="ai_temperature"
                                       min="0" max="1" step="0.1"
                                       value="<?= htmlspecialchars($configsByKey['ai_temperature']['value'] ?? '0.7') ?>"
                                       class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-violet-600"
                                       oninput="document.getElementById('temp_value').textContent = this.value">
                                <span id="temp_value" class="w-10 text-center font-mono text-sm bg-gray-100 px-2 py-1 rounded">
                                    <?= htmlspecialchars($configsByKey['ai_temperature']['value'] ?? '0.7') ?>
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-gray-400">0 = précis, 1 = créatif</p>
                        </div>
                    </div>

                    <!-- Clé API -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-key mr-1 text-gray-400"></i> Clé API
                        </label>
                        <div class="relative">
                            <input type="password" name="ai_api_key" id="ai_api_key"
                                   value="<?= htmlspecialchars($configsByKey['ai_api_key']['value'] ?? '') ?>"
                                   placeholder="sk-... ou sk-ant-..."
                                   class="w-full px-4 py-2.5 pr-24 border border-gray-300 rounded-lg focus:ring-2 focus:ring-violet-500 focus:border-violet-500 font-mono text-sm">
                            <button type="button" onclick="toggleApiKey()"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 px-3 py-1 text-sm text-gray-500 hover:text-gray-700">
                                <i id="api_key_icon" class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- URL API (pour Ollama) -->
                    <div id="api_url_section" class="<?= ($configsByKey['ai_provider']['value'] ?? 'openai') !== 'ollama' ? 'hidden' : '' ?>">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-link mr-1 text-gray-400"></i> URL de l'API Ollama
                        </label>
                        <input type="text" name="ai_api_url" id="ai_api_url"
                               value="<?= htmlspecialchars($configsByKey['ai_api_url']['value'] ?? 'http://localhost:11434') ?>"
                               placeholder="http://localhost:11434"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-violet-500 focus:border-violet-500 font-mono text-sm">
                    </div>

                    <!-- Bouton test connexion -->
                    <div class="flex items-center gap-4 pt-2">
                        <button type="button" onclick="testConnection()"
                                class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-lg transition flex items-center gap-2">
                            <i class="fas fa-plug"></i>
                            Tester la connexion
                        </button>
                        <span id="connection_status" class="text-sm"></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Section Personnalisation du Prompt -->
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center">
                        <i class="fas fa-wand-magic-sparkles text-indigo-600"></i>
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-900">Personnalisation du Prompt</h2>
                        <p class="text-sm text-gray-500">Apprenez au bot de nouvelles choses sans modifier le code</p>
                    </div>
                    <button type="button" onclick="previewPrompt()"
                            class="ml-auto px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition flex items-center gap-2">
                        <i class="fas fa-eye"></i>
                        Prévisualiser
                    </button>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-start gap-3 mb-6">
                    <i class="fas fa-lightbulb text-blue-500 text-lg flex-shrink-0 mt-0.5"></i>
                    <p class="text-sm text-blue-700">
                        Ces instructions sont ajoutées au prompt système de l'Agent. Vous pouvez activer/désactiver chaque section individuellement.
                    </p>
                </div>

                <div class="space-y-6">
                    <?php foreach ($fields as $key => $field):
                        $config = $configsByKey[$key] ?? ['value' => '', 'is_active' => 1];
                        $isActive = (bool)($config['is_active'] ?? true);
                        $value = htmlspecialchars($config['value'] ?? '');
                    ?>
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center">
                                    <i class="fas <?= $field['icon'] ?> text-indigo-600"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900"><?= $field['label'] ?></h3>
                                    <p class="text-sm text-gray-500"><?= $field['description'] ?></p>
                                </div>
                            </div>

                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="<?= $key ?>_active" class="sr-only peer"
                                       <?= $isActive ? 'checked' : '' ?>
                                       onchange="toggleSection('<?= $key ?>', this.checked)">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                <span class="ml-3 text-sm font-medium text-gray-700" id="<?= $key ?>_status">
                                    <?= $isActive ? 'Actif' : 'Inactif' ?>
                                </span>
                            </label>
                        </div>

                        <div class="p-6" id="<?= $key ?>_content" style="<?= $isActive ? '' : 'opacity: 0.5;' ?>">
                            <textarea name="<?= $key ?>" id="<?= $key ?>"
                                      rows="<?= $field['rows'] ?>"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono text-sm resize-y"
                                      placeholder="<?= htmlspecialchars($field['placeholder']) ?>"
                                      <?= $isActive ? '' : 'disabled' ?>><?= $value ?></textarea>

                            <div class="mt-2 flex justify-between items-center text-xs text-gray-400">
                                <span>Utilisez des retours à la ligne pour structurer</span>
                                <span id="<?= $key ?>_count"><?= strlen($config['value'] ?? '') ?> caractères</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                <button type="button" onclick="confirmReset()"
                        class="px-4 py-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition flex items-center gap-2">
                    <i class="fas fa-rotate-left"></i>
                    Réinitialiser
                </button>

                <button type="submit"
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition flex items-center gap-2">
                    <i class="fas fa-save"></i>
                    Enregistrer
                </button>
            </div>
        </form>
    </div>

    <!-- ============================================ -->
    <!-- ONGLET TOOLS -->
    <!-- ============================================ -->
    <div x-show="activeTab === 'tools'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" style="display: none;">

        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center">
                        <i class="fas fa-wrench text-amber-600"></i>
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-900">Tools de l'Agent</h2>
                        <p class="text-sm text-gray-500">Capacités et actions disponibles pour le chatbot</p>
                    </div>
                </div>
                <button type="button" onclick="openCreateToolModal()"
                        class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg transition flex items-center gap-2">
                    <i class="fas fa-plus"></i>
                    Créer un Tool via IA
                </button>
            </div>

            <div class="p-6">
                <div class="text-center py-12 text-gray-500">
                    <i class="fas fa-wrench text-6xl text-gray-300 mb-4"></i>
                    <p class="text-lg font-medium">Chargement des tools...</p>
                    <p class="text-sm">Cette section sera disponible après validation de l'étape 1</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- ONGLET STATISTIQUES -->
    <!-- ============================================ -->
    <div x-show="activeTab === 'stats'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" style="display: none;">

        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center">
                        <i class="fas fa-chart-bar text-emerald-600"></i>
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-900">Statistiques d'utilisation</h2>
                        <p class="text-sm text-gray-500">Analyse de l'utilisation de l'Agent STM</p>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="text-center py-12 text-gray-500">
                    <i class="fas fa-chart-line text-6xl text-gray-300 mb-4"></i>
                    <p class="text-lg font-medium">Statistiques à venir</p>
                    <p class="text-sm">Graphiques d'utilisation, tokens consommés, coûts estimés...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- ONGLET HISTORIQUE -->
    <!-- ============================================ -->
    <div x-show="activeTab === 'history'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" style="display: none;">

        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                        <i class="fas fa-history text-blue-600"></i>
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-900">Historique des conversations</h2>
                        <p class="text-sm text-gray-500">Toutes les conversations par utilisateur</p>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="text-center py-12 text-gray-500">
                    <i class="fas fa-comments text-6xl text-gray-300 mb-4"></i>
                    <p class="text-lg font-medium">Historique à venir</p>
                    <p class="text-sm">Liste des conversations, filtres, détails des échanges...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- ONGLET APPARENCE -->
    <!-- ============================================ -->
    <div x-show="activeTab === 'appearance'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" style="display: none;">

        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-pink-100 flex items-center justify-center">
                        <i class="fas fa-palette text-pink-600"></i>
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-900">Apparence du chatbot</h2>
                        <p class="text-sm text-gray-500">Modèles visuels et personnalisation</p>
                    </div>
                </div>
                <button type="button" onclick="openCustomizeViaAI()"
                        class="px-4 py-2 bg-pink-600 hover:bg-pink-700 text-white rounded-lg transition flex items-center gap-2">
                    <i class="fas fa-wand-magic-sparkles"></i>
                    Personnaliser via IA
                </button>
            </div>

            <div class="p-6">
                <div class="text-center py-12 text-gray-500">
                    <i class="fas fa-paint-brush text-6xl text-gray-300 mb-4"></i>
                    <p class="text-lg font-medium">Modèles visuels à venir</p>
                    <p class="text-sm">Classique, Moderne, Sombre, 🎉 Party Mode...</p>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Modal prévisualisation prompt -->
<div id="previewModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[80vh] flex flex-col">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-eye mr-2 text-indigo-600"></i>
                Prévisualisation du prompt
            </h3>
            <button onclick="closePreview()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6 overflow-y-auto flex-1">
            <pre id="previewContent" class="bg-gray-900 text-gray-100 p-4 rounded-lg text-sm font-mono whitespace-pre-wrap"></pre>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-between items-center">
            <span id="previewStats" class="text-sm text-gray-500"></span>
            <button onclick="closePreview()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg">
                Fermer
            </button>
        </div>
    </div>
</div>

<!-- Modal confirmation reset -->
<div id="resetModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
        <div class="p-6">
            <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Réinitialiser ?</h3>
            <p class="text-gray-500 text-center text-sm">
                Cette action remplacera vos personnalisations par les valeurs par défaut.
            </p>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
            <button onclick="closeReset()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                Annuler
            </button>
            <form action="/stm/admin/settings/agent/reset" method="POST" class="inline">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg">
                    Réinitialiser
                </button>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$title = 'Configuration Agent STM';

$pageScripts = <<<'JS'
<script>
// Toggle section active/inactive
function toggleSection(key, isActive) {
    const content = document.getElementById(key + '_content');
    const textarea = document.getElementById(key);
    const status = document.getElementById(key + '_status');

    content.style.opacity = isActive ? '1' : '0.5';
    textarea.disabled = !isActive;
    status.textContent = isActive ? 'Actif' : 'Inactif';
}

// Compteur de caractères
document.querySelectorAll('textarea').forEach(textarea => {
    textarea.addEventListener('input', function() {
        const countEl = document.getElementById(this.id + '_count');
        if (countEl) countEl.textContent = this.value.length + ' caractères';
    });
});

// Prévisualisation
function previewPrompt() {
    fetch('/stm/admin/settings/agent/preview')
        .then(res => res.json())
        .then(data => {
            document.getElementById('previewContent').textContent = data.prompt || '(Aucune configuration active)';
            document.getElementById('previewStats').textContent = data.length + ' caractères';
            document.getElementById('previewModal').classList.remove('hidden');
            document.getElementById('previewModal').classList.add('flex');
        });
}

function closePreview() {
    document.getElementById('previewModal').classList.add('hidden');
    document.getElementById('previewModal').classList.remove('flex');
}

function confirmReset() {
    document.getElementById('resetModal').classList.remove('hidden');
    document.getElementById('resetModal').classList.add('flex');
}

function closeReset() {
    document.getElementById('resetModal').classList.add('hidden');
    document.getElementById('resetModal').classList.remove('flex');
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { closePreview(); closeReset(); }
});

// Config IA
function toggleApiKey() {
    const input = document.getElementById('ai_api_key');
    const icon = document.getElementById('api_key_icon');
    input.type = input.type === 'password' ? 'text' : 'password';
    icon.className = input.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
}

function updateProviderUI(provider) {
    const urlSection = document.getElementById('api_url_section');
    if (urlSection) urlSection.classList.toggle('hidden', provider !== 'ollama');

    const modelSelect = document.getElementById('ai_model');
    if (modelSelect) {
        let firstVisible = null;
        modelSelect.querySelectorAll('option').forEach(opt => {
            const isForProvider = opt.dataset.provider === provider;
            opt.classList.toggle('hidden', !isForProvider);
            opt.disabled = !isForProvider;
            if (isForProvider && !firstVisible) firstVisible = opt;
        });
        if (firstVisible) modelSelect.value = firstVisible.value;
    }
}

function testConnection() {
    const statusEl = document.getElementById('connection_status');
    statusEl.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Test...';
    statusEl.className = 'text-sm text-gray-500';

    fetch('/stm/admin/settings/agent/test')
        .then(res => res.json())
        .then(data => {
            statusEl.innerHTML = data.success
                ? '<i class="fas fa-check-circle mr-2"></i>' + data.message
                : '<i class="fas fa-times-circle mr-2"></i>' + data.error;
            statusEl.className = 'text-sm ' + (data.success ? 'text-green-600' : 'text-red-600');
        });
}

// Placeholders pour les futurs onglets
function openCreateToolModal() {
    alert('🔧 Création de Tool via IA - À venir dans l\'étape 3');
}

function openCustomizeViaAI() {
    alert('🎨 Personnalisation via IA - À venir dans l\'étape 6');
}

function openAgentChat() {
    // Ouvrir le chat de l'agent (si widget existe)
    if (typeof toggleAgentChat === 'function') {
        toggleAgentChat();
    } else {
        alert('💬 Le widget Agent doit être activé sur cette page');
    }
}
</script>
JS;

require __DIR__ . '/../../layouts/admin.php';
?>