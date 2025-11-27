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
        $pageTitle = $lang === 'fr' ? 'Accès non autorisé' : 'Toegang niet toegestaan';
        $iconSvg = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>';
        $iconColor = 'text-red-600 bg-red-100';
        $mainMessage = $lang === 'fr' ? 'Vous n\'avez pas accès à cette campagne promotionnelle.' : 'U heeft geen toegang tot deze promotiecampagne.';
        $detailMessage = $lang === 'fr' ? 'Cette campagne est réservée à certains clients spécifiques.' : 'Deze campagne is gereserveerd voor bepaalde specifieke klanten.';
        if ($customer) {
            $infoBox = '<div class="bg-red-50 border border-red-200 rounded-lg p-4 mt-6"><p class="text-sm text-red-800"><i class="fas fa-info-circle mr-2"></i>' . ($lang === 'fr' ? 'Votre compte client <strong>' . htmlspecialchars($customer['customer_number']) . '</strong> n\'est pas autorisé.' : 'Uw klantaccount <strong>' . htmlspecialchars($customer['customer_number']) . '</strong> is niet gemachtigd.') . '</p></div>';
        }
        $actionButton = $customer 
            ? '<a href="/stm/c/' . htmlspecialchars($uuid) . '" class="inline-flex items-center bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-700 transition shadow-md"><svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>' . ($lang === 'fr' ? 'Retour' : 'Terug') . '</a>'
            : '<a href="/stm/c/' . htmlspecialchars($uuid) . '" class="inline-flex items-center bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition shadow-md"><svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>' . ($lang === 'fr' ? 'Essayer un autre compte' : 'Probeer een ander account') . '</a>';
        break;

    case 'quotas_reached':
        $pageTitle = $lang === 'fr' ? 'Plus de promotions disponibles' : 'Geen promoties meer beschikbaar';
        $iconSvg = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>';
        $iconColor = 'text-red-600 bg-red-100';
        $mainMessage = $lang === 'fr' ? 'Il n\'y a plus de promotions disponibles.' : 'Er zijn geen promoties meer beschikbaar.';
        $detailMessage = $lang === 'fr' ? 'Tous les quotas sont atteints.' : 'Alle quota zijn bereikt.';
        $actionButton = '<a href="/stm/c/' . htmlspecialchars($uuid) . '/catalog" class="inline-flex items-center bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition shadow-md"><svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4"/></svg>' . ($lang === 'fr' ? 'Voir le catalogue' : 'Bekijk catalogus') . '</a>';
        break;

    case 'campaign_ended':
        $pageTitle = $lang === 'fr' ? 'Campagne terminée' : 'Campagne beëindigd';
        $iconSvg = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>';
        $iconColor = 'text-orange-600 bg-orange-100';
        $mainMessage = $lang === 'fr' ? 'Cette campagne est terminée.' : 'Deze campagne is beëindigd.';
        $detailMessage = $lang === 'fr' ? 'La période de commande est close.' : 'De bestelperiode is gesloten.';
        break;

    case 'campaign_not_started':
        $pageTitle = $lang === 'fr' ? 'Campagne pas encore active' : 'Campagne nog niet actief';
        $iconSvg = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>';
        $iconColor = 'text-blue-600 bg-blue-100';
        $mainMessage = $lang === 'fr' ? 'Cette campagne n\'a pas encore commencé.' : 'Deze campagne is nog niet begonnen.';
        $detailMessage = $lang === 'fr' ? 'Revenez à partir du ' . (isset($campaign) ? date('d/m/Y', strtotime($campaign['start_date'])) : '') : 'Kom terug vanaf ' . (isset($campaign) ? date('d/m/Y', strtotime($campaign['start_date'])) : '');
        break;

    default:
        $pageTitle = $lang === 'fr' ? 'Accès refusé' : 'Toegang geweigerd';
        $iconSvg = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>';
        $iconColor = 'text-gray-600 bg-gray-100';
        $mainMessage = $lang === 'fr' ? 'L\'accès à cette page n\'est pas autorisé.' : 'Toegang tot deze pagina is niet toegestaan.';
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
            $helpText = $lang === 'fr' 
                ? 'Si vous pensez qu\'il s\'agit d\'une erreur, contactez votre représentant.'
                : 'Als u denkt dat dit een vergissing is, neem dan contact op met uw vertegenwoordiger.';
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
