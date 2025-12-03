<?php
/**
 * Messages Flash - Toast Notifications
 *
 * Affichage des messages temporaires en position fixe bas-droite :
 * - success (vert)
 * - error (rouge)
 * - warning (orange)
 * - info (bleu)
 *
 * Auto-dismiss après 5 secondes
 * Ne décale pas le contenu de la page
 *
 * @package STM
 * @version 2.1
 * @modified 03/12/2025 - Toast notifications en bas à droite
 */

use Core\Session;

$flashTypes = ["success", "error", "warning", "info"];
$hasFlash = false;

foreach ($flashTypes as $type) {
    if (Session::has("flash_$type")) {
        $hasFlash = true;
        break;
    }
}

if (!$hasFlash) {
    return;
}

// Configuration des types de messages
$flashConfig = [
    "success" => [
        "icon" => "fa-check-circle",
        "bgColor" => "bg-green-600",
        "textColor" => "text-white",
        "iconColor" => "text-green-100",
    ],
    "error" => [
        "icon" => "fa-exclamation-circle",
        "bgColor" => "bg-red-600",
        "textColor" => "text-white",
        "iconColor" => "text-red-100",
    ],
    "warning" => [
        "icon" => "fa-exclamation-triangle",
        "bgColor" => "bg-yellow-500",
        "textColor" => "text-white",
        "iconColor" => "text-yellow-100",
    ],
    "info" => [
        "icon" => "fa-info-circle",
        "bgColor" => "bg-blue-600",
        "textColor" => "text-white",
        "iconColor" => "text-blue-100",
    ],
];
?>

<!-- Container des toasts - Position fixe bas-droite -->
<div id="toast-container"
     class="fixed bottom-4 right-4 z-50 flex flex-col gap-3 max-w-sm"
     x-data="{ toasts: [] }"
     x-init="
        // Initialiser les toasts depuis PHP
        <?php foreach ($flashTypes as $type): ?>
            <?php if (Session::has("flash_$type")): ?>
                <?php
                $message = Session::get("flash_$type");
                unset($_SESSION["flash_$type"]);
                ?>
                toasts.push({
                    id: '<?= uniqid("toast_") ?>',
                    type: '<?= $type ?>',
                    message: '<?= addslashes(htmlspecialchars($message)) ?>',
                    visible: true
                });
            <?php endif; ?>
        <?php endforeach; ?>

        // Auto-dismiss après 5 secondes
        setTimeout(() => {
            toasts.forEach((toast, index) => {
                setTimeout(() => {
                    toast.visible = false;
                }, index * 200);
            });
        }, 5000);
     ">

    <template x-for="toast in toasts" :key="toast.id">
        <div x-show="toast.visible"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-x-full"
             x-transition:enter-end="opacity-100 transform translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform translate-x-0"
             x-transition:leave-end="opacity-0 transform translate-x-full"
             :class="{
                'bg-green-600': toast.type === 'success',
                'bg-red-600': toast.type === 'error',
                'bg-yellow-500': toast.type === 'warning',
                'bg-blue-600': toast.type === 'info'
             }"
             class="flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg text-white min-w-[280px]"
             role="alert">

            <!-- Icône -->
            <div class="flex-shrink-0">
                <i class="fas text-lg"
                   :class="{
                      'fa-check-circle': toast.type === 'success',
                      'fa-exclamation-circle': toast.type === 'error',
                      'fa-exclamation-triangle': toast.type === 'warning',
                      'fa-info-circle': toast.type === 'info'
                   }"></i>
            </div>

            <!-- Message -->
            <div class="flex-1 text-sm font-medium" x-text="toast.message"></div>

            <!-- Bouton fermeture -->
            <button @click="toast.visible = false"
                    type="button"
                    class="flex-shrink-0 p-1 hover:bg-white hover:bg-opacity-20 rounded transition-colors">
                <span class="sr-only">Fermer</span>
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
    </template>

</div>