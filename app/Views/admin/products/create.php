<?php
/**
 * Vue : Création d'un produit
 * 
 * Formulaire de création d'un nouveau produit lié à une campagne
 * 
 * @created 11/11/2025 21:45
 * @modified 11/11/2025 23:45 - Adaptation besoins Trendy Foods
 */

use Core\Session;

// Démarrer la capture du contenu pour le layout
ob_start();

// Récupérer les anciennes valeurs et erreurs
$old = $old ?? [];
$errors = $errors ?? [];
?>

<!-- En-tête de la page -->
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Nouveau produit</h1>
            <p class="mt-2 text-sm text-gray-600">Créer un nouveau produit dans le catalogue</p>
        </div>
        <a href="/stm/admin/products" 
           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            ← Retour à la liste
        </a>
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
                        Produits
                    </a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <span class="mx-2 text-gray-400">/</span>
                    <span class="text-gray-500">Nouveau produit</span>
                </div>
            </li>
        </ol>
    </nav>
</div>

<!-- Formulaire -->
<form method="POST" action="/stm/admin/products" enctype="multipart/form-data">
    <input type="hidden" name="_token" value="<?php echo Session::get('csrf_token'); ?>">

    <!-- Section : Informations de base -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                📋 Informations de base
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                Code d'identification et campagne associée
            </p>
        </div>
        
        <div class="px-4 py-5 sm:p-6">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                
                <!-- Code produit -->
                <div>
                    <label for="product_code" class="block text-sm font-medium text-gray-700">
                        Code article <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="product_code" 
                           id="product_code"
                           value="<?php echo htmlspecialchars($old['product_code'] ?? ''); ?>"
                           placeholder="Ex: COCA001"
                           required
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm <?php echo isset($errors['product_code']) ? 'border-red-300' : ''; ?>">
                    <p class="mt-1 text-xs text-gray-500">Code unique de l'article (sert aussi de numéro de colis)</p>
                    <?php if (isset($errors['product_code'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo $errors['product_code']; ?></p>
                    <?php endif; ?>
                </div>

                <!-- Campagne -->
                <div>
                    <label for="campaign_id" class="block text-sm font-medium text-gray-700">
                        Campagne <span class="text-red-500">*</span>
                    </label>
                    <select name="campaign_id" 
                            id="campaign_id"
                            required
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm <?php echo isset($errors['campaign_id']) ? 'border-red-300' : ''; ?>">
                        <option value="">-- Sélectionner une campagne --</option>
                        <?php foreach ($campaigns as $campaign): ?>
                            <option value="<?php echo $campaign['id']; ?>" 
                                    <?php echo (isset($old['campaign_id']) && $old['campaign_id'] == $campaign['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($campaign['title']); ?>
                                (<?php echo strtoupper($campaign['country']); ?> - 
                                <?php echo date('d/m/Y', strtotime($campaign['start_date'])); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Campagne à laquelle appartient ce produit</p>
                    <?php if (isset($errors['campaign_id'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo $errors['campaign_id']; ?></p>
                    <?php endif; ?>
                </div>

                <!-- Catégorie -->
                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700">
                        Catégorie
                    </label>
                    <select name="category_id" 
                            id="category_id"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="">-- Sélectionner une catégorie --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" 
                                    <?php echo (isset($old['category_id']) && $old['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name_fr']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Optionnel - pour organiser le catalogue</p>
                </div>

            </div>
        </div>
    </div>

    <!-- Section : Contenu français -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                🇫🇷 Contenu en français
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                Informations visibles par les clients francophones
            </p>
        </div>
        
        <div class="px-4 py-5 sm:p-6">
            <div class="space-y-6">
                
                <!-- Nom FR -->
                <div>
                    <label for="name_fr" class="block text-sm font-medium text-gray-700">
                        Nom du produit <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="name_fr" 
                           id="name_fr"
                           value="<?php echo htmlspecialchars($old['name_fr'] ?? ''); ?>"
                           placeholder="Ex: Coca-Cola Original 24x33cl"
                           required
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm <?php echo isset($errors['name_fr']) ? 'border-red-300' : ''; ?>">
                    <?php if (isset($errors['name_fr'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo $errors['name_fr']; ?></p>
                    <?php endif; ?>
                </div>

                <!-- Description FR -->
                <div>
                    <label for="description_fr" class="block text-sm font-medium text-gray-700">
                        Description
                    </label>
                    <textarea name="description_fr" 
                              id="description_fr" 
                              rows="4"
                              placeholder="Description détaillée du produit..."
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"><?php echo htmlspecialchars($old['description_fr'] ?? ''); ?></textarea>
                    <p class="mt-1 text-xs text-gray-500">Optionnel - visible sur la fiche produit</p>
                </div>

                <!-- Image FR -->
                <div>
                    <label for="image_fr" class="block text-sm font-medium text-gray-700">
                        Image du produit <span class="text-red-500">*</span>
                    </label>
                    <div class="mt-1 flex items-center">
                        <input type="file" 
                               name="image_fr" 
                               id="image_fr"
                               accept="image/jpeg,image/jpg,image/png,image/webp"
                               required
                               class="block w-full text-sm text-gray-500
                                      file:mr-4 file:py-2 file:px-4
                                      file:rounded-md file:border-0
                                      file:text-sm file:font-medium
                                      file:bg-indigo-50 file:text-indigo-700
                                      hover:file:bg-indigo-100">
                    </div>
                    <p class="mt-1 text-xs text-gray-500">JPG, PNG, WEBP - Maximum 5MB - Sera utilisée aussi pour NL si non uploadée</p>
                    <?php if (isset($errors['image_fr'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo $errors['image_fr']; ?></p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <!-- Section : Contenu néerlandais -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                🇳🇱 Contenu en néerlandais
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                Informations visibles par les clients néerlandophones (optionnel)
            </p>
        </div>
        
        <div class="px-4 py-5 sm:p-6">
            <div class="space-y-6">
                
                <!-- Nom NL -->
                <div>
                    <label for="name_nl" class="block text-sm font-medium text-gray-700">
                        Productnaam
                    </label>
                    <input type="text" 
                           name="name_nl" 
                           id="name_nl"
                           value="<?php echo htmlspecialchars($old['name_nl'] ?? ''); ?>"
                           placeholder="Bv: Coca-Cola Original 24x33cl"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <p class="mt-1 text-xs text-gray-500">Optionnel - si vide, le nom français sera utilisé</p>
                </div>

                <!-- Description NL -->
                <div>
                    <label for="description_nl" class="block text-sm font-medium text-gray-700">
                        Beschrijving
                    </label>
                    <textarea name="description_nl" 
                              id="description_nl" 
                              rows="4"
                              placeholder="Gedetailleerde productbeschrijving..."
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"><?php echo htmlspecialchars($old['description_nl'] ?? ''); ?></textarea>
                </div>

                <!-- Image NL -->
                <div>
                    <label for="image_nl" class="block text-sm font-medium text-gray-700">
                        Productafbeelding (optionnel)
                    </label>
                    <div class="mt-1">
                        <input type="file" 
                               name="image_nl" 
                               id="image_nl"
                               accept="image/jpeg,image/jpg,image/png,image/webp"
                               class="block w-full text-sm text-gray-500
                                      file:mr-4 file:py-2 file:px-4
                                      file:rounded-md file:border-0
                                      file:text-sm file:font-medium
                                      file:bg-indigo-50 file:text-indigo-700
                                      hover:file:bg-indigo-100">
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Si non uploadée, l'image française sera utilisée automatiquement</p>
                </div>

            </div>
        </div>
    </div>

    <!-- Section : Paramètres -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                ⚙️ Paramètres
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                Options d'affichage et de statut du produit
            </p>
        </div>
        
        <div class="px-4 py-5 sm:p-6">
            <div class="space-y-6">
                
                <!-- Ordre d'affichage -->
                <div class="sm:w-1/3">
                    <label for="display_order" class="block text-sm font-medium text-gray-700">
                        Ordre d'affichage
                    </label>
                    <input type="number" 
                           name="display_order" 
                           id="display_order"
                           value="<?php echo htmlspecialchars($old['display_order'] ?? '0'); ?>"
                           min="0"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <p class="mt-1 text-xs text-gray-500">Plus le nombre est petit, plus le produit apparaît en premier</p>
                </div>

                <!-- Statut actif -->
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input type="checkbox" 
                               name="is_active" 
                               id="is_active"
                               value="1"
                               <?php echo (!isset($old['is_active']) || $old['is_active']) ? 'checked' : ''; ?>
                               class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="is_active" class="font-medium text-gray-700">Produit actif</label>
                        <p class="text-gray-500">Ce produit sera visible dans le catalogue</p>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Boutons d'action -->
    <div class="flex items-center justify-end gap-x-4 mb-6">
        <a href="/stm/admin/products" 
           class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Annuler
        </a>
        <button type="submit" 
                class="inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            ✅ Créer le produit
        </button>
    </div>

</form>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
?>