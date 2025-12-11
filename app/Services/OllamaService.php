<?php
/**
 * OllamaService.php
 *
 * Service pour communiquer avec Ollama (LLM local)
 * Implémente AIServiceInterface pour compatibilité multi-provider
 *
 * NOTE: Ollama ne supporte pas nativement les function calls comme OpenAI.
 * Ce service simule le comportement via prompting si nécessaire.
 *
 * @created  2025/12/11
 * @package  STM Agent
 */

namespace App\Services;

class OllamaService implements AIServiceInterface
{
    private string $apiUrl;
    private string $model;
    private float $temperature;

    /**
     * Constructeur
     *
     * @param string $apiUrl URL de l'API Ollama (ex: http://localhost:11434)
     * @param string $model Modèle à utiliser
     * @param float $temperature Température (créativité)
     */
    public function __construct(string $apiUrl = 'http://localhost:11434', string $model = 'llama3', float $temperature = 0.7)
    {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->model = $model;
        $this->temperature = $temperature;
    }

    /**
     * @inheritDoc
     * 
     * NOTE: Ollama ne supporte pas les tools natifs.
     * Les tools sont inclus dans le prompt système.
     */
    public function chat(array $messages, string $systemPrompt, array $tools = []): array
    {
        // Si des tools sont fournis, les inclure dans le prompt système
        $enhancedSystemPrompt = $systemPrompt;
        
        if (!empty($tools)) {
            $enhancedSystemPrompt .= $this->buildToolsPrompt($tools);
        }

        // Construire le prompt complet
        $fullPrompt = $this->buildPrompt($messages, $enhancedSystemPrompt);

        $payload = [
            'model' => $this->model,
            'prompt' => $fullPrompt,
            'stream' => false,
            'options' => [
                'temperature' => $this->temperature
            ]
        ];

        $response = $this->makeRequest('/api/generate', $payload);

        // Convertir la réponse au format OpenAI-like
        return $this->convertResponse($response, $tools);
    }

    /**
     * Construire le prompt complet à partir des messages
     */
    private function buildPrompt(array $messages, string $systemPrompt): string
    {
        $prompt = '';

        if (!empty($systemPrompt)) {
            $prompt .= "System: {$systemPrompt}\n\n";
        }

        foreach ($messages as $msg) {
            $role = ucfirst($msg['role']);
            $content = $msg['content'];
            
            if ($msg['role'] === 'tool') {
                $prompt .= "Tool Result: {$content}\n\n";
            } else {
                $prompt .= "{$role}: {$content}\n\n";
            }
        }

        $prompt .= "Assistant: ";

        return $prompt;
    }

    /**
     * Construire le prompt des tools
     * Comme Ollama ne supporte pas les function calls natifs,
     * on décrit les tools dans le prompt
     */
    private function buildToolsPrompt(array $tools): string
    {
        $prompt = "\n\n## OUTILS DISPONIBLES\n";
        $prompt .= "Tu peux utiliser ces outils en répondant au format JSON spécifié.\n";
        $prompt .= "Pour appeler un outil, réponds UNIQUEMENT avec un JSON valide dans ce format:\n";
        $prompt .= '{"tool": "nom_outil", "arguments": {...}}' . "\n\n";

        foreach ($tools as $tool) {
            $func = $tool['function'];
            $prompt .= "### {$func['name']}\n";
            $prompt .= "Description: {$func['description']}\n";
            $prompt .= "Paramètres: " . json_encode($func['parameters'] ?? [], JSON_PRETTY_PRINT) . "\n\n";
        }

        return $prompt;
    }

    /**
     * Convertir la réponse Ollama au format OpenAI-like
     */
    private function convertResponse(array $response, array $tools = []): array
    {
        $content = $response['response'] ?? '';

        // Essayer de détecter un appel d'outil dans la réponse
        $toolCalls = null;
        
        if (!empty($tools) && preg_match('/\{[^}]*"tool"\s*:\s*"([^"]+)"[^}]*\}/s', $content, $matches)) {
            try {
                $toolJson = $matches[0];
                $toolData = json_decode($toolJson, true);
                
                if (isset($toolData['tool']) && isset($toolData['arguments'])) {
                    $toolCalls = [
                        [
                            'id' => 'call_' . uniqid(),
                            'type' => 'function',
                            'function' => [
                                'name' => $toolData['tool'],
                                'arguments' => json_encode($toolData['arguments'])
                            ]
                        ]
                    ];
                    // Retirer le JSON de la réponse
                    $content = trim(str_replace($toolJson, '', $content));
                }
            } catch (\Exception $e) {
                // Pas un appel d'outil valide, garder le contenu tel quel
            }
        }

        $message = ['content' => $content];
        if ($toolCalls) {
            $message['tool_calls'] = $toolCalls;
        }

        return [
            'choices' => [
                [
                    'message' => $message,
                    'finish_reason' => $toolCalls ? 'tool_calls' : 'stop'
                ]
            ],
            'usage' => [
                'total_tokens' => $response['eval_count'] ?? 0
            ]
        ];
    }

    /**
     * Effectuer la requête HTTP
     */
    private function makeRequest(string $endpoint, array $payload): array
    {
        $url = $this->apiUrl . $endpoint;
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 120 // Ollama peut être lent
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception("Erreur de connexion Ollama: " . $error);
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200) {
            $errorMsg = $data['error'] ?? 'Erreur inconnue';
            throw new \Exception("Erreur Ollama: " . $errorMsg);
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function getProviderName(): string
    {
        return 'Ollama';
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
            $ch = curl_init($this->apiUrl . '/api/tags');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($httpCode === 200) {
                $data = json_decode($response, true);
                $models = array_column($data['models'] ?? [], 'name');
                
                // Nettoyer les noms (retirer :latest etc.)
                $models = array_map(function($m) {
                    return explode(':', $m)[0];
                }, $models);
                
                if (in_array($this->model, $models)) {
                    return ['success' => true, 'message' => "Connexion Ollama OK - Modèle {$this->model} disponible"];
                } else {
                    return [
                        'success' => false, 
                        'error' => "Modèle {$this->model} non trouvé. Disponibles: " . implode(', ', array_unique($models))
                    ];
                }
            } else {
                return ['success' => false, 'error' => $error ?: "Impossible de contacter Ollama"];
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
