<?php
/**
 * Vue : Historique des conversations Agent STM
 *
 * Liste des conversations passées avec l'agent
 * Design cohérent avec les mascottes du widget
 * Utilise la mascotte sélectionnée par l'utilisateur (localStorage)
 *
 * @created  2025/12/09
 * @modified 2025/12/10 - Amélioration design + mascottes dynamiques avec détails
 * @package  STM Agent
 */
?>

<style>
@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}
.animate-float { animation: float 3s ease-in-out infinite; }
</style>

<!-- Container Alpine.js pour gérer la mascotte -->
<div x-data="historyPage()" x-init="init()">

<!-- En-tête avec mascotte sélectionnée -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <!-- Mascotte animée (dynamique) -->
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center shadow-lg animate-float"
                 :class="currentMascot.btnGradient">
                <div x-html="currentMascot.largeAvatar"></div>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Historique des conversations</h1>
                <p class="text-gray-500 mt-1">Retrouvez vos échanges avec <span x-text="currentMascot.name" class="font-medium"></span></p>
            </div>
        </div>
        <button @click="startNewConversation()"
                class="inline-flex items-center px-5 py-3 text-white rounded-xl transition shadow-lg hover:shadow-xl font-medium hover:opacity-90"
                :class="currentMascot.buttonGradient">
            <i class="fas fa-plus mr-2"></i>
            Nouvelle conversation
        </button>
    </div>
</div>

<?php if (empty($conversations)): ?>
<!-- Aucune conversation - État vide avec mascotte sélectionnée -->
<div class="bg-white rounded-2xl shadow-sm p-12 text-center">
    <div class="max-w-md mx-auto">
        <!-- Mascotte sélectionnée (grande) -->
        <div class="w-24 h-24 mx-auto mb-6 rounded-2xl flex items-center justify-center shadow-lg animate-float"
             :class="currentMascot.btnGradient">
            <div x-html="currentMascot.extraLargeAvatar"></div>
        </div>

        <h3 class="text-xl font-bold text-gray-900 mb-2">Aucune conversation pour l'instant</h3>
        <p class="text-gray-500 mb-6">
            Commencez à discuter avec <span x-text="currentMascot.name" class="font-medium"></span> !<br>
            Posez des questions sur vos campagnes, stats, produits...
        </p>

        <div class="space-y-3 text-left bg-gray-50 rounded-xl p-4 mb-6">
            <p class="text-sm font-medium text-gray-700 mb-2">💡 Exemples de questions :</p>
            <div class="text-sm text-gray-600 space-y-2">
                <div class="flex items-center gap-2">
                    <span :class="currentMascot.dotColor">•</span>
                    "Quelles sont les campagnes en cours ?"
                </div>
                <div class="flex items-center gap-2">
                    <span :class="currentMascot.dotColor">•</span>
                    "Compare Black Friday et Noël 2025"
                </div>
                <div class="flex items-center gap-2">
                    <span :class="currentMascot.dotColor">•</span>
                    "Top 10 des produits vendus"
                </div>
            </div>
        </div>

        <!-- Sélecteur de mascotte -->
        <div class="mb-6">
            <p class="text-sm text-gray-500 mb-3">Choisissez votre assistant :</p>
            <div class="flex justify-center gap-3">
                <template x-for="(mascot, key) in mascots" :key="key">
                    <button @click="changeMascot(key)"
                            class="w-14 h-14 rounded-xl flex items-center justify-center shadow-md transition-all hover:scale-110"
                            :class="[mascot.btnGradient, currentMascotKey === key ? 'ring-4 ring-offset-2 ring-gray-400' : '']">
                        <div x-html="mascot.miniAvatar"></div>
                    </button>
                </template>
            </div>
        </div>

        <button @click="startNewConversation()"
                class="inline-flex items-center px-6 py-3 text-white rounded-xl transition shadow-lg font-medium hover:opacity-90"
                :class="currentMascot.buttonGradient">
            <i class="fas fa-comments mr-2"></i>
            Démarrer une conversation
        </button>
    </div>
