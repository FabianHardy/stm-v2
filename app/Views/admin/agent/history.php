<?php
/**
 * Vue : Historique des conversations Agent STM
 *
 * Liste des conversations passées avec l'agent
 *
 * @created  2025/12/09
 * @package  STM Agent
 */
?>

<!-- En-tête -->
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">🤖 Historique Agent STM</h1>
            <p class="text-gray-600 mt-1">Vos conversations passées avec l'assistant</p>
        </div>
        <button onclick="startNewConversation()" 
                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
            <i class="fas fa-plus mr-2"></i>
            Nouvelle conversation
        </button>
    </div>
</div>

<?php if (empty($conversations)): ?>
<!-- Aucune conversation -->
<div class="bg-white rounded-lg shadow-sm p-12 text-center">
    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="fas fa-comments text-gray-400 text-2xl"></i>
    </div>
    <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucune conversation</h3>
    <p class="text-gray-500 mb-4">Vous n'avez pas encore discuté avec l'agent STM.</p>
    <button onclick="startNewConversation()" 
            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
        <i class="fas fa-robot mr-2"></i>
        Commencer une conversation
    </button>
</div>

<?php else: ?>
<!-- Liste des conversations -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="divide-y divide-gray-100">
        <?php foreach ($conversations as $conv): ?>
        <div class="p-4 hover:bg-gray-50 transition flex items-center justify-between group">
            <a href="/stm/admin/agent/conversation/<?= htmlspecialchars($conv['session_id']) ?>" 
               class="flex-1 min-w-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-robot text-indigo-600"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-gray-900 truncate">
                            <?= htmlspecialchars($conv['title'] ?? 'Conversation sans titre') ?>
                        </p>
                        <p class="text-sm text-gray-500">
                            <i class="fas fa-comment mr-1"></i>
                            <?= $conv['message_count'] ?> messages
                            <span class="mx-2">•</span>
                            <i class="fas fa-clock mr-1"></i>
                            <?= date('d/m/Y H:i', strtotime($conv['last_message_at'])) ?>
                        </p>
                    </div>
                </div>
            </a>
            
            <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition">
                <button onclick="loadConversation('<?= htmlspecialchars($conv['session_id']) ?>')"
                        class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition"
                        title="Reprendre cette conversation">
                    <i class="fas fa-play"></i>
                </button>
                <button onclick="deleteConversation('<?= htmlspecialchars($conv['session_id']) ?>')"
                        class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition"
                        title="Supprimer">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<script>
function startNewConversation() {
    // Ouvrir le widget avec une nouvelle conversation
    if (window.chatWidgetOpen) {
        window.chatWidgetOpen();
    } else {
        // Si le widget n'est pas chargé, juste afficher une alerte
        alert('Utilisez le bouton robot en bas à droite pour commencer une conversation.');
    }
}

function loadConversation(sessionId) {
    // Charger la conversation dans le widget
    if (window.chatWidgetLoad) {
        window.chatWidgetLoad(sessionId);
    } else {
        // Rediriger vers la page de conversation
        window.location.href = '/stm/admin/agent/conversation/' + sessionId;
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
