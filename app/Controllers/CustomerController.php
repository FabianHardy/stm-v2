<?php
/**
 * CustomerController - Consultation des clients
 *
 * Module de consultation des clients depuis la base externe (BE_CLL/LU_CLL)
 * avec enrichissement des données de commandes depuis la base locale.
 * Mode lecture seule - pas de CRUD.
 *
 * @package STM
 * @version 3.0
 * @created 12/11/2025 19:00
 * @modified 29/12/2025 - Refonte complète en mode consultation uniquement
 */

namespace App\Controllers;

use App\Helpers\StatsAccessHelper;
use Core\Session;
use Core\Database;
use Core\ExternalDatabase;

class CustomerController
{
    /**
     * Afficher la liste des clients depuis la DB externe
     * Avec filtres en cascade (pays > cluster > rep) et stats de commandes
     *
     * @return void
     */
    public function index(): void
    {
        // Récupérer les filtres
        $filters = [
            'country' => $_GET['country'] ?? 'BE',
            'cluster' => $_GET['cluster'] ?? '',
            'rep_id' => $_GET['rep_id'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];

        // Récupérer les données selon le rôle
        $accessibleCustomerNumbers = StatsAccessHelper::getAccessibleCustomerNumbersOnly();

        // Récupérer les clients avec stats
        $customers = $this->getCustomersWithStats($filters, $accessibleCustomerNumbers);

        // Récupérer les clusters pour le filtre
        $clusters = $this->getClusters($filters['country']);

        // Récupérer les représentants pour le filtre (filtrés par cluster si sélectionné)
        $representatives = $this->getRepresentatives($filters['country'], $filters['cluster']);

        // Stats globales
        $stats = $this->getGlobalStats($filters['country'], $accessibleCustomerNumbers);

        // Charger la vue
        require_once __DIR__ . '/../Views/admin/customers/index.php';
    }

    /**
     * Afficher le détail d'un client
     *
     * @return void
     */
    public function show(): void
    {
        $customerNumber = $_GET['customer_number'] ?? '';
        $country = $_GET['country'] ?? 'BE';

        if (empty($customerNumber)) {
            Session::setFlash('error', 'Numéro client requis');
            header('Location: /stm/admin/customers');
            exit;
        }

        // Vérifier l'accès au client selon le rôle
        $accessibleCustomerNumbers = StatsAccessHelper::getAccessibleCustomerNumbersOnly();
        if ($accessibleCustomerNumbers !== null && !in_array($customerNumber, $accessibleCustomerNumbers)) {
            Session::setFlash('error', 'Vous n\'avez pas accès à ce client');
            header('Location: /stm/admin/customers');
            exit;
        }

        // Récupérer les infos du client depuis la DB externe
        $customer = $this->getCustomerFromExternal($customerNumber, $country);

        if (!$customer) {
            Session::setFlash('error', 'Client introuvable');
            header('Location: /stm/admin/customers');
            exit;
        }

        // Récupérer les stats du client
        $customerStats = $this->getCustomerStats($customerNumber, $country);

        // Récupérer les commandes du client
        $orders = $this->getCustomerOrders($customerNumber, $country);

        // Charger la vue
        require_once __DIR__ . '/../Views/admin/customers/show.php';
    }

    /**
     * API : Récupérer les clusters pour un pays (AJAX)
     *
     * @return void
     */
    public function getClustersApi(): void
    {
        header('Content-Type: application/json');

        $country = $_GET['country'] ?? 'BE';
        $clusters = $this->getClusters($country);

        echo json_encode([
            'success' => true,
            'clusters' => $clusters
        ]);
        exit;
    }

    /**
     * API : Récupérer les représentants pour un pays/cluster (AJAX)
     *
     * @return void
     */
    public function getRepresentativesApi(): void
    {
        header('Content-Type: application/json');

        $country = $_GET['country'] ?? 'BE';
        $cluster = $_GET['cluster'] ?? '';

        $representatives = $this->getRepresentatives($country, $cluster);

        echo json_encode([
            'success' => true,
            'representatives' => $representatives
        ]);
        exit;
    }

    /**
     * API : Récupérer le détail d'une commande (AJAX)
     *
     * @return void
     */
    public function getOrderDetailApi(): void
    {
        header('Content-Type: application/json');

        $orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

        if (!$orderId) {
            echo json_encode(['success' => false, 'error' => 'ID commande requis']);
            exit;
        }

        try {
            $db = Database::getInstance();

            // Récupérer la commande
            $orderQuery = "
                SELECT o.id, o.created_at, o.total_items, o.status,
                       c.name as campaign_name,
                       cu.customer_number, cu.company_name, cu.country
                FROM orders o
                LEFT JOIN campaigns c ON o.campaign_id = c.id
                INNER JOIN customers cu ON o.customer_id = cu.id
                WHERE o.id = :order_id
            ";
            $orderResult = $db->query($orderQuery, [':order_id' => $orderId]);

            if (empty($orderResult)) {
                echo json_encode(['success' => false, 'error' => 'Commande introuvable']);
                exit;
            }

            $order = $orderResult[0];

            // Vérifier l'accès au client
            $accessibleCustomerNumbers = StatsAccessHelper::getAccessibleCustomerNumbersOnly();
            if ($accessibleCustomerNumbers !== null && !in_array($order['customer_number'], $accessibleCustomerNumbers)) {
                echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
                exit;
            }

            // Récupérer les lignes de commande
            $linesQuery = "
                SELECT ol.quantity, p.product_code, p.name_fr as product_name, p.image_fr as product_image
                FROM order_lines ol
                INNER JOIN products p ON ol.product_id = p.id
                WHERE ol.order_id = :order_id
                ORDER BY ol.quantity DESC, p.name_fr ASC
            ";
            $lines = $db->query($linesQuery, [':order_id' => $orderId]);

            $totalQuantity = array_sum(array_column($lines, 'quantity'));

            echo json_encode([
                'success' => true,
                'order' => [
                    'id' => $order['id'],
                    'created_at' => $order['created_at'],
                    'campaign_name' => $order['campaign_name'] ?? 'N/A',
                    'total_items' => $order['total_items'],
                    'total_quantity' => $totalQuantity,
                    'lines' => $lines
                ]
            ]);

        } catch (\Exception $e) {
            error_log("getOrderDetailApi error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
        }

        exit;
    }

    /**
     * Récupérer les clients depuis la DB externe avec stats de commandes
     *
     * @param array $filters
     * @param array|null $accessibleCustomerNumbers
     * @return array
     */
    private function getCustomersWithStats(array $filters, ?array $accessibleCustomerNumbers): array
    {
        try {
            $extDb = ExternalDatabase::getInstance();
            $db = Database::getInstance();

            $country = $filters['country'];
            $table = $country === 'BE' ? 'BE_CLL' : 'LU_CLL';
            $repTable = $country === 'BE' ? 'BE_REP' : 'LU_REP';

            // Construire la requête externe
            $query = "
                SELECT
                    c.CLL_NCLIXX as customer_number,
                    c.CLL_NOM as company_name,
                    c.CLL_ADRESSE1 as address,
                    c.CLL_CPOSTAL as postal_code,
                    c.CLL_LOCALITE as city,
                    c.IDE_REP as rep_id,
                    CONCAT(r.REP_PRENOM, ' ', r.REP_NOM) as rep_name,
                    r.REP_CLU as cluster
                FROM {$table} c
                LEFT JOIN {$repTable} r ON c.IDE_REP = r.IDE_REP
                WHERE 1=1
            ";

            $params = [];

            // Filtre par cluster
            if (!empty($filters['cluster'])) {
                $query .= " AND r.REP_CLU = ?";
                $params[] = $filters['cluster'];
            }

            // Filtre par représentant
            if (!empty($filters['rep_id'])) {
                $query .= " AND c.IDE_REP = ?";
                $params[] = $filters['rep_id'];
            }

            // Filtre par recherche
            if (!empty($filters['search'])) {
                $query .= " AND (c.CLL_NCLIXX LIKE ? OR c.CLL_NOM LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            // Filtre par clients accessibles (selon rôle)
            if ($accessibleCustomerNumbers !== null) {
                if (empty($accessibleCustomerNumbers)) {
                    return [];
                }
                $placeholders = implode(',', array_fill(0, count($accessibleCustomerNumbers), '?'));
                $query .= " AND c.CLL_NCLIXX IN ({$placeholders})";
                $params = array_merge($params, $accessibleCustomerNumbers);
            }

            $query .= " ORDER BY c.CLL_NOM ASC LIMIT 500";

            $externalCustomers = $extDb->query($query, $params);

            if (empty($externalCustomers)) {
                return [];
            }

            // Récupérer les stats de commandes depuis la base locale
            $customerNumbers = array_column($externalCustomers, 'customer_number');
            $orderStats = $this->getOrderStatsForCustomers($customerNumbers, $country);

            // Enrichir les clients avec les stats
            foreach ($externalCustomers as &$customer) {
                $custNum = $customer['customer_number'];
                $customer['country'] = $country;
                $customer['rep_name'] = trim((string)($customer['rep_name'] ?? ''));
                if (empty($customer['rep_name'])) {
                    $customer['rep_name'] = $customer['rep_id'] ?? '-';
                }

                if (isset($orderStats[$custNum])) {
                    $customer['last_order_date'] = $orderStats[$custNum]['last_order_date'];
                    $customer['campaigns_count'] = $orderStats[$custNum]['campaigns_count'];
                    $customer['orders_count'] = $orderStats[$custNum]['orders_count'];
                    $customer['total_quantity'] = $orderStats[$custNum]['total_quantity'];
                } else {
                    $customer['last_order_date'] = null;
                    $customer['campaigns_count'] = 0;
                    $customer['orders_count'] = 0;
                    $customer['total_quantity'] = 0;
                }
            }

            return $externalCustomers;

        } catch (\Exception $e) {
            error_log("getCustomersWithStats error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les stats de commandes pour une liste de clients
     *
     * @param array $customerNumbers
     * @param string $country
     * @return array
     */
    private function getOrderStatsForCustomers(array $customerNumbers, string $country): array
    {
        if (empty($customerNumbers)) {
            return [];
        }

        try {
            $db = Database::getInstance();

            $placeholders = implode(',', array_fill(0, count($customerNumbers), '?'));
            $params = $customerNumbers;
            $params[] = $country;

            $query = "
                SELECT
                    cu.customer_number,
                    MAX(o.created_at) as last_order_date,
                    COUNT(DISTINCT o.campaign_id) as campaigns_count,
                    COUNT(DISTINCT o.id) as orders_count,
                    COALESCE(SUM(ol.quantity), 0) as total_quantity
                FROM customers cu
                INNER JOIN orders o ON cu.id = o.customer_id
                LEFT JOIN order_lines ol ON o.id = ol.order_id
                WHERE cu.customer_number IN ({$placeholders})
                AND cu.country = ?
                AND o.status = 'validated'
                GROUP BY cu.customer_number
            ";

            $results = $db->query($query, $params);

            $stats = [];
            foreach ($results as $row) {
                $stats[$row['customer_number']] = [
                    'last_order_date' => $row['last_order_date'],
                    'campaigns_count' => (int)$row['campaigns_count'],
                    'orders_count' => (int)$row['orders_count'],
                    'total_quantity' => (int)$row['total_quantity']
                ];
            }

            return $stats;

        } catch (\Exception $e) {
            error_log("getOrderStatsForCustomers error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer un client depuis la DB externe
     *
     * @param string $customerNumber
     * @param string $country
     * @return array|null
     */
    private function getCustomerFromExternal(string $customerNumber, string $country): ?array
    {
        try {
            $extDb = ExternalDatabase::getInstance();

            $table = $country === 'BE' ? 'BE_CLL' : 'LU_CLL';
            $repTable = $country === 'BE' ? 'BE_REP' : 'LU_REP';

            $query = "
                SELECT
                    c.CLL_NCLIXX as customer_number,
                    c.CLL_NOM as company_name,
                    c.CLL_PRENOM as contact_name,
                    c.CLL_ADRESSE1 as address1,
                    c.CLL_ADRESSE2 as address2,
                    c.CLL_CPOSTAL as postal_code,
                    c.CLL_LOCALITE as city,
                    c.IDE_REP as rep_id,
                    CONCAT(r.REP_PRENOM, ' ', r.REP_NOM) as rep_name,
                    r.REP_CLU as cluster,
                    r.REP_EMAIL as rep_email
                FROM {$table} c
                LEFT JOIN {$repTable} r ON c.IDE_REP = r.IDE_REP
                WHERE c.CLL_NCLIXX = ?
                LIMIT 1
            ";

            $result = $extDb->query($query, [$customerNumber]);

            if (empty($result)) {
                return null;
            }

            $customer = $result[0];
            $customer['country'] = $country;
            $customer['rep_name'] = trim((string)($customer['rep_name'] ?? ''));
            if (empty($customer['rep_name'])) {
                $customer['rep_name'] = $customer['rep_id'] ?? '-';
            }

            return $customer;

        } catch (\Exception $e) {
            error_log("getCustomerFromExternal error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer les stats d'un client
     *
     * @param string $customerNumber
     * @param string $country
     * @return array
     */
    private function getCustomerStats(string $customerNumber, string $country): array
    {
        try {
            $db = Database::getInstance();

            $query = "
                SELECT
                    COUNT(DISTINCT o.id) as orders_count,
                    COUNT(DISTINCT o.campaign_id) as campaigns_count,
                    COALESCE(SUM(ol.quantity), 0) as total_quantity,
                    MIN(o.created_at) as first_order_date,
                    MAX(o.created_at) as last_order_date
                FROM customers cu
                INNER JOIN orders o ON cu.id = o.customer_id
                LEFT JOIN order_lines ol ON o.id = ol.order_id
                WHERE cu.customer_number = :customer_number
                AND cu.country = :country
                AND o.status = 'validated'
            ";

            $result = $db->query($query, [
                ':customer_number' => $customerNumber,
                ':country' => $country
            ]);

            return $result[0] ?? [
                'orders_count' => 0,
                'campaigns_count' => 0,
                'total_quantity' => 0,
                'first_order_date' => null,
                'last_order_date' => null
            ];

        } catch (\Exception $e) {
            error_log("getCustomerStats error: " . $e->getMessage());
            return [
                'orders_count' => 0,
                'campaigns_count' => 0,
                'total_quantity' => 0,
                'first_order_date' => null,
                'last_order_date' => null
            ];
        }
    }

    /**
     * Récupérer les commandes d'un client
     *
     * @param string $customerNumber
     * @param string $country
     * @return array
     */
    private function getCustomerOrders(string $customerNumber, string $country): array
    {
        try {
            $db = Database::getInstance();

            $query = "
                SELECT
                    o.id,
                    o.created_at,
                    o.total_items,
                    o.status,
                    c.name as campaign_name,
                    c.id as campaign_id,
                    (SELECT SUM(ol2.quantity) FROM order_lines ol2 WHERE ol2.order_id = o.id) as total_quantity
                FROM orders o
                INNER JOIN customers cu ON o.customer_id = cu.id
                LEFT JOIN campaigns c ON o.campaign_id = c.id
                WHERE cu.customer_number = :customer_number
                AND cu.country = :country
                AND o.status = 'validated'
                ORDER BY o.created_at DESC
            ";

            return $db->query($query, [
                ':customer_number' => $customerNumber,
                ':country' => $country
            ]);

        } catch (\Exception $e) {
            error_log("getCustomerOrders error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les clusters pour un pays (uniquement ceux ayant des clients)
     *
     * @param string $country
     * @return array
     */
    private function getClusters(string $country): array
    {
        try {
            $extDb = ExternalDatabase::getInstance();
            $clientTable = $country === 'BE' ? 'BE_CLL' : 'LU_CLL';
            $repTable = $country === 'BE' ? 'BE_REP' : 'LU_REP';

            // Seulement les clusters qui ont des clients
            $query = "
                SELECT DISTINCT r.REP_CLU as cluster
                FROM {$repTable} r
                INNER JOIN {$clientTable} c ON c.IDE_REP = r.IDE_REP
                WHERE r.REP_CLU IS NOT NULL AND r.REP_CLU != ''
                ORDER BY r.REP_CLU
            ";

            $results = $extDb->query($query);
            return array_column($results, 'cluster');

        } catch (\Exception $e) {
            error_log("getClusters error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les représentants pour un pays (uniquement ceux ayant des clients)
     *
     * @param string $country
     * @param string $cluster
     * @return array
     */
    private function getRepresentatives(string $country, string $cluster = ''): array
    {
        try {
            $extDb = ExternalDatabase::getInstance();
            $clientTable = $country === 'BE' ? 'BE_CLL' : 'LU_CLL';
            $repTable = $country === 'BE' ? 'BE_REP' : 'LU_REP';

            // Seulement les représentants qui ont des clients
            $query = "
                SELECT DISTINCT
                    r.IDE_REP as rep_id,
                    CONCAT(r.REP_PRENOM, ' ', r.REP_NOM) as rep_name,
                    r.REP_CLU as cluster
                FROM {$repTable} r
                INNER JOIN {$clientTable} c ON c.IDE_REP = r.IDE_REP
                WHERE r.REP_NOM IS NOT NULL AND r.REP_NOM != ''
            ";

            $params = [];

            if (!empty($cluster)) {
                $query .= " AND r.REP_CLU = ?";
                $params[] = $cluster;
            }

            $query .= " ORDER BY r.REP_PRENOM, r.REP_NOM, r.REP_CLU";

            $results = $extDb->query($query, $params);

            // Nettoyer les noms
            foreach ($results as &$rep) {
                $rep['rep_name'] = trim((string)($rep['rep_name'] ?? ''));
                if (empty($rep['rep_name'])) {
                    $rep['rep_name'] = $rep['rep_id'] ?? '-';
                }
            }

            return $results;

        } catch (\Exception $e) {
            error_log("getRepresentatives error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les stats globales
     *
     * @param string $country
     * @param array|null $accessibleCustomerNumbers
     * @return array
     */
    private function getGlobalStats(string $country, ?array $accessibleCustomerNumbers): array
    {
        try {
            $extDb = ExternalDatabase::getInstance();
            $db = Database::getInstance();

            $table = $country === 'BE' ? 'BE_CLL' : 'LU_CLL';

            // Total clients dans la DB externe
            $totalQuery = "SELECT COUNT(*) as total FROM {$table}";

            if ($accessibleCustomerNumbers !== null) {
                if (empty($accessibleCustomerNumbers)) {
                    return ['total_external' => 0, 'total_with_orders' => 0, 'total_orders' => 0];
                }
                $placeholders = implode(',', array_fill(0, count($accessibleCustomerNumbers), '?'));
                $totalQuery = "SELECT COUNT(*) as total FROM {$table} WHERE CLL_NCLIXX IN ({$placeholders})";
                $totalResult = $extDb->query($totalQuery, $accessibleCustomerNumbers);
            } else {
                $totalResult = $extDb->query($totalQuery);
            }

            $totalExternal = $totalResult[0]['total'] ?? 0;

            // Clients avec commandes
            $ordersQuery = "
                SELECT
                    COUNT(DISTINCT cu.customer_number) as customers_with_orders,
                    COUNT(DISTINCT o.id) as total_orders
                FROM customers cu
                INNER JOIN orders o ON cu.id = o.customer_id
                WHERE cu.country = :country
                AND o.status = 'validated'
            ";

            if ($accessibleCustomerNumbers !== null) {
                $placeholders = implode(',', array_fill(0, count($accessibleCustomerNumbers), '?'));
                $ordersQuery = "
                    SELECT
                        COUNT(DISTINCT cu.customer_number) as customers_with_orders,
                        COUNT(DISTINCT o.id) as total_orders
                    FROM customers cu
                    INNER JOIN orders o ON cu.id = o.customer_id
                    WHERE cu.country = ?
                    AND o.status = 'validated'
                    AND cu.customer_number IN ({$placeholders})
                ";
                $params = array_merge([$country], $accessibleCustomerNumbers);
                $ordersResult = $db->query($ordersQuery, $params);
            } else {
                $ordersResult = $db->query($ordersQuery, [':country' => $country]);
            }

            return [
                'total_external' => (int)$totalExternal,
                'total_with_orders' => (int)($ordersResult[0]['customers_with_orders'] ?? 0),
                'total_orders' => (int)($ordersResult[0]['total_orders'] ?? 0)
            ];

        } catch (\Exception $e) {
            error_log("getGlobalStats error: " . $e->getMessage());
            return ['total_external' => 0, 'total_with_orders' => 0, 'total_orders' => 0];
        }
    }
}