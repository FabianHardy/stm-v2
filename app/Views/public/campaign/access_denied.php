<?php
/**
 * Vue : Accès refusé à une campagne
 * Affiche un message selon la raison du refus
 * 
 * @created  2025/11/14 16:50
 */

// Déterminer la langue
$lang = isset($campaign) && $campaign['country'] === 'LU' ? 'nl' : 'fr';

// Messages selon la raison
$messages = [
    'campaign_not_found' => [
        'title_fr' => 'Campagne introuvable',
        'title_nl' => 'Campagne niet gevonden',
        'message_fr' => 'Cette campagne n\'existe pas ou a été supprimée.',
        'message_nl' => 'Deze campagne bestaat niet of is verwijderd.',
        'icon' => 'search',
        'color' => 'red'
    ],
    'upcoming' => [
        'title_fr' => 'Campagne à venir',
        'title_nl' => 'Aankomende campagne',
        'message_fr' => 'Cette campagne n\'a pas encore commencé. Elle sera disponible à partir du ',
        'message_nl' => 'Deze campagne is nog niet begonnen. Deze zal beschikbaar zijn vanaf ',
        'icon' => 'clock',
        'color' => 'blue'
    ],
    'ended' => [
        'title_fr' => 'Campagne terminée',
        'title_nl' => 'Campagne beëindigd',
        'message_fr' => 'Cette campagne est terminée depuis le ',
        'message_nl' => 'Deze campagne is afgelopen sinds ',
        'icon' => 'calendar',
        'color' => 'gray'
    ],
    'inactive' => [
        'title_fr' => 'Campagne inactive',
        'title_nl' => 'Campagne inactief',
        'message_fr' => 'Cette campagne est actuellement inactive. Veuillez réessayer plus tard.',
        'message_nl' => 'Deze campagne is momenteel inactief. Probeer het later opnieuw.',
        'icon' => 'ban',
        'color' => 'gray'
    ],
    'no_access' => [
        'title_fr' => 'Accès non autorisé',
        'title_nl' => 'Geen toegang',
        'message_fr' => 'Votre numéro client n\'est pas autorisé à accéder à cette campagne.',
        'message_nl' => 'Uw klantnummer is niet gemachtigd om toegang te krijgen tot deze campagne.',
        'icon' => 'lock',
        'color' => 'red'
    ],
    'quotas_reached' => [
        'title_fr' => 'Campagne fermée',
        'title_nl' => 'Campagne gesloten',
        'message_fr' => 'Tous les produits de cette campagne ont atteint leur quota maximum. La campagne est fermée.',
        'message_nl' => 'Alle producten van deze campagne hebben hun maximale quotum bereikt. De campagne is gesloten.',
        'icon' => 'check',
        'color' => 'green'
    ],
    'error' => [
        'title_fr' => 'Erreur',
        'title_nl' => 'Fout',
        'message_fr' => 'Une erreur est survenue. Veuillez réessayer plus tard.',
        'message_nl' => 'Er is een fout opgetreden. Probeer het later opnieuw.',
        'icon' => 'exclamation',
        'color' => 'red'
    ]
];

$msg = $messages[$reason] ?? $messages['error'];
$title = $lang === 'fr' ? $msg['title_fr'] : $msg['title_nl'];
$message = $lang === 'fr' ? $msg['message_fr'] : $msg['message_nl'];

// Ajouter la date si campagne à venir ou terminée
if ($reason === 'upcoming' && isset($campaign['start_date'])) {
    $message .= '<strong>' . date('d/m/Y', strtotime($campaign['start_date'])) . '</strong>.';
}
if ($reason === 'ended' && isset($campaign['end_date'])) {
    $message .= '<strong>' . date('d/m/Y', strtotime($campaign['end_date'])) . '</strong>.';
}

// Définir les couleurs selon le type
$colors = [
    'red' => 'bg-red-50 border-red-200 text-red-800',
    'blue' => 'bg-blue-50 border-blue-200 text-blue-800',
    'gray' => 'bg-gray-50 border-gray-200 text-gray-800',
    'green' => 'bg-green-50 border-green-200 text-green-800'
];
$colorClass = $colors[$msg['color']] ?? $colors['gray'];

