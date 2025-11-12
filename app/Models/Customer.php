<?php
/**
 * Customer.php
 * 
 * Model pour la gestion des clients B2B
 * Gère les clients avec contrainte unique composite (customer_number + country)
 * Permet l'import automatique depuis la base externe trendyblog_sig
 * 
 * @created  2025/11/12 20:00
 * @modified 2025/11/12 20:00 - Création initiale
 */

namespace App\Models;

use Core\Database;
use Core\ExternalDatabase;
use PDO;
use PDOException;

class Customer
{
    /**
     * Instance de la base de données
     */
    private Database $db;

    /**
     * Nom de la table
     */
    private string $table = 'customers';

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Récupérer tous les clients
     * 
     * @param array $filters Filtres optionnels (country, rep_name, is_active, is_blocked)
     * @param string $search Recherche dans customer_number, company_name, email
     * @param int $limit Limite de résultats (0 = pas de limite)
     * @param int $offset Décalage pour la pagination
     * @return array Liste des clients
     */
    public function findAll(array $filters = [], string $search = '', int $limit = 0, int $offset = 0): array
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE 1=1";
            $params = [];

            // Filtre par pays
            if (!empty($filters['country'])) {
                $sql .= " AND country = :country";
                $params[':country'] = $filters['country'];
            }

            // Filtre par représentant
            if (!empty($filters['rep_name'])) {
                $sql .= " AND rep_name = :rep_name";
                $params[':rep_name'] = $filters['rep_name'];
            }

            // Filtre par statut actif
            if (isset($filters['is_active'])) {
                $sql .= " AND is_active = :is_active";
                $params[':is_active'] = (int) $filters['is_active'];
            }

            // Filtre par statut bloqué
            if (isset($filters['is_blocked'])) {
                $sql .= " AND is_blocked = :is_blocked";
                $params[':is_blocked'] = (int) $filters['is_blocked'];
            }

            // Recherche textuelle
            if (!empty($search)) {
                $sql .= " AND (customer_number LIKE :search OR company_name LIKE :search OR email LIKE :search)";
                $params[':search'] = "%{$search}%";
            }

            // Tri par nom d'entreprise
            $sql .= " ORDER BY company_name ASC";

            // Limite et offset
            if ($limit > 0) {
                $sql .= " LIMIT :limit OFFSET :offset";
            }

            $stmt = $this->db->getPDO()->prepare($sql);

