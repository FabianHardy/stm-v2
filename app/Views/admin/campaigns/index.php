<?php

/**

 * Vue : Liste des campagnes

 *

 * @package STM/Views/Admin/Campaigns

 * @version 2.2.0

 * @created 07/11/2025

 * @modified 14/11/2025 - Ajout colonne statistiques (clients + promotions)

 */

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

    <div class="mt-4 sm:mt-0">

        <a href="/stm/admin/campaigns/create"

           class="inline-flex items-center gap-x-2 rounded-md bg-purple-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-600">

            <svg class="-ml-0.5 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">

                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />

            </svg>

            Nouvelle campagne

        </a>

    </div>

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

                <label for="is_active" class="block text-sm font-medium text-gray-700">Statut</label>

                <select name="is_active"

                        id="is_active"

                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm">

                    <option value="">Tous les statuts</option>

                    <option value="1" <?= isset($_GET["is_active"]) && $_GET["is_active"] === "1"
                        ? "selected"
                        : "" ?>>Active</option>

                    <option value="0" <?= isset($_GET["is_active"]) && $_GET["is_active"] === "0"
                        ? "selected"
                        : "" ?>>Inactive</option>

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

                    <!-- 🆕 NOUVELLE COLONNE STATISTIQUES -->

                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">

                        Statistiques

                    </th>

                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">

                        URL Publique

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

                        <!-- 🆕 COLSPAN MODIFIÉ : 7 au lieu de 6 -->

                        <td colspan="7" class="px-6 py-12 text-center">

                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">

                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />

                            </svg>

                            <h3 class="mt-2 text-sm font-medium text-gray-900">Aucune campagne</h3>

                            <p class="mt-1 text-sm text-gray-500">Commencez par créer une nouvelle campagne.</p>

                            <div class="mt-6">

                                <a href="/stm/admin/campaigns/create"

                                   class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">

                                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">

                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />

                                    </svg>

                                    Créer une campagne

                                </a>

                            </div>

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

                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium

                                    <?= $campaign["country"] === "BE"
                                        ? "bg-blue-100 text-blue-800"
                                        : "bg-red-100 text-red-800" ?>">

                                    <?= $campaign["country"] ?>

                                </span>

                            </td>



                            <!-- Période -->

                            <td class="px-6 py-4 whitespace-nowrap">

                                <div class="text-sm text-gray-900">

                                    <?= date("d/m/Y", strtotime($campaign["start_date"])) ?>

                                </div>

                                <div class="text-sm text-gray-500">

                                    <?= date("d/m/Y", strtotime($campaign["end_date"])) ?>

                                </div>

                            </td>



                            <!-- 🆕 STATISTIQUES -->

                            <td class="px-6 py-4">

                                <?php
                                $totalCustomers = $campaign["customer_stats"]["total"];

                                $withOrders = $campaign["customer_stats"]["with_orders"];

                                $promotions = $campaign["promotion_count"];
                                ?>

                                <div class="space-y-1">

                                    <!-- Clients -->

                                    <div class="flex items-center text-xs">

                                        <svg class="h-4 w-4 text-gray-400 mr-1.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">

                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />

                                        </svg>

                                        <span class="text-gray-600">

                                            <?php if ($totalCustomers === "Tous"): ?>

                                                Tous <?= $campaign["country"] ?>

                                            <?php else: ?>

                                                <?= $totalCustomers ?> élig.

                                            <?php endif; ?>

                                            <span class="text-gray-400 mx-1">/</span>

                                            <span class="font-medium text-gray-900"><?= $withOrders ?></span> cmd

                                        </span>

                                    </div>



                                    <!-- Promotions -->

                                    <div class="flex items-center text-xs">

                                        <svg class="h-4 w-4 text-gray-400 mr-1.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">

                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />

                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />

                                        </svg>

                                        <span class="text-gray-600">

                                            <span class="font-medium text-gray-900"><?= $promotions ?></span> promo<?= $promotions >
1
    ? "s"
    : "" ?>

                                        </span>

                                    </div>

                                </div>

                            </td>



                            <!-- URL Publique -->

                            <td class="px-6 py-4 whitespace-nowrap">

                                <button type="button"

                                    <?php $publicUrl =
                                        ($_ENV["APP_URL"] ?? "https://actions.trendyfoods.com/stm") .
                                        "/c/" .
                                        htmlspecialchars($campaign["unique_url"]); ?>
                                    onclick="copyToClipboard('<?= $publicUrl ?>', this)"

                                        class="inline-flex items-center gap-x-1.5 px-2.5 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors"

                                        title="Copier le lien public"

                                >

                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">

                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />

                                    </svg>

                                    <span class="font-mono text-xs">...<?= substr($campaign["uuid"], -8) ?></span>

                                </button>

                            </td>



                            <!-- Statut -->

                            <td class="px-6 py-4 whitespace-nowrap">

                                <?php if ($campaign["is_active"]): ?>

                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">

                                        <svg class="mr-1.5 h-2 w-2 fill-green-500" viewBox="0 0 6 6">

                                            <circle cx="3" cy="3" r="3" />

                                        </svg>

                                        Active

                                    </span>

                                <?php else: ?>

                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">

                                        <svg class="mr-1.5 h-2 w-2 fill-gray-500" viewBox="0 0 6 6">

                                            <circle cx="3" cy="3" r="3" />

                                        </svg>

                                        Inactive

                                    </span>

                                <?php endif; ?>

                            </td>



                            <!-- Actions -->

                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">

                                <div class="flex justify-end gap-x-2">

                                    <a href="/stm/admin/campaigns/<?= $campaign["id"] ?>"

                                       class="text-purple-600 hover:text-purple-900"

                                       title="Voir les détails">

                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">

                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />

                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />

                                        </svg>

                                    </a>

                                    <a href="/stm/admin/campaigns/<?= $campaign["id"] ?>/edit"

                                       class="text-blue-600 hover:text-blue-900"

                                       title="Modifier">

                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">

                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />

                                        </svg>

                                    </a>

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

<a href="?page=<?= $currentPage -
    1 .
    (!empty($_GET["search"]) ? "&search=" . urlencode($_GET["search"]) : "") .
    (!empty($_GET["country"]) ? "&country=" . $_GET["country"] : "") .
    (isset($_GET["is_active"]) ? "&is_active=" . $_GET["is_active"] : "") ?>"

                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">

                        Précédent

                    </a>

                <?php endif; ?>

                <?php if ($currentPage < $totalPages): ?>

                    <a href="?page=<?= $currentPage +
                        1 .
                        (!empty($_GET["search"]) ? "&search=" . urlencode($_GET["search"]) : "") .
                        (!empty($_GET["country"]) ? "&country=" . $_GET["country"] : "") .
                        (isset($_GET["is_active"]) ? "&is_active=" . $_GET["is_active"] : "") ?>"

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
                                (isset($_GET["is_active"]) ? "&is_active=" . $_GET["is_active"] : "") ?>"

                               class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?= $i ===
                               $currentPage
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
