<?php
/**
 * Vue : Formulaire de modification d'une campagne
 * 
 * Permet de modifier les informations d'une campagne existante :
 * - Informations de base (nom, pays, dates)
 * - Attribution clients (automatic/manual/protected)
 * - Paramètres de commande (type, livraison)
 * - Contenu multilingue (FR/NL)
 * 
 * @created  2025/11/14 02:00
 * @modified 2025/11/14 02:00 - Création initiale Sprint 5
 */

ob_start();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- En-tête -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">
                    Modifier la campagne
                </h1>
                <p class="mt-2 text-sm text-gray-600">
                    Modifiez les informations de la campagne <span class="font-semibold"><?= htmlspecialchars($campaign['name']) ?></span>
                </p>
            </div>
            <a href="/stm/admin/campaigns/<?= $campaign['id'] ?>" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Retour aux détails
            </a>
        </div>
    </div>

    <!-- Messages flash -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700"><?= htmlspecialchars($_SESSION['success']) ?></p>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700"><?= htmlspecialchars($_SESSION['error']) ?></p>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Formulaire -->
    <form method="POST" 
          action="/stm/admin/campaigns/<?= $campaign['id'] ?>" 
          class="space-y-8"
          x-data="{
              assignmentMode: '<?= htmlspecialchars($campaign['customer_assignment_mode']) ?>',
              deferredDelivery: <?= $campaign['deferred_delivery'] ? 'true' : 'false' ?>
          }">
        
        <!-- Method spoofing pour PUT -->
        <input type="hidden" name="_method" value="PUT">
        
        <!-- Token CSRF -->
        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">

        <!-- SECTION 1 : Informations de base -->
        <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg">
            <div class="px-6 py-5 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">
                    📋 Informations de base
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    Nom de la campagne, pays et période de validité
                </p>
            </div>
            
            <div class="px-6 py-6 space-y-6">
                <!-- Nom de la campagne -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Nom de la campagne <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           value="<?= htmlspecialchars($campaign['name']) ?>"
                           required
                           maxlength="255"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="Ex: Promotions Printemps 2025">
                    <p class="mt-1 text-sm text-gray-500">
                        Nom commercial de la campagne (visible par les clients)
                    </p>
                </div>

                <!-- Pays -->
                <div>
                    <label for="country" class="block text-sm font-medium text-gray-700 mb-2">
                        Pays <span class="text-red-500">*</span>
                    </label>
                    <select id="country" 
                            name="country" 
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="BE" <?= $campaign['country'] === 'BE' ? 'selected' : '' ?>>🇧🇪 Belgique</option>
                        <option value="LU" <?= $campaign['country'] === 'LU' ? 'selected' : '' ?>>🇱🇺 Luxembourg</option>
                    </select>
                    <p class="mt-1 text-sm text-gray-500">
                        Détermine les clients éligibles à la campagne
                    </p>
                </div>

                <!-- Dates -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Date de début -->
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Date de début <span class="text-red-500">*</span>
                        </label>
                        <input type="datetime-local" 
                               id="start_date" 
                               name="start_date" 
                               value="<?= date('Y-m-d\TH:i', strtotime($campaign['start_date'])) ?>"
                               required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <!-- Date de fin -->
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Date de fin <span class="text-red-500">*</span>
                        </label>
                        <input type="datetime-local" 
                               id="end_date" 
                               name="end_date" 
                               value="<?= date('Y-m-d\TH:i', strtotime($campaign['end_date'])) ?>"
                               required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 2 : Attribution clients -->
        <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg">
            <div class="px-6 py-5 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">
                    👥 Attribution des clients
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    Définissez qui peut accéder à cette campagne
                </p>
            </div>
            
            <div class="px-6 py-6 space-y-6">
                <!-- Mode d'attribution -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Mode d'attribution <span class="text-red-500">*</span>
                    </label>
                    
                    <div class="space-y-3">
                        <!-- Mode Automatique -->
                        <label class="relative flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition"
                               :class="assignmentMode === 'automatic' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300'">
                            <input type="radio" 
                                   name="customer_assignment_mode" 
                                   value="automatic" 
                                   x-model="assignmentMode"
                                   <?= $campaign['customer_assignment_mode'] === 'automatic' ? 'checked' : '' ?>
                                   class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500">
                            <div class="ml-3">
                                <span class="block text-sm font-medium text-gray-900">
                                    🌍 Tous les clients du pays (Automatique)
                                </span>
                                <span class="block text-sm text-gray-600 mt-1">
                                    Tous les clients BE ou LU peuvent accéder (lecture en temps réel)
                                </span>
                            </div>
                        </label>

                        <!-- Mode Manuel -->
                        <label class="relative flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition"
                               :class="assignmentMode === 'manual' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300'">
                            <input type="radio" 
                                   name="customer_assignment_mode" 
                                   value="manual" 
                                   x-model="assignmentMode"
                                   <?= $campaign['customer_assignment_mode'] === 'manual' ? 'checked' : '' ?>
                                   class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500">
                            <div class="ml-3">
                                <span class="block text-sm font-medium text-gray-900">
                                    📝 Liste manuelle de clients
                                </span>
                                <span class="block text-sm text-gray-600 mt-1">
                                    Définissez une liste restreinte de numéros clients
                                </span>
                            </div>
                        </label>

                        <!-- Mode Protégé -->
                        <label class="relative flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition"
                               :class="assignmentMode === 'protected' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300'">
                            <input type="radio" 
                                   name="customer_assignment_mode" 
                                   value="protected" 
                                   x-model="assignmentMode"
                                   <?= $campaign['customer_assignment_mode'] === 'protected' ? 'checked' : '' ?>
                                   class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500">
                            <div class="ml-3">
                                <span class="block text-sm font-medium text-gray-900">
                                    🔒 Accès protégé par mot de passe
                                </span>
                                <span class="block text-sm text-gray-600 mt-1">
                                    Tous les clients mais avec mot de passe requis
                                </span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Liste manuelle (si mode manual) -->
                <div x-show="assignmentMode === 'manual'" 
                     x-transition
                     class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <label for="customer_list" class="block text-sm font-medium text-gray-900 mb-2">
                        Liste des numéros clients
                    </label>
                    <textarea id="customer_list" 
                              name="customer_list" 
                              rows="6"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono text-sm"
                              placeholder="123456&#10;654321&#10;789012&#10;..."><?php 
                        if (!empty($campaign['customer_list'])) {
                            echo htmlspecialchars($campaign['customer_list']);
                        }
                    ?></textarea>
                    <p class="mt-2 text-sm text-gray-600">
                        📝 Entrez un numéro client par ligne. Formats acceptés : 123456, 123456-12, E12345-CB, *12345
                    </p>
                </div>

                <!-- Mot de passe (si mode protected) -->
                <div x-show="assignmentMode === 'protected'" 
                     x-transition
                     class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                    <label for="order_password" class="block text-sm font-medium text-gray-900 mb-2">
                        Mot de passe d'accès
                    </label>
                    <input type="text" 
                           id="order_password" 
                           name="order_password" 
                           value="<?= htmlspecialchars($campaign['order_password'] ?? '') ?>"
                           maxlength="255"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono"
                           placeholder="PROMO2025">
                    <p class="mt-2 text-sm text-gray-600">
                        🔑 Ce mot de passe sera demandé aux clients pour accéder à la campagne
                    </p>
                </div>
            </div>
        </div>

        <!-- SECTION 3 : Paramètres de commande -->
        <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg">
            <div class="px-6 py-5 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">
                    🚚 Paramètres de commande
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    Type de commande et modalités de livraison
                </p>
            </div>
            
            <div class="px-6 py-6 space-y-6">
                <!-- Type de commande -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Type de commande <span class="text-red-500">*</span>
                    </label>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <!-- Type W (Normal) -->
                        <label class="relative flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition"
                               :class="'<?= $campaign['order_type'] ?>' === 'W' ? 'border-green-500 bg-green-50' : 'border-gray-300'">
                            <input type="radio" 
                                   name="order_type" 
                                   value="W" 
                                   <?= $campaign['order_type'] === 'W' ? 'checked' : '' ?>
                                   class="mt-1 h-4 w-4 text-green-600 focus:ring-green-500">
                            <div class="ml-3">
                                <span class="block text-sm font-medium text-gray-900">
                                    ✅ Commande normale (W)
                                </span>
                                <span class="block text-sm text-gray-600 mt-1">
                                    Commande standard avec stock immédiat
                                </span>
                            </div>
                        </label>

                        <!-- Type V (Prospection) -->
                        <label class="relative flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition"
                               :class="'<?= $campaign['order_type'] ?>' === 'V' ? 'border-purple-500 bg-purple-50' : 'border-gray-300'">
                            <input type="radio" 
                                   name="order_type" 
                                   value="V" 
                                   <?= $campaign['order_type'] === 'V' ? 'checked' : '' ?>
                                   class="mt-1 h-4 w-4 text-purple-600 focus:ring-purple-500">
                            <div class="ml-3">
                                <span class="block text-sm font-medium text-gray-900">
                                    🎯 Prospection (V)
                                </span>
                                <span class="block text-sm text-gray-600 mt-1">
                                    Pré-commande ou test de marché
                                </span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Livraison différée -->
                <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                    <label class="flex items-start cursor-pointer">
                        <input type="checkbox" 
                               name="deferred_delivery" 
                               value="1"
                               x-model="deferredDelivery"
                               <?= $campaign['deferred_delivery'] ? 'checked' : '' ?>
                               class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 rounded">
                        <div class="ml-3">
                            <span class="block text-sm font-medium text-gray-900">
                                📅 Livraison différée
                            </span>
                            <span class="block text-sm text-gray-600 mt-1">
                                Définir une date de livraison future pour cette campagne
                            </span>
                        </div>
                    </label>

                    <!-- Date de livraison (si livraison différée) -->
                    <div x-show="deferredDelivery" 
                         x-transition
                         class="mt-4">
                        <label for="delivery_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Date de livraison souhaitée
                        </label>
                        <input type="date" 
                               id="delivery_date" 
                               name="delivery_date" 
                               value="<?= $campaign['delivery_date'] ? date('Y-m-d', strtotime($campaign['delivery_date'])) : '' ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <p class="mt-1 text-sm text-gray-600">
                            📦 Les commandes seront livrées à cette date
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 4 : Contenu multilingue -->
        <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg">
            <div class="px-6 py-5 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">
                    🌐 Contenu multilingue
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    Description de la campagne en français et néerlandais
                </p>
            </div>
            
            <div class="px-6 py-6 space-y-6">
                <!-- Description FR -->
                <div>
                    <label for="description_fr" class="block text-sm font-medium text-gray-700 mb-2">
                        🇫🇷 Description française
                    </label>
                    <textarea id="description_fr" 
                              name="description_fr" 
                              rows="4"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                              placeholder="Décrivez la campagne en français..."><?= htmlspecialchars($campaign['description_fr'] ?? '') ?></textarea>
                </div>

                <!-- Description NL -->
                <div>
                    <label for="description_nl" class="block text-sm font-medium text-gray-700 mb-2">
                        🇳🇱 Description néerlandaise
                    </label>
                    <textarea id="description_nl" 
                              name="description_nl" 
                              rows="4"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                              placeholder="Beschrijf de campagne in het Nederlands..."><?= htmlspecialchars($campaign['description_nl'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Boutons d'action -->
        <div class="flex items-center justify-between pt-6 border-t border-gray-200">
            <a href="/stm/admin/campaigns/<?= $campaign['id'] ?>" 
               class="inline-flex items-center px-6 py-3 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                Annuler
            </a>
            
            <button type="submit"
                    class="inline-flex items-center px-6 py-3 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Enregistrer les modifications
            </button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
$title = 'Modifier la campagne - STM';
require __DIR__ . '/../../layouts/admin.php';
?>