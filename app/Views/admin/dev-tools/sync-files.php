<?php
/**
 * Vue : Synchronisation Fichiers (Prod → Dev)
 * 
 * Interface pour copier les fichiers uploadés de production
 * vers développement en mode différentiel.
 * 
 * @created 2025/11/25 12:00
 */

// Variables disponibles :
// $title, $error, $analysis

use Core\FileSync;

ob_start();
?>

<!-- En-tête de page -->
<div class="mb-6">
    <div class="flex items-center gap-3 mb-2">
        <div class="p-2 bg-purple-100 rounded-lg">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
            </svg>
        </div>
        <div>
            <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($title) ?></h1>
            <p class="text-gray-500">Copier les fichiers uploadés de production vers développement</p>
        </div>
    </div>
</div>

<!-- Alerte Mode Différentiel -->
<div class="mb-6 p-4 bg-purple-50 border border-purple-200 rounded-lg flex items-start gap-3">
    <svg class="w-5 h-5 text-purple-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
    </svg>
    <div>
        <p class="font-medium text-purple-800">Mode Différentiel</p>
        <p class="text-sm text-purple-700">Seuls les fichiers nouveaux ou modifiés seront copiés. Les fichiers existants et identiques ne seront pas écrasés.</p>
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

<!-- Erreur -->
<?php if ($error): ?>
<div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
    <h3 class="font-medium text-red-800 mb-2">Erreur</h3>
    <p class="text-red-700"><?= htmlspecialchars($error) ?></p>
</div>
<?php else: ?>

<!-- Statistiques globales -->
<?php if ($analysis): ?>
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-2xl font-bold text-blue-600"><?= $analysis['total_source_files'] ?></div>
        <div class="text-sm text-gray-500">Fichiers en prod</div>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-2xl font-bold text-gray-600"><?= $analysis['total_target_files'] ?></div>
        <div class="text-sm text-gray-500">Fichiers en dev</div>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-2xl font-bold text-orange-600"><?= $analysis['files_to_copy'] ?></div>
        <div class="text-sm text-gray-500">À copier</div>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-2xl font-bold text-green-600"><?= $analysis['files_up_to_date'] ?></div>
        <div class="text-sm text-gray-500">À jour</div>
    </div>
</div>
<?php endif; ?>

<!-- Détail par dossier -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="px-5 py-4 border-b">
        <h2 class="font-semibold text-gray-800">Analyse des dossiers</h2>
    </div>
    
    <div class="divide-y">
        <?php if ($analysis && !empty($analysis['folders'])): ?>
            <?php foreach ($analysis['folders'] as $folder => $data): ?>
            <div class="p-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                        </svg>
                        <span class="font-medium text-gray-800"><?= htmlspecialchars($folder) ?></span>
                    </div>
                    <div class="flex items-center gap-4 text-sm">
                        <?php if (!$data['source_exists']): ?>
                        <span class="text-gray-400">Dossier source inexistant</span>
                        <?php else: ?>
                        <span class="text-gray-500">Prod: <?= $data['source_count'] ?></span>
                        <span class="text-gray-500">Dev: <?= $data['target_count'] ?></span>
                        <?php if (count($data['files_to_copy']) > 0): ?>
                        <span class="px-2 py-1 bg-orange-100 text-orange-700 rounded-full text-xs font-medium">
                            <?= count($data['files_to_copy']) ?> à copier
                        </span>
                        <?php else: ?>
                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">
                            À jour
                        </span>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!empty($data['files_to_copy'])): ?>
                <details class="mt-2">
                    <summary class="cursor-pointer text-sm text-blue-600 hover:text-blue-800">
                        Voir les fichiers à copier
                    </summary>
                    <div class="mt-2 max-h-48 overflow-y-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="text-left px-3 py-2 text-gray-600">Fichier</th>
                                    <th class="text-left px-3 py-2 text-gray-600">Raison</th>
                                    <th class="text-right px-3 py-2 text-gray-600">Taille</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <?php foreach ($data['files_to_copy'] as $filename => $info): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 font-mono text-xs"><?= htmlspecialchars($filename) ?></td>
                                    <td class="px-3 py-2">
                                        <?php if ($info['reason'] === 'new'): ?>
                                        <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded">Nouveau</span>
                                        <?php else: ?>
                                        <span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 text-xs rounded">Modifié</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 py-2 text-right text-gray-500"><?= FileSync::formatSize($info['size']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </details>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
        <div class="p-5 text-center text-gray-500">
            Aucun dossier à analyser
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Action de synchronisation -->
<?php if ($analysis && $analysis['files_to_copy'] > 0): ?>
<form action="/stm/admin/dev-tools/sync-files" method="POST" id="syncFilesForm">
    <input type="hidden" name="_token" value="<?= \Core\Session::getCsrfToken() ?>">
    
    <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="font-medium text-gray-800">
                    <?= $analysis['files_to_copy'] ?> fichier(s) à synchroniser
                </p>
                <p class="text-sm text-gray-500">
                    Taille totale: <?= FileSync::formatSize($analysis['total_size_to_copy']) ?>
                </p>
            </div>
            <button 
                type="submit" 
                class="px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition flex items-center gap-2"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                </svg>
                Synchroniser les fichiers
            </button>
        </div>
    </div>
</form>

<script>
document.getElementById('syncFilesForm')?.addEventListener('submit', function(e) {
    if (!confirm('Voulez-vous copier les <?= $analysis['files_to_copy'] ?> fichier(s) de production vers développement ?')) {
        e.preventDefault();
    }
});
</script>
<?php else: ?>
<div class="bg-white rounded-lg shadow p-5 text-center">
    <svg class="w-12 h-12 text-green-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
    </svg>
    <p class="text-gray-600">Tous les fichiers sont à jour !</p>
</div>
<?php endif; ?>

<?php endif; ?>

<?php
$content = ob_get_clean();
$pageTitle = $title;
$pageScripts = '';

require dirname(dirname(__DIR__)) . '/layouts/admin.php';
?>
