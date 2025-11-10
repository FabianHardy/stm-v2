<?php
/**
 * Classe Database - Singleton pour la connexion MySQL
 * 
 * Gère la connexion PDO à la base de données MySQL
 * Pattern Singleton pour une seule instance
 * 
 * @package Core
 * @version 2.0.0
 */

namespace Core;

use PDO;
use PDOException;
use Exception;

class Database
{
    /**
     * Instance unique de la classe (Singleton)
     * @var Database|null
     */
    private static ?Database $instance = null;

    /**
     * Objet PDO pour la connexion
     * @var PDO|null
     */
    private ?PDO $pdo = null;

    /**
     * Configuration de la base de données
     * @var array
     */
    private array $config = [];

    /**
     * Constructeur privé (Singleton)
     * 
     * Charge la configuration depuis les variables d'environnement
     * et initialise la connexion PDO
     * 
     * @throws Exception Si les variables d'environnement sont manquantes
     */
    private function __construct()
    {
        // Charger la configuration depuis les variables d'environnement
        $this->config = [
            'host'     => $_ENV['DB_HOST'] ?? 'localhost',
            'database' => $_ENV['DB_NAME'] ?? '',
            'username' => $_ENV['DB_USER'] ?? '',
            'password' => $_ENV['DB_PASS'] ?? '',  // ✅ CORRIGÉ : DB_PASS au lieu de DB_PASSWORD
            'charset'  => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            'options'  => [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]
        ];

        // Vérifier que les variables essentielles sont définies
        if (empty($this->config['database']) || empty($this->config['username'])) {
            throw new Exception("Configuration de base de données incomplète. Vérifiez votre fichier .env");
        }

        // Initialiser la connexion
        $this->connect();
    }

    /**
     * Empêche le clonage de l'instance (Singleton)
     * 
     * @throws Exception
     */
    private function __clone()
    {
        throw new Exception("Impossible de cloner un Singleton");
    }

    /**
     * Empêche la désérialisation (Singleton)
     * 
     * @throws Exception
     */
    public function __wakeup()
    {
        throw new Exception("Impossible de désérialiser un Singleton");
    }

    /**
     * Récupère l'instance unique de la classe (Singleton)
     * 
     * @return Database Instance unique
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Établit la connexion PDO à la base de données
     * 
     * @throws PDOException Si la connexion échoue
     */
    private function connect(): void
    {
        try {
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                $this->config['host'],
                $this->config['database'],
                $this->config['charset']
            );

            $this->pdo = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );

        } catch (PDOException $e) {
            // Log l'erreur (en développement)
            if (($_ENV['APP_DEBUG'] ?? false) === 'true') {
                error_log("Erreur de connexion MySQL : " . $e->getMessage());
            }

            // Message générique en production
            throw new PDOException(
                "Erreur de connexion à la base de données. Vérifiez votre configuration.",
                (int) $e->getCode()
            );
        }
    }

    /**
     * Récupère l'objet PDO
     * 
     * @return PDO Objet PDO connecté
     */
    public function getPDO(): PDO
    {
        return $this->pdo;
    }

    /**
     * Vérifie si la connexion est active
     * 
     * @return bool True si connecté, False sinon
     */
    public function isConnected(): bool
    {
        return $this->pdo !== null;
    }

    /**
     * Exécute une requête préparée avec des paramètres
     * 
     * @param string $query Requête SQL avec placeholders
     * @param array $params Paramètres à binder
     * @return \PDOStatement Statement exécuté
     * @throws PDOException Si l'exécution échoue
     */
    public function query(string $query, array $params = []): \PDOStatement
    {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt;

        } catch (PDOException $e) {
            // Log l'erreur
            if (($_ENV['APP_DEBUG'] ?? false) === 'true') {
                error_log("Erreur SQL : " . $e->getMessage());
                error_log("Requête : " . $query);
                error_log("Paramètres : " . json_encode($params));
            }

            throw $e;
        }
    }

    /**
     * Récupère toutes les lignes d'une requête
     * 
     * @param string $query Requête SQL
     * @param array $params Paramètres
     * @return array Tableau de résultats
     */
    public function fetchAll(string $query, array $params = []): array
    {
        return $this->query($query, $params)->fetchAll();
    }

    /**
     * Récupère une seule ligne
     * 
     * @param string $query Requête SQL
     * @param array $params Paramètres
     * @return array|false Ligne ou false
     */
    public function fetch(string $query, array $params = [])
    {
        return $this->query($query, $params)->fetch();
    }

    /**
     * Récupère une seule valeur (première colonne, première ligne)
     * 
     * @param string $query Requête SQL
     * @param array $params Paramètres
     * @return mixed Valeur ou false
     */
    public function fetchColumn(string $query, array $params = [])
    {
        return $this->query($query, $params)->fetchColumn();
    }

    /**
     * Exécute une requête INSERT et retourne l'ID inséré
     * 
     * @param string $query Requête INSERT
     * @param array $params Paramètres
     * @return string ID du dernier enregistrement inséré
     */
    public function insert(string $query, array $params = []): string
    {
        $this->query($query, $params);
        return $this->pdo->lastInsertId();
    }

    /**
     * Démarre une transaction
     * 
     * @return bool True si réussi
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Valide une transaction
     * 
     * @return bool True si réussi
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Annule une transaction
     * 
     * @return bool True si réussi
     */
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Récupère les informations de configuration (sans le mot de passe)
     * 
     * @return array Configuration (mot de passe masqué)
     */
    public function getConfig(): array
    {
        $config = $this->config;
        $config['password'] = '****'; // Masquer le mot de passe
        return $config;
    }
}