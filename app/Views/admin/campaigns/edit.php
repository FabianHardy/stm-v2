<?php
/**
 * Vue : Modification d'une campagne
 * 
 * Formulaire de modification d'une campagne existante
 * 
 * @modified 13/11/2025 - Ajout type, livraison, quotas, attribution clients
 */

// Démarrer la capture du contenu pour le layout
ob_start();
?>

<!-- En-tête de la page -->
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Modifier la campagne</h1>
            <p class="mt-2 text-sm text-gray-600">
                <?php echo htmlspecialchars($campaign['name']); ?> - 
                <?php echo $campaign['country'] === 'BE' ? '🇧🇪 Belgique' : '🇱🇺 Luxembourg'; ?>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/stm/admin/campaigns/<?php echo $campaign['id']; ?>" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                Voir les détails
            </a>
            <a href="/stm/admin/campaigns" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Retour à la liste
            </a>
        </div>
    </div>
</div>

<!-- Messages d'erreur (si présents) -->
<?php if (!empty($errors)): ?>
<div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">
                Erreurs de validation
            </h3>
            <div class="mt-2 text-sm text-red-700">
                <ul class="list-disc list-inside space-y-1">
                    <?php foreach ($errors as $field => $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Formulaire de modification -->
<div class="bg-white shadow rounded-lg">
    <form method="POST" action="/stm/admin/campaigns/<?php echo $campaign['id']; ?>" class="divide-y divide-gray-200">
        
        <!-- Token CSRF -->
        <input type="hidden" name="_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
        
        <!-- Section : Informations générales -->
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Informations générales</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <!-- Nom interne -->
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700">
                        Nom interne <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="name" 
                           id="name" 
                           required
                           value="<?php echo htmlspecialchars($old['name'] ?? $campaign['name']); ?>"
                           placeholder="Ex: PROMO_Q1_2025_BE"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <p class="mt-1 text-sm text-gray-500">Identifiant unique de la campagne (non visible par les clients)</p>
                </div>

                <!-- Pays -->
                <div>
                    <label for="country" class="block text-sm font-medium text-gray-700">
                        Pays <span class="text-red-500">*</span>
                    </label>
                    <select name="country" 
                            id="country" 
                            required
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Sélectionnez un pays</option>
                        <option value="BE" <?php echo ($old['country'] ?? $campaign['country']) === 'BE' ? 'selected' : ''; ?>>🇧🇪 Belgique</option>
                        <option value="LU" <?php echo ($old['country'] ?? $campaign['country']) === 'LU' ? 'selected' : ''; ?>>🇱🇺 Luxembourg</option>
                    </select>
                </div>

                <!-- Statut actif -->
                <div class="flex items-center h-full pt-6">
                    <div class="flex items-center h-5">
                        <input type="checkbox" 
                               name="is_active" 
                               id="is_active" 
                               value="1"
                               <?php echo ($old['is_active'] ?? $campaign['is_active']) ? 'checked' : ''; ?>
                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="is_active" class="font-medium text-gray-700">Campagne active</label>
                        <p class="text-gray-500">La campagne sera immédiatement visible et accessible</p>
                    </div>
                </div>

            </div>
        </div>

        <!-- Section : Dates -->
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Période de validité</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <!-- Date de début -->
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700">
                        Date de début <span class="text-red-500">*</span>
                    </label>
                    <input type="date" 
                           name="start_date" 
                           id="start_date" 
                           required
                           value="<?php echo htmlspecialchars($old['start_date'] ?? $campaign['start_date']); ?>"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <!-- Date de fin -->
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700">
                        Date de fin <span class="text-red-500">*</span>
                    </label>
                    <input type="date" 
                           name="end_date" 
                           id="end_date" 
                           required
                           value="<?php echo htmlspecialchars($old['end_date'] ?? $campaign['end_date']); ?>"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>

            </div>
        </div>

        <!-- NOUVEAU : Section Type et livraison -->
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">📦 Type et livraison</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <!-- Type de commande -->
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700">
                        Type de commande <span class="text-red-500">*</span>
                    </label>
                    <select name="type" 
                            id="type" 
                            required
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="W" <?php echo ($old['type'] ?? $campaign['type'] ?? 'W') === 'W' ? 'selected' : ''; ?>>Commande normale</option>
                        <option value="V" <?php echo ($old['type'] ?? $campaign['type'] ?? '') === 'V' ? 'selected' : ''; ?>>Prospection à livrer</option>
                    </select>
                    <p class="mt-1 text-sm text-gray-500">Type W = Normal, V = Prospection</p>
                </div>

                <!-- Date de livraison différée -->
                <div>
                    <label for="delivery_date" class="block text-sm font-medium text-gray-700">
                        Date de livraison différée
                    </label>
                    <input type="date" 
                           name="delivery_date" 
                           id="delivery_date"
                           value="<?php echo htmlspecialchars($old['delivery_date'] ?? $campaign['delivery_date'] ?? ''); ?>"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <p class="mt-1 text-sm text-gray-500">Si vide = livraison immédiate</p>
                </div>

            </div>
        </div>

        <!-- NOUVEAU : Section Quotas de commande -->
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">🔢 Quotas de commande (quantités)</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <!-- Quota global -->
                <div>
                    <label for="global_quota" class="block text-sm font-medium text-gray-700">
                        Quota global (tous clients)
                    </label>
                    <input type="number" 
                           name="global_quota" 
                           id="global_quota" 
                           min="1"
                           value="<?php echo htmlspecialchars($old['global_quota'] ?? $campaign['global_quota'] ?? ''); ?>"
                           placeholder="Ex: 1000"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <p class="mt-1 text-sm text-gray-500">Quantité max totale (unités). Vide = illimité</p>
                </div>

                <!-- Quota par client -->
                <div>
                    <label for="quota_per_customer" class="block text-sm font-medium text-gray-700">
                        Quota par client
                    </label>
                    <input type="number" 
                           name="quota_per_customer" 
                           id="quota_per_customer" 
                           min="1"
                           value="<?php echo htmlspecialchars($old['quota_per_customer'] ?? $campaign['quota_per_customer'] ?? ''); ?>"
                           placeholder="Ex: 50"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <p class="mt-1 text-sm text-gray-500">Quantité max par client (unités). Vide = illimité</p>
                </div>

            </div>
        </div>

        <!-- NOUVEAU : Section Attribution des clients -->
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">👥 Attribution des clients</h3>
            
            <div class="space-y-6">
                
                <!-- Mode d'attribution -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Mode d'attribution <span class="text-red-500">*</span>
                    </label>
                    
                    <div class="space-y-3">
                        <!-- Manuel -->
                        <div class="flex items-center">
                            <input type="radio" 
                                   name="customer_access_type" 
                                   id="access_manual" 
                                   value="manual" 
                                   <?php echo ($old['customer_access_type'] ?? $campaign['customer_access_type'] ?? 'manual') === 'manual' ? 'checked' : ''; ?>
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                            <label for="access_manual" class="ml-3">
                                <span class="block text-sm font-medium text-gray-700">📝 Liste manuelle</span>
                                <span class="block text-sm text-gray-500">Liste fixe de numéros clients</span>
                            </label>
                        </div>

                        <!-- Dynamique -->
                        <div class="flex items-center">
                            <input type="radio" 
                                   name="customer_access_type" 
                                   id="access_dynamic" 
                                   value="dynamic"
                                   <?php echo ($old['customer_access_type'] ?? $campaign['customer_access_type'] ?? '') === 'dynamic' ? 'checked' : ''; ?>
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                            <label for="access_dynamic" class="ml-3">
                                <span class="block text-sm font-medium text-gray-700">🔄 Dynamique (tous les clients)</span>
                                <span class="block text-sm text-gray-500">Lecture temps réel depuis base externe</span>
                            </label>
                        </div>

                        <!-- Protégé -->
                        <div class="flex items-center">
                            <input type="radio" 
                                   name="customer_access_type" 
                                   id="access_protected" 
                                   value="protected"
                                   <?php echo ($old['customer_access_type'] ?? $campaign['customer_access_type'] ?? '') === 'protected' ? 'checked' : ''; ?>
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                            <label for="access_protected" class="ml-3">
                                <span class="block text-sm font-medium text-gray-700">🔒 Protégé par mot de passe</span>
                                <span class="block text-sm text-gray-500">Accès libre avec mot de passe unique</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Liste clients (affiché si Manuel) -->
                <div id="customer_list_section" style="display: none;">
                    <label for="customer_list" class="block text-sm font-medium text-gray-700">
                        Liste des numéros clients
                    </label>
                    <textarea name="customer_list" 
                              id="customer_list" 
                              rows="6"
                              placeholder="123456&#10;654321&#10;789012"
                              class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 font-mono text-sm"><?php echo htmlspecialchars($old['customer_list'] ?? $campaign['customer_list'] ?? ''); ?></textarea>
                    <p class="mt-1 text-sm text-gray-500">Un numéro par ligne. Formats acceptés: 123456, 123456-12, E12345-CB, *12345</p>
                </div>

                <!-- Mot de passe (affiché si Protégé) -->
                <div id="order_password_section" style="display: none;">
                    <label for="order_password" class="block text-sm font-medium text-gray-700">
                        Mot de passe de la campagne
                    </label>
                    <input type="text" 
                           name="order_password" 
                           id="order_password"
                           value="<?php echo htmlspecialchars($old['order_password'] ?? $campaign['order_password'] ?? ''); ?>"
                           placeholder="Ex: PROMO2025"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <p class="mt-1 text-sm text-gray-500">Mot de passe demandé aux clients pour accéder</p>
                </div>

            </div>
        </div>

        <!-- Section : Contenu en français -->
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                🇫🇷 Contenu en français
            </h3>
            
            <div class="space-y-6">
                
                <!-- Titre FR -->
                <div>
                    <label for="title_fr" class="block text-sm font-medium text-gray-700">
                        Titre de la campagne <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="title_fr" 
                           id="title_fr" 
                           required
                           value="<?php echo htmlspecialchars($old['title_fr'] ?? $campaign['title_fr'] ?? ''); ?>"
                           placeholder="Ex: Promotions du printemps 2025"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <p class="mt-1 text-sm text-gray-500">Titre visible par les clients francophones</p>
                </div>

                <!-- Description FR -->
                <div>
                    <label for="description_fr" class="block text-sm font-medium text-gray-700">
                        Description
                    </label>
                    <textarea name="description_fr" 
                              id="description_fr" 
                              rows="4"
                              placeholder="Décrivez la campagne promotionnelle..."
                              class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"><?php echo htmlspecialchars($old['description_fr'] ?? $campaign['description_fr'] ?? ''); ?></textarea>
                    <p class="mt-1 text-sm text-gray-500">Description visible par les clients francophones (optionnel)</p>
                </div>

            </div>
        </div>

        <!-- Section : Contenu en néerlandais -->
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                🇳🇱 Contenu en néerlandais
            </h3>
            
            <div class="space-y-6">
                
                <!-- Titre NL -->
                <div>
                    <label for="title_nl" class="block text-sm font-medium text-gray-700">
                        Titel van de campagne
                    </label>
                    <input type="text" 
                           name="title_nl" 
                           id="title_nl"
                           value="<?php echo htmlspecialchars($old['title_nl'] ?? $campaign['title_nl'] ?? ''); ?>"
                           placeholder="Bv: Lentepromoties 2025"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <p class="mt-1 text-sm text-gray-500">Titre visible par les clients néerlandophones (optionnel)</p>
                </div>

                <!-- Description NL -->
                <div>
                    <label for="description_nl" class="block text-sm font-medium text-gray-700">
                        Beschrijving
                    </label>
                    <textarea name="description_nl" 
                              id="description_nl" 
                              rows="4"
                              placeholder="Beschrijf de promotiecampagne..."
                              class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"><?php echo htmlspecialchars($old['description_nl'] ?? $campaign['description_nl'] ?? ''); ?></textarea>
                    <p class="mt-1 text-sm text-gray-500">Description visible par les clients néerlandophones (optionnel)</p>
                </div>

            </div>
        </div>

        <!-- Boutons d'action -->
        <div class="px-4 py-4 sm:px-6 flex items-center justify-end gap-3 bg-gray-50">
            <a href="/stm/admin/campaigns/<?php echo $campaign['id']; ?>" 
               class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Annuler
            </a>
            <button type="submit" 
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Enregistrer les modifications
            </button>
        </div>

    </form>
</div>

<!-- Script pour validation des dates et toggle attribution -->
<script>
    // Validation : la date de fin doit être après la date de début
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');

    function validateDates() {
        const startDate = new Date(startDateInput.value);
        const endDate = new Date(endDateInput.value);

        if (startDate && endDate && endDate < startDate) {
            endDateInput.setCustomValidity('La date de fin doit être après la date de début');
        } else {
            endDateInput.setCustomValidity('');
        }
    }

    startDateInput.addEventListener('change', validateDates);
    endDateInput.addEventListener('change', validateDates);

    // Toggle affichage champs selon mode attribution
    const radios = document.querySelectorAll('input[name="customer_access_type"]');
    const customerListSection = document.getElementById('customer_list_section');
    const passwordSection = document.getElementById('order_password_section');

    function updateSections() {
        const selectedValue = document.querySelector('input[name="customer_access_type"]:checked').value;
        
        customerListSection.style.display = 'none';
        passwordSection.style.display = 'none';
        
        if (selectedValue === 'manual') {
            customerListSection.style.display = 'block';
        } else if (selectedValue === 'protected') {
            passwordSection.style.display = 'block';
        }
    }

    radios.forEach(radio => {
        radio.addEventListener('change', updateSections);
    });

    // Initialisation
    updateSections();
</script>

<?php
// Capturer le contenu
$content = ob_get_clean();

// Définir le titre de la page (variable attendue par le layout)
$title = 'Modifier la campagne';

// Script pour la validation des dates
$pageScripts = "
<script>
    // Validation additionnelle côté client si nécessaire
    console.log('Formulaire de modification de campagne chargé');
</script>
";

// Inclure le layout du dashboard (celui avec le beau design)
require __DIR__ . '/../../layouts/admin.php';
?>