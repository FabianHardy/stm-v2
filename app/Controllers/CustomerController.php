<?php
/**
 * CustomerController - Consultation des clients
 *
 * Module de consultation des clients depuis la base externe (BE_CLL/LU_CLL)
 * avec enrichissement des données de commandes depuis la base locale.
 * Mode lecture seule - pas de CRUD.
 *
 * SCOPE PAR RÔLE :
 * - superadmin/admin : Voit TOUS les clients
 * - manager_reps : Voit les clients de SES reps
 * - rep : Voit uniquement SES clients
 *
 * @package STM
 * @version 3.1
 * @created 12/11/2025 19:00
 * @modified 29/12/2025 - Refonte complète en mode consultation uniquement
 * @modified 30/12/2025 - Ajout vérifications de permissions
 */

namespace App\Controllers;

use App\Helpers\StatsAccessHelper;
use App\Helpers\PermissionHelper;
use Core\Session;
use Core\Database;
use Core\ExternalDatabase;

class CustomerController
{
    /**
     * Vérifie la permission de visualisation des clients
     *
     * @return void
     */
    private function requireViewPermission(): void
    {
        if (!PermissionHelper::can('customers.view')) {
            Session::setFlash('error', 'Vous n\'avez pas accès à cette fonctionnalité.');
            header('Location: /stm/admin/dashboard');
            exit;
        }
    }

    /**
     * Afficher la liste des clients depuis la DB externe
     * Avec filtres en cascade (pays > cluster > rep) et stats de commandes
     *
     * @return void
     */
    public function index(): void
    {
        $this->requireViewPermission();

        // Pagination
        $perPage = (int)($_GET['per_page'] ?? 50);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 50;
        $page = max(1, (int)($_GET['page'] ?? 1));

        // Tri
        $allowedSortColumns = ['company_name', 'customer_number', 'rep_name', 'cluster', 'last_order_date', 'campaigns_count', 'orders_count', 'total_quantity'];
        $sort = $_GET['sort'] ?? 'last_order_date';
        $sort = in_array($sort, $allowedSortColumns) ? $sort : 'last_order_date';
        $order = $_GET['order'] ?? ($sort === 'last_order_date' ? 'desc' : 'asc');
        $order = $order === 'desc' ? 'desc' : 'asc';

        // Récupérer les filtres
        $filters = [
            'country' => $_GET['country'] ?? 'BE',
            'cluster' => $_GET['cluster'] ?? '',
            'rep_id' => $_GET['rep_id'] ?? '',
            'search' => $_GET['search'] ?? '',
            'sort' => $sort,
            'order' => $order
        ];

        // Récupérer les données selon le rôle (scope géré par StatsAccessHelper)
        $accessibleCustomerNumbers = StatsAccessHelper::getAccessibleCustomerNumbersOnly();

        // Compter le total de clients pour la pagination
        $totalCustomers = $this->countCustomers($filters, $accessibleCustomerNumbers);
        $totalPages = ceil($totalCustomers / $perPage);
        $page = min($page, max(1, $totalPages)); // S'assurer que la page est valide

        // Récupérer les clients avec stats (paginés)
        $customers = $this->getCustomersWithStats($filters, $accessibleCustomerNumbers, $page, $perPage);

        // Récupérer les clusters pour les DEUX pays (pour filtrage côté client)
        $allClusters = [
            'BE' => $this->getClusters('BE'),
            'LU' => $this->getClusters('LU')
        ];

        // Récupérer les représentants pour les DEUX pays (pour filtrage côté client)
        $allRepresentatives = [
            'BE' => $this->getRepresentatives('BE', ''),
            'LU' => $this->getRepresentatives('LU', '')
        ];

        // Stats globales
        $stats = $this->getGlobalStats($filters['country'], $accessibleCustomerNumbers);

        // Pagination info
        $pagination = [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $totalCustomers,
            'total_pages' => $totalPages
        ];

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
        $this->requireViewPermission();

        $customerNumber = $_GET['customer_number'] ?? '';
        $country = $_GET['country'] ?? 'BE';

        if (empty($customerNumber)) {
            Session::setFlash('error', 'Numéro client requis');
            header('Location: /stm/admin/customers');
            exit;
        }

        // Vérifier l'accès au client selon le rôle (scope géré par StatsAccessHelper)
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

        // Vérifier la permission
        if (!PermissionHelper::can('customers.view')) {
            echo json_encode(['success' => false, 'error' => 'Permission refusée']);
            exit;
        }

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

        // Vérifier la permission
        if (!PermissionHelper::can('customers.view')) {
            echo json_encode(['success' => false, 'error' => 'Permission refusée']);
            exit;
        }

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

        // Vérifier la permission
        if (!PermissionHelper::can('customers.view')) {
            echo json_encode(['success' => false, 'error' => 'Permission refusée']);
            exit;
        }

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

            echo json_encode([
                'success' => true,
                'order' => $order,
                'lines' => $lines
            ]);

        } catch (\Exception $e) {
            error_log("getOrderDetailApi error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
        }

        exit;
    }

