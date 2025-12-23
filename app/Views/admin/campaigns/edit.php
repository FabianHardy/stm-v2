<?php
/**
 * Vue : Formulaire de modification d'une campagne
 *
 * Permet de modifier une campagne existante avec :
 * - Informations de base (nom, pays, dates, statut)
 * - Attribution clients (automatic/manual/protected)
 * - Paramètres de commande (type, livraison)
 * - Équipe (collaborateurs assignés)
 * - Contenu multilingue (FR/NL)
 *
 * @created  2025/11/14 02:00
 * @modified 2025/12/11 - Ajout section Équipe avec gestion AJAX
 */

ob_start();

// Extraire juste la date (sans l'heure) pour les champs date
$start_date_only = !empty($campaign['start_date']) ? substr($campaign['start_date'], 0, 10) : '';
$end_date_only = !empty($campaign['end_date']) ? substr($campaign['end_date'], 0, 10) : '';

// Récupérer les assignees et available users si non définis
if (!isset($assignees)) $assignees = [];
if (!isset($availableUsers)) $availableUsers = [];
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
                    <?= htmlspecialchars($campaign['name']) ?>
                </p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="/stm/admin/campaigns/<?= $campaign['id'] ?>"
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    Voir la campagne
                </a>
                <a href="/stm/admin/campaigns"
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Retour à la liste
                </a>
            </div>
        </div>
    </div>

    <!-- Messages flash -->
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

    <?php if (isset($_SESSION['errors'])): ?>
        <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4">
            <h3 class="text-sm font-bold text-red-700 mb-2">Erreurs de validation :</h3>
            <ul class="list-disc list-inside text-sm text-red-700">
                <?php foreach ($_SESSION['errors'] as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['errors']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['info'])): ?>
        <div class="mb-6 bg-blue-50 border-l-4 border-blue-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700"><?= htmlspecialchars($_SESSION['info']) ?></p>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['info']); ?>
    <?php endif; ?>

    <!-- Formulaire -->
    <form method="POST"
          action="/stm/admin/campaigns/<?= $campaign['id'] ?>"
          class="space-y-8"
          x-data="{
              assignmentMode: '<?= htmlspecialchars($campaign['customer_assignment_mode']) ?>',
              deferredDelivery: <?= $campaign['deferred_delivery'] ? 'true' : 'false' ?>
          }">

        <!-- Token CSRF -->
        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="_method" value="PUT">

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
                           required
                           maxlength="255"
                           value="<?= htmlspecialchars($old['name'] ?? $campaign['name']) ?>"
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
                        <option value="BE" <?= ($old['country'] ?? $campaign['country']) === 'BE' ? 'selected' : '' ?>>🇧🇪 Belgique</option>
                        <option value="LU" <?= ($old['country'] ?? $campaign['country']) === 'LU' ? 'selected' : '' ?>>🇱🇺 Luxembourg</option>
                    </select>
                    <p class="mt-1 text-sm text-gray-500">
                        Détermine les clients éligibles à la campagne
                    </p>
                </div>

                <!-- Statut actif -->
                <div class="flex items-center">
                    <input type="checkbox"
                           id="is_active"
                           name="is_active"
                           value="1"
                           <?= ($old['is_active'] ?? $campaign['is_active']) ? 'checked' : '' ?>
                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 block text-sm text-gray-700">
                        Campagne active
                    </label>
                </div>

                <!-- Dates -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Date de début -->
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Date de début <span class="text-red-500">*</span>
                        </label>
                        <input type="date"
                               id="start_date"
                               name="start_date"
                               required
                               value="<?= htmlspecialchars($old['start_date'] ?? $start_date_only) ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <p class="mt-1 text-sm text-gray-500">
                            ⏰ Début automatique à 00:01
                        </p>
                    </div>

                    <!-- Date de fin -->
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Date de fin <span class="text-red-500">*</span>
                        </label>
                        <input type="date"
                               id="end_date"
                               name="end_date"
                               required
                               value="<?= htmlspecialchars($old['end_date'] ?? $end_date_only) ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <p class="mt-1 text-sm text-gray-500">
                            ⏰ Fin automatique à 23:59
                        </p>
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
                                   <?= ($old['customer_assignment_mode'] ?? $campaign['customer_assignment_mode']) === 'automatic' ? 'checked' : '' ?>
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
                                   <?= ($old['customer_assignment_mode'] ?? $campaign['customer_assignment_mode']) === 'manual' ? 'checked' : '' ?>
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
                                   <?= ($old['customer_assignment_mode'] ?? $campaign['customer_assignment_mode']) === 'protected' ? 'checked' : '' ?>
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
                     class="p-4 bg-blue-50 border border-blue-200 rounded-lg"
                     style="display: <?= ($campaign['customer_assignment_mode'] === 'manual') ? 'block' : 'none' ?>;">
                    <label for="customer_list" class="block text-sm font-medium text-gray-900 mb-2">
                        Liste des numéros clients
                    </label>
                    <textarea id="customer_list"
                              name="customer_list"
                              rows="6"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono text-sm"
                              placeholder="123456&#10;654321&#10;789012&#10;..."><?= htmlspecialchars($old['customer_list'] ?? $campaign['customer_list'] ?? '') ?></textarea>
                    <p class="mt-2 text-sm text-gray-600">
                        📝 Entrez un numéro client par ligne. Formats acceptés : 123456, 123456-12, E12345-CB, *12345
                    </p>
                </div>

                <!-- Mot de passe (si mode protected) -->
                <div x-show="assignmentMode === 'protected'"
                     x-transition
                     class="p-4 bg-amber-50 border border-amber-200 rounded-lg"
                     style="display: <?= ($campaign['customer_assignment_mode'] === 'protected') ? 'block' : 'none' ?>;">
                    <label for="order_password" class="block text-sm font-medium text-gray-900 mb-2">
                        Mot de passe d'accès
                    </label>
                    <input type="text"
                           id="order_password"
                           name="order_password"
                           maxlength="255"
                           value="<?= htmlspecialchars($old['order_password'] ?? $campaign['order_password'] ?? '') ?>"
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
                        <label class="relative flex items-start p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                            <input type="radio"
                                   name="order_type"
                                   value="W"
                                   <?= ($old['order_type'] ?? $campaign['order_type']) === 'W' ? 'checked' : '' ?>
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
                        <label class="relative flex items-start p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                            <input type="radio"
                                   name="order_type"
                                   value="V"
                                   <?= ($old['order_type'] ?? $campaign['order_type']) === 'V' ? 'checked' : '' ?>
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
                               <?= ($old['deferred_delivery'] ?? $campaign['deferred_delivery']) ? 'checked' : '' ?>
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
                         class="mt-4"
                         style="display: <?= ($campaign['deferred_delivery']) ? 'block' : 'none' ?>;">
                        <label for="delivery_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Date de livraison souhaitée
                        </label>
                        <input type="date"
                               id="delivery_date"
                               name="delivery_date"
                               value="<?= htmlspecialchars($old['delivery_date'] ?? $campaign['delivery_date'] ?? '') ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <p class="mt-1 text-sm text-gray-600">
                            📦 Les commandes seront livrées à cette date
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 4 : Équipe -->
        <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg">
            <div class="px-6 py-5 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            👤 Équipe
                        </h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Utilisateurs assignés à cette campagne
                        </p>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                        <?= count($assignees) ?> membre<?= count($assignees) > 1 ? 's' : '' ?>
                    </span>
                </div>
            </div>

            <div class="px-6 py-6">
                <!-- Ajouter un collaborateur -->
                <?php if (!empty($availableUsers)): ?>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Ajouter un collaborateur
                    </label>
                    <div class="flex gap-3">
                        <select id="add_collaborator_select"
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">-- Sélectionner un utilisateur --</option>
                            <?php foreach ($availableUsers as $user): ?>
                                <option value="<?= $user['id'] ?>">
                                    <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button"
                                onclick="addCollaborator()"
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Ajouter
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Liste des collaborateurs -->
                <div id="team_list" class="space-y-3">
                    <?php if (empty($assignees)): ?>
                        <p class="text-sm text-gray-500 italic">Aucun collaborateur assigné</p>
                    <?php else: ?>
                        <?php foreach ($assignees as $assignee): ?>
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg"
                                 data-user-id="<?= $assignee['user_id'] ?>">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                        <span class="text-indigo-700 font-medium text-sm">
                                            <?= strtoupper(substr($assignee['user_name'] ?? 'U', 0, 2)) ?>
                                        </span>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($assignee['user_name'] ?? 'Utilisateur') ?>
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            <?= htmlspecialchars($assignee['user_email'] ?? '') ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <?php if ($assignee['role'] === 'owner'): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            👑 Owner
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Collaborateur
                                        </span>
                                        <button type="button"
                                                onclick="removeCollaborator(<?= $assignee['user_id'] ?>)"
                                                class="text-red-600 hover:text-red-900">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Info box -->
                <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-xs text-blue-700">
                        💡 <strong>Owner</strong> : Créateur de la campagne (non supprimable) •
                        <strong>Collaborateur</strong> : Peut voir et modifier la campagne
                    </p>
                </div>
            </div>
        </div>

        <!-- SECTION 5 : Contenu multilingue -->
        <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg">
            <div class="px-6 py-5 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">
                    🌐 Contenu multilingue
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    Titres et descriptions de la campagne en français et néerlandais
                </p>
            </div>

            <div class="px-6 py-6 space-y-6">
                <!-- Titre FR -->
                <div>
                    <label for="title_fr" class="block text-sm font-medium text-gray-700 mb-2">
                        🇫🇷 Titre français <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           id="title_fr"
                           name="title_fr"
                           required
                           maxlength="255"
                           value="<?= htmlspecialchars($old['title_fr'] ?? $campaign['title_fr']) ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="Ex: Promotions du Printemps 2025">
                </div>

                <!-- Description FR -->
                <div>
                    <label for="description_fr" class="block text-sm font-medium text-gray-700 mb-2">
                        🇫🇷 Description française
                    </label>
                    <textarea id="description_fr"
                              name="description_fr"
                              rows="4"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                              placeholder="Décrivez la campagne en français..."><?= htmlspecialchars($old['description_fr'] ?? $campaign['description_fr'] ?? '') ?></textarea>
                </div>

                <!-- Titre NL -->
                <div>
                    <label for="title_nl" class="block text-sm font-medium text-gray-700 mb-2">
                        🇳🇱 Titre néerlandais <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           id="title_nl"
                           name="title_nl"
                           required
                           maxlength="255"
                           value="<?= htmlspecialchars($old['title_nl'] ?? $campaign['title_nl']) ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="Bijv: Lentepromoties 2025">
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
                              placeholder="Beschrijf de campagne in het Nederlands..."><?= htmlspecialchars($old['description_nl'] ?? $campaign['description_nl'] ?? '') ?></textarea>
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

