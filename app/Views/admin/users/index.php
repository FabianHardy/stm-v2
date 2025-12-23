<?php
/**
 * Vue : Liste des utilisateurs
 *
 * Affiche la liste des utilisateurs avec filtres et pagination
 *
 * @package STM
 * @created 2025/12/10
 * @modified 2025/12/16 - Ajout bouton "Se connecter en tant que"
 */

use App\Models\User;
use Core\Session;

$activeMenu = 'users';

// Vérifier si l'utilisateur courant est superadmin (pour le bouton impersonate)
$currentUser = Session::get('user');
$isSuperAdmin = ($currentUser['role'] ?? '') === 'superadmin';
$isImpersonating = Session::get('impersonate_original_user') !== null;

ob_start();
?>

<!-- En-tête -->
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Gestion des utilisateurs</h1>
            <p class="text-gray-600 mt-1">Gérer les accès et les rôles des utilisateurs</p>
        </div>
        <a href="/stm/admin/users/create"
           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition shadow-sm">
            <i class="fas fa-plus"></i>
            <span>Nouvel utilisateur</span>
        </a>
    </div>
</div>

<!-- Statistiques rapides -->
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-users text-indigo-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900"><?= $stats['total'] ?? 0 ?></p>
                <p class="text-xs text-gray-500">Total</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-check-circle text-green-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-green-600"><?= $stats['active'] ?? 0 ?></p>
                <p class="text-xs text-gray-500">Actifs</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-user-shield text-red-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-red-600"><?= ($stats['superadmins'] ?? 0) + ($stats['admins'] ?? 0) ?></p>
                <p class="text-xs text-gray-500">Admins</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-user-tie text-orange-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-orange-600"><?= $stats['managers'] ?? 0 ?></p>
                <p class="text-xs text-gray-500">Managers</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-briefcase text-blue-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-blue-600"><?= $stats['reps'] ?? 0 ?></p>
                <p class="text-xs text-gray-500">Commerciaux</p>
            </div>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <form method="GET" action="/stm/admin/users" class="flex flex-wrap gap-4 items-end">

        <!-- Recherche -->
        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium text-gray-700 mb-1">Rechercher</label>
            <div class="relative">
                <input type="text" name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                       placeholder="Nom ou email..."
                       class="w-full border border-gray-300 rounded-lg pl-10 pr-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            </div>
        </div>

        <!-- Filtre rôle -->
        <div class="w-48">
            <label class="block text-sm font-medium text-gray-700 mb-1">Rôle</label>
            <select name="role" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500">
                <option value="">Tous les rôles</option>
                <?php foreach ($roles as $value => $label): ?>
                <option value="<?= $value ?>" <?= ($filters['role'] ?? '') === $value ? 'selected' : '' ?>>
                    <?= htmlspecialchars($label) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Filtre statut -->
        <div class="w-40">
            <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
            <select name="status" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500">
                <option value="">Tous</option>
                <option value="1" <?= ($filters['status'] ?? '') === '1' ? 'selected' : '' ?>>Actifs</option>
                <option value="0" <?= ($filters['status'] ?? '') === '0' ? 'selected' : '' ?>>Inactifs</option>
            </select>
        </div>

        <!-- Boutons -->
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                <i class="fas fa-filter mr-2"></i>Filtrer
            </button>
            <a href="/stm/admin/users" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                <i class="fas fa-times"></i>
            </a>
        </div>
    </form>
</div>

