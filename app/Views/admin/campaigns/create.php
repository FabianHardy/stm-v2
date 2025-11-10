<?php
/**
 * Vue : Création d'une campagne
 * 
 * Formulaire de création d'une nouvelle campagne promotionnelle
 * 
 * @modified 08/11/2025 15:40 - Correction action formulaire
 */

// Démarrer la capture du contenu pour le layout
ob_start();
?>

<!-- En-tête de la page -->
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Créer une campagne</h1>
            <p class="mt-2 text-sm text-gray-600">
                Créez une nouvelle campagne promotionnelle pour la Belgique ou le Luxembourg
            </p>
        </div>
        <a href="/stm/admin/campaigns" 
           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Retour à la liste
        </a>
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

<!-- Formulaire de création -->
<div class="bg-white shadow rounded-lg">
    <form method="POST" action="/stm/admin/campaigns" class="divide-y divide-gray-200">
        
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
                           value="<?php echo htmlspecialchars($old['name'] ?? ''); ?>"
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
                        <option value="BE" <?php echo ($old['country'] ?? '') === 'BE' ? 'selected' : ''; ?>>🇧🇪 Belgique</option>
                        <option value="LU" <?php echo ($old['country'] ?? '') === 'LU' ? 'selected' : ''; ?>>🇱🇺 Luxembourg</option>
                    </select>
                </div>

                <!-- Statut actif -->
                <div class="flex items-center h-full pt-6">
                    <div class="flex items-center h-5">
                        <input type="checkbox" 
                               name="is_active" 
                               id="is_active" 
                               value="1"
                               <?php echo ($old['is_active'] ?? true) ? 'checked' : ''; ?>
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
                           value="<?php echo htmlspecialchars($old['start_date'] ?? ''); ?>"
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
                           value="<?php echo htmlspecialchars($old['end_date'] ?? ''); ?>"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
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
                           value="<?php echo htmlspecialchars($old['title_fr'] ?? ''); ?>"
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
                              class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"><?php echo htmlspecialchars($old['description_fr'] ?? ''); ?></textarea>
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
                           value="<?php echo htmlspecialchars($old['title_nl'] ?? ''); ?>"
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
                              class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"><?php echo htmlspecialchars($old['description_nl'] ?? ''); ?></textarea>
                    <p class="mt-1 text-sm text-gray-500">Description visible par les clients néerlandophones (optionnel)</p>
                </div>

            </div>
        </div>

        <!-- Boutons d'action -->
        <div class="px-4 py-4 sm:px-6 flex items-center justify-end gap-3 bg-gray-50">
            <a href="/stm/admin/campaigns" 
               class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Annuler
            </a>
            <button type="submit" 
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Créer la campagne
            </button>
        </div>

    </form>
</div>

<!-- Script pour validation des dates -->
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
</script>

<?php
// Capturer le contenu
$content = ob_get_clean();

// Définir le titre de la page (variable attendue par le layout)
$title = 'Créer une campagne';

// Script pour la validation des dates
$pageScripts = "
<script>
    // Validation additionnelle côté client si nécessaire
    console.log('Formulaire de création de campagne chargé');
</script>
";

// Inclure le layout du dashboard (celui avec le beau design)
require __DIR__ . '/../../layouts/admin.php';
?>