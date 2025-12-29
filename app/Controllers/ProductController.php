<?php
/**
 * ProductController - Gestion des Promotions
 *
 * @created 11/11/2025
 * @modified 17/11/2025 - Ajout vérification hasOrders() avant suppression
 * @modified 18/12/2025 - Filtrage par campagnes accessibles selon le rôle
 * @modified 19/12/2025 - Filtre par défaut sur campagnes actives (campaign_status)
 * @modified 23/12/2025 - Conservation des filtres après suppression
 * @modified 23/12/2025 - Ajout stats de vente dans show() avec getProductSalesStats()
 */

namespace App\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Campaign;
use App\Helpers\StatsAccessHelper;
use Core\Session;

class ProductController
{
    private Product $productModel;

    public function __construct()
    {
        $this->productModel = new Product();
    }

    /**
     * Afficher la liste des Promotions avec filtres
     */
    public function index(): void
    {
        // Récupérer les campagnes accessibles selon le rôle
        $accessibleCampaignIds = StatsAccessHelper::getAccessibleCampaignIds();

        // Récupérer les filtres
        $filters = [
            "search" => $_GET["search"] ?? "",
            "category" => $_GET["category"] ?? "",
            "campaign_id" => $_GET["campaign_id"] ?? "",
            "country" => $_GET["country"] ?? "",
            // Par défaut, afficher uniquement les promos des campagnes actives
            "campaign_status" => $_GET["campaign_status"] ?? "active",
        ];

        // Ajouter le filtre par campagnes accessibles
        if ($accessibleCampaignIds !== null) {
            $filters["campaign_ids"] = $accessibleCampaignIds;
        }

        // Récupérer les Promotions filtrées
        $products = $this->productModel->getAll($filters);

        // Récupérer les statistiques (filtrées par rôle ET par statut campagne)
        $stats = $this->productModel->getStats($accessibleCampaignIds, $filters["campaign_status"]);

        // Récupérer les catégories pour le filtre
        $categoryModel = new Category();
        $categories = $categoryModel->getAll();

        // Récupérer les campagnes pour le filtre (filtrées par rôle) avec statut calculé
        $campaignModel = new Campaign();
        $allCampaigns = $campaignModel->getAll();
        if ($accessibleCampaignIds !== null) {
            $allCampaigns = array_filter($allCampaigns, function($c) use ($accessibleCampaignIds) {
                return in_array($c['id'], $accessibleCampaignIds);
            });
            $allCampaigns = array_values($allCampaigns);
        }

        // Calculer le statut de chaque campagne pour le filtrage dynamique
        $today = date('Y-m-d');
        $campaigns = [];
        foreach ($allCampaigns as $camp) {
            $campStatus = 'inactive';
            if ($camp['is_active']) {
                if ($today < $camp['start_date']) {
                    $campStatus = 'upcoming';
                } elseif ($today > $camp['end_date']) {
                    $campStatus = 'ended';
                } else {
                    $campStatus = 'active';
                }
            }
            $camp['computed_status'] = $campStatus;
            $campaigns[] = $camp;
        }

        // Créer le mapping catégorie -> campagnes (pour filtrage dynamique des catégories)
        $categoryToCampaigns = [];
        $productFilters = [];
        if ($accessibleCampaignIds !== null) {
            $productFilters['campaign_ids'] = $accessibleCampaignIds;
        }
        $allProducts = $this->productModel->getAll($productFilters);
        foreach ($allProducts as $prod) {
            $catId = (int)$prod['category_id'];
            $campId = (int)$prod['campaign_id'];
            if ($catId && $campId) {
                $catKey = strval($catId); // Clé string pour JSON
                if (!isset($categoryToCampaigns[$catKey])) {
                    $categoryToCampaigns[$catKey] = [];
                }
                if (!in_array($campId, $categoryToCampaigns[$catKey])) {
                    $categoryToCampaigns[$catKey][] = $campId;
                }
            }
        }

        // Charger la vue
        require_once __DIR__ . "/../Views/admin/products/index.php";
    }

