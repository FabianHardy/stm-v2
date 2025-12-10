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
        // DEBUG LOG vers fichier
        $logFile = dirname(__DIR__, 2) . '/public/agent_debug.log';
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - executeTool: {$toolName}\n", FILE_APPEND);
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Arguments: " . json_encode($arguments) . "\n", FILE_APPEND);

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
     * Utilise une jointure cross-database pour plus de fiabilité
     *
     * LOGIQUE CORRIGÉE :
     * 1. Trouver le représentant d'abord → déterminer son pays
     * 2. Chercher la campagne dans CE pays
     */
    private function getRepCampaignStats(array $args): array
    {
        // DEBUG LOG vers fichier
        $logFile = dirname(__DIR__, 2) . '/public/agent_debug.log';
        $log = function($msg) use ($logFile) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $msg . "\n", FILE_APPEND);
        };

        $log("=== getRepCampaignStats CALLED ===");
        $log("Args: " . json_encode($args));

        $repName = $args['rep_name'] ?? '';
        $campaignName = $args['campaign_name'] ?? '';

        if (empty($repName) || empty($campaignName)) {
            $log("ERROR: Missing args");
            return ['error' => 'rep_name et campaign_name sont requis'];
        }

        try {
            // 1. TROUVER LE REPRÉSENTANT D'ABORD (pour déterminer son pays)
            $extDb = $this->getExternalDb();
            if ($extDb === null) {
                $log("ERROR: External DB null");
                return ['error' => 'Impossible de se connecter à la base externe'];
            }

            $searchPattern = '%' . $repName . '%';
            $repCountry = null;
            $rep = null;

            // Chercher dans BE_REP
            $log("Searching BE_REP for: {$repName}");
            $repResults = $extDb->query(
                "SELECT IDE_REP, REP_PRENOM, REP_NOM FROM BE_REP
                 WHERE REP_NOM LIKE :name1 OR REP_PRENOM LIKE :name2 LIMIT 5",
                [':name1' => $searchPattern, ':name2' => $searchPattern]
            );

            $log("BE_REP results: " . json_encode($repResults));

            if ($repResults !== false && !empty($repResults)) {
                $rep = $repResults[0];
                $repCountry = 'BE';
            } else {
                // Chercher dans LU_REP
                $log("Searching LU_REP...");
                $repResults = $extDb->query(
                    "SELECT IDE_REP, REP_PRENOM, REP_NOM FROM LU_REP
                     WHERE REP_NOM LIKE :name1 OR REP_PRENOM LIKE :name2 LIMIT 5",
                    [':name1' => $searchPattern, ':name2' => $searchPattern]
                );

                if ($repResults !== false && !empty($repResults)) {
                    $rep = $repResults[0];
                    $repCountry = 'LU';
                }
            }

            if (!$rep) {
                $log("ERROR: Rep not found");
                return ['error' => "Représentant '{$repName}' non trouvé"];
            }

            $repFullName = trim(($rep['REP_PRENOM'] ?? '') . ' ' . ($rep['REP_NOM'] ?? ''));
            $ideRep = $rep['IDE_REP'];
            $log("Found rep: {$repFullName}, IDE_REP: {$ideRep}, Country: {$repCountry}");

            // 2. TROUVER LA CAMPAGNE DANS LE PAYS DU REP
            // Si plusieurs campagnes matchent, demander clarification
            $log("Searching campaign '{$campaignName}' for country {$repCountry}");

            // Chercher toutes les campagnes qui matchent avec leur nombre de commandes
            $allMatches = $this->db->query(
                "SELECT c.id, c.name, c.country, c.start_date, c.end_date,
                        (SELECT COUNT(*) FROM orders o WHERE o.campaign_id = c.id AND o.status = 'validated') as order_count
                 FROM campaigns c
                 WHERE c.name LIKE :name
                 AND c.country = :country
                 ORDER BY order_count DESC, c.start_date DESC
                 LIMIT 10",
                [':name' => '%' . $campaignName . '%', ':country' => $repCountry]
            );

            $log("All matching campaigns: " . json_encode($allMatches));

            // Filtrer les campagnes avec au moins quelques commandes ou récentes
            $relevantCampaigns = array_filter($allMatches, function($c) {
                return $c['order_count'] > 0 || strtotime($c['end_date']) > strtotime('-6 months');
            });
            $relevantCampaigns = array_values($relevantCampaigns);

            // Si plusieurs campagnes pertinentes, demander clarification avec boutons
            if (count($relevantCampaigns) > 1) {
                $log("Multiple campaigns found, asking for clarification");
                $campaignList = [];
                $buttons = [];

                foreach ($relevantCampaigns as $c) {
                    $period = date('d/m', strtotime($c['start_date'])) . ' - ' . date('d/m/Y', strtotime($c['end_date']));
                    $campaignList[] = [
                        'id' => $c['id'],
                        'name' => $c['name'],
                        'periode' => $period,
                        'commandes' => (int) $c['order_count']
                    ];

                    // Créer un bouton pour chaque campagne
                    $buttonLabel = $c['name'] . ' (' . $c['order_count'] . ' cmd)';
                    $buttons[] = [
                        'label' => $buttonLabel,
                        'action' => "Stats de {$repName} sur {$c['name']}"
                    ];
                }

                return [
                    'clarification_needed' => true,
                    'message' => "Plusieurs campagnes correspondent à '{$campaignName}' pour {$repFullName}. Laquelle souhaitez-vous ?",
                    'campagnes_disponibles' => $campaignList,
                    'buttons' => $buttons,
                    'representant' => $repFullName
                ];
            }

            // Si une seule campagne ou aucune pertinente, continuer normalement
            if (!empty($relevantCampaigns)) {
                $campaign = [$relevantCampaigns[0]];
                $log("Selected single campaign: ID={$relevantCampaigns[0]['id']}");
            } elseif (!empty($allMatches)) {
                // Prendre la première même sans commandes
                $campaign = [$allMatches[0]];
                $log("Selected campaign (no orders): ID={$allMatches[0]['id']}");
            } else {
                $campaign = [];
            }

            if (empty($campaign)) {
                // Fallback : chercher sans filtre pays si pas trouvé
                $log("Fallback: searching without country filter");
                $allMatches = $this->db->query(
                    "SELECT c.id, c.name, c.country, c.start_date, c.end_date,
                            (SELECT COUNT(*) FROM orders o WHERE o.campaign_id = c.id AND o.status = 'validated') as order_count
                     FROM campaigns c
                     WHERE c.name LIKE :name
                     ORDER BY order_count DESC, c.start_date DESC
                     LIMIT 5",
                    [':name' => '%' . $campaignName . '%']
                );

                if (empty($allMatches)) {
                    $log("ERROR: Campaign not found");
                    return ['error' => "Campagne '{$campaignName}' non trouvée"];
                }

                $campaign = [$allMatches[0]];
                $log("Fallback selected: ID={$allMatches[0]['id']}, orders={$allMatches[0]['order_count']}");
            }
            $campaign = $campaign[0];
            $log("Using campaign ID: {$campaign['id']}, country: {$campaign['country']}");

            // 3. Compter le total de clients du rep (base externe)
            $clientTable = $repCountry === 'BE' ? 'BE_CLL' : 'LU_CLL';
            $totalClientsResult = $extDb->query(
                "SELECT COUNT(*) as total FROM {$clientTable} WHERE IDE_REP = :ide_rep",
                [':ide_rep' => (string) $ideRep]
            );
            $totalClients = (int) ($totalClientsResult[0]['total'] ?? 0);
            $log("Total clients: {$totalClients}");

            // 4. Stats via jointure cross-database
            $extClientTable = $repCountry === 'BE' ? 'trendyblog_sig.BE_CLL' : 'trendyblog_sig.LU_CLL';

            $sql = "SELECT
                    COUNT(DISTINCT o.id) as commandes,
                    COUNT(DISTINCT c.id) as clients,
                    COALESCE(SUM(ol.quantity), 0) as promos
                 FROM orders o
                 JOIN customers c ON c.id = o.customer_id
                 JOIN order_lines ol ON o.id = ol.order_id
                 JOIN {$extClientTable} ext ON ext.CLL_NCLIXX = c.customer_number
                 WHERE o.campaign_id = :campaign_id
                 AND o.status = 'validated'
                 AND ext.IDE_REP = :rep_id";

            $log("Executing SQL with campaign_id={$campaign['id']}, rep_id={$ideRep}");

            $repStats = $this->db->query($sql,
                [':campaign_id' => $campaign['id'], ':rep_id' => (string) $ideRep]
            );

            $log("Stats result: " . json_encode($repStats));

            $stats = $repStats[0] ?? ['commandes' => 0, 'clients' => 0, 'promos' => 0];

            $result = [
                'representant' => $repFullName,
                'rep_id' => $ideRep,
                'campagne' => $campaign['name'],
                'pays' => $repCountry,
                'total_clients' => $totalClients,
                'clients_commande' => (int) $stats['clients'],
                'taux_participation' => $totalClients > 0
                    ? round(($stats['clients'] / $totalClients) * 100, 1) . '%'
                    : '0%',
                'commandes' => (int) $stats['commandes'],
                'promos_vendues' => (int) $stats['promos']
            ];

            $log("FINAL RESULT: " . json_encode($result));
            return $result;

        } catch (\Exception $e) {
            $log("EXCEPTION: " . $e->getMessage());
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