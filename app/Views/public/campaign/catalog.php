<!DOCTYPE html>
<html lang="<?= $customer['language'] ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($campaign['name']) ?> - <?= $customer['language'] === 'fr' ? 'Catalogue' : 'Catalogus' ?></title>
    
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
        
        /* Contenu au-dessus du fond */
        .content-wrapper {
            position: relative;
            z-index: 1;
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

        /* Truncate text */
        .truncate-2-lines {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body class="bg-gray-50" x-data="cartManager()" x-init="init()">

    <div class="content-wrapper">
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
                    
                    <div class="flex items-center gap-4">
                        <!-- Switch langue FR/NL (visible uniquement pour campagnes BE) -->
                        <?php if ($campaign['country'] === 'BE'): ?>
                        <div class="hidden lg:flex bg-gray-100 rounded-lg p-1">
                            <button onclick="window.location.href='?lang=fr'" 
                                    class="px-4 py-2 rounded-md <?= $customer['language'] === 'fr' ? 'bg-white text-blue-600 font-semibold shadow-sm' : 'text-gray-600 hover:bg-white hover:shadow-sm' ?> transition">
                                FR
                            </button>
                            <button onclick="window.location.href='?lang=nl'" 
                                    class="px-4 py-2 rounded-md <?= $customer['language'] === 'nl' ? 'bg-white text-blue-600 font-semibold shadow-sm' : 'text-gray-600 hover:bg-white hover:shadow-sm' ?> transition">
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
                        
                        <!-- Déconnexion desktop -->
                        <a href="/stm/c/<?= $uuid ?>" class="hidden lg:block text-gray-600 hover:text-gray-800">
                            <i class="fas fa-sign-out-alt mr-2"></i>
                            <?= $customer['language'] === 'fr' ? 'Déconnexion' : 'Afmelden' ?>
                        </a>
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
                    $color = $category['color'];
                    $hex = ltrim($color, '#');
                    $r = hexdec(substr($hex, 0, 2));
                    $g = hexdec(substr($hex, 2, 2));
                    $b = hexdec(substr($hex, 4, 2));
                    // Formule de luminosité perçue
                    $luminosity = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
                    $textColor = $luminosity > 0.5 ? '#000000' : '#FFFFFF';
                    ?>
                    <a href="#category-<?= $category['id'] ?>" 
                       class="category-btn flex items-center gap-2 px-4 py-2.5 rounded-lg font-medium whitespace-nowrap"
                       id="cat-btn-<?= $category['id'] ?>"
                       style="background-color: <?= htmlspecialchars($category['color']) ?>CC; color: <?= $textColor ?>;">
                        <?php if (!empty($category['icon_path'])): ?>
                            <img src="<?= htmlspecialchars($category['icon_path']) ?>" 
                                 alt="<?= htmlspecialchars($category['name_' . $customer['language']]) ?>" 
                                 class="w-5 h-5 object-contain"
                                 onerror="this.style.display='none'; console.log('Icon load error: <?= htmlspecialchars($category['icon_path']) ?>');">
                        <?php else: ?>
                            <i class="fas fa-tag w-5"></i>
                        <?php endif; ?>
                        <span><?= htmlspecialchars($category['name_' . $customer['language']]) ?></span>
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
                        
                    <?php if (!empty($category['products'])): ?>
                    <section id="category-<?= $category['id'] ?>" class="mb-12 scroll-mt">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-3">
                            <?php if (!empty($category['icon_path'])): ?>
                                <img src="<?= htmlspecialchars($category['icon_path']) ?>" 
                                     alt="" 
                                     class="w-8 h-8 object-contain"
                                     onerror="this.style.display='none'">
                            <?php endif; ?>
                            <?= htmlspecialchars($category['name_' . $customer['language']]) ?>
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php foreach ($category['products'] as $product): ?>
                            <div class="product-card bg-white rounded-lg shadow-md overflow-hidden">
                                <!-- Image produit -->
                                <div class="relative bg-gray-50" style="height: 213px;">
                                    <?php if (!empty($product['image_' . $customer['language']])): ?>
                                        <img src="<?= htmlspecialchars($product['image_' . $customer['language']]) ?>" 
                                             alt="<?= htmlspecialchars($product['name_' . $customer['language']]) ?>"
                                             class="w-full h-full object-contain cursor-pointer"
                                             onclick="openLightbox('<?= htmlspecialchars($product['image_' . $customer['language']]) ?>')">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center">
                                            <i class="fas fa-image text-6xl text-gray-300"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Infos produit -->
                                <div class="p-4">
                                    <h3 class="font-bold text-gray-800 mb-2 uppercase text-xs leading-tight" style="font-size: 12px; min-height: 2.5em; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        <?= htmlspecialchars($product['name_' . $customer['language']]) ?>
                                    </h3>
                                    
                                    <!-- Quotas sur 1 ligne -->
                                    <div class="text-sm text-gray-600 mb-3">
                                        <?php
                                        $maxOrderable = $product['quota_available'];
                                        $remaining = $product['quota_available'];
                                        $unit = $customer['language'] === 'fr' ? 'unité' : 'eenheid';
                                        $units = $customer['language'] === 'fr' ? 'unités' : 'eenheden';
                                        $maxLabel = $customer['language'] === 'fr' ? 'Maximum' : 'Maximum';
                                        $remainLabel = $customer['language'] === 'fr' ? 'Reste' : 'Resterend';
                                        ?>
                                        <span class="font-medium"><?= $maxLabel ?> : <?= $maxOrderable ?> <?= $maxOrderable > 1 ? $units : $unit ?></span>
                                        <span class="mx-2">|</span>
                                        <span class="font-medium"><?= $remainLabel ?> : <?= $remaining ?> <?= $remaining > 1 ? $units : $unit ?></span>
                                    </div>
                                    
                                    <?php if ($maxOrderable > 0): ?>
                                        <!-- Formulaire ajout panier -->
                                        <div class="flex items-center gap-2">
                                            <input type="number" 
                                                   id="qty-<?= $product['id'] ?>" 
                                                   min="1" 
                                                   max="<?= $maxOrderable ?>" 
                                                   value="1"
                                                   class="w-20 px-3 py-2 border border-gray-300 rounded-lg text-center">
                                            <button @click="addToCart(<?= $product['id'] ?>, <?= $maxOrderable ?>)"
                                                    class="flex-1 bg-red-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-red-700 transition">
                                                <i class="fas fa-cart-plus mr-2"></i>
                                                <?= $customer['language'] === 'fr' ? 'Ajouter' : 'Toevoegen' ?>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <!-- Badge épuisé -->
                                        <div class="bg-gray-200 text-gray-600 text-center py-2 rounded-lg font-semibold">
                                            <i class="fas fa-times-circle mr-2"></i>
                                            <?= $customer['language'] === 'fr' ? 'Épuisé' : 'Uitverkocht' ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    
                    <?php endforeach; ?>
                    
                </div>
                
                <!-- Panier (sidebar droite - sticky) -->
                <aside class="hidden lg:block w-96 shrink-0">
                    <div class="bg-white rounded-lg shadow-lg p-6 sticky top-32">
                        <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center justify-between">
                            <span>
                                <i class="fas fa-shopping-cart mr-2"></i>
                                <?= $customer['language'] === 'fr' ? 'Mon panier' : 'Mijn winkelwagen' ?>
                            </span>
                            <span x-show="cartItemCount > 0" 
                                  class="bg-red-500 text-white text-sm rounded-full w-8 h-8 flex items-center justify-center font-bold"
                                  x-text="cartItemCount">
                            </span>
                        </h2>
                        
                        <div x-show="cart.items.length === 0" class="text-center py-12 text-gray-400">
                            <i class="fas fa-shopping-cart text-5xl mb-3"></i>
                            <p><?= $customer['language'] === 'fr' ? 'Panier vide' : 'Winkelwagen leeg' ?></p>
                        </div>
                        
                        <div x-show="cart.items.length > 0">
                            <!-- Liste des articles -->
                            <div class="space-y-3 mb-6 max-h-96 overflow-y-auto">
                                <template x-for="item in cart.items" :key="item.product_id">
                                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                        <!-- Image produit -->
                                        <img :src="item.image_<?= $customer['language'] ?>" 
                                             :alt="item.name_<?= $customer['language'] ?>"
                                             class="w-16 h-16 object-contain rounded"
                                             onerror="this.src='/stm/assets/images/placeholder.png'">
                                        
                                        <!-- Nom + Contrôles -->
                                        <div class="flex-1 min-w-0">
                                            <!-- Nom du produit (tronqué) -->
                                            <p class="font-medium text-sm text-gray-800 truncate mb-2" 
                                               x-text="item.name_<?= $customer['language'] ?>">
                                            </p>
                                            
                                            <!-- Contrôles quantité -->
                                            <div class="flex items-center gap-2">
                                                <button @click="updateQuantity(item.product_id, item.quantity - 1)"
                                                        class="w-7 h-7 bg-gray-200 hover:bg-gray-300 rounded flex items-center justify-center">
                                                    <i class="fas fa-minus text-xs"></i>
                                                </button>
                                                <span class="w-8 text-center font-bold" x-text="item.quantity"></span>
                                                <button @click="updateQuantity(item.product_id, item.quantity + 1)"
                                                        class="w-7 h-7 bg-gray-200 hover:bg-gray-300 rounded flex items-center justify-center">
                                                    <i class="fas fa-plus text-xs"></i>
                                                </button>
                                                <button @click="removeFromCart(item.product_id)"
                                                        class="ml-auto text-red-600 hover:text-red-800">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            
                            <!-- Total articles -->
                            <div class="border-t pt-4 mb-4">
                                <div class="flex justify-between items-center text-lg font-semibold">
                                    <span><?= $customer['language'] === 'fr' ? 'Total articles' : 'Totaal artikelen' ?> :</span>
                                    <span class="text-blue-600 text-2xl" x-text="cartItemCount"></span>
                                </div>
                            </div>
                            
                            <!-- Actions -->
                            <div class="space-y-2">
                                <button @click="validateOrder()"
                                        class="w-full bg-green-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-green-700 transition">
                                    <i class="fas fa-check mr-2"></i>
                                    <?= $customer['language'] === 'fr' ? 'Valider ma commande' : 'Mijn bestelling bevestigen' ?>
                                </button>
                                <button @click="clearCart()"
                                        class="w-full bg-gray-200 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-300 transition">
                                    <i class="fas fa-trash mr-2"></i>
                                    <?= $customer['language'] === 'fr' ? 'Vider le panier' : 'Winkelwagen legen' ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </aside>
                
            </div>
        </div>
    </div>

    <!-- Panier mobile (overlay) -->
    <div x-show="showCartMobile" 
         @click.self="toggleCartMobile()"
         class="fixed inset-0 bg-black bg-opacity-50 z-50 lg:hidden"
         style="display: none;">
        <div class="absolute right-0 top-0 h-full w-full max-w-md bg-white shadow-xl p-6 overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold flex items-center">
                    <i class="fas fa-shopping-cart mr-2"></i>
                    <?= $customer['language'] === 'fr' ? 'Mon panier' : 'Mijn winkelwagen' ?>
                    <span x-show="cartItemCount > 0" 
                          class="ml-2 bg-red-500 text-white text-sm rounded-full w-6 h-6 flex items-center justify-center"
                          x-text="cartItemCount">
                    </span>
                </h2>
                <button @click="toggleCartMobile()" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <div x-show="cart.items.length === 0" class="text-center py-12 text-gray-400">
                <i class="fas fa-shopping-cart text-5xl mb-3"></i>
                <p><?= $customer['language'] === 'fr' ? 'Panier vide' : 'Winkelwagen leeg' ?></p>
            </div>
            
            <div x-show="cart.items.length > 0">
                <!-- Liste des articles -->
                <div class="space-y-3 mb-6">
                    <template x-for="item in cart.items" :key="item.product_id">
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <img :src="item.image_<?= $customer['language'] ?>" 
                                 :alt="item.name_<?= $customer['language'] ?>"
                                 class="w-16 h-16 object-contain rounded"
                                 onerror="this.src='/stm/assets/images/placeholder.png'">
                            
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-sm text-gray-800 truncate mb-2" 
                                   x-text="item.name_<?= $customer['language'] ?>">
                                </p>
                                
                                <div class="flex items-center gap-2">
                                    <button @click="updateQuantity(item.product_id, item.quantity - 1)"
                                            class="w-7 h-7 bg-gray-200 hover:bg-gray-300 rounded flex items-center justify-center">
                                        <i class="fas fa-minus text-xs"></i>
                                    </button>
                                    <span class="w-8 text-center font-bold" x-text="item.quantity"></span>
                                    <button @click="updateQuantity(item.product_id, item.quantity + 1)"
                                            class="w-7 h-7 bg-gray-200 hover:bg-gray-300 rounded flex items-center justify-center">
                                        <i class="fas fa-plus text-xs"></i>
                                    </button>
                                    <button @click="removeFromCart(item.product_id)"
                                            class="ml-auto text-red-600 hover:text-red-800">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                
                <!-- Total -->
                <div class="border-t pt-4 mb-4">
                    <div class="flex justify-between items-center text-lg font-semibold">
                        <span><?= $customer['language'] === 'fr' ? 'Total articles' : 'Totaal artikelen' ?> :</span>
                        <span class="text-blue-600 text-2xl" x-text="cartItemCount"></span>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="space-y-2">
                    <button @click="validateOrder()"
                            class="w-full bg-green-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-green-700 transition">
                        <i class="fas fa-check mr-2"></i>
                        <?= $customer['language'] === 'fr' ? 'Valider ma commande' : 'Mijn bestelling bevestigen' ?>
                    </button>
                    <button @click="clearCart()"
                            class="w-full bg-gray-200 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-300 transition">
                        <i class="fas fa-trash mr-2"></i>
                        <?= $customer['language'] === 'fr' ? 'Vider le panier' : 'Winkelwagen legen' ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Lightbox -->
    <div id="lightbox" 
         class="lightbox-overlay fixed inset-0 z-50 hidden items-center justify-center p-4" 
         onclick="closeLightbox()">
        <img id="lightbox-img" src="" alt="" class="max-w-full max-h-full object-contain">
    </div>

    <script>
        function openLightbox(src) {
            document.getElementById('lightbox-img').src = src;
            document.getElementById('lightbox').classList.remove('hidden');
            document.getElementById('lightbox').classList.add('flex');
        }
        
        function closeLightbox() {
            document.getElementById('lightbox').classList.add('hidden');
            document.getElementById('lightbox').classList.remove('flex');
        }
        
        function cartManager() {
            return {
                cart: <?= json_encode($cart) ?>,
                showCartMobile: false,
                
                get cartItemCount() {
                    return this.cart.items?.reduce((sum, item) => sum + parseInt(item.quantity), 0) || 0;
                },
                
                toggleCartMobile() {
                    this.showCartMobile = !this.showCartMobile;
                },
                
                init() {
                    // Observer pour activer le bon bouton catégorie au scroll
                    const sections = document.querySelectorAll('[id^="category-"]');
                    
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
                                    // Réinitialiser l'icône aussi
                                    const img = btn.querySelector('img');
                                    if (img && btn.getAttribute('data-original-filter')) {
                                        img.style.filter = btn.getAttribute('data-original-filter');
                                    }
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
                                    // Icône en blanc aussi (si c'est une icône Font Awesome)
                                    const icon = btn.querySelector('i');
                                    if (icon) icon.style.color = 'white';
                                    // Pour les images, on peut appliquer un filtre pour les rendre blanches
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
                
                async addToCart(productId, maxOrderable) {
                    const qtyInput = document.getElementById('qty-' + productId);
                    const quantity = parseInt(qtyInput.value);
                    
                    if (quantity <= 0 || quantity > maxOrderable) {
                        alert('<?= $customer['language'] === 'fr' ? 'Quantité invalide. Maximum' : 'Ongeldige hoeveelheid. Maximum' ?> : ' + maxOrderable);
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
                            this.showNotification('<?= $customer['language'] === 'fr' ? '✓ Produit ajouté au panier' : '✓ Product toegevoegd aan winkelwagen' ?>');
                        } else {
                            alert('<?= $customer['language'] === 'fr' ? 'Erreur' : 'Fout' ?> : ' + data.error);
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('<?= $customer['language'] === 'fr' ? 'Erreur de connexion' : 'Verbindingsfout' ?>');
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
                            alert('<?= $customer['language'] === 'fr' ? 'Erreur' : 'Fout' ?> : ' + data.error);
                        }
                    } catch (error) {
                        console.error('Error:', error);
                    }
                },
                
                async removeFromCart(productId) {
                    if (!confirm('<?= $customer['language'] === 'fr' ? 'Retirer ce produit du panier ?' : 'Dit product uit winkelwagen verwijderen?' ?>')) return;
                    
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
                    if (!confirm('<?= $customer['language'] === 'fr' ? 'Vider complètement le panier ?' : 'Winkelwagen volledig legen?' ?>')) return;
                    
                    try {
                        const response = await fetch('/stm/c/<?= $uuid ?>/cart/clear', {
                            method: 'POST'
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            this.cart = data.cart;
                            this.showNotification('<?= $customer['language'] === 'fr' ? 'Panier vidé' : 'Winkelwagen geleegd' ?>');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                    }
                },
                
                validateOrder() {
                    if (this.cart.items.length === 0) {
                        alert('<?= $customer['language'] === 'fr' ? 'Votre panier est vide' : 'Uw winkelwagen is leeg' ?>');
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