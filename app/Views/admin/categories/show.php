<?php
/**
 * Vue : Détails d'une catégorie
 *
 * Affiche les informations complètes d'une catégorie
 *
 * @created 11/11/2025
 * @modified 11/11/2025 21:00 - Correction formulaire suppression
 * @modified 2025/12/15 - Masquage conditionnel boutons selon permissions (Phase 5)
 */

use Core\Session;
use App\Helpers\PermissionHelper;

// Démarrer la capture du contenu pour le layout
ob_start();

// Récupérer les messages flash
$success = Session::getFlash('success');
$error = Session::getFlash('error');

// Vérification des permissions
$canEdit = PermissionHelper::can('categories.edit');
$canDelete = PermissionHelper::can('categories.delete');
?>

<!-- En-tête de la page -->
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Détails de la catégorie</h1>
            <p class="mt-2 text-sm text-gray-600">
                <?php echo htmlspecialchars($category['name_fr']); ?>
            </p>
        </div>
        <div class="flex gap-2">
            <?php if ($canEdit): ?>
            <a href="/stm/admin/products/categories/<?php echo $category['id']; ?>/edit"
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                ✏️ Modifier
            </a>
            <?php endif; ?>
            <a href="/stm/admin/products/categories"
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                ← Retour à la liste
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
                    <a href="/stm/admin/products/categories" class="text-gray-700 hover:text-gray-900">
                        Catégories
                    </a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <span class="mx-2 text-gray-400">/</span>
                    <span class="text-gray-500">
                        <?php echo htmlspecialchars($category['name_fr']); ?>
                    </span>
                </div>
            </li>
        </ol>
    </nav>
</div>

<!-- Messages flash -->
<?php if ($success): ?>
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline"><?php echo htmlspecialchars($success); ?></span>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
    </div>
<?php endif; ?>

