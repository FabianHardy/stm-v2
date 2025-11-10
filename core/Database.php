<?php
/**
 * Classe Database
 * 
 * Gestion de la connexion à la base de données MySQL.
 * 
 * Pattern Singleton pour garantir une seule connexion.
 * 
 * @package STM
 * @version 2.1
 */

namespace Core;

use PDO;
use PDOException;

class Database
{
    /**
     * Instance unique de Database (Singleton)
     * 
     * @var Database|null
     */
    private static ?Database $instance = null;
    
    /**
     * Connexion PDO
     * 
     * @var PDO|null
     */
    private ?PDO $connection = null;
    
    /**
     * Constructeur privé (Singleton)
     * 
     * @throws PDOException
     */
    private function __construct()
    {
        $this->connect();
    }
    
    /**
     * Récupère l'instance unique de Database
     * 
     * @return Database
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        
        return self::$instance;
    }
    
    /**
     * Récupère une variable d'environnement (compatible getenv et $_ENV)
     * 
     * @param string $key Nom de la variable
     * @param string $default Valeur par défaut
     * @return string
     */
    private function env(string $key, string $default = ''): string
    {
        // Essayer getenv() en premier
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }
        
        // Fallback sur $_ENV
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }
        
        // Retourner la valeur par défaut
        return $default;
    }
    
    /**
     * Crée la connexion à la base de données
     * 
     * @return void
     * @throws PDOException
     */
    private function connect(): void
    {
        try {
            // Récupérer les paramètres depuis .env avec support des deux méthodes
            $host = $this->env('DB_HOST', 'localhost');
            $port = $this->env('DB_PORT', '3306');
            
            // Support pour DB_DATABASE ou DB_NAME (compatibilité O2switch)
            $database = $this->env('DB_NAME');
            if (empty($database)) {
                $database = $this->env('DB_DATABASE');
            }
            
            // Support pour DB_USERNAME ou DB_USER (compatibilité O2switch)
            $username = $this->env('DB_USER');
            if (empty($username)) {
                $username = $this->env('DB_USERNAME');
            }
            
            // Support pour DB_PASSWORD ou DB_PASS (compatibilité O2switch)
            $password = $this->env('DB_PASS');
            if (empty($password)) {
                $password = $this->env('DB_PASSWORD');
            }
            
            $charset = $this->env('DB_CHARSET', 'utf8mb4');
            
            // Vérifier que les paramètres essentiels sont présents
            if (empty($database) || empty($username)) {
                throw new PDOException('Paramètres de connexion à la base de données manquants. Vérifiez votre fichier .env');
            }
            
            // Construire le DSN
            $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=$charset";
            
            // Options PDO pour la sécurité et les performances
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset COLLATE {$charset}_unicode_ci"
            ];
            
            // Créer la connexion PDO
            $this->connection = new PDO($dsn, $username, $password, $options);
            
        } catch (PDOException $e) {
            // Logger l'erreur
            error_log('Erreur de connexion à la base de données : ' . $e->getMessage());
            
            // En mode debug, afficher l'erreur
            if ($this->env('APP_DEBUG') === 'true') {
                throw new PDOException('Impossible de se connecter à la base de données : ' . $e->getMessage());
            } else {
                throw new PDOException('Erreur de connexion à la base de données. Veuillez contacter l\'administrateur.');
            }
        }
    }
    
    /**
     * Récupère la connexion PDO
     * 
     * @return PDO
     */
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connect();
        }
        
        return $this->connection;
    }
    
    /**
     * Exécute une requête SELECT et retourne tous les résultats
     * 
     * @param string $query Requête SQL
     * @param array $params Paramètres de la requête
     * @return array
     */
    public function query(string $query, array $params = []): array
    {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Erreur query() : ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Exécute une requête SELECT et retourne la première ligne
     * 
     * @param string $query Requête SQL
     * @param array $params Paramètres de la requête
     * @return array|null
     */
    public function queryOne(string $query, array $params = []): ?array
    {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            error_log('Erreur queryOne() : ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Exécute une requête INSERT, UPDATE ou DELETE
     * 
     * @param string $query Requête SQL
     * @param array $params Paramètres de la requête
     * @return bool
     */
    public function execute(string $query, array $params = []): bool
    {
        try {
            $stmt = $this->connection->prepare($query);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('Erreur execute() : ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Récupère le dernier ID inséré
     * 
     * @return string
     */
    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }
    
    /**
     * Commence une transaction
     * 
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Valide une transaction
     * 
     * @return bool
     */
    public function commit(): bool
    {
        return $this->connection->commit();
    }
    
    /**
     * Annule une transaction
     * 
     * @return bool
     */
    public function rollBack(): bool
    {
        return $this->connection->rollBack();
    }
    
    /**
     * Vérifie si une transaction est en cours
     * 
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->connection->inTransaction();
    }
    
    /**
     * Empêche le clonage de l'instance (Singleton)
     * 
     * @return void
     */
    private function __clone()
    {
        // Empêcher le clonage
    }
    
    /**
     * Empêche la désérialisation de l'instance (Singleton)
     * 
     * @return void
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
    
    /**
     * Ferme la connexion à la base de données
     * 
     * @return void
     */
    public function close(): void
    {
        $this->connection = null;
    }
}