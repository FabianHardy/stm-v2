<?php
/**
 * Vue : Détails d'une catégorie
 * 
 * Affiche les informations complètes d'une catégorie.
 * 
 * @modified 11/11/2025 10:10 - Création initiale
 */

// 1. Capturer le contenu
ob_start();
?>

<!-- Breadcrumb -->
<nav class="flex mb-6" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-3">
        <li class="inline-flex items-center">
            <a href="/stm/admin/dashboard" class="text-gray-700 hover:text-indigo-600">
                <i class="fas fa-home mr-2"></i>Tableau de bord
            </a>
        </li>
        <li>
            <div class="flex items-center">
                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                <a href="/stm/admin/categories" class="text-gray-700 hover:text-indigo-600">Catégories</a>
            </div>
        </li>
        <li>
            <div class="flex items-center">
                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                <span class="text-gray-500"><?= htmlspecialchars($category['name_fr']) ?></span>
            </div>
        </li>
    </ol>
</nav>

<!-- Messages flash -->
<?php if ($success = \Core\Session::getFlash('success')): ?>
<div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded">
    <div class="flex items-center">
        <i class="fas fa-check-circle text-green-500 mr-3"></i>
        <p class="text-green-700"><?= htmlspecialchars($success) ?></p>
    </div>
</div>
<?php endif; ?>

<?php if ($error = \Core\Session::getFlash('error')): ?>
<div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
    <div class="flex items-center">
        <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
        <p class="text-red-700"><?= htmlspecialchars($error) ?></p>
    </div>
</div>
<?php endif; ?>

<!-- En-tête avec actions -->
<div class="flex justify-between items-start mb-6">
    <div class="flex items-center">
        <?php if (!empty($category['icon_path'])): ?>
            <img src="<?= htmlspecialchars($category['icon_path']) ?>" 
                 alt="<?= htmlspecialchars($category['name_fr']) ?>" 
                 class="h-16 w-16 mr-4 rounded">
        <?php else: ?>
            <div class="h-16 w-16 mr-4 rounded flex items-center justify-center" 
                 style="background-color: <?= htmlspecialchars($category['color']) ?>">
                <i class="fas fa-tag text-white text-2xl"></i>
            </div>
        <?php endif; ?>
        <div>
            <h1 class="text-3xl font-bold text-gray-900"><?= htmlspecialchars($category['name_fr']) ?></h1>
            <?php if (!empty($category['name_nl'])): ?>
                <p class="text-gray-600 mt-1"><?= htmlspecialchars($category['name_nl']) ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="flex gap-2">
        <a href="/stm/admin/categories/<?= $category['id'] ?>/edit" 
           class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
            <i class="fas fa-edit mr-2"></i>Modifier
        </a>
        <form method="POST" action="/stm/admin/categories/<?= $category['id'] ?>/delete" 
              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?')" 
              class="inline">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <button type="submit" 
                    class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                <i class="fas fa-trash mr-2"></i>Supprimer
            </button>
        </form>
    </div>
</div>

