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
 * @modified 2025/01/05 - Intégration API Trendy Foods pour éligibilité produits
 * @modified 2026/01/05 - Sprint 14 : Mode Représentant (commande pour client)
 * @modified 2026/01/08 - Sprint 14 : Amélioration messages compte désactivé + prompt select_account
 * @modified 2026/01/08 - Sprint 15 : Mode traitement commandes (direct/pending)
 */

namespace App\Controllers;

use Core\Database;
use Core\Session;
use App\Services\MailchimpEmailService;
use App\Services\TrendyFoodsApiService;

class PublicCampaignController
{
    private Database $db;

    /**
     * Service API Trendy Foods pour vérification éligibilité
     */
    private ?TrendyFoodsApiService $trendyApi = null;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Obtenir l'instance du service API Trendy Foods (lazy loading)
     *
     * @return TrendyFoodsApiService
     */
    private function getTrendyApiService(): TrendyFoodsApiService
    {
        if ($this->trendyApi === null) {
            $this->trendyApi = new TrendyFoodsApiService();
        }
        return $this->trendyApi;
    }

    /**
     * Récupérer l'adresse IP du client (gestion des proxys)
     *
     * @return string|null Adresse IP
     */
    private function getClientIp(): ?string
    {
        // Priorité : headers proxy puis REMOTE_ADDR
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Proxy standard
            'HTTP_X_REAL_IP',            // Nginx
            'HTTP_CLIENT_IP',            // Autres
            'REMOTE_ADDR'                // Direct
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                // X-Forwarded-For peut contenir plusieurs IPs, prendre la première
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                // Valider que c'est une IP valide
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return null;
    }

    /**
     * Détecter le type d'appareil depuis le User Agent
     *
     * @return string 'mobile', 'tablet' ou 'desktop'
     */
    private function detectDeviceType(): string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if (empty($userAgent)) {
            return 'desktop';
        }

        $userAgent = strtolower($userAgent);

        // Tablettes (vérifier avant mobile car certains tablets ont "mobile" dans leur UA)
        $tablets = ['ipad', 'tablet', 'playbook', 'silk', 'kindle'];
        foreach ($tablets as $tablet) {
            if (strpos($userAgent, $tablet) !== false) {
                return 'tablet';
            }
        }

        // Android tablet (sans "mobile")
        if (strpos($userAgent, 'android') !== false && strpos($userAgent, 'mobile') === false) {
            return 'tablet';
        }

        // Mobiles
        $mobiles = ['iphone', 'ipod', 'android', 'mobile', 'phone', 'blackberry', 'opera mini', 'opera mobi'];
        foreach ($mobiles as $mobile) {
            if (strpos($userAgent, $mobile) !== false) {
                return 'mobile';
            }
        }

        return 'desktop';
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
        // Sprint 14 : Si cookie mode rep présent et session expirée, rediriger vers SSO rep
        if ($this->shouldRedirectToRepLogin($uuid)) {
            header("Location: /stm/c/{$uuid}/rep");
            exit();
        }

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

            // ========================================
            // VÉRIFICATION ÉLIGIBILITÉ PRODUITS VIA API TRENDY FOODS
            // ========================================

            $eligibilityCheck = $this->checkProductsEligibility($campaign["id"], $customerNumber, $country);

            // Si l'API a échoué
            if ($eligibilityCheck['api_error']) {
                error_log("[PublicCampaignController] API Trendy Foods indisponible pour client {$customerNumber}");
                // Message d'erreur générique pour l'utilisateur
                Session::set("error", $selectedLanguage === 'fr'
                    ? "Service temporairement indisponible. Veuillez réessayer dans quelques instants."
                    : "Service tijdelijk niet beschikbaar. Probeer het later opnieuw.");
                header("Location: /stm/c/{$uuid}");
                exit();
            }

            // Si aucun produit autorisé pour ce client
            if (empty($eligibilityCheck['authorized_codes'])) {
                $this->renderAccessDenied("no_products_authorized", $uuid, $campaign);
                return;
            }

            // Vérifier si tous les produits ont atteint leurs quotas
            $hasAvailableProducts = $this->checkAvailableProducts($campaign["id"], $customerNumber, $country);

            if (!$hasAvailableProducts) {
                $this->renderAccessDenied("quotas_reached", $uuid, $campaign);
                return;
            }