    /**
     * Afficher le formulaire de création
     *
     * @modified 12/11/2025 15:45 - FIX : Utilisation categories
     * @modified 18/12/2025 - Filtrage campagnes par rôle
     */
    public function create(): void
    {
        // Récupérer les catégories depuis categories
        $db = \Core\Database::getInstance();
        $categories = $db->query("SELECT * FROM categories ORDER BY display_order ASC");

        // Récupérer les campagnes accessibles selon le rôle
        $accessibleCampaignIds = StatsAccessHelper::getAccessibleCampaignIds();

        // Récupérer les campagnes ACTIVES OU FUTURES (pas les passées)
        $campaignModel = new Campaign();

        if (method_exists($campaignModel, "getActiveOrFuture")) {
            $allCampaigns = $campaignModel->getActiveOrFuture();
        } elseif (method_exists($campaignModel, "getActive")) {
            $allCampaigns = $campaignModel->getActive();
        } else {
            $allCampaigns = $campaignModel->getAll();
        }

        // Filtrer par campagnes accessibles
        if ($accessibleCampaignIds !== null) {
            $campaigns = array_filter($allCampaigns, function($c) use ($accessibleCampaignIds) {
                return in_array($c['id'], $accessibleCampaignIds);
            });
            $campaigns = array_values($campaigns); // Réindexer
        } else {
            $campaigns = $allCampaigns;
        }

        // Debug: vérifier si on a des campagnes
        error_log("ProductController::create() - Nombre de campagnes: " . count($campaigns));

        // Charger la vue
        require_once __DIR__ . "/../Views/admin/products/create.php";
    }

    /**
     * Enregistrer un nouveau Promotion
     *
     * @modified 12/11/2025 17:30 - Ajout quotas
     */
    public function store(): void
    {
        // Validation CSRF
        if (!$this->validateCSRF()) {
            Session::setFlash("error", "Token de sécurité invalide");
            header("Location: /stm/admin/products/create");
            exit();
        }

        // Récupérer les données du formulaire
        $data = [
            "campaign_id" => $_POST["campaign_id"] ?? null,
            "category_id" => $_POST["category_id"] ?? null,
            "product_code" => trim($_POST["product_code"] ?? ""),
            "name_fr" => trim($_POST["name_fr"] ?? ""),
            "name_nl" => trim($_POST["name_nl"] ?? ""),
            "description_fr" => trim($_POST["description_fr"] ?? ""),
            "description_nl" => trim($_POST["description_nl"] ?? ""),
            "display_order" => (int) ($_POST["display_order"] ?? 0),
            "max_total" => !empty($_POST["max_total"]) ? (int) $_POST["max_total"] : null,
            "max_per_customer" => !empty($_POST["max_per_customer"]) ? (int) $_POST["max_per_customer"] : null,
            "is_active" => isset($_POST["is_active"]) ? 1 : 0,
        ];

        // Validation
        $errors = $this->productModel->validate($data);

        if (!empty($errors)) {
            Session::set("errors", $errors);
            Session::set("old", $data);
            header("Location: /stm/admin/products/create");
            exit();
        }

        // Upload image FR (OBLIGATOIRE)
        try {
            if (!empty($_FILES["image_fr"]["tmp_name"])) {
                $data["image_fr"] = $this->handleImageUpload($_FILES["image_fr"], "fr");
            } else {
                Session::setFlash("error", 'L\'image française est obligatoire');
                Session::set("old", $data);
                header("Location: /stm/admin/products/create");
                exit();
            }
        } catch (\Exception $e) {
            Session::setFlash("error", "Erreur image FR : " . $e->getMessage());
            Session::set("old", $data);
            header("Location: /stm/admin/products/create");
            exit();
        }

        // Upload image NL (OPTIONNEL - sinon copier FR)
        try {
            if (!empty($_FILES["image_nl"]["tmp_name"])) {
                $data["image_nl"] = $this->handleImageUpload($_FILES["image_nl"], "nl");
            } else {
                // Copier l'image FR vers NL
                $data["image_nl"] = $data["image_fr"];
            }
        } catch (\Exception $e) {
            // Si erreur, utiliser l'image FR
            $data["image_nl"] = $data["image_fr"];
        }

        // Créer le Promotion
        try {
            $productId = $this->productModel->create($data);

            if ($productId) {
                Session::setFlash("success", "Promotion créée avec succès");
                header("Location: /stm/admin/products/" . $productId);
            } else {
                Session::setFlash("error", "Erreur lors de la création de la Promotion");
                Session::set("old", $data);
                header("Location: /stm/admin/products/create");
            }
        } catch (\Exception $e) {
            Session::set("old", $data);
            header("Location: /stm/admin/products/create");
        }
        exit();
    }

