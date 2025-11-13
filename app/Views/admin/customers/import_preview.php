<?php
/**
 * Vue : Import clients depuis DB externe
 * 
 * Prévisualisation et sélection des clients à importer depuis la base externe
 * 
 * @package STM/Views/Admin/Customers
 * @version 2.0
 * @created 12/11/2025 19:30
 */

$pageTitle = 'Import clients';
$selectedCountry = $_GET['country'] ?? 'BE';
ob_start();
?>

<div class="max-w-7xl mx-auto">
    <!-- En-tête -->
    <div class="mb-6">
        <div class="flex items-center gap-4 mb-4">
            <a href="/stm/admin/customers/create" 
               class="text-gray-400 hover:text-gray-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Importer des clients</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Sélectionnez les clients à importer depuis la base de données externe
                </p>
            </div>
        </div>

        <!-- Info -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <svg class="h-6 w-6 text-blue-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
                <div class="flex-1">
                    <h3 class="text-sm font-medium text-blue-900">Base de données externe connectée</h3>
                    <p class="mt-1 text-sm text-blue-700">
                        Les clients sont importés depuis les tables BE_CLL et LU_CLL. Les doublons sont automatiquement détectés (contrainte : numéro client + pays).
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white shadow-sm rounded-lg p-4 mb-6" x-data="importForm()">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <!-- Pays -->
            <div>
                <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Pays</label>
                <select id="country" 
                        x-model="country"
                        @change="updateFilters()"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="BE">🇧🇪 Belgique</option>
                    <option value="LU">🇱🇺 Luxembourg</option>
                </select>
            </div>

            <!-- Recherche -->
            <div class="sm:col-span-2">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Recherche</label>
                <input type="text" 
                       id="search" 
                       x-model="search"
                       placeholder="Numéro client, nom..."
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>

            <!-- Boutons -->
            <div class="sm:col-span-3 flex gap-2">
                <button @click="updateFilters()" 
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                    Filtrer
                </button>
                <button @click="resetFilters()" 
                        class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Réinitialiser
                </button>
            </div>
        </div>
    </div>

    <!-- Formulaire d'import -->
    <form method="POST" action="/stm/admin/customers/import/execute" x-data="selectionManager()">
        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        <input type="hidden" name="country" value="<?= htmlspecialchars($selectedCountry) ?>">

        <!-- Barre d'actions -->
        <div class="bg-white shadow-sm rounded-t-lg p-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-700">
                        <span x-text="selectedCount"></span> client(s) sélectionné(s)
                    </span>
                    <div class="h-4 w-px bg-gray-300"></div>
                    <button type="button" 
                            @click="selectAll()"
                            class="text-sm text-indigo-600 hover:text-indigo-900">
                        Tout sélectionner
                    </button>
                    <button type="button" 
                            @click="unselectAll()"
                            class="text-sm text-gray-600 hover:text-gray-900">
                        Tout désélectionner
                    </button>
                </div>
                <button type="submit" 
                        :disabled="selectedCount === 0"
                        :class="selectedCount === 0 ? 'opacity-50 cursor-not-allowed' : ''"
                        class="inline-flex items-center gap-x-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                    <svg class="-ml-0.5 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Importer la sélection
                </button>
            </div>
        </div>

        <!-- Tableau -->
        <div class="bg-white shadow-sm rounded-b-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="w-12 px-6 py-3">
                                <!-- Checkbox header -->
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Numéro
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nom
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Représentant
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Email
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Statut
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($externalCustomers)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                    <p class="mt-2 font-medium">Aucun client trouvé</p>
                                    <p class="mt-1 text-gray-400">Essayez de modifier les filtres</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($externalCustomers as $customer): ?>
                                <tr class="hover:bg-gray-50 <?= $customer['already_imported'] ? 'bg-gray-50' : '' ?>">
                                    <td class="w-12 px-6 py-4">
                                        <?php if ($customer['already_imported']): ?>
                                            <span class="text-gray-400" title="Déjà importé">
                                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                </svg>
                                            </span>
                                        <?php else: ?>
                                            <input type="checkbox" 
                                                   name="customers[]" 
                                                   value="<?= htmlspecialchars($customer['customer_number']) ?>"
                                                   @change="updateCount()"
                                                   class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($customer['customer_number']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= htmlspecialchars($customer['company_name']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        -
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        -
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($customer['already_imported']): ?>
                                            <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">
                                                Déjà importé
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                                Disponible
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
$title = 'Import clients';

// Scripts Alpine.js
$pageScripts = "
<script>
// Gestion des filtres
function importForm() {
    return {
        country: '" . htmlspecialchars($selectedCountry) . "',
        search: '" . htmlspecialchars($_GET['search'] ?? '') . "',
        
        updateFilters() {
            const params = new URLSearchParams({
                country: this.country,
                search: this.search
            });
            window.location.href = '/stm/admin/customers/import?' + params.toString();
        },
        
        resetFilters() {
            this.country = 'BE';
            this.search = '';
            this.updateFilters();
        }
    }
}

// Gestion de la sélection
function selectionManager() {
    return {
        selectedCount: 0,
        
        init() {
            this.updateCount();
        },
        
        updateCount() {
            this.selectedCount = document.querySelectorAll('input[name=\"customers[]\"]:checked').length;
        },
        
        selectAll() {
            document.querySelectorAll('input[name=\"customers[]\"]:not(:disabled)').forEach(checkbox => {
                checkbox.checked = true;
            });
            this.updateCount();
        },
        
        unselectAll() {
            document.querySelectorAll('input[name=\"customers[]\"]').forEach(checkbox => {
                checkbox.checked = false;
            });
            this.updateCount();
        }
    }
}
</script>
";

require __DIR__ . '/../../layouts/admin.php';
?>