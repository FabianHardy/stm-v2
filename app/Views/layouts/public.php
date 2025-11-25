<?php
/**
 * Layout Public - STM v2
 * 
 * Layout centralisé pour toutes les pages publiques (côté client)
 * Gère le head, les styles communs, le bandeau DEV et le footer
 * 
 * @package STM
 * @created 2025/11/21
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
 */

// Valeurs par défaut
$lang = $lang ?? 'fr';
$title = $title ?? 'Trendy Foods';
$useAlpine = $useAlpine ?? true;
$bodyClass = $bodyClass ?? '';
$bodyAttrs = $bodyAttrs ?? '';
$pageStyles = $pageStyles ?? '';
$pageScripts = $pageScripts ?? '';
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
<body class="bg-gray-50 <?= htmlspecialchars($bodyClass) ?>" <?= $bodyAttrs ?>>
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

    <footer class="bg-gray-800 text-gray-300 py-6 relative z-10">
        <div class="container mx-auto px-4 text-center">
            <p class="text-sm">
                © <?= date('Y') ?> Trendy Foods - 
                <?= $lang === 'fr' ? 'Tous droits réservés' : 'Alle rechten voorbehouden' ?>
            </p>
        </div>
    </footer>

    <?php if (!empty($pageScripts)): ?>
    <script><?= $pageScripts ?></script>
    <?php endif; ?>

</body>
</html>
