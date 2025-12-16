<?php
/**
 * Header Admin / Topbar
 *
 * Barre supérieure avec :
 * - Bandeau d'impersonation (si actif)
 * - Bouton hamburger (mobile)
 * - Fil d'Ariane
 * - Zone de recherche
 * - Notifications
 * - Menu utilisateur
 *
 * @package STM
 * @version 2.1
 * @modified 16/12/2025 - Ajout bandeau "Se connecter en tant que"
 */

use Core\Session;
use App\Helpers\PermissionHelper;

$currentUser = Session::get('user');
$userName = $currentUser['username'] ?? 'Admin';
$userRole = $currentUser['role'] ?? 'admin';

// Vérifier si on est en mode impersonate
$isImpersonating = Session::get('impersonate_original_user') !== null;
$originalUser = Session::get('impersonate_original_user');

// Permissions pour le menu utilisateur
$canViewSettings = PermissionHelper::can('settings.view');

// Nombre de notifications non lues (à implémenter)
$unreadNotifications = 3;
?>

<?php if ($isImpersonating): ?>
<!-- Bandeau d'impersonation -->
<div class="bg-orange-500 text-white py-2 px-4 shadow-md relative z-50">
    <div class="max-w-7xl mx-auto flex items-center justify-between">
        <div class="flex items-center gap-3">
            <i class="fas fa-user-secret text-lg"></i>
            <span class="font-medium">
                Vous êtes connecté en tant que <strong><?= htmlspecialchars($userName) ?></strong>
                <span class="text-orange-200">(<?= ucfirst($userRole) ?>)</span>
            </span>
        </div>
        <a href="/stm/admin/impersonate/stop" 
           class="inline-flex items-center gap-2 px-4 py-1.5 bg-white text-orange-600 rounded-lg hover:bg-orange-100 transition font-medium text-sm shadow-sm">
            <i class="fas fa-sign-out-alt"></i>
            Revenir à mon compte (<?= htmlspecialchars($originalUser['username'] ?? 'Admin') ?>)
        </a>
    </div>
</div>
<?php endif; ?>

