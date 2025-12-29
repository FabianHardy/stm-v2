<?php
/**
 * Contrôleur des commandes (Admin)
 *
 * Gère l'affichage et la gestion des commandes côté administration.
 * Inclut l'export TXT au format ERP identique à la validation publique.
 *
 * @package    App\Controllers
 * @author     Fabian Hardy
 * @version    1.2.0
 * @created    2025/11/27
 * @modified   2025/11/27 - Fix colonnes customers (pas de contact_name, phone, address)
 * @modified   2025/11/27 - Correction exportTxt() : format ERP identique à generateOrderFile()
 * @modified   2025/12/29 - Ajout index(), today(), pending(), export(), updateStatus(), regenerateFile()
 */

namespace App\Controllers;

use Core\Database;

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
    public const STATUS_SYNCED = 'synced';
    public const STATUS_ERROR = 'error';

    public const STATUSES = [
        self::STATUS_PENDING_SYNC => 'En attente de synchro',
        self::STATUS_SYNCED => 'Synchronisée',
        self::STATUS_ERROR => 'Erreur'
    ];

    public const STATUS_COLORS = [
        self::STATUS_PENDING_SYNC => 'yellow',
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
     * Liste des commandes avec filtres et pagination
     *
     * @return void
     */
    public function index(): void
    {
        // Pagination
        $perPage = (int)($_GET['per_page'] ?? 50);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 50;
        $page = max(1, (int)($_GET['page'] ?? 1));

        // Filtres
        $filters = [
            'campaign_id' => $_GET['campaign_id'] ?? '',
            'status' => $_GET['status'] ?? '',
            'country' => $_GET['country'] ?? '',
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

        // Données pour les filtres
        $campaigns = $this->getCampaignsWithOrders();
        $statuses = self::STATUSES;

        // Stats
        $stats = $this->getStats();

        // Pagination info
        $pagination = [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $totalOrders,
            'total_pages' => $totalPages
        ];

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
        // Pagination
        $perPage = (int)($_GET['per_page'] ?? 50);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 50;
        $page = max(1, (int)($_GET['page'] ?? 1));

        // Filtres avec today forcé
        $filters = [
            'campaign_id' => $_GET['campaign_id'] ?? '',
            'status' => $_GET['status'] ?? '',
            'country' => $_GET['country'] ?? '',
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
        // Pagination
        $perPage = (int)($_GET['per_page'] ?? 50);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 50;
        $page = max(1, (int)($_GET['page'] ?? 1));

        // Filtres avec status forcé
        $filters = [
            'campaign_id' => $_GET['campaign_id'] ?? '',
            'status' => self::STATUS_PENDING_SYNC,
            'country' => $_GET['country'] ?? '',
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
        // Pagination
        $perPage = (int)($_GET['per_page'] ?? 50);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 50;
        $page = max(1, (int)($_GET['page'] ?? 1));

        // Filtres
        $filters = [
            'campaign_id' => $_GET['campaign_id'] ?? '',
            'status' => $_GET['status'] ?? '',
            'country' => $_GET['country'] ?? '',
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
        // Récupérer la commande avec les infos client et campagne
        $order = $this->db->queryOne(
            "
            SELECT
                o.*,
                c.name as campaign_name,
                c.country as campaign_country,
                c.start_date as campaign_start,
                c.end_date as campaign_end,
                cu.customer_number,
                cu.company_name,
                cu.email as customer_email_db,
                cu.language as customer_language,
                cu.rep_name,
                cu.country as customer_country
            FROM orders o
            LEFT JOIN campaigns c ON o.campaign_id = c.id
            LEFT JOIN customers cu ON o.customer_id = cu.id
            WHERE o.id = :id
        ",
            [":id" => $id],
        );

        // Vérifier que la commande existe
        if (!$order) {
            $_SESSION["flash"] = [
                "type" => "error",
                "message" => "Commande introuvable.",
            ];
            header("Location: /stm/admin/orders");
            exit();
        }

        // Récupérer les lignes de commande avec les infos produit
        $orderLines = $this->db->query(
            "
            SELECT
                ol.*,
                p.name_fr as product_name_fr,
                p.name_nl as product_name_nl,
                p.image_fr,
                p.product_code,
                cat.name_fr as category_name,
                cat.color as category_color
            FROM order_lines ol
            LEFT JOIN products p ON ol.product_id = p.id
            LEFT JOIN categories cat ON p.category_id = cat.id
            WHERE ol.order_id = :order_id
            ORDER BY cat.display_order ASC, p.product_code ASC
        ",
            [":order_id" => $id],
        );

        // Calculer le total des quantités
        $totalQuantity = 0;
        foreach ($orderLines as $line) {
            $totalQuantity += (int) $line["quantity"];
        }

        // Récupérer l'URL de retour
        $backUrl = $_SERVER["HTTP_REFERER"] ?? "/stm/admin/orders";
        if (strpos($backUrl, "/stm/") === false) {
            $backUrl = "/stm/admin/orders";
        }

        // Passer les données à la vue
        require __DIR__ . "/../Views/admin/orders/show.php";
    }

    /**
     * Mettre à jour le statut de synchronisation
     *
     * @return void
     */
    public function updateStatus(): void
    {
        // Vérifier le token CSRF
        if (!isset($_POST['_token']) || $_POST['_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Token CSRF invalide.'];
            header('Location: /stm/admin/orders');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $errorMessage = $_POST['error_message'] ?? null;

        if (!$id || !array_key_exists($status, self::STATUSES)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Paramètres invalides.'];
            header('Location: /stm/admin/orders');
            exit;
        }

        // Préparer la requête
        $params = [
            ':status' => $status,
            ':error_message' => $errorMessage,
            ':id' => $id
        ];

        $sql = "UPDATE orders SET status = :status, sync_error_message = :error_message";

        if ($status === self::STATUS_SYNCED) {
            $sql .= ", synced_at = NOW()";
        }

        $sql .= ", updated_at = NOW() WHERE id = :id";

        $this->db->execute($sql, $params);

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Statut mis à jour avec succès.'];
        header('Location: /stm/admin/orders/' . $id);
        exit;
    }

    /**
     * Télécharger le fichier TXT existant d'une commande
     *
     * @return void
     */
    public function downloadFile(): void
    {
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Commande introuvable.'];
            header('Location: /stm/admin/orders');
            exit;
        }

        $order = $this->db->queryOne(
            "SELECT id, order_number, file_path FROM orders WHERE id = :id",
            [':id' => $id]
        );

        if (!$order || empty($order['file_path'])) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Fichier introuvable.'];
            header('Location: /stm/admin/orders/' . $id);
            exit;
        }

        $fullPath = __DIR__ . '/../../public/' . $order['file_path'];

        if (!file_exists($fullPath)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Le fichier n\'existe plus sur le serveur.'];
            header('Location: /stm/admin/orders/' . $id);
            exit;
        }

        // Téléchargement
        $filename = basename($order['file_path']);
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($fullPath));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        readfile($fullPath);
        exit;
    }

    /**
     * Régénérer le fichier TXT d'une commande
     *
     * @return void
     */
    public function regenerateFile(): void
    {
        // Vérifier le token CSRF
        if (!isset($_POST['_token']) || $_POST['_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Token CSRF invalide.'];
            header('Location: /stm/admin/orders');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);

        if (!$id) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Commande introuvable.'];
            header('Location: /stm/admin/orders');
            exit;
        }

        // Récupérer la commande
        $order = $this->db->queryOne(
            "
            SELECT
                o.*,
                c.name as campaign_name,
                c.order_type,
                c.deferred_delivery,
                c.delivery_date,
                cu.customer_number,
                cu.company_name,
                cu.country as customer_country
            FROM orders o
            LEFT JOIN campaigns c ON o.campaign_id = c.id
            LEFT JOIN customers cu ON o.customer_id = cu.id
            WHERE o.id = :id
        ",
            [":id" => $id]
        );

        if (!$order) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Commande introuvable.'];
            header('Location: /stm/admin/orders');
            exit;
        }

        // Récupérer les lignes de commande
        $orderLines = $this->db->query(
            "
            SELECT
                ol.quantity,
                ol.product_code,
                p.product_code as current_product_code
            FROM order_lines ol
            LEFT JOIN products p ON ol.product_id = p.id
            WHERE ol.order_id = :order_id
            ORDER BY ol.product_code ASC
        ",
            [":order_id" => $id]
        );

        if (empty($orderLines)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Aucune ligne de commande.'];
            header('Location: /stm/admin/orders/' . $id);
            exit;
        }

        // Générer le contenu du fichier (format ERP)
        $content = $this->generateFileContent($order, $orderLines);

        // Déterminer le répertoire selon le pays
        $country = $order['customer_country'] ?? 'BE';
        $directory = __DIR__ . '/../../public/commande_' . $country . '/';

        // Créer le répertoire si nécessaire
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Générer le nom de fichier
        $customerNumber8 = $this->formatCustomerNumber($order['customer_number'] ?? '');
        $filename = 'WebAction_' . date('Ymd-His') . '_' . $customerNumber8 . '.txt';
        $filepath = $directory . $filename;

        // Écrire le fichier
        if (file_put_contents($filepath, $content) === false) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Impossible d\'écrire le fichier.'];
            header('Location: /stm/admin/orders/' . $id);
            exit;
        }

        // Mettre à jour le chemin en base
        $relativePath = 'commande_' . $country . '/' . $filename;
        $this->db->execute(
            "UPDATE orders SET file_path = :file_path, file_generated_at = NOW(), updated_at = NOW() WHERE id = :id",
            [':file_path' => $relativePath, ':id' => $id]
        );

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Fichier régénéré avec succès : ' . $filename];
        header('Location: /stm/admin/orders/' . $id);
        exit;
    }

    /**
     * Exporter le fichier TXT de la commande (format ERP) - téléchargement direct
     *
     * @param int $id ID de la commande
     * @return void
     */
    public function exportTxt(int $id): void
    {
        // Récupérer la commande avec toutes les infos nécessaires
        $order = $this->db->queryOne(
            "
            SELECT
                o.*,
                c.name as campaign_name,
                c.order_type,
                c.deferred_delivery,
                c.delivery_date,
                cu.customer_number,
                cu.company_name,
                cu.country as customer_country
            FROM orders o
            LEFT JOIN campaigns c ON o.campaign_id = c.id
            LEFT JOIN customers cu ON o.customer_id = cu.id
            WHERE o.id = :id
        ",
            [":id" => $id],
        );

        if (!$order) {
            $_SESSION["flash"] = [
                "type" => "error",
                "message" => "Commande introuvable.",
            ];
            header("Location: /stm/admin/orders");
            exit();
        }

        // Récupérer les lignes de commande
        $orderLines = $this->db->query(
            "
            SELECT
                ol.quantity,
                ol.product_code,
                p.product_code as current_product_code
            FROM order_lines ol
            LEFT JOIN products p ON ol.product_id = p.id
            WHERE ol.order_id = :order_id
            ORDER BY ol.product_code ASC
        ",
            [":order_id" => $id],
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
     *
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @return array
     */
    private function getOrders(array $filters, int $page, int $perPage): array
    {
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
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['campaign_id'])) {
            $sql .= " AND o.campaign_id = :campaign_id";
            $params[':campaign_id'] = $filters['campaign_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND o.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['country'])) {
            $sql .= " AND cu.country = :country";
            $params[':country'] = $filters['country'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(o.created_at) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(o.created_at) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (o.order_number LIKE :search OR cu.customer_number LIKE :search2 OR cu.company_name LIKE :search3)";
            $params[':search'] = '%' . $filters['search'] . '%';
            $params[':search2'] = '%' . $filters['search'] . '%';
            $params[':search3'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['today'])) {
            $sql .= " AND DATE(o.created_at) = CURDATE()";
        }

        $sql .= " ORDER BY o.created_at DESC";

        $offset = ($page - 1) * $perPage;
        $sql .= " LIMIT {$perPage} OFFSET {$offset}";

        return $this->db->query($sql, $params);
    }

    /**
     * Compter les commandes avec filtres
     *
     * @param array $filters
     * @return int
     */
    private function countOrders(array $filters): int
    {
        $sql = "
            SELECT COUNT(*) as total
            FROM orders o
            LEFT JOIN customers cu ON o.customer_id = cu.id
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['campaign_id'])) {
            $sql .= " AND o.campaign_id = :campaign_id";
            $params[':campaign_id'] = $filters['campaign_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND o.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['country'])) {
            $sql .= " AND cu.country = :country";
            $params[':country'] = $filters['country'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(o.created_at) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(o.created_at) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (o.order_number LIKE :search OR cu.customer_number LIKE :search2 OR cu.company_name LIKE :search3)";
            $params[':search'] = '%' . $filters['search'] . '%';
            $params[':search2'] = '%' . $filters['search'] . '%';
            $params[':search3'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['today'])) {
            $sql .= " AND DATE(o.created_at) = CURDATE()";
        }

        $result = $this->db->queryOne($sql, $params);
        return (int)($result['total'] ?? 0);
    }

    /**
     * Récupérer les statistiques globales
     *
     * @return array
     */
    private function getStats(): array
    {
        $result = $this->db->queryOne("
            SELECT
                COUNT(*) as total_orders,
                SUM(total_items) as total_items,
                SUM(CASE WHEN status = 'pending_sync' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'synced' THEN 1 ELSE 0 END) as synced_count,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as error_count,
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_count
            FROM orders
        ");

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
     *
     * @return array
     */
    private function getCampaignsWithOrders(): array
    {
        return $this->db->query("
            SELECT DISTINCT c.id, c.name, c.country
            FROM campaigns c
            INNER JOIN orders o ON o.campaign_id = c.id
            ORDER BY c.name ASC
        ");
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