<?php
/**
 * Vue : Accès campagne publique - Page d'identification client
 * 
 * @package STM
 * @created 2025/11/14
 * @modified 2025/11/21 - Adaptation au layout public centralisé
 * @modified 2025/12/30 - Migration vers système trans() centralisé
 */

// ========================================
// PRÉPARATION DES DONNÉES
// ========================================

// Langue : paramètre GET ou FR par défaut, forcé FR pour LU
$requestedLang = $_GET['lang'] ?? 'fr';
$lang = in_array($requestedLang, ['fr', 'nl'], true) ? $requestedLang : 'fr';
if ($campaign['country'] === 'LU') {
    $lang = 'fr';
}

// Récupérer les erreurs de session
$error = \Core\Session::get('error');
\Core\Session::remove('error');

// Titre et description selon la langue
$campaignTitle = $lang === 'fr' ? $campaign['title_fr'] : $campaign['title_nl'];
$campaignDescription = $lang === 'fr' ? $campaign['description_fr'] : $campaign['description_nl'];

// UUID pour les liens
$uuid = $campaign['uuid'];

// Variables pour le layout
$title = $campaignTitle;
$useAlpine = false;

// Récupérer les pages statiques pour le footer (utilisé par le layout)
$staticPageModel = new \App\Models\StaticPage();
$footerPages = $staticPageModel->getFooterPages($campaign['id']);

// ========================================
// CONTENU DE LA PAGE
// ========================================
ob_start();
?>

<!-- Header -->
<?php 
$showClient = false; // Pas de client connecté sur cette page
include __DIR__ . '/../../components/public/header.php'; 
?>

<!-- Bande campagne -->
<?php
$barTitle = $campaignTitle;
$barSubtitle = $campaignDescription;
$barColor = 'blue';
$showDates = true;
$showCountry = true;
include __DIR__ . '/../../components/public/campaign_bar.php';
?>

<!-- Contenu principal -->
<main class="container mx-auto px-4 py-12 relative z-10">
    <div class="max-w-md mx-auto">
        
        <!-- Formulaire d'identification -->
        <div class="bg-white rounded-lg shadow-lg p-8 mb-8">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-4">
                    <i class="fas fa-user-check text-3xl text-blue-600"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">
                    <?= trans('show.client_access', $lang) ?>
                </h3>
                <p class="text-gray-600">
                    <?php if ($promotionsCount === 1): ?>
                        <?= trans('show.identify_single', $lang) ?>
                    <?php else: ?>
                        <?= trans('show.identify_plural', $lang) ?>
                    <?php endif; ?>
                </p>
            </div>

            <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-lg">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-circle text-red-500 mt-0.5 mr-3"></i>
                    <p class="text-sm text-red-700"><?= htmlspecialchars($error) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" action="/stm/c/<?= htmlspecialchars($uuid) ?>/identify" class="space-y-6">
                <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="language" id="language-input" value="<?= $lang ?>">

                <!-- Numéro client -->
                <div>
                    <label for="customer_number" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-id-card mr-2 text-blue-600"></i>
                        <?= trans('show.customer_number', $lang) ?>
                        <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="customer_number" name="customer_number" required
                        placeholder="<?= trans('show.customer_placeholder', $lang) ?>"
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                    <p class="mt-2 text-sm text-gray-500">
                        <i class="fas fa-info-circle mr-1"></i>
                        <?= trans('show.customer_hint', $lang) ?>
                    </p>
                </div>

                <!-- Pays (si BOTH) -->
                <?php if ($campaign['country'] === 'BOTH'): ?>
                <div>
                    <label for="country" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-flag mr-2 text-blue-600"></i>
                        <?= trans('show.country', $lang) ?>
                        <span class="text-red-500">*</span>
                    </label>
                    <select id="country" name="country" required
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        <option value="BE"><?= trans('show.belgium', $lang) ?></option>
                        <option value="LU"><?= trans('show.luxembourg', $lang) ?></option>
                    </select>
                </div>
                <?php else: ?>
                <input type="hidden" name="country" value="<?= htmlspecialchars($campaign['country']) ?>">
                <?php endif; ?>

                <!-- Bouton submit -->
                <button type="submit" 
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-6 rounded-lg transition-all duration-200 flex items-center justify-center space-x-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <span><?= trans('show.access_promotions', $lang) ?></span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
        </div>

        <!-- Section aide -->
        <?php 
        $country = $campaign['country'] === 'BOTH' ? 'BE' : $campaign['country'];
        include __DIR__ . '/../../components/public/help_box.php'; 
        ?>

    </div>
</main>

<?php
$content = ob_get_clean();

// Pas de scripts spécifiques pour cette page
$pageScripts = '';
$pageStyles = '';

// Inclure le layout
require __DIR__ . '/../../layouts/public.php';
?>
