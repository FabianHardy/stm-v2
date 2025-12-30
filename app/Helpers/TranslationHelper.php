<?php
/**
 * Helper : TranslationHelper
 * 
 * Fonctions globales pour accéder aux traductions
 * À inclure dans le bootstrap ou autoload
 * 
 * @package    App\Helpers
 * @author     Fabian Hardy
 * @version    1.0.0
 * @created    2025/12/30
 */

use App\Models\Translation;

/**
 * Récupérer une traduction
 * 
 * @param string $key Clé de traduction (ex: 'checkout.title')
 * @param string $lang Langue (fr/nl), défaut: fr
 * @param array $params Paramètres de remplacement (ex: ['year' => 2025])
 * @return string Texte traduit ou clé si non trouvée
 * 
 * @example
 * // Simple
 * trans('checkout.title', 'fr')
 * // => "Validation de votre commande"
 * 
 * // Avec paramètres
 * trans('common.copyright', 'fr', ['year' => 2025])
 * // => "© 2025 Trendy Foods. Tous droits réservés."
 * 
 * // Avec liens dynamiques
 * trans('checkout.cgv_1', 'fr', ['link_cgu' => '/stm/c/abc123/page/cgu', 'link_privacy' => '/stm/c/abc123/page/confidentialite'])
 */
function trans(string $key, string $lang = 'fr', array $params = []): string
{
    static $model = null;
    
    if ($model === null) {
        $model = new Translation();
    }
    
    return $model->get($key, $lang, $params);
}

/**
 * Récupérer une traduction avec échappement HTML
 * 
 * @param string $key
 * @param string $lang
 * @param array $params
 * @return string
 */
function trans_e(string $key, string $lang = 'fr', array $params = []): string
{
    return htmlspecialchars(trans($key, $lang, $params), ENT_QUOTES, 'UTF-8');
}

/**
 * Récupérer une traduction HTML (sans échappement)
 * Utiliser uniquement pour les traductions marquées is_html = 1
 * 
 * @param string $key
 * @param string $lang
 * @param array $params
 * @return string
 */
function trans_html(string $key, string $lang = 'fr', array $params = []): string
{
    return trans($key, $lang, $params);
}

/**
 * Vérifier si une clé de traduction existe
 * 
 * @param string $key
 * @return bool
 */
function trans_exists(string $key): bool
{
    static $model = null;
    
    if ($model === null) {
        $model = new Translation();
    }
    
    $translation = $model->findByKey($key);
    return $translation !== null;
}

/**
 * Récupérer les paramètres de liens pour les CGV
 * 
 * @param string $campaignUuid UUID de la campagne
 * @return array Paramètres pour trans()
 */
function trans_links(string $campaignUuid): array
{
    $baseUrl = '/stm/c/' . htmlspecialchars($campaignUuid) . '/page/';
    
    return [
        'link_cgu' => $baseUrl . 'cgu',
        'link_privacy' => $baseUrl . 'confidentialite',
        'link_cgv' => $baseUrl . 'cgv',
        'link_mentions' => $baseUrl . 'mentions-legales',
        'year' => date('Y')
    ];
}

/**
 * Récupérer une traduction CGV avec liens résolus
 * 
 * @param string $key Clé CGV (checkout.cgv_1, checkout.cgv_2, etc.)
 * @param string $lang Langue
 * @param string $campaignUuid UUID campagne
 * @return string HTML avec liens
 */
function trans_cgv(string $key, string $lang, string $campaignUuid): string
{
    $params = trans_links($campaignUuid);
    return trans($key, $lang, $params);
}

/**
 * Récupérer toutes les traductions JS pour une catégorie
 * Utile pour passer les traductions à JavaScript
 * 
 * @param string $category Catégorie (ex: 'catalog')
 * @param string $lang Langue
 * @return array Traductions clé => valeur
 * 
 * @example
 * // PHP
 * $jsTranslations = trans_js('catalog', 'fr');
 * 
 * // Dans la vue
 * <script>const t = <?= json_encode($jsTranslations) ?>;</script>
 */
function trans_js(string $category, string $lang = 'fr'): array
{
    static $model = null;
    
    if ($model === null) {
        $model = new Translation();
    }
    
    $all = $model->getAll();
    $result = [];
    
    foreach ($all as $key => $translation) {
        // Filtrer par catégorie et préfixe js_
        if ($translation['category'] === $category && strpos($key, '.js_') !== false) {
            // Extraire le nom court (catalog.js_added => added)
            $shortKey = str_replace($category . '.js_', '', $key);
            $result[$shortKey] = ($lang === 'nl' && !empty($translation['text_nl'])) 
                ? $translation['text_nl'] 
                : $translation['text_fr'];
        }
    }
    
    return $result;
}

/**
 * Générer le script JS des traductions pour une page
 * 
 * @param string $category
 * @param string $lang
 * @return string Code JavaScript
 */
function trans_js_script(string $category, string $lang = 'fr'): string
{
    $translations = trans_js($category, $lang);
    $json = json_encode($translations, JSON_UNESCAPED_UNICODE);
    
    return "const t = {$json};";
}
