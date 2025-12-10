<?php
/**
 * Vue : Modification d'un utilisateur
 * 
 * @package STM
 * @created 2025/12/10
 */

use App\Models\User;

$activeMenu = 'users';
ob_start();
?>

<!-- En-tête -->
<div class="mb-6">
    <div class="flex items-center gap-4">
        <a href="/stm/admin/users" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Modifier l'utilisateur</h1>
            <p class="text-gray-600 mt-1"><?= htmlspecialchars($user['name']) ?> - <?= htmlspecialchars($user['email']) ?></p>
        </div>
    </div>
</div>

<!-- Formulaire -->
<div class="bg-white rounded-lg shadow-sm" x-data="userForm()">
    <form method="POST" action="/stm/admin/users/<?= $user['id'] ?>" class="p-6 space-y-6">
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
                           value="<?= htmlspecialchars($user['name']) ?>"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                
                <!-- Email (lecture seule) -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                        Email
                    </label>
                    <input type="email" id="email" disabled
                           value="<?= htmlspecialchars($user['email']) ?>"
                           class="w-full border border-gray-200 rounded-lg px-4 py-2 bg-gray-50 text-gray-500 cursor-not-allowed">
                    <p class="text-xs text-gray-500 mt-1">
                        <i class="fas fa-lock mr-1"></i>
                        L'email ne peut pas être modifié (lié à Microsoft)
                    </p>
                </div>
            </div>
            
            <!-- Infos Microsoft -->
            <?php if ($user['microsoft_id']): ?>
            <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                <p class="text-sm text-blue-700">
                    <i class="fab fa-microsoft mr-2"></i>
                    Compte lié à Microsoft Entra
                    <span class="text-xs text-blue-500 ml-2">(ID: <?= htmlspecialchars(substr($user['microsoft_id'], 0, 8)) ?>...)</span>
                </p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Rôle et permissions -->
        <div class="border-b border-gray-200 pb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Rôle et permissions</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Rôle -->
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-1">
                        Rôle <span class="text-red-500">*</span>
                    </label>
                    <select id="role" name="role" required x-model="selectedRole"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <?php foreach ($roles as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $user['role'] === $value ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Actif -->
                <div class="flex items-center">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" class="sr-only peer" 
                               <?= $user['is_active'] ? 'checked' : '' ?>>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        <span class="ml-3 text-sm font-medium text-gray-700">Compte actif</span>
                    </label>
                </div>
            </div>
            
            <!-- Description du rôle -->
            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                <p class="text-sm font-medium text-gray-700 mb-2">Permissions du rôle :</p>
                <ul class="text-sm text-gray-600 space-y-1">
                    <template x-if="selectedRole === 'superadmin'">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Accès complet à toutes les fonctionnalités</li>
                    </template>
                    <template x-if="selectedRole === 'admin'">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Gestion des campagnes, produits, clients, stats (pas de gestion utilisateurs)</li>
                    </template>
                    <template x-if="selectedRole === 'createur'">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Création de campagnes, catégories, produits (modification de ses créations uniquement)</li>
                    </template>
                    <template x-if="selectedRole === 'manager_reps'">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Visualisation des campagnes et stats de ses commerciaux</li>
                    </template>
                    <template x-if="selectedRole === 'rep'">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Visualisation de ses propres clients, stats et commandes</li>
                    </template>
                </ul>
            </div>
        </div>
        
        <!-- Liaison représentant (si rôle rep ou manager_reps) -->
        <div class="pb-6" x-show="selectedRole === 'rep' || selectedRole === 'manager_reps'">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Liaison représentant</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Pays -->
                <div>
                    <label for="rep_country" class="block text-sm font-medium text-gray-700 mb-1">
                        Pays <span class="text-red-500">*</span>
                    </label>
                    <select id="rep_country" name="rep_country" x-model="selectedCountry"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="">-- Sélectionner --</option>
                        <option value="BE" <?= $user['rep_country'] === 'BE' ? 'selected' : '' ?>>🇧🇪 Belgique</option>
                        <option value="LU" <?= $user['rep_country'] === 'LU' ? 'selected' : '' ?>>🇱🇺 Luxembourg</option>
                    </select>
                </div>
                
                <!-- Représentant -->
                <div>
                    <label for="rep_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Représentant <span class="text-red-500">*</span>
                    </label>
                    <select id="rep_id" name="rep_id"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="">-- Sélectionner un pays d'abord --</option>
                        
                        <template x-if="selectedCountry === 'BE'">
                            <optgroup label="🇧🇪 Belgique">
                                <?php foreach ($reps['BE'] as $rep): ?>
                                <option value="<?= htmlspecialchars($rep['id']) ?>" <?= $user['rep_id'] === $rep['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($rep['name']) ?> (<?= htmlspecialchars($rep['id']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </optgroup>
                        </template>
                        
                        <template x-if="selectedCountry === 'LU'">
                            <optgroup label="🇱🇺 Luxembourg">
                                <?php foreach ($reps['LU'] as $rep): ?>
                                <option value="<?= htmlspecialchars($rep['id']) ?>" <?= $user['rep_id'] === $rep['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($rep['name']) ?> (<?= htmlspecialchars($rep['id']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </optgroup>
                        </template>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Infos système -->
        <div class="pb-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Informations système</h2>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <p class="text-gray-500">ID</p>
                    <p class="font-medium text-gray-900">#<?= $user['id'] ?></p>
                </div>
                <div>
                    <p class="text-gray-500">Créé le</p>
                    <p class="font-medium text-gray-900"><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></p>
                </div>
                <div>
                    <p class="text-gray-500">Modifié le</p>
                    <p class="font-medium text-gray-900"><?= date('d/m/Y H:i', strtotime($user['updated_at'])) ?></p>
                </div>
                <div>
                    <p class="text-gray-500">Dernière connexion</p>
                    <p class="font-medium text-gray-900">
                        <?= $user['last_login_at'] ? date('d/m/Y H:i', strtotime($user['last_login_at'])) : 'Jamais' ?>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Boutons -->
        <div class="flex items-center justify-between pt-6">
            <button type="button" onclick="confirmDelete(<?= $user['id'] ?>, '<?= htmlspecialchars(addslashes($user['name'])) ?>')"
                    class="px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                <i class="fas fa-trash mr-2"></i>Supprimer
            </button>
            
            <div class="flex gap-4">
                <a href="/stm/admin/users" 
                   class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                    Annuler
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                    <i class="fas fa-save mr-2"></i>Enregistrer
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Modal de suppression -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
        <div class="text-center">
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">Confirmer la suppression</h3>
            <p class="text-gray-600 mb-6">
                Êtes-vous sûr de vouloir supprimer cet utilisateur ?
                <br><span class="text-sm text-red-600">Cette action est irréversible.</span>
            </p>
            
            <form id="deleteForm" method="POST" action="/stm/admin/users/<?= $user['id'] ?>/delete" class="flex gap-3 justify-center">
                <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                
                <button type="button" onclick="closeDeleteModal()"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                    Annuler
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                    <i class="fas fa-trash mr-2"></i>Supprimer
                </button>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$pageScripts = <<<SCRIPTS
<script>
function userForm() {
    return {
        selectedRole: '<?= $user['role'] ?>',
        selectedCountry: '<?= $user['rep_country'] ?? '' ?>'
    }
}

function confirmDelete() {
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('deleteModal').classList.add('flex');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    document.getElementById('deleteModal').classList.remove('flex');
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeDeleteModal();
});
</script>
SCRIPTS;

require __DIR__ . '/../../layouts/admin.php';
?>
