<?php
/**
 * Vue : Page d'accès refusé
 * 
 * Affichée lorsqu'un client n'a pas accès à une campagne spécifique
 * 
 * @package STM
 * @created 18/11/2025
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

// Récupérer la langue depuis la session ou par défaut FR
$currentLanguage = $_SESSION['temp_language'] ?? $_SESSION['public_customer']['language'] ?? 'fr';

// Récupérer le client si connecté
$customer = $_SESSION['public_customer'] ?? null;
?>
<!DOCTYPE html>
<html lang="<?= $currentLanguage ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $currentLanguage === 'fr' ? 'Accès refusé' : 'Toegang geweigerd' ?> - <?= $campaign ? htmlspecialchars($campaign['name']) : 'STM' ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            position: relative;
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
            opacity: 0.6;
            pointer-events: none;
            z-index: 0;
        }

        /* Contenu principal au-dessus du fond */
        .content-wrapper {
            position: relative;
            z-index: 1;
        }
    </style>
</head>
<body class="bg-gray-50">

    <div class="content-wrapper">
        <!-- Header blanc avec logo -->
        <header class="bg-white shadow-sm sticky top-0 z-50">
            <div class="container mx-auto px-4 py-4">
                <div class="flex items-center justify-between">
                    <!-- Logo Trendy Foods -->
                    <div>
                        <img src="/stm/assets/images/logo.png" 
                             alt="Trendy Foods" 
                             class="h-12"
                             onerror="this.style.display='none'">
                    </div>

                    <!-- Switch langue FR/NL (visible uniquement pour BE) -->
                    <?php if (!$customer || $customer['country'] === 'BE'): ?>
                    <div class="flex bg-gray-100 rounded-lg p-1">
                        <button onclick="switchLanguage('fr')" 
                                class="px-4 py-2 rounded-md <?= $currentLanguage === 'fr' ? 'bg-white text-blue-600 font-semibold shadow-sm' : 'text-gray-600 hover:bg-white hover:shadow-sm' ?> transition">
                            FR
                        </button>
                        <button onclick="switchLanguage('nl')" 
                                class="px-4 py-2 rounded-md <?= $currentLanguage === 'nl' ? 'bg-white text-blue-600 font-semibold shadow-sm' : 'text-gray-600 hover:bg-white hover:shadow-sm' ?> transition">
                            NL
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- Bande bleue d'information -->
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-lg relative z-10" 
             style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);">
            <div class="container mx-auto px-4 py-6">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="flex items-center">
                        <div class="bg-white bg-opacity-20 rounded-full p-3 mr-4">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <div>
                            <?php if ($campaign): ?>
                                <h1 class="text-2xl font-bold"><?= htmlspecialchars($campaign['title_' . $currentLanguage]) ?></h1>
                            <?php else: ?>
                                <h1 class="text-2xl font-bold">
                                    <?= $currentLanguage === 'fr' ? 'Campagne promotionnelle' : 'Promotiecampagne' ?>
                                </h1>
                            <?php endif; ?>
                            <p class="text-blue-100 text-sm mt-1">
                                <?= $currentLanguage === 'fr' ? 'Accès restreint' : 'Beperkte toegang' ?>
                            </p>
                        </div>
                    </div>
                    <?php if ($customer): ?>
                    <div class="text-right">
                        <p class="text-blue-100 text-sm">
                            <?= $currentLanguage === 'fr' ? 'Client N°' : 'Klant Nr.' ?>
                        </p>
                        <p class="text-xl font-bold"><?= htmlspecialchars($customer['customer_number']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Contenu principal -->
        <div class="container mx-auto px-4 py-12">
            <div class="max-w-2xl mx-auto">
                
                <!-- Carte d'accès refusé -->
                <div class="bg-white rounded-lg shadow-lg p-8">
                    
                    <!-- Icône d'avertissement -->
                    <div class="text-center mb-6">
                        <div class="inline-flex items-center justify-center w-24 h-24 bg-blue-100 rounded-full mb-4">
                            <svg class="w-16 h-16 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        
                        <h2 class="text-3xl font-bold text-gray-800 mb-4">
                            <?= $currentLanguage === 'fr' ? 'Accès non autorisé' : 'Toegang niet toegestaan' ?>
                        </h2>
                    </div>

                    <!-- Message principal -->
                    <div class="text-center text-gray-600 space-y-4 mb-8">
                        <p class="text-lg">
                            <?= $currentLanguage === 'fr' 
                                ? 'Vous n\'avez pas accès à cette campagne promotionnelle.' 
                                : 'U heeft geen toegang tot deze promotiecampagne.' ?>
                        </p>
                        
                        <?php if ($customer): ?>
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6">
                                <p class="text-sm text-blue-800">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    <?= $currentLanguage === 'fr' 
                                        ? 'Votre compte client <strong>' . htmlspecialchars($customer['customer_number']) . '</strong> n\'est pas autorisé à participer à cette campagne.' 
                                        : 'Uw klantaccount <strong>' . htmlspecialchars($customer['customer_number']) . '</strong> is niet gemachtigd om deel te nemen aan deze campagne.' ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Raisons possibles -->
                    <div class="bg-gray-50 rounded-lg p-6 mb-8">
                        <h3 class="font-semibold text-gray-800 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <?= $currentLanguage === 'fr' ? 'Raisons possibles' : 'Mogelijke redenen' ?>
                        </h3>
                        <ul class="space-y-3 text-sm text-gray-700">
                            <li class="flex items-start">
                                <svg class="w-5 h-5 mr-2 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                                <?= $currentLanguage === 'fr' 
                                    ? 'La campagne est réservée à certains clients spécifiques' 
                                    : 'De campagne is gereserveerd voor bepaalde specifieke klanten' ?>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 mr-2 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                                <?= $currentLanguage === 'fr' 
                                    ? 'Votre compte n\'est pas inclus dans la liste des participants' 
                                    : 'Uw account is niet opgenomen in de deelnemerslijst' ?>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 mr-2 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                                <?= $currentLanguage === 'fr' 
                                    ? 'La campagne concerne une zone géographique différente' 
                                    : 'De campagne betreft een ander geografisch gebied' ?>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 mr-2 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                                <?= $currentLanguage === 'fr' 
                                    ? 'La campagne est terminée ou n\'a pas encore débuté' 
                                    : 'De campagne is afgelopen of nog niet begonnen' ?>
                            </li>
                        </ul>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <?php if ($customer): ?>
                            <!-- Bouton retour (si client connecté) -->
                            <a href="/stm/c/<?= htmlspecialchars($uuid) ?>" 
                               class="inline-flex items-center justify-center bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-700 transition-colors shadow-md hover:shadow-lg">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                </svg>
                                <?= $currentLanguage === 'fr' ? 'Retour' : 'Terug' ?>
                            </a>
                        <?php else: ?>
                            <!-- Bouton essayer avec un autre compte -->
                            <a href="/stm/c/<?= htmlspecialchars($uuid) ?>" 
                               class="inline-flex items-center justify-center bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors shadow-md hover:shadow-lg">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                </svg>
                                <?= $currentLanguage === 'fr' ? 'Essayer un autre compte' : 'Probeer een ander account' ?>
                            </a>
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
                                ? 'Si vous pensez qu\'il s\'agit d\'une erreur ou si vous souhaitez plus d\'informations sur cette campagne, veuillez contacter votre représentant commercial.' 
                                : 'Als u denkt dat dit een vergissing is of als u meer informatie wenst over deze campagne, neem dan contact op met uw vertegenwoordiger.' ?>
                        </p>
                        <div class="pt-3 border-t border-blue-300">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-phone text-blue-600 w-6 mr-2"></i>
                                <span class="font-medium">+32 2 123 45 67</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-envelope text-blue-600 w-6 mr-2"></i>
                                <span class="font-medium">support@trendyfoods.com</span>
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

    <!-- Script switch langue -->
    <script>
    function switchLanguage(lang) {
        // Stocker la langue dans la session temporaire via AJAX
        fetch('/stm/c/<?= htmlspecialchars($uuid) ?>/set-language', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ language: lang })
        }).then(() => {
            // Recharger la page pour appliquer la langue
            window.location.reload();
        });
    }
    </script>

</body>
</html>