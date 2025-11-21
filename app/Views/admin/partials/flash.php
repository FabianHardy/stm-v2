<?php
/**
 * Messages Flash
 * 
 * Affichage des messages temporaires :
 * - success (vert)
 * - error (rouge)
 * - warning (orange)
 * - info (bleu)
 * 
 * Auto-dismiss après 5 secondes
 * 
 * @package STM
 * @version 2.0
 */

use Core\Session;

$flashTypes = ['success', 'error', 'warning', 'info'];
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
    'success' => [
        'icon' => 'fa-check-circle',
        'bgColor' => 'bg-green-50',
        'borderColor' => 'border-green-500',
        'textColor' => 'text-green-800',
        'iconColor' => 'text-green-500',
    ],
    'error' => [
        'icon' => 'fa-exclamation-circle',
        'bgColor' => 'bg-red-50',
        'borderColor' => 'border-red-500',
        'textColor' => 'text-red-800',
        'iconColor' => 'text-red-500',
    ],
    'warning' => [
        'icon' => 'fa-exclamation-triangle',
        'bgColor' => 'bg-yellow-50',
        'borderColor' => 'border-yellow-500',
        'textColor' => 'text-yellow-800',
        'iconColor' => 'text-yellow-500',
    ],
    'info' => [
        'icon' => 'fa-info-circle',
        'bgColor' => 'bg-blue-50',
        'borderColor' => 'border-blue-500',
        'textColor' => 'text-blue-800',
        'iconColor' => 'text-blue-500',
    ],
];
?>

<!-- Container des messages flash -->
<div class="space-y-3" x-data="{ dismissedAlerts: [] }">
    
    <?php foreach ($flashTypes as $type): ?>
        <?php if (Session::has("flash_$type")): ?>
            <?php 
                $message = Session::get("flash_$type");
                $config = $flashConfig[$type];
                $uniqueId = uniqid("flash_$type-");
            ?>
            
            <div x-show="!dismissedAlerts.includes('<?= $uniqueId ?>')"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 data-auto-dismiss
                 class="flex items-start gap-3 p-4 rounded-lg border-l-4 <?= $config['bgColor'] ?> <?= $config['borderColor'] ?> shadow-sm"
                 role="alert">
                
                <!-- Icône -->
                <div class="flex-shrink-0">
                    <i class="fas <?= $config['icon'] ?> <?= $config['iconColor'] ?> text-xl"></i>
                </div>
                
                <!-- Message -->
                <div class="flex-1 <?= $config['textColor'] ?>">
                    <p class="text-sm font-medium">
                        <?= htmlspecialchars($message) ?>
                    </p>
                </div>
                
                <!-- Bouton fermeture -->
                <button @click="dismissedAlerts.push('<?= $uniqueId ?>')"
                        type="button"
                        class="flex-shrink-0 p-1 <?= $config['textColor'] ?> hover:bg-black hover:bg-opacity-5 rounded transition-colors">
                    <span class="sr-only">Fermer</span>
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
            
        <?php endif; ?>
    <?php endforeach; ?>
    
</div>

<!-- Script pour auto-dismiss -->
<script>
    // Auto-dismiss après 5 secondes
    setTimeout(() => {
        const alerts = document.querySelectorAll('[data-auto-dismiss]');
        alerts.forEach(alert => {
            const closeBtn = alert.querySelector('button');
            if (closeBtn) closeBtn.click();
        });
    }, 5000);
</script>
