<?php

/**

 * Vue : Détails d'une campagne

 *

 * Affiche toutes les informations d'une campagne :

 * - Statistiques rapides (clients, promotions, commandes)

 * - Informations de base

 * - Attribution clients

 * - Paramètres de commande

 * - Contenu multilingue

 * - Actions disponibles

 *

 * @created  2025/11/14 02:00

 * @modified 2025/11/14 16:00 - Ajout statistiques clients ayant commandé + % conversion

 */

ob_start();

// Calculer le statut de la campagne

$now = new DateTime();

$start = new DateTime($campaign["start_date"]);

$end = new DateTime($campaign["end_date"]);

if ($now < $start) {
    $statusClass = "bg-blue-100 text-blue-800";

    $statusText = "📅 À venir";
} elseif ($now > $end) {
    $statusClass = "bg-gray-100 text-gray-800";

    $statusText = "🏁 Terminée";
} else {
    $statusClass = "bg-green-100 text-green-800";

    $statusText = "✅ En cours";
}
?>



<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- En-tête -->

    <div class="mb-8">

        <div class="flex items-center justify-between">

            <div>

                <div class="flex items-center gap-3 mb-2">

                    <h1 class="text-3xl font-bold text-gray-900">

                        <?= htmlspecialchars($campaign["name"]) ?>

                    </h1>

                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= $statusClass ?>">

                        <?= $statusText ?>

                    </span>

                    <?php if (!$campaign["is_active"]): ?>

                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">

                            ⏸️ Inactive

                        </span>

                    <?php endif; ?>

                </div>

                <p class="text-sm text-gray-600">

                    <?= $campaign["country"] === "BE" ? "🇧🇪 Belgique" : "🇱🇺 Luxembourg" ?> •

                    Du <?= date("d/m/Y à H:i", strtotime($campaign["start_date"])) ?>

                    au <?= date("d/m/Y à H:i", strtotime($campaign["end_date"])) ?>

                </p>

            </div>

            <div class="flex items-center space-x-3">

                <a href="/stm/admin/campaigns/<?= $campaign["id"] ?>/edit"

                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">

                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>

                    </svg>

                    Modifier

                </a>

                <a href="/stm/admin/campaigns"

                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">

                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>

                    </svg>

                    Retour

                </a>

            </div>

        </div>

    </div>



    <!-- Messages flash -->

    <?php if (isset($_SESSION["success"])): ?>

        <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4">

            <div class="flex">

                <div class="flex-shrink-0">

                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">

                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>

                    </svg>

                </div>

                <div class="ml-3">

                    <p class="text-sm text-green-700"><?= htmlspecialchars($_SESSION["success"]) ?></p>

                </div>

            </div>

        </div>

        <?php unset($_SESSION["success"]); ?>

    <?php endif; ?>



    <!-- SECTION : Lien de la campagne -->

    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg shadow-lg p-6 mb-8 text-white">

        <div class="flex items-center justify-between">

            <div class="flex-1">

                <h3 class="text-sm font-semibold uppercase tracking-wide mb-2 opacity-90">

                    🔗 Lien public de la campagne

                </h3>

                <div class="flex items-center space-x-3">

                    <div class="flex-1 bg-white bg-opacity-20 rounded-lg px-4 py-3 backdrop-blur-sm">

                        <code id="campaign-url" class="text-white font-mono text-sm break-all">

                            <?= $_ENV["APP_URL"] ?? "https://actions.trendyfoods.com/stm" ?>/c/<?= htmlspecialchars(
    $campaign["unique_url"],
) ?>

                        </code>

                    </div>

                    <button type="button"

                            onclick="copyToClipboard()"

                            class="flex-shrink-0 inline-flex items-center px-4 py-3 bg-white text-indigo-600 rounded-lg font-medium text-sm hover:bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-indigo-600 transition">

                        <svg id="copy-icon" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>

                        </svg>

                        <span id="copy-text">Copier</span>

                    </button>

                </div>

                <p class="mt-3 text-sm opacity-90">

                    💡 Partagez ce lien avec vos clients pour qu'ils accèdent directement à la campagne

                </p>

            </div>

        </div>

    </div>



    <script>

        function copyToClipboard() {

            const url = document.getElementById('campaign-url').textContent.trim();

            const copyText = document.getElementById('copy-text');

            const copyIcon = document.getElementById('copy-icon');



            navigator.clipboard.writeText(url).then(function() {

                // Animation de succès

                copyText.textContent = 'Copié !';

                copyIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>';



                // Reset après 2 secondes

                setTimeout(function() {

                    copyText.textContent = 'Copier';

                    copyIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>';

                }, 2000);

            }, function(err) {

                copyText.textContent = 'Erreur';

                setTimeout(function() {

                    copyText.textContent = 'Copier';

                }, 2000);

            });

        }

    </script>



    <!-- SECTION : Statistiques rapides -->

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

        <!-- 🆕 Carte Clients AMÉLIORÉE avec statistiques commandes -->

        <div class="bg-white overflow-hidden shadow-sm ring-1 ring-gray-900/5 rounded-lg hover:shadow-md transition">

            <div class="p-6">

                <div class="flex items-center justify-between mb-4">

                    <div class="flex items-center">

                        <div class="flex-shrink-0">

                            <div class="p-3 bg-blue-100 rounded-lg">

                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>

                                </svg>

                            </div>

                        </div>

                        <div class="ml-3">

                            <dt class="text-sm font-medium text-gray-500">

                                Clients

                            </dt>

                        </div>

                    </div>



                    <!-- Badge % de conversion (si mode manual) -->

                    <?php
                    $conversionRate = 0;

                    $isManual = $campaign["customer_assignment_mode"] === "manual";

                    $totalClients = $customerCount ?? 0;

                    $commandeClients = $customersWithOrders ?? 0;

                    if ($isManual && $totalClients > 0) {
                        $conversionRate = round(($commandeClients / $totalClients) * 100);
                    }
                    ?>

                    <?php if ($isManual && $totalClients > 0): ?>

                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold

                            <?php if ($conversionRate >= 50): ?>

                                bg-green-100 text-green-800

                            <?php elseif ($conversionRate >= 25): ?>

                                bg-yellow-100 text-yellow-800

                            <?php else: ?>

                                bg-gray-100 text-gray-800

                            <?php endif; ?>">

                            <?= $conversionRate ?>% conversion

                        </span>

                    <?php endif; ?>

                </div>



                <dl class="space-y-3">

                    <!-- Clients éligibles -->

                    <div class="flex items-center justify-between">

                        <dt class="text-xs font-medium text-gray-500 uppercase">Éligibles</dt>

                        <dd class="text-lg font-semibold text-gray-900">

                            <?php if ($isManual): ?>

                                <?= number_format($totalClients) ?>

                            <?php else: ?>

                                <span class="text-blue-600">Tous <?= $campaign["country"] ?></span>

                            <?php endif; ?>

                        </dd>

                    </div>



                    <!-- Clients ayant commandé -->

                    <div class="flex items-center justify-between pt-2 border-t border-gray-100">

                        <dt class="text-xs font-medium text-gray-500 uppercase">Ont commandé</dt>

                        <dd class="text-lg font-bold text-blue-600">

                            <?= number_format($commandeClients) ?>

                        </dd>

                    </div>

                </dl>

            </div>

        </div>



        <!-- Carte Promotions (INCHANGÉE) -->

        <div class="bg-white overflow-hidden shadow-sm ring-1 ring-gray-900/5 rounded-lg hover:shadow-md transition">

            <div class="p-6">

                <div class="flex items-center">

                    <div class="flex-shrink-0">

                        <div class="p-3 bg-purple-100 rounded-lg">

                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>

                            </svg>

                        </div>

                    </div>

                    <div class="ml-5 w-0 flex-1">

                        <dl>

                            <dt class="text-sm font-medium text-gray-500 truncate">

                                Promotions actives

                            </dt>

                            <dd class="text-2xl font-semibold text-gray-900">

                                <?= number_format($promotionCount ?? 0) ?>

                            </dd>

                        </dl>

                    </div>

                </div>

            </div>

        </div>



        <!-- Carte Commandes (INCHANGÉE) -->

        <div class="bg-white overflow-hidden shadow-sm ring-1 ring-gray-900/5 rounded-lg hover:shadow-md transition">

            <div class="p-6">

                <div class="flex items-center">

                    <div class="flex-shrink-0">

                        <div class="p-3 bg-green-100 rounded-lg">

                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>

                            </svg>

                        </div>

                    </div>

                    <div class="ml-5 w-0 flex-1">

                        <dl>

                            <dt class="text-sm font-medium text-gray-500 truncate">

                                Commandes reçues

                            </dt>

                            <dd class="text-2xl font-semibold text-gray-900">

                                <?= number_format($orderCount ?? 0) ?>

                            </dd>

                        </dl>

                    </div>

                </div>

            </div>

        </div>

    </div>



    <!-- SECTION 1 : Informations de base -->

    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg mb-8">

        <div class="px-6 py-5 border-b border-gray-200">

            <h2 class="text-lg font-semibold text-gray-900">

                📋 Informations de base

            </h2>

        </div>



        <div class="px-6 py-6">

            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-6">

                <!-- Nom -->

                <div>

                    <dt class="text-sm font-medium text-gray-500 mb-1">Nom de la campagne</dt>

                    <dd class="text-base text-gray-900 font-medium"><?= htmlspecialchars($campaign["name"]) ?></dd>

                </div>



                <!-- Pays -->

                <div>

                    <dt class="text-sm font-medium text-gray-500 mb-1">Pays</dt>

                    <dd class="text-base text-gray-900 font-medium">

                        <?= $campaign["country"] === "BE" ? "🇧🇪 Belgique" : "🇱🇺 Luxembourg" ?>

                    </dd>

                </div>



                <!-- Slug -->

                <div>

                    <dt class="text-sm font-medium text-gray-500 mb-1">Slug (URL)</dt>

                    <dd class="text-base text-gray-900 font-mono text-sm"><?= htmlspecialchars(
                        $campaign["slug"],
                    ) ?></dd>

                </div>



                <!-- UUID -->

                <div>

                    <dt class="text-sm font-medium text-gray-500 mb-1">UUID</dt>

                    <dd class="text-base text-gray-900 font-mono text-sm"><?= htmlspecialchars(
                        $campaign["uuid"],
                    ) ?></dd>

                </div>



                <!-- Date de début -->

                <div>

                    <dt class="text-sm font-medium text-gray-500 mb-1">Date de début</dt>

                    <dd class="text-base text-gray-900 font-medium">

                        <?= date("d/m/Y à H:i", strtotime($campaign["start_date"])) ?>

                    </dd>

                </div>



                <!-- Date de fin -->

                <div>

                    <dt class="text-sm font-medium text-gray-500 mb-1">Date de fin</dt>

                    <dd class="text-base text-gray-900 font-medium">

                        <?= date("d/m/Y à H:i", strtotime($campaign["end_date"])) ?>

                    </dd>

                </div>



                <!-- Statut actif -->

                <div>

                    <dt class="text-sm font-medium text-gray-500 mb-1">Statut</dt>

                    <dd>

                        <?php if ($campaign["is_active"]): ?>

                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">

                                ✅ Active

                            </span>

                        <?php else: ?>

                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">

                                ⏸️ Inactive

                            </span>

                        <?php endif; ?>

                    </dd>

                </div>



                <!-- Dates de création/modification -->

                <div>

                    <dt class="text-sm font-medium text-gray-500 mb-1">Dates système</dt>

                    <dd class="text-sm text-gray-600">

                        Créée le <?= date("d/m/Y", strtotime($campaign["created_at"])) ?><br>

                        Modifiée le <?= date("d/m/Y à H:i", strtotime($campaign["updated_at"])) ?>

                    </dd>

                </div>

            </dl>

        </div>

    </div>



    <!-- SECTION 2 : Attribution clients -->

    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg mb-8">

        <div class="px-6 py-5 border-b border-gray-200">

            <h2 class="text-lg font-semibold text-gray-900">

                👥 Attribution des clients

            </h2>

        </div>



        <div class="px-6 py-6">

            <dl class="space-y-6">

                <!-- Mode d'attribution -->

                <div>

                    <dt class="text-sm font-medium text-gray-500 mb-2">Mode d'attribution</dt>

                    <dd>

                        <?php
                        $modes = [
                            "automatic" => [
                                "icon" => "🌍",
                                "label" => "Tous les clients du pays (Automatique)",
                                "color" => "indigo",
                            ],

                            "manual" => ["icon" => "📝", "label" => "Liste manuelle de clients", "color" => "blue"],

                            "protected" => [
                                "icon" => "🔒",
                                "label" => "Accès protégé par mot de passe",
                                "color" => "amber",
                            ],
                        ];

                        $mode = $modes[$campaign["customer_assignment_mode"]];
                        ?>

                        <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-<?= $mode[
                            "color"
                        ] ?>-100 text-<?= $mode["color"] ?>-800">

                            <?= $mode["icon"] ?> <?= $mode["label"] ?>

                        </span>

                    </dd>

                </div>



                <!-- Détails selon le mode -->

                <?php if ($campaign["customer_assignment_mode"] === "manual"): ?>

                    <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">

                        <dt class="text-sm font-medium text-gray-900 mb-2">

                            📋 Clients éligibles (<?= number_format($customerCount ?? 0) ?>)

                        </dt>

                        <dd class="text-sm text-gray-700">

                            Liste restreinte de clients définie manuellement

                        </dd>

                    </div>

                <?php elseif (
                    $campaign["customer_assignment_mode"] === "protected" &&
                    !empty($campaign["order_password"])
                ): ?>

                    <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">

                        <dt class="text-sm font-medium text-gray-900 mb-2">🔑 Mot de passe d'accès</dt>

                        <dd class="text-base text-gray-900 font-mono font-semibold">

                            <?= htmlspecialchars($campaign["order_password"]) ?>

                        </dd>

                    </div>

                <?php endif; ?>

            </dl>

        </div>

    </div>



    <!-- SECTION 3 : Paramètres de commande -->

    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg mb-8">

        <div class="px-6 py-5 border-b border-gray-200">

            <h2 class="text-lg font-semibold text-gray-900">

                🚚 Paramètres de commande

            </h2>

        </div>



        <div class="px-6 py-6">

            <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Type de commande -->

                <div>

                    <dt class="text-sm font-medium text-gray-500 mb-2">Type de commande</dt>

                    <dd>

                        <?php if ($campaign["order_type"] === "W"): ?>

                            <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-green-100 text-green-800">

                                ✅ Commande normale (W)

                            </span>

                        <?php else: ?>

                            <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-purple-100 text-purple-800">

                                🎯 Prospection (V)

                            </span>

                        <?php endif; ?>

                    </dd>

                </div>



                <!-- Livraison -->

                <div>

                    <dt class="text-sm font-medium text-gray-500 mb-2">Modalité de livraison</dt>

                    <dd>

                        <?php if ($campaign["deferred_delivery"]): ?>

                            <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-orange-100 text-orange-800">

                                📅 Livraison différée

                            </span>

                            <?php if (!empty($campaign["delivery_date"])): ?>

                                <p class="mt-2 text-sm text-gray-600">

                                    Date prévue : <strong><?= date(
                                        "d/m/Y",
                                        strtotime($campaign["delivery_date"]),
                                    ) ?></strong>

                                </p>

                            <?php endif; ?>

                        <?php else: ?>

                            <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-green-100 text-green-800">

                                ⚡ Livraison immédiate

                            </span>

                        <?php endif; ?>

                    </dd>

                </div>

            </dl>

        </div>

    </div>



    <!-- SECTION 4 : Contenu multilingue -->

    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg mb-8">

        <div class="px-6 py-5 border-b border-gray-200">

            <h2 class="text-lg font-semibold text-gray-900">

                🌐 Contenu multilingue

            </h2>

        </div>



        <div class="px-6 py-6 space-y-6">

            <!-- Version française -->

            <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">

                <h3 class="text-sm font-semibold text-gray-900 mb-3">🇫🇷 Version française</h3>

                <dl class="space-y-3">

                    <div>

                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Titre</dt>

                        <dd class="text-base text-gray-900 font-medium">

                            <?= htmlspecialchars($campaign["title_fr"]) ?>

                        </dd>

                    </div>

                    <?php if (!empty($campaign["description_fr"])): ?>

                        <div>

                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Description</dt>

                            <dd class="text-sm text-gray-700 leading-relaxed">

                                <?= nl2br(htmlspecialchars($campaign["description_fr"])) ?>

                            </dd>

                        </div>

                    <?php endif; ?>

                </dl>

            </div>



            <!-- Version néerlandaise -->

            <div class="p-4 bg-orange-50 border border-orange-200 rounded-lg">

                <h3 class="text-sm font-semibold text-gray-900 mb-3">🇳🇱 Version néerlandaise</h3>

                <dl class="space-y-3">

                    <div>

                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Titel</dt>

                        <dd class="text-base text-gray-900 font-medium">

                            <?= htmlspecialchars($campaign["title_nl"]) ?>

                        </dd>

                    </div>

                    <?php if (!empty($campaign["description_nl"])): ?>

                        <div>

                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Beschrijving</dt>

                            <dd class="text-sm text-gray-700 leading-relaxed">

                                <?= nl2br(htmlspecialchars($campaign["description_nl"])) ?>

                            </dd>

                        </div>

                    <?php endif; ?>

                </dl>

            </div>

        </div>

    </div>



    <!-- Actions rapides -->

    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg">

        <div class="px-6 py-5 border-b border-gray-200">

            <h2 class="text-lg font-semibold text-gray-900">

                ⚡ Actions rapides

            </h2>

        </div>



        <div class="px-6 py-6">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                <!-- Modifier -->

                <a href="/stm/admin/campaigns/<?= $campaign["id"] ?>/edit"

                   class="inline-flex items-center justify-center px-4 py-3 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">

                    <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>

                    </svg>

                    Modifier

                </a>



                <!-- Voir les promotions -->

                <a href="/stm/admin/promotions?campaign=<?= $campaign["id"] ?>"

                   class="inline-flex items-center justify-center px-4 py-3 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition">

                    <svg class="w-5 h-5 mr-2 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>

                    </svg>

                    Promotions

                </a>



                <!-- Voir les commandes -->

                <a href="/stm/admin/orders?campaign=<?= $campaign["id"] ?>"

                   class="inline-flex items-center justify-center px-4 py-3 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition">

                    <svg class="w-5 h-5 mr-2 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>

                    </svg>

                    Commandes

                </a>



                <!-- Supprimer -->

                <button type="button"

                        onclick="if(confirm('Êtes-vous sûr de vouloir supprimer cette campagne ?')) { document.getElementById('delete-form').submit(); }"

                        class="inline-flex items-center justify-center px-4 py-3 border border-red-300 rounded-lg shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition">

                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>

                    </svg>

                    Supprimer

                </button>



                <!-- Formulaire de suppression caché -->

                <form id="delete-form" method="POST" action="/stm/admin/campaigns/<?= $campaign[
                    "id"
                ] ?>/delete" class="hidden">

                    <input type="hidden" name="_token" value="<?= $_SESSION["csrf_token"] ?>">

                    <input type="hidden" name="_method" value="DELETE">

                </form>

            </div>

        </div>

    </div>

</div>



<?php
$content = ob_get_clean();

$title = $campaign["name"] . " - STM";

require __DIR__ . "/../../layouts/admin.php";


?>
