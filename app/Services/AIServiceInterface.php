<?php
/**
 * Interface AIServiceInterface
 *
 * Interface commune pour tous les fournisseurs d'IA
 * Permet de switcher entre OpenAI, Claude, Ollama, etc.
 *
 * @created  2025/12/11
 * @modified 2025/12/11
 */

namespace App\Services;

interface AIServiceInterface
{
    /**
     * Envoyer une requête de chat à l'IA
     *
     * @param array $messages Messages de la conversation [['role' => 'user', 'content' => '...']]
     * @param string $systemPrompt Prompt système
     * @param array $tools Outils disponibles (function calling)
     * @return array Réponse de l'IA
     */
    public function chat(array $messages, string $systemPrompt, array $tools = []): array;

    /**
     * Obtenir le nom du provider
     *
     * @return string
     */
    public function getProviderName(): string;

    /**
     * Obtenir le modèle utilisé
     *
     * @return string
     */
    public function getModel(): string;

    /**
     * Tester la connexion
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function testConnection(): array;
}
