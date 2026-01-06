<?php
/**
 * rep_select_client.php
 *
 * Page de sélection du client par le représentant commercial
 * Le rep peut rechercher un client par numéro parmi ceux autorisés sur la campagne
 *
 * Chemin : /app/Views/public/campaign/rep_select_client.php
 *
 * @created 2026/01/05 - Sprint 14
 * @modified 2026/01/06 - Utilisation système trans() pour traductions
 */

// Variables disponibles :
// $campaign : Données de la campagne
// $rep : Données du représentant connecté (depuis session rep_session)
// $error : Message d'erreur éventuel

// Gestion du switch langue
$requestedLang = $_GET['lang'] ?? null;
if ($requestedLang && in_array($requestedLang, ['fr', 'nl'], true)) {
    $_SESSION['rep_session']['rep_language'] = $requestedLang;
    // Rediriger pour nettoyer l'URL
    $cleanUrl = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: {$cleanUrl}");
    exit;
}

$lang = $_SESSION['rep_session']['rep_language'] ?? 'fr';
$isManualMode = ($campaign['customer_assignment_mode'] ?? 'automatic') === 'manual';
$showLangSwitch = in_array($campaign['country'] ?? 'BE', ['BE', 'BOTH']);
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= trans('rep.select_title', $lang) ?> - <?= htmlspecialchars($campaign['title_' . $lang] ?? $campaign['title_fr']) ?></title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        /* Style pour les radio buttons custom */
        .radio-card:has(input:checked) {
            border-color: #3b82f6;
            background-color: #eff6ff;
        }
        .radio-card:has(input:checked) .check-icon {
            opacity: 1;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

    <!-- Header avec logo et info rep -->
    <header class="bg-white shadow-sm">
        <div class="max-w-4xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <!-- Logo et titre campagne -->
                <div class="flex items-center space-x-4">
                    <img src="/stm/assets/images/logo.png" alt="Trendy Foods" class="h-10" onerror="this.style.display='none'">
                    <div>
                        <h1 class="text-lg font-bold text-gray-900">
                            <?= htmlspecialchars($campaign['title_' . $lang] ?? $campaign['title_fr']) ?>
                        </h1>
                        <p class="text-sm text-purple-600 font-medium">
                            <i class="fas fa-user-tie mr-1"></i>
                            <?= trans('rep.mode_label', $lang) ?>
                        </p>
                    </div>
                </div>

                <!-- Info Rep connecté + switch langue -->
                <div class="flex items-center space-x-4">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($rep['rep_name'] ?? '') ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars($rep['rep_email'] ?? '') ?></p>
                    </div>

                    <!-- Switch langue FR/NL -->
                    <?php if ($showLangSwitch): ?>
                    <div class="hidden sm:flex bg-gray-100 rounded-lg p-1">
                        <a href="?lang=fr"
                           class="px-3 py-1.5 rounded-md text-sm <?= $lang === 'fr' ? 'bg-white text-purple-600 font-semibold shadow-sm' : 'text-gray-600 hover:bg-white hover:shadow-sm' ?> transition">
                            FR
                        </a>
                        <a href="?lang=nl"
                           class="px-3 py-1.5 rounded-md text-sm <?= $lang === 'nl' ? 'bg-white text-purple-600 font-semibold shadow-sm' : 'text-gray-600 hover:bg-white hover:shadow-sm' ?> transition">
                            NL
                        </a>
                    </div>
                    <?php endif; ?>

                    <a href="/stm/c/<?= htmlspecialchars($campaign['uuid']) ?>/rep/logout"
                       class="text-gray-400 hover:text-red-500 transition"
                       title="<?= trans('rep.logout', $lang) ?>">
                        <i class="fas fa-sign-out-alt text-xl"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Contenu principal -->
    <main class="max-w-2xl mx-auto px-4 py-8">

        <!-- Titre et icône -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-purple-100 rounded-full mb-4">
                <i class="fas fa-user-tie text-purple-600 text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-900">
                <?= trans('rep.select_title', $lang) ?>
            </h2>
            <p class="text-gray-600 mt-2">
                <?= trans('rep.select_subtitle', $lang) ?>
            </p>
        </div>

        <!-- Message d'erreur -->
        <?php if (!empty($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
            <i class="fas fa-exclamation-circle mr-3 flex-shrink-0"></i>
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
                        <?= trans('rep.customer_number', $lang) ?> <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           id="customer_number"
                           name="customer_number"
                           x-model="customerNumber"
                           placeholder="<?= trans('rep.customer_number_placeholder', $lang) ?>"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-lg"
                           autofocus>
                    <p class="text-xs text-gray-500 mt-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        <?= trans('rep.customer_number_formats', $lang) ?>
                    </p>
                </div>

                <!-- Sélecteur de pays (si campagne BOTH) -->
                <?php if ($campaign['country'] === 'BOTH'): ?>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-globe mr-2 text-gray-400"></i>
                        <?= trans('rep.country', $lang) ?> <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="radio-card relative flex items-center justify-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-300 transition">
                            <input type="radio" name="country" value="BE" class="sr-only" checked>
                            <span class="flex items-center">
                                <span class="text-2xl mr-2">🇧🇪</span>
                                <span class="font-medium"><?= trans('rep.country_be', $lang) ?></span>
                            </span>
                            <span class="check-icon absolute top-2 right-2 text-purple-500 opacity-0">
                                <i class="fas fa-check-circle"></i>
                            </span>
                        </label>
                        <label class="radio-card relative flex items-center justify-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-300 transition">
                            <input type="radio" name="country" value="LU" class="sr-only">
                            <span class="flex items-center">
                                <span class="text-2xl mr-2">🇱🇺</span>
                                <span class="font-medium"><?= trans('rep.country_lu', $lang) ?></span>
                            </span>
                            <span class="check-icon absolute top-2 right-2 text-purple-500 opacity-0">
                                <i class="fas fa-check-circle"></i>
                            </span>
                        </label>
                    </div>
                </div>
                <?php else: ?>
                <input type="hidden" name="country" value="<?= htmlspecialchars($campaign['country']) ?>">
                <?php endif; ?>

                <!-- Sélecteur de langue client (si BE ou BOTH) -->
                <?php if ($campaign['country'] === 'BE' || $campaign['country'] === 'BOTH'): ?>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-language mr-2 text-gray-400"></i>
                        <?= trans('rep.client_language', $lang) ?>
                    </label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="radio-card relative flex items-center justify-center p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-300 transition">
                            <input type="radio" name="language" value="fr" class="sr-only" <?= $lang === 'fr' ? 'checked' : '' ?>>
                            <span class="font-medium">Français</span>
                            <span class="check-icon absolute top-2 right-2 text-purple-500 opacity-0">
                                <i class="fas fa-check-circle"></i>
                            </span>
                        </label>
                        <label class="radio-card relative flex items-center justify-center p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-300 transition">
                            <input type="radio" name="language" value="nl" class="sr-only" <?= $lang === 'nl' ? 'checked' : '' ?>>
                            <span class="font-medium">Nederlands</span>
                            <span class="check-icon absolute top-2 right-2 text-purple-500 opacity-0">
                                <i class="fas fa-check-circle"></i>
                            </span>
                        </label>
                    </div>
                </div>
                <?php else: ?>
                <!-- Luxembourg = toujours français -->
                <input type="hidden" name="language" value="fr">
                <?php endif; ?>

                <!-- Bouton submit -->
                <button type="submit"
                        :disabled="!customerNumber.trim() || loading"
                        :class="{ 'opacity-50 cursor-not-allowed': !customerNumber.trim() || loading }"
                        class="w-full bg-purple-600 text-white py-4 px-6 rounded-lg font-semibold hover:bg-purple-700 transition flex items-center justify-center">
                    <template x-if="!loading">
                        <span>
                            <i class="fas fa-arrow-right mr-2"></i>
                            <?= trans('rep.go_to_catalog', $lang) ?>
                        </span>
                    </template>
                    <template x-if="loading">
                        <span>
                            <i class="fas fa-spinner fa-spin mr-2"></i>
                            <?= trans('rep.loading', $lang) ?>
                        </span>
                    </template>
                </button>
            </form>
        </div>

        <!-- Info mode campagne -->
        <div class="mt-6 bg-purple-50 border border-purple-200 rounded-lg p-4">
            <div class="flex items-start">
                <i class="fas fa-info-circle text-purple-500 mt-0.5 mr-3 flex-shrink-0"></i>
                <div class="text-sm text-purple-800">
                    <?php if ($isManualMode): ?>
                        <p class="font-medium mb-1">
                            <?= trans('rep.mode_restricted', $lang) ?>
                        </p>
                        <p>
                            <?= trans('rep.mode_restricted_info', $lang) ?>
                        </p>
                    <?php else: ?>
                        <p class="font-medium mb-1">
                            <?= trans('rep.mode_open', $lang) ?>
                        </p>
                        <p>
                            <?= trans('rep.mode_open_info', $lang) ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Lien retour dashboard -->
        <div class="text-center mt-6">
            <a href="/stm/admin/dashboard" class="text-gray-500 hover:text-gray-700 text-sm">
                <i class="fas fa-arrow-left mr-1"></i>
                <?= trans('rep.back_to_dashboard', $lang) ?>
            </a>
        </div>

    </main>

    <!-- Footer -->
    <footer class="py-6 text-center text-gray-400 text-sm">
        <p>&copy; <?= date('Y') ?> Trendy Foods - <?= trans('common.all_rights_reserved', $lang) ?></p>
    </footer>

    <!-- Script pour les radio buttons -->
    <script>
        // Rafraîchir visuellement les radio buttons au chargement
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('input[type="radio"]').forEach(function(radio) {
                radio.addEventListener('change', function() {
                    // Forcer le re-render CSS
                    this.closest('.radio-card')?.classList.add('selected');
                });
            });
        });
    </script>

</body>
</html>