<?php
/**
 * Vue : Statistiques - Rapports et exports
 * 
 * Page d'export CSV/Excel des données
 * 
 * @package STM
 * @created 2025/11/25
 */

use Core\Session;

$flash = Session::getFlash('error');

ob_start();
?>

<!-- En-tête -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Statistiques - Rapports</h1>
    <p class="text-gray-600 mt-1">Exportez vos données au format CSV ou Excel</p>
</div>

<?php if ($flash): ?>
<div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
    <div class="flex items-center">
        <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
        <p class="text-red-700"><?= htmlspecialchars($flash) ?></p>
    </div>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    
    <!-- Export global -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                <i class="fas fa-globe text-indigo-600"></i>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Export global</h3>
                <p class="text-sm text-gray-500">Toutes les commandes sur une période</p>
            </div>
        </div>
        
        <form method="POST" action="/stm/admin/stats/export" class="space-y-4">
            <input type="hidden" name="type" value="global">
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date début</label>
                    <input type="date" name="date_from" value="<?= date('Y-m-d', strtotime('-14 days')) ?>" 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date fin</label>
                    <input type="date" name="date_to" value="<?= date('Y-m-d') ?>" 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Campagne (optionnel)</label>
                <select name="campaign_id" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">Toutes les campagnes</option>
                    <?php foreach ($campaigns as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Format</label>
                <div class="flex gap-4">
                    <label class="flex items-center">
                        <input type="radio" name="format" value="csv" checked class="mr-2">
                        <span class="text-sm">CSV (virgule)</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="format" value="excel" class="mr-2">
                        <span class="text-sm">Excel (point-virgule)</span>
                    </label>
                </div>
            </div>
            
            <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700 transition">
                <i class="fas fa-download mr-2"></i>Télécharger
            </button>
        </form>
        
        <div class="mt-4 text-xs text-gray-500">
            <p><strong>Colonnes:</strong> Num_Client, Nom, Pays, Promo_Art, Nom_Produit, Quantité, Rep_Name, Cluster, Date_Commande</p>
        </div>
    </div>
    
    <!-- Export par campagne -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-bullhorn text-green-600"></i>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Export par campagne</h3>
                <p class="text-sm text-gray-500">Commandes d'une campagne spécifique</p>
            </div>
        </div>
        
        <form method="POST" action="/stm/admin/stats/export" class="space-y-4">
            <input type="hidden" name="type" value="campaign">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Campagne <span class="text-red-500">*</span></label>
                <select name="campaign_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">-- Sélectionner --</option>
                    <?php foreach ($campaigns as $c): ?>
                    <option value="<?= $c['id'] ?>">
                        <?= htmlspecialchars($c['name']) ?> (<?= $c['country'] ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Format</label>
                <div class="flex gap-4">
                    <label class="flex items-center">
                        <input type="radio" name="format" value="csv" checked class="mr-2">
                        <span class="text-sm">CSV</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="format" value="excel" class="mr-2">
                        <span class="text-sm">Excel</span>
                    </label>
                </div>
            </div>
            
            <button type="submit" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-download mr-2"></i>Télécharger
            </button>
        </form>
        
        <div class="mt-4 text-xs text-gray-500">
            <p><strong>Colonnes:</strong> Num_Client, Nom, Pays, Promo_Art, Nom_Produit, Quantité, Email, Rep_Name, Date_Commande</p>
        </div>
    </div>
    
    <!-- Export représentants -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                <i class="fas fa-users text-orange-600"></i>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Export représentants</h3>
                <p class="text-sm text-gray-500">Stats par commercial</p>
            </div>
        </div>
        
        <form method="POST" action="/stm/admin/stats/export" class="space-y-4">
            <input type="hidden" name="type" value="reps">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Campagne (optionnel)</label>
                <select name="campaign_id" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">Toutes les campagnes</option>
                    <?php foreach ($campaigns as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Format</label>
                <div class="flex gap-4">
                    <label class="flex items-center">
                        <input type="radio" name="format" value="csv" checked class="mr-2">
                        <span class="text-sm">CSV</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="format" value="excel" class="mr-2">
                        <span class="text-sm">Excel</span>
                    </label>
                </div>
            </div>
            
            <button type="submit" class="w-full bg-orange-600 text-white py-2 rounded-lg hover:bg-orange-700 transition">
                <i class="fas fa-download mr-2"></i>Télécharger
            </button>
        </form>
        
        <div class="mt-4 text-xs text-gray-500">
            <p><strong>Colonnes:</strong> Rep_ID, Rep_Nom, Cluster, Pays, Nb_Clients, Clients_Commandé, Taux_Conv, Total_Quantité</p>
        </div>
    </div>
    
    <!-- Export clients sans commande -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                <i class="fas fa-user-times text-red-600"></i>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Clients sans commande</h3>
                <p class="text-sm text-gray-500">Liste des clients n'ayant pas commandé</p>
            </div>
        </div>
        
        <form method="POST" action="/stm/admin/stats/export" class="space-y-4">
            <input type="hidden" name="type" value="not_ordered">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Campagne <span class="text-red-500">*</span></label>
                <select name="campaign_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">-- Sélectionner --</option>
                    <?php foreach ($campaigns as $c): ?>
                    <option value="<?= $c['id'] ?>">
                        <?= htmlspecialchars($c['name']) ?> (<?= $c['country'] ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Format</label>
                <div class="flex gap-4">
                    <label class="flex items-center">
                        <input type="radio" name="format" value="csv" checked class="mr-2">
                        <span class="text-sm">CSV</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="format" value="excel" class="mr-2">
                        <span class="text-sm">Excel</span>
                    </label>
                </div>
            </div>
            
            <button type="submit" class="w-full bg-red-600 text-white py-2 rounded-lg hover:bg-red-700 transition">
                <i class="fas fa-download mr-2"></i>Télécharger
            </button>
        </form>
        
        <div class="mt-4 text-xs text-gray-500">
            <p><strong>Colonnes:</strong> Num_Client, Nom, Pays, Rep_Name</p>
        </div>
    </div>
</div>

<!-- Info format -->
<div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
    <div class="flex items-start">
        <i class="fas fa-info-circle text-blue-600 mt-0.5 mr-3"></i>
        <div class="text-sm text-blue-800">
            <p class="font-medium mb-1">Format des fichiers</p>
            <ul class="list-disc list-inside space-y-1">
                <li><strong>CSV (virgule)</strong> : Compatible avec la plupart des logiciels</li>
                <li><strong>Excel (point-virgule)</strong> : Optimisé pour Microsoft Excel</li>
                <li>Les fichiers sont encodés en UTF-8 avec BOM pour un affichage correct des accents</li>
            </ul>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageScripts = '';
require __DIR__ . '/../../layouts/admin.php';
?>
