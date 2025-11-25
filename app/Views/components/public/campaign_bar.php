<?php
/**
 * Composant : Bande colorée (Campaign Bar)
 * 
 * Bande colorée sous le header avec titre/icône
 * Couleurs : blue (défaut), green (succès), red (erreur), orange (warning)
 * 
 * @package STM
 * @created 2025/11/21
 * 
 * Variables :
 * - $lang, $barTitle, $barSubtitle (optionnel)
 * - $barColor : 'blue', 'green', 'red', 'orange'
 * - $barIcon : SVG path (optionnel)
 * - $showDates, $showCountry : bool (optionnel)
 * - $campaign : pour dates/pays
 * - $backButton : ['url' => ..., 'label' => ...] (optionnel)
 */

$lang = $lang ?? 'fr';
$barColor = $barColor ?? 'blue';
$barTitle = $barTitle ?? '';
$barSubtitle = $barSubtitle ?? '';
$barIcon = $barIcon ?? '';
$showDates = $showDates ?? false;
$showCountry = $showCountry ?? false;

$colors = [
    'blue'   => 'linear-gradient(135deg, #277caeff 0%, #225a99ff 100%)',
    'green'  => 'linear-gradient(135deg, #2ecc71 0%, #27ae60 100%)',
    'red'    => 'linear-gradient(135deg, #e74c3c 0%, #c0392b 100%)',
    'orange' => 'linear-gradient(135deg, #f39c12 0%, #d68910 100%)',
];
$bg = $colors[$barColor] ?? $colors['blue'];
?>
<div class="text-white shadow-lg sticky top-[72px] z-30" style="background:<?= $bg ?>;">
    <div class="container mx-auto px-4 py-6">
        
        <?php if (isset($backButton)): ?>
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <a href="<?= htmlspecialchars($backButton['url'] ?? '#') ?>" class="flex items-center text-white hover:text-gray-200 transition order-2 sm:order-1">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <?= htmlspecialchars($backButton['label'] ?? ($lang === 'fr' ? 'Retour' : 'Terug')) ?>
            </a>
            <h2 class="text-2xl sm:text-3xl font-bold text-center order-1 sm:order-2"><?= htmlspecialchars($barTitle) ?></h2>
            <div class="hidden sm:block w-24 order-3"></div>
        </div>
        
        <?php else: ?>
        <div class="text-center">
            <?php if (!empty($barIcon)): ?>
            <div class="flex items-center justify-center mb-2">
                <div class="bg-white bg-opacity-20 rounded-full p-3 mr-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><?= $barIcon ?></svg>
                </div>
                <h2 class="text-3xl font-bold"><?= htmlspecialchars($barTitle) ?></h2>
            </div>
            <?php else: ?>
            <h2 class="text-3xl font-bold mb-2"><?= htmlspecialchars($barTitle) ?></h2>
            <?php endif; ?>
            
            <?php if (!empty($barSubtitle)): ?>
            <p class="text-blue-100 leading-relaxed max-w-2xl mx-auto"><?= nl2br(htmlspecialchars($barSubtitle)) ?></p>
            <?php endif; ?>
            
            <?php if ($showDates && isset($campaign)): ?>
            <div class="flex items-center justify-center gap-6 mt-4 text-sm text-blue-100">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-calendar-alt"></i>
                    <span><?= date('d/m/Y', strtotime($campaign['start_date'])) ?> - <?= date('d/m/Y', strtotime($campaign['end_date'])) ?></span>
                </div>
                <?php if ($showCountry): ?>
                <div class="flex items-center space-x-2">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><?php
                        $cl = ['BE' => $lang === 'fr' ? 'Belgique' : 'België', 'LU' => 'Luxembourg', 'BOTH' => $lang === 'fr' ? 'Belgique & Luxembourg' : 'België & Luxemburg'];
                        echo $cl[$campaign['country']] ?? $campaign['country'];
                    ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
    </div>
</div>
