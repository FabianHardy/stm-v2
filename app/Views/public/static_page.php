<?php
/**
 * Vue Publique - Affichage d'une page fixe
 *
 * Affiche CGU, CGV, Mentions légales, etc. côté client
 * Supporte le mode embed (?embed=1) pour affichage dans une modal
 *
 * @package    App\Views\public
 * @author     Fabian Hardy
 * @version    1.1.0
 * @created    2025/12/30
 * @modified   2025/12/30 - Ajout mode embed pour affichage modal
 */

// Mode embed (pour iframe dans modal)
$isEmbed = isset($_GET['embed']) && $_GET['embed'] == '1';

// En mode embed, afficher uniquement le contenu
if ($isEmbed):
?>
<!DOCTYPE html>
<html lang="<?= $currentLanguage ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            background: white;
            padding: 0;
            margin: 0;
        }
        /* Styles pour le contenu HTML */
        .page-content h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .page-content h2:first-child {
            margin-top: 0;
        }
        .page-content h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #374151;
            margin-top: 1.25rem;
            margin-bottom: 0.5rem;
        }
        .page-content p {
            margin-bottom: 0.875rem;
            line-height: 1.7;
            color: #4b5563;
        }
        .page-content ul, .page-content ol {
            margin: 0.75rem 0;
            padding-left: 1.5rem;
        }
        .page-content li {
            margin-bottom: 0.375rem;
            color: #4b5563;
        }
        .page-content ul li {
            list-style-type: disc;
        }
        .page-content ol li {
            list-style-type: decimal;
        }
        .page-content em {
            color: #6b7280;
        }
        .page-content strong {
            font-weight: 600;
            color: #1f2937;
        }
        .page-content a {
            color: #ea580c;
            text-decoration: underline;
        }
        .page-content a:hover {
            color: #c2410c;
        }
    </style>
</head>
<body>
    <div class="page-content p-6">
        <?= $content ?>
    </div>
</body>
</html>
<?php
// Fin du mode embed
else:
// Mode normal avec layout complet
?>
<!DOCTYPE html>
<html lang="<?= $currentLanguage ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - Trendy Foods</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        /* Styles pour le contenu HTML */
        .page-content h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-top: 2rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .page-content h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #374151;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
        }
        .page-content p {
            margin-bottom: 1rem;
            line-height: 1.7;
            color: #4b5563;
        }
        .page-content ul, .page-content ol {
            margin: 1rem 0;
            padding-left: 1.5rem;
        }
        .page-content li {
            margin-bottom: 0.5rem;
            color: #4b5563;
        }
        .page-content ul li {
            list-style-type: disc;
        }
        .page-content ol li {
            list-style-type: decimal;
        }
        .page-content em {
            color: #6b7280;
        }
        .page-content strong {
            font-weight: 600;
            color: #1f2937;
        }
        .page-content a {
            color: #7c3aed;
            text-decoration: underline;
        }
        .page-content a:hover {
            color: #5b21b6;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">

    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo / Retour -->
                <a href="/stm/c/<?= htmlspecialchars($campaign['uuid']) ?>/catalog" class="flex items-center gap-3 text-gray-700 hover:text-orange-600">
                    <i class="fas fa-arrow-left"></i>
                    <span class="font-medium"><?= $currentLanguage === 'fr' ? 'Retour à la campagne' : 'Terug naar de campagne' ?></span>
                </a>

                <!-- Sélecteur de langue (Belgique uniquement) -->
                <?php if ($showLanguageSwitch): ?>
                <div class="flex items-center gap-2">
                    <a href="?lang=fr"
                       class="px-3 py-1.5 rounded text-sm font-medium <?= $currentLanguage === 'fr' ? 'bg-orange-100 text-orange-700' : 'text-gray-500 hover:bg-gray-100' ?>">
                        🇫🇷 FR
                    </a>
                    <a href="?lang=nl"
                       class="px-3 py-1.5 rounded text-sm font-medium <?= $currentLanguage === 'nl' ? 'bg-orange-100 text-orange-700' : 'text-gray-500 hover:bg-gray-100' ?>">
                        🇳🇱 NL
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Contenu principal -->
    <main class="flex-grow py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <!-- Titre -->
                <div class="bg-gradient-to-r from-orange-600 to-orange-700 px-6 py-8">
                    <h1 class="text-2xl md:text-3xl font-bold text-white"><?= htmlspecialchars($title) ?></h1>
                </div>

                <!-- Contenu -->
                <div class="px-6 py-8 page-content">
                    <?= $content ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-auto">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <!-- Liens pages fixes -->
                <?php if (!empty($footerPages)): ?>
                <nav class="flex flex-wrap gap-4 text-sm">
                    <?php foreach ($footerPages as $footerPage): ?>
                    <a href="/stm/c/<?= htmlspecialchars($campaign['uuid']) ?>/page/<?= htmlspecialchars($footerPage['slug']) ?>"
                       class="text-gray-500 hover:text-orange-600 <?= $footerPage['slug'] === $slug ? 'text-orange-600 font-medium' : '' ?>">
                        <?= htmlspecialchars($currentLanguage === 'nl' && !empty($footerPage['title_nl']) ? $footerPage['title_nl'] : $footerPage['title_fr']) ?>
                    </a>
                    <?php endforeach; ?>
                </nav>
                <?php endif; ?>

                <!-- Copyright -->
                <p class="text-sm text-gray-400">
                    © <?= date('Y') ?> Trendy Foods. <?= $currentLanguage === 'fr' ? 'Tous droits réservés.' : 'Alle rechten voorbehouden.' ?>
                </p>
            </div>
        </div>
    </footer>

</body>
</html>
<?php endif; ?>