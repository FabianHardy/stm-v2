<?php
/**
 * AIServiceFactory.php
 *
 * Factory pour créer le bon service IA selon la configuration
 * Lit la config depuis AgentConfig et instancie le service approprié
 *
 * @created  2025/12/11
 * @package  STM Agent
 */

namespace App\Services;

use App\Models\AgentConfig;

class AIServiceFactory
{
    /**
     * Créer une instance du service IA configuré
     *
     * @return AIServiceInterface
     * @throws \Exception Si le provider n'est pas supporté
     */
    public static function create(): AIServiceInterface
    {
        try {
            $config = AgentConfig::getInstance();
            $provider = $config->getAIProvider();
            $model = $config->getAIModel();
            $apiKey = $config->getAIApiKey();
            $apiUrl = $config->getAIApiUrl();
            $temperature = $config->getAITemperature();

            return self::createFromParams($provider, $model, $apiKey, $apiUrl, $temperature);

        } catch (\Exception $e) {
            // Fallback : essayer OpenAI depuis .env
            error_log("AIServiceFactory: Config DB non disponible, fallback .env - " . $e->getMessage());
            return new OpenAIService(null, 'gpt-4o', 0.7);
        }
    }

    /**
     * Créer une instance avec des paramètres explicites
     *
     * @param string $provider Provider (openai, claude, ollama)
     * @param string $model Modèle à utiliser
     * @param string $apiKey Clé API
     * @param string $apiUrl URL API (pour Ollama)
     * @param float $temperature Température
     * @return AIServiceInterface
     */
    public static function createFromParams(
        string $provider,
        string $model,
        string $apiKey,
        string $apiUrl = '',
        float $temperature = 0.7
    ): AIServiceInterface {
        
        switch ($provider) {
            case 'openai':
                return new OpenAIService($apiKey ?: null, $model, $temperature);

            case 'claude':
                if (empty($apiKey)) {
                    throw new \Exception('Clé API Claude requise');
                }
                return new ClaudeService($apiKey, $model, $temperature);

            case 'ollama':
                return new OllamaService(
                    $apiUrl ?: 'http://localhost:11434',
                    $model,
                    $temperature
                );

            default:
                throw new \Exception("Provider IA non supporté: {$provider}");
        }
    }

    /**
     * Obtenir la liste des providers disponibles
     *
     * @return array
     */
    public static function getAvailableProviders(): array
    {
        return [
            'openai' => [
                'name' => 'OpenAI',
                'models' => ['gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo', 'gpt-3.5-turbo'],
                'requires_key' => true,
                'requires_url' => false
            ],
            'claude' => [
                'name' => 'Anthropic Claude',
                'models' => ['claude-3-5-sonnet-20241022', 'claude-3-opus-20240229', 'claude-3-haiku-20240307'],
                'requires_key' => true,
                'requires_url' => false
            ],
            'ollama' => [
                'name' => 'Ollama (Local)',
                'models' => ['llama3', 'llama3.1', 'mistral', 'codellama', 'mixtral'],
                'requires_key' => false,
                'requires_url' => true
            ]
        ];
    }

    /**
     * Tester un provider avec des paramètres donnés
     *
     * @param string $provider
     * @param string $model
     * @param string $apiKey
     * @param string $apiUrl
     * @return array ['success' => bool, 'message' => string]
     */
    public static function testProvider(
        string $provider,
        string $model,
        string $apiKey,
        string $apiUrl = ''
    ): array {
        try {
            $service = self::createFromParams($provider, $model, $apiKey, $apiUrl);
            return $service->testConnection();
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
