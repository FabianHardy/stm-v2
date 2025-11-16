<?php
/**
 * Vue : Page de validation de commande (Checkout)
 * 
 * Affiche le récapitulatif du panier avec formulaire de validation
 * (email + CGV obligatoires + date livraison si applicable)
 * 
 * @package STM
 * @created 17/11/2025
 */

// Vérifier que les variables nécessaires sont définies
if (!isset($campaign, $customer, $cart)) {
    header('Location: /stm/');
    exit;
}

ob_start();
?>

<!-- En-tête de la page -->
<div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-lg">
    <div class="container mx-auto px-4 py-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold"><?= htmlspecialchars($campaign['title_' . $customer['language']]) ?></h1>
                <p class="text-blue-100 text-sm mt-1">
                    <?= $customer['language'] === 'fr' ? 'Validation de votre commande' : 'Validatie van uw bestelling' ?>
                </p>
            </div>
            <div class="text-right">
                <p class="text-blue-100 text-sm">
                    <?= $customer['language'] === 'fr' ? 'Client N°' : 'Klant Nr.' ?>
                </p>
                <p class="text-xl font-bold"><?= htmlspecialchars($customer['customer_number']) ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Contenu principal -->
<div class="container mx-auto px-4 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Colonne gauche : Récapitulatif panier -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <?= $customer['language'] === 'fr' ? 'Récapitulatif de votre commande' : 'Overzicht van uw bestelling' ?>
                </h2>

                <?php if (empty($cart['items'])): ?>
                    <!-- Panier vide -->
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <p class="text-gray-500 mb-4">
                            <?= $customer['language'] === 'fr' ? 'Votre panier est vide' : 'Uw winkelwagen is leeg' ?>
                        </p>
                        <a href="/stm/c/<?= $campaign['unique_url'] ?>/catalog" 
                           class="inline-block bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                            <?= $customer['language'] === 'fr' ? 'Retour au catalogue' : 'Terug naar catalogus' ?>
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Liste des produits -->
                    <div class="space-y-4">
                        <?php foreach ($cart['items'] as $item): ?>
                            <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <!-- Image produit -->
                                <?php if (!empty($item['image_fr'])): ?>
                                    <img src="<?= htmlspecialchars($item['image_fr']) ?>" 
                                         alt="<?= htmlspecialchars($item['product_name']) ?>"
                                         class="w-20 h-20 object-cover rounded">
                                <?php else: ?>
                                    <div class="w-20 h-20 bg-gray-200 rounded flex items-center justify-center">
                                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>

                                <!-- Détails produit -->
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-800">
                                        <?= htmlspecialchars($item['product_name']) ?>
                                    </h3>
                                    <p class="text-sm text-gray-500">
                                        <?= $customer['language'] === 'fr' ? 'Code' : 'Code' ?>: 
                                        <?= htmlspecialchars($item['product_code']) ?>
                                    </p>
                                </div>

                                <!-- Quantité -->
                                <div class="text-right">
                                    <p class="text-sm text-gray-600">
                                        <?= $customer['language'] === 'fr' ? 'Quantité' : 'Hoeveelheid' ?>
                                    </p>
                                    <p class="text-2xl font-bold text-blue-600">
                                        <?= $item['quantity'] ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Total articles -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-semibold text-gray-700">
                                <?= $customer['language'] === 'fr' ? 'Total articles' : 'Totaal artikelen' ?>
                            </span>
                            <span class="text-2xl font-bold text-blue-600">
                                <?php
                                $totalItems = array_sum(array_column($cart['items'], 'quantity'));
                                echo $totalItems;
                                ?>
                            </span>
                        </div>
                    </div>

                    <!-- Bouton retour catalogue -->
                    <div class="mt-6">
                        <a href="/stm/c/<?= $campaign['unique_url'] ?>/catalog" 
                           class="inline-flex items-center text-blue-600 hover:text-blue-700">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            <?= $customer['language'] === 'fr' ? 'Modifier ma commande' : 'Mijn bestelling wijzigen' ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Colonne droite : Formulaire de validation -->
        <?php if (!empty($cart['items'])): ?>
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">
                        <?= $customer['language'] === 'fr' ? 'Informations de commande' : 'Bestelgegevens' ?>
                    </h2>

                    <form id="checkoutForm" method="POST" action="/stm/c/<?= $campaign['unique_url'] ?>/order/submit">
                        <!-- Token CSRF -->
                        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">

                        <!-- Email obligatoire -->
                        <div class="mb-6">
                            <label for="customer_email" class="block text-sm font-medium text-gray-700 mb-2">
                                <?= $customer['language'] === 'fr' ? 'Email de confirmation' : 'Bevestigings-e-mail' ?>
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="email" 
                                   id="customer_email" 
                                   name="customer_email" 
                                   required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="<?= $customer['language'] === 'fr' ? 'votre@email.com' : 'uw@email.com' ?>">
                        </div>

                        <!-- Date de livraison (si deferred_delivery = 1) -->
                        <?php if ($campaign['deferred_delivery'] == 1 && !empty($campaign['delivery_date'])): ?>
                            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-blue-900">
                                            <?= $customer['language'] === 'fr' ? 'Date de livraison prévue' : 'Geplande leveringsdatum' ?>
                                        </p>
                                        <p class="text-lg font-bold text-blue-700 mt-1">
                                            <?php
                                            $deliveryDate = new DateTime($campaign['delivery_date']);
                                            $locale = $customer['language'] === 'fr' ? 'fr_FR' : 'nl_NL';
                                            echo strftime('%d %B %Y', $deliveryDate->getTimestamp());
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Conditions Générales de Vente (3 checkboxes) -->
                        <div class="mb-6 space-y-3">
                            <p class="text-sm font-medium text-gray-700 mb-3">
                                <?= $customer['language'] === 'fr' ? 'Conditions de commande' : 'Bestelvoorwaarden' ?>
                                <span class="text-red-500">*</span>
                            </p>

                            <!-- CGV 1 -->
                            <label class="flex items-start cursor-pointer">
                                <input type="checkbox" 
                                       name="cgv_1" 
                                       required
                                       class="mt-1 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">
                                    <?= $customer['language'] === 'fr' 
                                        ? "J'accepte les conditions générales de vente" 
                                        : "Ik aanvaard de algemene verkoopvoorwaarden" ?>
                                </span>
                            </label>

                            <!-- CGV 2 -->
                            <label class="flex items-start cursor-pointer">
                                <input type="checkbox" 
                                       name="cgv_2" 
                                       required
                                       class="mt-1 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">
                                    <?= $customer['language'] === 'fr' 
                                        ? "Je confirme avoir vérifié ma commande et les quantités" 
                                        : "Ik bevestig dat ik mijn bestelling en de hoeveelheden heb gecontroleerd" ?>
                                </span>
                            </label>

                            <!-- CGV 3 -->
                            <label class="flex items-start cursor-pointer">
                                <input type="checkbox" 
                                       name="cgv_3" 
                                       required
                                       class="mt-1 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">
                                    <?= $customer['language'] === 'fr' 
                                        ? "Je comprends que cette commande est définitive et ne peut être annulée" 
                                        : "Ik begrijp dat deze bestelling definitief is en niet kan worden geannuleerd" ?>
                                </span>
                            </label>
                        </div>

                        <!-- Bouton de validation -->
                        <button type="submit" 
                                class="w-full bg-green-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-green-700 transition-colors shadow-lg hover:shadow-xl">
                            <span class="flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <?= $customer['language'] === 'fr' ? 'Confirmer ma commande' : 'Mijn bestelling bevestigen' ?>
                            </span>
                        </button>

                        <!-- Note de sécurité -->
                        <p class="text-xs text-gray-500 mt-4 text-center">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            <?= $customer['language'] === 'fr' 
                                ? 'Vos données sont sécurisées' 
                                : 'Uw gegevens zijn beveiligd' ?>
                        </p>
                    </form>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- Script de validation côté client -->
