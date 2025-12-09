<?php
/**
 * AgentTools.php
 *
 * Définition et exécution des tools disponibles pour l'agent
 * Inclut le tool Text-to-SQL pour requêtes dynamiques
 *
 * @created  2025/12/09
 * @modified 2025/12/09 - Ajout Text-to-SQL
 * @package  STM Agent
 */

namespace App\Agent;

use Core\Database;

class AgentTools
{
    private Database $db;

    /**
     * Schéma de la base de données pour le Text-to-SQL
     */
    private string $dbSchema = <<<'SCHEMA'
## BASE DE DONNÉES STM v2

### Table: campaigns
Colonnes: id, uuid, name, slug, title_fr, title_nl, description_fr, description_nl, country (BE/LU), status (draft/scheduled/active/ended/cancelled), start_date, end_date, customer_access_mode (automatic/manual/protected), is_active, created_at
Clé primaire: id

### Table: customers
Colonnes: id, customer_number, email, company_name, country (BE/LU), language (fr/nl), rep_name, rep_id, customer_type, is_active, total_orders, last_order_date, created_at
Clé primaire: id
Note: customer_number + country est UNIQUE (même numéro peut exister en BE et LU)

### Table: orders
Colonnes: id, uuid, order_number, campaign_id (FK campaigns), customer_id (FK customers), customer_email, total_items, total_quantity, total_products, status (pending/confirmed/processing/completed/cancelled), notes, created_at
Clé primaire: id

### Table: order_lines
Colonnes: id, order_id (FK orders), product_id (FK products), product_code, product_name, quantity, created_at
Clé primaire: id

### Table: products
Colonnes: id, product_code, name_fr, name_nl, description_fr, description_nl, category_id (FK categories), campaign_id (FK campaigns), max_per_customer, max_total, total_ordered, unit_size, package_size, brand, image_path, display_order, is_active, created_at
Clé primaire: id

### Table: categories
Colonnes: id, code, name_fr, name_nl, color, icon_path, display_order, is_active
Clé primaire: id

### Table: campaign_customers
Colonnes: id, campaign_id (FK campaigns), customer_number, country (BE/LU), is_authorized, has_ordered, created_at
Note: Utilisée uniquement en mode customer_access_mode='manual'

### Table: users (admins)
Colonnes: id, username, email, password_hash, role (admin/manager/user), is_active, last_login, created_at

### RELATIONS IMPORTANTES:
- orders.customer_id → customers.id
- orders.campaign_id → campaigns.id
- order_lines.order_id → orders.id
- order_lines.product_id → products.id
- products.campaign_id → campaigns.id
- products.category_id → categories.id

### EXEMPLES DE REQUÊTES UTILES:
-- Stats d'un représentant sur une campagne:
SELECT c.rep_name, COUNT(DISTINCT o.id) as commandes, SUM(o.total_quantity) as promos
FROM orders o
JOIN customers c ON o.customer_id = c.id
WHERE o.campaign_id = X AND c.rep_name LIKE '%NomRep%'
GROUP BY c.rep_id

-- Clients ayant commandé plus de X promos:
SELECT c.customer_number, c.company_name, SUM(o.total_quantity) as total
FROM orders o
JOIN customers c ON o.customer_id = c.id
WHERE o.campaign_id = X
GROUP BY c.id
HAVING total > X

-- Produits jamais commandés:
SELECT p.product_code, p.name_fr
FROM products p
LEFT JOIN order_lines ol ON p.id = ol.product_id
WHERE p.campaign_id = X AND ol.id IS NULL
SCHEMA;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Obtenir le schéma DB (pour le system prompt)
     */
    public function getDbSchema(): string
    {
        return $this->dbSchema;
    }

