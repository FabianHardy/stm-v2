<?php
/**
 * Widget Chat Agent STM
 *
 * Widget flottant avec choix de mascotte (Zippy, Mochi, Pepper)
 * Supporte les boutons d'action cliquables pour les clarifications
 * Le choix de mascotte est sauvegardé en localStorage
 *
 * @created  2025/12/09
 * @modified 2025/12/10 - Ajout choix mascotte + boutons cliquables
 * @package  STM Agent
 */
?>

<!-- Chat Widget Container -->
<div x-data="chatWidget()" x-init="init()" x-cloak class="fixed bottom-6 right-6 z-50">

    <!-- Bouton flottant avec mascotte -->
    <button @click="toggleChat()"
            class="w-14 h-14 rounded-full shadow-lg flex items-center justify-center transition-all duration-300 hover:scale-110"
            :class="[currentMascot.btnGradient, { 'scale-0': isOpen, 'scale-100': !isOpen }]">
        <div x-html="currentMascot.floatingAvatar"></div>
    </button>

    <!-- Fenêtre de chat -->
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95 translate-y-4"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-4"
         class="absolute bottom-0 right-0 w-96 h-[520px] bg-white rounded-2xl shadow-2xl flex flex-col overflow-hidden border border-gray-200">

        <!-- Header avec gradient dynamique -->
        <div class="px-4 py-3 flex items-center justify-between flex-shrink-0 transition-all duration-300"
             :class="[currentMascot.headerGradient, currentMascot.textColor]">
            <div class="flex items-center gap-3">
                <!-- Avatar mascotte -->
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center"
                     x-html="currentMascot.avatar">
                </div>
                <div>
                    <h3 class="font-bold text-sm" x-text="currentMascot.name"></h3>
                    <p class="text-xs opacity-80" x-text="currentMascot.subtitle"></p>
                </div>
            </div>
            <div class="flex items-center gap-1">
                <!-- Sélecteur de mascotte -->
                <div class="relative" x-data="{ mascotMenu: false }">
                    <button @click="mascotMenu = !mascotMenu"
                            class="w-8 h-8 hover:bg-white/20 rounded-full flex items-center justify-center transition"
                            title="Changer de mascotte">
                        <i class="fas fa-palette text-sm"></i>
                    </button>
                    <!-- Menu mascottes -->
                    <div x-show="mascotMenu"
                         @click.outside="mascotMenu = false"
                         x-transition
                         class="absolute right-0 top-10 bg-white rounded-xl shadow-xl border border-gray-200 p-2 z-50 w-44">
                        <p class="text-xs text-gray-500 px-2 py-1 font-medium">Choisir mascotte</p>
                        <template x-for="(mascot, key) in mascots" :key="key">
                            <button @click="changeMascot(key); mascotMenu = false"
                                    class="w-full flex items-center gap-2 px-2 py-2 rounded-lg hover:bg-gray-100 transition text-left"
                                    :class="{ 'bg-gray-100': currentMascotKey === key }">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center"
                                     :class="mascot.btnGradient"
                                     x-html="mascot.miniAvatar">
                                </div>
                                <span class="text-sm text-gray-700 font-medium" x-text="mascot.name"></span>
                                <i x-show="currentMascotKey === key" class="fas fa-check text-green-500 ml-auto text-xs"></i>
                            </button>
                        </template>
                    </div>
                </div>
                <!-- Nouvelle conversation -->
                <button @click="newConversation()"
                        class="w-8 h-8 hover:bg-white/20 rounded-full flex items-center justify-center transition"
                        title="Nouvelle conversation">
                    <i class="fas fa-plus text-sm"></i>
                </button>
                <!-- Historique -->
                <a href="/stm/admin/agent/history"
                   class="w-8 h-8 hover:bg-white/20 rounded-full flex items-center justify-center transition"
                   title="Historique">
                    <i class="fas fa-history text-sm"></i>
                </a>
                <!-- Fermer -->
                <button @click="toggleChat()" class="w-8 h-8 hover:bg-white/20 rounded-full flex items-center justify-center transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Messages -->
        <div class="flex-1 overflow-y-auto p-4 space-y-4" x-ref="messagesContainer">

            <!-- Message de bienvenue -->
            <template x-if="messages.length === 0">
                <div class="text-center py-6">
                    <div class="w-20 h-20 mx-auto mb-4 rounded-2xl flex items-center justify-center animate-bounce-slow"
                         :class="currentMascot.btnGradient">
                        <div x-html="currentMascot.largeAvatar"></div>
                    </div>
                    <h4 class="font-bold text-gray-900 mb-1" x-text="currentMascot.greeting"></h4>
                    <p class="text-sm text-gray-500 mb-4">
                        Comment puis-je vous aider ?
                    </p>
                    <div class="space-y-2">
                        <button @click="sendQuickQuestion('Quelles sont les campagnes en cours ?')"
                                class="block w-full text-left text-sm bg-gray-50 hover:bg-gray-100 rounded-lg px-3 py-2 text-gray-700 transition">
                            📊 Campagnes en cours
                        </button>
                        <button @click="sendQuickQuestion('Montre-moi les stats de Black Friday 2025')"
                                class="block w-full text-left text-sm bg-gray-50 hover:bg-gray-100 rounded-lg px-3 py-2 text-gray-700 transition">
                            📈 Stats Black Friday
                        </button>
                        <button @click="sendQuickQuestion('Top 10 des produits vendus')"
                                class="block w-full text-left text-sm bg-gray-50 hover:bg-gray-100 rounded-lg px-3 py-2 text-gray-700 transition">
                            🏆 Top produits
                        </button>
                    </div>
                </div>
            </template>

            <!-- Liste des messages -->
            <template x-for="(msg, index) in messages" :key="index">
                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div class="max-w-[85%] px-4 py-2 text-sm"
                         :class="msg.role === 'user'
                            ? [currentMascot.userBubble, 'text-white rounded-2xl rounded-br-md']
                            : 'bg-gray-100 text-gray-800 rounded-2xl rounded-bl-md'">
                        <!-- Contenu texte du message -->
                        <div x-html="formatMessage(msg.content)"></div>

                        <!-- Boutons d'action (pour les messages assistant uniquement) -->
                        <template x-if="msg.role === 'assistant' && getButtons(msg.content).length > 0">
                            <div class="mt-3 pt-3 border-t border-gray-200 space-y-2">
                                <template x-for="(btn, btnIdx) in getButtons(msg.content)" :key="btnIdx">
                                    <button @click="sendQuickQuestion(btn.action)"
                                            class="block w-full text-left text-xs bg-white hover:bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 transition hover:border-gray-300"
                                            :class="currentMascot.buttonText">
                                        <i class="fas fa-arrow-right mr-2 opacity-50"></i>
                                        <span x-text="btn.label"></span>
                                    </button>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <!-- Loading indicator -->
            <div x-show="isLoading" class="flex justify-start">
                <div class="bg-gray-100 text-gray-800 rounded-2xl rounded-bl-md px-4 py-3">
                    <div class="flex items-center gap-2">
                        <div class="flex space-x-1">
                            <div class="w-2 h-2 rounded-full animate-bounce" :class="currentMascot.dotColor" style="animation-delay: 0ms"></div>
                            <div class="w-2 h-2 rounded-full animate-bounce" :class="currentMascot.dotColor" style="animation-delay: 150ms"></div>
                            <div class="w-2 h-2 rounded-full animate-bounce" :class="currentMascot.dotColor" style="animation-delay: 300ms"></div>
                        </div>
                        <span class="text-xs text-gray-500" x-text="currentMascot.thinkingText"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Input -->
        <div class="border-t border-gray-200 p-3 flex-shrink-0 bg-white">
            <form @submit.prevent="sendMessage()" class="flex gap-2">
                <input type="text"
                       x-model="inputMessage"
                       :disabled="isLoading"
                       placeholder="Posez votre question..."
                       class="flex-1 border border-gray-300 rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:border-transparent disabled:bg-gray-100"
                       :class="currentMascot.focusRing">
                <button type="submit"
                        :disabled="isLoading || !inputMessage.trim()"
                        class="w-10 h-10 text-white rounded-full flex items-center justify-center transition disabled:opacity-50"
                        :class="currentMascot.sendButton">
                    <i class="fas fa-paper-plane text-sm"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<style>
