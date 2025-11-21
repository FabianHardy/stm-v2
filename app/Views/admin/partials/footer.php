<?php
/**
 * Footer Admin
 * 
 * Footer simple avec :
 * - Copyright
 * - Liens utiles
 * - Version de l'application
 * 
 * @package STM
 * @version 2.0
 */

$currentYear = date('Y');
$appVersion = '2.0.0';
?>

<footer class="bg-white border-t border-gray-200 py-4">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
            
            <!-- Copyright -->
            <div class="text-sm text-gray-500">
                <p>
                    © <?= $currentYear ?> 
                    <span class="font-semibold text-gray-700">Trendy Foods</span> 
                    - Tous droits réservés
                </p>
            </div>
            
            <!-- Liens utiles -->
            <div class="flex items-center gap-6 text-sm">
                <a href="#" class="text-gray-600 hover:text-primary-600 transition-colors">
                    <i class="fas fa-book mr-1"></i>
                    Documentation
                </a>
                <a href="#" class="text-gray-600 hover:text-primary-600 transition-colors">
                    <i class="fas fa-life-ring mr-1"></i>
                    Support
                </a>
                <span class="text-gray-400">|</span>
                <span class="text-gray-500">
                    Version <span class="font-mono font-medium"><?= $appVersion ?></span>
                </span>
            </div>
            
        </div>
    </div>
</footer>
