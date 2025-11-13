<?php
/**
 * Vue : Détails de la campagne
 * 
 * @package STM/Views/Admin/Campaigns
 * @version 3.0.0
 * @modified 13/11/2025 - Ajout affichage type, delivery_date, quotas, attribution
 */

ob_start();
?>

<!-- En-tête avec actions -->
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($campaign['name']) ?></h2>
            <p class="mt-1 text-sm text-gray-500">
                <?= $campaign['country'] === 'BE' ? '🇧🇪 Belgique' : '🇱🇺 Luxembourg' ?> • 
                <?= $campaign['type'] === 'V' ? '🎯 Prospection' : '📦 Normal' ?> •
                Du <?= date('d/m/Y', strtotime($campaign['start_date'])) ?> au <?= date('d/m/Y', strtotime($campaign['end_date'])) ?>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium
                <?= $campaign['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                <?= $campaign['is_active'] ? '✅ Active' : '⏸️ Inactive' ?>
            </span>
            <a href="/stm/admin/campaigns/edit/<?= $campaign['id'] ?>" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                ✏️ Modifier
            </a>
        </div>
    </div>
</div>

<!-- Statistiques rapides -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
    <!-- Clients -->
    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Clients autorisés</dt>
                        <dd class="text-lg font-semibold text-gray-900"><?= $stats['customers'] ?? 0 ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Promotions -->
    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Promotions</dt>
                        <dd class="text-lg font-semibold text-gray-900"><?= $stats['promotions'] ?? 0 ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Commandes -->
    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Commandes</dt>
                        <dd class="text-lg font-semibold text-gray-900"><?= $stats['orders'] ?? 0 ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Montant total -->
    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 7.756a4.5 4.5 0 100 8.488M7.5 10.5h5.25m-5.25 3h5.25M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Montant total</dt>
                        <dd class="text-lg font-semibold text-gray-900"><?= number_format($stats['amount'] ?? 0, 2, ',', ' ') ?> €</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Contenu en colonnes -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Colonne principale (2/3) -->
    <div class="lg:col-span-2 space-y-6">
        
        <!-- Paramètres de la campagne -->
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Paramètres de la campagne</h3>
            
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <!-- Type -->
                <div>
                    <dt class="text-sm font-medium text-gray-500">Type de commande</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <?= $campaign['type'] === 'V' ? '🎯 Prospection à livrer' : '📦 Commande normale' ?>
                    </dd>
                </div>

                <!-- Date livraison -->
                <div>
                    <dt class="text-sm font-medium text-gray-500">Livraison</dt>
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

        <!-- Quotas de commande -->
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">📦 Quotas de commande</h3>
            
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <!-- Quota global -->
                <div>
                    <dt class="text-sm font-medium text-gray-500">Quota global</dt>
                    <dd class="mt-1">
                        <?php if (!is_null($campaign['global_quota'])): ?>
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium bg-purple-100 text-purple-800">
                                🌍 <?= number_format($campaign['global_quota'], 0, ',', ' ') ?> unités max
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium bg-gray-100 text-gray-800">
                                ∞ Illimité
                            </span>
                        <?php endif; ?>
                    </dd>
                </div>

                <!-- Quota par client -->
                <div>
                    <dt class="text-sm font-medium text-gray-500">Quota par client</dt>
                    <dd class="mt-1">
                        <?php if (!is_null($campaign['quota_per_customer'])): ?>
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium bg-blue-100 text-blue-800">
                                👤 <?= number_format($campaign['quota_per_customer'], 0, ',', ' ') ?> unités max
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium bg-gray-100 text-gray-800">
                                ∞ Illimité
                            </span>
                        <?php endif; ?>
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Attribution des clients -->
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">👥 Attribution des clients</h3>
            
            <div class="space-y-4">
                <!-- Mode d'attribution -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mode d'accès</label>
                    <?php 
                    $accessType = $campaign['customer_access_type'] ?? 'manual';
                    $accessLabels = [
                        'manual' => ['icon' => '📝', 'label' => 'Liste manuelle', 'color' => 'bg-blue-100 text-blue-800'],
                        'dynamic' => ['icon' => '🔄', 'label' => 'Dynamique (tous les clients)', 'color' => 'bg-green-100 text-green-800'],
                        'protected' => ['icon' => '🔒', 'label' => 'Protégé par mot de passe', 'color' => 'bg-purple-100 text-purple-800']
                    ];
                    $current = $accessLabels[$accessType] ?? $accessLabels['manual'];
                    ?>
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium <?= $current['color'] ?>">
                        <?= $current['icon'] ?> <?= $current['label'] ?>
                    </span>
                </div>

                <!-- Liste clients (si manuel) -->
                <?php if ($accessType === 'manual'): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Clients autorisés</label>
                    <?php if (!empty($campaign['customer_list'])): ?>
                        <?php 
                        $customerNumbers = array_filter(array_map('trim', explode("\n", $campaign['customer_list'])));
                        $count = count($customerNumbers);
                        ?>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-900"><?= $count ?> client<?= $count > 1 ? 's' : '' ?></span>
                                <button type="button" onclick="toggleCustomerList()" class="text-xs text-purple-600 hover:text-purple-700">
                                    Voir la liste
                                </button>
                            </div>
                            <div id="customerListDetail" style="display: none;" class="mt-3 max-h-60 overflow-y-auto">
                                <div class="bg-white rounded border border-gray-200 p-3 font-mono text-xs">
                                    <?php foreach ($customerNumbers as $number): ?>
                                        <div class="py-1 border-b border-gray-100 last:border-0"><?= htmlspecialchars($number) ?></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-sm text-gray-500 italic">Aucun client défini</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Mot de passe (si protégé) -->
                <?php if ($accessType === 'protected'): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mot de passe</label>
                    <?php if (!empty($campaign['order_password'])): ?>
                        <div class="bg-gray-50 rounded-lg p-4 flex items-center justify-between">
                            <span class="font-mono text-sm text-gray-900"><?= htmlspecialchars($campaign['order_password']) ?></span>
                            <button type="button" onclick="copyPassword()" class="text-xs text-purple-600 hover:text-purple-700">
                                📋 Copier
                            </button>
                        </div>
                    <?php else: ?>
                        <p class="text-sm text-gray-500 italic">Aucun mot de passe défini</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Info dynamique -->
                <?php if ($accessType === 'dynamic'): ?>
                <div class="bg-blue-50 rounded-lg p-4">
                    <p class="text-sm text-blue-700">
                        ℹ️ Les clients sont lus en temps réel depuis la base de données externe (<?= $campaign['country'] ?>_CLL)
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Contenu multilingue -->
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Contenu public</h3>
            
            <div class="space-y-6">
                <!-- Version française -->
                <?php if (!empty($campaign['title_fr']) || !empty($campaign['description_fr'])): ?>
                <div class="border-l-4 border-blue-500 pl-4">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">🇫🇷 Version française</h4>
                    <?php if (!empty($campaign['title_fr'])): ?>
                        <h5 class="text-base font-semibold text-gray-900 mb-2"><?= htmlspecialchars($campaign['title_fr']) ?></h5>
                    <?php endif; ?>
                    <?php if (!empty($campaign['description_fr'])): ?>
                        <p class="text-sm text-gray-600"><?= nl2br(htmlspecialchars($campaign['description_fr'])) ?></p>
                    <?php endif; ?>
                    <?php if (empty($campaign['title_fr']) && empty($campaign['description_fr'])): ?>
                        <p class="text-sm text-gray-400 italic">Aucun contenu français défini</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Version néerlandaise -->
                <?php if (!empty($campaign['title_nl']) || !empty($campaign['description_nl'])): ?>
                <div class="border-l-4 border-orange-500 pl-4">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">🇳🇱 Version néerlandaise</h4>
                    <?php if (!empty($campaign['title_nl'])): ?>
                        <h5 class="text-base font-semibold text-gray-900 mb-2"><?= htmlspecialchars($campaign['title_nl']) ?></h5>
                    <?php endif; ?>
                    <?php if (!empty($campaign['description_nl'])): ?>
                        <p class="text-sm text-gray-600"><?= nl2br(htmlspecialchars($campaign['description_nl'])) ?></p>
                    <?php endif; ?>
                    <?php if (empty($campaign['title_nl']) && empty($campaign['description_nl'])): ?>
                        <p class="text-sm text-gray-400 italic">Geen Nederlandse inhoud gedefinieerd</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if (empty($campaign['title_fr']) && empty($campaign['description_fr']) && empty($campaign['title_nl']) && empty($campaign['description_nl'])): ?>
                    <p class="text-sm text-gray-400 italic">Aucun contenu public défini pour cette campagne</p>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Colonne latérale (1/3) -->
    <div class="space-y-6">
        
        <!-- URL publique -->
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">🌐 URL publique</h3>
            
            <div class="space-y-3">
                <div class="bg-gray-50 rounded-lg p-3 break-all font-mono text-xs">
                    <?= htmlspecialchars($campaign['public_url'] ?? '#') ?>
                </div>
                <button type="button" onclick="copyUrl()" 
                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    📋 Copier l'URL
                </button>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Actions rapides</h3>
            
            <div class="space-y-2">
                <a href="/stm/admin/campaigns/edit/<?= $campaign['id'] ?>" 
                   class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    ✏️ Modifier
                </a>
                <a href="/stm/admin/campaigns/<?= $campaign['id'] ?>/products" 
                   class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    🏷️ Gérer les promotions
                </a>
                <a href="/stm/admin/campaigns/<?= $campaign['id'] ?>/orders" 
                   class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    🛒 Voir les commandes
                </a>
                <a href="/stm/admin/campaigns" 
                   class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    ← Retour à la liste
                </a>
            </div>
        </div>

        <!-- Informations système -->
        <div class="bg-gray-50 rounded-lg p-4">
            <h4 class="text-xs font-medium text-gray-500 uppercase mb-3">Informations</h4>
            <dl class="space-y-2 text-xs">
                <div>
                    <dt class="text-gray-500">Créée le</dt>
                    <dd class="text-gray-900 font-medium"><?= date('d/m/Y à H:i', strtotime($campaign['created_at'])) ?></dd>
                </div>
                <div>
                    <dt class="text-gray-500">Modifiée le</dt>
                    <dd class="text-gray-900 font-medium"><?= date('d/m/Y à H:i', strtotime($campaign['updated_at'])) ?></dd>
                </div>
            </dl>
        </div>

    </div>
</div>

<script>
function toggleCustomerList() {
    const detail = document.getElementById('customerListDetail');
    detail.style.display = detail.style.display === 'none' ? 'block' : 'none';
}

function copyUrl() {
    const url = "<?= htmlspecialchars($campaign['public_url'] ?? '#') ?>";
    navigator.clipboard.writeText(url).then(() => {
        alert('✅ URL copiée dans le presse-papiers !');
    });
}

function copyPassword() {
    const password = "<?= htmlspecialchars($campaign['order_password'] ?? '') ?>";
    navigator.clipboard.writeText(password).then(() => {
        alert('✅ Mot de passe copié !');
    });
}
</script>

<?php
$content = ob_get_clean();
$title = $campaign['name'];

require __DIR__ . '/../../layouts/admin.php';