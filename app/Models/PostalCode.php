<?php
/**
 * Model PostalCode
 *
 * Gestion des codes postaux BE/LU pour autocomplete
 * Sprint 16 : Mode Prospect
 *
 * @package    App\Models
 * @author     Claude AI
 * @version    1.0.0
 * @created    2026/01/09
 */

namespace App\Models;

use Core\Database;

class PostalCode
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Rechercher des codes postaux (autocomplete)
     *
     * @param string $search Terme de recherche (code postal ou ville)
     * @param string|null $country Filtrer par pays (BE ou LU)
     * @param int $limit Nombre max de résultats
     * @return array
     */
    public function search(string $search, ?string $country = null, int $limit = 10): array
    {
        $search = trim($search);
        if (strlen($search) < 2) {
            return [];
        }

        $params = [":search" => $search . "%"];
        
        $query = "SELECT DISTINCT code, locality_fr, locality_nl, country 
                  FROM postal_codes 
                  WHERE (code LIKE :search OR locality_fr LIKE :search_city OR locality_nl LIKE :search_city)";
        
        $params[":search_city"] = "%" . $search . "%";
        
        if ($country) {
            $query .= " AND country = :country";
            $params[":country"] = strtoupper($country);
        }
        
        $query .= " ORDER BY code ASC LIMIT " . (int) $limit;
        
        return $this->db->query($query, $params);
    }

    /**
     * Récupérer les localités pour un code postal
     *
     * @param string $code Code postal
     * @param string|null $country Filtrer par pays
     * @return array
     */
    public function getLocalitiesByCode(string $code, ?string $country = null): array
    {
        $params = [":code" => $code];
        
        $query = "SELECT id, code, locality_fr, locality_nl, country 
                  FROM postal_codes 
                  WHERE code = :code";
        
        if ($country) {
            $query .= " AND country = :country";
            $params[":country"] = strtoupper($country);
        }
        
        $query .= " ORDER BY locality_fr ASC";
        
        return $this->db->query($query, $params);
    }

    /**
     * Vérifier si un code postal existe
     *
     * @param string $code
     * @param string|null $country
     * @return bool
     */
    public function exists(string $code, ?string $country = null): bool
    {
        $params = [":code" => $code];
        
        $query = "SELECT COUNT(*) as total FROM postal_codes WHERE code = :code";
        
        if ($country) {
            $query .= " AND country = :country";
            $params[":country"] = strtoupper($country);
        }
        
        $result = $this->db->query($query, $params);
        return (int) ($result[0]["total"] ?? 0) > 0;
    }

    /**
     * Récupérer tous les codes postaux d'un pays
     *
     * @param string $country BE ou LU
     * @return array
     */
    public function getAllByCountry(string $country): array
    {
        $query = "SELECT DISTINCT code, locality_fr, locality_nl 
                  FROM postal_codes 
                  WHERE country = :country 
                  ORDER BY code ASC";
        
        return $this->db->query($query, [":country" => strtoupper($country)]);
    }
}
