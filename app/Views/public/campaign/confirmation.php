<?php
/**
 * Vue : Page de confirmation après validation de commande
 *
 * @package STM
 * @created 2025/11/17
 * @modified 2025/11/21 - Adaptation au layout public centralisé
 * @modified 2025/12/30 - Migration vers système trans() centralisé
 * @modified 2026/01/06 - Sprint 14 : Badge mode représentant + redirection rep
 * @modified 2026/01/09 - Sprint 16 : Support mode prospect
 */

// ========================================
// PRÉPARATION DES DONNÉES
// ========================================

// UUID depuis l'URL (nécessaire pour la redirection)
$urlParts = explode('/', $_SERVER['REQUEST_URI']);
$uuidIndex = array_search('c', $urlParts);
$uuid = $uuidIndex !== false ? $urlParts[$uuidIndex + 1] : '';

// Sprint 16 : Détection mode prospect
$isProspectOrder = isset($_SESSION['prospect_id']) && !empty($_SESSION['prospect_id']);

if ($isProspectOrder) {
    // Mode prospect : utiliser les données prospect
    $customer = [
        'customer_number' => $_SESSION['prospect_number'] ?? '',
        'company_name' => $_SESSION['prospect_name'] ?? '',
        'email' => $_SESSION['prospect_email'] ?? '',
        'country' => $_SESSION['prospect_country'] ?? 'BE',
        'language' => $_SESSION['prospect_language'] ?? 'fr',
        'is_prospect' => true,
    ];
} elseif (isset($_SESSION['public_customer'])) {
    // Mode client normal
    $customer = $_SESSION['public_customer'];
} else {
    // Pas de session valide
    // Sprint 14 : Si cookie mode rep, rediriger vers SSO rep
    $repModeCookie = $_COOKIE['stm_rep_mode'] ?? null;
    if ($repModeCookie && $repModeCookie === $uuid) {
        header("Location: /stm/c/{$uuid}/rep");
    } else {
        header('Location: /stm/');
    }
    exit;
}

// Sprint 14 : Détection mode représentant
$isRepOrder = $customer['is_rep_order'] ?? false;
$repName = $customer['rep_name'] ?? '';
$repEmail = $customer['rep_email'] ?? '';

// Sprint 16 : URL catalogue selon le mode
$catalogUrl = $isProspectOrder ? "/stm/c/{$uuid}/prospect/catalog" : "/stm/c/{$uuid}/catalog";

// Gestion switch langue
$requestedLang = $_GET['lang'] ?? null;
if ($requestedLang && in_array($requestedLang, ['fr', 'nl'], true)) {
    if ($isProspectOrder) {
        $_SESSION['prospect_language'] = $requestedLang;
    } else {
        $_SESSION['public_customer']['language'] = $requestedLang;
    }
    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

$lang = $customer['language'];

// Récupérer campagne
try {
    $db = \Core\Database::getInstance();
    $campaignResult = $db->query("SELECT * FROM campaigns WHERE uuid = :uuid", [':uuid' => $uuid]);
    $campaign = !empty($campaignResult) ? $campaignResult[0] : null;
} catch (\PDOException $e) {
    $campaign = null;
}

if (!$campaign) {
    header('Location: /stm/');
    exit;
}

// Variables pour le layout
$title = trans('confirmation.title', $lang);
$useAlpine = false;

// Récupérer les pages statiques pour le footer (utilisé par le layout)
$staticPageModel = new \App\Models\StaticPage();
$footerPages = $staticPageModel->getFooterPages($campaign['id']);

// ========================================
// CONTENU DE LA PAGE
// ========================================
ob_start();
?>

<!-- Header -->
<?php include __DIR__ . '/../../components/public/header.php'; ?>

<!-- Bande confirmation (verte) -->
<?php
$barTitle = trans('confirmation.bar_title', $lang);
$barColor = 'green';
$barIcon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>';
include __DIR__ . '/../../components/public/campaign_bar.php';
?>

<!-- Contenu principal -->
<div class="container mx-auto px-4 py-12">
    <div class="max-w-3xl mx-auto">

        <!-- Carte de confirmation -->
        <div class="bg-white rounded-lg shadow-lg p-8 text-center">
            <div class="mb-6">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-green-100 rounded-full">
                    <svg class="w-16 h-16 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>

            <h2 class="text-3xl font-bold text-gray-800 mb-4">
                <?= trans('confirmation.thank_you', $lang) ?>
            </h2>

            <div class="text-gray-600 space-y-3 mb-8">
                <p class="text-lg">
                    <?= trans('confirmation.order_registered', $lang) ?>
                </p>
                <p>
                    <?= trans('confirmation.email_sent', $lang) ?>
                </p>

                <?php if ($campaign['deferred_delivery'] == 1 && !empty($campaign['delivery_date'])): ?>
                <div class="mt-6 p-6 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-center justify-center mb-2">
                        <svg class="w-6 h-6 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="font-semibold text-blue-900">
                            <?= trans('confirmation.delivery_from', $lang) ?>
                        </span>
                    </div>
                    <p class="text-2xl font-bold text-blue-700">
                        <?php
                        $deliveryDate = new DateTime($campaign['delivery_date']);
                        $months = $lang === 'fr'
                            ? ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre']
                            : ['januari','februari','maart','april','mei','juni','juli','augustus','september','oktober','november','december'];
                        echo $deliveryDate->format('d') . ' ' . $months[(int)$deliveryDate->format('m') - 1] . ' ' . $deliveryDate->format('Y');
                        ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>

            <div class="flex justify-center">
                <a href="<?= $catalogUrl ?>"
                   class="inline-flex items-center justify-center bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors shadow-md hover:shadow-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <?= trans('confirmation.back_to_catalog', $lang) ?>
                </a>
            </div>
        </div>

        <!-- Note informative -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <div class="flex items-start">
                <svg class="w-6 h-6 text-blue-600 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-sm text-blue-800">
                    <p class="font-semibold mb-1"><?= trans('confirmation.need_help', $lang) ?></p>
                    <p><?= trans('confirmation.contact_rep', $lang) ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageScripts = '';
$pageStyles = '';
require __DIR__ . '/../../layouts/public.php';
?>