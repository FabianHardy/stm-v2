<?php
/**
 * Modèle AgentConfig
 *
 * Gestion de la configuration de l'Agent STM
 * Stocke les instructions personnalisées du prompt
 * et la configuration du fournisseur IA
 *
 * @created  2025/12/11
 * @modified 2025/12/11 - Ajout config fournisseur IA
 */

namespace App\Models;

use Core\Database;

class AgentConfig
{
    private static ?AgentConfig $instance = null;
    private Database $db;
    private array $cache = [];

    /**
     * Constructeur privé (singleton)
     */
    private function __construct()
    {
        $this->db = Database::getInstance();
        $this->loadAll();
    }

    /**
     * Obtenir l'instance unique
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Charger toutes les configurations en cache
     */
    private function loadAll(): void
    {
        try {
            $results = $this->db->query(
                "SELECT `key`, `value`, `is_active`, `is_sensitive` FROM agent_config"
            );

            foreach ($results as $row) {
                $this->cache[$row['key']] = [
                    'value' => $row['value'],
                    'is_active' => (bool)$row['is_active'],
                    'is_sensitive' => (bool)($row['is_sensitive'] ?? false)
                ];
            }
        } catch (\PDOException $e) {
            error_log("AgentConfig::loadAll error: " . $e->getMessage());
            $this->cache = [];
        }
    }

    /**
     * Obtenir une valeur de configuration
     *
     * @param string $key Clé de configuration
     * @param string $default Valeur par défaut
     * @return string
     */
    public function get(string $key, string $default = ''): string
    {
        if (!isset($this->cache[$key])) {
            return $default;
        }

        // Retourner vide si désactivé
        if (!$this->cache[$key]['is_active']) {
            return '';
        }

        return $this->cache[$key]['value'] ?? $default;
    }

    /**
     * Vérifier si une config est active
     *
     * @param string $key Clé de configuration
     * @return bool
     */
    public function isActive(string $key): bool
    {
        return isset($this->cache[$key]) && $this->cache[$key]['is_active'];
    }

    /**
     * Vérifier si une config est sensible
     *
     * @param string $key Clé de configuration
     * @return bool
     */
    public function isSensitive(string $key): bool
    {
        return isset($this->cache[$key]) && $this->cache[$key]['is_sensitive'];
    }

    /**
     * Mettre à jour une configuration
     *
     * @param string $key Clé
     * @param string $value Nouvelle valeur
     * @param bool $isActive Actif ou non
     * @return bool
     */
    public function set(string $key, string $value, bool $isActive = true): bool
    {
        try {
            $this->db->query(
                "INSERT INTO agent_config (`key`, `value`, `is_active`)
                 VALUES (:key, :value, :active)
                 ON DUPLICATE KEY UPDATE
                 `value` = VALUES(`value`),
                 `is_active` = VALUES(`is_active`)",
                [
                    ':key' => $key,
                    ':value' => $value,
                    ':active' => $isActive ? 1 : 0
                ]
            );

            // Mettre à jour le cache
            $this->cache[$key] = [
                'value' => $value,
                'is_active' => $isActive,
                'is_sensitive' => $this->cache[$key]['is_sensitive'] ?? false
            ];

            return true;
        } catch (\PDOException $e) {
            error_log("AgentConfig::set error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mettre à jour plusieurs configurations d'un coup
     *
     * @param array $configs ['key' => ['value' => '...', 'is_active' => true], ...]
     * @return bool
     */
    public function setMultiple(array $configs): bool
    {
        try {
            foreach ($configs as $key => $data) {
                $value = $data['value'] ?? '';
                $isActive = $data['is_active'] ?? true;
                $this->set($key, $value, $isActive);
            }
            return true;
        } catch (\Exception $e) {
            error_log("AgentConfig::setMultiple error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtenir toutes les configurations
     *
     * @param bool $includeSensitive Inclure les configs sensibles
     * @return array
     */
    public function getAll(bool $includeSensitive = false): array
    {
        try {
            $sql = "SELECT * FROM agent_config";
            if (!$includeSensitive) {
                $sql .= " WHERE is_sensitive = 0";
            }
            $sql .= " ORDER BY id";

            return $this->db->query($sql);
        } catch (\PDOException $e) {
            error_log("AgentConfig::getAll error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Générer le prompt personnalisé complet
     *
     * Combine toutes les sections actives en un seul prompt
     *
     * @return string
     */
    public function buildCustomPrompt(): string
    {
        $sections = [];

        // Instructions générales
        $general = $this->get('general_instructions');
        if (!empty($general)) {
            $sections[] = "## INSTRUCTIONS PERSONNALISÉES\n{$general}";
        }

        // Vocabulaire métier
        $vocab = $this->get('business_vocabulary');
        if (!empty($vocab)) {
            $sections[] = "## VOCABULAIRE MÉTIER\n{$vocab}";
        }

        // Règles de réponse
        $rules = $this->get('response_rules');
        if (!empty($rules)) {
            $sections[] = "## RÈGLES DE RÉPONSE\n{$rules}";
        }

        // Exemples Q/R
        $examples = $this->get('qa_examples');
        if (!empty($examples)) {
            $sections[] = "## EXEMPLES DE QUESTIONS/RÉPONSES\n{$examples}";
        }

        if (empty($sections)) {
            return '';
        }

        return "\n\n" . implode("\n\n", $sections);
    }

    // =========================================
    // CONFIGURATION IA
    // =========================================

    /**
     * Obtenir le fournisseur IA configuré
     *
     * @return string (openai, claude, ollama)
     */
    public function getAIProvider(): string
    {
        return $this->get('ai_provider', 'openai');
    }

    /**
     * Obtenir le modèle IA configuré
     *
     * @return string
     */
    public function getAIModel(): string
    {
        return $this->get('ai_model', 'gpt-4o');
    }

    /**
     * Obtenir la clé API
     *
     * @return string
     */
    public function getAIApiKey(): string
    {
        return $this->get('ai_api_key', '');
    }

    /**
     * Obtenir l'URL API personnalisée (pour Ollama)
     *
     * @return string
     */
    public function getAIApiUrl(): string
    {
        return $this->get('ai_api_url', '');
    }

    /**
     * Obtenir la température
     *
     * @return float
     */
    public function getAITemperature(): float
    {
        return (float)$this->get('ai_temperature', '0.7');
    }

    /**
     * Obtenir toute la configuration IA
     *
     * @return array
     */
    public function getAIConfig(): array
    {
        return [
            'provider' => $this->getAIProvider(),
            'model' => $this->getAIModel(),
            'api_key' => $this->getAIApiKey(),
            'api_url' => $this->getAIApiUrl(),
            'temperature' => $this->getAITemperature()
        ];
    }

    /**
     * Recharger le cache depuis la DB
     */
    public function refresh(): void
    {
        $this->cache = [];
        $this->loadAll();
    }
}