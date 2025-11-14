<?php
/**
 * PublicCampaignController.php
 * 
 * Contrôleur pour l'interface publique des campagnes
 * Gère l'accès client, l'identification, le catalogue et la commande
 * 
 * @created  2025/11/14 16:30
 * @modified 2025/11/14 18:00 - Ajout catalogue + panier (Sous-tâche 2)
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
                'language' => 'fr', // TODO: Sprint traductions FR/NL
                'logged_at' => date('Y-m-d H:i:s')
            ]);
            
            // Initialiser le panier vide
            Session::set('cart', [
                'campaign_uuid' => $uuid,
                'items' => []
            ]);
            
            // Rediriger vers le catalogue
            header("Location: /stm/c/{$uuid}/catalog");
            exit;
            
        } catch (\PDOException $e) {
            error_log("Erreur identify() : " . $e->getMessage());
            // En développement, afficher l'erreur détaillée
            $errorDetail = ($_ENV['APP_DEBUG'] ?? false) ? $e->getMessage() : 'Une erreur est survenue. Veuillez réessayer.';
            Session::set('error', $errorDetail);
            header("Location: /stm/c/{$uuid}");
            exit;
        }
    }

    /**
     * Afficher le catalogue de produits
     * Route : GET /c/{uuid}/catalog
     * 
     * @param string $uuid UUID de la campagne
     * @return void
     */
    public function catalog(string $uuid): void
    {
        try {
            // Vérifier que le client est identifié
            $customer = Session::get('public_customer');
            if (!$customer || $customer['campaign_uuid'] !== $uuid) {
                header("Location: /stm/c/{$uuid}");
                exit;
            }
            
            // Récupérer la campagne
            $query = "SELECT * FROM campaigns WHERE uuid = :uuid AND is_active = 1";
            $campaign = $this->db->query($query, [':uuid' => $uuid]);
            
            if (empty($campaign)) {
                Session::remove('public_customer');
                header("Location: /stm/c/{$uuid}");
                exit;
            }
            
            $campaign = $campaign[0];
            
            // Récupérer toutes les catégories actives avec leurs produits
            $categoriesQuery = "
                SELECT DISTINCT
                    cat.id,
                    cat.code,
                    cat.name_fr,
                    cat.color,
                    cat.display_order
                FROM product_categories cat
                INNER JOIN products p ON p.category_id = cat.id
                WHERE p.campaign_id = :campaign_id
                  AND p.is_active = 1
                  AND cat.is_active = 1
                ORDER BY cat.display_order ASC, cat.name_fr ASC
            ";
            
            $categories = $this->db->query($categoriesQuery, [':campaign_id' => $campaign['id']]);
            
            // Pour chaque catégorie, récupérer ses produits avec quotas
            foreach ($categories as $key => $category) {
                $productsQuery = "
                    SELECT 
                        p.*
                    FROM products p
                    WHERE p.category_id = :category_id
                      AND p.campaign_id = :campaign_id
                      AND p.is_active = 1
                    ORDER BY p.display_order ASC, p.name_fr ASC
                ";
                
                $products = $this->db->query($productsQuery, [
                    ':category_id' => $category['id'],
                    ':campaign_id' => $campaign['id']
                ]);
                
                // Calculer les quotas disponibles pour chaque produit
                foreach ($products as $productKey => $product) {
                    $quotas = $this->calculateAvailableQuotas(
                        $product['id'],
                        $customer['customer_number'],
                        $customer['country'],
                        $product['max_per_customer'],
                        $product['max_total']
                    );
                    
                    $products[$productKey]['available_for_customer'] = $quotas['customer'];
                    $products[$productKey]['available_global'] = $quotas['global'];
                    $products[$productKey]['max_orderable'] = $quotas['max_orderable'];
                    $products[$productKey]['is_orderable'] = $quotas['is_orderable'];
                }
                
                $categories[$key]['products'] = $products;
            }
            
            // Récupérer le panier depuis la session
            $cart = Session::get('cart', [
                'campaign_uuid' => $uuid,
                'items' => []
            ]);
            
            // Afficher la vue
            require __DIR__ . '/../Views/public/campaign/catalog.php';
            
        } catch (\PDOException $e) {
            error_log("Erreur catalog() : " . $e->getMessage());
            Session::set('error', 'Une erreur est survenue lors du chargement du catalogue.');
            header("Location: /stm/c/{$uuid}");
            exit;
        }
    }

    /**
     * Ajouter un produit au panier
     * Route : POST /c/{uuid}/cart/add
     * 
     * @param string $uuid UUID de la campagne
     * @return void
     */
    public function addToCart(string $uuid): void
    {
        header('Content-Type: application/json');
        
        try {
            // Vérifier session client
            $customer = Session::get('public_customer');
            if (!$customer || $customer['campaign_uuid'] !== $uuid) {
                echo json_encode(['success' => false, 'error' => 'Session expirée']);
                exit;
            }
            
            // Récupérer les données
            $productId = (int)($_POST['product_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 0);
            
            if ($productId <= 0 || $quantity <= 0) {
                echo json_encode(['success' => false, 'error' => 'Données invalides']);
                exit;
            }
            
            // Récupérer le produit
            $productQuery = "SELECT * FROM products WHERE id = :id AND campaign_id = :campaign_id AND is_active = 1";
            $product = $this->db->query($productQuery, [
                ':id' => $productId,
                ':campaign_id' => $customer['campaign_id']
            ]);
            
            if (empty($product)) {
                echo json_encode(['success' => false, 'error' => 'Produit introuvable']);
                exit;
            }
            
            $product = $product[0];
            
            // Vérifier les quotas disponibles
            $quotas = $this->calculateAvailableQuotas(
                $productId,
                $customer['customer_number'],
                $customer['country'],
                $product['max_per_customer'],
                $product['max_total']
            );
            
            if (!$quotas['is_orderable']) {
                echo json_encode(['success' => false, 'error' => 'Produit plus disponible']);
                exit;
            }
            
            // Récupérer le panier
            $cart = Session::get('cart', ['campaign_uuid' => $uuid, 'items' => []]);
            
            // Chercher si le produit existe déjà dans le panier
            $existingIndex = null;
            foreach ($cart['items'] as $index => $item) {
                if ($item['product_id'] == $productId) {
                    $existingIndex = $index;
                    break;
                }
            }
            
            // Calculer la nouvelle quantité totale
            $currentQtyInCart = $existingIndex !== null ? $cart['items'][$existingIndex]['quantity'] : 0;
            $newTotalQty = $currentQtyInCart + $quantity;
            
            // Vérifier que la nouvelle quantité ne dépasse pas les quotas
            if ($newTotalQty > $quotas['max_orderable']) {
                echo json_encode([
                    'success' => false, 
                    'error' => "Quantité maximale : {$quotas['max_orderable']}"
                ]);
                exit;
            }
            
            // Ajouter ou mettre à jour le produit dans le panier
            if ($existingIndex !== null) {
                // Mise à jour quantité
                $cart['items'][$existingIndex]['quantity'] = $newTotalQty;
            } else {
                // Nouveau produit
                $cart['items'][] = [
                    'product_id' => $productId,
                    'product_code' => $product['product_code'],
                    'product_name' => $product['name_fr'],
                    'quantity' => $quantity,
                    'image_fr' => $product['image_fr'] ?? null
                ];
            }
            
            // Sauvegarder le panier en session
            Session::set('cart', $cart);
            
            // Retourner le succès avec le panier mis à jour
            echo json_encode([
                'success' => true,
                'cart' => $cart,
                'message' => 'Produit ajouté au panier'
            ]);
            
        } catch (\Exception $e) {
            error_log("Erreur addToCart() : " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
        }
        
        exit;
    }

    /**
     * Mettre à jour la quantité d'un produit dans le panier
     * Route : POST /c/{uuid}/cart/update
     * 
     * @param string $uuid UUID de la campagne
     * @return void
     */
    public function updateCart(string $uuid): void
    {
        header('Content-Type: application/json');
        
        try {
            // Vérifier session client
            $customer = Session::get('public_customer');
            if (!$customer || $customer['campaign_uuid'] !== $uuid) {
                echo json_encode(['success' => false, 'error' => 'Session expirée']);
                exit;
            }
            
            $productId = (int)($_POST['product_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 0);
            
            if ($productId <= 0) {
                echo json_encode(['success' => false, 'error' => 'Données invalides']);
                exit;
            }
            
            $cart = Session::get('cart', ['campaign_uuid' => $uuid, 'items' => []]);
            
            // Si quantité = 0, supprimer le produit
            if ($quantity <= 0) {
                $cart['items'] = array_values(array_filter($cart['items'], function($item) use ($productId) {
                    return $item['product_id'] != $productId;
                }));
            } else {
                // Vérifier les quotas
                $product = $this->db->query("SELECT * FROM products WHERE id = :id", [':id' => $productId]);
                
                if (empty($product)) {
                    echo json_encode(['success' => false, 'error' => 'Produit introuvable']);
                    exit;
                }
                
                $product = $product[0];
                
                $quotas = $this->calculateAvailableQuotas(
                    $productId,
                    $customer['customer_number'],
                    $customer['country'],
                    $product['max_per_customer'],
                    $product['max_total']
                );
                
                if ($quantity > $quotas['max_orderable']) {
                    echo json_encode([
                        'success' => false, 
                        'error' => "Quantité maximale : {$quotas['max_orderable']}"
                    ]);
                    exit;
                }
                
                // Mettre à jour la quantité
                foreach ($cart['items'] as &$item) {
                    if ($item['product_id'] == $productId) {
                        $item['quantity'] = $quantity;
                        break;
                    }
                }
            }
            
            
            Session::set('cart', $cart);
            
            echo json_encode(['success' => true, 'cart' => $cart]);
            
        } catch (\Exception $e) {
            error_log("Erreur updateCart() : " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
        }
        
        exit;
    }

    /**
     * Retirer un produit du panier
     * Route : POST /c/{uuid}/cart/remove
     * 
     * @param string $uuid UUID de la campagne
     * @return void
     */
    public function removeFromCart(string $uuid): void
    {
        header('Content-Type: application/json');
        
        try {
            $customer = Session::get('public_customer');
            if (!$customer || $customer['campaign_uuid'] !== $uuid) {
                echo json_encode(['success' => false, 'error' => 'Session expirée']);
                exit;
            }
            
            $productId = (int)($_POST['product_id'] ?? 0);
            
            $cart = Session::get('cart', ['campaign_uuid' => $uuid, 'items' => []]);
            
            // Retirer le produit
            $cart['items'] = array_values(array_filter($cart['items'], function($item) use ($productId) {
                return $item['product_id'] != $productId;
            }));
            
            
            Session::set('cart', $cart);
            
            echo json_encode(['success' => true, 'cart' => $cart]);
            
        } catch (\Exception $e) {
            error_log("Erreur removeFromCart() : " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
        }
        
        exit;
    }

    /**
     * Vider complètement le panier
     * Route : POST /c/{uuid}/cart/clear
     * 
     * @param string $uuid UUID de la campagne
     * @return void
     */
    public function clearCart(string $uuid): void
    {
        header('Content-Type: application/json');
        
        try {
            $customer = Session::get('public_customer');
            if (!$customer || $customer['campaign_uuid'] !== $uuid) {
                echo json_encode(['success' => false, 'error' => 'Session expirée']);
                exit;
            }
            
            Session::set('cart', [
                'campaign_uuid' => $uuid,
                'items' => []
            ]);
            
            echo json_encode(['success' => true, 'cart' => Session::get('cart')]);
            
        } catch (\Exception $e) {
            error_log("Erreur clearCart() : " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
        }
        
        exit;
    }

    /**
     * Calculer les quotas disponibles pour un produit
     * 
     * @param int $productId ID du produit
     * @param string $customerNumber Numéro client
     * @param string $country Pays
     * @param int|null $maxPerCustomer Quota max par client
     * @param int|null $maxTotal Quota max global
     * @return array ['customer' => int, 'global' => int, 'max_orderable' => int, 'is_orderable' => bool]
     */
    private function calculateAvailableQuotas(
        int $productId,
        string $customerNumber,
        string $country,
        ?int $maxPerCustomer,
        ?int $maxTotal
    ): array {
        // Quota utilisé par le client (commandes validées uniquement)
        $customerUsed = $this->getCustomerQuotaUsed($productId, $customerNumber, $country);
        
        // Quota utilisé globalement (toutes commandes validées)
        $globalUsed = $this->getGlobalQuotaUsed($productId);
        
        // Calculer disponibles
        $availableForCustomer = is_null($maxPerCustomer) ? PHP_INT_MAX : ($maxPerCustomer - $customerUsed);
        $availableGlobal = is_null($maxTotal) ? PHP_INT_MAX : ($maxTotal - $globalUsed);
        
        // Maximum commandable = minimum des 2
        $maxOrderable = min($availableForCustomer, $availableGlobal);
        $isOrderable = $maxOrderable > 0;
        
        return [
            'customer' => max(0, $availableForCustomer),
            'global' => max(0, $availableGlobal),
            'max_orderable' => max(0, $maxOrderable),
            'is_orderable' => $isOrderable
        ];
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
                CLL_NCLIXX as customer_number,
                CLL_NOM as company_name,
                CLL_PRENOM as contact_name
            FROM {$table}
            WHERE CLL_NCLIXX = :customer_number
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
            ";
            
            $result = $this->db->query($query, [
                ':campaign_id' => $campaign['id'],
                ':customer_number' => $customerNumber,
                ':country' => $country
            ]);
            
            return ($result[0]['count'] ?? 0) > 0;
        }
        
        // Mode PROTECTED : vérifier mot de passe + existence client
        if ($campaign['customer_assignment_mode'] === 'protected') {
            $password = $_POST['password'] ?? '';
            
            // Vérifier d'abord le mot de passe
            if (empty($password) || $password !== $campaign['order_password']) {
                return false;
            }
            
            // Mot de passe correct : client déjà vérifié dans identify()
            return true;
        }
        
        // Mode inconnu ou non géré
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
            $quotas = $this->calculateAvailableQuotas(
                $product['id'],
                $customerNumber,
                $country,
                $product['max_per_customer'],
                $product['max_total']
            );
            
            // Si au moins 1 produit est disponible
            if ($quotas['is_orderable']) {
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