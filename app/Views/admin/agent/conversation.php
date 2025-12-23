<?php
/**
 * Vue : Détail d'une conversation Agent STM
 *
 * Affiche tous les messages d'une conversation
 * Design cohérent avec les mascottes du widget
 *
 * @created  2025/12/09
 * @modified 2025/12/10 - Amélioration design + mascottes dynamiques
 * @package  STM Agent
 */
?>

<style>
@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-6px); }
}
.animate-float { animation: float 3s ease-in-out infinite; }

/* Styles pour le contenu formaté des messages */
.prose-content strong { font-weight: 600; }
.prose-content em { font-style: italic; }
.prose-content code {
    background: rgba(0,0,0,0.05);
    padding: 0.1em 0.3em;
    border-radius: 0.25em;
    font-family: monospace;
    font-size: 0.9em;
}
.prose-content ul, .prose-content ol {
    margin: 0.5em 0;
    padding-left: 1.25em;
}
.prose-content li { margin: 0.25em 0; }
.prose-content .list-bullet { opacity: 0.5; margin-right: 0.5em; }
</style>

<!-- Container Alpine.js pour gérer la mascotte -->
<div x-data="conversationPage()" x-init="init()">

<!-- En-tête avec mascotte -->
<div class="mb-6">
    <div class="flex items-center gap-4">
        <a href="/stm/admin/agent/history"
           class="p-3 text-gray-400 hover:text-gray-600 rounded-xl transition"
           :class="currentMascot.backButtonHover">
            <i class="fas fa-arrow-left text-lg"></i>
        </a>

        <!-- Avatar mascotte animé -->
        <div class="w-14 h-14 rounded-2xl flex items-center justify-center shadow-lg animate-float"
             :class="currentMascot.btnGradient">
            <div x-html="currentMascot.headerAvatar"></div>
        </div>

        <div class="flex-1">
            <h1 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($conversationTitle) ?></h1>
            <p class="text-sm text-gray-500">
                <i class="fas fa-clock mr-1"></i>
                Commencée le <?= date('d/m/Y à H:i', strtotime($startedAt)) ?>
                <span class="mx-2">•</span>
                <i class="fas fa-message mr-1"></i>
                <?= count($messages) ?> messages
            </p>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2">
            <!-- Sélecteur mascotte compact -->
            <div class="flex items-center gap-1 mr-2">
                <template x-for="(mascot, key) in mascots" :key="key">
                    <button @click="changeMascot(key)"
                            class="w-8 h-8 rounded-lg flex items-center justify-center shadow transition-all hover:scale-110"
                            :class="[mascot.btnGradient, currentMascotKey === key ? 'ring-2 ring-offset-1 ring-gray-400' : 'opacity-50 hover:opacity-100']">
                        <div x-html="mascot.tinyAvatar"></div>
                    </button>
                </template>
            </div>

            <button @click="loadInWidget('<?= htmlspecialchars($sessionId) ?>')"
                    class="inline-flex items-center px-5 py-2.5 text-white rounded-xl transition shadow-lg hover:shadow-xl font-medium hover:opacity-90"
                    :class="currentMascot.buttonGradient">
                <i class="fas fa-play mr-2"></i>
                Reprendre
            </button>
        </div>
    </div>
</div>

