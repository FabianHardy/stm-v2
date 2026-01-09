<?php
/**
 * Vue : Accès campagne publique - Formulaire prospect
 *
 * Sprint 16 : Mode Prospect
 * Utilise le même layout et composants que show.php
 *
 * @package STM
 * @created 2026/01/09
 * @modified 2026/01/09 - Alignement design avec page client (show.php)
 */

// ========================================
// PRÉPARATION DES DONNÉES
// ========================================

// Langue : paramètre GET ou FR par défaut, forcé FR pour LU
$requestedLang = $_GET['lang'] ?? $lang ?? 'fr';
$lang = in_array($requestedLang, ['fr', 'nl'], true) ? $requestedLang : 'fr';
if ($campaign['country'] === 'LU') {
    $lang = 'fr';
}

// Stocker la langue en session
$_SESSION['prospect_language'] = $lang;

// Récupérer les erreurs et anciennes valeurs
$errors = $errors ?? [];
$old = $old ?? [];

// Titre et description selon la langue
$campaignTitle = $lang === 'fr' ? ($campaign['title_fr'] ?? $campaign['name']) : ($campaign['title_nl'] ?? $campaign['name']);
$campaignDescription = $lang === 'fr' ? ($campaign['description_fr'] ?? '') : ($campaign['description_nl'] ?? '');

// UUID pour les liens
$uuid = $campaign['uuid'];

// Variables pour le layout
$title = trans('prospect.title', $lang) . ' - ' . $campaignTitle;
$useAlpine = true;
$bodyAttrs = 'x-data="prospectForm()"';

// Récupérer les pages statiques pour le footer (utilisé par le layout)
$staticPageModel = new \App\Models\StaticPage();
$footerPages = $staticPageModel->getFooterPages($campaign['id']);

// ========================================
// CONTENU DE LA PAGE
// ========================================
ob_start();
?>

<!-- Header -->
<?php
$showClient = false; // Pas de client connecté sur cette page
include __DIR__ . '/../components/public/header.php';
?>

<!-- Bande campagne -->
<?php
$barTitle = $campaignTitle;
$barSubtitle = $campaignDescription;
$barColor = 'blue';
$showDates = true;
$showCountry = true;
include __DIR__ . '/../components/public/campaign_bar.php';
?>

