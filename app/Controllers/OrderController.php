<?php
/**
 * Contrôleur des commandes (Admin)
 *
 * Gère l'affichage et la gestion des commandes côté administration.
 * Inclut l'export TXT au format ERP identique à la validation publique.
 *
 * @package    App\Controllers
 * @author     Fabian Hardy
 * @version    1.1.0
 * @created    2025/11/27
 * @modified   2025/11/27 - Fix colonnes customers (pas de contact_name, phone, address)
 * @modified   2025/11/27 - Correction exportTxt() : format ERP identique à generateOrderFile()
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
     * Format identique à generateOrderFile() de PublicCampaignController :
     * I00{DDMMYY}{DDMMYY_livraison si deferred_delivery}
     * H{numClient8char}{V/W}{NomCampagne}
     * D{codeProduit}{quantité10digits}
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
            header("Location: /stm/admin/dashboard");
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

        // ========================================
        // GÉNÉRATION FORMAT ERP (identique à generateOrderFile)
        // ========================================

        $today = date("dmy"); // Format: 271125

        // Ligne I00 : Date commande + date livraison (si applicable)
        if ($order["deferred_delivery"] == 1 && !empty($order["delivery_date"])) {
            $deliveryDate = date("dmy", strtotime($order["delivery_date"]));
            $lineI = "I00{$today}{$deliveryDate}\n";
        } else {
            $lineI = "I00{$today}\n";
        }

        // Formater numéro client sur 8 caractères
        $customerNumber8 = $this->formatCustomerNumber($order["customer_number"] ?? "");

        // Ligne H : Numéro client + Type commande + Nom campagne
        $orderType = $order["order_type"] ?? "W"; // V ou W, défaut W
        $campaignName = str_replace([" ", "-", "_"], "", $order["campaign_name"] ?? ""); // Enlever espaces et tirets
        $lineH = "H{$customerNumber8}{$orderType}{$campaignName}\n";

        // Lignes D : Détails produits
        $linesD = "";
        foreach ($orderLines as $line) {
            // Utiliser product_code stocké dans order_lines, sinon celui du produit actuel
            $productCode = $line["product_code"] ?? ($line["current_product_code"] ?? "");
            $quantity = sprintf("%'.010d", (int) $line["quantity"]); // Padding 10 digits avec 0
            $linesD .= "D{$productCode}{$quantity}\n";
        }

        // Contenu complet du fichier
        $content = $lineI . $lineH . $linesD;

        // Nom du fichier : WebAction_{Ymd-His}_{numClient8}.txt
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

    /**
     * Formater un numéro client sur 8 caractères
     *
     * Règles (identiques à PublicCampaignController) :
     * - Si 6 chiffres (ex: 802412) → Ajouter "00" à la fin (80241200)
     * - Si format avec tiret (ex: 802412-12) → Enlever tiret (80241212)
     * - Enlever aussi les * et les lettres E, CB
     * - Si moins de 8 caractères → padding avec 0 à gauche
     *
     * @param string $number Numéro client brut
     * @return string Numéro sur 8 caractères
     */
    private function formatCustomerNumber(string $number): string
    {
        // Enlever *, tirets, E, CB
        $cleaned = str_replace(["*", "-", "E", "CB"], "", $number);

        // Ne garder que les chiffres
        $cleaned = preg_replace("/[^0-9]/", "", $cleaned);

        $length = strlen($cleaned);

        if ($length === 6) {
            return $cleaned . "00"; // Ajouter 00 à la fin
        }

        // Si plus de 8, tronquer à 8
        if ($length > 8) {
            return substr($cleaned, 0, 8);
        }

        // Si moins de 8, padding avec 0 à gauche (pour *12345 → 00012345)
        return str_pad($cleaned, 8, "0", STR_PAD_LEFT);
    }
}
