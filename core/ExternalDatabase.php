<?php
/**
 * ExternalDatabase.php
 *
 * Classe singleton pour gérer la connexion à la base de données externe
 * trendyblog_sig contenant les informations clients, représentants et articles
 *
 * Tables utilisées :
 * - BE_CLL : Clients Belgique
 * - LU_CLL : Clients Luxembourg
 * - BE_REP : Représentants Belgique
 * - LU_REP : Représentants Luxembourg
 * - BE_COLIS : Mapping codes articles (Art_Colis → Detail_Art)
 * - BE_ART : Articles/Produits (détails)
 * - BE_FOD : Fournisseurs
 *
 * @created  2025/11/12 19:25
 * @modified 2025/12/09 - Ajout méthodes fournisseurs via BE_COLIS → BE_ART → BE_FOD
 */

namespace Core;

use PDO;
use PDOException;

class ExternalDatabase
{
    /**
     * Instance unique de la classe (Singleton)
     */
    private static ?ExternalDatabase $instance = null;

    /**
     * Instance PDO pour la connexion
     */
    private ?PDO $pdo = null;

    /**
     * Constructeur privé (Singleton)
     * Initialise la connexion à la base de données externe
     */
    private function __construct()
    {
        try {
            // Récupérer les credentials depuis .env
            $host = $_ENV['EXTERNAL_DB_HOST'] ?? $_ENV['DB_HOST'];
            $dbname = $_ENV['EXTERNAL_DB_NAME'] ?? 'trendyblog_sig';
            $user = $_ENV['EXTERNAL_DB_USER'] ?? $_ENV['DB_USER'];
            $password = $_ENV['EXTERNAL_DB_PASSWORD'] ?? $_ENV['DB_PASSWORD'];

            // Options PDO
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];

            // Créer la connexion
            $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $user, $password, $options);