    /**
     * Obtenir la définition des tools au format OpenAI
     */
    public function getToolsDefinition(): array
    {
        return [
            // Tool principal : Text-to-SQL
            [
                'type' => 'function',
                'function' => [
                    'name' => 'query_database',
                    'description' => 'Exécute une requête SQL SELECT sur la base de données STM. Utilise ce tool pour répondre à TOUTES les questions sur les données (stats, clients, représentants, produits, commandes, etc.). Tu dois générer la requête SQL toi-même basée sur le schéma de la base de données.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'sql' => [
                                'type' => 'string',
                                'description' => 'Requête SQL SELECT à exécuter. UNIQUEMENT SELECT (pas de INSERT, UPDATE, DELETE). Limite toujours à 100 résultats max avec LIMIT.'
                            ],
                            'explanation' => [
                                'type' => 'string',
                                'description' => 'Explication courte de ce que fait la requête'
                            ]
                        ],
                        'required' => ['sql', 'explanation']
                    ]
                ]
            ],
            // Tool simplifié : Liste campagnes
            [
                'type' => 'function',
                'function' => [
                    'name' => 'list_campaigns',
                    'description' => 'Liste rapide des campagnes. Pour des questions simples sur les campagnes disponibles.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'country' => [
                                'type' => 'string',
                                'description' => 'Filtrer par pays (BE ou LU)',
                                'enum' => ['BE', 'LU']
                            ],
                            'status' => [
                                'type' => 'string',
                                'description' => 'Filtrer par statut',
                                'enum' => ['draft', 'scheduled', 'active', 'ended', 'cancelled']
                            ]
                        ],
                        'required' => []
                    ]
                ]
            ],
            // Tool : Rechercher un représentant
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_representative',
                    'description' => 'Rechercher un représentant par nom pour obtenir son rep_id et ses infos',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => [
                                'type' => 'string',
                                'description' => 'Nom ou partie du nom du représentant à rechercher'
                            ]
                        ],
                        'required' => ['name']
                    ]
                ]
            ]
        ];
    }

    /**
     * Exécuter un tool avec ses arguments
     */
    public function executeTool(string $toolName, array $arguments): array
    {
        try {
            switch ($toolName) {
                case 'query_database':
                    return $this->queryDatabase($arguments);

                case 'list_campaigns':
                    return $this->listCampaigns($arguments);

                case 'search_representative':
                    return $this->searchRepresentative($arguments);

                default:
                    return ['error' => "Tool inconnu: {$toolName}"];
            }
        } catch (\Exception $e) {
            error_log("AgentTools error ({$toolName}): " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Tool Text-to-SQL : Exécuter une requête SQL générée par l'agent
     */
    private function queryDatabase(array $args): array
    {
        $sql = trim($args['sql'] ?? '');
        $explanation = $args['explanation'] ?? '';

        if (empty($sql)) {
            return ['error' => 'Requête SQL vide'];
        }

        // ========================================
        // SÉCURITÉ : Validation de la requête
        // ========================================

        // 1. Convertir en minuscules pour vérification
        $sqlLower = strtolower($sql);

        // 2. Vérifier que c'est un SELECT
        if (!preg_match('/^\s*select\s/i', $sql)) {
            return ['error' => 'Seules les requêtes SELECT sont autorisées'];
        }

        // 3. Interdire les mots-clés dangereux
        $forbidden = ['insert', 'update', 'delete', 'drop', 'truncate', 'alter', 'create', 'grant', 'revoke', 'exec', 'execute', '--'];
        foreach ($forbidden as $word) {
            if ($word === '--') {
                if (strpos($sql, $word) !== false) {
                    return ['error' => "Caractère interdit détecté: {$word}"];
                }
            } else {
                if (preg_match('/\b' . $word . '\b/i', $sql)) {
                    return ['error' => "Mot-clé interdit: {$word}"];
                }
            }
        }

        // 4. Ajouter LIMIT si absent
        if (!preg_match('/\blimit\s+\d+/i', $sql)) {
            $sql = rtrim($sql, ' ;') . ' LIMIT 100';
        }

        // 5. S'assurer que LIMIT ne dépasse pas 100
        if (preg_match('/\blimit\s+(\d+)/i', $sql, $matches)) {
            $limit = (int) $matches[1];
            if ($limit > 100) {
                $sql = preg_replace('/\blimit\s+\d+/i', 'LIMIT 100', $sql);
            }
        }

        // ========================================
        // Exécution de la requête
        // ========================================

        try {
            $startTime = microtime(true);
            $results = $this->db->query($sql);
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            // Formater les résultats
            $count = count($results);

            // Si trop de colonnes, limiter l'affichage
            if ($count > 0 && count($results[0]) > 10) {
                $results = array_map(function($row) {
                    return array_slice($row, 0, 10);
                }, $results);
            }

            return [
                'success' => true,
                'explanation' => $explanation,
                'query' => $sql,
                'results' => $results,
                'count' => $count,
                'duration_ms' => $duration
            ];

        } catch (\PDOException $e) {
            error_log("Agent SQL Error: " . $e->getMessage() . " | Query: " . $sql);
            return [
                'error' => 'Erreur SQL: ' . $e->getMessage(),
                'query' => $sql
            ];
        }
    }

    /**
     * Liste des campagnes (simplifié)
     */
    private function listCampaigns(array $args): array
    {
        $sql = "SELECT id, name, country, status, start_date, end_date FROM campaigns WHERE 1=1";
        $params = [];

        if (!empty($args['country'])) {
            $sql .= " AND country = :country";
            $params[':country'] = $args['country'];
        }

        if (!empty($args['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $args['status'];
        }

        $sql .= " ORDER BY start_date DESC LIMIT 20";

        $campaigns = $this->db->query($sql, $params);

        $statusLabels = [
            'draft' => 'Brouillon',
            'scheduled' => 'Programmée',
            'active' => 'En cours',
            'ended' => 'Terminée',
            'cancelled' => 'Annulée'
        ];

        $result = [];
        foreach ($campaigns as $c) {
            $result[] = [
                'id' => $c['id'],
                'nom' => $c['name'],
                'pays' => $c['country'],
                'statut' => $statusLabels[$c['status']] ?? $c['status'],
                'debut' => date('d/m/Y', strtotime($c['start_date'])),
                'fin' => date('d/m/Y', strtotime($c['end_date']))
            ];
        }

        return [
            'campagnes' => $result,
            'total' => count($result)
        ];
    }

    /**
     * Rechercher un représentant par nom
     */
    private function searchRepresentative(array $args): array
    {
        $name = $args['name'] ?? '';

        if (empty($name)) {
            return ['error' => 'Nom du représentant requis'];
        }

        // Chercher dans la table customers (rep_name)
        $results = $this->db->query(
            "SELECT DISTINCT rep_id, rep_name, country, COUNT(*) as nb_clients
             FROM customers
             WHERE rep_name LIKE :name AND rep_name IS NOT NULL
             GROUP BY rep_id, rep_name, country
             ORDER BY nb_clients DESC
             LIMIT 10",
            [':name' => '%' . $name . '%']
        );

        if (empty($results)) {
            return [
                'found' => false,
                'message' => "Aucun représentant trouvé avec le nom '{$name}'"
            ];
        }

        return [
            'found' => true,
            'representants' => $results,
            'count' => count($results)
        ];
    }
}