<?php
/**
 * AgentController.php
 *
 * Controller pour l'agent conversationnel STM
 * Gère les échanges entre l'utilisateur et OpenAI
 * Sauvegarde l'historique des conversations par utilisateur
 *
 * @created  2025/12/09
 * @modified 2025/12/09 - Ajout historique conversations
 * @package  STM Agent
 */

namespace App\Controllers;

use App\Services\OpenAIService;
use App\Agent\AgentTools;
use Core\Database;

class AgentController
{
    private OpenAIService $openai;
    private AgentTools $tools;
    private Database $db;
    private string $systemPrompt;

    public function __construct()
    {
        $this->openai = new OpenAIService();
        $this->tools = new AgentTools();
        $this->db = Database::getInstance();

        // Récupérer le schéma DB pour le prompt
        $dbSchema = $this->tools->getDbSchema();

        $this->systemPrompt = <<<PROMPT
Tu es l'assistant STM, un agent intelligent pour le système de gestion de campagnes promotionnelles STM v2 de Trendy Foods.

## TON RÔLE
Tu aides les utilisateurs à interroger les données des campagnes promotionnelles.

## ARCHITECTURE DES DONNÉES
Il y a DEUX bases de données :
1. **Base LOCALE** (trendyblog_stm_v2) : campaigns, orders, products, order_lines, customers
2. **Base EXTERNE** (trendyblog_sig) : BE_CLL/LU_CLL (clients), BE_REP/LU_REP (représentants)

⚠️ IMPORTANT : Les représentants et leurs clients sont dans la BASE EXTERNE !
- Pour chercher un rep par nom → utilise `query_external_database` sur BE_REP
- Pour les stats d'un rep sur une campagne → utilise `get_rep_campaign_stats`
- Pour les commandes/produits → utilise `query_database` sur la base locale

## RÈGLES
1. Réponds toujours en français
2. Sois concis et précis
3. Choisis le bon tool selon le type de données
4. Formate les nombres avec espaces (6 314)

## TOOLS DISPONIBLES
- `get_rep_campaign_stats` : Stats d'un rep sur une campagne (RECOMMANDÉ pour les questions type "Stats de Tahir sur Black Friday")
- `query_external_database` : Requêtes sur BE_CLL, LU_CLL, BE_REP, LU_REP
- `query_database` : Requêtes sur la base locale (orders, products, etc.)
- `list_campaigns` : Liste rapide des campagnes

## SCHÉMA DES BASES
{$dbSchema}
PROMPT;
    }

