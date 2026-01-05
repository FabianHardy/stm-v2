<?php
/**
 * Vue : Page d'accès refusé
 *
 * Affichée lorsqu'un client n'a pas accès à une campagne
 * Messages personnalisés selon la raison du refus
 *
 * @package STM
 * @created 2025/11/18
 * @modified 2025/11/21 - Adaptation au layout public centralisé
 * @modified 2025/12/30 - Migration vers système trans() centralisé
 * @modified 2025/01/05 - Ajout motif "no_products_authorized" pour API Trendy Foods
 */

// ========================================
// PRÉPARATION DES DONNÉES
// ========================================

// UUID depuis l'URL
$urlParts = explode('/', $_SERVER['REQUEST_URI']);
$uuidIndex = array_search('c', $urlParts);
$uuid = $uuidIndex !== false ? $urlParts[$uuidIndex + 1] : '';

// Récupérer infos campagne
try {
    $db = \Core\Database::getInstance();
    $query = "SELECT * FROM campaigns WHERE uuid = :uuid";
    $campaignResult = $db->query($query, [':uuid' => $uuid]);
    $campaign = !empty($campaignResult) ? $campaignResult[0] : null;
} catch (\PDOException $e) {
    error_log("Erreur access_denied: " . $e->getMessage());
    $campaign = null;
}

// Client connecté ?
$customer = $_SESSION['public_customer'] ?? null;

