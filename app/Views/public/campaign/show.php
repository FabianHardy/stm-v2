<?php
/**
 * Vue : Page d'identification client
 * 
 * Permet au client de s'identifier pour accéder à une campagne
 * 
 * @package STM
 * @created 18/11/2025
 * @modified 18/11/2025 - Redesign complet avec code couleur bleu clair
 */

// Récupérer l'UUID de la campagne depuis l'URL
$urlParts = explode('/', $_SERVER['REQUEST_URI']);
$uuidIndex = array_search('c', $urlParts);
$uuid = $uuidIndex !== false ? $urlParts[$uuidIndex + 1] : '';

// Récupérer les infos de la campagne
try {
    $db = \Core\Database::getInstance();
    $query = "SELECT * FROM campaigns WHERE uuid = :uuid";
    $campaignResult = $db->query($query, [':uuid' => $uuid]);
    $campaign = !empty($campaignResult) ? $campaignResult[0] : null;
} catch (\PDOException $e) {
    error_log("Erreur show: " . $e->getMessage());
    $campaign = null;
}

// Si pas de campagne, rediriger
if (!$campaign) {
    header('Location: /stm/');
    exit;
}

// Langue par défaut
$currentLanguage = $_SESSION['temp_language'] ?? 'fr';
?>
<!DOCTYPE html>
<html lang="<?= $currentLanguage ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $currentLanguage === 'fr' ? 'Identification' : 'Identificatie' ?> - <?= htmlspecialchars($campaign['name']) ?></title>
    
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
                    <?php
                    // Détecter le pays depuis les données de campagne si disponible
                    $showLanguageSwitch = true; // Par défaut on affiche (sera masqué si LU après identification)
                    ?>
                    <?php if ($showLanguageSwitch): ?>
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

        <!-- Bande bleue avec infos campagne -->
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-lg relative z-10" 
             style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);">
            <div class="container mx-auto px-4 py-8">
                <div class="max-w-2xl mx-auto text-center">
                    <h1 class="text-3xl md:text-4xl font-bold mb-2">
                        <?= htmlspecialchars($campaign['title_' . $currentLanguage]) ?>
                    </h1>
                    <?php if (!empty($campaign['description_' . $currentLanguage])): ?>
                        <p class="text-blue-100 text-lg">
                            <?= htmlspecialchars($campaign['description_' . $currentLanguage]) ?>
                        </p>
                    <?php endif; ?>
                    
                    <!-- Dates de la campagne -->
                    <div class="mt-4 flex flex-col sm:flex-row items-center justify-center gap-4 text-sm text-blue-100">
                        <div class="flex items-center">
                            <i class="far fa-calendar mr-2"></i>
                            <span>
                                <?= $currentLanguage === 'fr' ? 'Du' : 'Van' ?>
                                <?= (new DateTime($campaign['start_date']))->format('d/m/Y') ?>
                                <?= $currentLanguage === 'fr' ? 'au' : 'tot' ?>
                                <?= (new DateTime($campaign['end_date']))->format('d/m/Y') ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages d'erreur/succès -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="container mx-auto px-4 mt-6">
                <div class="max-w-md mx-auto">
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-lg shadow-md flex items-start">
                        <svg class="w-6 h-6 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span><?= htmlspecialchars($_SESSION['error']) ?></span>
                    </div>
                </div>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="container mx-auto px-4 mt-6">
                <div class="max-w-md mx-auto">
                    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-6 py-4 rounded-lg shadow-md flex items-start">
                        <svg class="w-6 h-6 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span><?= htmlspecialchars($_SESSION['success']) ?></span>
                    </div>
                </div>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Formulaire d'identification -->
        <div class="container mx-auto px-4 py-12">
            <div class="max-w-md mx-auto">
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-4">
                            <i class="fas fa-user text-2xl text-blue-600"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800">
                            <?= $currentLanguage === 'fr' ? 'Identification' : 'Identificatie' ?>
                        </h2>
                        <p class="text-gray-600 mt-2">
                            <?= $currentLanguage === 'fr' 
                                ? 'Veuillez vous identifier pour accéder au catalogue' 
                                : 'Gelieve u te identificeren om toegang te krijgen tot de catalogus' ?>
                        </p>
                    </div>

                    <form method="POST" action="/stm/c/<?= htmlspecialchars($uuid) ?>/authenticate" class="space-y-6">
                        <!-- Token CSRF -->
                        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <input type="hidden" name="language" id="language" value="<?= $currentLanguage ?>">

                        <!-- Numéro client -->
                        <div>
                            <label for="customer_number" class="block text-sm font-medium text-gray-700 mb-2">
                                <?= $currentLanguage === 'fr' ? 'Numéro de client' : 'Klantnummer' ?>
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="customer_number" 
                                   name="customer_number" 
                                   required
                                   autocomplete="off"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                   placeholder="<?= $currentLanguage === 'fr' ? 'Ex: 123456' : 'Vb: 123456' ?>">
                        </div>

                        <!-- Code postal -->
                        <div>
                            <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-2">
                                <?= $currentLanguage === 'fr' ? 'Code postal' : 'Postcode' ?>
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="postal_code" 
                                   name="postal_code" 
                                   required
                                   autocomplete="off"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                   placeholder="<?= $currentLanguage === 'fr' ? 'Ex: 1000' : 'Vb: 1000' ?>">
                        </div>

                        <!-- Bouton de connexion -->
                        <button type="submit" 
                                class="w-full bg-blue-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-blue-700 transition-colors shadow-md hover:shadow-lg">
                            <span class="flex items-center justify-center">
                                <i class="fas fa-sign-in-alt mr-2"></i>
                                <?= $currentLanguage === 'fr' ? 'Se connecter' : 'Aanmelden' ?>
                            </span>
                        </button>
                    </form>
                </div>

                <!-- Section aide -->
                <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <h3 class="font-semibold text-blue-900 mb-3 flex items-center">
                        <i class="fas fa-info-circle mr-2"></i>
                        <?= $currentLanguage === 'fr' ? 'Besoin d\'aide ?' : 'Hulp nodig?' ?>
                    </h3>
                    <div class="text-sm text-blue-800 space-y-2">
                        <p>
                            <?= $currentLanguage === 'fr' 
                                ? 'Si vous ne connaissez pas votre numéro de client ou votre code postal, veuillez contacter votre représentant commercial.' 
                                : 'Als u uw klantnummer of postcode niet kent, neem dan contact op met uw vertegenwoordiger.' ?>
                        </p>
                        <div class="mt-4 flex items-center">
                            <i class="fas fa-phone text-blue-600 mr-2"></i>
                            <span class="font-medium">+32 2 123 45 67</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-envelope text-blue-600 mr-2"></i>
                            <span class="font-medium">support@trendyfoods.com</span>
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
        document.getElementById('language').value = lang;
        
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