<!-- Contenu principal -->
<div class="bg-white shadow overflow-hidden sm:rounded-lg">

    <!-- En-tête avec aperçu -->
    <div class="px-4 py-5 sm:px-6 bg-gray-50 border-b border-gray-200">
        <div class="flex items-center gap-4">
            <!-- Icône de la catégorie -->
            <?php if (!empty($category['icon_path'])): ?>
                <div class="flex-shrink-0 w-16 h-16 rounded-lg flex items-center justify-center"
                     style="background-color: <?php echo htmlspecialchars($category['color']); ?>20;">
                    <img src="<?php echo htmlspecialchars($category['icon_path']); ?>"
                         alt="Icône"
                         class="w-10 h-10">
                </div>
            <?php else: ?>
                <div class="flex-shrink-0 w-16 h-16 rounded-lg flex items-center justify-center"
                     style="background-color: <?php echo htmlspecialchars($category['color']); ?>;">
                    <span class="text-2xl text-white font-bold">
                        <?php echo strtoupper(substr($category['name_fr'], 0, 2)); ?>
                    </span>
                </div>
            <?php endif; ?>

            <!-- Info principale -->
            <div class="flex-1">
                <h3 class="text-lg font-medium text-gray-900">
                    <?php echo htmlspecialchars($category['name_fr']); ?>
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Code : <span class="font-mono font-semibold"><?php echo htmlspecialchars($category['code']); ?></span>
                </p>
            </div>

            <!-- Badge statut -->
            <?php if ($category['is_active']): ?>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                    ✓ Active
                </span>
            <?php else: ?>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                    ✗ Inactive
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Informations détaillées -->
    <div class="px-4 py-5 sm:p-6">
        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">

            <!-- Code -->
            <div class="sm:col-span-1">
                <dt class="text-sm font-medium text-gray-500">Code</dt>
                <dd class="mt-1 text-sm text-gray-900 font-mono font-semibold">
                    <?php echo htmlspecialchars($category['code']); ?>
                </dd>
            </div>

            <!-- Ordre d'affichage -->
            <div class="sm:col-span-1">
                <dt class="text-sm font-medium text-gray-500">Ordre d'affichage</dt>
                <dd class="mt-1 text-sm text-gray-900">
                    <?php echo htmlspecialchars($category['display_order']); ?>
                </dd>
            </div>

            <!-- Nom français -->
            <div class="sm:col-span-1">
                <dt class="text-sm font-medium text-gray-500">🇫🇷 Nom français</dt>
                <dd class="mt-1 text-sm text-gray-900">
                    <?php echo htmlspecialchars($category['name_fr']); ?>
                </dd>
            </div>

            <!-- Nom néerlandais -->
            <div class="sm:col-span-1">
                <dt class="text-sm font-medium text-gray-500">🇳🇱 Nom néerlandais</dt>
                <dd class="mt-1 text-sm text-gray-900">
                    <?php echo htmlspecialchars($category['name_nl']); ?>
                </dd>
            </div>

            <!-- Couleur -->
            <div class="sm:col-span-1">
                <dt class="text-sm font-medium text-gray-500">Couleur</dt>
                <dd class="mt-1 flex items-center gap-2">
                    <div class="w-8 h-8 rounded border border-gray-300"
                         style="background-color: <?php echo htmlspecialchars($category['color']); ?>;">
                    </div>
                    <span class="text-sm text-gray-900 font-mono">
                        <?php echo htmlspecialchars($category['color']); ?>
                    </span>
                </dd>
            </div>

            <!-- Icône -->
            <div class="sm:col-span-1">
                <dt class="text-sm font-medium text-gray-500">Icône</dt>
                <dd class="mt-1">
                    <?php if (!empty($category['icon_path'])): ?>
                        <div class="flex items-center gap-2">
                            <img src="<?php echo htmlspecialchars($category['icon_path']); ?>"
                                 alt="Icône"
                                 class="w-8 h-8">
                            <span class="text-xs text-gray-500 truncate max-w-xs">
                                <?php echo htmlspecialchars(basename($category['icon_path'])); ?>
                            </span>
                        </div>
                    <?php else: ?>
                        <span class="text-sm text-gray-500 italic">Aucune icône</span>
                    <?php endif; ?>
                </dd>
            </div>

            <!-- Date de création -->
            <div class="sm:col-span-1">
                <dt class="text-sm font-medium text-gray-500">Date de création</dt>
                <dd class="mt-1 text-sm text-gray-900">
                    <?php echo date('d/m/Y à H:i', strtotime($category['created_at'])); ?>
                </dd>
            </div>

            <!-- Statut -->
            <div class="sm:col-span-1">
                <dt class="text-sm font-medium text-gray-500">Statut</dt>
                <dd class="mt-1">
                    <?php if ($category['is_active']): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Active
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            Inactive
                        </span>
                    <?php endif; ?>
                </dd>
            </div>

        </dl>
    </div>

    <!-- Actions -->
    <div class="px-4 py-4 sm:px-6 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
        <div class="flex gap-2">
            <?php if ($canEdit): ?>
            <a href="/stm/admin/products/categories/<?php echo $category['id']; ?>/edit"
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                ✏️ Modifier
            </a>
            <?php endif; ?>
            <a href="/stm/admin/products/categories"
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                ← Retour à la liste
            </a>
        </div>

        <?php if ($canDelete): ?>
        <!-- Formulaire de suppression -->
        <form method="POST"
              action="/stm/admin/products/categories/<?php echo $category['id']; ?>/delete"
              onsubmit="return confirm('⚠️ Êtes-vous sûr de vouloir supprimer cette catégorie ?\n\nCette action est irréversible.');"
              class="inline">
            <input type="hidden" name="_token" value="<?php echo htmlspecialchars(Session::get('csrf_token')); ?>">
            <button type="submit"
                    class="inline-flex items-center px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50">
                🗑️ Supprimer
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
?>