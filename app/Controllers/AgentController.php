<?php
/**
 * AgentController.php
 *
 * Controller pour l'agent conversationnel STM
 * Gère les échanges entre l'utilisateur et OpenAI
 *
 * @created  2025/12/09
 * @package  STM Agent
 */

namespace App\Controllers;

use App\Services\OpenAIService;
use App\Agent\AgentTools;

class AgentController
{
    private OpenAIService $openai;
    private AgentTools $tools;
    private string $systemPrompt;

    public function __construct()
    {
        $this->openai = new OpenAIService();
        $this->tools = new AgentTools();

        $this->systemPrompt = <<<PROMPT
Tu es l'assistant STM, un agent intelligent pour le système de gestion de campagnes promotionnelles STM v2 de Trendy Foods.

Tu aides les utilisateurs à :
- Consulter les statistiques des campagnes (ventes, commandes, produits, représentants)
- Obtenir des informations sur les performances
- Comparer les campagnes entre elles

Règles importantes :
1. Réponds toujours en français
2. Sois concis et précis dans tes réponses
3. Utilise les tools disponibles pour obtenir les données réelles
4. Formate les nombres avec des espaces (ex: 6 314 au lieu de 6314)
5. Si tu ne trouves pas une campagne, suggère à l'utilisateur de vérifier le nom
6. Si une question ne concerne pas STM, indique poliment que tu es spécialisé dans les stats STM

Tu as accès aux tools suivants :
- list_campaigns : lister les campagnes
- get_campaign_stats : stats détaillées d'une campagne
- get_top_products : classement des produits
- get_rep_stats : stats par représentant
- compare_campaigns : comparer plusieurs campagnes
PROMPT;
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

        if (empty($userMessage)) {
            echo json_encode(['error' => 'Message vide']);
            return;
        }

        try {
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

            echo json_encode([
                'success' => true,
                'response' => $finalResponse
            ]);

        } catch (\Exception $e) {
            error_log("AgentController error: " . $e->getMessage());
            echo json_encode([
                'error' => 'Erreur: ' . $e->getMessage()
            ]);
        }
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

                <p class="text-sm text-gray-500">
                    💡 Utilisez le widget en bas à droite pour discuter avec l'agent.
                </p>
            </div>
        </div>
        <?php
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/admin.php';
    }
}