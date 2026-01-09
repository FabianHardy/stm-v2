<?php
/**
 * Vue catalogue prospect
 *
 * Sprint 16 : Mode Prospect
 * Catalogue des promotions pour les prospects
 *
 * @created    2026/01/09
 */

// Variables attendues :
// $campaign, $categories, $promotions, $cart, $cartCount
// $lang, $prospectNumber, $prospectName, $orderSource

$lang = $lang ?? 'fr';
$campaignTitle = $lang === 'nl' ? ($campaign['title_nl'] ?? $campaign['name']) : ($campaign['title_fr'] ?? $campaign['name']);

// Traductions
$t = [
    'fr' => [
        'catalog' => 'Catalogue',
        'welcome' => 'Bienvenue',
        'your_cart' => 'Votre panier',
        'items' => 'article(s)',
        'empty_cart' => 'Votre panier est vide',
        'checkout' => 'Valider ma commande',
        'add_to_cart' => 'Ajouter',
        'quantity' => 'Quantité',
        'available' => 'Disponible',
        'unit' => 'unité(s)',
        'max_qty' => 'Max',
        'no_promotions' => 'Aucune promotion disponible',
        'all_categories' => 'Toutes les catégories',
        'search' => 'Rechercher...',
        'logout' => 'Terminer',
        'cart_updated' => 'Panier mis à jour',
    ],
    'nl' => [
        'catalog' => 'Catalogus',
        'welcome' => 'Welkom',
        'your_cart' => 'Uw winkelwagen',
        'items' => 'artikel(en)',
        'empty_cart' => 'Uw winkelwagen is leeg',
        'checkout' => 'Bestelling bevestigen',
        'add_to_cart' => 'Toevoegen',
        'quantity' => 'Aantal',
        'available' => 'Beschikbaar',
        'unit' => 'eenheid/eenheden',
        'max_qty' => 'Max',
        'no_promotions' => 'Geen promoties beschikbaar',
        'all_categories' => 'Alle categorieën',
        'search' => 'Zoeken...',
        'logout' => 'Beëindigen',
        'cart_updated' => 'Winkelwagen bijgewerkt',
    ],
][$lang] ?? $t['fr'];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['catalog'] ?> - <?= htmlspecialchars($campaignTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen" x-data="catalogApp()">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($campaignTitle) ?></h1>
                    <p class="text-sm text-purple-600">
                        👤 <?= $t['welcome'] ?>, <strong><?= htmlspecialchars($prospectName) ?></strong>
                        <span class="text-gray-500">(<?= htmlspecialchars($prospectNumber) ?>)</span>
                    </p>
                </div>
                
                <div class="flex items-center gap-4">
                    <!-- Panier -->
                    <div class="relative">
                        <button @click="showCart = !showCart"
                                class="flex items-center gap-2 bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition">
                            🛒 <span x-text="cartCount"><?= $cartCount ?></span> <?= $t['items'] ?>
                        </button>
                    </div>
                    
                    <!-- Terminer -->
                    <a href="/stm/c/<?= htmlspecialchars($campaign['uuid']) ?>/prospect" 
                       class="text-gray-500 hover:text-gray-700 text-sm">
                        <?= $t['logout'] ?>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 py-6">
        <div class="flex gap-6">
            <!-- Sidebar catégories -->
            <aside class="w-64 flex-shrink-0 hidden lg:block">
                <div class="bg-white rounded-lg shadow-sm p-4 sticky top-24">
                    <h3 class="font-semibold text-gray-900 mb-3"><?= $t['all_categories'] ?></h3>
                    <ul class="space-y-1">
                        <li>
                            <button @click="selectedCategory = null"
                                    :class="selectedCategory === null ? 'bg-purple-100 text-purple-700' : 'text-gray-600 hover:bg-gray-100'"
                                    class="w-full text-left px-3 py-2 rounded text-sm transition">
                                <?= $t['all_categories'] ?>
                            </button>
                        </li>
                        <?php foreach ($categories as $cat): ?>
                        <li>
                            <button @click="selectedCategory = <?= $cat['id'] ?>"
                                    :class="selectedCategory === <?= $cat['id'] ?> ? 'bg-purple-100 text-purple-700' : 'text-gray-600 hover:bg-gray-100'"
                                    class="w-full text-left px-3 py-2 rounded text-sm transition">
                                <?= htmlspecialchars($lang === 'nl' ? ($cat['name_nl'] ?? $cat['name_fr']) : $cat['name_fr']) ?>
                            </button>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </aside>

            <!-- Contenu principal -->
            <main class="flex-1">
                <!-- Barre de recherche -->
                <div class="mb-6">
                    <input type="text"
                           x-model="searchQuery"
                           placeholder="<?= $t['search'] ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                </div>

                <!-- Liste des promotions -->
                <?php if (empty($promotions)): ?>
                <div class="bg-white rounded-lg p-8 text-center text-gray-500">
                    <?= $t['no_promotions'] ?>
                </div>
                <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <?php foreach ($promotions as $promo): ?>
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden"
                         x-show="filterPromotion(<?= $promo['category_id'] ?? 'null' ?>, '<?= htmlspecialchars(addslashes($promo['product_name'])) ?>')"
                         x-transition>
                        
                        <!-- Image -->
                        <?php if (!empty($promo['image_url'])): ?>
                        <div class="aspect-video bg-gray-100">
                            <img src="<?= htmlspecialchars($promo['image_url']) ?>" 
                                 alt="<?= htmlspecialchars($promo['product_name']) ?>"
                                 class="w-full h-full object-contain">
                        </div>
                        <?php endif; ?>

                        <!-- Contenu -->
                        <div class="p-4">
                            <h4 class="font-semibold text-gray-900 mb-1">
                                <?= htmlspecialchars($promo['product_name']) ?>
                            </h4>
                            
                            <p class="text-sm text-gray-500 mb-2">
                                <?= htmlspecialchars($lang === 'nl' ? ($promo['category_name_nl'] ?? $promo['category_name_fr']) : $promo['category_name_fr']) ?>
                            </p>

                            <?php
                            $descField = $lang === 'nl' ? 'description_nl' : 'description_fr';
                            $description = $promo[$descField] ?? $promo['description_fr'] ?? '';
                            if (!empty($description)):
                            ?>
                            <p class="text-sm text-gray-600 mb-3 line-clamp-2">
                                <?= htmlspecialchars($description) ?>
                            </p>
                            <?php endif; ?>

                            <!-- Quantité et ajout -->
                            <div class="flex items-center gap-2 mt-4">
                                <div class="flex items-center border border-gray-300 rounded">
                                    <button type="button"
                                            @click="decrementQty(<?= $promo['id'] ?>)"
                                            class="px-3 py-1 text-gray-600 hover:bg-gray-100">-</button>
                                    <input type="number"
                                           x-model.number="quantities[<?= $promo['id'] ?>]"
                                           min="1"
                                           max="<?= $promo['max_quantity_per_order'] ?? 999 ?>"
                                           class="w-16 text-center border-x border-gray-300 py-1">
                                    <button type="button"
                                            @click="incrementQty(<?= $promo['id'] ?>, <?= $promo['max_quantity_per_order'] ?? 999 ?>)"
                                            class="px-3 py-1 text-gray-600 hover:bg-gray-100">+</button>
                                </div>
                                
                                <button @click="addToCart(<?= $promo['id'] ?>)"
                                        class="flex-1 bg-purple-600 text-white py-2 px-4 rounded hover:bg-purple-700 transition text-sm">
                                    <?= $t['add_to_cart'] ?>
                                </button>
                            </div>

                            <?php if (!empty($promo['max_quantity_per_order'])): ?>
                            <p class="text-xs text-gray-500 mt-2">
                                <?= $t['max_qty'] ?>: <?= $promo['max_quantity_per_order'] ?> <?= $t['unit'] ?>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Modal panier -->
    <div x-show="showCart" 
         x-cloak
         class="fixed inset-0 z-50 overflow-hidden"
         @keydown.escape.window="showCart = false">
        
        <!-- Overlay -->
        <div class="absolute inset-0 bg-black/50" @click="showCart = false"></div>
        
        <!-- Panneau -->
        <div class="absolute right-0 top-0 h-full w-full max-w-md bg-white shadow-xl"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full">
            
            <div class="flex flex-col h-full">
                <!-- Header -->
                <div class="px-4 py-4 border-b flex items-center justify-between">
                    <h3 class="text-lg font-semibold"><?= $t['your_cart'] ?></h3>
                    <button @click="showCart = false" class="text-gray-500 hover:text-gray-700">
                        ✕
                    </button>
                </div>

                <!-- Contenu -->
                <div class="flex-1 overflow-y-auto p-4">
                    <template x-if="Object.keys(cart).length === 0">
                        <p class="text-gray-500 text-center py-8"><?= $t['empty_cart'] ?></p>
                    </template>
                    
                    <template x-for="(item, id) in cart" :key="id">
                        <div class="flex items-center gap-3 py-3 border-b">
                            <div class="flex-1">
                                <p class="font-medium text-sm" x-text="item.name"></p>
                                <p class="text-sm text-gray-500">
                                    <?= $t['quantity'] ?>: <span x-text="item.quantity"></span>
                                </p>
                            </div>
                            <button @click="removeFromCart(id)" class="text-red-500 hover:text-red-700">
                                🗑️
                            </button>
                        </div>
                    </template>
                </div>

                <!-- Footer -->
                <div class="px-4 py-4 border-t">
                    <p class="text-sm text-gray-600 mb-3">
                        Total: <strong x-text="cartCount"></strong> <?= $t['items'] ?>
                    </p>
                    
                    <form method="POST" action="/stm/c/<?= htmlspecialchars($campaign['uuid']) ?>/prospect/order">
                        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <button type="submit"
                                :disabled="Object.keys(cart).length === 0"
                                class="w-full bg-purple-600 text-white py-3 rounded-lg font-semibold hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition">
                            <?= $t['checkout'] ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast notification -->
    <div x-show="showToast"
         x-transition
         x-cloak
         class="fixed bottom-4 right-4 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg">
        <span x-text="toastMessage"></span>
    </div>

    <script>
        function catalogApp() {
            return {
                cart: <?= json_encode($cart) ?>,
                cartCount: <?= $cartCount ?>,
                quantities: {},
                selectedCategory: null,
                searchQuery: '',
                showCart: false,
                showToast: false,
                toastMessage: '',

                init() {
                    // Initialiser les quantités par défaut
                    <?php foreach ($promotions as $promo): ?>
                    this.quantities[<?= $promo['id'] ?>] = 1;
                    <?php endforeach; ?>
                },

                filterPromotion(categoryId, productName) {
                    // Filtre par catégorie
                    if (this.selectedCategory !== null && categoryId !== this.selectedCategory) {
                        return false;
                    }
                    // Filtre par recherche
                    if (this.searchQuery && !productName.toLowerCase().includes(this.searchQuery.toLowerCase())) {
                        return false;
                    }
                    return true;
                },

                incrementQty(promoId, max) {
                    if (this.quantities[promoId] < max) {
                        this.quantities[promoId]++;
                    }
                },

                decrementQty(promoId) {
                    if (this.quantities[promoId] > 1) {
                        this.quantities[promoId]--;
                    }
                },

                async addToCart(promoId) {
                    const qty = this.quantities[promoId] || 1;
                    
                    const formData = new FormData();
                    formData.append('promotion_id', promoId);
                    formData.append('quantity', qty);
                    formData.append('_token', '<?= $_SESSION['csrf_token'] ?? '' ?>');

                    try {
                        const response = await fetch('/stm/c/<?= $campaign['uuid'] ?>/cart/add', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            this.cart = data.cart;
                            this.cartCount = data.cartCount;
                            this.toast('<?= $t['cart_updated'] ?>');
                        }
                    } catch (e) {
                        console.error('Erreur ajout panier:', e);
                    }
                },

                async removeFromCart(promoId) {
                    const formData = new FormData();
                    formData.append('promotion_id', promoId);
                    formData.append('_token', '<?= $_SESSION['csrf_token'] ?? '' ?>');

                    try {
                        const response = await fetch('/stm/c/<?= $campaign['uuid'] ?>/cart/remove', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            this.cart = data.cart;
                            this.cartCount = data.cartCount;
                        }
                    } catch (e) {
                        console.error('Erreur suppression panier:', e);
                    }
                },

                toast(message) {
                    this.toastMessage = message;
                    this.showToast = true;
                    setTimeout(() => {
                        this.showToast = false;
                    }, 2000);
                }
            }
        }
    </script>
</body>
</html>
