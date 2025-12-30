<?php
/**
 * Vue Admin : Liste des traductions
 *
 * @package    App\Views\admin\translations
 * @author     Fabian Hardy
 * @version    1.0.0
 * @created    2025/12/30
 */

$pageTitle = isset($showMissingOnly) && $showMissingOnly ? 'Traductions manquantes' : 'Traductions';

ob_start();
?>

<div class="space-y-6">
    <!-- En-tête -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-language mr-2 text-purple-600"></i>
                <?= $pageTitle ?>
            </h1>
            <p class="mt-1 text-sm text-gray-600">
                Gérez les traductions FR/NL du front client
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <!-- Bouton régénérer cache -->
            <a href="/stm/admin/translations/rebuild-cache"
               class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition"
               onclick="return confirm('Régénérer le cache des traductions ?')">
                <i class="fas fa-sync-alt mr-2"></i>
                Régénérer cache
            </a>

            <!-- Bouton export -->
            <a href="/stm/admin/translations/export"
               class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                <i class="fas fa-download mr-2"></i>
                Exporter JSON
            </a>

            <?php if (!isset($showMissingOnly) && $missingCount > 0): ?>
            <!-- Bouton traductions manquantes -->
            <a href="/stm/admin/translations/missing"
               class="inline-flex items-center px-4 py-2 bg-orange-100 text-orange-700 rounded-lg hover:bg-orange-200 transition">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?= $missingCount ?> manquante(s)
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Messages flash -->
    <?php if (isset($_SESSION['success'])): ?>
    <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-r-lg">
        <div class="flex items-center">
            <i class="fas fa-check-circle text-green-500 mr-3"></i>
            <p class="text-green-700"><?= htmlspecialchars($_SESSION['success']) ?></p>
        </div>
    </div>
    <?php unset($_SESSION['success']); endif; ?>

    <?php if (isset($_SESSION['warning'])): ?>
    <div class="bg-orange-50 border-l-4 border-orange-500 p-4 rounded-r-lg">
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle text-orange-500 mr-3"></i>
            <p class="text-orange-700"><?= htmlspecialchars($_SESSION['warning']) ?></p>
        </div>
    </div>
    <?php unset($_SESSION['warning']); endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
            <p class="text-red-700"><?= htmlspecialchars($_SESSION['error']) ?></p>
        </div>
    </div>
    <?php unset($_SESSION['error']); endif; ?>

    <!-- Statistiques par catégorie -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
        <a href="/stm/admin/translations"
           class="p-3 rounded-lg text-center transition <?= empty($category) && !isset($showMissingOnly) ? 'bg-purple-100 ring-2 ring-purple-500' : 'bg-white hover:bg-gray-50' ?>">
            <p class="text-2xl font-bold text-purple-600"><?= array_sum(array_column($countByCategory, 'count')) ?></p>
            <p class="text-xs text-gray-600 uppercase">Toutes</p>
        </a>
        <?php foreach ($countByCategory as $cat): ?>
        <a href="/stm/admin/translations?category=<?= urlencode($cat['category']) ?>"
           class="p-3 rounded-lg text-center transition <?= ($category ?? '') === $cat['category'] ? 'bg-purple-100 ring-2 ring-purple-500' : 'bg-white hover:bg-gray-50' ?>">
            <p class="text-2xl font-bold text-gray-800"><?= $cat['count'] ?></p>
            <p class="text-xs text-gray-600 uppercase"><?= htmlspecialchars($cat['category']) ?></p>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Filtres et recherche -->
    <?php if (!isset($showMissingOnly)): ?>
    <div class="bg-white rounded-lg shadow-sm p-4">
        <form method="GET" action="/stm/admin/translations" class="flex flex-col sm:flex-row gap-4" id="filterForm">
            <!-- Filtre catégorie -->
            <div class="flex-1">
                <select name="category"
                        id="categorySelect"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="">Toutes les catégories</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>" <?= ($category ?? '') === $cat ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Recherche -->
            <div class="flex-1">
                <div class="relative">
                    <input type="text"
                           name="search"
                           value="<?= htmlspecialchars($search ?? '') ?>"
                           placeholder="Rechercher (clé, texte FR/NL)..."
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>

            <button type="submit" class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                <i class="fas fa-filter mr-2"></i>
                Filtrer
            </button>

            <?php if (!empty($category) || !empty($search)): ?>
            <a href="/stm/admin/translations" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-center">
                <i class="fas fa-times mr-2"></i>
                Réinitialiser
            </a>
            <?php endif; ?>
        </form>
    </div>
    <?php else: ?>
    <div class="bg-orange-50 border-l-4 border-orange-500 p-4 rounded-r-lg">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-info-circle text-orange-500 mr-3"></i>
                <p class="text-orange-700">
                    Affichage des traductions sans version néerlandaise (<?= count($translations) ?>)
                </p>
            </div>
            <a href="/stm/admin/translations" class="text-orange-700 hover:text-orange-900 underline">
                Voir toutes
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tableau des traductions -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <?php if (empty($translations)): ?>
        <div class="p-12 text-center">
            <i class="fas fa-language text-6xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">Aucune traduction trouvée</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-48">
                            Clé
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            🇫🇷 Français
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            🇳🇱 Néerlandais
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-20">
                            HTML
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php
                    $currentCategory = '';
                    foreach ($translations as $trans):
                        // Afficher un séparateur de catégorie
                        if ($trans['category'] !== $currentCategory && empty($category) && !isset($showMissingOnly)):
                            $currentCategory = $trans['category'];
                    ?>
                    <tr class="bg-purple-50">
                        <td colspan="5" class="px-4 py-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
                                <i class="fas fa-folder mr-2"></i>
                                <?= htmlspecialchars($currentCategory) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <tr class="hover:bg-gray-50 transition" data-id="<?= $trans['id'] ?>">
                        <!-- Clé -->
                        <td class="px-4 py-3">
                            <code class="text-xs bg-gray-100 px-2 py-1 rounded text-purple-700 font-mono">
                                <?= htmlspecialchars($trans['key']) ?>
                            </code>
                            <?php if (!empty($trans['description'])): ?>
                            <p class="text-xs text-gray-400 mt-1" title="<?= htmlspecialchars($trans['description']) ?>">
                                <?= htmlspecialchars(mb_substr($trans['description'], 0, 40)) ?><?= mb_strlen($trans['description']) > 40 ? '...' : '' ?>
                            </p>
                            <?php endif; ?>
                        </td>

                        <!-- FR -->
                        <td class="px-4 py-3">
                            <div class="text-sm text-gray-900 max-w-xs overflow-hidden"
                                 title="<?= htmlspecialchars(strip_tags($trans['text_fr'])) ?>">
                                <?php if ($trans['is_html']): ?>
                                <span class="text-xs text-orange-500 mr-1">[HTML]</span>
                                <?php endif; ?>
                                <?= htmlspecialchars(mb_substr(strip_tags($trans['text_fr']), 0, 80)) ?><?= mb_strlen(strip_tags($trans['text_fr'])) > 80 ? '...' : '' ?>
                            </div>
                        </td>

                        <!-- NL -->
                        <td class="px-4 py-3">
                            <?php if (!empty($trans['text_nl'])): ?>
                            <div class="text-sm text-gray-900 max-w-xs overflow-hidden"
                                 title="<?= htmlspecialchars(strip_tags($trans['text_nl'])) ?>">
                                <?= htmlspecialchars(mb_substr(strip_tags($trans['text_nl']), 0, 80)) ?><?= mb_strlen(strip_tags($trans['text_nl'])) > 80 ? '...' : '' ?>
                            </div>
                            <?php else: ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-orange-100 text-orange-700">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Manquant
                            </span>
                            <?php endif; ?>
                        </td>

                        <!-- HTML -->
                        <td class="px-4 py-3 text-center">
                            <?php if ($trans['is_html']): ?>
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-orange-100 text-orange-600">
                                <i class="fas fa-code text-xs"></i>
                            </span>
                            <?php else: ?>
                            <span class="text-gray-300">-</span>
                            <?php endif; ?>
                        </td>

                        <!-- Actions -->
                        <td class="px-4 py-3 text-center">
                            <a href="/stm/admin/translations/<?= $trans['id'] ?>/edit"
                               class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-200 transition"
                               title="Modifier">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Compteur -->
        <div class="px-4 py-3 bg-gray-50 border-t text-sm text-gray-600">
            <i class="fas fa-list mr-2"></i>
            <?= count($translations) ?> traduction(s) affichée(s)
        </div>
        <?php endif; ?>
    </div>

    <!-- Légende -->
    <div class="bg-blue-50 rounded-lg p-4">
        <h4 class="font-semibold text-blue-800 mb-2">
            <i class="fas fa-info-circle mr-2"></i>
            Variables disponibles
        </h4>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm text-blue-700">
            <code class="bg-blue-100 px-2 py-1 rounded">{year}</code>
            <code class="bg-blue-100 px-2 py-1 rounded">{date}</code>
            <code class="bg-blue-100 px-2 py-1 rounded">{customer}</code>
            <code class="bg-blue-100 px-2 py-1 rounded">{link_cgu}</code>
            <code class="bg-blue-100 px-2 py-1 rounded">{link_privacy}</code>
            <code class="bg-blue-100 px-2 py-1 rounded">{link_cgv}</code>
            <code class="bg-blue-100 px-2 py-1 rounded">{link_mentions}</code>
            <code class="bg-blue-100 px-2 py-1 rounded">{start} / {end}</code>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$title = $pageTitle;
require __DIR__ . '/../../layouts/admin.php';
?>