    /**
     * Obtenir l'ID de l'utilisateur connecté
     */
    private function getCurrentUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Sauvegarder un message dans l'historique
     */
    private function saveMessage(string $sessionId, string $role, string $content, ?string $title = null): void
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) return;

        try {
            $sql = "INSERT INTO agent_conversations (user_id, session_id, title, role, content)
                    VALUES (:user_id, :session_id, :title, :role, :content)";

            $this->db->query($sql, [
                ':user_id' => $userId,
                ':session_id' => $sessionId,
                ':title' => $title,
                ':role' => $role,
                ':content' => $content
            ]);
        } catch (\Exception $e) {
            error_log("Erreur sauvegarde message agent: " . $e->getMessage());
        }
    }

    /**
     * Générer un titre à partir du premier message
     */
    private function generateTitle(string $message): string
    {
        $title = mb_substr($message, 0, 50);
        if (mb_strlen($message) > 50) {
            $title .= '...';
        }
        return $title;
    }

    /**
     * Endpoint principal du chat
     * POST /stm/admin/agent/chat
     */
    public function chat(): void
    {
        header('Content-Type: application/json');

        // Vérifier la méthode
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'Méthode non autorisée']);
            return;
        }

        // Récupérer le message
        $input = json_decode(file_get_contents('php://input'), true);
        $userMessage = trim($input['message'] ?? '');
        $history = $input['history'] ?? [];
        $sessionId = $input['session_id'] ?? $this->generateSessionId();
        $isNewSession = empty($input['session_id']);

        if (empty($userMessage)) {
            echo json_encode(['error' => 'Message vide']);
            return;
        }

        try {
            // Sauvegarder le message utilisateur
            $title = $isNewSession ? $this->generateTitle($userMessage) : null;
            $this->saveMessage($sessionId, 'user', $userMessage, $title);

            // Construire l'historique des messages
            $messages = [];

            foreach ($history as $msg) {
                $messages[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content']
                ];
            }

            // Ajouter le nouveau message
            $messages[] = [
                'role' => 'user',
                'content' => $userMessage
            ];

            // Appeler OpenAI avec les tools
            $response = $this->openai->chat(
                $messages,
                $this->tools->getToolsDefinition(),
                $this->systemPrompt
            );

            // Traiter les tool calls si présents
            $finalResponse = $this->processResponse($response, $messages);

            // Sauvegarder la réponse de l'assistant
            $this->saveMessage($sessionId, 'assistant', $finalResponse);

            echo json_encode([
                'success' => true,
                'response' => $finalResponse,
                'session_id' => $sessionId
            ]);

        } catch (\Exception $e) {
            error_log("AgentController error: " . $e->getMessage());
            echo json_encode([
                'error' => 'Erreur: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Générer un ID de session unique
     */
    private function generateSessionId(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Traiter la réponse et exécuter les tools si nécessaire
     */
    private function processResponse(array $response, array $messages): string
    {
        $extracted = $this->openai->extractMessage($response);

        // Si pas de tool calls, retourner le contenu directement
        if (empty($extracted['tool_calls'])) {
            return $extracted['content'] ?? 'Je n\'ai pas pu générer de réponse.';
        }

        // Exécuter les tool calls
        $toolResults = [];

        foreach ($extracted['tool_calls'] as $toolCall) {
            $toolName = $toolCall['function']['name'];
            $arguments = json_decode($toolCall['function']['arguments'], true) ?? [];

            // Exécuter le tool
            $result = $this->tools->executeTool($toolName, $arguments);

            $toolResults[] = [
                'tool_call_id' => $toolCall['id'],
                'name' => $toolName,
                'result' => $result
            ];
        }

        // Ajouter l'assistant message avec les tool calls
        $messages[] = [
            'role' => 'assistant',
            'content' => $extracted['content'],
            'tool_calls' => $extracted['tool_calls']
        ];

        // Ajouter les résultats des tools
        foreach ($toolResults as $tr) {
            $messages[] = [
                'role' => 'tool',
                'tool_call_id' => $tr['tool_call_id'],
                'content' => json_encode($tr['result'], JSON_UNESCAPED_UNICODE)
            ];
        }

        // Appeler OpenAI à nouveau pour obtenir la réponse finale
        $finalResponse = $this->openai->continueWithToolResult(
            $messages,
            $this->tools->getToolsDefinition(),
            $this->systemPrompt
        );

        $finalExtracted = $this->openai->extractMessage($finalResponse);

        // Vérifier s'il y a encore des tool calls (récursion limitée)
        if (!empty($finalExtracted['tool_calls'])) {
            // Récursion une fois max pour éviter les boucles infinies
            return $this->processResponse($finalResponse, $messages);
        }

        return $finalExtracted['content'] ?? 'Je n\'ai pas pu générer de réponse.';
    }

    /**
     * Page historique des conversations
     * GET /stm/admin/agent/history
     */
    public function history(): void
    {
        $userId = $this->getCurrentUserId();

        // Récupérer les conversations groupées par session
        $conversations = $this->db->query(
            "SELECT
                session_id,
                MIN(title) as title,
                MIN(created_at) as started_at,
                MAX(created_at) as last_message_at,
                COUNT(*) as message_count
             FROM agent_conversations
             WHERE user_id = :user_id
             GROUP BY session_id
             ORDER BY last_message_at DESC
             LIMIT 50",
            [':user_id' => $userId]
        );

        $title = "Historique - Agent STM";
        $activeMenu = "agent-history";

        ob_start();
        require __DIR__ . '/../Views/admin/agent/history.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/admin.php';
    }

    /**
     * Voir une conversation spécifique
     * GET /stm/admin/agent/conversation/{session_id}
     */
    public function conversation(string $sessionId): void
    {
        $userId = $this->getCurrentUserId();

        // Récupérer les messages de cette conversation
        $messages = $this->db->query(
            "SELECT role, content, created_at
             FROM agent_conversations
             WHERE user_id = :user_id AND session_id = :session_id
             ORDER BY created_at ASC",
            [':user_id' => $userId, ':session_id' => $sessionId]
        );

        if (empty($messages)) {
            header('Location: /stm/admin/agent/history');
            exit;
        }

        // Récupérer le titre
        $info = $this->db->query(
            "SELECT title, MIN(created_at) as started_at
             FROM agent_conversations
             WHERE session_id = :session_id
             GROUP BY session_id",
            [':session_id' => $sessionId]
        );

        $conversationTitle = $info[0]['title'] ?? 'Conversation';
        $startedAt = $info[0]['started_at'] ?? null;

        $title = "Conversation - Agent STM";
        $activeMenu = "agent-history";

        ob_start();
        require __DIR__ . '/../Views/admin/agent/conversation.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/admin.php';
    }

    /**
     * Charger une conversation dans le widget (AJAX)
     * GET /stm/admin/agent/load/{session_id}
     */
    public function load(string $sessionId): void
    {
        header('Content-Type: application/json');

        $userId = $this->getCurrentUserId();

        $messages = $this->db->query(
            "SELECT role, content
             FROM agent_conversations
             WHERE user_id = :user_id AND session_id = :session_id
             ORDER BY created_at ASC",
            [':user_id' => $userId, ':session_id' => $sessionId]
        );

        echo json_encode([
            'success' => true,
            'session_id' => $sessionId,
            'messages' => $messages
        ]);
    }

    /**
     * Supprimer une conversation
     * POST /stm/admin/agent/delete/{session_id}
     */
    public function delete(string $sessionId): void
    {
        header('Content-Type: application/json');

        $userId = $this->getCurrentUserId();

        try {
            $this->db->query(
                "DELETE FROM agent_conversations
                 WHERE user_id = :user_id AND session_id = :session_id",
                [':user_id' => $userId, ':session_id' => $sessionId]
            );

            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Page de test de l'agent (GET)
     */
    public function index(): void
    {
        $title = "Agent STM";
        $activeMenu = "agent";

        ob_start();
        ?>
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-4">🤖 Agent STM</h1>
                <p class="text-gray-600 mb-6">
                    Posez des questions sur vos campagnes, statistiques, produits et représentants.
                </p>

                <div class="bg-gray-50 rounded-lg p-4 mb-4">
                    <p class="text-sm font-medium text-gray-700 mb-2">Exemples de questions :</p>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• "Quelles sont les campagnes en cours ?"</li>
                        <li>• "Combien de commandes pour Black Friday 2025 ?"</li>
                        <li>• "Quel est le top 5 des produits de la campagne Noël ?"</li>
                        <li>• "Compare Black Friday et la campagne Anniversaire"</li>
                    </ul>
                </div>

                <div class="flex gap-4">
                    <a href="/stm/admin/agent/history" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                        <i class="fas fa-history mr-2"></i>
                        Voir l'historique
                    </a>
                </div>

                <p class="text-sm text-gray-500 mt-4">
                    💡 Utilisez le widget en bas à droite pour discuter avec l'agent.
                </p>
            </div>
        </div>
        <?php
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/admin.php';
    }
}