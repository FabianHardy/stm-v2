<?php
/**
 * Vue : Page de validation de commande (checkout)
 *
 * @package STM
 * @created 2025/11/17
 * @modified 2025/11/21 - Adaptation au layout public centralisé
 * @modified 2025/12/30 - Migration vers système trans() centralisé
 * @modified 2026/01/06 - Sprint 14 : Badge mode représentant
 * @modified 2026/01/09 - Sprint 16 : Support mode prospect
 */

// Sprint 16 : Détection mode prospect
$isProspectOrder = isset($_SESSION['prospect_id']) && !empty($_SESSION['prospect_id']);

if ($isProspectOrder) {
    // Mode prospect : utiliser les données prospect
    $customer = [
        'customer_number' => $_SESSION['prospect_number'] ?? '',
        'company_name' => $_SESSION['prospect_name'] ?? '',
        'email' => $_SESSION['prospect_email'] ?? '',
        'country' => $_SESSION['prospect_country'] ?? 'BE',
        'language' => $_SESSION['prospect_language'] ?? 'fr',
        'is_prospect' => true,
    ];
} elseif (isset($_SESSION['public_customer'])) {
    // Mode client normal
    $customer = $_SESSION['public_customer'];
} else {
    // Pas de session valide
    header('Location: /stm/');
    exit;
}

$lang = $customer['language'];
$uuid = $campaign['uuid'];

// Sprint 16 : URL catalogue selon le mode
$catalogUrl = $isProspectOrder ? "/stm/c/{$uuid}/prospect/catalog" : "/stm/c/{$uuid}/catalog";

// Sprint 14 : Détection mode représentant
$isRepOrder = $customer['is_rep_order'] ?? false;
$repName = $customer['rep_name'] ?? '';
$repEmail = $customer['rep_email'] ?? '';

// Sprint 14 : Variables pour l'affichage des prix (mode rep)
$showPrices = ($campaign['show_prices'] ?? 1) == 1;
$orderType = $campaign['order_type'] ?? 'W';

/**
 * Obtenir le prix d'un item selon les règles métier
 * @param array $item Item du panier
 * @param string $orderType Type de commande (W/V)
 * @return float|null Prix à afficher
 */
function getItemPriceCheckout(array $item, string $orderType): ?float {
    $prixNormal = $item['api_prix'] ?? null;
    $prixPromo = $item['api_prix_promo'] ?? null;

    if ($orderType === 'V') {
        // Type V : prix normal uniquement
        return $prixNormal ?: $prixPromo;
    } else {
        // Type W : prix promo prioritaire
        return $prixPromo ?: $prixNormal;
    }
}

/**
 * Formater un prix pour affichage
 * @param float|null $price Prix à formater
 * @return string Prix formaté ou chaîne vide
 */
function formatPriceCheckout(?float $price): string {
    if ($price === null || $price <= 0) {
        return '';
    }
    return number_format($price, 2, ',', ' ') . ' €';
}

// Titre de la page
$title = trans('checkout.title', $lang) . ' - ' . htmlspecialchars($campaign['name']);
$useAlpine = true;
$bodyAttrs = '';
$pageStyles = '';

// Récupérer les pages statiques pour le footer (utilisé par le layout)
$staticPageModel = new \App\Models\StaticPage();
$footerPages = $staticPageModel->getFooterPages($campaign['id']);

