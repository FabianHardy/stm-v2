<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($campaign['name']) ?> - Catalogue</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Smooth scroll */
        html {
            scroll-behavior: smooth;
        }
        
        /* Fond Trendy Foods en arrière-plan */
        body {
            position: relative;
        }
        
        body::before {
            content: '';
            position: fixed;
            bottom: 0;
            right: 0;
            width: 400px;
            height: 400px;
            background: url('/stm/assets/images/fond.png') no-repeat;
            background-size: contain;
            opacity: 0.6;
            pointer-events: none;
            z-index: 0;
        }
        
        /* Lightbox overlay */
        .lightbox-overlay {
            background: rgba(0, 0, 0, 0.9);
        }
        
        /* Sticky nav offset */
        .scroll-mt {
            scroll-margin-top: 10rem;
        }

        /* Barre catégories horizontale avec scroll */
        .category-nav {
            overflow-x: auto;
            scrollbar-width: thin;
            scroll-behavior: smooth;
        }
        
        .category-nav::-webkit-scrollbar {
            height: 8px;
        }
        
        .category-nav::-webkit-scrollbar-thumb {
            background: #006eb8;
            border-radius: 4px;
        }

        .category-nav::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        /* Style des boutons catégories */
        .category-btn {
            transition: all 0.2s;
            border: 2px solid transparent;
        }

        .category-btn:hover {
            border-color: #006eb8;
            transform: translateY(-2px);
        }

        .category-btn.active {
            border-color: #e73029;
            box-shadow: 0 4px 6px rgba(231, 48, 41, 0.3);
        }

        /* Hover effect produits */
        .product-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-gray-50" x-data="cartManager()" x-init="init()">

    <!-- Header -->
    <header class="bg-white shadow-md sticky top-0 z-40">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <!-- Logo Trendy Foods -->
                    <img src="/stm/assets/images/logo.png" alt="Trendy Foods" class="h-12" onerror="this.style.display='none'">
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
                
                <!-- Bouton panier mobile -->
                <button @click="toggleCartMobile()" class="lg:hidden relative bg-blue-600 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-shopping-cart"></i>
                    <span x-show="cartItemCount > 0" 
                          x-text="cartItemCount" 
                          class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-6 h-6 flex items-center justify-center font-bold">
                    </span>
                </button>
                
                <!-- Déconnexion desktop -->
                <a href="/stm/c/<?= $uuid ?>" class="hidden lg:block text-gray-600 hover:text-gray-800">
                    <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                </a>
            </div>
        </div>
    </header>

    <!-- Navigation catégories horizontale (sticky) -->
    <nav class="bg-white border-b sticky top-[72px] z-30 shadow-sm">
        <div class="container mx-auto px-4">
            <div class="category-nav flex gap-3 py-3">
                <?php foreach ($categories as $category): ?>
                <a href="#category-<?= $category['id'] ?>" 
                   class="category-btn flex items-center gap-2 px-4 py-2.5 rounded-lg font-medium whitespace-nowrap"
                   id="cat-btn-<?= $category['id'] ?>"
                   style="background-color: <?= htmlspecialchars($category['color']) ?>CC; color: <?= htmlspecialchars($category['color']) ?>;">
                    <?php if (!empty($category['icon'])): ?>
                        <img src="<?= htmlspecialchars($category['icon']) ?>" 
                             alt="<?= htmlspecialchars($category['name_fr']) ?>" 
                             class="w-5 h-5 object-contain"
                             onerror="this.style.display='none'; console.log('Icon load error: <?= htmlspecialchars($category['icon']) ?>');">
                    <?php else: ?>
                        <i class="fas fa-tag w-5" style="color: <?= htmlspecialchars($category['color']) ?>;"></i>
                    <?php endif; ?>
                    <span><?= htmlspecialchars($category['name_fr']) ?></span>
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
                $orderableProducts = array_filter($category['products'], function($p) {
                    return $p['is_orderable'] === true;
                });
                $productCount = count($orderableProducts);
                
                // Si aucun produit disponible, ne pas afficher la catégorie
                if ($productCount === 0) continue;
                ?>
                
                <!-- Section catégorie -->
                <section id="category-<?= $category['id'] ?>" class="mb-12 scroll-mt">
                    <h2 class="text-2xl font-bold mb-6 flex items-center">
                        <span class="w-1 h-8 mr-3 rounded" style="background-color: <?= htmlspecialchars($category['color']) ?>;"></span>
                        <?= htmlspecialchars($category['name_fr']) ?>
                    </h2>
                    
                    <!-- Grid produits - 2 COLONNES MAX -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <?php foreach ($orderableProducts as $product): ?>
                        <!-- Card produit -->
                        <div class="product-card bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
                            
                            <!-- Image A4 paysage -->
                            <div class="relative bg-gray-100 cursor-pointer" style="height: 213px;" @click="openLightbox('<?= htmlspecialchars($product['image_fr']) ?>')">
                                <?php if (!empty($product['image_fr'])): ?>
                                    <img src="<?= htmlspecialchars($product['image_fr']) ?>" 
                                         alt="<?= htmlspecialchars($product['name_fr']) ?>"
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
                                <?php if (!$product['is_orderable']): ?>
                                <div class="absolute top-2 left-2 bg-gray-600 text-white px-3 py-1 rounded-full text-sm font-bold shadow-lg">
                                    ÉPUISÉ
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Infos produit -->
                            <div class="p-4">
                                <!-- Titre 12px uppercase -->
                                <h3 class="font-bold text-gray-800 mb-2 line-clamp-2 uppercase" style="font-size: 12px; line-height: 1.3; min-height: 32px;">
                                    <?= htmlspecialchars($product['name_fr']) ?>
                                </h3>
                                
                                <!-- Quotas sur 1 SEULE LIGNE -->
                                <?php if (!is_null($product['max_per_customer']) && $product['is_orderable']): ?>
                                <div class="text-sm mb-3 flex items-center justify-between">
                                    <span class="flex items-center">
                                        <i class="fas fa-box w-4 text-blue-600 mr-1"></i>
                                        <span class="font-semibold text-blue-600">
                                            Maximum : <?= $product['max_per_customer'] ?> <?= $product['max_per_customer'] > 1 ? 'unités' : 'unité' ?>
                                        </span>
                                    </span>
                                    <span class="flex items-center">
                                        <?php if ($product['available_for_customer'] > 1): ?>
                                            <i class="fas fa-check-circle w-4 text-green-600 mr-1"></i>
                                            <span class="font-semibold text-green-600">
                                                Reste : <?= $product['available_for_customer'] ?> unités
                                            </span>
                                        <?php elseif ($product['available_for_customer'] == 1): ?>
                                            <i class="fas fa-exclamation-circle w-4 text-orange-600 mr-1"></i>
                                            <span class="font-semibold text-orange-600">
                                                Reste : 1 unité
                                            </span>
                                        <?php else: ?>
                                            <i class="fas fa-times-circle w-4 text-red-600 mr-1"></i>
                                            <span class="font-semibold text-red-600">
                                                Reste : 0 unité
                                            </span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <?php endif; ?>

                                <!-- Alerte si quota global proche -->
                                <?php if (!is_null($product['max_total']) && $product['available_global'] <= 10 && $product['available_global'] > 0): ?>
                                <div class="bg-amber-50 border-l-4 border-amber-400 p-2 mb-3 text-xs">
                                    <i class="fas fa-exclamation-triangle text-amber-600 mr-1"></i>
                                    <span class="text-amber-800">Stock global limité : <?= $product['available_global'] ?> restants</span>
                                </div>
                                <?php endif; ?>

                                <!-- Boutons action -->
                                <?php if ($product['is_orderable']): ?>
                                <div class="flex items-center gap-3">
                                    <input 
                                        type="number" 
                                        id="qty-<?= $product['id'] ?>" 
                                        value="1" 
                                        min="1" 
                                        max="<?= $product['max_orderable'] ?>"
                                        class="w-20 px-3 py-2 border border-gray-300 rounded-lg text-center focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                    >
                                    <button 
                                        @click="addToCart(<?= $product['id'] ?>, <?= $product['max_orderable'] ?>)"
                                        class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg transition shadow-md hover:shadow-lg flex items-center justify-center"
                                    >
                                        <i class="fas fa-shopping-cart mr-2"></i>
                                        Ajouter
                                    </button>
                                </div>
                                <?php else: ?>
                                <button 
                                    disabled 
                                    class="w-full bg-gray-300 text-gray-500 font-semibold py-2 px-4 rounded-lg cursor-not-allowed flex items-center justify-center"
                                >
                                    <i class="fas fa-ban mr-2"></i>
                                    Stock épuisé
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
                <div class="bg-white rounded-lg shadow-lg p-6 sticky top-32">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-800 flex items-center">
                            <i class="fas fa-shopping-cart mr-2"></i>
                            Mon panier
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
                                <p class="text-sm">Votre panier est vide</p>
                            </div>
                        </template>
                        
                        <template x-for="item in cart.items" :key="item.product_id">
                            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                <img :src="item.image" 
                                     :alt="item.name"
                                     class="w-12 h-12 object-cover rounded"
                                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 60 60\'%3E%3Crect fill=\'%23e0e0e0\' width=\'60\' height=\'60\'/%3E%3C/svg%3E'">
                                <div class="flex-1 text-sm">
                                    <p class="font-semibold text-gray-800 line-clamp-2" x-text="item.name"></p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <button @click="updateQuantity(item.product_id, item.quantity - 1)" 
                                                class="w-6 h-6 rounded bg-gray-200 hover:bg-gray-300 flex items-center justify-center">
                                            <i class="fas fa-minus text-xs"></i>
                                        </button>
                                        <span class="font-bold" x-text="item.quantity"></span>
                                        <button @click="updateQuantity(item.product_id, item.quantity + 1)" 
                                                class="w-6 h-6 rounded bg-gray-200 hover:bg-gray-300 flex items-center justify-center">
                                            <i class="fas fa-plus text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                                <button @click="removeFromCart(item.product_id)" 
                                        class="text-red-600 hover:text-red-700">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </template>
                    </div>
                    
                    <!-- Total -->
                    <div class="border-t pt-4 mb-4" x-show="cart.items.length > 0">
                        <div class="flex justify-between text-lg font-bold">
                            <span>Total articles :</span>
                            <span class="text-blue-600" x-text="cartItemCount"></span>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <template x-if="cart.items.length > 0">
                        <div>
                            <button @click="validateOrder()" 
                                    class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg mb-2 transition shadow-md hover:shadow-lg flex items-center justify-center">
                                <i class="fas fa-check mr-2"></i>
                                Valider ma commande
                            </button>
                            <button @click="clearCart()" 
                                    class="w-full bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 rounded-lg transition flex items-center justify-center">
                                <i class="fas fa-trash mr-2"></i>
                                Vider le panier
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
                Mon panier
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
                    <p>Votre panier est vide</p>
                    <button @click="toggleCartMobile()" 
                            class="mt-4 bg-blue-600 text-white px-6 py-2 rounded-lg">
                        Continuer mes achats
                    </button>
                </div>
            </template>

            <div class="space-y-4">
                <template x-for="item in cart.items" :key="item.product_id">
                    <div class="bg-white border rounded-lg p-4 flex items-center gap-4">
                        <img :src="item.image" 
                             :alt="item.name"
                             class="w-16 h-16 object-cover rounded">
                        <div class="flex-1">
                            <p class="font-semibold text-gray-800" x-text="item.name"></p>
                            <div class="flex items-center gap-3 mt-2">
                                <button @click="updateQuantity(item.product_id, item.quantity - 1)" 
                                        class="w-8 h-8 rounded bg-gray-200 hover:bg-gray-300">
                                    -
                                </button>
                                <span class="font-bold text-lg" x-text="item.quantity"></span>
                                <button @click="updateQuantity(item.product_id, item.quantity + 1)" 
                                        class="w-8 h-8 rounded bg-gray-200 hover:bg-gray-300">
                                    +
                                </button>
                            </div>
                        </div>
                        <button @click="removeFromCart(item.product_id)" 
                                class="text-red-600 hover:text-red-700 text-xl">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </template>
            </div>

            <!-- Actions mobile -->
            <template x-if="cart.items.length > 0">
                <div class="mt-6 space-y-3">
                    <div class="bg-gray-100 rounded-lg p-4 flex justify-between items-center">
                        <span class="font-semibold">Total articles :</span>
                        <span class="text-2xl font-bold text-blue-600" x-text="cartItemCount"></span>
                    </div>
                    <button @click="validateOrder()" 
                            class="w-full bg-green-600 text-white font-bold py-4 rounded-lg text-lg">
                        <i class="fas fa-check mr-2"></i>
                        Valider ma commande
                    </button>
                    <button @click="clearCart()" 
                            class="w-full bg-gray-300 text-gray-700 font-semibold py-3 rounded-lg">
                        <i class="fas fa-trash mr-2"></i>
                        Vider le panier
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
        function cartManager() {
            return {
                cart: <?= json_encode($cart) ?>,
                showCartMobile: false,
                showLightbox: false,
                lightboxImage: '',
                
                init() {
                    console.log('Cart initialized', this.cart);
                    // Gérer les catégories actives au scroll
                    this.handleCategoryHighlight();
                },
                
                get cartItemCount() {
                    return this.cart.items.reduce((total, item) => total + item.quantity, 0);
                },
                
                toggleCartMobile() {
                    this.showCartMobile = !this.showCartMobile;
                },
                
                openLightbox(imagePath) {
                    this.lightboxImage = imagePath;
                    this.showLightbox = true;
                },
                
                closeLightbox() {
                    this.showLightbox = false;
                },
                
                handleCategoryHighlight() {
                    // Observer pour mettre en surbrillance la catégorie active
                    const sections = document.querySelectorAll('section[id^="category-"]');
                    const options = {
                        root: null,
                        rootMargin: '-50% 0px -50% 0px',
                        threshold: 0
                    };
                    
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                const catId = entry.target.id.replace('category-', '');
                                // Retirer active de tous
                                document.querySelectorAll('.category-btn').forEach(btn => {
                                    btn.classList.remove('active');
                                    // Réinitialiser le style original
                                    const originalBg = btn.getAttribute('data-original-bg');
                                    const originalColor = btn.getAttribute('data-original-color');
                                    if (originalBg) btn.style.backgroundColor = originalBg;
                                    if (originalColor) btn.style.color = originalColor;
                                });
                                // Ajouter active au bon
                                const btn = document.getElementById('cat-btn-' + catId);
                                if (btn) {
                                    // Sauvegarder les styles originaux si pas déjà fait
                                    if (!btn.getAttribute('data-original-bg')) {
                                        btn.setAttribute('data-original-bg', btn.style.backgroundColor);
                                        btn.setAttribute('data-original-color', btn.style.color);
                                    }
                                    btn.classList.add('active');
                                    // Appliquer le style actif (rouge avec texte blanc)
                                    btn.style.backgroundColor = '#e73029';
                                    btn.style.color = 'white';
                                    // Icônes en blanc aussi
                                    const icon = btn.querySelector('i');
                                    if (icon) icon.style.color = 'white';
                                }
                            }
                        });
                    }, options);
                    
                    sections.forEach(section => observer.observe(section));
                },
                
                async addToCart(productId, maxOrderable) {
                    const qtyInput = document.getElementById('qty-' + productId);
                    const quantity = parseInt(qtyInput.value);
                    
                    if (quantity <= 0 || quantity > maxOrderable) {
                        alert('Quantité invalide. Maximum : ' + maxOrderable);
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
                            qtyInput.value = 1; // Reset
                            this.showNotification('✓ Produit ajouté au panier');
                        } else {
                            alert('Erreur : ' + data.error);
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Erreur de connexion');
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
                            alert('Erreur : ' + data.error);
                        }
                    } catch (error) {
                        console.error('Error:', error);
                    }
                },
                
                async removeFromCart(productId) {
                    if (!confirm('Retirer ce produit du panier ?')) return;
                    
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
                    if (!confirm('Vider complètement le panier ?')) return;
                    
                    try {
                        const response = await fetch('/stm/c/<?= $uuid ?>/cart/clear', {
                            method: 'POST'
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            this.cart = data.cart;
                            this.showNotification('Panier vidé');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                    }
                },
                
                validateOrder() {
                    if (this.cart.items.length === 0) {
                        alert('Votre panier est vide');
                        return;
                    }
                    
                    window.location.href = '/stm/c/<?= $uuid ?>/checkout';
                },
                
                showNotification(message) {
                    const notification = document.createElement('div');
                    notification.className = 'fixed top-20 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
                    notification.textContent = message;
                    document.body.appendChild(notification);
                    
                    setTimeout(() => {
                        notification.remove();
                    }, 3000);
                }
            }
        }
    </script>
</body>
</html>