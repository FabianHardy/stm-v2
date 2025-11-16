<?php
/**
 * Vue : Page de confirmation de commande
 * 
 * Affichée après la validation réussie d'une commande
 * 
 * @package STM
 * @created 17/11/2025
 */

// Vérifier que la commande existe en session
if (!isset($_SESSION['last_order_uuid']) || !isset($_SESSION['public_customer'])) {
    header('Location: /stm/');
    exit;
}

$customer = $_SESSION['public_customer'];
$orderUuid = $_SESSION['last_order_uuid'];

// Récupérer les détails de la commande
$db = \Core\Database::getInstance();

try {
    $query = "SELECT o.*, c.name as campaign_name, c.unique_url
              FROM orders o
              JOIN campaigns c ON o.campaign_id = c.id
              WHERE o.uuid = :uuid
              LIMIT 1";
    
    $order = $db->queryOne($query, [':uuid' => $orderUuid]);

    if (!$order) {
        throw new \Exception("Commande introuvable");
    }

    // Récupérer les lignes de commande
    $queryLines = "SELECT * FROM order_lines WHERE order_id = :order_id ORDER BY id";
    $orderLines = $db->query($queryLines, [':order_id' => $order['id']]);

} catch (\PDOException $e) {
    error_log("Erreur confirmation: " . $e->getMessage());
    header('Location: /stm/');
    exit;
}

// Nettoyer la session
unset($_SESSION['last_order_uuid']);

ob_start();
?>

<!-- Animation de succès -->
<div class="min-h-screen bg-gradient-to-br from-green-50 to-blue-50 flex items-center justify-center px-4 py-8">
    <div class="max-w-2xl w-full">
        
        <!-- Carte de confirmation -->
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            
            <!-- En-tête avec icône de succès -->
            <div class="bg-gradient-to-r from-green-500 to-green-600 p-8 text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-full mb-4 animate-bounce">
                    <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">
                    <?= $customer['language'] === 'fr' ? 'Commande validée !' : 'Bestelling gevalideerd!' ?>
                </h1>
                <p class="text-green-100 text-lg">
                    <?= $customer['language'] === 'fr' 
                        ? 'Merci pour votre confiance' 
                        : 'Bedankt voor uw vertrouwen' ?>
                </p>
            </div>

            <!-- Contenu -->
            <div class="p-8">
                
                <!-- Message de confirmation -->
                <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">
                    <p class="text-green-800 text-center">
                        <?= $customer['language'] === 'fr' 
                            ? 'Votre commande a été enregistrée avec succès. Un email de confirmation vous a été envoyé.' 
                            : 'Uw bestelling is succesvol geregistreerd. Er is een bevestigingsmail naar u verzonden.' ?>
                    </p>
                </div>

                <!-- Détails de la commande -->
                <div class="space-y-4 mb-6">
                    
                    <!-- Référence commande -->
                    <div class="flex justify-between items-center py-3 border-b border-gray-200">
                        <span class="text-gray-600">
                            <?= $customer['language'] === 'fr' ? 'Référence' : 'Referentie' ?>
                        </span>
                        <span class="font-mono font-bold text-blue-600">
                            <?= htmlspecialchars(strtoupper(substr($order['uuid'], 0, 8))) ?>
                        </span>
                    </div>

                    <!-- Campagne -->
                    <div class="flex justify-between items-center py-3 border-b border-gray-200">
                        <span class="text-gray-600">
                            <?= $customer['language'] === 'fr' ? 'Campagne' : 'Campagne' ?>
                        </span>
                        <span class="font-semibold text-gray-800">
                            <?= htmlspecialchars($order['campaign_name']) ?>
                        </span>
                    </div>

                    <!-- Numéro client -->
                    <div class="flex justify-between items-center py-3 border-b border-gray-200">
                        <span class="text-gray-600">
                            <?= $customer['language'] === 'fr' ? 'Numéro client' : 'Klantnummer' ?>
                        </span>
                        <span class="font-semibold text-gray-800">
                            <?= htmlspecialchars($customer['customer_number']) ?>
                        </span>
                    </div>

                    <!-- Email -->
                    <div class="flex justify-between items-center py-3 border-b border-gray-200">
                        <span class="text-gray-600">Email</span>
                        <span class="font-semibold text-gray-800">
                            <?= htmlspecialchars($order['customer_email']) ?>
                        </span>
                    </div>

                    <!-- Date -->
                    <div class="flex justify-between items-center py-3 border-b border-gray-200">
                        <span class="text-gray-600">Date</span>
                        <span class="font-semibold text-gray-800">
                            <?php
                            $orderDate = new DateTime($order['created_at']);
                            echo $orderDate->format('d/m/Y H:i');
                            ?>
                        </span>
                    </div>
                </div>

                <!-- Récapitulatif produits -->
                <div class="mb-6">
                    <h3 class="font-bold text-gray-800 mb-4">
                        <?= $customer['language'] === 'fr' ? 'Produits commandés' : 'Bestelde producten' ?>
                    </h3>
                    <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                        <?php foreach ($orderLines as $line): ?>
                            <div class="flex justify-between items-center">
                                <div class="flex-1">
                                    <p class="font-medium text-gray-800">
                                        <?= htmlspecialchars($line['product_name']) ?>
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        <?= $customer['language'] === 'fr' ? 'Code' : 'Code' ?>: 
                                        <?= htmlspecialchars($line['product_code']) ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span class="text-2xl font-bold text-blue-600">
                                        <?= $line['quantity'] ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Total -->
                        <div class="pt-3 border-t border-gray-300 flex justify-between items-center">
                            <span class="font-bold text-gray-800">
                                <?= $customer['language'] === 'fr' ? 'Total articles' : 'Totaal artikelen' ?>
                            </span>
                            <span class="text-2xl font-bold text-blue-600">
                                <?= $order['total_items'] ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Informations complémentaires -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="text-sm text-blue-800">
                            <?= $customer['language'] === 'fr' 
                                ? '<strong>Prochaines étapes :</strong><br>Votre commande a été transmise à notre service logistique. Vous serez informé de la date de livraison par email.' 
                                : '<strong>Volgende stappen:</strong><br>Uw bestelling is doorgestuurd naar onze logistieke dienst. U wordt per e-mail geïnformeerd over de leveringsdatum.' ?>
                        </div>
                    </div>
                </div>

                <!-- Boutons d'action -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="/stm/c/<?= htmlspecialchars($order['unique_url']) ?>/catalog" 
                       class="flex-1 bg-blue-600 text-white text-center py-3 px-6 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                        <?= $customer['language'] === 'fr' ? 'Nouvelle commande' : 'Nieuwe bestelling' ?>
                    </a>
                    <button onclick="window.print()" 
                            class="flex-1 bg-gray-200 text-gray-700 text-center py-3 px-6 rounded-lg font-semibold hover:bg-gray-300 transition-colors">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        <?= $customer['language'] === 'fr' ? 'Imprimer' : 'Afdrukken' ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <p class="text-center text-gray-500 text-sm mt-6">
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            <?= $customer['language'] === 'fr' 
                ? 'Un email récapitulatif vous a été envoyé' 
                : 'Er is een overzichtsmail naar u verzonden' ?>
        </p>
    </div>
</div>

<style>
@media print {
    .bg-gradient-to-br { background: white !important; }
    button { display: none !important; }
}
</style>

<?php
$content = ob_get_clean();
$title = ($customer['language'] === 'fr' ? 'Confirmation de commande' : 'Bestelbevestiging') . ' - Trendy Foods';

// Layout simplifié pour interface publique
require __DIR__ . '/../../layouts/admin.php';
?>