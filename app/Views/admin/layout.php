<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Administration'; ?> - STM v2</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* Personnalisation du scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased">

    <div class="flex h-screen overflow-hidden">

        <!-- Sidebar -->
        <aside class="w-64 bg-white border-r border-gray-200 flex flex-col">

            <!-- Logo -->
            <div class="h-16 flex items-center px-6 border-b border-gray-200">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-600 to-blue-500 rounded-lg flex items-center justify-center text-white font-bold text-xl">
                        S
                    </div>
                    <div class="ml-3">
                        <div class="text-sm font-bold text-gray-900">STM Admin</div>
                        <div class="text-xs text-gray-500">v2.0</div>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto px-3 py-4">
                <!-- Dashboard -->
                <a href="/stm/admin/dashboard"
                   class="flex items-center px-3 py-2 mb-1 text-sm font-medium rounded-lg <?php echo (strpos($_SERVER['REQUEST_URI'], '/dashboard') !== false) ? 'bg-purple-50 text-purple-700' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i class="fas fa-chart-line w-5"></i>
                    <span class="ml-3">Dashboard</span>
                </a>

                <!-- Campagnes -->
                <a href="/stm/admin/campaigns"
                   class="flex items-center px-3 py-2 mb-1 text-sm font-medium rounded-lg <?php echo (strpos($_SERVER['REQUEST_URI'], '/campaigns') !== false) ? 'bg-purple-50 text-purple-700' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i class="fas fa-bullhorn w-5"></i>
                    <span class="ml-3 flex-1">Campagnes</span>
                    <span class="bg-purple-100 text-purple-700 text-xs font-semibold px-2 py-0.5 rounded-full">
                        <?php
                        // Compter les campagnes (à implémenter)
                        echo '0';
                        ?>
                    </span>
                </a>

                <!-- Promotions -->
                <a href="/stm/admin/products"
                   class="flex items-center px-3 py-2 mb-1 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-box w-5"></i>
                    <span class="ml-3">Promotions</span>
                </a>

                <!-- Clients -->
                <a href="/stm/admin/customers"
                   class="flex items-center px-3 py-2 mb-1 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-users w-5"></i>
                    <span class="ml-3">Clients</span>
                </a>

                <!-- Commandes -->
                <a href="/stm/admin/orders"
                   class="flex items-center px-3 py-2 mb-1 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-shopping-cart w-5"></i>
                    <span class="ml-3 flex-1">Commandes</span>
                    <span class="bg-green-100 text-green-700 text-xs font-semibold px-2 py-0.5 rounded-full">0</span>
                </a>

                <!-- Statistiques -->
                <a href="/stm/admin/statistics"
                   class="flex items-center px-3 py-2 mb-1 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-chart-bar w-5"></i>
                    <span class="ml-3">Statistiques</span>
                </a>

                <!-- Séparateur -->
                <div class="my-4 border-t border-gray-200"></div>
                <div class="px-3 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    Paramètres
                </div>

                <!-- Mon profil -->
                <a href="/stm/admin/profile"
                   class="flex items-center px-3 py-2 mb-1 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-user w-5"></i>
                    <span class="ml-3">Mon profil</span>
                </a>

                <!-- Utilisateurs -->
                <a href="/stm/admin/users"
                   class="flex items-center px-3 py-2 mb-1 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-users-cog w-5"></i>
                    <span class="ml-3">Utilisateurs</span>
                </a>

                <!-- Configuration -->
                <a href="/stm/admin/settings"
                   class="flex items-center px-3 py-2 mb-1 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-cog w-5"></i>
                    <span class="ml-3">Configuration</span>
                </a>
            </nav>

            <!-- Footer Sidebar -->
            <div class="p-4 border-t border-gray-200">
                <div class="flex items-center text-sm text-purple-600 cursor-pointer hover:text-purple-700">
                    <i class="fas fa-question-circle"></i>
                    <span class="ml-2">Besoin d'aide ?</span>
                </div>
                <a href="#" class="block mt-2 text-xs text-gray-500 hover:text-gray-700">
                    Consulter le guide →
                </a>
            </div>

        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">

            <!-- Header -->
            <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6">

                <!-- Breadcrumb -->
                <div class="flex items-center text-sm">
                    <i class="fas fa-home text-gray-400"></i>
                    <span class="mx-2 text-gray-400">/</span>
                    <span class="text-gray-600"><?php echo $pageTitle ?? 'Dashboard'; ?></span>
                </div>

                <!-- Actions Header -->
                <div class="flex items-center space-x-4">

                    <!-- Search -->
                    <div class="relative">
                        <input type="text"
                               placeholder="Rechercher..."
                               class="pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>

                    <!-- Bouton Ctrl+K -->
                    <button class="px-3 py-2 text-xs text-gray-500 border border-gray-300 rounded-lg hover:bg-gray-50">
                        <span class="mr-1">Ctrl</span>+<span class="ml-1">K</span>
                    </button>

                    <!-- Notifications -->
                    <button class="relative p-2 text-gray-600 hover:bg-gray-100 rounded-lg">
                        <i class="fas fa-bell text-lg"></i>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                    </button>

                    <!-- User Menu -->
                    <div class="flex items-center cursor-pointer hover:bg-gray-100 rounded-lg px-3 py-2" x-data="{ open: false }">
                        <div class="w-8 h-8 bg-gradient-to-br from-purple-600 to-blue-500 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                            AD
                        </div>
                        <div class="ml-2">
                            <div class="text-sm font-medium text-gray-700">Admin</div>
                            <div class="text-xs text-gray-500"><?php echo $_SESSION['user_email'] ?? 'admin'; ?></div>
                        </div>
                        <i class="fas fa-chevron-down ml-2 text-xs text-gray-400"></i>
                    </div>

                    <!-- Déconnexion -->
                    <a href="/stm/admin/logout"
                       class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-purple-600 to-blue-500 rounded-lg hover:from-purple-700 hover:to-blue-600 transition-all duration-200">
                        Déconnexion
                    </a>
                </div>

            </header>

            <!-- Messages Flash -->
            <?php if (isset($_SESSION['flash_success'])): ?>
                <div class="mx-6 mt-4">
                    <div class="bg-green-50 border-l-4 border-green-500 text-green-800 px-4 py-3 rounded-lg shadow-sm" role="alert">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle mr-3"></i>
                            <span><?php echo $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['flash_error'])): ?>
                <div class="mx-6 mt-4">
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-800 px-4 py-3 rounded-lg shadow-sm" role="alert">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle mr-3"></i>
                            <span><?php echo $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['flash_warning'])): ?>
                <div class="mx-6 mt-4">
                    <div class="bg-yellow-50 border-l-4 border-yellow-500 text-yellow-800 px-4 py-3 rounded-lg shadow-sm" role="alert">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle mr-3"></i>
                            <span><?php echo $_SESSION['flash_warning']; unset($_SESSION['flash_warning']); ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-6">
                <?php echo $content; ?>
            </main>

            <!-- Footer -->
            <footer class="bg-white border-t border-gray-200 py-4 px-6">
                <div class="flex justify-between items-center text-sm text-gray-500">
                    <div>
                        © <?php echo date('Y'); ?> Trendy Foods - STM v2
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="#" class="hover:text-gray-700">
                            <i class="fas fa-file-alt mr-1"></i> Documentation
                        </a>
                        <a href="#" class="hover:text-gray-700">
                            <i class="fas fa-life-ring mr-1"></i> Support
                        </a>
                        <span>Version 2.0.0</span>
                    </div>
                </div>
            </footer>

        </div>

    </div>

</body>
</html>