<!-- Contenu principal -->
<main class="container mx-auto px-4 py-8 relative z-10">
    <div class="max-w-2xl mx-auto">

        <!-- Formulaire prospect -->
        <div class="bg-white rounded-lg shadow-lg p-6 md:p-8 mb-8">
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                    <i class="fas fa-seedling text-3xl text-green-600"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">
                    <?= trans('prospect.title', $lang) ?>
                </h3>
                <p class="text-gray-600">
                    <?= trans('prospect.subtitle', $lang) ?>
                </p>
            </div>

            <!-- Messages d'erreur -->
            <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-lg">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-circle text-red-500 mt-0.5 mr-3"></i>
                    <div>
                        <p class="text-sm font-semibold text-red-700 mb-1">
                            <?= $lang === 'nl' ? 'Corrigeer de volgende fouten:' : 'Veuillez corriger les erreurs suivantes :' ?>
                        </p>
                        <ul class="list-disc list-inside text-sm text-red-700">
                            <?php foreach ($errors as $field => $message): ?>
                            <li><?= htmlspecialchars($message) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST"
                  action="/stm/c/<?= htmlspecialchars($uuid) ?>/prospect/register"
                  class="space-y-6"
                  @submit="validateForm($event)">

                <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="campaign_id" value="<?= $campaign['id'] ?>">
                <input type="hidden" name="language" value="<?= $lang ?>">

                <!-- ============================================ -->
                <!-- SECTION : IDENTIFICATION -->
                <!-- ============================================ -->
                <div class="border-b border-gray-200 pb-6">
                    <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">
                        <i class="fas fa-user mr-2"></i><?= trans('prospect.section_identity', $lang) ?>
                    </h4>

                    <!-- Civilité + Entreprise -->
                    <div class="grid grid-cols-12 gap-4 mb-4">
                        <div class="col-span-12 md:col-span-3">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <?= trans('prospect.civility', $lang) ?> <span class="text-red-500">*</span>
                            </label>
                            <div class="flex gap-4 pt-2">
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="civility" value="M."
                                           <?= ($old['civility'] ?? 'M.') === 'M.' ? 'checked' : '' ?>
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500" required>
                                    <span class="ml-2 text-sm"><?= trans('prospect.civility_mr', $lang) ?></span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="civility" value="Mme"
                                           <?= ($old['civility'] ?? '') === 'Mme' ? 'checked' : '' ?>
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm"><?= trans('prospect.civility_mrs', $lang) ?></span>
                                </label>
                            </div>
                        </div>
                        <div class="col-span-12 md:col-span-9">
                            <label for="company_name" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-building mr-2 text-blue-600"></i>
                                <?= trans('prospect.company_name', $lang) ?> <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="company_name" name="company_name"
                                   value="<?= htmlspecialchars($old['company_name'] ?? '') ?>"
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                   required>
                        </div>
                    </div>

                    <!-- Type de magasin -->
                    <div>
                        <label for="shop_type_id" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-store mr-2 text-blue-600"></i>
                            <?= trans('prospect.shop_type', $lang) ?> <span class="text-red-500">*</span>
                        </label>
                        <select id="shop_type_id" name="shop_type_id"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
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

                <!-- ============================================ -->
                <!-- SECTION : TVA -->
                <!-- ============================================ -->
                <div class="border-b border-gray-200 pb-6">
                    <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">
                        <i class="fas fa-file-invoice mr-2"></i><?= trans('prospect.section_vat', $lang) ?>
                    </h4>

                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-12 md:col-span-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <?= trans('prospect.vat_liable', $lang) ?> <span class="text-red-500">*</span>
                            </label>
                            <div class="flex gap-4 pt-2">
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="is_vat_liable" value="1"
                                           x-model="isVatLiable"
                                           <?= ($old['is_vat_liable'] ?? '1') === '1' ? 'checked' : '' ?>
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500" required>
                                    <span class="ml-2 text-sm"><?= trans('prospect.yes', $lang) ?></span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="is_vat_liable" value="0"
                                           x-model="isVatLiable"
                                           <?= ($old['is_vat_liable'] ?? '') === '0' ? 'checked' : '' ?>
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm"><?= trans('prospect.no', $lang) ?></span>
                                </label>
                            </div>
                        </div>
                        <div class="col-span-12 md:col-span-8">
                            <div x-show="isVatLiable === '1'">
                                <label for="vat_number" class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-hashtag mr-2 text-blue-600"></i>
                                    <?= trans('prospect.vat_number', $lang) ?> <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="vat_number" name="vat_number"
                                       value="<?= htmlspecialchars($old['vat_number'] ?? '') ?>"
                                       placeholder="BE0123456789"
                                       class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                       x-bind:required="isVatLiable === '1'">
                            </div>
                            <div x-show="isVatLiable === '0'" class="flex items-center h-full pt-6" x-cloak>
                                <span class="text-sm text-gray-400 italic">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    <?= trans('prospect.not_vat_liable', $lang) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============================================ -->
                <!-- SECTION : COORDONNÉES -->
                <!-- ============================================ -->
                <div class="border-b border-gray-200 pb-6">
                    <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">
                        <i class="fas fa-address-book mr-2"></i><?= trans('prospect.section_contact', $lang) ?>
                    </h4>

                    <!-- Email + Confirmation -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-envelope mr-2 text-blue-600"></i>
                                <?= trans('prospect.email', $lang) ?> <span class="text-red-500">*</span>
                            </label>
                            <input type="email" id="email" name="email"
                                   x-model="email"
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                   required>
                        </div>
                        <div>
                            <label for="email_confirm" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-envelope-open mr-2 text-blue-600"></i>
                                <?= trans('prospect.email_confirm', $lang) ?> <span class="text-red-500">*</span>
                            </label>
                            <input type="email" id="email_confirm" name="email_confirm"
                                   x-model="emailConfirm"
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                   :class="{'border-red-500 bg-red-50': email && emailConfirm && email !== emailConfirm}"
                                   required>
                            <p x-show="email && emailConfirm && email !== emailConfirm"
                               x-cloak
                               class="mt-1 text-xs text-red-600">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                <?= trans('prospect.email_mismatch', $lang) ?>
                            </p>
                        </div>
                    </div>

                    <!-- Téléphone + Fax -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-phone mr-2 text-blue-600"></i>
                                <?= trans('prospect.phone', $lang) ?> <span class="text-red-500">*</span>
                            </label>
                            <input type="tel" id="phone" name="phone"
                                   value="<?= htmlspecialchars($old['phone'] ?? '') ?>"
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                   required>
                        </div>
                        <div>
                            <label for="fax" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-fax mr-2 text-blue-600"></i>
                                <?= trans('prospect.fax', $lang) ?>
                                <span class="text-gray-400 font-normal">(<?= trans('prospect.optional', $lang) ?>)</span>
                            </label>
                            <input type="tel" id="fax" name="fax"
                                   value="<?= htmlspecialchars($old['fax'] ?? '') ?>"
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        </div>
                    </div>
                </div>

                <!-- ============================================ -->
                <!-- SECTION : ADRESSE -->
                <!-- ============================================ -->
                <div class="border-b border-gray-200 pb-6">
                    <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">
                        <i class="fas fa-map-marker-alt mr-2"></i><?= trans('prospect.section_address', $lang) ?>
                    </h4>

                    <!-- Pays + Adresse -->
                    <div class="grid grid-cols-12 gap-4 mb-4">
                        <div class="col-span-12 md:col-span-3">
                            <label for="country" class="block text-sm font-semibold text-gray-700 mb-2">
                                <?= trans('prospect.country', $lang) ?> <span class="text-red-500">*</span>
                            </label>
                            <select id="country" name="country"
                                    x-model="country"
                                    @change="onCountryChange()"
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                    required>
                                <option value="BE">🇧🇪 <?= $lang === 'nl' ? 'België' : 'Belgique' ?></option>
                                <option value="LU">🇱🇺 Luxembourg</option>
                            </select>
                        </div>
                        <div class="col-span-12 md:col-span-9">
                            <label for="address" class="block text-sm font-semibold text-gray-700 mb-2">
                                <?= trans('prospect.address', $lang) ?> <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="address" name="address"
                                   value="<?= htmlspecialchars($old['address'] ?? '') ?>"
                                   placeholder="<?= trans('prospect.address_placeholder', $lang) ?>"
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                   required>
                        </div>
                    </div>

                    <!-- Code postal + Localité -->
                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-12 md:col-span-3">
                            <label for="postal_code" class="block text-sm font-semibold text-gray-700 mb-2">
                                <?= trans('prospect.postal_code', $lang) ?> <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="postal_code" name="postal_code"
                                   value="<?= htmlspecialchars($old['postal_code'] ?? '') ?>"
                                   x-model="postalCode"
                                   @input="onPostalCodeInput()"
                                   maxlength="6"
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                   required>
                        </div>
                        <div class="col-span-12 md:col-span-9">
                            <label for="city" class="block text-sm font-semibold text-gray-700 mb-2">
                                <?= trans('prospect.city', $lang) ?> <span class="text-red-500">*</span>
                                <span x-show="loadingLocalities" x-cloak class="text-blue-500 ml-1">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </span>
                            </label>
                            <select id="city" name="city"
                                    x-model="city"
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
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

                <!-- ============================================ -->
                <!-- INFORMATIONS COMPLÉMENTAIRES -->
                <!-- ============================================ -->
                <div>
                    <label for="additional_info" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-comment mr-2 text-blue-600"></i>
                        <?= trans('prospect.additional_info', $lang) ?>
                        <span class="text-gray-400 font-normal">(<?= trans('prospect.optional', $lang) ?>)</span>
                    </label>
                    <textarea id="additional_info" name="additional_info" rows="2"
                              class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                              placeholder="<?= trans('prospect.additional_placeholder', $lang) ?>"><?= htmlspecialchars($old['additional_info'] ?? '') ?></textarea>
                </div>

                <!-- Bouton submit -->
                <button type="submit"
                        :disabled="submitting || (email && emailConfirm && email !== emailConfirm)"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-6 rounded-lg transition-all duration-200 flex items-center justify-center space-x-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none">
                    <span x-show="!submitting"><?= trans('prospect.submit', $lang) ?></span>
                    <i x-show="!submitting" class="fas fa-arrow-right"></i>
                    <span x-show="submitting" x-cloak class="flex items-center gap-2">
                        <i class="fas fa-spinner fa-spin"></i>
                        <?= trans('prospect.loading', $lang) ?>
                    </span>
                </button>

                <p class="text-center text-sm text-gray-500">
                    <span class="text-red-500">*</span> <?= trans('prospect.required_fields', $lang) ?>
                </p>
            </form>
        </div>

        <!-- Section aide -->
        <?php
        $country = $campaign['country'] === 'BOTH' ? 'BE' : $campaign['country'];
        include __DIR__ . '/../components/public/help_box.php';
        ?>

    </div>
