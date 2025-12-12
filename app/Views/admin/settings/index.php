<?php
/**
 * Vue : Configuration système
 *
 * Affiche la configuration avec onglets :
 * - Permissions (matrice rôles × permissions éditable)
 * - Général (à venir)
 *
 * @package STM
 * @created 2025/12/10
 * @modified 2025/12/10 - Matrice permissions éditable avec toasts
 * @modified 2025/12/12 - Gestion hiérarchique : grise les rôles non gérables
 */

$activeMenu = 'settings';
ob_start();
?>

<!-- En-tête -->
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Configuration</h1>
            <p class="text-gray-600 mt-1">Paramètres système et permissions</p>
        </div>
    </div>
</div>

<!-- Onglets -->
<div class="mb-6">
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex gap-6">
            <a href="?tab=permissions"
               class="py-3 px-1 border-b-2 font-medium text-sm transition <?= $activeTab === 'permissions' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                <i class="fas fa-shield-alt mr-2"></i>Permissions
            </a>
            <a href="?tab=general"
               class="py-3 px-1 border-b-2 font-medium text-sm transition <?= $activeTab === 'general' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                <i class="fas fa-cog mr-2"></i>Général
            </a>
        </nav>
    </div>
</div>

<?php if ($activeTab === 'permissions'): ?>
<!-- Onglet Permissions -->
<form id="permissions-form">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">

        <!-- En-tête -->
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Matrice des permissions</h2>
                    <p class="text-sm text-gray-500 mt-1">
                        <?php if ($canEditPermissions): ?>
                            Cochez les permissions pour chaque rôle
                            <?php if (!empty($manageableRoles) && count($manageableRoles) < 5): ?>
                                <span class="text-amber-600">
                                    (vous pouvez modifier : <?= implode(', ', array_map(function($r) use ($roleLabels) { return $roleLabels[$r] ?? $r; }, $manageableRoles)) ?>)
                                </span>
                            <?php endif; ?>
                        <?php else: ?>
                            Permissions accordées par rôle (lecture seule)
                        <?php endif; ?>
                    </p>
                </div>
                <div class="flex items-center gap-4">
                    <?php if ($canEditPermissions): ?>
                        <button type="button" id="btn-save-permissions"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-save mr-2"></i>
                            Enregistrer
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tableau des permissions -->
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider w-64">
                            Permission
                        </th>
                        <?php foreach ($matrixData['roles'] as $roleKey): ?>
                        <?php
                            $isProtected = \App\Helpers\PermissionHelper::isProtectedRole($roleKey);
                            $canManage = in_array($roleKey, $manageableRoles ?? []);
                            $roleColorClass = '';
                            switch ($roleKey) {
                                case 'superadmin': $roleColorClass = 'bg-red-50 text-red-700'; break;
                                case 'admin': $roleColorClass = 'bg-purple-50 text-purple-700'; break;
                                case 'createur': $roleColorClass = 'bg-blue-50 text-blue-700'; break;
                                case 'manager_reps': $roleColorClass = 'bg-orange-50 text-orange-700'; break;
                                case 'rep': $roleColorClass = 'bg-green-50 text-green-700'; break;
                            }
                        ?>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider w-28 <?= $roleColorClass ?>">
                            <?= htmlspecialchars($roleLabels[$roleKey] ?? $roleKey) ?>
                            <?php if ($isProtected): ?>
                                <i class="fas fa-lock text-xs ml-1" title="Rôle protégé"></i>
                            <?php elseif (!$canManage && $canEditPermissions): ?>
                                <i class="fas fa-ban text-xs ml-1 text-gray-400" title="Niveau égal ou supérieur au vôtre"></i>
                            <?php endif; ?>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php
                    $currentCategory = '';
                    foreach ($matrixData['permissions'] as $perm):
                        // Afficher la catégorie si elle change
                        if ($perm['category'] !== $currentCategory):
                            $currentCategory = $perm['category'];
                            $catInfo = $categories[$currentCategory] ?? ['label' => $currentCategory, 'icon' => 'fa-circle'];
                    ?>
                    <!-- Ligne de catégorie -->
                    <tr class="bg-gray-50">
                        <td colspan="<?= count($matrixData['roles']) + 1 ?>" class="px-6 py-3">
                            <div class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                                <i class="fas <?= $catInfo['icon'] ?? 'fa-circle' ?> text-gray-500"></i>
                                <?= htmlspecialchars($catInfo['label'] ?? $currentCategory) ?>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-3 text-sm text-gray-700 pl-10">
                            <div>
                                <?= htmlspecialchars($perm['name'] ?? $perm['code']) ?>
                                <span class="text-xs text-gray-400 ml-1">(<?= htmlspecialchars($perm['code']) ?>)</span>
                            </div>
                            <?php if (!empty($perm['description'])): ?>
                                <p class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($perm['description']) ?></p>
                            <?php endif; ?>
                        </td>

                        <?php foreach ($matrixData['roles'] as $roleKey): ?>
                        <?php
                            $hasPermission = $matrixData['matrix'][$roleKey][$perm['code']] ?? false;
                            $isProtected = \App\Helpers\PermissionHelper::isProtectedRole($roleKey);
                            $canManage = in_array($roleKey, $manageableRoles ?? []);
                            $canGrantThis = \App\Helpers\PermissionHelper::can($perm['code']);

                            // Désactivé si : pas de permission d'édition, OU rôle protégé, OU ne peut pas gérer ce rôle, OU ne possède pas cette permission
                            $isDisabled = !$canEditPermissions || $isProtected || !$canManage || !$canGrantThis;
                        ?>
                        <td class="px-4 py-3 text-center">
                            <?php if ($isDisabled): ?>
                                <!-- Affichage lecture seule -->
                                <?php if ($hasPermission): ?>
                                    <span class="inline-flex items-center justify-center w-6 h-6 bg-green-100 rounded-full" title="<?= !$canGrantThis && $canEditPermissions ? 'Vous ne possédez pas cette permission' : '' ?>">
                                        <i class="fas fa-check text-green-600 text-xs"></i>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center justify-center w-6 h-6 bg-gray-100 rounded-full">
                                        <i class="fas fa-times text-gray-400 text-xs"></i>
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <!-- Checkbox éditable -->
                                <label class="inline-flex items-center justify-center cursor-pointer">
                                    <input type="checkbox"
                                           name="permissions[<?= htmlspecialchars($roleKey) ?>][<?= htmlspecialchars($perm['code']) ?>]"
                                           value="1"
                                           <?= $hasPermission ? 'checked' : '' ?>
                                           class="permission-checkbox w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 cursor-pointer">
                                </label>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="p-4 bg-gray-50 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <p class="text-xs text-gray-500">
                    <i class="fas fa-lock mr-1"></i>
                    Le rôle <strong>Super Admin</strong> a toujours toutes les permissions et ne peut pas être modifié.
                    <?php if ($canEditPermissions && !empty($manageableRoles) && count($manageableRoles) < 4): ?>
                        <br><i class="fas fa-info-circle mr-1 text-amber-500"></i>
                        <span class="text-amber-600">Vous ne pouvez modifier que les rôles de niveau inférieur au vôtre et accorder uniquement les permissions que vous possédez.</span>
                    <?php endif; ?>
                </p>
                <?php if ($canEditPermissions): ?>
                    <button type="button" id="btn-save-permissions-bottom"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-save mr-2"></i>
                        Enregistrer les modifications
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</form>