    /**
     * Afficher les détails d'un Promotion
     * @modified 18/12/2025 - Vérification accès campagne
     * @modified 23/12/2025 - Ajout statistiques de vente filtrées par rôle
     */
    public function show(int $id): void
    {
        // ✅ CORRECTIF : Utiliser findById() au lieu de find()
        $product = $this->productModel->findById($id);

        if (!$product) {
            Session::setFlash("error", "Promotion non trouvée");
            header("Location: /stm/admin/products");
            exit();
        }

        // Vérifier l'accès à la campagne du produit
        if (!$this->canAccessProduct($product)) {
            Session::setFlash("error", "Vous n'avez pas accès à cette promotion");
            header("Location: /stm/admin/products");
            exit();
        }

        // Récupérer les stats de vente filtrées par rôle
        $productStats = $this->getProductSalesStats($id);

        // Charger la vue
        require_once __DIR__ . "/../Views/admin/products/show.php";
    }

    /**
     * Récupérer les statistiques de vente d'un produit
     * Filtrées selon le rôle de l'utilisateur connecté
     *
     * @param int $productId
     * @return array
     * @created 2025/12/23
     */
    private function getProductSalesStats(int $productId): array
    {
        $db = \Core\Database::getInstance();

        // Récupérer les clients accessibles selon le rôle
        $accessibleCustomerNumbers = StatsAccessHelper::getAccessibleCustomerNumbersOnly();

        // Construire le filtre clients
        $customerFilter = "";
        $params = [':product_id' => $productId];

        if ($accessibleCustomerNumbers !== null) {
            if (empty($accessibleCustomerNumbers)) {
                // Aucun client accessible = pas de stats
                return [
                    'total_sold' => 0,
                    'orders_count' => 0,
                    'customers_count' => 0,
                    'top_customers' => [],
                    'top_reps' => []
                ];
            }
            $placeholders = [];
            foreach ($accessibleCustomerNumbers as $i => $num) {
                $key = ":cust_{$i}";
                $placeholders[] = $key;
                $params[$key] = $num;
            }
            $customerFilter = "AND cu.customer_number IN (" . implode(",", $placeholders) . ")";
        }

        // Stats globales
        $statsQuery = "
            SELECT
                COALESCE(SUM(ol.quantity), 0) as total_sold,
                COUNT(DISTINCT o.id) as orders_count,
                COUNT(DISTINCT cu.customer_number) as customers_count
            FROM order_lines ol
            INNER JOIN orders o ON ol.order_id = o.id
            INNER JOIN customers cu ON o.customer_id = cu.id
            WHERE ol.product_id = :product_id
            AND o.status = 'validated'
            {$customerFilter}
        ";

        $statsResult = $db->query($statsQuery, $params);
        $stats = $statsResult[0] ?? ['total_sold' => 0, 'orders_count' => 0, 'customers_count' => 0];

        // Top 5 clients
        $topCustomersParams = [':product_id' => $productId];
        $topCustomersFilter = "";
        if ($accessibleCustomerNumbers !== null) {
            $placeholders = [];
            foreach ($accessibleCustomerNumbers as $i => $num) {
                $key = ":topc_{$i}";
                $placeholders[] = $key;
                $topCustomersParams[$key] = $num;
            }
            $topCustomersFilter = "AND cu.customer_number IN (" . implode(",", $placeholders) . ")";
        }

        $topCustomersQuery = "
            SELECT
                cu.customer_number,
                cu.company_name,
                cu.country,
                SUM(ol.quantity) as total_quantity,
                COUNT(DISTINCT o.id) as orders_count
            FROM order_lines ol
            INNER JOIN orders o ON ol.order_id = o.id
            INNER JOIN customers cu ON o.customer_id = cu.id
            WHERE ol.product_id = :product_id
            AND o.status = 'validated'
            {$topCustomersFilter}
            GROUP BY cu.id, cu.customer_number, cu.company_name, cu.country
            ORDER BY total_quantity DESC
            LIMIT 5
        ";

        $topCustomers = $db->query($topCustomersQuery, $topCustomersParams);

        // Top 5 représentants (avec enrichissement depuis DB externe)
        $topReps = $this->getTopRepsForProduct($productId, $accessibleCustomerNumbers);

        return [
            'total_sold' => (int)$stats['total_sold'],
            'orders_count' => (int)$stats['orders_count'],
            'customers_count' => (int)$stats['customers_count'],
            'top_customers' => $topCustomers,
            'top_reps' => $topReps
        ];
    }

