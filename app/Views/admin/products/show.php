<?php
/**
 * Vue : Détails d'un Promotion
 * 
 * Affichage complet des informations d'un Promotion
 * 
 * @created 11/11/2025 22:50
 * @modified 11/11/2025 23:05 - Amélioration mise en page (sections claires)
 */

use Core\Session;

// Démarrer la capture du contenu pour le layout
ob_start();
?>

<!-- En-tête de la page -->
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">
                <?php echo htmlspecialchars($product['name_fr']); ?>
            </h1>
            <p class="mt-2 text-sm text-gray-600">
                Code: <span class="font-medium"><?php echo htmlspecialchars($product['product_code']); ?></span>
                <?php if ($product['ean']): ?>
                    • EAN: <span class="font-medium"><?php echo htmlspecialchars($product['ean']); ?></span>
                <?php endif; ?>
            </p>
        </div>
        <div class="flex gap-2">
            <a href="/stm/admin/products" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                ← Retour à la liste
            </a>
            <a href="/stm/admin/products/<?php echo $product['id']; ?>/edit" 
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                ✏️ Modifier
            </a>
        </div>
    </div>

    <!-- Breadcrumb -->
    <nav class="mt-4 flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="/stm/admin/dashboard" class="text-gray-700 hover:text-gray-900">
                    🏠 Dashboard
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <span class="mx-2 text-gray-400">/</span>
                    <a href="/stm/admin/products" class="text-gray-700 hover:text-gray-900">
                        Promotions
                    </a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <span class="mx-2 text-gray-400">/</span>
                    <span class="text-gray-500"><?php echo htmlspecialchars($product['product_code']); ?></span>
                </div>
            </li>
        </ol>
    </nav>
</div>

