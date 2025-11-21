<?php
/**
 * Campaign Model
 * 
 * Gestion des campagnes promotionnelles
 * 
 * @created  2025/11/08 10:00
 * @modified 2025/11/14 00:00 - Sprint 5 : Ajout colonnes order_password, order_type, deferred_delivery, delivery_date + modification addCustomersToCampaign() pour utiliser customer_number + country
 */

namespace App\Models;

use Core\Database;

class Campaign
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Générer un UUID unique
     * 
     * @return string
     */
    private function generateUniqueUuid(): string
    {
        do {
            $uuid = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff)
            );
            
            $exists = $this->findByUuid($uuid);
        } while ($exists);
        
        return $uuid;
    }

    /**
     * Générer un slug depuis le nom
     * 
     * @param string $name
     * @return string
     */
    private function generateSlug(string $name): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        
        // Vérifier l'unicité
        $counter = 1;
        $originalSlug = $slug;
        
        while ($this->findBySlug($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    /**
     * Récupérer toutes les campagnes avec filtres optionnels
     * 
     * @param array $filters Filtres (search, country, status)
     * @return array
     */
    public function getAll(array $filters = []): array
    {
        $query = "SELECT * FROM campaigns WHERE 1=1";
        $params = [];

        // Filtre recherche
        if (!empty($filters['search'])) {
            $query .= " AND (name LIKE :search OR title_fr LIKE :search OR title_nl LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        // Filtre pays
        if (!empty($filters['country'])) {
            $query .= " AND country = :country";
            $params[':country'] = $filters['country'];
        }

        // Filtre statut
        if (!empty($filters['status'])) {
            switch ($filters['status']) {
                case 'active':
                    $query .= " AND is_active = 1 AND start_date <= CURDATE() AND end_date >= CURDATE()";
                    break;
                case 'upcoming':
                    $query .= " AND start_date > CURDATE()";
                    break;
                case 'ended':
                    $query .= " AND end_date < CURDATE()";
                    break;
                case 'inactive':
                    $query .= " AND is_active = 0";
                    break;
            }
        }

        $query .= " ORDER BY start_date DESC";

        try {
            return $this->db->query($query, $params);
        } catch (\PDOException $e) {
            error_log("Erreur getAll: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les campagnes actives
     * 
     * @return array
     */
    public function getActive(): array
    {
        $query = "SELECT * FROM campaigns 
                  WHERE is_active = 1 
                  AND start_date <= CURDATE() 
                  AND end_date >= CURDATE()
                  ORDER BY start_date DESC";

        try {
            return $this->db->query($query);
        } catch (\PDOException $e) {
            error_log("Erreur getActive: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les campagnes archivées
     * 
     * @return array
     */
    public function getArchived(): array
    {
        $query = "SELECT * FROM campaigns 
                  WHERE end_date < CURDATE()
                  ORDER BY end_date DESC";

        try {
            return $this->db->query($query);
        } catch (\PDOException $e) {
            error_log("Erreur getArchived: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les statistiques des campagnes
     * 
     * @return array
     */
    public function getStats(): array
    {
        try {
            $total = $this->db->queryOne("SELECT COUNT(*) as count FROM campaigns");
            $active = $this->db->queryOne("SELECT COUNT(*) as count FROM campaigns 
                                           WHERE is_active = 1 
                                           AND start_date <= CURDATE() 
                                           AND end_date >= CURDATE()");
            $upcoming = $this->db->queryOne("SELECT COUNT(*) as count FROM campaigns 
                                             WHERE start_date > CURDATE()");
            $ended = $this->db->queryOne("SELECT COUNT(*) as count FROM campaigns 
                                          WHERE end_date < CURDATE()");

            return [
                'total' => $total['count'] ?? 0,
                'active' => $active['count'] ?? 0,
                'upcoming' => $upcoming['count'] ?? 0,
                'ended' => $ended['count'] ?? 0
            ];
        } catch (\PDOException $e) {
            error_log("Erreur getStats: " . $e->getMessage());
            return [
                'total' => 0,
                'active' => 0,
                'upcoming' => 0,
                'ended' => 0
            ];
        }
    }

    /**
     * Compter le nombre total de campagnes
     * 
     * @param array $filters Filtres optionnels
     * @return int
     */
    public function count(array $filters = []): int
    {
        $query = "SELECT COUNT(*) as count FROM campaigns WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $query .= " AND (name LIKE :search OR title_fr LIKE :search OR title_nl LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['country'])) {
            $query .= " AND country = :country";
            $params[':country'] = $filters['country'];
        }

        try {
            $result = $this->db->queryOne($query, $params);
            return (int) ($result['count'] ?? 0);
        } catch (\PDOException $e) {
            error_log("Erreur count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupérer une campagne par ID
     * 
     * @param int $id
     * @return array|false
     */
    public function findById(int $id): array|false
    {
        $query = "SELECT * FROM campaigns WHERE id = :id";
        
        try {
            $result = $this->db->queryOne($query, [':id' => $id]);
            return $result ?: false;
        } catch (\PDOException $e) {
            error_log("Erreur findById: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer une campagne par UUID
     * 
     * @param string $uuid
     * @return array|false
     */
    public function findByUuid(string $uuid): array|false
    {
        $query = "SELECT * FROM campaigns WHERE uuid = :uuid";
        
        try {
            $result = $this->db->queryOne($query, [':uuid' => $uuid]);
            return $result ?: false;
        } catch (\PDOException $e) {
            error_log("Erreur findByUuid: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer une campagne par slug
     * 
     * @param string $slug
     * @return array|false
     */
    public function findBySlug(string $slug): array|false
    {
        $query = "SELECT * FROM campaigns WHERE slug = :slug";
        
        try {
            $result = $this->db->queryOne($query, [':slug' => $slug]);
            return $result ?: false;
        } catch (\PDOException $e) {
            error_log("Erreur findBySlug: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Créer une nouvelle campagne
     * 
     * @param array $data Données de la campagne
     * @return int|false ID de la campagne créée ou false
     */
    public function create(array $data): int|false
    {
        // Générer UUID et slug
        $uuid = $this->generateUniqueUuid();
        $slug = $this->generateSlug($data['name']);
        
        $query = "INSERT INTO campaigns (
                    uuid, slug, name, country, is_active,
                    start_date, end_date,
                    title_fr, description_fr,
                    title_nl, description_nl,
                    customer_assignment_mode,
                    order_password, order_type, deferred_delivery, delivery_date,
                    unique_url,
                    created_at
                ) VALUES (
                    :uuid, :slug, :name, :country, :is_active,
                    :start_date, :end_date,
                    :title_fr, :description_fr,
                    :title_nl, :description_nl,
                    :customer_assignment_mode,
                    :order_password, :order_type, :deferred_delivery, :delivery_date,
                    :unique_url,
                    NOW()
                )";

        $params = [
            ':uuid' => $uuid,
            ':slug' => $slug,
            ':name' => $data['name'],
            ':country' => $data['country'],
            ':is_active' => $data['is_active'] ?? 1,
            ':start_date' => $data['start_date'],
            ':end_date' => $data['end_date'],
            ':title_fr' => $data['title_fr'] ?? null,
            ':description_fr' => $data['description_fr'] ?? null,
            ':title_nl' => $data['title_nl'] ?? null,
            ':description_nl' => $data['description_nl'] ?? null,
            ':customer_assignment_mode' => $data['customer_assignment_mode'] ?? 'automatic',
            ':order_password' => $data['order_password'] ?? null,
            ':order_type' => $data['order_type'] ?? 'W',
            ':unique_url' => $uuid, // Utiliser le même UUID pour unique_url
            ':deferred_delivery' => $data['deferred_delivery'] ?? 0,
            ':delivery_date' => $data['delivery_date'] ?? null,
        ];

        try {
            $this->db->execute($query, $params);
            return (int)$this->db->lastInsertId();
        } catch (\PDOException $e) {
            error_log("Erreur create: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mettre à jour une campagne
     * 
     * @param int $id ID de la campagne
     * @param array $data Nouvelles données
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        // Si le nom change, régénérer le slug
        $campaign = $this->findById($id);
        if ($campaign && $campaign['name'] !== $data['name']) {
            $data['slug'] = $this->generateSlug($data['name']);
        }
        
        $query = "UPDATE campaigns SET
                    name = :name,
                    country = :country,
                    is_active = :is_active,
                    start_date = :start_date,
                    end_date = :end_date,
                    title_fr = :title_fr,
                    description_fr = :description_fr,
                    title_nl = :title_nl,
                    description_nl = :description_nl,
                    customer_assignment_mode = :customer_assignment_mode,
                    order_password = :order_password,
                    order_type = :order_type,
                    deferred_delivery = :deferred_delivery,
                    delivery_date = :delivery_date,
                    updated_at = NOW()";
        
        if (isset($data['slug'])) {
            $query .= ", slug = :slug";
        }
        
        $query .= " WHERE id = :id";

        $params = [
            ':id' => $id,
            ':name' => $data['name'],
            ':country' => $data['country'],
            ':is_active' => $data['is_active'] ?? 1,
            ':start_date' => $data['start_date'],
            ':end_date' => $data['end_date'],
            ':title_fr' => $data['title_fr'] ?? null,
            ':description_fr' => $data['description_fr'] ?? null,
            ':title_nl' => $data['title_nl'] ?? null,
            ':description_nl' => $data['description_nl'] ?? null,
            ':customer_assignment_mode' => $data['customer_assignment_mode'] ?? 'automatic',
            ':order_password' => $data['order_password'] ?? null,
            ':order_type' => $data['order_type'] ?? 'W',
            ':deferred_delivery' => $data['deferred_delivery'] ?? 0,
            ':delivery_date' => $data['delivery_date'] ?? null,
        ];
        
        if (isset($data['slug'])) {
            $params[':slug'] = $data['slug'];
        }

        try {
            return $this->db->execute($query, $params);
        } catch (\PDOException $e) {
            error_log("Erreur update: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer une campagne
     * 
     * Supprime d'abord les clients liés, puis la campagne
     * 
     * @param int $id ID de la campagne
     * @return bool
     */
    public function delete(int $id): bool
    {
        try {
            // 1. Supprimer d'abord les clients liés (si mode manual)
            $this->removeAllCustomers($id);
            
            // 2. Supprimer la campagne
            $query = "DELETE FROM campaigns WHERE id = :id";
            return $this->db->execute($query, [':id' => $id]);
        } catch (\PDOException $e) {
            error_log("Erreur delete: " . $e->getMessage());
            return false;
        }
    }


    /**
     * Valider les données d'une campagne
     * 
     * @param array $data Données à valider
     * @return array Tableau des erreurs (vide si pas d'erreur)
     */
    public function validate(array $data): array
    {
        $errors = [];

        // Nom requis
        if (empty($data['name'])) {
            $errors['name'] = 'Le nom de la campagne est requis';
        }

        // Pays requis
        if (empty($data['country']) || !in_array($data['country'], ['BE', 'LU'])) {
            $errors['country'] = 'Le pays doit être BE ou LU';
        }

        // Dates requises
        if (empty($data['start_date'])) {
            $errors['start_date'] = 'La date de début est requise';
        }

        if (empty($data['end_date'])) {
            $errors['end_date'] = 'La date de fin est requise';
        }

        // Validation cohérence des dates
        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            if (strtotime($data['end_date']) < strtotime($data['start_date'])) {
                $errors['end_date'] = 'La date de fin doit être après la date de début';
            }
        }

        // Validation order_type
        if (!empty($data['order_type']) && !in_array($data['order_type'], ['V', 'W'])) {
            $errors['order_type'] = 'Le type de commande doit être V ou W';
        }

        // Validation customer_assignment_mode
        if (!empty($data['customer_assignment_mode']) && 
            !in_array($data['customer_assignment_mode'], ['automatic', 'manual', 'protected'])) {
            $errors['customer_assignment_mode'] = 'Le mode d\'attribution doit être automatic, manual ou protected';
        }

        // Si mode protected, le mot de passe est requis
        if (!empty($data['customer_assignment_mode']) && 
            $data['customer_assignment_mode'] === 'protected' && 
            empty($data['order_password'])) {
            $errors['order_password'] = 'Le mot de passe est requis pour le mode protégé';
        }

        // Si livraison différée, la date de livraison est requise
        if (!empty($data['deferred_delivery']) && 
            $data['deferred_delivery'] == 1 && 
            empty($data['delivery_date'])) {
            $errors['delivery_date'] = 'La date de livraison est requise pour une livraison différée';
        }

        return $errors;
    }

    /**
     * Ajouter des clients à une campagne (mode MANUAL)
     * 
     * @param int $campaignId ID de la campagne
     * @param array $customerNumbers Liste des numéros clients
     * @return int Nombre de clients ajoutés
     */
    public function addCustomersToCampaign(int $campaignId, array $customerNumbers): int
    {
        // Récupérer le pays de la campagne
        $campaign = $this->findById($campaignId);
        if (!$campaign) {
            return 0;
        }
        
        $country = $campaign['country'];
        $added = 0;
        
        foreach ($customerNumbers as $number) {
            $number = trim($number);
            if (empty($number)) {
                continue;
            }
            
            // Vérifier si le client n'existe pas déjà
            $checkQuery = "SELECT COUNT(*) as count FROM campaign_customers 
                          WHERE campaign_id = :campaign_id 
                          AND customer_number = :customer_number 
                          AND country = :country";
            
            try {
                $result = $this->db->queryOne($checkQuery, [
                    ':campaign_id' => $campaignId,
                    ':customer_number' => $number,
                    ':country' => $country
                ]);
                
                if ($result['count'] > 0) {
                    continue; // Client déjà associé
                }
                
                // Ajouter le client
                $insertQuery = "INSERT INTO campaign_customers 
                               (campaign_id, customer_number, country, created_at) 
                               VALUES (:campaign_id, :customer_number, :country, NOW())";
                
                if ($this->db->execute($insertQuery, [
                    ':campaign_id' => $campaignId,
                    ':customer_number' => $number,
                    ':country' => $country
                ])) {
                    $added++;
                }
            } catch (\PDOException $e) {
                error_log("Erreur ajout client {$number}: " . $e->getMessage());
            }
        }
        
        return $added;
    }

    /**
    /**
     * Compter le nombre de clients éligibles pour une campagne
     * 
     * @param int $campaignId ID de la campagne
     * @return int|string Nombre de clients ou "Tous" pour mode automatic/protected
     */
    public function countCustomers(int $campaignId): int|string
    {
        $campaign = $this->findById($campaignId);
        
        if (!$campaign) {
            return 0;
        }

        // Mode automatic ou protected : Tous les clients du pays
        if (in_array($campaign['customer_assignment_mode'], ['automatic', 'protected'])) {
            return 'Tous';
        }

        // Mode manual : Compter dans campaign_customers
        $query = "SELECT COUNT(*) as count FROM campaign_customers 
                  WHERE campaign_id = :campaign_id";
        
        try {
            $result = $this->db->queryOne($query, [':campaign_id' => $campaignId]);
            return (int) ($result['count'] ?? 0);
        } catch (\PDOException $e) {
            error_log("Erreur countCustomers: " . $e->getMessage());
            return 0;
        }
    }
    /**
     * Compter le nombre de promotions (produits) actives pour une campagne
     * 
     * @param int $campaignId ID de la campagne
     * @return int
     */
    public function countPromotions(int $campaignId): int
    {
        $query = "SELECT COUNT(*) as count FROM products 
                  WHERE campaign_id = :campaign_id 
                  AND is_active = 1";
        
        try {
            $result = $this->db->queryOne($query, [':campaign_id' => $campaignId]);
            return (int) ($result['count'] ?? 0);
        } catch (\PDOException $e) {
            error_log("Erreur countPromotions: " . $e->getMessage());
            return 0;
        }
    }
    /**
     * Compter le nombre de campagnes par pays
     * 
     * @param string $country Code pays (BE ou LU)
     * @return int
     */
    public function countByCountry(string $country): int
    {
        $query = "SELECT COUNT(*) as count FROM campaigns WHERE country = :country";
        
        try {
            $result = $this->db->queryOne($query, [':country' => $country]);
            return (int) ($result['count'] ?? 0);
        } catch (\PDOException $e) {
            error_log("Erreur countByCountry: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupérer la liste des numéros clients d'une campagne (mode MANUAL)
     * 
     * @param int $campaignId ID de la campagne
     * @return array Liste des numéros clients
     */
    public function getCustomerNumbers(int $campaignId): array
    {
        $query = "SELECT customer_number FROM campaign_customers 
                  WHERE campaign_id = :campaign_id 
                  ORDER BY customer_number ASC";
        
        try {
            $results = $this->db->query($query, [':campaign_id' => $campaignId]);
            return array_column($results, 'customer_number');
        } catch (\PDOException $e) {
            error_log("Erreur getCustomerNumbers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Supprimer tous les clients d'une campagne
     * 
     * @param int $campaignId ID de la campagne
     * @return bool
     */
    public function removeAllCustomers(int $campaignId): bool
    {
        $query = "DELETE FROM campaign_customers WHERE campaign_id = :campaign_id";
        
        try {
            return $this->db->execute($query, [':campaign_id' => $campaignId]);
        } catch (\PDOException $e) {
            error_log("Erreur removeAllCustomers: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Compter le nombre de clients DISTINCTS ayant passé commande pour une campagne
     * 
     * @param int $campaignId ID de la campagne
     * @return int
     * @created 14/11/2025 - Ajout statistiques clients avec commandes
     */
    public function countCustomersWithOrders(int $campaignId): int
    {
        $query = "SELECT COUNT(DISTINCT customer_id) as total 
                  FROM orders 
                  WHERE campaign_id = :campaign_id";
        
        try {
            $result = $this->db->queryOne($query, [':campaign_id' => $campaignId]);
            return $result['total'] ?? 0;
        } catch (\PDOException $e) {
            error_log("Erreur countCustomersWithOrders: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupérer les statistiques clients complètes pour une campagne
     * Retourne le nombre de clients éligibles ET le nombre ayant commandé
     * 
     * @param int $campaignId ID de la campagne
     * @return array ['total' => int|string, 'with_orders' => int]
     * @created 14/11/2025 - Ajout statistiques clients avec commandes
     */
    public function getCustomerStats(int $campaignId): array
    {
        return [
            'total' => $this->countCustomers($campaignId),
            'with_orders' => $this->countCustomersWithOrders($campaignId)
        ];
    }
}