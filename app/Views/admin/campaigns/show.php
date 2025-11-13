<?php
/**
 * Vue : Détails d'une campagne
 * 
 * Affiche toutes les informations d'une campagne :
 * - Statistiques rapides (clients, promotions, commandes)
 * - Informations de base
 * - Type de commande et livraison
 * - Attribution clients
 * - Contenu multilingue
 * - Actions disponibles
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
                <div class="flex items-center gap-3 mb-2">
                    <h1 class="text-3xl font-bold text-gray-900">
                        <?= htmlspecialchars($campaign['name']) ?>
                    </h1>
                    <?php
                    // Badge de statut
                    $now = new DateTime();
                    $start = new DateTime($campaign['start_date']);
                    $end = new DateTime($campaign['end_date']);
                    
                    if ($now < $start) {
                        $statusClass = 'bg-blue-100 text-blue-800';
                        $statusText = 'À venir';
                    } elseif ($now > $end) {
                        $statusClass = 'bg-gray-100 text-gray-800';
                        $statusText = 'Terminée';
                    } else {
                        $statusClass = 'bg-green-100 text-green-800';
                        $statusText = 'Active';
                    }
                    ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= $statusClass ?>">
                        <?= $statusText ?>
                    </span>
                </div>
                <p class="text-sm text-gray-600">
                    Campagne <?= $campaign['country'] === 'BE' ? '🇧🇪 Belgique' : '🇱🇺 Luxembourg' ?> • 
                    Du <?= date('d/m/Y', strtotime($campaign['start_date'])) ?> au <?= date('d/m/Y', strtotime($campaign['end_date'])) ?>
                </p>
            </div>
            <a href="/stm/admin/campaigns" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Retour à la liste
            </a>
        </div>
    </div>

    <!-- SECTION 1 : Statistiques rapides -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Carte Clients -->
        <div class="bg-white overflow-hidden shadow-sm ring-1 ring-gray-900/5 rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="p-3 bg-blue-100 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Clients
                            </dt>
                            <dd class="text-2xl font-semibold text-gray-900">
                                <?php
                                if ($campaign['customer_assignment_mode'] === 'manual') {
                                    echo number_format($customerCount ?? 0);
                                } else {
                                    echo '<span class="text-blue-600">∞</span>';
                                }
                                ?>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Carte Promotions -->
        <div class="bg-white overflow-hidden shadow-sm ring-1 ring-gray-900/5 rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="p-3 bg-green-100 rounded-lg">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Promotions
                            </dt>
                            <dd class="text-2xl font-semibold text-gray-900">
                                <?= number_format($promotionCount ?? 0) ?>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Carte Commandes -->
        <div class="bg-white overflow-hidden shadow-sm ring-1 ring-gray-900/5 rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="p-3 bg-purple-100 rounded-lg">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Commandes
                            </dt>
                            <dd class="text-2xl font-semibold text-gray-900">
                                0
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Carte Montant total -->
        <div class="bg-white overflow-hidden shadow-sm ring-1 ring-gray-900/5 rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="p-3 bg-amber-100 rounded-lg">
                            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Montant total
                            </dt>
                            <dd class="text-2xl font-semibold text-gray-900">
                                0 €
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Colonne principale (2/3) -->
        <div class="lg:col-span-2 space-y-8">
            <!-- SECTION 2 : Informations de base -->
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg">
                <div class="px-6 py-5 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">
                        📋 Informations de base
                    </h2>
                </div>
                <div class="px-6 py-6">
                    <dl class="grid grid-cols-1 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Nom de la campagne</dt>
                            <dd class="mt-1 text-base text-gray-900"><?= htmlspecialchars($campaign['name']) ?></dd>
                        </div>
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Pays</dt>
                                <dd class="mt-1 text-base text-gray-900">
                                    <?= $campaign['country'] === 'BE' ? '🇧🇪 Belgique' : '🇱🇺 Luxembourg' ?>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Statut</dt>
                                <dd class="mt-1">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= $statusClass ?>">
                                        <?= $statusText ?>
                                    </span>
                                </dd>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Date de début</dt>
                                <dd class="mt-1 text-base text-gray-900">
                                    <?= date('d/m/Y à H:i', strtotime($campaign['start_date'])) ?>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Date de fin</dt>
                                <dd class="mt-1 text-base text-gray-900">
                                    <?= date('d/m/Y à H:i', strtotime($campaign['end_date'])) ?>
                                </dd>
                            </div>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- SECTION 3 : Type & Livraison -->
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg">
                <div class="px-6 py-5 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">
                        🚚 Type de commande & Livraison
                    </h2>
                </div>
                <div class="px-6 py-6">
                    <dl class="grid grid-cols-1 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 mb-2">Type de commande</dt>
                            <dd>
                                <?php if ($campaign['order_type'] === 'W'): ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                        ✅ Commande normale (W)
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
                                        🎯 Prospection (V)
                                    </span>
                                <?php endif; ?>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 mb-2">Livraison</dt>
                            <dd>
                                <?php if ($campaign['deferred_delivery'] && $campaign['delivery_date']): ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-800">
                                        📅 Différée au <?= date('d/m/Y', strtotime($campaign['delivery_date'])) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                        ⚡ Immédiate
                                    </span>
                                <?php endif; ?>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- SECTION 4 : Attribution clients -->
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg">
                <div class="px-6 py-5 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">
                        👥 Attribution des clients
                    </h2>
                </div>
                <div class="px-6 py-6">
                    <dl class="space-y-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 mb-2">Mode d'attribution</dt>
                            <dd>
                                <?php if ($campaign['customer_assignment_mode'] === 'automatic'): ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                        🌍 Automatique (Tous les clients)
                                    </span>
                                    <p class="mt-2 text-sm text-gray-600">
                                        Tous les clients <?= $campaign['country'] === 'BE' ? 'belges' : 'luxembourgeois' ?> peuvent accéder à cette campagne
                                    </p>
                                <?php elseif ($campaign['customer_assignment_mode'] === 'manual'): ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800">
                                        📝 Manuel (Liste restreinte)
                                    </span>
                                    <div class="mt-4 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                                        <p class="text-sm font-medium text-gray-700 mb-2">
                                            Liste des clients autorisés (<?= number_format($customerCount ?? 0) ?> clients) :
                                        </p>
                                        <?php if (!empty($campaign['customer_list'])): ?>
                                            <pre class="text-sm text-gray-900 font-mono whitespace-pre-wrap"><?= htmlspecialchars($campaign['customer_list']) ?></pre>
                                        <?php else: ?>
                                            <p class="text-sm text-gray-500 italic">Aucun client défini</p>
                                        <?php endif; ?>
                                    </div>
                                <?php else: // protected ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-800">
                                        🔒 Protégé (Avec mot de passe)
                                    </span>
                                    <div class="mt-4 p-4 bg-gray-50 border border-gray-200 rounded-lg" x-data="{ showPassword: false }">
                                        <p class="text-sm font-medium text-gray-700 mb-2">
                                            Mot de passe d'accès :
                                        </p>
                                        <div class="flex items-center gap-2">
                                            <code class="flex-1 px-3 py-2 bg-white border border-gray-300 rounded text-sm font-mono"
                                                  x-text="showPassword ? '<?= htmlspecialchars($campaign['order_password']) ?>' : '••••••••'">
                                            </code>
                                            <button type="button"
                                                    @click="showPassword = !showPassword"
                                                    class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">
                                                <span x-show="!showPassword">👁️ Afficher</span>
                                                <span x-show="showPassword">🙈 Masquer</span>
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- SECTION 5 : Contenu multilingue -->
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg">
                <div class="px-6 py-5 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">
                        🌐 Contenu multilingue
                    </h2>
                </div>
                <div class="px-6 py-6 space-y-6">
                    <!-- Description FR -->
                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-2">🇫🇷 Description française</dt>
                        <dd class="text-sm text-gray-900">
                            <?php if (!empty($campaign['description_fr'])): ?>
                                <div class="prose prose-sm max-w-none">
                                    <?= nl2br(htmlspecialchars($campaign['description_fr'])) ?>
                                </div>
                            <?php else: ?>
                                <p class="text-gray-400 italic">Aucune description en français</p>
                            <?php endif; ?>
                        </dd>
                    </div>

                    <!-- Description NL -->
                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-2">🇳🇱 Description néerlandaise</dt>
                        <dd class="text-sm text-gray-900">
                            <?php if (!empty($campaign['description_nl'])): ?>
                                <div class="prose prose-sm max-w-none">
                                    <?= nl2br(htmlspecialchars($campaign['description_nl'])) ?>
                                </div>
                            <?php else: ?>
                                <p class="text-gray-400 italic">Aucune description en néerlandais</p>
                            <?php endif; ?>
                        </dd>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne latérale (1/3) -->
        <div class="space-y-8">
            <!-- SECTION 6 : Actions -->
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg">
                <div class="px-6 py-5 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">
                        ⚡ Actions rapides
                    </h2>
                </div>
                <div class="px-6 py-6 space-y-3">
                    <!-- Modifier -->
                    <a href="/stm/admin/campaigns/<?= $campaign['id'] ?>/edit" 
                       class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Modifier la campagne
                    </a>

                    <!-- Gérer les promotions -->
                    <a href="/stm/admin/promotions?campaign_id=<?= $campaign['id'] ?>" 
                       class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        Gérer les promotions
                    </a>

                    <!-- Supprimer -->
                    <button type="button"
                            onclick="if(confirm('Êtes-vous sûr de vouloir supprimer cette campagne ?')) { document.getElementById('delete-form').submit(); }"
                            class="w-full inline-flex items-center justify-center px-4 py-2 border border-red-300 rounded-lg shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Supprimer la campagne
                    </button>

                    <!-- Formulaire de suppression caché -->
                    <form id="delete-form" 
                          method="POST" 
                          action="/stm/admin/campaigns/<?= $campaign['id'] ?>" 
                          class="hidden">
                        <input type="hidden" name="_method" value="DELETE">
                        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
                    </form>
                </div>
            </div>

            <!-- URL publique -->
            <div class="bg-gradient-to-br from-indigo-50 to-purple-50 shadow-sm ring-1 ring-indigo-900/10 rounded-lg">
                <div class="px-6 py-5 border-b border-indigo-200">
                    <h2 class="text-lg font-semibold text-indigo-900">
                        🔗 Accès client
                    </h2>
                </div>
                <div class="px-6 py-6">
                    <p class="text-sm text-indigo-700 mb-3">
                        URL publique de la campagne :
                    </p>
                    <div class="flex items-center gap-2">
                        <input type="text" 
                               readonly 
                               value="<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/stm/c/<?= $campaign['uuid'] ?>"
                               id="campaign-url"
                               class="flex-1 px-3 py-2 bg-white border border-indigo-300 rounded text-sm font-mono text-indigo-900">
                        <button type="button"
                                onclick="navigator.clipboard.writeText(document.getElementById('campaign-url').value); this.innerHTML='✅'; setTimeout(() => this.innerHTML='📋', 2000)"
                                class="px-3 py-2 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
                            📋
                        </button>
                    </div>
                    <p class="mt-3 text-xs text-indigo-600">
                        💡 Partagez cette URL avec vos clients pour qu'ils accèdent à la campagne
                    </p>
                </div>
            </div>

            <!-- Informations techniques -->
            <div class="bg-gray-50 shadow-sm ring-1 ring-gray-900/5 rounded-lg">
                <div class="px-6 py-5 border-b border-gray-200">
                    <h2 class="text-sm font-semibold text-gray-700">
                        ℹ️ Informations techniques
                    </h2>
                </div>
                <div class="px-6 py-4 space-y-2 text-xs text-gray-600">
                    <div class="flex justify-between">
                        <span>ID campagne :</span>
                        <span class="font-mono"><?= $campaign['id'] ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>UUID :</span>
                        <span class="font-mono"><?= substr($campaign['uuid'], 0, 8) ?>...</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Créée le :</span>
                        <span><?= date('d/m/Y', strtotime($campaign['created_at'])) ?></span>
                    </div>
                    <?php if ($campaign['updated_at']): ?>
                    <div class="flex justify-between">
                        <span>Modifiée le :</span>
                        <span><?= date('d/m/Y', strtotime($campaign['updated_at'])) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$title = htmlspecialchars($campaign['name']) . ' - Détails - STM';
require __DIR__ . '/../../layouts/admin.php';
?>