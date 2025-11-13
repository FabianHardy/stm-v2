<?php
/**
 * Vue : Détails d'une campagne
 * 
 * @package STM/Views/Admin/Campaigns
 * @version 2.2.0
 * @modified 13/11/2025 - Ajout affichage type, livraison, quotas, attribution
 */

$pageTitle = 'Détails de la campagne';
ob_start();
?>

<!-- Breadcrumb -->
<nav class="flex mb-6" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-3">
        <li class="inline-flex items-center">
            <a href="/stm/admin/dashboard" class="text-gray-700 hover:text-purple-600">
                Dashboard
            </a>
        </li>
        <li>
            <div class="flex items-center">
                <svg class="w-3 h-3 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                </svg>
                <a href="/stm/admin/campaigns" class="text-gray-700 hover:text-purple-600">
                    Campagnes
                </a>
            </div>
        </li>
        <li aria-current="page">
            <div class="flex items-center">
                <svg class="w-3 h-3 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-gray-500">
                    <?= htmlspecialchars($campaign['name']) ?>
                </span>
            </div>
        </li>
    </ol>
</nav>

<!-- En-tête avec actions -->
<div class="sm:flex sm:items-center sm:justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">
            <?= htmlspecialchars($campaign['name']) ?>
        </h2>
        <div class="mt-1 flex items-center gap-x-2">
            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium 
                <?= $campaign['country'] === 'BE' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800' ?>">
                <?= htmlspecialchars($campaign['country']) ?>
            </span>
            <?php if ($campaign['is_active']): ?>
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
        </div>
    </div>
    <div class="mt-4 sm:mt-0 flex gap-x-2">
        <a href="/stm/admin/campaigns/<?= $campaign['id'] ?>/edit" 
           class="inline-flex items-center gap-x-2 rounded-md bg-white px-4 py-2.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
            <svg class="-ml-0.5 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
            </svg>
            Modifier
        </a>
        <form method="POST" 
              action="/stm/admin/campaigns/<?= $campaign['id'] ?>/delete" 
              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette campagne ?')"
              class="inline">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <button type="submit" 
                    class="inline-flex items-center gap-x-2 rounded-md bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700">
                <svg class="-ml-0.5 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
                Supprimer
            </button>
        </form>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- Colonne principale -->
    <div class="lg:col-span-2 space-y-6">
        
        <!-- URL Publique (mise en évidence) -->
        <div class="bg-gradient-to-r from-purple-50 to-purple-100 rounded-lg shadow-sm border-2 border-purple-200">
            <div class="p-6">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-purple-900 flex items-center gap-x-2">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                            </svg>
                            URL Publique de la Campagne
                        </h3>
                        <p class="mt-2 text-sm text-purple-700">
                            Partagez cette URL avec vos clients pour qu'ils accèdent à la campagne
                        </p>
                        
                        <?php 
                        $baseUrl = rtrim($_ENV['APP_URL'] ?? 'https://actions.trendyfoods.com/stm', '/');
                        $publicUrl = $baseUrl . '/c/' . $campaign['uuid'];
                        ?>
                        
                        <div class="mt-4 flex items-center gap-x-2">
                            <div class="flex-1 bg-white rounded-lg p-3 border-2 border-purple-300">
                                <code class="text-sm text-gray-900 break-all">
                                    <?= htmlspecialchars($publicUrl) ?>
                                </code>
                            </div>
                            <button 
                                onclick="copyToClipboard('<?= htmlspecialchars($publicUrl) ?>', this)"
                                class="inline-flex items-center gap-x-2 rounded-lg bg-purple-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 transition"
                                title="Copier l'URL"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" />
                                </svg>
                                Copier
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informations de base -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations de base</h3>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Pays</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <?= $campaign['country'] === 'BE' ? '🇧🇪 Belgique' : '🇱🇺 Luxembourg' ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Statut</dt>
                    <dd class="mt-1">
                        <?php if ($campaign['is_active']): ?>
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                Active
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">
                                Inactive
                            </span>
                        <?php endif; ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Date de début</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <?= date('d/m/Y', strtotime($campaign['start_date'])) ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Date de fin</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <?= date('d/m/Y', strtotime($campaign['end_date'])) ?>
                    </dd>
                </div>
            </dl>
        </div>

        <!-- NOUVEAU : Type et Livraison -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">📦 Type et Livraison</h3>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Type de commande</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <?php if (($campaign['type'] ?? 'W') === 'V'): ?>
                            <span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800">
                                🎯 Prospection à livrer
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">
                                📦 Commande normale
                            </span>
                        <?php endif; ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Date de livraison</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <?php if (!empty($campaign['delivery_date'])): ?>
                            📅 Différée au <?= date('d/m/Y', strtotime($campaign['delivery_date'])) ?>
                        <?php else: ?>
                            ⚡ Immédiate
                        <?php endif; ?>
                    </dd>
                </div>
            </dl>
        </div>

        <!-- NOUVEAU : Quotas de commande -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">🔢 Quotas de commande (quantités)</h3>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Quota global</dt>
                    <dd class="mt-1">
                        <?php if (!empty($campaign['global_quota'])): ?>
                            <span class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-800">
                                🌍 <?= number_format($campaign['global_quota'], 0, ',', ' ') ?> unités max
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">
                                ∞ Illimité
                            </span>
                        <?php endif; ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Quota par client</dt>
                    <dd class="mt-1">
                        <?php if (!empty($campaign['quota_per_customer'])): ?>
                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">
                                👤 <?= number_format($campaign['quota_per_customer'], 0, ',', ' ') ?> unités max
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">
                                ∞ Illimité
                            </span>
                        <?php endif; ?>
                    </dd>
                </div>
            </dl>
        </div>

        <!-- NOUVEAU : Attribution des clients -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">👥 Attribution des clients</h3>
            
            <div class="mb-4">
                <dt class="text-sm font-medium text-gray-500 mb-2">Mode d'accès</dt>
                <dd>
                    <?php 
                    $accessType = $campaign['customer_access_type'] ?? 'manual';
                    if ($accessType === 'manual'): ?>
                        <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-sm font-medium text-blue-800">
                            📝 Liste manuelle
                        </span>
                    <?php elseif ($accessType === 'dynamic'): ?>
                        <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800">
                            🔄 Dynamique (tous les clients)
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center rounded-full bg-purple-100 px-3 py-1 text-sm font-medium text-purple-800">
                            🔒 Protégé par mot de passe
                        </span>
                    <?php endif; ?>
                </dd>
            </div>

            <?php if ($accessType === 'manual' && !empty($campaign['customer_list'])): ?>
                <div class="mt-4">
                    <dt class="text-sm font-medium text-gray-500 mb-2">Clients autorisés</dt>
                    <dd>
                        <?php 
                        $customerNumbers = array_filter(array_map('trim', explode("\n", $campaign['customer_list'])));
                        $count = count($customerNumbers);
                        ?>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-sm text-gray-900 mb-2">
                                <?= $count ?> client<?= $count > 1 ? 's' : '' ?> autorisé<?= $count > 1 ? 's' : '' ?>
                            </div>
                            <details>
                                <summary class="cursor-pointer text-sm text-indigo-600 hover:text-indigo-700">Voir la liste</summary>
                                <div class="mt-3 max-h-48 overflow-y-auto bg-white rounded border p-3">
                                    <ul class="space-y-1 font-mono text-xs">
                                        <?php foreach ($customerNumbers as $number): ?>
                                            <li class="py-1 border-b border-gray-100 last:border-0">
                                                <?= htmlspecialchars($number) ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </details>
                        </div>
                    </dd>
                </div>
            <?php endif; ?>

            <?php if ($accessType === 'protected' && !empty($campaign['order_password'])): ?>
                <div class="mt-4">
                    <dt class="text-sm font-medium text-gray-500 mb-2">Mot de passe de la campagne</dt>
                    <dd class="bg-gray-50 rounded-lg p-4">
                        <code class="text-sm text-gray-900 font-mono">
                            <?= htmlspecialchars($campaign['order_password']) ?>
                        </code>
                    </dd>
                </div>
            <?php endif; ?>

            <?php if ($accessType === 'dynamic'): ?>
                <div class="mt-4 bg-blue-50 rounded-lg p-4">
                    <p class="text-sm text-blue-700">
                        ℹ️ Les clients sont lus en temps réel depuis la base de données externe (<?= $campaign['country'] ?>_CLL)
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Contenu multilingue -->
        <?php if (!empty($campaign['title_fr']) || !empty($campaign['description_fr']) || !empty($campaign['title_nl']) || !empty($campaign['description_nl'])): ?>
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Contenu multilingue</h3>
            
            <?php if (!empty($campaign['title_fr']) || !empty($campaign['description_fr'])): ?>
            <div class="mb-6">
                <h4 class="text-sm font-medium text-gray-700 mb-2">🇫🇷 Version française</h4>
                <?php if (!empty($campaign['title_fr'])): ?>
                    <p class="text-base font-semibold text-gray-900 mb-1">
                        <?= htmlspecialchars($campaign['title_fr']) ?>
                    </p>
                <?php endif; ?>
                <?php if (!empty($campaign['description_fr'])): ?>
                    <p class="text-sm text-gray-600">
                        <?= nl2br(htmlspecialchars($campaign['description_fr'])) ?>
                    </p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($campaign['title_nl']) || !empty($campaign['description_nl'])): ?>
            <div>
                <h4 class="text-sm font-medium text-gray-700 mb-2">🇳🇱 Version néerlandaise</h4>
                <?php if (!empty($campaign['title_nl'])): ?>
                    <p class="text-base font-semibold text-gray-900 mb-1">
                        <?= htmlspecialchars($campaign['title_nl']) ?>
                    </p>
                <?php endif; ?>
                <?php if (!empty($campaign['description_nl'])): ?>
                    <p class="text-sm text-gray-600">
                        <?= nl2br(htmlspecialchars($campaign['description_nl'])) ?>
                    </p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>

    <!-- Colonne latérale -->
    <div class="space-y-6">
        
        <!-- Informations système -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations système</h3>
            <dl class="space-y-3">
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase">UUID</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-mono break-all">
                        <?= htmlspecialchars($campaign['uuid']) ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase">Créée le</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <?= date('d/m/Y à H:i', strtotime($campaign['created_at'])) ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase">Modifiée le</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <?= date('d/m/Y à H:i', strtotime($campaign['updated_at'])) ?>
                    </dd>
                </div>
            </dl>
        </div>

    </div>

</div>

<script>
function copyToClipboard(text, button) {
    navigator.clipboard.writeText(text).then(function() {
        const originalText = button.innerHTML;
        button.innerHTML = '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg> Copié !';
        setTimeout(function() {
            button.innerHTML = originalText;
        }, 2000);
    });
}
</script>

<?php
$content = ob_get_clean();
$title = 'Détails de la campagne - ' . $campaign['name'];
require __DIR__ . '/../../layouts/admin.php';
?>