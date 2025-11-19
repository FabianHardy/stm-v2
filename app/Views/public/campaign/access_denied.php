<?php
/**
 * Vue : Page d'accès refusé
 * 
 * Affichée lorsqu'un client n'a pas accès à une campagne spécifique
 * Messages personnalisés selon la raison du refus
 * 
 * @package STM
 * @created 18/11/2025
 * @modified 19/11/2025 - Refonte complète avec messages spécifiques par cas + header harmonisé
 */

// Récupérer l'UUID de la campagne depuis l'URL
$urlParts = explode('/', $_SERVER['REQUEST_URI']);
$uuidIndex = array_search('c', $urlParts);
$uuid = $uuidIndex !== false ? $urlParts[$uuidIndex + 1] : '';

// Récupérer les infos de la campagne si disponibles
try {
    $db = \Core\Database::getInstance();
    $query = "SELECT * FROM campaigns WHERE uuid = :uuid";
    $campaignResult = $db->query($query, [':uuid' => $uuid]);
    $campaign = !empty($campaignResult) ? $campaignResult[0] : null;
} catch (\PDOException $e) {
    error_log("Erreur access_denied: " . $e->getMessage());
    $campaign = null;
}

// Récupérer le client si connecté
$customer = $_SESSION['public_customer'] ?? null;

// ========================================
// GESTION DU SWITCH LANGUE (FR/NL)
// ========================================
$requestedLang = $_GET['lang'] ?? null;

if ($requestedLang && in_array($requestedLang, ['fr', 'nl'], true)) {
    // Si client connecté, mettre à jour sa langue
    if ($customer) {
        $_SESSION['public_customer']['language'] = $requestedLang;
        $customer['language'] = $requestedLang;
    } else {
        // Sinon stocker dans session temporaire
        $_SESSION['temp_language'] = $requestedLang;
    }
    
    // Rediriger pour nettoyer l'URL
    $cleanUrl = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: {$cleanUrl}");
    exit;
}

// Récupérer la langue depuis la session ou par défaut FR
$currentLanguage = $_SESSION['temp_language'] ?? $_SESSION['public_customer']['language'] ?? 'fr';
// ========================================
// DÉTERMINER SI ON AFFICHE LE SWITCH LANGUE
// ========================================
// Règles :
// - Si campagne introuvable → BE par défaut (switch visible)
// - Si campagne BE ou BOTH → Switch visible (sauf si client LU)
// - Si campagne LU → Pas de switch (FR uniquement)

$showLanguageSwitch = false;