    /**
     * Top représentants pour un produit
     *
     * @param int $productId
     * @param array|null $accessibleCustomerNumbers
     * @return array
     */
    private function getTopRepsForProduct(int $productId, ?array $accessibleCustomerNumbers): array
    {
        $db = \Core\Database::getInstance();

        // Construire le filtre clients
        $customerFilter = "";
        $params = [':product_id' => $productId];

        if ($accessibleCustomerNumbers !== null) {
            if (empty($accessibleCustomerNumbers)) {
                return [];
            }
            $placeholders = [];
            foreach ($accessibleCustomerNumbers as $i => $num) {
                $key = ":rep_{$i}";
                $placeholders[] = $key;
                $params[$key] = $num;
            }
            $customerFilter = "AND cu.customer_number IN (" . implode(",", $placeholders) . ")";
        }

        // Récupérer les ventes groupées par customer_number + country
        $query = "
            SELECT
                cu.customer_number,
                cu.country,
                SUM(ol.quantity) as total_quantity,
                COUNT(DISTINCT o.id) as orders_count,
                COUNT(DISTINCT cu.id) as customers_count
            FROM order_lines ol
            INNER JOIN orders o ON ol.order_id = o.id
            INNER JOIN customers cu ON o.customer_id = cu.id
            WHERE ol.product_id = :product_id
            AND o.status = 'validated'
            {$customerFilter}
            GROUP BY cu.customer_number, cu.country
        ";

        $salesByCustomer = $db->query($query, $params);

        if (empty($salesByCustomer)) {
            return [];
        }

        // Séparer par pays
        $customersBE = [];
        $customersLU = [];
        foreach ($salesByCustomer as $row) {
            if ($row['country'] === 'BE') {
                $customersBE[$row['customer_number']] = $row;
            } else {
                $customersLU[$row['customer_number']] = $row;
            }
        }

        // Récupérer les codes rep depuis la base externe
        $repStats = [];

        try {
            $extDb = \Core\ExternalDatabase::getInstance();

            // BE
            if (!empty($customersBE)) {
                $placeholders = implode(",", array_fill(0, count($customersBE), "?"));
                $beData = $extDb->query(
                    "SELECT cll.CLL_NCLIXX as customer_number, cll.IDE_REP as rep_id,
                            CONCAT(r.REP_PRENOM, ' ', r.REP_NOM) as rep_name
                     FROM BE_CLL cll
                     LEFT JOIN BE_REP r ON cll.IDE_REP = r.IDE_REP
                     WHERE cll.CLL_NCLIXX IN ({$placeholders})",
                    array_keys($customersBE)
                );

                foreach ($beData as $row) {
                    $custNum = $row['customer_number'];
                    $repId = $row['rep_id'] ?? 'N/A';
                    $repName = trim($row['rep_name'] ?? '');
                    if (empty($repName)) $repName = $repId;

                    if (!isset($repStats[$repId])) {
                        $repStats[$repId] = [
                            'rep_id' => $repId,
                            'rep_name' => $repName,
                            'total_quantity' => 0,
                            'orders_count' => 0,
                            'customers_count' => 0
                        ];
                    }

                    if (isset($customersBE[$custNum])) {
                        $repStats[$repId]['total_quantity'] += (int)$customersBE[$custNum]['total_quantity'];
                        $repStats[$repId]['orders_count'] += (int)$customersBE[$custNum]['orders_count'];
                        $repStats[$repId]['customers_count']++;
                    }
                }
            }

            // LU
            if (!empty($customersLU)) {
                $placeholders = implode(",", array_fill(0, count($customersLU), "?"));
                $luData = $extDb->query(
                    "SELECT cll.CLL_NCLIXX as customer_number, cll.IDE_REP as rep_id,
                            CONCAT(r.REP_PRENOM, ' ', r.REP_NOM) as rep_name
                     FROM LU_CLL cll
                     LEFT JOIN LU_REP r ON cll.IDE_REP = r.IDE_REP
                     WHERE cll.CLL_NCLIXX IN ({$placeholders})",
                    array_keys($customersLU)
                );

                foreach ($luData as $row) {
                    $custNum = $row['customer_number'];
                    $repId = $row['rep_id'] ?? 'N/A';
                    $repName = trim($row['rep_name'] ?? '');
                    if (empty($repName)) $repName = $repId;

                    if (!isset($repStats[$repId])) {
                        $repStats[$repId] = [
                            'rep_id' => $repId,
                            'rep_name' => $repName,
                            'total_quantity' => 0,
                            'orders_count' => 0,
                            'customers_count' => 0
                        ];
                    }

                    if (isset($customersLU[$custNum])) {
                        $repStats[$repId]['total_quantity'] += (int)$customersLU[$custNum]['total_quantity'];
                        $repStats[$repId]['orders_count'] += (int)$customersLU[$custNum]['orders_count'];
                        $repStats[$repId]['customers_count']++;
                    }
                }
            }
        } catch (\Exception $e) {
            error_log("getTopRepsForProduct error: " . $e->getMessage());
        }

        // Trier par quantité et limiter à 5
        usort($repStats, function($a, $b) {
            return $b['total_quantity'] - $a['total_quantity'];
        });

        return array_slice($repStats, 0, 5);
    }