    // ========================================
    // MÉTHODES PRIVÉES
    // ========================================

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
            $clientTable = $country === 'BE' ? 'BE_CLL' : 'LU_CLL';
            $repTable = $country === 'BE' ? 'BE_REP' : 'LU_REP';

            $query = "
                SELECT
                    c.CLL_NCLIXX as customer_number,
                    c.CLL_NOM as company_name,
                    c.CLL_ADRESSE1 as address,
                    c.CLL_CPOSTAL as postal_code,
                    c.CLL_LOCALITE as city,
                    c.IDE_REP as rep_id,
                    r.REP_CLU as cluster,
                    CONCAT(r.REP_PRENOM, ' ', r.REP_NOM) as rep_name
                FROM {$clientTable} c
                LEFT JOIN {$repTable} r ON c.IDE_REP = r.IDE_REP
                WHERE c.CLL_NCLIXX = ?
                LIMIT 1
            ";

            $result = $extDb->query($query, [$customerNumber]);

            // S'assurer que c'est un array
            if (!is_array($result) || empty($result)) {
                return null;
            }

            // Ajouter le pays (déterminé par la table utilisée)
            $result[0]['country'] = $country;

            return $result[0];

        } catch (\Exception $e) {
            error_log("getCustomerFromExternal error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer les clients avec stats (paginés)
     * Gère le tri par colonnes externes (company_name, etc.) OU par stats locales (last_order_date, etc.)
     *
     * @param array $filters
     * @param array|null $accessibleCustomerNumbers
     * @param int $page
     * @param int $perPage
     * @return array
     */
    private function getCustomersWithStats(array $filters, ?array $accessibleCustomerNumbers, int $page, int $perPage): array
    {
        $country = $filters['country'];
        $sort = $filters['sort'] ?? 'last_order_date';
        $order = strtoupper($filters['order'] ?? 'DESC');

        // Colonnes de tri qui viennent des stats (DB locale)
        $statsColumns = ['last_order_date', 'orders_count', 'campaigns_count', 'total_quantity'];

        // Si tri par colonne de stats, on récupère d'abord les customer_numbers triés depuis la DB locale
        if (in_array($sort, $statsColumns)) {
            return $this->getCustomersSortedByStats($filters, $accessibleCustomerNumbers, $page, $perPage);
        }

        // Sinon, tri par colonne de la DB externe (company_name, customer_number, etc.)
        return $this->getCustomersSortedByExternal($filters, $accessibleCustomerNumbers, $page, $perPage);
    }

    /**
     * Récupérer les clients triés par une colonne de stats (DB locale en premier)
     *
     * @param array $filters
     * @param array|null $accessibleCustomerNumbers
     * @param int $page
     * @param int $perPage
     * @return array
     */
    private function getCustomersSortedByStats(array $filters, ?array $accessibleCustomerNumbers, int $page, int $perPage): array
    {
        try {
            $db = Database::getInstance();
            $extDb = ExternalDatabase::getInstance();

            $country = $filters['country'];
            $sort = $filters['sort'] ?? 'last_order_date';
            $order = strtoupper($filters['order'] ?? 'DESC') === 'DESC' ? 'DESC' : 'ASC';
            $clientTable = $country === 'BE' ? 'BE_CLL' : 'LU_CLL';
            $repTable = $country === 'BE' ? 'BE_REP' : 'LU_REP';
            $offset = ($page - 1) * $perPage;

            // Mapping des colonnes de tri
            $sortColumn = match($sort) {
                'last_order_date' => 'last_order_date',
                'orders_count' => 'orders_count',
                'campaigns_count' => 'campaigns_count',
                'total_quantity' => 'total_quantity',
                default => 'last_order_date'
            };

            // Construire la requête pour récupérer les customer_numbers triés par stats
            $sql = "
                SELECT
                    cu.customer_number,
                    COUNT(DISTINCT o.id) as orders_count,
                    COUNT(DISTINCT o.campaign_id) as campaigns_count,
                    COALESCE(SUM(o.total_items), 0) as total_quantity,
                    MAX(o.created_at) as last_order_date
                FROM customers cu
                LEFT JOIN orders o ON cu.id = o.customer_id AND o.status = 'synced'
                WHERE cu.country = ?
            ";
            $params = [$country];

            // Filtrer par numéros accessibles (scope)
            if ($accessibleCustomerNumbers !== null) {
                if (empty($accessibleCustomerNumbers)) {
                    return [];
                }
                $placeholders = implode(',', array_fill(0, count($accessibleCustomerNumbers), '?'));
                $sql .= " AND cu.customer_number IN ({$placeholders})";
                $params = array_merge($params, $accessibleCustomerNumbers);
            }

            // Sous-requête pour les filtres de la DB externe
            $externalFilters = $this->buildExternalFiltersSubquery($filters, $country);
            if (!empty($externalFilters['customer_numbers'])) {
                $placeholders = implode(',', array_fill(0, count($externalFilters['customer_numbers']), '?'));
                $sql .= " AND cu.customer_number IN ({$placeholders})";
                $params = array_merge($params, $externalFilters['customer_numbers']);
            } elseif ($externalFilters['has_filters']) {
                // Si on a des filtres externes mais aucun résultat
                return [];
            }

            $sql .= " GROUP BY cu.customer_number";
            $sql .= " ORDER BY {$sortColumn} {$order}";
            $sql .= " LIMIT {$perPage} OFFSET {$offset}";

            $statsResults = $db->query($sql, $params);

            if (!is_array($statsResults) || empty($statsResults)) {
                return [];
            }

            // Récupérer les infos clients depuis la DB externe
            $customerNumbers = array_column($statsResults, 'customer_number');
            $placeholders = implode(',', array_fill(0, count($customerNumbers), '?'));

            $extSql = "
                SELECT
                    c.CLL_NCLIXX as customer_number,
                    c.CLL_NOM as company_name,
                    c.IDE_REP as rep_id,
                    r.REP_CLU as cluster,
                    CONCAT(r.REP_PRENOM, ' ', r.REP_NOM) as rep_name
                FROM {$clientTable} c
                LEFT JOIN {$repTable} r ON c.IDE_REP = r.IDE_REP
                WHERE c.CLL_NCLIXX IN ({$placeholders})
            ";

            $extResults = $extDb->query($extSql, $customerNumbers);

            if (!is_array($extResults)) {
                $extResults = [];
            }

            // Créer un map des infos externes
            $extMap = [];
            foreach ($extResults as $row) {
                $extMap[$row['customer_number']] = $row;
            }

            // Fusionner les résultats (garder l'ordre des stats)
            $customers = [];
            foreach ($statsResults as $stat) {
                $num = $stat['customer_number'];
                $ext = $extMap[$num] ?? [];

                $customers[] = [
                    'customer_number' => $num,
                    'company_name' => $ext['company_name'] ?? 'Client ' . $num,
                    'rep_id' => $ext['rep_id'] ?? null,
                    'cluster' => $ext['cluster'] ?? null,
                    'rep_name' => $ext['rep_name'] ?? null,
                    'country' => $country,
                    'orders_count' => (int)$stat['orders_count'],
                    'campaigns_count' => (int)$stat['campaigns_count'],
                    'total_quantity' => (int)$stat['total_quantity'],
                    'last_order_date' => $stat['last_order_date']
                ];
            }

            return $customers;

        } catch (\Exception $e) {
            error_log("getCustomersSortedByStats error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les clients triés par une colonne externe (DB externe en premier)
     *
     * @param array $filters
     * @param array|null $accessibleCustomerNumbers
     * @param int $page
     * @param int $perPage
     * @return array
     */
    private function getCustomersSortedByExternal(array $filters, ?array $accessibleCustomerNumbers, int $page, int $perPage): array
    {
        try {
            $extDb = ExternalDatabase::getInstance();
            $db = Database::getInstance();

            $country = $filters['country'];
            $clientTable = $country === 'BE' ? 'BE_CLL' : 'LU_CLL';
            $repTable = $country === 'BE' ? 'BE_REP' : 'LU_REP';

            // Construire la requête de base
            $sql = "
                SELECT
                    c.CLL_NCLIXX as customer_number,
                    c.CLL_NOM as company_name,
                    c.IDE_REP as rep_id,
                    r.REP_CLU as cluster,
                    CONCAT(r.REP_PRENOM, ' ', r.REP_NOM) as rep_name
                FROM {$clientTable} c
                LEFT JOIN {$repTable} r ON c.IDE_REP = r.IDE_REP
                WHERE 1=1
            ";

            $params = [];

            // Filtrer par numéros clients accessibles (scope)
            if ($accessibleCustomerNumbers !== null) {
                if (empty($accessibleCustomerNumbers)) {
                    return [];
                }
                $placeholders = implode(',', array_fill(0, count($accessibleCustomerNumbers), '?'));
                $sql .= " AND c.CLL_NCLIXX IN ({$placeholders})";
                $params = array_merge($params, $accessibleCustomerNumbers);
            }

            // Appliquer les filtres
            if (!empty($filters['cluster'])) {
                $sql .= " AND r.REP_CLU = ?";
                $params[] = $filters['cluster'];
            }

            if (!empty($filters['rep_id'])) {
                $sql .= " AND c.IDE_REP = ?";
                $params[] = $filters['rep_id'];
            }

            if (!empty($filters['search'])) {
                $sql .= " AND (c.CLL_NCLIXX LIKE ? OR c.CLL_NOM LIKE ?)";
                $params[] = '%' . $filters['search'] . '%';
                $params[] = '%' . $filters['search'] . '%';
            }

            // Tri
            $sortColumn = match($filters['sort']) {
                'customer_number' => 'c.CLL_NCLIXX',
                'company_name' => 'c.CLL_NOM',
                'rep_name' => 'r.REP_NOM',
                'cluster' => 'r.REP_CLU',
                default => 'c.CLL_NOM'
            };
            $sortOrder = strtoupper($filters['order']) === 'DESC' ? 'DESC' : 'ASC';
            $sql .= " ORDER BY {$sortColumn} {$sortOrder}";

            // Pagination
            $offset = ($page - 1) * $perPage;
            $sql .= " LIMIT {$perPage} OFFSET {$offset}";

            $customers = $extDb->query($sql, $params);

            // S'assurer que c'est un array (query peut retourner false)
            if (!is_array($customers)) {
                return [];
            }

            // Ajouter le pays à chaque client (déterminé par la table utilisée)
            foreach ($customers as &$customer) {
                $customer['country'] = $country;
            }
            unset($customer);

            // Enrichir avec les stats de commandes depuis la DB locale
            if (!empty($customers)) {
                $customerNumbers = array_column($customers, 'customer_number');
                $statsMap = $this->getCustomersStatsMap($customerNumbers, $country);

                foreach ($customers as &$customer) {
                    $num = $customer['customer_number'];
                    $customer['orders_count'] = $statsMap[$num]['orders_count'] ?? 0;
                    $customer['campaigns_count'] = $statsMap[$num]['campaigns_count'] ?? 0;
                    $customer['total_quantity'] = $statsMap[$num]['total_quantity'] ?? 0;
                    $customer['last_order_date'] = $statsMap[$num]['last_order_date'] ?? null;
                }
            }

            return $customers;

        } catch (\Exception $e) {
            error_log("getCustomersSortedByExternal error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Construire une sous-requête pour les filtres de la DB externe
     * Retourne les customer_numbers qui matchent les filtres
     *
     * @param array $filters
     * @param string $country
     * @return array ['customer_numbers' => [], 'has_filters' => bool]
     */
    private function buildExternalFiltersSubquery(array $filters, string $country): array
    {
        $hasFilters = !empty($filters['cluster']) || !empty($filters['rep_id']) || !empty($filters['search']);

        if (!$hasFilters) {
            return ['customer_numbers' => null, 'has_filters' => false];
        }

        try {
            $extDb = ExternalDatabase::getInstance();
            $clientTable = $country === 'BE' ? 'BE_CLL' : 'LU_CLL';
            $repTable = $country === 'BE' ? 'BE_REP' : 'LU_REP';

            $sql = "
                SELECT c.CLL_NCLIXX as customer_number
                FROM {$clientTable} c
                LEFT JOIN {$repTable} r ON c.IDE_REP = r.IDE_REP
                WHERE 1=1
            ";
            $params = [];

            if (!empty($filters['cluster'])) {
                $sql .= " AND r.REP_CLU = ?";
                $params[] = $filters['cluster'];
            }

            if (!empty($filters['rep_id'])) {
                $sql .= " AND c.IDE_REP = ?";
                $params[] = $filters['rep_id'];
            }

            if (!empty($filters['search'])) {
                $sql .= " AND (c.CLL_NCLIXX LIKE ? OR c.CLL_NOM LIKE ?)";
                $params[] = '%' . $filters['search'] . '%';
                $params[] = '%' . $filters['search'] . '%';
            }

            $results = $extDb->query($sql, $params);

            if (!is_array($results)) {
                return ['customer_numbers' => [], 'has_filters' => true];
            }

            return [
                'customer_numbers' => array_column($results, 'customer_number'),
                'has_filters' => true
            ];

        } catch (\Exception $e) {
            error_log("buildExternalFiltersSubquery error: " . $e->getMessage());
            return ['customer_numbers' => [], 'has_filters' => true];
        }
    }

    /**
     * Compter les clients avec filtres
     * Gère le cas où on trie par stats (seuls les clients avec commandes sont comptés)
     *
     * @param array $filters
     * @param array|null $accessibleCustomerNumbers
     * @return int
     */
    private function countCustomers(array $filters, ?array $accessibleCustomerNumbers): int
    {
        $sort = $filters['sort'] ?? 'last_order_date';
        $statsColumns = ['last_order_date', 'orders_count', 'campaigns_count', 'total_quantity'];

        // Si tri par colonne de stats, on compte depuis la DB locale
        if (in_array($sort, $statsColumns)) {
            return $this->countCustomersByStats($filters, $accessibleCustomerNumbers);
        }

        // Sinon, on compte depuis la DB externe
        return $this->countCustomersByExternal($filters, $accessibleCustomerNumbers);
    }

    /**
     * Compter les clients depuis la DB locale (ceux qui ont des commandes)
     *
     * @param array $filters
     * @param array|null $accessibleCustomerNumbers
     * @return int
     */
    private function countCustomersByStats(array $filters, ?array $accessibleCustomerNumbers): int
    {
        try {
            $db = Database::getInstance();
            $country = $filters['country'];

            $sql = "
                SELECT COUNT(DISTINCT cu.customer_number) as total
                FROM customers cu
                LEFT JOIN orders o ON cu.id = o.customer_id AND o.status = 'synced'
                WHERE cu.country = ?
            ";
            $params = [$country];

            // Filtrer par numéros accessibles (scope)
            if ($accessibleCustomerNumbers !== null) {
                if (empty($accessibleCustomerNumbers)) {
                    return 0;
                }
                $placeholders = implode(',', array_fill(0, count($accessibleCustomerNumbers), '?'));
                $sql .= " AND cu.customer_number IN ({$placeholders})";
                $params = array_merge($params, $accessibleCustomerNumbers);
            }

            // Sous-requête pour les filtres de la DB externe
            $externalFilters = $this->buildExternalFiltersSubquery($filters, $country);
            if (!empty($externalFilters['customer_numbers'])) {
                $placeholders = implode(',', array_fill(0, count($externalFilters['customer_numbers']), '?'));
                $sql .= " AND cu.customer_number IN ({$placeholders})";
                $params = array_merge($params, $externalFilters['customer_numbers']);
            } elseif ($externalFilters['has_filters']) {
                return 0;
            }

            $result = $db->query($sql, $params);

            if (!is_array($result) || empty($result)) {
                return 0;
            }

            return (int)($result[0]['total'] ?? 0);

        } catch (\Exception $e) {
            error_log("countCustomersByStats error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Compter les clients depuis la DB externe
     *
     * @param array $filters
     * @param array|null $accessibleCustomerNumbers
     * @return int
     */
    private function countCustomersByExternal(array $filters, ?array $accessibleCustomerNumbers): int
    {
        try {
            $extDb = ExternalDatabase::getInstance();

            $country = $filters['country'];
            $clientTable = $country === 'BE' ? 'BE_CLL' : 'LU_CLL';
            $repTable = $country === 'BE' ? 'BE_REP' : 'LU_REP';

            $sql = "
                SELECT COUNT(*) as total
                FROM {$clientTable} c
                LEFT JOIN {$repTable} r ON c.IDE_REP = r.IDE_REP
                WHERE 1=1
            ";

            $params = [];

            // Filtrer par numéros clients accessibles (scope)
            if ($accessibleCustomerNumbers !== null) {
                if (empty($accessibleCustomerNumbers)) {
                    return 0;
                }
                $placeholders = implode(',', array_fill(0, count($accessibleCustomerNumbers), '?'));
                $sql .= " AND c.CLL_NCLIXX IN ({$placeholders})";
                $params = array_merge($params, $accessibleCustomerNumbers);
            }

            if (!empty($filters['cluster'])) {
                $sql .= " AND r.REP_CLU = ?";
                $params[] = $filters['cluster'];
            }

            if (!empty($filters['rep_id'])) {
                $sql .= " AND c.IDE_REP = ?";
                $params[] = $filters['rep_id'];
            }

            if (!empty($filters['search'])) {
                $sql .= " AND (c.CLL_NCLIXX LIKE ? OR c.CLL_NOM LIKE ?)";
                $params[] = '%' . $filters['search'] . '%';
                $params[] = '%' . $filters['search'] . '%';
            }

            $result = $extDb->query($sql, $params);

            // S'assurer que c'est un array
            if (!is_array($result) || empty($result)) {
                return 0;
            }

            return (int)($result[0]['total'] ?? 0);

        } catch (\Exception $e) {
            error_log("countCustomersByExternal error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupérer les stats de commandes pour une liste de clients
     *
     * @param array $customerNumbers
     * @param string $country
     * @return array
     */
    private function getCustomersStatsMap(array $customerNumbers, string $country): array
    {
        if (empty($customerNumbers)) {
            return [];
        }

        try {
            $db = Database::getInstance();

            $placeholders = implode(',', array_fill(0, count($customerNumbers), '?'));
            $params = array_merge($customerNumbers, [$country]);

            $query = "
                SELECT
                    cu.customer_number,
                    COUNT(DISTINCT o.id) as orders_count,
                    COUNT(DISTINCT o.campaign_id) as campaigns_count,
                    SUM(o.total_items) as total_quantity,
                    MAX(o.created_at) as last_order_date
                FROM customers cu
                INNER JOIN orders o ON cu.id = o.customer_id
                WHERE cu.customer_number IN ({$placeholders})
                AND cu.country = ?
                AND o.status = 'synced'
                GROUP BY cu.customer_number
            ";

            $results = $db->query($query, $params);

            // S'assurer que c'est un array
            if (!is_array($results)) {
                return [];
            }

            $map = [];
            foreach ($results as $row) {
                $map[$row['customer_number']] = $row;
            }

            return $map;

        } catch (\Exception $e) {
            error_log("getCustomersStatsMap error: " . $e->getMessage());
            return [];
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
                    SUM(o.total_items) as total_quantity,
                    MIN(o.created_at) as first_order_date,
                    MAX(o.created_at) as last_order_date
                FROM customers cu
                INNER JOIN orders o ON cu.id = o.customer_id
                WHERE cu.customer_number = :customer_number
                AND cu.country = :country
                AND o.status = 'synced'
            ";

            $result = $db->query($query, [
                ':customer_number' => $customerNumber,
                ':country' => $country
            ]);

            // S'assurer que c'est un array
            if (!is_array($result) || empty($result)) {
                return [
                    'orders_count' => 0,
                    'campaigns_count' => 0,
                    'total_quantity' => 0,
                    'first_order_date' => null,
                    'last_order_date' => null
                ];
            }

            return $result[0];

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
                AND o.status = 'synced'
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

            // S'assurer que c'est un array
            if (!is_array($results)) {
                return [];
            }

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

            // S'assurer que c'est un array
            if (!is_array($results)) {
                return [];
            }

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

            // S'assurer que c'est un array
            $totalExternal = (is_array($totalResult) && isset($totalResult[0]['total']))
                ? $totalResult[0]['total']
                : 0;

            // Clients avec commandes
            $ordersQuery = "
                SELECT
                    COUNT(DISTINCT cu.customer_number) as customers_with_orders,
                    COUNT(DISTINCT o.id) as total_orders
                FROM customers cu
                INNER JOIN orders o ON cu.id = o.customer_id
                WHERE cu.country = :country
                AND o.status = 'synced'
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
                    AND o.status = 'synced'
                    AND cu.customer_number IN ({$placeholders})
                ";
                $params = array_merge([$country], $accessibleCustomerNumbers);
                $ordersResult = $db->query($ordersQuery, $params);
            } else {
                $ordersResult = $db->query($ordersQuery, [':country' => $country]);
            }

            // S'assurer que c'est un array
            $customersWithOrders = (is_array($ordersResult) && isset($ordersResult[0]['customers_with_orders']))
                ? $ordersResult[0]['customers_with_orders']
                : 0;
            $totalOrders = (is_array($ordersResult) && isset($ordersResult[0]['total_orders']))
                ? $ordersResult[0]['total_orders']
                : 0;

            return [
                'total_external' => (int)$totalExternal,
                'total_with_orders' => (int)$customersWithOrders,
                'total_orders' => (int)$totalOrders
            ];

        } catch (\Exception $e) {
            error_log("getGlobalStats error: " . $e->getMessage());
            return ['total_external' => 0, 'total_with_orders' => 0, 'total_orders' => 0];
        }
    }
}