if (!$customer) {
    // Pas de client connecté
    // Si pas de campagne (introuvable) OU campagne BE/BOTH → Switch visible
    $showLanguageSwitch = !$campaign || in_array($campaign['country'] ?? 'BE', ['BE', 'BOTH']);
} else {
    // Client connecté → Vérifier son pays
    $showLanguageSwitch = $customer['country'] === 'BE';
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
    // ==========================================
    // CAS 1 : CLIENT PAS AUTORISÉ
    // ==========================================
    case 'no_access':
        $pageTitle = $currentLanguage === 'fr' ? 'Accès non autorisé' : 'Toegang niet toegestaan';
        $iconSvg = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>';
        $iconColor = 'text-red-600 bg-red-100';
        
        $mainMessage = $currentLanguage === 'fr' 
            ? 'Vous n\'avez pas accès à cette campagne promotionnelle.' 
            : 'U heeft geen toegang tot deze promotiecampagne.';
        
        $detailMessage = $currentLanguage === 'fr'
            ? 'Cette campagne est réservée à certains clients spécifiques.'
            : 'Deze campagne is gereserveerd voor bepaalde specifieke klanten.';
        
        // Info box avec numéro client si connecté
        if ($customer) {
            $infoBox = '<div class="bg-red-50 border border-red-200 rounded-lg p-4 mt-6">
                <p class="text-sm text-red-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    ' . ($currentLanguage === 'fr' 
                        ? 'Votre compte client <strong>' . htmlspecialchars($customer['customer_number']) . '</strong> n\'est pas autorisé à participer à cette campagne.' 
                        : 'Uw klantaccount <strong>' . htmlspecialchars($customer['customer_number']) . '</strong> is niet gemachtigd om deel te nemen aan deze campagne.') . '
                </p>
            </div>';
        }
        
        // Bouton action selon si client connecté ou non
        if ($customer) {
            $actionButton = '<a href="/stm/c/' . htmlspecialchars($uuid) . '" 
                class="inline-flex items-center justify-center bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-700 transition-colors shadow-md hover:shadow-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                ' . ($currentLanguage === 'fr' ? 'Retour' : 'Terug') . '
            </a>';
        } else {
            $actionButton = '<a href="/stm/c/' . htmlspecialchars($uuid) . '" 
                class="inline-flex items-center justify-center bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors shadow-md hover:shadow-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
                ' . ($currentLanguage === 'fr' ? 'Essayer un autre compte' : 'Probeer een ander account') . '
            </a>';
        }
        break;

    // ==========================================
    // CAS 2 : PLUS DE PROMOTIONS DISPONIBLES
    // ==========================================
    case 'quotas_reached':
        $pageTitle = $currentLanguage === 'fr' ? 'Plus de promotions disponibles' : 'Geen promoties meer beschikbaar';
        $iconSvg = '<path strokeLinecap="round" strokeLinejoin="round" d="M6 18 18 6M6 6l12 12" />';
  

        $iconColor = 'text-red-600 bg-red-100';
        
        $mainMessage = $currentLanguage === 'fr' 
            ? 'Il n\'y a plus de promotions disponibles pour cette campagne.' 
            : 'Er zijn geen promoties meer beschikbaar voor deze campagne.';
        
        $detailMessage = $currentLanguage === 'fr'
            ? 'Toutes les promotions ont été prises ou les quotas sont atteints.'
            : 'Alle promoties zijn genomen of de quota zijn bereikt.';
        
        $actionButton = '<a href="/stm/c/' . htmlspecialchars($uuid) . '/catalog" 
            class="inline-flex items-center justify-center bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors shadow-md hover:shadow-lg">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            ' . ($currentLanguage === 'fr' ? 'Retour au catalogue' : 'Terug naar catalogus') . '
        </a>';
        break;

    // ==========================================
    // CAS 3 : CAMPAGNE PAS ENCORE COMMENCÉE
    // ==========================================
    case 'upcoming':
        $pageTitle = $currentLanguage === 'fr' ? 'Cette campagne n\'a pas encore commencé' : 'Deze campagne is nog niet begonnen';
        $iconSvg = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>';
        $iconColor = 'text-blue-600 bg-blue-100';
        
        // Formater la date de début
        if ($campaign && !empty($campaign['start_date'])) {
            $startDate = new DateTime($campaign['start_date']);
            $monthsFr = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
            $monthsNl = ['januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december'];
            $day = $startDate->format('d');
            $monthIndex = (int)$startDate->format('m') - 1;
            $year = $startDate->format('Y');
            $monthName = $currentLanguage === 'fr' ? $monthsFr[$monthIndex] : $monthsNl[$monthIndex];
            $formattedDate = "$day $monthName $year";
            
            $mainMessage = $currentLanguage === 'fr' 
                ? 'La campagne débutera le <strong>' . $formattedDate . '</strong>.' 
                : 'De campagne begint op <strong>' . $formattedDate . '</strong>.';
        } else {
            $mainMessage = $currentLanguage === 'fr' 
                ? 'La campagne n\'a pas encore commencé.' 
                : 'De campagne is nog niet begonnen.';
        }
        
        $detailMessage = $currentLanguage === 'fr'
            ? 'Revenez à partir de cette date pour consulter les promotions disponibles.'
            : 'Kom terug vanaf deze datum om de beschikbare promoties te bekijken.';
        
        $actionButton = '<a href="/stm/c/' . htmlspecialchars($uuid) . '" 
            class="inline-flex items-center justify-center bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors shadow-md hover:shadow-lg">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            ' . ($currentLanguage === 'fr' ? 'J\'ai compris' : 'Ik begrijp het') . '
        </a>';
        break;

    // ==========================================
    // CAS 4 : CAMPAGNE TERMINÉE
    // ==========================================
    case 'ended':
        $pageTitle = $currentLanguage === 'fr' ? 'Cette campagne est terminée' : 'Deze campagne is afgelopen';
        $iconSvg = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>';
        $iconColor = 'text-gray-600 bg-gray-100';
        
        // Formater la date de fin
        if ($campaign && !empty($campaign['end_date'])) {
            $endDate = new DateTime($campaign['end_date']);
            $monthsFr = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
            $monthsNl = ['januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december'];
            $day = $endDate->format('d');
            $monthIndex = (int)$endDate->format('m') - 1;
            $year = $endDate->format('Y');
            $monthName = $currentLanguage === 'fr' ? $monthsFr[$monthIndex] : $monthsNl[$monthIndex];
            $formattedDate = "$day $monthName $year";
            
            $mainMessage = $currentLanguage === 'fr' 
                ? 'La campagne s\'est terminée le <strong>' . $formattedDate . '</strong>.' 
                : 'De campagne eindigde op <strong>' . $formattedDate . '</strong>.';
        } else {
            $mainMessage = $currentLanguage === 'fr' 
                ? 'La campagne est terminée.' 
                : 'De campagne is afgelopen.';
        }
        
        $detailMessage = $currentLanguage === 'fr'
            ? 'Les commandes ne sont plus acceptées pour cette campagne.'
            : 'Bestellingen worden niet meer geaccepteerd voor deze campagne.';
        
        $actionButton = '<a href="/stm/c/' . htmlspecialchars($uuid) . '" 
            class="inline-flex items-center justify-center bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-700 transition-colors shadow-md hover:shadow-lg">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            ' . ($currentLanguage === 'fr' ? 'Retour' : 'Terug') . '
        </a>';
        break;

    // ==========================================
    // CAS 5 : CAMPAGNE DÉSACTIVÉE
    // ==========================================
    case 'inactive':
        $pageTitle = $currentLanguage === 'fr' ? 'Campagne temporairement désactivée' : 'Campagne tijdelijk gedeactiveerd';
        $iconSvg = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>';
        $iconColor = 'text-orange-600 bg-orange-100';
        
        $mainMessage = $currentLanguage === 'fr' 
            ? 'Cette campagne n\'est pas accessible actuellement.' 
            : 'Deze campagne is momenteel niet toegankelijk.';
        
        $detailMessage = $currentLanguage === 'fr'
            ? 'La campagne a été temporairement désactivée.'
            : 'De campagne is tijdelijk gedeactiveerd.';
        
        $actionButton = '<a href="/stm/c/' . htmlspecialchars($uuid) . '" 
            class="inline-flex items-center justify-center bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-700 transition-colors shadow-md hover:shadow-lg">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            ' . ($currentLanguage === 'fr' ? 'Retour' : 'Terug') . '
        </a>';
        break;

    // ==========================================
    // CAS 6 : CAMPAGNE INTROUVABLE
    // ==========================================
    case 'campaign_not_found':
        $pageTitle = $currentLanguage === 'fr' ? 'Campagne introuvable' : 'Campagne niet gevonden';
        $iconSvg = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>';
        $iconColor = 'text-red-600 bg-red-100';
        
        $mainMessage = $currentLanguage === 'fr' 
            ? 'La campagne demandée est introuvable.' 
            : 'De gevraagde campagne is niet gevonden.';
        
        $detailMessage = $currentLanguage === 'fr'
            ? 'Le lien utilisé est peut-être incorrect ou la campagne a été supprimée.'
            : 'De gebruikte link is mogelijk onjuist of de campagne is verwijderd.';
        
        $actionButton = ''; // Pas de bouton pour ce cas
        break;

    // ==========================================
    // CAS 7 : ERREUR TECHNIQUE
    // ==========================================
    case 'error':
    default:
        $pageTitle = $currentLanguage === 'fr' ? 'Une erreur est survenue' : 'Er is een fout opgetreden';
        $iconSvg = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>';
        $iconColor = 'text-red-600 bg-red-100';
        
        $mainMessage = $currentLanguage === 'fr' 
            ? 'Une erreur technique est survenue.' 
            : 'Er is een technische fout opgetreden.';
        
        $detailMessage = $currentLanguage === 'fr'
            ? 'Veuillez réessayer dans quelques instants.'
            : 'Probeer het over enkele ogenblikken opnieuw.';
        
        $actionButton = ''; // Pas de bouton pour ce cas
        break;
}
?>
<!DOCTYPE html>
<html lang="<?= $currentLanguage ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - <?= $campaign ? htmlspecialchars($campaign['name']) : 'STM' ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* Structure flexbox pour footer en bas */
        html, body {
            height: 100%;
        }

        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .content-wrapper {
            flex: 1;
        }

        footer {
            margin-top: 0;
        }

        /* Fond Trendy Foods en bas à droite */
        body::before {
            content: '';
            position: fixed;
            bottom: 0;
            right: 0;
            width: 400px;
            height: 400px;
            background: url('/stm/assets/images/fond.png') no-repeat;
            background-size: contain;
            background-position: bottom right;
            opacity: 0.6;
            pointer-events: none;
            z-index: -1;
        }
    </style>