    /**
     * Afficher le formulaire d'édition
     * @modified 18/12/2025 - Vérification accès + filtrage campagnes
     */
    public function edit(int $id): void
    {
        // ✅ CORRECTIF : Utiliser findById() au lieu de find()
        $product = $this->productModel->findById($id);

        if (!$product) {
            Session::setFlash("error", "Promotion non trouvée");
            header("Location: /stm/admin/products");
            exit();
        }

        // Vérifier l'accès à la campagne du produit
        if (!$this->canAccessProduct($product)) {
            Session::setFlash("error", "Vous n'avez pas accès à cette promotion");
            header("Location: /stm/admin/products");
            exit();
        }

        // Récupérer les catégories
        $categoryModel = new Category();
        $categories = $categoryModel->getAll();

        // Récupérer les campagnes accessibles selon le rôle
        $accessibleCampaignIds = StatsAccessHelper::getAccessibleCampaignIds();

        // Récupérer les campagnes ACTIVES OU FUTURES (pas les passées)
        $campaignModel = new Campaign();

        if (method_exists($campaignModel, "getActiveOrFuture")) {
            $allCampaigns = $campaignModel->getActiveOrFuture();
        } elseif (method_exists($campaignModel, "getActive")) {
            $allCampaigns = $campaignModel->getActive();
        } else {
            $allCampaigns = $campaignModel->getAll();
        }

        // Filtrer par campagnes accessibles
        if ($accessibleCampaignIds !== null) {
            $campaigns = array_filter($allCampaigns, function($c) use ($accessibleCampaignIds) {
                return in_array($c['id'], $accessibleCampaignIds);
            });
            $campaigns = array_values($campaigns);
        } else {
            $campaigns = $allCampaigns;
        }

        // Debug
        error_log("ProductController::edit() - Nombre de campagnes: " . count($campaigns));

        // Charger la vue
        require_once __DIR__ . "/../Views/admin/products/edit.php";
    }

