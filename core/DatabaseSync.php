<?php
/**
 * DatabaseSync - Utilitaire de synchronisation base de données
 * 
 * Permet de copier les données de la base de production vers
 * la base de développement avec vérification de structure.
 * 
 * @package Core
 * @created 2025/11/25 12:00
 */

namespace Core;

use PDO;
use PDOException;
use Exception;

class DatabaseSync
{
    /**
     * Connexion à la base de données source (prod)
     * @var PDO
     */
    private PDO $sourceDb;
    
    /**
     * Connexion à la base de données cible (dev)
     * @var PDO
     */
    private PDO $targetDb;
    
    /**
     * Tables à exclure de la synchronisation
     * @var array
     */
    private array $excludedTables = [
        'users',      // Garder les utilisateurs spécifiques à chaque env
        'sessions'    // Sessions spécifiques à chaque environnement
    ];
    
    /**
     * Tables optionnelles (non cochées par défaut)
     * @var array
     */
    private array $optionalTables = [
        'audit_logs'  // Logs d'audit - volumineux et pas nécessaire pour les tests
    ];
    
    /**
     * Résultats de la synchronisation
     * @var array
     */
    private array $results = [];
    
    /**
     * Erreurs rencontrées
     * @var array
     */
    private array $errors = [];
    
    /**
     * Constructeur
     * 
     * @param array $sourceConfig Configuration de la base source (prod)
     * @param array $targetConfig Configuration de la base cible (dev)
     */
    public function __construct(array $sourceConfig, array $targetConfig)
    {
        $this->sourceDb = $this->createConnection($sourceConfig, 'source');
        $this->targetDb = $this->createConnection($targetConfig, 'target');
    }
    