<!-- Tableau des utilisateurs -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden">

    <?php if (empty($users)): ?>
    <div class="text-center py-12">
        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-users text-gray-400 text-2xl"></i>
        </div>
        <p class="text-gray-500 font-medium">Aucun utilisateur trouvé</p>
        <p class="text-sm text-gray-400 mt-1">Modifiez vos filtres ou créez un nouvel utilisateur</p>
    </div>
    <?php else: ?>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Utilisateur</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Rôle</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Rep lié</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Dernière connexion</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($users as $user): ?>
                <tr class="hover:bg-gray-50 transition <?= !$user['is_active'] ? 'opacity-60' : '' ?>" data-user-id="<?= $user['id'] ?>">

                    <!-- Utilisateur -->
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-sm font-bold text-indigo-600">
                                    <?= strtoupper(substr($user['name'], 0, 2)) ?>
                                </span>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900"><?= htmlspecialchars($user['name']) ?></p>
                                <p class="text-sm text-gray-500"><?= htmlspecialchars($user['email']) ?></p>
                            </div>
                        </div>
                    </td>

                    <!-- Rôle -->
                    <td class="px-6 py-4">
                        <?php
                        $roleColors = [
                            'superadmin' => 'bg-red-100 text-red-800',
                            'admin' => 'bg-purple-100 text-purple-800',
                            'createur' => 'bg-blue-100 text-blue-800',
                            'manager_reps' => 'bg-orange-100 text-orange-800',
                            'rep' => 'bg-green-100 text-green-800'
                        ];
                        $roleColor = $roleColors[$user['role']] ?? 'bg-gray-100 text-gray-800';
                        $roleLabel = User::ROLE_LABELS[$user['role']] ?? $user['role'];
                        ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $roleColor ?>">
                            <?= htmlspecialchars($roleLabel) ?>
                        </span>
                    </td>

                    <!-- Rep lié -->
                    <td class="px-6 py-4">
                        <?php if (!empty($user['rep_id'])): ?>
                        <span class="text-sm text-gray-900">
                            <?= htmlspecialchars($user['rep_id']) ?>
                            <?php if (!empty($user['rep_country'])): ?>
                            <span class="text-gray-400">(<?= $user['rep_country'] ?>)</span>
                            <?php endif; ?>
                        </span>
                        <?php else: ?>
                        <span class="text-sm text-gray-400">—</span>
                        <?php endif; ?>
                    </td>

                    <!-- Statut -->
                    <td class="px-6 py-4 text-center">
                        <?php if ($user['role'] !== 'superadmin'): ?>
                        <button onclick="toggleUser(<?= $user['id'] ?>, this)"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors
                                       <?= $user['is_active'] ? 'bg-indigo-600' : 'bg-gray-200' ?>">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform
                                         <?= $user['is_active'] ? 'translate-x-5' : 'translate-x-1' ?>"></span>
                        </button>
                        <?php else: ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Toujours actif
                        </span>
                        <?php endif; ?>
                    </td>

                    <!-- Dernière connexion -->
                    <td class="px-6 py-4">
                        <?php if (!empty($user['last_login_at'])): ?>
                        <span class="text-sm text-gray-600">
                            <?= date('d/m/Y H:i', strtotime($user['last_login_at'])) ?>
                        </span>
                        <?php else: ?>
                        <span class="text-sm text-gray-400">Jamais</span>
                        <?php endif; ?>
                    </td>

                    <!-- Actions -->
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">

                            <?php
                            // Bouton "Se connecter en tant que" - seulement pour superadmin,
                            // pas sur les superadmins, pas si déjà en mode impersonate,
                            // et seulement sur les utilisateurs actifs
                            $canImpersonate = $isSuperAdmin
                                && !$isImpersonating
                                && $user['role'] !== 'superadmin'
                                && $user['is_active'];
                            ?>
                            <?php if ($canImpersonate): ?>
                            <a href="/stm/admin/users/<?= $user['id'] ?>/impersonate"
                               class="p-2 text-gray-400 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition"
                               title="Se connecter en tant que <?= htmlspecialchars($user['name']) ?>"
                               onclick="return confirm('Vous allez vous connecter en tant que <?= htmlspecialchars(addslashes($user['name'])) ?>.\n\nVous pourrez revenir à votre compte à tout moment.\n\nContinuer ?')">
                                <i class="fas fa-user-secret"></i>
                            </a>
                            <?php endif; ?>

                            <a href="/stm/admin/users/<?= $user['id'] ?>/edit"
                               class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition"
                               title="Modifier">
                                <i class="fas fa-edit"></i>
                            </a>

                            <?php if ($user['role'] !== 'superadmin'): ?>
                            <button onclick="confirmDelete(<?= $user['id'] ?>, '<?= htmlspecialchars(addslashes($user['name'])) ?>')"
                                    class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
                                    title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php else: ?>
                            <span class="p-2 text-gray-200 cursor-not-allowed" title="Impossible de supprimer un superadmin">
                                <i class="fas fa-trash"></i>
                            </span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($pagination['total'] > 1): ?>
    <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
        <p class="text-sm text-gray-500">
            Page <?= $pagination['current'] ?> sur <?= $pagination['total'] ?>
            (<?= $pagination['count'] ?> utilisateurs)
        </p>

        <div class="flex gap-2">
            <?php if ($pagination['current'] > 1): ?>
            <a href="?<?= http_build_query(array_merge($filters, ['page' => $pagination['current'] - 1])) ?>"
               class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50 text-sm">
                <i class="fas fa-chevron-left"></i>
            </a>
            <?php endif; ?>

            <?php for ($i = max(1, $pagination['current'] - 2); $i <= min($pagination['total'], $pagination['current'] + 2); $i++): ?>
            <a href="?<?= http_build_query(array_merge($filters, ['page' => $i])) ?>"
               class="px-3 py-1 border rounded text-sm <?= $i === $pagination['current'] ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-300 hover:bg-gray-50' ?>">
                <?= $i ?>
            </a>
            <?php endfor; ?>

            <?php if ($pagination['current'] < $pagination['total']): ?>
            <a href="?<?= http_build_query(array_merge($filters, ['page' => $pagination['current'] + 1])) ?>"
               class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50 text-sm">
                <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>
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
                Êtes-vous sûr de vouloir supprimer l'utilisateur <strong id="deleteUserName"></strong> ?
                <br><span class="text-sm text-red-600">Cette action est irréversible.</span>
            </p>

            <form id="deleteForm" method="POST" class="flex gap-3 justify-center">
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
?>