// Gestion du switch langue
$requestedLang = $_GET['lang'] ?? null;
if ($requestedLang && in_array($requestedLang, ['fr', 'nl'], true)) {
    if ($customer) {
        $_SESSION['public_customer']['language'] = $requestedLang;
    } else {
        $_SESSION['temp_language'] = $requestedLang;
    }
    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

$lang = $_SESSION['temp_language'] ?? $_SESSION['public_customer']['language'] ?? 'fr';

// Switch langue visible ?
$showLang = !$customer
    ? (!$campaign || in_array($campaign['country'] ?? 'BE', ['BE', 'BOTH']))
    : ($customer['country'] === 'BE');

// Récupérer les pages statiques pour le footer (utilisé par le layout)
if ($campaign) {
    $staticPageModel = new \App\Models\StaticPage();
    $footerPages = $staticPageModel->getFooterPages($campaign['id']);
} else {
    $footerPages = [];
}

// ========================================
// DÉFINIR LE MESSAGE SELON LA RAISON
// ========================================

$pageTitle = '';
$iconSvg = '';
$iconColor = '';
$mainMessage = '';
$detailMessage = '';
$infoBox = '';
$actionButton = '';

switch($reason) {
    case 'no_access':
        $pageTitle = trans('denied.no_access_title', $lang);
        $iconSvg = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>';
        $iconColor = 'text-red-600 bg-red-100';
        $mainMessage = trans('denied.no_access_message', $lang);
        $detailMessage = trans('denied.no_access_detail', $lang);
        if ($customer) {
            $infoBox = '<div class="bg-red-50 border border-red-200 rounded-lg p-4 mt-6"><p class="text-sm text-red-800"><i class="fas fa-info-circle mr-2"></i>' . trans('denied.account_not_authorized', $lang, ['account' => htmlspecialchars($customer['customer_number'])]) . '</p></div>';
        }
        $actionButton = $customer
            ? '<a href="/stm/c/' . htmlspecialchars($uuid) . '" class="inline-flex items-center bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-700 transition shadow-md"><svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>' . trans('common.back', $lang) . '</a>'
            : '<a href="/stm/c/' . htmlspecialchars($uuid) . '" class="inline-flex items-center bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition shadow-md"><svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>' . trans('denied.try_another', $lang) . '</a>';
        break;

    // ========================================
    // NOUVEAU MOTIF : Aucun produit autorisé (API Trendy Foods)
    // ========================================
    case 'no_products_authorized':
        $pageTitle = $lang === 'fr' ? 'Aucun produit disponible' : 'Geen producten beschikbaar';
        $iconSvg = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>';
        $iconColor = 'text-orange-600 bg-orange-100';
        $mainMessage = $lang === 'fr'
            ? 'Vous n\'avez actuellement pas accès aux produits de cette campagne.'
            : 'U heeft momenteel geen toegang tot de producten van deze campagne.';
        $detailMessage = $lang === 'fr'
            ? 'Les produits de cette promotion ne sont pas disponibles pour votre compte. Veuillez contacter votre représentant commercial pour plus d\'informations.'
            : 'De producten van deze promotie zijn niet beschikbaar voor uw account. Neem contact op met uw vertegenwoordiger voor meer informatie.';
        if ($customer) {
            $infoBox = '<div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mt-6"><p class="text-sm text-orange-800"><i class="fas fa-info-circle mr-2"></i>' . ($lang === 'fr'
                ? 'Compte client : ' . htmlspecialchars($customer['customer_number'])
                : 'Klantnummer: ' . htmlspecialchars($customer['customer_number'])) . '</p></div>';
        }
        $actionButton = '<a href="/stm/c/' . htmlspecialchars($uuid) . '" class="inline-flex items-center bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-700 transition shadow-md"><svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>' . ($lang === 'fr' ? 'Retour' : 'Terug') . '</a>';
        break;

    case 'quotas_reached':
        $pageTitle = trans('denied.quotas_title', $lang);
        $iconSvg = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>';
        $iconColor = 'text-red-600 bg-red-100';
        $mainMessage = trans('denied.quotas_message', $lang);
        $detailMessage = trans('denied.quotas_detail', $lang);
        $actionButton = '<a href="/stm/c/' . htmlspecialchars($uuid) . '/catalog" class="inline-flex items-center bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition shadow-md"><svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4"/></svg>' . trans('denied.view_catalog', $lang) . '</a>';
        break;

    case 'campaign_ended':
        $pageTitle = trans('denied.ended_title', $lang);
        $iconSvg = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>';
        $iconColor = 'text-orange-600 bg-orange-100';
        $mainMessage = trans('denied.ended_message', $lang);
        $detailMessage = trans('denied.ended_detail', $lang);
        break;

    case 'campaign_not_started':
        $pageTitle = trans('denied.not_started_title', $lang);
        $iconSvg = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>';
        $iconColor = 'text-blue-600 bg-blue-100';
        $mainMessage = trans('denied.not_started_message', $lang);
        $startDate = isset($campaign) ? date('d/m/Y', strtotime($campaign['start_date'])) : '';
        $detailMessage = trans('denied.not_started_detail', $lang, ['date' => $startDate]);
        break;

    default:
        $pageTitle = trans('denied.default_title', $lang);
        $iconSvg = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>';
        $iconColor = 'text-gray-600 bg-gray-100';
        $mainMessage = trans('denied.default_message', $lang);
        $detailMessage = '';
}

// Variables pour le layout
$title = $pageTitle;
$useAlpine = false;

// ========================================
// CONTENU DE LA PAGE
// ========================================
ob_start();
?>

<!-- Header -->
<?php include __DIR__ . '/../../components/public/header.php'; ?>

<!-- Bande de statut (rouge) -->
<?php
$barTitle = $pageTitle;
$barColor = 'red';
$barIcon = $iconSvg;
include __DIR__ . '/../../components/public/campaign_bar.php';
?>

<!-- Contenu principal -->
<div class="container mx-auto px-4 py-12">
    <div class="max-w-2xl mx-auto">

        <!-- Carte principale -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="p-8">
                <div class="flex justify-center mb-6">
                    <div class="inline-flex items-center justify-center w-20 h-20 <?= $iconColor ?> rounded-full">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><?= $iconSvg ?></svg>
                    </div>
                </div>

                <h2 class="text-2xl font-bold text-gray-800 text-center mb-4"><?= $pageTitle ?></h2>

                <div class="text-center text-gray-600 space-y-4 mb-6">
                    <p class="text-lg"><?= $mainMessage ?></p>
                    <?php if ($detailMessage): ?><p><?= $detailMessage ?></p><?php endif; ?>
                    <?= $infoBox ?>
                </div>

                <?php if (!empty($actionButton)): ?>
                <div class="flex justify-center"><?= $actionButton ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Section aide -->
        <div class="mt-8">
            <?php
            $country = $campaign['country'] ?? 'BE';
            $helpText = trans('denied.help_text', $lang);
            include __DIR__ . '/../../components/public/help_box.php';
            ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageScripts = '';
$pageStyles = '';
require __DIR__ . '/../../layouts/public.php';
?>