<!-- Badges de statut et catégorie -->
<div class="flex items-center gap-3 mb-6">
    <!-- Badge statut -->
    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $product['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
        <?php echo $product['is_active'] ? '✓ Actif' : '✗ Inactif'; ?>
    </span>
    
    <!-- Badge catégorie -->
    <?php if (!empty($product['category_name'])): ?>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800">
            📁 <?php echo htmlspecialchars($product['category_name']); ?>
        </span>
    <?php endif; ?>
    
    <!-- Badge ordre d'affichage -->
    <?php if ($product['display_order'] > 0): ?>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
            #️⃣ Ordre: <?php echo $product['display_order']; ?>
        </span>
    <?php endif; ?>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    
    <!-- Colonne gauche -->
    <div class="space-y-6">
        
        <!-- Section : Informations de base -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    📋 Informations de base
                </h3>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Code Promotion</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-medium">
                            <?php echo htmlspecialchars($product['product_code']); ?>
                        </dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Numéro de colis</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <?php echo htmlspecialchars($product['package_number']); ?>
                        </dd>
                    </div>
                    
                    <?php if ($product['ean']): ?>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Code EAN</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-mono">
                                <?php echo htmlspecialchars($product['ean']); ?>
                            </dd>
                        </div>
                    <?php endif; ?>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Catégorie</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <?php echo !empty($product['category_name']) ? htmlspecialchars($product['category_name']) : '<span class="text-gray-400">Non catégorisé</span>'; ?>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Section : Images -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    🖼️ Images du Promotion
                </h3>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <!-- Image FR -->
                    <div>
                        <p class="text-sm font-medium text-gray-700 mb-2">🇫🇷 Français</p>
                        <?php if (!empty($product['image_fr'])): ?>
                            <img src="<?php echo htmlspecialchars($product['image_fr']); ?>" 
                                 alt="Image FR"
                                 class="w-full h-48 object-cover rounded-lg border-2 border-gray-200 shadow-sm">
                        <?php else: ?>
                            <div class="w-full h-48 flex items-center justify-center bg-gray-100 rounded-lg border-2 border-gray-200">
                                <span class="text-gray-400 text-sm">Aucune image</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Image NL -->
                    <div>
                        <p class="text-sm font-medium text-gray-700 mb-2">🇳🇱 Nederlands</p>
                        <?php if (!empty($product['image_nl'])): ?>
                            <img src="<?php echo htmlspecialchars($product['image_nl']); ?>" 
                                 alt="Image NL"
                                 class="w-full h-48 object-cover rounded-lg border-2 border-gray-200 shadow-sm">
                        <?php else: ?>
                            <div class="w-full h-48 flex items-center justify-center bg-gray-100 rounded-lg border-2 border-gray-200">
                                <span class="text-gray-400 text-sm">Geen afbeelding</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Colonne droite -->
    <div class="space-y-6">
        
        <!-- Section : Contenu français -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    🇫🇷 Contenu en français
                </h3>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Nom du Promotion</dt>
                        <dd class="mt-1 text-base text-gray-900 font-medium">
                            <?php echo htmlspecialchars($product['name_fr']); ?>
                        </dd>
                    </div>
                    
                    <?php if (!empty($product['description_fr'])): ?>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Description</dt>
                            <dd class="mt-1 text-sm text-gray-900 whitespace-pre-line">
                                <?php echo htmlspecialchars($product['description_fr']); ?>
                            </dd>
                        </div>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

        <!-- Section : Contenu néerlandais -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    🇳🇱 Inhoud in het Nederlands
                </h3>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Productnaam</dt>
                        <dd class="mt-1 text-base text-gray-900">
                            <?php 
                            if (!empty($product['name_nl'])) {
                                echo htmlspecialchars($product['name_nl']);
                            } else {
                                echo '<span class="text-gray-400 italic">Identique au français</span>';
                            }
                            ?>
                        </dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Beschrijving</dt>
                        <dd class="mt-1 text-sm text-gray-900 whitespace-pre-line">
                            <?php 
                            if (!empty($product['description_nl'])) {
                                echo htmlspecialchars($product['description_nl']);
                            } else {
                                echo '<span class="text-gray-400 italic">Geen beschrijving</span>';
                            }
                            ?>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Section : Paramètres -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    ⚙️ Paramètres
                </h3>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Statut</dt>
                        <dd class="mt-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $product['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $product['is_active'] ? '✓ Actif' : '✗ Inactif'; ?>
                            </span>
                        </dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Ordre d'affichage</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <?php echo $product['display_order']; ?>
                            <span class="text-gray-500 text-xs ml-2">(Plus petit = apparaît en premier)</span>
                        </dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Date de création</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <?php 
                            $date = new DateTime($product['created_at']);
                            echo $date->format('d/m/Y à H:i'); 
                            ?>
                        </dd>
                    </div>
                    
                    <?php if ($product['updated_at'] !== $product['created_at']): ?>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Dernière modification</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <?php 
                                $date = new DateTime($product['updated_at']);
                                echo $date->format('d/m/Y à H:i'); 
                                ?>
                            </dd>
                        </div>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

    </div>
</div>

<!-- Section : Actions -->
<div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-6">
    <h3 class="text-lg font-medium text-gray-900 mb-4">⚡ Actions rapides</h3>
    <div class="flex flex-wrap gap-3">
        <a href="/stm/admin/products/<?php echo $product['id']; ?>/edit" 
           class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
            ✏️ Modifier le Promotion
        </a>
        
        <form method="POST" action="/stm/admin/products/<?php echo $product['id']; ?>/delete" 
              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce Promotion ?');"
              class="inline">
            <input type="hidden" name="_token" value="<?php echo Session::get('csrf_token'); ?>">
            <input type="hidden" name="_method" value="DELETE">
            <button type="submit" 
                    class="inline-flex items-center px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50">
                🗑️ Supprimer
            </button>
        </form>
        
        <a href="/stm/admin/products" 
           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            ← Retour à la liste
        </a>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
?>