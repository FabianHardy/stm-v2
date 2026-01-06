<?php
/**
 * rep_access.php
 *
 * Page de connexion pour les représentants commerciaux
 * Permet de choisir la langue avant de se connecter via Microsoft SSO
 *
 * Chemin : /app/Views/public/campaign/rep_access.php
 *
 * @created 2026/01/06 - Sprint 14
 */

// Variables disponibles :
// $campaign : Données de la campagne
// $lang : Langue actuelle (fr/nl)
// $error : Message d'erreur éventuel

$lang = $lang ?? 'fr';
$uuid = $campaign['uuid'] ?? '';

// Déterminer si le switch langue est disponible (BE ou BOTH)
$showLangSwitch = in_array($campaign['country'] ?? 'BE', ['BE', 'BOTH']);
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= trans('rep.page_title', $lang) ?> - <?= htmlspecialchars($campaign['title_' . $lang] ?? $campaign['title_fr'] ?? 'Campagne') ?></title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .microsoft-btn {
            background-color: #2f2f2f;
            transition: background-color 0.2s;
        }
        .microsoft-btn:hover {
            background-color: #1a1a1a;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-purple-50 to-indigo-100 min-h-screen flex flex-col">

    <!-- Header avec switch langue -->
    <header class="bg-white shadow-sm">
        <div class="max-w-4xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <!-- Logo et titre -->
                <div class="flex items-center space-x-4">
                    <img src="/stm/assets/images/logo.png" alt="Trendy Foods" class="h-10" onerror="this.style.display='none'">
                    <div>
                        <h1 class="text-lg font-bold text-gray-900">
                            <?= htmlspecialchars($campaign['title_' . $lang] ?? $campaign['title_fr'] ?? '') ?>
                        </h1>
                        <p class="text-sm text-purple-600 font-medium">
                            <i class="fas fa-user-tie mr-1"></i>
                            <?= trans('rep.mode_label', $lang) ?>
                        </p>
                    </div>
                </div>

                <!-- Switch langue FR/NL -->
                <?php if ($showLangSwitch): ?>
                <div class="flex bg-gray-100 rounded-lg p-1">
                    <a href="?lang=fr"
                       class="px-4 py-2 rounded-md <?= $lang === 'fr' ? 'bg-white text-purple-600 font-semibold shadow-sm' : 'text-gray-600 hover:bg-white hover:shadow-sm' ?> transition">
                        FR
                    </a>
                    <a href="?lang=nl"
                       class="px-4 py-2 rounded-md <?= $lang === 'nl' ? 'bg-white text-purple-600 font-semibold shadow-sm' : 'text-gray-600 hover:bg-white hover:shadow-sm' ?> transition">
                        NL
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Contenu principal -->
    <main class="flex-1 flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">

            <!-- Carte de connexion -->
            <div class="bg-white rounded-2xl shadow-xl p-8">

                <!-- Icône et titre -->
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-purple-100 rounded-full mb-4">
                        <i class="fas fa-user-tie text-purple-600 text-3xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">
                        <?= trans('rep.login_title', $lang) ?>
                    </h2>
                    <p class="text-gray-600 mt-2">
                        <?= trans('rep.login_subtitle', $lang) ?>
                    </p>
                </div>

                <!-- Message d'erreur -->
                <?php if (!empty($error)): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                    <i class="fas fa-exclamation-circle mr-3 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
                <?php endif; ?>

                <!-- Bouton Microsoft SSO -->
                <form action="/stm/c/<?= htmlspecialchars($uuid) ?>/rep/login" method="POST">
                    <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="lang" value="<?= $lang ?>">

                    <button type="submit"
                            class="microsoft-btn w-full text-white py-4 px-6 rounded-lg font-semibold flex items-center justify-center">
                        <svg class="w-5 h-5 mr-3" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="10" height="10" fill="#F25022"/>
                            <rect x="11" width="10" height="10" fill="#7FBA00"/>
                            <rect y="11" width="10" height="10" fill="#00A4EF"/>
                            <rect x="11" y="11" width="10" height="10" fill="#FFB900"/>
                        </svg>
                        <?= trans('rep.login_button', $lang) ?>
                    </button>
                </form>

                <!-- Info -->
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-500">
                        <i class="fas fa-shield-alt mr-1"></i>
                        <?= trans('rep.login_info', $lang) ?>
                    </p>
                </div>
            </div>

            <!-- Lien vers accès client -->
            <div class="text-center mt-6">
                <p class="text-gray-600 text-sm mb-2">
                    <?= trans('rep.not_a_rep', $lang) ?>
                </p>
                <a href="/stm/c/<?= htmlspecialchars($uuid) ?>"
                   class="text-purple-600 hover:text-purple-800 font-medium text-sm">
                    <i class="fas fa-arrow-left mr-1"></i>
                    <?= trans('rep.client_access', $lang) ?>
                </a>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="py-6 text-center text-gray-400 text-sm">
        <p>&copy; <?= date('Y') ?> Trendy Foods - <?= trans('common.all_rights_reserved', $lang) ?></p>
    </footer>

</body>
</html>