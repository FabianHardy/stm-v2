<?php
/**
 * Vue formulaire inscription prospect
 *
 * Sprint 16 : Mode Prospect
 * Formulaire d'inscription pour les nouveaux clients (prospects)
 * Utilise le système de traductions existant (TranslationHelper)
 *
 * @created    2026/01/09
 * @modified   2026/01/09 - Réorganisation UX + trans() + switch langue
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

$campaignTitle = $lang === 'nl'
    ? ($campaign['title_nl'] ?? $campaign['title_fr'] ?? $campaign['name'])
    : ($campaign['title_fr'] ?? $campaign['name']);

// Déterminer si on affiche le switch langue (BE = oui, LU = non)
$showLangSwitch = ($campaign['country'] ?? 'BE') === 'BE';
$currentUrl = "/stm/c/" . htmlspecialchars($campaign['uuid']) . "/prospect";
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= trans('prospect.title', $lang) ?> - <?= htmlspecialchars($campaignTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-2xl mx-auto py-6 px-4" x-data="prospectForm()">

        <!-- Header avec switch langue -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex-1"></div>

            <?php if ($showLangSwitch): ?>
            <!-- Switch langue (BE uniquement) -->
            <div class="flex items-center gap-2 bg-white rounded-lg shadow-sm px-3 py-1.5">
                <a href="<?= $currentUrl ?>?lang=fr"
                   class="flex items-center gap-1 px-2 py-1 rounded text-sm <?= $lang === 'fr' ? 'bg-purple-100 text-purple-700 font-medium' : 'text-gray-500 hover:text-gray-700' ?>">
                    🇫🇷 FR
                </a>
                <span class="text-gray-300">|</span>
                <a href="<?= $currentUrl ?>?lang=nl"
                   class="flex items-center gap-1 px-2 py-1 rounded text-sm <?= $lang === 'nl' ? 'bg-purple-100 text-purple-700 font-medium' : 'text-gray-500 hover:text-gray-700' ?>">
                    🇳🇱 NL
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Titre -->
        <div class="text-center mb-6">
            <?php if (!empty($campaign['banner_' . $lang]) || !empty($campaign['banner_fr'])): ?>
            <img src="<?= htmlspecialchars($campaign['banner_' . $lang] ?? $campaign['banner_fr']) ?>"
                 alt="<?= htmlspecialchars($campaignTitle) ?>"
                 class="max-h-20 mx-auto mb-3">
            <?php endif; ?>

            <h1 class="text-xl font-bold text-gray-900"><?= trans('prospect.title', $lang) ?></h1>
            <p class="text-sm text-gray-600"><?= trans('prospect.subtitle', $lang) ?></p>
            <p class="text-xs text-purple-600 font-medium mt-1"><?= htmlspecialchars($campaignTitle) ?></p>
        </div>

        <!-- Messages d'erreur -->
        <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm">
            <p class="font-semibold mb-2"><?= $lang === 'nl' ? 'Corrigeer de volgende fouten:' : 'Veuillez corriger les erreurs suivantes :' ?></p>
            <ul class="list-disc list-inside space-y-1">
                <?php foreach ($errors as $field => $message): ?>
                <li><?= htmlspecialchars($message) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Formulaire -->
        <form method="POST"
              action="/stm/c/<?= htmlspecialchars($campaign['uuid']) ?>/prospect/register"
              class="bg-white shadow-lg rounded-lg p-5 space-y-4"
              @submit="validateForm($event)">

            <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="campaign_id" value="<?= $campaign['id'] ?>">
            <input type="hidden" name="language" value="<?= $lang ?>">

            <!-- ========================================== -->
            <!-- SECTION 1 : IDENTITÉ (Qui êtes-vous ?)    -->
            <!-- ========================================== -->
            <div class="border-b border-gray-100 pb-4">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">
                    <?= trans('prospect.section_identity', $lang) ?>
                </h3>

                <!-- Civilité + Nom entreprise -->
                <div class="grid grid-cols-12 gap-3 mb-3">
                    <div class="col-span-3">
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            <?= trans('prospect.civility', $lang) ?> <span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-3 mt-1">
                            <label class="flex items-center text-sm">
                                <input type="radio" name="civility" value="M."
                                       <?= ($old['civility'] ?? 'M.') === 'M.' ? 'checked' : '' ?>
                                       class="h-4 w-4 text-purple-600 focus:ring-purple-500" required>
                                <span class="ml-1"><?= trans('prospect.civility_mr', $lang) ?></span>
                            </label>
                            <label class="flex items-center text-sm">
                                <input type="radio" name="civility" value="Mme"
                                       <?= ($old['civility'] ?? '') === 'Mme' ? 'checked' : '' ?>
                                       class="h-4 w-4 text-purple-600 focus:ring-purple-500">
                                <span class="ml-1"><?= trans('prospect.civility_mrs', $lang) ?></span>
                            </label>
                        </div>
                    </div>
                    <div class="col-span-9">
                        <label for="company_name" class="block text-xs font-medium text-gray-700 mb-1">
                            <?= trans('prospect.company_name', $lang) ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="company_name" name="company_name"
                               value="<?= htmlspecialchars($old['company_name'] ?? '') ?>"
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                               required>
                    </div>
                </div>

                <!-- Type de magasin -->
                <div>
                    <label for="shop_type_id" class="block text-xs font-medium text-gray-700 mb-1">
                        <?= trans('prospect.shop_type', $lang) ?> <span class="text-red-500">*</span>
                    </label>
                    <select id="shop_type_id" name="shop_type_id"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                            required>
                        <option value=""><?= trans('prospect.select_shop_type', $lang) ?></option>
                        <?php foreach ($shopTypes as $type): ?>
                        <option value="<?= $type['id'] ?>" <?= ($old['shop_type_id'] ?? '') == $type['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($type['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- SECTION 2 : FISCALITÉ (TVA)               -->
            <!-- ========================================== -->
            <div class="border-b border-gray-100 pb-4">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">
                    <?= trans('prospect.section_vat', $lang) ?>
                </h3>

                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-3">
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            <?= trans('prospect.vat_liable', $lang) ?> <span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-3 mt-1">
                            <label class="flex items-center text-sm">
                                <input type="radio" name="is_vat_liable" value="1"
                                       x-model="isVatLiable"
                                       <?= ($old['is_vat_liable'] ?? '1') === '1' ? 'checked' : '' ?>
                                       class="h-4 w-4 text-purple-600 focus:ring-purple-500" required>
                                <span class="ml-1"><?= trans('prospect.yes', $lang) ?></span>
                            </label>
                            <label class="flex items-center text-sm">
                                <input type="radio" name="is_vat_liable" value="0"
                                       x-model="isVatLiable"
                                       <?= ($old['is_vat_liable'] ?? '') === '0' ? 'checked' : '' ?>
                                       class="h-4 w-4 text-purple-600 focus:ring-purple-500">
                                <span class="ml-1"><?= trans('prospect.no', $lang) ?></span>
                            </label>
                        </div>
                    </div>
                    <div class="col-span-9" x-show="isVatLiable === '1'" x-transition>
                        <label for="vat_number" class="block text-xs font-medium text-gray-700 mb-1">
                            <?= trans('prospect.vat_number', $lang) ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="vat_number" name="vat_number"
                               value="<?= htmlspecialchars($old['vat_number'] ?? '') ?>"
                               placeholder="BE0123456789"
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                               :required="isVatLiable === '1'"
                               :disabled="isVatLiable !== '1'">
                    </div>
                    <div class="col-span-9" x-show="isVatLiable === '0'" x-cloak>
                        <div class="h-full flex items-end">
                            <span class="text-sm text-gray-400 italic"><?= trans('prospect.not_vat_liable', $lang) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- SECTION 3 : CONTACT                       -->
            <!-- ========================================== -->
            <div class="border-b border-gray-100 pb-4">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">
                    <?= trans('prospect.section_contact', $lang) ?>
                </h3>

                <!-- Email + Confirmation -->
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div>
                        <label for="email" class="block text-xs font-medium text-gray-700 mb-1">
                            <?= trans('prospect.email', $lang) ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="email" id="email" name="email"
                               value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                               x-model="email"
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                               required>
                    </div>
                    <div>
                        <label for="email_confirm" class="block text-xs font-medium text-gray-700 mb-1">
                            <?= trans('prospect.email_confirm', $lang) ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="email" id="email_confirm" name="email_confirm"
                               x-model="emailConfirm"
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                               :class="{'border-red-500 bg-red-50': email && emailConfirm && email !== emailConfirm}"
                               required>
                        <p x-show="email && emailConfirm && email !== emailConfirm"
                           class="mt-1 text-xs text-red-600" x-cloak>
                            <?= trans('prospect.email_mismatch', $lang) ?>
                        </p>
                    </div>
                </div>

                <!-- Téléphone + Fax -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="phone" class="block text-xs font-medium text-gray-700 mb-1">
                            <?= trans('prospect.phone', $lang) ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="tel" id="phone" name="phone"
                               value="<?= htmlspecialchars($old['phone'] ?? '') ?>"
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                               required>
                    </div>
                    <div>
                        <label for="fax" class="block text-xs font-medium text-gray-700 mb-1">
                            <?= trans('prospect.fax', $lang) ?>
                            <span class="text-gray-400 font-normal">(<?= trans('prospect.optional', $lang) ?>)</span>
                        </label>
                        <input type="tel" id="fax" name="fax"
                               value="<?= htmlspecialchars($old['fax'] ?? '') ?>"
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- SECTION 4 : LOCALISATION                  -->
            <!-- ========================================== -->
            <div class="border-b border-gray-100 pb-4">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">
                    <?= trans('prospect.section_address', $lang) ?>
                </h3>

                <!-- Pays + Adresse -->
                <div class="grid grid-cols-12 gap-3 mb-3">
                    <div class="col-span-3">
                        <label for="country" class="block text-xs font-medium text-gray-700 mb-1">
                            <?= trans('prospect.country', $lang) ?> <span class="text-red-500">*</span>
                        </label>
                        <select id="country" name="country"
                                x-model="country"
                                @change="onCountryChange()"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                required>
                            <option value="BE">🇧🇪 Belgique</option>
                            <option value="LU">🇱🇺 Luxembourg</option>
                        </select>
                    </div>
                    <div class="col-span-9">
                        <label for="address" class="block text-xs font-medium text-gray-700 mb-1">
                            <?= trans('prospect.address', $lang) ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="address" name="address"
                               value="<?= htmlspecialchars($old['address'] ?? '') ?>"
                               placeholder="<?= trans('prospect.address_placeholder', $lang) ?>"
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                               required>
                    </div>
                </div>

                <!-- Code postal + Localité -->
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-3">
                        <label for="postal_code" class="block text-xs font-medium text-gray-700 mb-1">
                            <?= trans('prospect.postal_code', $lang) ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="postal_code" name="postal_code"
                               value="<?= htmlspecialchars($old['postal_code'] ?? '') ?>"
                               x-model="postalCode"
                               @input="onPostalCodeInput()"
                               maxlength="6"
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                               required>
                    </div>
                    <div class="col-span-9">
                        <label for="city" class="block text-xs font-medium text-gray-700 mb-1">
                            <?= trans('prospect.city', $lang) ?> <span class="text-red-500">*</span>
                            <span x-show="loadingLocalities" class="text-purple-500 ml-1" x-cloak>
                                <svg class="inline w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </span>
                        </label>
                        <select id="city" name="city"
                                x-model="city"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                :class="{'bg-gray-100': localities.length === 0}"
                                required>
                            <option value="" x-show="localities.length === 0">
                                <?= trans('prospect.enter_postal', $lang) ?>
                            </option>
                            <option value="" x-show="localities.length > 0" x-cloak>
                                <?= trans('prospect.select_city', $lang) ?>
                            </option>
                            <template x-for="loc in localities" :key="loc.id">
                                <option :value="loc.locality_<?= $lang === 'nl' ? 'nl' : 'fr' ?> || loc.locality_fr"
                                        x-text="loc.locality_<?= $lang === 'nl' ? 'nl' : 'fr' ?> || loc.locality_fr">
                                </option>
                            </template>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- SECTION 5 : COMPLÉMENTS                   -->
            <!-- ========================================== -->
            <div>
                <label for="additional_info" class="block text-xs font-medium text-gray-700 mb-1">
                    <?= trans('prospect.additional_info', $lang) ?>
                    <span class="text-gray-400 font-normal">(<?= trans('prospect.optional', $lang) ?>)</span>
                </label>
                <textarea id="additional_info" name="additional_info" rows="2"
                          class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                          placeholder="<?= trans('prospect.additional_placeholder', $lang) ?>"><?= htmlspecialchars($old['additional_info'] ?? '') ?></textarea>
            </div>

            <!-- Footer formulaire -->
            <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                <p class="text-xs text-gray-500">
                    <span class="text-red-500">*</span> <?= trans('prospect.required_fields', $lang) ?>
                </p>
                <button type="submit"
                        :disabled="submitting || (email && emailConfirm && email !== emailConfirm)"
                        class="bg-purple-600 text-white py-2.5 px-6 rounded-lg font-semibold text-sm hover:bg-purple-700 focus:ring-4 focus:ring-purple-200 disabled:opacity-50 disabled:cursor-not-allowed transition flex items-center gap-2">
                    <span x-show="!submitting">🌱 <?= trans('prospect.submit', $lang) ?></span>
                    <span x-show="submitting" x-cloak class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <?= trans('prospect.loading', $lang) ?>
                    </span>
                </button>
            </div>
        </form>

        <!-- Footer -->
        <div class="text-center mt-4 text-xs text-gray-400">
            © <?= date('Y') ?> Trendy Foods
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
                isVatLiable: '<?= htmlspecialchars($old['is_vat_liable'] ?? '1') ?>',
                localities: [],
                loadingLocalities: false,
                submitting: false,
                searchTimeout: null,

                init() {
                    if (this.postalCode && this.postalCode.length >= 4) {
                        this.loadLocalities();
                    }
                },

                onCountryChange() {
                    this.localities = [];
                    this.city = '';
                    if (this.postalCode && this.postalCode.length >= 4) {
                        this.loadLocalities();
                    }
                },

                onPostalCodeInput() {
                    clearTimeout(this.searchTimeout);

                    if (this.postalCode.length < 4) {
                        this.localities = [];
                        this.city = '';
                        return;
                    }

                    this.searchTimeout = setTimeout(() => {
                        this.loadLocalities();
                    }, 300);
                },

                async loadLocalities() {
                    if (this.postalCode.length < 4) return;

                    this.loadingLocalities = true;
                    try {
                        const response = await fetch(`/stm/api/postal-codes/localities?code=${encodeURIComponent(this.postalCode)}&country=${this.country}`);
                        const data = await response.json();

                        if (data.success && data.data) {
                            this.localities = data.data;

                            if (this.localities.length === 1) {
                                const lang = '<?= $lang ?>';
                                this.city = this.localities[0][`locality_${lang}`] || this.localities[0].locality_fr;
                            } else if (this.localities.length > 1) {
                                this.city = '';
                            }
                        } else {
                            this.localities = [];
                        }
                    } catch (e) {
                        console.error('Erreur chargement localités:', e);
                        this.localities = [];
                    } finally {
                        this.loadingLocalities = false;
                    }
                },

                validateForm(event) {
                    if (this.email !== this.emailConfirm) {
                        event.preventDefault();
                        alert('<?= trans('prospect.email_mismatch', $lang) ?>');
                        return false;
                    }

                    if (this.isVatLiable === '0') {
                        const vatInput = document.getElementById('vat_number');
                        if (vatInput) {
                            vatInput.removeAttribute('required');
                            vatInput.value = '';
                        }
                    }

                    this.submitting = true;
                    return true;
                }
            }
        }
    </script>
</body>
</html>