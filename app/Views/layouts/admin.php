<?php
/**
 * Layout Admin Principal
 * 
 * Template de base réutilisable pour toutes les pages admin.
 * Inclut : header, sidebar, zone de contenu, footer et scripts.
 * 
 * Variables attendues :
 * - $title : Titre de la page
 * - $content : Contenu principal (buffer)
 * - $pageScripts : Scripts JS additionnels (optionnel)
 * 
 * @package STM
 * @version 2.0
 * @modified 10/11/2025 - Ajout récupération stats campagnes actives pour sidebar
 */

use Core\Session;
use App\Models\Campaign;

$currentUser = Session::get('user');
$currentRoute = $_SERVER['REQUEST_URI'] ?? '/admin/dashboard';

// Récupérer les statistiques pour la sidebar
try {
    $campaignModel = new Campaign();
    $campaignStats = $campaignModel->getStats();
    $activeCampaignsCount = $campaignStats['active']; // ✅ Campagnes ACTIVES uniquement
} catch (\Exception $e) {
    error_log("Erreur récupération stats campagnes: " . $e->getMessage());
    $activeCampaignsCount = 0;
}
?>
<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="<?= Session::get('csrf_token') ?>">
    
    <title><?= htmlspecialchars($title ?? 'Dashboard') ?> - STM Admin</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">
    
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js pour les interactions -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- HTMX pour les requêtes AJAX -->
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    
    <!-- Chart.js pour les graphiques -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Configuration Tailwind personnalisée -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#faf5ff',
                            100: '#f3e8ff',
                            200: '#e9d5ff',
                            300: '#d8b4fe',
                            400: '#c084fc',
                            500: '#a855f7',
                            600: '#9333ea',
                            700: '#7e22ce',
                            800: '#6b21a8',
                            900: '#581c87',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Styles personnalisés -->
    <style>
        /* Animations */
        @keyframes slideIn {
            from {
                transform: translateX(-100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .animate-slide-in {
            animation: slideIn 0.3s ease-out;
        }
        
        .animate-fade-in {
            animation: fadeIn 0.3s ease-out;
        }
        
        /* Scrollbar personnalisée */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #9333ea;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #7e22ce;
        }
        
        /* Loading spinner */
        .htmx-request .htmx-indicator {
            display: inline-block;
        }
        
        .htmx-indicator {
            display: none;
        }
    </style>
</head>
<body class="h-full" x-data="{ sidebarOpen: false }">
    
    <!-- Overlay mobile pour sidebar -->
    <div x-show="sidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="sidebarOpen = false"
         class="fixed inset-0 bg-gray-900/80 backdrop-blur-sm lg:hidden z-40"
         style="display: none;">
    </div>

    <!-- Container principal -->
    <div class="flex h-full">
        
        <!-- Sidebar -->
        <?php include __DIR__ . '/../admin/partials/sidebar.php'; ?>
        
        <!-- Zone de contenu principale -->
        <div class="flex flex-col flex-1 min-w-0 lg:pl-64">
            
            <!-- Header / Topbar -->
            <?php include __DIR__ . '/../admin/partials/header.php'; ?>
            
            <!-- Contenu principal -->
            <main class="flex-1 overflow-y-auto bg-gray-100">
                
                <!-- Messages flash -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                    <?php include __DIR__ . '/../admin/partials/flash.php'; ?>
                </div>
                
                <!-- Contenu de la page -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
                    <?= $content ?? '' ?>
                </div>
                
            </main>
            
            <!-- Footer -->
            <?php include __DIR__ . '/../admin/partials/footer.php'; ?>
            
        </div>
    </div>
    
    <!-- Scripts globaux -->
    <script>
        // Configuration HTMX
        document.body.addEventListener('htmx:configRequest', (event) => {
            event.detail.headers['X-CSRF-Token'] = document.querySelector('meta[name="csrf-token"]').content;
        });
        
        // Gestion des erreurs HTMX
        document.body.addEventListener('htmx:responseError', (event) => {
            console.error('Erreur HTMX:', event.detail);
            alert('Une erreur est survenue. Veuillez réessayer.');
        });
        
        // Auto-dismiss des alertes après 5 secondes
        setTimeout(() => {
            document.querySelectorAll('[data-auto-dismiss]').forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
        
        // Confirmation avant suppression
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-confirm]')) {
                if (!confirm(e.target.dataset.confirm)) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            }
        });
    </script>
    
    <!-- Scripts spécifiques à la page -->
    <?= $pageScripts ?? '' ?>
    
</body>
</html>