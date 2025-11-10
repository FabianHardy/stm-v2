<?php
/**
 * Component Modal
 * 
 * Fenêtre modale réutilisable.
 * 
 * Paramètres :
 * - id (string) : ID unique de la modale
 * - title (string) : Titre de la modale
 * - content (string) : Contenu HTML de la modale
 * - size (string) : Taille ('sm', 'md', 'lg', 'xl')
 * - closeButton (bool) : Afficher le bouton fermeture
 * - footer (string|null) : HTML du footer (boutons)
 * 
 * Usage :
 * <!-- Bouton pour ouvrir la modale -->
 * <button onclick="openModal('myModal')">Ouvrir</button>
 * 
 * <!-- Modale -->
 * <?php include 'components/modal.php'; ?>
 * 
 * @package STM
 * @version 2.0
 */

// Valeurs par défaut
$id = $id ?? 'modal-' . uniqid();
$title = $title ?? 'Modal';
$content = $content ?? '';
$size = $size ?? 'md';
$closeButton = $closeButton ?? true;
$footer = $footer ?? null;

// Classes de taille
$sizeClasses = [
    'sm' => 'max-w-md',
    'md' => 'max-w-2xl',
    'lg' => 'max-w-4xl',
    'xl' => 'max-w-6xl',
];

$maxWidth = $sizeClasses[$size] ?? $sizeClasses['md'];
?>

<!-- Modal -->
<div id="<?= $id ?>"
     x-data="{ open: false }"
     x-show="open"
     @keydown.escape.window="open = false"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    
    <!-- Overlay -->
    <div x-show="open"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="open = false"
         class="fixed inset-0 bg-gray-900 bg-opacity-75 backdrop-blur-sm">
    </div>
    
    <!-- Container -->
    <div class="flex min-h-full items-center justify-center p-4">
        
        <!-- Modal content -->
        <div x-show="open"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-95"
             @click.stop
             class="relative w-full <?= $maxWidth ?> bg-white rounded-lg shadow-xl">
            
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">
                    <?= htmlspecialchars($title) ?>
                </h3>
                
                <?php if ($closeButton): ?>
                <button @click="open = false"
                        type="button"
                        class="p-1 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                    <span class="sr-only">Fermer</span>
                    <i class="fas fa-times text-xl"></i>
                </button>
                <?php endif; ?>
            </div>
            
            <!-- Body -->
            <div class="px-6 py-4 max-h-[70vh] overflow-y-auto">
                <?= $content ?>
            </div>
            
            <!-- Footer -->
            <?php if ($footer): ?>
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
                <?= $footer ?>
            </div>
            <?php endif; ?>
            
        </div>
    </div>
</div>

<!-- Scripts globaux pour gérer les modales -->
<script>
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.__x.$data.open = true;
        }
    }
    
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.__x.$data.open = false;
        }
    }
</script>
