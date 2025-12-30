<?php
/**
 * Vue : Détail d'une commande
 *
 * Affiche les informations complètes d'une commande :
 * - Infos client
 * - Infos campagne
 * - Lignes de commande (produits, quantités)
 * - Gestion fichier TXT
 *
 * @package    App\Views\admin\orders
 * @author     Fabian Hardy
 * @version    1.2.0
 * @created    2025/11/27
 * @modified   2025/12/30 - Suppression N° commande et section statut synchro
 */

// Permissions pour les boutons (à remplacer par PermissionHelper quand disponible)
$canExport = true;

ob_start();

// Statuts de synchronisation avec labels et couleurs
$statusLabels = [
    "pending_sync" => ["label" => "En attente de synchro", "class" => "bg-yellow-100 text-yellow-800", "icon" => "fa-clock"],
    "synced" => ["label" => "Synchronisée", "class" => "bg-green-100 text-green-800", "icon" => "fa-check-circle"],
    "error" => ["label" => "Erreur", "class" => "bg-red-100 text-red-800", "icon" => "fa-exclamation-triangle"],
    // Anciens statuts (rétrocompatibilité)
    "pending" => ["label" => "En attente", "class" => "bg-yellow-100 text-yellow-800", "icon" => "fa-clock"],
    "validated" => ["label" => "Validée", "class" => "bg-green-100 text-green-800", "icon" => "fa-check-circle"],
    "cancelled" => ["label" => "Annulée", "class" => "bg-red-100 text-red-800", "icon" => "fa-times-circle"],
];
$currentStatus = $statusLabels[$order["status"] ?? "pending_sync"] ?? $statusLabels["pending_sync"];

// Sécurisation des données
$campaignName = htmlspecialchars($order["campaign_name"] ?? "N/A");
$companyName = htmlspecialchars($order["company_name"] ?? "N/A");
$customerNumber = htmlspecialchars($order["customer_number"] ?? "N/A");
$customerEmail = htmlspecialchars($order["customer_email"] ?? ($order["customer_email_db"] ?? "N/A"));
$repName = htmlspecialchars($order["rep_name"] ?? "");
$customerLanguage = $order["customer_language"] ?? "fr";
$country = $order["customer_country"] ?? ($order["campaign_country"] ?? "BE");

// Vérifier si le fichier TXT existe (physiquement OU stocké en DB)
$fileExistsPhysically = !empty($order['file_path']) && file_exists(__DIR__ . '/../../../../public/' . $order['file_path']);
$hasFileContent = !empty($order['file_content']);
$fileExists = $fileExistsPhysically || $hasFileContent;

// Calculer la quantité totale à partir des lignes de commande
$totalQuantity = 0;
if (!empty($orderLines) && is_array($orderLines)) {
    foreach ($orderLines as $line) {
        $totalQuantity += (int)($line['quantity'] ?? 0);
    }
}

// Titre de la page : "Commande - N° Client - Nom Campagne"
$pageTitle = "Commande - {$customerNumber} - {$campaignName}";
?>

<!-- En-tête de page -->
<div class="mb-6">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center gap-4">
            <a href="<?php echo htmlspecialchars($backUrl ?? '/stm/admin/orders'); ?>" class="inline-flex items-center text-gray-500 hover:text-gray-700 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Retour
            </a>
            <div class="h-6 w-px bg-gray-300"></div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    <?php echo $pageTitle; ?>
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    Passée le <?php echo date("d/m/Y à H:i", strtotime($order["created_at"])); ?>
                </p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <?php if ($canExport && $fileExists): ?>
            <!-- Bouton Télécharger TXT -->
            <a href="/stm/admin/orders/download?id=<?php echo (int) $order["id"]; ?>"
               class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 border border-transparent rounded-md text-sm font-medium text-white transition-colors">
                <i class="fas fa-download mr-2"></i>
                Télécharger TXT
            </a>
            <?php endif; ?>
            <?php if ($canExport): ?>
            <!-- Bouton Régénérer TXT -->
            <form method="POST" action="/stm/admin/orders/regenerate" class="inline">
                <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="id" value="<?= $order['id'] ?>">
                <button type="submit"
                        onclick="return confirm('Régénérer le fichier TXT ?')"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                    <i class="fas fa-sync-alt mr-2"></i>
                    <?= $fileExists ? 'Régénérer' : 'Générer' ?> TXT
                </button>
            </form>
            <?php endif; ?>
            <!-- Statut -->
            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium <?php echo $currentStatus["class"]; ?>">
                <i class="fas <?php echo $currentStatus["icon"]; ?> mr-2"></i>
                <?php echo $currentStatus["label"]; ?>
            </span>
        </div>
    </div>
</div>