<script>
document.getElementById('checkoutForm')?.addEventListener('submit', function(e) {
    // Vérifier que toutes les CGV sont cochées
    const cgv1 = document.querySelector('input[name="cgv_1"]');
    const cgv2 = document.querySelector('input[name="cgv_2"]');
    const cgv3 = document.querySelector('input[name="cgv_3"]');
    
    if (!cgv1.checked || !cgv2.checked || !cgv3.checked) {
        e.preventDefault();
        alert('<?= $customer['language'] === 'fr' 
            ? "Veuillez accepter toutes les conditions pour continuer" 
            : "Gelieve alle voorwaarden te aanvaarden om verder te gaan" ?>');
        return false;
    }
    
    // Vérifier l'email
    const email = document.getElementById('customer_email').value;
    if (!email || !email.includes('@')) {
        e.preventDefault();
        alert('<?= $customer['language'] === 'fr' 
            ? "Veuillez saisir une adresse email valide" 
            : "Gelieve een geldig e-mailadres in te voeren" ?>');
        return false;
    }
    
    // Confirmation finale
    if (!confirm('<?= $customer['language'] === 'fr' 
        ? "Êtes-vous sûr de vouloir confirmer votre commande ?" 
        : "Bent u zeker dat u uw bestelling wilt bevestigen?" ?>')) {
        e.preventDefault();
        return false;
    }
});
</script>

<?php
$content = ob_get_clean();
$title = ($customer['language'] === 'fr' ? 'Validation de commande' : 'Validatie bestelling') . ' - ' . htmlspecialchars($campaign['name']);

// Layout simplifié pour interface publique (pas de sidebar admin)
require __DIR__ . '/../../layouts/admin.php';
?>