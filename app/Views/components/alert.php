<?php
/**
 * Component Alert
 * 
 * Alerte réutilisable pour afficher des messages dans les pages.
 * 
 * Paramètres :
 * - type (string) : Type d'alerte ('success', 'error', 'warning', 'info')
 * - message (string) : Message à afficher
 * - title (string|null) : Titre optionnel
 * - dismissible (bool) : true si l'alerte peut être fermée
 * - icon (string|null) : Icône personnalisée (sinon auto)
 * 
 * @package STM
 * @version 2.0
 */

// Valeurs par défaut
$type = $type ?? 'info';
$message = $message ?? '';
$title = $title ?? null;
$dismissible = $dismissible ?? true;
$icon = $icon ?? null;

// Configuration par type
$alertConfig = [
    'success' => [
        'icon' => 'fa-check-circle',
        'bgColor' => 'bg-green-50',
        'borderColor' => 'border-green-500',
        'textColor' => 'text-green-800',
        'iconColor' => 'text-green-500',
        'titleColor' => 'text-green-900',
    ],
    'error' => [
        'icon' => 'fa-exclamation-circle',
        'bgColor' => 'bg-red-50',
        'borderColor' => 'border-red-500',
        'textColor' => 'text-red-800',
        'iconColor' => 'text-red-500',
        'titleColor' => 'text-red-900',
    ],
    'warning' => [
        'icon' => 'fa-exclamation-triangle',
        'bgColor' => 'bg-yellow-50',
        'borderColor' => 'border-yellow-500',
        'textColor' => 'text-yellow-800',
        'iconColor' => 'text-yellow-500',
        'titleColor' => 'text-yellow-900',
    ],
    'info' => [
        'icon' => 'fa-info-circle',
        'bgColor' => 'bg-blue-50',
        'borderColor' => 'border-blue-500',
        'textColor' => 'text-blue-800',
        'iconColor' => 'text-blue-500',
        'titleColor' => 'text-blue-900',
    ],
];

$config = $alertConfig[$type] ?? $alertConfig['info'];
$icon = $icon ?? $config['icon'];
?>

<div <?= $dismissible ? 'x-data="{ show: true }"' : '' ?>
     <?= $dismissible ? 'x-show="show"' : '' ?>
     <?= $dismissible ? 'x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"' : '' ?>
     class="flex items-start gap-3 p-4 rounded-lg border-l-4 <?= $config['bgColor'] ?> <?= $config['borderColor'] ?> shadow-sm"
     role="alert">
    
    <!-- Icône -->
    <div class="flex-shrink-0">
        <i class="fas <?= $icon ?> <?= $config['iconColor'] ?> text-xl"></i>
    </div>
    
    <!-- Contenu -->
    <div class="flex-1 <?= $config['textColor'] ?>">
        
        <!-- Titre optionnel -->
        <?php if ($title): ?>
        <h3 class="text-sm font-semibold <?= $config['titleColor'] ?> mb-1">
            <?= htmlspecialchars($title) ?>
        </h3>
        <?php endif; ?>
        
        <!-- Message -->
        <div class="text-sm">
            <?= nl2br(htmlspecialchars($message)) ?>
        </div>
    </div>
    
    <!-- Bouton fermeture -->
    <?php if ($dismissible): ?>
    <button @click="show = false"
            type="button"
            class="flex-shrink-0 p-1 <?= $config['textColor'] ?> hover:bg-black hover:bg-opacity-5 rounded transition-colors">
        <span class="sr-only">Fermer</span>
        <i class="fas fa-times text-sm"></i>
    </button>
    <?php endif; ?>
    
</div>