            // Bind des paramètres
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            if ($limit > 0) {
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            }

            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Customer::findAll() - Erreur : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer un client par son ID
     * 
     * @param int $id ID du client
     * @return array|null Données du client ou null si non trouvé
     */
    public function findById(int $id): ?array
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
            $stmt = $this->db->getPDO()->prepare($sql);
            $stmt->execute([':id' => $id]);

            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Customer::findById() - Erreur : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer un client par numéro client ET pays
     * CRITIQUE : Utilise la contrainte unique composite
     * 
     * @param string $customerNumber Numéro client
     * @param string $country Pays (BE ou LU)
     * @return array|null Données du client ou null si non trouvé
     */
    public function findByCustomerNumberAndCountry(string $customerNumber, string $country): ?array
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE customer_number = :customer_number AND country = :country LIMIT 1";
            $stmt = $this->db->getPDO()->prepare($sql);
            $stmt->execute([
                ':customer_number' => $customerNumber,
                ':country' => $country
            ]);

            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Customer::findByCustomerNumberAndCountry() - Erreur : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Créer un nouveau client
     * 
     * @param array $data Données du client
     * @return int|false ID du client créé ou false en cas d'erreur
     */
    public function create(array $data): int|false
    {
        try {
            // Valider les données
            $errors = $this->validate($data);
            if (!empty($errors)) {
                error_log("Customer::create() - Erreurs de validation : " . json_encode($errors));
                return false;
            }

            $sql = "INSERT INTO {$this->table} (
                customer_number, 
                email, 
                company_name, 
                country, 
                language, 
                rep_name, 
                rep_id, 
                is_active, 
                is_blocked
            ) VALUES (
                :customer_number, 
                :email, 
                :company_name, 
                :country, 
                :language, 
                :rep_name, 
                :rep_id, 
                :is_active, 
                :is_blocked
            )";

            $stmt = $this->db->getPDO()->prepare($sql);
            $result = $stmt->execute([
                ':customer_number' => $data['customer_number'],
                ':email' => $data['email'] ?? null,
                ':company_name' => $data['company_name'],
                ':country' => $data['country'],
                ':language' => $data['language'] ?? 'fr',
                ':rep_name' => $data['rep_name'] ?? null,
                ':rep_id' => $data['rep_id'] ?? null,
                ':is_active' => isset($data['is_active']) ? (int) $data['is_active'] : 1,
                ':is_blocked' => isset($data['is_blocked']) ? (int) $data['is_blocked'] : 0
            ]);

            if ($result) {
                return (int) $this->db->getPDO()->lastInsertId();
            }

            return false;
        } catch (PDOException $e) {
            error_log("Customer::create() - Erreur SQL : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mettre à jour un client
     * 
     * @param int $id ID du client
     * @param array $data Données à mettre à jour
     * @return bool True si succès, false sinon
     */
    public function update(int $id, array $data): bool
    {
        try {
            // Ajouter l'ID pour la validation (éviter les doublons)
            $data['id'] = $id;

            // Valider les données
            $errors = $this->validate($data);
            if (!empty($errors)) {
                error_log("Customer::update() - Erreurs de validation : " . json_encode($errors));
                return false;
            }

            $sql = "UPDATE {$this->table} SET 
                customer_number = :customer_number,
                email = :email,
                company_name = :company_name,
                country = :country,
                language = :language,
                rep_name = :rep_name,
                rep_id = :rep_id,
                is_active = :is_active,
                is_blocked = :is_blocked,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id";

            $stmt = $this->db->getPDO()->prepare($sql);
            return $stmt->execute([
                ':id' => $id,
                ':customer_number' => $data['customer_number'],
                ':email' => $data['email'] ?? null,
                ':company_name' => $data['company_name'],
                ':country' => $data['country'],
                ':language' => $data['language'] ?? 'fr',
                ':rep_name' => $data['rep_name'] ?? null,
                ':rep_id' => $data['rep_id'] ?? null,
                ':is_active' => isset($data['is_active']) ? (int) $data['is_active'] : 1,
                ':is_blocked' => isset($data['is_blocked']) ? (int) $data['is_blocked'] : 0
            ]);
        } catch (PDOException $e) {
            error_log("Customer::update() - Erreur SQL : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer un client
     * 
     * @param int $id ID du client
     * @return bool True si succès, false sinon
     */
    public function delete(int $id): bool
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE id = :id";
            $stmt = $this->db->getPDO()->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Customer::delete() - Erreur : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Valider les données d'un client
     * 
     * @param array $data Données à valider
     * @return array Tableau des erreurs (vide si pas d'erreur)
     */
    public function validate(array $data): array
    {
        $errors = [];

        // customer_number : requis, format flexible
        if (empty($data['customer_number'])) {
            $errors['customer_number'] = 'Le numéro client est requis';
        } elseif (!$this->isValidCustomerNumber($data['customer_number'])) {
            $errors['customer_number'] = 'Format de numéro client invalide (exemples valides : 123456, 123456-12, E12345-CB, *12345)';
        } else {
            // Vérifier l'unicité (customer_number + country)
            $existing = $this->findByCustomerNumberAndCountry($data['customer_number'], $data['country']);
            if ($existing && (!isset($data['id']) || $existing['id'] != $data['id'])) {
                $errors['customer_number'] = 'Ce numéro client existe déjà pour ce pays';
            }
        }

        // company_name : requis
        if (empty($data['company_name'])) {
            $errors['company_name'] = 'Le nom de l\'entreprise est requis';
        }

        // country : requis, BE ou LU
        if (empty($data['country'])) {
            $errors['country'] = 'Le pays est requis';
        } elseif (!in_array($data['country'], ['BE', 'LU'])) {
            $errors['country'] = 'Le pays doit être BE ou LU';
        }

        // email : optionnel, format valide si fourni (NON unique)
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format d\'email invalide';
        }

        // language : optionnel, fr ou nl
        if (!empty($data['language']) && !in_array($data['language'], ['fr', 'nl'])) {
            $errors['language'] = 'La langue doit être fr ou nl';
        }

        return $errors;
    }

    /**
     * Vérifier si le format du numéro client est valide
     * Formats acceptés : 123456, 123456-12, E12345-CB, *12345, etc.
     * 
     * @param string $customerNumber Numéro client à vérifier
     * @return bool True si valide, false sinon
     */
    private function isValidCustomerNumber(string $customerNumber): bool
    {
        // Format flexible : lettres (A-Z), chiffres (0-9), tiret (-), astérisque (*)
        // Minimum 4 caractères, maximum 20
        return preg_match('/^[A-Z0-9*-]{4,20}$/i', $customerNumber) === 1;
    }

    /**
     * Importer un client depuis la base externe trendyblog_sig
     * 
     * @param string $customerNumber Numéro client (CLL_NCLIXX)
     * @param string $country Pays (BE ou LU)
     * @return int|false ID du client importé ou false en cas d'erreur
     */
    public function importFromExternal(string $customerNumber, string $country): int|false
    {
        try {
            // Vérifier si le client existe déjà dans notre base
            $existing = $this->findByCustomerNumberAndCountry($customerNumber, $country);
            if ($existing) {
                error_log("Customer::importFromExternal() - Client déjà existant : {$customerNumber} ({$country})");
                return (int) $existing['id'];
            }

            // Récupérer les données depuis la DB externe
            $externalDb = ExternalDatabase::getInstance();
            $externalCustomer = $externalDb->getCustomer($customerNumber, $country);

            if (!$externalCustomer) {
                error_log("Customer::importFromExternal() - Client non trouvé dans la DB externe : {$customerNumber} ({$country})");
                return false;
            }

            // Préparer les données pour l'import
            $data = [
                'customer_number' => $externalCustomer['CLL_NCLIXX'],
                'company_name' => $externalCustomer['CLL_NOM'],
                'email' => null, // Pas d'email dans la DB externe
                'country' => $country,
                'language' => $country === 'BE' ? 'fr' : 'fr', // Par défaut français
                'rep_name' => null,
                'rep_id' => $externalCustomer['IDE_REP'] ?? null,
                'is_active' => 1,
                'is_blocked' => 0
            ];

            // Si on a un ID représentant, récupérer son nom
            if (!empty($data['rep_id'])) {
                $rep = $externalDb->getRepresentative($data['rep_id'], $country);
                if ($rep) {
                    $data['rep_name'] = trim(($rep['REP_PRENOM'] ?? '') . ' ' . ($rep['REP_NOM'] ?? ''));
                }
            }

            // Créer le client dans notre base
            $customerId = $this->create($data);

            if ($customerId) {
                error_log("Customer::importFromExternal() - Client importé avec succès : {$customerNumber} ({$country}) -> ID {$customerId}");
            }

            return $customerId;
        } catch (\Exception $e) {
            error_log("Customer::importFromExternal() - Erreur : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtenir les statistiques des clients
     * 
     * @return array Statistiques (total, par pays, par statut)
     */
    public function getStats(): array
    {
        try {
            $stats = [
                'total' => 0,
                'active' => 0,
                'blocked' => 0,
                'by_country' => [
                    'BE' => 0,
                    'LU' => 0
                ]
            ];

            // Total
            $sql = "SELECT COUNT(*) as total FROM {$this->table}";
            $result = $this->db->getPDO()->query($sql)->fetch();
            $stats['total'] = (int) $result['total'];

            // Actifs
            $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE is_active = 1";
            $result = $this->db->getPDO()->query($sql)->fetch();
            $stats['active'] = (int) $result['total'];

            // Bloqués
            $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE is_blocked = 1";
            $result = $this->db->getPDO()->query($sql)->fetch();
            $stats['blocked'] = (int) $result['total'];

            // Par pays
            $sql = "SELECT country, COUNT(*) as total FROM {$this->table} GROUP BY country";
            $results = $this->db->getPDO()->query($sql)->fetchAll();
            foreach ($results as $row) {
                $stats['by_country'][$row['country']] = (int) $row['total'];
            }

            return $stats;
        } catch (PDOException $e) {
            error_log("Customer::getStats() - Erreur : " . $e->getMessage());
            return [
                'total' => 0,
                'active' => 0,
                'blocked' => 0,
                'by_country' => ['BE' => 0, 'LU' => 0]
            ];
        }
    }

    /**
     * Obtenir tous les représentants uniques
     * 
     * @param string|null $country Filtrer par pays (optionnel)
     * @return array Liste des représentants
     */
    public function getRepresentatives(?string $country = null): array
    {
        try {
            $sql = "SELECT DISTINCT rep_name, rep_id FROM {$this->table} WHERE rep_name IS NOT NULL";

            if ($country) {
                $sql .= " AND country = :country";
            }

            $sql .= " ORDER BY rep_name ASC";

            if ($country) {
                $stmt = $this->db->getPDO()->prepare($sql);
                $stmt->execute([':country' => $country]);
                return $stmt->fetchAll();
            } else {
                return $this->db->getPDO()->query($sql)->fetchAll();
            }
        } catch (PDOException $e) {
            error_log("Customer::getRepresentatives() - Erreur : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Compter le nombre total de clients (avec filtres optionnels)
     * 
     * @param array $filters Filtres optionnels
     * @param string $search Recherche textuelle
     * @return int Nombre de clients
     */
    public function count(array $filters = [], string $search = ''): int
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE 1=1";
            $params = [];

            // Appliquer les mêmes filtres que findAll()
            if (!empty($filters['country'])) {
                $sql .= " AND country = :country";
                $params[':country'] = $filters['country'];
            }

            if (!empty($filters['rep_name'])) {
                $sql .= " AND rep_name = :rep_name";
                $params[':rep_name'] = $filters['rep_name'];
            }

            if (isset($filters['is_active'])) {
                $sql .= " AND is_active = :is_active";
                $params[':is_active'] = (int) $filters['is_active'];
            }

            if (isset($filters['is_blocked'])) {
                $sql .= " AND is_blocked = :is_blocked";
                $params[':is_blocked'] = (int) $filters['is_blocked'];
            }

            if (!empty($search)) {
                $sql .= " AND (customer_number LIKE :search OR company_name LIKE :search OR email LIKE :search)";
                $params[':search'] = "%{$search}%";
            }

            $stmt = $this->db->getPDO()->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();

            return (int) $result['total'];
        } catch (PDOException $e) {
            error_log("Customer::count() - Erreur : " . $e->getMessage());
            return 0;
        }
    }
}