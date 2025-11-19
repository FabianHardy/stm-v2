<?php
/**
 * Vue : Page de validation de commande (checkout)
 * 
 * Permet au client de valider sa commande en fournissant son email
 * et en acceptant les conditions générales
 * 
 * @package STM
 * @created 17/11/2025
 * @modified 19/11/2025 - Harmonisation header avec catalog.php + Bande orange titre agrandi
 */

// Vérifier que l'utilisateur a bien une session client
if (!isset($_SESSION['public_customer'])) {
    header('Location: /stm/');
    exit;
}

$customer = $_SESSION['public_customer'];
?>
<!DOCTYPE html>
<html lang="<?= $customer['language'] ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $customer['language'] === 'fr' ? 'Validation de commande' : 'Validatie bestelling' ?> - <?= htmlspecialchars($campaign['name']) ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* Alpine.js cloak */
        [x-cloak] {
            display: none !important;
        }

        body {
            position: relative;
        }

        /* Fond Trendy Foods en bas à droite */
body::before {
    content: '';
    position: fixed;
    bottom: 0;
    right: 0;
    width: 400px;
    height: 400px;
    background: url('/stm/assets/images/fond.png') no-repeat;
    background-size: contain;
    background-position: bottom right;  /* ✅ AJOUTÉ - Collé au coin */
    opacity: 0.6;
    pointer-events: none;
    z-index: 20;  /* ✅ MODIFIÉ - Au-dessus du footer (z-10) */
}

        /* Contenu principal au-dessus du fond */
        .content-wrapper {
            position: relative;
            z-index: 1;
        }
    </style>
