<?php
/**
 * Vue Configuration Agent STM
 *
 * Page de configuration du chatbot via l'admin
 * Permet de personnaliser le prompt sans modifier le code
 * Section IA réservée aux super admins
 *
 * @created  2025/12/11
 * @modified 2025/12/11 - Ajout config fournisseur IA
 */

ob_start();
?>

<div class="space-y-6">
    <!-- En-tête -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Configuration de l'Agent STM</h1>
            <p class="text-gray-500 mt-1">Personnalisez le comportement du chatbot sans modifier le code</p>
        </div>
        <div class="flex items-center gap-3">
            <!-- Bouton prévisualisation -->
            <button type="button" 
                    onclick="previewPrompt()"
                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition flex items-center gap-2">
                <i class="fas fa-eye"></i>
                Prévisualiser
            </button>
        </div>
    </div>

    <!-- Navigation onglets settings -->
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
            <a href="/stm/admin/settings" 
               class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                <i class="fas fa-shield-halved mr-2"></i>Permissions
            </a>
            <a href="/stm/admin/settings?tab=general" 
               class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                <i class="fas fa-cog mr-2"></i>Général
            </a>
            <a href="/stm/admin/settings/agent" 
               class="border-indigo-500 text-indigo-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                <i class="fas fa-robot mr-2"></i>Agent STM
            </a>
        </nav>
    </div>

    <!-- Formulaire -->
    <form action="/stm/admin/settings/agent/save" method="POST" class="space-y-8">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

        <?php if ($isSuperAdmin): ?>
        <!-- ================================================ -->
        <!-- SECTION FOURNISSEUR IA (Super Admin uniquement) -->
        <!-- ================================================ -->
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
                                    peer-checked:border-<?= $provider['color'] ?>-500 peer-checked:bg-<?= $provider['color'] ?>-50
                                    border-gray-200 hover:border-gray-300">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-10 h-10 rounded-lg bg-<?= $provider['color'] ?>-100 flex items-center justify-center">
                                    <i class="fas <?= $provider['icon'] ?> text-<?= $provider['color'] ?>-600"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900"><?= $provider['name'] ?></p>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500"><?= $provider['description'] ?></p>
                        </div>
                        <div class="absolute top-3 right-3 hidden peer-checked:block">
                            <i class="fas fa-check-circle text-<?= $provider['color'] ?>-500"></i>
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
                        <select name="ai_model" 
                                id="ai_model"
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
                            <input type="range" 
                                   name="ai_temperature" 
                                   id="ai_temperature"
                                   min="0" max="1" step="0.1"
                                   value="<?= htmlspecialchars($configsByKey['ai_temperature']['value'] ?? '0.7') ?>"
                                   class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-violet-600"
                                   oninput="document.getElementById('temp_value').textContent = this.value">
                            <span id="temp_value" class="w-10 text-center font-mono text-sm bg-gray-100 px-2 py-1 rounded">
                                <?= htmlspecialchars($configsByKey['ai_temperature']['value'] ?? '0.7') ?>
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-gray-400">0 = précis et déterministe, 1 = créatif et varié</p>
                    </div>
                </div>

                <!-- Clé API -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-key mr-1 text-gray-400"></i> Clé API
                    </label>
                    <div class="relative">
                        <input type="password" 
                               name="ai_api_key" 
                               id="ai_api_key"
                               value="<?= htmlspecialchars($configsByKey['ai_api_key']['value'] ?? '') ?>"
                               placeholder="sk-... ou sk-ant-..."
                               class="w-full px-4 py-2.5 pr-24 border border-gray-300 rounded-lg focus:ring-2 focus:ring-violet-500 focus:border-violet-500 font-mono text-sm">
                        <button type="button" 
                                onclick="toggleApiKey()"
                                class="absolute right-2 top-1/2 -translate-y-1/2 px-3 py-1 text-sm text-gray-500 hover:text-gray-700">
                            <i id="api_key_icon" class="fas fa-eye"></i>
                            <span id="api_key_text">Afficher</span>
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-gray-400">Laissez vide pour conserver la clé actuelle</p>
                </div>

                <!-- URL API (pour Ollama) -->
                <div id="api_url_section" class="<?= ($configsByKey['ai_provider']['value'] ?? 'openai') !== 'ollama' ? 'hidden' : '' ?>">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-link mr-1 text-gray-400"></i> URL de l'API Ollama
                    </label>
                    <input type="text" 
                           name="ai_api_url" 
                           id="ai_api_url"
                           value="<?= htmlspecialchars($configsByKey['ai_api_url']['value'] ?? 'http://localhost:11434') ?>"
                           placeholder="http://localhost:11434"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-violet-500 focus:border-violet-500 font-mono text-sm">
                </div>

                <!-- Bouton test connexion -->
                <div class="flex items-center gap-4 pt-2">
                    <button type="button" 
                            onclick="testConnection()"
                            class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-lg transition flex items-center gap-2">
                        <i class="fas fa-plug"></i>
                        Tester la connexion
                    </button>
                    <span id="connection_status" class="text-sm"></span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ================================================ -->
        <!-- SECTION PERSONNALISATION DU PROMPT -->
        <!-- ================================================ -->
        <div>
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center">
                    <i class="fas fa-wand-magic-sparkles text-indigo-600"></i>
                </div>
                <div>
                    <h2 class="font-bold text-gray-900">Personnalisation du Prompt</h2>
                    <p class="text-sm text-gray-500">Apprenez au bot de nouvelles choses sans modifier le code</p>
                </div>
            </div>

            <!-- Alerte info -->
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-start gap-3 mb-6">
                <div class="flex-shrink-0">
                    <i class="fas fa-lightbulb text-blue-500 text-lg"></i>
                </div>
                <div>
                    <h4 class="font-medium text-blue-900">Comment ça marche ?</h4>
                    <p class="text-sm text-blue-700 mt-1">
                        Ces instructions sont ajoutées au prompt système de l'Agent. Vous pouvez activer/désactiver chaque section 
                        individuellement. Les modifications sont appliquées immédiatement à toutes les nouvelles conversations.
                    </p>
                </div>
            </div>

            <div class="space-y-6">
                <?php foreach ($fields as $key => $field): 
                    $config = $configsByKey[$key] ?? ['value' => '', 'is_active' => 1];
                    $isActive = (bool)($config['is_active'] ?? true);
                    $value = htmlspecialchars($config['value'] ?? '');
                ?>
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
                    <!-- Header de la carte -->
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
                        
                        <!-- Toggle actif/inactif -->
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" 
                                   name="<?= $key ?>_active" 
                                   class="sr-only peer"
                                   <?= $isActive ? 'checked' : '' ?>
                                   onchange="toggleSection('<?= $key ?>', this.checked)">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            <span class="ml-3 text-sm font-medium text-gray-700" id="<?= $key ?>_status">
                                <?= $isActive ? 'Actif' : 'Inactif' ?>
                            </span>
                        </label>
                    </div>
                    
                    <!-- Contenu -->
                    <div class="p-6" id="<?= $key ?>_content" style="<?= $isActive ? '' : 'opacity: 0.5;' ?>">
                        <textarea name="<?= $key ?>"
                                  id="<?= $key ?>"
                                  rows="<?= $field['rows'] ?>"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono text-sm resize-y"
                                  placeholder="<?= htmlspecialchars($field['placeholder']) ?>"
                                  <?= $isActive ? '' : 'disabled' ?>><?= $value ?></textarea>
                        
                        <!-- Compteur de caractères -->
                        <div class="mt-2 flex justify-between items-center text-xs text-gray-400">
                            <span>Utilisez des retours à la ligne pour structurer vos instructions</span>
                            <span id="<?= $key ?>_count"><?= strlen($config['value'] ?? '') ?> caractères</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Boutons d'action -->
        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
            <button type="button"
                    onclick="confirmReset()"
                    class="px-4 py-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition flex items-center gap-2">
                <i class="fas fa-rotate-left"></i>
                Réinitialiser par défaut
            </button>
            
            <div class="flex items-center gap-3">
                <a href="/stm/admin/settings" 
                   class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Annuler
                </a>
                <button type="submit"
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition flex items-center gap-2">
                    <i class="fas fa-save"></i>
                    Enregistrer
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Modal prévisualisation -->
<div id="previewModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[80vh] flex flex-col">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-eye mr-2 text-indigo-600"></i>
                Prévisualisation du prompt personnalisé
            </h3>
            <button onclick="closePreview()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6 overflow-y-auto flex-1">
            <pre id="previewContent" class="bg-gray-900 text-gray-100 p-4 rounded-lg text-sm font-mono whitespace-pre-wrap overflow-x-auto"></pre>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-between items-center">
            <span id="previewStats" class="text-sm text-gray-500"></span>
            <button onclick="closePreview()" 
                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
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
            <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Réinitialiser la configuration ?</h3>
            <p class="text-gray-500 text-center text-sm">
                Cette action remplacera toutes vos personnalisations du prompt par les valeurs par défaut. 
                La configuration IA ne sera pas modifiée.
            </p>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
            <button onclick="closeReset()" 
                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                Annuler
            </button>
            <form action="/stm/admin/settings/agent/reset" method="POST" class="inline">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <button type="submit"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
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
    
    if (isActive) {
        content.style.opacity = '1';
        textarea.disabled = false;
        status.textContent = 'Actif';
    } else {
        content.style.opacity = '0.5';
        textarea.disabled = true;
        status.textContent = 'Inactif';
    }
}

