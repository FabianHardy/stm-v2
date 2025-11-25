<?php
/**
 * Vue : Synchronisation Base de Données (Prod → Dev)
 * 
 * Interface pour copier les données de production vers développement
 * avec vérification de structure et sélection des tables.
 * 
 * @created 2025/11/25 12:00
 */

// Variables disponibles :
// $title, $error, $structureReport, $tablesStats, $excludedTables, $optionalTables

ob_start();
?>

<!-- En-tête de page -->
<div class="mb-6">
    <div class="flex items-center gap-3 mb-2">
        <div class="p-2 bg-orange-100 rounded-lg">
            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
            </svg>
        </div>
        <div>
            <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($title) ?></h1>
            <p class="text-gray-500">Copier les données de production vers développement</p>
        </div>
    </div>
</div>

<!-- Alerte Mode Dev -->
<div class="mb-6 p-4 bg-orange-50 border border-orange-200 rounded-lg flex items-start gap-3">
    <svg class="w-5 h-5 text-orange-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
    </svg>
    <div>
        <p class="font-medium text-orange-800">Mode Développement</p>
        <p class="text-sm text-orange-700">Cette fonctionnalité est disponible uniquement en mode développement. Elle permet de copier les données de la base de production vers la base de développement.</p>
    </div>
</div>

<!-- Messages flash -->
<?php if ($flashSuccess = \Core\Session::flash('success')): ?>
<div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg flex items-center gap-3">
    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
    </svg>
    <p class="text-green-800"><?= htmlspecialchars($flashSuccess) ?></p>
</div>
<?php endif; ?>

<?php if ($flashError = \Core\Session::flash('error')): ?>
<div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center gap-3">
    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
    </svg>
    <p class="text-red-800"><?= htmlspecialchars($flashError) ?></p>
</div>
<?php endif; ?>

<!-- Erreur de connexion -->
<?php if ($error): ?>
<div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
    <h3 class="font-medium text-red-800 mb-2">Erreur de connexion</h3>
    <p class="text-red-700"><?= htmlspecialchars($error) ?></p>
</div>
<?php else: ?>

<!-- Informations bases de données -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <!-- Source (Prod) -->
    <div class="bg-white rounded-lg shadow p-5 border-l-4 border-blue-500">
        <div class="flex items-center gap-2 mb-3">
            <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded">SOURCE</span>
            <span class="text-gray-500 text-sm">Production</span>
        </div>
        <p class="font-mono text-sm text-gray-700">trendyblog_stm_v2</p>
        <p class="text-xs text-gray-500 mt-1">actions.trendyfoods.com/stm/</p>
    </div>
    
    <!-- Target (Dev) -->
    <div class="bg-white rounded-lg shadow p-5 border-l-4 border-orange-500">
        <div class="flex items-center gap-2 mb-3">
            <span class="px-2 py-1 bg-orange-100 text-orange-800 text-xs font-medium rounded">CIBLE</span>
            <span class="text-gray-500 text-sm">Développement</span>
        </div>
        <p class="font-mono text-sm text-gray-700">trendyblog_stm_dev</p>
        <p class="text-xs text-gray-500 mt-1">dev.trendyfoodsblog.com/stm/</p>
    </div>
</div>

