<?php
/**
 * Vue : Création d'un client
 * 
 * Formulaire de création manuelle d'un client avec option d'import DB externe
 * 
 * @package STM/Views/Admin/Customers
 * @version 2.0
 * @created 12/11/2025 19:30
 */

$pageTitle = 'Nouveau client';
ob_start();
?>

<div class="max-w-4xl mx-auto">
    <!-- En-tête -->
    <div class="mb-6">
        <div class="flex items-center gap-4 mb-4">
            <a href="/stm/admin/customers" 
               class="text-gray-400 hover:text-gray-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Créer un nouveau client</h2>
                <p class="mt-1 text-sm text-gray-500">Ajoutez un client manuellement ou importez depuis la base externe</p>
            </div>
        </div>

        <!-- Option Import -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <svg class="h-6 w-6 text-blue-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
                <div class="flex-1">
                    <h3 class="text-sm font-medium text-blue-900">Vous avez beaucoup de clients à ajouter ?</h3>
                    <p class="mt-1 text-sm text-blue-700">
                        Utilisez l'import depuis la base de données externe pour ajouter plusieurs clients en une seule fois.
                    </p>
                    <div class="mt-3">
                        <a href="/stm/admin/customers/import" 
                           class="inline-flex items-center gap-x-2 rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                            <svg class="-ml-0.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            Accéder à l'import
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulaire -->
    <form method="POST" action="/stm/admin/customers" class="space-y-6" x-data="customerForm()">
        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

        <!-- Informations principales -->
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Informations principales</h3>
            
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <!-- Numéro client -->
                <div>
                    <label for="customer_number" class="block text-sm font-medium text-gray-700">
                        Numéro client <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="customer_number" 
                           name="customer_number" 
                           value="<?= htmlspecialchars($old['customer_number'] ?? '') ?>"
                           required
                           placeholder="123456 ou 123456-12"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm <?= isset($errors['customer_number']) ? 'border-red-300' : '' ?>">
                    <?php if (isset($errors['customer_number'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= $errors['customer_number'] ?></p>
                    <?php endif; ?>
                    <p class="mt-1 text-xs text-gray-500">Formats acceptés : 123456, 123456-12, E12345-CB, *12345</p>
                </div>

                <!-- Nom -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">
                        Nom <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           value="<?= htmlspecialchars($old['name'] ?? '') ?>"
                           required
                           placeholder="Nom du client"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm <?= isset($errors['name']) ? 'border-red-300' : '' ?>">
                    <?php if (isset($errors['name'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= $errors['name'] ?></p>
                    <?php endif; ?>
                </div>

                <!-- Pays -->
                <div>
                    <label for="country" class="block text-sm font-medium text-gray-700">
                        Pays <span class="text-red-500">*</span>
                    </label>
                    <select id="country" 
                            name="country"
                            required
                            x-model="country"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm <?= isset($errors['country']) ? 'border-red-300' : '' ?>">
                        <option value="">Sélectionner un pays</option>
                        <option value="BE" <?= ($old['country'] ?? '') === 'BE' ? 'selected' : '' ?>>🇧🇪 Belgique</option>
                        <option value="LU" <?= ($old['country'] ?? '') === 'LU' ? 'selected' : '' ?>>🇱🇺 Luxembourg</option>
                    </select>
                    <?php if (isset($errors['country'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= $errors['country'] ?></p>
                    <?php endif; ?>
                </div>

                <!-- Représentant -->
                <div>
                    <label for="representative" class="block text-sm font-medium text-gray-700">
                        Représentant
                    </label>
                    <select id="representative" 
                            name="representative"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Aucun représentant</option>
                        
                        <template x-if="country === 'BE'">
                            <optgroup label="Belgique">
                                <?php foreach ($representatives['BE'] ?? [] as $rep): ?>
                                    <option value="<?= htmlspecialchars($rep) ?>" 
                                            <?= ($old['representative'] ?? '') === $rep ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($rep) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        </template>
                        
                        <template x-if="country === 'LU'">
                            <optgroup label="Luxembourg">
                                <?php foreach ($representatives['LU'] ?? [] as $rep): ?>
                                    <option value="<?= htmlspecialchars($rep) ?>" 
                                            <?= ($old['representative'] ?? '') === $rep ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($rep) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        </template>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Les représentants affichés dépendent du pays sélectionné</p>
                </div>
            </div>
        </div>

        <!-- Contact -->
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Coordonnées</h3>
            
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">
                        Email
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                           placeholder="contact@client.be"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm <?= isset($errors['email']) ? 'border-red-300' : '' ?>">
                    <?php if (isset($errors['email'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= $errors['email'] ?></p>
                    <?php endif; ?>
                    <p class="mt-1 text-xs text-gray-500">L'email n'est pas unique (plusieurs clients peuvent partager le même)</p>
                </div>

                <!-- Téléphone -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">
                        Téléphone
                    </label>
                    <input type="tel" 
                           id="phone" 
                           name="phone" 
                           value="<?= htmlspecialchars($old['phone'] ?? '') ?>"
                           placeholder="+32 2 123 45 67"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <!-- Adresse -->
                <div class="sm:col-span-2">
                    <label for="address" class="block text-sm font-medium text-gray-700">
                        Adresse
                    </label>
                    <input type="text" 
                           id="address" 
                           name="address" 
                           value="<?= htmlspecialchars($old['address'] ?? '') ?>"
                           placeholder="Rue et numéro"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <!-- Code postal -->
                <div>
                    <label for="postal_code" class="block text-sm font-medium text-gray-700">
                        Code postal
                    </label>
                    <input type="text" 
                           id="postal_code" 
                           name="postal_code" 
                           value="<?= htmlspecialchars($old['postal_code'] ?? '') ?>"
                           placeholder="1000"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <!-- Ville -->
                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700">
                        Ville
                    </label>
                    <input type="text" 
                           id="city" 
                           name="city" 
                           value="<?= htmlspecialchars($old['city'] ?? '') ?>"
                           placeholder="Bruxelles"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
            </div>
        </div>

        <!-- Catégorisation -->
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Catégorisation</h3>
            
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <!-- Type -->
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700">
                        Type de client
                    </label>
                    <input type="text" 
                           id="type" 
                           name="type" 
                           value="<?= htmlspecialchars($old['type'] ?? '') ?>"
                           placeholder="Horeca, Retail, etc."
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <!-- Segment -->
                <div>
                    <label for="segment" class="block text-sm font-medium text-gray-700">
                        Segment
                    </label>
                    <input type="text" 
                           id="segment" 
                           name="segment" 
                           value="<?= htmlspecialchars($old['segment'] ?? '') ?>"
                           placeholder="A, B, C, etc."
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
            </div>
        </div>

        <!-- Statut -->
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Statut</h3>
            
            <div class="flex items-center">
                <input type="checkbox" 
                       id="is_active" 
                       name="is_active" 
                       value="1"
                       <?= ($old['is_active'] ?? true) ? 'checked' : '' ?>
                       class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <label for="is_active" class="ml-2 block text-sm text-gray-900">
                    Client actif
                </label>
            </div>
            <p class="mt-2 text-sm text-gray-500">
                Les clients inactifs ne peuvent pas passer de commandes
            </p>
        </div>

        <!-- Boutons -->
        <div class="flex justify-end gap-3 pt-4">
            <a href="/stm/admin/customers" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Annuler
            </a>
            <button type="submit" 
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                Créer le client
            </button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
$title = 'Nouveau client';

// Script Alpine.js pour gestion dynamique du select représentant
$pageScripts = "
<script>
function customerForm() {
    return {
        country: '" . ($old['country'] ?? '') . "'
    }
}
</script>
";

require __DIR__ . '/../../layouts/admin.php';
?>
