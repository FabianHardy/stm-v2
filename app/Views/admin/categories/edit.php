<?php
/**
 * Vue : Modification d'une catégorie
 * 
 * Formulaire de modification d'une catégorie existante.
 * 
 * @modified 11/11/2025 10:15 - Création initiale
 */

// 1. Capturer le contenu
ob_start();
?>

<!-- Breadcrumb -->
<nav class="flex mb-6" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-3">
        <li class="inline-flex items-center">
            <a href="/stm/admin/dashboard" class="text-gray-700 hover:text-indigo-600">
                <i class="fas fa-home mr-2"></i>Tableau de bord
            </a>
        </li>
        <li>
            <div class="flex items-center">
                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                <a href="/stm/admin/products/categories" class="text-gray-700 hover:text-indigo-600">Catégories</a>
            </div>
        </li>
        <li>
            <div class="flex items-center">
                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                <a href="/stm/admin/products/categories/<?= $category['id'] ?>" class="text-gray-700 hover:text-indigo-600">
                    <?= htmlspecialchars($category['name_fr']) ?>
                </a>
            </div>
        </li>
        <li>
            <div class="flex items-center">
                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                <span class="text-gray-500">Modifier</span>
            </div>
        </li>
    </ol>
</nav>

<!-- En-tête -->
<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900 mb-2">Modifier la catégorie</h1>
    <p class="text-gray-600"><?= htmlspecialchars($category['name_fr']) ?></p>
</div>

