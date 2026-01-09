<?php
/**
 * Model ShopType
 *
 * Gestion des types de magasin pour les prospects
 * Sprint 16 : Mode Prospect
 *
 * @package    App\Models
 * @author     Claude AI
 * @version    1.0.0
 * @created    2026/01/09
 */

namespace App\Models;

use Core\Database;

class ShopType
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Récupérer tous les types de magasin actifs
     *
     * @return array
     */
    public function getAllActive(): array
    {
        $query = "SELECT id, name 
                  FROM shop_types 
                  WHERE is_active = 1 
                  ORDER BY sort_order ASC, name ASC";
        
        return $this->db->query($query);
    }

    /**
     * Récupérer tous les types de magasin
     *
     * @return array
     */
    public function getAll(): array
    {
        $query = "SELECT * FROM shop_types ORDER BY sort_order ASC, name ASC";
        return $this->db->query($query);
    }

    /**
     * Trouver un type par ID
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $query = "SELECT * FROM shop_types WHERE id = :id";
        $result = $this->db->query($query, [":id" => $id]);
        return $result[0] ?? null;
    }

    /**
     * Trouver un type par nom
     *
     * @param string $name
     * @return array|null
     */
    public function findByName(string $name): ?array
    {
        $query = "SELECT * FROM shop_types WHERE name = :name";
        $result = $this->db->query($query, [":name" => $name]);
        return $result[0] ?? null;
    }
}