<!-- Informations principales -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Carte principale -->
    <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Informations</h2>
        
        <div class="space-y-4">
            <!-- Code -->
            <div class="flex items-start border-b border-gray-200 pb-4">
                <div class="w-1/3">
                    <span class="text-sm font-medium text-gray-500">Code</span>
                </div>
                <div class="w-2/3">
                    <code class="px-3 py-1 bg-gray-100 rounded text-gray-900 font-mono">
                        <?= htmlspecialchars($category['code']) ?>
                    </code>
                </div>
            </div>

            <!-- Nom français -->
            <div class="flex items-start border-b border-gray-200 pb-4">
                <div class="w-1/3">
                    <span class="text-sm font-medium text-gray-500">Nom (FR)</span>
                </div>
                <div class="w-2/3">
                    <span class="text-gray-900"><?= htmlspecialchars($category['name_fr']) ?></span>
                </div>
            </div>

            <!-- Nom néerlandais -->
            <div class="flex items-start border-b border-gray-200 pb-4">
                <div class="w-1/3">
                    <span class="text-sm font-medium text-gray-500">Nom (NL)</span>
                </div>
                <div class="w-2/3">
                    <?php if (!empty($category['name_nl'])): ?>
                        <span class="text-gray-900"><?= htmlspecialchars($category['name_nl']) ?></span>
                    <?php else: ?>
                        <span class="text-gray-400 italic">Non renseigné</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Couleur -->
            <div class="flex items-start border-b border-gray-200 pb-4">
                <div class="w-1/3">
                    <span class="text-sm font-medium text-gray-500">Couleur</span>
                </div>
                <div class="w-2/3 flex items-center gap-3">
                    <div class="h-8 w-8 rounded border border-gray-300" 
                         style="background-color: <?= htmlspecialchars($category['color']) ?>">
                    </div>
                    <code class="px-3 py-1 bg-gray-100 rounded text-gray-900 font-mono">
                        <?= htmlspecialchars($category['color']) ?>
                    </code>
                </div>
            </div>

            <!-- Icône -->
            <div class="flex items-start border-b border-gray-200 pb-4">
                <div class="w-1/3">
                    <span class="text-sm font-medium text-gray-500">Icône</span>
                </div>
                <div class="w-2/3">
                    <?php if (!empty($category['icon_path'])): ?>
                        <div class="flex items-center gap-3">
                            <img src="<?= htmlspecialchars($category['icon_path']) ?>" 
                                 alt="Icône" 
                                 class="h-8 w-8">
                            <code class="text-xs text-gray-600 font-mono">
                                <?= htmlspecialchars($category['icon_path']) ?>
                            </code>
                        </div>
                    <?php else: ?>
                        <span class="text-gray-400 italic">Aucune icône</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Ordre d'affichage -->
            <div class="flex items-start border-b border-gray-200 pb-4">
                <div class="w-1/3">
                    <span class="text-sm font-medium text-gray-500">Ordre d'affichage</span>
                </div>
                <div class="w-2/3">
                    <span class="text-gray-900 font-semibold"><?= htmlspecialchars($category['display_order']) ?></span>
                </div>
            </div>

            <!-- Statut -->
            <div class="flex items-start">
                <div class="w-1/3">
                    <span class="text-sm font-medium text-gray-500">Statut</span>
                </div>
                <div class="w-2/3">
                    <?php if ($category['is_active']): ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            <i class="fas fa-check-circle mr-2"></i>Active
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                            <i class="fas fa-times-circle mr-2"></i>Inactive
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Métadonnées -->
    <div class="space-y-6">
        <!-- Statistiques -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Statistiques</h2>
            
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Produits associés</span>
                    <span class="text-lg font-bold text-indigo-600">
                        <i class="fas fa-box mr-1"></i>
                        <?= isset($productsCount) ? $productsCount : '0' ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Dates -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Dates</h2>
            
            <div class="space-y-3">
                <div>
                    <span class="text-xs text-gray-500 block mb-1">Créée le</span>
                    <span class="text-sm text-gray-900">
                        <?= date('d/m/Y à H:i', strtotime($category['created_at'])) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Actions</h2>
            
            <div class="space-y-2">
                <form method="POST" action="/stm/admin/categories/<?= $category['id'] ?>/toggle" class="w-full">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <button type="submit" 
                            class="w-full px-4 py-2 <?= $category['is_active'] ? 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200' : 'bg-green-100 text-green-700 hover:bg-green-200' ?> rounded-lg transition text-sm font-medium">
                        <i class="fas fa-<?= $category['is_active'] ? 'toggle-off' : 'toggle-on' ?> mr-2"></i>
                        <?= $category['is_active'] ? 'Désactiver' : 'Activer' ?>
                    </button>
                </form>
                
                <a href="/stm/admin/categories" 
                   class="block w-full px-4 py-2 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-lg transition text-sm font-medium text-center">
                    <i class="fas fa-arrow-left mr-2"></i>Retour à la liste
                </a>
            </div>
        </div>
    </div>
</div>

<?php
// 2. Variables pour le layout
$content = ob_get_clean();
$title = htmlspecialchars($category['name_fr']) . ' - Catégorie - STM';

// 3. Inclure le layout (2 niveaux à remonter depuis categories/)
require __DIR__ . '/../../layouts/admin.php';
?>
