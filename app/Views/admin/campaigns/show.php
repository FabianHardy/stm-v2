<?php
/**
 * Vue : Détails d'une campagne
 * 
 * @package STM/Views/Admin/Campaigns
 * @version 2.1.0
 * @modified 11/11/2025 - Ajout URL publique avec QR code
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

                        <div class="mt-3 flex items-center gap-x-4 text-xs text-purple-700">
                            <div class="flex items-center gap-x-1">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.348 14.651a3.75 3.75 0 010-5.303m5.304 0a3.75 3.75 0 010 5.303m-7.425 2.122a6.75 6.75 0 010-9.546m9.546 0a6.75 6.75 0 010 9.546M5.106 18.894c-3.808-3.808-3.808-9.98 0-13.789m13.788 0c3.808 3.808 3.808 9.98 0 13.789" />
                                </svg>
                                <span>UUID: <code class="font-mono"><?= htmlspecialchars($campaign['uuid']) ?></code></span>
                            </div>
                            <div class="flex items-center gap-x-1">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 8.25h15m-16.5 7.5h15m-1.8-13.5l-3.9 19.5m-2.1-19.5l-3.9 19.5" />
                                </svg>
                                <span>Slug: <code class="font-mono"><?= htmlspecialchars($campaign['slug']) ?></code></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informations générales -->
        <div class="bg-white shadow-sm rounded-lg">
            <div class="px-6 py-5 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Informations générales</h3>
            </div>
            <div class="px-6 py-5">
                <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Nom de la campagne</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($campaign['name']) ?></dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Pays</dt>
                        <dd class="mt-1">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium 
                                <?= $campaign['country'] === 'BE' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800' ?>">
                                <?= $campaign['country'] === 'BE' ? 'Belgique' : 'Luxembourg' ?>
                            </span>
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

                    <div class="sm:col-span-2">
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
                </dl>
            </div>
        </div>

        <!-- Contenu multilingue -->
        <?php if (!empty($campaign['title_fr']) || !empty($campaign['title_nl'])): ?>
        <div class="bg-white shadow-sm rounded-lg">
            <div class="px-6 py-5 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Contenu</h3>
            </div>
            <div class="px-6 py-5 space-y-6">
                
                <?php if (!empty($campaign['title_fr']) || !empty($campaign['description_fr'])): ?>
                <div>
                    <h4 class="text-sm font-medium text-gray-900 mb-2 flex items-center gap-x-2">
                        <span class="inline-flex items-center rounded px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-800">
                            FR
                        </span>
                        Français
                    </h4>
                    <?php if (!empty($campaign['title_fr'])): ?>
                        <div class="mb-2">
                            <dt class="text-xs font-medium text-gray-500">Titre</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($campaign['title_fr']) ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($campaign['description_fr'])): ?>
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Description</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?= nl2br(htmlspecialchars($campaign['description_fr'])) ?></dd>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($campaign['title_nl']) || !empty($campaign['description_nl'])): ?>
                <div>
                    <h4 class="text-sm font-medium text-gray-900 mb-2 flex items-center gap-x-2">
                        <span class="inline-flex items-center rounded px-2 py-0.5 text-xs font-medium bg-orange-100 text-orange-800">
                            NL
                        </span>
                        Néerlandais
                    </h4>
                    <?php if (!empty($campaign['title_nl'])): ?>
                        <div class="mb-2">
                            <dt class="text-xs font-medium text-gray-500">Titre</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($campaign['title_nl']) ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($campaign['description_nl'])): ?>
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Description</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?= nl2br(htmlspecialchars($campaign['description_nl'])) ?></dd>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        
        <!-- Métadonnées -->
        <div class="bg-white shadow-sm rounded-lg">
            <div class="px-6 py-5 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Métadonnées</h3>
            </div>
            <div class="px-6 py-5">
                <dl class="space-y-4">
                    <div>
                        <dt class="text-xs font-medium text-gray-500">ID</dt>
                        <dd class="mt-1 text-sm text-gray-900">#<?= $campaign['id'] ?></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Créée le</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <?= date('d/m/Y à H:i', strtotime($campaign['created_at'])) ?>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Dernière modification</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <?= date('d/m/Y à H:i', strtotime($campaign['updated_at'])) ?>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="bg-white shadow-sm rounded-lg">
            <div class="px-6 py-5 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Actions rapides</h3>
            </div>
            <div class="px-6 py-5 space-y-2">
                <a href="#" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md">
                    <div class="flex items-center gap-x-2">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                        </svg>
                        Gérer les produits
                    </div>
                </a>
                <a href="#" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md">
                    <div class="flex items-center gap-x-2">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                        </svg>
                        Gérer les clients
                    </div>
                </a>
                <a href="#" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md">
                    <div class="flex items-center gap-x-2">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                        Voir les statistiques
                    </div>
                </a>
            </div>
        </div>

    </div>
</div>

<!-- Script copie URL -->
<script>
function copyToClipboard(text, button) {
    navigator.clipboard.writeText(text).then(() => {
        // Feedback visuel
        const originalHTML = button.innerHTML;
        button.innerHTML = '<svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg> Copié !';
        button.classList.remove('bg-purple-600', 'hover:bg-purple-700');
        button.classList.add('bg-green-600');
        
        setTimeout(() => {
            button.innerHTML = originalHTML;
            button.classList.remove('bg-green-600');
            button.classList.add('bg-purple-600', 'hover:bg-purple-700');
        }, 2000);
    }).catch(err => {
        alert('Erreur lors de la copie : ' + err);
    });
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/admin.php';
?>