<?php
/**
 * Vue : Modification d'une Promotion
 * 
 * Formulaire d'édition d'une Promotion existant avec upload d'images
 * 
 * @created 11/11/2025 22:45
 * @modified 12/11/2025 16:50 - Ajout section Quotas de commande
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
            <h1 class="text-3xl font-bold text-gray-900">Modifier la Promotion</h1>
            <p class="mt-2 text-sm text-gray-600">
                <?php echo htmlspecialchars($product['name_fr']); ?> 
                <span class="text-gray-400">(<?php echo htmlspecialchars($product['product_code']); ?>)</span>
            </p>
        </div>
        <a href="/stm/admin/products/<?php echo $product['id']; ?>" 
           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            ← Retour aux détails
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
                        Promotions
                    </a>
                </div>
            </li>
            <li>
                <div class="flex items-center">
                    <span class="mx-2 text-gray-400">/</span>
                    <a href="/stm/admin/products/<?php echo $product['id']; ?>" class="text-gray-700 hover:text-gray-900">
                        <?php echo htmlspecialchars($product['product_code']); ?>
                    </a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <span class="mx-2 text-gray-400">/</span>
                    <span class="text-gray-500">Modifier</span>
                </div>
            </li>
        </ol>
    </nav>
</div>

<!-- Formulaire -->
<form method="POST" action="/stm/admin/products/<?php echo $product['id']; ?>" enctype="multipart/form-data">
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
                
                <!-- Code Promotion -->
                <div>
                    <label for="product_code" class="block text-sm font-medium text-gray-700">
                        Code article <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="product_code" 
                           id="product_code"
                           value="<?php echo htmlspecialchars($old['product_code'] ?? $product['product_code']); ?>"
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
                            <?php 
                            // Gérer différents noms de champs pour le titre
                            $campaignTitle = $campaign['title'] ?? $campaign['name'] ?? $campaign['campaign_name'] ?? 'Campagne #' . $campaign['id'];
                            ?>
                            <option value="<?php echo $campaign['id']; ?>" 
                                    <?php echo ((isset($old['campaign_id']) && $old['campaign_id'] == $campaign['id']) || 
                                               (!isset($old['campaign_id']) && $product['campaign_id'] == $campaign['id'])) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($campaignTitle); ?>
                                (<?php echo strtoupper($campaign['country']); ?> - 
                                <?php echo date('d/m/Y', strtotime($campaign['start_date'])); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Campagne à laquelle appartient cette Promotion</p>
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
                                    <?php echo ((isset($old['category_id']) && $old['category_id'] == $cat['id']) || 
                                               (!isset($old['category_id']) && $product['category_id'] == $cat['id'])) ? 'selected' : ''; ?>>
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
                        Nom de la Promotion <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="name_fr" 
                           id="name_fr"
                           value="<?php echo htmlspecialchars($old['name_fr'] ?? $product['name_fr']); ?>"
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
                        Description (optionnel)
                    </label>
                    <textarea name="description_fr" 
                              id="description_fr" 
                              rows="4"
                              placeholder="Description détaillée de la Promotion..."
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"><?php echo htmlspecialchars($old['description_fr'] ?? $product['description_fr'] ?? ''); ?></textarea>
                    <p class="mt-1 text-xs text-gray-500">Optionnel - visible sur la fiche Promotion</p>
                </div>

                <!-- Image FR -->
                <div>
                    <label for="image_fr" class="block text-sm font-medium text-gray-700">
                        Image du Promotion
                    </label>
                    
                    <!-- Image actuelle -->
                    <?php if (!empty($product['image_fr'])): ?>
                        <div class="mt-2 mb-3">
                            <img src="<?php echo htmlspecialchars($product['image_fr']); ?>" 
                                 alt="Image actuelle FR" 
                                 class="h-32 w-32 object-cover rounded-lg border-2 border-gray-200 shadow-sm">
                            <p class="mt-1 text-xs text-gray-500">Image actuelle - uploader pour remplacer</p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-1 flex items-center">
                        <input type="file" 
                               name="image_fr" 
                               id="image_fr"
                               accept="image/jpeg,image/jpg,image/png,image/webp"
                               class="block w-full text-sm text-gray-500
                                      file:mr-4 file:py-2 file:px-4
                                      file:rounded-md file:border-0
                                      file:text-sm file:font-medium
                                      file:bg-indigo-50 file:text-indigo-700
                                      hover:file:bg-indigo-100">
                    </div>
                    <p class="mt-1 text-xs text-gray-500">JPG, PNG, WEBP - Maximum 5MB - Laissez vide pour garder l'image actuelle</p>
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
                           value="<?php echo htmlspecialchars($old['name_nl'] ?? $product['name_nl'] ?? ''); ?>"
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
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"><?php echo htmlspecialchars($old['description_nl'] ?? $product['description_nl'] ?? ''); ?></textarea>
                </div>

                <!-- Image NL -->
                <div>
                    <label for="image_nl" class="block text-sm font-medium text-gray-700">
                        Productafbeelding (optionnel)
                    </label>
                    
                    <!-- Image actuelle -->
                    <?php if (!empty($product['image_nl'])): ?>
                        <div class="mt-2 mb-3">
                            <img src="<?php echo htmlspecialchars($product['image_nl']); ?>" 
                                 alt="Image actuelle NL" 
                                 class="h-32 w-32 object-cover rounded-lg border-2 border-gray-200 shadow-sm">
                            <p class="mt-1 text-xs text-gray-500">Huidige afbeelding - uploaden om te vervangen</p>
                        </div>
                    <?php elseif (!empty($product['image_fr'])): ?>
                        <div class="mt-2 mb-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-xs text-blue-700">
                                ℹ️ Aucune image NL spécifique - l'image FR est utilisée automatiquement
                            </p>
                        </div>
                    <?php endif; ?>
                    
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
                Options d'affichage et de statut de la Promotion
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
                           value="<?php echo htmlspecialchars($old['display_order'] ?? $product['display_order'] ?? '0'); ?>"
                           min="0"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <p class="mt-1 text-xs text-gray-500">Plus le nombre est petit, plus la Promotion apparaît en premier</p>
                </div>

                <!-- Statut actif -->
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input type="checkbox" 
                               name="is_active" 
                               id="is_active"
                               value="1"
                               <?php echo (isset($old['is_active']) ? ($old['is_active'] ? 'checked' : '') : ($product['is_active'] ? 'checked' : '')); ?>
                               class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="is_active" class="font-medium text-gray-700">Promotion active</label>
                        <p class="text-gray-500">Cette Promotion sera visible dans le catalogue</p>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Section : Quotas de commande (Optionnel) -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                📊 Quotas de commande (Optionnel)
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                Limiter la quantité maximum commandable (global et/ou par client)
            </p>
        </div>
        
        <div class="px-4 py-5 sm:p-6">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                
                <!-- Quota global -->
                <div>
                    <label for="max_total" class="block text-sm font-medium text-gray-700">
                        Quota global maximum
                    </label>
                    <input type="number" 
                           name="max_total" 
                           id="max_total"
                           value="<?php echo htmlspecialchars($old['max_total'] ?? $product['max_total'] ?? ''); ?>"
                           min="1"
                           placeholder="Illimité"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <p class="mt-1 text-xs text-gray-500">
                        Maximum d'unités vendables au total (tous clients confondus)
                    </p>
                </div>

                <!-- Quota par client -->
                <div>
                    <label for="max_per_customer" class="block text-sm font-medium text-gray-700">
                        Quota par client
                    </label>
                    <input type="number" 
                           name="max_per_customer" 
                           id="max_per_customer"
                           value="<?php echo htmlspecialchars($old['max_per_customer'] ?? $product['max_per_customer'] ?? ''); ?>"
                           min="1"
                           placeholder="Illimité"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <p class="mt-1 text-xs text-gray-500">
                        Maximum qu'un client peut commander individuellement
                    </p>
                </div>

            </div>

            <!-- Exemples -->
            <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-xs text-blue-800 font-medium mb-2">💡 Exemples d'utilisation :</p>
                <ul class="text-xs text-blue-700 space-y-1">
                    <li>• <strong>Global: 500, Par client: 10</strong> → Stock limité + répartition équitable</li>
                    <li>• <strong>Global: illimité, Par client: 20</strong> → Limite individuelle sans limite globale</li>
                    <li>• <strong>Global: 200, Par client: illimité</strong> → Premier arrivé premier servi jusqu'à 200</li>
                    <li>• <strong>Global: illimité, Par client: illimité</strong> → Aucune limite</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Boutons d'action -->
    <div class="flex items-center justify-between mb-6">
        <!-- Bouton supprimer à gauche -->
        <button type="button"
                onclick="if(confirm('Êtes-vous sûr de vouloir supprimer cette Promotion ?')) { document.getElementById('delete-form').submit(); }"
                class="inline-flex items-center px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
            🗑️ Supprimer la Promotion
        </button>

        <!-- Boutons annuler/enregistrer à droite -->
        <div class="flex items-center gap-x-4">
            <a href="/stm/admin/products/<?php echo $product['id']; ?>" 
               class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Annuler
            </a>
            <button type="submit" 
                    class="inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                💾 Enregistrer les modifications
            </button>
        </div>
    </div>

</form>

<!-- Formulaire de suppression caché -->
<form id="delete-form" method="POST" action="/stm/admin/products/<?php echo $product['id']; ?>/delete" style="display: none;">
    <input type="hidden" name="_token" value="<?php echo Session::get('csrf_token'); ?>">
</form>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
?>