</div>

<?php else: ?>

<!-- Stats rapides avec couleurs de la mascotte -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="rounded-xl p-4 border transition-colors" :class="currentMascot.statsBg1">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center" :class="currentMascot.statsIcon1">
                <i class="fas fa-comments text-white"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900"><?= count($conversations) ?></p>
                <p class="text-sm text-gray-500">Conversations</p>
            </div>
        </div>
    </div>
    <div class="rounded-xl p-4 border transition-colors" :class="currentMascot.statsBg2">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center" :class="currentMascot.statsIcon2">
                <i class="fas fa-message text-white"></i>
            </div>
            <div>
                <?php
                $totalMessages = array_sum(array_column($conversations, 'message_count'));
                ?>
                <p class="text-2xl font-bold text-gray-900"><?= $totalMessages ?></p>
                <p class="text-sm text-gray-500">Messages échangés</p>
            </div>
        </div>
    </div>
    <div class="rounded-xl p-4 border transition-colors" :class="currentMascot.statsBg3">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center" :class="currentMascot.statsIcon3">
                <i class="fas fa-clock text-white"></i>
            </div>
            <div>
                <?php
                $lastActivity = !empty($conversations) ? $conversations[0]['last_message_at'] : null;
                $lastActivityText = $lastActivity ? date('d/m à H:i', strtotime($lastActivity)) : '-';
                ?>
                <p class="text-lg font-bold text-gray-900"><?= $lastActivityText ?></p>
                <p class="text-sm text-gray-500">Dernière activité</p>
            </div>
        </div>
    </div>
</div>

<!-- Sélecteur de mascotte compact -->
<div class="flex items-center justify-between mb-4">
    <p class="text-sm text-gray-500">
        <?= count($conversations) ?> conversation(s) avec <span x-text="currentMascot.name" class="font-medium"></span>
    </p>
    <div class="flex items-center gap-2">
        <span class="text-xs text-gray-400">Mascotte :</span>
        <template x-for="(mascot, key) in mascots" :key="key">
            <button @click="changeMascot(key)"
                    class="w-8 h-8 rounded-lg flex items-center justify-center shadow transition-all hover:scale-110"
                    :class="[mascot.btnGradient, currentMascotKey === key ? 'ring-2 ring-offset-1 ring-gray-400' : 'opacity-60 hover:opacity-100']">
                <div x-html="mascot.tinyAvatar"></div>
            </button>
        </template>
    </div>
</div>

<!-- Liste des conversations -->
<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <div class="divide-y divide-gray-100">
        <?php foreach ($conversations as $index => $conv): ?>
        <div class="p-4 hover:bg-gray-50 transition group">
            <div class="flex items-center gap-4">
                <!-- Avatar mascotte sélectionnée -->
                <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0 shadow-md group-hover:scale-105 transition-transform"
                     :class="currentMascot.btnGradient">
                    <div x-html="currentMascot.listAvatar"></div>
                </div>

                <!-- Contenu -->
                <a href="/stm/admin/agent/conversation/<?= htmlspecialchars($conv['session_id']) ?>"
                   class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <p class="font-semibold text-gray-900 truncate">
                            <?= htmlspecialchars($conv['title'] ?? 'Conversation sans titre') ?>
                        </p>
                        <?php if (strtotime($conv['last_message_at']) > strtotime('-1 hour')): ?>
                        <span class="px-2 py-0.5 text-xs rounded-full font-medium" :class="currentMascot.recentBadge">
                            Récent
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-4 text-sm text-gray-500">
                        <span class="flex items-center gap-1">
                            <i class="fas fa-message text-xs" :class="currentMascot.iconColor"></i>
                            <?= $conv['message_count'] ?> messages
                        </span>
                        <span class="flex items-center gap-1">
                            <i class="fas fa-calendar text-xs"></i>
                            <?= date('d/m/Y', strtotime($conv['started_at'])) ?>
                        </span>
                        <span class="flex items-center gap-1">
                            <i class="fas fa-clock text-xs"></i>
                            <?= date('H:i', strtotime($conv['last_message_at'])) ?>
                        </span>
                    </div>
                </a>

                <!-- Actions -->
                <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition">
                    <button @click="loadConversation('<?= htmlspecialchars($conv['session_id']) ?>')"
                            class="p-2.5 rounded-lg transition" :class="currentMascot.actionButton"
                            title="Reprendre cette conversation">
                        <i class="fas fa-play text-sm"></i>
                    </button>
                    <button onclick="deleteConversation('<?= htmlspecialchars($conv['session_id']) ?>')"
                            class="p-2.5 bg-gray-100 text-gray-500 rounded-lg hover:bg-red-100 hover:text-red-600 transition"
                            title="Supprimer">
                        <i class="fas fa-trash text-sm"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Note en bas -->
