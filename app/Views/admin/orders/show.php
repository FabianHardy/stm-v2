<?php
/**
 * Vue : Détail d'une commande
 *
 * Affiche les informations complètes d'une commande :
 * - Infos générales (numéro, date, statut)
 * - Infos client
 * - Infos campagne
 * - Lignes de commande (produits, quantités)
 *
 * @package    App\Views\admin\orders
 * @author     Fabian Hardy
 * @version    1.0.1
 * @created    2025/11/27
 * @modified   2025/11/27 - Fix colonnes customers (pas de contact_name, phone, address)
 */

ob_start();

// Statuts avec labels et couleurs
$statusLabels = [
    "pending" => ["label" => "En attente", "class" => "bg-yellow-100 text-yellow-800", "icon" => "fa-clock"],
    "validated" => ["label" => "Validée", "class" => "bg-green-100 text-green-800", "icon" => "fa-check-circle"],
    "cancelled" => ["label" => "Annulée", "class" => "bg-red-100 text-red-800", "icon" => "fa-times-circle"],
];
$currentStatus = $statusLabels[$order["status"] ?? "pending"] ?? $statusLabels["pending"];

// Sécurisation des données
$orderNumber = htmlspecialchars($order["order_number"] ?? "N/A");
$campaignName = htmlspecialchars($order["campaign_name"] ?? "N/A");
$companyName = htmlspecialchars($order["company_name"] ?? "N/A");
$customerNumber = htmlspecialchars($order["customer_number"] ?? "N/A");
$customerEmail = htmlspecialchars($order["customer_email"] ?? ($order["customer_email_db"] ?? "N/A"));
$repName = htmlspecialchars($order["rep_name"] ?? "");
$customerLanguage = $order["customer_language"] ?? "fr";
$country = $order["customer_country"] ?? ($order["campaign_country"] ?? "BE");
?>

<!-- En-tête de page -->
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="<?php echo htmlspecialchars(
                $backUrl,
            ); ?>" class="inline-flex items-center text-gray-500 hover:text-gray-700 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Retour
            </a>
            <div class="h-6 w-px bg-gray-300"></div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Commande #<?php echo $orderNumber; ?>
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    Passée le <?php echo date("d/m/Y à H:i", strtotime($order["created_at"])); ?>
                </p>
            </div>
        </div>
        <div>
            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium <?php echo $currentStatus[
                "class"
            ]; ?>">
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
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $country ===
                    "BE"
                        ? "bg-blue-100 text-blue-800"
                        : "bg-yellow-100 text-yellow-800"; ?>">
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
            <div>
                <dt class="text-xs font-medium text-gray-500 uppercase">Période</dt>
                <dd class="text-sm text-gray-700">
                    <?php echo date("d/m/Y", strtotime($order["campaign_start"])); ?>
                    <span class="text-gray-400 mx-1">→</span>
                    <?php echo date("d/m/Y", strtotime($order["campaign_end"])); ?>
                </dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500 uppercase">Pays campagne</dt>
                <dd class="text-sm text-gray-700">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo ($order[
                        "campaign_country"
                    ] ??
                        "BE") ===
                    "BE"
                        ? "bg-blue-100 text-blue-800"
                        : "bg-yellow-100 text-yellow-800"; ?>">
                        <?php echo ($order["campaign_country"] ?? "BE") === "BE" ? "🇧🇪 Belgique" : "🇱🇺 Luxembourg"; ?>
                    </span>
                </dd>
            </div>
            <div class="pt-2">
                <a href="/stm/admin/campaigns/<?php echo (int) $order[
                    "campaign_id"
                ]; ?>" class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800">
                    <i class="fas fa-external-link-alt mr-1"></i>
                    Voir la campagne
                </a>
            </div>
            <?php endif; ?>
        </dl>
    </div>

    <!-- Résumé commande -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="fas fa-shopping-cart text-green-500 mr-2"></i>
            Résumé
        </h3>
        <dl class="space-y-3">
            <div class="flex justify-between items-center">
                <dt class="text-sm text-gray-500">Produits différents</dt>
                <dd class="text-lg font-bold text-gray-900"><?php echo count($orderLines); ?></dd>
            </div>
            <div class="flex justify-between items-center">
                <dt class="text-sm text-gray-500">Quantité totale</dt>
                <dd class="text-lg font-bold text-orange-600"><?php echo number_format(
                    $totalQuantity,
                    0,
                    ",",
                    " ",
                ); ?> promos</dd>
            </div>
            <div class="border-t pt-3 mt-3">
                <div class="flex justify-between items-center">
                    <dt class="text-sm text-gray-500">Créée le</dt>
                    <dd class="text-sm text-gray-700"><?php echo date(
                        "d/m/Y H:i",
                        strtotime($order["created_at"]),
                    ); ?></dd>
                </div>
            </div>
            <?php if (!empty($order["file_path"])): ?>
            <div class="flex justify-between items-center">
                <dt class="text-sm text-gray-500">Fichier ERP</dt>
                <dd class="text-sm">
                    <span class="inline-flex items-center text-green-600">
                        <i class="fas fa-check-circle mr-1"></i>
                        Généré
                    </span>
                </dd>
            </div>
            <?php endif; ?>
            <?php if ($order["status"] === "cancelled" && !empty($order["cancelled_at"])): ?>
            <div class="border-t pt-3 mt-3 bg-red-50 -mx-6 -mb-6 px-6 pb-6 rounded-b-lg">
                <div class="text-red-800">
                    <p class="text-sm font-medium">Annulée le <?php echo date(
                        "d/m/Y H:i",
                        strtotime($order["cancelled_at"]),
                    ); ?></p>
                    <?php if (!empty($order["cancelled_reason"])): ?>
                    <p class="text-sm mt-1"><?php echo htmlspecialchars($order["cancelled_reason"]); ?></p>
                    <?php endif; ?>
                </div>
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

                    $productName = htmlspecialchars(
                        $line["product_name"] ?? ($line["product_name_fr"] ?? "Produit inconnu"),
                    );
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
                                <img class="h-10 w-10 rounded object-cover" src="<?php echo htmlspecialchars(
                                    $line["image_fr"],
                                ); ?>" alt="<?php echo $productName; ?>">
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
                <?php
                endforeach; ?>
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

<!-- Infos techniques (optionnel, affiché si dispo) -->
<?php if (!empty($order["ip_address"]) || !empty($order["device_type"])): ?>
<div class="mt-6 bg-gray-50 rounded-lg p-4">
    <h4 class="text-sm font-medium text-gray-500 mb-2">Informations techniques</h4>
    <div class="flex flex-wrap gap-4 text-xs text-gray-500">
        <?php if (!empty($order["ip_address"])): ?>
        <span><i class="fas fa-network-wired mr-1"></i> IP: <?php echo htmlspecialchars($order["ip_address"]); ?></span>
        <?php endif; ?>
        <?php if (!empty($order["device_type"])): ?>
        <span><i class="fas fa-<?php echo $order["device_type"] === "mobile"
            ? "mobile-alt"
            : ($order["device_type"] === "tablet"
                ? "tablet-alt"
                : "desktop"); ?> mr-1"></i> <?php echo ucfirst($order["device_type"]); ?></span>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
$title = "Commande #" . $orderNumber;

// Pas de scripts spécifiques pour cette page
$pageScripts = "";

require __DIR__ . "/../../layouts/admin.php";

?>
