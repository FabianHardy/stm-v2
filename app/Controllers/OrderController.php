<?php
/**
 * Contrôleur des commandes (Admin)
 *
 * Gère l'affichage et la gestion des commandes côté administration.
 * Inclut l'export TXT au format ERP identique à la validation publique.
 *
 * SCOPE PAR RÔLE :
 * - superadmin/admin : Voit TOUT
 * - createur : Voit les commandes de SES campagnes
 * - manager_reps : Voit les commandes de ses reps
 * - rep : Voit les commandes de SES clients
 *
 * @package    App\Controllers
 * @author     Fabian Hardy
 * @version    1.4.0
 * @created    2025/11/27
 * @modified   2025/11/27 - Fix colonnes customers (pas de contact_name, phone, address)
 * @modified   2025/11/27 - Correction exportTxt() : format ERP identique à generateOrderFile()
 * @modified   2025/12/29 - Ajout index(), today(), pending(), export(), updateStatus(), regenerateFile()
 * @modified   2025/12/30 - Ajout vérifications de permissions et filtrage par scope
 * @modified   2026/01/08 - Ajout filtre source (client/rep) + jointure rep dans show()
 * @modified   2026/01/08 - Sprint 15 : Ajout statut validated, export Excel, génération TXT à la demande
 */

namespace App\Controllers;

use Core\Database;
use Core\Session;
use App\Helpers\PermissionHelper;

class OrderController
{
    /**
     * Instance de la base de données
     * @var Database
     */
    private Database $db;

    /**
     * Statuts de synchronisation
     */
    public const STATUS_PENDING_SYNC = 'pending_sync';
    public const STATUS_VALIDATED = 'validated';  // Sprint 15 : Commande validée mais pas de TXT
    public const STATUS_SYNCED = 'synced';
    public const STATUS_ERROR = 'error';

    public const STATUSES = [
        self::STATUS_PENDING_SYNC => 'En attente de synchro',
        self::STATUS_VALIDATED => 'Validée (en attente)', // Sprint 15
        self::STATUS_SYNCED => 'Synchronisée',
        self::STATUS_ERROR => 'Erreur'
    ];

    public const STATUS_COLORS = [
        self::STATUS_PENDING_SYNC => 'yellow',
        self::STATUS_VALIDATED => 'orange',  // Sprint 15
        self::STATUS_SYNCED => 'green',
        self::STATUS_ERROR => 'red'
    ];

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Vérifie la permission de visualisation des commandes
     *
     * @return void
     */
    private function requireViewPermission(): void
    {
        if (!PermissionHelper::can('orders.view')) {
            Session::setFlash('error', 'Vous n\'avez pas accès aux commandes.');
            header('Location: /stm/admin/dashboard');
            exit;
        }
    }

    /**
     * Vérifie la permission d'export
     *
     * @return void
     */
    private function requireExportPermission(): void
    {
        if (!PermissionHelper::can('orders.export')) {
            Session::setFlash('error', 'Vous n\'avez pas la permission d\'exporter les commandes.');
            header('Location: /stm/admin/orders');
            exit;
        }
    }

    /**
     * Liste des commandes avec filtres et pagination
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

        // Filtres
        $filters = [
            'campaign_id' => $_GET['campaign_id'] ?? '',
            'status' => $_GET['status'] ?? '',
            'country' => $_GET['country'] ?? '',
            'source' => $_GET['source'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];

        // Compter le total
        $totalOrders = $this->countOrders($filters);
        $totalPages = ceil($totalOrders / $perPage);
        $page = min($page, max(1, $totalPages));

        // Récupérer les commandes
        $orders = $this->getOrders($filters, $page, $perPage);

        // Données pour les filtres (filtrées selon le scope)
        $campaigns = $this->getCampaignsWithOrders();
        $statuses = self::STATUSES;

        // Stats (filtrées par scope)
        $stats = $this->getStats();

        // Pagination info
        $pagination = [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $totalOrders,
            'total_pages' => $totalPages
        ];

        // Permissions pour la vue
        $canExport = PermissionHelper::can('orders.export');

        // Vue
        $pageTitle = 'Toutes les commandes';
        require __DIR__ . '/../Views/admin/orders/index.php';
    }

    /**
     * Commandes du jour
     *
     * @return void
     */
    public function today(): void
    {
        if (!PermissionHelper::can('orders.today')) {
            Session::setFlash('error', 'Vous n\'avez pas accès à cette fonctionnalité.');
            header('Location: /stm/admin/orders');
            exit;
        }

        // Pagination
        $perPage = (int)($_GET['per_page'] ?? 50);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 50;
        $page = max(1, (int)($_GET['page'] ?? 1));

        // Filtres avec today forcé
        $filters = [
            'campaign_id' => $_GET['campaign_id'] ?? '',
            'status' => $_GET['status'] ?? '',
            'country' => $_GET['country'] ?? '',
            'source' => $_GET['source'] ?? '',
            'search' => $_GET['search'] ?? '',
            'today' => true
        ];

        // Compter et récupérer
        $totalOrders = $this->countOrders($filters);
        $totalPages = ceil($totalOrders / $perPage);
        $page = min($page, max(1, $totalPages));

        $orders = $this->getOrders($filters, $page, $perPage);

        // Données pour les filtres
        $campaigns = $this->getCampaignsWithOrders();
        $statuses = self::STATUSES;
        $stats = $this->getStats();

        $pagination = [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $totalOrders,
            'total_pages' => $totalPages
        ];

        // Permissions pour la vue
        $canExport = PermissionHelper::can('orders.export');

        $pageTitle = 'Commandes du jour';
        $isToday = true;
        require __DIR__ . '/../Views/admin/orders/index.php';
    }