<!-- Zone de messages -->
<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <!-- Header de la zone de chat -->
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between"
         :class="currentMascot.chatHeaderBg">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center" :class="currentMascot.btnGradient">
                <div x-html="currentMascot.smallAvatar"></div>
            </div>
            <div>
                <p class="font-semibold text-gray-900">Conversation avec <span x-text="currentMascot.name"></span></p>
                <p class="text-xs text-gray-500"><?= date('d/m/Y', strtotime($startedAt)) ?></p>
            </div>
        </div>
        <div class="flex items-center gap-2 text-sm text-gray-500">
            <span class="px-3 py-1 rounded-full" :class="currentMascot.badgeBg">
                <i class="fas fa-message mr-1" :class="currentMascot.iconColor"></i>
                <?= count($messages) ?> messages
            </span>
        </div>
    </div>

    <!-- Messages -->
    <div class="p-6 space-y-4 max-w-4xl mx-auto">
        <?php foreach ($messages as $index => $msg): ?>
        <div class="flex <?= $msg['role'] === 'user' ? 'justify-end' : 'justify-start' ?>"
             data-role="<?= $msg['role'] ?>">

            <?php if ($msg['role'] === 'assistant'): ?>
            <!-- Avatar assistant -->
            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 mr-3 shadow-md"
                 :class="currentMascot.btnGradient">
                <div x-html="currentMascot.smallAvatar"></div>
            </div>
            <?php endif; ?>

            <div class="max-w-[75%]">
                <?php if ($msg['role'] === 'assistant'): ?>
                <!-- Bulle assistant -->
                <div class="bg-gray-50 text-gray-800 rounded-2xl rounded-tl-md px-4 py-3 shadow-sm border border-gray-100">
                    <div class="text-sm leading-relaxed prose-content" x-html="formatMessage(<?= htmlspecialchars(json_encode($msg['content']), ENT_QUOTES) ?>)"></div>
                </div>
                <div class="text-xs text-gray-400 mt-1.5 ml-2">
                    <span x-text="currentMascot.name"></span> • <?= date('H:i', strtotime($msg['created_at'])) ?>
                </div>
                <?php else: ?>
                <!-- Bulle utilisateur -->
                <div class="text-white rounded-2xl rounded-tr-md px-4 py-3 shadow-md"
                     :class="currentMascot.userBubble">
                    <div class="text-sm leading-relaxed"><?= nl2br(htmlspecialchars($msg['content'])) ?></div>
                </div>
                <div class="text-xs text-gray-400 mt-1.5 text-right mr-2">
                    Vous • <?= date('H:i', strtotime($msg['created_at'])) ?>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($msg['role'] === 'user'): ?>
            <!-- Avatar utilisateur -->
            <div class="w-10 h-10 bg-gray-200 rounded-xl flex items-center justify-center flex-shrink-0 ml-3 shadow-md">
                <i class="fas fa-user text-gray-500"></i>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Footer -->
    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
        <div class="flex items-center justify-between max-w-4xl mx-auto">
            <p class="text-sm text-gray-500">
                <i class="fas fa-info-circle mr-1"></i>
                Cette conversation est terminée. Cliquez sur "Reprendre" pour continuer.
            </p>
            <button @click="loadInWidget('<?= htmlspecialchars($sessionId) ?>')"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg transition"
                    :class="currentMascot.secondaryButton">
                <i class="fas fa-external-link-alt mr-2"></i>
                Ouvrir dans le widget
            </button>
        </div>
    </div>
</div>

<!-- Navigation rapide -->
<div class="mt-6 flex items-center justify-between">
    <a href="/stm/admin/agent/history"
       class="inline-flex items-center px-4 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition">
        <i class="fas fa-arrow-left mr-2"></i>
        Retour à l'historique
    </a>
    <button onclick="deleteConversation('<?= htmlspecialchars($sessionId) ?>')"
            class="inline-flex items-center px-4 py-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition">
        <i class="fas fa-trash mr-2"></i>
        Supprimer
    </button>
</div>

</div>

