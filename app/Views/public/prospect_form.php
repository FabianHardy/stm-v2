<?php
/**
 * Vue formulaire inscription prospect
 *
 * Sprint 16 : Mode Prospect
 * Formulaire d'inscription pour les nouveaux clients (prospects)
 *
 * @created    2026/01/09
 */

// Variables attendues :
// $campaign : données de la campagne
// $shopTypes : liste des types de magasin
// $errors : erreurs de validation (optionnel)
// $old : anciennes valeurs du formulaire (optionnel)
// $lang : langue courante (fr ou nl)

$lang = $lang ?? 'fr';
$errors = $errors ?? [];
$old = $old ?? [];

// Traductions
$translations = [
    'fr' => [
        'title' => 'Inscription Nouveau Client',
        'subtitle' => 'Remplissez ce formulaire pour accéder au catalogue promotionnel',
        'civility' => 'Civilité',
        'mr' => 'M.',
        'mrs' => 'Mme',
        'company_name' => 'Nom de l\'entreprise / Contact',
        'vat_number' => 'Numéro de TVA',
        'vat_liable' => 'Êtes-vous assujetti à la TVA ?',
        'yes' => 'Oui',
        'no' => 'Non',
        'email' => 'Email',
        'email_confirm' => 'Confirmer l\'email',
        'shop_type' => 'Type de magasin',
        'select_shop_type' => '-- Sélectionnez un type --',
        'address' => 'Adresse',
        'postal_code' => 'Code postal',
        'city' => 'Localité',
        'select_city' => '-- Sélectionnez la localité --',
        'country' => 'Pays',
        'phone' => 'Téléphone',
        'fax' => 'Fax (optionnel)',
        'additional_info' => 'Informations supplémentaires (optionnel)',
        'submit' => 'Accéder au catalogue',
        'required' => 'Champs obligatoires',
        'loading' => 'Chargement...',
        'email_mismatch' => 'Les emails ne correspondent pas',
    ],
    'nl' => [
        'title' => 'Registratie Nieuwe Klant',
        'subtitle' => 'Vul dit formulier in om toegang te krijgen tot de promotiecatalogus',
        'civility' => 'Aanhef',
        'mr' => 'Dhr.',
        'mrs' => 'Mevr.',
        'company_name' => 'Bedrijfsnaam / Contact',
        'vat_number' => 'BTW-nummer',
        'vat_liable' => 'Bent u BTW-plichtig?',
        'yes' => 'Ja',
        'no' => 'Nee',
        'email' => 'E-mail',
        'email_confirm' => 'Bevestig e-mail',
        'shop_type' => 'Type winkel',
        'select_shop_type' => '-- Selecteer een type --',
        'address' => 'Adres',
        'postal_code' => 'Postcode',
        'city' => 'Plaats',
        'select_city' => '-- Selecteer de plaats --',
        'country' => 'Land',
        'phone' => 'Telefoon',
        'fax' => 'Fax (optioneel)',
        'additional_info' => 'Extra informatie (optioneel)',
        'submit' => 'Toegang tot catalogus',
        'required' => 'Verplichte velden',
        'loading' => 'Laden...',
        'email_mismatch' => 'E-mailadressen komen niet overeen',
    ],
];

