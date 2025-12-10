<?php
/**
 * Vue : Création d'un utilisateur
 *
 * @package STM
 * @created 2025/12/10
 * @modified 2025/12/10 - Suppression liaison rep (auto via Microsoft)
 */

use App\Models\User;
use Core\Session;

$activeMenu = 'users';
$oldInput = Session::get('old_input') ?? [];
Session::remove('old_input');

// Rôles disponibles pour création manuelle (pas rep, il est auto-créé)
$availableRoles = [
    'superadmin' => 'Super Admin',
    'admin' => 'Administrateur',
    'createur' => 'Créateur',
    'manager_reps' => 'Manager Reps'
];

ob_start();
?>

<!-- En-tête -->
<div class="mb-6">
    <div class="flex items-center gap-4">
        <a href="/stm/admin/users" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Nouvel utilisateur</h1>
            <p class="text-gray-600 mt-1">Créer un nouveau compte utilisateur</p>
        </div>
    </div>
</div>

<!-- Info -->
<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
    <div class="flex gap-3">
        <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
        <div class="text-sm text-blue-700">
            <p class="font-medium">Note sur les commerciaux (reps)</p>
            <p class="mt-1">Les comptes commerciaux sont créés automatiquement lors de leur première connexion Microsoft, si leur manager est un <strong>Manager Reps</strong> existant.</p>
        </div>
    </div>
</div>

<!-- Formulaire -->
<div class="bg-white rounded-lg shadow-sm" x-data="{ selectedRole: '<?= $oldInput['role'] ?? '' ?>' }">
    <form method="POST" action="/stm/admin/users" class="p-6 space-y-6">
        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

        <!-- Informations de base -->
        <div class="border-b border-gray-200 pb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Informations de base</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Nom -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Nom complet <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" required
                           value="<?= htmlspecialchars($oldInput['name'] ?? '') ?>"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="Jean Dupont">
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                        Email Microsoft <span class="text-red-500">*</span>
                    </label>
                    <input type="email" id="email" name="email" required
                           value="<?= htmlspecialchars($oldInput['email'] ?? '') ?>"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="jean.dupont@trendyfoods.com">
                    <p class="text-xs text-gray-500 mt-1">
                        <i class="fab fa-microsoft mr-1"></i>
                        Doit correspondre exactement à l'email du compte Microsoft Entra
                    </p>
                </div>
            </div>
        </div>

        <!-- Rôle et permissions -->
        <div class="pb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Rôle et permissions</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Rôle -->
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-1">
                        Rôle <span class="text-red-500">*</span>
                    </label>
                    <select id="role" name="role" required x-model="selectedRole"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="">-- Sélectionner un rôle --</option>
                        <?php foreach ($availableRoles as $value => $label): ?>
                        <option value="<?= $value ?>" <?= ($oldInput['role'] ?? '') === $value ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Actif -->
                <div class="flex items-center">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        <span class="ml-3 text-sm font-medium text-gray-700">Compte actif</span>
                    </label>
                </div>
            </div>

            <!-- Description du rôle -->
            <div class="mt-4 p-4 bg-gray-50 rounded-lg" x-show="selectedRole" x-cloak>
                <p class="text-sm font-medium text-gray-700 mb-2">Permissions du rôle :</p>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li x-show="selectedRole === 'superadmin'"><i class="fas fa-check text-green-500 mr-2"></i>Accès complet à toutes les fonctionnalités</li>
                    <li x-show="selectedRole === 'admin'"><i class="fas fa-check text-green-500 mr-2"></i>Gestion des campagnes, produits, clients, stats (pas de gestion utilisateurs)</li>
                    <li x-show="selectedRole === 'createur'"><i class="fas fa-check text-green-500 mr-2"></i>Création de campagnes, catégories, produits (modification de ses créations uniquement)</li>
                    <li x-show="selectedRole === 'manager_reps'"><i class="fas fa-check text-green-500 mr-2"></i>Visualisation des campagnes et stats de ses commerciaux (liaison auto)</li>
                </ul>
            </div>
        </div>

        <!-- Boutons -->
        <div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-200">
            <a href="/stm/admin/users"
               class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                Annuler
            </a>
            <button type="submit"
                    class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                <i class="fas fa-save mr-2"></i>Créer l'utilisateur
            </button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
$pageScripts = '';
require __DIR__ . '/../../layouts/admin.php';
?>