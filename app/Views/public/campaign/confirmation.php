<?php
/**
 * Vue : Page de confirmation après validation de commande
 * 
 * Affiche le message de succès et les détails de la commande validée
 * 
 * @package STM
 * @created 17/11/2025
 * @modified 18/11/2025 - Redesign complet avec code couleur vert succès
 */

// Vérifier que l'utilisateur a bien une session client
if (!isset($_SESSION['public_customer'])) {
    header('Location: /stm/');
    exit;
}

$customer = $_SESSION['public_customer'];

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
    error_log("Erreur confirmation: " . $e->getMessage());
    $campaign = null;
}

// Si pas de campagne, rediriger
if (!$campaign) {
    header('Location: /stm/');
    exit;
}

// Récupérer l'UUID de la dernière commande si disponible
$orderUuid = $_SESSION['last_order_uuid'] ?? null;
?>
<!DOCTYPE html>
<html lang="<?= $customer['language'] ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $customer['language'] === 'fr' ? 'Commande validée' : 'Bestelling bevestigd' ?> - <?= htmlspecialchars($campaign['name']) ?></title>
    
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
                    <?php if ($customer['country'] === 'BE'): ?>
                    <div class="flex bg-gray-100 rounded-lg p-1">
                        <button onclick="window.location.href='?lang=fr'" 
                                class="px-4 py-2 rounded-md <?= $customer['language'] === 'fr' ? 'bg-white text-green-600 font-semibold shadow-sm' : 'text-gray-600 hover:bg-white hover:shadow-sm' ?> transition">
                            FR
                        </button>
                        <button onclick="window.location.href='?lang=nl'" 
                                class="px-4 py-2 rounded-md <?= $customer['language'] === 'nl' ? 'bg-white text-green-600 font-semibold shadow-sm' : 'text-gray-600 hover:bg-white hover:shadow-sm' ?> transition">
                            NL
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- Bande verte succès avec infos -->
        <div class="bg-gradient-to-r from-green-500 to-green-600 text-white shadow-lg relative z-10" 
             style="background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);">
            <div class="container mx-auto px-4 py-6">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="flex items-center">
                        <div class="bg-white bg-opacity-20 rounded-full p-3 mr-4">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold"><?= htmlspecialchars($campaign['title_' . $customer['language']]) ?></h1>
                            <p class="text-green-100 text-sm mt-1">
                                <?= $customer['language'] === 'fr' ? 'Commande validée avec succès' : 'Bestelling succesvol bevestigd' ?>
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-green-100 text-sm">
                            <?= $customer['language'] === 'fr' ? 'Client N°' : 'Klant Nr.' ?>
                        </p>
                        <p class="text-xl font-bold"><?= htmlspecialchars($customer['customer_number']) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Message de succès (flash) -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="container mx-auto px-4 mt-6">
                <div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-6 py-4 rounded-lg shadow-md flex items-start">
                    <svg class="w-6 h-6 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="font-semibold text-lg"><?= htmlspecialchars($_SESSION['success']) ?></p>
                        <?php if ($orderUuid): ?>
                            <p class="text-sm mt-1">
                                <?= $customer['language'] === 'fr' ? 'Numéro de commande' : 'Bestelnummer' ?> : 
                                <span class="font-mono font-bold"><?= htmlspecialchars($orderUuid) ?></span>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Contenu principal -->
        <div class="container mx-auto px-4 py-8">
            <div class="max-w-3xl mx-auto">
                
                <!-- Carte de confirmation -->
                <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                    <!-- Grande icône de succès (80px) -->
                    <div class="mb-6">
                        <div class="inline-flex items-center justify-center w-24 h-24 bg-green-100 rounded-full">
                            <svg class="w-16 h-16 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Titre -->
                    <h2 class="text-3xl font-bold text-gray-800 mb-4">
                        <?= $customer['language'] === 'fr' ? 'Merci pour votre commande !' : 'Bedankt voor uw bestelling!' ?>
                    </h2>

                    <!-- Message -->
                    <div class="text-gray-600 space-y-3 mb-8">
                        <p class="text-lg">
                            <?= $customer['language'] === 'fr' 
                                ? 'Votre commande a été enregistrée et sera traitée dans les plus brefs délais.' 
                                : 'Uw bestelling is geregistreerd en wordt zo spoedig mogelijk verwerkt.' ?>
                        </p>
                        <p>
                            <?= $customer['language'] === 'fr' 
                                ? 'Vous recevrez un email de confirmation à l\'adresse indiquée.' 
                                : 'U ontvangt een bevestigingsmail op het opgegeven adres.' ?>
                        </p>
                        
                        <!-- Date de livraison si applicable -->
                        <?php if ($campaign['deferred_delivery'] == 1 && !empty($campaign['delivery_date'])): ?>
                            <div class="mt-6 p-6 bg-blue-50 border border-blue-200 rounded-lg">
                                <div class="flex items-center justify-center mb-2">
                                    <svg class="w-6 h-6 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="font-semibold text-blue-900">
                                        <?= $customer['language'] === 'fr' ? 'Date de livraison prévue' : 'Geplande leveringsdatum' ?>
                                    </span>
                                </div>
                                <p class="text-2xl font-bold text-blue-700">
                                    <?php
                                    $deliveryDate = new DateTime($campaign['delivery_date']);
                                    $monthsFr = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
                                    $monthsNl = ['januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december'];
                                    $day = $deliveryDate->format('d');
                                    $monthIndex = (int)$deliveryDate->format('m') - 1;
                                    $year = $deliveryDate->format('Y');
                                    $monthName = $customer['language'] === 'fr' ? $monthsFr[$monthIndex] : $monthsNl[$monthIndex];
                                    echo "$day $monthName $year";
                                    ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Informations commande -->
                    <?php if ($orderUuid): ?>
                        <div class="bg-gray-50 rounded-lg p-6 mb-8">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <?= $customer['language'] === 'fr' ? 'Informations de commande' : 'Bestelgegevens' ?>
                            </h3>
                            <div class="space-y-3 text-left">
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-gray-600">
                                        <?= $customer['language'] === 'fr' ? 'Numéro de commande' : 'Bestelnummer' ?> :
                                    </span>
                                    <span class="font-mono font-semibold text-green-700"><?= htmlspecialchars($orderUuid) ?></span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-gray-600">
                                        <?= $customer['language'] === 'fr' ? 'Client' : 'Klant' ?> :
                                    </span>
                                    <span class="font-semibold"><?= htmlspecialchars($customer['customer_number']) ?></span>
                                </div>
                                <div class="flex justify-between items-center py-2">
                                    <span class="text-gray-600">
                                        <?= $customer['language'] === 'fr' ? 'Date' : 'Datum' ?> :
                                    </span>
                                    <span class="font-semibold"><?= date('d/m/Y H:i') ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Actions -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <!-- Bouton retour catalogue (BLEU) -->
                        <a href="/stm/c/<?= htmlspecialchars($uuid) ?>/catalog" 
                           class="inline-flex items-center justify-center bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors shadow-md hover:shadow-lg">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <?= $customer['language'] === 'fr' ? 'Retour au catalogue' : 'Terug naar catalogus' ?>
                        </a>
                        
                        <!-- Bouton déconnexion (GRIS) -->
                        <a href="/stm/c/<?= htmlspecialchars($uuid) ?>" 
                           class="inline-flex items-center justify-center bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-700 transition-colors shadow-md hover:shadow-lg">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            <?= $customer['language'] === 'fr' ? 'Se déconnecter' : 'Afmelden' ?>
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
                            <p class="font-semibold mb-1">
                                <?= $customer['language'] === 'fr' ? 'Besoin d\'aide ?' : 'Hulp nodig?' ?>
                            </p>
                            <p>
                                <?= $customer['language'] === 'fr' 
                                    ? 'Pour toute question concernant votre commande, contactez votre commercial habituel.' 
                                    : 'Voor vragen over uw bestelling kunt u contact opnemen met uw vaste vertegenwoordiger.' ?>
                            </p>
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
                <?= $customer['language'] === 'fr' ? 'Tous droits réservés' : 'Alle rechten voorbehouden' ?>
            </p>
        </div>
    </footer>

</body>
</html>