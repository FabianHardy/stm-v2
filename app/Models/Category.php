<?php
/**
 * Model : Category
 * 
 * Gestion des catégories de produits
 * 
 * @created 11/11/2025
 * @modified 11/11/2025 21:00 - Ajout méthode isUsedByProducts()
 */

namespace App\Models;

use Core\Database;

class Category
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Récupérer toutes les catégories avec filtres
     * 
     * @param array $filters Filtres de recherche
     * @return array
     */
    public function getAll(array $filters = []): array
    {
        $query = "SELECT * FROM categories WHERE 1=1";
        $params = [];

        // Filtre par recherche
        if (!empty($filters['search'])) {
            $query .= " AND (name_fr LIKE :search OR name_nl LIKE :search OR code LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        // Filtre par statut
        if (isset($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query .= " AND is_active = 1";
            } elseif ($filters['status'] === 'inactive') {
                $query .= " AND is_active = 0";
            }
        }

        $query .= " ORDER BY display_order ASC, name_fr ASC";

        return $this->db->query($query, $params);
    }

    /**
     * Récupérer une catégorie par ID
     * 
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $query = "SELECT * FROM categories WHERE id = :id";
        $result = $this->db->query($query, [':id' => $id]);
        
        return $result[0] ?? null;
    }

    /**
     * Récupérer une catégorie par code
     * 
     * @param string $code
     * @return array|null
     */
    public function findByCode(string $code): ?array
    {
        $query = "SELECT * FROM categories WHERE code = :code";
        $result = $this->db->query($query, [':code' => $code]);
        
        return $result[0] ?? null;
    }

    /**
     * Créer une nouvelle catégorie
     * 
     * @param array $data
     * @return int|false ID de la catégorie créée ou false
     */
    public function create(array $data)
    {
        $query = "INSERT INTO categories (
                    code, name_fr, name_nl, color, 
                    icon_path, display_order, is_active
                  ) VALUES (
                    :code, :name_fr, :name_nl, :color,
                    :icon_path, :display_order, :is_active
                  )";

        $params = [
            ':code' => $data['code'],
            ':name_fr' => $data['name_fr'],
            ':name_nl' => $data['name_nl'] ?? $data['name_fr'],
            ':color' => $data['color'],
            ':icon_path' => $data['icon_path'] ?? null,
            ':display_order' => $data['display_order'] ?? 0,
            ':is_active' => $data['is_active'] ?? 1
        ];

        try {
            $this->db->execute($query, $params);
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            error_log("Erreur création catégorie: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mettre à jour une catégorie
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $query = "UPDATE categories SET
                    code = :code,
                    name_fr = :name_fr,
                    name_nl = :name_nl,
                    color = :color,
                    icon_path = :icon_path,
                    display_order = :display_order,
                    is_active = :is_active
                  WHERE id = :id";

        $params = [
            ':id' => $id,
            ':code' => $data['code'],
            ':name_fr' => $data['name_fr'],
            ':name_nl' => $data['name_nl'] ?? $data['name_fr'],
            ':color' => $data['color'],
            ':icon_path' => $data['icon_path'] ?? null,
            ':display_order' => $data['display_order'] ?? 0,
            ':is_active' => $data['is_active'] ?? 1
        ];

        try {
            return $this->db->execute($query, $params);
        } catch (\PDOException $e) {
            error_log("Erreur mise à jour catégorie: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer une catégorie
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $query = "DELETE FROM categories WHERE id = :id";
        
        try {
            return $this->db->execute($query, [':id' => $id]);
        } catch (\PDOException $e) {
            error_log("Erreur suppression catégorie: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifier si la catégorie est utilisée par des produits
     * 
     * @param int $id ID de la catégorie
     * @return bool True si utilisée, false sinon
     * @created 11/11/2025 21:00
     */
    public function isUsedByProducts(int $id): bool
    {
        $query = "SELECT COUNT(*) as count FROM products WHERE category_id = :category_id";
        $result = $this->db->query($query, [':category_id' => $id]);
        
        return ($result[0]['count'] ?? 0) > 0;
    }

    /**
     * Valider les données d'une catégorie
     * 
     * @param array $data
     * @param int|null $id ID pour update (null pour create)
     * @return array Tableau d'erreurs (vide si valide)
     */
    public function validate(array $data, ?int $id = null): array
    {
        $errors = [];

        // Code obligatoire
        if (empty($data['code'])) {
            $errors['code'] = 'Le code est obligatoire';
        } else {
            // Vérifier unicité du code
            $existing = $this->findByCode($data['code']);
            if ($existing && (!$id || $existing['id'] != $id)) {
                $errors['code'] = 'Ce code existe déjà';
            }
        }

        // Nom FR obligatoire
        if (empty($data['name_fr'])) {
            $errors['name_fr'] = 'Le nom français est obligatoire';
        }

        // Couleur au format HEX
        if (!empty($data['color'])) {
            if (!preg_match('/^#[A-Fa-f0-9]{6}$/', $data['color'])) {
                $errors['color'] = 'La couleur doit être au format HEX (#RRGGBB)';
            }
        } else {
            $errors['color'] = 'La couleur est obligatoire';
        }

        // Ordre d'affichage positif
        if (isset($data['display_order']) && $data['display_order'] < 0) {
            $errors['display_order'] = "L'ordre d'affichage doit être positif";
        }

        return $errors;
    }

    /**
     * Compter les catégories
     * 
     * @return int
     */
    public function count(): int
    {
        $query = "SELECT COUNT(*) as count FROM categories";
        $result = $this->db->query($query);
        
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
            'total' => $this->count(),
            'active' => 0,
            'inactive' => 0
        ];

        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(is_active) as active
                  FROM categories";
        
        $result = $this->db->query($query);
        
        if (!empty($result)) {
            $stats['total'] = (int)$result[0]['total'];
            $stats['active'] = (int)$result[0]['active'];
            $stats['inactive'] = $stats['total'] - $stats['active'];
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
        $query = "UPDATE categories SET display_order = :order WHERE id = :id";
        
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