<?php
/**
 * Model Product (renommé en Promotion dans l'interface)
 * Gestion des promotions par campagne
 * 
 * @package STM/Models
 * @version 2.0.0
 * @created 11/11/2025
 */

namespace App\Models;

use Core\Database;

class Product
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Récupérer toutes les promotions avec filtres
     * 
     * @param array $filters Filtres optionnels
     * @return array
     */
    public function getAll(array $filters = []): array
    {
        $sql = "SELECT p.*, c.name as campaign_name, c.country as campaign_country
                FROM products p
                LEFT JOIN campaigns c ON p.campaign_id = c.id
                WHERE 1=1";
        
        $params = [];

        // Filtre par campagne
        if (!empty($filters['campaign_id'])) {
            $sql .= " AND p.campaign_id = :campaign_id";
            $params[':campaign_id'] = $filters['campaign_id'];
        }

        // Filtre par recherche
        if (!empty($filters['search'])) {
            $sql .= " AND (p.code_article LIKE :search OR p.title_fr LIKE :search OR p.title_nl LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        // Filtre par statut
        if (isset($filters['is_active'])) {
            $sql .= " AND p.is_active = :is_active";
            $params[':is_active'] = $filters['is_active'];
        }

        $sql .= " ORDER BY p.created_at DESC";

        return $this->db->query($sql, $params);
    }

    /**
     * Récupérer une promotion par son ID
     * 
     * @param int $id ID de la promotion
     * @return array|false
     */
    public function findById(int $id): array|false
    {
        $sql = "SELECT p.*, c.name as campaign_name, c.country as campaign_country
                FROM products p
                LEFT JOIN campaigns c ON p.campaign_id = c.id
                WHERE p.id = :id";
        
        $result = $this->db->queryOne($sql, [':id' => $id]);
        
        return $result ?: false;
    }

    /**
     * Récupérer une promotion par son code article
     * 
     * @param string $code Code article
     * @return array|false
     */
    public function findByCode(string $code): array|false
    {
        $sql = "SELECT * FROM products WHERE code_article = :code";
        $result = $this->db->queryOne($sql, [':code' => $code]);
        
        return $result ?: false;
    }

    /**
     * Créer une nouvelle promotion
     * 
     * @param array $data Données de la promotion
     * @return int|false ID de la promotion créée ou false
     */
    public function create(array $data): int|false
    {
        $sql = "INSERT INTO products (
                    code_article, 
                    campaign_id,
                    title_fr, 
                    title_nl,
                    name_fr,
                    name_nl,
                    description_fr,
                    description_nl,
                    image_fr,
                    image_nl,
                    is_active
                ) VALUES (
                    :code_article,
                    :campaign_id,
                    :title_fr,
                    :title_nl,
                    :name_fr,
                    :name_nl,
                    :description_fr,
                    :description_nl,
                    :image_fr,
                    :image_nl,
                    :is_active
                )";

        $params = [
            ':code_article' => $data['code_article'],
            ':campaign_id' => $data['campaign_id'],
            ':title_fr' => $data['title_fr'] ?? $data['name_fr'] ?? null,
            ':title_nl' => $data['title_nl'] ?? $data['name_nl'] ?? null,
            ':name_fr' => $data['name_fr'] ?? $data['title_fr'] ?? null,
            ':name_nl' => $data['name_nl'] ?? $data['title_nl'] ?? null,
            ':description_fr' => $data['description_fr'] ?? null,
            ':description_nl' => $data['description_nl'] ?? null,
            ':image_fr' => $data['image_fr'] ?? null,
            ':image_nl' => $data['image_nl'] ?? null,
            ':is_active' => $data['is_active'] ?? 1,
        ];

        if ($this->db->execute($sql, $params)) {
            return (int) $this->db->lastInsertId();
        }
        
        return false;
    }

    /**
     * Mettre à jour une promotion
     * 
     * @param int $id ID de la promotion
     * @param array $data Nouvelles données
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE products SET
                    code_article = :code_article,
                    campaign_id = :campaign_id,
                    title_fr = :title_fr,
                    title_nl = :title_nl,
                    name_fr = :name_fr,
                    name_nl = :name_nl,
                    description_fr = :description_fr,
                    description_nl = :description_nl,
                    image_fr = :image_fr,
                    image_nl = :image_nl,
                    is_active = :is_active
                WHERE id = :id";

        $params = [
            ':id' => $id,
            ':code_article' => $data['code_article'],
            ':campaign_id' => $data['campaign_id'],
            ':title_fr' => $data['title_fr'] ?? $data['name_fr'] ?? null,
            ':title_nl' => $data['title_nl'] ?? $data['name_nl'] ?? null,
            ':name_fr' => $data['name_fr'] ?? $data['title_fr'] ?? null,
            ':name_nl' => $data['name_nl'] ?? $data['title_nl'] ?? null,
            ':description_fr' => $data['description_fr'] ?? null,
            ':description_nl' => $data['description_nl'] ?? null,
            ':image_fr' => $data['image_fr'] ?? null,
            ':image_nl' => $data['image_nl'] ?? null,
            ':is_active' => $data['is_active'] ?? 1,
        ];

        return $this->db->execute($sql, $params);
    }

    /**
     * Supprimer une promotion
     * 
     * @param int $id ID de la promotion
     * @return bool
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM products WHERE id = :id";
        
        return $this->db->execute($sql, [':id' => $id]);
    }

    /**
     * Récupérer les promotions actives
     * 
     * @return array
     */
    public function getActive(): array
    {
        $sql = "SELECT p.*, c.name as campaign_name
                FROM products p
                LEFT JOIN campaigns c ON p.campaign_id = c.id
                WHERE p.is_active = 1
                ORDER BY p.created_at DESC";
        
        return $this->db->query($sql);
    }

    /**
     * Récupérer les promotions d'une campagne
     * 
     * @param int $campaignId ID de la campagne
     * @return array
     */
    public function getByCampaign(int $campaignId): array
    {
        $sql = "SELECT * FROM products 
                WHERE campaign_id = :campaign_id 
                ORDER BY created_at DESC";
        
        return $this->db->query($sql, [':campaign_id' => $campaignId]);
    }

    /**
     * Compter les promotions
     * 
     * @param array $filters Filtres optionnels
     * @return int
     */
    public function count(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) as total FROM products WHERE 1=1";
        $params = [];

        if (!empty($filters['campaign_id'])) {
            $sql .= " AND campaign_id = :campaign_id";
            $params[':campaign_id'] = $filters['campaign_id'];
        }

        if (isset($filters['is_active'])) {
            $sql .= " AND is_active = :is_active";
            $params[':is_active'] = $filters['is_active'];
        }

        $result = $this->db->query($sql, $params);
        
        return isset($result[0]['total']) ? (int) $result[0]['total'] : 0;
    }

    /**
     * Valider les données d'une promotion
     * 
     * @param array $data Données à valider
     * @return array Tableau des erreurs (vide si OK)
     */
    public function validate(array $data): array
    {
        $errors = [];

        // Code article obligatoire
        if (empty($data['code_article'])) {
            $errors['code_article'] = 'Le code article est obligatoire';
        } else {
            // Vérifier l'unicité (sauf si update avec même code)
            $existing = $this->findByCode($data['code_article']);
            if ($existing && (!isset($data['id']) || $existing['id'] != $data['id'])) {
                $errors['code_article'] = 'Ce code article existe déjà';
            }
        }

        // Campagne obligatoire
        if (empty($data['campaign_id'])) {
            $errors['campaign_id'] = 'La campagne est obligatoire';
        }

        // Titre FR OU name_fr obligatoire
        if (empty($data['title_fr']) && empty($data['name_fr'])) {
            $errors['title_fr'] = 'Le titre en français est obligatoire';
        }

        return $errors;
    }

    /**
     * Récupérer les statistiques des promotions
     * 
     * @return array
     */
    public function getStats(): array
    {
        $stats = [
            'total' => 0,
            'active' => 0,
            'inactive' => 0,
        ];

        // Total
        $sql = "SELECT COUNT(*) as count FROM products";
        $result = $this->db->query($sql);
        $stats['total'] = isset($result[0]['count']) ? (int) $result[0]['count'] : 0;

        // Actives
        $sql = "SELECT COUNT(*) as count FROM products WHERE is_active = 1";
        $result = $this->db->query($sql);
        $stats['active'] = isset($result[0]['count']) ? (int) $result[0]['count'] : 0;

        // Inactives
        $sql = "SELECT COUNT(*) as count FROM products WHERE is_active = 0";
        $result = $this->db->query($sql);
        $stats['inactive'] = isset($result[0]['count']) ? (int) $result[0]['count'] : 0;

        return $stats;
    }
}