<?php
/**
 * Vue : Accès campagne publique
 * Page d'identification client
 * 
 * @created  2025/11/14 16:45
 */

// Déterminer la langue (par défaut FR)
$lang = $campaign['country'] === 'LU' ? 'nl' : 'fr';

// Récupérer les erreurs de session
$error = \Core\Session::get('error');
\Core\Session::remove('error');

$title = $lang === 'fr' ? $campaign['title_fr'] : $campaign['title_nl'];
$description = $lang === 'fr' ? $campaign['description_fr'] : $campaign['description_nl'];
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
            
            <!-- Campaign Info -->
            <div class="bg-white rounded-lg shadow-md p-8 mb-8">
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-bold text-gray-900 mb-3">
                        <?= htmlspecialchars($title) ?>
                    </h2>
                    
                    <?php if ($description): ?>
                    <p class="text-gray-600 leading-relaxed">
                        <?= nl2br(htmlspecialchars($description)) ?>
                    </p>
                    <?php endif; ?>
                    
                    <!-- Dates -->
                    <div class="flex items-center justify-center space-x-6 mt-6 text-sm text-gray-500">
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>
                                <?= date('d/m/Y', strtotime($campaign['start_date'])) ?>
                                -
                                <?= date('d/m/Y', strtotime($campaign['end_date'])) ?>
                            </span>
                        </div>
                        
                        <?php if ($campaign['country'] !== 'BOTH'): ?>
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>
                            </svg>
                            <span><?= $campaign['country'] ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <hr class="my-8 border-gray-200">

                <!-- Identification Form -->
                <div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-6 text-center">
                        <?= $lang === 'fr' ? 'Accès client' : 'Toegang klant' ?>
                    </h3>

                    <?php if ($error): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-red-500 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-sm text-red-700"><?= htmlspecialchars($error) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <form method="POST" action="/stm/c/<?= htmlspecialchars($campaign['uuid']) ?>/identify" class="space-y-6">
                        
                        <!-- Token CSRF -->
                        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

                        <!-- Numéro client -->
                        <div>
                            <label for="customer_number" class="block text-sm font-medium text-gray-700 mb-2">
                                <?= $lang === 'fr' ? 'Numéro client' : 'Klantnummer' ?>
                                <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="customer_number" 
                                name="customer_number" 
                                required
                                placeholder="<?= $lang === 'fr' ? 'Ex: 123456' : 'Bijv: 123456' ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-trendy-500 focus:border-transparent"
                            >
                            <p class="mt-2 text-sm text-gray-500">
                                <?= $lang === 'fr' 
                                    ? 'Entrez votre numéro client à 6 chiffres' 
                                    : 'Voer uw klantnummer van 6 cijfers in' 
                                ?>
                            </p>
                        </div>

                        <!-- Pays (si BOTH) -->
                        <?php if ($campaign['country'] === 'BOTH'): ?>
                        <div>
                            <label for="country" class="block text-sm font-medium text-gray-700 mb-2">
                                <?= $lang === 'fr' ? 'Pays' : 'Land' ?>
                                <span class="text-red-500">*</span>
                            </label>
                            <select 
                                id="country" 
                                name="country" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-trendy-500 focus:border-transparent"
                            >
                                <option value="BE"><?= $lang === 'fr' ? 'Belgique' : 'België' ?></option>
                                <option value="LU"><?= $lang === 'fr' ? 'Luxembourg' : 'Luxemburg' ?></option>
                            </select>
                        </div>
                        <?php else: ?>
                        <input type="hidden" name="country" value="<?= htmlspecialchars($campaign['country']) ?>">
                        <?php endif; ?>

                        <!-- Submit Button -->
                        <button 
                            type="submit" 
                            class="w-full bg-trendy-600 text-white font-semibold py-3 px-6 rounded-lg hover:bg-trendy-700 transition-colors duration-200 flex items-center justify-center space-x-2"
                        >
                            <span><?= $lang === 'fr' ? 'Accéder au catalogue' : 'Toegang tot catalogus' ?></span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </button>

                    </form>
                </div>
            </div>

            <!-- Help Section -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                <div class="flex items-start space-x-3">
                    <svg class="w-6 h-6 text-blue-600 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <h4 class="font-semibold text-blue-900 mb-2">
                            <?= $lang === 'fr' ? 'Besoin d\'aide ?' : 'Hulp nodig?' ?>
                        </h4>
                        <p class="text-sm text-blue-800 leading-relaxed">
                            <?= $lang === 'fr' 
                                ? 'Si vous ne connaissez pas votre numéro client ou si vous rencontrez des difficultés, contactez notre service client au +32 (0)4 XXX XX XX ou par email à info@trendyfoods.be' 
                                : 'Als u uw klantnummer niet kent of problemen ondervindt, neem dan contact op met onze klantenservice op +32 (0)4 XXX XX XX of per e-mail op info@trendyfoods.be' 
                            ?>
                        </p>
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