</head>
<body class="bg-gray-50">

    <div class="content-wrapper">
        <!-- Header blanc avec logo + infos campagne + client (harmonisé avec confirmation.php) -->
        <header class="bg-white shadow-md sticky top-0 z-40">
            <div class="container mx-auto px-4 py-4">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <!-- Logo Trendy Foods -->
                        <img src="/stm/assets/images/logo.png" 
                             alt="Trendy Foods" 
                             class="h-12"
                             onerror="this.style.display='none'">
                        <div>
                            <?php if ($campaign): ?>
                                <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($campaign['name']) ?></h1>
                                <?php if ($customer): ?>
                                    <p class="text-sm text-gray-600">
                                        <i class="fas fa-building mr-1"></i>
                                        <?= htmlspecialchars($customer['company_name']) ?>
                                        <span class="mx-2">•</span>
                                        <?= htmlspecialchars($customer['customer_number']) ?>
                                    </p>
                                <?php endif; ?>
                            <?php else: ?>
                                <h1 class="text-2xl font-bold text-gray-800">
                                    <?= $currentLanguage === 'fr' ? 'Campagne promotionnelle' : 'Promotiecampagne' ?>
                                </h1>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <!-- Switch langue FR/NL (visible uniquement pour BE) -->
                        <?php if ($showLanguageSwitch): ?>
                        <div class="hidden lg:flex bg-gray-100 rounded-lg p-1">
                            <button onclick="window.location.href='?lang=fr'" 
                                    class="px-4 py-2 rounded-md <?= $currentLanguage === 'fr' ? 'bg-white text-blue-600 font-semibold shadow-sm' : 'text-gray-600 hover:bg-white hover:shadow-sm' ?> transition">
                                FR
                            </button>
                            <button onclick="window.location.href='?lang=nl'" 
                                    class="px-4 py-2 rounded-md <?= $currentLanguage === 'nl' ? 'bg-white text-blue-600 font-semibold shadow-sm' : 'text-gray-600 hover:bg-white hover:shadow-sm' ?> transition">
                                NL
                            </button>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Déconnexion (si client connecté) -->
                        <?php if ($customer): ?>
                        <a href="/stm/c/<?= htmlspecialchars($uuid) ?>" 
                           class="hidden lg:block text-gray-600 hover:text-gray-800 transition">
                            <i class="fas fa-sign-out-alt mr-2"></i>
                            <?= $currentLanguage === 'fr' ? 'Déconnexion' : 'Afmelden' ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>

        <!-- Bande de statut avec icône + titre -->
        <div class="bg-gradient-to-r from-red-500 to-red-600 text-white shadow-lg sticky top-[72px] z-30" 
             style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);">
            <div class="container mx-auto px-4 py-6">
                <div class="flex items-center justify-center">
                    <div class="bg-white bg-opacity-20 rounded-full p-3 mr-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <?= $iconSvg ?>
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold">
                        <?= $pageTitle ?>
                    </h2>
                </div>
            </div>
        </div>

        <!-- Contenu principal -->
        <div class="container mx-auto px-4 py-12">
            <div class="max-w-2xl mx-auto">
                <!-- Carte principale -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="p-8">
                        <!-- Grande icône de statut -->
                        <div class="flex justify-center mb-6">
                            <div class="inline-flex items-center justify-center w-20 h-20 <?= $iconColor ?> rounded-full">
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <?= $iconSvg ?>
                                </svg>
                            </div>
                        </div>

                        <!-- Titre -->
                        <h2 class="text-2xl font-bold text-gray-800 text-center mb-4">
                            <?= $pageTitle ?>
                        </h2>

                        <!-- Message principal -->
                        <div class="text-center text-gray-600 space-y-4 mb-6">
                            <p class="text-lg">
                                <?= $mainMessage ?>
                            </p>
                            <p>
                                <?= $detailMessage ?>
                            </p>
                            
                            <?= $infoBox ?>
                        </div>

                        <!-- Actions -->
                        <?php if (!empty($actionButton)): ?>
                        <div class="flex justify-center">
                            <?= $actionButton ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Section aide / contact -->
                <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <h3 class="font-semibold text-blue-900 mb-3 flex items-center">
                        <i class="fas fa-life-ring mr-2"></i>
                        <?= $currentLanguage === 'fr' ? 'Besoin d\'aide ?' : 'Hulp nodig?' ?>
                    </h3>
                    <div class="text-sm text-blue-800 space-y-3">
                        <p>
                            <?= $currentLanguage === 'fr' 
                                ? 'Si vous pensez qu\'il s\'agit d\'une erreur ou si vous souhaitez plus d\'informations sur cette campagne promotionelle, veuillez contacter votre représentant.' 
                                : 'Als u denkt dat dit een vergissing is of als u meer informatie wenst over deze campagne, neem dan contact op met uw vertegenwoordiger.' ?>
                        </p>
                        <div class="pt-3 border-t border-blue-300">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-phone text-blue-600 w-6 mr-2"></i>
                               <?php if ($campaign['country'] === 'BE'): ?>
                                  <span class="font-medium">+32 (0)87 321 888</span>
                                <?php else: ?>
                                  <span class="font-medium">+352 35 71 791</span>
                                <?php endif; ?> 
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-envelope text-blue-600 w-6 mr-2"></i>
                                <?php if ($campaign['country'] === 'BE'): ?>
                                   <span class="font-medium">info@trendyfoods.com</span>
                                <?php else: ?>
                                    <span class="font-medium">info@lu.trendyfoods.com</span>
                                <?php endif; ?>  
                                
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-gray-300 py-6 mt-12 relative z-10">
        <div class="container mx-auto px-4 text-center">
            <p class="text-sm">
                © <?= date('Y') ?> Trendy Foods - 
                <?= $currentLanguage === 'fr' ? 'Tous droits réservés' : 'Alle rechten voorbehouden' ?>
            </p>
        </div>
    </footer>

</body>
</html>