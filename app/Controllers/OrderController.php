<?php
/**
 * Contrôleur des commandes (Admin)
 *
 * Gère l'affichage et la gestion des commandes côté administration.
 * Pour l'instant, seule la méthode show() est implémentée.
 *
 * @package    App\Controllers
 * @author     Fabian Hardy
 * @version    1.0.0
 * @created    2025/11/27
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
                cu.contact_name,
                cu.email as customer_email_db,
                cu.phone,
                cu.address,
                cu.postal_code,
                cu.city,
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
        $orderLines = $this->db->query(
            "
            SELECT
                ol.*,
                p.name_fr as product_name_fr,
                p.name_nl as product_name_nl,
                p.image_path_fr,
                cat.name_fr as category_name,
                cat.color as category_color
            FROM order_lines ol
            LEFT JOIN products p ON ol.product_id = p.id
            LEFT JOIN categories cat ON p.category_id = cat.id
            WHERE ol.order_id = :order_id
            ORDER BY cat.display_order ASC, p.name_fr ASC
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
}
