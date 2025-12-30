<?php
/**
 * Model Order
 *
 * Gestion des commandes avec :
 * - Récupération avec filtres et pagination
 * - Statistiques (total, today, pending, error)
 * - Gestion des statuts de synchronisation ERP
 * - Génération/régénération fichiers TXT
 *
 * @package    App\Models
 * @author     Fabian Hardy
 * @version    2.0.0
 * @created    2025/11/27
 * @modified   2025/12/30 - Refonte complète avec statuts synchro ERP
 */

namespace App\Models;

use Core\Database;

class Order
{
    private Database $db;

    /**
     * Constantes des statuts de synchronisation
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
     * Récupérer toutes les commandes avec filtres et pagination
     *
     * @param array $filters Filtres (campaign_id, status, country, date_from, date_to, search, today)
     * @param int $page Numéro de page
     * @param int $perPage Nombre par page
     * @return array
     */
    public function getAll(array $filters = [], int $page = 1, int $perPage = 50): array
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

        // Filtre par campagne
        if (!empty($filters['campaign_id'])) {
            $sql .= " AND o.campaign_id = :campaign_id";
            $params[':campaign_id'] = $filters['campaign_id'];
        }

        // Filtre par statut
        if (!empty($filters['status'])) {
            $sql .= " AND o.status = :status";
            $params[':status'] = $filters['status'];
        }

        // Filtre par pays (via customer)
        if (!empty($filters['country'])) {
            $sql .= " AND cu.country = :country";
            $params[':country'] = $filters['country'];
        }

