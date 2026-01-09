<?php
/**
 * Vue confirmation commande prospect
 *
 * Sprint 16 : Mode Prospect
 * Page de confirmation après soumission de commande
 *
 * @created    2026/01/09
 */

// Variables attendues :
// $campaign, $order, $orderLines, $lang

$lang = $lang ?? 'fr';
$campaignTitle = $lang === 'nl' ? ($campaign['title_nl'] ?? $campaign['name']) : ($campaign['title_fr'] ?? $campaign['name']);

$t = [
    'fr' => [
        'title' => 'Commande confirmée !',
        'thank_you' => 'Merci pour votre commande',
        'order_number' => 'Numéro de commande',
        'prospect_number' => 'Votre numéro prospect',
        'total_items' => 'Total articles',
        'summary' => 'Récapitulatif',
        'product' => 'Produit',
        'quantity' => 'Quantité',
        'next_steps' => 'Prochaines étapes',
        'next_steps_text' => 'Notre équipe commerciale vous contactera prochainement pour finaliser votre inscription en tant que nouveau client Trendy Foods.',
        'email_sent' => 'Un email de confirmation a été envoyé à',
        'back_home' => 'Retour à l\'accueil',
        'new_order' => 'Passer une autre commande',
    ],
    'nl' => [
        'title' => 'Bestelling bevestigd!',
        'thank_you' => 'Bedankt voor uw bestelling',
        'order_number' => 'Bestelnummer',
        'prospect_number' => 'Uw prospectnummer',
        'total_items' => 'Totaal artikelen',
        'summary' => 'Samenvatting',
        'product' => 'Product',
        'quantity' => 'Aantal',
        'next_steps' => 'Volgende stappen',
        'next_steps_text' => 'Ons commercieel team zal binnenkort contact met u opnemen om uw registratie als nieuwe klant van Trendy Foods te voltooien.',
        'email_sent' => 'Een bevestigingsmail is verstuurd naar',
        'back_home' => 'Terug naar home',
        'new_order' => 'Nog een bestelling plaatsen',
    ],
][$lang] ?? $t['fr'];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['title'] ?> - <?= htmlspecialchars($campaignTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-2xl mx-auto py-8 px-4">
        <!-- Success Icon -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mb-4">
                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            
            <h1 class="text-2xl font-bold text-gray-900"><?= $t['title'] ?></h1>
            <p class="text-gray-600 mt-2"><?= $t['thank_you'] ?></p>
        </div>

        <!-- Infos commande -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <p class="text-sm text-gray-500"><?= $t['order_number'] ?></p>
                    <p class="font-semibold text-lg text-purple-600"><?= htmlspecialchars($order['order_number'] ?? '') ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500"><?= $t['prospect_number'] ?></p>
                    <p class="font-semibold text-lg"><?= htmlspecialchars($order['prospect_number'] ?? '') ?></p>
                </div>
            </div>

            <div class="border-t pt-4">
                <p class="text-sm text-gray-500"><?= $t['total_items'] ?></p>
                <p class="font-semibold text-2xl"><?= $order['total_items'] ?? 0 ?></p>
            </div>
        </div>

        <!-- Récapitulatif -->
        <?php if (!empty($orderLines)): ?>
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h2 class="font-semibold text-lg mb-4"><?= $t['summary'] ?></h2>
            
            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="text-left py-2 text-sm text-gray-500"><?= $t['product'] ?></th>
                        <th class="text-right py-2 text-sm text-gray-500"><?= $t['quantity'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderLines as $line): ?>
                    <tr class="border-b">
                        <td class="py-3"><?= htmlspecialchars($line['product_name']) ?></td>
                        <td class="py-3 text-right font-medium"><?= $line['quantity'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Prochaines étapes -->
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-6 mb-6">
            <h3 class="font-semibold text-purple-800 mb-2">📋 <?= $t['next_steps'] ?></h3>
            <p class="text-purple-700 text-sm"><?= $t['next_steps_text'] ?></p>
        </div>

        <!-- Email envoyé -->
        <?php if (!empty($order['customer_email'])): ?>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <p class="text-blue-700 text-sm">
                ✉️ <?= $t['email_sent'] ?> <strong><?= htmlspecialchars($order['customer_email']) ?></strong>
            </p>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="flex gap-4">
            <a href="/stm/c/<?= htmlspecialchars($campaign['uuid']) ?>/prospect/catalog"
               class="flex-1 text-center bg-purple-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-purple-700 transition">
                🛒 <?= $t['new_order'] ?>
            </a>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 text-sm text-gray-500">
            <p>© <?= date('Y') ?> Trendy Foods</p>
        </div>
    </div>
</body>
</html>