<!-- Toast container -->
<div id="toast-container" class="fixed bottom-4 right-4 z-50 space-y-2"></div>

<script>
// Toast notification
function showToast(type, message) {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');

    const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
    const icon = type === 'success'
        ? '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>'
        : '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';

    toast.className = `${bgColor} text-white px-4 py-3 rounded-lg shadow-lg flex items-center space-x-2 transform translate-x-full transition-transform duration-300`;
    toast.innerHTML = `${icon}<span>${message}</span>`;

    container.appendChild(toast);

    setTimeout(() => toast.classList.remove('translate-x-full'), 10);
    setTimeout(() => {
        toast.classList.add('translate-x-full');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Ajouter un collaborateur
function addCollaborator() {
    const select = document.getElementById('add_collaborator_select');
    const userId = select.value;

    if (!userId) {
        showToast('error', 'Veuillez sélectionner un utilisateur');
        return;
    }

    const csrfToken = '<?= $_SESSION['csrf_token'] ?>';

    fetch('/stm/admin/campaigns/<?= $campaign['id'] ?>/assignees', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            user_id: userId,
            csrf_token: csrfToken
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showToast('success', result.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('error', result.message);
        }
    })
    .catch(error => {
        showToast('error', 'Erreur de connexion au serveur');
        console.error('Error:', error);
    });
}

// Retirer un collaborateur
function removeCollaborator(userId) {
    if (!confirm('Retirer ce collaborateur de l\'équipe ?')) return;

    const csrfToken = '<?= $_SESSION['csrf_token'] ?>';

    fetch('/stm/admin/campaigns/<?= $campaign['id'] ?>/assignees/' + userId + '/delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ csrf_token: csrfToken })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showToast('success', result.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('error', result.message);
        }
    })
    .catch(error => {
        showToast('error', 'Erreur de connexion au serveur');
        console.error('Error:', error);
    });
}
</script>

<?php
$content = ob_get_clean();
$title = 'Modifier la campagne - STM';
require __DIR__ . '/../../layouts/admin.php';
?>