    /**
     * Crée une connexion PDO
     * 
     * @param array $config Configuration de connexion
     * @param string $name Nom de la connexion (pour les erreurs)
     * @return PDO
     * @throws Exception
     */
    private function createConnection(array $config, string $name): PDO
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $config['host'] ?? 'localhost',
                $config['port'] ?? '3306',
                $config['database'],
                $config['charset'] ?? 'utf8mb4'
            );
            
            $pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            
            return $pdo;
            
        } catch (PDOException $e) {
            throw new Exception("Impossible de se connecter à la base {$name}: " . $e->getMessage());
        }
    }
    
    /**
     * Récupère la liste des tables de la base source
     * 
     * @return array
     */
    public function getSourceTables(): array
    {
        $stmt = $this->sourceDb->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        return array_filter($tables, function($table) {
            return !in_array($table, $this->excludedTables);
        });
    }
    
    /**
     * Récupère la liste des tables de la base cible
     * 
     * @return array
     */
    public function getTargetTables(): array
    {
        $stmt = $this->targetDb->query("SHOW TABLES");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Récupère la structure d'une table
     * 
     * @param PDO $db Connexion PDO
     * @param string $table Nom de la table
     * @return array
     */
    public function getTableStructure(PDO $db, string $table): array
    {
        $stmt = $db->query("DESCRIBE `{$table}`");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $structure = [];
        foreach ($columns as $col) {
            $structure[$col['Field']] = [
                'type' => $col['Type'],
                'null' => $col['Null'],
                'key' => $col['Key'],
                'default' => $col['Default'],
                'extra' => $col['Extra']
            ];
        }
        
        return $structure;
    }
    
    /**
     * Compare la structure de deux tables
     * 
     * @param string $table Nom de la table
     * @return array Différences trouvées
     */
    public function compareTableStructure(string $table): array
    {
        $sourceStructure = $this->getTableStructure($this->sourceDb, $table);
        $targetStructure = $this->getTableStructure($this->targetDb, $table);
        
        $differences = [];
        
        // Colonnes présentes dans source mais pas dans target
        foreach ($sourceStructure as $col => $def) {
            if (!isset($targetStructure[$col])) {
                $differences[] = [
                    'type' => 'missing_in_dev',
                    'column' => $col,
                    'message' => "Colonne '{$col}' absente dans la DB dev"
                ];
            } elseif ($def['type'] !== $targetStructure[$col]['type']) {
                $differences[] = [
                    'type' => 'type_mismatch',
                    'column' => $col,
                    'message' => "Type différent pour '{$col}': PROD={$def['type']} vs DEV={$targetStructure[$col]['type']}"
                ];
            }
        }
        
        // Colonnes présentes dans target mais pas dans source
        foreach ($targetStructure as $col => $def) {
            if (!isset($sourceStructure[$col])) {
                $differences[] = [
                    'type' => 'missing_in_prod',
                    'column' => $col,
                    'message' => "Colonne '{$col}' présente uniquement dans la DB dev"
                ];
            }
        }
        
        return $differences;
    }
    
    /**
     * Vérifie que toutes les tables ont une structure identique
     * 
     * @param array $tables Tables à vérifier
     * @return array Rapport de vérification
     */
    public function verifyAllStructures(array $tables): array
    {
        $report = [
            'success' => true,
            'tables' => []
        ];
        
        $targetTables = $this->getTargetTables();
        
        foreach ($tables as $table) {
            // Vérifier que la table existe dans la cible
            if (!in_array($table, $targetTables)) {
                $report['success'] = false;
                $report['tables'][$table] = [
                    'exists' => false,
                    'differences' => [
                        ['type' => 'table_missing', 'message' => "Table '{$table}' n'existe pas dans la DB dev"]
                    ]
                ];
                continue;
            }
            
            $differences = $this->compareTableStructure($table);
            
            $report['tables'][$table] = [
                'exists' => true,
                'differences' => $differences,
                'is_identical' => empty($differences)
            ];
            
            if (!empty($differences)) {
                $report['success'] = false;
            }
        }
        
        return $report;
    }
    
    /**
     * Compte le nombre de lignes dans une table
     * 
     * @param PDO $db Connexion PDO
     * @param string $table Nom de la table
     * @return int
     */
    public function countRows(PDO $db, string $table): int
    {
        $stmt = $db->query("SELECT COUNT(*) FROM `{$table}`");
        return (int) $stmt->fetchColumn();
    }
    
    /**
     * Récupère les statistiques des tables
     * 
     * @param array $tables Tables à analyser
     * @return array
     */
    public function getTablesStats(array $tables): array
    {
        $stats = [];
        
        foreach ($tables as $table) {
            $stats[$table] = [
                'source_count' => $this->countRows($this->sourceDb, $table),
                'target_count' => $this->countRows($this->targetDb, $table),
                'is_optional' => in_array($table, $this->optionalTables)
            ];
        }
        
        return $stats;
    }
    
    /**
     * Synchronise une table (copie les données de source vers target)
     * 
     * @param string $table Nom de la table
     * @return array Résultat de la synchronisation
     */
    public function syncTable(string $table): array
    {
        $result = [
            'table' => $table,
            'success' => false,
            'rows_deleted' => 0,
            'rows_copied' => 0,
            'error' => null
        ];
        
        try {
            // Compter les lignes source
            $sourceCount = $this->countRows($this->sourceDb, $table);
            
            // Désactiver les contraintes FK temporairement
            $this->targetDb->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            // Vider la table cible
            $this->targetDb->exec("TRUNCATE TABLE `{$table}`");
            $result['rows_deleted'] = $this->countRows($this->targetDb, $table);
            
            // Si la table source est vide, on s'arrête là
            if ($sourceCount === 0) {
                $result['success'] = true;
                $result['rows_copied'] = 0;
                $this->targetDb->exec("SET FOREIGN_KEY_CHECKS = 1");
                return $result;
            }
            
            // Récupérer les colonnes
            $columns = array_keys($this->getTableStructure($this->sourceDb, $table));
            $columnList = '`' . implode('`, `', $columns) . '`';
            
            // Copier les données par lots de 500
            $batchSize = 500;
            $offset = 0;
            $totalCopied = 0;
            
            while ($offset < $sourceCount) {
                // Récupérer un lot de données
                $stmt = $this->sourceDb->query(
                    "SELECT * FROM `{$table}` LIMIT {$batchSize} OFFSET {$offset}"
                );
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($rows)) {
                    break;
                }
                
                // Préparer l'insertion
                $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
                $insertSql = "INSERT INTO `{$table}` ({$columnList}) VALUES {$placeholders}";
                $insertStmt = $this->targetDb->prepare($insertSql);
                
                // Insérer chaque ligne
                foreach ($rows as $row) {
                    $values = [];
                    foreach ($columns as $col) {
                        $values[] = $row[$col] ?? null;
                    }
                    $insertStmt->execute($values);
                    $totalCopied++;
                }
                
                $offset += $batchSize;
            }
            
            // Réactiver les contraintes FK
            $this->targetDb->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            $result['success'] = true;
            $result['rows_copied'] = $totalCopied;
            
        } catch (PDOException $e) {
            $result['error'] = $e->getMessage();
            $this->targetDb->exec("SET FOREIGN_KEY_CHECKS = 1");
        }
        
        return $result;
    }
    
    /**
     * Synchronise plusieurs tables
     * 
     * @param array $tables Tables à synchroniser
     * @return array Résultats de la synchronisation
     */
    public function syncTables(array $tables): array
    {
        $results = [
            'success' => true,
            'total_rows_copied' => 0,
            'tables' => []
        ];
        
        // Ordre de synchronisation (respecter les FK)
        $orderedTables = $this->orderTablesByDependencies($tables);
        
        foreach ($orderedTables as $table) {
            $result = $this->syncTable($table);
            $results['tables'][$table] = $result;
            $results['total_rows_copied'] += $result['rows_copied'];
            
            if (!$result['success']) {
                $results['success'] = false;
            }
        }
        
        return $results;
    }
    
    /**
     * Ordonne les tables selon leurs dépendances FK
     * (tables sans FK en premier)
     * 
     * @param array $tables
     * @return array
     */
    private function orderTablesByDependencies(array $tables): array
    {
        // Ordre recommandé pour STM v2 (tables parent d'abord)
        $preferredOrder = [
            'product_categories',
            'categories',
            'campaigns',
            'products',
            'customers',
            'email_templates',
            'terms_conditions',
            'campaign_customers',
            'campaign_products',
            'orders',
            'order_lines',
            'audit_logs'
        ];
        
        $ordered = [];
        
        // Ajouter les tables dans l'ordre préféré si elles sont dans la liste
        foreach ($preferredOrder as $table) {
            if (in_array($table, $tables)) {
                $ordered[] = $table;
            }
        }
        
        // Ajouter les tables restantes
        foreach ($tables as $table) {
            if (!in_array($table, $ordered)) {
                $ordered[] = $table;
            }
        }
        
        return $ordered;
    }
    
    /**
     * Récupère les tables exclues
     * 
     * @return array
     */
    public function getExcludedTables(): array
    {
        return $this->excludedTables;
    }
    
    /**
     * Récupère les tables optionnelles
     * 
     * @return array
     */
    public function getOptionalTables(): array
    {
        return $this->optionalTables;
    }
    
    /**
     * Ferme les connexions
     */
    public function close(): void
    {
        $this->sourceDb = null;
        $this->targetDb = null;
    }
}
