<?php
/**
 * Vue : Accès campagne publique
 * Page d'identification client
 * 
 * @created  2025/11/14 16:45
 * @modified 2025/11/18 18:00 - Redesign avec logo, fond et code couleur vert
 */

// Déterminer la langue (par défaut FR)
$lang = $campaign['country'] === 'LU' ? 'fr' : 'fr'; // TODO: gérer la détection navigateur pour BE

// Récupérer les erreurs de session
$error = \Core\Session::get('error');
\Core\Session::remove('error');

$title = $lang === 'fr' ? $campaign['title_fr'] : $campaign['title_nl'];
$description = $lang === 'fr' ? $campaign['description_fr'] : $campaign['description_nl'];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - Trendy Foods</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Fond Trendy Foods en arrière-plan */
        body {
            position: relative;
        }
        
        body::before {
            content: '';
            position: fixed;
            bottom: 0;
            right: 0;
            width: 400px;
            height: 400px;
            background: url('/stm/assets/images/fond.png') no-repeat;
            background-size: contain;
            opacity: 0.6;
            pointer-events: none;
            z-index: 0;
        }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Header -->
    <header class="bg-white shadow-md relative z-10">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <!-- Logo Trendy Foods -->
                    <img src="/stm/assets/images/logo.png" alt="Trendy Foods" class="h-12" onerror="this.style.display='none'">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Trendy Foods</h1>
                        <p class="text-sm text-gray-600">
                            <?= $lang === 'fr' ? 'Votre grossiste de confiance' : 'Uw vertrouwde groothandel' ?>
                        </p>
                    </div>
                </div>
                
                <!-- Switch langue (BE uniquement) -->
                <?php if ($campaign['country'] === 'BE' || $campaign['country'] === 'BOTH'): ?>
                <div class="flex bg-gray-100 rounded-lg p-1">
                    <button class="px-4 py-2 rounded-md <?= $lang === 'fr' ? 'bg-white text-blue-600 font-semibold shadow-sm' : 'text-gray-600 hover:bg-white hover:shadow-sm' ?> transition">
                        FR
                    </button>
                    <button class="px-4 py-2 rounded-md <?= $lang === 'nl' ? 'bg-white text-blue-600 font-semibold shadow-sm' : 'text-gray-600 hover:bg-white hover:shadow-sm' ?> transition">
                        NL
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Bande d'information campagne (VERT #27ae60) -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-lg relative z-10" style="background: linear-gradient(135deg, #277caeff 0%, #225a99ff 100%);">
        <div class="container mx-auto px-4 py-6">
            <div class="text-center">
                <h2 class="text-3xl font-bold mb-2"><?= htmlspecialchars($title) ?></h2>
                
                <?php if ($description): ?>
                <p class="text-blue-100 leading-relaxed max-w-2xl mx-auto">
                    <?= nl2br(htmlspecialchars($description)) ?>
                </p>
                <?php endif; ?>
                
                <!-- Dates et pays -->
                <div class="flex items-center justify-center gap-6 mt-4 text-sm text-blue-100">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-calendar-alt"></i>
                        <span>
                            <?= date('d/m/Y', strtotime($campaign['start_date'])) ?>
                            -
                            <?= date('d/m/Y', strtotime($campaign['end_date'])) ?>
                        </span>
                    </div>
                    
                    <?php if ($campaign['country'] !== 'BOTH'): ?>
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-flag"></i>
                        <span><?= $campaign['country'] ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-12 relative z-10">
        
        <div class="max-w-lg mx-auto">
            
            <!-- Card formulaire -->
            <div class="bg-white rounded-lg shadow-lg p-8 mb-8">
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-4">
                        <i class="fas fa-user-check text-3xl text-blue-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">
                        <?= $lang === 'fr' ? 'Accès client' : 'Toegang klant' ?>
                    </h3>
                    <p class="text-gray-600">
                        <?= $lang === 'fr' 
                            ? 'Identifiez-vous pour accéder à la campagne' 
                            : 'Identificeer uzelf om toegang te krijgen tot de campagne' ?>
                    </p>
                </div>

                <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-lg">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-circle text-red-500 mt-0.5 mr-3"></i>
                        <p class="text-sm text-red-700"><?= htmlspecialchars($error) ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <form method="POST" action="/stm/c/<?= htmlspecialchars($campaign['uuid']) ?>/identify" class="space-y-6">
                    
                    <!-- Token CSRF -->
                    <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

                    <!-- Numéro client -->
                    <div>
                        <label for="customer_number" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-id-card mr-2 text-blue-600"></i>
                            <?= $lang === 'fr' ? 'Numéro client' : 'Klantnummer' ?>
                            <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="customer_number" 
                            name="customer_number" 
                            required
                            placeholder="<?= $lang === 'fr' ? 'Ex: 123456' : 'Bijv: 123456' ?>"
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        >
                        <p class="mt-2 text-sm text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            <?= $lang === 'fr' 
                                ? 'Entrez votre numéro client à 6 chiffres' 
                                : 'Voer uw klantnummer van 6 cijfers in' 
                            ?>
                        </p>
                    </div>

                    <!-- Pays (si BOTH) -->
                    <?php if ($campaign['country'] === 'BOTH'): ?>
                    <div>
                        <label for="country" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-flag mr-2 text-blue-600"></i>
                            <?= $lang === 'fr' ? 'Pays' : 'Land' ?>
                            <span class="text-red-500">*</span>
                        </label>
                        <select 
                            id="country" 
                            name="country" 
                            required
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        >
                            <option value="BE"><?= $lang === 'fr' ? 'Belgique' : 'België' ?></option>
                            <option value="LU"><?= $lang === 'fr' ? 'Luxembourg' : 'Luxemburg' ?></option>
                        </select>
                    </div>
                    <?php else: ?>
                    <input type="hidden" name="country" value="<?= htmlspecialchars($campaign['country']) ?>">
                    <?php endif; ?>

                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-6 rounded-lg transition-all duration-200 flex items-center justify-center space-x-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                    >
                        <span><?= $lang === 'fr' ? 'Accéder au catalogue' : 'Toegang tot catalogus' ?></span>
                        <i class="fas fa-arrow-right"></i>
                    </button>

                </form>
            </div>

            <!-- Section aide -->
            <div class="bg-blue-50 border-2 border-blue-200 rounded-lg p-6">
                <div class="flex items-start space-x-3">
                    <i class="fas fa-question-circle text-2xl text-blue-600 flex-shrink-0 mt-1"></i>
                    <div>
                        <h4 class="font-bold text-blue-900 mb-2">
                            <?= $lang === 'fr' ? 'Besoin d\'aide ?' : 'Hulp nodig?' ?>
                        </h4>
                        <p class="text-sm text-blue-800 leading-relaxed">
                            <?= $lang === 'fr' 
                                ? 'Si vous ne connaissez pas votre numéro client ou si vous rencontrez des difficultés, contactez notre service client :' 
                                : 'Als u uw klantnummer niet kent of problemen ondervindt, neem dan contact op met onze klantenservice:' 
                            ?>
                        </p>
                        <div class="mt-3 space-y-1 text-sm text-blue-900">
                            <p>
                                <i class="fas fa-phone mr-2"></i>
                                <strong>+32 (0)87 321 888 </strong>
                            </p>
                            <p>
                                <i class="fas fa-envelope mr-2"></i>
                                <strong>info@trendyfoods.be</strong>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-gray-300 mt-16 relative z-10">
        <div class="container mx-auto px-4 py-8">
            <div class="text-center text-sm">
                <p>&copy; <?= date('Y') ?> Trendy Foods. 
                    <?= $lang === 'fr' ? 'Tous droits réservés.' : 'Alle rechten voorbehouden.' ?>
                </p>
            </div>
        </div>
    </footer>

</body>
</html>