<!-- Statut de la structure -->
<?php if ($structureReport): ?>
<div class="bg-white rounded-lg shadow mb-6">
    <div class="px-5 py-4 border-b flex items-center justify-between">
        <h2 class="font-semibold text-gray-800">Vérification de la structure</h2>
        <?php if ($structureReport['success']): ?>
        <span class="px-3 py-1 bg-green-100 text-green-800 text-sm font-medium rounded-full flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            Structure identique
        </span>
        <?php else: ?>
        <span class="px-3 py-1 bg-red-100 text-red-800 text-sm font-medium rounded-full flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            Différences détectées
        </span>
        <?php endif; ?>
    </div>
    
    <?php if (!$structureReport['success']): ?>
    <div class="p-5 bg-red-50 border-b border-red-100">
        <p class="text-red-800 text-sm mb-3">Des différences de structure ont été détectées. Veuillez les corriger avant de synchroniser.</p>
        <ul class="space-y-2">
            <?php foreach ($structureReport['tables'] as $table => $info): ?>
                <?php if (!empty($info['differences'])): ?>
                <li class="text-sm">
                    <span class="font-medium text-red-700"><?= htmlspecialchars($table) ?></span>
                    <ul class="ml-4 mt-1 text-red-600">
                        <?php foreach ($info['differences'] as $diff): ?>
                        <li>• <?= htmlspecialchars($diff['message']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Formulaire de synchronisation -->
<form action="/stm/admin/dev-tools/sync-db" method="POST" id="syncForm">
    <input type="hidden" name="_token" value="<?= \Core\Session::getCsrfToken() ?>">
    
    <div class="bg-white rounded-lg shadow">
        <div class="px-5 py-4 border-b">
            <h2 class="font-semibold text-gray-800">Tables à synchroniser</h2>
            <p class="text-sm text-gray-500 mt-1">Sélectionnez les tables que vous souhaitez copier de prod vers dev</p>
        </div>
        
        <div class="p-5">
            <!-- Boutons de sélection rapide -->
            <div class="flex gap-3 mb-4">
                <button type="button" onclick="selectAll()" class="px-3 py-1.5 text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 rounded transition">
                    Tout sélectionner
                </button>
                <button type="button" onclick="deselectAll()" class="px-3 py-1.5 text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 rounded transition">
                    Tout désélectionner
                </button>
            </div>
            
            <!-- Liste des tables -->
            <div class="space-y-2">
                <?php if ($tablesStats): ?>
                    <?php foreach ($tablesStats as $table => $stats): ?>
                    <?php 
                        $isOptional = $stats['is_optional'];
                        $hasStructureError = isset($structureReport['tables'][$table]) && 
                                            !empty($structureReport['tables'][$table]['differences']);
                    ?>
                    <label class="flex items-center p-3 rounded-lg border <?= $hasStructureError ? 'border-red-200 bg-red-50' : 'border-gray-200 hover:bg-gray-50' ?> cursor-pointer transition">
                        <input 
                            type="checkbox" 
                            name="tables[]" 
                            value="<?= htmlspecialchars($table) ?>"
                            class="table-checkbox w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500"
                            <?= !$isOptional && !$hasStructureError ? 'checked' : '' ?>
                            <?= $hasStructureError ? 'disabled' : '' ?>
                        >
                        <div class="ml-3 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-gray-800"><?= htmlspecialchars($table) ?></span>
                                <?php if ($isOptional): ?>
                                <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded">Optionnel</span>
                                <?php endif; ?>
                                <?php if ($hasStructureError): ?>
                                <span class="px-2 py-0.5 bg-red-100 text-red-600 text-xs rounded">Structure différente</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-medium text-gray-700">
                                <?= number_format($stats['source_count']) ?> lignes
                            </div>
                            <div class="text-xs text-gray-500">
                                Dev: <?= number_format($stats['target_count']) ?>
                            </div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Tables exclues -->
            <?php if (!empty($excludedTables)): ?>
            <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Tables exclues (non synchronisées)</h4>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($excludedTables as $table): ?>
                    <span class="px-2 py-1 bg-gray-200 text-gray-600 text-xs rounded">
                        <?= htmlspecialchars($table) ?>
                    </span>
                    <?php endforeach; ?>
                </div>
                <p class="text-xs text-gray-500 mt-2">Ces tables contiennent des données spécifiques à chaque environnement.</p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Actions -->
        <div class="px-5 py-4 bg-gray-50 border-t rounded-b-lg flex items-center justify-between">
            <p class="text-sm text-gray-500">
                <span id="selectedCount">0</span> table(s) sélectionnée(s)
            </p>
            <button 
                type="submit" 
                id="submitBtn"
                class="px-6 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-medium rounded-lg transition flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                <?= !$structureReport['success'] ? 'disabled' : '' ?>
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Synchroniser
            </button>
        </div>
    </div>
</form>

<?php endif; ?>

<script>
// Compteur de tables sélectionnées
function updateCount() {
    const checkboxes = document.querySelectorAll('.table-checkbox:checked');
    document.getElementById('selectedCount').textContent = checkboxes.length;
}

// Tout sélectionner
function selectAll() {
    document.querySelectorAll('.table-checkbox:not(:disabled)').forEach(cb => cb.checked = true);
    updateCount();
}

// Tout désélectionner
function deselectAll() {
    document.querySelectorAll('.table-checkbox').forEach(cb => cb.checked = false);
    updateCount();
}

// Événements
document.querySelectorAll('.table-checkbox').forEach(cb => {
    cb.addEventListener('change', updateCount);
});

// Confirmation avant soumission
document.getElementById('syncForm')?.addEventListener('submit', function(e) {
    const count = document.querySelectorAll('.table-checkbox:checked').length;
    
    if (count === 0) {
        e.preventDefault();
        alert('Veuillez sélectionner au moins une table.');
        return;
    }
    
    if (!confirm('⚠️ ATTENTION !\n\nCette action va :\n1. Vider les tables sélectionnées dans la DB de développement\n2. Copier toutes les données de production vers développement\n\nVoulez-vous continuer ?')) {
        e.preventDefault();
    }
});

// Initialisation
updateCount();
</script>

<?php
$content = ob_get_clean();
$pageTitle = $title;

// Scripts additionnels pour cette page
$pageScripts = '';

require dirname(dirname(__DIR__)) . '/layouts/admin.php';
?>
