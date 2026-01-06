<?php
/**
 * Composant : Header Public
 *
 * Header blanc avec logo, infos client/campagne et switch langue
 *
 * @package STM
 * @created 2025/11/21
 * @modified 2026/01/06 - Sprint 14 : Badge mode représentant (style catalog)
 *
 * Variables :
 * - $lang, $campaign, $customer, $uuid
 * - $showLogo (défaut: true), $showClient (défaut: auto), $showLang (défaut: auto)
 * - $sticky (défaut: true)
 */

$lang = $lang ?? 'fr';
$showLogo = $showLogo ?? true;
$showClient = $showClient ?? isset($customer);
$sticky = $sticky ?? true;
$uuid = $uuid ?? ($campaign['uuid'] ?? '');

// Sprint 14 : Détection mode représentant
$isRepOrder = $customer['is_rep_order'] ?? false;
$repName = $customer['rep_name'] ?? '';
$repEmail = $customer['rep_email'] ?? '';

// Switch langue visible pour BE/BOTH, caché pour LU
if (!isset($showLang)) {
    $showLang = isset($customer['country'])
        ? ($customer['country'] === 'BE')
        : (isset($campaign['country']) ? in_array($campaign['country'], ['BE', 'BOTH']) : true);
}

$headerClass = 'bg-white shadow-md relative z-40' . ($sticky ? ' sticky top-0' : '');
?>
<header class="<?= $headerClass ?>">
    <div class="container mx-auto px-4 py-4">
        <div class="flex justify-between items-center">

            <div class="flex items-center space-x-4">
                <?php if ($showLogo): ?>
                <img src="/stm/assets/images/logo.png" alt="Trendy Foods" class="h-12" onerror="this.style.display='none'">
                <?php endif; ?>

                <div>
                    <?php if (isset($campaign['name'])): ?>
                        <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($campaign['name']) ?></h1>
                        <?php if ($showClient && isset($customer)): ?>
                        <p class="text-sm text-gray-600">
                            <i class="fas fa-building mr-1"></i>
                            <?= htmlspecialchars($customer['company_name'] ?? '') ?>
                            <span class="mx-2">•</span>
                            <?= htmlspecialchars($customer['customer_number'] ?? '') ?>
                        </p>
                        <?php endif; ?>
                    <?php else: ?>
                        <h1 class="text-2xl font-bold text-gray-900">Trendy Foods</h1>
                        <p class="text-sm text-gray-600">
                            <?= $lang === 'fr' ? 'Être proche pour voir loin' : 'Dichtbij zijn om ver te kunnen kijken' ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <?php if ($isRepOrder): ?>
                <!-- Badge Mode représentant -->
                <span class="hidden lg:inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-700 border border-purple-200">
                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Mode représentant
                </span>
                <?php endif; ?>

                <?php if ($isRepOrder && !empty($repName)): ?>
                <!-- Nom du rep connecté -->
                <div class="hidden lg:block text-right">
                    <p class="text-sm font-medium text-gray-700"><?= htmlspecialchars($repName) ?></p>
                    <p class="text-xs text-gray-500"><?= htmlspecialchars($repEmail) ?></p>
                </div>
                <?php endif; ?>

                <?php if ($showLang): ?>
                <div class="hidden lg:flex bg-gray-100 rounded-lg p-1">
                    <button onclick="switchLanguage('fr')" class="px-4 py-2 rounded-md <?= $lang === 'fr' ? 'bg-white text-blue-600 font-semibold shadow-sm' : 'text-gray-600 hover:bg-white hover:shadow-sm' ?> transition">FR</button>
                    <button onclick="switchLanguage('nl')" class="px-4 py-2 rounded-md <?= $lang === 'nl' ? 'bg-white text-blue-600 font-semibold shadow-sm' : 'text-gray-600 hover:bg-white hover:shadow-sm' ?> transition">NL</button>
                </div>
                <?php endif; ?>

                <?php if (isset($customer) && !empty($uuid)): ?>
                    <?php if ($isRepOrder): ?>
                    <!-- Changer de client (mode rep) -->
                    <a href="/stm/c/<?= htmlspecialchars($uuid) ?>/rep/select-client" class="hidden lg:flex items-center text-gray-600 hover:text-gray-800 transition">
                        <i class="fas fa-exchange-alt mr-2"></i><?= $lang === 'fr' ? 'Changer de client' : 'Klant wijzigen' ?>
                    </a>
                    <?php else: ?>
                    <!-- Déconnexion client normal -->
                    <a href="/stm/c/<?= htmlspecialchars($uuid) ?>" class="hidden lg:block text-gray-600 hover:text-gray-800 transition">
                        <i class="fas fa-sign-out-alt mr-2"></i><?= $lang === 'fr' ? 'Déconnexion' : 'Afmelden' ?>
                    </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<script>
function switchLanguage(l){const u=new URL(window.location.href);u.searchParams.set('lang',l);window.location.href=u.toString();}
</script>