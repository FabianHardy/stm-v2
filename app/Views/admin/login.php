<?php
/**
 * Page de connexion admin
 *
 * Interface de login pour l'espace admin STM v2
 * - Bouton Microsoft Entra (SSO) en principal
 * - Formulaire classique masqué (pour comptes de test)
 *
 * @modified 2025/12/15 - Ajout authentification Microsoft Entra
 */

use Core\Session;

Session::start();

// Récupérer les erreurs et anciennes valeurs
$errors = Session::get("errors", []);
$old = Session::get("old", []);
Session::remove("errors");
Session::remove("old");

// Vérifier si Microsoft est configuré (via fichier config ou .env)
$microsoftConfigured = false;
$configFile = dirname(__DIR__, 3) . '/config/microsoft.php';
if (file_exists($configFile)) {
    $msConfig = require $configFile;
    $microsoftConfigured = !empty($msConfig['client_id'] ?? '');
} else {
    $microsoftConfigured = !empty($_ENV['MICROSOFT_CLIENT_ID'] ?? $_SERVER['MICROSOFT_CLIENT_ID'] ?? '');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - STM v2 Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js pour les interactions -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <!-- Logo et titre -->
        <div class="text-center mb-8">
            <div class="inline-block bg-white rounded-full p-4 mb-4 shadow-lg">
                <svg class="w-12 h-12 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">STM v2</h1>
            <p class="text-purple-100">Système de Gestion de Promotions B2B</p>
        </div>

        <!-- Carte de connexion -->
        <div class="bg-white rounded-2xl shadow-2xl p-8" x-data="{ showClassicLogin: false }">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Connexion</h2>
            <p class="text-gray-600 mb-6">Connectez-vous avec votre compte Trendy Foods</p>

            <!-- Messages flash -->
            <?php include __DIR__ . "/partials/flash.php"; ?>

            <?php if ($microsoftConfigured): ?>
            <!-- ========================================== -->
            <!-- BOUTON MICROSOFT (Principal) -->
            <!-- ========================================== -->
            <a href="/stm/auth/microsoft"
               class="w-full flex justify-center items-center py-3 px-4 border border-gray-300 rounded-lg shadow-sm bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 font-medium transition-all duration-150 mb-4">
                <!-- Logo Microsoft -->
                <svg class="w-5 h-5 mr-3" viewBox="0 0 21 21" xmlns="http://www.w3.org/2000/svg">
                    <rect x="1" y="1" width="9" height="9" fill="#f25022"/>
                    <rect x="11" y="1" width="9" height="9" fill="#7fba00"/>
                    <rect x="1" y="11" width="9" height="9" fill="#00a4ef"/>
                    <rect x="11" y="11" width="9" height="9" fill="#ffb900"/>
                </svg>
                <span class="text-gray-700">Se connecter avec Microsoft</span>
            </a>

            <!-- Séparateur -->
            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-200"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <button type="button"
                            @click="showClassicLogin = !showClassicLogin"
                            class="px-4 bg-white text-gray-500 hover:text-gray-700 cursor-pointer">
                        <span x-show="!showClassicLogin">Connexion administrateur</span>
                        <span x-show="showClassicLogin">Masquer</span>
                        <svg class="inline-block w-4 h-4 ml-1 transition-transform duration-200"
                             :class="{ 'rotate-180': showClassicLogin }"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <!-- ========================================== -->
            <!-- FORMULAIRE CLASSIQUE (Masqué par défaut) -->
            <!-- ========================================== -->
            <div x-show="showClassicLogin"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform -translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 transform translate-y-0"
                 x-transition:leave-end="opacity-0 transform -translate-y-2"
                 x-cloak>

                <form method="POST" action="/stm/admin/login" class="space-y-6">
                    <!-- Token CSRF -->
                    <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">

                    <!-- Nom d'utilisateur -->
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                            Nom d'utilisateur
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <input
                                type="text"
                                id="username"
                                name="username"
                                value="<?= htmlspecialchars($old["username"] ?? "") ?>"
                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent <?= isset($errors["username"]) ? "border-red-500" : "" ?>"
                                placeholder="Entrez votre nom d'utilisateur"
                                required>
                        </div>
                        <?php if (isset($errors["username"])): ?>
                            <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors["username"][0]) ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Mot de passe -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Mot de passe
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent <?= isset($errors["password"]) ? "border-red-500" : "" ?>"
                                placeholder="Entrez votre mot de passe"
                                required>
                        </div>
                        <?php if (isset($errors["password"])): ?>
                            <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors["password"][0]) ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Se souvenir de moi -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input
                                type="checkbox"
                                id="remember"
                                name="remember"
                                class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                            <label for="remember" class="ml-2 block text-sm text-gray-700">
                                Se souvenir de moi
                            </label>
                        </div>
                    </div>

                    <!-- Bouton de connexion -->
                    <button
                        type="submit"
                        class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-white bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 font-medium transition-all duration-150">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                        </svg>
                        Se connecter
                    </button>
                </form>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6 text-white text-sm">
            <p>&copy; <?= date("Y") ?> STM v2 - Trendy Foods</p>
            <p class="mt-1 text-purple-100">Version 2.0.0</p>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</body>
</html>