ob_start();
?>

        <!-- Header blanc avec logo + infos (cohérent avec catalog) -->
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
                            <div class="flex items-center gap-2 text-sm text-gray-600">
                                <i class="fas fa-building"></i>
                                <span><?= htmlspecialchars($customer['company_name']) ?></span>
                                <span>•</span>
                                <span><?= htmlspecialchars($customer['customer_number']) ?></span>
                                <?php if ($isRepOrder): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-700 border border-purple-200 ml-2">
                                    <i class="fas fa-user-tie mr-1"></i>
                                    <?= $lang === 'fr' ? 'Mode représentant' : 'Vertegenwoordiger' ?>
                                </span>
                                <?php elseif ($isProspectOrder): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700 border border-green-200 ml-2">
                                    <i class="fas fa-seedling mr-1"></i>
                                    <?= $lang === 'fr' ? 'Mode prospect' : 'Prospect modus' ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <?php if ($isRepOrder && !empty($repName)): ?>
                        <!-- Nom du rep connecté -->
                        <div class="hidden lg:block text-right">
                            <p class="text-sm font-medium text-gray-700"><?= htmlspecialchars($repName) ?></p>
                            <p class="text-xs text-gray-500"><?= htmlspecialchars($repEmail) ?></p>
                        </div>
                        <?php endif; ?>

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

                        <?php if ($isRepOrder): ?>
                        <!-- Changer de client (mode rep) -->
                        <a href="/stm/c/<?= $uuid ?>/rep/select-client"
                           class="hidden lg:flex items-center text-gray-600 hover:text-gray-800 transition">
                            <i class="fas fa-exchange-alt mr-2"></i>
                            <?= $lang === 'fr' ? 'Changer de client' : 'Klant wijzigen' ?>
                        </a>
                        <?php elseif ($isProspectOrder): ?>
                        <!-- Déconnexion (prospect) -->
                        <a href="/stm/c/<?= $uuid ?>/prospect/logout"
                           class="hidden lg:block text-gray-600 hover:text-gray-800 transition">
                            <i class="fas fa-sign-out-alt mr-2"></i>
                            <?= trans('common.logout', $lang) ?>
                        </a>
                        <?php else: ?>
                        <!-- Déconnexion (client normal) -->
                        <a href="/stm/c/<?= $uuid ?>"
                           class="hidden lg:block text-gray-600 hover:text-gray-800 transition">
                            <i class="fas fa-sign-out-alt mr-2"></i>
                            <?= trans('common.logout', $lang) ?>
                        </a>
                        <?php endif; ?>
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
                    <a href="<?= $catalogUrl ?>"
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
                                <a href="<?= $catalogUrl ?>"
                                   class="inline-block bg-orange-600 text-white px-6 py-2 rounded-lg hover:bg-orange-700 transition">
                                    <?= trans('checkout.back_to_catalog', $lang) ?>
                                </a>
                            </div>
                        <?php else: ?>
                            <!-- Liste des produits -->
                            <div class="space-y-4 mb-6">
                                <?php
                                $cartTotalPrice = 0;
                                foreach ($cart['items'] as $item):
                                    // Fallback image : langue actuelle -> FR -> placeholder
                                    $itemImage = !empty($item['image_' . $lang]) ? $item['image_' . $lang] : ($item['image_fr'] ?? '');
                                    $itemName = !empty($item['name_' . $lang]) ? $item['name_' . $lang] : ($item['name_fr'] ?? '');

                                    // Prix (mode rep)
                                    $itemPrice = getItemPriceCheckout($item, $orderType);
                                    $lineTotal = $itemPrice ? ($itemPrice * $item['quantity']) : 0;
                                    $cartTotalPrice += $lineTotal;
                                ?>
                                    <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                        <!-- Image produit -->
                                        <?php if (!empty($itemImage)): ?>
                                            <img src="<?= htmlspecialchars($itemImage) ?>"
                                                 alt="<?= htmlspecialchars($itemName) ?>"
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
                                                <?= htmlspecialchars($itemName) ?>
                                            </h3>
                                            <?php if ($isRepOrder && $showPrices && $itemPrice): ?>
                                            <!-- Prix unitaire (mode rep) -->
                                            <p class="text-sm text-gray-500 mt-1">
                                                <?= $lang === 'fr' ? 'Prix unit.' : 'Eenheidsprijs' ?> :
                                                <span class="font-medium text-gray-700"><?= formatPriceCheckout($itemPrice) ?></span>
                                            </p>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Quantité -->
                                        <div class="text-right">
                                            <p class="text-sm text-gray-600">
                                                <?= trans('checkout.quantity', $lang) ?>
                                            </p>
                                            <p class="text-2xl font-bold text-orange-600">
                                                <?= $item['quantity'] ?>
                                            </p>
                                            <?php if ($isRepOrder && $showPrices && $lineTotal > 0): ?>
                                            <!-- Sous-total ligne (mode rep) -->
                                            <p class="text-sm font-semibold text-blue-600 mt-1">
                                                <?= formatPriceCheckout($lineTotal) ?>
                                            </p>
                                            <?php endif; ?>
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
                                <?php if ($isRepOrder && $showPrices && $cartTotalPrice > 0): ?>
                                <!-- Total € (mode rep) -->
                                <div class="flex justify-between items-center text-lg mt-4 pt-4 border-t border-gray-200">
                                    <span class="font-semibold text-gray-700">
                                        Total :
                                    </span>
                                    <span class="text-2xl font-bold text-green-600">
                                        <?= formatPriceCheckout($cartTotalPrice) ?>
                                    </span>
                                </div>
                                <?php endif; ?>
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

                        <?php
                        // Sprint 16 : URL différente pour prospects
                        $formAction = $isProspectOrder
                            ? "/stm/c/{$uuid}/prospect/order"
                            : "/stm/c/{$uuid}/order/submit";
                        ?>
                        <form method="POST" action="<?= $formAction ?>" id="checkoutForm">
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

                            <!-- Conditions Générales de Vente (checkboxes avec modals) -->
                            <div class="mb-6 space-y-3">
                                <p class="text-sm font-medium text-gray-700 mb-3">
                                    <?= trans('checkout.conditions_label', $lang) ?>
                                    <span class="text-red-500">*</span>
                                </p>

                                <!-- CGV 1 : CGU + Vie privée (avec liens ouvrant modal) -->
                                <label class="flex items-start cursor-pointer">
                                    <input type="checkbox"
                                           name="cgv_1"
                                           required
                                           class="mt-1 h-4 w-4 text-orange-600 border-gray-300 rounded focus:ring-orange-500">
                                    <span class="ml-3 text-sm text-gray-700">
                                        <?php if ($lang === 'nl'): ?>
                                            Ik aanvaard de
                                            <button type="button" @click="footerModalOpen = true; footerModalUrl = '/stm/c/<?= $uuid ?>/page/cgu'; footerModalTitle = 'Algemene Gebruiksvoorwaarden'" class="text-blue-600 hover:text-blue-800 underline font-medium">Algemene Gebruiksvoorwaarden</button>
                                            en het
                                            <button type="button" @click="footerModalOpen = true; footerModalUrl = '/stm/c/<?= $uuid ?>/page/confidentialite'; footerModalTitle = 'Privacybeleid'" class="text-blue-600 hover:text-blue-800 underline font-medium">Privacybeleid</button>
                                        <?php else: ?>
                                            J'accepte les
                                            <button type="button" @click="footerModalOpen = true; footerModalUrl = '/stm/c/<?= $uuid ?>/page/cgu'; footerModalTitle = 'Conditions Générales d\'Utilisation'" class="text-blue-600 hover:text-blue-800 underline font-medium">Conditions Générales d'Utilisation</button>
                                            et la
                                            <button type="button" @click="footerModalOpen = true; footerModalUrl = '/stm/c/<?= $uuid ?>/page/confidentialite'; footerModalTitle = 'Politique Vie Privée'" class="text-blue-600 hover:text-blue-800 underline font-medium">Politique Vie Privée</button>
                                        <?php endif; ?>
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