<!-- Grille principale -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

    <!-- Infos Client -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="fas fa-user text-indigo-500 mr-2"></i>
            Client
        </h3>
        <dl class="space-y-3">
            <div>
                <dt class="text-xs font-medium text-gray-500 uppercase">Société</dt>
                <dd class="text-sm font-semibold text-gray-900"><?php echo $companyName; ?></dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500 uppercase">N° Client</dt>
                <dd class="text-sm text-gray-700">
                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 font-mono">
                        <?php echo $customerNumber; ?>
                    </span>
                </dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500 uppercase">Email</dt>
                <dd class="text-sm text-gray-700">
                    <a href="mailto:<?php echo $customerEmail; ?>" class="text-indigo-600 hover:underline">
                        <?php echo $customerEmail; ?>
                    </a>
                </dd>
            </div>
            <?php if ($repName): ?>
            <div>
                <dt class="text-xs font-medium text-gray-500 uppercase">Commercial</dt>
                <dd class="text-sm text-gray-700"><?php echo $repName; ?></dd>
            </div>
            <?php endif; ?>
            <div>
                <dt class="text-xs font-medium text-gray-500 uppercase">Langue</dt>
                <dd class="text-sm text-gray-700">
                    <?php echo $customerLanguage === "nl" ? "🇳🇱 Néerlandais" : "🇫🇷 Français"; ?>
                </dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500 uppercase">Pays</dt>
                <dd class="text-sm text-gray-700">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $country === "BE" ? "bg-blue-100 text-blue-800" : "bg-yellow-100 text-yellow-800"; ?>">
                        <?php echo $country === "BE" ? "🇧🇪 Belgique" : "🇱🇺 Luxembourg"; ?>
                    </span>
                </dd>
            </div>
        </dl>
    </div>

    <!-- Infos Campagne -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="fas fa-bullhorn text-orange-500 mr-2"></i>
            Campagne
        </h3>
        <dl class="space-y-3">
            <div>
                <dt class="text-xs font-medium text-gray-500 uppercase">Nom</dt>
                <dd class="text-sm font-semibold text-gray-900"><?php echo $campaignName; ?></dd>
            </div>
            <?php if (!empty($order["campaign_id"])): ?>
            <?php if (!empty($order["campaign_start"]) && !empty($order["campaign_end"])): ?>
            <div>
                <dt class="text-xs font-medium text-gray-500 uppercase">Période</dt>
                <dd class="text-sm text-gray-700">
                    <?php echo date("d/m/Y", strtotime($order["campaign_start"])); ?>
                    <span class="text-gray-400 mx-1">→</span>
                    <?php echo date("d/m/Y", strtotime($order["campaign_end"])); ?>
                </dd>
            </div>
            <?php endif; ?>
            <div>
                <dt class="text-xs font-medium text-gray-500 uppercase">Pays campagne</dt>
                <dd class="text-sm text-gray-700">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo ($order["campaign_country"] ?? "BE") === "BE" ? "bg-blue-100 text-blue-800" : "bg-yellow-100 text-yellow-800"; ?>">
                        <?php echo ($order["campaign_country"] ?? "BE") === "BE" ? "🇧🇪 Belgique" : "🇱🇺 Luxembourg"; ?>
                    </span>
                </dd>
            </div>
            <?php endif; ?>
        </dl>
        <div class="mt-4 pt-4 border-t border-gray-200">
            <a href="/stm/admin/campaigns/<?= $order['campaign_id'] ?>"
               class="text-sm text-indigo-600 hover:text-indigo-800">
                <i class="fas fa-external-link-alt mr-1"></i>
                Voir la campagne
            </a>
        </div>
    </div>

    <!-- Récapitulatif & Fichier -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="fas fa-receipt text-green-500 mr-2"></i>
            Récapitulatif
        </h3>
        <dl class="space-y-3">
            <div class="flex justify-between items-center">
                <dt class="text-sm text-gray-500">Produits</dt>
                <dd class="text-sm font-medium text-gray-900"><?php echo count($orderLines); ?></dd>
            </div>
            <div class="flex justify-between items-center">
                <dt class="text-sm text-gray-500">Quantité totale</dt>
                <dd class="text-lg font-bold text-orange-600"><?php echo number_format($totalQuantity, 0, ",", " "); ?> promos</dd>
            </div>
            <div class="border-t pt-3 mt-3">
                <div class="flex justify-between items-center">
                    <dt class="text-sm text-gray-500">Créée le</dt>
                    <dd class="text-sm text-gray-700"><?php echo date("d/m/Y H:i", strtotime($order["created_at"])); ?></dd>
                </div>
            </div>

            <!-- Fichier ERP -->
            <div class="border-t pt-3 mt-3">
                <dt class="text-xs font-medium text-gray-500 uppercase mb-2">Fichier ERP</dt>
                <?php if (!empty($order['file_path'])): ?>
                    <dd class="text-xs text-gray-600 font-mono break-all mb-2">
                        <?= htmlspecialchars(basename($order['file_path'])) ?>
                    </dd>
                    <dd>
                        <?php if ($fileExists): ?>
                            <span class="inline-flex items-center text-green-600 text-sm">
                                <i class="fas fa-check-circle mr-1"></i>
                                Fichier présent
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center text-red-600 text-sm">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                Fichier manquant
                            </span>
                        <?php endif; ?>
                    </dd>
                    <?php if (!empty($order['file_generated_at'])): ?>
                    <dd class="text-xs text-gray-500 mt-1">
                        Généré le <?= date('d/m/Y à H:i', strtotime($order['file_generated_at'])) ?>
                    </dd>
                    <?php endif; ?>
                <?php else: ?>
                    <dd class="text-sm text-gray-400">
                        <i class="fas fa-file-excel mr-1"></i>
                        Non généré
                    </dd>
                <?php endif; ?>
            </div>

            <!-- Statut synchro si erreur -->
            <?php if ($order["status"] === "error" && !empty($order["sync_error_message"])): ?>
            <div class="border-t pt-3 mt-3 bg-red-50 -mx-6 -mb-6 px-6 pb-6 rounded-b-lg">
                <div class="text-red-800">
                    <p class="text-sm font-medium">Erreur de synchronisation</p>
                    <p class="text-sm mt-1"><?php echo htmlspecialchars($order["sync_error_message"]); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($order["synced_at"])): ?>
            <div class="flex justify-between items-center">
                <dt class="text-sm text-gray-500">Synchronisée le</dt>
                <dd class="text-sm text-gray-700"><?php echo date("d/m/Y H:i", strtotime($order["synced_at"])); ?></dd>
            </div>
            <?php endif; ?>
        </dl>
    </div>
