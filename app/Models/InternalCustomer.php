<?php
/**
 * InternalCustomer Model
 *
 * Gestion des comptes internes auto-ajoutés aux campagnes manual
 *
 * @created  2025/12/03 14:00
 */

namespace App\Models;

use Core\Database;

class InternalCustomer
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Récupérer tous les comptes internes
     *
     * @param array $filters Filtres optionnels (country, active)
     * @return array
     */
    public function getAll(array $filters = []): array
    {
        $query = "SELECT * FROM internal_customers WHERE 1=1";
        $params = [];

        // Filtre par pays
        if (!empty($filters["country"])) {
            $query .= " AND country = :country";
            $params[":country"] = $filters["country"];
        }

        // Filtre par statut actif
        if (isset($filters["is_active"])) {
            $query .= " AND is_active = :is_active";
            $params[":is_active"] = $filters["is_active"];
        }

        $query .= " ORDER BY country ASC, customer_number ASC";

        try {
            return $this->db->query($query, $params);
        } catch (\PDOException $e) {
            error_log("Erreur InternalCustomer::getAll: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les comptes internes actifs par pays
     *
     * @param string $country Code pays (BE ou LU)
     * @return array Liste des numéros clients
     */
    public function getActiveByCountry(string $country): array
    {
        $query = "SELECT customer_number FROM internal_customers
                  WHERE country = :country AND is_active = 1
                  ORDER BY customer_number ASC";

        try {
            $results = $this->db->query($query, [":country" => $country]);
            return array_column($results, "customer_number");
        } catch (\PDOException $e) {
            error_log("Erreur InternalCustomer::getActiveByCountry: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer un compte interne par ID
     *
     * @param int $id
     * @return array|false
     */
    public function findById(int $id): array|false
    {
        $query = "SELECT * FROM internal_customers WHERE id = :id";

        try {
            return $this->db->queryOne($query, [":id" => $id]);
        } catch (\PDOException $e) {
            error_log("Erreur InternalCustomer::findById: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifier si un compte existe déjà
     *
     * @param string $customerNumber
     * @param string $country
     * @param int|null $excludeId ID à exclure (pour update)
     * @return bool
     */
    public function exists(string $customerNumber, string $country, ?int $excludeId = null): bool
    {
        $query = "SELECT COUNT(*) as count FROM internal_customers
                  WHERE customer_number = :customer_number AND country = :country";
        $params = [
            ":customer_number" => $customerNumber,
            ":country" => $country,
        ];

        if ($excludeId) {
            $query .= " AND id != :exclude_id";
            $params[":exclude_id"] = $excludeId;
        }

        try {
            $result = $this->db->queryOne($query, $params);
            return ($result["count"] ?? 0) > 0;
        } catch (\PDOException $e) {
            error_log("Erreur InternalCustomer::exists: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Créer un nouveau compte interne
     *
     * @param array $data
     * @return int ID créé ou 0 si erreur
     */
    public function create(array $data): int
    {
        $query = "INSERT INTO internal_customers
                  (customer_number, country, description, is_active, created_at)
                  VALUES (:customer_number, :country, :description, :is_active, NOW())";

        $params = [
            ":customer_number" => trim($data["customer_number"]),
            ":country" => $data["country"],
            ":description" => $data["description"] ?? null,
            ":is_active" => $data["is_active"] ?? 1,
        ];

        try {
            $this->db->execute($query, $params);
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            error_log("Erreur InternalCustomer::create: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Mettre à jour un compte interne
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $query = "UPDATE internal_customers SET
                  customer_number = :customer_number,
                  country = :country,
                  description = :description,
                  is_active = :is_active,
                  updated_at = NOW()
                  WHERE id = :id";

        $params = [
            ":id" => $id,
            ":customer_number" => trim($data["customer_number"]),
            ":country" => $data["country"],
            ":description" => $data["description"] ?? null,
            ":is_active" => $data["is_active"] ?? 1,
        ];

        try {
            return $this->db->execute($query, $params);
        } catch (\PDOException $e) {
            error_log("Erreur InternalCustomer::update: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer un compte interne
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $query = "DELETE FROM internal_customers WHERE id = :id";

        try {
            return $this->db->execute($query, [":id" => $id]);
        } catch (\PDOException $e) {
            error_log("Erreur InternalCustomer::delete: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Activer/Désactiver un compte
     *
     * @param int $id
     * @param bool $active
     * @return bool
     */
    public function toggleActive(int $id, bool $active): bool
    {
        $query = "UPDATE internal_customers SET is_active = :is_active, updated_at = NOW() WHERE id = :id";

        try {
            return $this->db->execute($query, [":id" => $id, ":is_active" => $active ? 1 : 0]);
        } catch (\PDOException $e) {
            error_log("Erreur InternalCustomer::toggleActive: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Compter les comptes par pays
     *
     * @return array ['BE' => int, 'LU' => int, 'total' => int]
     */
    public function countByCountry(): array
    {
        try {
            $be = $this->db->queryOne(
                "SELECT COUNT(*) as c FROM internal_customers WHERE country = 'BE' AND is_active = 1",
            );
            $lu = $this->db->queryOne(
                "SELECT COUNT(*) as c FROM internal_customers WHERE country = 'LU' AND is_active = 1",
            );
            $total = $this->db->queryOne("SELECT COUNT(*) as c FROM internal_customers WHERE is_active = 1");

            return [
                "BE" => (int) ($be["c"] ?? 0),
                "LU" => (int) ($lu["c"] ?? 0),
                "total" => (int) ($total["c"] ?? 0),
            ];
        } catch (\PDOException $e) {
            error_log("Erreur InternalCustomer::countByCountry: " . $e->getMessage());
            return ["BE" => 0, "LU" => 0, "total" => 0];
        }
    }

    /**
     * Valider les données d'un compte interne
     *
     * @param array $data
     * @param int|null $excludeId Pour update
     * @return array Erreurs de validation
     */
    public function validate(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        // Numéro client requis
        if (empty($data["customer_number"])) {
            $errors["customer_number"] = "Le numéro client est requis";
        }

        // Pays requis et valide
        if (empty($data["country"])) {
            $errors["country"] = "Le pays est requis";
        } elseif (!in_array($data["country"], ["BE", "LU"])) {
            $errors["country"] = "Le pays doit être BE ou LU";
        }

        // Vérifier unicité
        if (!empty($data["customer_number"]) && !empty($data["country"])) {
            if ($this->exists($data["customer_number"], $data["country"], $excludeId)) {
                $errors["customer_number"] = "Ce numéro client existe déjà pour ce pays";
            }
        }

        return $errors;
    }
}
