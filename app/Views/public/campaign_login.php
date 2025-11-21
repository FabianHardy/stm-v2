<?php
/**
 * Vue : Connexion client (publique)
 * 
 * Page de connexion pour les clients accédant à une campagne via URL unique
 * Connexion simple avec numéro client uniquement (pas de mot de passe)
 * 
 * @package STM/Views/Public
 * @version 1.0
 * @created 13/11/2025 09:55
 */

// Récupérer les données passées par le contrôleur
$campaign = $campaign ?? null;
$error = $error ?? null;
?>
<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    
    <title>Connexion - <?= htmlspecialchars($campaign['name'] ?? 'STM') ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/stm/assets/images/favicon.png">
    
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#faf5ff',
                            100: '#f3e8ff',
                            200: '#e9d5ff',
                            300: '#d8b4fe',
                            400: '#c084fc',
                            500: '#a855f7',
                            600: '#9333ea',
                            700: '#7e22ce',
                            800: '#6b21a8',
                            900: '#581c87',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="h-full">
    
    <div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8 bg-gradient-to-br from-purple-50 to-white">
        
        <!-- Logo / Branding -->
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="flex justify-center">
                <div class="bg-gradient-to-br from-purple-600 to-purple-700 text-white w-16 h-16 rounded-2xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-shopping-cart text-2xl"></i>
                </div>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Trendy Foods
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Système de commandes promotionnelles
            </p>
        </div>

        <?php if ($campaign): ?>
            <!-- Carte de connexion -->
            <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
                <div class="bg-white py-8 px-4 shadow-xl sm:rounded-lg sm:px-10 border border-gray-100">
                    
                    <!-- Info campagne -->
                    <div class="mb-6 p-4 bg-purple-50 border border-purple-200 rounded-lg">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-purple-100">
                                    <i class="fas fa-bullhorn text-purple-600"></i>
                                </span>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-sm font-semibold text-purple-900">
                                    <?= htmlspecialchars($campaign['name']) ?>
                                </h3>
                                <p class="mt-1 text-xs text-purple-700">
                                    <i class="fas fa-flag mr-1"></i>
                                    <?= $campaign['country'] === 'BE' ? '🇧🇪 Belgique' : '🇱🇺 Luxembourg' ?>
                                </p>
                                <?php if (!empty($campaign['title_' . ($campaign['country'] === 'BE' ? 'fr' : 'fr')])): ?>
                                    <p class="mt-2 text-sm text-gray-700">
                                        <?= htmlspecialchars($campaign['title_fr'] ?? $campaign['title_nl'] ?? '') ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Message d'erreur -->
                    <?php if ($error): ?>
                        <div class="mb-6 rounded-md bg-red-50 p-4 border border-red-200">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-red-800">
                                        <?= htmlspecialchars($error) ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Formulaire de connexion -->
                    <form method="POST" action="/stm/c/<?= htmlspecialchars($campaign['unique_url']) ?>/login" class="space-y-6">
                        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        
                        <div>
                            <label for="customer_number" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-user mr-2 text-gray-400"></i>
                                Numéro client
                            </label>
                            <input 
                                type="text" 
                                name="customer_number" 
                                id="customer_number" 
                                required
                                autofocus
                                placeholder="Ex: 123456, 670975-06, E12345-CB..."
                                class="appearance-none block w-full px-3 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm"
                            >
                            <p class="mt-2 text-xs text-gray-500">
                                <i class="fas fa-info-circle mr-1"></i>
                                Entrez votre numéro client pour accéder aux promotions
                            </p>
                        </div>

                        <div>
                            <button 
                                type="submit"
                                class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-all duration-150"
                            >
                                <i class="fas fa-sign-in-alt mr-2"></i>
                                Se connecter
                            </button>
                        </div>
                    </form>

                    <!-- Info supplémentaire -->
                    <div class="mt-6">
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-300"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-2 bg-white text-gray-500">
                                    <i class="fas fa-lock mr-1 text-gray-400"></i>
                                    Connexion sécurisée
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dates de validité -->
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-500">
                        <i class="fas fa-calendar mr-2"></i>
                        Campagne valide du 
                        <strong><?= date('d/m/Y', strtotime($campaign['start_date'])) ?></strong>
                        au 
                        <strong><?= date('d/m/Y', strtotime($campaign['end_date'])) ?></strong>
                    </p>
                </div>
            </div>
        
        <?php else: ?>
            <!-- Campagne non trouvée -->
            <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
                <div class="bg-white py-8 px-4 shadow-xl sm:rounded-lg sm:px-10 border border-gray-100 text-center">
                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">Campagne introuvable</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Cette campagne n'existe pas ou n'est plus disponible.
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="mt-8 text-center">
            <p class="text-xs text-gray-400">
                © <?= date('Y') ?> Trendy Foods - Tous droits réservés
            </p>
        </div>

    </div>

</body>
</html>