            // Tout est OK - créer la session client
            Session::set("public_customer", [
                "customer_number" => $customerNumber,
                "country" => $country,
                "company_name" => $customerData["company_name"],
                "campaign_uuid" => $uuid,
                "campaign_id" => $campaign["id"],
                "language" => $selectedLanguage,
                "logged_at" => date("Y-m-d H:i:s"),
                // Par défaut, ce n'est PAS une commande rep
                "is_rep_order" => false,
                "rep_id" => null,
                "rep_name" => null,
                "rep_email" => null,
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
                // Sprint 14 : Si cookie mode rep, rediriger vers SSO rep
                if ($this->shouldRedirectToRepLogin($uuid)) {
                    header("Location: /stm/c/{$uuid}/rep");
                } else {
                    header("Location: /stm/c/{$uuid}");
                }
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

            // ========================================
            // RÉCUPÉRER L'ÉLIGIBILITÉ PRODUITS VIA API (TEMPS RÉEL)
            // ========================================

            $eligibilityCheck = $this->checkProductsEligibility(
                $campaign["id"],
                $customer["customer_number"],
                $customer["country"]
            );

            // Si l'API a échoué, afficher une erreur
            if ($eligibilityCheck['api_error']) {
                error_log("[PublicCampaignController] API Trendy Foods indisponible pour catalog - client {$customer['customer_number']}");
                Session::set("error", $customer["language"] === 'fr'
                    ? "Service temporairement indisponible. Veuillez réessayer."
                    : "Service tijdelijk niet beschikbaar. Probeer het opnieuw.");
                header("Location: /stm/c/{$uuid}");
                exit();
            }

            // Codes produits autorisés
            $authorizedCodes = $eligibilityCheck['authorized_codes'];

            // Infos produits (pour les prix)
            $productsApiInfo = $eligibilityCheck['products_info'];

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

                // Filtrer les produits selon l'éligibilité API et calculer les quotas
                $filteredProducts = [];

                foreach ($products as $product) {
                    // Vérifier si le produit est autorisé par l'API
                    if (!in_array($product["product_code"], $authorizedCodes)) {
                        continue; // Produit non autorisé → on l'ignore
                    }

                    // Calculer les quotas disponibles
                    $quotas = $this->calculateAvailableQuotas(
                        $product["id"],
                        $customer["customer_number"],
                        $customer["country"],
                        $product["max_per_customer"],
                        $product["max_total"],
                    );

                    $product["available_for_customer"] = $quotas["customer"];
                    $product["available_global"] = $quotas["global"];
                    $product["max_orderable"] = $quotas["max_orderable"];
                    $product["is_orderable"] = $quotas["is_orderable"];

                    // Ajouter les infos de l'API (prix)
                    if (isset($productsApiInfo[$product["product_code"]])) {
                        $product["api_prix"] = $productsApiInfo[$product["product_code"]]["prix"];
                        $product["api_prix_promo"] = $productsApiInfo[$product["product_code"]]["prix_promo"];
                        $product["api_prix_colis"] = $productsApiInfo[$product["product_code"]]["prix_colis"];
                    }

                    $filteredProducts[] = $product;
                }

                $categories[$key]["products"] = $filteredProducts;
            }

            // Supprimer les catégories vides (aucun produit autorisé)
            $categories = array_filter($categories, function($cat) {
                return !empty($cat["products"]);
            });
            $categories = array_values($categories); // Réindexer

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
     * Vérifier l'éligibilité des produits d'une campagne pour un client via l'API Trendy Foods
     *
     * @param int $campaignId ID de la campagne
     * @param string $customerNumber Numéro client
     * @param string $country Code pays (BE/LU)
     * @return array ['api_error' => bool, 'authorized_codes' => array, 'products_info' => array]
     */
    private function checkProductsEligibility(int $campaignId, string $customerNumber, string $country): array
    {
        // Récupérer tous les product_code de la campagne
        $query = "
            SELECT product_code
            FROM products
            WHERE campaign_id = :campaign_id
              AND is_active = 1
        ";
        $products = $this->db->query($query, [":campaign_id" => $campaignId]);

        if (empty($products)) {
            return [
                'api_error' => false,
                'authorized_codes' => [],
                'products_info' => []
            ];
        }

        // Extraire les codes produits
        $productCodes = array_column($products, 'product_code');

        // Appeler l'API Trendy Foods
        $apiService = $this->getTrendyApiService();
        $productsInfo = $apiService->getProductsInfo($customerNumber, $country, $productCodes);

        // Vérifier si l'API a répondu correctement
        if (!$apiService->isApiResponseValid($productsInfo)) {
            return [
                'api_error' => true,
                'authorized_codes' => [],
                'products_info' => []
            ];
        }

        // Filtrer les produits autorisés
        $authorizedCodes = $apiService->filterAuthorizedProducts($productsInfo);

        return [
            'api_error' => false,
            'authorized_codes' => $authorizedCodes,
            'products_info' => $productsInfo
        ];
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

            // Calculer les quotas disponibles
            $quotas = $this->calculateAvailableQuotas(
                $productId,
                $customer["customer_number"],
                $customer["country"],
                $product["max_per_customer"],
                $product["max_total"],
            );

            // Récupérer le panier actuel
            $cart = Session::get("cart", ["campaign_uuid" => $uuid, "items" => []]);

            // Calculer la quantité déjà dans le panier
            $currentInCart = 0;
            foreach ($cart["items"] as $item) {
                if ($item["product_id"] === $productId) {
                    $currentInCart = $item["quantity"];
                    break;
                }
            }

            // Vérifier si on peut ajouter la quantité demandée
            $newTotal = $currentInCart + $quantity;

            if ($newTotal > $quotas["max_orderable"]) {
                $lang = $customer["language"] ?? "fr";
                $errorMsg =
                    $lang === "fr"
                        ? "Quantité maximum atteinte. Vous pouvez commander jusqu'à " . $quotas["max_orderable"] . " unités."
                        : "Maximum aantal bereikt. U kunt tot " . $quotas["max_orderable"] . " eenheden bestellen.";

                echo json_encode([
                    "success" => false,
                    "error" => $errorMsg,
                    "max_available" => $quotas["max_orderable"],
                ]);
                exit();
            }

            // Ajouter ou mettre à jour le produit dans le panier
            $found = false;
            foreach ($cart["items"] as &$item) {
                if ($item["product_id"] === $productId) {
                    $item["quantity"] = $newTotal;
                    $found = true;
                    break;
                }
            }
            unset($item);

            if (!$found) {
                // Récupérer les prix via API Trendy Foods (pour mode rep)
                $apiPrix = null;
                $apiPrixPromo = null;

                try {
                    $trendyApi = $this->getTrendyApiService();
                    $productsInfo = $trendyApi->getProductsInfo(
                        $customer["customer_number"],
                        $customer["country"],
                        [$product["product_code"]]
                    );

                    if ($trendyApi->isApiResponseValid($productsInfo) && isset($productsInfo[$product["product_code"]])) {
                        $apiPrix = $productsInfo[$product["product_code"]]["prix"];
                        $apiPrixPromo = $productsInfo[$product["product_code"]]["prix_promo"];
                    }
                } catch (\Exception $e) {
                    error_log("Erreur API prix dans addToCart: " . $e->getMessage());
                }

                $cart["items"][] = [
                    "product_id" => $productId,
                    "code" => $product["product_code"],
                    "name_fr" => $product["name_fr"],
                    "name_nl" => $product["name_nl"] ?? $product["name_fr"],
                    "quantity" => $quantity,
                    "image_fr" => $product["image_fr"],
                    "image_nl" => $product["image_nl"] ?? $product["image_fr"],
                    // Prix récupérés via API
                    "api_prix" => $apiPrix,
                    "api_prix_promo" => $apiPrixPromo,
                ];
            }

            // Sauvegarder le panier
            Session::set("cart", $cart);

            // Calculer le total d'articles
            $totalItems = array_sum(array_column($cart["items"], "quantity"));

            echo json_encode([
                "success" => true,
                "cart" => $cart,
                "totalItems" => $totalItems,
            ]);
        } catch (\PDOException $e) {
            error_log("Erreur addToCart : " . $e->getMessage());
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
            $customer = Session::get("public_customer");

            if (!$customer || $customer["campaign_uuid"] !== $uuid) {
                echo json_encode(["success" => false, "error" => "Session expirée"]);
                exit();
            }

            $productId = (int) ($_POST["product_id"] ?? 0);
            $quantity = (int) ($_POST["quantity"] ?? 0);

            $cart = Session::get("cart", ["campaign_uuid" => $uuid, "items" => []]);

            // Si quantité = 0, supprimer le produit
            if ($quantity <= 0) {
                $cart["items"] = array_filter($cart["items"], function ($item) use ($productId) {
                    return $item["product_id"] !== $productId;
                });
                $cart["items"] = array_values($cart["items"]);
            } else {
                // Vérifier les quotas
                $productQuery = "SELECT * FROM products WHERE id = :id";
                $product = $this->db->query($productQuery, [":id" => $productId]);

                if (!empty($product)) {
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
                            "error" => "Quantité maximum dépassée",
                            "max_available" => $quotas["max_orderable"],
                        ]);
                        exit();
                    }
                }

