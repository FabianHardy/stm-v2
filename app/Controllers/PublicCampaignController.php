<?php

/**

 * PublicCampaignController.php

 *

 * Contrôleur pour l'interface publique des campagnes

 * Gère l'accès client, l'identification, le catalogue et la commande

 *

 * @created  2025/11/14 16:30

 * @modified 2025/11/14 18:00 - Ajout catalogue + panier (Sous-tâche 2)

 * @modified 2025/11/18 10:00 - Ajout envoi email confirmation (Sprint 7.3.2)

 * @modified 2025/11/19 19:30 - Ajout gestion singulier/pluriel pour libellé promotions
 * @modified 2025/12/30 - Ajout méthode showStaticPage() pour pages fixes (Sprint 9)
 */

namespace App\Controllers;

use Core\Database;

use Core\Session;

use App\Services\MailchimpEmailService;

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

            $campaign = $this->db->query($query, [":uuid" => $uuid]);

            // Campagne introuvable

            if (empty($campaign)) {
                $this->renderAccessDenied("campaign_not_found", $uuid);

                return;
            }

            $campaign = $campaign[0];

            // Vérifier le statut de la campagne

            if ($campaign["computed_status"] === "upcoming") {
                $this->renderAccessDenied("upcoming", $uuid, $campaign);

                return;
            }

            if ($campaign["computed_status"] === "ended") {
                $this->renderAccessDenied("ended", $uuid, $campaign);

                return;
            }

            if ($campaign["computed_status"] === "inactive") {
                $this->renderAccessDenied("inactive", $uuid, $campaign);

                return;
            }

            // Campagne active - afficher la page d'identification

            // Compter les promotions actives de la campagne

            $promotionsCountResult = $this->db->query(
                "SELECT COUNT(*) as count FROM products WHERE campaign_id = :campaign_id AND is_active = 1",

                [":campaign_id" => $campaign["id"]],
            );

            $promotionsCount = (int) ($promotionsCountResult[0]["count"] ?? 0);

            $this->renderIdentificationPage($campaign, $promotionsCount);
        } catch (\PDOException $e) {
            error_log("Erreur show() : " . $e->getMessage());

            $this->renderAccessDenied("error", $uuid);
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
            // ========================================

            // STOCKER LA LANGUE DÈS LE DÉBUT

            // ========================================

            // Récupérer la langue du formulaire AVANT les vérifications

            $requestedLang = $_POST["language"] ?? "fr";

            $selectedLanguage = in_array($requestedLang, ["fr", "nl"], true) ? $requestedLang : "fr";

            // Stocker dans session temporaire pour access_denied

            Session::set("temp_language", $selectedLanguage);

            // Récupérer la campagne

            $query = "SELECT * FROM campaigns WHERE uuid = :uuid AND is_active = 1";

            $campaign = $this->db->query($query, [":uuid" => $uuid]);

            if (empty($campaign)) {
                $this->renderAccessDenied("campaign_not_found", $uuid);

                return;
            }

            $campaign = $campaign[0];

            // Récupérer les données du formulaire

            $customerNumber = trim($_POST["customer_number"] ?? "");

            $country = $_POST["country"] ?? ($campaign["country"] === "BOTH" ? "BE" : $campaign["country"]);

            // Validation

            if (empty($customerNumber)) {
                Session::set("error", "Le numéro client est obligatoire.");

                header("Location: /stm/c/{$uuid}");

                exit();
            }

            // Vérifier que le client existe dans la DB externe

            $externalDb = $this->getExternalDatabase();

            $customerData = $this->getCustomerFromExternal($externalDb, $customerNumber, $country);

            if (!$customerData) {
                Session::set("error", "Numéro client introuvable. Veuillez vérifier votre numéro.");

                header("Location: /stm/c/{$uuid}");

                exit();
            }

            // Vérifier les droits selon le mode de la campagne

            $hasAccess = $this->checkCustomerAccess($campaign, $customerNumber, $country);

            if (!$hasAccess) {
                $this->renderAccessDenied("no_access", $uuid, $campaign);

                return;
            }

            // Vérifier si tous les produits ont atteint leurs quotas

            $hasAvailableProducts = $this->checkAvailableProducts($campaign["id"], $customerNumber, $country);

            if (!$hasAvailableProducts) {
                $this->renderAccessDenied("quotas_reached", $uuid, $campaign);

                return;
            }

            // Déterminer la langue selon le paramètre transmis ou FR par défaut

            //    $requestedLang = $_POST['language'] ?? 'fr';

            //    $defaultLanguage = in_array($requestedLang, ['fr', 'nl'], true) ? $requestedLang : 'fr';

            // Tout est OK - créer la session client

            Session::set("public_customer", [
                "customer_number" => $customerNumber,

                "country" => $country,

                "company_name" => $customerData["company_name"],

                "campaign_uuid" => $uuid,

                "campaign_id" => $campaign["id"],

                "language" => $selectedLanguage,

                "logged_at" => date("Y-m-d H:i:s"),
            ]);

            // Initialiser le panier vide

            Session::set("cart", [
                "campaign_uuid" => $uuid,

                "items" => [],
            ]);

            // Rediriger vers le catalogue

            header("Location: /stm/c/{$uuid}/catalog");

            exit();
        } catch (\PDOException $e) {
            error_log("Erreur identify() : " . $e->getMessage());

            // En développement, afficher l'erreur détaillée

            $errorDetail =
                $_ENV["APP_DEBUG"] ?? false ? $e->getMessage() : "Une erreur est survenue. Veuillez réessayer.";

            Session::set("error", $errorDetail);

            header("Location: /stm/c/{$uuid}");

            exit();
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
            // ========================================

            // GESTION DU SWITCH LANGUE (FR/NL)

            // ========================================

            // Récupérer le paramètre ?lang de l'URL

            $requestedLang = $_GET["lang"] ?? null;

            // Si une langue est demandée ET que c'est FR ou NL

            if ($requestedLang && in_array($requestedLang, ["fr", "nl"], true)) {
                // Récupérer le client de la session

                $tempCustomer = Session::get("public_customer");

                // Si le client existe, mettre à jour sa langue

                if ($tempCustomer) {
                    $tempCustomer["language"] = $requestedLang;

                    Session::set("public_customer", $tempCustomer);
                }

                // Rediriger vers l'URL propre (sans ?lang=)

                $cleanUrl = strtok($_SERVER["REQUEST_URI"], "?");

                header("Location: {$cleanUrl}");

                exit();
            }

            // Vérifier que le client est identifié

            $customer = Session::get("public_customer");

            if (!$customer || $customer["campaign_uuid"] !== $uuid) {
                header("Location: /stm/c/{$uuid}");

                exit();
            }

            // Récupérer la campagne

            $query = "SELECT * FROM campaigns WHERE uuid = :uuid AND is_active = 1";

            $campaign = $this->db->query($query, [":uuid" => $uuid]);

            if (empty($campaign)) {
                Session::remove("public_customer");

                header("Location: /stm/c/{$uuid}");

                exit();
            }

            $campaign = $campaign[0];

            // Récupérer toutes les catégories actives avec leurs produits

            $categoriesQuery = "

                SELECT DISTINCT

                    cat.id,

                    cat.code,

                    cat.name_fr,

                    cat.name_nl,

                    cat.color,

                    cat.icon_path,

                    cat.display_order

                FROM categories cat

                INNER JOIN products p ON p.category_id = cat.id

                WHERE p.campaign_id = :campaign_id

                  AND p.is_active = 1

                  AND cat.is_active = 1

                ORDER BY cat.display_order ASC, cat.name_fr ASC

            ";

            $categories = $this->db->query($categoriesQuery, [":campaign_id" => $campaign["id"]]);

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
                    ":category_id" => $category["id"],

                    ":campaign_id" => $campaign["id"],
                ]);

                // Calculer les quotas disponibles pour chaque produit

                foreach ($products as $productKey => $product) {
                    $quotas = $this->calculateAvailableQuotas(
                        $product["id"],

                        $customer["customer_number"],

                        $customer["country"],

                        $product["max_per_customer"],

                        $product["max_total"],
                    );

                    $products[$productKey]["available_for_customer"] = $quotas["customer"];

                    $products[$productKey]["available_global"] = $quotas["global"];

                    $products[$productKey]["max_orderable"] = $quotas["max_orderable"];

                    $products[$productKey]["is_orderable"] = $quotas["is_orderable"];
                }

                $categories[$key]["products"] = $products;
            }

            // Récupérer le panier depuis la session

            $cart = Session::get("cart", [
                "campaign_uuid" => $uuid,

                "items" => [],
            ]);

            // Afficher la vue

            require __DIR__ . "/../Views/public/campaign/catalog.php";
        } catch (\PDOException $e) {
            error_log("Erreur catalog() : " . $e->getMessage());

            Session::set("error", "Une erreur est survenue lors du chargement du catalogue.");

            header("Location: /stm/c/{$uuid}");

            exit();
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
        header("Content-Type: application/json");

        try {
            // Vérifier session client

            $customer = Session::get("public_customer");

            if (!$customer || $customer["campaign_uuid"] !== $uuid) {
                echo json_encode(["success" => false, "error" => "Session expirée"]);

                exit();
            }

            // Récupérer les données

            $productId = (int) ($_POST["product_id"] ?? 0);

            $quantity = (int) ($_POST["quantity"] ?? 0);

            if ($productId <= 0 || $quantity <= 0) {
                echo json_encode(["success" => false, "error" => "Données invalides"]);

                exit();
            }

            // Récupérer le produit

            $productQuery = "SELECT * FROM products WHERE id = :id AND campaign_id = :campaign_id AND is_active = 1";

            $product = $this->db->query($productQuery, [
                ":id" => $productId,

                ":campaign_id" => $customer["campaign_id"],
            ]);

            if (empty($product)) {
                echo json_encode(["success" => false, "error" => "Produit introuvable"]);

                exit();
            }

            $product = $product[0];

            // Vérifier les quotas disponibles

            $quotas = $this->calculateAvailableQuotas(
                $productId,

                $customer["customer_number"],

                $customer["country"],

                $product["max_per_customer"],

                $product["max_total"],
            );

            if (!$quotas["is_orderable"]) {
                echo json_encode(["success" => false, "error" => "Produit plus disponible"]);

                exit();
            }

            // Récupérer le panier

            $cart = Session::get("cart", ["campaign_uuid" => $uuid, "items" => []]);

            // Chercher si le produit existe déjà dans le panier

            $existingIndex = null;

            foreach ($cart["items"] as $index => $item) {
                if ($item["product_id"] == $productId) {
                    $existingIndex = $index;

                    break;
                }
            }

            // Calculer la nouvelle quantité totale

            $currentQtyInCart = $existingIndex !== null ? $cart["items"][$existingIndex]["quantity"] : 0;

            $newTotalQty = $currentQtyInCart + $quantity;

            // Vérifier que la nouvelle quantité ne dépasse pas les quotas

            if ($newTotalQty > $quotas["max_orderable"]) {
                echo json_encode([
                    "success" => false,

                    "error" => "Quantité maximale : {$quotas["max_orderable"]}",
                ]);

                exit();
            }

            // Ajouter ou mettre à jour le produit dans le panier

            if ($existingIndex !== null) {
                // Mise à jour quantité

                $cart["items"][$existingIndex]["quantity"] = $newTotalQty;
            } else {
                // Nouveau produit - Stocker FR et NL pour le switch langue

                $cart["items"][] = [
                    "product_id" => $productId,

                    "code" => $product["product_code"],

                    "name_fr" => $product["name_fr"],

                    "name_nl" => $product["name_nl"] ?? $product["name_fr"],

                    "image_fr" => $product["image_fr"] ?? null,

                    "image_nl" => $product["image_nl"] ?? $product["image_fr"],

                    "quantity" => $quantity,
                ];
            }

            // Sauvegarder le panier en session

            Session::set("cart", $cart);

            // Retourner le succès avec le panier mis à jour

            echo json_encode([
                "success" => true,

                "cart" => $cart,

                "message" => "Produit ajouté au panier",
            ]);
        } catch (\Exception $e) {
            error_log("Erreur addToCart() : " . $e->getMessage());

            echo json_encode(["success" => false, "error" => "Erreur serveur"]);
        }

        exit();
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
        header("Content-Type: application/json");

        try {
            // Vérifier session client

            $customer = Session::get("public_customer");

            if (!$customer || $customer["campaign_uuid"] !== $uuid) {
                echo json_encode(["success" => false, "error" => "Session expirée"]);

                exit();
            }

            $productId = (int) ($_POST["product_id"] ?? 0);

            $quantity = (int) ($_POST["quantity"] ?? 0);

            if ($productId <= 0) {
                echo json_encode(["success" => false, "error" => "Données invalides"]);

                exit();
            }

            $cart = Session::get("cart", ["campaign_uuid" => $uuid, "items" => []]);

            // Si quantité = 0, supprimer le produit

            if ($quantity <= 0) {
                $cart["items"] = array_values(
                    array_filter($cart["items"], function ($item) use ($productId) {
                        return $item["product_id"] != $productId;
                    }),
                );
            } else {
                // Vérifier les quotas

                $product = $this->db->query("SELECT * FROM products WHERE id = :id", [":id" => $productId]);

                if (empty($product)) {
                    echo json_encode(["success" => false, "error" => "Produit introuvable"]);

                    exit();
                }

                $product = $product[0];

                $quotas = $this->calculateAvailableQuotas(
                    $productId,

                    $customer["customer_number"],

                    $customer["country"],

                    $product["max_per_customer"],

                    $product["max_total"],
                );

                if ($quantity > $quotas["max_orderable"]) {
                    echo json_encode([
                        "success" => false,

                        "error" => "Quantité maximale : {$quotas["max_orderable"]}",
                    ]);

                    exit();
                }

                // Mettre à jour la quantité

                foreach ($cart["items"] as &$item) {
                    if ($item["product_id"] == $productId) {
                        $item["quantity"] = $quantity;

                        break;
                    }
                }
            }

            Session::set("cart", $cart);

            echo json_encode(["success" => true, "cart" => $cart]);
        } catch (\Exception $e) {
            error_log("Erreur updateCart() : " . $e->getMessage());

            echo json_encode(["success" => false, "error" => "Erreur serveur"]);
        }

        exit();
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
        header("Content-Type: application/json");

        try {
            $customer = Session::get("public_customer");

            if (!$customer || $customer["campaign_uuid"] !== $uuid) {
                echo json_encode(["success" => false, "error" => "Session expirée"]);

                exit();
            }

            $productId = (int) ($_POST["product_id"] ?? 0);

            $cart = Session::get("cart", ["campaign_uuid" => $uuid, "items" => []]);

            // Retirer le produit

            $cart["items"] = array_values(
                array_filter($cart["items"], function ($item) use ($productId) {
                    return $item["product_id"] != $productId;
                }),
            );

            Session::set("cart", $cart);

            echo json_encode(["success" => true, "cart" => $cart]);
        } catch (\Exception $e) {
            error_log("Erreur removeFromCart() : " . $e->getMessage());

            echo json_encode(["success" => false, "error" => "Erreur serveur"]);
        }

        exit();
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
        header("Content-Type: application/json");

        try {
            $customer = Session::get("public_customer");

            if (!$customer || $customer["campaign_uuid"] !== $uuid) {
                echo json_encode(["success" => false, "error" => "Session expirée"]);

                exit();
            }

            Session::set("cart", [
                "campaign_uuid" => $uuid,

                "items" => [],
            ]);

            echo json_encode(["success" => true, "cart" => Session::get("cart")]);
        } catch (\Exception $e) {
            error_log("Erreur clearCart() : " . $e->getMessage());

            echo json_encode(["success" => false, "error" => "Erreur serveur"]);
        }

        exit();
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

        ?int $maxTotal,
    ): array {
        // Quota utilisé par le client (commandes validées uniquement)

        $customerUsed = $this->getCustomerQuotaUsed($productId, $customerNumber, $country);

        // Quota utilisé globalement (toutes commandes validées)

        $globalUsed = $this->getGlobalQuotaUsed($productId);

        // Calculer disponibles

        $availableForCustomer = is_null($maxPerCustomer) ? PHP_INT_MAX : $maxPerCustomer - $customerUsed;

        $availableGlobal = is_null($maxTotal) ? PHP_INT_MAX : $maxTotal - $globalUsed;

        // Maximum commandable = minimum des 2

        $maxOrderable = min($availableForCustomer, $availableGlobal);

        $isOrderable = $maxOrderable > 0;

        return [
            "customer" => max(0, $availableForCustomer),

            "global" => max(0, $availableGlobal),

            "max_orderable" => max(0, $maxOrderable),

            "is_orderable" => $isOrderable,
        ];
    }

    /**

     * Récupérer la connexion à la base externe

     *

     * @return \PDO

     */

    private function getExternalDatabase(): \PDO
    {
        $host = $_ENV["DB_HOST"] ?? "localhost";

        $dbname = "trendyblog_sig"; // DB externe

        $user = $_ENV["DB_USER"] ?? "";

        $password = $_ENV["DB_PASS"] ?? "";

        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

        return new \PDO($dsn, $user, $password, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,

            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
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
        $table = $country === "BE" ? "BE_CLL" : "LU_CLL";

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

        $stmt->execute([":customer_number" => $customerNumber]);

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
        // ========================================
        // COMPTES INTERNES : accès à TOUTES les campagnes
        // ========================================
        $internalCheck = $this->db->query(
            "SELECT COUNT(*) as count FROM internal_customers
             WHERE customer_number = :customer_number
             AND country = :country
             AND is_active = 1",
            [
                ":customer_number" => $customerNumber,
                ":country" => $country,
            ],
        );

        if (($internalCheck[0]["count"] ?? 0) > 0) {
            return true; // Compte interne : accès à toutes les campagnes
        }

        // Mode AUTOMATIC : tous les clients ont accès

        if ($campaign["customer_assignment_mode"] === "automatic") {
            return true;
        }

        // Mode MANUAL : vérifier si le client est dans la liste

        if ($campaign["customer_assignment_mode"] === "manual") {
            $query = "

                SELECT COUNT(*) as count

                FROM campaign_customers

                WHERE campaign_id = :campaign_id

                  AND customer_number = :customer_number

                  AND country = :country

            ";

            $result = $this->db->query($query, [
                ":campaign_id" => $campaign["id"],

                ":customer_number" => $customerNumber,

                ":country" => $country,
            ]);

            return ($result[0]["count"] ?? 0) > 0;
        }

        // Mode PROTECTED : vérifier mot de passe + existence client

        if ($campaign["customer_assignment_mode"] === "protected") {
            $password = $_POST["password"] ?? "";

            // Vérifier d'abord le mot de passe

            if (empty($password) || $password !== $campaign["order_password"]) {
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

        $products = $this->db->query($query, [":campaign_id" => $campaignId]);

        if (empty($products)) {
            return false; // Aucun produit dans la campagne
        }

        foreach ($products as $product) {
            $quotas = $this->calculateAvailableQuotas(
                $product["id"],

                $customerNumber,

                $country,

                $product["max_per_customer"],

                $product["max_total"],
            );

            // Si au moins 1 produit est disponible

            if ($quotas["is_orderable"]) {
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
            ":product_id" => $productId,

            ":customer_number" => $customerNumber,

            ":country" => $country,
        ]);

        return (int) ($result[0]["total"] ?? 0);
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

        $result = $this->db->query($query, [":product_id" => $productId]);

        return (int) ($result[0]["total"] ?? 0);
    }

    /**

     * Afficher la page d'identification client

     *

     * @param array $campaign Données de la campagne

     * @param int $promotionsCount Nombre de promotions actives

     * @return void

     */

    private function renderIdentificationPage(array $campaign, int $promotionsCount): void
    {
        // Inclure la vue

        require __DIR__ . "/../Views/public/campaign/show.php";
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

        require __DIR__ . "/../Views/public/campaign/access_denied.php";
    }

    /**

     * Afficher la page de validation de commande (checkout)

     *

     * @param string $uuid UUID de la campagne

     * @return void

     * @created 17/11/2025

     */

    public function checkout(string $uuid): void
    {
        // ========================================

        // GESTION DU SWITCH LANGUE (FR/NL)

        // ========================================

        $requestedLang = $_GET["lang"] ?? null;

        if ($requestedLang && in_array($requestedLang, ["fr", "nl"], true)) {
            if (isset($_SESSION["public_customer"])) {
                $_SESSION["public_customer"]["language"] = $requestedLang;
            }

            $cleanUrl = strtok($_SERVER["REQUEST_URI"], "?");

            header("Location: {$cleanUrl}");

            exit();
        }

        // Vérifier session client

        if (!isset($_SESSION["public_customer"]) || $_SESSION["public_customer"]["campaign_uuid"] !== $uuid) {
            header("Location: /stm/c/" . $uuid);

            exit();
        }

        // Récupérer les données de session

        $customer = $_SESSION["public_customer"];

        $cart = $_SESSION["cart"] ?? ["campaign_uuid" => $uuid, "items" => []];

        // Vérifier que le panier n'est pas vide

        if (empty($cart["items"])) {
            $_SESSION["error"] =
                $customer["language"] === "fr"
                    ? "Votre panier est vide. Veuillez ajouter des produits avant de valider."
                    : "Uw winkelwagen is leeg. Voeg producten toe voordat u valideert.";

            header("Location: /stm/c/" . $uuid . "/catalog");

            exit();
        }

        // Récupérer la campagne depuis la DB

        try {
            $query = "SELECT * FROM campaigns WHERE uuid = :uuid AND is_active = 1";

            $campaign = $this->db->query($query, [":uuid" => $uuid]);

            if (empty($campaign)) {
                header("Location: /stm/c/" . $uuid);

                exit();
            }

            $campaign = $campaign[0];

            // Charger la vue checkout

            require __DIR__ . "/../Views/public/campaign/checkout.php";
        } catch (\PDOException $e) {
            error_log("Erreur checkout: " . $e->getMessage());

            $_SESSION["error"] =
                $customer["language"] === "fr"
                    ? "Une erreur est survenue. Veuillez réessayer."
                    : "Er is een fout opgetreden. Probeer het opnieuw.";

            header("Location: /stm/c/" . $uuid . "/catalog");

            exit();
        }
    }

    /**

     * Traiter la soumission de commande

     *

     * Processus complet :

     * 1. Validation email + CGV

     * 2. Validation quotas finale

     * 3. Création/récupération client dans table customers

     * 4. Création commande dans table orders

     * 5. Création lignes dans table order_lines

     * 6. Génération fichier TXT pour ERP

     * 7. Vidage panier

     * 8. Redirection page confirmation

     *

     * @param string $uuid UUID de la campagne

     * @return void

     * @created 17/11/2025

     */

    public function submitOrder(string $uuid): void
    {
        // Vérifier session client

        if (!isset($_SESSION["public_customer"]) || $_SESSION["public_customer"]["campaign_uuid"] !== $uuid) {
            header("Location: /stm/c/" . $uuid);

            exit();
        }

        $customer = $_SESSION["public_customer"];

        $cart = $_SESSION["cart"] ?? ["campaign_uuid" => $uuid, "items" => []];

        // Vérifier panier non vide

        if (empty($cart["items"])) {
            $_SESSION["error"] = $customer["language"] === "fr" ? "Votre panier est vide." : "Uw winkelwagen is leeg.";

            header("Location: /stm/c/" . $uuid . "/catalog");

            exit();
        }

        // PROTECTION : Empêcher double validation

        if (
            isset($_SESSION["last_order_uuid"]) &&
            isset($_SESSION["order_validated_at"]) &&
            time() - $_SESSION["order_validated_at"] < 60
        ) {
            // Commande déjà validée il y a moins de 60 secondes

            header("Location: /stm/c/" . $uuid . "/order/confirmation");

            exit();
        }

        // Vérifier token CSRF

        if (!isset($_POST["_token"]) || $_POST["_token"] !== $_SESSION["csrf_token"]) {
            $_SESSION["error"] =
                $customer["language"] === "fr" ? "Token de sécurité invalide." : "Ongeldig beveiligingstoken.";

            header("Location: /stm/c/" . $uuid . "/checkout");

            exit();
        }

        // Récupérer et valider l'email

        $customerEmail = trim($_POST["customer_email"] ?? "");

        if (empty($customerEmail) || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            $_SESSION["error"] =
                $customer["language"] === "fr"
                    ? "Veuillez saisir une adresse email valide."
                    : "Gelieve een geldig e-mailadres in te voeren.";

            header("Location: /stm/c/" . $uuid . "/checkout");

            exit();
        }

        // Vérifier CGV

        if (!isset($_POST["cgv_1"]) || !isset($_POST["cgv_2"]) || !isset($_POST["cgv_3"])) {
            $_SESSION["error"] =
                $customer["language"] === "fr"
                    ? "Vous devez accepter toutes les conditions."
                    : "U moet alle voorwaarden aanvaarden.";

            header("Location: /stm/c/" . $uuid . "/checkout");

            exit();
        }

        try {
            // Démarrer transaction

            $this->db->beginTransaction();

            // 1. Récupérer la campagne

            $query = "SELECT * FROM campaigns WHERE uuid = :uuid AND is_active = 1";

            $campaignResult = $this->db->query($query, [":uuid" => $uuid]);

            if (empty($campaignResult)) {
                throw new \Exception("Campagne introuvable");
            }

            $campaign = $campaignResult[0];

            // 2. VALIDATION FINALE DES QUOTAS pour chaque produit

            foreach ($cart["items"] as $item) {
                // Récupérer le produit

                $productQuery = "SELECT * FROM products WHERE id = :id AND campaign_id = :campaign_id";

                $productResult = $this->db->query($productQuery, [
                    ":id" => $item["product_id"],

                    ":campaign_id" => $campaign["id"],
                ]);

                if (empty($productResult)) {
                    throw new \Exception("Produit introuvable : " . $item["name_" . $customer["language"]]);
                }

                $product = $productResult[0];

                // Calculer quotas disponibles

                $quotas = $this->calculateAvailableQuotas(
                    $product["id"],

                    $customer["customer_number"],

                    $customer["country"],

                    $product["max_per_customer"],

                    $product["max_total"],
                );

                // Vérifier si quantité demandée est disponible

                if ($item["quantity"] > $quotas["max_orderable"]) {
                    throw new \Exception(
                        "Quota dépassé pour " .
                            $item["name_" . $customer["language"]] .
                            ". Maximum disponible : " .
                            $quotas["max_orderable"],
                    );
                }
            }

            // 3. Créer ou récupérer le client dans table customers

            $customerId = $this->getOrCreateCustomer(
                $customer["customer_number"],

                $customer["country"],

                $customer["company_name"] ?? "",

                $customerEmail,
            );

            // 4. Créer la commande dans table orders

            $orderUuid = $this->generateUuid();

            $totalItems = array_sum(array_column($cart["items"], "quantity"));

            $totalProducts = count($cart["items"]);

            $queryOrder = "INSERT INTO orders (

                uuid, campaign_id, customer_id, customer_email,

                total_items, total_products, status, created_at

            ) VALUES (

                :uuid, :campaign_id, :customer_id, :customer_email,

                :total_items, :total_products, 'pending', NOW()

            )";

            $this->db->execute($queryOrder, [
                ":uuid" => $orderUuid,

                ":campaign_id" => $campaign["id"],

                ":customer_id" => $customerId,

                ":customer_email" => $customerEmail,

                ":total_items" => $totalItems,

                ":total_products" => $totalProducts,
            ]);

            $orderId = $this->db->lastInsertId();

            // 5. Créer les lignes de commande dans order_lines

            foreach ($cart["items"] as $item) {
                $queryLine = "INSERT INTO order_lines (

                    order_id, product_id, product_code, product_name, quantity, created_at

                ) VALUES (

                    :order_id, :product_id, :product_code, :product_name, :quantity, NOW()

                )";

                $this->db->execute($queryLine, [
                    ":order_id" => $orderId,

                    ":product_id" => $item["product_id"],

                    ":product_code" => $item["code"],

                    ":product_name" => $item["name_" . $customer["language"]],

                    ":quantity" => $item["quantity"],
                ]);
            }

            // 6. Générer le fichier TXT pour l'ERP

            $fileData = $this->generateOrderFile(
                $orderId,

                $campaign,

                $customer["customer_number"],

                $customer["country"],

                $cart["items"],
            );

            // Mettre à jour le chemin du fichier ET le contenu dans la commande

            $this->db->execute(
                "UPDATE orders SET file_path = :file_path, file_content = :file_content, file_generated_at = NOW(), status = 'synced' WHERE id = :id",

                [":file_path" => $fileData['path'], ":file_content" => $fileData['content'], ":id" => $orderId],
            );

            // 7. Valider la transaction

            $this->db->commit();

            // 8. Vider le panier

            $_SESSION["cart"] = ["campaign_uuid" => $uuid, "items" => []];

            // 9. Stocker l'UUID de la commande en session

            $_SESSION["last_order_uuid"] = $orderUuid;

            // 10. Message de succès

            $_SESSION["success"] =
                $customer["language"] === "fr"
                    ? "Votre commande a été validée avec succès !"
                    : "Uw bestelling is succesvol gevalideerd!";

            // 11. Préparer les données email pour envoi asynchrone

            $_SESSION["pending_email"] = [
                "customer_email" => $customerEmail,

                "order_id" => $orderId,

                "order_number" => "ORD-" . date("Y") . "-" . str_pad($orderId, 6, "0", STR_PAD_LEFT),

                "campaign_title_fr" => $campaign["title_fr"],

                "campaign_title_nl" => $campaign["title_nl"],

                "customer_number" => $customer["customer_number"],

                "company_name" => $customer["company_name"] ?? "Client " . $customer["customer_number"],

                "country" => $campaign["country"],

                "deferred_delivery" => $campaign["deferred_delivery"] ?? 0,

                "delivery_date" => $campaign["delivery_date"] ?? null,

                "language" => $customer["language"] ?? "fr",

                "lines" => $cart["items"],
            ];

            // Horodatage de la validation (protection double soumission)

            $_SESSION["order_validated_at"] = time();

            // 12. Redirection IMMÉDIATE (AVANT l'envoi email)

            header("Location: /stm/c/" . $uuid . "/order/confirmation");

            exit();
        } catch (\Exception $e) {
            // Rollback en cas d'erreur

            $this->db->rollBack();

            error_log("Erreur submitOrder: " . $e->getMessage());

            $_SESSION["error"] =
                $customer["language"] === "fr"
                    ? "Erreur lors de la validation de votre commande : " . $e->getMessage()
                    : "Fout bij het valideren van uw bestelling: " . $e->getMessage();

            header("Location: /stm/c/" . $uuid . "/checkout");

            exit();
        }
    }

    /**

     * Récupérer ou créer un client dans la table customers

     *

     * @param string $customerNumber Numéro client

     * @param string $country Pays (BE/LU)

     * @param string $companyName Nom société

     * @param string $email Email

     * @return int ID du client

     * @created 17/11/2025

     */

    private function getOrCreateCustomer(
        string $customerNumber,

        string $country,

        string $companyName,

        string $email,
    ): int {
        // Chercher client existant

        $query = "SELECT id FROM customers WHERE customer_number = :number AND country = :country LIMIT 1";

        $existingResult = $this->db->query($query, [
            ":number" => $customerNumber,

            ":country" => $country,
        ]);

        if (!empty($existingResult)) {
            $existing = $existingResult[0];

            // Client existe : mettre à jour email et last_order_date

            $this->db->execute(
                "UPDATE customers SET email = :email, last_order_date = CURDATE(), total_orders = total_orders + 1 WHERE id = :id",

                [":email" => $email, ":id" => $existing["id"]],
            );

            return (int) $existing["id"];
        }

        // Client n'existe pas : créer

        $queryInsert = "INSERT INTO customers (

            customer_number, country, company_name, email,

            total_orders, last_order_date, is_active, created_at

        ) VALUES (

            :number, :country, :company, :email,

            1, CURDATE(), 1, NOW()

        )";

        $this->db->execute($queryInsert, [
            ":number" => $customerNumber,

            ":country" => $country,

            ":company" => $companyName,

            ":email" => $email,
        ]);

        return $this->db->lastInsertId();
    }

    /**

     * Générer le fichier TXT pour l'ERP

     *

     * Format traitement.php :

     * I00{DDMMYY}{DDMMYY_livraison}

     * H{numClient8}{V/W}{NomCampagne}

     * D{numProduit}{qte10digits}

     *

     * @param int $orderId ID de la commande

     * @param array $campaign Données campagne

     * @param string $customerNumber Numéro client

     * @param string $country Pays (BE/LU)

     * @param array $items Produits du panier

     * @return string Chemin relatif du fichier généré

     * @created 17/11/2025

     */

    private function generateOrderFile(
        int $orderId,

        array $campaign,

        string $customerNumber,

        string $country,

        array $items,
    ): array {
        $today = date("dmy"); // Format: 171125

        // Ligne I00 : Date commande + date livraison (si applicable)

        if ($campaign["deferred_delivery"] == 1 && !empty($campaign["delivery_date"])) {
            $deliveryDate = date("dmy", strtotime($campaign["delivery_date"]));

            $lineI = "I00{$today}{$deliveryDate}\n";
        } else {
            $lineI = "I00{$today}\n";
        }

        // Formater numéro client sur 8 caractères

        $customerNumber8 = $this->formatCustomerNumber($customerNumber);

        // Ligne H : Numéro client + Type commande + Nom campagne

        $orderType = $campaign["order_type"]; // V ou W

        $campaignName = str_replace([" ", "-", "_"], "", $campaign["name"]); // Enlever espaces et tirets

        $lineH = "H{$customerNumber8}{$orderType}{$campaignName}\n";

        // Lignes D : Détails produits

        $linesD = "";

        foreach ($items as $item) {
            $productCode = $item["code"];

            $quantity = sprintf("%'.010d", $item["quantity"]); // Padding 10 digits avec 0

            $linesD .= "D{$productCode}{$quantity}\n";
        }

        // Contenu complet du fichier

        $content = $lineI . $lineH . $linesD;

        // Créer le répertoire si nécessaire

        $directory = $country === "BE" ? "commande_BE" : "commande_LU";

        $fullPath = __DIR__ . "/../../public/" . $directory;

        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        // Nom du fichier : WebAction_{Ymd-His}_{numClient8}.txt
        // Utiliser formatCustomerNumberForFilename() pour remplacer * par x (Windows)
        $customerNumberFilename = $this->formatCustomerNumberForFilename($customerNumber);
        $filename = "WebAction_" . date("Ymd-His") . "_" . $customerNumberFilename . ".txt";

        $filepath = $fullPath . "/" . $filename;

        // Écrire le fichier

        file_put_contents($filepath, $content);

        // Retourner chemin relatif ET contenu pour stockage en DB

        return [
            'path' => "/" . $directory . "/" . $filename,
            'content' => $content
        ];
    }

    /**
     * Formater un numéro client sur 8 caractères (pour contenu fichier)
     *
     * Règles :
     * - Numéro de base (6 car.) : Ajouter "00" → 8 caractères
     *   Ex: 802412 → 80241200, *12345 → *1234500, DE1234 → DE123400
     * - Numéro livraison (6 + "-" + 2 = 9 car.) : Supprimer le tiret → 8 caractères
     *   Ex: 802412-AF → 802412AF, *12345-G1 → *12345G1, DE1234-25 → DE123425
     *
     * @param string $number Numéro client brut
     * @return string Numéro sur 8 caractères
     * @created 17/11/2025
     * @modified 25/11/2025 - Correction : garder *, lettres intacts, supprimer uniquement le tiret
     */
    private function formatCustomerNumber(string $number): string
    {
        // Supprimer uniquement le tiret
        $cleaned = str_replace("-", "", $number);

        // Si 6 caractères → ajouter 00 à la fin
        if (strlen($cleaned) === 6) {
            return $cleaned . "00";
        }

        return $cleaned;
    }

    /**
     * Formater un numéro client pour le nom de fichier (compatible Windows)
     *
     * Même logique que formatCustomerNumber() mais remplace * par x
     * car * est interdit dans les noms de fichiers Windows
     *
     * @param string $number Numéro client brut
     * @return string Numéro formaté compatible Windows
     * @created 25/11/2025
     */
    private function formatCustomerNumberForFilename(string $number): string
    {
        $formatted = $this->formatCustomerNumber($number);
        return str_replace("*", "x", $formatted);
    }

    /**

     * Générer un UUID v4

     *

     * @return string UUID

     * @created 17/11/2025

     */

    private function generateUuid(): string
    {
        return sprintf(
            "%04x%04x-%04x-%04x-%04x-%04x%04x%04x",

            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),

            mt_rand(0, 0xffff),

            mt_rand(0, 0x0fff) | 0x4000,

            mt_rand(0, 0x3fff) | 0x8000,

            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
        );
    }

    /**

     * Afficher la page de confirmation de commande

     *

     * @param string $uuid UUID de la campagne

     * @return void

     * @created 19/11/2025

     */

    public function orderConfirmation(string $uuid): void
    {
        // Programmer l'envoi email APRÈS la réponse HTTP (shutdown function)

        if (isset($_SESSION["pending_email"])) {
            $data = $_SESSION["pending_email"];

            unset($_SESSION["pending_email"]);

            register_shutdown_function(function () use ($data) {
                @ini_set("display_errors", "0");

                error_reporting(0);

                try {
                    $orderData = [
                        "order_number" => $data["order_number"],

                        "campaign_title_fr" => $data["campaign_title_fr"],

                        "campaign_title_nl" => $data["campaign_title_nl"],

                        "customer_number" => $data["customer_number"],

                        "company_name" => $data["company_name"],

                        "created_at" => date("Y-m-d H:i:s"),

                        "country" => $data["country"],

                        "deferred_delivery" => $data["deferred_delivery"],

                        "delivery_date" => $data["delivery_date"],

                        "lines" => [],
                    ];

                    foreach ($data["lines"] as $item) {
                        $orderData["lines"][] = [
                            "name_fr" => $item["name_fr"],

                            "name_nl" => $item["name_nl"],

                            "quantity" => $item["quantity"],

                            "product_code" => $item["code"],
                        ];
                    }

                    $mailchimpService = new \App\Services\MailchimpEmailService();

                    $emailSent = @$mailchimpService->sendOrderConfirmation(
                        $data["customer_email"],
                        $orderData,
                        $data["language"],
                    );

                    if ($emailSent) {
                        error_log(
                            "Email confirmation envoyé avec succès via Mailchimp à: {$data["customer_email"]} (Commande: {$data["order_number"]})",
                        );
                    } else {
                        error_log(
                            "Échec envoi email confirmation via Mailchimp à: {$data["customer_email"]} (Commande: {$data["order_number"]})",
                        );
                    }
                } catch (\Exception $e) {
                    error_log("Erreur envoi email Mailchimp asynchrone: " . $e->getMessage());
                }
            });
        }

        // Vérifier la session

        if (!isset($_SESSION["last_order_uuid"])) {
            header("Location: /stm/c/" . $uuid);

            exit();
        }

        // Récupérer les infos de la campagne

        $campaign = $this->db->query(
            "SELECT * FROM campaigns WHERE uuid = :uuid",

            [":uuid" => $uuid],
        );

        if (empty($campaign)) {
            header("Location: /stm/");

            exit();
        }

        $campaign = $campaign[0];

        // Récupérer la commande

        $orderUuid = $_SESSION["last_order_uuid"];

        $order = $this->db->query(
            "SELECT * FROM orders WHERE uuid = :uuid",

            [":uuid" => $orderUuid],
        );

        if (empty($order)) {
            header("Location: /stm/c/" . $uuid);

            exit();
        }

        $order = $order[0];

        // Charger la vraie vue de confirmation

        require_once __DIR__ . "/../Views/public/campaign/confirmation.php";
    }

    /**
     * Afficher une page fixe (CGU, CGV, mentions légales, etc.)
     *
     * Route: GET /c/{uuid}/page/{slug}
     *
     * @param string $uuid UUID de la campagne
     * @param string $slug Slug de la page (cgu, cgv, mentions-legales, etc.)
     */
    public function showStaticPage(string $uuid, string $slug): void
    {
        // Récupérer la campagne
        $campaign = $this->db->query(
            "SELECT * FROM campaigns WHERE uuid = :uuid",
            [":uuid" => $uuid]
        );

        if (empty($campaign)) {
            http_response_code(404);
            echo "<h1>Campagne introuvable</h1>";
            exit;
        }

        $campaign = $campaign[0];

        // Récupérer le model StaticPage
        $staticPageModel = new \App\Models\StaticPage();

        // Déterminer la langue
        $language = $_SESSION['public_customer']['language'] ?? 'fr';
        if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'nl'])) {
            $language = $_GET['lang'];
        }
        // Luxembourg = toujours français
        if ($campaign['country'] === 'LU') {
            $language = 'fr';
        }

        // Récupérer la page avec surcharge éventuelle
        $pageContent = $staticPageModel->render($slug, $language, (int)$campaign['id']);

        if (!$pageContent) {
            http_response_code(404);
            echo "<h1>Page introuvable</h1>";
            exit;
        }

        // Récupérer les pages pour le footer
        $footerPages = $staticPageModel->getFooterPages((int)$campaign['id']);

        // Variables pour la vue
        $title = $pageContent['title'];
        $content = $pageContent['content'];
        $currentLanguage = $language;
        $showLanguageSwitch = ($campaign['country'] === 'BE');

        require __DIR__ . "/../Views/public/static_page.php";
    }
}