<!-- Légende des rôles -->
<div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <div class="p-4 bg-red-50 rounded-lg border border-red-100">
        <div class="flex items-center gap-2 mb-2">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Super Admin</span>
            <i class="fas fa-lock text-red-400 text-xs"></i>
        </div>
        <p class="text-sm text-gray-600">Accès total. Toutes les permissions, toujours. <strong>Non modifiable.</strong></p>
    </div>

    <div class="p-4 bg-purple-50 rounded-lg border border-purple-100">
        <div class="flex items-center gap-2 mb-2">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">Administrateur</span>
        </div>
        <p class="text-sm text-gray-600">Gestion complète sauf les utilisateurs internes. Accès à toutes les campagnes.</p>
    </div>

    <div class="p-4 bg-blue-50 rounded-lg border border-blue-100">
        <div class="flex items-center gap-2 mb-2">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Créateur</span>
        </div>
        <p class="text-sm text-gray-600">Crée et gère ses propres campagnes. Accès limité aux campagnes où il est assigné.</p>
    </div>

    <div class="p-4 bg-orange-50 rounded-lg border border-orange-100">
        <div class="flex items-center gap-2 mb-2">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800">Manager Reps</span>
        </div>
        <p class="text-sm text-gray-600">Supervise une équipe de commerciaux. Lecture seule sur les campagnes.</p>
    </div>

    <div class="p-4 bg-green-50 rounded-lg border border-green-100">
        <div class="flex items-center gap-2 mb-2">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Commercial</span>
        </div>
        <p class="text-sm text-gray-600">Lecture seule. Voit uniquement <strong>ses propres clients</strong> et commandes.</p>
    </div>
</div>

<?php elseif ($activeTab === 'general'): ?>
<!-- Onglet Général -->
<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="text-center py-12">
        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-cog text-gray-400 text-2xl"></i>
        </div>
        <p class="text-gray-500 font-medium">Paramètres généraux</p>
        <p class="text-sm text-gray-400 mt-1">À venir dans une prochaine version</p>
    </div>
</div>

<?php endif; ?>

<?php
$content = ob_get_clean();