                // Mettre à jour la quantité
                foreach ($cart["items"] as &$item) {
                    if ($item["product_id"] === $productId) {
                        $item["quantity"] = $quantity;
                        break;
                    }
                }
                unset($item);
            }

            Session::set("cart", $cart);

            $totalItems = array_sum(array_column($cart["items"], "quantity"));

            echo json_encode([
                "success" => true,
                "cart" => $cart,
                "totalItems" => $totalItems,
            ]);
        } catch (\PDOException $e) {
            error_log("Erreur updateCart : " . $e->getMessage());
            echo json_encode(["success" => false, "error" => "Erreur serveur"]);
        }

        exit();
    }

    /**
     * Supprimer un produit du panier
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

            $cart["items"] = array_filter($cart["items"], function ($item) use ($productId) {
                return $item["product_id"] !== $productId;
            });
            $cart["items"] = array_values($cart["items"]);

            Session::set("cart", $cart);

            $totalItems = array_sum(array_column($cart["items"], "quantity"));

            echo json_encode([
                "success" => true,
                "cart" => $cart,
                "totalItems" => $totalItems,
            ]);
        } catch (\PDOException $e) {
            error_log("Erreur removeFromCart : " . $e->getMessage());
            echo json_encode(["success" => false, "error" => "Erreur serveur"]);
        }

        exit();
    }

    /**
     * Vider le panier
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

            Session::set("cart", ["campaign_uuid" => $uuid, "items" => []]);

            echo json_encode([
                "success" => true,
                "cart" => ["campaign_uuid" => $uuid, "items" => []],
                "totalItems" => 0,
            ]);
        } catch (\PDOException $e) {
            error_log("Erreur clearCart : " . $e->getMessage());
            echo json_encode(["success" => false, "error" => "Erreur serveur"]);
        }

        exit();
    }

    /**
     * Calculer les quotas disponibles pour un produit/client
     *
     * @param int $productId ID du produit
     * @param string $customerNumber Numéro client
     * @param string $country Pays
     * @param int|null $maxPerCustomer Quota max par client (null = illimité)
     * @param int|null $maxTotal Quota global (null = illimité)
     * @return array Quotas calculés
     */
    private function calculateAvailableQuotas(
        int $productId,
        string $customerNumber,
        string $country,
        ?int $maxPerCustomer,
        ?int $maxTotal,
    ): array {
        // Quota client
        $customerUsed = $this->getCustomerQuotaUsed($productId, $customerNumber, $country);
        $customerAvailable = $maxPerCustomer !== null ? max(0, $maxPerCustomer - $customerUsed) : PHP_INT_MAX;

        // Quota global
        $globalUsed = $this->getGlobalQuotaUsed($productId);
        $globalAvailable = $maxTotal !== null ? max(0, $maxTotal - $globalUsed) : PHP_INT_MAX;

        // Maximum commandable (le plus restrictif)
        $maxOrderable = min($customerAvailable, $globalAvailable);

        return [
            "customer" => $customerAvailable,
            "global" => $globalAvailable,
            "max_orderable" => $maxOrderable,
            "is_orderable" => $maxOrderable > 0,
        ];
    }

    /**
     * Obtenir une connexion à la base de données externe (trendyblog_sig)
     *
     * @return \PDO Connexion PDO
     */
    private function getExternalDatabase(): \PDO
    {
        // Utiliser la méthode env() pour récupérer les variables de manière robuste
        $host = $this->env("EXTERNAL_DB_HOST", $this->env("DB_HOST", "localhost"));
        $dbname = $this->env("EXTERNAL_DB_NAME", "trendyblog_sig");
        $username = $this->env("EXTERNAL_DB_USER", $this->env("DB_USER", ""));
        $password = $this->env("EXTERNAL_DB_PASSWORD", $this->env("DB_PASS", ""));

        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

        return new \PDO($dsn, $username, $password, [
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
            // Sprint 14 : Si cookie mode rep, rediriger vers SSO rep
            if ($this->shouldRedirectToRepLogin($uuid)) {
                header("Location: /stm/c/{$uuid}/rep");
            } else {
                header("Location: /stm/c/" . $uuid);
            }
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

            // ========================================
            // SPRINT 14 : Rafraîchir les prix via API pour le checkout (mode rep)
            // ========================================
            $isRepOrder = $customer["is_rep_order"] ?? false;
            $showPrices = ($campaign["show_prices"] ?? 1) == 1;
            $orderType = $campaign["order_type"] ?? "W";

            if ($isRepOrder && $showPrices && !empty($cart["items"])) {
                try {
                    // Récupérer tous les codes produits du panier
                    $productCodes = array_column($cart["items"], "code");

                    // Appeler l'API pour obtenir les prix à jour
                    $trendyApi = $this->getTrendyApiService();
                    $productsInfo = $trendyApi->getProductsInfo(
                        $customer["customer_number"],
                        $customer["country"],
                        $productCodes
                    );

                    // Mettre à jour les prix dans le panier
                    if ($trendyApi->isApiResponseValid($productsInfo)) {
                        foreach ($cart["items"] as &$item) {
                            if (isset($productsInfo[$item["code"]])) {
                                $item["api_prix"] = $productsInfo[$item["code"]]["prix"];
                                $item["api_prix_promo"] = $productsInfo[$item["code"]]["prix_promo"];
                            }
                        }
                        unset($item);

                        // Sauvegarder le panier avec les prix mis à jour
                        $_SESSION["cart"] = $cart;
                    }
                } catch (\Exception $e) {
                    error_log("Erreur API prix dans checkout: " . $e->getMessage());
                }
            }

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
     * 4. Création commande dans table orders (avec traçabilité rep si applicable)
     * 5. Création lignes dans table order_lines
     * 6. Génération fichier TXT pour ERP
     * 7. Vidage panier
     * 8. Redirection page confirmation
     *
     * @param string $uuid UUID de la campagne
     * @return void
     * @created 17/11/2025
     * @modified 05/01/2026 - Sprint 14 : Ajout traçabilité rep (ordered_by_rep_id, order_source)
     */
    public function submitOrder(string $uuid): void
    {
        // Vérifier session client
        if (!isset($_SESSION["public_customer"]) || $_SESSION["public_customer"]["campaign_uuid"] !== $uuid) {
            // Sprint 14 : Si cookie mode rep, rediriger vers SSO rep
            if ($this->shouldRedirectToRepLogin($uuid)) {
                header("Location: /stm/c/{$uuid}/rep");
            } else {
                header("Location: /stm/c/" . $uuid);
            }
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
            // ========================================
            // SPRINT 14 : Traçabilité rep + données tracking
            // ========================================
            $orderUuid = $this->generateUuid();
            $totalItems = array_sum(array_column($cart["items"], "quantity"));
            $totalProducts = count($cart["items"]);

            // Déterminer la source et le rep si applicable
            $isRepOrder = $customer["is_rep_order"] ?? false;
            $orderSource = $isRepOrder ? 'rep' : 'client';
            $orderedByRepId = $isRepOrder ? ($customer["rep_id"] ?? null) : null;

            // Données tracking
            $ipAddress = $this->getClientIp();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $deviceType = $this->detectDeviceType();

            $queryOrder = "INSERT INTO orders (
                uuid, campaign_id, customer_id, customer_email,
                ordered_by_rep_id, order_source,
                total_items, total_products,
                ip_address, user_agent, device_type,
                status, created_at
            ) VALUES (
                :uuid, :campaign_id, :customer_id, :customer_email,
                :ordered_by_rep_id, :order_source,
                :total_items, :total_products,
                :ip_address, :user_agent, :device_type,
                'pending', NOW()
            )";

            $this->db->execute($queryOrder, [
                ":uuid" => $orderUuid,
                ":campaign_id" => $campaign["id"],
                ":customer_id" => $customerId,
                ":customer_email" => $customerEmail,
                ":ordered_by_rep_id" => $orderedByRepId,
                ":order_source" => $orderSource,
                ":total_items" => $totalItems,
                ":total_products" => $totalProducts,
                ":ip_address" => $ipAddress,
                ":user_agent" => $userAgent ? substr($userAgent, 0, 500) : null, // Limiter la taille
                ":device_type" => $deviceType,
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

            // 6. SPRINT 15 : Traitement selon le mode de la campagne
            // ========================================
            $orderProcessingMode = $campaign["order_processing_mode"] ?? "direct";

            if ($orderProcessingMode === "pending") {
                // Mode PENDING : pas de fichier TXT, statut validated
                $this->db->execute(
                    "UPDATE orders SET status = 'validated' WHERE id = :id",
                    [":id" => $orderId],
                );
            } else {
                // Mode DIRECT (défaut) : Générer le fichier TXT pour l'ERP
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
            }

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
            // ========================================
            // SPRINT 14 : Ajout infos rep pour copie email
            // SPRINT 15 : Ajout mode de traitement pour email différent
            // ========================================
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
                // SPRINT 14 : Infos rep pour copie email
                "is_rep_order" => $isRepOrder,
                "rep_email" => $customer["rep_email"] ?? null,
                "rep_name" => $customer["rep_name"] ?? null,
                // SPRINT 15 : Mode de traitement (pour email différent)
                "order_processing_mode" => $orderProcessingMode,
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

        return (int) $this->db->lastInsertId();
    }

    /**
     * Générer le fichier TXT pour l'ERP
     *
     * Format :
     * - Ligne I : Entête avec dates
     * - Ligne H : Header client/campagne
     * - Lignes D : Détail produits
     *
     * @param int $orderId ID de la commande
     * @param array $campaign Données campagne
     * @param string $customerNumber Numéro client
     * @param string $country Pays
     * @param array $items Lignes du panier
     * @return array ['path' => string, 'content' => string]
     * @created 17/11/2025
     * @modified 25/11/2025 - Utiliser formatCustomerNumberForFilename pour noms fichiers
     */
    private function generateOrderFile(
        int $orderId,
        array $campaign,
        string $customerNumber,
        string $country,
        array $items,
    ): array {
        // Format dates
        $orderDate = date("dmy");

        // Date de livraison
        if (($campaign["deferred_delivery"] ?? 0) == 1 && !empty($campaign["delivery_date"])) {
            $deliveryDate = date("dmy", strtotime($campaign["delivery_date"]));
        } else {
            $deliveryDate = $orderDate;
        }

        // Numéro client formaté (8 caractères)
        $customerFormatted = $this->formatCustomerNumber($customerNumber);

        // Type commande (V ou W)
        $orderType = $campaign["order_type"] ?? "W";

        // Nom campagne (20 caractères max, sans accents)
        $campaignName = $this->sanitizeForTxt($campaign["name"] ?? "CAMPAGNE", 20);

        // Contenu du fichier
        $content = "";

        // Ligne I : Entête
        $content .= "I00{$orderDate}{$deliveryDate}\r\n";

        // Ligne H : Header
        $content .= "H{$customerFormatted}{$orderType}{$campaignName}\r\n";

        // Lignes D : Détail produits
        foreach ($items as $item) {
            $productCode = str_pad($item["code"], 6, "0", STR_PAD_LEFT);
            $quantity = str_pad((string) $item["quantity"], 10, "0", STR_PAD_LEFT);
            $content .= "D{$productCode}{$quantity}\r\n";
        }

        // Déterminer le répertoire
        $baseDir = $_ENV["STORAGE_PATH"] ?? dirname(__DIR__, 2) . "/public";
        $countryDir = strtolower($country);
        $directory = "{$baseDir}/commande_{$countryDir}";

        // Créer le répertoire s'il n'existe pas
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Nom de fichier unique (utiliser format compatible Windows)
        $customerFilename = $this->formatCustomerNumberForFilename($customerNumber);
        $filename = "{$customerFilename}_{$orderId}_{$orderDate}.txt";
        $filepath = "{$directory}/{$filename}";

        // Écrire le fichier
        file_put_contents($filepath, $content);

        // Retourner le chemin relatif et le contenu
        return [
            'path' => "/commande_{$countryDir}/{$filename}",
            'content' => $content
        ];
    }

    /**
     * Nettoyer une chaîne pour fichier TXT (ASCII uniquement)
     *
     * @param string $text Texte à nettoyer
     * @param int $maxLength Longueur maximale
     * @return string Texte nettoyé
     * @created 17/11/2025
     */
    private function sanitizeForTxt(string $text, int $maxLength): string
    {
        // Remplacer les accents
        $text = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $text);

        // Garder uniquement alphanumérique et espaces
        $text = preg_replace("/[^A-Za-z0-9 ]/", "", $text);

        // Tronquer et uppercase
        return strtoupper(substr($text, 0, $maxLength));
    }

    /**
     * Formater le numéro client sur 8 caractères
     *
     * Règles définies :
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
     * @modified 05/01/2026 - Sprint 14 : Ajout envoi copie email au rep
     */
    public function orderConfirmation(string $uuid): void
    {
        // Envoyer l'email de confirmation (si pending_email existe)
        if (isset($_SESSION["pending_email"])) {
            $data = $_SESSION["pending_email"];
            unset($_SESSION["pending_email"]);

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
                    // SPRINT 14 : Info si commande rep
                    "is_rep_order" => $data["is_rep_order"] ?? false,
                    "rep_name" => $data["rep_name"] ?? null,
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

                // Email au client
                $emailSent = $mailchimpService->sendOrderConfirmation(
                    $data["customer_email"],
                    $orderData,
                    $data["language"],
                );

                if ($emailSent) {
                    error_log("Email confirmation envoyé à: {$data["customer_email"]} (Commande: {$data["order_number"]})");

                    // Mettre à jour email_sent dans la DB
                    $this->db->execute(
                        "UPDATE orders SET email_sent = 1, email_sent_at = NOW() WHERE id = :id",
                        [":id" => $data["order_id"]]
                    );
                } else {
                    error_log("Échec envoi email confirmation à: {$data["customer_email"]} (Commande: {$data["order_number"]})");
                }

                // ========================================
                // SPRINT 14 : Copie email au rep
                // ========================================
                if (!empty($data["rep_email"]) && $data["rep_email"] !== $data["customer_email"]) {
                    // Marquer comme copie rep
                    $orderData["is_rep_copy"] = true;

                    $repEmailSent = $mailchimpService->sendOrderConfirmation(
                        $data["rep_email"],
                        $orderData,
                        $data["language"],
                    );

                    if ($repEmailSent) {
                        error_log("Copie email confirmation envoyée au rep: {$data["rep_email"]} (Commande: {$data["order_number"]})");
                    } else {
                        error_log("Échec envoi copie email rep à: {$data["rep_email"]} (Commande: {$data["order_number"]})");
                    }
                }

            } catch (\Exception $e) {
                error_log("Erreur envoi email confirmation: " . $e->getMessage());
            }
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

    // ============================================================================
    // SPRINT 14 : MÉTHODES MODE REPRÉSENTANT
    // ============================================================================

    /**
     * Page de connexion pour les représentants
     * Affiche le formulaire avec switch langue et bouton SSO Microsoft
     *
     * Route : GET /c/{uuid}/rep
     *
     * @param string $uuid UUID de la campagne
     * @return void
     * @created 05/01/2026 - Sprint 14
     * @modified 06/01/2026 - Ajout page connexion avec switch langue
     */
    public function repAccess(string $uuid): void
    {
        try {
            // Récupérer la campagne
            $query = "SELECT * FROM campaigns WHERE uuid = :uuid AND is_active = 1";
            $campaign = $this->db->query($query, [":uuid" => $uuid]);

            if (empty($campaign)) {
                $this->renderAccessDenied("campaign_not_found", $uuid);
                return;
            }

            $campaign = $campaign[0];

            // Vérifier statut campagne
            $now = date('Y-m-d');
            if ($now < $campaign['start_date']) {
                $this->renderAccessDenied("upcoming", $uuid, $campaign);
                return;
            }
            if ($now > $campaign['end_date']) {
                $this->renderAccessDenied("ended", $uuid, $campaign);
                return;
            }

            // Vérifier si le rep est déjà connecté en session
            if ($this->isRepAuthenticated()) {
                // Rediriger vers la sélection de client
                header("Location: /stm/c/{$uuid}/rep/select-client");
                exit();
            }

            // Gestion du switch langue
            $requestedLang = $_GET['lang'] ?? null;
            if ($requestedLang && in_array($requestedLang, ['fr', 'nl'], true)) {
                Session::set('rep_language', $requestedLang);
                // Rediriger pour nettoyer l'URL
                header("Location: /stm/c/{$uuid}/rep");
                exit();
            }

            // Langue : session > défaut selon pays
            $lang = Session::get('rep_language');
            if (!$lang) {
                $lang = ($campaign['country'] === 'LU') ? 'fr' : 'fr'; // Défaut FR
                Session::set('rep_language', $lang);
            }

            // Récupérer erreur éventuelle
            $error = Session::get('error');
            Session::remove('error');

            // Afficher la page de connexion
            require __DIR__ . "/../Views/public/campaign/rep_access.php";

        } catch (\PDOException $e) {
            error_log("Erreur repAccess() : " . $e->getMessage());
            $this->renderAccessDenied("error", $uuid);
        }
    }

    /**
     * Déclencher la connexion SSO Microsoft pour les représentants
     *
     * Route : POST /c/{uuid}/rep/login
     *
     * @param string $uuid UUID de la campagne
     * @return void
     * @created 06/01/2026 - Sprint 14
     */
    public function repLogin(string $uuid): void
    {
        // Vérifier token CSRF
        if (!isset($_POST['_token']) || $_POST['_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            Session::set('error', 'Token de sécurité invalide.');
            header("Location: /stm/c/{$uuid}/rep");
            exit();
        }

        // Stocker la langue choisie
        $lang = $_POST['lang'] ?? 'fr';
        if (in_array($lang, ['fr', 'nl'], true)) {
            Session::set('rep_language', $lang);
        }

        // Stocker l'UUID de la campagne pour le callback SSO
        Session::set('rep_campaign_uuid', $uuid);

        // Rediriger vers l'authentification Microsoft SSO
        $appUrl = $this->env('APP_URL', 'https://dev.trendyfoodsblog.com/stm');
        $redirectUri = urlencode($appUrl . '/auth/microsoft/callback-rep');
        $authUrl = $this->buildMicrosoftAuthUrl($redirectUri);

        header("Location: {$authUrl}");
        exit();
    }

    /**
     * Callback Microsoft SSO pour les représentants
     *
     * Route : GET /auth/microsoft/callback-rep
     *
     * @return void
     * @created 05/01/2026 - Sprint 14
     */
    public function repMicrosoftCallback(): void
    {
        try {
            $code = $_GET['code'] ?? null;
            $error = $_GET['error'] ?? null;

            $uuid = Session::get('rep_campaign_uuid');

            if (!$uuid) {
                header("Location: /stm/admin/login");
                exit();
            }

            if ($error || !$code) {
                error_log("[Rep SSO] Erreur: " . ($error ?? 'No code'));
                Session::set('error', 'Échec de l\'authentification Microsoft.');
                header("Location: /stm/c/{$uuid}/rep");
                exit();
            }

            // Échanger le code contre un token
            $tokenData = $this->exchangeMicrosoftCode($code, 'callback-rep');

            if (!$tokenData || !isset($tokenData['access_token'])) {
                Session::set('error', 'Erreur lors de l\'authentification.');
                header("Location: /stm/c/{$uuid}/rep");
                exit();
            }

            // Récupérer les infos utilisateur
            $userInfo = $this->getMicrosoftUserInfo($tokenData['access_token']);

            if (!$userInfo || !isset($userInfo['id'])) {
                Session::set('error', 'Impossible de récupérer les informations utilisateur.');
                header("Location: /stm/c/{$uuid}/rep");
                exit();
            }

            // Vérifier que l'utilisateur existe dans notre base (SANS filtre is_active pour pouvoir détecter les comptes désactivés)
            $query = "SELECT * FROM users WHERE microsoft_id = :microsoft_id";
            $user = $this->db->query($query, [":microsoft_id" => $userInfo['id']]);

            if (empty($user)) {
                // Utilisateur non trouvé par microsoft_id, essayer par email
                $query = "SELECT * FROM users WHERE email = :email";
                $user = $this->db->query($query, [":email" => $userInfo['mail'] ?? $userInfo['userPrincipalName']]);
            }

            if (empty($user)) {
                // Aucun compte trouvé
                Session::set('error', 'Aucun compte n\'est associé à cet identifiant Microsoft. Veuillez contacter votre administrateur.');
                header("Location: /stm/c/{$uuid}/rep");
                exit();
            }

            $user = $user[0];

            // Vérifier si le compte est actif
            if (empty($user['is_active'])) {
                Session::set('error', 'Votre compte a été désactivé. Veuillez contacter votre administrateur.');
                header("Location: /stm/c/{$uuid}/rep");
                exit();
            }

            // Vérifier que c'est bien un rôle autorisé (rep, manager_reps, admin, superadmin)
            $allowedRoles = ['rep', 'manager_reps', 'admin', 'superadmin'];
            if (!in_array($user['role'], $allowedRoles)) {
                Session::set('error', 'Votre rôle ne permet pas d\'utiliser cette fonctionnalité.');
                header("Location: /stm/c/{$uuid}/rep");
                exit();
            }

            // Stocker les infos rep en session
            $repLanguage = Session::get('rep_language') ?? 'fr';
            Session::set('rep_session', [
                'rep_id' => $user['id'],
                'rep_name' => $user['name'],
                'rep_email' => $user['email'],
                'rep_role' => $user['role'],
                'rep_country' => $user['rep_country'],
                'rep_language' => $repLanguage,
                'campaign_uuid' => $uuid,
                'authenticated_at' => date('Y-m-d H:i:s')
            ]);

            // Cookie pour mémoriser le mode rep (survit à l'expiration de session)
            // Durée : 8 heures (journée de travail)
            setcookie('stm_rep_mode', $uuid, [
                'expires' => time() + (8 * 3600),
                'path' => '/stm/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            // Nettoyer
            Session::remove('rep_campaign_uuid');

            // Rediriger vers la sélection de client
            header("Location: /stm/c/{$uuid}/rep/select-client");
            exit();

        } catch (\Exception $e) {
            error_log("Erreur repMicrosoftCallback: " . $e->getMessage());
            $uuid = Session::get('rep_campaign_uuid') ?? '';
            Session::set('error', 'Une erreur est survenue lors de l\'authentification.');
            header("Location: /stm/c/{$uuid}/rep");
            exit();
        }
    }

    /**
     * Page de sélection du client par le rep
     *
     * Route : GET /c/{uuid}/rep/select-client
     *
     * @param string $uuid UUID de la campagne
     * @return void
     * @created 05/01/2026 - Sprint 14
     */
    public function repSelectClient(string $uuid): void
    {
        // Vérifier que le rep est authentifié
        if (!$this->isRepAuthenticated() || !$this->isRepOnCampaign($uuid)) {
            header("Location: /stm/c/{$uuid}/rep");
            exit();
        }

        try {
            // Récupérer la campagne
            $query = "SELECT * FROM campaigns WHERE uuid = :uuid AND is_active = 1";
            $campaign = $this->db->query($query, [":uuid" => $uuid]);

            if (empty($campaign)) {
                $this->renderAccessDenied("campaign_not_found", $uuid);
                return;
            }

            $campaign = $campaign[0];
            $rep = Session::get('rep_session');
            $error = Session::get('error');
            Session::remove('error');

            // Afficher la vue
            require __DIR__ . "/../Views/public/campaign/rep_select_client.php";

        } catch (\PDOException $e) {
            error_log("Erreur repSelectClient: " . $e->getMessage());
            $this->renderAccessDenied("error", $uuid);
        }
    }

    /**
     * Identifier le rep en tant que client
     *
     * Route : POST /c/{uuid}/rep/identify
     *
     * @param string $uuid UUID de la campagne
     * @return void
     * @created 05/01/2026 - Sprint 14
     */
    public function repIdentifyAs(string $uuid): void
    {
        // Vérifier que le rep est authentifié
        if (!$this->isRepAuthenticated() || !$this->isRepOnCampaign($uuid)) {
            header("Location: /stm/c/{$uuid}/rep");
            exit();
        }

        try {
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
            $language = $_POST["language"] ?? "fr";

            // Validation
            if (empty($customerNumber)) {
                Session::set("error", "Le numéro client est obligatoire.");
                header("Location: /stm/c/{$uuid}/rep/select-client");
                exit();
            }

            // Vérifier que le client existe dans la DB externe
            $externalDb = $this->getExternalDatabase();
            $customerData = $this->getCustomerFromExternal($externalDb, $customerNumber, $country);

            if (!$customerData) {
                Session::set("error", "Numéro client introuvable dans la base de données.");
                header("Location: /stm/c/{$uuid}/rep/select-client");
                exit();
            }

            // Vérifier les droits selon le mode de la campagne
            $hasAccess = $this->checkCustomerAccessForRep($campaign, $customerNumber, $country);

            if (!$hasAccess) {
                Session::set("error", "Ce client n'est pas autorisé pour cette campagne.");
                header("Location: /stm/c/{$uuid}/rep/select-client");
                exit();
            }

            // Vérifier l'éligibilité produits via API Trendy Foods
            $eligibilityCheck = $this->checkProductsEligibility($campaign["id"], $customerNumber, $country);

            if ($eligibilityCheck['api_error']) {
                Session::set("error", "Service temporairement indisponible. Veuillez réessayer.");
                header("Location: /stm/c/{$uuid}/rep/select-client");
                exit();
            }

            if (empty($eligibilityCheck['authorized_codes'])) {
                $this->renderAccessDenied("no_products_authorized", $uuid, $campaign);
                return;
            }

            // Vérifier les quotas disponibles
            $hasAvailableProducts = $this->checkAvailableProducts($campaign["id"], $customerNumber, $country);

            if (!$hasAvailableProducts) {
                $this->renderAccessDenied("quotas_reached", $uuid, $campaign);
                return;
            }

            // Récupérer les infos du rep
            $rep = Session::get('rep_session');

            // Créer la session client (avec flag rep)
            Session::set("public_customer", [
                "customer_number" => $customerNumber,
                "country" => $country,
                "company_name" => $customerData["company_name"],
                "campaign_uuid" => $uuid,
                "campaign_id" => $campaign["id"],
                "language" => $language,
                "logged_at" => date("Y-m-d H:i:s"),
                // Flags mode rep
                "is_rep_order" => true,
                "rep_id" => $rep['rep_id'],
                "rep_name" => $rep['rep_name'],
                "rep_email" => $rep['rep_email']
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
            error_log("Erreur repIdentifyAs: " . $e->getMessage());
            Session::set("error", "Une erreur est survenue.");
            header("Location: /stm/c/{$uuid}/rep/select-client");
            exit();
        }
    }

    /**
     * Déconnexion du rep
     *
     * Route : GET /c/{uuid}/rep/logout
     *
     * @param string $uuid UUID de la campagne
     * @return void
     * @created 05/01/2026 - Sprint 14
     * @modified 06/01/2026 - Suppression cookie stm_rep_mode
     */
    public function repLogout(string $uuid): void
    {
        Session::remove('rep_session');
        Session::remove('public_customer');
        Session::remove('cart');

        // Supprimer le cookie mode rep
        setcookie('stm_rep_mode', '', [
            'expires' => time() - 3600,
            'path' => '/stm/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        header("Location: /stm/c/{$uuid}");
        exit();
    }

    /**
     * Vérifier si un rep est authentifié
     *
     * @return bool
     * @created 05/01/2026 - Sprint 14
     */
    private function isRepAuthenticated(): bool
    {
        $repSession = Session::get('rep_session');
        return !empty($repSession) && isset($repSession['rep_id']);
    }

    /**
     * Vérifier si le rep est sur la bonne campagne
     *
     * @param string $uuid UUID de la campagne
     * @return bool
     * @created 05/01/2026 - Sprint 14
     */
    private function isRepOnCampaign(string $uuid): bool
    {
        $repSession = Session::get('rep_session');
        return !empty($repSession) && ($repSession['campaign_uuid'] ?? '') === $uuid;
    }

    /**
     * Vérifier si on doit rediriger vers la connexion rep (session expirée mais cookie présent)
     *
     * @param string $uuid UUID de la campagne
     * @return bool True si redirection nécessaire
     * @created 06/01/2026 - Sprint 14
     */
    private function shouldRedirectToRepLogin(string $uuid): bool
    {
        // Si le cookie mode rep existe pour cette campagne
        $repModeCookie = $_COOKIE['stm_rep_mode'] ?? null;

        if ($repModeCookie === $uuid) {
            // Et que la session rep n'existe plus (expirée)
            $repSession = Session::get('rep_session');
            $publicCustomer = Session::get('public_customer');

            // Ni session rep, ni session client = session expirée
            if (empty($repSession) && empty($publicCustomer)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifier les droits d'accès client pour le mode rep
     * Même logique que checkCustomerAccess mais sans le mode protected (password)
     *
     * @param array $campaign Données de la campagne
     * @param string $customerNumber Numéro client
     * @param string $country Pays
     * @return bool
     * @created 05/01/2026 - Sprint 14
     */
    private function checkCustomerAccessForRep(array $campaign, string $customerNumber, string $country): bool
    {
        // Mode AUTOMATIC ou PROTECTED : tous les clients de la DB externe ont accès
        if ($campaign["customer_assignment_mode"] === "automatic" || $campaign["customer_assignment_mode"] === "protected") {
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

        return false;
    }

    /**
     * Récupérer une variable d'environnement de manière robuste
     *
     * @param string $key Nom de la variable
     * @param string $default Valeur par défaut
     * @return string
     * @created 05/01/2026 - Sprint 14 fix
     */
    private function env(string $key, string $default = ''): string
    {
        // Essayer getenv() d'abord (le plus fiable)
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }

        // Fallback sur $_ENV
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }

        // Fallback sur $_SERVER (certains hébergeurs)
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }

        return $default;
    }

    /**
     * Construire l'URL d'authentification Microsoft
     *
     * @param string $redirectUri URI de callback encodé
     * @return string URL d'authentification
     * @created 05/01/2026 - Sprint 14
     */
    private function buildMicrosoftAuthUrl(string $redirectUri): string
    {
        $tenantId = $this->env('MICROSOFT_TENANT_ID');
        $clientId = $this->env('MICROSOFT_CLIENT_ID');

        // Debug si vide
        if (empty($tenantId) || empty($clientId)) {
            error_log("[Rep SSO] ERREUR: Variables Microsoft manquantes - TENANT_ID: " . (empty($tenantId) ? 'VIDE' : 'OK') . ", CLIENT_ID: " . (empty($clientId) ? 'VIDE' : 'OK'));
        }

        $params = [
            'client_id' => $clientId,
            'response_type' => 'code',
            'redirect_uri' => urldecode($redirectUri),
            'scope' => 'openid profile email User.Read',
            'response_mode' => 'query',
            'state' => bin2hex(random_bytes(16)),
            'prompt' => 'select_account'  // Force la sélection de compte même si déjà connecté
        ];

        return "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/authorize?" . http_build_query($params);
    }

    /**
     * Échanger le code Microsoft contre un token
     *
     * @param string $code Code d'autorisation
     * @param string $callbackType Type de callback ('callback' ou 'callback-rep')
     * @return array|null Données du token ou null
     * @created 05/01/2026 - Sprint 14
     */
    private function exchangeMicrosoftCode(string $code, string $callbackType = 'callback'): ?array
    {
        $tenantId = $this->env('MICROSOFT_TENANT_ID');
        $clientId = $this->env('MICROSOFT_CLIENT_ID');
        $clientSecret = $this->env('MICROSOFT_CLIENT_SECRET');
        $appUrl = $this->env('APP_URL', 'https://dev.trendyfoodsblog.com/stm');
        $redirectUri = $appUrl . '/auth/microsoft/' . $callbackType;

        $url = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token";

        $data = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
            'scope' => 'openid profile email User.Read'
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Récupérer les infos utilisateur depuis Microsoft Graph
     *
     * @param string $accessToken Token d'accès
     * @return array|null Infos utilisateur ou null
     * @created 05/01/2026 - Sprint 14
     */
    private function getMicrosoftUserInfo(string $accessToken): ?array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://graph.microsoft.com/v1.0/me',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ]
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }
}