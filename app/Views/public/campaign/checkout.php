<?php
/**
 * Vue : Page de validation de commande (checkout)
 *
 * @package STM
 * @created 2025/11/17
 * @modified 2025/11/21 - Adaptation au layout public centralisé
 * @modified 2025/12/30 - Migration vers système trans() centralisé
 */

if (!isset($_SESSION['public_customer'])) {
    header('Location: /stm/');
    exit;
}

$customer = $_SESSION['public_customer'];
$lang = $customer['language'];
$uuid = $campaign['uuid'];

// Titre de la page
$title = trans('checkout.title', $lang) . ' - ' . htmlspecialchars($campaign['name']);
$useAlpine = true;
$bodyAttrs = '';
$pageStyles = '';

// Récupérer les pages statiques pour le footer
$staticPageModel = new \App\Models\StaticPage();
$footerPages = $staticPageModel->getFooterPages($campaign['id']);

// Préparer les liens pour les CGV
$transLinks = trans_links($uuid);

ob_start();
?>

    <div class="content-wrapper">

        <!-- Header blanc avec logo + infos -->
        <header class="bg-white shadow-md sticky top-0 z-40">
            <div class="container mx-auto px-4 py-4">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <!-- Logo Trendy Foods -->
                        <img src="/stm/assets/images/logo.png"
                             alt="Trendy Foods"
                             class="h-12"
                             onerror="this.style.display='none'">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($campaign['name']) ?></h1>
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-building mr-1"></i>
                                <?= htmlspecialchars($customer['company_name']) ?>
                                <span class="mx-2">•</span>
                                <?= htmlspecialchars($customer['customer_number']) ?>
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <!-- Switch langue FR/NL (visible uniquement pour BE) -->
                        <?php if ($customer['country'] === 'BE'): ?>
                        <div class="hidden lg:flex bg-gray-100 rounded-lg p-1">
                            <button onclick="window.location.href='?lang=fr'"
                                    class="px-4 py-2 rounded-md <?= $lang === 'fr' ? 'bg-white text-blue-600 font-semibold shadow-sm' : 'text-gray-600 hover:bg-white hover:shadow-sm' ?> transition">
                                FR
                            </button>
                            <button onclick="window.location.href='?lang=nl'"
                                    class="px-4 py-2 rounded-md <?= $lang === 'nl' ? 'bg-white text-blue-600 font-semibold shadow-sm' : 'text-gray-600 hover:bg-white hover:shadow-sm' ?> transition">
                                NL
                            </button>
                        </div>
                        <?php endif; ?>

                        <!-- Déconnexion -->
                        <a href="/stm/c/<?= $uuid ?>"
                           class="hidden lg:block text-gray-600 hover:text-gray-800 transition">
                            <i class="fas fa-sign-out-alt mr-2"></i>
                            <?= trans('common.logout', $lang) ?>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Bande orange avec titre centré -->
        <div class="bg-gradient-to-r from-orange-600 to-orange-700 text-white shadow-lg sticky top-[72px] z-30"
             style="background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);">
            <div class="container mx-auto px-4 py-4 sm:py-6">
                <div class="flex items-center gap-2 sm:gap-4">
                    <!-- Bouton retour à gauche -->
                    <a href="/stm/c/<?= $uuid ?>/catalog"
                       class="flex-shrink-0 flex items-center text-white hover:text-orange-100 transition font-semibold">
                        <i class="fas fa-arrow-left sm:mr-2"></i>
                        <span class="hidden sm:inline"><?= trans('common.back', $lang) ?></span>
                    </a>

                    <!-- Titre centré -->
                    <h2 class="flex-1 text-base sm:text-xl md:text-2xl lg:text-4xl font-bold flex items-center justify-center text-center">
                        <i class="fas fa-check-circle mr-2 sm:mr-3 hidden sm:inline"></i>
                        <?= trans('checkout.title', $lang) ?>
                    </h2>

                    <!-- Espace vide à droite pour équilibre -->
                    <div class="flex-shrink-0 w-6 sm:w-24"></div>
                </div>
            </div>
        </div>

        <!-- Messages d'erreur/succès -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="container mx-auto px-4 mt-6">
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-lg shadow-md flex items-start">
                    <svg class="w-6 h-6 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span><?= htmlspecialchars($_SESSION['error']) ?></span>
                </div>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="container mx-auto px-4 mt-6">
                <div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-6 py-4 rounded-lg shadow-md flex items-start">
                    <svg class="w-6 h-6 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span><?= htmlspecialchars($_SESSION['success']) ?></span>
                </div>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Contenu principal -->
        <div class="container mx-auto px-4 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- Colonne gauche : Récapitulatif panier (2/3) -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <?= trans('checkout.cart_summary', $lang) ?>
                        </h2>

                        <?php if (empty($cart['items'])): ?>
                            <div class="text-center py-12">
                                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <p class="text-gray-500 mb-4">
                                    <?= trans('checkout.cart_empty', $lang) ?>
                                </p>
                                <a href="/stm/c/<?= $uuid ?>/catalog"
                                   class="inline-block bg-orange-600 text-white px-6 py-2 rounded-lg hover:bg-orange-700 transition">
                                    <?= trans('checkout.back_to_catalog', $lang) ?>
                                </a>
                            </div>
                        <?php else: ?>
                            <!-- Liste des produits -->
                            <div class="space-y-4 mb-6">
                                <?php foreach ($cart['items'] as $item): ?>
                                    <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                        <!-- Image produit -->
                                        <?php if (!empty($item['image_' . $lang])): ?>
                                            <img src="<?= htmlspecialchars($item['image_' . $lang]) ?>"
                                                 alt="<?= htmlspecialchars($item['name_' . $lang]) ?>"
                                                 class="w-24 h-24 object-contain rounded">
                                        <?php else: ?>
                                            <div class="w-24 h-24 bg-gray-200 rounded flex items-center justify-center">
                                                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Infos produit -->
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-gray-800">
                                                <?= htmlspecialchars($item['name_' . $lang]) ?>
                                            </h3>
                                        </div>

                                        <!-- Quantité -->
                                        <div class="text-right">
                                            <p class="text-sm text-gray-600">
                                                <?= trans('checkout.quantity', $lang) ?>
                                            </p>
                                            <p class="text-2xl font-bold text-orange-600">
                                                <?= $item['quantity'] ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Total articles -->
                            <div class="mt-6 pt-6 border-t border-gray-200">
                                <div class="flex justify-between items-center text-lg">
                                    <span class="font-semibold text-gray-700">
                                        <?= trans('checkout.total_items', $lang) ?> :
                                    </span>
                                    <span class="text-2xl font-bold text-orange-600">
                                        <?= array_sum(array_column($cart['items'], 'quantity')) ?>
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Colonne droite : Formulaire de validation (1/3) -->
                <?php if (!empty($cart['items'])): ?>
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-md p-6 sticky top-24">
                        <h2 class="text-xl font-bold text-gray-800 mb-6">
                            <?= trans('checkout.finalize', $lang) ?>
                        </h2>

                        <form method="POST" action="/stm/c/<?= $uuid ?>/order/submit" id="checkoutForm">
                            <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

                            <!-- Email -->
                            <div class="mb-6">
                                <label for="customer_email" class="block text-sm font-medium text-gray-700 mb-2">
                                    <?= trans('checkout.email_label', $lang) ?>
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="email"
                                       id="customer_email"
                                       name="customer_email"
                                       required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                       placeholder="exemple@email.com">
                                <p class="mt-1 text-xs text-gray-500">
                                    <?= trans('checkout.email_hint', $lang) ?>
                                </p>
                            </div>

                            <!-- Date de livraison si applicable -->
                            <?php if ($campaign['deferred_delivery'] == 1 && !empty($campaign['delivery_date'])): ?>
                                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                    <div class="flex items-start">
                                        <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <div>
                                            <p class="text-sm font-semibold text-blue-900">
                                                <?= trans('checkout.delivery_from', $lang) ?>
                                            </p>
                                            <p class="text-sm text-blue-700 mt-1">
                                                <?php
                                                $deliveryDate = new DateTime($campaign['delivery_date']);

                                                // Traduction des mois
                                                $monthsFr = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
                                                $monthsNl = ['januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december'];

                                                $day = $deliveryDate->format('d');
                                                $monthIndex = (int)$deliveryDate->format('m') - 1;
                                                $year = $deliveryDate->format('Y');

                                                $monthName = $lang === 'fr' ? $monthsFr[$monthIndex] : $monthsNl[$monthIndex];
                                                echo "$day $monthName $year";
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Conditions Générales de Vente (checkboxes via trans()) -->
                            <div class="mb-6 space-y-3">
                                <p class="text-sm font-medium text-gray-700 mb-3">
                                    <?= trans('checkout.conditions_label', $lang) ?>
                                    <span class="text-red-500">*</span>
                                </p>

                                <!-- CGV 1 : CGU + Vie privée (avec liens dynamiques) -->
                                <label class="flex items-start cursor-pointer">
                                    <input type="checkbox"
                                           name="cgv_1"
                                           required
                                           class="mt-1 h-4 w-4 text-orange-600 border-gray-300 rounded focus:ring-orange-500">
                                    <span class="ml-3 text-sm text-gray-700">
                                        <?= trans('checkout.cgv_1', $lang, $transLinks) ?>
                                    </span>
                                </label>

                                <!-- CGV 2 : Vérification commande -->
                                <label class="flex items-start cursor-pointer">
                                    <input type="checkbox"
                                           name="cgv_2"
                                           required
                                           class="mt-1 h-4 w-4 text-orange-600 border-gray-300 rounded focus:ring-orange-500">
                                    <span class="ml-3 text-sm text-gray-700">
                                        <?= trans('checkout.cgv_2', $lang) ?>
                                    </span>
                                </label>

                                <!-- CGV 3 : Commande définitive -->
                                <label class="flex items-start cursor-pointer">
                                    <input type="checkbox"
                                           name="cgv_3"
                                           required
                                           class="mt-1 h-4 w-4 text-orange-600 border-gray-300 rounded focus:ring-orange-500">
                                    <span class="ml-3 text-sm text-gray-700">
                                        <?= trans('checkout.cgv_3', $lang) ?>
                                    </span>
                                </label>
                            </div>

                            <!-- Bouton de validation VERT -->
                            <button type="submit"
                                    class="w-full bg-green-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-green-700 transition-colors shadow-lg hover:shadow-xl">
                                <span class="flex items-center justify-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <?= trans('checkout.confirm_button', $lang) ?>
                                </span>
                            </button>

                            <!-- Note de sécurité -->
                            <p class="text-xs text-gray-500 mt-4 text-center">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                <?= trans('checkout.data_secure', $lang) ?>
                            </p>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- Footer avec liens vers pages statiques -->
    <?php if (!empty($footerPages)): ?>
    <footer class="bg-white border-t border-gray-200 mt-8">
        <div class="container mx-auto px-4 py-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <!-- Liens pages statiques -->
                <nav class="flex flex-wrap justify-center md:justify-start gap-4 text-sm">
                    <?php foreach ($footerPages as $footerPage): ?>
                    <a href="/stm/c/<?= htmlspecialchars($uuid) ?>/page/<?= htmlspecialchars($footerPage['slug']) ?>"
                       target="_blank"
                       class="text-gray-500 hover:text-orange-600 transition">
                        <?= htmlspecialchars($lang === 'nl' && !empty($footerPage['title_nl']) ? $footerPage['title_nl'] : $footerPage['title_fr']) ?>
                    </a>
                    <?php endforeach; ?>
                </nav>

                <!-- Copyright -->
                <p class="text-sm text-gray-400 text-center md:text-right">
                    <?= trans('footer.copyright', $lang, ['year' => date('Y')]) ?>
                </p>
            </div>
        </div>
    </footer>
    <?php endif; ?>

    <!-- Script de validation côté client -->
    <script>
    document.getElementById('checkoutForm')?.addEventListener('submit', function(e) {
        // Vérifier que toutes les CGV requises sont cochées
        const requiredCheckboxes = document.querySelectorAll('input[type="checkbox"][required]');
        let allChecked = true;

        requiredCheckboxes.forEach(function(checkbox) {
            if (!checkbox.checked) {
                allChecked = false;
            }
        });

        if (!allChecked) {
            e.preventDefault();
            alert('<?= trans_e('checkout.js_accept_conditions', $lang) ?>');
            return false;
        }

        // Vérifier l'email
        const email = document.getElementById('customer_email').value;
        if (!email || !email.includes('@')) {
            e.preventDefault();
            alert('<?= trans_e('checkout.js_invalid_email', $lang) ?>');
            return false;
        }

        // Confirmation finale
        if (!confirm('<?= trans_e('checkout.js_confirm_order', $lang) ?>')) {
            e.preventDefault();
            return false;
        }
    });
    </script>

<?php
$content = ob_get_clean();

$pageScripts = '';

require __DIR__ . '/../../layouts/public.php';
?>