<script>
// Toggle activation utilisateur
async function toggleUser(userId, btn) {
    try {
        const response = await fetch('/stm/admin/users/' + userId + '/toggle', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        });

        const data = await response.json();

        if (data.success) {
            // Toggle visuel
            if (data.active) {
                btn.classList.remove('bg-gray-200');
                btn.classList.add('bg-indigo-600');
                btn.querySelector('span').classList.remove('translate-x-0');
                btn.querySelector('span').classList.add('translate-x-5');
                btn.closest('tr').classList.remove('opacity-60');
            } else {
                btn.classList.remove('bg-indigo-600');
                btn.classList.add('bg-gray-200');
                btn.querySelector('span').classList.remove('translate-x-5');
                btn.querySelector('span').classList.add('translate-x-0');
                btn.closest('tr').classList.add('opacity-60');
            }

            // Notification
            showToast(data.message, 'success');
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Erreur de connexion', 'error');
    }
}

// Modal de suppression
function confirmDelete(userId, userName) {
    document.getElementById('deleteUserName').textContent = userName;
    document.getElementById('deleteForm').action = '/stm/admin/users/' + userId + '/delete';
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('deleteModal').classList.add('flex');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    document.getElementById('deleteModal').classList.remove('flex');
}

// Toast notification
function showToast(message, type) {
    type = type || 'info';
    const toast = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-600' : type === 'error' ? 'bg-red-600' : 'bg-indigo-600';
    const icon = type === 'success' ? 'check' : type === 'error' ? 'times' : 'info';
    toast.className = 'fixed bottom-4 right-4 px-4 py-3 rounded-lg shadow-lg text-white z-50 ' + bgColor;
    toast.innerHTML = '<i class="fas fa-' + icon + '-circle mr-2"></i>' + message;
    document.body.appendChild(toast);

    setTimeout(function() {
        toast.remove();
    }, 3000);
}

// Fermer modal avec Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDeleteModal();
    }
});
</script>

<?php
$pageScripts = '';
require __DIR__ . '/../../layouts/admin.php';
?>