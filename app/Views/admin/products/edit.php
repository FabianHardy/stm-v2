<?php
/**
 * Vue : Modification d'un produit
 * @created 11/11/2025 21:45
 */
use Core\Session;
ob_start();
$old = $old ?? [];
$errors = $errors ?? [];
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900">Modifier le produit</h1>
    <p class="mt-2 text-sm text-gray-600"><?php echo htmlspecialchars($product['name_fr']); ?></p>
</div>

<form method="POST" action="/stm/admin/products/<?php echo $product['id']; ?>" enctype="multipart/form-data" class="space-y-6">
    <input type="hidden" name="_token" value="<?php echo Session::get('csrf_token'); ?>">

    <!-- Informations de base -->
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-medium mb-4">📋 Informations de base</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700">Code produit *</label>
                <input type="text" name="product_code" value="<?php echo htmlspecialchars($old['product_code'] ?? $product['product_code']); ?>" required
                       class="mt-1 block w-full border-gray-300 rounded-md">
                <?php if (isset($errors['product_code'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?php echo $errors['product_code']; ?></p>
                <?php endif; ?>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">N° de colis *</label>
                <input type="text" name="package_number" value="<?php echo htmlspecialchars($old['package_number'] ?? $product['package_number']); ?>" required
                       class="mt-1 block w-full border-gray-300 rounded-md">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Code EAN</label>
                <input type="text" name="ean" pattern="\d{13}" value="<?php echo htmlspecialchars($old['ean'] ?? $product['ean']); ?>"
                       class="mt-1 block w-full border-gray-300 rounded-md">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Catégorie</label>
                <select name="category_id" class="mt-1 block w-full border-gray-300 rounded-md">
                    <option value="">-- Aucune catégorie --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($product['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name_fr']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- Descriptions FR -->
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-medium mb-4">🇫🇷 Contenu français</h2>
        
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Nom *</label>
                <input type="text" name="name_fr" value="<?php echo htmlspecialchars($old['name_fr'] ?? $product['name_fr']); ?>" required
                       class="mt-1 block w-full border-gray-300 rounded-md">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description_fr" rows="3" class="mt-1 block w-full border-gray-300 rounded-md"><?php echo htmlspecialchars($old['description_fr'] ?? $product['description_fr']); ?></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Image FR</label>
                <?php if (!empty($product['image_fr'])): ?>
                    <img src="<?php echo htmlspecialchars($product['image_fr']); ?>" alt="Image actuelle" class="mt-2 h-24 w-24 object-cover rounded">
                    <p class="mt-1 text-xs text-gray-500">Image actuelle - Upload pour remplacer</p>
                <?php endif; ?>
                <input type="file" name="image_fr" accept="image/jpeg,image/jpg,image/png,image/webp"
                       class="mt-1 block w-full">
            </div>
        </div>
    </div>

    <!-- Descriptions NL -->
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-medium mb-4">🇳🇱 Contenu néerlandais</h2>
        
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Naam</label>
                <input type="text" name="name_nl" value="<?php echo htmlspecialchars($old['name_nl'] ?? $product['name_nl']); ?>"
                       class="mt-1 block w-full border-gray-300 rounded-md">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Beschrijving</label>
                <textarea name="description_nl" rows="3" class="mt-1 block w-full border-gray-300 rounded-md"><?php echo htmlspecialchars($old['description_nl'] ?? $product['description_nl']); ?></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Afbeelding NL</label>
                <?php if (!empty($product['image_nl'])): ?>
                    <img src="<?php echo htmlspecialchars($product['image_nl']); ?>" alt="Image actuelle" class="mt-2 h-24 w-24 object-cover rounded">
                <?php endif; ?>
                <input type="file" name="image_nl" accept="image/jpeg,image/jpg,image/png,image/webp"
                       class="mt-1 block w-full">
            </div>
        </div>
    </div>

    <!-- Options -->
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-medium mb-4">⚙️ Options</h2>
        
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Ordre d'affichage</label>
                <input type="number" name="display_order" value="<?php echo htmlspecialchars($product['display_order']); ?>" min="0"
                       class="mt-1 block w-full border-gray-300 rounded-md">
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="is_active" id="is_active" value="1" <?php echo $product['is_active'] ? 'checked' : ''; ?>
                       class="h-4 w-4 text-indigo-600">
                <label for="is_active" class="ml-2 text-sm text-gray-700">Produit actif</label>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex justify-between items-center">
        <form method="POST" action="/stm/admin/products/<?php echo $product['id']; ?>/delete" 
              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ?');">
            <input type="hidden" name="_token" value="<?php echo Session::get('csrf_token'); ?>">
            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                🗑️ Supprimer
            </button>
        </form>

        <div class="flex gap-4">
            <a href="/stm/admin/products/<?php echo $product['id']; ?>" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                Annuler
            </a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                ✅ Enregistrer
            </button>
        </div>
    </div>
</form>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
?>