<script>
function conversationPage() {
    return {
        currentMascotKey: 'zippy',

        mascots: {
            zippy: {
                name: 'Zippy',
                btnGradient: 'bg-gradient-to-br from-cyan-400 to-blue-500',
                buttonGradient: 'bg-gradient-to-r from-cyan-500 to-blue-500',
                userBubble: 'bg-gradient-to-r from-cyan-500 to-blue-500',
                chatHeaderBg: 'bg-gradient-to-r from-cyan-50 to-blue-50',
                badgeBg: 'bg-cyan-100',
                iconColor: 'text-cyan-600',
                backButtonHover: 'hover:bg-cyan-50',
                secondaryButton: 'bg-cyan-100 text-cyan-700 hover:bg-cyan-200',
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
                smallAvatar: `<svg viewBox="0 0 100 100" class="w-6 h-6">
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
                headerAvatar: `<svg viewBox="0 0 100 100" class="w-10 h-10">
                    <circle cx="50" cy="10" r="6" fill="#67e8f9"/>
                    <rect x="47" y="14" width="6" height="9" fill="#67e8f9"/>
                    <rect x="18" y="24" width="64" height="56" rx="14" fill="#fff"/>
                    <rect x="8" y="38" width="10" height="20" rx="4" fill="#67e8f9"/>
                    <rect x="82" y="38" width="10" height="20" rx="4" fill="#67e8f9"/>
                    <circle cx="38" cy="50" r="10" fill="#0891b2"/>
                    <circle cx="62" cy="50" r="10" fill="#0891b2"/>
                    <circle cx="40" cy="47" r="4" fill="#fff"/>
                    <circle cx="64" cy="47" r="4" fill="#fff"/>
                    <rect x="30" y="68" width="40" height="8" rx="4" fill="#22d3ee"/>
                    <path d="M34 72 Q50 77 66 72" stroke="#fff" stroke-width="2" fill="none" stroke-linecap="round"/>
                </svg>`
            },
            mochi: {
                name: 'Mochi',
                btnGradient: 'bg-gradient-to-br from-pink-300 to-rose-400',
                buttonGradient: 'bg-gradient-to-r from-pink-400 to-rose-500',
                userBubble: 'bg-gradient-to-r from-pink-400 to-rose-500',
                chatHeaderBg: 'bg-gradient-to-r from-pink-50 to-rose-50',
                badgeBg: 'bg-pink-100',
                iconColor: 'text-pink-600',
                backButtonHover: 'hover:bg-pink-50',
                secondaryButton: 'bg-pink-100 text-pink-700 hover:bg-pink-200',
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
                smallAvatar: `<svg viewBox="0 0 100 100" class="w-6 h-6">
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
                headerAvatar: `<svg viewBox="0 0 100 100" class="w-10 h-10">
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
                </svg>`
            },
            pepper: {
                name: 'Pepper',
                btnGradient: 'bg-gradient-to-br from-red-400 to-red-600',
                buttonGradient: 'bg-gradient-to-r from-red-500 to-red-600',
                userBubble: 'bg-gradient-to-r from-red-500 to-red-600',
                chatHeaderBg: 'bg-gradient-to-r from-red-50 to-orange-50',
                badgeBg: 'bg-red-100',
                iconColor: 'text-red-600',
                backButtonHover: 'hover:bg-red-50',
                secondaryButton: 'bg-red-100 text-red-700 hover:bg-red-200',
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
                smallAvatar: `<svg viewBox="0 0 100 100" class="w-6 h-6">
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
                headerAvatar: `<svg viewBox="0 0 100 100" class="w-10 h-10">
                    <rect x="43" y="4" width="14" height="14" rx="4" fill="#22c55e"/>
                    <ellipse cx="50" cy="16" rx="16" ry="6" fill="#22c55e"/>
                    <ellipse cx="50" cy="58" rx="34" ry="40" fill="#fff"/>
                    <ellipse cx="38" cy="55" rx="12" ry="38" fill="#fecaca" opacity="0.25"/>
                    <circle cx="38" cy="50" r="7" fill="#1f2937"/>
                    <circle cx="62" cy="50" r="7" fill="#1f2937"/>
                    <circle cx="40" cy="47" r="3" fill="#fff"/>
                    <circle cx="64" cy="47" r="3" fill="#fff"/>
                    <path d="M32 68 Q50 92 68 68" stroke="#991b1b" stroke-width="3" fill="#fff" stroke-linecap="round"/>
                    <ellipse cx="50" cy="80" rx="7" ry="4" fill="#f87171"/>
                    <circle cx="26" cy="60" r="6" fill="#fca5a5" opacity="0.6"/>
                    <circle cx="74" cy="60" r="6" fill="#fca5a5" opacity="0.6"/>
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

        loadInWidget(sessionId) {
            if (window.chatWidgetLoad) {
                window.chatWidgetLoad(sessionId);
            } else {
                alert('Le widget de chat n\'est pas disponible. Actualisez la page.');
            }
        },

        /**
         * Formater le message pour l'affichage (même logique que le widget)
         */
        formatMessage(content) {
            if (!content) return '';

            // Supprimer les balises boutons du texte
            let formatted = content.replace(/\[BTN:[^\]]+\]/g, '');

            // Échapper HTML
            formatted = formatted
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

            // Convertir les retours à la ligne
            formatted = formatted.replace(/\n/g, '<br>');

            // Mettre en gras **texte**
            formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

            // Mettre en italique *texte* (mais pas les ** déjà traités)
            formatted = formatted.replace(/(?<!\*)\*([^*]+)\*(?!\*)/g, '<em>$1</em>');

            // Code inline `code`
            formatted = formatted.replace(/`([^`]+)`/g, '<code>$1</code>');

            // Listes avec - ou •
            formatted = formatted.replace(/(^|<br>)- /g, '$1<span class="list-bullet">•</span>');
            formatted = formatted.replace(/(^|<br>)• /g, '$1<span class="list-bullet">•</span>');

            // Listes numérotées
            formatted = formatted.replace(/(^|<br>)(\d+)\. /g, '$1<span class="list-bullet">$2.</span> ');

            // Nettoyer les lignes vides multiples
            formatted = formatted.replace(/(<br>\s*){3,}/g, '<br><br>');

            return formatted.trim();
        }
    }
}

function deleteConversation(sessionId) {
    if (!confirm('Supprimer cette conversation ? Cette action est irréversible.')) return;

    fetch('/stm/admin/agent/delete/' + sessionId, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.href = '/stm/admin/agent/history';
        } else {
            alert('Erreur: ' + (data.error || 'Impossible de supprimer'));
        }
    })
    .catch(err => {
        alert('Erreur de connexion');
    });
}
</script>