<div class="mt-6 text-center">
    <p class="text-sm text-gray-400">
        <i class="fas fa-info-circle mr-1"></i>
        Cliquez sur une conversation pour voir les détails ou sur
        <i class="fas fa-play mr-1" :class="currentMascot.iconColor"></i> pour la reprendre dans le widget
    </p>
</div>

<?php endif; ?>

<!-- Fermeture du container Alpine.js -->
</div>

<script>
function historyPage() {
    return {
        currentMascotKey: 'zippy',

        mascots: {
            zippy: {
                name: 'Zippy',
                btnGradient: 'bg-gradient-to-br from-cyan-400 to-blue-500',
                buttonGradient: 'bg-gradient-to-r from-cyan-500 to-blue-500',
                dotColor: 'text-cyan-500',
                iconColor: 'text-cyan-500',
                recentBadge: 'bg-cyan-100 text-cyan-700',
                actionButton: 'bg-cyan-100 text-cyan-600 hover:bg-cyan-200',
                statsBg1: 'bg-gradient-to-br from-cyan-50 to-blue-50 border-cyan-100',
                statsBg2: 'bg-gradient-to-br from-cyan-50 to-cyan-100 border-cyan-100',
                statsBg3: 'bg-gradient-to-br from-blue-50 to-cyan-50 border-blue-100',
                statsIcon1: 'bg-cyan-500',
                statsIcon2: 'bg-cyan-600',
                statsIcon3: 'bg-blue-500',
                tinyAvatar: `<svg viewBox="0 0 100 100" class="w-5 h-5">
                    <circle cx="50" cy="8" r="5" fill="#67e8f9"/>
                    <rect x="48" y="11" width="4" height="7" fill="#67e8f9"/>
                    <rect x="22" y="20" width="56" height="50" rx="10" fill="#fff"/>
                    <rect x="10" y="32" width="10" height="16" rx="4" fill="#67e8f9"/>
                    <rect x="80" y="32" width="10" height="16" rx="4" fill="#67e8f9"/>
                    <circle cx="40" cy="44" r="7" fill="#0891b2"/>
                    <circle cx="60" cy="44" r="7" fill="#0891b2"/>
                    <circle cx="42" cy="42" r="2.5" fill="#fff"/>
                    <circle cx="62" cy="42" r="2.5" fill="#fff"/>
                    <rect x="34" y="58" width="32" height="5" rx="2" fill="#22d3ee"/>
                    <path d="M38 60 Q50 64 62 60" stroke="#fff" stroke-width="1" fill="none" stroke-linecap="round"/>
                </svg>`,
                miniAvatar: `<svg viewBox="0 0 100 100" class="w-8 h-8">
                    <circle cx="50" cy="8" r="5" fill="#67e8f9"/>
                    <rect x="48" y="11" width="4" height="8" fill="#67e8f9"/>
                    <rect x="20" y="20" width="60" height="52" rx="12" fill="#fff"/>
                    <rect x="8" y="32" width="10" height="18" rx="4" fill="#67e8f9"/>
                    <rect x="82" y="32" width="10" height="18" rx="4" fill="#67e8f9"/>
                    <circle cx="38" cy="44" r="8" fill="#0891b2"/>
                    <circle cx="62" cy="44" r="8" fill="#0891b2"/>
                    <circle cx="40" cy="42" r="3" fill="#fff"/>
                    <circle cx="64" cy="42" r="3" fill="#fff"/>
                    <rect x="30" y="58" width="40" height="7" rx="3" fill="#22d3ee"/>
                    <path d="M34 61 Q50 66 66 61" stroke="#fff" stroke-width="1.5" fill="none" stroke-linecap="round"/>
                </svg>`,
                listAvatar: `<svg viewBox="0 0 100 100" class="w-8 h-8">
                    <circle cx="50" cy="8" r="5" fill="#67e8f9"/>
                    <rect x="48" y="11" width="4" height="8" fill="#67e8f9"/>
                    <rect x="20" y="20" width="60" height="52" rx="12" fill="#fff"/>
                    <rect x="8" y="32" width="10" height="18" rx="4" fill="#67e8f9"/>
                    <rect x="82" y="32" width="10" height="18" rx="4" fill="#67e8f9"/>
                    <circle cx="38" cy="44" r="8" fill="#0891b2"/>
                    <circle cx="62" cy="44" r="8" fill="#0891b2"/>
                    <circle cx="40" cy="42" r="3" fill="#fff"/>
                    <circle cx="64" cy="42" r="3" fill="#fff"/>
                    <rect x="30" y="58" width="40" height="7" rx="3" fill="#22d3ee"/>
                    <path d="M34 61 Q50 66 66 61" stroke="#fff" stroke-width="1.5" fill="none" stroke-linecap="round"/>
                </svg>`,
                largeAvatar: `<svg viewBox="0 0 100 100" class="w-12 h-12">
                    <circle cx="50" cy="8" r="6" fill="#67e8f9"/>
                    <rect x="47" y="12" width="6" height="10" fill="#67e8f9"/>
                    <rect x="16" y="24" width="68" height="58" rx="14" fill="#fff"/>
                    <rect x="6" y="38" width="12" height="22" rx="5" fill="#67e8f9"/>
                    <rect x="82" y="38" width="12" height="22" rx="5" fill="#67e8f9"/>
                    <circle cx="38" cy="50" r="10" fill="#0891b2"/>
                    <circle cx="62" cy="50" r="10" fill="#0891b2"/>
                    <circle cx="40" cy="47" r="4" fill="#fff"/>
                    <circle cx="64" cy="47" r="4" fill="#fff"/>
                    <rect x="28" y="68" width="44" height="9" rx="4" fill="#22d3ee"/>
                    <path d="M32 72 Q50 78 68 72" stroke="#fff" stroke-width="2" fill="none" stroke-linecap="round"/>
                </svg>`,
                extraLargeAvatar: `<svg viewBox="0 0 100 100" class="w-16 h-16">
                    <circle cx="50" cy="6" r="7" fill="#67e8f9"/>
                    <rect x="46" y="11" width="8" height="12" fill="#67e8f9"/>
                    <rect x="12" y="24" width="76" height="62" rx="16" fill="#fff"/>
                    <rect x="2" y="40" width="14" height="24" rx="6" fill="#67e8f9"/>
                    <rect x="84" y="40" width="14" height="24" rx="6" fill="#67e8f9"/>
                    <circle cx="36" cy="52" r="12" fill="#0891b2"/>
                    <circle cx="64" cy="52" r="12" fill="#0891b2"/>
                    <circle cx="38" cy="48" r="5" fill="#fff"/>
                    <circle cx="66" cy="48" r="5" fill="#fff"/>
                    <rect x="26" y="72" width="48" height="10" rx="5" fill="#22d3ee"/>
                    <path d="M30 77 Q50 84 70 77" stroke="#fff" stroke-width="2" fill="none" stroke-linecap="round"/>
                </svg>`
            },
            mochi: {
                name: 'Mochi',
                btnGradient: 'bg-gradient-to-br from-pink-300 to-rose-400',
                buttonGradient: 'bg-gradient-to-r from-pink-400 to-rose-500',
                dotColor: 'text-pink-500',
                iconColor: 'text-pink-500',
                recentBadge: 'bg-pink-100 text-pink-700',
                actionButton: 'bg-pink-100 text-pink-600 hover:bg-pink-200',
                statsBg1: 'bg-gradient-to-br from-pink-50 to-rose-50 border-pink-100',
                statsBg2: 'bg-gradient-to-br from-pink-50 to-pink-100 border-pink-100',
                statsBg3: 'bg-gradient-to-br from-rose-50 to-pink-50 border-rose-100',
                statsIcon1: 'bg-pink-400',
                statsIcon2: 'bg-pink-500',
                statsIcon3: 'bg-rose-400',
                tinyAvatar: `<svg viewBox="0 0 100 100" class="w-5 h-5">
                    <ellipse cx="50" cy="55" rx="32" ry="30" fill="#fff"/>
                    <circle cx="30" cy="35" r="9" fill="#fff"/>
                    <circle cx="70" cy="35" r="9" fill="#fff"/>
                    <circle cx="30" cy="35" r="5" fill="#fbcfe8"/>
                    <circle cx="70" cy="35" r="5" fill="#fbcfe8"/>
                    <ellipse cx="40" cy="52" rx="5" ry="7" fill="#1f2937"/>
                    <ellipse cx="60" cy="52" rx="5" ry="7" fill="#1f2937"/>
                    <ellipse cx="42" cy="50" rx="1.5" ry="2" fill="#fff"/>
                    <ellipse cx="62" cy="50" rx="1.5" ry="2" fill="#fff"/>
                    <path d="M44 68 Q50 75 56 68" stroke="#1f2937" stroke-width="2" fill="none" stroke-linecap="round"/>
                    <ellipse cx="26" cy="60" rx="4" ry="2.5" fill="#f9a8d4" opacity="0.6"/>
                    <ellipse cx="74" cy="60" rx="4" ry="2.5" fill="#f9a8d4" opacity="0.6"/>
                </svg>`,
                miniAvatar: `<svg viewBox="0 0 100 100" class="w-8 h-8">
                    <ellipse cx="50" cy="55" rx="35" ry="32" fill="#fff"/>
                    <circle cx="28" cy="32" r="10" fill="#fff"/>
                    <circle cx="72" cy="32" r="10" fill="#fff"/>
                    <circle cx="28" cy="32" r="6" fill="#fbcfe8"/>
                    <circle cx="72" cy="32" r="6" fill="#fbcfe8"/>
                    <ellipse cx="38" cy="52" rx="6" ry="8" fill="#1f2937"/>
                    <ellipse cx="62" cy="52" rx="6" ry="8" fill="#1f2937"/>
                    <ellipse cx="40" cy="49" rx="2" ry="3" fill="#fff"/>
                    <ellipse cx="64" cy="49" rx="2" ry="3" fill="#fff"/>
                    <path d="M43 68 Q50 76 57 68" stroke="#1f2937" stroke-width="2" fill="none" stroke-linecap="round"/>
                    <ellipse cx="24" cy="60" rx="5" ry="3" fill="#f9a8d4" opacity="0.6"/>
                    <ellipse cx="76" cy="60" rx="5" ry="3" fill="#f9a8d4" opacity="0.6"/>
                </svg>`,
                listAvatar: `<svg viewBox="0 0 100 100" class="w-8 h-8">
                    <ellipse cx="50" cy="55" rx="35" ry="32" fill="#fff"/>
                    <circle cx="28" cy="32" r="10" fill="#fff"/>
                    <circle cx="72" cy="32" r="10" fill="#fff"/>
                    <circle cx="28" cy="32" r="6" fill="#fbcfe8"/>
                    <circle cx="72" cy="32" r="6" fill="#fbcfe8"/>
                    <ellipse cx="38" cy="52" rx="6" ry="8" fill="#1f2937"/>
                    <ellipse cx="62" cy="52" rx="6" ry="8" fill="#1f2937"/>
                    <ellipse cx="40" cy="49" rx="2" ry="3" fill="#fff"/>
                    <ellipse cx="64" cy="49" rx="2" ry="3" fill="#fff"/>
                    <path d="M43 68 Q50 76 57 68" stroke="#1f2937" stroke-width="2" fill="none" stroke-linecap="round"/>
                    <ellipse cx="24" cy="60" rx="5" ry="3" fill="#f9a8d4" opacity="0.6"/>
                    <ellipse cx="76" cy="60" rx="5" ry="3" fill="#f9a8d4" opacity="0.6"/>
                </svg>`,
                largeAvatar: `<svg viewBox="0 0 100 100" class="w-12 h-12">
                    <ellipse cx="50" cy="58" rx="38" ry="35" fill="#fff"/>
                    <circle cx="24" cy="30" r="12" fill="#fff"/>
                    <circle cx="76" cy="30" r="12" fill="#fff"/>
                    <circle cx="24" cy="30" r="7" fill="#fbcfe8"/>
                    <circle cx="76" cy="30" r="7" fill="#fbcfe8"/>
                    <ellipse cx="36" cy="55" rx="7" ry="10" fill="#1f2937"/>
                    <ellipse cx="64" cy="55" rx="7" ry="10" fill="#1f2937"/>
                    <ellipse cx="38" cy="51" rx="3" ry="4" fill="#fff"/>
                    <ellipse cx="66" cy="51" rx="3" ry="4" fill="#fff"/>
                    <ellipse cx="50" cy="68" rx="4" ry="3" fill="#f9a8d4"/>
                    <path d="M42 75 Q47 83 50 75 Q53 83 58 75" stroke="#1f2937" stroke-width="2.5" fill="none" stroke-linecap="round"/>
                    <ellipse cx="22" cy="65" rx="7" ry="4" fill="#f9a8d4" opacity="0.6"/>
                    <ellipse cx="78" cy="65" rx="7" ry="4" fill="#f9a8d4" opacity="0.6"/>
                </svg>`,
                extraLargeAvatar: `<svg viewBox="0 0 100 100" class="w-16 h-16">
                    <ellipse cx="50" cy="58" rx="40" ry="38" fill="#fff"/>
                    <circle cx="22" cy="28" r="14" fill="#fff"/>
                    <circle cx="78" cy="28" r="14" fill="#fff"/>
                    <circle cx="22" cy="28" r="9" fill="#fbcfe8"/>
                    <circle cx="78" cy="28" r="9" fill="#fbcfe8"/>
                    <ellipse cx="35" cy="55" rx="8" ry="12" fill="#1f2937"/>
                    <ellipse cx="65" cy="55" rx="8" ry="12" fill="#1f2937"/>
                    <ellipse cx="37" cy="50" rx="3" ry="5" fill="#fff"/>
                    <ellipse cx="67" cy="50" rx="3" ry="5" fill="#fff"/>
                    <ellipse cx="50" cy="70" rx="5" ry="4" fill="#f9a8d4"/>
                    <path d="M40 78 Q45 88 50 78 Q55 88 60 78" stroke="#1f2937" stroke-width="3" fill="none" stroke-linecap="round"/>
                    <ellipse cx="20" cy="68" rx="8" ry="5" fill="#f9a8d4" opacity="0.6"/>
                    <ellipse cx="80" cy="68" rx="8" ry="5" fill="#f9a8d4" opacity="0.6"/>
                </svg>`
            },
            pepper: {
                name: 'Pepper',
                btnGradient: 'bg-gradient-to-br from-red-400 to-red-600',
                buttonGradient: 'bg-gradient-to-r from-red-500 to-red-600',
                dotColor: 'text-red-500',
                iconColor: 'text-red-500',
                recentBadge: 'bg-red-100 text-red-700',
                actionButton: 'bg-red-100 text-red-600 hover:bg-red-200',
                statsBg1: 'bg-gradient-to-br from-red-50 to-orange-50 border-red-100',
                statsBg2: 'bg-gradient-to-br from-red-50 to-red-100 border-red-100',
                statsBg3: 'bg-gradient-to-br from-orange-50 to-red-50 border-orange-100',
                statsIcon1: 'bg-red-500',
                statsIcon2: 'bg-red-600',
                statsIcon3: 'bg-orange-500',
                tinyAvatar: `<svg viewBox="0 0 100 100" class="w-5 h-5">
                    <rect x="45" y="5" width="10" height="12" rx="3" fill="#22c55e"/>
                    <ellipse cx="50" cy="15" rx="12" ry="4" fill="#22c55e"/>
                    <ellipse cx="50" cy="55" rx="28" ry="34" fill="#fff"/>
                    <circle cx="40" cy="48" r="5" fill="#1f2937"/>
                    <circle cx="60" cy="48" r="5" fill="#1f2937"/>
                    <circle cx="42" cy="46" r="2" fill="#fff"/>
                    <circle cx="62" cy="46" r="2" fill="#fff"/>
                    <path d="M38 62 Q50 76 62 62" stroke="#991b1b" stroke-width="2" fill="#fff" stroke-linecap="round"/>
                    <ellipse cx="50" cy="68" rx="4" ry="2.5" fill="#f87171"/>
                    <circle cx="28" cy="56" r="3" fill="#fca5a5" opacity="0.5"/>
                    <circle cx="72" cy="56" r="3" fill="#fca5a5" opacity="0.5"/>
                </svg>`,
                miniAvatar: `<svg viewBox="0 0 100 100" class="w-8 h-8">
                    <rect x="45" y="5" width="10" height="14" rx="3" fill="#22c55e"/>
                    <ellipse cx="50" cy="17" rx="14" ry="5" fill="#22c55e"/>
                    <ellipse cx="50" cy="55" rx="30" ry="36" fill="#fff"/>
                    <circle cx="38" cy="48" r="6" fill="#1f2937"/>
                    <circle cx="62" cy="48" r="6" fill="#1f2937"/>
                    <circle cx="40" cy="46" r="2.5" fill="#fff"/>
                    <circle cx="64" cy="46" r="2.5" fill="#fff"/>
                    <path d="M35 62 Q50 80 65 62" stroke="#dc2626" stroke-width="3" fill="#fff" stroke-linecap="round"/>
                    <ellipse cx="50" cy="72" rx="5" ry="3" fill="#f87171"/>
                    <circle cx="26" cy="56" r="4" fill="#fca5a5" opacity="0.5"/>
                    <circle cx="74" cy="56" r="4" fill="#fca5a5" opacity="0.5"/>
                </svg>`,
                listAvatar: `<svg viewBox="0 0 100 100" class="w-8 h-8">
                    <rect x="45" y="5" width="10" height="14" rx="3" fill="#22c55e"/>
                    <ellipse cx="50" cy="17" rx="14" ry="5" fill="#22c55e"/>
                    <ellipse cx="50" cy="55" rx="30" ry="36" fill="#fff"/>
                    <circle cx="38" cy="48" r="6" fill="#1f2937"/>
                    <circle cx="62" cy="48" r="6" fill="#1f2937"/>
                    <circle cx="40" cy="46" r="2.5" fill="#fff"/>
                    <circle cx="64" cy="46" r="2.5" fill="#fff"/>
                    <path d="M35 62 Q50 80 65 62" stroke="#dc2626" stroke-width="3" fill="#fff" stroke-linecap="round"/>
                    <ellipse cx="50" cy="72" rx="5" ry="3" fill="#f87171"/>
                    <circle cx="26" cy="56" r="4" fill="#fca5a5" opacity="0.5"/>
                    <circle cx="74" cy="56" r="4" fill="#fca5a5" opacity="0.5"/>
                </svg>`,
                largeAvatar: `<svg viewBox="0 0 100 100" class="w-12 h-12">
                    <rect x="43" y="4" width="14" height="16" rx="4" fill="#22c55e"/>
                    <ellipse cx="50" cy="18" rx="18" ry="6" fill="#22c55e"/>
                    <ellipse cx="50" cy="58" rx="34" ry="40" fill="#fff"/>
                    <ellipse cx="38" cy="55" rx="10" ry="38" fill="#fecaca" opacity="0.2"/>
                    <circle cx="38" cy="50" r="7" fill="#1f2937"/>
                    <circle cx="62" cy="50" r="7" fill="#1f2937"/>
                    <circle cx="40" cy="47" r="3" fill="#fff"/>
                    <circle cx="64" cy="47" r="3" fill="#fff"/>
                    <path d="M32 68 Q50 92 68 68" stroke="#991b1b" stroke-width="3" fill="#fff" stroke-linecap="round"/>
                    <ellipse cx="50" cy="80" rx="7" ry="4" fill="#f87171"/>
                    <circle cx="26" cy="60" r="6" fill="#fca5a5" opacity="0.5"/>
                    <circle cx="74" cy="60" r="6" fill="#fca5a5" opacity="0.5"/>
                </svg>`,
                extraLargeAvatar: `<svg viewBox="0 0 100 100" class="w-16 h-16">
                    <rect x="42" y="2" width="16" height="18" rx="5" fill="#22c55e"/>
                    <ellipse cx="50" cy="18" rx="20" ry="7" fill="#22c55e"/>
                    <ellipse cx="50" cy="58" rx="36" ry="42" fill="#fff"/>
                    <ellipse cx="36" cy="55" rx="12" ry="40" fill="#fecaca" opacity="0.2"/>
                    <circle cx="38" cy="50" r="8" fill="#1f2937"/>
                    <circle cx="62" cy="50" r="8" fill="#1f2937"/>
                    <circle cx="40" cy="47" r="3.5" fill="#fff"/>
                    <circle cx="64" cy="47" r="3.5" fill="#fff"/>
                    <path d="M30 70 Q50 96 70 70" stroke="#991b1b" stroke-width="4" fill="#fff" stroke-linecap="round"/>
                    <ellipse cx="50" cy="84" rx="8" ry="5" fill="#f87171"/>
                    <circle cx="24" cy="62" r="7" fill="#fca5a5" opacity="0.5"/>
                    <circle cx="76" cy="62" r="7" fill="#fca5a5" opacity="0.5"/>
                </svg>`
            }
        },

        get currentMascot() {
            return this.mascots[this.currentMascotKey];
        },

        init() {
            // Charger la mascotte sauvegardée (même clé que le widget)
            const saved = localStorage.getItem('stm_mascot');
            if (saved && this.mascots[saved]) {
                this.currentMascotKey = saved;
            }
        },

        changeMascot(key) {
            this.currentMascotKey = key;
            localStorage.setItem('stm_mascot', key);
        },

        startNewConversation() {
            if (window.chatWidgetOpen) {
                window.chatWidgetOpen();
            } else {
                alert('Utilisez le bouton en bas à droite pour commencer une conversation.');
            }
        },

        loadConversation(sessionId) {
            if (window.chatWidgetLoad) {
                window.chatWidgetLoad(sessionId);
            } else {
                window.location.href = '/stm/admin/agent/conversation/' + sessionId;
            }
        }
    }
}

function deleteConversation(sessionId) {
    if (!confirm('Supprimer cette conversation ?')) return;

    fetch('/stm/admin/agent/delete/' + sessionId, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erreur: ' + (data.error || 'Impossible de supprimer'));
        }
    })
    .catch(err => {
        alert('Erreur de connexion');
    });
}
</script>