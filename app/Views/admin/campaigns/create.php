<?php
/**
 * Vue : Création de campagne
 * 
 * @package STM/Views/Admin/Campaigns
 * @version 2.2.0
 * @modified 13/11/2025 - Ajout section Attribution Clients (3 champs)
 */

ob_start();
?>

<!-- En-tête -->
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Créer une nouvelle campagne</h2>
    <p class="mt-1 text-sm text-gray-500">Définissez les informations de base et l'attribution des clients</p>
</div>

<!-- Messages d'erreur -->
<?php if (!empty($errors)): ?>
    <div class="mb-6 rounded-md bg-red-50 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">Erreurs de validation</h3>
                <ul class="mt-2 list-disc list-inside text-sm text-red-700">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Formulaire -->
<form method="POST" action="/stm/admin/campaigns/store" class="space-y-8">
    <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

    <!-- Section 1 : Informations de base -->
    <div class="bg-white shadow-sm rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Informations de base</h3>
        
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <!-- Nom -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">
                    Nom de la campagne <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" id="name" required
                       value="<?= htmlspecialchars($old['name'] ?? '') ?>"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                       placeholder="Ex: Promo Rentrée 2025">
                <p class="mt-1 text-xs text-gray-500">Nom interne unique (sera dans l'URL)</p>
            </div>

            <!-- Pays -->
            <div>
                <label for="country" class="block text-sm font-medium text-gray-700">
                    Pays <span class="text-red-500">*</span>
                </label>
                <select name="country" id="country" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm">
                    <option value="">-- Sélectionner --</option>
                    <option value="BE" <?= ($old['country'] ?? '') === 'BE' ? 'selected' : '' ?>>🇧🇪 Belgique</option>
                    <option value="LU" <?= ($old['country'] ?? '') === 'LU' ? 'selected' : '' ?>>🇱🇺 Luxembourg</option>
                </select>
            </div>

            <!-- Date début -->
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700">
                    Date de début <span class="text-red-500">*</span>
                </label>
                <input type="date" name="start_date" id="start_date" required
                       value="<?= htmlspecialchars($old['start_date'] ?? '') ?>"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm">
            </div>

            <!-- Date fin -->
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700">
                    Date de fin <span class="text-red-500">*</span>
                </label>
                <input type="date" name="end_date" id="end_date" required
                       value="<?= htmlspecialchars($old['end_date'] ?? '') ?>"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm">
            </div>

            <!-- Statut -->
            <div>
                <label for="is_active" class="block text-sm font-medium text-gray-700">
                    Statut
                </label>
                <select name="is_active" id="is_active"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm">
                    <option value="1" <?= ($old['is_active'] ?? '1') === '1' ? 'selected' : '' ?>>✅ Active</option>
                    <option value="0" <?= ($old['is_active'] ?? '1') === '0' ? 'selected' : '' ?>>⏸️ Inactive</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Section 2 : Contenu multilingue -->
    <div class="bg-white shadow-sm rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Contenu multilingue</h3>
        
        <div class="space-y-6">
            <!-- Version française -->
            <div class="border-l-4 border-blue-500 pl-4">
                <h4 class="text-sm font-medium text-gray-900 mb-3">🇫🇷 Version française</h4>
                
                <div class="space-y-4">
                    <div>
                        <label for="title_fr" class="block text-sm font-medium text-gray-700">
                            Titre
                        </label>
                        <input type="text" name="title_fr" id="title_fr"
                               value="<?= htmlspecialchars($old['title_fr'] ?? '') ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                               placeholder="Titre public de la campagne">
                    </div>

                    <div>
                        <label for="description_fr" class="block text-sm font-medium text-gray-700">
                            Description
                        </label>
                        <textarea name="description_fr" id="description_fr" rows="4"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                                  placeholder="Description publique..."><?= htmlspecialchars($old['description_fr'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Version néerlandaise -->
            <div class="border-l-4 border-orange-500 pl-4">
                <h4 class="text-sm font-medium text-gray-900 mb-3">🇳🇱 Version néerlandaise</h4>
                
                <div class="space-y-4">
                    <div>
                        <label for="title_nl" class="block text-sm font-medium text-gray-700">
                            Titel
                        </label>
                        <input type="text" name="title_nl" id="title_nl"
                               value="<?= htmlspecialchars($old['title_nl'] ?? '') ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                               placeholder="Publieke titel van de campagne">
                    </div>

                    <div>
                        <label for="description_nl" class="block text-sm font-medium text-gray-700">
                            Beschrijving
                        </label>
                        <textarea name="description_nl" id="description_nl" rows="4"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                                  placeholder="Publieke beschrijving..."><?= htmlspecialchars($old['description_nl'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 3 : Attribution des clients -->
    <div class="bg-white shadow-sm rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">👥 Attribution des clients</h3>
        <p class="text-sm text-gray-500 mb-4">Définissez comment les clients peuvent accéder à cette campagne</p>
        
        <div class="space-y-6">
            <!-- Mode d'attribution -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">
                    Mode d'attribution <span class="text-red-500">*</span>
                </label>
                
                <div class="space-y-3">
                    <!-- Manuel -->
                    <label class="relative flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition">
                        <input type="radio" name="customer_access_type" value="manual" 
                               <?= ($old['customer_access_type'] ?? 'manual') === 'manual' ? 'checked' : '' ?>
                               class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 mt-1">
                        <div class="ml-3 flex-1">
                            <div class="text-sm font-medium text-gray-900">📝 Manuel</div>
                            <div class="text-xs text-gray-500 mt-1">
                                Liste fixe de numéros clients (un par ligne)
                            </div>
                        </div>
                    </label>

                    <!-- Dynamique -->
                    <label class="relative flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition">
                        <input type="radio" name="customer_access_type" value="dynamic"
                               <?= ($old['customer_access_type'] ?? '') === 'dynamic' ? 'checked' : '' ?>
                               class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 mt-1">
                        <div class="ml-3 flex-1">
                            <div class="text-sm font-medium text-gray-900">🔄 Dynamique</div>
                            <div class="text-xs text-gray-500 mt-1">
                                Lecture en temps réel depuis la base externe (tous les clients du pays)
                            </div>
                        </div>
                    </label>

                    <!-- Protégé -->
                    <label class="relative flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition">
                        <input type="radio" name="customer_access_type" value="protected"
                               <?= ($old['customer_access_type'] ?? '') === 'protected' ? 'checked' : '' ?>
                               class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 mt-1">
                        <div class="ml-3 flex-1">
                            <div class="text-sm font-medium text-gray-900">🔒 Protégé par mot de passe</div>
                            <div class="text-xs text-gray-500 mt-1">
                                Accès libre avec un mot de passe unique pour tous
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Liste clients (affichée si Manuel) -->
            <div id="customer_list_section" style="display: none;">
                <label for="customer_list" class="block text-sm font-medium text-gray-700">
                    Liste des numéros clients
                </label>
                <textarea name="customer_list" id="customer_list" rows="6"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm font-mono text-xs"
                          placeholder="123456&#10;654321&#10;789012&#10;..."><?= htmlspecialchars($old['customer_list'] ?? '') ?></textarea>
                <p class="mt-1 text-xs text-gray-500">
                    Un numéro client par ligne. Accepte: 123456, 123456-12, E12345-CB, *12345
                </p>
            </div>

            <!-- Mot de passe (affiché si Protégé) -->
            <div id="order_password_section" style="display: none;">
                <label for="order_password" class="block text-sm font-medium text-gray-700">
                    Mot de passe de la campagne
                </label>
                <input type="text" name="order_password" id="order_password"
                       value="<?= htmlspecialchars($old['order_password'] ?? '') ?>"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                       placeholder="Ex: PROMO2025">
                <p class="mt-1 text-xs text-gray-500">
                    Ce mot de passe sera demandé aux clients pour accéder à la campagne
                </p>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
        <a href="/stm/admin/campaigns" 
           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            ← Annuler
        </a>
        <button type="submit"
                class="inline-flex items-center px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
            Créer la campagne
        </button>
    </div>
</form>

<!-- JavaScript pour toggle des champs selon le mode -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const radios = document.querySelectorAll('input[name="customer_access_type"]');
    const customerListSection = document.getElementById('customer_list_section');
    const passwordSection = document.getElementById('order_password_section');

    function updateSections() {
        const selectedValue = document.querySelector('input[name="customer_access_type"]:checked').value;
        
        // Masquer tous les champs
        customerListSection.style.display = 'none';
        passwordSection.style.display = 'none';
        
        // Afficher selon le mode
        if (selectedValue === 'manual') {
            customerListSection.style.display = 'block';
        } else if (selectedValue === 'protected') {
            passwordSection.style.display = 'block';
        }
    }

    radios.forEach(radio => {
        radio.addEventListener('change', updateSections);
    });

    // Initialisation au chargement
    updateSections();
});
</script>

<?php
$content = ob_get_clean();
$title = 'Créer une campagne';

require __DIR__ . '/../../layouts/admin.php';