[x-cloak] { display: none !important; }

@keyframes bounce-slow {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}
.animate-bounce-slow { animation: bounce-slow 2s ease-in-out infinite; }
</style>

<script>
function chatWidget() {
    return {
        isOpen: false,
        isLoading: false,
        inputMessage: '',
        messages: [],
        sessionId: null,
        currentMascotKey: 'zippy',

        // Définition des 3 mascottes
        mascots: {
            zippy: {
                name: 'Zippy',
                subtitle: 'Robot Assistant',
                greeting: 'Bip boop ! 🤖 Je suis Zippy !',
                thinkingText: 'Zippy calcule...',
                headerGradient: 'bg-gradient-to-r from-cyan-400 to-blue-500',
                btnGradient: 'bg-gradient-to-br from-cyan-400 to-blue-500',
                textColor: 'text-white',
                userBubble: 'bg-cyan-500',
                sendButton: 'bg-cyan-500 hover:bg-cyan-600',
                focusRing: 'focus:ring-cyan-400',
                dotColor: 'bg-cyan-400',
                buttonText: 'text-cyan-700',
                avatar: `<svg viewBox="0 0 100 100" class="w-7 h-7">
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
                miniAvatar: `<svg viewBox="0 0 100 100" class="w-5 h-5">
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
                floatingAvatar: `<svg viewBox="0 0 100 100" class="w-9 h-9">
                    <circle cx="50" cy="10" r="6" fill="#67e8f9"/>
                    <rect x="48" y="14" width="4" height="10" fill="#67e8f9"/>
                    <rect x="18" y="25" width="64" height="55" rx="14" fill="#fff"/>
                    <rect x="6" y="38" width="12" height="20" rx="5" fill="#67e8f9"/>
                    <rect x="82" y="38" width="12" height="20" rx="5" fill="#67e8f9"/>
                    <circle cx="38" cy="50" r="10" fill="#0891b2"/>
                    <circle cx="62" cy="50" r="10" fill="#0891b2"/>
                    <circle cx="40" cy="47" r="4" fill="#fff"/>
                    <circle cx="64" cy="47" r="4" fill="#fff"/>
                    <rect x="30" y="68" width="40" height="8" rx="4" fill="#22d3ee"/>
                    <path d="M34 72 Q50 77 66 72" stroke="#fff" stroke-width="1.5" fill="none" stroke-linecap="round"/>
                </svg>`,
                largeAvatar: `<svg viewBox="0 0 100 100" class="w-14 h-14">
                    <circle cx="50" cy="10" r="7" fill="#67e8f9"/>
                    <rect x="47" y="15" width="6" height="10" fill="#67e8f9"/>
                    <rect x="15" y="26" width="70" height="58" rx="16" fill="#fff"/>
                    <rect x="5" y="40" width="12" height="22" rx="5" fill="#67e8f9"/>
                    <rect x="83" y="40" width="12" height="22" rx="5" fill="#67e8f9"/>
                    <circle cx="38" cy="52" r="11" fill="#0891b2"/>
                    <circle cx="62" cy="52" r="11" fill="#0891b2"/>
                    <circle cx="40" cy="49" r="4" fill="#fff"/>
                    <circle cx="64" cy="49" r="4" fill="#fff"/>
                    <rect x="28" y="70" width="44" height="9" rx="4" fill="#22d3ee"/>
                    <path d="M32 74 Q50 79 68 74" stroke="#fff" stroke-width="2" fill="none" stroke-linecap="round"/>
                </svg>`
            },
            mochi: {
                name: 'Mochi',
                subtitle: 'Assistant Kawaii',
                greeting: 'Coucou ! 🌸 Je suis Mochi !',
                thinkingText: 'Mochi réfléchit...',
                headerGradient: 'bg-gradient-to-r from-pink-300 to-rose-400',
                btnGradient: 'bg-gradient-to-br from-pink-300 to-rose-400',
                textColor: 'text-gray-900',
                userBubble: 'bg-pink-400',
                sendButton: 'bg-pink-500 hover:bg-pink-600',
                focusRing: 'focus:ring-pink-400',
                dotColor: 'bg-pink-400',
                buttonText: 'text-pink-700',
                avatar: `<svg viewBox="0 0 100 100" class="w-7 h-7">
                    <ellipse cx="50" cy="55" rx="35" ry="32" fill="#fce7f3"/>
                    <circle cx="28" cy="32" r="10" fill="#fce7f3"/>
                    <circle cx="72" cy="32" r="10" fill="#fce7f3"/>
                    <circle cx="28" cy="32" r="6" fill="#fbcfe8"/>
                    <circle cx="72" cy="32" r="6" fill="#fbcfe8"/>
                    <ellipse cx="38" cy="52" rx="6" ry="8" fill="#1f2937"/>
                    <ellipse cx="62" cy="52" rx="6" ry="8" fill="#1f2937"/>
                    <ellipse cx="40" cy="49" rx="2" ry="3" fill="#fff"/>
                    <ellipse cx="64" cy="49" rx="2" ry="3" fill="#fff"/>
                    <path d="M43 68 Q48 75 50 68 Q52 75 57 68" stroke="#1f2937" stroke-width="2" fill="none" stroke-linecap="round"/>
                    <ellipse cx="24" cy="60" rx="5" ry="3" fill="#f9a8d4" opacity="0.6"/>
                    <ellipse cx="76" cy="60" rx="5" ry="3" fill="#f9a8d4" opacity="0.6"/>
                </svg>`,
                miniAvatar: `<svg viewBox="0 0 100 100" class="w-5 h-5">
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
                floatingAvatar: `<svg viewBox="0 0 100 100" class="w-9 h-9">
                    <ellipse cx="50" cy="55" rx="38" ry="35" fill="#fff"/>
                    <circle cx="25" cy="30" r="12" fill="#fff"/>
                    <circle cx="75" cy="30" r="12" fill="#fff"/>
                    <circle cx="25" cy="30" r="7" fill="#fbcfe8"/>
                    <circle cx="75" cy="30" r="7" fill="#fbcfe8"/>
                    <ellipse cx="35" cy="52" rx="7" ry="9" fill="#1f2937"/>
                    <ellipse cx="65" cy="52" rx="7" ry="9" fill="#1f2937"/>
                    <ellipse cx="37" cy="49" rx="3" ry="4" fill="#fff"/>
                    <ellipse cx="67" cy="49" rx="3" ry="4" fill="#fff"/>
                    <path d="M42 70 Q47 78 50 70 Q53 78 58 70" stroke="#1f2937" stroke-width="2.5" fill="none" stroke-linecap="round"/>
                    <ellipse cx="24" cy="62" rx="6" ry="4" fill="#f9a8d4" opacity="0.7"/>
                    <ellipse cx="76" cy="62" rx="6" ry="4" fill="#f9a8d4" opacity="0.7"/>
                </svg>`,
                largeAvatar: `<svg viewBox="0 0 100 100" class="w-14 h-14">
                    <ellipse cx="50" cy="58" rx="40" ry="36" fill="#fff"/>
                    <circle cx="22" cy="28" r="14" fill="#fff"/>
                    <circle cx="78" cy="28" r="14" fill="#fff"/>
                    <circle cx="22" cy="28" r="8" fill="#fbcfe8"/>
                    <circle cx="78" cy="28" r="8" fill="#fbcfe8"/>
                    <ellipse cx="35" cy="55" rx="8" ry="11" fill="#1f2937"/>
                    <ellipse cx="65" cy="55" rx="8" ry="11" fill="#1f2937"/>
                    <ellipse cx="37" cy="51" rx="3" ry="4" fill="#fff"/>
                    <ellipse cx="67" cy="51" rx="3" ry="4" fill="#fff"/>
                    <ellipse cx="50" cy="68" rx="4" ry="3" fill="#f9a8d4"/>
                    <path d="M40 75 Q45 84 50 75 Q55 84 60 75" stroke="#1f2937" stroke-width="3" fill="none" stroke-linecap="round"/>
                    <ellipse cx="20" cy="65" rx="8" ry="5" fill="#f9a8d4" opacity="0.6"/>
                    <ellipse cx="80" cy="65" rx="8" ry="5" fill="#f9a8d4" opacity="0.6"/>
                </svg>`
            },
            pepper: {
                name: 'Pepper',
                subtitle: 'Assistant Piquant',
                greeting: 'Salut ! 🌶️ Je suis Pepper !',
                thinkingText: 'Pepper mijote...',
                headerGradient: 'bg-gradient-to-r from-red-400 to-red-600',
                btnGradient: 'bg-gradient-to-br from-red-400 to-red-600',
                textColor: 'text-white',
                userBubble: 'bg-red-500',
                sendButton: 'bg-red-500 hover:bg-red-600',
                focusRing: 'focus:ring-red-400',
                dotColor: 'bg-red-400',
                buttonText: 'text-red-700',
                avatar: `<svg viewBox="0 0 100 100" class="w-7 h-7">
                    <rect x="45" y="5" width="10" height="14" rx="3" fill="#22c55e"/>
                    <ellipse cx="50" cy="17" rx="14" ry="5" fill="#22c55e"/>
                    <ellipse cx="50" cy="55" rx="30" ry="36" fill="#fff"/>
                    <ellipse cx="38" cy="52" rx="8" ry="34" fill="#fecaca" opacity="0.2"/>
                    <circle cx="38" cy="48" r="6" fill="#1f2937"/>
                    <circle cx="62" cy="48" r="6" fill="#1f2937"/>
                    <circle cx="40" cy="46" r="2.5" fill="#fff"/>
                    <circle cx="64" cy="46" r="2.5" fill="#fff"/>
                    <path d="M35 62 Q50 80 65 62" stroke="#dc2626" stroke-width="3" fill="#fff" stroke-linecap="round"/>
                    <ellipse cx="50" cy="72" rx="5" ry="3" fill="#f87171"/>
                    <circle cx="26" cy="56" r="4" fill="#fca5a5" opacity="0.5"/>
                    <circle cx="74" cy="56" r="4" fill="#fca5a5" opacity="0.5"/>
                </svg>`,
                miniAvatar: `<svg viewBox="0 0 100 100" class="w-5 h-5">
                    <rect x="45" y="5" width="10" height="14" rx="3" fill="#22c55e"/>
                    <ellipse cx="50" cy="17" rx="14" ry="5" fill="#22c55e"/>
                    <ellipse cx="50" cy="55" rx="30" ry="36" fill="#fff"/>
                    <circle cx="38" cy="48" r="6" fill="#1f2937"/>
                    <circle cx="62" cy="48" r="6" fill="#1f2937"/>
                    <circle cx="40" cy="46" r="2" fill="#fff"/>
                    <circle cx="64" cy="46" r="2" fill="#fff"/>
                    <path d="M35 62 Q50 78 65 62" stroke="#dc2626" stroke-width="3" fill="#fff" stroke-linecap="round"/>
                    <ellipse cx="50" cy="70" rx="4" ry="2.5" fill="#f87171"/>
                    <circle cx="26" cy="56" r="4" fill="#fca5a5" opacity="0.5"/>
                    <circle cx="74" cy="56" r="4" fill="#fca5a5" opacity="0.5"/>
                </svg>`,
                floatingAvatar: `<svg viewBox="0 0 100 100" class="w-9 h-9">
                    <rect x="43" y="5" width="14" height="14" rx="4" fill="#22c55e"/>
                    <ellipse cx="50" cy="17" rx="16" ry="6" fill="#22c55e"/>
                    <ellipse cx="50" cy="58" rx="34" ry="40" fill="#fff"/>
                    <ellipse cx="38" cy="55" rx="12" ry="38" fill="#fecaca" opacity="0.3"/>
                    <circle cx="38" cy="50" r="7" fill="#1f2937"/>
                    <circle cx="62" cy="50" r="7" fill="#1f2937"/>
                    <circle cx="40" cy="48" r="3" fill="#fff"/>
                    <circle cx="64" cy="48" r="3" fill="#fff"/>
                    <path d="M32 68 Q50 90 68 68" stroke="#991b1b" stroke-width="3" fill="#fff" stroke-linecap="round"/>
                    <ellipse cx="50" cy="78" rx="6" ry="4" fill="#f87171"/>
                    <circle cx="26" cy="60" r="5" fill="#fca5a5" opacity="0.6"/>
                    <circle cx="74" cy="60" r="5" fill="#fca5a5" opacity="0.6"/>
                </svg>`,
                largeAvatar: `<svg viewBox="0 0 100 100" class="w-14 h-14">
                    <rect x="42" y="2" width="16" height="16" rx="5" fill="#22c55e"/>
                    <ellipse cx="50" cy="16" rx="18" ry="7" fill="#22c55e"/>
                    <ellipse cx="50" cy="58" rx="36" ry="42" fill="#fff"/>
                    <ellipse cx="36" cy="58" rx="14" ry="40" fill="#fecaca" opacity="0.25"/>
                    <circle cx="38" cy="50" r="8" fill="#1f2937"/>
                    <circle cx="62" cy="50" r="8" fill="#1f2937"/>
                    <circle cx="40" cy="47" r="3" fill="#fff"/>
                    <circle cx="64" cy="47" r="3" fill="#fff"/>
                    <path d="M30 70 Q50 95 70 70" stroke="#991b1b" stroke-width="4" fill="#fff" stroke-linecap="round"/>
                    <ellipse cx="50" cy="82" rx="8" ry="5" fill="#f87171"/>
                    <circle cx="24" cy="62" r="7" fill="#fca5a5" opacity="0.6"/>
                    <circle cx="76" cy="62" r="7" fill="#fca5a5" opacity="0.6"/>
                </svg>`
            }
        },

        get currentMascot() {
            return this.mascots[this.currentMascotKey];
        },

        init() {
            // Charger la mascotte sauvegardée
            const saved = localStorage.getItem('stm_mascot');
            if (saved && this.mascots[saved]) {
                this.currentMascotKey = saved;
            }

            // Exposer des fonctions globales pour l'historique
            window.chatWidgetOpen = () => this.openChat();
            window.chatWidgetLoad = (sessionId) => this.loadConversation(sessionId);
        },

        changeMascot(key) {
            this.currentMascotKey = key;
            localStorage.setItem('stm_mascot', key);
        },

        toggleChat() {
            this.isOpen = !this.isOpen;
        },

        openChat() {
            this.isOpen = true;
        },

        newConversation() {
            this.messages = [];
            this.sessionId = null;
            this.inputMessage = '';
        },

        async loadConversation(sessionId) {
            this.isLoading = true;
            this.isOpen = true;

            try {
                const response = await fetch('/stm/admin/agent/load/' + sessionId);
                const data = await response.json();

                if (data.success) {
                    this.sessionId = data.session_id;
                    this.messages = data.messages || [];
                    this.scrollToBottom();
                }
            } catch (error) {
                console.error('Erreur chargement conversation:', error);
            }

            this.isLoading = false;
        },

        async sendMessage() {
            const message = this.inputMessage.trim();
            if (!message || this.isLoading) return;

            // Ajouter le message utilisateur
            this.messages.push({
                role: 'user',
                content: message
            });

            this.inputMessage = '';
            this.isLoading = true;
            this.scrollToBottom();

            try {
                // Préparer l'historique (max 10 derniers messages)
                const history = this.messages.slice(-11, -1).map(m => ({
                    role: m.role,
                    content: m.content
                }));

                const response = await fetch('/stm/admin/agent/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        message: message,
                        history: history,
                        session_id: this.sessionId
                    })
                });

                const data = await response.json();

                if (data.error) {
                    this.messages.push({
                        role: 'assistant',
                        content: '❌ ' + data.error
                    });
                } else {
                    // Mettre à jour le session_id si nouveau
                    if (data.session_id) {
                        this.sessionId = data.session_id;
                    }

                    this.messages.push({
                        role: 'assistant',
                        content: data.response
                    });
                }
            } catch (error) {
                console.error('Chat error:', error);
                this.messages.push({
                    role: 'assistant',
                    content: '❌ Erreur de connexion. Veuillez réessayer.'
                });
            }

            this.isLoading = false;
            this.scrollToBottom();
        },

        sendQuickQuestion(question) {
            this.inputMessage = question;
            this.sendMessage();
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const container = this.$refs.messagesContainer;
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            });
        },

        /**
         * Extraire les boutons du message
         * Format: [BTN:action|label]
         */
        getButtons(content) {
            if (!content) return [];

            const buttons = [];
            const regex = /\[BTN:([^\|]+)\|([^\]]+)\]/g;
            let match;

            while ((match = regex.exec(content)) !== null) {
                buttons.push({
                    action: match[1].trim(),
                    label: match[2].trim()
                });
            }

            return buttons;
        },

        /**
         * Formater le message pour l'affichage
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

            // Listes avec - ou •
            formatted = formatted.replace(/^- /gm, '<span class="opacity-50">•</span> ');
            formatted = formatted.replace(/^• /gm, '<span class="opacity-50">•</span> ');

            // Nettoyer les lignes vides multiples
            formatted = formatted.replace(/(<br>\s*){3,}/g, '<br><br>');

            return formatted.trim();
        }
    }
}
</script>