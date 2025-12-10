<?php
/**
 * AgentTools.php
 *
 * Définition et exécution des tools disponibles pour l'agent
 * Inclut le tool Text-to-SQL pour requêtes dynamiques
 * Accès aux bases locale ET externe
 *
 * @created  2025/12/09
 * @modified 2025/12/09 - Ajout Text-to-SQL + base externe
 * @package  STM Agent
 */

namespace App\Agent;

use Core\Database;
use Core\ExternalDatabase;

class AgentTools
{
    private Database $db;

    /**
     * Schéma de la base de données LOCALE pour le Text-to-SQL
     */
    private string $dbSchema = <<<'SCHEMA'
## BASE DE DONNÉES LOCALE (trendyblog_stm_v2)

### Table: campaigns
Colonnes: id, uuid, name, slug, title_fr, title_nl, description_fr, description_nl, country (BE/LU), status (draft/scheduled/active/ended/cancelled), start_date, end_date, customer_access_mode (automatic/manual/protected), is_active, created_at
Clé primaire: id

### Table: customers (remplie lors des commandes)
Colonnes: id, customer_number, email, company_name, country (BE/LU), language (fr/nl), rep_name, rep_id, customer_type, is_active, total_orders, last_order_date, created_at
Note: Se remplit à la volée lors des commandes. Pour les infos complètes des clients/reps, utiliser la BASE EXTERNE.

### Table: orders
Colonnes: id, uuid, order_number, campaign_id (FK campaigns), customer_id (FK customers), customer_email, total_items (nombre total d'articles), total_products (nombre de produits différents), status (pending/validated/cancelled), notes, created_at
Clé primaire: id
⚠️ IMPORTANT: Toujours filtrer par status = 'validated' pour les stats ! Les commandes pending ou cancelled ne comptent pas.
Note: Pour compter les promos vendues, utiliser SUM depuis order_lines.quantity

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

### RELATIONS:
- orders.customer_id → customers.id
- orders.campaign_id → campaigns.id
- order_lines.order_id → orders.id
- order_lines.product_id → products.id
- products.campaign_id → campaigns.id
SCHEMA;

    /**
     * Schéma de la base externe (trendyblog_sig)
     */
    private string $externalDbSchema = <<<'SCHEMA'
## BASE DE DONNÉES EXTERNE (trendyblog_sig) - Données Trendy Foods

### Table: BE_CLL (Clients Belgique - 26000+ clients)
Colonnes: IDE_CLL (PK), CLL_NCLIXX (numéro client), CLL_NOM (nom entreprise), CLL_PRENOM, CLL_ADRESSE1, CLL_ADRESSE2, CLL_CPOSTAL, CLL_LOCALITE, IDE_REP (ID représentant)

### Table: LU_CLL (Clients Luxembourg)
Structure identique à BE_CLL

### Table: BE_REP (Représentants Belgique)
Colonnes: IDE_REP (PK), REP_PRENOM, REP_NOM, REP_EMAIL, REP_CLU (cluster), REP_SIPAD

### Table: LU_REP (Représentants Luxembourg)
Structure identique à BE_REP

### EXEMPLE - Trouver un représentant par nom:
SELECT IDE_REP, REP_PRENOM, REP_NOM FROM BE_REP WHERE REP_NOM LIKE '%ZERAFI%' OR REP_PRENOM LIKE '%Tahir%'

### EXEMPLE - Clients d'un représentant:
SELECT c.CLL_NCLIXX, c.CLL_NOM, c.CLL_LOCALITE
FROM BE_CLL c
WHERE c.IDE_REP = (SELECT IDE_REP FROM BE_REP WHERE REP_NOM LIKE '%ZERAFI%')

### EXEMPLE - Stats complètes d'un rep sur une campagne (jointure locale + externe):
-- D'abord trouver l'IDE_REP dans BE_REP
-- Puis trouver les customer_number dans BE_CLL avec cet IDE_REP
-- Puis chercher les commandes dans la base locale avec ces customer_number
SCHEMA;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Obtenir le schéma DB complet (pour le system prompt)
     */
    public function getDbSchema(): string
    {
        return $this->dbSchema . "\n\n" . $this->externalDbSchema;
    }

    /**
     * Obtenir la définition des tools au format OpenAI
     */
    public function getToolsDefinition(): array
    {
        return [
            // Tool principal : Text-to-SQL BASE LOCALE
            [
                'type' => 'function',
                'function' => [
                    'name' => 'query_database',
                    'description' => 'Exécute une requête SQL SELECT sur la base de données LOCALE STM (campaigns, orders, products, etc.). Pour les infos des clients/représentants Trendy Foods, utilise query_external_database.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'sql' => [
                                'type' => 'string',
                                'description' => 'Requête SQL SELECT. UNIQUEMENT SELECT. Limite à 100 résultats avec LIMIT.'
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
            // Tool : Text-to-SQL BASE EXTERNE
            [
                'type' => 'function',
                'function' => [
                    'name' => 'query_external_database',
                    'description' => 'Exécute une requête SQL SELECT sur la base de données EXTERNE Trendy Foods (BE_CLL, LU_CLL, BE_REP, LU_REP). Utilise ce tool pour chercher des représentants, des clients par nom, ou des infos sur les commerciaux.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'sql' => [
                                'type' => 'string',
                                'description' => 'Requête SQL SELECT sur les tables BE_CLL, LU_CLL, BE_REP, LU_REP. UNIQUEMENT SELECT.'
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
            // Tool combiné : Stats rep sur campagne
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_rep_campaign_stats',
                    'description' => 'Obtenir les statistiques d\'un représentant sur une campagne. Combine les données externes (rep, clients) et locales (commandes).',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'rep_name' => [
                                'type' => 'string',
                                'description' => 'Nom ou partie du nom du représentant (ex: Tahir, ZERAFI)'
                            ],
                            'campaign_name' => [
                                'type' => 'string',
                                'description' => 'Nom ou partie du nom de la campagne (ex: Black Friday)'
                            ],
                            'country' => [
                                'type' => 'string',
                                'description' => 'Pays (BE ou LU). Défaut: BE',
                                'enum' => ['BE', 'LU']
                            ]
                        ],
                        'required' => ['rep_name', 'campaign_name']
                    ]
                ]
            ],
            // Tool simplifié : Liste campagnes
            [
                'type' => 'function',
                'function' => [
                    'name' => 'list_campaigns',
                    'description' => 'Liste rapide des campagnes disponibles.',
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

                case 'query_external_database':
                    return $this->queryExternalDatabase($arguments);

                case 'get_rep_campaign_stats':
                    return $this->getRepCampaignStats($arguments);

                case 'list_campaigns':
                    return $this->listCampaigns($arguments);

                default:
                    return ['error' => "Tool inconnu: {$toolName}"];
            }
        } catch (\Exception $e) {
            error_log("AgentTools error ({$toolName}): " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Tool Text-to-SQL : Base LOCALE
     */
    private function queryDatabase(array $args): array
    {
        $sql = trim($args['sql'] ?? '');
        $explanation = $args['explanation'] ?? '';

        if (empty($sql)) {
            return ['error' => 'Requête SQL vide'];
        }

        // Validation sécurité
        $validation = $this->validateSql($sql);
        if ($validation !== true) {
            return ['error' => $validation];
        }

        // Ajouter LIMIT si absent
        $sql = $this->ensureLimit($sql);

        try {
            $startTime = microtime(true);
            $results = $this->db->query($sql);
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success' => true,
                'database' => 'locale',
                'explanation' => $explanation,
                'query' => $sql,
                'results' => array_slice($results, 0, 100),
                'count' => count($results),
                'duration_ms' => $duration
            ];

        } catch (\PDOException $e) {
            error_log("Agent SQL Error (local): " . $e->getMessage() . " | Query: " . $sql);
            return ['error' => 'Erreur SQL: ' . $e->getMessage(), 'query' => $sql];
        }
    }

    /**
     * Tool Text-to-SQL : Base EXTERNE
     */
    private function queryExternalDatabase(array $args): array
    {
        $sql = trim($args['sql'] ?? '');
        $explanation = $args['explanation'] ?? '';

        if (empty($sql)) {
            return ['error' => 'Requête SQL vide'];
        }

        // Validation sécurité
        $validation = $this->validateSql($sql);
        if ($validation !== true) {
            return ['error' => $validation];
        }

        // Vérifier que les tables sont autorisées
        $allowedTables = ['BE_CLL', 'LU_CLL', 'BE_REP', 'LU_REP', 'BE_ART', 'BE_COLIS', 'BE_FOD'];
        $sqlUpper = strtoupper($sql);
        $hasAllowedTable = false;
        foreach ($allowedTables as $table) {
            if (strpos($sqlUpper, $table) !== false) {
                $hasAllowedTable = true;
                break;
            }
        }
        if (!$hasAllowedTable) {
            return ['error' => 'Tables autorisées: BE_CLL, LU_CLL, BE_REP, LU_REP, BE_ART'];
        }

        // Ajouter LIMIT si absent
        $sql = $this->ensureLimit($sql);

        try {
            $extDb = ExternalDatabase::getInstance();
            $startTime = microtime(true);

            // ExternalDatabase::query retourne un PDOStatement, utiliser fetchAll
            $stmt = $extDb->query($sql);
            $results = is_array($stmt) ? $stmt : $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success' => true,
                'database' => 'externe',
                'explanation' => $explanation,
                'query' => $sql,
                'results' => array_slice($results, 0, 100),
                'count' => count($results),
                'duration_ms' => $duration
            ];

        } catch (\Exception $e) {
            error_log("Agent SQL Error (external): " . $e->getMessage() . " | Query: " . $sql);
            return ['error' => 'Erreur SQL: ' . $e->getMessage(), 'query' => $sql];
        }
    }

    /**
     * Tool combiné : Stats d'un rep sur une campagne
     */
    private function getRepCampaignStats(array $args): array
    {
        $repName = $args['rep_name'] ?? '';
        $campaignName = $args['campaign_name'] ?? '';
        $country = $args['country'] ?? 'BE';

        if (empty($repName) || empty($campaignName)) {
            return ['error' => 'rep_name et campaign_name sont requis'];
        }

        try {
            // 1. Trouver la campagne
            $campaign = $this->db->query(
                "SELECT id, name, country, start_date, end_date FROM campaigns
                 WHERE name LIKE :name
                 ORDER BY start_date DESC LIMIT 1",
                [':name' => '%' . $campaignName . '%']
            );

            if (empty($campaign)) {
                return ['error' => "Campagne '{$campaignName}' non trouvée"];
            }
            $campaign = $campaign[0];
            $country = $campaign['country'];

            // 2. Essayer de se connecter à la base externe
            $extDb = $this->getExternalDb();

            if ($extDb === null) {
                return ['error' => 'Impossible de se connecter à la base externe'];
            }

            // 3. Trouver le représentant dans la base externe
            $repTable = $country === 'BE' ? 'BE_REP' : 'LU_REP';

            $searchPattern = '%' . $repName . '%';
            $repResults = $extDb->query(
                "SELECT IDE_REP, REP_PRENOM, REP_NOM FROM {$repTable}
                 WHERE REP_NOM LIKE :name1 OR REP_PRENOM LIKE :name2 LIMIT 5",
                [':name1' => $searchPattern, ':name2' => $searchPattern]
            );

            // Si false (erreur) ou vide, essayer l'autre pays
            if ($repResults === false || empty($repResults)) {
                $repTable = $country === 'BE' ? 'LU_REP' : 'BE_REP';
                $repResults = $extDb->query(
                    "SELECT IDE_REP, REP_PRENOM, REP_NOM FROM {$repTable}
                     WHERE REP_NOM LIKE :name1 OR REP_PRENOM LIKE :name2 LIMIT 5",
                    [':name1' => $searchPattern, ':name2' => $searchPattern]
                );
            }

            if ($repResults === false || empty($repResults)) {
                return ['error' => "Représentant '{$repName}' non trouvé dans les bases externes"];
            }

            $rep = $repResults[0];
            $repFullName = trim(($rep['REP_PRENOM'] ?? '') . ' ' . ($rep['REP_NOM'] ?? ''));
            $ideRep = $rep['IDE_REP'];

            // 4. Compter les clients de ce rep dans la base externe
            $clientTable = $country === 'BE' ? 'BE_CLL' : 'LU_CLL';
            $totalClientsResult = $extDb->query(
                "SELECT COUNT(*) as total FROM {$clientTable} WHERE IDE_REP = :ide_rep",
                [':ide_rep' => (string) $ideRep]
            );

            if ($totalClientsResult === false) {
                return ['error' => 'Erreur lors du comptage des clients'];
            }
            $totalClients = (int) ($totalClientsResult[0]['total'] ?? 0);

            // 5. Récupérer les customer_numbers de ce rep
            $clientsResult = $extDb->query(
                "SELECT CLL_NCLIXX FROM {$clientTable} WHERE IDE_REP = :ide_rep",
                [':ide_rep' => (string) $ideRep]
            );

            if ($clientsResult === false) {
                $clientsResult = [];
            }
            $repCustomerNumbers = array_column($clientsResult, 'CLL_NCLIXX');

            if (empty($repCustomerNumbers)) {
                return [
                    'representant' => $repFullName,
                    'rep_id' => $ideRep,
                    'campagne' => $campaign['name'],
                    'total_clients' => 0,
                    'clients_commande' => 0,
                    'commandes' => 0,
                    'promos_vendues' => 0
                ];
            }

            // 6. Chercher les stats de commandes pour cette campagne
            // On part des orders et on filtre par customer_number
            $stats = $this->db->query(
                "SELECT
                    COUNT(DISTINCT o.id) as total_orders,
                    COUNT(DISTINCT c.customer_number) as clients_ordered,
                    COALESCE(SUM(ol.quantity), 0) as total_promos
                 FROM orders o
                 JOIN customers c ON c.id = o.customer_id
                 JOIN order_lines ol ON ol.order_id = o.id
                 WHERE o.campaign_id = :campaign_id
                 AND o.status = 'validated'
                 AND c.country = :country",
                [':campaign_id' => $campaign['id'], ':country' => $country]
            );

            $allStats = $stats[0] ?? ['total_orders' => 0, 'clients_ordered' => 0, 'total_promos' => 0];

            // 7. Filtrer pour ne garder que les clients du rep
            // Échapper les numéros pour la requête IN (comme dans Stats.php)
            $escapedNumbers = array_map(function ($num) {
                return "'" . addslashes((string) $num) . "'";
            }, $repCustomerNumbers);
            $inClause = implode(',', $escapedNumbers);

            $repStats = $this->db->query(
                "SELECT
                    COUNT(DISTINCT o.id) as total_orders,
                    COUNT(DISTINCT c.customer_number) as clients_ordered,
                    COALESCE(SUM(ol.quantity), 0) as total_promos
                 FROM orders o
                 INNER JOIN customers c ON o.customer_id = c.id
                 LEFT JOIN order_lines ol ON o.id = ol.order_id
                 WHERE o.campaign_id = :campaign_id
                 AND o.status = 'validated'
                 AND c.country = :country
                 AND c.customer_number IN ({$inClause})",
                [':campaign_id' => $campaign['id'], ':country' => $country]
            );

            $repStatsData = $repStats[0] ?? ['total_orders' => 0, 'clients_ordered' => 0, 'total_promos' => 0];

            return [
                'representant' => $repFullName,
                'rep_id' => $ideRep,
                'campagne' => $campaign['name'],
                'pays' => $country,
                'total_clients' => $totalClients,
                'clients_commande' => (int) $repStatsData['clients_ordered'],
                'taux_participation' => $totalClients > 0
                    ? round(($repStatsData['clients_ordered'] / $totalClients) * 100, 1) . '%'
                    : '0%',
                'commandes' => (int) $repStatsData['total_orders'],
                'promos_vendues' => (int) $repStatsData['total_promos']
            ];

        } catch (\Exception $e) {
            error_log("getRepCampaignStats error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Fallback : Stats rep depuis la base locale uniquement
     */
    private function getRepCampaignStatsFromLocal(string $repName, array $campaign): array
    {
        // Chercher dans customers.rep_name
        $stats = $this->db->query(
            "SELECT
                c.rep_name,
                COUNT(DISTINCT o.id) as total_orders,
                COUNT(DISTINCT c.id) as total_clients,
                COALESCE(SUM(ol.quantity), 0) as total_quantity
             FROM customers c
             INNER JOIN orders o ON o.customer_id = c.id AND o.campaign_id = :campaign_id
             LEFT JOIN order_lines ol ON ol.order_id = o.id
             WHERE c.rep_name LIKE :rep_name
             AND o.status = 'validated'
             GROUP BY c.rep_name
             LIMIT 1",
            [':campaign_id' => $campaign['id'], ':rep_name' => '%' . $repName . '%']
        );

        if (empty($stats)) {
            return [
                'error' => "Représentant '{$repName}' non trouvé (base locale)",
                'note' => 'La connexion à la base externe a échoué, recherche dans la base locale uniquement'
            ];
        }

        $stat = $stats[0];
        return [
            'representant' => $stat['rep_name'] ?? $repName,
            'campagne' => $campaign['name'],
            'source' => 'base_locale',
            'clients_ayant_commande' => (int) $stat['total_clients'],
            'commandes' => (int) $stat['total_orders'],
            'promos_vendues' => (int) $stat['total_quantity'],
            'note' => 'Stats basées sur les clients ayant déjà commandé (base locale)'
        ];
    }

    /**
     * Validation SQL (sécurité)
     */
    private function validateSql(string $sql): bool|string
    {
        // Vérifier que c'est un SELECT
        if (!preg_match('/^\s*select\s/i', $sql)) {
            return 'Seules les requêtes SELECT sont autorisées';
        }

        // Interdire les mots-clés dangereux
        $forbidden = ['insert', 'update', 'delete', 'drop', 'truncate', 'alter', 'create', 'grant', 'revoke', 'exec', 'execute', '--'];
        foreach ($forbidden as $word) {
            if ($word === '--') {
                if (strpos($sql, $word) !== false) {
                    return "Caractère interdit: {$word}";
                }
            } else {
                if (preg_match('/\b' . $word . '\b/i', $sql)) {
                    return "Mot-clé interdit: {$word}";
                }
            }
        }

        return true;
    }

    /**
     * S'assurer qu'il y a un LIMIT
     */
    private function ensureLimit(string $sql): string
    {
        if (!preg_match('/\blimit\s+\d+/i', $sql)) {
            $sql = rtrim($sql, ' ;') . ' LIMIT 100';
        } else {
            // S'assurer que LIMIT <= 100
            $sql = preg_replace_callback('/\blimit\s+(\d+)/i', function($m) {
                return 'LIMIT ' . min((int)$m[1], 100);
            }, $sql);
        }
        return $sql;
    }

    /**
     * Liste des campagnes (simplifié)
     * Le statut est calculé dynamiquement basé sur les dates
     */
    private function listCampaigns(array $args): array
    {
        $sql = "SELECT id, name, country, status, start_date, end_date FROM campaigns WHERE 1=1";
        $params = [];

        if (!empty($args['country'])) {
            $sql .= " AND country = :country";
            $params[':country'] = $args['country'];
        }

        $sql .= " ORDER BY start_date DESC LIMIT 20";

        $campaigns = $this->db->query($sql, $params);

        $today = date('Y-m-d');
        $result = [];

        foreach ($campaigns as $c) {
            // Calculer le statut dynamiquement basé sur les dates
            $calculatedStatus = $this->calculateCampaignStatus($c['start_date'], $c['end_date'], $c['status']);

            // Filtrer par statut si demandé
            if (!empty($args['status']) && $calculatedStatus !== $args['status']) {
                continue;
            }

            $statusLabels = [
                'draft' => 'Brouillon',
                'scheduled' => 'Programmée',
                'active' => 'En cours',
                'ended' => 'Terminée',
                'cancelled' => 'Annulée'
            ];

            $result[] = [
                'id' => $c['id'],
                'nom' => $c['name'],
                'pays' => $c['country'],
                'statut' => $statusLabels[$calculatedStatus] ?? $calculatedStatus,
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
     * Calculer le statut d'une campagne basé sur ses dates
     */
    private function calculateCampaignStatus(string $startDate, string $endDate, string $dbStatus): string
    {
        // Si annulée dans la DB, garder ce statut
        if ($dbStatus === 'cancelled') {
            return 'cancelled';
        }

        $today = date('Y-m-d');
        $start = date('Y-m-d', strtotime($startDate));
        $end = date('Y-m-d', strtotime($endDate));

        if ($today < $start) {
            return 'scheduled';
        } elseif ($today >= $start && $today <= $end) {
            return 'active';
        } else {
            return 'ended';
        }
    }

    /**
     * Obtenir une connexion à la base externe avec gestion d'erreur
     */
    private function getExternalDb(): ?ExternalDatabase
    {
        try {
            return ExternalDatabase::getInstance();
        } catch (\Exception $e) {
            error_log("Agent: Impossible de se connecter à la base externe: " . $e->getMessage());
            return null;
        }
    }
}