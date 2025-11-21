<?php
/**
 * Component Card
 * 
 * Carte réutilisable pour afficher des KPI, statistiques ou informations.
 * 
 * Paramètres :
 * - title (string) : Titre de la carte
 * - value (string|int) : Valeur principale
 * - icon (string) : Icône Font Awesome (ex: 'fa-users')
 * - iconColor (string) : Couleur de l'icône (ex: 'text-primary-600')
 * - iconBg (string) : Couleur de fond de l'icône (ex: 'bg-primary-100')
 * - trend (string|null) : Tendance (+12%, -5%, etc.)
 * - trendUp (bool) : true si tendance positive
 * - link (string|null) : Lien vers une page
 * - subtitle (string|null) : Sous-titre optionnel
 * 
 * @package STM
 * @version 2.0
 */

// Valeurs par défaut
$title = $title ?? 'Titre';
$value = $value ?? '0';
$icon = $icon ?? 'fa-chart-line';
$iconColor = $iconColor ?? 'text-primary-600';
$iconBg = $iconBg ?? 'bg-primary-100';
$trend = $trend ?? null;
$trendUp = $trendUp ?? true;
$link = $link ?? null;
$subtitle = $subtitle ?? null;

// Classes de tendance
$trendClass = $trendUp ? 'text-green-600' : 'text-red-600';
$trendIcon = $trendUp ? 'fa-arrow-up' : 'fa-arrow-down';
?>

<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
    
    <!-- Header -->
    <div class="flex items-start justify-between mb-4">
        
        <!-- Icône -->
        <div class="<?= $iconBg ?> rounded-lg p-3">
            <i class="fas <?= $icon ?> <?= $iconColor ?> text-2xl"></i>
        </div>
        
        <!-- Tendance -->
        <?php if ($trend): ?>
        <div class="flex items-center gap-1 px-2 py-1 rounded-full <?= $trendUp ? 'bg-green-100' : 'bg-red-100' ?>">
            <i class="fas <?= $trendIcon ?> <?= $trendClass ?> text-xs"></i>
            <span class="text-sm font-semibold <?= $trendClass ?>">
                <?= htmlspecialchars($trend) ?>
            </span>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Contenu -->
    <div class="space-y-1">
        
        <!-- Valeur principale -->
        <p class="text-3xl font-bold text-gray-900">
            <?= htmlspecialchars($value) ?>
        </p>
        
        <!-- Titre -->
        <p class="text-sm font-medium text-gray-600">
            <?= htmlspecialchars($title) ?>
        </p>
        
        <!-- Sous-titre optionnel -->
        <?php if ($subtitle): ?>
        <p class="text-xs text-gray-500 mt-2">
            <?= htmlspecialchars($subtitle) ?>
        </p>
        <?php endif; ?>
    </div>
    
    <!-- Lien optionnel -->
    <?php if ($link): ?>
    <div class="mt-4 pt-4 border-t border-gray-100">
        <a href="<?= htmlspecialchars($link) ?>" 
           class="flex items-center justify-between text-sm text-primary-600 hover:text-primary-700 font-medium group">
            <span>Voir détails</span>
            <i class="fas fa-arrow-right text-xs group-hover:translate-x-1 transition-transform"></i>
        </a>
    </div>
    <?php endif; ?>
    
</div>
