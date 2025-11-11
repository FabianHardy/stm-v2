<!-- 
    Composant : Header Client
    Description : En-tête de l'interface client avec logo et navigation
-->

<header class="bg-white shadow-md">
    <nav class="container mx-auto px-4 py-4">
        <div class="flex items-center justify-between">
            
            <!-- Logo -->
            <div class="flex items-center space-x-3">
                <img src="/assets/images/logos/trendy-foods-logo.png" 
                     alt="Trendy Foods" 
                     class="h-12"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <span class="text-2xl font-bold text-primary-600" style="display:none;">
                    Trendy Foods
                </span>
            </div>
            
            <!-- Navigation principale -->
            <div class="hidden md:flex items-center space-x-6">
                <a href="/" class="text-gray-700 hover:text-primary-600 font-medium transition">
                    <?= $lang === 'nl' ? 'Home' : 'Accueil' ?>
                </a>
                
                <?php if (isset($_SESSION['customer_logged_in'])): ?>
                    <a href="/campaign/<?= $_SESSION['campaign_slug'] ?? '' ?>/products" 
                       class="text-gray-700 hover:text-primary-600 font-medium transition">
                        <?= $lang === 'nl' ? 'Producten' : 'Promotions' ?>
                    </a>
                    <a href="/cart" class="text-gray-700 hover:text-primary-600 font-medium transition relative">
                        <?= $lang === 'nl' ? 'Winkelwagen' : 'Panier' ?>
                        <?php if (isset($_SESSION['cart_count']) && $_SESSION['cart_count'] > 0): ?>
                            <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                <?= $_SESSION['cart_count'] ?>
                            </span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- Actions utilisateur -->
            <div class="flex items-center space-x-4">
                
                <!-- Sélecteur de langue -->
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" 
                            class="flex items-center space-x-1 text-gray-700 hover:text-primary-600">
                        <span class="font-medium uppercase"><?= $lang ?? 'fr' ?></span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" 
                         @click.away="open = false"
                         class="absolute right-0 mt-2 w-24 bg-white rounded-lg shadow-lg py-1 z-50">
                        <a href="?lang=fr" class="block px-4 py-2 text-sm hover:bg-gray-100">Français</a>
                        <a href="?lang=nl" class="block px-4 py-2 text-sm hover:bg-gray-100">Nederlands</a>
                    </div>
                </div>
                
                <!-- Compte client -->
                <?php if (isset($_SESSION['customer_logged_in'])): ?>
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" 
                                class="flex items-center space-x-2 text-gray-700 hover:text-primary-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span class="hidden md:inline"><?= $_SESSION['customer_name'] ?? '' ?></span>
                        </button>
                        <div x-show="open" 
                             @click.away="open = false"
                             class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50">
                            <a href="/logout" class="block px-4 py-2 text-red-600 hover:bg-red-50">
                                <?= $lang === 'nl' ? 'Afmelden' : 'Déconnexion' ?>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Menu mobile -->
                <button class="md:hidden text-gray-700" @click="mobileMenuOpen = !mobileMenuOpen">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
            
        </div>
    </nav>
</header>
