<?php
/**
 * Model : Product
 * 
 * Gestion des produits
 * 
 * @created 11/11/2025 21:30
 */

namespace App\Models;

use Core\Database;

class Product
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Récupérer tous les produits avec filtres
     * 
     * @param array $filters Filtres de recherche
     * @return array
     */
    public function getAll(array $filters = []): array
    {
        $query = "SELECT p.*, c.name_fr as category_name, c.color as category_color 
                  FROM products p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE 1=1";
        $params = [];

        // Filtre par recherche
        if (!empty($filters['search'])) {
            $query .= " AND (p.name_fr LIKE :search OR p.name_nl LIKE :search 
                        OR p.product_code LIKE :search OR p.package_number LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        // Filtre par catégorie
        if (!empty($filters['category_id'])) {
            $query .= " AND p.category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }

        // Filtre par statut
        if (isset($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query .= " AND p.is_active = 1";
            } elseif ($filters['status'] === 'inactive') {
                $query .= " AND p.is_active = 0";
            }
        }

        $query .= " ORDER BY p.display_order ASC, p.name_fr ASC";

        return $this->db->query($query, $params);
    }

    /**
     * Récupérer un produit par ID
     * 
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $query = "SELECT p.*, c.name_fr as category_name, c.color as category_color 
                  FROM products p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.id = :id";
        $result = $this->db->query($query, [':id' => $id]);
        
        return $result[0] ?? null;
    }

    /**
     * Récupérer un produit par code
     * 
     * @param string $code
     * @return array|null
     */
    public function findByCode(string $code): ?array
    {
        $query = "SELECT * FROM products WHERE product_code = :code";
        $result = $this->db->query($query, [':code' => $code]);
        
        return $result[0] ?? null;
    }

    /**
     * Récupérer les produits d'une catégorie
     * 
     * @param int $categoryId
     * @return array
     */
    public function getByCategory(int $categoryId): array
    {
        $query = "SELECT * FROM products 
                  WHERE category_id = :category_id 
                  AND is_active = 1
                  ORDER BY display_order ASC, name_fr ASC";
        
        return $this->db->query($query, [':category_id' => $categoryId]);
    }

    /**
     * Créer un nouveau produit
     * 
     * @param array $data
     * @return int|false ID du produit créé ou false
     */
    public function create(array $data)
    {
        $query = "INSERT INTO products (
                    product_code, package_number, ean, category_id,
                    name_fr, name_nl, description_fr, description_nl,
                    image_fr, image_nl, display_order, is_active
                  ) VALUES (
                    :product_code, :package_number, :ean, :category_id,
                    :name_fr, :name_nl, :description_fr, :description_nl,
                    :image_fr, :image_nl, :display_order, :is_active
                  )";

        $params = [
            ':product_code' => $data['product_code'],
            ':package_number' => $data['package_number'],
            ':ean' => $data['ean'] ?? null,
            ':category_id' => $data['category_id'] ?? null,
            ':name_fr' => $data['name_fr'],
            ':name_nl' => $data['name_nl'] ?? $data['name_fr'],
            ':description_fr' => $data['description_fr'] ?? null,
            ':description_nl' => $data['description_nl'] ?? null,
            ':image_fr' => $data['image_fr'] ?? null,
            ':image_nl' => $data['image_nl'] ?? null,
            ':display_order' => $data['display_order'] ?? 0,
            ':is_active' => $data['is_active'] ?? 1
        ];

        try {
            $this->db->execute($query, $params);
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            error_log("Erreur création produit: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mettre à jour un produit
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $query = "UPDATE products SET
                    product_code = :product_code,
                    package_number = :package_number,
                    ean = :ean,
                    category_id = :category_id,
                    name_fr = :name_fr,
                    name_nl = :name_nl,
                    description_fr = :description_fr,
                    description_nl = :description_nl,
                    image_fr = :image_fr,
                    image_nl = :image_nl,
                    display_order = :display_order,
                    is_active = :is_active,
                    updated_at = NOW()
                  WHERE id = :id";

        $params = [
            ':id' => $id,
            ':product_code' => $data['product_code'],
            ':package_number' => $data['package_number'],
            ':ean' => $data['ean'] ?? null,
            ':category_id' => $data['category_id'] ?? null,
            ':name_fr' => $data['name_fr'],
            ':name_nl' => $data['name_nl'] ?? $data['name_fr'],
            ':description_fr' => $data['description_fr'] ?? null,
            ':description_nl' => $data['description_nl'] ?? null,
            ':image_fr' => $data['image_fr'] ?? null,
            ':image_nl' => $data['image_nl'] ?? null,
            ':display_order' => $data['display_order'] ?? 0,
            ':is_active' => $data['is_active'] ?? 1
        ];

        try {
            return $this->db->execute($query, $params);
        } catch (\PDOException $e) {
            error_log("Erreur mise à jour produit: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer un produit
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $query = "DELETE FROM products WHERE id = :id";
        
        try {
            return $this->db->execute($query, [':id' => $id]);
        } catch (\PDOException $e) {
            error_log("Erreur suppression produit: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifier si le produit est utilisé dans des campagnes
     * 
     * @param int $id ID du produit
     * @return bool True si utilisé, false sinon
     * @created 11/11/2025 21:30
     */
    public function isUsedByCampaigns(int $id): bool
    {
        $query = "SELECT COUNT(*) as count FROM campaign_products WHERE product_id = :product_id";
        $result = $this->db->query($query, [':product_id' => $id]);
        
        return ($result[0]['count'] ?? 0) > 0;
    }

    /**
     * Valider les données d'un produit
     * 
     * @param array $data
     * @param int|null $id ID pour update (null pour create)
     * @return array Tableau d'erreurs (vide si valide)
     */
    public function validate(array $data, ?int $id = null): array
    {
        $errors = [];

        // Code produit obligatoire
        if (empty($data['product_code'])) {
            $errors['product_code'] = 'Le code produit est obligatoire';
        } else {
            // Vérifier unicité du code
            $existing = $this->findByCode($data['product_code']);
            if ($existing && (!$id || $existing['id'] != $id)) {
                $errors['product_code'] = 'Ce code produit existe déjà';
            }
        }

        // Numéro de colis obligatoire
        if (empty($data['package_number'])) {
            $errors['package_number'] = 'Le numéro de colis est obligatoire';
        }

        // Nom FR obligatoire
        if (empty($data['name_fr'])) {
            $errors['name_fr'] = 'Le nom français est obligatoire';
        }

        // EAN : 13 chiffres si renseigné
        if (!empty($data['ean']) && !preg_match('/^\d{13}$/', $data['ean'])) {
            $errors['ean'] = 'Le code EAN doit contenir exactement 13 chiffres';
        }

        // Ordre d'affichage positif
        if (isset($data['display_order']) && $data['display_order'] < 0) {
            $errors['display_order'] = "L'ordre d'affichage doit être positif";
        }

        return $errors;
    }

    /**
     * Compter les produits
     * 
     * @param array $filters Filtres optionnels
     * @return int
     */
    public function count(array $filters = []): int
    {
        $query = "SELECT COUNT(*) as count FROM products WHERE 1=1";
        $params = [];

        if (!empty($filters['category_id'])) {
            $query .= " AND category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }

        if (isset($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query .= " AND is_active = 1";
            } elseif ($filters['status'] === 'inactive') {
                $query .= " AND is_active = 0";
            }
        }

        $result = $this->db->query($query, $params);
        
        return (int)($result[0]['count'] ?? 0);
    }

    /**
     * Obtenir les statistiques
     * 
     * @return array
     */
    public function getStats(): array
    {
        $stats = [
            'total' => 0,
            'active' => 0,
            'inactive' => 0,
            'with_category' => 0,
            'without_category' => 0
        ];

        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(is_active) as active,
                    SUM(CASE WHEN category_id IS NOT NULL THEN 1 ELSE 0 END) as with_category
                  FROM products";
        
        $result = $this->db->query($query);
        
        if (!empty($result)) {
            $stats['total'] = (int)$result[0]['total'];
            $stats['active'] = (int)$result[0]['active'];
            $stats['inactive'] = $stats['total'] - $stats['active'];
            $stats['with_category'] = (int)$result[0]['with_category'];
            $stats['without_category'] = $stats['total'] - $stats['with_category'];
        }

        return $stats;
    }

    /**
     * Mettre à jour l'ordre d'affichage
     * 
     * @param int $id
     * @param int $order
     * @return bool
     */
    public function updateDisplayOrder(int $id, int $order): bool
    {
        $query = "UPDATE products SET display_order = :order WHERE id = :id";
        
        try {
            return $this->db->execute($query, [
                ':id' => $id,
                ':order' => $order
            ]);
        } catch (\PDOException $e) {
            error_log("Erreur mise à jour ordre: " . $e->getMessage());
            return false;
        }
    }
}
