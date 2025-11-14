<?php
/**
 * PublicCampaignController.php
 * 
 * Contrôleur pour l'interface publique des campagnes
 * Gère l'accès client, l'identification et la commande
 * 
 * @created  2025/11/14 16:30
 * @modified 2025/11/14 16:30 - Création initiale
 */

namespace App\Controllers;

use Core\Database;
use Core\Session;

class PublicCampaignController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Afficher la page d'accès à une campagne via UUID
     * Route : GET /c/{uuid}
     * 
     * @param string $uuid UUID de la campagne
     * @return void
     */
    public function show(string $uuid): void
    {
        try {
            // Récupérer la campagne par UUID
            $query = "
                SELECT 
                    c.*,
                    CASE 
                        WHEN CURDATE() < c.start_date THEN 'upcoming'
                        WHEN CURDATE() > c.end_date THEN 'ended'
                        WHEN c.is_active = 1 THEN 'active'
                        ELSE 'inactive'
                    END as computed_status
                FROM campaigns c
                WHERE c.uuid = :uuid
            ";
            
            $campaign = $this->db->query($query, [':uuid' => $uuid]);
            
            // Campagne introuvable
            if (empty($campaign)) {
                $this->renderAccessDenied('campaign_not_found', $uuid);
                return;
            }
            
            $campaign = $campaign[0];
            
            // Vérifier le statut de la campagne
            if ($campaign['computed_status'] === 'upcoming') {
                $this->renderAccessDenied('upcoming', $uuid, $campaign);
                return;
            }
            
            if ($campaign['computed_status'] === 'ended') {
                $this->renderAccessDenied('ended', $uuid, $campaign);
                return;
            }
            
            if ($campaign['computed_status'] === 'inactive') {
                $this->renderAccessDenied('inactive', $uuid, $campaign);
                return;
            }
            
            // Campagne active - afficher la page d'identification
            $this->renderIdentificationPage($campaign);
            
        } catch (\PDOException $e) {
            error_log("Erreur show() : " . $e->getMessage());
            $this->renderAccessDenied('error', $uuid);
        }
    }

    /**
     * Traiter l'identification du client
     * Route : POST /c/{uuid}/identify
     * 
     * @param string $uuid UUID de la campagne
     * @return void
     */
    public function identify(string $uuid): void
    {
        try {
            // Récupérer la campagne
            $query = "SELECT * FROM campaigns WHERE uuid = :uuid AND is_active = 1";
            $campaign = $this->db->query($query, [':uuid' => $uuid]);
            
            if (empty($campaign)) {
                $this->renderAccessDenied('campaign_not_found', $uuid);
                return;
            }
            
            $campaign = $campaign[0];
            
            // Récupérer les données du formulaire
            $customerNumber = trim($_POST['customer_number'] ?? '');
            $country = $_POST['country'] ?? ($campaign['country'] === 'BOTH' ? 'BE' : $campaign['country']);
            
            // Validation
            if (empty($customerNumber)) {
                Session::set('error', 'Le numéro client est obligatoire.');
                header("Location: /stm/c/{$uuid}");
                exit;
            }
            
            // Vérifier que le client existe dans la DB externe
            $externalDb = $this->getExternalDatabase();
            $customerData = $this->getCustomerFromExternal($externalDb, $customerNumber, $country);
            
            if (!$customerData) {
                Session::set('error', 'Numéro client introuvable. Veuillez vérifier votre numéro.');
                header("Location: /stm/c/{$uuid}");
                exit;
            }
            
            // Vérifier les droits selon le mode de la campagne
            $hasAccess = $this->checkCustomerAccess($campaign, $customerNumber, $country);
            
            if (!$hasAccess) {
                $this->renderAccessDenied('no_access', $uuid, $campaign);
                return;
            }
            
            // Vérifier si tous les produits ont atteint leurs quotas
            $hasAvailableProducts = $this->checkAvailableProducts($campaign['id'], $customerNumber, $country);
            
            if (!$hasAvailableProducts) {
                $this->renderAccessDenied('quotas_reached', $uuid, $campaign);
                return;
            }
            
            // Tout est OK - créer la session client
            Session::set('public_customer', [
                'customer_number' => $customerNumber,
                'country' => $country,
                'company_name' => $customerData['company_name'],
                'campaign_uuid' => $uuid,
                'campaign_id' => $campaign['id'],
                'language' => $customerData['language'] ?? 'fr',
                'logged_at' => date('Y-m-d H:i:s')
            ]);
            
            // Rediriger vers le catalogue
            header("Location: /stm/c/{$uuid}/catalog");
            exit;
            
        } catch (\PDOException $e) {
            error_log("Erreur identify() : " . $e->getMessage());
            Session::set('error', 'Une erreur est survenue. Veuillez réessayer.');
            header("Location: /stm/c/{$uuid}");
            exit;
        }
    }

    /**
     * Récupérer la connexion à la base externe
     * 
     * @return \PDO
     */
    private function getExternalDatabase(): \PDO
    {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $dbname = 'trendyblog_sig'; // DB externe
        $user = $_ENV['DB_USER'] ?? '';
        $password = $_ENV['DB_PASS'] ?? '';
        
        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
        
        return new \PDO($dsn, $user, $password, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
        ]);
    }

    /**
     * Récupérer les infos client depuis la DB externe
     * 
     * @param \PDO $externalDb Connexion DB externe
     * @param string $customerNumber Numéro client
     * @param string $country Pays (BE/LU)
     * @return array|null Données client ou null si introuvable
     */
    private function getCustomerFromExternal(\PDO $externalDb, string $customerNumber, string $country): ?array
    {
        $table = $country === 'BE' ? 'BE_CLL' : 'LU_CLL';
        
        $query = "
            SELECT 
                IDE_CLL as customer_number,
                CLL_NOM as company_name,
                CLL_EMAIL as email,
                CLL_LANGUE as language
            FROM {$table}
            WHERE IDE_CLL = :customer_number
            LIMIT 1
        ";
        
        $stmt = $externalDb->prepare($query);
        $stmt->execute([':customer_number' => $customerNumber]);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }

    /**
     * Vérifier si le client a accès à cette campagne
     * 
     * @param array $campaign Données de la campagne
     * @param string $customerNumber Numéro client
     * @param string $country Pays
     * @return bool True si accès autorisé
     */
    private function checkCustomerAccess(array $campaign, string $customerNumber, string $country): bool
    {
        // Mode AUTOMATIC : tous les clients ont accès
        if ($campaign['customer_assignment_mode'] === 'automatic') {
            return true;
        }
        
        // Mode MANUAL : vérifier si le client est dans la liste
        if ($campaign['customer_assignment_mode'] === 'manual') {
            $query = "
                SELECT COUNT(*) as count
                FROM campaign_customers
                WHERE campaign_id = :campaign_id
                  AND customer_number = :customer_number
                  AND country = :country
                  AND is_authorized = 1
            ";
            
            $result = $this->db->query($query, [
                ':campaign_id' => $campaign['id'],
                ':customer_number' => $customerNumber,
                ':country' => $country
            ]);
            
            return ($result[0]['count'] ?? 0) > 0;
        }
        
        return false;
    }

    /**
     * Vérifier s'il reste des produits commandables (quotas)
     * 
     * @param int $campaignId ID de la campagne
     * @param string $customerNumber Numéro client
     * @param string $country Pays
     * @return bool True s'il reste au moins 1 produit commandable
     */
    private function checkAvailableProducts(int $campaignId, string $customerNumber, string $country): bool
    {
        // Récupérer tous les produits de la campagne
        $query = "
            SELECT 
                p.id,
                p.max_per_customer,
                p.max_total
            FROM products p
            WHERE p.campaign_id = :campaign_id
              AND p.is_active = 1
        ";
        
        $products = $this->db->query($query, [':campaign_id' => $campaignId]);
        
        if (empty($products)) {
            return false; // Aucun produit dans la campagne
        }
        
        foreach ($products as $product) {
            // Vérifier quota client
            $customerQuota = $this->getCustomerQuotaUsed($product['id'], $customerNumber, $country);
            $customerAvailable = is_null($product['max_per_customer']) || 
                                 $customerQuota < $product['max_per_customer'];
            
            // Vérifier quota global
            $globalQuota = $this->getGlobalQuotaUsed($product['id']);
            $globalAvailable = is_null($product['max_total']) || 
                              $globalQuota < $product['max_total'];
            
            // Si au moins 1 produit est disponible
            if ($customerAvailable && $globalAvailable) {
                return true;
            }
        }
        
        return false; // Tous les quotas atteints
    }

    /**
     * Récupérer le quota utilisé par un client pour un produit
     * 
     * @param int $productId ID du produit
     * @param string $customerNumber Numéro client
     * @param string $country Pays
     * @return int Quantité déjà commandée
     */
    private function getCustomerQuotaUsed(int $productId, string $customerNumber, string $country): int
    {
        $query = "
            SELECT COALESCE(SUM(ol.quantity), 0) as total
            FROM order_lines ol
            INNER JOIN orders o ON o.id = ol.order_id
            INNER JOIN customers c ON c.id = o.customer_id
            WHERE ol.product_id = :product_id
              AND c.customer_number = :customer_number
              AND c.country = :country
              AND o.status != 'cancelled'
        ";
        
        $result = $this->db->query($query, [
            ':product_id' => $productId,
            ':customer_number' => $customerNumber,
            ':country' => $country
        ]);
        
        return (int)($result[0]['total'] ?? 0);
    }

    /**
     * Récupérer le quota global utilisé pour un produit
     * 
     * @param int $productId ID du produit
     * @return int Quantité totale commandée
     */
    private function getGlobalQuotaUsed(int $productId): int
    {
        $query = "
            SELECT COALESCE(SUM(ol.quantity), 0) as total
            FROM order_lines ol
            INNER JOIN orders o ON o.id = ol.order_id
            WHERE ol.product_id = :product_id
              AND o.status != 'cancelled'
        ";
        
        $result = $this->db->query($query, [':product_id' => $productId]);
        
        return (int)($result[0]['total'] ?? 0);
    }

    /**
     * Afficher la page d'identification client
     * 
     * @param array $campaign Données de la campagne
     * @return void
     */
    private function renderIdentificationPage(array $campaign): void
    {
        // Inclure la vue
        require __DIR__ . '/../Views/public/campaign/show.php';
    }

    /**
     * Afficher la page d'accès refusé
     * 
     * @param string $reason Raison du refus
     * @param string $uuid UUID de la campagne
     * @param array|null $campaign Données de la campagne (optionnel)
     * @return void
     */
    private function renderAccessDenied(string $reason, string $uuid, ?array $campaign = null): void
    {
        // Inclure la vue
        require __DIR__ . '/../Views/public/campaign/access_denied.php';
    }
}