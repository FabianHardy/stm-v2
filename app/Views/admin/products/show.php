<?php
/**
 * Vue : Détails d'un produit
 * @created 11/11/2025 21:45
 */
use Core\Session;
ob_start();
?>

<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Détails du produit</h1>
            <p class="mt-2 text-sm text-gray-600"><?php echo htmlspecialchars($product['name_fr']); ?></p>
        </div>
        <div class="flex gap-2">
            <a href="/stm/admin/products/<?php echo $product['id']; ?>/edit" 
               class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                ✏️ Modifier
            </a>
            <a href="/stm/admin/products" 
               class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                ← Retour
            </a>
        </div>
    </div>
</div>

<?php if ($success = Session::getFlash('success')): ?>
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
        <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<?php if ($error = Session::getFlash('error')): ?>
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<!-- Aperçu produit -->
<div class="bg-white shadow rounded-lg p-6 mb-6">
    <div class="flex items-start gap-6">
        <!-- Images -->
        <div class="flex gap-4">
            <?php if (!empty($product['image_fr'])): ?>
                <div>
                    <img src="<?php echo htmlspecialchars($product['image_fr']); ?>" alt="Image FR" class="h-32 w-32 object-cover rounded">
                    <p class="mt-1 text-xs text-gray-500 text-center">🇫🇷 FR</p>
                </div>
            <?php endif; ?>
            <?php if (!empty($product['image_nl'])): ?>
                <div>
                    <img src="<?php echo htmlspecialchars($product['image_nl']); ?>" alt="Image NL" class="h-32 w-32 object-cover rounded">
                    <p class="mt-1 text-xs text-gray-500 text-center">🇳🇱 NL</p>
                </div>
            <?php endif; ?>
            <?php if (empty($product['image_fr']) && empty($product['image_nl'])): ?>
                <div class="h-32 w-32 bg-gray-200 rounded flex items-center justify-center">
                    <span class="text-4xl">📦</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Info principale -->
        <div class="flex-1">
            <h2 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($product['name_fr']); ?></h2>
            <?php if (!empty($product['name_nl']) && $product['name_nl'] !== $product['name_fr']): ?>
                <p class="mt-1 text-lg text-gray-600">🇳🇱 <?php echo htmlspecialchars($product['name_nl']); ?></p>
            <?php endif; ?>

            <div class="mt-4 flex items-center gap-4">
                <?php if ($product['is_active']): ?>
                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">✅ Actif</span>
                <?php else: ?>
                    <span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm font-medium">❌ Inactif</span>
                <?php endif; ?>

                <?php if (!empty($product['category_name'])): ?>
                    <span class="px-3 py-1 rounded-full text-sm font-medium"
                          style="background-color: <?php echo htmlspecialchars($product['category_color']); ?>20; color: <?php echo htmlspecialchars($product['category_color']); ?>;">
                        <?php echo htmlspecialchars($product['category_name']); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Détails -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    
    <!-- Codes & Identifiants -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium mb-4">🏷️ Codes & Identifiants</h3>
        <dl class="space-y-3">
            <div>
                <dt class="text-sm font-medium text-gray-500">Code produit</dt>
                <dd class="mt-1 text-sm text-gray-900 font-mono"><?php echo htmlspecialchars($product['product_code']); ?></dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Numéro de colis</dt>
                <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($product['package_number']); ?></dd>
            </div>
            <?php if (!empty($product['ean'])): ?>
            <div>
                <dt class="text-sm font-medium text-gray-500">Code EAN</dt>
                <dd class="mt-1 text-sm text-gray-900 font-mono"><?php echo htmlspecialchars($product['ean']); ?></dd>
            </div>
            <?php endif; ?>
        </dl>
    </div>

    <!-- Catégorie & Options -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium mb-4">⚙️ Catégorie & Options</h3>
        <dl class="space-y-3">
            <div>
                <dt class="text-sm font-medium text-gray-500">Catégorie</dt>
                <dd class="mt-1">
                    <?php if (!empty($product['category_name'])): ?>
                        <span class="text-sm text-gray-900"><?php echo htmlspecialchars($product['category_name']); ?></span>
                    <?php else: ?>
                        <span class="text-sm text-gray-400 italic">Sans catégorie</span>
                    <?php endif; ?>
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Ordre d'affichage</dt>
                <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($product['display_order']); ?></dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Créé le</dt>
                <dd class="mt-1 text-sm text-gray-900"><?php echo date('d/m/Y à H:i', strtotime($product['created_at'])); ?></dd>
            </div>
        </dl>
    </div>
</div>

<!-- Descriptions -->
<?php if (!empty($product['description_fr']) || !empty($product['description_nl'])): ?>
<div class="bg-white shadow rounded-lg p-6 mb-6">
    <h3 class="text-lg font-medium mb-4">📝 Descriptions</h3>
    
    <?php if (!empty($product['description_fr'])): ?>
    <div class="mb-4">
        <h4 class="text-sm font-medium text-gray-700 mb-2">🇫🇷 Français</h4>
        <p class="text-sm text-gray-600"><?php echo nl2br(htmlspecialchars($product['description_fr'])); ?></p>
    </div>
    <?php endif; ?>

    <?php if (!empty($product['description_nl'])): ?>
    <div>
        <h4 class="text-sm font-medium text-gray-700 mb-2">🇳🇱 Nederlands</h4>
        <p class="text-sm text-gray-600"><?php echo nl2br(htmlspecialchars($product['description_nl'])); ?></p>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Actions -->
<div class="flex justify-between items-center">
    <form method="POST" action="/stm/admin/products/<?php echo $product['id']; ?>/delete" 
          onsubmit="return confirm('⚠️ Êtes-vous sûr de vouloir supprimer ce produit ?');">
        <input type="hidden" name="_token" value="<?php echo htmlspecialchars(Session::get('csrf_token')); ?>">
        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
            🗑️ Supprimer le produit
        </button>
    </form>

    <div class="flex gap-2">
        <a href="/stm/admin/products/<?php echo $product['id']; ?>/edit" 
           class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
            ✏️ Modifier
        </a>
        <a href="/stm/admin/products" 
           class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
            ← Retour à la liste
        </a>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
?>
