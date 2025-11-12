<?php
/**
 * Model Product (renommé en Promotion dans l'interface)
 * Gestion des promotions par campagne
 * 
 * @package STM/Models
 * @version 2.2.0
 * @created 11/11/2025
 * @modified 12/11/2025 17:30 - Ajout quotas (max_total, max_per_customer)
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
        $sql = "SELECT p.*, 
                       c.name as campaign_name, 
                       c.country as campaign_country,
                       cat.name_fr as category_name
                FROM products p
                LEFT JOIN campaigns c ON p.campaign_id = c.id
                LEFT JOIN categories cat ON p.category_id = cat.id
                WHERE 1=1";
        
        $params = [];

        // Filtre par campagne
        if (!empty($filters['campaign_id'])) {
            $sql .= " AND p.campaign_id = :campaign_id";
            $params[':campaign_id'] = $filters['campaign_id'];
        }

        // Filtre par catégorie
        if (!empty($filters['category'])) {
            $sql .= " AND p.category_id = :category";
            $params[':category'] = $filters['category'];
        }

        // Filtre par recherche
        if (!empty($filters['search'])) {
            $sql .= " AND (p.product_code LIKE :search OR p.name_fr LIKE :search OR p.name_nl LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        // Filtre par statut
        if (isset($filters['is_active'])) {
            $sql .= " AND p.is_active = :is_active";
            $params[':is_active'] = $filters['is_active'];
        }

        // Filtre par statut textuel
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'active') {
                $sql .= " AND p.is_active = 1";
            } elseif ($filters['status'] === 'inactive') {
                $sql .= " AND p.is_active = 0";
            }
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
        $sql = "SELECT p.*, 
                       c.name as campaign_name, 
                       c.country as campaign_country,
                       cat.name_fr as category_name
                FROM products p
                LEFT JOIN campaigns c ON p.campaign_id = c.id
                LEFT JOIN categories cat ON p.category_id = cat.id
                WHERE p.id = :id";
        
        $result = $this->db->queryOne($sql, [':id' => $id]);
        
        return $result ?: false;
    }

    /**
     * ALIAS : find() → findById() pour compatibilité
     * 
     * @param int $id ID de la promotion
     * @return array|false
     */
    public function find(int $id): array|false
    {
        return $this->findById($id);
    }

    /**
     * Récupérer une promotion par son code produit
     * 
     * @param string $code Code produit
     * @return array|false
     */
    public function findByCode(string $code): array|false
    {
        $sql = "SELECT * FROM products WHERE product_code = :code";
        $result = $this->db->queryOne($sql, [':code' => $code]);
        
        return $result ?: false;
    }

    /**
     * Créer une nouvelle promotion
     * 
     * @param array $data Données de la promotion
     * @return int|false ID de la promotion créée ou false
     * @modified 12/11/2025 17:30 - Ajout max_total et max_per_customer
     */
    public function create(array $data): int|false
    {
        $sql = "INSERT INTO products (
                    campaign_id,
                    category_id,
                    product_code, 
                    name_fr, 
                    name_nl,
                    description_fr,
                    description_nl,
                    image_fr,
                    image_nl,
                    display_order,
                    max_total,
                    max_per_customer,
                    is_active
                ) VALUES (
                    :campaign_id,
                    :category_id,
                    :product_code,
                    :name_fr,
                    :name_nl,
                    :description_fr,
                    :description_nl,
                    :image_fr,
                    :image_nl,
                    :display_order,
                    :max_total,
                    :max_per_customer,
                    :is_active
                )";

        $params = [
            ':campaign_id' => $data['campaign_id'] ?? null,
            ':category_id' => $data['category_id'] ?? null,
            ':product_code' => $data['product_code'] ?? '',
            ':name_fr' => $data['name_fr'] ?? '',
            ':name_nl' => $data['name_nl'] ?? '',
            ':description_fr' => $data['description_fr'] ?? null,
            ':description_nl' => $data['description_nl'] ?? null,
            ':image_fr' => $data['image_fr'] ?? null,
            ':image_nl' => $data['image_nl'] ?? null,
            ':display_order' => $data['display_order'] ?? 0,
            ':max_total' => !empty($data['max_total']) ? (int)$data['max_total'] : null,
            ':max_per_customer' => !empty($data['max_per_customer']) ? (int)$data['max_per_customer'] : null,
            ':is_active' => $data['is_active'] ?? 1,
        ];

        try {
            if ($this->db->execute($sql, $params)) {
                return (int) $this->db->lastInsertId();
            }
            return false;
        } catch (\PDOException $e) {
            error_log("Product::create() - SQL Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mettre à jour une promotion
     * 
     * @param int $id ID de la promotion
     * @param array $data Nouvelles données
     * @return bool
     * @modified 12/11/2025 17:30 - Ajout max_total et max_per_customer
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE products SET
                    campaign_id = :campaign_id,
                    category_id = :category_id,
                    product_code = :product_code,
                    name_fr = :name_fr,
                    name_nl = :name_nl,
                    description_fr = :description_fr,
                    description_nl = :description_nl,
                    image_fr = :image_fr,
                    image_nl = :image_nl,
                    display_order = :display_order,
                    max_total = :max_total,
                    max_per_customer = :max_per_customer,
                    is_active = :is_active
                WHERE id = :id";

        $params = [
            ':id' => $id,
            ':campaign_id' => $data['campaign_id'] ?? null,
            ':category_id' => $data['category_id'] ?? null,
            ':product_code' => $data['product_code'] ?? '',
            ':name_fr' => $data['name_fr'] ?? '',
            ':name_nl' => $data['name_nl'] ?? '',
            ':description_fr' => $data['description_fr'] ?? null,
            ':description_nl' => $data['description_nl'] ?? null,
            ':image_fr' => $data['image_fr'] ?? null,
            ':image_nl' => $data['image_nl'] ?? null,
            ':display_order' => $data['display_order'] ?? 0,
            ':max_total' => !empty($data['max_total']) ? (int)$data['max_total'] : null,
            ':max_per_customer' => !empty($data['max_per_customer']) ? (int)$data['max_per_customer'] : null,
            ':is_active' => $data['is_active'] ?? 1,
        ];

        try {
            return $this->db->execute($sql, $params);
        } catch (\PDOException $e) {
            error_log("Product::update() - SQL Error: " . $e->getMessage());
            return false;
        }
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
                ORDER BY display_order ASC, created_at DESC";
        
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
     * @modified 12/11/2025 17:30 - Ajout validation quotas
     */
    public function validate(array $data): array
    {
        $errors = [];

        // Code produit obligatoire
        if (empty($data['product_code'])) {
            $errors['product_code'] = 'Le code produit est obligatoire';
        } else {
            // Vérifier l'unicité (sauf si update avec même code)
            $existing = $this->findByCode($data['product_code']);
            if ($existing && (!isset($data['id']) || $existing['id'] != $data['id'])) {
                $errors['product_code'] = 'Ce code produit existe déjà';
            }
        }

        // Campagne obligatoire
        if (empty($data['campaign_id'])) {
            $errors['campaign_id'] = 'La campagne est obligatoire';
        } else {
            // Vérifier que la campagne existe
            $campaignModel = new \App\Models\Campaign();
            $campaign = $campaignModel->findById((int) $data['campaign_id']);
            
            if (!$campaign) {
                $errors['campaign_id'] = 'La campagne sélectionnée n\'existe pas';
            }
        }

        // Nom FR obligatoire
        if (empty($data['name_fr'])) {
            $errors['name_fr'] = 'Le nom en français est obligatoire';
        }

        // Validation des quotas (optionnels)
        if (isset($data['max_total']) && $data['max_total'] !== null && $data['max_total'] !== '') {
            $maxTotal = is_numeric($data['max_total']) ? (int)$data['max_total'] : $data['max_total'];
            if (!is_int($maxTotal) || $maxTotal < 1) {
                $errors['max_total'] = 'Le quota global doit être un nombre entier positif (minimum 1)';
            }
        }

        if (isset($data['max_per_customer']) && $data['max_per_customer'] !== null && $data['max_per_customer'] !== '') {
            $maxPerCustomer = is_numeric($data['max_per_customer']) ? (int)$data['max_per_customer'] : $data['max_per_customer'];
            if (!is_int($maxPerCustomer) || $maxPerCustomer < 1) {
                $errors['max_per_customer'] = 'Le quota par client doit être un nombre entier positif (minimum 1)';
            }
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