</main>

<?php
$content = ob_get_clean();

// Variables échappées pour JavaScript
$escapedEmail = addslashes($old['email'] ?? '');
$escapedCountry = addslashes($old['country'] ?? 'BE');
$escapedPostalCode = addslashes($old['postal_code'] ?? '');
$escapedCity = addslashes($old['city'] ?? '');
$escapedVatLiable = addslashes($old['is_vat_liable'] ?? '1');

// Scripts spécifiques à cette page
$pageScripts = <<<SCRIPT
<script>
    function prospectForm() {
        return {
            email: '{$escapedEmail}',
            emailConfirm: '',
            country: '{$escapedCountry}',
            postalCode: '{$escapedPostalCode}',
            city: '{$escapedCity}',
            isVatLiable: '{$escapedVatLiable}',
            localities: [],
            loadingLocalities: false,
            submitting: false,
            searchTimeout: null,

            init() {
                // Charger les localités si code postal pré-rempli
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
                    const response = await fetch('/stm/api/postal-codes/localities?code=' + encodeURIComponent(this.postalCode) + '&country=' + this.country);
                    const data = await response.json();

                    if (data.success && data.data) {
                        this.localities = data.data;

                        // Auto-sélection si une seule localité
                        if (this.localities.length === 1) {
                            const lang = '{$lang}';
                            this.city = this.localities[0]['locality_' + lang] || this.localities[0].locality_fr;
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
                // Vérifier correspondance emails
                if (this.email !== this.emailConfirm) {
                    event.preventDefault();
                    alert('Les adresses email ne correspondent pas.');
                    return false;
                }

                // Si non assujetti TVA, vider le champ
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
SCRIPT;

$pageStyles = <<<'STYLES'
<style>
    [x-cloak] { display: none !important; }
</style>
STYLES;

// Inclure le layout
require __DIR__ . '/../layouts/public.php';
?>