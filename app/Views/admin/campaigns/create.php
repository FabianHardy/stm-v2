<?php
/**
 * Vue : Créer une campagne
 * 
 * Formulaire de création de campagne avec :
 * - Informations de base (nom, pays, dates, type, livraison)
 * - Attribution clients (automatic/manual/protected)
 * - Contenu multilingue (FR/NL)
 * 
 * @created  13/11/2025
 * @modified 14/11/2025 - Retrait section quotas (gérés au niveau promotions)
 */

ob_start();

// Récupérer les anciennes valeurs et erreurs (si soumission avec erreurs)
$errors = $errors ?? [];
$old = $old ?? [];
?>

<!-- En-tête -->
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Créer une campagne</h1>
            <p class="mt-2 text-sm text-gray-600">
                Définissez une nouvelle campagne promotionnelle
            </p>
        </div>
        <a href="/stm/admin/campaigns" 
           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Retour
        </a>
    </div>
</div>

<!-- Formulaire -->
<form method="POST" action="/stm/admin/campaigns" class="space-y-6">
    <!-- Token CSRF -->
    <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">

    <!-- Section 1 : Informations de base -->
    <div class="bg-white shadow-sm rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Informations générales
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Nom de la campagne -->
            <div class="md:col-span-2">
                <label for="name" class="block text-sm font-medium text-gray-700">
                    Nom de la campagne <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       name="name" 
                       id="name" 
                       required
                       value="<?= htmlspecialchars($old['name'] ?? '') ?>"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 <?= !empty($errors['name']) ? 'border-red-300' : '' ?>"
                       placeholder="Ex: Promotions de Noël 2025">
                <?php if (!empty($errors['name'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['name']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Pays -->
            <div>
                <label for="country" class="block text-sm font-medium text-gray-700">
                    Pays <span class="text-red-500">*</span>
                </label>
                <select name="country" 
                        id="country" 
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 <?= !empty($errors['country']) ? 'border-red-300' : '' ?>">
                    <option value="">Sélectionner...</option>
                    <option value="BE" <?= ($old['country'] ?? '') === 'BE' ? 'selected' : '' ?>>🇧🇪 Belgique</option>
                    <option value="LU" <?= ($old['country'] ?? '') === 'LU' ? 'selected' : '' ?>>🇱🇺 Luxembourg</option>
                </select>
                <?php if (!empty($errors['country'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['country']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Statut -->
            <div>
                <label for="is_active" class="flex items-center cursor-pointer">
                    <input type="checkbox" 
                           name="is_active" 
                           id="is_active"
                           value="1"
                           <?= !empty($old['is_active']) ? 'checked' : '' ?>
                           class="rounded border-gray-300 text-purple-600 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-200 focus:ring-opacity-50">
                    <span class="ml-2 text-sm font-medium text-gray-700">Campagne active</span>
                </label>
                <p class="mt-1 text-xs text-gray-500">
                    Si cochée, la campagne sera visible publiquement
                </p>
            </div>

            <!-- Date de début -->
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700">
                    Date de début <span class="text-red-500">*</span>
                </label>
                <input type="date" 
                       name="start_date" 
                       id="start_date" 
                       required
                       value="<?= htmlspecialchars($old['start_date'] ?? '') ?>"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 <?= !empty($errors['start_date']) ? 'border-red-300' : '' ?>">
                <?php if (!empty($errors['start_date'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['start_date']) ?></p>
                <?php endif; ?>
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
                       value="<?= htmlspecialchars($old['end_date'] ?? '') ?>"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 <?= !empty($errors['end_date']) ? 'border-red-300' : '' ?>">
                <?php if (!empty($errors['end_date'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['end_date']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Type de commande -->
            <div>
                <label for="order_type" class="block text-sm font-medium text-gray-700">
                    Type de commande <span class="text-red-500">*</span>
                </label>
                <select name="order_type" 
                        id="order_type" 
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                    <option value="W" <?= ($old['order_type'] ?? 'W') === 'W' ? 'selected' : '' ?>>Commande normale (W)</option>
                    <option value="V" <?= ($old['order_type'] ?? '') === 'V' ? 'selected' : '' ?>>Prospection à livrer (V)</option>
                </select>
                <p class="mt-1 text-xs text-gray-500">
                    W = Commande standard | V = Prospection (échantillons)
                </p>
            </div>

            <!-- Livraison différée -->
            <div>
                <label for="deferred_delivery" class="flex items-center cursor-pointer">
                    <input type="checkbox" 
                           name="deferred_delivery" 
                           id="deferred_delivery"
                           value="1"
                           <?= !empty($old['deferred_delivery']) ? 'checked' : '' ?>
                           class="rounded border-gray-300 text-purple-600 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-200 focus:ring-opacity-50"
                           onchange="document.getElementById('delivery_date_field').style.display = this.checked ? 'block' : 'none'">
                    <span class="ml-2 text-sm font-medium text-gray-700">Livraison différée</span>
                </label>
                <p class="mt-1 text-xs text-gray-500">
                    Cochez si la livraison est prévue à une date ultérieure
                </p>
            </div>

            <!-- Date de livraison (conditionnelle) -->
            <div id="delivery_date_field" style="display: <?= !empty($old['deferred_delivery']) ? 'block' : 'none' ?>">
                <label for="delivery_date" class="block text-sm font-medium text-gray-700">
                    Date de livraison souhaitée
                </label>
                <input type="date" 
                       name="delivery_date" 
                       id="delivery_date"
                       value="<?= htmlspecialchars($old['delivery_date'] ?? '') ?>"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 <?= !empty($errors['delivery_date']) ? 'border-red-300' : '' ?>">
                <?php if (!empty($errors['delivery_date'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['delivery_date']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Section 2 : Attribution des clients -->
    <div class="bg-white shadow-sm rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Attribution des clients
        </h3>
        <p class="text-sm text-gray-500 mb-6">
            Choisissez qui peut accéder à cette campagne
        </p>

        <div class="space-y-4">
            <!-- Mode : Automatique -->
            <label class="flex items-start p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors <?= ($old['customer_assignment_mode'] ?? 'automatic') === 'automatic' ? 'border-purple-500 bg-purple-50' : 'border-gray-200' ?>">
                <input type="radio" 
                       name="customer_assignment_mode" 
                       value="automatic" 
                       <?= ($old['customer_assignment_mode'] ?? 'automatic') === 'automatic' ? 'checked' : '' ?>
                       class="mt-1 text-purple-600 focus:ring-purple-500"
                       onchange="document.getElementById('customer_list_field').style.display='none'; document.getElementById('order_password_field').style.display='none';">
                <div class="ml-3 flex-1">
                    <span class="block text-sm font-medium text-gray-900">
                        🌍 Automatique (lecture directe)
                    </span>
                    <span class="block text-sm text-gray-500 mt-1">
                        Tous les clients du pays peuvent accéder à la campagne
                    </span>
                </div>
            </label>

            <!-- Mode : Manuel (liste restreinte) -->
            <label class="flex items-start p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors <?= ($old['customer_assignment_mode'] ?? '') === 'manual' ? 'border-purple-500 bg-purple-50' : 'border-gray-200' ?>">
                <input type="radio" 
                       name="customer_assignment_mode" 
                       value="manual"
                       <?= ($old['customer_assignment_mode'] ?? '') === 'manual' ? 'checked' : '' ?>
                       class="mt-1 text-purple-600 focus:ring-purple-500"
                       onchange="document.getElementById('customer_list_field').style.display='block'; document.getElementById('order_password_field').style.display='none';">
                <div class="ml-3 flex-1">
                    <span class="block text-sm font-medium text-gray-900">
                        📋 Liste restreinte (manuel)
                    </span>
                    <span class="block text-sm text-gray-500 mt-1">
                        Seuls les clients de la liste peuvent accéder
                    </span>
                </div>
            </label>

            <!-- Champ : Liste clients (conditionnel) -->
            <div id="customer_list_field" style="display: <?= ($old['customer_assignment_mode'] ?? '') === 'manual' ? 'block' : 'none' ?>" class="ml-8 mt-2">
                <label for="customer_list" class="block text-sm font-medium text-gray-700 mb-2">
                    Numéros clients (un par ligne)
                </label>
                <textarea name="customer_list" 
                          id="customer_list" 
                          rows="5"
                          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                          placeholder="123456&#10;654321&#10;789012"><?= htmlspecialchars($old['customer_list'] ?? '') ?></textarea>
                <p class="mt-1 text-xs text-gray-500">
                    💡 Entrez un numéro client par ligne
                </p>
            </div>

            <!-- Mode : Protégé par mot de passe -->
            <label class="flex items-start p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors <?= ($old['customer_assignment_mode'] ?? '') === 'protected' ? 'border-purple-500 bg-purple-50' : 'border-gray-200' ?>">
                <input type="radio" 
                       name="customer_assignment_mode" 
                       value="protected"
                       <?= ($old['customer_assignment_mode'] ?? '') === 'protected' ? 'checked' : '' ?>
                       class="mt-1 text-purple-600 focus:ring-purple-500"
                       onchange="document.getElementById('customer_list_field').style.display='none'; document.getElementById('order_password_field').style.display='block';">
                <div class="ml-3 flex-1">
                    <span class="block text-sm font-medium text-gray-900">
                        🔒 Protégée par mot de passe
                    </span>
                    <span class="block text-sm text-gray-500 mt-1">
                        Tous les clients du pays peuvent accéder avec un mot de passe
                    </span>
                </div>
            </label>

            <!-- Champ : Mot de passe (conditionnel) -->
            <div id="order_password_field" style="display: <?= ($old['customer_assignment_mode'] ?? '') === 'protected' ? 'block' : 'none' ?>" class="ml-8 mt-2">
                <label for="order_password" class="block text-sm font-medium text-gray-700 mb-2">
                    Mot de passe de la campagne <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       name="order_password" 
                       id="order_password"
                       value="<?= htmlspecialchars($old['order_password'] ?? '') ?>"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 <?= !empty($errors['order_password']) ? 'border-red-300' : '' ?>"
                       placeholder="Ex: PROMO2025">
                <?php if (!empty($errors['order_password'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['order_password']) ?></p>
                <?php endif; ?>
                <p class="mt-1 text-xs text-gray-500">
                    💡 Les clients devront entrer ce mot de passe pour accéder à la campagne
                </p>
            </div>
        </div>
    </div>

    <!-- Section 3 : Contenu multilingue -->
    <div class="bg-white shadow-sm rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Contenu multilingue
        </h3>
        <p class="text-sm text-gray-500 mb-6">
            Titres et descriptions pour chaque langue
        </p>

        <!-- Onglets FR/NL -->
        <div x-data="{ tab: 'fr' }">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <button type="button"
                            @click="tab = 'fr'"
                            :class="tab === 'fr' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        🇫🇷 Français
                    </button>
                    <button type="button"
                            @click="tab = 'nl'"
                            :class="tab === 'nl' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        🇳🇱 Néerlandais
                    </button>
                </nav>
            </div>

            <!-- Contenu FR -->
            <div x-show="tab === 'fr'" class="mt-6 space-y-4">
                <!-- Titre FR -->
                <div>
                    <label for="title_fr" class="block text-sm font-medium text-gray-700">
                        Titre français
                    </label>
                    <input type="text" 
                           name="title_fr" 
                           id="title_fr"
                           value="<?= htmlspecialchars($old['title_fr'] ?? '') ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                           placeholder="Ex: Promotions exceptionnelles de Noël">
                </div>

                <!-- Description FR -->
                <div>
                    <label for="description_fr" class="block text-sm font-medium text-gray-700">
                        Description française
                    </label>
                    <textarea name="description_fr" 
                              id="description_fr" 
                              rows="4"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                              placeholder="Décrivez votre campagne..."><?= htmlspecialchars($old['description_fr'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Contenu NL -->
            <div x-show="tab === 'nl'" class="mt-6 space-y-4">
                <!-- Titre NL -->
                <div>
                    <label for="title_nl" class="block text-sm font-medium text-gray-700">
                        Titre néerlandais
                    </label>
                    <input type="text" 
                           name="title_nl" 
                           id="title_nl"
                           value="<?= htmlspecialchars($old['title_nl'] ?? '') ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                           placeholder="Ex: Uitzonderlijke kerstpromoties">
                </div>

                <!-- Description NL -->
                <div>
                    <label for="description_nl" class="block text-sm font-medium text-gray-700">
                        Description néerlandaise
                    </label>
                    <textarea name="description_nl" 
                              id="description_nl" 
                              rows="4"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                              placeholder="Beschrijf uw campagne..."><?= htmlspecialchars($old['description_nl'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Boutons d'action -->
    <div class="flex items-center justify-end gap-x-4">
        <a href="/stm/admin/campaigns" 
           class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50">
            Annuler
        </a>
        <button type="submit" 
                class="px-4 py-2 text-sm font-medium text-white bg-purple-600 border border-transparent rounded-md shadow-sm hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
            Créer la campagne
        </button>
    </div>
</form>

<?php
$content = ob_get_clean();
$title = 'Créer une campagne';
require __DIR__ . '/../../layouts/admin.php';
?>