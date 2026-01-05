<?php
/**
 * Vue : Liste des campagnes
 *
 * @package STM/Views/Admin/Campaigns
 * @version 2.4.0
 * @created 07/11/2025
 * @modified 14/11/2025 - Ajout colonne statistiques (clients + promotions)
 * @modified 15/12/2025 - Masquage conditionnel boutons selon permissions (Phase 5)
 * @modified 19/12/2025 - Correction affichage statut (is_active + dates)
 */

use App\Helpers\PermissionHelper;

// Vérification des permissions pour l'affichage conditionnel
$canCreate = PermissionHelper::can('campaigns.create');
$canEdit = PermissionHelper::can('campaigns.edit');
$canDelete = PermissionHelper::can('campaigns.delete');

$pageTitle = "Campagnes";
ob_start();
?>

<!-- En-tête avec actions -->
<div class="sm:flex sm:items-center sm:justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Gestion des campagnes</h2>
        <p class="mt-1 text-sm text-gray-500">
            <?= $total ?> campagne<?= $total > 1 ? "s" : "" ?> au total
        </p>
    </div>
    <?php if ($canCreate): ?>
    <div class="mt-4 sm:mt-0">
        <a href="/stm/admin/campaigns/create"
           class="inline-flex items-center gap-x-2 rounded-md bg-purple-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-600">
            <svg class="-ml-0.5 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Nouvelle campagne
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Statistiques rapides -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Total</dt>
                        <dd class="text-lg font-semibold text-gray-900"><?= $stats["total"] ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Actives</dt>
                        <dd class="text-lg font-semibold text-green-600"><?= $stats["active"] ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0l2.77-.693a9 9 0 016.208.682l.108.054a9 9 0 006.086.71l3.114-.732a48.524 48.524 0 01-.005-10.499l-3.11.732a9 9 0 01-6.085-.711l-.108-.054a9 9 0 00-6.208-.682L3 4.5M3 15V4.5" />
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Belgique</dt>
                        <dd class="text-lg font-semibold text-gray-900"><?= $stats["be"] ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0l2.77-.693a9 9 0 016.208.682l.108.054a9 9 0 006.086.71l3.114-.732a48.524 48.524 0 01-.005-10.499l-3.11.732a9 9 0 01-6.085-.711l-.108-.054a9 9 0 00-6.208-.682L3 4.5M3 15V4.5" />
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Luxembourg</dt>
                        <dd class="text-lg font-semibold text-gray-900"><?= $stats["lu"] ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="bg-white shadow-sm rounded-lg mb-6">
    <div class="p-6">
        <form method="GET" action="/stm/admin/campaigns" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700">Recherche</label>
                <input type="text"
                       name="search"
                       id="search"
                       value="<?= htmlspecialchars($_GET["search"] ?? "") ?>"
                       placeholder="Nom, titre..."
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm">
            </div>

            <div>
                <label for="country" class="block text-sm font-medium text-gray-700">Pays</label>
                <select name="country"
                        id="country"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm">
                    <option value="">Tous les pays</option>
                    <option value="BE" <?= ($_GET["country"] ?? "") === "BE" ? "selected" : "" ?>>Belgique</option>
                    <option value="LU" <?= ($_GET["country"] ?? "") === "LU" ? "selected" : "" ?>>Luxembourg</option>
                </select>
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700">Statut</label>
                <select name="status"
                        id="status"
                        autocomplete="off"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm">
                    <option value="" <?= empty($_GET["status"]) ? "selected" : "" ?>>Tous les statuts</option>
                    <option value="active" <?= ($_GET["status"] ?? "") === "active" ? "selected" : "" ?>>Active (en cours)</option>
                    <option value="upcoming" <?= ($_GET["status"] ?? "") === "upcoming" ? "selected" : "" ?>>À venir</option>
                    <option value="ended" <?= ($_GET["status"] ?? "") === "ended" ? "selected" : "" ?>>Terminée</option>
                    <option value="inactive" <?= ($_GET["status"] ?? "") === "inactive" ? "selected" : "" ?>>Inactive (désactivée)</option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit"
                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    Filtrer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tableau des campagnes -->