    /**
     * Mettre à jour un Promotion
     *
     * @modified 12/11/2025 17:30 - Ajout quotas
     * @modified 18/12/2025 - Vérification accès campagne
     */
    public function update(int $id): void
    {
        // Validation CSRF
        if (!$this->validateCSRF()) {
            Session::setFlash("error", "Token de sécurité invalide");
            header("Location: /stm/admin/products/" . $id . "/edit");
            exit();
        }

        // ✅ CORRECTIF : Utiliser findById() au lieu de find()
        // Récupérer le Promotion existant
        $product = $this->productModel->findById($id);

        if (!$product) {
            Session::setFlash("error", "Promotion non trouvée");
            header("Location: /stm/admin/products");
            exit();
        }

        // Vérifier l'accès à la campagne du produit
        if (!$this->canAccessProduct($product)) {
            Session::setFlash("error", "Vous n'avez pas accès à cette promotion");
            header("Location: /stm/admin/products");
            exit();
        }

        // Récupérer les données
        $data = [
            "id" => $id, // ✅ AJOUT : Nécessaire pour la validation (vérification unicité code produit)
            "campaign_id" => $_POST["campaign_id"] ?? null,
            "category_id" => $_POST["category_id"] ?? null,
            "product_code" => trim($_POST["product_code"] ?? ""),
            "name_fr" => trim($_POST["name_fr"] ?? ""),
            "name_nl" => trim($_POST["name_nl"] ?? ""),
            "description_fr" => trim($_POST["description_fr"] ?? ""),
            "description_nl" => trim($_POST["description_nl"] ?? ""),
            "display_order" => (int) ($_POST["display_order"] ?? 0),
            "max_total" => !empty($_POST["max_total"]) ? (int) $_POST["max_total"] : null,
            "max_per_customer" => !empty($_POST["max_per_customer"]) ? (int) $_POST["max_per_customer"] : null,
            "is_active" => isset($_POST["is_active"]) ? 1 : 0,
            "image_fr" => $product["image_fr"], // Garder ancienne par défaut
            "image_nl" => $product["image_nl"],
        ];

        // Validation
        $errors = $this->productModel->validate($data);

        if (!empty($errors)) {
            Session::set("errors", $errors);
            Session::set("old", $data);
            header("Location: /stm/admin/products/" . $id . "/edit");
            exit();
        }

        // Upload nouvelle image FR si fournie
        if (!empty($_FILES["image_fr"]["tmp_name"])) {
            try {
                // Supprimer ancienne image FR
                $this->deleteImage($product["image_fr"]);

                // Upload nouvelle
                $data["image_fr"] = $this->handleImageUpload($_FILES["image_fr"], "fr");
            } catch (\Exception $e) {
                Session::setFlash("error", "Erreur image FR : " . $e->getMessage());
            }
        }

        // Upload nouvelle image NL si fournie, sinon copier FR
        if (!empty($_FILES["image_nl"]["tmp_name"])) {
            try {
                // Supprimer ancienne image NL (si différente de FR)
                if ($product["image_nl"] !== $product["image_fr"]) {
                    $this->deleteImage($product["image_nl"]);
                }

                // Upload nouvelle
                $data["image_nl"] = $this->handleImageUpload($_FILES["image_nl"], "nl");
            } catch (\Exception $e) {
                // Utiliser FR en cas d'erreur
                $data["image_nl"] = $data["image_fr"];
            }
        } else {
            // Si pas de nouvel upload NL, copier FR
            $data["image_nl"] = $data["image_fr"];
        }

        // Mettre à jour
        if ($this->productModel->update($id, $data)) {
            Session::setFlash("success", "Promotion modifiée avec succès");
            header("Location: /stm/admin/products/" . $id);
        } else {
            Session::setFlash("error", "Erreur lors de la modification");
            header("Location: /stm/admin/products/" . $id . "/edit");
        }
        exit();
    }