// Compteur de caractères
document.querySelectorAll('textarea').forEach(textarea => {
    textarea.addEventListener('input', function() {
        const countEl = document.getElementById(this.id + '_count');
        if (countEl) {
            countEl.textContent = this.value.length + ' caractères';
        }
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
        })
        .catch(err => {
            alert('Erreur lors de la prévisualisation');
            console.error(err);
        });
}

function closePreview() {
    document.getElementById('previewModal').classList.add('hidden');
    document.getElementById('previewModal').classList.remove('flex');
}

// Reset confirmation
function confirmReset() {
    document.getElementById('resetModal').classList.remove('hidden');
    document.getElementById('resetModal').classList.add('flex');
}

function closeReset() {
    document.getElementById('resetModal').classList.add('hidden');
    document.getElementById('resetModal').classList.remove('flex');
}

// Fermer modals avec Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePreview();
        closeReset();
    }
});

// ====== CONFIGURATION IA (Super Admin) ======

// Afficher/masquer la clé API
function toggleApiKey() {
    const input = document.getElementById('ai_api_key');
    const icon = document.getElementById('api_key_icon');
    const text = document.getElementById('api_key_text');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
        text.textContent = 'Masquer';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
        text.textContent = 'Afficher';
    }
}

// Mettre à jour l'UI selon le provider sélectionné
function updateProviderUI(provider) {
    // Afficher/masquer URL API (pour Ollama)
    const urlSection = document.getElementById('api_url_section');
    if (urlSection) {
        urlSection.classList.toggle('hidden', provider !== 'ollama');
    }
    
    // Mettre à jour les modèles disponibles
    const modelSelect = document.getElementById('ai_model');
    if (modelSelect) {
        const options = modelSelect.querySelectorAll('option');
        let firstVisible = null;
        
        options.forEach(opt => {
            const isForProvider = opt.dataset.provider === provider;
            opt.classList.toggle('hidden', !isForProvider);
            opt.disabled = !isForProvider;
            
            if (isForProvider && !firstVisible) {
                firstVisible = opt;
            }
        });
        
        // Sélectionner le premier modèle visible
        if (firstVisible) {
            modelSelect.value = firstVisible.value;
        }
    }
}

// Tester la connexion au provider IA
function testConnection() {
    const statusEl = document.getElementById('connection_status');
    statusEl.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Test en cours...';
    statusEl.className = 'text-sm text-gray-500';
    
    fetch('/stm/admin/settings/agent/test')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                statusEl.innerHTML = '<i class="fas fa-check-circle mr-2"></i>' + data.message;
                statusEl.className = 'text-sm text-green-600';
            } else {
                statusEl.innerHTML = '<i class="fas fa-times-circle mr-2"></i>' + data.error;
                statusEl.className = 'text-sm text-red-600';
            }
        })
        .catch(err => {
            statusEl.innerHTML = '<i class="fas fa-times-circle mr-2"></i>Erreur de connexion';
            statusEl.className = 'text-sm text-red-600';
        });
}
</script>
JS;

require __DIR__ . '/../../layouts/admin.php';
?>
