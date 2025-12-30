<?php
/**
 * Layout Public - STM v2
 *
 * Layout centralisé pour toutes les pages publiques (côté client)
 * Gère le head, les styles communs, le bandeau DEV et le footer
 *
 * @package STM
 * @created 2025/11/21
 * @modified 2025/12/30 - Ajout footer avec pages statiques + modal
 *
 * Variables attendues :
 * - $title       (string)  Titre de la page (obligatoire)
 * - $lang        (string)  Langue : 'fr' ou 'nl' (obligatoire)
 * - $content     (string)  Contenu HTML principal (obligatoire)
 * - $pageStyles  (string)  CSS additionnels (optionnel)
 * - $pageScripts (string)  JS en fin de page (optionnel)
 * - $useAlpine   (bool)    Inclure Alpine.js ? (défaut: true)
 * - $bodyClass   (string)  Classes CSS pour <body> (optionnel)
 * - $bodyAttrs   (string)  Attributs <body> ex: x-data="..." (optionnel)
 * - $footerPages (array)   Pages statiques pour le footer (optionnel)
 * - $campaign    (array)   Campagne avec uuid (optionnel, requis si footerPages)
 */

// Valeurs par défaut
$lang = $lang ?? 'fr';
$title = $title ?? 'Trendy Foods';
$useAlpine = $useAlpine ?? true;
$bodyClass = $bodyClass ?? '';
$bodyAttrs = $bodyAttrs ?? '';
$pageStyles = $pageStyles ?? '';
$pageScripts = $pageScripts ?? '';
$footerPages = $footerPages ?? [];
$campaignUuid = $campaign['uuid'] ?? '';

// Si on a des pages footer, on a besoin d'Alpine.js pour la modal
if (!empty($footerPages)) {
    $useAlpine = true;
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars($title) ?> - Trendy Foods</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <?php if ($useAlpine): ?>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <?php endif; ?>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* Alpine.js cloak */
        [x-cloak] { display: none !important; }

        /* Structure flexbox pour footer en bas */
        html { scroll-behavior: smooth; height: 100%; }
        body { display: flex; flex-direction: column; min-height: 100vh; }

        /* Fond Trendy Foods en arrière-plan */
        body::before {
            content: '';
            position: fixed;
            bottom: 0;
            right: 0;
            width: 400px;
            height: 400px;
            background: url('/stm/assets/images/fond.png') no-repeat;
            background-size: contain;
            background-position: bottom right;
            opacity: 0.6;
            pointer-events: none;
            z-index: -1;
        }

        .content-wrapper { flex: 1; }
        footer { margin-top: auto; }

        <?= $pageStyles ?>
    </style>
</head>
<body class="bg-gray-50 <?= htmlspecialchars($bodyClass) ?>"
      <?php if (!empty($footerPages)): ?>
      x-data="{ footerModalOpen: false, footerModalUrl: '', footerModalTitle: '' }"
      <?php endif; ?>
      <?= $bodyAttrs ?>>
<?php
// Bandeau DEV
$appEnv = $_ENV['APP_ENV'] ?? 'production';
if ($appEnv === 'development'):
?>
<div id="dev-banner-public" style="background:linear-gradient(90deg,#f59e0b,#d97706);color:white;text-align:center;padding:6px 15px;font-weight:500;font-size:12px;position:fixed;top:0;left:0;right:0;z-index:99999;box-shadow:0 1px 5px rgba(0,0,0,0.2);">
    🔧 MODE TEST — Environnement de développement
    <button onclick="this.parentElement.style.display='none'" style="margin-left:15px;background:rgba(255,255,255,0.2);border:1px solid rgba(255,255,255,0.4);color:white;padding:2px 10px;border-radius:3px;cursor:pointer;font-size:11px;">✕</button>
</div>
<style>body{padding-top:32px!important;}</style>
<?php endif; ?>

    <div class="content-wrapper">
        <?= $content ?>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-gray-300 py-6 relative z-10">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

                <?php if (!empty($footerPages) && !empty($campaignUuid)): ?>
                <!-- Liens pages statiques (ouvrent modal) -->
                <nav class="flex flex-wrap justify-center md:justify-start gap-4 text-sm">
                    <?php foreach ($footerPages as $footerPage):
                        $pageTitle = $lang === 'nl' && !empty($footerPage['title_nl']) ? $footerPage['title_nl'] : $footerPage['title_fr'];
                        $escapedTitle = htmlspecialchars(addslashes($pageTitle), ENT_QUOTES);
                    ?>
                    <button type="button"
                            @click="footerModalOpen = true; footerModalUrl = '/stm/c/<?= htmlspecialchars($campaignUuid) ?>/page/<?= htmlspecialchars($footerPage['slug']) ?>'; footerModalTitle = '<?= $escapedTitle ?>'"
                            class="text-gray-400 hover:text-white transition cursor-pointer">
                        <?= htmlspecialchars($pageTitle) ?>
                    </button>
                    <?php endforeach; ?>
                </nav>
                <?php endif; ?>

                <!-- Copyright -->
                <p class="text-sm text-gray-400 text-center <?= !empty($footerPages) ? 'md:text-right' : '' ?>">
                    © <?= date('Y') ?> Trendy Foods.
                    <?= $lang === 'fr' ? 'Tous droits réservés.' : 'Alle rechten voorbehouden.' ?>
                </p>
            </div>
        </div>
    </footer>

    <?php if (!empty($footerPages)): ?>
    <!-- Modal pour afficher les pages statiques -->
    <div x-show="footerModalOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;"
         x-cloak>

        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-50" @click="footerModalOpen = false"></div>

        <!-- Modal Content -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="footerModalOpen"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative bg-white rounded-xl shadow-2xl w-full max-w-3xl max-h-[85vh] overflow-hidden"
                 @click.away="footerModalOpen = false">

                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-semibold text-gray-800" x-text="footerModalTitle"></h3>
                    <button @click="footerModalOpen = false"
                            class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-200 rounded-full transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Body avec iframe -->
                <div class="overflow-y-auto" style="max-height: calc(85vh - 130px);">
                    <iframe :src="footerModalUrl + '?embed=1'"
                            class="w-full border-0"
                            style="min-height: 400px; height: 60vh;"></iframe>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 text-right">
                    <button @click="footerModalOpen = false"
                            class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition font-medium">
                        <?= $lang === 'fr' ? 'Fermer' : 'Sluiten' ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($pageScripts)): ?>
    <script><?= $pageScripts ?></script>
    <?php endif; ?>

</body>
</html>