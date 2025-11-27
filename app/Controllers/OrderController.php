<?php
/**
 * Contrôleur des commandes (Admin)
 *
 * Gère l'affichage et la gestion des commandes côté administration.
 * Pour l'instant, seule la méthode show() est implémentée.
 *
 * @package    App\Controllers
 * @author     Fabian Hardy
 * @version    1.0.1
 * @created    2025/11/27
 * @modified   2025/11/27 - Fix colonnes customers (pas de contact_name, phone, address)
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
     * Constructeur
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
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
        // Note: customers n'a pas contact_name, phone, address, postal_code, city
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
            header("Location: /stm/admin/dashboard");
            exit();
        }

        // Récupérer les lignes de commande avec les infos produit
        // Table categories (pas product_categories)
        // Tri par catégorie puis par code produit
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

        // Récupérer l'URL de retour (depuis le referer ou dashboard par défaut)
        $backUrl = $_SERVER["HTTP_REFERER"] ?? "/stm/admin/dashboard";

        // Si le referer contient "orders", on garde, sinon on utilise le dashboard
        if (strpos($backUrl, "/stm/") === false) {
            $backUrl = "/stm/admin/dashboard";
        }

        // Passer les données à la vue
        require __DIR__ . "/../Views/admin/orders/show.php";
    }

    /**
     * Liste des commandes (à implémenter plus tard)
     *
     * @return void
     */
    public function index(): void
    {
        // TODO: Sprint Commandes - Afficher la liste des commandes
        $_SESSION["flash"] = [
            "type" => "info",
            "message" => "Module commandes en cours de développement.",
        ];
        header("Location: /stm/admin/dashboard");
        exit();
    }

    /**
     * Exporter le fichier TXT de la commande (format ERP)
     *
     * @param int $id ID de la commande
     * @return void
     */
    public function exportTxt(int $id): void
    {
        // Récupérer la commande
        $order = $this->db->queryOne(
            "
            SELECT
                o.*,
                c.name as campaign_name,
                c.order_type,
                cu.customer_number,
                cu.company_name
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
            header("Location: /stm/admin/dashboard");
            exit();
        }

        // Récupérer les lignes de commande
        $orderLines = $this->db->query(
            "
            SELECT
                ol.quantity,
                ol.product_code,
                ol.product_name,
                p.product_code as current_product_code
            FROM order_lines ol
            LEFT JOIN products p ON ol.product_id = p.id
            WHERE ol.order_id = :order_id
            ORDER BY ol.product_code ASC
        ",
            [":order_id" => $id],
        );

        // Générer le contenu du fichier TXT
        $content = "";
        $orderType = $order["order_type"] ?? "W";
        $customerNumber = $order["customer_number"] ?? "";

        foreach ($orderLines as $line) {
            $productCode = $line["product_code"] ?? ($line["current_product_code"] ?? "");
            $quantity = (int) ($line["quantity"] ?? 0);

            // Format: TYPE;CUSTOMER_NUMBER;PRODUCT_CODE;QUANTITY
            $content .= sprintf("%s;%s;%s;%d\n", $orderType, $customerNumber, $productCode, $quantity);
        }

        // Nom du fichier
        $filename = sprintf(
            "commande_%s_%s_%s.txt",
            $customerNumber,
            preg_replace("/[^a-zA-Z0-9]/", "_", $order["campaign_name"] ?? "campagne"),
            date("Ymd_His", strtotime($order["created_at"])),
        );

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
}