    /**
     * Commandes en attente de synchronisation
     *
     * @return void
     */
    public function pending(): void
    {
        if (!PermissionHelper::can('orders.pending')) {
            Session::setFlash('error', 'Vous n\'avez pas accès à cette fonctionnalité.');
            header('Location: /stm/admin/orders');
            exit;
        }

        // Pagination
        $perPage = (int)($_GET['per_page'] ?? 50);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 50;
        $page = max(1, (int)($_GET['page'] ?? 1));

        // Filtres avec status forcé
        $filters = [
            'campaign_id' => $_GET['campaign_id'] ?? '',
            'status' => self::STATUS_PENDING_SYNC,
            'country' => $_GET['country'] ?? '',
            'source' => $_GET['source'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];

        $totalOrders = $this->countOrders($filters);
        $totalPages = ceil($totalOrders / $perPage);
        $page = min($page, max(1, $totalPages));

        $orders = $this->getOrders($filters, $page, $perPage);

        $campaigns = $this->getCampaignsWithOrders();
        $statuses = self::STATUSES;
        $stats = $this->getStats();

        $pagination = [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $totalOrders,
            'total_pages' => $totalPages
        ];

        // Permissions pour la vue
        $canExport = PermissionHelper::can('orders.export');

        $pageTitle = 'Commandes en attente';
        $isPending = true;
        require __DIR__ . '/../Views/admin/orders/index.php';
    }

    /**
     * Page d'export des fichiers TXT
     *
     * @return void
     */
    public function export(): void
    {
        $this->requireExportPermission();

        // Pagination
        $perPage = (int)($_GET['per_page'] ?? 50);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 50;
        $page = max(1, (int)($_GET['page'] ?? 1));

        // Filtres
        $filters = [
            'campaign_id' => $_GET['campaign_id'] ?? '',
            'status' => $_GET['status'] ?? '',
            'country' => $_GET['country'] ?? '',
            'source' => $_GET['source'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];

        $totalOrders = $this->countOrders($filters);
        $totalPages = ceil($totalOrders / $perPage);
        $page = min($page, max(1, $totalPages));

        $orders = $this->getOrders($filters, $page, $perPage);

        $campaigns = $this->getCampaignsWithOrders();
        $statuses = self::STATUSES;
        $stats = $this->getStats();

        $pagination = [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $totalOrders,
            'total_pages' => $totalPages
        ];

        $pageTitle = 'Export fichiers TXT';
        $isExport = true;
        require __DIR__ . '/../Views/admin/orders/export.php';
    }

    /**
     * Afficher le détail d'une commande
     *
     * @param int $id ID de la commande
     * @return void
     */
    public function show(int $id): void
    {
        $this->requireViewPermission();

        // Vérifier l'accès à cette commande selon le scope
        if (!PermissionHelper::canViewOrder($id)) {
            Session::setFlash('error', 'Vous n\'avez pas accès à cette commande.');
            header('Location: /stm/admin/orders');
            exit;
        }

        // Récupérer la commande avec les infos client et campagne
        $order = $this->db->queryOne(
            "
            SELECT
                o.*,
                c.name as campaign_name,
                c.country as campaign_country,
                cu.customer_number,
                cu.company_name,
                cu.email as customer_email,
                cu.country as customer_country,
                rep.name as rep_user_name,
                rep.rep_id as rep_user_code
            FROM orders o
            LEFT JOIN campaigns c ON o.campaign_id = c.id
            LEFT JOIN customers cu ON o.customer_id = cu.id
            LEFT JOIN users rep ON o.ordered_by_rep_id = rep.id
            WHERE o.id = :id
            ",
            [":id" => $id]
        );

        if (!$order) {
            Session::setFlash('error', 'Commande introuvable.');
            header("Location: /stm/admin/orders");
            exit();
        }

        // Récupérer les lignes de commande
        $orderLines = $this->db->query(
            "
            SELECT
                ol.*,
                p.name_fr as product_name_fr,
                p.name_nl as product_name_nl,
                p.product_code as current_product_code,
                p.image_fr as product_image
            FROM order_lines ol
            LEFT JOIN products p ON ol.product_id = p.id
            WHERE ol.order_id = :order_id
            ORDER BY ol.id ASC
            ",
            [":order_id" => $id]
        );

        // Permissions pour la vue
        $canExport = PermissionHelper::can('orders.export');

        $pageTitle = 'Détail commande #' . $id;
        require __DIR__ . '/../Views/admin/orders/show.php';
    }

    /**
     * Télécharger le fichier TXT pour une commande
     *
     * @param int $id ID de la commande
     * @return void
     */
    public function downloadTxt(int $id): void
    {
        $this->requireExportPermission();

        // Vérifier l'accès à cette commande
        if (!PermissionHelper::canViewOrder($id)) {
            Session::setFlash('error', 'Vous n\'avez pas accès à cette commande.');
            header('Location: /stm/admin/orders');
            exit;
        }

        // Récupérer la commande
        $order = $this->db->queryOne(
            "
            SELECT
                o.*,
                c.name as campaign_name,
                cu.customer_number
            FROM orders o
            LEFT JOIN campaigns c ON o.campaign_id = c.id
            LEFT JOIN customers cu ON o.customer_id = cu.id
            WHERE o.id = :id
            ",
            [":id" => $id]
        );

        if (!$order) {
            Session::setFlash('error', 'Commande introuvable.');
            header("Location: /stm/admin/orders");
            exit();
        }

        // Récupérer les lignes de commande
        $orderLines = $this->db->query(
            "
            SELECT
                ol.*,
                p.product_code as current_product_code
            FROM order_lines ol
            LEFT JOIN products p ON ol.product_id = p.id
            WHERE ol.order_id = :order_id
            ORDER BY ol.id ASC
            ",
            [":order_id" => $id]
        );

        // Générer le contenu
        $content = $this->generateFileContent($order, $orderLines);

        // Nom du fichier
        $customerNumber8 = $this->formatCustomerNumber($order["customer_number"] ?? "");
        $filename = "WebAction_" . date("Ymd-His") . "_" . $customerNumber8 . ".txt";

        // Headers pour téléchargement
        header("Content-Type: text/plain; charset=utf-8");
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header("Content-Length: " . strlen($content));
        header("Cache-Control: no-cache, no-store, must-revalidate");
        header("Pragma: no-cache");
        header("Expires: 0");

        echo $content;
        exit();
    }

    // ========================================
    // MÉTHODES PRIVÉES
    // ========================================

    /**
     * Générer le contenu du fichier TXT format ERP
     *
     * @param array $order
     * @param array $orderLines
     * @return string
     */
    private function generateFileContent(array $order, array $orderLines): string
    {
        $today = date("dmy");

        // Ligne I00
        if (($order["deferred_delivery"] ?? 0) == 1 && !empty($order["delivery_date"])) {
            $deliveryDate = date("dmy", strtotime($order["delivery_date"]));
            $lineI = "I00{$today}{$deliveryDate}\n";
        } else {
            $lineI = "I00{$today}\n";
        }

        // Ligne H
        $customerNumber8 = $this->formatCustomerNumber($order["customer_number"] ?? "");
        $orderType = $order["order_type"] ?? "W";
        $campaignName = str_replace([" ", "-", "_"], "", $order["campaign_name"] ?? "");
        $lineH = "H{$customerNumber8}{$orderType}{$campaignName}\n";

        // Lignes D
        $linesD = "";
        foreach ($orderLines as $line) {
            $productCode = $line["product_code"] ?? ($line["current_product_code"] ?? "");
            $quantity = sprintf("%'.010d", (int) $line["quantity"]);
            $linesD .= "D{$productCode}{$quantity}\n";
        }

        return $lineI . $lineH . $linesD;
    }

    /**
     * Récupérer les commandes avec filtres et pagination
     * FILTRÉES PAR SCOPE selon le rôle
     *
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @return array
     */
    private function getOrders(array $filters, int $page, int $perPage): array
    {
        // Récupérer le filtre de scope
        $scopeFilter = PermissionHelper::getOrderScopeFilter('o', 'cu');

        $sql = "
            SELECT
                o.*,
                c.name as campaign_name,
                c.country as campaign_country,
                cu.customer_number,
                cu.company_name,
                cu.country as customer_country
            FROM orders o
            LEFT JOIN campaigns c ON o.campaign_id = c.id
            LEFT JOIN customers cu ON o.customer_id = cu.id
            WHERE ({$scopeFilter['sql']})
        ";

        $params = $scopeFilter['params'];

        if (!empty($filters['campaign_id'])) {
            $sql .= " AND o.campaign_id = ?";
            $params[] = $filters['campaign_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND o.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['country'])) {
            $sql .= " AND cu.country = ?";
            $params[] = $filters['country'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(o.created_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(o.created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (o.order_number LIKE ? OR cu.customer_number LIKE ? OR cu.company_name LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['today'])) {
            $sql .= " AND DATE(o.created_at) = CURDATE()";
        }

        if (!empty($filters['source'])) {
            $sql .= " AND COALESCE(o.order_source, 'client') = ?";
            $params[] = $filters['source'];
        }

        $sql .= " ORDER BY o.created_at DESC";

        $offset = ($page - 1) * $perPage;
        $sql .= " LIMIT {$perPage} OFFSET {$offset}";

        return $this->db->query($sql, $params);
    }

    /**
     * Compter les commandes avec filtres
     * FILTRÉES PAR SCOPE selon le rôle
     *
     * @param array $filters
     * @return int
     */
    private function countOrders(array $filters): int
    {
        // Récupérer le filtre de scope
        $scopeFilter = PermissionHelper::getOrderScopeFilter('o', 'cu');

        $sql = "
            SELECT COUNT(*) as total
            FROM orders o
            LEFT JOIN customers cu ON o.customer_id = cu.id
            WHERE ({$scopeFilter['sql']})
        ";

        $params = $scopeFilter['params'];

        if (!empty($filters['campaign_id'])) {
            $sql .= " AND o.campaign_id = ?";
            $params[] = $filters['campaign_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND o.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['country'])) {
            $sql .= " AND cu.country = ?";
            $params[] = $filters['country'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(o.created_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(o.created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (o.order_number LIKE ? OR cu.customer_number LIKE ? OR cu.company_name LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['today'])) {
            $sql .= " AND DATE(o.created_at) = CURDATE()";
        }

        if (!empty($filters['source'])) {
            $sql .= " AND COALESCE(o.order_source, 'client') = ?";
            $params[] = $filters['source'];
        }

        $result = $this->db->queryOne($sql, $params);
        return (int)($result['total'] ?? 0);
    }

    /**
     * Récupérer les statistiques globales
     * FILTRÉES PAR SCOPE selon le rôle
     *
     * @return array
     */
    private function getStats(): array
    {
        // Récupérer le filtre de scope
        $scopeFilter = PermissionHelper::getOrderScopeFilter('o', 'cu');

        $sql = "
            SELECT
                COUNT(*) as total_orders,
                SUM(o.total_items) as total_items,
                SUM(CASE WHEN o.status = 'pending_sync' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN o.status = 'synced' THEN 1 ELSE 0 END) as synced_count,
                SUM(CASE WHEN o.status = 'error' THEN 1 ELSE 0 END) as error_count,
                SUM(CASE WHEN DATE(o.created_at) = CURDATE() THEN 1 ELSE 0 END) as today_count
            FROM orders o
            LEFT JOIN customers cu ON o.customer_id = cu.id
            WHERE ({$scopeFilter['sql']})
        ";

        $result = $this->db->queryOne($sql, $scopeFilter['params']);

        return $result ?? [
            'total_orders' => 0,
            'total_items' => 0,
            'pending_count' => 0,
            'synced_count' => 0,
            'error_count' => 0,
            'today_count' => 0
        ];
    }

    /**
     * Récupérer les campagnes ayant des commandes
     * FILTRÉES PAR SCOPE selon le rôle
     *
     * @return array
     */
    private function getCampaignsWithOrders(): array
    {
        // Récupérer le filtre de scope
        $scopeFilter = PermissionHelper::getOrderScopeFilter('o', 'cu');

        $sql = "
            SELECT DISTINCT c.id, c.name, c.country
            FROM campaigns c
            INNER JOIN orders o ON o.campaign_id = c.id
            LEFT JOIN customers cu ON o.customer_id = cu.id
            WHERE ({$scopeFilter['sql']})
            ORDER BY c.name ASC
        ";

        return $this->db->query($sql, $scopeFilter['params']);
    }

    // ========================================
    // SPRINT 15 : Export Excel & Génération TXT à la demande
    // ========================================

    /**
     * Exporter les commandes sélectionnées en Excel
     *
     * POST /admin/orders/export-excel
     *
     * @return void
     */
    public function exportExcel(): void
    {
        $this->requireViewPermission();

        // Récupérer les IDs des commandes à exporter
        $orderIds = $_POST['order_ids'] ?? [];
        $campaignId = $_POST['campaign_id'] ?? null;
        $exportAll = $_POST['export_all'] ?? false;

        // Si export_all, récupérer toutes les commandes de la campagne avec statut validated
        if ($exportAll && $campaignId) {
            $result = $this->db->query(
                "SELECT id FROM orders WHERE campaign_id = :campaign_id AND status = 'validated'",
                [':campaign_id' => $campaignId]
            );
            $orderIds = array_column($result, 'id');
        }

        if (empty($orderIds)) {
            Session::setFlash('error', 'Aucune commande sélectionnée pour l\'export.');
            header('Location: /stm/admin/orders');
            exit;
        }

        // Récupérer les données des commandes
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $sql = "
            SELECT
                o.id,
                o.uuid,
                o.status,
                o.order_source,
                o.customer_email,
                o.total_items,
                o.total_products,
                o.created_at,
                c.name as campaign_name,
                c.country as campaign_country,
                c.order_type,
                cu.customer_number,
                cu.company_name,
                cu.country as customer_country,
                u.name as rep_name
            FROM orders o
            INNER JOIN campaigns c ON o.campaign_id = c.id
            LEFT JOIN customers cu ON o.customer_id = cu.id
            LEFT JOIN users u ON o.ordered_by_rep_id = u.id
            WHERE o.id IN ({$placeholders})
            ORDER BY o.created_at DESC
        ";

        $orders = $this->db->query($sql, $orderIds);

        if (empty($orders)) {
            Session::setFlash('error', 'Aucune commande trouvée.');
            header('Location: /stm/admin/orders');
            exit;
        }

        // Récupérer les lignes de commande pour chaque commande
        $ordersWithLines = [];
        foreach ($orders as $order) {
            $lines = $this->db->query(
                "SELECT product_code, product_name, quantity FROM order_lines WHERE order_id = :order_id",
                [':order_id' => $order['id']]
            );
            $order['lines'] = $lines;
            $ordersWithLines[] = $order;
        }

        // Générer le fichier Excel
        $this->generateExcelFile($ordersWithLines);
    }

    /**
     * Générer le fichier Excel et l'envoyer au navigateur
     *
     * @param array $orders Commandes avec leurs lignes
     * @return void
     */
    private function generateExcelFile(array $orders): void
    {
        // Augmenter le temps d'exécution
        set_time_limit(300);

        // Nettoyer les buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Désactiver l'affichage des erreurs
        ini_set('display_errors', '0');

        // Nom du fichier
        $campaignName = $orders[0]['campaign_name'] ?? 'commandes';
        $filename = 'export_commandes_' . preg_replace('/[^a-zA-Z0-9]/', '_', $campaignName) . '_' . date('Y-m-d_His') . '.xlsx';

        // Créer le fichier Excel avec PhpSpreadsheet
        require_once BASE_PATH . '/vendor/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Commandes');

        // En-têtes
        $headers = ['Réf. Commande', 'Date', 'Client N°', 'Société', 'Pays', 'Email', 'Source', 'Représentant', 'Code Produit', 'Produit', 'Quantité', 'Campagne'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $sheet->getStyle($col . '1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E2E8F0');
            $col++;
        }

        // Données - une ligne par produit
        $row = 2;
        foreach ($orders as $order) {
            foreach ($order['lines'] as $line) {
                $sheet->setCellValue('A' . $row, 'ORD-' . date('Y', strtotime($order['created_at'])) . '-' . str_pad($order['id'], 6, '0', STR_PAD_LEFT));
                $sheet->setCellValue('B' . $row, date('d/m/Y H:i', strtotime($order['created_at'])));
                $sheet->setCellValue('C' . $row, $order['customer_number']);
                $sheet->setCellValue('D' . $row, $order['company_name']);
                $sheet->setCellValue('E' . $row, $order['customer_country']);
                $sheet->setCellValue('F' . $row, $order['customer_email']);
                $sheet->setCellValue('G' . $row, $order['order_source'] === 'rep' ? 'Représentant' : 'Client');
                $sheet->setCellValue('H' . $row, $order['rep_name'] ?? '-');
                $sheet->setCellValue('I' . $row, $line['product_code']);
                $sheet->setCellValue('J' . $row, $line['product_name']);
                $sheet->setCellValue('K' . $row, $line['quantity']);
                $sheet->setCellValue('L' . $row, $order['campaign_name']);
                $row++;
            }
        }

        // Auto-size des colonnes
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Envoyer le fichier
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Générer le fichier TXT à la demande pour une commande
     *
     * POST /admin/orders/{id}/generate-txt
     *
     * @param int $id ID de la commande
     * @return void
     */
    public function generateTxtOnDemand(int $id): void
    {
        $this->requireViewPermission();

        // Récupérer la commande
        $order = $this->db->query(
            "SELECT o.*, c.order_type, c.country, cu.customer_number
             FROM orders o
             INNER JOIN campaigns c ON o.campaign_id = c.id
             LEFT JOIN customers cu ON o.customer_id = cu.id
             WHERE o.id = :id",
            [':id' => $id]
        );

        if (empty($order)) {
            Session::setFlash('error', 'Commande introuvable.');
            header('Location: /stm/admin/orders');
            exit;
        }

        $order = $order[0];

        // Vérifier que la commande n'a pas déjà un fichier TXT
        if (!empty($order['file_path']) && file_exists($order['file_path'])) {
            Session::setFlash('warning', 'Cette commande a déjà un fichier TXT généré.');
            header('Location: /stm/admin/orders/' . $id);
            exit;
        }

        // Récupérer les lignes de commande
        $lines = $this->db->query(
            "SELECT product_code, quantity FROM order_lines WHERE order_id = :order_id",
            [':order_id' => $id]
        );

        // Générer le contenu du fichier TXT
        $content = $this->generateTxtContent($order, $lines);

        // Créer le fichier
        $country = strtolower($order['country']);
        $directory = BASE_PATH . "/storage/orders/{$country}";

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = "order_{$id}_" . date('Ymd_His') . ".txt";
        $filepath = "{$directory}/{$filename}";

        if (file_put_contents($filepath, $content) === false) {
            Session::setFlash('error', 'Erreur lors de la création du fichier TXT.');
            header('Location: /stm/admin/orders/' . $id);
            exit;
        }

        // Mettre à jour la commande
        $this->db->execute(
            "UPDATE orders SET file_path = :file_path, file_content = :file_content, file_generated_at = NOW(), status = 'synced' WHERE id = :id",
            [':file_path' => $filepath, ':file_content' => $content, ':id' => $id]
        );

        Session::setFlash('success', 'Fichier TXT généré avec succès.');
        header('Location: /stm/admin/orders/' . $id);
        exit;
    }

    /**
     * Générer le contenu du fichier TXT au format ERP
     *
     * @param array $order Données de la commande
     * @param array $lines Lignes de commande
     * @return string Contenu du fichier TXT
     */
    private function generateTxtContent(array $order, array $lines): string
    {
        $content = "";
        $orderType = $order['order_type'] ?? 'W';
        $customerNumber = $this->formatCustomerNumber($order['customer_number']);

        foreach ($lines as $line) {
            $productCode = str_pad($line['product_code'], 8, " ", STR_PAD_RIGHT);
            $quantity = str_pad($line['quantity'], 5, "0", STR_PAD_LEFT);

            $content .= "{$orderType}{$customerNumber}{$productCode}{$quantity}\r\n";
        }

        return $content;
    }

    /**
     * Formater un numéro client sur 8 caractères
     *
     * @param string $number Numéro client brut
     * @return string Numéro sur 8 caractères
     */
    private function formatCustomerNumber(string $number): string
    {
        $cleaned = str_replace(["*", "-", "E", "CB"], "", $number);
        $cleaned = preg_replace("/[^0-9]/", "", $cleaned);

        $length = strlen($cleaned);

        if ($length === 6) {
            return $cleaned . "00";
        }

        if ($length > 8) {
            return substr($cleaned, 0, 8);
        }

        return str_pad($cleaned, 8, "0", STR_PAD_LEFT);
    }
}