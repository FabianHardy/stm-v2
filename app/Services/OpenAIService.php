<?php
/**
 * OpenAIService.php
 *
 * Service pour communiquer avec l'API OpenAI
 * Gère les appels chat completions avec support des tools (function calling)
 *
 * @created  2025/12/09
 * @package  STM Agent
 */

namespace App\Services;

class OpenAIService
{
    /**
     * Clé API OpenAI
     */
    private string $apiKey;

    /**
     * URL de l'API
     */
    private string $apiUrl = 'https://api.openai.com/v1/chat/completions';

    /**
     * Modèle à utiliser
     */
    private string $model = 'gpt-4o-mini';

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->apiKey = $_ENV['OPENAI_API_KEY'] ?? '';

        if (empty($this->apiKey)) {
            throw new \Exception('OPENAI_API_KEY non configurée dans .env');
        }
    }

    /**
     * Envoyer un message au chat et obtenir une réponse
     *
     * @param array $messages Historique des messages
     * @param array $tools Liste des tools disponibles (optionnel)
     * @param string|null $systemPrompt Prompt système (optionnel)
     * @return array Réponse de l'API
     */
    public function chat(array $messages, array $tools = [], ?string $systemPrompt = null): array
    {
        // Ajouter le system prompt si fourni
        $fullMessages = [];

        if ($systemPrompt) {
            $fullMessages[] = [
                'role' => 'system',
                'content' => $systemPrompt
            ];
        }

        $fullMessages = array_merge($fullMessages, $messages);

        // Construire le payload
        $payload = [
            'model' => $this->model,
            'messages' => $fullMessages,
            'temperature' => 0.7,
            'max_tokens' => 1000
        ];

        // Ajouter les tools si fournis
        if (!empty($tools)) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }

        // Appeler l'API
        $response = $this->makeRequest($payload);

        return $response;
    }

    /**
     * Continuer une conversation après un tool call
     *
     * @param array $messages Messages incluant le tool call et son résultat
     * @param array $tools Liste des tools
     * @param string|null $systemPrompt Prompt système
     * @return array Réponse de l'API
     */
    public function continueWithToolResult(array $messages, array $tools = [], ?string $systemPrompt = null): array
    {
        return $this->chat($messages, $tools, $systemPrompt);
    }

    /**
     * Effectuer la requête HTTP vers OpenAI
     *
     * @param array $payload Données à envoyer
     * @return array Réponse décodée
     */
    private function makeRequest(array $payload): array
    {
        $ch = curl_init($this->apiUrl);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 60
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            error_log("OpenAI cURL Error: " . $error);
            throw new \Exception("Erreur de connexion à OpenAI: " . $error);
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200) {
            $errorMsg = $data['error']['message'] ?? 'Erreur inconnue';
            error_log("OpenAI API Error: " . $errorMsg);
            throw new \Exception("Erreur OpenAI: " . $errorMsg);
        }

        return $data;
    }

    /**
     * Extraire le message de la réponse
     *
     * @param array $response Réponse de l'API
     * @return array ['content' => string, 'tool_calls' => array|null]
     */
    public function extractMessage(array $response): array
    {
        $choice = $response['choices'][0] ?? null;

        if (!$choice) {
            return ['content' => 'Erreur: réponse vide', 'tool_calls' => null];
        }

        $message = $choice['message'] ?? [];

        return [
            'content' => $message['content'] ?? '',
            'tool_calls' => $message['tool_calls'] ?? null,
            'finish_reason' => $choice['finish_reason'] ?? 'stop'
        ];
    }

    /**
     * Vérifier si la réponse contient des tool calls
     *
     * @param array $response Réponse de l'API
     * @return bool
     */
    public function hasToolCalls(array $response): bool
    {
        $message = $this->extractMessage($response);
        return !empty($message['tool_calls']);
    }

    /**
     * Obtenir les tool calls de la réponse
     *
     * @param array $response Réponse de l'API
     * @return array Liste des tool calls
     */
    public function getToolCalls(array $response): array
    {
        $message = $this->extractMessage($response);
        return $message['tool_calls'] ?? [];
    }
}