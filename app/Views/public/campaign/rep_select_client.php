<?php
/**
 * rep_select_client.php
 *
 * Page de sélection du client par le représentant commercial
 * Le rep peut rechercher un client par numéro parmi ceux autorisés sur la campagne
 *
 * @created 2026/01/05
 */

// Variables disponibles :
// $campaign : Données de la campagne
// $rep : Données du représentant connecté
// $error : Message d'erreur éventuel
// $customers : Liste des clients autorisés (mode manual)

$lang = $_SESSION['rep_language'] ?? 'fr';
$isManualMode = $campaign['customer_assignment_mode'] === 'manual';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang === 'fr' ? 'Sélection client' : 'Klantselectie' ?> - <?= htmlspecialchars($campaign['title_' . $lang] ?? $campaign['title_fr']) ?></title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">

    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="max-w-4xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <img src="/stm/assets/images/logo.png" alt="Trendy Foods" class="h-10">
                    <div>
                        <h1 class="text-lg font-bold text-gray-900">
                            <?= htmlspecialchars($campaign['title_' . $lang] ?? $campaign['title_fr']) ?>
                        </h1>
                        <p class="text-sm text-gray-500">
                            <?= $lang === 'fr' ? 'Mode représentant' : 'Vertegenwoordiger modus' ?>
                        </p>
                    </div>
                </div>

                <!-- Info Rep connecté -->
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($rep['name']) ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars($rep['email']) ?></p>
                    </div>
                    <a href="/stm/c/<?= htmlspecialchars($campaign['uuid']) ?>/rep/logout"
                       class="text-gray-400 hover:text-red-500 transition"
                       title="<?= $lang === 'fr' ? 'Déconnexion' : 'Afmelden' ?>">
                        <i class="fas fa-sign-out-alt text-xl"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Contenu principal -->
    <main class="max-w-2xl mx-auto px-4 py-8">

        <!-- Titre -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-4">
                <i class="fas fa-user-tie text-blue-600 text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-900">
                <?= $lang === 'fr' ? 'Sélectionner un client' : 'Selecteer een klant' ?>
            </h2>
            <p class="text-gray-600 mt-2">
                <?= $lang === 'fr'
                    ? 'Entrez le numéro du client pour lequel vous souhaitez passer commande'
                    : 'Voer het klantnummer in waarvoor u een bestelling wilt plaatsen' ?>
            </p>
        </div>

        <!-- Message d'erreur -->
        <?php if (!empty($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
            <i class="fas fa-exclamation-circle mr-3"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <!-- Formulaire de recherche -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <form action="/stm/c/<?= htmlspecialchars($campaign['uuid']) ?>/rep/identify" method="POST"
                  x-data="{ customerNumber: '', loading: false }"
                  @submit="loading = true">

                <!-- Token CSRF -->
                <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

                <!-- Champ numéro client -->
                <div class="mb-6">
                    <label for="customer_number" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-id-card mr-2 text-gray-400"></i>
                        <?= $lang === 'fr' ? 'Numéro client' : 'Klantnummer' ?> *
                    </label>
                    <input type="text"
                           id="customer_number"
                           name="customer_number"
                           x-model="customerNumber"
                           placeholder="<?= $lang === 'fr' ? 'Ex: 123456 ou 123456-12' : 'Bv: 123456 of 123456-12' ?>"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg"
                           autofocus>
                    <p class="text-xs text-gray-500 mt-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        <?= $lang === 'fr'
                            ? 'Formats acceptés : 123456, 123456-12, E12345-CB, *12345'
                            : 'Aanvaarde formaten: 123456, 123456-12, E12345-CB, *12345' ?>
                    </p>
                </div>

                <!-- Sélecteur de pays (si campagne BOTH) -->
                <?php if ($campaign['country'] === 'BOTH'): ?>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-globe mr-2 text-gray-400"></i>
                        <?= $lang === 'fr' ? 'Pays' : 'Land' ?> *
                    </label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="relative flex items-center justify-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-300 transition has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                            <input type="radio" name="country" value="BE" class="sr-only peer" checked>
                            <span class="flex items-center">
                                <span class="text-2xl mr-2">🇧🇪</span>
                                <span class="font-medium"><?= $lang === 'fr' ? 'Belgique' : 'België' ?></span>
                            </span>
                            <span class="absolute top-2 right-2 text-blue-500 opacity-0 peer-checked:opacity-100">
                                <i class="fas fa-check-circle"></i>
                            </span>
                        </label>
                        <label class="relative flex items-center justify-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-300 transition has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                            <input type="radio" name="country" value="LU" class="sr-only peer">
                            <span class="flex items-center">
                                <span class="text-2xl mr-2">🇱🇺</span>
                                <span class="font-medium">Luxembourg</span>
                            </span>
                            <span class="absolute top-2 right-2 text-blue-500 opacity-0 peer-checked:opacity-100">
                                <i class="fas fa-check-circle"></i>
                            </span>
                        </label>
                    </div>
                </div>
                <?php else: ?>
                <input type="hidden" name="country" value="<?= $campaign['country'] ?>">
                <?php endif; ?>

                <!-- Sélecteur de langue (si BE) -->
                <?php if ($campaign['country'] === 'BE' || $campaign['country'] === 'BOTH'): ?>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-language mr-2 text-gray-400"></i>
                        <?= $lang === 'fr' ? 'Langue du client' : 'Taal van de klant' ?>
                    </label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="relative flex items-center justify-center p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-300 transition has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                            <input type="radio" name="language" value="fr" class="sr-only peer" <?= $lang === 'fr' ? 'checked' : '' ?>>
                            <span class="font-medium">Français</span>
                            <span class="absolute top-2 right-2 text-blue-500 opacity-0 peer-checked:opacity-100">
                                <i class="fas fa-check-circle"></i>
                            </span>
                        </label>
                        <label class="relative flex items-center justify-center p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-300 transition has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                            <input type="radio" name="language" value="nl" class="sr-only peer" <?= $lang === 'nl' ? 'checked' : '' ?>>
                            <span class="font-medium">Nederlands</span>
                            <span class="absolute top-2 right-2 text-blue-500 opacity-0 peer-checked:opacity-100">
                                <i class="fas fa-check-circle"></i>
                            </span>
                        </label>
                    </div>
                </div>
                <?php else: ?>
                <input type="hidden" name="language" value="fr">
                <?php endif; ?>

                <!-- Bouton submit -->
                <button type="submit"
                        :disabled="!customerNumber.trim() || loading"
                        :class="{ 'opacity-50 cursor-not-allowed': !customerNumber.trim() || loading }"
                        class="w-full bg-blue-600 text-white py-4 px-6 rounded-lg font-semibold hover:bg-blue-700 transition flex items-center justify-center">
                    <template x-if="!loading">
                        <span>
                            <i class="fas fa-arrow-right mr-2"></i>
                            <?= $lang === 'fr' ? 'Accéder au catalogue' : 'Naar catalogus' ?>
                        </span>
                    </template>
                    <template x-if="loading">
                        <span>
                            <i class="fas fa-spinner fa-spin mr-2"></i>
                            <?= $lang === 'fr' ? 'Chargement...' : 'Laden...' ?>
                        </span>
                    </template>
                </button>
            </form>
        </div>

        <!-- Info mode campagne -->
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start">
                <i class="fas fa-info-circle text-blue-500 mt-0.5 mr-3"></i>
                <div class="text-sm text-blue-800">
                    <?php if ($isManualMode): ?>
                        <p class="font-medium mb-1">
                            <?= $lang === 'fr' ? 'Mode liste restreinte' : 'Beperkte lijstmodus' ?>
                        </p>
                        <p>
                            <?= $lang === 'fr'
                                ? 'Cette campagne est limitée à une liste de clients spécifiques.'
                                : 'Deze campagne is beperkt tot een specifieke lijst van klanten.' ?>
                        </p>
                    <?php else: ?>
                        <p class="font-medium mb-1">
                            <?= $lang === 'fr' ? 'Mode accès libre' : 'Vrije toegang modus' ?>
                        </p>
                        <p>
                            <?= $lang === 'fr'
                                ? 'Tous les clients peuvent accéder à cette campagne.'
                                : 'Alle klanten hebben toegang tot deze campagne.' ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Lien retour -->
        <div class="text-center mt-6">
            <a href="/stm/admin/dashboard" class="text-gray-500 hover:text-gray-700 text-sm">
                <i class="fas fa-arrow-left mr-1"></i>
                <?= $lang === 'fr' ? 'Retour au tableau de bord' : 'Terug naar dashboard' ?>
            </a>
        </div>

    </main>

    <!-- Footer -->
    <footer class="py-6 text-center text-gray-400 text-sm">
        <p>&copy; <?= date('Y') ?> Trendy Foods - <?= $lang === 'fr' ? 'Tous droits réservés' : 'Alle rechten voorbehouden' ?></p>
    </footer>

</body>
</html>