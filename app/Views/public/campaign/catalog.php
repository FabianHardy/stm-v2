<?php
/**
 * Vue : Catalogue produits - Page principale client
 *
 * @package STM
 * @created 2025/11/14
 * @modified 2025/11/21 - Adaptation au layout public centralisé
 * @modified 2025/12/30 - Migration vers système trans() centralisé
 * @modified 2026/01/05 - Sprint 14 : Affichage prix conditionnel pour reps
 */

// ========================================
// VARIABLES POUR LE LAYOUT
// ========================================

$lang = $customer["language"];
$uuid = $campaign["uuid"];
$title = htmlspecialchars($campaign["name"]) . " - " . trans('catalog.title', $lang);
$useAlpine = true;
$bodyAttrs = 'x-data="cartManager()" x-init="init()"';

// ========================================
// SPRINT 14 : Détection mode rep et affichage prix
// ========================================
$isRepOrder = $customer["is_rep_order"] ?? false;
$showPrices = ($campaign["show_prices"] ?? 1) == 1;
$orderType = $campaign["order_type"] ?? "W"; // W = normal, V = prospection

/**
 * Formater un prix pour affichage
 * @param float|null $price Prix à formater
 * @return string Prix formaté ou chaîne vide
 */
function formatPrice(?float $price): string {
    if ($price === null || $price <= 0) {
        return '';
    }
    return number_format($price, 2, ',', ' ') . ' €';
}

/**
 * Générer le bloc HTML d'affichage des prix selon les règles métier
 *
 * Règles :
 * - Client direct : jamais de prix
 * - Rep + show_prices OFF : pas de prix
 * - Rep + show_prices ON + Type W : prix promo + prix normal barré (si promo)
 * - Rep + show_prices ON + Type V : prix normal seul (jamais barré)
 *
 * @param array $product Données du produit (avec api_prix, api_prix_promo)
 * @param bool $isRepOrder Est-ce une commande rep ?
 * @param bool $showPrices Option show_prices de la campagne
 * @param string $orderType Type de commande (W/V)
 * @return string HTML du bloc prix
 */
function renderPriceBlock(array $product, bool $isRepOrder, bool $showPrices, string $orderType): string {
    // Pas de prix pour les clients directs ou si show_prices désactivé
    if (!$isRepOrder || !$showPrices) {
        return '';
    }

    $prixNormal = $product["api_prix"] ?? null;
    $prixPromo = $product["api_prix_promo"] ?? null;

    // Pas de prix disponible
    if (!$prixNormal && !$prixPromo) {
        return '';
    }

    $html = '<div class="mt-3 p-2 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg border border-blue-100">';

    if ($orderType === 'V') {
        // Type V (prospection) : prix normal seul, jamais barré
        $displayPrice = $prixNormal ?: $prixPromo;
        if ($displayPrice) {
            $html .= '<div class="text-center">';
            $html .= '<span class="text-lg font-bold text-blue-700">' . formatPrice($displayPrice) . '</span>';
            $html .= '</div>';
        }
    } else {
        // Type W (normal) : prix promo + prix normal barré si différent
        if ($prixPromo && $prixNormal && $prixPromo < $prixNormal) {
            // Promo active : afficher promo + normal barré
            $html .= '<div class="flex items-center justify-center gap-2">';
            $html .= '<span class="text-lg font-bold text-green-600">' . formatPrice($prixPromo) . '</span>';
            $html .= '<span class="text-sm text-gray-400 line-through">' . formatPrice($prixNormal) . '</span>';
            $html .= '</div>';
        } elseif ($prixPromo) {
            // Seulement prix promo
            $html .= '<div class="text-center">';
            $html .= '<span class="text-lg font-bold text-blue-700">' . formatPrice($prixPromo) . '</span>';
            $html .= '</div>';
        } elseif ($prixNormal) {
            // Seulement prix normal
            $html .= '<div class="text-center">';
            $html .= '<span class="text-lg font-bold text-blue-700">' . formatPrice($prixNormal) . '</span>';
            $html .= '</div>';
        }
    }

    $html .= '</div>';
    return $html;
}

// Récupérer les pages statiques pour le footer (utilisé par le layout)
$staticPageModel = new \App\Models\StaticPage();
$footerPages = $staticPageModel->getFooterPages($campaign['id']);

