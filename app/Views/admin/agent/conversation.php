<?php
/**
 * Vue : Détail d'une conversation Agent STM
 *
 * Affiche tous les messages d'une conversation
 *
 * @created  2025/12/09
 * @package  STM Agent
 */
?>

<!-- En-tête -->
<div class="mb-6">
    <div class="flex items-center gap-4">
        <a href="/stm/admin/agent/history" 
           class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div class="flex-1">
            <h1 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($conversationTitle) ?></h1>
            <p class="text-sm text-gray-500">
                <i class="fas fa-clock mr-1"></i>
                Commencée le <?= date('d/m/Y à H:i', strtotime($startedAt)) ?>
                <span class="mx-2">•</span>
                <?= count($messages) ?> messages
            </p>
        </div>
        <button onclick="loadInWidget('<?= htmlspecialchars($sessionId) ?>')"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
            <i class="fas fa-play mr-2"></i>
            Reprendre
        </button>
    </div>
</div>

<!-- Messages -->
<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="space-y-4 max-w-3xl mx-auto">
        <?php foreach ($messages as $msg): ?>
        <div class="flex <?= $msg['role'] === 'user' ? 'justify-end' : 'justify-start' ?>">
            <div class="max-w-[80%] <?= $msg['role'] === 'user' 
                    ? 'bg-indigo-600 text-white rounded-2xl rounded-br-md' 
                    : 'bg-gray-100 text-gray-800 rounded-2xl rounded-bl-md' ?> px-4 py-3">
                
                <?php if ($msg['role'] === 'assistant'): ?>
                <div class="flex items-center gap-2 mb-2 pb-2 border-b border-gray-200">
                    <div class="w-6 h-6 bg-indigo-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-robot text-indigo-600 text-xs"></i>
                    </div>
                    <span class="text-xs font-medium text-gray-500">Agent STM</span>
                </div>
                <?php endif; ?>
                
                <div class="text-sm whitespace-pre-wrap"><?= nl2br(htmlspecialchars($msg['content'])) ?></div>
                
                <div class="text-xs <?= $msg['role'] === 'user' ? 'text-indigo-200' : 'text-gray-400' ?> mt-2">
                    <?= date('H:i', strtotime($msg['created_at'])) ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function loadInWidget(sessionId) {
    if (window.chatWidgetLoad) {
        window.chatWidgetLoad(sessionId);
    } else {
        alert('Le widget de chat n\'est pas disponible. Actualisez la page.');
    }
}
</script>
