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
        
        /* Lightbox overlay */
        .lightbox-overlay {
            background: rgba(0, 0, 0, 0.9);
        }
        
        /* Badge catégorie avec couleur dynamique */
        .category-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        /* Sticky nav offset */
        .scroll-mt {
            scroll-margin-top: 8rem;
        }
    </style>
</head>
<body class="bg-gray-50" x-data="cartManager()" x-init="init()">

    <!-- Header -->
    <header class="bg-white shadow-md sticky top-0 z-40">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($campaign['name']) ?></h1>
                    <p class="text-sm text-gray-600">
                        <i class="fas fa-building mr-1"></i>
                        <?= htmlspecialchars($customer['company_name']) ?> 
                        <span class="mx-2">•</span>
                        <?= htmlspecialchars($customer['customer_number']) ?>
                    </p>
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

    <!-- Navigation catégories (sticky) -->
    <nav class="bg-white border-b sticky top-[72px] z-30 shadow-sm">
        <div class="container mx-auto px-4">
            <div class="flex overflow-x-auto py-3 space-x-4">
                <?php foreach ($categories as $category): ?>
                <a href="#category-<?= $category['id'] ?>" 
                   class="category-badge whitespace-nowrap hover:opacity-80 transition"
                   style="background-color: <?= htmlspecialchars($category['color']) ?>20; color: <?= htmlspecialchars($category['color']) ?>;">
                    <?= htmlspecialchars($category['name_fr']) ?>
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
                <!-- Section catégorie -->
                <section id="category-<?= $category['id'] ?>" class="mb-12 scroll-mt">
                    <h2 class="text-3xl font-bold mb-6 flex items-center">
                        <span class="w-2 h-8 mr-3 rounded" style="background-color: <?= htmlspecialchars($category['color']) ?>;"></span>
                        <?= htmlspecialchars($category['name_fr']) ?>
                    </h2>
                    
                    <!-- Grid produits -->
                    <?php 
                    // Filtrer les produits commandables uniquement
                    $orderableProducts = array_filter($category['products'], function($p) {
                        return $p['is_orderable'] === true;
                    });
                    $productCount = count($orderableProducts);
                    
                    // Si aucun produit disponible, ne pas afficher la catégorie
                    if ($productCount === 0) continue;
                    
                    // Grid dynamique : 1 colonne si 1 produit, 2 colonnes sinon
                    $gridClass = $productCount === 1 ? 'grid-cols-1' : 'grid-cols-1 md:grid-cols-2';
                    ?>
                    <div class="grid <?= $gridClass ?> gap-6">
                        <?php foreach ($orderableProducts as $product): ?>
                        <!-- Card produit -->
                        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
                            
                            <!-- Image -->
                            <div class="relative h-48 bg-gray-100 cursor-pointer" @click="openLightbox('<?= htmlspecialchars($product['image_fr']) ?>')">
                                <?php if (!empty($product['image_fr'])): ?>
                                    <img src="/stm/<?= htmlspecialchars($product['image_fr']) ?>" 
                                         alt="<?= htmlspecialchars($product['name_fr']) ?>"
                                         class="w-full h-full object-contain p-4">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                                        <i class="fas fa-image text-6xl"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Icône zoom -->
                                <div class="absolute bottom-2 right-2 bg-white bg-opacity-90 rounded-full p-2">
                                    <i class="fas fa-search-plus text-gray-600"></i>
                                </div>
                            </div>
                            
                            <!-- Infos produit -->
                            <div class="p-4">
                                <h3 class="text-lg font-bold text-gray-800 mb-2">
                                    <?= htmlspecialchars($product['name_fr']) ?>
                                </h3>
                                
                                <?php if (!empty($product['description'])): ?>
                                <p class="text-sm text-gray-600 mb-3 line-clamp-2">
                                    <?= htmlspecialchars($product['description_fr']) ?>
                                </p>
                                <?php endif; ?>
                                

                                <!-- Quotas disponibles -->
                                <div class="text-sm text-gray-600 mb-4 space-y-1">
                                    <?php if (!is_null($product['max_per_customer'])): ?>
                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 space-y-1">
                                        <div class="flex items-center text-sm">
                                            <i class="fas fa-box w-4 text-blue-600 mr-2"></i>
                                            <span class="font-semibold">Maximum autorisé :</span>
                                            <span class="ml-1 font-bold text-blue-600"><?= $product['max_per_customer'] ?> unités</span>
                                        </div>
                                        <div class="flex items-center text-sm">
                                            <i class="fas fa-check-circle w-4 text-green-600 mr-2"></i>
                                            <span class="font-semibold">Reste disponible :</span>
                                            <span class="ml-1 font-bold text-green-600"><?= $product['available_for_customer'] ?> unités</span>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                </div>
                                
                                <!-- Formulaire ajout panier -->
                                <div class="flex items-center gap-2">
                                    <input type="number" 
                                           min="1" 
                                           max="<?= $product['max_orderable'] ?>" 
                                           value="1"
                                           id="qty-<?= $product['id'] ?>"
                                           class="w-20 px-3 py-2 border rounded-lg text-center">
                                    
                                    <button @click="addToCart(<?= $product['id'] ?>, <?= $product['max_orderable'] ?>)"
                                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition">
                                        <i class="fas fa-cart-plus mr-2"></i>Ajouter
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endforeach; ?>
                
            </div>
            
            <!-- Panier (sidebar desktop) -->
            <aside class="hidden lg:block w-80 sticky top-[145px] self-start">
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-2xl font-bold mb-4 flex items-center">
                        <i class="fas fa-shopping-cart mr-2 text-blue-600"></i>
                        Mon panier
                        <span x-show="cartItemCount > 0" 
                              x-text="'(' + cartItemCount + ')'" 
                              class="ml-2 text-blue-600">
                        </span>
                    </h2>
                    
                    <!-- Panier vide -->
                    <div x-show="cart.items.length === 0" class="text-center py-8 text-gray-500">
                        <i class="fas fa-shopping-cart text-6xl mb-4 text-gray-300"></i>
                        <p>Votre panier est vide</p>
                    </div>
                    
                    <!-- Liste produits panier -->
                    <div x-show="cart.items.length > 0" class="space-y-4 mb-4 max-h-96 overflow-y-auto">
                        <template x-for="item in cart.items" :key="item.product_id">
                            <div class="border-b pb-4">
                                <div class="flex justify-between items-start mb-2">
                                    <h4 class="font-semibold text-sm flex-1" x-text="item.product_name"></h4>
                                    <button @click="removeFromCart(item.product_id)" 
                                            class="text-red-500 hover:text-red-700 ml-2">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-2">
                                        <button @click="updateQuantity(item.product_id, item.quantity - 1)"
                                                class="w-6 h-6 bg-gray-200 rounded hover:bg-gray-300">
                                            <i class="fas fa-minus text-xs"></i>
                                        </button>
                                        <span class="w-8 text-center font-bold" x-text="item.quantity"></span>
                                        <button @click="updateQuantity(item.product_id, item.quantity + 1)"
                                                class="w-6 h-6 bg-gray-200 rounded hover:bg-gray-300">
                                            <i class="fas fa-plus text-xs"></i>
                                        </button>
                                    </div>

                                </div>
                            </div>
                        </template>
                    </div>
                    
                    <!-- Actions -->
                    <div x-show="cart.items.length > 0" class="border-t pt-4">
                        
                        <button @click="validateOrder()" 
                                class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg transition mb-2">
                            <i class="fas fa-check mr-2"></i>Valider ma commande
                        </button>
                        
                        <button @click="clearCart()" 
                                class="w-full bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 rounded-lg transition">
                            <i class="fas fa-trash mr-2"></i>Vider le panier
                        </button>
                    </div>
                </div>
            </aside>
            
        </div>
    </div>

    <!-- Modal panier mobile -->
    <div x-show="showCartMobile" 
         x-cloak
         @click.self="toggleCartMobile()"
         class="fixed inset-0 bg-black bg-opacity-50 z-50 lg:hidden"
         style="display: none;">
        <div class="fixed bottom-0 inset-x-0 bg-white rounded-t-2xl shadow-2xl max-h-[80vh] overflow-hidden"
             @click.away="showCartMobile = false">
            
            <!-- Header modal -->
            <div class="flex justify-between items-center p-4 border-b bg-gray-50">
                <h3 class="text-xl font-bold flex items-center">
                    <i class="fas fa-shopping-cart mr-2 text-blue-600"></i>
                    Mon panier
                    <span x-show="cartItemCount > 0" 
                          x-text="'(' + cartItemCount + ')'" 
                          class="ml-2 text-blue-600">
                    </span>
                </h3>
                <button @click="toggleCartMobile()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <!-- Contenu panier mobile -->
            <div class="p-4 overflow-y-auto" style="max-height: calc(80vh - 200px);">
                <!-- Panier vide -->
                <div x-show="cart.items.length === 0" class="text-center py-8 text-gray-500">
                    <i class="fas fa-shopping-cart text-6xl mb-4 text-gray-300"></i>
                    <p>Votre panier est vide</p>
                </div>
                
                <!-- Liste produits -->
                <div x-show="cart.items.length > 0" class="space-y-4 mb-4">
                    <template x-for="item in cart.items" :key="item.product_id">
                        <div class="border-b pb-4">
                            <div class="flex justify-between items-start mb-2">
                                <h4 class="font-semibold text-sm flex-1" x-text="item.product_name"></h4>
                                <button @click="removeFromCart(item.product_id)" 
                                        class="text-red-500 hover:text-red-700 ml-2">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2">
                                    <button @click="updateQuantity(item.product_id, item.quantity - 1)"
                                            class="w-8 h-8 bg-gray-200 rounded hover:bg-gray-300">
                                        <i class="fas fa-minus text-xs"></i>
                                    </button>
                                    <span class="w-10 text-center font-bold" x-text="item.quantity"></span>
                                    <button @click="updateQuantity(item.product_id, item.quantity + 1)"
                                            class="w-8 h-8 bg-gray-200 rounded hover:bg-gray-300">
                                        <i class="fas fa-plus text-xs"></i>
                                    </button>
                                </div>

                            </div>
                        </div>
                    </template>
                </div>
            </div>
            
            <!-- Footer modal -->
            <div x-show="cart.items.length > 0" class="border-t bg-gray-50 p-4">
                
                <button @click="validateOrder()" 
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg transition mb-2">
                    <i class="fas fa-check mr-2"></i>Valider ma commande
                </button>
                
                <button @click="clearCart()" 
                        class="w-full bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 rounded-lg transition">
                    <i class="fas fa-trash mr-2"></i>Vider le panier
                </button>
            </div>
        </div>
    </div>

    <!-- Lightbox image -->
    <div x-show="showLightbox" 
         x-cloak
         @click="closeLightbox()"
         class="fixed inset-0 lightbox-overlay z-50 flex items-center justify-center p-4"
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
                },
                
                get cartItemCount() {
                    return this.cart.items.reduce((total, item) => total + item.quantity, 0);
                },
                
                toggleCartMobile() {
                    this.showCartMobile = !this.showCartMobile;
                },
                
                openLightbox(imagePath) {
                    this.lightboxImage = '/stm/' + imagePath;
                    this.showLightbox = true;
                },
                
                closeLightbox() {
                    this.showLightbox = false;
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
                    
                    // Redirection vers page de validation (sous-tâche 3)
                    window.location.href = '/stm/c/<?= $uuid ?>/order';
                },
                
                showNotification(message) {
                    // Simple notification (peut être amélioré avec un toast)
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