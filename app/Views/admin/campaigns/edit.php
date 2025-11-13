<?php
/**
 * Vue : Modification d'une campagne
 * 
 * Formulaire de modification d'une campagne existante
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
                           value="<?php echo htmlspecialchars($old['title_fr'] ?? $campaign['title_fr']); ?>"
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
                              class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"><?php echo htmlspecialchars($old['description_fr'] ?? $campaign['description_fr']); ?></textarea>
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
                           value="<?php echo htmlspecialchars($old['title_nl'] ?? $campaign['title_nl']); ?>"
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
                              class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"><?php echo htmlspecialchars($old['description_nl'] ?? $campaign['description_nl']); ?></textarea>
                    <p class="mt-1 text-sm text-gray-500">Description visible par les clients néerlandophones (optionnel)</p>
                </div>

            </div>
        </div>

        <!-- Boutons d'action -->
        <div class="px-4 py-4 sm:px-6 flex items-center justify-between bg-gray-50">
            <form method="POST" action="/stm/admin/campaigns/<?php echo $campaign['id']; ?>/delete" style="display: inline;">
                <input type="hidden" name="_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                <button type="submit" 
                        onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette campagne ?')"
                        class="inline-flex items-center px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Supprimer
                </button>
            </form>
            
            <div class="flex items-center gap-3">
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
$title = 'Modifier la campagne';

// Inclure le layout du dashboard (celui avec le beau design)
require __DIR__ . '/../../layouts/admin.php';
?>