</div>

<!-- Lignes de commande -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
            <i class="fas fa-list text-gray-500 mr-2"></i>
            Détail de la commande
        </h3>
        <span class="text-sm text-gray-500">
            <?php echo count($orderLines); ?> produit<?php echo count($orderLines) > 1 ? "s" : ""; ?>
        </span>
    </div>

    <?php if (empty($orderLines)): ?>
    <div class="px-6 py-12 text-center text-gray-500">
        <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
        <p>Aucune ligne de commande</p>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produit</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Catégorie</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Quantité</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($orderLines as $line):
                    $productName = htmlspecialchars($line["product_name"] ?? ($line["product_name_fr"] ?? "Produit inconnu"));
                    $productCode = htmlspecialchars($line["product_code"] ?? "N/A");
                    $categoryName = htmlspecialchars($line["category_name"] ?? "Sans catégorie");
                    $categoryColor = $line["category_color"] ?? "#6B7280";
                    $quantity = (int) ($line["quantity"] ?? 0);
                ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <?php if (!empty($line["image_fr"])): ?>
                            <div class="flex-shrink-0 h-10 w-10 mr-3">
                                <img class="h-10 w-10 rounded object-cover" src="<?php echo htmlspecialchars($line["image_fr"]); ?>" alt="<?php echo $productName; ?>">
                            </div>
                            <?php elseif (!empty($line["image_path"])): ?>
                            <div class="flex-shrink-0 h-10 w-10 mr-3">
                                <img class="h-10 w-10 rounded object-cover" src="/stm/<?php echo htmlspecialchars($line["image_path"]); ?>" alt="<?php echo $productName; ?>">
                            </div>
                            <?php else: ?>
                            <div class="flex-shrink-0 h-10 w-10 mr-3 bg-gray-100 rounded flex items-center justify-center">
                                <i class="fas fa-box text-gray-400"></i>
                            </div>
                            <?php endif; ?>
                            <div>
                                <div class="text-sm font-medium text-gray-900"><?php echo $productName; ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 text-xs font-mono text-gray-700">
                            <?php echo $productCode; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background-color: <?php echo $categoryColor; ?>20; color: <?php echo $categoryColor; ?>;">
                            <?php echo $categoryName; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <span class="inline-flex items-center justify-center w-12 h-8 bg-orange-100 text-orange-800 font-bold rounded">
                            <?php echo $quantity; ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="bg-gray-50">
                <tr>
                    <td colspan="3" class="px-6 py-4 text-right text-sm font-medium text-gray-900">
                        Total
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="inline-flex items-center justify-center px-4 py-2 bg-orange-500 text-white font-bold rounded-lg">
                            <?php echo number_format($totalQuantity, 0, ",", " "); ?> promos
                        </span>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Infos techniques -->
<?php if (!empty($order["ip_address"]) || !empty($order["device_type"])): ?>
<div class="mt-6 bg-gray-50 rounded-lg p-4">
    <h4 class="text-sm font-medium text-gray-500 mb-2">Informations techniques</h4>
    <div class="flex flex-wrap gap-4 text-xs text-gray-500">
        <span><i class="fas fa-hashtag mr-1"></i> ID: <?php echo $order["id"]; ?></span>
        <span><i class="fas fa-fingerprint mr-1"></i> UUID: <?php echo htmlspecialchars($order["uuid"] ?? 'N/A'); ?></span>
        <?php if (!empty($order["ip_address"])): ?>
        <span><i class="fas fa-network-wired mr-1"></i> IP: <?php echo htmlspecialchars($order["ip_address"]); ?></span>
        <?php endif; ?>
        <?php if (!empty($order["device_type"])): ?>
        <span><i class="fas fa-<?php echo $order["device_type"] === "mobile" ? "mobile-alt" : ($order["device_type"] === "tablet" ? "tablet-alt" : "desktop"); ?> mr-1"></i> <?php echo ucfirst($order["device_type"]); ?></span>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
$title = $pageTitle;

$pageScripts = "";

require __DIR__ . "/../../layouts/admin.php";
?>