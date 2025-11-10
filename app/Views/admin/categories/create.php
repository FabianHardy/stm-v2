<?php
/**
 * Vue : Création d'une catégorie
 * 
 * Formulaire de création avec support upload d'icônes
 * 
 * @package STM
 * @version 1.1
 * @modified 11/11/2025 - Ajout upload d'icônes
 */

use Core\Session;

$title = 'Nouvelle catégorie';
$errors = $errors ?? [];
$old = $old ?? [];

ob_start();
?>

<!-- Header de page -->
<div class="mb-8">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Nouvelle catégorie</h1>
            <p class="mt-2 text-sm text-gray-600">
                Créer une nouvelle catégorie de produits
            </p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="/stm/admin/categories" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Retour à la liste
            </a>
        </div>
    </div>
</div>

<!-- Messages flash -->
<?php if (Session::hasFlash('error')): ?>
    <div class="mb-6 bg-red-50 border border-red-200 text-red-800 rounded-lg p-4">
        <div class="flex">
            <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="ml-3 text-sm font-medium"><?= htmlspecialchars(Session::getFlash('error')) ?></p>
        </div>
    </div>
<?php endif; ?>

<!-- Formulaire -->
<div class="bg-white shadow rounded-lg">
    <form method="POST" action="/stm/admin/categories" enctype="multipart/form-data" class="divide-y divide-gray-200">
        
        <!-- Token CSRF -->
        <input type="hidden" name="_token" value="<?= Session::get('csrf_token') ?>">
        
        <!-- Section 1 : Informations de base -->
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Informations de base</h3>
            
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                
                <!-- Code -->
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700">
                        Code unique <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="code" 
                           id="code" 
                           value="<?= htmlspecialchars($old['code'] ?? '') ?>"
                           placeholder="Ex: FBOAALC"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 <?= isset($errors['code']) ? 'border-red-300' : '' ?>"
                           required>
                    <?php if (isset($errors['code'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['code'][0]) ?></p>
                    <?php endif; ?>
                    <p class="mt-1 text-xs text-gray-500">Sera converti en majuscules automatiquement</p>
                </div>

                <!-- Ordre d'affichage -->
                <div>
                    <label for="display_order" class="block text-sm font-medium text-gray-700">
                        Ordre d'affichage
                    </label>
                    <input type="number" 
                           name="display_order" 
                           id="display_order" 
                           value="<?= htmlspecialchars($old['display_order'] ?? 0) ?>"
                           min="0"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                    <p class="mt-1 text-xs text-gray-500">0 = en premier</p>
                </div>
                
            </div>
        </div>

        <!-- Section 2 : Noms multilingues -->
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Noms multilingues</h3>
            
            <div class="space-y-4">
                
                <!-- Nom français -->
                <div>
                    <label for="name_fr" class="block text-sm font-medium text-gray-700">
                        Nom français <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="name_fr" 
                           id="name_fr" 
                           value="<?= htmlspecialchars($old['name_fr'] ?? '') ?>"
                           placeholder="Ex: Boissons alcoolisées"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 <?= isset($errors['name_fr']) ? 'border-red-300' : '' ?>"
                           required>
                    <?php if (isset($errors['name_fr'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['name_fr'][0]) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Nom néerlandais -->
                <div>
                    <label for="name_nl" class="block text-sm font-medium text-gray-700">
                        Nom néerlandais <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="name_nl" 
                           id="name_nl" 
                           value="<?= htmlspecialchars($old['name_nl'] ?? '') ?>"
                           placeholder="Ex: Alcoholische dranken"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 <?= isset($errors['name_nl']) ? 'border-red-300' : '' ?>"
                           required>
                    <?php if (isset($errors['name_nl'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['name_nl'][0]) ?></p>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>

        <!-- Section 3 : Apparence -->
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Apparence</h3>
            
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                
                <!-- Couleur -->
                <div>
                    <label for="color" class="block text-sm font-medium text-gray-700 mb-2">
                        Couleur <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center space-x-3">
                        <input type="color" 
                               name="color" 
                               id="color" 
                               value="<?= htmlspecialchars($old['color'] ?? '#6B7280') ?>"
                               class="h-10 w-20 rounded border-gray-300 cursor-pointer">
                        <input type="text" 
                               id="color_hex" 
                               value="<?= htmlspecialchars($old['color'] ?? '#6B7280') ?>"
                               readonly
                               class="block w-24 rounded-md border-gray-300 bg-gray-50 text-sm">
                        <div id="color_preview" 
                             class="h-10 w-10 rounded border border-gray-300"
                             style="background-color: <?= htmlspecialchars($old['color'] ?? '#6B7280') ?>;"></div>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Couleur de fond pour l'affichage</p>
                </div>

                <!-- Statut actif -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Statut
                    </label>
                    <div class="flex items-center">
                        <input type="checkbox" 
                               name="is_active" 
                               id="is_active" 
                               value="1"
                               <?= isset($old['is_active']) && $old['is_active'] ? 'checked' : 'checked' ?>
                               class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                        <label for="is_active" class="ml-2 text-sm text-gray-700">
                            Activer cette catégorie
                        </label>
                    </div>
                </div>
                
            </div>
        </div>

        <!-- Section 4 : Icône (UPLOAD) -->
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Icône de la catégorie</h3>
            
            <div class="space-y-4">
                
                <!-- Choix : Upload OU URL -->
                <div x-data="{ uploadType: 'file' }">
                    
                    <!-- Tabs -->
                    <div class="border-b border-gray-200 mb-4">
                        <nav class="-mb-px flex space-x-8">
                            <button type="button"
                                    @click="uploadType = 'file'"
                                    :class="uploadType === 'file' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                📁 Upload de fichier
                            </button>
                            <button type="button"
                                    @click="uploadType = 'url'"
                                    :class="uploadType === 'url' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                🔗 URL d'icône
                            </button>
                        </nav>
                    </div>

                    <!-- Tab 1 : Upload de fichier -->
                    <div x-show="uploadType === 'file'" class="space-y-3">
                        <label for="icon" class="block text-sm font-medium text-gray-700">
                            Choisir une icône
                        </label>
                        <input type="file" 
                               name="icon" 
                               id="icon" 
                               accept=".svg,.png,.jpg,.jpeg,.webp"
                               class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none">
                        <p class="text-xs text-gray-500">
                            Formats acceptés : SVG, PNG, JPG, WEBP (max 2MB)
                        </p>
                        
                        <!-- Aperçu de l'icône uploadée -->
                        <div id="icon_preview" class="hidden mt-4">
                            <p class="text-sm font-medium text-gray-700 mb-2">Aperçu :</p>
                            <div class="flex items-center space-x-3">
                                <img id="icon_preview_img" src="" alt="Aperçu" class="h-16 w-16 object-contain border border-gray-300 rounded p-2">
                                <button type="button" 
                                        onclick="document.getElementById('icon').value = ''; document.getElementById('icon_preview').classList.add('hidden');"
                                        class="text-red-600 hover:text-red-800 text-sm">
                                    Supprimer
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 2 : URL d'icône -->
                    <div x-show="uploadType === 'url'">
                        <label for="icon_path" class="block text-sm font-medium text-gray-700">
                            URL de l'icône
                        </label>
                        <input type="text" 
                               name="icon_path" 
                               id="icon_path" 
                               value="<?= htmlspecialchars($old['icon_path'] ?? '') ?>"
                               placeholder="/assets/images/categories/exemple.svg"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                        <p class="mt-1 text-xs text-gray-500">
                            Chemin relatif ou URL complète vers l'icône
                        </p>
                    </div>
                    
                </div>
                
            </div>
        </div>

        <!-- Boutons d'action -->
        <div class="px-6 py-4 bg-gray-50 flex items-center justify-end space-x-3">
            <a href="/stm/admin/categories" 
               class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                Annuler
            </a>
            <button type="submit" 
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700">
                Créer la catégorie
            </button>
        </div>
        
    </form>
</div>

<!-- Script pour le sélecteur de couleur -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const colorPicker = document.getElementById('color');
    const colorHex = document.getElementById('color_hex');
    const colorPreview = document.getElementById('color_preview');
    
    // Mettre à jour l'affichage quand on change la couleur
    colorPicker.addEventListener('input', function() {
        const color = this.value;
        colorHex.value = color;
        colorPreview.style.backgroundColor = color;
    });
    
    // Aperçu de l'icône uploadée
    const iconInput = document.getElementById('icon');
    const iconPreview = document.getElementById('icon_preview');
    const iconPreviewImg = document.getElementById('icon_preview_img');
    
    iconInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                iconPreviewImg.src = e.target.result;
                iconPreview.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }
    });
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
?>