<div class="bg-white shadow-sm rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Campagne
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Pays
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Période
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Statistiques
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        URLs
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Statut
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($campaigns)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Aucune campagne</h3>
                            <p class="mt-1 text-sm text-gray-500">Commencez par créer une nouvelle campagne.</p>
                            <?php if ($canCreate): ?>
                            <div class="mt-6">
                                <a href="/stm/admin/campaigns/create"
                                   class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                    Créer une campagne
                                </a>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($campaigns as $campaign): ?>
                        <tr class="hover:bg-gray-50">
                            <!-- Nom -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($campaign["name"]) ?>
                                        </div>
                                        <?php if (!empty($campaign["title_fr"])): ?>
                                            <div class="text-sm text-gray-500">
                                                <?= htmlspecialchars(substr($campaign["title_fr"], 0, 50)) .
                                                    (strlen($campaign["title_fr"]) > 50 ? "..." : "") ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>

                            <!-- Pays -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                    <?= $campaign["country"] === "BE"
                                        ? "bg-yellow-100 text-yellow-800"
                                        : "bg-red-100 text-red-800" ?>">
                                    <?= $campaign["country"] ?>
                                </span>
                            </td>

                            <!-- Période -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div>
                                    <?= date("d/m/Y", strtotime($campaign["start_date"])) ?>
                                </div>
                                <div>
                                    <?= date("d/m/Y", strtotime($campaign["end_date"])) ?>
                                </div>
                            </td>

                            <!-- Statistiques -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php
                                $customerStats = $campaign["customer_stats"] ?? [];
                                $eligible = $customerStats["total"] ?? 0;
                                $ordered = $customerStats["with_orders"] ?? 0;
                                $promotionCount = $campaign["promotion_count"] ?? 0;
                                ?>
                                <div class="flex flex-col gap-1">
                                    <span class="inline-flex items-center text-xs">
                                        <svg class="mr-1 h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                        </svg>
                                        <?= $eligible ?> élig. / <?= $ordered ?> cmd
                                    </span>
                                    <span class="inline-flex items-center text-xs">
                                        <svg class="mr-1 h-4 w-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                                        </svg>
                                        <?= $promotionCount ?> promo<?= $promotionCount > 1 ? "s" : "" ?>
                                    </span>
                                </div>
                            </td>

                            <!-- URLs (Client + Reps) -->
                            <td class="px-6 py-4 text-sm">
                                <?php if (!empty($campaign["uuid"])): ?>
                                    <?php
                                    $baseUrl = $_ENV["APP_URL"] ?? $_SERVER["APP_URL"] ?? "https://actions.trendyfoods.com/stm";
                                    $clientUrl = $baseUrl . "/c/" . $campaign["uuid"];
                                    $repUrl = $baseUrl . "/c/" . $campaign["uuid"] . "/rep";
                                    $shortUuid = "..." . substr($campaign["uuid"], -8);
                                    ?>
                                    <div class="flex flex-col gap-1.5">
                                        <!-- URL Client -->
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700" title="Accès client direct">
                                                👤
                                            </span>
                                            <a href="<?= $clientUrl ?>"
                                               target="_blank"
                                               class="text-purple-600 hover:text-purple-800 font-mono text-xs"
                                               title="<?= $clientUrl ?>">
                                                <?= $shortUuid ?>
                                            </a>
                                            <button onclick="copyToClipboard('<?= $clientUrl ?>', this)"
                                                    class="text-gray-400 hover:text-gray-600"
                                                    title="Copier URL Client">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" />
                                                </svg>
                                            </button>
                                        </div>
                                        <!-- URL Reps -->
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700" title="Accès représentant (SSO)">
                                                🧑‍💼
                                            </span>
                                            <a href="<?= $repUrl ?>"
                                               target="_blank"
                                               class="text-purple-600 hover:text-purple-800 font-mono text-xs"
                                               title="<?= $repUrl ?>">
                                                <?= $shortUuid ?>/rep
                                            </a>
                                            <button onclick="copyToClipboard('<?= $repUrl ?>', this)"
                                                    class="text-gray-400 hover:text-gray-600"
                                                    title="Copier URL Reps">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>

                            <!-- Statut -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                // Calculer le statut réel basé sur is_active ET les dates
                                $today = date('Y-m-d');
                                $startDate = $campaign["start_date"];
                                $endDate = $campaign["end_date"];

                                if (!$campaign["is_active"]) {
                                    // Désactivé manuellement
                                    $statusClass = "bg-red-100 text-red-800";
                                    $dotClass = "fill-red-500";
                                    $statusLabel = "Inactive";
                                } elseif ($today < $startDate) {
                                    // Pas encore commencée
                                    $statusClass = "bg-blue-100 text-blue-800";
                                    $dotClass = "fill-blue-500";
                                    $statusLabel = "À venir";
                                } elseif ($today > $endDate) {
                                    // Terminée
                                    $statusClass = "bg-gray-100 text-gray-800";
                                    $dotClass = "fill-gray-500";
                                    $statusLabel = "Terminée";
                                } else {
                                    // Active et dans la période
                                    $statusClass = "bg-green-100 text-green-800";
                                    $dotClass = "fill-green-500";
                                    $statusLabel = "Active";
                                }
                                ?>
                                <span class="inline-flex items-center rounded-full <?= $statusClass ?> px-2.5 py-0.5 text-xs font-medium">
                                    <svg class="mr-1.5 h-2 w-2 <?= $dotClass ?>" viewBox="0 0 6 6">
                                        <circle cx="3" cy="3" r="3" />
                                    </svg>
                                    <?= $statusLabel ?>
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end gap-x-2">
                                    <!-- Voir (toujours visible) -->
                                    <a href="/stm/admin/campaigns/<?= $campaign["id"] ?>"
                                       class="text-purple-600 hover:text-purple-900"
                                       title="Voir les détails">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </a>

                                    <?php if ($canEdit): ?>
                                    <!-- Modifier -->
                                    <a href="/stm/admin/campaigns/<?= $campaign["id"] ?>/edit"
                                       class="text-blue-600 hover:text-blue-900"
                                       title="Modifier">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                        </svg>
                                    </a>
                                    <?php endif; ?>

                                    <?php if ($canDelete): ?>
                                    <!-- Supprimer -->
                                    <form method="POST"
                                          action="/stm/admin/campaigns/<?= $campaign["id"] ?>/delete"
                                          onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette campagne ?')"
                                          class="inline">
                                        <input type="hidden" name="_token" value="<?= $_SESSION["csrf_token"] ?? "" ?>">
                                        <button type="submit"
                                                class="text-red-600 hover:text-red-900"
                                                title="Supprimer">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
            <div class="flex-1 flex justify-between sm:hidden">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=<?= $currentPage - 1 .
                        (!empty($_GET["search"]) ? "&search=" . urlencode($_GET["search"]) : "") .
                        (!empty($_GET["country"]) ? "&country=" . $_GET["country"] : "") .
                        (!empty($_GET["status"]) ? "&status=" . $_GET["status"] : "") ?>"
                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Précédent
                    </a>
                <?php endif; ?>
                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?= $currentPage + 1 .
                        (!empty($_GET["search"]) ? "&search=" . urlencode($_GET["search"]) : "") .
                        (!empty($_GET["country"]) ? "&country=" . $_GET["country"] : "") .
                        (!empty($_GET["status"]) ? "&status=" . $_GET["status"] : "") ?>"
                       class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Suivant
                    </a>
                <?php endif; ?>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Affichage de
                        <span class="font-medium"><?= ($currentPage - 1) * $perPage + 1 ?></span>
                        à
                        <span class="font-medium"><?= min($currentPage * $perPage, $total) ?></span>
                        sur
                        <span class="font-medium"><?= $total ?></span>
                        résultats
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?= $i .
                                (!empty($_GET["search"]) ? "&search=" . urlencode($_GET["search"]) : "") .
                                (!empty($_GET["country"]) ? "&country=" . $_GET["country"] : "") .
                                (!empty($_GET["status"]) ? "&status=" . $_GET["status"] : "") ?>"
                               class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?= $i === $currentPage
                                   ? "z-10 bg-purple-50 border-purple-500 text-purple-600"
                                   : "bg-white border-gray-300 text-gray-500 hover:bg-gray-50" ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </nav>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Script copie URL -->
<script>
function copyToClipboard(text, button) {
    navigator.clipboard.writeText(text).then(() => {
        // Feedback visuel
        const originalHTML = button.innerHTML;
        button.innerHTML = '<svg class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg><span class="text-green-600">Copié !</span>';

        setTimeout(() => {
            button.innerHTML = originalHTML;
        }, 2000);
    }).catch(err => {
        alert('Erreur lors de la copie : ' + err);
    });
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . "/../../layouts/admin.php";
?>