    /**
     * Supprimer un Promotion
     *
     * @modified 17/11/2025 - Ajout vérification hasOrders() avant suppression
     * @modified 18/12/2025 - Vérification accès campagne
     * @modified 23/12/2025 - Conservation des filtres après suppression
     */
    public function destroy(int $id): void
    {
        // Récupérer les filtres pour la redirection
        $redirectFilters = $_POST['redirect_filters'] ?? '';
        $redirectUrl = '/stm/admin/products' . (!empty($redirectFilters) ? '?' . $redirectFilters : '');

        // Validation CSRF
        if (!$this->validateCSRF()) {
            Session::setFlash("error", "Token de sécurité invalide");
            header("Location: " . $redirectUrl);
            exit();
        }

        // ✅ CORRECTIF : Utiliser findById() au lieu de find()
        $product = $this->productModel->findById($id);

        if (!$product) {
            Session::setFlash("error", "Promotion non trouvée");
            header("Location: " . $redirectUrl);
            exit();
        }

        // Vérifier l'accès à la campagne du produit
        if (!$this->canAccessProduct($product)) {
            Session::setFlash("error", "Vous n'avez pas accès à cette promotion");
            header("Location: " . $redirectUrl);
            exit();
        }

        // ✅ NOUVEAU : Vérifier si la promotion a des commandes associées
        if ($this->productModel->hasOrders($id)) {
            Session::setFlash(
                "error",
                "Impossible de supprimer cette promotion car elle fait partie de commandes existantes. Pour la retirer du catalogue, désactivez-la plutôt.",
            );
            header("Location: " . $redirectUrl);
            exit();
        }

        // Supprimer les images
        $this->deleteImage($product["image_fr"]);
        if ($product["image_nl"] !== $product["image_fr"]) {
            $this->deleteImage($product["image_nl"]);
        }

        // Supprimer le Promotion
        if ($this->productModel->delete($id)) {
            Session::setFlash("success", "Promotion supprimée avec succès (incluant les images)");
        } else {
            Session::setFlash("error", "Erreur lors de la suppression");
        }

        header("Location: " . $redirectUrl);
        exit();
    }

    /**
     * Gérer l'upload d'une image avec NOM ALÉATOIRE
     *
     * @param array $file Fichier $_FILES
     * @param string $lang 'fr' ou 'nl'
     * @return string Chemin relatif de l'image
     * @throws \Exception
     */
    private function handleImageUpload(array $file, string $lang): string
    {
        // Vérifier si fichier uploadé
        if (empty($file["tmp_name"]) || $file["error"] !== UPLOAD_ERR_OK) {
            throw new \Exception("Aucun fichier uploadé ou erreur d'upload");
        }

        // Validation type MIME
        $allowedTypes = ["image/jpeg", "image/jpg", "image/png", "image/webp"];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file["tmp_name"]);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            throw new \Exception("Type de fichier non autorisé. Formats acceptés : JPG, PNG, WEBP");
        }

        // Validation taille (5MB max)
        if ($file["size"] > 5 * 1024 * 1024) {
            throw new \Exception("L'image ne doit pas dépasser 5MB");
        }

        // Générer nom ALÉATOIRE (sécurité)
        $extension = pathinfo($file["name"], PATHINFO_EXTENSION);
        $randomName = "product_" . $lang . "_" . uniqid() . "_" . time() . "." . $extension;

        // Chemin de destination
        $uploadDir = __DIR__ . "/../../public/uploads/products/";
        $filePath = $uploadDir . $randomName;

        // Créer dossier si inexistant
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Déplacer le fichier
        if (!move_uploaded_file($file["tmp_name"], $filePath)) {
            throw new \Exception("Erreur lors de l'enregistrement de l'image");
        }

        // Retourner chemin relatif
        return "/stm/uploads/products/" . $randomName;
    }

    /**
     * Supprimer une image du serveur
     *
     * @param string|null $imagePath Chemin relatif de l'image
     * @return bool
     */
    private function deleteImage(?string $imagePath): bool
    {
        if (empty($imagePath)) {
            return false;
        }

        $fullPath = __DIR__ . "/../../public" . $imagePath;

        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }

        return false;
    }

    /**
     * Valider le token CSRF
     *
     * @return bool
     */
    private function validateCSRF(): bool
    {
        $token = $_POST["_token"] ?? "";
        return $token === Session::get("csrf_token");
    }

    /**
     * Vérifier si l'utilisateur peut accéder à un produit
     * basé sur les campagnes auxquelles il a accès
     *
     * @param array $product Données du produit
     * @return bool
     */
    private function canAccessProduct(array $product): bool
    {
        $accessibleCampaignIds = StatsAccessHelper::getAccessibleCampaignIds();

        // null = accès à tout
        if ($accessibleCampaignIds === null) {
            return true;
        }

        // Vérifier si la campagne du produit est dans la liste des campagnes accessibles
        return in_array($product['campaign_id'], $accessibleCampaignIds);
    }
}