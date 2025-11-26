<?php
/**
 * Vue : Statistiques - Par commercial
 * 
 * Stats par représentant avec détail clients
 * 
 * @package STM
 * @created 2025/11/25
 */

ob_start();
?>

<!-- En-tête -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Statistiques - Par commercial</h1>
    <p class="text-gray-600 mt-1">Performance des représentants et suivi clients</p>
</div>

<!-- Filtres -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <form method="GET" action="/stm/admin/stats/sales" class="flex flex-wrap gap-4 items-end">
        
        <!-- Pays -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Pays</label>
            <select name="country" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <option value="">Tous</option>
                <option value="BE" <?= ($country ?? '') === 'BE' ? 'selected' : '' ?>>Belgique</option>
                <option value="LU" <?= ($country ?? '') === 'LU' ? 'selected' : '' ?>>Luxembourg</option>
            </select>
        </div>
        
        <!-- Campagne -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Campagne</label>
            <select name="campaign_id" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <option value="">Toutes</option>
                <?php foreach ($campaigns as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ($campaignId ?? null) == $c['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition">
            <i class="fas fa-filter mr-2"></i>Filtrer
        </button>
        
        <!-- Export -->
        <form method="POST" action="/stm/admin/stats/export" class="inline ml-auto">
            <input type="hidden" name="type" value="reps">
            <input type="hidden" name="campaign_id" value="<?= $campaignId ?? '' ?>">
            <input type="hidden" name="format" value="csv">
            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-download mr-2"></i>Export CSV
            </button>
        </form>
    </form>
</div>

<?php if ($repDetail): ?>

<!-- Détail d'un représentant -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
                <i class="fas fa-user text-indigo-600 text-xl"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($repDetail['name']) ?></h2>
                <p class="text-sm text-gray-500">
                    <?= htmlspecialchars($repDetail['cluster']) ?> • <?= $repDetail['country'] ?>
                    <?php if ($repDetail['email']): ?>
                    • <a href="mailto:<?= htmlspecialchars($repDetail['email']) ?>" class="text-indigo-600"><?= htmlspecialchars($repDetail['email']) ?></a>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        
        <a href="/stm/admin/stats/sales<?= $country ? '?country=' . $country : '' ?><?= $campaignId ? ($country ? '&' : '?') . 'campaign_id=' . $campaignId : '' ?>" 
           class="text-gray-600 hover:text-gray-800">
            <i class="fas fa-times text-xl"></i>
        </a>
    </div>
    
    <!-- Stats du rep -->
    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-gray-50 rounded-lg p-4 text-center">
            <p class="text-2xl font-bold text-gray-900"><?= $repDetail['total_clients'] ?></p>
            <p class="text-xs text-gray-500">Clients assignés</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-4 text-center">
            <p class="text-2xl font-bold text-green-600"><?= $repDetail['stats']['customers_ordered'] ?></p>
            <p class="text-xs text-gray-500">Ont commandé</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-4 text-center">
            <?php 
            $convRate = $repDetail['total_clients'] > 0 
                ? round(($repDetail['stats']['customers_ordered'] / $repDetail['total_clients']) * 100, 1) 
                : 0;
            ?>
            <p class="text-2xl font-bold text-indigo-600"><?= $convRate ?>%</p>
            <p class="text-xs text-gray-500">Taux conversion</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-4 text-center">
            <p class="text-2xl font-bold text-orange-600"><?= number_format($repDetail['stats']['total_quantity'], 0, ',', ' ') ?></p>
            <p class="text-xs text-gray-500">Promos vendues</p>
        </div>
    </div>
    
    <!-- Liste des clients du rep -->
    <h3 class="font-semibold text-gray-900 mb-3">Clients (<?= count($repClients) ?>)</h3>
    
    <div class="overflow-x-auto max-h-96">
        <table class="min-w-full">
            <thead class="sticky top-0 bg-white">
                <tr class="text-left text-xs text-gray-500 uppercase border-b">
                    <th class="pb-2 pr-4">Client</th>
                    <th class="pb-2 pr-4">Ville</th>
                    <th class="pb-2 pr-4 text-center">Statut</th>
                    <th class="pb-2 pr-4 text-right">Commandes</th>
                    <th class="pb-2 text-right">Quantité</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                <?php foreach ($repClients as $client): ?>
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="py-2 pr-4">
                        <p class="font-medium"><?= htmlspecialchars($client['company_name'] ?? '-') ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars($client['customer_number']) ?></p>
                    </td>
                    <td class="py-2 pr-4 text-gray-600"><?= htmlspecialchars($client['city'] ?? '-') ?></td>
                    <td class="py-2 pr-4 text-center">
                        <?php if ($client['has_ordered']): ?>
                        <span class="inline-flex items-center px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">
                            <i class="fas fa-check mr-1"></i>Commandé
                        </span>
                        <?php else: ?>
                        <span class="inline-flex items-center px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">
                            <i class="fas fa-times mr-1"></i>Pas commandé
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="py-2 pr-4 text-right"><?= $client['orders_count'] ?></td>
                    <td class="py-2 text-right font-medium"><?= number_format($client['total_quantity'], 0, ',', ' ') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php else: ?>

<!-- Liste des représentants -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr class="text-left text-xs text-gray-500 uppercase">
                    <th class="px-6 py-3">Représentant</th>
                    <th class="px-6 py-3">Cluster</th>
                    <th class="px-6 py-3">Pays</th>
                    <th class="px-6 py-3 text-right">Clients</th>
                    <th class="px-6 py-3 text-right">Ont commandé</th>
                    <th class="px-6 py-3 text-right">Taux conv.</th>
                    <th class="px-6 py-3 text-right">Quantité</th>
                    <th class="px-6 py-3 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (empty($reps)): ?>
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-users text-4xl text-gray-300 mb-3"></i>
                        <p>Aucun représentant trouvé</p>
                        <p class="text-sm">Vérifiez la connexion à la base externe</p>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($reps as $rep): ?>
                <?php 
                $convRate = $rep['total_clients'] > 0 
                    ? round(($rep['stats']['customers_ordered'] / $rep['total_clients']) * 100, 1) 
                    : 0;
                $convClass = $convRate >= 50 ? 'text-green-600' : ($convRate >= 25 ? 'text-orange-600' : 'text-red-600');
                ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center mr-3">
                                <span class="text-xs font-medium text-indigo-600">
                                    <?= strtoupper(substr($rep['name'], 0, 2)) ?>
                                </span>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900"><?= htmlspecialchars($rep['name']) ?></p>
                                <?php if ($rep['email']): ?>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars($rep['email']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs">
                            <?= htmlspecialchars($rep['cluster']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs <?= $rep['country'] === 'BE' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800' ?>">
                            <?= $rep['country'] ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right"><?= number_format($rep['total_clients'], 0, ',', ' ') ?></td>
                    <td class="px-6 py-4 text-right font-medium text-green-600"><?= $rep['stats']['customers_ordered'] ?></td>
                    <td class="px-6 py-4 text-right font-bold <?= $convClass ?>"><?= $convRate ?>%</td>
                    <td class="px-6 py-4 text-right font-medium"><?= number_format($rep['stats']['total_quantity'], 0, ',', ' ') ?></td>
                    <td class="px-6 py-4 text-center">
                        <a href="/stm/admin/stats/sales?rep_id=<?= urlencode($rep['id']) ?>&rep_country=<?= $rep['country'] ?><?= $campaignId ? '&campaign_id=' . $campaignId : '' ?>" 
                           class="text-indigo-600 hover:text-indigo-800">
                            <i class="fas fa-eye"></i> Détail
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Résumé par cluster -->
<?php if (!empty($reps)): ?>
<div class="mt-6 bg-white rounded-lg shadow-sm p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Résumé par cluster</h3>
    
    <?php
    // Grouper par cluster
    $clusterSummary = [];
    foreach ($reps as $rep) {
        $cluster = $rep['cluster'];
        if (!isset($clusterSummary[$cluster])) {
            $clusterSummary[$cluster] = ['reps' => 0, 'clients' => 0, 'ordered' => 0, 'quantity' => 0];
        }
        $clusterSummary[$cluster]['reps']++;
        $clusterSummary[$cluster]['clients'] += $rep['total_clients'];
        $clusterSummary[$cluster]['ordered'] += $rep['stats']['customers_ordered'];
        $clusterSummary[$cluster]['quantity'] += $rep['stats']['total_quantity'];
    }
    // Trier par quantité
    uasort($clusterSummary, fn($a, $b) => $b['quantity'] - $a['quantity']);
    ?>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($clusterSummary as $cluster => $stats): ?>
        <?php $rate = $stats['clients'] > 0 ? round(($stats['ordered'] / $stats['clients']) * 100, 1) : 0; ?>
        <div class="border border-gray-200 rounded-lg p-4">
            <h4 class="font-semibold text-gray-900 mb-2"><?= htmlspecialchars($cluster) ?></h4>
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div>
                    <span class="text-gray-500">Représentants:</span>
                    <span class="font-medium ml-1"><?= $stats['reps'] ?></span>
                </div>
                <div>
                    <span class="text-gray-500">Clients:</span>
                    <span class="font-medium ml-1"><?= number_format($stats['clients'], 0, ',', ' ') ?></span>
                </div>
                <div>
                    <span class="text-gray-500">Taux conv.:</span>
                    <span class="font-medium ml-1 <?= $rate >= 50 ? 'text-green-600' : 'text-orange-600' ?>"><?= $rate ?>%</span>
                </div>
                <div>
                    <span class="text-gray-500">Quantité:</span>
                    <span class="font-bold ml-1 text-indigo-600"><?= number_format($stats['quantity'], 0, ',', ' ') ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>

<?php
$content = ob_get_clean();
$pageScripts = '';
require __DIR__ . '/../../layouts/admin.php';
?>
