<?php
/**
 * Vue Admin : Édition d'une traduction
 * 
 * @package    App\Views\admin\translations
 * @author     Fabian Hardy
 * @version    1.0.0
 * @created    2025/12/30
 */

ob_start();
?>

<div class="max-w-4xl mx-auto space-y-6">
    <!-- En-tête avec navigation -->
    <div class="flex items-center justify-between">
        <div>
            <a href="/stm/admin/translations?category=<?= urlencode($translation['category']) ?>" 
               class="inline-flex items-center text-gray-600 hover:text-purple-600 transition mb-2">
                <i class="fas fa-arrow-left mr-2"></i>
                Retour à la liste
            </a>
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-edit mr-2 text-purple-600"></i>
                Modifier la traduction
            </h1>
        </div>
        
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
            <i class="fas fa-folder mr-2"></i>
            <?= htmlspecialchars($translation['category']) ?>
        </span>
    </div>
    
    <!-- Messages flash -->
    <?php if (isset($_SESSION['error'])): ?>
    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
            <p class="text-red-700"><?= htmlspecialchars($_SESSION['error']) ?></p>
        </div>
    </div>
    <?php unset($_SESSION['error']); endif; ?>
    
    <!-- Clé de traduction (lecture seule) -->
    <div class="bg-gray-50 rounded-lg p-4">
        <label class="block text-sm font-medium text-gray-500 mb-1">Clé de traduction</label>
        <code class="text-lg font-mono bg-white px-4 py-2 rounded border border-gray-200 block text-purple-700">
            <?= htmlspecialchars($translation['key']) ?>
        </code>
        <?php if (!empty($translation['description'])): ?>
        <p class="mt-2 text-sm text-gray-600">
            <i class="fas fa-info-circle mr-1"></i>
            <?= htmlspecialchars($translation['description']) ?>
        </p>
        <?php endif; ?>
    </div>
    
    <!-- Formulaire -->
    <form method="POST" action="/stm/admin/translations/<?= $translation['id'] ?>/update" class="space-y-6">
        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <!-- Tabs FR / NL -->
            <div x-data="{ tab: 'fr' }" class="divide-y divide-gray-200">
                <div class="flex border-b">
                    <button type="button" 
                            @click="tab = 'fr'"
                            :class="tab === 'fr' ? 'border-purple-500 text-purple-600 bg-purple-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="flex-1 py-4 px-6 text-center font-medium border-b-2 transition">
                        <span class="mr-2">🇫🇷</span> Français
                        <span class="text-red-500">*</span>
                    </button>
                    <button type="button" 
                            @click="tab = 'nl'"
                            :class="tab === 'nl' ? 'border-purple-500 text-purple-600 bg-purple-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="flex-1 py-4 px-6 text-center font-medium border-b-2 transition">
                        <span class="mr-2">🇳🇱</span> Néerlandais
                        <?php if (empty($translation['text_nl'])): ?>
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-orange-100 text-orange-700">
                            Manquant
                        </span>
                        <?php endif; ?>
                    </button>
                </div>
                
                <div class="p-6">
                    <!-- Texte FR -->
                    <div x-show="tab === 'fr'" x-transition>
                        <label for="text_fr" class="block text-sm font-medium text-gray-700 mb-2">
                            Texte français <span class="text-red-500">*</span>
                        </label>
                        <?php if ($translation['is_html']): ?>
                        <textarea name="text_fr" 
                                  id="text_fr" 
                                  rows="6" 
                                  required
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent font-mono text-sm"
                                  placeholder="Texte français..."><?= htmlspecialchars($translation['text_fr']) ?></textarea>
                        <p class="mt-2 text-sm text-orange-600">
                            <i class="fas fa-code mr-1"></i>
                            Ce champ contient du HTML. Les balises seront conservées.
                        </p>
                        <?php else: ?>
                        <textarea name="text_fr" 
                                  id="text_fr" 
                                  rows="4" 
                                  required
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                  placeholder="Texte français..."><?= htmlspecialchars($translation['text_fr']) ?></textarea>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Texte NL -->
                    <div x-show="tab === 'nl'" x-transition>
                        <label for="text_nl" class="block text-sm font-medium text-gray-700 mb-2">
                            Texte néerlandais
                        </label>
                        <?php if ($translation['is_html']): ?>
                        <textarea name="text_nl" 
                                  id="text_nl" 
                                  rows="6"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent font-mono text-sm"
                                  placeholder="Texte néerlandais..."><?= htmlspecialchars($translation['text_nl'] ?? '') ?></textarea>
                        <p class="mt-2 text-sm text-orange-600">
                            <i class="fas fa-code mr-1"></i>
                            Ce champ contient du HTML. Les balises seront conservées.
                        </p>
                        <?php else: ?>
                        <textarea name="text_nl" 
                                  id="text_nl" 
                                  rows="4"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                  placeholder="Texte néerlandais..."><?= htmlspecialchars($translation['text_nl'] ?? '') ?></textarea>
                        <?php endif; ?>
                        <p class="mt-2 text-sm text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            Si vide, le texte français sera utilisé par défaut.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Options supplémentaires -->
        <div class="bg-white rounded-lg shadow-sm p-6 space-y-4">
            <h3 class="font-semibold text-gray-800 mb-4">
                <i class="fas fa-cog mr-2 text-gray-400"></i>
                Options
            </h3>
            
            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                    Description (aide pour l'admin)
                </label>
                <input type="text" 
                       name="description" 
                       id="description" 
                       value="<?= htmlspecialchars($translation['description'] ?? '') ?>"
                       maxlength="255"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                       placeholder="Description courte pour identifier cette traduction...">
            </div>
            
            <!-- is_html -->
            <div>
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" 
                           name="is_html" 
                           value="1" 
                           <?= $translation['is_html'] ? 'checked' : '' ?>
                           class="w-5 h-5 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                    <span class="ml-3 text-gray-700">
                        <strong>Contient du HTML</strong>
                        <span class="text-sm text-gray-500 block">
                            Cochez si le texte contient des balises HTML (liens, mise en forme, etc.)
                        </span>
                    </span>
                </label>
            </div>
        </div>
        
        <!-- Variables disponibles -->
        <div class="bg-blue-50 rounded-lg p-4">
            <h4 class="font-semibold text-blue-800 mb-3">
                <i class="fas fa-code mr-2"></i>
                Variables disponibles
            </h4>
            <p class="text-sm text-blue-700 mb-3">
                Cliquez sur une variable pour la copier dans le presse-papiers :
            </p>
            <div class="flex flex-wrap gap-2">
                <?php 
                $variables = [
                    '{year}' => 'Année courante',
                    '{date}' => 'Date formatée',
                    '{customer}' => 'Numéro client',
                    '{start}' => 'Date début campagne',
                    '{end}' => 'Date fin campagne',
                    '{link_cgu}' => 'Lien vers CGU',
                    '{link_privacy}' => 'Lien vers Confidentialité',
                    '{link_cgv}' => 'Lien vers CGV',
                    '{link_mentions}' => 'Lien vers Mentions légales'
                ];
                foreach ($variables as $var => $desc): 
                ?>
                <button type="button"
                        onclick="copyToClipboard('<?= $var ?>')"
                        class="inline-flex items-center px-3 py-1.5 bg-white rounded-lg border border-blue-200 hover:bg-blue-100 transition text-sm"
                        title="<?= htmlspecialchars($desc) ?>">
                    <code class="text-blue-700 font-mono"><?= $var ?></code>
                    <i class="fas fa-copy ml-2 text-blue-400"></i>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Boutons d'action -->
        <div class="flex items-center justify-between pt-4">
            <a href="/stm/admin/translations?category=<?= urlencode($translation['category']) ?>" 
               class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                <i class="fas fa-times mr-2"></i>
                Annuler
            </a>
            
            <button type="submit" 
                    class="px-8 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition shadow-lg">
                <i class="fas fa-save mr-2"></i>
                Enregistrer les modifications
            </button>
        </div>
    </form>
    
    <!-- Aperçu -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">
            <i class="fas fa-eye mr-2 text-gray-400"></i>
            Aperçu du rendu
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- FR -->
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-xs font-medium text-gray-500 uppercase mb-2">🇫🇷 Français</p>
                <div class="prose prose-sm" id="preview-fr">
                    <?php if ($translation['is_html']): ?>
                    <?= $translation['text_fr'] ?>
                    <?php else: ?>
                    <?= nl2br(htmlspecialchars($translation['text_fr'])) ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- NL -->
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-xs font-medium text-gray-500 uppercase mb-2">🇳🇱 Néerlandais</p>
                <div class="prose prose-sm" id="preview-nl">
                    <?php if (!empty($translation['text_nl'])): ?>
                        <?php if ($translation['is_html']): ?>
                        <?= $translation['text_nl'] ?>
                        <?php else: ?>
                        <?= nl2br(htmlspecialchars($translation['text_nl'])) ?>
                        <?php endif; ?>
                    <?php else: ?>
                    <span class="text-gray-400 italic">Utilise le texte français</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        // Notification temporaire
        const notification = document.createElement('div');
        notification.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50';
        notification.innerHTML = '<i class="fas fa-check mr-2"></i>Copié : ' + text;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 2000);
    });
}
</script>

<?php
$content = ob_get_clean();
$title = 'Modifier traduction - ' . $translation['key'];
require __DIR__ . '/../../layouts/admin.php';
?>
