<?php
/**
 * Formulaire création compte interne
 * 
 * @created 2025/12/03 14:00
 */

ob_start();
?>

<!-- En-tête -->
<div class="mb-6">
    <nav class="flex items-center text-sm text-gray-500 mb-2">
        <a href="/stm/admin/config/internal-customers" class="hover:text-gray-700">Comptes internes</a>
        <svg class="w-4 h-4 mx-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
        </svg>
        <span class="text-gray-900">Nouveau compte</span>
    </nav>
    <h1 class="text-2xl font-bold text-gray-900">Ajouter un compte interne</h1>
</div>

<!-- Formulaire -->
<div class="bg-white rounded-lg shadow">
    <form method="POST" action="/stm/admin/config/internal-customers" class="p-6 space-y-6">
        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
        
        <!-- Numéro client -->
        <div>
            <label for="customer_number" class="block text-sm font-medium text-gray-700 mb-1">
                Numéro client <span class="text-red-500">*</span>
            </label>
            <input type="text" 
                   id="customer_number" 
                   name="customer_number" 
                   value="<?= htmlspecialchars($old['customer_number'] ?? '') ?>"
                   class="w-full max-w-xs rounded-lg border-gray-300 shadow-sm focus:ring-red-500 focus:border-red-500 font-mono <?= isset($errors['customer_number']) ? 'border-red-500' : '' ?>"
                   placeholder="Ex: 802412"
                   required>
            <?php if (isset($errors['customer_number'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= $errors['customer_number'] ?></p>
            <?php endif; ?>
        </div>
        
        <!-- Pays -->
        <div>
            <label for="country" class="block text-sm font-medium text-gray-700 mb-1">
                Pays <span class="text-red-500">*</span>
            </label>
            <select id="country" 
                    name="country" 
                    class="w-full max-w-xs rounded-lg border-gray-300 shadow-sm focus:ring-red-500 focus:border-red-500 <?= isset($errors['country']) ? 'border-red-500' : '' ?>"
                    required>
                <option value="">-- Sélectionner --</option>
                <option value="BE" <?= ($old['country'] ?? '') === 'BE' ? 'selected' : '' ?>>🇧🇪 Belgique</option>
                <option value="LU" <?= ($old['country'] ?? '') === 'LU' ? 'selected' : '' ?>>🇱🇺 Luxembourg</option>
            </select>
            <?php if (isset($errors['country'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= $errors['country'] ?></p>
            <?php endif; ?>
            <p class="mt-1 text-sm text-gray-500">Ce compte sera ajouté aux campagnes de ce pays uniquement.</p>
        </div>
        
        <!-- Description -->
        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                Description <span class="text-gray-400">(optionnel)</span>
            </label>
            <input type="text" 
                   id="description" 
                   name="description" 
                   value="<?= htmlspecialchars($old['description'] ?? '') ?>"
                   class="w-full max-w-md rounded-lg border-gray-300 shadow-sm focus:ring-red-500 focus:border-red-500"
                   placeholder="Ex: Fabian - Développeur">
            <p class="mt-1 text-sm text-gray-500">Pour identifier facilement à qui appartient ce compte.</p>
        </div>
        
        <!-- Actif -->
        <div class="flex items-center">
            <input type="checkbox" 
                   id="is_active" 
                   name="is_active" 
                   class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded"
                   <?= ($old['is_active'] ?? true) ? 'checked' : '' ?>>
            <label for="is_active" class="ml-2 block text-sm text-gray-700">
                Compte actif
            </label>
        </div>
        
        <!-- Boutons -->
        <div class="flex items-center gap-4 pt-4 border-t border-gray-200">
            <button type="submit" 
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors">
                Ajouter le compte
            </button>
            <a href="/stm/admin/config/internal-customers" 
               class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors">
                Annuler
            </a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
$title = 'Nouveau compte interne - Configuration';

require __DIR__ . '/../../layouts/admin.php';
?>
