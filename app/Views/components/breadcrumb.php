<?php
/**
 * Component Breadcrumb (Fil d'Ariane)
 * 
 * Navigation secondaire pour indiquer la position dans l'arborescence.
 * 
 * Paramètres :
 * - items (array) : Liste des items du breadcrumb
 *   [
 *     ['label' => 'Dashboard', 'url' => '/admin/dashboard'],
 *     ['label' => 'Campagnes', 'url' => '/admin/campaigns'],
 *     ['label' => 'Créer', 'url' => null], // dernier item sans lien
 *   ]
 * 
 * @package STM
 * @version 2.0
 */

// Valeurs par défaut
$items = $items ?? [];

if (empty($items)) {
    return;
}
?>

<nav class="flex items-center text-sm text-gray-600" aria-label="Breadcrumb">
    <ol class="flex items-center gap-2 flex-wrap">
        
        <!-- Icône home -->
        <li class="flex items-center">
            <a href="/admin/dashboard" 
               class="text-gray-500 hover:text-primary-600 transition-colors"
               title="Dashboard">
                <i class="fas fa-home"></i>
            </a>
        </li>
        
        <!-- Items du breadcrumb -->
        <?php foreach ($items as $index => $item): ?>
            <?php $isLast = ($index === count($items) - 1); ?>
            
            <li class="flex items-center gap-2">
                <!-- Séparateur -->
                <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                
                <!-- Item -->
                <?php if ($isLast || !isset($item['url'])): ?>
                    <!-- Dernier item (actif) -->
                    <span class="font-medium text-gray-900">
                        <?= htmlspecialchars($item['label']) ?>
                    </span>
                <?php else: ?>
                    <!-- Item avec lien -->
                    <a href="<?= htmlspecialchars($item['url']) ?>"
                       class="text-gray-600 hover:text-primary-600 transition-colors">
                        <?= htmlspecialchars($item['label']) ?>
                    </a>
                <?php endif; ?>
            </li>
            
        <?php endforeach; ?>
        
    </ol>
</nav>
