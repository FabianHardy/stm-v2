<?php
/**
 * Model Category
 * Gestion des catégories de produits
 * 
 * @package STM/Models
 * @version 1.0.0
 * @created 11/11/2025 09:30
 * @modified 11/11/2025 09:30 - Création initiale
 */

namespace App\Models;

use Core\Database;
use PDO;

class Category
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Récupérer toutes les catégories triées par ordre d'affichage
     * 
     * @param array $filters Filtres optionnels (is_active)
     * @return array Liste des catégories
     * 
     * @created 11/11/2025 09:30
     */
    public function getAll(array $filters = []): array
    {
        $sql = "SELECT * FROM categories WHERE 1=1";
        $params = [];

        // Filtre par statut actif/inactif
        if (isset($filters['is_active'])) {
            $sql .= " AND is_active = :is_active";
            $params['is_active'] = $filters['is_active'];
        }

        // Tri par ordre d'affichage
        $sql .= " ORDER BY display_order ASC, name_fr ASC";

        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Récupérer une catégorie par son ID
     * 
     * @param int $id ID de la catégorie
     * @return array|false Données de la catégorie ou false
     * 
     * @created 11/11/2025 09:30
     */
    public function findById(int $id)
    {
        $sql = "SELECT * FROM categories WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    /**
     * Récupérer une catégorie par son code
     * 
     * @param string $code Code de la catégorie
     * @return array|false Données de la catégorie ou false
     * 
     * @created 11/11/2025 09:30
     */
    public function findByCode(string $code)
    {
        $sql = "SELECT * FROM categories WHERE code = :code";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':code', $code);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    /**
     * Créer une nouvelle catégorie
     * 
     * @param array $data Données de la catégorie
     * @return int|false ID de la catégorie créée ou false
     * 
     * @created 11/11/2025 09:30
     */
    public function create(array $data)
    {
        // Validation des données
        $errors = $this->validate($data);
        if (!empty($errors)) {
            return false;
        }

        $sql = "INSERT INTO categories (
                    code, name_fr, name_nl, color, icon_path, 
                    display_order, is_active
                ) VALUES (
                    :code, :name_fr, :name_nl, :color, :icon_path, 
                    :display_order, :is_active
                )";

        try {
            $stmt = $this->db->prepare($sql);
            
            $stmt->bindValue(':code', $data['code']);
            $stmt->bindValue(':name_fr', $data['name_fr']);
            $stmt->bindValue(':name_nl', $data['name_nl'] ?? '');
            $stmt->bindValue(':color', $data['color']);
            $stmt->bindValue(':icon_path', $data['icon_path'] ?? null);
            $stmt->bindValue(':display_order', $data['display_order'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':is_active', $data['is_active'] ?? 1, PDO::PARAM_INT);
            
            $stmt->execute();
            
            return (int) $this->db->lastInsertId();
        } catch (\PDOException $e) {
            error_log("Erreur création catégorie : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mettre à jour une catégorie
     * 
     * @param int $id ID de la catégorie
     * @param array $data Nouvelles données
     * @return bool Succès de la mise à jour
     * 
     * @created 11/11/2025 09:30
     */
    public function update(int $id, array $data): bool
    {
        // Validation des données
        $errors = $this->validate($data, $id);
        if (!empty($errors)) {
            return false;
        }

        $sql = "UPDATE categories SET
                    code = :code,
                    name_fr = :name_fr,
                    name_nl = :name_nl,
                    color = :color,
                    icon_path = :icon_path,
                    display_order = :display_order,
                    is_active = :is_active
                WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            
            $stmt->bindValue(':code', $data['code']);
            $stmt->bindValue(':name_fr', $data['name_fr']);
            $stmt->bindValue(':name_nl', $data['name_nl'] ?? '');
            $stmt->bindValue(':color', $data['color']);
            $stmt->bindValue(':icon_path', $data['icon_path'] ?? null);
            $stmt->bindValue(':display_order', $data['display_order'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':is_active', $data['is_active'] ?? 1, PDO::PARAM_INT);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Erreur mise à jour catégorie : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer une catégorie
     * 
     * @param int $id ID de la catégorie
     * @return bool Succès de la suppression
     * 
     * @created 11/11/2025 09:30
     */
    public function delete(int $id): bool
    {
        // Vérifier si des produits utilisent cette catégorie
        $sql = "SELECT COUNT(*) as count FROM products WHERE category_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();

        if ($result['count'] > 0) {
            error_log("Impossible de supprimer la catégorie $id : {$result['count']} produit(s) associé(s)");
            return false;
        }

        // Supprimer la catégorie
        $sql = "DELETE FROM categories WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Erreur suppression catégorie : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Valider les données d'une catégorie
     * 
     * @param array $data Données à valider
     * @param int|null $id ID de la catégorie (pour update)
     * @return array Tableau des erreurs
     * 
     * @created 11/11/2025 09:30
     */
    public function validate(array $data, ?int $id = null): array
    {
        $errors = [];

        // Code obligatoire
        if (empty($data['code'])) {
            $errors['code'] = 'Le code est obligatoire';
        } elseif (strlen($data['code']) > 50) {
            $errors['code'] = 'Le code ne doit pas dépasser 50 caractères';
        } else {
            // Vérifier l'unicité du code
            $existing = $this->findByCode($data['code']);
            if ($existing && (!$id || $existing['id'] != $id)) {
                $errors['code'] = 'Ce code est déjà utilisé';
            }
        }

        // Nom français obligatoire
        if (empty($data['name_fr'])) {
            $errors['name_fr'] = 'Le nom français est obligatoire';
        } elseif (strlen($data['name_fr']) > 255) {
            $errors['name_fr'] = 'Le nom français ne doit pas dépasser 255 caractères';
        }

        // Nom néerlandais optionnel mais avec limite
        if (isset($data['name_nl']) && strlen($data['name_nl']) > 255) {
            $errors['name_nl'] = 'Le nom néerlandais ne doit pas dépasser 255 caractères';
        }

        // Couleur obligatoire et format HEX
        if (empty($data['color'])) {
            $errors['color'] = 'La couleur est obligatoire';
        } elseif (!preg_match('/^#[0-9A-Fa-f]{6}$/', $data['color'])) {
            $errors['color'] = 'La couleur doit être au format hexadécimal (#RRGGBB)';
        }

        // Ordre d'affichage doit être un nombre positif
        if (isset($data['display_order']) && (!is_numeric($data['display_order']) || $data['display_order'] < 0)) {
            $errors['display_order'] = "L'ordre d'affichage doit être un nombre positif";
        }

        return $errors;
    }

    /**
     * Compter le nombre total de catégories
     * 
     * @param array $filters Filtres optionnels
     * @return int Nombre de catégories
     * 
     * @created 11/11/2025 09:30
     */
    public function count(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) as total FROM categories WHERE 1=1";
        $params = [];

        if (isset($filters['is_active'])) {
            $sql .= " AND is_active = :is_active";
            $params['is_active'] = $filters['is_active'];
        }

        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch();
        
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Mettre à jour l'ordre d'affichage de plusieurs catégories
     * 
     * @param array $orders Tableau [id => display_order]
     * @return bool Succès de la mise à jour
     * 
     * @created 11/11/2025 09:30
     */
    public function updateDisplayOrder(array $orders): bool
    {
        try {
            $this->db->beginTransaction();

            $sql = "UPDATE categories SET display_order = :display_order WHERE id = :id";
            $stmt = $this->db->prepare($sql);

            foreach ($orders as $id => $displayOrder) {
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                $stmt->bindValue(':display_order', $displayOrder, PDO::PARAM_INT);
                $stmt->execute();
            }

            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur mise à jour ordre catégories : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer les statistiques des catégories
     * 
     * @return array Statistiques
     * 
     * @created 11/11/2025 09:30
     */
    public function getStats(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive
                FROM categories";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch() ?: [
            'total' => 0,
            'active' => 0,
            'inactive' => 0
        ];
    }
}