<header class="sticky top-0 z-30 bg-white shadow-sm border-b border-gray-200 <?= $isImpersonating ? 'border-t-4 border-t-orange-500' : '' ?>">
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">

            <!-- Partie gauche : Hamburger + Fil d'Ariane -->
            <div class="flex items-center gap-4 flex-1">

                <!-- Bouton hamburger (mobile uniquement) -->
                <button @click="sidebarOpen = !sidebarOpen"
                        type="button"
                        class="lg:hidden -m-2.5 p-2.5 text-gray-700 hover:text-primary-600 transition-colors">
                    <span class="sr-only">Ouvrir la navigation</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                <!-- Fil d'Ariane (desktop uniquement) -->
                <nav class="hidden lg:flex items-center text-sm" aria-label="Breadcrumb">
                    <ol class="flex items-center gap-2">
                        <li class="flex items-center">
                            <a href="/stm/admin/dashboard" class="text-gray-500 hover:text-primary-600 transition-colors">
                                <i class="fas fa-home"></i>
                            </a>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                            <span class="text-gray-900 font-medium">
                                <?= htmlspecialchars($title ?? 'Dashboard') ?>
                            </span>
                        </li>
                    </ol>
                </nav>

            </div>

            <!-- Partie droite : Recherche + Notifications + User Menu -->
            <div class="flex items-center gap-3">

                <!-- Barre de recherche (desktop uniquement) -->
                <div class="hidden md:block" x-data="{ searchOpen: false }">
                    <div class="relative">
                        <button @click="searchOpen = !searchOpen"
                                class="flex items-center gap-2 px-4 py-2 text-sm text-gray-600 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <i class="fas fa-search"></i>
                            <span class="hidden lg:inline">Rechercher...</span>
                            <kbd class="hidden lg:inline px-2 py-1 text-xs font-semibold text-gray-800 bg-white border border-gray-200 rounded">
                                Ctrl+K
                            </kbd>
                        </button>

                        <!-- Modal de recherche -->
                        <div x-show="searchOpen"
                             x-transition
                             @click.away="searchOpen = false"
                             class="absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-xl border border-gray-200 p-4"
                             style="display: none;">
                            <input type="text"
                                   placeholder="Rechercher campagnes, produits, clients..."
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                   autofocus>
                            <div class="mt-3 text-xs text-gray-500">
                                <p>Appuyez sur <kbd class="px-1 py-0.5 bg-gray-100 border border-gray-300 rounded">↵</kbd> pour rechercher</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="relative" x-data="{ notifOpen: false }">
                    <button @click="notifOpen = !notifOpen"
                            type="button"
                            class="relative p-2 text-gray-600 hover:text-primary-600 hover:bg-gray-50 rounded-lg transition-colors">
                        <span class="sr-only">Notifications</span>
                        <i class="fas fa-bell text-xl"></i>

                        <!-- Badge de notifications -->
                        <?php if ($unreadNotifications > 0): ?>
                        <span class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">
                            <?= $unreadNotifications ?>
                        </span>
                        <?php endif; ?>
                    </button>

                    <!-- Dropdown notifications -->
                    <div x-show="notifOpen"
                         x-transition
                         @click.away="notifOpen = false"
                         class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-gray-200 overflow-hidden"
                         style="display: none;">

                        <!-- Header -->
                        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
                            <button class="text-xs text-primary-600 hover:text-primary-700">
                                Tout marquer comme lu
                            </button>
                        </div>

                        <!-- Liste des notifications -->
                        <div class="max-h-96 overflow-y-auto">
                            <!-- Notification 1 -->
                            <a href="#" class="block px-4 py-3 hover:bg-gray-50 transition-colors border-b border-gray-100">
                                <div class="flex gap-3">
                                    <div class="flex-shrink-0">
                                        <div class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center">
                                            <i class="fas fa-shopping-cart text-primary-600"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-gray-900 font-medium">Nouvelle commande</p>
                                        <p class="text-xs text-gray-600 mt-1">Client ABC a passé une commande</p>
                                        <p class="text-xs text-gray-400 mt-1">Il y a 5 minutes</p>
                                    </div>
                                </div>
                            </a>

                            <!-- Notification 2 -->
                            <a href="#" class="block px-4 py-3 hover:bg-gray-50 transition-colors border-b border-gray-100">
                                <div class="flex gap-3">
                                    <div class="flex-shrink-0">
                                        <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                            <i class="fas fa-check text-green-600"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-gray-900 font-medium">Campagne activée</p>
                                        <p class="text-xs text-gray-600 mt-1">La campagne "Printemps 2025" est maintenant active</p>
                                        <p class="text-xs text-gray-400 mt-1">Il y a 1 heure</p>
                                    </div>
                                </div>
                            </a>

                            <!-- Notification 3 -->
                            <a href="#" class="block px-4 py-3 hover:bg-gray-50 transition-colors">
                                <div class="flex gap-3">
                                    <div class="flex-shrink-0">
                                        <div class="h-10 w-10 rounded-full bg-yellow-100 flex items-center justify-center">
                                            <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-gray-900 font-medium">Stock faible</p>
                                        <p class="text-xs text-gray-600 mt-1">3 produits ont un stock inférieur à 10</p>
                                        <p class="text-xs text-gray-400 mt-1">Il y a 2 heures</p>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!-- Footer -->
                        <div class="px-4 py-3 bg-gray-50 border-t border-gray-200">
                            <a href="#" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                                Voir toutes les notifications →
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Menu utilisateur -->
                <div class="relative" x-data="{ userMenuOpen: false }">
                    <button @click="userMenuOpen = !userMenuOpen"
                            type="button"
                            class="flex items-center gap-2 p-2 hover:bg-gray-50 rounded-lg transition-colors <?= $isImpersonating ? 'ring-2 ring-orange-400' : '' ?>">
                        <div class="h-8 w-8 rounded-full <?= $isImpersonating ? 'bg-orange-500' : 'bg-primary-600' ?> flex items-center justify-center text-white font-semibold text-sm">
                            <?php if ($isImpersonating): ?>
                                <i class="fas fa-user-secret text-xs"></i>
                            <?php else: ?>
                                <?= strtoupper(substr($userName, 0, 2)) ?>
                            <?php endif; ?>
                        </div>
                        <div class="hidden md:block text-left">
                            <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($userName) ?></p>
                            <p class="text-xs <?= $isImpersonating ? 'text-orange-600 font-medium' : 'text-gray-500' ?>">
                                <?= ucfirst($userRole) ?>
                                <?= $isImpersonating ? ' (simulé)' : '' ?>
                            </p>
                        </div>
                        <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                    </button>

                    <!-- Dropdown user menu -->
                    <div x-show="userMenuOpen"
                         x-transition
                         @click.away="userMenuOpen = false"
                         class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-xl border border-gray-200 overflow-hidden"
                         style="display: none;">

                        <!-- User info -->
                        <div class="px-4 py-3 <?= $isImpersonating ? 'bg-orange-50 border-b border-orange-200' : 'bg-gray-50 border-b border-gray-200' ?>">
                            <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($userName) ?></p>
                            <p class="text-xs text-gray-500 mt-0.5"><?= htmlspecialchars($currentUser['email'] ?? '') ?></p>
                            <?php if ($isImpersonating): ?>
                            <p class="text-xs text-orange-600 mt-1 font-medium">
                                <i class="fas fa-user-secret mr-1"></i>Mode simulation
                            </p>
                            <?php endif; ?>
                        </div>

                        <!-- Menu items -->
                        <div class="py-1">
                            <?php if ($isImpersonating): ?>
                            <!-- Revenir à mon compte -->
                            <a href="/stm/admin/impersonate/stop" class="flex items-center gap-3 px-4 py-2 text-sm text-orange-600 hover:bg-orange-50 font-medium">
                                <i class="fas fa-sign-out-alt w-4"></i>
                                Revenir à mon compte
                            </a>
                            <div class="border-t border-gray-200 my-1"></div>
                            <?php endif; ?>

                            <!-- Mon profil - accessible à tous -->
                            <a href="/stm/admin/profile" class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-user w-4"></i>
                                Mon profil
                            </a>

                            <!-- Paramètres - seulement si permission settings.view -->
                            <?php if ($canViewSettings && !$isImpersonating): ?>
                            <a href="/stm/admin/settings" class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-cog w-4"></i>
                                Paramètres
                            </a>
                            <?php endif; ?>

                            <!-- Aide - accessible à tous -->
                            <a href="#" class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-question-circle w-4"></i>
                                Aide
                            </a>
                        </div>

                        <!-- Déconnexion -->
                        <?php if (!$isImpersonating): ?>
                        <div class="border-t border-gray-200">
                            <a href="/stm/admin/logout" class="flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                <i class="fas fa-sign-out-alt w-4"></i>
                                Déconnexion
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</header>

<!-- Raccourci clavier pour la recherche -->
<script>
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            document.querySelector('[x-data*="searchOpen"]').__x.$data.searchOpen = true;
        }
    });
</script>