</head>
<body class="bg-gray-50" x-data="{ showCGU: false, showRGPD: false }">

    <div class="content-wrapper">
        <!-- Header blanc avec logo + infos (identique catalog.php) -->
        <header class="bg-white shadow-md sticky top-0 z-40">
            <div class="container mx-auto px-4 py-4">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <!-- Logo Trendy Foods -->
                        <img src="/stm/assets/images/logo.png" 
                             alt="Trendy Foods" 
                             class="h-12"
                             onerror="this.style.display='none'">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($campaign['name']) ?></h1>
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-building mr-1"></i>
                                <?= htmlspecialchars($customer['company_name']) ?>
                                <span class="mx-2">•</span>
                                <?= htmlspecialchars($customer['customer_number']) ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <!-- Switch langue FR/NL (visible uniquement pour BE) -->
                        <?php if ($customer['country'] === 'BE'): ?>
                        <div class="hidden lg:flex bg-gray-100 rounded-lg p-1">
                            <button onclick="window.location.href='?lang=fr'" 
                                    class="px-4 py-2 rounded-md <?= $customer['language'] === 'fr' ? 'bg-white text-blue-600 font-semibold shadow-sm' : 'text-gray-600 hover:bg-white hover:shadow-sm' ?> transition">
                                FR
                            </button>
                            <button onclick="window.location.href='?lang=nl'" 
                                    class="px-4 py-2 rounded-md <?= $customer['language'] === 'nl' ? 'bg-white text-blue-600 font-semibold shadow-sm' : 'text-gray-600 hover:bg-white hover:shadow-sm' ?> transition">
                                NL
                            </button>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Déconnexion -->
                        <a href="/stm/c/<?= $campaign['uuid'] ?>" 
                           class="hidden lg:block text-gray-600 hover:text-gray-800 transition">
                            <i class="fas fa-sign-out-alt mr-2"></i>
                            <?= $customer['language'] === 'fr' ? 'Déconnexion' : 'Afmelden' ?>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Bande orange avec titre centré (remplace la barre catégories) -->
        <div class="bg-gradient-to-r from-orange-600 to-orange-700 text-white shadow-lg sticky top-[72px] z-30" 
             style="background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);">
            <div class="container mx-auto px-4 py-6">
                <div class="flex items-center justify-between relative">
                    <!-- Bouton retour à gauche -->
                    <a href="/stm/c/<?= $campaign['uuid'] ?>/catalog" 
                       class="flex items-center text-white hover:text-orange-100 transition font-semibold">
                        <i class="fas fa-arrow-left mr-2"></i>
                        <?= $customer['language'] === 'fr' ? 'Retour' : 'Terug' ?>
                    </a>
                    
                    <!-- Titre centré (position absolue) -->
                    <div class="absolute left-1/2 transform -translate-x-1/2">
                        <h2 class="text-4xl font-bold flex items-center whitespace-nowrap">
                            <i class="fas fa-check-circle mr-3"></i>
                            <?= $customer['language'] === 'fr' ? 'Validation de votre commande' : 'Validatie van uw bestelling' ?>
                        </h2>
                    </div>
                    
                    <!-- Espace vide à droite pour équilibre -->
                    <div class="w-24"></div>
                </div>
            </div>
        </div>

        <!-- Messages d'erreur/succès -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="container mx-auto px-4 mt-6">
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-lg shadow-md flex items-start">
                    <svg class="w-6 h-6 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span><?= htmlspecialchars($_SESSION['error']) ?></span>
                </div>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="container mx-auto px-4 mt-6">
                <div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-6 py-4 rounded-lg shadow-md flex items-start">
                    <svg class="w-6 h-6 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span><?= htmlspecialchars($_SESSION['success']) ?></span>
                </div>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Contenu principal -->
        <div class="container mx-auto px-4 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Colonne gauche : Récapitulatif panier (2/3) -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <?= $customer['language'] === 'fr' ? 'Récapitulatif de votre commande' : 'Overzicht van uw bestelling' ?>
                        </h2>

                        <?php if (empty($cart['items'])): ?>
                            <div class="text-center py-12">
                                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <p class="text-gray-500 mb-4">
                                    <?= $customer['language'] === 'fr' ? 'Votre panier est vide' : 'Uw winkelwagen is leeg' ?>
                                </p>
                                <a href="/stm/c/<?= $campaign['uuid'] ?>/catalog" 
                                   class="inline-block bg-orange-600 text-white px-6 py-2 rounded-lg hover:bg-orange-700 transition">
                                    <?= $customer['language'] === 'fr' ? 'Retour au catalogue' : 'Terug naar catalogus' ?>
                                </a>
                            </div>
                        <?php else: ?>
                            <!-- Liste des produits -->
                            <div class="space-y-4 mb-6">
                                <?php foreach ($cart['items'] as $item): ?>
                                    <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                        <!-- Image produit -->
                                        <?php if (!empty($item['image_' . $customer['language']])): ?>
                                            <img src="<?= htmlspecialchars($item['image_' . $customer['language']]) ?>" 
                                                 alt="<?= htmlspecialchars($item['name_' . $customer['language']]) ?>"
                                                 class="w-24 h-24 object-contain rounded">
                                        <?php else: ?>
                                            <div class="w-24 h-24 bg-gray-200 rounded flex items-center justify-center">
                                                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Infos produit -->
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-gray-800">
                                                <?= htmlspecialchars($item['name_' . $customer['language']]) ?>
                                            </h3>
                                        </div>

                                        <!-- Quantité -->
                                        <div class="text-right">
                                            <p class="text-sm text-gray-600">
                                                <?= $customer['language'] === 'fr' ? 'Quantité' : 'Hoeveelheid' ?>
                                            </p>
                                            <p class="text-2xl font-bold text-orange-600">
                                                <?= $item['quantity'] ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Total articles -->
                            <div class="mt-6 pt-6 border-t border-gray-200">
                                <div class="flex justify-between items-center text-lg">
                                    <span class="font-semibold text-gray-700">
                                        <?= $customer['language'] === 'fr' ? 'Total articles' : 'Totaal artikelen' ?> :
                                    </span>
                                    <span class="text-2xl font-bold text-orange-600">
                                        <?= array_sum(array_column($cart['items'], 'quantity')) ?>
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Colonne droite : Formulaire de validation (1/3) -->
                <?php if (!empty($cart['items'])): ?>
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-md p-6 sticky top-24">
                        <h2 class="text-xl font-bold text-gray-800 mb-6">
                            <?= $customer['language'] === 'fr' ? 'Finaliser la commande' : 'Bestelling afronden' ?>
                        </h2>

                        <form method="POST" action="/stm/c/<?= $campaign['uuid'] ?>/order/submit" id="checkoutForm">
                            <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

                            <!-- Email -->
                            <div class="mb-6">
                                <label for="customer_email" class="block text-sm font-medium text-gray-700 mb-2">
                                    <?= $customer['language'] === 'fr' ? 'Adresse email' : 'E-mailadres' ?>
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="email" 
                                       id="customer_email" 
                                       name="customer_email" 
                                       required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                       placeholder="exemple@email.com">
                                <p class="mt-1 text-xs text-gray-500">
                                    <?= $customer['language'] === 'fr' 
                                        ? 'Vous recevrez une confirmation à cette adresse' 
                                        : 'U ontvangt een bevestiging op dit adres' ?>
                                </p>
                            </div>

                            <!-- Date de livraison si applicable -->
                            <?php if ($campaign['deferred_delivery'] == 1 && !empty($campaign['delivery_date'])): ?>
                                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                    <div class="flex items-start">
                                        <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <div>
                                            <p class="text-sm font-semibold text-blue-900">
                                                <?= $customer['language'] === 'fr' ? 'Livraison à partir du' : 'Levering vanaf' ?>
                                            </p>
                                            <p class="text-sm text-blue-700 mt-1">
                                                <?php
                                                $deliveryDate = new DateTime($campaign['delivery_date']);
                                                
                                                // Traduction des mois
                                                $monthsFr = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
                                                $monthsNl = ['januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december'];
                                                
                                                $day = $deliveryDate->format('d');
                                                $monthIndex = (int)$deliveryDate->format('m') - 1;
                                                $year = $deliveryDate->format('Y');
                                                
                                                $monthName = $customer['language'] === 'fr' ? $monthsFr[$monthIndex] : $monthsNl[$monthIndex];
                                                echo "$day $monthName $year";
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

                                <!-- CGV 1 : CGU + RGPD avec liens cliquables -->
                                <label class="flex items-start cursor-pointer">
                                    <input type="checkbox" 
                                           name="cgv_1" 
                                           required
                                           class="mt-1 h-4 w-4 text-orange-600 border-gray-300 rounded focus:ring-orange-500">
                                    <span class="ml-3 text-sm text-gray-700">
                                        <?php if ($customer['language'] === 'fr'): ?>
                                            J'accepte les 
                                            <button type="button" 
                                                    @click.prevent="showCGU = true"
                                                    class="text-blue-600 hover:text-blue-800 underline font-medium">
                                                Conditions Générales d'Utilisation
                                            </button>
                                            et la 
                                            <button type="button" 
                                                    @click.prevent="showRGPD = true"
                                                    class="text-blue-600 hover:text-blue-800 underline font-medium">
                                                Politique Vie Privée
                                            </button>
                                        <?php else: ?>
                                            Ik aanvaard de 
                                            <button type="button" 
                                                    @click.prevent="showCGU = true"
                                                    class="text-blue-600 hover:text-blue-800 underline font-medium">
                                                Algemene Gebruiksvoorwaarden
                                            </button>
                                            en het 
                                            <button type="button" 
                                                    @click.prevent="showRGPD = true"
                                                    class="text-blue-600 hover:text-blue-800 underline font-medium">
                                                Privacybeleid
                                            </button>
                                        <?php endif; ?>
                                    </span>
                                </label>

                                <!-- CGV 2 -->
                                <label class="flex items-start cursor-pointer">
                                    <input type="checkbox" 
                                           name="cgv_2" 
                                           required
                                           class="mt-1 h-4 w-4 text-orange-600 border-gray-300 rounded focus:ring-orange-500">
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
                                           class="mt-1 h-4 w-4 text-orange-600 border-gray-300 rounded focus:ring-orange-500">
                                    <span class="ml-3 text-sm text-gray-700">
                                        <?= $customer['language'] === 'fr' 
                                            ? "Je comprends que cette commande est définitive et ne peut être annulée" 
                                            : "Ik begrijp dat deze bestelling definitief is en niet kan worden geannuleerd" ?>
                                    </span>
                                </label>
                            </div>

                            <!-- Bouton de validation VERT -->
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
    </div>

    <!-- Modal CGU -->
    <div x-show="showCGU" 
         x-cloak
         @click.away="showCGU = false"
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Overlay -->
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" 
                 @click="showCGU = false"></div>

            <!-- Modal Content -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <!-- Header -->
                <div class="bg-blue-600 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white">
                        <?= $customer['language'] === 'fr' ? "Conditions générales d'utilisation du Site internet" : "Algemene gebruiksvoorwaarden van de website" ?>
                    </h3>
                    <button @click="showCGU = false" 
                            class="text-white hover:text-gray-200">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="bg-white px-6 py-6 max-h-96 overflow-y-auto">
                    <div class="prose prose-sm max-w-none">
                        <?php if ($customer['language'] === 'fr'): ?>
                            <p>Ce site Internet (à l'exclusion des sites liés) est géré par TRENDY FOODS BELGIUM SA.</p>
                            
                            <h3 class="text-lg font-bold mt-4 mb-2">Article 1 : Consentement à être lié par ces conditions</h3>
                            <p>Votre utilisation de ce site internet et de tous les logiciels, applications, données, produits, concours, tombolas, ou de tout autre service offert sur ce site, au départ ou par l'intermédiaire de celui-ci par TRENDY FOODS BELGIUM SA (collectivement dénommés « les Services TRENDY FOODS BELGIUM SA »), est soumise aux conditions générales d'utilisation conclues entre vous et TRENDY FOODS BELGIUM SA.</p>
                            <p>Outre les conditions juridiques, la convention juridique inclut (i) la Politique de respect de la vie privée et (ii) les conditions générales de vente.</p>
                            
                            <h3 class="text-lg font-bold mt-4 mb-2">Article 2 : Droits d'auteur</h3>
                            <p>Copyright © 2017 TRENDY FOODS BELGIUM SA. Tous droits réservés.</p>
                            <p>Tous les droits d'auteur ou autres droits de propriété intellectuelle sur tout texte, image, son, logiciel et autre contenu de ce site sont la propriété de TRENDY FOODS BELGIUM SA et de ses entités affiliées, ou sont inclus avec l'autorisation du propriétaire correspondant. Les références aux entités du groupe ou aux entités affiliées comprennent toutes les entités du groupe TRENDY FOODS.</p>
                            
                            <h3 class="text-lg font-bold mt-4 mb-2">Article 3 : Contenu</h3>
                            <p>Les informations contenues sur ce site sont fournies de bonne foi, mais elles sont données exclusivement à des fins informatives. Elles sont fournies en l'état et aucune garantie n'est donnée quant à leur précision ou exhaustivité.</p>
                            <p>Ni TRENDY FOODS BELGIUM SA ni l'une de ses filiales, ni leurs dirigeants, employés ou agents, ne peuvent être tenus responsables d'une quelconque perte, dommage ou dépense découlant d'un quelconque accès à ce site ou d'une quelconque utilisation de celui-ci ou de tout site lié à celui-ci, y compris, et sans limitation, d'une quelconque perte de bénéfice, perte indirecte, fortuite ou consécutive.</p>
                            
                            <h3 class="text-lg font-bold mt-4 mb-2">Article 4 : Contactez-nous</h3>
                            <p>Le présent site web est exploité par TRENDY FOODS BELGIUM SA, Avenue du Parc 37, à B-4800 Verviers, BCE 0407.095.835, RPR Verviers.<br>
                            Si vous souhaitez obtenir de plus amples renseignements ou effectuer des commentaires au sujet de ce site web, veuillez nous contacter par (i) courriel à privacy@trendyfoods.com, (ii) téléphone au +32 (0)87 32 18 88 ou (iii) courrier postal à TRENDY FOODS BELGIUM SA, Avenue du Parc, 37 à B-4800 Verviers.</p>
                            
                            <h3 class="text-lg font-bold mt-4 mb-2">Article 5 : Vie Privée</h3>
                            <p>Nous respectons votre vie privée. Nous ne collectons pas de données sans votre consentement. Nous vous invitons à consulter notre Politique « Vie Privée » pour de plus amples informations.</p>
                            
                            <h3 class="text-lg font-bold mt-4 mb-2">Article 6 : Modifications</h3>
                            <p>TRENDY FOODS BELGIUM SA se réserve le droit d'apporter des modifications et des corrections sur ce site au fur et à mesure des besoins et sans préavis.</p>
                            
                            <h3 class="text-lg font-bold mt-4 mb-2">Article 7 : Droit applicable et juridictions compétentes</h3>
                            <p>La présente Convention sera soumise à la législation belge.</p>
                            <p>Tout litige découlant de ou en relation avec la présente Convention et/ou sa fin sera soumis exclusivement aux juridictions compétentes de l'arrondissement judiciaire de Liège, division Verviers.</p>
                        <?php else: ?>
                            <p>Deze internetsite (behalve de ermee verbonden sites) wordt beheerd door TRENDY FOODS BELGIUM NV.</p>
                            
                            <h3 class="text-lg font-bold mt-4 mb-2">Article 1 : Instemming met deze bindende voorwaarden</h3>
                            <p>Uw gebruik van deze internetsite en van alle software, toepassingen, gegevens, producten, wedstrijden, tombola's en alle andere diensten die op deze site rechtstreeks of door bemiddeling worden aangeboden door TRENDY FOODS BELGIUM NV (en die samen "de Diensten van TRENDY FOODS BELGIUM NV" worden genoemd), is onderworpen aan de tussen u en TRENDY FOODS BELGIUM NV overeengekomen algemene gebruiksvoorwaarden.</p>
                            <p>Naast de juridische voorwaarden omvat de juridische overeenkomst (i) het Beleid inzake bescherming van de persoonlijke levenssfeer en (ii) de algemene verkoopsvoorwaarden.</p>
                            
                            <h3 class="text-lg font-bold mt-4 mb-2">Article 2 : Auteursrechten</h3>
                            <p>Copyright © 2017 TRENDY FOODS BELGIUM NV. Alle rechten voorbehouden</p>
                            <p>Alle auteurs en andere intellectuele eigendomsrechten op alle teksten, afbeeldingen, geluiden, software en andere inhoud van deze site zijn eigendom van TRENDY FOODS BELGIUM NV en van haar aangesloten entiteiten, of werden erin opgenomen met toestemming van de desbetreffende eigenaar. De verwijzingen naar de entiteiten van de groep en naar de aangesloten entiteiten omvatten alle entiteiten van de groep TRENDY FOODS.</p>
                            
                            <h3 class="text-lg font-bold mt-4 mb-2">Article 3 : Inhoud</h3>
                            <p>De op deze site aanwezige informatie wordt te goeder trouw geleverd, maar is uitsluitend van informatieve aard. Ze wordt geleverd zoals ze is, zonder enige garantie op de juistheid en de volledigheid ervan.</p>
                            <p>TRENDY FOODS BELGIUM NV of een van de dochterondernemingen ervan kunnen, evenmin als de leidinggevenden, de bedienden en de agenten ervan, aansprakelijk worden gesteld voor verlies, schade of uitgaven die het gevolg zijn van de toegang tot of het gebruik van deze site of van eender welke ermee verbonden site, met inbegrip van en zonder beperking tot een of andere vorm van winstderving of indirect, toevallig of daaruit voortvloeiend verlies.</p>
                            
                            <h3 class="text-lg font-bold mt-4 mb-2">Article 4 : Neem contact met ons op</h3>
                            <p>Onderhavige website wordt beheerd door TRENDY FOODS BELGIUM NV, Avenue du Parc 37 te B 4800 Verviers, KBO 0407.095.835, RPR Verviers. Voor meer informatie over of voor commentaar op deze website gelieve u contact met ons op te nemen per (i) e-mail op "privacy@trendyfoods.com", (ii) telefoon op +32 (0)87 32 18 88 of (iii) brief aan het adres van TRENDY FOODS BELGIUM NV, Avenue du Parc 37 te B 4800 Verviers.</p>
                            
                            <h3 class="text-lg font-bold mt-4 mb-2">Article 5 : Privacy</h3>
                            <p>Wij beschermen uw persoonlijke levenssfeer. Wij zamelen geen gegevens zonder uw toestemming. Voor meer informatie: gelieve ons Privacybeleid te raadplegen.</p>
                            
                            <h3 class="text-lg font-bold mt-4 mb-2">Article 6 : Wijzigingen</h3>
                            <p>TRENDY FOODS BELGIUM NV behoudt zich het recht voor deze site zonder voorafgaand bericht te wijzigen en te verbeteren naargelang de behoeften.</p>
                            
                            <h3 class="text-lg font-bold mt-4 mb-2">Article 7 : Toepasselijk recht en rechtsbevoegdheid</h3>
                            <p>Onderhavige Overeenkomst valt onder de Belgische wetgeving.</p>
                            <p>Alle geschillen die voortvloeien uit of in verband staan met onderhavige Overeenkomst en/of het doel ervan, kunnen enkel worden voorgelegd aan de bevoegde rechtsmachten uit het gerechtelijk arrondissement van Luik, afdeling Verviers.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 px-6 py-4">
                    <button @click="showCGU = false" 
                            class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        <?= $customer['language'] === 'fr' ? 'Fermer' : 'Sluiten' ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal RGPD (Politique Vie Privée) -->
    <div x-show="showRGPD" 
         x-cloak
         @click.away="showRGPD = false"
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Overlay -->
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" 
                 @click="showRGPD = false"></div>

            <!-- Modal Content -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <!-- Header -->
                <div class="bg-green-600 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white">
                        <?= $customer['language'] === 'fr' ? "Politique « Vie Privée » de TRENDY FOODS BELGIUM SA" : "Privacybeleid van TRENDY FOODS BELGIUM SA" ?>
                    </h3>
                    <button @click="showRGPD = false" 
                            class="text-white hover:text-gray-200">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="bg-white px-6 py-6 max-h-96 overflow-y-auto">
                    <div class="prose prose-sm max-w-none">
                        <?php if ($customer['language'] === 'fr'): ?>
                            <h3 class="text-lg font-bold mt-4 mb-2">1 : Objectif</h3>
                            <p>Le présent document constitue la politique « Vie Privée » mise en œuvre par TRENDY FOODS BELGIUM SA dans le cadre de ses activités.</p>
                            <p>La protection de votre vie privée et de vos données à caractère personnel est d'une importance capitale pour TRENDY FOODS BELGIUM SA.</p>
                            <p>Cette politique « Vie Privée » est rédigée afin de garantir le respect du Règlement européen 2016/679 du 27 avril 2016 relatif à la protection des personnes physiques à l'égard du traitement des données à caractère personnel et à la libre circulation de ces données, et abrogeant la Directive 95/46/EC (Règlement général sur la protection des données, ou RGPD).</p>
                            
                            <h3 class="text-lg font-bold mt-4 mb-2">2 : Quelle est la portée de cette politique ?</h3>
                            <p>Nous recueillons et utilisons uniquement les données personnelles qui sont nécessaires dans le cadre de nos activités et qui nous permettent de vous proposer des produits et services de qualité.</p>
                            <p>TRENDY FOODS BELGIUM SA, ayant son siège social Avenue du Parc, 37 à B-4800 Verviers, est Responsable du traitement des données à caractère personnel qu'elle est amenée à traiter.</p>
                            
                            <h3 class="text-lg font-bold mt-4 mb-2">3 : Quelles données sont couvertes par notre politique ?</h3>
                            <p>Les données couvertes par la présente politique sont les données à caractère personnel des personnes physiques, c'est-à-dire des données qui permettent directement ou indirectement d'identifier une personne.</p>
                            <p>Dans le cadre de vos relations et interactions avec TRENDY FOODS BELGIUM SA, nous pouvons être amenés à collecter différentes données à caractère personnel, telles que :</p>
                            <ul class="list-disc pl-6 space-y-1">
                                <li><strong>Des données d'identification et de contact</strong> (exemples : vos titre, nom, adresse, date et lieu de naissance, numéro de compte, numéro de téléphone, adresse mail, adresse IP, profession)</li>
                                <li><strong>Situation familiale</strong> (exemples : état civil, nombre d'enfants)</li>
                                <li><strong>Données bancaires, financières et transactionnelles</strong></li>
                                <li><strong>Données relatives à vos comportements et habitudes</strong></li>
                                <li><strong>Données relatives à vos préférences et intérêts</strong></li>
                            </ul>
                            
                            <h3 class="text-lg font-bold mt-4 mb-2">10 : Quels sont vos droits et comment les exercer ?</h3>
                            <p>Conformément à la réglementation applicable, vous disposez de différents droits :</p>
                            <ul class="list-disc pl-6 space-y-1">
                                <li>Le droit de demander l'accès aux données à caractère personnel</li>
                                <li>Le droit à la rectification</li>
                                <li>Le droit à l'effacement des données</li>
                                <li>Le droit de s'opposer au traitement</li>
                                <li>Le droit de retirer son consentement</li>
                                <li>Le droit de demander une limitation du traitement</li>
                                <li>Le droit à la portabilité des données</li>
                            </ul>
                            
                            <h3 class="text-lg font-bold mt-4 mb-2">14 : Comment nous contacter ?</h3>
                            <p>Si vous avez des questions concernant l'utilisation de vos données personnelles visée par la présente politique, vous nous contacter par e-mail à l'adresse <strong>privacy@trendyfoods.com</strong></p>
                            
                            <hr class="my-4">
                            <p class="text-sm text-gray-600">La présente Politique « Vie Privée » est applicable à dater du 25 mai 2018.</p>
                        <?php else: ?>
                            <h3 class="text-lg font-bold mt-4 mb-2">1 : Doel</h3>
                            <p>Onderhavig document vormt het "Privacybeleid" dat door TRENDY FOODS BELGIUM NV wordt gevoerd in het kader van zijn activiteiten.</p>
                            <p>De bescherming van uw persoonlijke levenssfeer en van uw persoonsgegevens is van kapitaal belang voor TRENDY FOODS BELGIUM NV.</p>
                            <p>Dit "Privacybeleid" werd opgesteld om de naleving te garanderen van de Europese Verordening 2016/679 van 27 april 2016 betreffende de bescherming van natuurlijke personen in verband met de verwerking van persoonsgegevens en betreffende het vrije verkeer van de gegevens en tot intrekking van Richtlijn 95/46/EG (algemene verordening gegevensbescherming of AVG/GDPR).</p>
                            <p>Dit "Privacybeleid" dient om u volledig te informeren over dat onderwerp en om uit te leggen hoe wij uw persoonsgegevens inzamelen, gebruiken en bewaren.</p>
                            
                            <h3 class="text-lg font-bold mt-4 mb-2">2 : Wat is de draagwijdte van dit beleid?</h3>
                            <p>Wij verzamelen en gebruiken enkel persoonsgegevens die nodig zijn in het kader van onze activiteiten en die ons in staat stellen u producten en diensten van goede kwaliteit aan te bieden.</p>
                            <p>TRENDY FOODS BELGIUM NV, waarvan de maatschappelijke zetel gevestigd is aan de Avenue du Parc 37 te B-4800 Verviers, is verantwoordelijk voor de verwerking van de persoonsgegevens die het inzamelt.</p>
                            <p>Wij zijn bijgevolg uw partner en ook die van de controleautoriteiten (zoals de "Gegevensbeschermingsautoriteit") voor alle aangelegenheden in verband met het gebruik van uw gegevens door onze firma.</p>
                            
                            <h3 class="text-lg font-bold mt-4 mb-2">3 : Welke gegevens vallen onder ons beleid?</h3>
                            <p>De gegevens waarop onderhavig beleid betrekking heeft, zijn persoonsgegevens van natuurlijke personen, dat wil zeggen gegevens waardoor iemand direct of indirect kan worden geïdentificeerd.</p>
                            <p>In het kader van uw betrekkingen en interacties met TRENDY FOODS BELGIUM NV kan het zijn dat wij verscheidene persoonsgegevens inzamelen, zoals:</p>
                            <ul class="list-disc pl-6 space-y-1">
                                <li><strong>Identificatie en contactgegevens</strong> (bijvoorbeeld: uw titel, naam, adres, geboorteplaats en datum, rijksregisternummer, rekeningnummer, telefoonnummer, e-mailadres, IP-adres, beroep)</li>
                                <li><strong>Gezinstoestand</strong> (bijvoorbeeld: burgerlijke staat, aantal kinderen)</li>
                                <li><strong>Bankgegevens en financiële en transactionele gegevens</strong></li>
                                <li><strong>Gegevens betreffende uw gedrag en gewoonten</strong></li>
                                <li><strong>Gegevens betreffende uw voorkeuren en interesses</strong></li>
                            </ul>
                            
                            <h3 class="text-lg font-bold mt-4 mb-2">10 : Wat zijn uw rechten en hoe kunt u ze uitoefenen?</h3>
                            <p>Conform de toepasselijke regelgeving beschikt u over verschillende rechten:</p>
                            <ul class="list-disc pl-6 space-y-1">
                                <li>Het recht om toegang te vragen tot de persoonsgegevens</li>
                                <li>Het recht op verbetering</li>
                                <li>Het recht op verwijdering van de gegevens</li>
                                <li>Het recht om zich te verzetten tegen de verwerking</li>
                                <li>Het recht om uw toestemming in te trekken</li>
                                <li>Het recht om een beperking van de verwerking te vragen</li>
                                <li>Het recht op overdraagbaarheid van de gegevens</li>
                            </ul>
                            
                            <h3 class="text-lg font-bold mt-4 mb-2">14 : Hoe kunt u contact met ons opnemen?</h3>
                            <p>Indien u vragen hebt omtrent het gebruik van uw persoonsgegevens zoals bedoeld in dit beleid, kunt u contact met ons opnemen per e-mail op het adres <strong>privacy@trendyfoods.com</strong></p>
                            
                            <hr class="my-4">
                            <p class="text-sm text-gray-600">Dit "Privacybeleid" is van toepassing vanaf 25 mei 2018.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 px-6 py-4">
                    <button @click="showRGPD = false" 
                            class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                        <?= $customer['language'] === 'fr' ? 'Fermer' : 'Sluiten' ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Script de validation côté client -->
    <script>
    document.getElementById('checkoutForm')?.addEventListener('submit', function(e) {
        // Vérifier que toutes les CGV sont cochées
        const cgv1 = document.querySelector('input[name="cgv_1"]');
        const cgv2 = document.querySelector('input[name="cgv_2"]');
        const cgv3 = document.querySelector('input[name="cgv_3"]');
        
        if (!cgv1?.checked || !cgv2?.checked || !cgv3?.checked) {
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
      <!-- Footer -->
    <footer class="bg-gray-800 text-gray-300 py-6 mt-12 relative z-10">
        <div class="container mx-auto px-4 text-center">
            <p class="text-sm">
                © <?= date('Y') ?> Trendy Foods - 
                <?= $customer['language'] === 'fr' ? 'Tous droits réservés' : 'Alle rechten voorbehouden' ?>
            </p>
        </div>
    </footer>
</body>
</html>