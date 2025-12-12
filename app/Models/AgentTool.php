<?php
/**
 * Modèle AgentTool
 *
 * Gestion des tools de l'Agent STM
 * Permet de créer, modifier, activer/désactiver les tools
 *
 * @created  2025/12/12
 */

namespace App\Models;

use Core\Database;

class AgentTool
{
    private static ?AgentTool $instance = null;
    private Database $db;

    private function __construct()
    {
        $this->db = Database::getInstance();
    }

    public static function getInstance(): AgentTool
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Récupérer tous les tools
     */
    public function getAll(bool $activeOnly = false): array
    {
        try {
            $sql = "SELECT * FROM agent_tools";
            if ($activeOnly) {
                $sql .= " WHERE is_active = 1";
            }
            $sql .= " ORDER BY is_system DESC, display_name ASC";

            return $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("AgentTool::getAll error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer un tool par son ID
     */
    public function getById(int $id): ?array
    {
        try {
            $sql = "SELECT * FROM agent_tools WHERE id = :id";
            $result = $this->db->query($sql, [':id' => $id])->fetch(\PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            error_log("AgentTool::getById error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer un tool par son nom
     */
    public function getByName(string $name): ?array
    {
        try {
            $sql = "SELECT * FROM agent_tools WHERE name = :name";
            $result = $this->db->query($sql, [':name' => $name])->fetch(\PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            error_log("AgentTool::getByName error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Activer/Désactiver un tool
     */
    public function toggle(int $id): bool
    {
        try {
            $sql = "UPDATE agent_tools SET is_active = NOT is_active WHERE id = :id";
            $this->db->query($sql, [':id' => $id]);
            return true;
        } catch (\PDOException $e) {
            error_log("AgentTool::toggle error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Activer un tool
     */
    public function activate(int $id): bool
    {
        try {
            $sql = "UPDATE agent_tools SET is_active = 1 WHERE id = :id";
            $this->db->query($sql, [':id' => $id]);
            return true;
        } catch (\PDOException $e) {
            error_log("AgentTool::activate error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Désactiver un tool
     */
    public function deactivate(int $id): bool
    {
        try {
            $sql = "UPDATE agent_tools SET is_active = 0 WHERE id = :id";
            $this->db->query($sql, [':id' => $id]);
            return true;
        } catch (\PDOException $e) {
            error_log("AgentTool::deactivate error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Créer un nouveau tool
     */
    public function create(array $data): ?int
    {
        try {
            $sql = "INSERT INTO agent_tools (name, display_name, description, parameters, php_code, is_system, is_active, created_by)
                    VALUES (:name, :display_name, :description, :parameters, :php_code, :is_system, :is_active, :created_by)";

            $this->db->query($sql, [
                ':name' => $data['name'],
                ':display_name' => $data['display_name'],
                ':description' => $data['description'],
                ':parameters' => $data['parameters'] ?? '{}',
                ':php_code' => $data['php_code'] ?? null,
                ':is_system' => $data['is_system'] ?? 0,
                ':is_active' => $data['is_active'] ?? 1,
                ':created_by' => $data['created_by'] ?? null
            ]);

            return (int) $this->db->lastInsertId();
        } catch (\PDOException $e) {
            error_log("AgentTool::create error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Mettre à jour un tool
     */
    public function update(int $id, array $data): bool
    {
        try {
            $fields = [];
            $params = [':id' => $id];

            $allowedFields = ['display_name', 'description', 'parameters', 'php_code', 'is_active'];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = :$field";
                    $params[":$field"] = $data[$field];
                }
            }

            if (empty($fields)) {
                return false;
            }

            $sql = "UPDATE agent_tools SET " . implode(', ', $fields) . " WHERE id = :id AND is_system = 0";
            $this->db->query($sql, $params);

            return true;
        } catch (\PDOException $e) {
            error_log("AgentTool::update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer un tool (seulement les non-système)
     */
    public function delete(int $id): bool
    {
        try {
            $sql = "DELETE FROM agent_tools WHERE id = :id AND is_system = 0";
            $this->db->query($sql, [':id' => $id]);
            return true;
        } catch (\PDOException $e) {
            error_log("AgentTool::delete error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Incrémenter le compteur d'utilisation
     */
    public function incrementUsage(string $name): bool
    {
        try {
            $sql = "UPDATE agent_tools SET usage_count = usage_count + 1 WHERE name = :name";
            $this->db->query($sql, [':name' => $name]);
            return true;
        } catch (\PDOException $e) {
            error_log("AgentTool::incrementUsage error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer les tools actifs formatés pour OpenAI
     */
    public function getToolsForOpenAI(): array
    {
        $tools = $this->getAll(true);
        $formatted = [];

        foreach ($tools as $tool) {
            $parameters = json_decode($tool['parameters'] ?? '{}', true) ?: [
                'type' => 'object',
                'properties' => new \stdClass()
            ];

            $formatted[] = [
                'type' => 'function',
                'function' => [
                    'name' => $tool['name'],
                    'description' => $tool['description'],
                    'parameters' => $parameters
                ]
            ];
        }

        return $formatted;
    }

    /**
     * Récupérer les statistiques des tools
     */
    public function getStats(): array
    {
        try {
            $sql = "SELECT
                        COUNT(*) as total,
                        SUM(is_active) as active,
                        SUM(is_system) as system_tools,
                        SUM(usage_count) as total_usage
                    FROM agent_tools";

            return $this->db->query($sql)->fetch(\PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("AgentTool::getStats error: " . $e->getMessage());
            return [];
        }
    }
}