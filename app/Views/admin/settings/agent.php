<?php
/**
 * Vue Configuration Agent STM
 *
 * Page de configuration complète du chatbot via l'admin
 * 5 onglets : Configuration, Tools, Statistiques, Historique, Apparence
 *
 * @created  2025/12/11
 * @modified 2025/12/12 - Ajout des 5 onglets
 * @modified 2025/12/16 - Ajout filtrage permissions sur configuration
 */

use App\Helpers\PermissionHelper;

// Permissions pour l'agent
$canConfig = PermissionHelper::can('agent.config');

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

    <?php if (!$canConfig): ?>
    <!-- Message si pas de permission de configuration -->
    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4">
        <div class="flex items-center">
            <i class="fas fa-lock text-yellow-500 mr-2"></i>
            <p class="text-yellow-700">Vous n'avez pas les permissions nécessaires pour modifier la configuration de l'agent. Affichage en lecture seule.</p>
        </div>
    </div>
    <?php endif; ?>

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
                                   <?= !$canConfig ? 'disabled' : '' ?>
                                   onchange="updateProviderUI('<?= $providerId ?>')">
                            <div class="p-4 rounded-xl border-2 transition-all
                                        peer-checked:border-violet-500 peer-checked:bg-violet-50
                                        border-gray-200 hover:border-gray-300 <?= !$canConfig ? 'opacity-60' : '' ?>">
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
                                    <?= !$canConfig ? 'disabled' : '' ?>
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-violet-500 focus:border-violet-500 <?= !$canConfig ? 'bg-gray-100' : '' ?>">
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
                                       <?= !$canConfig ? 'disabled' : '' ?>
                                       class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-violet-600 <?= !$canConfig ? 'opacity-60' : '' ?>"
                                       oninput="document.getElementById('temp_value').textContent = this.value">
                                <span id="temp_value" class="w-10 text-center font-mono text-sm bg-gray-100 px-2 py-1 rounded">
                                    <?= htmlspecialchars($configsByKey['ai_temperature']['value'] ?? '0.7') ?>
                                </span>
                            </div>
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
                                   <?= !$canConfig ? 'disabled' : '' ?>
                                   placeholder="sk-..."
                                   class="w-full px-4 py-2.5 pr-20 border border-gray-300 rounded-lg focus:ring-2 focus:ring-violet-500 focus:border-violet-500 font-mono text-sm <?= !$canConfig ? 'bg-gray-100' : '' ?>">
                            <button type="button" onclick="toggleApiKey()"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 px-3 py-1 text-gray-500 hover:text-gray-700">
                                <i id="api_key_icon" class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- URL API (Ollama) -->
                    <div id="api_url_section" class="<?= ($configsByKey['ai_provider']['value'] ?? 'openai') !== 'ollama' ? 'hidden' : '' ?>">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-link mr-1 text-gray-400"></i> URL de l'API (Ollama)
                        </label>
                        <input type="url"
                               name="ai_api_url"
                               value="<?= htmlspecialchars($configsByKey['ai_api_url']['value'] ?? 'http://localhost:11434') ?>"
                               <?= !$canConfig ? 'disabled' : '' ?>
                               placeholder="http://localhost:11434"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-violet-500 focus:border-violet-500 <?= !$canConfig ? 'bg-gray-100' : '' ?>">
                    </div>

                    <!-- Test connexion -->
                    <div class="flex items-center justify-between pt-4 border-t border-violet-200">
                        <div id="connection_status" class="text-sm text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i> Cliquez sur "Tester" pour vérifier la connexion
                        </div>
                        <button type="button" onclick="testConnection()"
                                class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-lg transition text-sm">
                            <i class="fas fa-plug mr-2"></i>Tester la connexion
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Section System Prompt -->
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-600 flex items-center justify-center">
                            <i class="fas fa-brain text-white"></i>
                        </div>
                        <div>
                            <h2 class="font-bold text-gray-900">Instructions de l'Agent (System Prompt)</h2>
                            <p class="text-sm text-gray-500">Définissez le comportement et la personnalité de l'agent</p>
                        </div>
                    </div>
                </div>

                <div class="p-6 space-y-4">
                    <!-- Variables disponibles -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <p class="text-sm font-medium text-blue-800 mb-2">
                            <i class="fas fa-info-circle mr-1"></i> Variables disponibles :
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <code class="px-2 py-1 bg-white rounded text-xs text-blue-700 border border-blue-200">{company_name}</code>
                            <code class="px-2 py-1 bg-white rounded text-xs text-blue-700 border border-blue-200">{user_name}</code>
                            <code class="px-2 py-1 bg-white rounded text-xs text-blue-700 border border-blue-200">{user_role}</code>
                            <code class="px-2 py-1 bg-white rounded text-xs text-blue-700 border border-blue-200">{current_date}</code>
                            <code class="px-2 py-1 bg-white rounded text-xs text-blue-700 border border-blue-200">{current_page}</code>
                        </div>
                    </div>

                    <!-- Textarea -->
                    <textarea name="system_prompt"
                              rows="12"
                              <?= !$canConfig ? 'disabled' : '' ?>
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm <?= !$canConfig ? 'bg-gray-100' : '' ?>"
                              placeholder="Tu es un assistant pour STM..."><?= htmlspecialchars($configsByKey['system_prompt']['value'] ?? '') ?></textarea>

                    <div class="flex items-center justify-between">
                        <p class="text-xs text-gray-500">
                            <i class="fas fa-lightbulb mr-1"></i>
                            Conseil : Soyez précis sur le contexte métier et le ton souhaité
                        </p>
                        <button type="button" onclick="previewPrompt()"
                                class="text-sm text-blue-600 hover:text-blue-800 transition">
                            <i class="fas fa-eye mr-1"></i> Prévisualiser avec variables
                        </button>
                    </div>
                </div>
            </div>

            <!-- Section Messages -->
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-green-600 flex items-center justify-center">
                            <i class="fas fa-comment-dots text-white"></i>
                        </div>
                        <div>
                            <h2 class="font-bold text-gray-900">Messages de l'interface</h2>
                            <p class="text-sm text-gray-500">Personnalisez les textes affichés aux utilisateurs</p>
                        </div>
                    </div>
                </div>

                <div class="p-6 grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Message de bienvenue</label>
                        <textarea name="welcome_message"
                                  rows="3"
                                  <?= !$canConfig ? 'disabled' : '' ?>
                                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 <?= !$canConfig ? 'bg-gray-100' : '' ?>"
                                  placeholder="Bonjour ! Comment puis-je vous aider ?"><?= htmlspecialchars($configsByKey['welcome_message']['value'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Placeholder du champ de saisie</label>
                        <input type="text"
                               name="input_placeholder"
                               value="<?= htmlspecialchars($configsByKey['input_placeholder']['value'] ?? 'Posez votre question...') ?>"
                               <?= !$canConfig ? 'disabled' : '' ?>
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 <?= !$canConfig ? 'bg-gray-100' : '' ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Message d'erreur</label>
                        <input type="text"
                               name="error_message"
                               value="<?= htmlspecialchars($configsByKey['error_message']['value'] ?? 'Désolé, une erreur est survenue.') ?>"
                               <?= !$canConfig ? 'disabled' : '' ?>
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 <?= !$canConfig ? 'bg-gray-100' : '' ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Message "en cours de réflexion"</label>
                        <input type="text"
                               name="thinking_message"
                               value="<?= htmlspecialchars($configsByKey['thinking_message']['value'] ?? 'Je réfléchis...') ?>"
                               <?= !$canConfig ? 'disabled' : '' ?>
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 <?= !$canConfig ? 'bg-gray-100' : '' ?>">
                    </div>
                </div>
            </div>

            <!-- Boutons d'action -->
            <?php if ($canConfig): ?>
            <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                <button type="button" onclick="confirmReset()"
                        class="px-4 py-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition">
                    <i class="fas fa-undo mr-2"></i>Réinitialiser par défaut
                </button>
                <button type="submit"
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition flex items-center gap-2">
                    <i class="fas fa-save"></i>
                    Enregistrer la configuration
                </button>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- ============================================ -->
    <!-- ONGLET TOOLS -->
    <!-- ============================================ -->
    <div x-show="activeTab === 'tools'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">

        <div class="space-y-6">
            <!-- En-tête Tools -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Outils disponibles</h2>
                    <p class="text-sm text-gray-500">Fonctionnalités que l'agent peut utiliser</p>
                </div>
                <?php if ($canConfig): ?>
                <button type="button" onclick="openCreateToolModal()"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition flex items-center gap-2">
                    <i class="fas fa-plus"></i>
                    Créer un Tool via IA
                </button>
                <?php endif; ?>
            </div>

            <!-- Liste des tools -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php if (!empty($tools)): ?>
                    <?php foreach ($tools as $tool): ?>
                    <div id="tool-<?= $tool['id'] ?>" class="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-md transition">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                    <i class="fas <?= htmlspecialchars($tool['icon'] ?? 'fa-wrench') ?> text-indigo-600"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900"><?= htmlspecialchars($tool['name']) ?></h3>
                                    <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($tool['description'] ?? '') ?></p>
                                    <div class="flex items-center gap-2 mt-2">
                                        <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded">
                                            <?= htmlspecialchars($tool['type'] ?? 'custom') ?>
                                        </span>
                                        <?php if ($tool['is_system'] ?? false): ?>
                                        <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded">
                                            <i class="fas fa-lock mr-1"></i>Système
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <!-- Toggle actif -->
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox"
                                           class="sr-only peer"
                                           data-tool-id="<?= $tool['id'] ?>"
                                           <?= $tool['is_active'] ? 'checked' : '' ?>
                                           <?= !$canConfig ? 'disabled' : '' ?>
                                           onchange="toggleTool(<?= $tool['id'] ?>, this.checked)">
                                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600"></div>
                                </label>
                                <?php if ($canConfig && !($tool['is_system'] ?? false)): ?>
                                <!-- Boutons édition/suppression -->
                                <button type="button" onclick="editTool(<?= $tool['id'] ?>)"
                                        class="p-1.5 text-gray-400 hover:text-gray-600 transition">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" onclick="deleteTool(<?= $tool['id'] ?>, '<?= htmlspecialchars($tool['name']) ?>')"
                                        class="p-1.5 text-gray-400 hover:text-red-600 transition">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-2 bg-gray-50 rounded-lg p-8 text-center">
                        <i class="fas fa-wrench text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500">Aucun tool configuré</p>
                        <?php if ($canConfig): ?>
                        <button type="button" onclick="openCreateToolModal()"
                                class="mt-3 text-indigo-600 hover:text-indigo-800">
                            <i class="fas fa-plus mr-1"></i>Créer le premier tool
                        </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- ONGLET STATISTIQUES -->
    <!-- ============================================ -->
    <div x-show="activeTab === 'stats'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">

        <div class="space-y-6">
            <!-- KPIs -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg border border-gray-200 p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-comments text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900"><?= $agentStats['total_conversations'] ?? 0 ?></p>
                            <p class="text-sm text-gray-500">Conversations</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg border border-gray-200 p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
                            <i class="fas fa-paper-plane text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900"><?= $agentStats['total_messages'] ?? 0 ?></p>
                            <p class="text-sm text-gray-500">Messages</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg border border-gray-200 p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center">
                            <i class="fas fa-wrench text-orange-600"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900"><?= $agentStats['tool_calls'] ?? 0 ?></p>
                            <p class="text-sm text-gray-500">Appels Tools</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg border border-gray-200 p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                            <i class="fas fa-coins text-purple-600"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900"><?= number_format($agentStats['tokens_used'] ?? 0, 0, ',', ' ') ?></p>
                            <p class="text-sm text-gray-500">Tokens utilisés</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Placeholder graphiques -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Activité des 7 derniers jours</h3>
                <div class="h-64 flex items-center justify-center bg-gray-50 rounded-lg">
                    <p class="text-gray-400"><i class="fas fa-chart-line mr-2"></i>Graphique à venir</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- ONGLET HISTORIQUE -->
    <!-- ============================================ -->
    <div x-show="activeTab === 'history'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">

        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900">Dernières conversations</h3>
                <div class="flex items-center gap-2">
                    <input type="text" placeholder="Rechercher..." class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm">
                </div>
            </div>

            <?php if (!empty($recentConversations)): ?>
            <div class="divide-y divide-gray-100">
                <?php foreach ($recentConversations as $conv): ?>
                <div class="px-6 py-4 hover:bg-gray-50 transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-900"><?= htmlspecialchars($conv['user_name'] ?? 'Utilisateur') ?></p>
                            <p class="text-sm text-gray-500 truncate max-w-md"><?= htmlspecialchars($conv['last_message'] ?? '') ?></p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500"><?= date('d/m/Y H:i', strtotime($conv['created_at'])) ?></p>
                            <p class="text-xs text-gray-400"><?= $conv['message_count'] ?? 0 ?> messages</p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="px-6 py-12 text-center">
                <i class="fas fa-history text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">Aucune conversation récente</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- ONGLET APPARENCE -->
    <!-- ============================================ -->
    <div x-show="activeTab === 'appearance'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">

        <form action="/stm/admin/settings/agent/appearance" method="POST" class="space-y-6">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Options visuelles -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Options visuelles</h3>

                    <div class="space-y-4">
                        <!-- Couleur principale -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Couleur principale</label>
                            <div class="flex items-center gap-3">
                                <input type="color"
                                       name="primary_color"
                                       value="<?= htmlspecialchars($configsByKey['primary_color']['value'] ?? '#4F46E5') ?>"
                                       <?= !$canConfig ? 'disabled' : '' ?>
                                       class="w-10 h-10 rounded cursor-pointer">
                                <input type="text"
                                       name="primary_color_hex"
                                       value="<?= htmlspecialchars($configsByKey['primary_color']['value'] ?? '#4F46E5') ?>"
                                       <?= !$canConfig ? 'disabled' : '' ?>
                                       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg font-mono text-sm <?= !$canConfig ? 'bg-gray-100' : '' ?>">
                            </div>
                        </div>

                        <!-- Position du widget -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Position du widget</label>
                            <select name="widget_position"
                                    <?= !$canConfig ? 'disabled' : '' ?>
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg <?= !$canConfig ? 'bg-gray-100' : '' ?>">
                                <option value="bottom-right" <?= ($configsByKey['widget_position']['value'] ?? 'bottom-right') === 'bottom-right' ? 'selected' : '' ?>>Bas droite</option>
                                <option value="bottom-left" <?= ($configsByKey['widget_position']['value'] ?? '') === 'bottom-left' ? 'selected' : '' ?>>Bas gauche</option>
                            </select>
                        </div>

                        <!-- Taille du widget -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Taille du widget</label>
                            <select name="widget_size"
                                    <?= !$canConfig ? 'disabled' : '' ?>
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg <?= !$canConfig ? 'bg-gray-100' : '' ?>">
                                <option value="small" <?= ($configsByKey['widget_size']['value'] ?? '') === 'small' ? 'selected' : '' ?>>Petit</option>
                                <option value="medium" <?= ($configsByKey['widget_size']['value'] ?? 'medium') === 'medium' ? 'selected' : '' ?>>Moyen</option>
                                <option value="large" <?= ($configsByKey['widget_size']['value'] ?? '') === 'large' ? 'selected' : '' ?>>Grand</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Prévisualisation -->
                <div class="bg-gray-100 rounded-lg p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Prévisualisation</h3>
                    <div class="bg-white rounded-lg shadow-lg p-4 max-w-sm mx-auto">
                        <div class="flex items-center gap-3 mb-4 pb-3 border-b">
                            <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center">
                                <i class="fas fa-robot text-white text-sm"></i>
                            </div>
                            <span class="font-semibold text-gray-900">Agent STM</span>
                        </div>
                        <div class="space-y-3">
                            <div class="bg-gray-100 rounded-lg p-3 text-sm">
                                Bonjour ! Comment puis-je vous aider ?
                            </div>
                            <div class="bg-indigo-600 text-white rounded-lg p-3 text-sm ml-8">
                                Quelle est ma dernière commande ?
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($canConfig): ?>
            <div class="flex justify-end">
                <button type="submit"
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition">
                    <i class="fas fa-save mr-2"></i>Enregistrer l'apparence
                </button>
            </div>
            <?php endif; ?>
        </form>
    </div>

</div>

<!-- Modal Prévisualisation Prompt -->
<div id="previewModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full mx-4 max-h-[80vh] overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-bold text-gray-900">Prévisualisation du System Prompt</h3>
            <button type="button" onclick="closePreview()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6 overflow-y-auto max-h-[60vh]">
            <pre id="previewContent" class="whitespace-pre-wrap text-sm bg-gray-50 p-4 rounded-lg border border-gray-200"></pre>
        </div>
    </div>
</div>

<!-- Modal Reset -->
<div id="resetModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4">
        <div class="p-6 text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">Réinitialiser la configuration ?</h3>
            <p class="text-gray-500 mb-6">Cette action restaurera tous les paramètres par défaut. Cette opération est irréversible.</p>
            <div class="flex gap-3 justify-center">
                <button type="button" onclick="closeReset()"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                    Annuler
                </button>
                <form action="/stm/admin/settings/agent/reset" method="POST" class="inline">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <button type="submit"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                        Réinitialiser
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Création Tool -->
<div id="createToolModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full mx-4">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-bold text-gray-900">Créer un Tool via IA</h3>
            <button type="button" onclick="closeCreateToolModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6">
            <p class="text-sm text-gray-500 mb-4">
                Décrivez en langage naturel ce que le tool doit faire. L'IA générera automatiquement le code.
            </p>
            <textarea id="toolDescription"
                      rows="4"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                      placeholder="Ex: Un outil qui recherche les commandes d'un client par son numéro..."></textarea>
            <div class="mt-4 flex justify-end gap-3">
                <button type="button" onclick="closeCreateToolModal()"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                    Annuler
                </button>
                <button type="button" id="createToolBtn" onclick="submitCreateTool()"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition flex items-center gap-2">
                    <i class="fas fa-magic"></i> Générer le Tool
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$title = "Configuration Agent STM";

$pageScripts = <<<JS
<script>
// Preview prompt
function previewPrompt() {
    const prompt = document.querySelector('textarea[name="system_prompt"]').value;
    const preview = prompt
        .replace(/{company_name}/g, '<span class="text-indigo-600 font-semibold">Trendy Foods</span>')
        .replace(/{user_name}/g, '<span class="text-green-600 font-semibold">Jean Dupont</span>')
        .replace(/{user_role}/g, '<span class="text-orange-600 font-semibold">Admin</span>')
        .replace(/{current_date}/g, '<span class="text-blue-600 font-semibold">' + new Date().toLocaleDateString('fr-FR') + '</span>')
        .replace(/{current_page}/g, '<span class="text-purple-600 font-semibold">Dashboard</span>');

    document.getElementById('previewContent').innerHTML = preview;
    document.getElementById('previewModal').classList.remove('hidden');
    document.getElementById('previewModal').classList.add('flex');
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
    document.getElementById('createToolModal').classList.remove('hidden');
    document.getElementById('createToolModal').classList.add('flex');
    document.getElementById('toolDescription').focus();
}

function closeCreateToolModal() {
    document.getElementById('createToolModal').classList.add('hidden');
    document.getElementById('createToolModal').classList.remove('flex');
    document.getElementById('toolDescription').value = '';
}

function submitCreateTool() {
    const description = document.getElementById('toolDescription').value.trim();
    if (!description) {
        alert('Veuillez décrire le tool à créer');
        return;
    }

    const btn = document.getElementById('createToolBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Génération...';

    fetch('/stm/admin/settings/agent/tools/create', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ description: description })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            closeCreateToolModal();
            location.reload();
        } else {
            alert('❌ ' + (data.error || 'Erreur lors de la création'));
        }
    })
    .catch(err => {
        alert('❌ Erreur de connexion');
        console.error(err);
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-magic"></i> Générer le Tool';
    });
}

function toggleTool(toolId, active) {
    fetch('/stm/admin/settings/agent/tools/toggle', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: toolId, active: active })
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            alert('❌ ' + (data.error || 'Erreur'));
            // Remettre l'état précédent
            const checkbox = document.querySelector(`input[data-tool-id="${toolId}"]`);
            if (checkbox) checkbox.checked = !active;
        }
    })
    .catch(err => {
        console.error(err);
        const checkbox = document.querySelector(`input[data-tool-id="${toolId}"]`);
        if (checkbox) checkbox.checked = !active;
    });
}

function editTool(toolId) {
    alert('📝 Édition du tool #' + toolId + ' - À venir');
}

function deleteTool(toolId, toolName) {
    if (!confirm(`Supprimer le tool "${toolName}" ?`)) return;

    fetch('/stm/admin/settings/agent/tools/delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: toolId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('tool-' + toolId)?.remove();
        } else {
            alert('❌ ' + (data.error || 'Erreur'));
        }
    })
    .catch(err => {
        alert('❌ Erreur de connexion');
        console.error(err);
    });
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