            error_log("ExternalDatabase: Connexion établie à {$dbname}");
        } catch (PDOException $e) {
            error_log("ExternalDatabase: Erreur de connexion - " . $e->getMessage());
            throw new PDOException("Impossible de se connecter à la base externe: " . $e->getMessage());
        }
    }

    /**
     * Empêcher le clonage de l'instance
     */
    private function __clone() {}

    /**
     * Empêcher la désérialisation de l'instance
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }

    /**
     * Obtenir l'instance unique de la classe
     *
     * @return ExternalDatabase Instance unique
     */
    public static function getInstance(): ExternalDatabase
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Obtenir l'objet PDO
     *
     * @return PDO Instance PDO
     */
    public function getPDO(): PDO
    {
        return $this->pdo;
    }

    /**
     * Récupérer un client depuis BE_CLL ou LU_CLL
     *
     * @param string $cllNclixx Numéro client (CLL_NCLIXX)
     * @param string $country Pays ('BE' ou 'LU')
     * @return array|null Données du client ou null si non trouvé
     */
    public function getCustomer(string $cllNclixx, string $country): ?array
    {
        try {
            // Déterminer la table selon le pays
            $table = $country === 'BE' ? 'BE_CLL' : 'LU_CLL';

            // Préparer la requête
            $sql = "SELECT
                        IDE_CLL,
                        CLL_NCLIXX,
                        CLL_NOM,
                        CLL_PRENOM,
                        CLL_ADRESSE1,
                        CLL_ADRESSE2,
                        CLL_CPOSTAL,
                        CLL_LOCALITE,
                        IDE_REP
                    FROM {$table}
                    WHERE CLL_NCLIXX = :cll_nclixx
                    LIMIT 1";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':cll_nclixx' => $cllNclixx]);

            $result = $stmt->fetch();

            return $result ?: null;
        } catch (PDOException $e) {
            error_log("ExternalDatabase::getCustomer() - Erreur : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer TOUS les clients d'un pays
     * Utilisé pour l'attribution dynamique "tous les clients"
     *
     * @param string $country Pays ('BE' ou 'LU')
     * @return array Tableau de clients
     */
    public function getAllCustomers(string $country): array
    {
        try {
            // Déterminer la table selon le pays
            $table = $country === 'BE' ? 'BE_CLL' : 'LU_CLL';

            // Préparer la requête
            $sql = "SELECT
                        IDE_CLL,
                        CLL_NCLIXX,
                        CLL_NOM,
                        CLL_PRENOM,
                        CLL_ADRESSE1,
                        CLL_ADRESSE2,
                        CLL_CPOSTAL,
                        CLL_LOCALITE,
                        IDE_REP
                    FROM {$table}
                    ORDER BY CLL_NOM ASC";

            $stmt = $this->pdo->query($sql);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("ExternalDatabase::getAllCustomers() - Erreur : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les informations d'un représentant
     *
     * @param string $ideRep ID du représentant (IDE_REP)
     * @param string $country Pays ('BE' ou 'LU')
     * @return array|null Données du représentant ou null si non trouvé
     */
    public function getRepresentative(string $ideRep, string $country): ?array
    {
        try {
            // Déterminer la table selon le pays
            $table = $country === 'BE' ? 'BE_REP' : 'LU_REP';

            // Préparer la requête
            $sql = "SELECT
                        IDE_REP,
                        REP_PRENOM,
                        REP_NOM,
                        REP_EMAIL,
                        REP_CLU,
                        REP_SIPAD
                    FROM {$table}
                    WHERE IDE_REP = :ide_rep
                    LIMIT 1";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':ide_rep' => $ideRep]);

            $result = $stmt->fetch();

            return $result ?: null;
        } catch (PDOException $e) {
            error_log("ExternalDatabase::getRepresentative() - Erreur : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer tous les représentants d'un pays
     *
     * @param string $country Pays ('BE' ou 'LU')
     * @return array Tableau de représentants
     */
    public function getAllRepresentatives(string $country): array
    {
        try {
            // Déterminer la table selon le pays
            $table = $country === 'BE' ? 'BE_REP' : 'LU_REP';

            // Préparer la requête
            $sql = "SELECT
                        IDE_REP,
                        REP_PRENOM,
                        REP_NOM,
                        REP_EMAIL
                    FROM {$table}
                    ORDER BY REP_NOM ASC, REP_PRENOM ASC";

            $stmt = $this->pdo->query($sql);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("ExternalDatabase::getAllRepresentatives() - Erreur : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Compter le nombre de clients dans une table
     *
     * @param string $country Pays ('BE' ou 'LU')
     * @return int Nombre de clients
     */
    public function countCustomers(string $country): int
    {
        try {
            $table = $country === 'BE' ? 'BE_CLL' : 'LU_CLL';
            $sql = "SELECT COUNT(*) as total FROM {$table}";
            $stmt = $this->pdo->query($sql);
            $result = $stmt->fetch();

            return (int) $result['total'];
        } catch (PDOException $e) {
            error_log("ExternalDatabase::countCustomers() - Erreur : " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Tester la connexion à la base de données
     *
     * @return bool True si la connexion fonctionne, False sinon
     */
    public function testConnection(): bool
    {
        try {
            // Test simple : sélectionner la version de MySQL
            $stmt = $this->pdo->query("SELECT VERSION() as version");
            $result = $stmt->fetch();

            if ($result) {
                error_log("ExternalDatabase: Test connexion OK - MySQL version : " . $result['version']);
                return true;
            }

            return false;
        } catch (PDOException $e) {
            error_log("ExternalDatabase::testConnection() - Erreur : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifier si les tables nécessaires existent
     *
     * @return array État des tables [table => bool]
     */
    public function checkTables(): array
    {
        $tables = ['BE_CLL', 'LU_CLL', 'BE_REP', 'LU_REP', 'BE_COLIS', 'BE_ART', 'BE_FOD'];
        $result = [];

        foreach ($tables as $table) {
            try {
                $sql = "SHOW TABLES LIKE '{$table}'";
                $stmt = $this->pdo->query($sql);
                $result[$table] = $stmt->rowCount() > 0;
            } catch (PDOException $e) {
                $result[$table] = false;
            }
        }

        return $result;
    }

    /**
     * Exécuter une requête SQL personnalisée (pour debug)
     *
     * @param string $sql Requête SQL
     * @param array $params Paramètres de la requête
     * @return array|bool Résultats ou false en cas d'erreur
     */
    public function query(string $sql, array $params = []): array|bool
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("ExternalDatabase::query() - Erreur : " . $e->getMessage());
            return false;
        }
    }

    // ========================================================================
    // MÉTHODES FOURNISSEURS (BE_FOD) - Ajout 09/12/2025
    // ========================================================================

    /**
     * Récupérer un fournisseur par son ID
     *
     * @param string $ideFod ID fournisseur (IDE_FOD)
     * @return array|null Données du fournisseur ou null
     */
    public function getSupplier(string $ideFod): ?array
    {
        try {
            $sql = "SELECT IDE_FOD, FOD_NFOUXX, FOD_NOM 
                    FROM BE_FOD 
                    WHERE IDE_FOD = :ide_fod 
                    LIMIT 1";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':ide_fod' => $ideFod]);

            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("ExternalDatabase::getSupplier() - Erreur : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer tous les fournisseurs
     *
     * @return array Liste des fournisseurs
     */
    public function getAllSuppliers(): array
    {
        try {
            $sql = "SELECT IDE_FOD, FOD_NFOUXX, FOD_NOM 
                    FROM BE_FOD 
                    ORDER BY FOD_NOM ASC";

            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("ExternalDatabase::getAllSuppliers() - Erreur : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les fournisseurs pour une liste de codes articles
     * 
     * Chemin : product_code → BE_COLIS.Art_Colis → BE_COLIS.Detail_Art → BE_ART.ART_RECHART → BE_FOD
     *
     * @param array $productCodes Liste des codes articles (Art_Colis dans BE_COLIS)
     * @return array Mapping [code_article => ['supplier_id', 'supplier_number', 'supplier_name']]
     */
    public function getSuppliersForProducts(array $productCodes): array
    {
        if (empty($productCodes)) {
            return [];
        }

        try {
            // Créer les placeholders pour IN
            $placeholders = implode(',', array_fill(0, count($productCodes), '?'));

            // Jointure via BE_COLIS pour lier product_code → fournisseur
            $sql = "SELECT 
                        co.Art_Colis as product_code,
                        a.IDE_FODI as supplier_id,
                        COALESCE(f.FOD_NFOUXX, 'N/A') as supplier_number,
                        COALESCE(f.FOD_NOM, 'Fournisseur inconnu') as supplier_name
                    FROM BE_COLIS co
                    LEFT JOIN BE_ART a ON a.ART_RECHART = co.Detail_Art
                    LEFT JOIN BE_FOD f ON a.IDE_FODI = f.IDE_FOD
                    WHERE co.Art_Colis IN ({$placeholders})";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($productCodes);

            $results = $stmt->fetchAll();

            // Convertir en mapping
            $mapping = [];
            foreach ($results as $row) {
                $mapping[$row['product_code']] = [
                    'supplier_id' => $row['supplier_id'],
                    'supplier_number' => $row['supplier_number'],
                    'supplier_name' => $row['supplier_name']
                ];
            }

            return $mapping;
        } catch (PDOException $e) {
            error_log("ExternalDatabase::getSuppliersForProducts() - Erreur : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer un article avec ses informations fournisseur
     * 
     * Chemin : product_code → BE_COLIS.Art_Colis → BE_COLIS.Detail_Art → BE_ART → BE_FOD
     *
     * @param string $productCode Code article (Art_Colis dans BE_COLIS)
     * @return array|null Article avec fournisseur ou null
     */
    public function getArticleWithSupplier(string $productCode): ?array
    {
        try {
            $sql = "SELECT 
                        co.Art_Colis as product_code,
                        a.IDE_ART,
                        a.ART_RECHART as detail_code,
                        a.ART_LIB1 as product_name,
                        a.IDE_FODI as supplier_id,
                        COALESCE(f.FOD_NFOUXX, 'N/A') as supplier_number,
                        COALESCE(f.FOD_NOM, 'Fournisseur inconnu') as supplier_name
                    FROM BE_COLIS co
                    LEFT JOIN BE_ART a ON a.ART_RECHART = co.Detail_Art
                    LEFT JOIN BE_FOD f ON a.IDE_FODI = f.IDE_FOD
                    WHERE co.Art_Colis = :product_code
                    LIMIT 1";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':product_code' => $productCode]);

            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("ExternalDatabase::getArticleWithSupplier() - Erreur : " . $e->getMessage());
            return null;
        }
    }
}