// Scripts pour la sauvegarde AJAX avec toasts
$pageScripts = <<<'SCRIPT'
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('permissions-form');
    const btnSave = document.getElementById('btn-save-permissions');
    const btnSaveBottom = document.getElementById('btn-save-permissions-bottom');

    if (!form) return;

    /**
     * Affiche un toast notification (même style que le système existant)
     */
    function showToast(type, message) {
        // Trouver ou créer le container
        let container = document.getElementById('toast-container');

        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'fixed bottom-4 right-4 z-50 flex flex-col gap-3 max-w-sm';
            document.body.appendChild(container);
        }

        // Configuration des couleurs par type
        const config = {
            success: { bg: 'bg-green-600', icon: 'fa-check-circle' },
            error: { bg: 'bg-red-600', icon: 'fa-exclamation-circle' },
            warning: { bg: 'bg-yellow-500', icon: 'fa-exclamation-triangle' },
            info: { bg: 'bg-blue-600', icon: 'fa-info-circle' }
        };

        const conf = config[type] || config.info;
        const toastId = 'toast_' + Date.now();

        // Créer le toast
        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = conf.bg + ' flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg text-white min-w-[280px] transform translate-x-full opacity-0 transition-all duration-300';
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="flex-shrink-0">
                <i class="fas ${conf.icon} text-lg"></i>
            </div>
            <div class="flex-1 text-sm font-medium">${message}</div>
            <button type="button" class="flex-shrink-0 p-1 hover:bg-white hover:bg-opacity-20 rounded transition-colors" onclick="this.parentElement.remove()">
                <span class="sr-only">Fermer</span>
                <i class="fas fa-times text-sm"></i>
            </button>
        `;

        container.appendChild(toast);

        // Animation d'entrée
        requestAnimationFrame(function() {
            toast.classList.remove('translate-x-full', 'opacity-0');
            toast.classList.add('translate-x-0', 'opacity-100');
        });

        // Auto-dismiss après 5 secondes
        setTimeout(function() {
            toast.classList.remove('translate-x-0', 'opacity-100');
            toast.classList.add('translate-x-full', 'opacity-0');
            setTimeout(function() {
                toast.remove();
            }, 300);
        }, 5000);
    }

    /**
     * Sauvegarde les permissions
     */
    async function savePermissions() {
        const formData = new FormData(form);
        const csrfToken = formData.get('csrf_token');

        // Construire l'objet permissions
        const permissions = {};
        const checkboxes = form.querySelectorAll('.permission-checkbox');

        checkboxes.forEach(function(checkbox) {
            const match = checkbox.name.match(/permissions\[([^\]]+)\]\[([^\]]+)\]/);
            if (match) {
                const role = match[1];
                const permCode = match[2];

                if (!permissions[role]) {
                    permissions[role] = {};
                }
                permissions[role][permCode] = checkbox.checked ? 1 : 0;
            }
        });

        // Désactiver les boutons
        if (btnSave) {
            btnSave.disabled = true;
            btnSave.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Enregistrement...';
        }
        if (btnSaveBottom) {
            btnSaveBottom.disabled = true;
            btnSaveBottom.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Enregistrement...';
        }

        try {
            const response = await fetch('/stm/admin/settings/permissions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    csrf_token: csrfToken,
                    permissions: permissions
                })
            });

            const result = await response.json();

            if (result.success) {
                showToast('success', result.message);

                // Afficher les warnings s'il y en a
                if (result.warnings && result.warnings.length > 0) {
                    setTimeout(function() {
                        result.warnings.forEach(function(warning) {
                            showToast('warning', warning);
                        });
                    }, 500);
                }
            } else {
                showToast('error', result.message);

                // Afficher les erreurs détaillées
                if (result.errors && result.errors.length > 0) {
                    setTimeout(function() {
                        result.errors.slice(0, 3).forEach(function(error) {
                            showToast('warning', error);
                        });
                    }, 500);
                }
            }

        } catch (error) {
            console.error('Erreur:', error);
            showToast('error', 'Erreur de connexion au serveur');
        } finally {
            // Réactiver les boutons
            if (btnSave) {
                btnSave.disabled = false;
                btnSave.innerHTML = '<i class="fas fa-save mr-2"></i>Enregistrer';
            }
            if (btnSaveBottom) {
                btnSaveBottom.disabled = false;
                btnSaveBottom.innerHTML = '<i class="fas fa-save mr-2"></i>Enregistrer les modifications';
            }
        }
    }

    // Event listeners
    if (btnSave) {
        btnSave.addEventListener('click', savePermissions);
    }
    if (btnSaveBottom) {
        btnSaveBottom.addEventListener('click', savePermissions);
    }
});
</script>
SCRIPT;

require __DIR__ . '/../../layouts/admin.php';
?>