        // Filtre par date début
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(o.created_at) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }

        // Filtre par date fin
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(o.created_at) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }

        // Filtre recherche (numéro commande, client, société)
        if (!empty($filters['search'])) {
            $sql .= " AND (o.order_number LIKE :search OR cu.customer_number LIKE :search2 OR cu.company_name LIKE :search3)";
            $params[':search'] = '%' . $filters['search'] . '%';
            $params[':search2'] = '%' . $filters['search'] . '%';
            $params[':search3'] = '%' . $filters['search'] . '%';
        }

        // Filtre aujourd'hui uniquement
        if (!empty($filters['today'])) {
            $sql .= " AND DATE(o.created_at) = CURDATE()";
        }

        $sql .= " ORDER BY o.created_at DESC";

        // Pagination
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
    public function countByFilters(array $filters = []): int
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
     * Récupérer une commande par son ID avec détails
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $sql = "
            SELECT
                o.*,
                c.name as campaign_name,
                c.country as campaign_country,
                c.start_date as campaign_start,
                c.end_date as campaign_end,
                c.order_type,
                c.deferred_delivery,
                c.delivery_date,
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
        ";

        $result = $this->db->queryOne($sql, [':id' => $id]);
        return $result ?: null;
    }

    /**
     * Récupérer une commande par son UUID
     *
     * @param string $uuid
     * @return array|null
     */
    public function findByUuid(string $uuid): ?array
    {
        $sql = "SELECT * FROM orders WHERE uuid = :uuid";
        $result = $this->db->queryOne($sql, [':uuid' => $uuid]);
        return $result ?: null;
    }

    /**
     * Récupérer les lignes d'une commande
     *
     * @param int $orderId
     * @return array
     */
    public function getOrderLines(int $orderId): array
    {
        $sql = "
            SELECT
                ol.*,
                p.name_fr as product_name_fr,
                p.name_nl as product_name_nl,
                p.image_fr,
                p.product_code as current_product_code,
                cat.name_fr as category_name,
                cat.color as category_color
            FROM order_lines ol
            LEFT JOIN products p ON ol.product_id = p.id
            LEFT JOIN categories cat ON p.category_id = cat.id
            WHERE ol.order_id = :order_id
            ORDER BY cat.display_order ASC, p.product_code ASC
        ";

        return $this->db->query($sql, [':order_id' => $orderId]);
    }

    /**
     * Mettre à jour le statut d'une commande
     *
     * @param int $id
     * @param string $status
     * @param string|null $errorMessage
     * @return bool
     */
    public function updateStatus(int $id, string $status, ?string $errorMessage = null): bool
    {
        $params = [
            ':status' => $status,
            ':error_message' => $errorMessage,
            ':id' => $id
        ];

        $sql = "UPDATE orders SET status = :status, sync_error_message = :error_message";

        // Si synchronisée, mettre à jour la date de synchro
        if ($status === self::STATUS_SYNCED) {
            $sql .= ", synced_at = NOW()";
        }

        $sql .= ", updated_at = NOW() WHERE id = :id";

        return $this->db->execute($sql, $params);
    }

    /**
     * Récupérer les statistiques globales des commandes
     *
     * @return array
     */
    public function getStats(): array
    {
        $result = $this->db->queryOne("
            SELECT
                COUNT(*) as total_orders,
                COALESCE(SUM(total_items), 0) as total_items,
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
     * Récupérer les campagnes ayant des commandes (pour le filtre dropdown)
     *
     * @return array
     */
    public function getCampaignsWithOrders(): array
    {
        return $this->db->query("
            SELECT DISTINCT c.id, c.name, c.country
            FROM campaigns c
            INNER JOIN orders o ON o.campaign_id = c.id
            ORDER BY c.name ASC
        ");
    }

    /**
     * Vérifier si le fichier TXT existe
     *
     * @param string|null $filePath
     * @return bool
     */
    public function fileExists(?string $filePath): bool
    {
        if (empty($filePath)) {
            return false;
        }

        $fullPath = $this->getFullFilePath($filePath);
        return file_exists($fullPath);
    }

    /**
     * Obtenir le chemin complet d'un fichier
     *
     * @param string $filePath
     * @return string
     */
    public function getFullFilePath(string $filePath): string
    {
        return dirname(__DIR__, 2) . '/public/' . $filePath;
    }

    /**
     * Régénérer le fichier TXT d'une commande
     *
     * @param int $orderId
     * @return array ['success' => bool, 'message' => string, 'filename' => string|null]
     */
    public function regenerateFile(int $orderId): array
    {
        // Récupérer la commande
        $order = $this->findById($orderId);
        if (!$order) {
            return ['success' => false, 'message' => 'Commande introuvable', 'filename' => null];
        }

        // Récupérer les lignes
        $lines = $this->getOrderLines($orderId);
        if (empty($lines)) {
            return ['success' => false, 'message' => 'Aucune ligne de commande', 'filename' => null];
        }

        // Générer le contenu
        $content = $this->generateFileContent($order, $lines);

        // Déterminer le répertoire
        $country = $order['customer_country'] ?? 'BE';
        $directory = dirname(__DIR__, 2) . '/public/commande_' . $country . '/';

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
            return ['success' => false, 'message' => 'Impossible d\'écrire le fichier', 'filename' => null];
        }

        // Mettre à jour en base
        $relativePath = 'commande_' . $country . '/' . $filename;
        $this->db->execute(
            "UPDATE orders SET file_path = :file_path, file_generated_at = NOW(), updated_at = NOW() WHERE id = :id",
            [':file_path' => $relativePath, ':id' => $orderId]
        );

        return ['success' => true, 'message' => 'Fichier généré avec succès', 'filename' => $filename];
    }

    /**
     * Générer le contenu du fichier TXT format ERP
     *
     * Format:
     * I00{DDMMYY}{DDMMYY_livraison si deferred}
     * H{numClient8char}{V/W}{NomCampagne}
     * D{codeProduit}{quantité10digits}
     *
     * @param array $order
     * @param array $lines
     * @return string
     */
    public function generateFileContent(array $order, array $lines): string
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
        foreach ($lines as $line) {
            $productCode = $line["product_code"] ?? ($line["current_product_code"] ?? "");
            $quantity = sprintf("%'.010d", (int) $line["quantity"]);
            $linesD .= "D{$productCode}{$quantity}\n";
        }

        return $lineI . $lineH . $linesD;
    }

    /**
     * Formater un numéro client sur 8 caractères
     *
     * Règles :
     * - 6 chiffres (802412) → Ajouter "00" à la fin (80241200)
     * - Format avec tiret (802412-12) → Enlever tiret (80241212)
     * - Enlever *, E, CB
     * - Padding avec 0 à gauche si < 8
     *
     * @param string $number
     * @return string
     */
    private function formatCustomerNumber(string $number): string
    {
        // Enlever *, tirets, E, CB
        $cleaned = str_replace(["*", "-", "E", "CB"], "", $number);

        // Ne garder que les chiffres
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

    /**
     * Créer une nouvelle commande
     *
     * @param array $data
     * @return int|false ID de la commande ou false
     */
    public function create(array $data): int|false
    {
        $sql = "INSERT INTO orders (
            uuid, order_number, campaign_id, customer_id, customer_email,
            total_items, total_products, status, notes, created_at, updated_at
        ) VALUES (
            :uuid, :order_number, :campaign_id, :customer_id, :customer_email,
            :total_items, :total_products, :status, :notes, NOW(), NOW()
        )";

        $params = [
            ':uuid' => $data['uuid'] ?? $this->generateUuid(),
            ':order_number' => $data['order_number'] ?? $this->generateOrderNumber(),
            ':campaign_id' => $data['campaign_id'],
            ':customer_id' => $data['customer_id'],
            ':customer_email' => $data['customer_email'] ?? null,
            ':total_items' => $data['total_items'] ?? 0,
            ':total_products' => $data['total_products'] ?? 0,
            ':status' => $data['status'] ?? self::STATUS_PENDING_SYNC,
            ':notes' => $data['notes'] ?? null
        ];

        $result = $this->db->execute($sql, $params);

        if ($result) {
            return (int)$this->db->lastInsertId();
        }

        return false;
    }

    /**
     * Générer un UUID v4
     *
     * @return string
     */
    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Générer un numéro de commande unique
     *
     * @return string Format: ORD-YYYY-XXXXXX
     */
    private function generateOrderNumber(): string
    {
        $year = date('Y');
        $lastOrder = $this->db->queryOne(
            "SELECT MAX(CAST(SUBSTRING(order_number, 10) AS UNSIGNED)) as last_num
             FROM orders
             WHERE order_number LIKE :prefix",
            [':prefix' => "ORD-{$year}-%"]
        );

        $nextNum = ($lastOrder['last_num'] ?? 0) + 1;
        return "ORD-{$year}-" . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
    }
}