<!-- Messages d'erreur globaux -->
<?php if (!empty($errors)): ?>
<div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
    <div class="flex items-start">
        <i class="fas fa-exclamation-circle text-red-500 mr-3 mt-0.5"></i>
        <div>
            <p class="font-medium text-red-800 mb-2">Erreurs de validation :</p>
            <ul class="list-disc list-inside text-red-700 text-sm space-y-1">
                <?php foreach ($errors as $field => $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Formulaire -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <form method="POST" action="/stm/admin/products/categories/<?= $category['id'] ?>" id="categoryForm">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

        <div class="space-y-6">
            <!-- Code -->
            <div>
                <label for="code" class="block text-sm font-medium text-gray-700 mb-2">
                    Code <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       id="code" 
                       name="code" 
                       value="<?= htmlspecialchars($old['code']) ?>"
                       class="w-full px-4 py-2 border <?= isset($errors['code']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                       placeholder="Ex: FBOAALC"
                       maxlength="50"
                       required>
                <p class="mt-1 text-sm text-gray-500">
                    Code unique pour identifier la catégorie (max 50 caractères)
                </p>
                <?php if (isset($errors['code'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['code']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Noms (FR/NL) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name_fr" class="block text-sm font-medium text-gray-700 mb-2">
                        Nom (Français) <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="name_fr" 
                           name="name_fr" 
                           value="<?= htmlspecialchars($old['name_fr']) ?>"
                           class="w-full px-4 py-2 border <?= isset($errors['name_fr']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="Ex: Boissons alcoolisées"
                           maxlength="255"
                           required>
                    <?php if (isset($errors['name_fr'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['name_fr']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="name_nl" class="block text-sm font-medium text-gray-700 mb-2">
                        Nom (Néerlandais)
                    </label>
                    <input type="text" 
                           id="name_nl" 
                           name="name_nl" 
                           value="<?= htmlspecialchars($old['name_nl']) ?>"
                           class="w-full px-4 py-2 border <?= isset($errors['name_nl']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="Ex: Alcoholische dranken"
                           maxlength="255">
                    <?php if (isset($errors['name_nl'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['name_nl']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Couleur -->
            <div>
                <label for="color" class="block text-sm font-medium text-gray-700 mb-2">
                    Couleur <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center gap-4">
                    <input type="color" 
                           id="color" 
                           name="color" 
                           value="<?= htmlspecialchars($old['color']) ?>"
                           class="h-12 w-20 border border-gray-300 rounded cursor-pointer"
                           required>
                    <input type="text" 
                           id="color_hex" 
                           value="<?= htmlspecialchars($old['color']) ?>"
                           class="flex-1 px-4 py-2 border <?= isset($errors['color']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono"
                           placeholder="#6366F1"
                           pattern="^#[0-9A-Fa-f]{6}$"
                           maxlength="7"
                           readonly>
                    <div id="color_preview" class="h-12 w-12 rounded border border-gray-300" style="background-color: <?= htmlspecialchars($old['color']) ?>"></div>
                </div>
                <p class="mt-1 text-sm text-gray-500">
                    Couleur d'identification de la catégorie (format hexadécimal)
                </p>
                <?php if (isset($errors['color'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['color']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Icône -->
            <div>
                <label for="icon_path" class="block text-sm font-medium text-gray-700 mb-2">
                    Chemin de l'icône (optionnel)
                </label>
                <input type="text" 
                       id="icon_path" 
                       name="icon_path" 
                       value="<?= htmlspecialchars($old['icon_path']) ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                       placeholder="/assets/images/categories/alcohol.svg">
                <p class="mt-1 text-sm text-gray-500">
                    URL ou chemin vers l'icône SVG de la catégorie
                </p>
                <?php if (!empty($old['icon_path'])): ?>
                    <div class="mt-2 flex items-center gap-2">
                        <span class="text-sm text-gray-600">Aperçu :</span>
                        <img src="<?= htmlspecialchars($old['icon_path']) ?>" 
                             alt="Icône actuelle" 
                             class="h-8 w-8"
                             onerror="this.style.display='none'">
                    </div>
                <?php endif; ?>
            </div>

            <!-- Ordre d'affichage -->
            <div>
                <label for="display_order" class="block text-sm font-medium text-gray-700 mb-2">
                    Ordre d'affichage
                </label>
                <input type="number" 
                       id="display_order" 
                       name="display_order" 
                       value="<?= htmlspecialchars($old['display_order']) ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                       min="0"
                       step="1">
                <p class="mt-1 text-sm text-gray-500">
                    Les catégories sont triées par cet ordre (0 = premier)
                </p>
            </div>

            <!-- Statut actif -->
            <div class="flex items-center">
                <input type="checkbox" 
                       id="is_active" 
                       name="is_active" 
                       value="1"
                       <?= $old['is_active'] ? 'checked' : '' ?>
                       class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                <label for="is_active" class="ml-2 block text-sm text-gray-900">
                    Catégorie active (visible dans l'application)
                </label>
            </div>
        </div>

        <!-- Boutons d'action -->
        <div class="mt-8 flex justify-between items-center pt-6 border-t border-gray-200">
            <div>
                <form method="POST" action="/stm/admin/products/categories/<?= $category['id'] ?>/delete" 
                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?')" 
                      class="inline">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <button type="submit" 
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                        <i class="fas fa-trash mr-2"></i>Supprimer
                    </button>
                </form>
            </div>
            
            <div class="flex gap-4">
                <a href="/stm/admin/products/categories/<?= $category['id'] ?>" 
                   class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                    <i class="fas fa-times mr-2"></i>Annuler
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                    <i class="fas fa-save mr-2"></i>Enregistrer les modifications
                </button>
            </div>
        </div>
    </form>
</div>

<?php
// 2. Variables pour le layout
$content = ob_get_clean();
$title = 'Modifier ' . htmlspecialchars($category['name_fr']) . ' - STM';

// 3. Scripts JS pour le sélecteur de couleur
$pageScripts = "
<script>
    // Synchroniser le color picker avec l'input texte
    const colorInput = document.getElementById('color');
    const colorHex = document.getElementById('color_hex');
    const colorPreview = document.getElementById('color_preview');
    
    colorInput.addEventListener('input', function() {
        const color = this.value.toUpperCase();
        colorHex.value = color;
        colorPreview.style.backgroundColor = color;
    });
    
    colorHex.addEventListener('input', function() {
        const color = this.value.toUpperCase();
        if (/^#[0-9A-F]{6}$/i.test(color)) {
            colorInput.value = color;
            colorPreview.style.backgroundColor = color;
        }
    });
</script>
";

// 4. Inclure le layout (2 niveaux à remonter depuis categories/)
require __DIR__ . '/../../layouts/admin.php';
?>