// Styles spécifiques
$pageStyles = <<<'CSS'
    .lightbox-overlay { background: rgba(0, 0, 0, 0.9); }
    .scroll-mt { scroll-margin-top: 10rem; }
    .category-nav { overflow-x: auto; scrollbar-width: thin; scroll-behavior: smooth; }
    .category-nav::-webkit-scrollbar { height: 8px; }
    .category-nav::-webkit-scrollbar-thumb { background: #006eb8; border-radius: 4px; }
    .category-nav::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
    .category-btn { transition: all 0.2s; border: 2px solid transparent; }
    .category-btn:hover { border-color: #006eb8; transform: translateY(-2px); }
    .category-btn.active { border-color: #e73029; box-shadow: 0 4px 6px rgba(231, 48, 41, 0.3); }
    .product-card { transition: transform 0.2s, box-shadow 0.2s; }
    .product-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
CSS;

ob_start();
?>
    <!-- Header -->
    <header class="bg-white shadow-md sticky top-0 z-40">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <!-- Logo Trendy Foods -->
                    <img src="/stm/assets/images/logo.png" alt="Trendy Foods" class="h-12" onerror="this.style.display='none'">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($campaign["name"]) ?></h1>
                        <p class="text-sm text-gray-600">
                            <i class="fas fa-building mr-1"></i>
                            <?= htmlspecialchars($customer["company_name"]) ?>
                            <span class="mx-2">•</span>
                            <?= htmlspecialchars($customer["customer_number"]) ?>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <?php // SPRINT 14 : Badge mode rep ?>
                    <?php if ($isRepOrder): ?>
                    <div class="hidden lg:flex items-center bg-purple-100 text-purple-700 px-3 py-1.5 rounded-full text-sm font-medium">
                        <i class="fas fa-user-tie mr-2"></i>
                        <?= $lang === 'fr' ? 'Mode représentant' : 'Vertegenwoordiger modus' ?>
                    </div>
                    <?php endif; ?>

                    <!-- Switch langue FR/NL (visible uniquement pour campagnes BE) -->
                    <?php if ($campaign["country"] === "BE"): ?>
                    <div class="hidden lg:flex bg-gray-100 rounded-lg p-1">
                        <button onclick="switchLanguage('fr')"
                                class="px-4 py-2 rounded-md <?= $lang === "fr"
                                    ? "bg-white text-blue-600 font-semibold shadow-sm"
                                    : "text-gray-600 hover:bg-white hover:shadow-sm" ?> transition">
                            FR
                        </button>
                        <button onclick="switchLanguage('nl')"
                                class="px-4 py-2 rounded-md <?= $lang === "nl"
                                    ? "bg-white text-blue-600 font-semibold shadow-sm"
                                    : "text-gray-600 hover:bg-white hover:shadow-sm" ?> transition">
                            NL
                        </button>
                    </div>
                    <?php endif; ?>

                    <!-- Bouton panier mobile -->
                    <button @click="toggleCartMobile()" class="lg:hidden relative bg-blue-600 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-shopping-cart"></i>
                        <span x-show="cartItemCount > 0"
                              x-text="cartItemCount"
                              class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-6 h-6 flex items-center justify-center font-bold">
                        </span>
                    </button>

                    <!-- Déconnexion / Changer client (rep) -->
                    <?php if ($isRepOrder): ?>
                    <a href="/stm/c/<?= $uuid ?>/rep/select-client" class="hidden lg:block text-purple-600 hover:text-purple-800">
                        <i class="fas fa-exchange-alt mr-2"></i><?= $lang === 'fr' ? 'Changer de client' : 'Klant wijzigen' ?>
                    </a>
                    <?php else: ?>
                    <a href="/stm/c/<?= $uuid ?>" class="hidden lg:block text-gray-600 hover:text-gray-800">
                        <i class="fas fa-sign-out-alt mr-2"></i><?= trans('common.logout', $lang) ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation catégories horizontale (sticky) -->
    <nav class="bg-white border-b sticky top-[72px] z-30 shadow-sm">
        <div class="container mx-auto px-4">
            <div class="category-nav flex gap-3 py-3">
                <?php foreach ($categories as $category): ?>
                <?php
                // Calculer la couleur de texte optimale (blanc ou noir) selon la luminosité du fond
                $color = $category["color"];
                $hex = ltrim($color, "#");
                $r = hexdec(substr($hex, 0, 2));
                $g = hexdec(substr($hex, 2, 2));
                $b = hexdec(substr($hex, 4, 2));
                // Formule de luminosité perçue
                $luminosity = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
                $textColor = $luminosity > 0.5 ? "#000000" : "#FFFFFF";
                ?>
                <a href="#category-<?= $category["id"] ?>"
                   class="category-btn flex items-center gap-2 pl-2 pr-6 py-2.5 rounded-lg font-medium whitespace-nowrap"
                   id="cat-btn-<?= $category["id"] ?>"
                   style="background-color: <?= htmlspecialchars($category["color"]) ?>CC; color: <?= $textColor ?>;">
                    <?php if (!empty($category["icon_path"])): ?>
                        <img src="<?= htmlspecialchars($category["icon_path"]) ?>"
                             alt="<?= htmlspecialchars($category["name_" . $lang]) ?>"
                             class="w-5 h-5 object-contain"
                             onerror="this.style.display='none';">
                    <?php else: ?>
                        <i class="fas fa-tag w-5"></i>
                    <?php endif; ?>
                    <span><?= htmlspecialchars($category["name_" . $lang]) ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </nav>

    <!-- Layout principal -->
    <div class="container mx-auto px-4 py-6">
        <div class="flex flex-col lg:flex-row gap-6">

            <!-- Zone produits (gauche) -->
            <div class="flex-1">

                <?php foreach ($categories as $category): ?>

                <?php
                // Filtrer les produits commandables uniquement AVANT d'afficher la catégorie
                $orderableProducts = array_filter($category["products"], function ($p) {
                    return $p["is_orderable"] === true;
                });
                $productCount = count($orderableProducts);

                // Si aucun produit disponible, ne pas afficher la catégorie
                if ($productCount === 0) {
                    continue;
                }
                ?>

                <!-- Section catégorie -->
                <section id="category-<?= $category["id"] ?>" class="mb-12 scroll-mt">
                    <h2 class="text-2xl font-bold mb-6 flex items-center">
                        <span class="w-1 h-8 mr-3 rounded" style="background-color: <?= htmlspecialchars($category["color"]) ?>;"></span>
                        <?= htmlspecialchars($category["name_" . $lang]) ?>
                    </h2>

                    <!-- Grid produits - Dynamique selon nombre de produits -->
                    <div class="grid <?= $productCount === 1 ? "grid-cols-1 max-w-4xl mx-auto" : "grid-cols-1 lg:grid-cols-2" ?> gap-6">

                        <?php foreach ($orderableProducts as $product): ?>
                        <!-- Card produit -->
                        <div class="product-card bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">

                            <!-- Image A4 paysage -->
                            <?php
                            $productImage = $product["image_" . $lang] ?? $product["image_fr"];
                            $productName = $product["name_" . $lang];
                            ?>
                            <div class="relative bg-gray-100 cursor-pointer" style="height: <?= $productCount === 1 ? "450px" : "213px" ?>;" @click="openLightbox('<?= htmlspecialchars($productImage) ?>')">
                                <?php if (!empty($productImage)): ?>
                                    <img src="<?= htmlspecialchars($productImage) ?>"
                                         alt="<?= htmlspecialchars($productName) ?>"
                                         class="w-full h-full object-contain p-4">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                                        <i class="fas fa-image text-6xl"></i>
                                    </div>
                                <?php endif; ?>

                                <!-- Icône zoom -->
                                <div class="absolute top-2 right-2 bg-white bg-opacity-90 rounded-full p-2 shadow-lg hover:bg-white transition">
                                    <i class="fas fa-search-plus text-gray-600"></i>
                                </div>

                                <!-- Badge ÉPUISÉ si quotas à 0 -->
                                <?php if (!$product["is_orderable"]): ?>
                                <div class="absolute top-2 left-2 bg-gray-600 text-white px-3 py-1 rounded-full text-sm font-bold shadow-lg">
                                    <?= trans('catalog.sold_out_badge', $lang) ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Infos produit -->
                            <div class="p-4">
                                <!-- Titre 12px uppercase -->
                                <h3 class="font-bold text-gray-800 mb-2 line-clamp-2 uppercase" style="font-size: 12px; line-height: 1.3; min-height: 32px;">
                                    <?= htmlspecialchars($productName) ?>
                                </h3>

                                <!-- SPRINT 14 : Bloc prix (uniquement pour reps) -->
                                <?= renderPriceBlock($product, $isRepOrder, $showPrices, $orderType) ?>

                                <!-- Quotas sur 1 SEULE LIGNE -->
                                <?php if (!is_null($product["max_per_customer"]) && $product["is_orderable"]): ?>
                                <div class="text-sm mb-3 flex items-center justify-between">
                                    <span class="flex items-center">
                                        <i class="fas fa-box w-4 text-blue-600 mr-1"></i>
                                        <span class="font-semibold text-blue-600">
                                            <?= trans('catalog.maximum', $lang) ?> : <?= $product["max_per_customer"] ?> <?= $product["max_per_customer"] > 1 ? trans('catalog.units', $lang) : trans('catalog.unit', $lang) ?>
                                        </span>
                                    </span>
                                    <span class="flex items-center">
                                        <?php if ($product["available_for_customer"] > 1): ?>
                                            <i class="fas fa-check-circle w-4 text-green-600 mr-1"></i>
                                            <span class="font-semibold text-green-600">
                                                <?= trans('catalog.remaining', $lang) ?> : <?= $product["available_for_customer"] ?> <?= trans('catalog.units', $lang) ?>
                                            </span>
                                        <?php elseif ($product["available_for_customer"] == 1): ?>
                                            <i class="fas fa-exclamation-circle w-4 text-orange-600 mr-1"></i>
                                            <span class="font-semibold text-orange-600">
                                                <?= trans('catalog.remaining', $lang) ?> : 1 <?= trans('catalog.unit', $lang) ?>
                                            </span>
                                        <?php else: ?>
                                            <i class="fas fa-times-circle w-4 text-red-600 mr-1"></i>
                                            <span class="font-semibold text-red-600">
                                                <?= trans('catalog.remaining', $lang) ?> : 0 <?= trans('catalog.unit', $lang) ?>
                                            </span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <?php endif; ?>

                                <!-- Alerte si quota global proche -->
                                <?php if (!is_null($product["max_total"]) && $product["available_global"] <= 10 && $product["available_global"] > 0): ?>
                                <div class="bg-amber-50 border-l-4 border-amber-400 p-2 mb-3 text-xs">
                                    <i class="fas fa-exclamation-triangle text-amber-600 mr-1"></i>
                                    <span class="text-amber-800">
                                        <?= trans('catalog.limited_stock', $lang) ?> : <?= $product["available_global"] ?> <?= trans('catalog.remaining_global', $lang) ?>
                                    </span>
                                </div>
                                <?php endif; ?>

                                <!-- Boutons action -->
                                <?php if ($product["is_orderable"]): ?>
                                <div class="flex items-center gap-3">
                                    <input
                                        type="number"
                                        id="qty-<?= $product["id"] ?>"
                                        value="1"
                                        min="1"
                                        max="<?= $product["max_orderable"] ?>"
                                        class="w-20 px-3 py-2 border border-gray-300 rounded-lg text-center focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                    >
                                    <button
                                        @click="addToCart(<?= $product["id"] ?>, <?= $product["max_orderable"] ?>)"
                                        class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg transition shadow-md hover:shadow-lg flex items-center justify-center"
                                    >
                                        <i class="fas fa-shopping-cart mr-2"></i>
                                        <?= trans('catalog.add_to_cart', $lang) ?>
                                    </button>
                                </div>
                                <?php else: ?>
                                <button
                                    disabled
                                    class="w-full bg-gray-300 text-gray-500 font-semibold py-2 px-4 rounded-lg cursor-not-allowed flex items-center justify-center"
                                >
                                    <i class="fas fa-ban mr-2"></i>
                                    <?= trans('catalog.sold_out', $lang) ?>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <?php endforeach; ?>
            </div>

            <!-- Panier sidebar (droite) -->
            <div class="lg:w-80 hidden lg:block">
                <div class="bg-white rounded-lg shadow-lg p-6 sticky top-40">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-800 flex items-center">
                            <i class="fas fa-shopping-cart mr-2"></i>
                            <?= trans('catalog.my_cart', $lang) ?>
                            <span x-show="cartItemCount > 0"
                                  x-text="cartItemCount"
                                  class="ml-2 bg-red-600 text-white text-sm px-2 py-1 rounded-full">
                            </span>
                        </h3>
                    </div>

                    <!-- Items panier -->
                    <div class="space-y-3 mb-4 max-h-96 overflow-y-auto">
                        <template x-if="cart.items.length === 0">
                            <div class="text-center py-8 text-gray-400">
                                <i class="fas fa-shopping-basket text-4xl mb-2"></i>
                                <p class="text-sm"><?= trans('catalog.cart_empty', $lang) ?></p>
                            </div>
                        </template>

                        <template x-for="item in cart.items" :key="item.product_id">
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <!-- Vignette + Nom du produit -->
                                <div class="flex items-start gap-2 mb-2">
                                    <template x-if="item.image_<?= $lang ?>">
                                        <img :src="item.image_<?= $lang ?>"
                                             :alt="item.name_<?= $lang ?>"
                                             class="w-12 h-12 object-contain rounded bg-white flex-shrink-0">
                                    </template>
                                    <p class="text-sm font-medium text-gray-800 line-clamp-2" x-text="item.name_<?= $lang ?>"></p>
                                </div>

                                <!-- Contrôles quantité + poubelle -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <button @click="updateQuantity(item.product_id, item.quantity - 1)"
                                                class="w-7 h-7 rounded bg-gray-200 hover:bg-gray-300 flex items-center justify-center">
                                            <i class="fas fa-minus text-xs"></i>
                                        </button>
                                        <span class="font-bold text-lg" x-text="item.quantity"></span>
                                        <button @click="updateQuantity(item.product_id, item.quantity + 1)"
                                                class="w-7 h-7 rounded bg-gray-200 hover:bg-gray-300 flex items-center justify-center">
                                            <i class="fas fa-plus text-xs"></i>
                                        </button>
                                    </div>
                                    <button @click="removeFromCart(item.product_id)"
                                            class="text-red-600 hover:text-red-700">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Total -->
                    <div class="border-t pt-4 mb-4" x-show="cart.items.length > 0">
                        <div class="flex justify-between text-lg font-bold">
                            <span><?= trans('catalog.total_items', $lang) ?> :</span>
                            <span class="text-blue-600" x-text="cartItemCount"></span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <template x-if="cart.items.length > 0">
                        <div>
                            <button @click="validateOrder()"
                                    class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg mb-2 transition shadow-md hover:shadow-lg flex items-center justify-center">
                                <i class="fas fa-check mr-2"></i>
                                <?= trans('catalog.validate_order', $lang) ?>
                            </button>
                            <button @click="clearCart()"
                                    class="w-full bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 rounded-lg transition flex items-center justify-center">
                                <i class="fas fa-trash mr-2"></i>
                                <?= trans('catalog.clear_cart', $lang) ?>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

        </div>
    </div>

    <!-- Panier mobile (modal plein écran) -->
    <div x-show="showCartMobile"
         x-transition
         class="fixed inset-0 bg-white z-50 overflow-y-auto lg:hidden"
         style="display: none;">

        <!-- Header modal -->
        <div class="bg-blue-600 text-white px-4 py-4 flex items-center justify-between sticky top-0">
            <h3 class="text-xl font-bold flex items-center">
                <i class="fas fa-shopping-cart mr-2"></i>
                <?= trans('catalog.my_cart', $lang) ?>
                <span x-show="cartItemCount > 0"
                      x-text="'(' + cartItemCount + ')'"
                      class="ml-2">
                </span>
            </h3>
            <button @click="toggleCartMobile()" class="text-white text-2xl">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Contenu panier -->
        <div class="p-4">
            <template x-if="cart.items.length === 0">
                <div class="text-center py-12 text-gray-400">
                    <i class="fas fa-shopping-basket text-6xl mb-4"></i>
                    <p><?= trans('catalog.cart_empty', $lang) ?></p>
                    <button @click="toggleCartMobile()"
                            class="mt-4 bg-blue-600 text-white px-6 py-2 rounded-lg">
                        <?= trans('catalog.continue_shopping', $lang) ?>
                    </button>
                </div>
            </template>

            <div class="space-y-4">
                <template x-for="item in cart.items" :key="item.product_id">
                    <div class="bg-white border rounded-lg p-4">
                        <!-- Vignette + Nom du produit -->
                        <div class="flex items-start gap-3 mb-3">
                            <template x-if="item.image_<?= $lang ?>">
                                <img :src="item.image_<?= $lang ?>"
                                     :alt="item.name_<?= $lang ?>"
                                     class="w-16 h-16 object-contain rounded bg-gray-50 flex-shrink-0">
                            </template>
                            <p class="text-sm font-medium text-gray-800" x-text="item.name_<?= $lang ?>"></p>
                        </div>

                        <!-- Contrôles quantité + poubelle -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <button @click="updateQuantity(item.product_id, item.quantity - 1)"
                                        class="w-8 h-8 rounded bg-gray-200 hover:bg-gray-300 flex items-center justify-center">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <span class="font-bold text-lg" x-text="item.quantity"></span>
                                <button @click="updateQuantity(item.product_id, item.quantity + 1)"
                                        class="w-8 h-8 rounded bg-gray-200 hover:bg-gray-300 flex items-center justify-center">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <button @click="removeFromCart(item.product_id)"
                                    class="text-red-600 hover:text-red-700 text-xl">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Actions mobile -->
            <template x-if="cart.items.length > 0">
                <div class="mt-6 space-y-3">
                    <div class="bg-gray-100 rounded-lg p-4 flex justify-between items-center">
                        <span class="font-semibold"><?= trans('catalog.total_items', $lang) ?> :</span>
                        <span class="text-2xl font-bold text-blue-600" x-text="cartItemCount"></span>
                    </div>
                    <button @click="validateOrder()"
                            class="w-full bg-green-600 text-white font-bold py-4 rounded-lg text-lg">
                        <i class="fas fa-check mr-2"></i>
                        <?= trans('catalog.validate_order', $lang) ?>
                    </button>
                    <button @click="clearCart()"
                            class="w-full bg-gray-300 text-gray-700 font-semibold py-3 rounded-lg">
                        <i class="fas fa-trash mr-2"></i>
                        <?= trans('catalog.clear_cart', $lang) ?>
                    </button>
                </div>
            </template>
        </div>
    </div>

    <!-- Lightbox zoom image -->
    <div x-show="showLightbox"
         x-transition
         @click="closeLightbox()"
         class="lightbox-overlay fixed inset-0 z-50 flex items-center justify-center p-4"
         style="display: none;">
        <div class="relative max-w-4xl max-h-full">
            <button @click="closeLightbox()"
                    class="absolute -top-10 right-0 text-white hover:text-gray-300 text-3xl">
                <i class="fas fa-times"></i>
            </button>
            <img :src="lightboxImage"
                 alt="Zoom produit"
                 class="max-w-full max-h-screen object-contain rounded-lg">
        </div>
    </div>

    <!-- Alpine.js Script -->
    <script>
        // Traductions via trans_js
        const t = <?= json_encode([
            'invalidQty' => trans('catalog.js_invalid_qty', $lang),
            'added' => trans('catalog.js_added', $lang),
            'error' => trans('catalog.js_error', $lang),
            'connectionError' => trans('catalog.js_connection_error', $lang),
            'removeConfirm' => trans('catalog.js_remove_confirm', $lang),
            'clearConfirm' => trans('catalog.js_clear_confirm', $lang),
            'cartCleared' => trans('catalog.js_cart_cleared', $lang),
            'emptyCart' => trans('catalog.js_empty_cart', $lang)
        ]) ?>;

        function switchLanguage(newLang) {
            window.location.href = '?lang=' + newLang;
        }

        function cartManager() {
            return {
                cart: <?= json_encode($_SESSION["cart"] ?? ["items" => []]) ?>,
                isCartOpen: false,
                showCartMobile: false,
                showLightbox: false,
                lightboxImage: '',
                // Variables pour la modal footer (pages statiques)
                footerModalOpen: false,
                footerModalUrl: '',
                footerModalTitle: '',

                get cartItemCount() {
                    return this.cart.items ? this.cart.items.reduce((sum, item) => sum + item.quantity, 0) : 0;
                },

                init() {
                    const sections = document.querySelectorAll('section[id^="category-"]');
                    const options = { root: null, rootMargin: '-50% 0px -50% 0px', threshold: 0 };

                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                const catId = entry.target.id.replace('category-', '');
                                document.querySelectorAll('.category-btn').forEach(btn => {
                                    btn.classList.remove('active');
                                    btn.style.backgroundColor = '';
                                    btn.style.color = '';
                                    const icon = btn.querySelector('i');
                                    if (icon) icon.style.color = '';
                                    const img = btn.querySelector('img');
                                    if (img && btn.getAttribute('data-original-filter')) {
                                        img.style.filter = btn.getAttribute('data-original-filter');
                                    }
                                });
                                const btn = document.getElementById('cat-btn-' + catId);
                                if (btn) {
                                    if (!btn.getAttribute('data-original-bg')) {
                                        btn.setAttribute('data-original-bg', btn.style.backgroundColor);
                                        btn.setAttribute('data-original-color', btn.style.color);
                                    }
                                    btn.classList.add('active');
                                    btn.style.backgroundColor = '#e73029';
                                    btn.style.color = 'white';
                                    const icon = btn.querySelector('i');
                                    if (icon) icon.style.color = 'white';
                                    const img = btn.querySelector('img');
                                    if (img) {
                                        if (!btn.getAttribute('data-original-filter')) {
                                            btn.setAttribute('data-original-filter', img.style.filter || 'none');
                                        }
                                        img.style.filter = 'brightness(0) invert(1)';
                                    }
                                }
                            }
                        });
                    }, options);

                    sections.forEach(section => observer.observe(section));
                },

                toggleCartMobile() {
                    this.showCartMobile = !this.showCartMobile;
                },

                openLightbox(imageUrl) {
                    if (imageUrl) {
                        this.lightboxImage = imageUrl;
                        this.showLightbox = true;
                    }
                },

                closeLightbox() {
                    this.showLightbox = false;
                    this.lightboxImage = '';
                },

                async addToCart(productId, maxOrderable) {
                    const qtyInput = document.getElementById('qty-' + productId);
                    const quantity = parseInt(qtyInput.value);

                    if (quantity <= 0 || quantity > maxOrderable) {
                        alert(t.invalidQty + ' : ' + maxOrderable);
                        return;
                    }

                    try {
                        const formData = new FormData();
                        formData.append('product_id', productId);
                        formData.append('quantity', quantity);

                        const response = await fetch('/stm/c/<?= $uuid ?>/cart/add', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.cart = data.cart;
                            qtyInput.value = 1;
                            this.showNotification(t.added);
                        } else {
                            alert(t.error + ' : ' + data.error);
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert(t.connectionError);
                    }
                },

                async updateQuantity(productId, newQuantity) {
                    if (newQuantity <= 0) {
                        return this.removeFromCart(productId);
                    }

                    try {
                        const formData = new FormData();
                        formData.append('product_id', productId);
                        formData.append('quantity', newQuantity);

                        const response = await fetch('/stm/c/<?= $uuid ?>/cart/update', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.cart = data.cart;
                        } else {
                            alert(t.error + ' : ' + data.error);
                        }
                    } catch (error) {
                        console.error('Error:', error);
                    }
                },

                async removeFromCart(productId) {
                    if (!confirm(t.removeConfirm)) return;

                    try {
                        const formData = new FormData();
                        formData.append('product_id', productId);

                        const response = await fetch('/stm/c/<?= $uuid ?>/cart/remove', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.cart = data.cart;
                        }
                    } catch (error) {
                        console.error('Error:', error);
                    }
                },

                async clearCart() {
                    if (!confirm(t.clearConfirm)) return;

                    try {
                        const response = await fetch('/stm/c/<?= $uuid ?>/cart/clear', {
                            method: 'POST'
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.cart = data.cart;
                            this.showNotification(t.cartCleared);
                        }
                    } catch (error) {
                        console.error('Error:', error);
                    }
                },

                validateOrder() {
                    if (this.cart.items.length === 0) {
                        alert(t.emptyCart);
                        return;
                    }
                    window.location.href = '/stm/c/<?= $uuid ?>/checkout';
                },

                showNotification(message) {
                    const notification = document.createElement('div');
                    notification.className = 'fixed top-20 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
                    notification.textContent = message;
                    document.body.appendChild(notification);
                    setTimeout(() => notification.remove(), 3000);
                }
            }
        }
    </script>

<?php
$content = ob_get_clean();
$pageScripts = '';

require __DIR__ . "/../../layouts/public.php";
?>