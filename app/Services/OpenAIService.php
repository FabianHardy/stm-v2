<?php
/**
 * OpenAIService.php
 *
 * Service pour communiquer avec l'API OpenAI
 * Implémente AIServiceInterface pour compatibilité multi-provider
 *
 * @created  2025/12/09
 * @modified 2025/12/11 - Implémentation interface + config DB
 * @package  STM Agent
 */

namespace App\Services;

class OpenAIService implements AIServiceInterface
{
    private string $apiKey;
    private string $apiUrl = 'https://api.openai.com/v1/chat/completions';
    private string $model;
    private float $temperature;

    /**
     * Constructeur
     *
     * @param string|null $apiKey Clé API (si null, lit depuis config ou .env)
     * @param string $model Modèle à utiliser
     * @param float $temperature Température (créativité)
     */
    public function __construct(?string $apiKey = null, string $model = 'gpt-4o', float $temperature = 0.7)
    {
        $this->apiKey = $apiKey ?? $this->getApiKeyFromEnv();
        $this->model = $model;
        $this->temperature = $temperature;

        if (empty($this->apiKey)) {
            throw new \Exception('Clé API OpenAI non configurée');
        }
    }

    /**
     * Récupérer la clé API depuis l'environnement
     */
    private function getApiKeyFromEnv(): string
    {
        // Depuis $_ENV
        if (!empty($_ENV['OPENAI_API_KEY'])) {
            return $_ENV['OPENAI_API_KEY'];
        }

        // Depuis getenv()
        $key = getenv('OPENAI_API_KEY');
        if (!empty($key)) {
            return $key;
        }

        // Lecture directe du fichier .env
        $envFile = dirname(__DIR__, 2) . '/.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, 'OPENAI_API_KEY=') === 0) {
                    return trim(substr($line, strlen('OPENAI_API_KEY=')), '"\'');
                }
            }
        }

        return '';
    }

    /**
     * @inheritDoc
     */
    public function chat(array $messages, string $systemPrompt, array $tools = []): array
    {
        $fullMessages = [];
        
        if (!empty($systemPrompt)) {
            $fullMessages[] = [
                'role' => 'system',
                'content' => $systemPrompt
            ];
        }

        $fullMessages = array_merge($fullMessages, $messages);

        $payload = [
            'model' => $this->model,
            'messages' => $fullMessages,
            'temperature' => $this->temperature,
            'max_tokens' => 2000
        ];

        if (!empty($tools)) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }

        return $this->makeRequest($payload);
    }

    /**
     * Continuer une conversation après un tool call
     */
    public function continueWithToolResult(array $messages, string $systemPrompt, array $tools = []): array
    {
        return $this->chat($messages, $systemPrompt, $tools);
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
            throw new \Exception("Erreur de connexion OpenAI: " . $error);
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200) {
            $errorMsg = $data['error']['message'] ?? 'Erreur inconnue';
            throw new \Exception("Erreur OpenAI: " . $errorMsg);
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function getProviderName(): string
    {
        return 'OpenAI';
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
            $ch = curl_init('https://api.openai.com/v1/models/' . $this->model);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $this->apiKey],
                CURLOPT_TIMEOUT => 10
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                return ['success' => true, 'message' => "Connexion OpenAI OK - Modèle {$this->model}"];
            } else {
                return ['success' => false, 'error' => "Erreur HTTP {$httpCode}"];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Extraire le message de la réponse
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
