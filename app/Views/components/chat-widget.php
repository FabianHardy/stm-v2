<?php
/**
 * Widget Chat Agent STM
 *
 * Widget flottant en bas à droite de l'interface admin
 * Permet de discuter avec l'agent IA
 *
 * @created  2025/12/09
 * @package  STM Agent
 */
?>

<!-- Chat Widget Container -->
<div x-data="chatWidget()" x-cloak class="fixed bottom-6 right-6 z-50">

    <!-- Bouton flottant -->
    <button @click="toggleChat()"
            class="w-14 h-14 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full shadow-lg flex items-center justify-center transition-all duration-300"
            :class="{ 'scale-0': isOpen, 'scale-100': !isOpen }">
        <i class="fas fa-robot text-xl"></i>
    </button>

    <!-- Fenêtre de chat -->
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95 translate-y-4"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-4"
         class="absolute bottom-0 right-0 w-96 h-[500px] bg-white rounded-2xl shadow-2xl flex flex-col overflow-hidden border border-gray-200">

        <!-- Header -->
        <div class="bg-indigo-600 text-white px-4 py-3 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center">
                    <i class="fas fa-robot"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-sm">Agent STM</h3>
                    <p class="text-xs text-indigo-200">Assistant campagnes</p>
                </div>
            </div>
            <button @click="toggleChat()" class="w-8 h-8 hover:bg-white/20 rounded-full flex items-center justify-center transition">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Messages -->
        <div class="flex-1 overflow-y-auto p-4 space-y-4" x-ref="messagesContainer">

            <!-- Message de bienvenue -->
            <template x-if="messages.length === 0">
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-robot text-indigo-600 text-2xl"></i>
                    </div>
                    <h4 class="font-semibold text-gray-900 mb-2">Bonjour ! 👋</h4>
                    <p class="text-sm text-gray-500 mb-4">
                        Je suis votre assistant STM. Posez-moi des questions sur vos campagnes !
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
                        <button @click="sendQuickQuestion('Liste toutes les campagnes')"
                                class="block w-full text-left text-sm bg-gray-50 hover:bg-gray-100 rounded-lg px-3 py-2 text-gray-700 transition">
                            📋 Toutes les campagnes
                        </button>
                    </div>
                </div>
            </template>

            <!-- Liste des messages -->
            <template x-for="(msg, index) in messages" :key="index">
                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div :class="msg.role === 'user'
                            ? 'bg-indigo-600 text-white rounded-2xl rounded-br-md'
                            : 'bg-gray-100 text-gray-800 rounded-2xl rounded-bl-md'"
                         class="max-w-[85%] px-4 py-2 text-sm">
                        <div x-html="formatMessage(msg.content)"></div>
                    </div>
                </div>
            </template>

            <!-- Loading indicator -->
            <div x-show="isLoading" class="flex justify-start">
                <div class="bg-gray-100 text-gray-800 rounded-2xl rounded-bl-md px-4 py-3">
                    <div class="flex items-center gap-2">
                        <div class="flex space-x-1">
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                        </div>
                        <span class="text-xs text-gray-500">Analyse en cours...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Input -->
        <div class="border-t border-gray-200 p-3 flex-shrink-0">
            <form @submit.prevent="sendMessage()" class="flex gap-2">
                <input type="text"
                       x-model="inputMessage"
                       :disabled="isLoading"
                       placeholder="Posez votre question..."
                       class="flex-1 border border-gray-300 rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent disabled:bg-gray-100">
                <button type="submit"
                        :disabled="isLoading || !inputMessage.trim()"
                        class="w-10 h-10 bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 text-white rounded-full flex items-center justify-center transition">
                    <i class="fas fa-paper-plane text-sm"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function chatWidget() {
    return {
        isOpen: false,
        isLoading: false,
        inputMessage: '',
        messages: [],

        toggleChat() {
            this.isOpen = !this.isOpen;
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
                const history = this.messages.slice(-10).map(m => ({
                    role: m.role,
                    content: m.content
                }));

                // Retirer le dernier message car il sera envoyé comme 'message'
                history.pop();

                const response = await fetch('/stm/admin/agent/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        message: message,
                        history: history
                    })
                });

                const data = await response.json();

                if (data.error) {
                    this.messages.push({
                        role: 'assistant',
                        content: '❌ ' + data.error
                    });
                } else {
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

        formatMessage(content) {
            if (!content) return '';

            // Échapper HTML
            let formatted = content
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

            // Convertir les retours à la ligne
            formatted = formatted.replace(/\n/g, '<br>');

            // Mettre en gras **texte**
            formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

            // Listes avec •
            formatted = formatted.replace(/^• /gm, '<span class="text-indigo-500">•</span> ');

            return formatted;
        }
    }
}
</script>

<style>
[x-cloak] { display: none !important; }
</style>