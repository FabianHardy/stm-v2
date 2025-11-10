<?php
/**
 * Component Pagination
 * 
 * Pagination réutilisable pour les listes.
 * 
 * Paramètres :
 * - currentPage (int) : Page actuelle
 * - totalPages (int) : Nombre total de pages
 * - baseUrl (string) : URL de base (ex: '/admin/campaigns')
 * - showInfo (bool) : Afficher "Page X sur Y"
 * - maxLinks (int) : Nombre max de liens visibles
 * 
 * @package STM
 * @version 2.0
 */

// Valeurs par défaut
$currentPage = $currentPage ?? 1;
$totalPages = $totalPages ?? 1;
$baseUrl = $baseUrl ?? '#';
$showInfo = $showInfo ?? true;
$maxLinks = $maxLinks ?? 7;

// Calcul de la plage de pages à afficher
$startPage = max(1, $currentPage - floor($maxLinks / 2));
$endPage = min($totalPages, $startPage + $maxLinks - 1);

if ($endPage - $startPage < $maxLinks - 1) {
    $startPage = max(1, $endPage - $maxLinks + 1);
}

/**
 * Génère l'URL d'une page
 */
function getPageUrl(string $baseUrl, int $page): string {
    $separator = str_contains($baseUrl, '?') ? '&' : '?';
    return $baseUrl . $separator . 'page=' . $page;
}
?>

<?php if ($totalPages > 1): ?>
<nav class="flex items-center justify-between border-t border-gray-200 px-4 py-3 sm:px-6 bg-white rounded-lg">
    
    <!-- Info (mobile) -->
    <?php if ($showInfo): ?>
    <div class="flex flex-1 justify-between sm:hidden">
        <p class="text-sm text-gray-700">
            Page <span class="font-medium"><?= $currentPage ?></span> sur <span class="font-medium"><?= $totalPages ?></span>
        </p>
    </div>
    <?php endif; ?>
    
    <!-- Navigation -->
    <div class="flex flex-1 items-center justify-between">
        
        <!-- Bouton Précédent -->
        <div>
            <?php if ($currentPage > 1): ?>
            <a href="<?= getPageUrl($baseUrl, $currentPage - 1) ?>"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-chevron-left text-xs"></i>
                <span class="hidden sm:inline">Précédent</span>
            </a>
            <?php else: ?>
            <span class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-200 rounded-lg cursor-not-allowed">
                <i class="fas fa-chevron-left text-xs"></i>
                <span class="hidden sm:inline">Précédent</span>
            </span>
            <?php endif; ?>
        </div>
        
        <!-- Numéros de pages (desktop) -->
        <div class="hidden md:flex items-center gap-1">
            
            <?php if ($startPage > 1): ?>
            <a href="<?= getPageUrl($baseUrl, 1) ?>"
               class="inline-flex items-center justify-center w-10 h-10 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                1
            </a>
            <?php if ($startPage > 2): ?>
            <span class="px-2 text-gray-500">...</span>
            <?php endif; ?>
            <?php endif; ?>
            
            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                <?php if ($i === $currentPage): ?>
                <span class="inline-flex items-center justify-center w-10 h-10 text-sm font-semibold text-white bg-primary-600 border border-primary-600 rounded-lg">
                    <?= $i ?>
                </span>
                <?php else: ?>
                <a href="<?= getPageUrl($baseUrl, $i) ?>"
                   class="inline-flex items-center justify-center w-10 h-10 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <?= $i ?>
                </a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($endPage < $totalPages): ?>
            <?php if ($endPage < $totalPages - 1): ?>
            <span class="px-2 text-gray-500">...</span>
            <?php endif; ?>
            <a href="<?= getPageUrl($baseUrl, $totalPages) ?>"
               class="inline-flex items-center justify-center w-10 h-10 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <?= $totalPages ?>
            </a>
            <?php endif; ?>
            
        </div>
        
        <!-- Bouton Suivant -->
        <div>
            <?php if ($currentPage < $totalPages): ?>
            <a href="<?= getPageUrl($baseUrl, $currentPage + 1) ?>"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <span class="hidden sm:inline">Suivant</span>
                <i class="fas fa-chevron-right text-xs"></i>
            </a>
            <?php else: ?>
            <span class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-200 rounded-lg cursor-not-allowed">
                <span class="hidden sm:inline">Suivant</span>
                <i class="fas fa-chevron-right text-xs"></i>
            </span>
            <?php endif; ?>
        </div>
        
    </div>
</nav>
<?php endif; ?>