$t = $translations[$lang] ?? $translations['fr'];
$campaignTitle = $lang === 'nl' ? ($campaign['title_nl'] ?? $campaign['title_fr'] ?? $campaign['name']) : ($campaign['title_fr'] ?? $campaign['name']);
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['title'] ?> - <?= htmlspecialchars($campaignTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-2xl mx-auto py-8 px-4" x-data="prospectForm()">
        <!-- Header -->
        <div class="text-center mb-8">
            <?php if (!empty($campaign['banner_' . $lang]) || !empty($campaign['banner_fr'])): ?>
            <img src="<?= htmlspecialchars($campaign['banner_' . $lang] ?? $campaign['banner_fr']) ?>" 
                 alt="<?= htmlspecialchars($campaignTitle) ?>"
                 class="max-h-32 mx-auto mb-4">
            <?php endif; ?>
            
            <h1 class="text-2xl font-bold text-gray-900"><?= $t['title'] ?></h1>
            <p class="text-gray-600 mt-2"><?= $t['subtitle'] ?></p>
            <p class="text-sm text-purple-600 mt-1 font-medium"><?= htmlspecialchars($campaignTitle) ?></p>
        </div>

        <!-- Messages d'erreur globaux -->
        <?php if (!empty($errors['global'])): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
            <?= htmlspecialchars($errors['global']) ?>
        </div>
        <?php endif; ?>

        <!-- Formulaire -->
        <form method="POST" 
              action="/stm/c/<?= htmlspecialchars($campaign['uuid']) ?>/prospect/register"
              class="bg-white shadow-lg rounded-lg p-6 space-y-6"
              @submit="validateForm($event)">
            
            <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="campaign_id" value="<?= $campaign['id'] ?>">
            <input type="hidden" name="language" value="<?= $lang ?>">

            <!-- Civilité -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <?= $t['civility'] ?> <span class="text-red-500">*</span>
                </label>
                <div class="flex gap-4">
                    <label class="flex items-center">
                        <input type="radio" name="civility" value="M." 
                               <?= ($old['civility'] ?? '') === 'M.' ? 'checked' : '' ?>
                               class="h-4 w-4 text-purple-600 focus:ring-purple-500" required>
                        <span class="ml-2"><?= $t['mr'] ?></span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="civility" value="Mme"
                               <?= ($old['civility'] ?? '') === 'Mme' ? 'checked' : '' ?>
                               class="h-4 w-4 text-purple-600 focus:ring-purple-500">
                        <span class="ml-2"><?= $t['mrs'] ?></span>
                    </label>
                </div>
                <?php if (!empty($errors['civility'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['civility']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Nom entreprise -->
            <div>
                <label for="company_name" class="block text-sm font-medium text-gray-700 mb-1">
                    <?= $t['company_name'] ?> <span class="text-red-500">*</span>
                </label>
                <input type="text" id="company_name" name="company_name" 
                       value="<?= htmlspecialchars($old['company_name'] ?? '') ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                       required>
                <?php if (!empty($errors['company_name'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['company_name']) ?></p>
                <?php endif; ?>
            </div>

            <!-- TVA -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="vat_number" class="block text-sm font-medium text-gray-700 mb-1">
                        <?= $t['vat_number'] ?> <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="vat_number" name="vat_number"
                           value="<?= htmlspecialchars($old['vat_number'] ?? '') ?>"
                           placeholder="BE0123456789"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                           required>
                    <?php if (!empty($errors['vat_number'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['vat_number']) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <?= $t['vat_liable'] ?> <span class="text-red-500">*</span>
                    </label>
                    <div class="flex gap-4 mt-2">
                        <label class="flex items-center">
                            <input type="radio" name="is_vat_liable" value="1"
                                   <?= ($old['is_vat_liable'] ?? '1') === '1' ? 'checked' : '' ?>
                                   class="h-4 w-4 text-purple-600 focus:ring-purple-500" required>
                            <span class="ml-2"><?= $t['yes'] ?></span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="is_vat_liable" value="0"
                                   <?= ($old['is_vat_liable'] ?? '') === '0' ? 'checked' : '' ?>
                                   class="h-4 w-4 text-purple-600 focus:ring-purple-500">
                            <span class="ml-2"><?= $t['no'] ?></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Email avec confirmation -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                        <?= $t['email'] ?> <span class="text-red-500">*</span>
                    </label>
                    <input type="email" id="email" name="email"
                           value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                           x-model="email"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                           required>
                    <?php if (!empty($errors['email'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['email']) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="email_confirm" class="block text-sm font-medium text-gray-700 mb-1">
                        <?= $t['email_confirm'] ?> <span class="text-red-500">*</span>
                    </label>
                    <input type="email" id="email_confirm" name="email_confirm"
                           x-model="emailConfirm"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                           :class="{'border-red-500': email && emailConfirm && email !== emailConfirm}"
                           required>
                    <p x-show="email && emailConfirm && email !== emailConfirm" 
                       class="mt-1 text-sm text-red-600" x-cloak>
                        <?= $t['email_mismatch'] ?>
                    </p>
                </div>
            </div>

            <!-- Type de magasin -->
            <div>
                <label for="shop_type_id" class="block text-sm font-medium text-gray-700 mb-1">
                    <?= $t['shop_type'] ?> <span class="text-red-500">*</span>
                </label>
                <select id="shop_type_id" name="shop_type_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                        required>
                    <option value=""><?= $t['select_shop_type'] ?></option>
                    <?php foreach ($shopTypes as $type): ?>
                    <option value="<?= $type['id'] ?>" <?= ($old['shop_type_id'] ?? '') == $type['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($type['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['shop_type_id'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['shop_type_id']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Adresse -->
            <div>
                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">
                    <?= $t['address'] ?> <span class="text-red-500">*</span>
                </label>
                <input type="text" id="address" name="address"
                       value="<?= htmlspecialchars($old['address'] ?? '') ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                       required>
                <?php if (!empty($errors['address'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['address']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Code postal, Localité, Pays -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Pays -->
                <div>
                    <label for="country" class="block text-sm font-medium text-gray-700 mb-1">
                        <?= $t['country'] ?> <span class="text-red-500">*</span>
                    </label>
                    <select id="country" name="country"
                            x-model="country"
                            @change="loadLocalities()"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                            required>
                        <option value="BE" <?= ($old['country'] ?? 'BE') === 'BE' ? 'selected' : '' ?>>Belgique / België</option>
                        <option value="LU" <?= ($old['country'] ?? '') === 'LU' ? 'selected' : '' ?>>Luxembourg</option>
                    </select>
                </div>

                <!-- Code postal -->
                <div>
                    <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">
                        <?= $t['postal_code'] ?> <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="postal_code" name="postal_code"
                           value="<?= htmlspecialchars($old['postal_code'] ?? '') ?>"
                           x-model="postalCode"
                           @input="searchPostalCode()"
                           @blur="loadLocalities()"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                           required>
                    <?php if (!empty($errors['postal_code'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['postal_code']) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Localité -->
                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700 mb-1">
                        <?= $t['city'] ?> <span class="text-red-500">*</span>
                    </label>
                    <select id="city" name="city"
                            x-model="city"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                            :disabled="localities.length === 0"
                            required>
                        <option value=""><?= $t['select_city'] ?></option>
                        <template x-for="loc in localities" :key="loc.id">
                            <option :value="loc.locality_<?= $lang === 'nl' ? 'nl' : 'fr' ?> || loc.locality_fr" 
                                    x-text="loc.locality_<?= $lang === 'nl' ? 'nl' : 'fr' ?> || loc.locality_fr">
                            </option>
                        </template>
                    </select>
                    <p x-show="loadingLocalities" class="mt-1 text-sm text-gray-500" x-cloak>
                        <?= $t['loading'] ?>
                    </p>
                    <?php if (!empty($errors['city'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['city']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Téléphone et Fax -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                        <?= $t['phone'] ?> <span class="text-red-500">*</span>
                    </label>
                    <input type="tel" id="phone" name="phone"
                           value="<?= htmlspecialchars($old['phone'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                           required>
                    <?php if (!empty($errors['phone'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['phone']) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="fax" class="block text-sm font-medium text-gray-700 mb-1">
                        <?= $t['fax'] ?>
                    </label>
                    <input type="tel" id="fax" name="fax"
                           value="<?= htmlspecialchars($old['fax'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                </div>
            </div>

            <!-- Infos supplémentaires -->
            <div>
                <label for="additional_info" class="block text-sm font-medium text-gray-700 mb-1">
                    <?= $t['additional_info'] ?>
                </label>
                <textarea id="additional_info" name="additional_info" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"><?= htmlspecialchars($old['additional_info'] ?? '') ?></textarea>
            </div>

            <!-- Note champs obligatoires -->
            <p class="text-sm text-gray-500">
                <span class="text-red-500">*</span> <?= $t['required'] ?>
            </p>

            <!-- Bouton submit -->
            <div class="pt-4">
                <button type="submit"
                        :disabled="submitting || (email && emailConfirm && email !== emailConfirm)"
                        class="w-full bg-purple-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-purple-700 focus:ring-4 focus:ring-purple-200 disabled:opacity-50 disabled:cursor-not-allowed transition">
                    <span x-show="!submitting">👤 <?= $t['submit'] ?></span>
                    <span x-show="submitting" x-cloak><?= $t['loading'] ?></span>
                </button>
            </div>
        </form>

        <!-- Footer -->
        <div class="text-center mt-8 text-sm text-gray-500">
            <p>© <?= date('Y') ?> Trendy Foods</p>
        </div>
    </div>

    <script>
        function prospectForm() {
            return {
                email: '<?= htmlspecialchars($old['email'] ?? '') ?>',
                emailConfirm: '',
                country: '<?= htmlspecialchars($old['country'] ?? 'BE') ?>',
                postalCode: '<?= htmlspecialchars($old['postal_code'] ?? '') ?>',
                city: '<?= htmlspecialchars($old['city'] ?? '') ?>',
                localities: [],
                loadingLocalities: false,
                submitting: false,

                init() {
                    if (this.postalCode) {
                        this.loadLocalities();
                    }
                },

                async loadLocalities() {
                    if (this.postalCode.length < 4) {
                        this.localities = [];
                        return;
                    }

                    this.loadingLocalities = true;
                    try {
                        const response = await fetch(`/stm/api/postal-codes/localities?code=${this.postalCode}&country=${this.country}`);
                        const data = await response.json();
                        if (data.success) {
                            this.localities = data.data;
                            // Pré-sélectionner si une seule localité
                            if (this.localities.length === 1) {
                                const lang = '<?= $lang ?>';
                                this.city = this.localities[0][`locality_${lang}`] || this.localities[0].locality_fr;
                            }
                        }
                    } catch (e) {
                        console.error('Erreur chargement localités:', e);
                    } finally {
                        this.loadingLocalities = false;
                    }
                },

                validateForm(event) {
                    if (this.email !== this.emailConfirm) {
                        event.preventDefault();
                        alert('<?= $t['email_mismatch'] ?>');
                        return false;
                    }
                    this.submitting = true;
                    return true;
                }
            }
        }
    </script>
</body>
</html>