// Icons SVG
$icons = [
    'search' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>',
    'clock' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    'calendar' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
    'ban' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>',
    'lock' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>',
    'check' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    'exclamation' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'
];
$icon = $icons[$msg['icon']] ?? $icons['exclamation'];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - Trendy Foods</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom Tailwind Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'trendy': {
                            50: '#fef2f2',
                            100: '#fee2e2',
                            200: '#fecaca',
                            300: '#fca5a5',
                            400: '#f87171',
                            500: '#ef4444',
                            600: '#e74c3c',
                            700: '#b91c1c',
                            800: '#991b1b',
                            900: '#7f1d1d'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">

    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="container mx-auto px-4 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <img src="/stm/assets/images/logo.png" alt="Trendy Foods" class="h-12" onerror="this.style.display='none'">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Trendy Foods</h1>
                        <p class="text-sm text-gray-600">
                            <?= $lang === 'fr' ? 'Votre grossiste de confiance' : 'Uw vertrouwde groothandel' ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-12">
        
        <div class="max-w-2xl mx-auto">
            
            <!-- Access Denied Message -->
            <div class="bg-white rounded-lg shadow-md p-8">
                
                <!-- Icon -->
                <div class="flex justify-center mb-6">
                    <div class="p-4 <?= $colorClass ?> rounded-full border-2">
                        <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <?= $icon ?>
                        </svg>
                    </div>
                </div>

                <!-- Title -->
                <h2 class="text-3xl font-bold text-center text-gray-900 mb-4">
                    <?= htmlspecialchars($title) ?>
                </h2>

                <!-- Message -->
                <div class="<?= $colorClass ?> border-2 rounded-lg p-6 mb-8">
                    <p class="text-center text-lg leading-relaxed">
                        <?= $message ?>
                    </p>
                </div>

                <!-- Campaign Info (si disponible) -->
                <?php if (isset($campaign) && $reason !== 'campaign_not_found'): ?>
                <div class="border-t border-gray-200 pt-6 mb-6">
                    <h3 class="font-semibold text-gray-900 mb-3 text-center">
                        <?= $lang === 'fr' ? 'Informations sur la campagne' : 'Informatie over de campagne' ?>
                    </h3>
                    <div class="space-y-2 text-center text-gray-700">
                        <p class="font-medium text-lg">
                            <?= htmlspecialchars($lang === 'fr' ? $campaign['title_fr'] : $campaign['title_nl']) ?>
                        </p>
                        <p class="text-sm text-gray-500">
                            <?= date('d/m/Y', strtotime($campaign['start_date'])) ?>
                            -
                            <?= date('d/m/Y', strtotime($campaign['end_date'])) ?>
                        </p>
                        <p class="text-sm">
                            <span class="px-3 py-1 bg-gray-100 rounded-full">
                                <?= $campaign['country'] === 'BOTH' 
                                    ? ($lang === 'fr' ? 'BE + LU' : 'BE + LU') 
                                    : $campaign['country'] 
                                ?>
                            </span>
                        </p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Help Section -->
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
                    <div class="flex items-start space-x-3">
                        <svg class="w-6 h-6 text-gray-600 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">
                                <?= $lang === 'fr' ? 'Besoin d\'aide ?' : 'Hulp nodig?' ?>
                            </h4>
                            <p class="text-sm text-gray-700 leading-relaxed mb-3">
                                <?= $lang === 'fr' 
                                    ? 'Si vous pensez qu\'il s\'agit d\'une erreur ou si vous avez des questions, n\'hésitez pas à nous contacter :' 
                                    : 'Als u denkt dat dit een vergissing is of als u vragen heeft, aarzel dan niet om contact met ons op te nemen:' 
                                ?>
                            </p>
                            <div class="space-y-1 text-sm text-gray-700">
                                <p>
                                    <span class="font-medium">
                                        <?= $lang === 'fr' ? 'Téléphone :' : 'Telefoon:' ?>
                                    </span>
                                    +32 (0)4 XXX XX XX
                                </p>
                                <p>
                                    <span class="font-medium">Email:</span>
                                    info@trendyfoods.be
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-gray-300 mt-16">
        <div class="container mx-auto px-4 py-8">
            <div class="text-center text-sm">
                <p>&copy; <?= date('Y') ?> Trendy Foods. 
                    <?= $lang === 'fr' ? 'Tous droits réservés.' : 'Alle rechten voorbehouden.' ?>
                </p>
            </div>
        </div>
    </footer>

</body>
</html>