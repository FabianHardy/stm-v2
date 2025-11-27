<?php
/**
 * Composant : Boîte d'aide / Contact
 * 
 * Section d'aide avec numéros de téléphone et email selon le pays
 * 
 * @package STM
 * @created 2025/11/21
 * 
 * Variables :
 * - $lang : 'fr' ou 'nl'
 * - $country : 'BE' ou 'LU' (défaut: BE)
 * - $helpTitle : Titre personnalisé (optionnel)
 * - $helpText : Texte personnalisé (optionnel)
 */

$lang = $lang ?? 'fr';
$country = $country ?? 'BE';

$defaultTitle = $lang === 'fr' ? 'Besoin d\'aide ?' : 'Hulp nodig?';
$defaultText = $lang === 'fr' 
    ? 'Si vous ne connaissez pas votre numéro client ou si vous rencontrez des difficultés, contactez notre service client :' 
    : 'Als u uw klantnummer niet kent of problemen ondervindt, neem dan contact op met onze klantenservice:';

$helpTitle = $helpTitle ?? $defaultTitle;
$helpText = $helpText ?? $defaultText;

// Coordonnées selon le pays
$phone = $country === 'LU' ? '+352 35 71 791' : '+32 (0)87 321 888';
$email = $country === 'LU' ? 'info@lu.trendyfoods.com' : 'info@trendyfoods.com';
?>
<div class="bg-blue-50 border-2 border-blue-200 rounded-lg p-6">
    <div class="flex items-start space-x-3">
        <i class="fas fa-question-circle text-2xl text-blue-600 flex-shrink-0 mt-1"></i>
        <div>
            <h4 class="font-bold text-blue-900 mb-2"><?= htmlspecialchars($helpTitle) ?></h4>
            <p class="text-sm text-blue-800 leading-relaxed"><?= htmlspecialchars($helpText) ?></p>
            <div class="mt-3 space-y-1 text-sm text-blue-900">
                <p><i class="fas fa-phone mr-2"></i><strong><?= $phone ?></strong></p>
                <p><i class="fas fa-envelope mr-2"></i><strong><?= $email ?></strong></p>
            </div>
        </div>
    </div>
</div>
