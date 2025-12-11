<?php
/**
 * ClaudeService.php
 *
 * Service pour communiquer avec l'API Anthropic Claude
 * Implémente AIServiceInterface pour compatibilité multi-provider
 *
 * @created  2025/12/11
 * @package  STM Agent
 */

namespace App\Services;

class ClaudeService implements AIServiceInterface
{
    private string $apiKey;
    private string $apiUrl = 'https://api.anthropic.com/v1/messages';
    private string $model;
    private float $temperature;
    private string $anthropicVersion = '2023-06-01';

    /**
     * Constructeur
     *
     * @param string $apiKey Clé API Anthropic
     * @param string $model Modèle Claude à utiliser
     * @param float $temperature Température (créativité)
     */
    public function __construct(string $apiKey, string $model = 'claude-3-5-sonnet-20241022', float $temperature = 0.7)
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
        $this->temperature = $temperature;

        if (empty($this->apiKey)) {
            throw new \Exception('Clé API Claude non configurée');
        }
    }

    /**
     * @inheritDoc
     */
    public function chat(array $messages, string $systemPrompt, array $tools = []): array
    {
        // Convertir les messages au format Claude
        $claudeMessages = $this->convertMessages($messages);

        $payload = [
            'model' => $this->model,
            'max_tokens' => 2000,
            'temperature' => $this->temperature,
            'messages' => $claudeMessages
        ];

        // Ajouter le system prompt
        if (!empty($systemPrompt)) {
            $payload['system'] = $systemPrompt;
        }

        // Convertir les tools au format Claude
        if (!empty($tools)) {
            $payload['tools'] = $this->convertTools($tools);
        }

        $response = $this->makeRequest($payload);

        // Convertir la réponse au format OpenAI-like pour compatibilité
        return $this->convertResponse($response);
    }

    /**
     * Convertir les messages au format Claude
     */
    private function convertMessages(array $messages): array
    {
        $claudeMessages = [];

        foreach ($messages as $msg) {
            // Ignorer les messages système (gérés séparément)
            if ($msg['role'] === 'system') {
                continue;
            }

            // Convertir tool_calls en tool_use (format Claude)
            if (isset($msg['tool_calls'])) {
                $claudeMessages[] = [
                    'role' => 'assistant',
                    'content' => array_map(function($tc) {
                        return [
                            'type' => 'tool_use',
                            'id' => $tc['id'],
                            'name' => $tc['function']['name'],
                            'input' => json_decode($tc['function']['arguments'], true) ?? []
                        ];
                    }, $msg['tool_calls'])
                ];
                continue;
            }

            // Convertir tool_result (format OpenAI) en tool_result (format Claude)
            if ($msg['role'] === 'tool') {
                $claudeMessages[] = [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'tool_result',
                            'tool_use_id' => $msg['tool_call_id'],
                            'content' => $msg['content']
                        ]
                    ]
                ];
                continue;
            }

            // Messages normaux
            $claudeMessages[] = [
                'role' => $msg['role'],
                'content' => $msg['content']
            ];
        }

        return $claudeMessages;
    }

    /**
     * Convertir les tools au format Claude
     */
    private function convertTools(array $tools): array
    {
        return array_map(function($tool) {
            $func = $tool['function'];
            return [
                'name' => $func['name'],
                'description' => $func['description'] ?? '',
                'input_schema' => $func['parameters'] ?? ['type' => 'object', 'properties' => []]
            ];
        }, $tools);
    }

    /**
     * Convertir la réponse Claude au format OpenAI-like
     */
    private function convertResponse(array $response): array
    {
        $content = '';
        $toolCalls = [];

        foreach ($response['content'] ?? [] as $block) {
            if ($block['type'] === 'text') {
                $content .= $block['text'];
            } elseif ($block['type'] === 'tool_use') {
                $toolCalls[] = [
                    'id' => $block['id'],
                    'type' => 'function',
                    'function' => [
                        'name' => $block['name'],
                        'arguments' => json_encode($block['input'])
                    ]
                ];
            }
        }

        $message = ['content' => $content];
        if (!empty($toolCalls)) {
            $message['tool_calls'] = $toolCalls;
        }

        return [
            'choices' => [
                [
                    'message' => $message,
                    'finish_reason' => $response['stop_reason'] === 'tool_use' ? 'tool_calls' : 'stop'
                ]
            ],
            'usage' => $response['usage'] ?? []
        ];
    }

    /**
     * Effectuer la requête HTTP
     */
    private function makeRequest(array $payload): array
    {
        $ch = curl_init($this->apiUrl);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: ' . $this->anthropicVersion
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 60
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception("Erreur de connexion Claude: " . $error);
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200) {
            $errorMsg = $data['error']['message'] ?? 'Erreur inconnue';
            throw new \Exception("Erreur Claude: " . $errorMsg);
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function getProviderName(): string
    {
        return 'Claude';
    }

    /**
     * @inheritDoc
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * @inheritDoc
     */
    public function testConnection(): array
    {
        try {
            $ch = curl_init($this->apiUrl);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'x-api-key: ' . $this->apiKey,
                    'anthropic-version: ' . $this->anthropicVersion,
                    'Content-Type: application/json'
                ],
                CURLOPT_POSTFIELDS => json_encode([
                    'model' => $this->model,
                    'max_tokens' => 10,
                    'messages' => [['role' => 'user', 'content' => 'Test']]
                ]),
                CURLOPT_TIMEOUT => 10
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                return ['success' => true, 'message' => "Connexion Claude OK - Modèle {$this->model}"];
            } else {
                $data = json_decode($response, true);
                return ['success' => false, 'error' => $data['error']['message'] ?? "Erreur HTTP {$httpCode}"];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Extraire le message de la réponse (format unifié)
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
     */
    public function hasToolCalls(array $response): bool
    {
        $message = $this->extractMessage($response);
        return !empty($message['tool_calls']);
    }

    /**
     * Obtenir les tool calls de la réponse
     */
    public function getToolCalls(array $response): array
    {
        $message = $this->extractMessage($response);
        return $message['tool_calls'] ?? [];
    }
}
