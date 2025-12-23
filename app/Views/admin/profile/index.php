<?php
/**
 * Page Mon Profil
 *
 * Affiche les informations de l'utilisateur connecté
 * et permet de modifier l'avatar et le mot de passe
 *
 * @package STM
 * @version 1.0
 * @created 19/12/2025
 */

use Core\Session;

$title = 'Mon profil';

// Générer les initiales
$nameParts = explode(' ', $user['name'] ?? 'U');
$initials = '';
foreach ($nameParts as $part) {
    if (!empty($part)) {
        $initials .= strtoupper(substr($part, 0, 1));
        if (strlen($initials) >= 2) break;
    }
}

// Labels des rôles
$roleLabels = [
    'superadmin' => 'Super Administrateur',
    'admin' => 'Administrateur',
    'createur' => 'Créateur de campagnes',
    'manager_reps' => 'Manager Commerciaux',
    'rep' => 'Commercial',
];

$roleColors = [
    'superadmin' => 'bg-red-100 text-red-800',
    'admin' => 'bg-purple-100 text-purple-800',
    'createur' => 'bg-blue-100 text-blue-800',
    'manager_reps' => 'bg-green-100 text-green-800',
    'rep' => 'bg-gray-100 text-gray-800',
];

$statusColors = [
    'active' => 'bg-green-100 text-green-700',
    'upcoming' => 'bg-blue-100 text-blue-700',
    'ended' => 'bg-gray-100 text-gray-600',
];

ob_start();
?>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Mon profil</h1>
            <p class="text-gray-600 mt-1">Gérez vos informations personnelles</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Colonne gauche : Infos + Avatar -->
        <div class="lg:col-span-1 space-y-6">

            <!-- Carte profil -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">

                <!-- Header avec gradient -->
                <div class="h-24 bg-gradient-to-r from-primary-600 to-primary-700"></div>

                <!-- Avatar -->
                <div class="px-6 pb-6">
                    <div class="-mt-12 mb-4">
                        <?php if (!empty($user['avatar'])): ?>
                            <img src="<?= htmlspecialchars($user['avatar']) ?>"
                                 alt="Avatar"
                                 class="h-24 w-24 rounded-full border-4 border-white shadow-lg object-cover">
                        <?php else: ?>
                            <div class="h-24 w-24 rounded-full border-4 border-white shadow-lg bg-primary-600 flex items-center justify-center text-white text-2xl font-bold">
                                <?= $initials ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Nom et rôle -->
                    <h2 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($user['name']) ?></h2>
                    <p class="text-gray-600"><?= htmlspecialchars($user['email']) ?></p>

                    <div class="mt-3">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= $roleColors[$user['role']] ?? 'bg-gray-100 text-gray-800' ?>">
                            <?= $roleLabels[$user['role']] ?? ucfirst($user['role']) ?>
                        </span>
                    </div>

                    <!-- Dates -->
                    <div class="mt-4 pt-4 border-t border-gray-200 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Membre depuis</span>
                            <span class="text-gray-900 font-medium">
                                <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modifier l'avatar -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-camera text-primary-600 mr-2"></i>
                    Photo de profil
                </h3>

                <form action="/stm/admin/profile/avatar" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="<?= Session::get('csrf_token') ?>">

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Nouvelle image
                            </label>
                            <input type="file"
                                   name="avatar"
                                   accept="image/jpeg,image/png,image/webp"
                                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 cursor-pointer">
                            <p class="mt-1 text-xs text-gray-500">JPG, PNG ou WEBP. Max 2MB.</p>
                        </div>

                        <div class="flex gap-2">
                            <button type="submit"
                                    class="flex-1 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition font-medium text-sm">
                                <i class="fas fa-upload mr-2"></i>
                                Enregistrer
                            </button>

                            <?php if (!empty($user['avatar'])): ?>
                            <a href="/stm/admin/profile/avatar/delete?_token=<?= Session::get('csrf_token') ?>"
                               onclick="return confirm('Supprimer votre photo de profil ?')"
                               class="px-4 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition font-medium text-sm">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>

        </div>

        <!-- Colonne droite : Stats + Mot de passe + Campagnes -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Statistiques -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-chart-bar text-primary-600 mr-2"></i>
                    Mes statistiques
                </h3>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <!-- Campagnes -->
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <div class="text-3xl font-bold text-primary-600"><?= $stats['campaigns_assigned'] ?></div>
                        <div class="text-sm text-gray-600 mt-1">Campagnes</div>
                        <div class="text-xs text-green-600 mt-1">
                            <?= $stats['campaigns_active'] ?> active(s)
                        </div>
                    </div>

                    <!-- Commandes -->
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <div class="text-3xl font-bold text-green-600"><?= $stats['orders_total'] ?></div>
                        <div class="text-sm text-gray-600 mt-1">Commandes</div>
                        <div class="text-xs text-blue-600 mt-1">
                            <?= $stats['orders_this_month'] ?> ce mois
                        </div>
                    </div>

                    <!-- Promotions -->
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <div class="text-3xl font-bold text-blue-600"><?= $stats['products_managed'] ?></div>
                        <div class="text-sm text-gray-600 mt-1">Promotions</div>
                        <div class="text-xs text-gray-500 mt-1">actives</div>
                    </div>

                    <?php if ($stats['customers_accessible'] > 0): ?>
                    <!-- Clients -->
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <div class="text-3xl font-bold text-orange-600"><?= number_format($stats['customers_accessible'], 0, ',', ' ') ?></div>
                        <div class="text-sm text-gray-600 mt-1">Clients</div>
                        <div class="text-xs text-gray-500 mt-1">accessibles</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Campagnes assignées -->
            <?php if (!empty($assignedCampaigns)): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-bullhorn text-primary-600 mr-2"></i>
                        Mes campagnes
                    </h3>
                    <a href="/stm/admin/campaigns" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                        Voir tout →
                    </a>
                </div>

                <div class="space-y-3">
                    <?php foreach ($assignedCampaigns as $campaign): ?>
                    <a href="/stm/admin/campaigns/<?= $campaign['id'] ?>"
                       class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-lg bg-primary-100 flex items-center justify-center">
                                <span class="text-primary-700 font-bold text-sm"><?= $campaign['country'] ?></span>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900"><?= htmlspecialchars($campaign['name']) ?></p>
                                <p class="text-xs text-gray-500">
                                    <?= date('d/m/Y', strtotime($campaign['start_date'])) ?> - <?= date('d/m/Y', strtotime($campaign['end_date'])) ?>
                                </p>
                            </div>
                        </div>
                        <span class="px-2 py-1 rounded-full text-xs font-medium <?= $statusColors[$campaign['status']] ?? 'bg-gray-100 text-gray-600' ?>">
                            <?php
                            $statusLabels = ['active' => 'Active', 'upcoming' => 'À venir', 'ended' => 'Terminée'];
                            echo $statusLabels[$campaign['status']] ?? $campaign['status'];
                            ?>
                        </span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Informations du compte (lecture seule) -->
            <div class="bg-gray-50 rounded-xl border border-gray-200 p-6">
                <div class="flex items-center gap-2 mb-4">
                    <i class="fas fa-info-circle text-gray-400"></i>
                    <h3 class="text-sm font-medium text-gray-600">Informations du compte (lecture seule)</h3>
                </div>

                <p class="text-sm text-gray-500">
                    Votre nom, votre adresse email et votre mot de passe sont synchronisés avec Microsoft Entra ID et ne peuvent pas être modifiés ici.
                    Contactez votre administrateur si vous avez besoin de mettre à jour ces informations.
                </p>
            </div>

        </div>

    </div>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/admin.php';
?>