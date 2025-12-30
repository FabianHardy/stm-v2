<?php
/**
 * Controller Stats - STM v2
 *
 * Gestion des pages de statistiques admin
 *
 * @package STM
 * @created 2025/11/25
 * @modified 2025/12/09 - Ajout stats fournisseurs dans campaigns()
 * @modified 2025/12/22 - Correction export Excel : quantités par produit (getClientProductQuantities)
 * @modified 2025/12/22 - Système de cache intelligent pour exports Excel
 * @modified 2025/12/23 - Correction getExportAccessScope() pour support impersonation
 * @modified 2025/12/23 - Correction graphiques (getDailyEvolution, getCategoryStatsForCampaign) pour filtre clients
 * @modified 2025/12/23 - Ajout API getCustomerOrdersApi() pour modal détail commandes client
 */

namespace App\Controllers;

use App\Models\Stats;
use App\Models\Campaign;
use Core\Session;
use App\Helpers\StatsAccessHelper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class StatsController
{
    private Stats $statsModel;
    private Campaign $campaignModel;

    /**
     * Constructeur
     */
    public function __construct()
    {
        // Vérifier l'authentification (utilise user_id comme les autres controllers)
        if (!Session::get("user_id")) {
            header("Location: /stm/admin/login");
            exit();
        }

        $this->statsModel = new Stats();
        $this->campaignModel = new Campaign();
    }

    // =========================================================================
    // MÉTHODES DE FILTRAGE HIÉRARCHIQUE
    // =========================================================================

    /**
     * Récupère les IDs des campagnes accessibles selon le rôle de l'utilisateur
     * Délègue au helper centralisé
     *
     * @return array|null Liste des IDs ou null si accès à tout
     * @created 2025/12/16
     */
    private function getAccessibleCampaignIds(): ?array
    {
        return StatsAccessHelper::getAccessibleCampaignIds();
    }

    /**
     * Récupère les IDs des représentants gérés par le manager connecté
     * Délègue au helper centralisé
     *
     * @return array Liste des [rep_id, rep_country]
     * @created 2025/12/16
     */
    private function getManagedRepIds(): array
    {
        return StatsAccessHelper::getManagedRepIds();
    }

    /**
     * Filtre une liste de campagnes selon l'accès de l'utilisateur
     * Délègue au helper centralisé
     *
     * @param array $campaigns Liste des campagnes
     * @return array Liste filtrée
     * @created 2025/12/16
     */
    private function filterCampaignsList(array $campaigns): array
    {
        return StatsAccessHelper::filterCampaignsList($campaigns);
    }

    /**
     * Vérifie si l'utilisateur a accès à une campagne spécifique
     * Délègue au helper centralisé
     *
     * @param int $campaignId ID de la campagne
     * @return bool True si accès autorisé
     * @created 2025/12/16
     */
    private function canAccessCampaign(int $campaignId): bool
    {
        return StatsAccessHelper::canAccessCampaign($campaignId);
    }

    /**
     * Filtre les représentants selon le rôle de l'utilisateur
     * Délègue au helper centralisé
     *
     * @param array $reps Liste des représentants
     * @return array Liste filtrée
     * @created 2025/12/16
     */
    private function filterRepsList(array $reps): array
    {
        return StatsAccessHelper::filterRepsList($reps);
    }

    // =========================================================================
    // MÉTHODES PUBLIQUES
    // =========================================================================

    /**
     * Vue globale des statistiques
     *
     * @return void
     * @modified 2025/12/17 - Ajout filtrage automatique par pays
     */
    public function index(): void
    {
        // Récupérer les pays accessibles et le pays par défaut
        $accessibleCountries = StatsAccessHelper::getAccessibleCountries();
        $defaultCountry = StatsAccessHelper::getDefaultCountry();

        // Récupérer les filtres
        $period = $_GET["period"] ?? "7"; // 7 jours par défaut
        $campaignId = !empty($_GET["campaign_id"]) ? (int) $_GET["campaign_id"] : null;
        $country = !empty($_GET["country"]) ? $_GET["country"] : $defaultCountry;

        // Vérifier que le pays sélectionné est accessible
        if ($country && !StatsAccessHelper::canAccessCountry($country)) {
            $country = $defaultCountry;
        }

        // Vérifier l'accès à la campagne sélectionnée
        if ($campaignId && !$this->canAccessCampaign($campaignId)) {
            Session::setFlash("error", "Vous n'avez pas accès à cette campagne");
            header("Location: /stm/admin/stats");
            exit();
        }

        // Calculer les dates selon la période
        $dateTo = date("Y-m-d");

        switch ($period) {
            case "7":
            default:
                $dateFrom = date("Y-m-d", strtotime("-7 days"));
                $periodLabel = "7 derniers jours";
                break;
            case "14":
                $dateFrom = date("Y-m-d", strtotime("-14 days"));
                $periodLabel = "14 derniers jours";
                break;
            case "30":
                $dateFrom = date("Y-m-d", strtotime("-30 days"));
                $periodLabel = "30 derniers jours";
                break;
            case "month":
                $dateFrom = date("Y-m-01"); // Premier jour du mois
                $periodLabel = "Ce mois (" . date("F Y") . ")";
                break;
        }

        // Récupérer les données (filtrées par campagnes accessibles si pas de campagne spécifique)
        $accessibleCampaignIds = $this->getAccessibleCampaignIds();

        // Passer le filtre d'accès aux méthodes du modèle
        $kpis = $this->statsModel->getGlobalKPIs($dateFrom, $dateTo, $campaignId, $country, $accessibleCampaignIds);
        $dailyEvolution = $this->statsModel->getDailyEvolution($dateFrom, $dateTo, $campaignId, $accessibleCampaignIds);
        $topProducts = $this->statsModel->getTopProducts($dateFrom, $dateTo, $campaignId, $country, 10, $accessibleCampaignIds);
        $clusterStats = $this->statsModel->getStatsByCluster($dateFrom, $dateTo, $campaignId, $country, $accessibleCampaignIds);

        // Liste des campagnes pour le filtre (filtrée selon accès)
        $allCampaigns = $this->statsModel->getCampaignsList();
        $campaigns = $this->filterCampaignsList($allCampaigns);

        // Préparer les données pour les graphiques
        $chartLabels = [];
        $chartOrders = [];
        $chartQuantity = [];

        // Générer toutes les dates de la période
        $currentDate = new \DateTime($dateFrom);
        $endDate = new \DateTime($dateTo);
        $dailyMap = [];

        foreach ($dailyEvolution as $row) {
            $dailyMap[$row["day"]] = $row;
        }

        while ($currentDate <= $endDate) {
            $day = $currentDate->format("Y-m-d");
            $chartLabels[] = $currentDate->format("d/m");
            $chartOrders[] = (int) ($dailyMap[$day]["orders_count"] ?? 0);
            $chartQuantity[] = (int) ($dailyMap[$day]["quantity"] ?? 0);
            $currentDate->modify("+1 day");
        }

        // Données pour le graphique cluster
        // Le format est déjà ['cluster_name' => ['quantity' => X, 'customers' => Y], ...]
        $clusterGroups = $clusterStats;

        // Variables pour la vue
        $title = "Statistiques - Vue globale";

        require __DIR__ . "/../Views/admin/stats/index.php";
    }


    /**
     * Statistiques par campagne
     *
     * @return void
     * @modified 2025/12/04 - Ajout graphiques évolution + catégories
     * @modified 2025/12/09 - Ajout stats par fournisseur
     * @modified 2025/12/09 - Ajout mapping produit → fournisseur pour onglet Produits
     * @modified 2025/12/16 - Ajout filtrage hiérarchique par rôle
     * @modified 2025/12/17 - Ajout filtrage par clients accessibles
 * @modified 2025/12/23 - Ajout top clients (getTopCustomersForCampaign)
     */
    public function campaigns(): void
    {
        // Récupérer les filtres
        $campaignId = !empty($_GET["campaign_id"]) ? (int) $_GET["campaign_id"] : null;
        $country = !empty($_GET["country"]) ? $_GET["country"] : null;
        $repId = !empty($_GET["rep_id"]) ? $_GET["rep_id"] : null;
        $repCountry = !empty($_GET["rep_country"]) ? $_GET["rep_country"] : null;

        // Vérifier l'accès à la campagne sélectionnée
        if ($campaignId && !$this->canAccessCampaign($campaignId)) {
            Session::setFlash("error", "Vous n'avez pas accès à cette campagne");
            header("Location: /stm/admin/stats/campaigns");
            exit();
        }

        // Récupérer les clients accessibles selon le rôle
        $accessibleCustomerNumbers = StatsAccessHelper::getAccessibleCustomerNumbersOnly();

        // Liste des campagnes (filtrée selon accès)
        $allCampaigns = $this->statsModel->getCampaignsList();
        $campaigns = $this->filterCampaignsList($allCampaigns);

        // Stats de la campagne sélectionnée
        $campaignStats = null;
        $campaignProducts = [];
        $reps = [];
        $repDetail = null;
        $repClients = [];

        // Données pour graphiques
        $dailyEvolution = [];
        $categoryStats = [];
        $chartLabels = [];
        $chartOrders = [];
        $chartQuantity = [];
        $categoryLabels = [];
        $categoryData = [];
        $categoryColors = [];

        // Stats par fournisseur
        $supplierStats = [];

        // Mapping produit → fournisseur pour l'onglet Produits
        $productSuppliers = [];

        // Top clients
        $topCustomers = [];
        $topCustomersLimit = isset($_GET["customers_limit"]) ? (int) $_GET["customers_limit"] : 50;
        // Valider la limite (10, 25, 50, 100)
        if (!in_array($topCustomersLimit, [10, 25, 50, 100])) {
            $topCustomersLimit = 50;
        }

        if ($campaignId) {
            // Passer le filtre clients aux méthodes de stats
            $campaignStats = $this->statsModel->getCampaignStats($campaignId, $accessibleCustomerNumbers);
            $campaignProducts = $this->statsModel->getCampaignProducts($campaignId, $accessibleCustomerNumbers);

            // Récupérer les représentants filtrés sur cette campagne
            $campaignCountry = $campaignStats["campaign"]["country"] ?? null;
            $allReps = $this->statsModel->getRepStats($campaignCountry, $campaignId);

            // Filtrer les reps selon le rôle (manager_reps ne voit que ses reps)
            $reps = $this->filterRepsList($allReps);

            // Détail d'un représentant si sélectionné
            if ($repId && $repCountry) {
                $repClients = $this->statsModel->getRepClients($repId, $repCountry, $campaignId);

                foreach ($reps as $rep) {
                    if ($rep["id"] === $repId && $rep["country"] === $repCountry) {
                        $repDetail = $rep;
                        break;
                    }
                }
            }

            // ============================================
            // Mapping produit → fournisseur pour onglet Produits
            // ============================================
            if (!empty($campaignProducts)) {
                try {
                    $productCodes = array_column($campaignProducts, 'product_code');
                    $externalDb = \Core\ExternalDatabase::getInstance();
                    $productSuppliers = $externalDb->getSuppliersForProducts($productCodes);
                } catch (\Exception $e) {
                    error_log("Erreur getSuppliersForProducts: " . $e->getMessage());
                    $productSuppliers = [];
                }
            }

            // ============================================
            // Évolution journalière sur la période de la campagne
            // ============================================
            $campaign = $campaignStats["campaign"];
            $startDate = $campaign["start_date"];
            $endDate = $campaign["end_date"];

            // Si la campagne est toujours en cours, limiter à aujourd'hui
            $today = date("Y-m-d");
            if ($endDate > $today) {
                $endDate = $today;
            }

            $dailyEvolution = $this->statsModel->getDailyEvolution($startDate, $endDate, $campaignId, null, $accessibleCustomerNumbers);

            // Préparer les données pour le graphique d'évolution
            $currentDate = new \DateTime($startDate);
            $endDateObj = new \DateTime($endDate);
            $dailyMap = [];

            foreach ($dailyEvolution as $row) {
                $dailyMap[$row["day"]] = $row;
            }

            while ($currentDate <= $endDateObj) {
                $day = $currentDate->format("Y-m-d");
                $chartLabels[] = $currentDate->format("D d/m");
                $chartOrders[] = (int) ($dailyMap[$day]["orders_count"] ?? 0);
                $chartQuantity[] = (int) ($dailyMap[$day]["quantity"] ?? 0);
                $currentDate->modify("+1 day");
            }

            // ============================================
            // Stats par catégorie pour le donut
            // ============================================
            $categoryStats = $this->getCategoryStatsForCampaign($campaignId, $accessibleCustomerNumbers);

            foreach ($categoryStats as $cat) {
                $categoryLabels[] = $cat["category_name"];
                $categoryData[] = (int) $cat["quantity"];
                $categoryColors[] = $cat["color"] ?? $this->getRandomColor();
            }

            // ============================================
            // Stats par fournisseur (09/12/2025)
            // ============================================
            try {
                $supplierStats = $this->campaignModel->getSupplierStats($campaignId);
            } catch (\Exception $e) {
                error_log("Erreur getSupplierStats: " . $e->getMessage());
                $supplierStats = [];
            }

            // ============================================
            // Top clients (23/12/2025)
            // ============================================
            $topCustomers = $this->statsModel->getTopCustomersForCampaign($campaignId, $accessibleCustomerNumbers, $topCustomersLimit);
        }

        $title = "Statistiques - Par campagne";

        // Encoder les données JSON pour les graphiques
        $chartLabelsJson = json_encode($chartLabels);
        $chartOrdersJson = json_encode($chartOrders);
        $chartQuantityJson = json_encode($chartQuantity);
        $categoryLabelsJson = json_encode($categoryLabels);
        $categoryDataJson = json_encode($categoryData);
        $categoryColorsJson = json_encode($categoryColors);

        require __DIR__ . "/../Views/admin/stats/campaigns.php";
    }

    /**
     * Récupérer les stats par catégorie pour une campagne
     *
     * @param int $campaignId ID de la campagne
     * @param array|null $accessibleCustomerNumbers Liste des numéros clients accessibles (null = tout)
     * @return array Stats par catégorie
     * @modified 2025/12/23 - Ajout filtre par clients accessibles
     */
    private function getCategoryStatsForCampaign(int $campaignId, ?array $accessibleCustomerNumbers = null): array
    {
        $db = \Core\Database::getInstance();
        $params = [":campaign_id" => $campaignId];

        // Construire le filtre clients
        $customerFilter = "";
        $customerJoin = "";

        if ($accessibleCustomerNumbers !== null) {
            if (empty($accessibleCustomerNumbers)) {
                // Aucun client accessible = retourner tableau vide
                return [];
            }
            $placeholders = [];
            foreach ($accessibleCustomerNumbers as $i => $num) {
                $key = ":cat_cust_{$i}";
                $placeholders[] = $key;
                $params[$key] = $num;
            }
            $customerJoin = "INNER JOIN customers cu ON o.customer_id = cu.id";
            $customerFilter = "AND cu.customer_number IN (" . implode(",", $placeholders) . ")";
        }

        $query = "
            SELECT
                c.name_fr as category_name,
                c.color,
                COALESCE(SUM(ol.quantity), 0) as quantity
            FROM categories c
            INNER JOIN products p ON c.id = p.category_id AND p.campaign_id = :campaign_id
            LEFT JOIN order_lines ol ON p.id = ol.product_id
            LEFT JOIN orders o ON ol.order_id = o.id AND o.status = 'synced'
            {$customerJoin}
            WHERE 1=1
            {$customerFilter}
            GROUP BY c.id, c.name_fr, c.color
            HAVING quantity > 0
            ORDER BY quantity DESC
        ";

        return $db->query($query, $params);
    }

    /**
     * Générer une couleur aléatoire pour les graphiques
     *
     * @return string Couleur hex
     */
    private function getRandomColor(): string
    {
        $colors = [
            '#6366f1', '#8b5cf6', '#a855f7', '#d946ef', '#ec4899',
            '#f43f5e', '#ef4444', '#f97316', '#f59e0b', '#eab308',
            '#84cc16', '#22c55e', '#10b981', '#14b8a6', '#06b6d4',
            '#0ea5e9', '#3b82f6', '#6366f1'
        ];
        return $colors[array_rand($colors)];
    }

    /**
     * Statistiques par commercial
     *
     * @return void
     * @modified 2025/12/16 - Ajout filtrage hiérarchique par rôle
     * @modified 2025/12/17 - Ajout filtrage automatique par pays
     */
    public function sales(): void
    {
        // Récupérer les pays accessibles et le pays par défaut
        $accessibleCountries = StatsAccessHelper::getAccessibleCountries();
        $defaultCountry = StatsAccessHelper::getDefaultCountry();

        // Récupérer les filtres
        $country = !empty($_GET["country"]) ? $_GET["country"] : $defaultCountry;
        $campaignId = !empty($_GET["campaign_id"]) ? (int) $_GET["campaign_id"] : null;
        $repId = !empty($_GET["rep_id"]) ? $_GET["rep_id"] : null;
        $repCountry = !empty($_GET["rep_country"]) ? $_GET["rep_country"] : null;

        // Vérifier que le pays sélectionné est accessible
        if ($country && !StatsAccessHelper::canAccessCountry($country)) {
            $country = $defaultCountry;
        }

        // Vérifier l'accès à la campagne sélectionnée
        if ($campaignId && !$this->canAccessCampaign($campaignId)) {
            Session::setFlash("error", "Vous n'avez pas accès à cette campagne");
            header("Location: /stm/admin/stats/sales");
            exit();
        }

        // Liste des campagnes pour le filtre (filtrée selon accès)
        $allCampaigns = $this->statsModel->getCampaignsList();
        $campaigns = $this->filterCampaignsList($allCampaigns);

        // Liste des clusters
        $clusters = $this->statsModel->getClustersList();

        // Liste des représentants avec leurs stats (filtrée selon rôle)
        $allReps = $this->statsModel->getRepStats($country, $campaignId);
        $reps = $this->filterRepsList($allReps);

        // Détail d'un représentant si sélectionné
        $repDetail = null;
        $repClients = [];

        if ($repId && $repCountry) {
            $repClients = $this->statsModel->getRepClients($repId, $repCountry, $campaignId);

            // Trouver les infos du rep
            foreach ($reps as $rep) {
                if ($rep["id"] === $repId && $rep["country"] === $repCountry) {
                    $repDetail = $rep;
                    break;
                }
            }
        }

        $title = "Statistiques - Par représentant";

        require __DIR__ . "/../Views/admin/stats/sales.php";
    }

    /**
     * Page des rapports et exports
     *
     * @return void
     * @modified 2025/12/16 - Ajout filtrage hiérarchique par rôle
     */
    public function reports(): void
    {
        // Liste des campagnes pour les exports (filtrée selon accès)
        $allCampaigns = $this->statsModel->getCampaignsList();
        $campaigns = $this->filterCampaignsList($allCampaigns);

        $title = "Statistiques - Rapports";

        require __DIR__ . "/../Views/admin/stats/reports.php";
    }

    /**
     * Export CSV/Excel
     *
     * @return void
     * @modified 2025/12/16 - Ajout vérification accès campagne
     */
    public function export(): void
    {
        $type = $_POST["type"] ?? "global";
        $format = $_POST["format"] ?? "csv";
        $campaignId = !empty($_POST["campaign_id"]) ? (int) $_POST["campaign_id"] : null;
        $dateFrom = $_POST["date_from"] ?? date("Y-m-d", strtotime("-14 days"));
        $dateTo = $_POST["date_to"] ?? date("Y-m-d");

        // Vérifier l'accès à la campagne si spécifiée
        if ($campaignId && !$this->canAccessCampaign($campaignId)) {
            Session::setFlash("error", "Vous n'avez pas accès à cette campagne");
            header("Location: /stm/admin/stats/reports");
            exit();
        }

        $data = [];
        $filename = "";
        $headers = [];

        switch ($type) {
            case "campaign":
                if (!$campaignId) {
                    Session::setFlash("error", "Veuillez sélectionner une campagne");
                    header("Location: /stm/admin/stats/reports");
                    exit();
                }

                // Export des commandes d'une campagne
                $data = $this->getExportCampaignData($campaignId);
                $headers = [
                    "Num_Client",
                    "Nom",
                    "Pays",
                    "Promo_Art",
                    "Nom_Produit",
                    "Quantité",
                    "Email",
                    "Rep_Name",
                    "Date_Commande",
                ];
                $filename = "export_campagne_" . $campaignId . "_" . date("Ymd");
                break;

            case "reps":
                // Export stats par représentant
                $data = $this->getExportRepsData($campaignId);
                $headers = [
                    "Rep_ID",
                    "Rep_Nom",
                    "Cluster",
                    "Pays",
                    "Nb_Clients",
                    "Clients_Commandé",
                    "Taux_Conv",
                    "Total_Quantité",
                ];
                $filename = "export_reps_" . date("Ymd");
                break;

            case "not_ordered":
                if (!$campaignId) {
                    Session::setFlash("error", "Veuillez sélectionner une campagne");
                    header("Location: /stm/admin/stats/reports");
                    exit();
                }

                // Export clients n'ayant pas commandé
                $data = $this->statsModel->getCustomersNotOrdered($campaignId, 5000);
                $headers = ["Num_Client", "Nom", "Pays", "Rep_Name"];
                $filename = "clients_sans_commande_" . $campaignId . "_" . date("Ymd");
                break;

            default:
                // Export global
                $data = $this->getExportGlobalData($dateFrom, $dateTo, $campaignId);
                $headers = [
                    "Num_Client",
                    "Nom",
                    "Pays",
                    "Promo_Art",
                    "Nom_Produit",
                    "Quantité",
                    "Rep_Name",
                    "Cluster",
                    "Date_Commande",
                ];
                $filename = "export_global_" . date("Ymd");
                break;
        }

        // Générer le fichier
        if ($format === "csv") {
            $this->exportCSV($data, $headers, $filename);
        } else {
            // Pour Excel, on utilise CSV avec séparateur point-virgule
            $this->exportCSV($data, $headers, $filename, ";");
        }
    }

    /**
     * Récupère les données pour export global
     */
    private function getExportGlobalData(string $dateFrom, string $dateTo, ?int $campaignId): array
    {
        $db = \Core\Database::getInstance();

        $params = [
            ":date_from" => $dateFrom . " 00:00:00",
            ":date_to" => $dateTo . " 23:59:59",
        ];

        $campaignFilter = "";
        if ($campaignId) {
            $campaignFilter = " AND o.campaign_id = :campaign_id";
            $params[":campaign_id"] = $campaignId;
        }

        $query = "
            SELECT cu.customer_number as Num_Client,
                   cu.company_name as Nom,
                   cu.country as Pays,
                   p.product_code as Promo_Art,
                   p.name as Nom_Produit,
                   ol.quantity as Quantite,
                   cu.rep_name as Rep_Name,
                   '' as Cluster,
                   DATE_FORMAT(o.created_at, '%Y-%m-%d %H:%i') as Date_Commande
            FROM orders o
            INNER JOIN customers cu ON o.customer_id = cu.id
            INNER JOIN order_lines ol ON o.id = ol.order_id
            INNER JOIN products p ON ol.product_id = p.id
            WHERE o.status = 'synced'
            AND o.created_at BETWEEN :date_from AND :date_to
            {$campaignFilter}
            ORDER BY o.created_at DESC, cu.customer_number
        ";

        return $db->query($query, $params);
    }

    /**
     * Récupère les données pour export campagne
     */
    private function getExportCampaignData(int $campaignId): array
    {
        $db = \Core\Database::getInstance();

        $query = "
            SELECT cu.customer_number as Num_Client,
                   cu.company_name as Nom,
                   cu.country as Pays,
                   p.product_code as Promo_Art,
                   p.name as Nom_Produit,
                   ol.quantity as Quantite,
                   o.customer_email as Email,
                   cu.rep_name as Rep_Name,
                   DATE_FORMAT(o.created_at, '%Y-%m-%d %H:%i') as Date_Commande
            FROM orders o
            INNER JOIN customers cu ON o.customer_id = cu.id
            INNER JOIN order_lines ol ON o.id = ol.order_id
            INNER JOIN products p ON ol.product_id = p.id
            WHERE o.campaign_id = :campaign_id
            AND o.status = 'synced'
            ORDER BY cu.customer_number, p.product_code
        ";

        return $db->query($query, [":campaign_id" => $campaignId]);
    }

    /**
     * Récupère les données pour export représentants
     */
    private function getExportRepsData(?int $campaignId): array
    {
        $reps = $this->statsModel->getRepStats(null, $campaignId);

        $data = [];
        foreach ($reps as $rep) {
            $convRate =
                $rep["total_clients"] > 0
                    ? round(($rep["stats"]["customers_ordered"] / $rep["total_clients"]) * 100, 1) . "%"
                    : "0%";

            $data[] = [
                "Rep_ID" => $rep["id"],
                "Rep_Nom" => $rep["name"],
                "Cluster" => $rep["cluster"],
                "Pays" => $rep["country"],
                "Nb_Clients" => $rep["total_clients"],
                "Clients_Commande" => $rep["stats"]["customers_ordered"],
                "Taux_Conv" => $convRate,
                "Total_Quantite" => $rep["stats"]["total_quantity"],
            ];
        }

        return $data;
    }

    /**
     * Génère et télécharge un fichier CSV
     */
    private function exportCSV(array $data, array $headers, string $filename, string $delimiter = ","): void
    {
        header("Content-Type: text/csv; charset=utf-8");
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header("Pragma: no-cache");
        header("Expires: 0");

        // BOM UTF-8 pour Excel
        echo "\xEF\xBB\xBF";

        $output = fopen("php://output", "w");

        // En-têtes
        fputcsv($output, $headers, $delimiter);

        // Données
        foreach ($data as $row) {
            if (is_array($row)) {
                fputcsv($output, array_values($row), $delimiter);
            }
        }

        fclose($output);
        exit();
    }
    /**
     * Export Excel détaillé des représentants pour une campagne
     *
     * Génère un fichier Excel multi-feuilles :
     * - Feuille 1 : Récap de tous les représentants
     * - Feuilles 2+ : Détail par représentant (clients + quantités par produit)
     *
     * @return void
     * @created 2025/12/10
     * @modified 2025/12/10 - Filtrage selon mode campagne + reps avec clients uniquement
     * @modified 2025/12/16 - Ajout vérification accès campagne + filtrage reps par rôle
     */
    public function exportRepsExcel(): void
    {
        // Augmenter les limites pour la génération Excel (gros volumes)
        set_time_limit(600); // 10 minutes
        ini_set('memory_limit', '2048M'); // 2 Go

        $campaignId = !empty($_POST["campaign_id"]) ? (int) $_POST["campaign_id"] : null;
        $downloadToken = $_POST['download_token'] ?? '';
        $forceRegenerate = !empty($_POST["force_regenerate"]);

        if (!$campaignId) {
            Session::setFlash("error", "Veuillez sélectionner une campagne");
            header("Location: /stm/admin/stats/campaigns");
            exit();
        }

        // Vérifier l'accès à la campagne
        if (!$this->canAccessCampaign($campaignId)) {
            Session::setFlash("error", "Vous n'avez pas accès à cette campagne");
            header("Location: /stm/admin/stats/campaigns");
            exit();
        }

        // Récupérer les infos de la campagne
        $campaign = $this->campaignModel->findById($campaignId);
        if (!$campaign) {
            Session::setFlash("error", "Campagne introuvable");
            header("Location: /stm/admin/stats/campaigns");
            exit();
        }

        $campaignCountry = $campaign["country"];
        $campaignName = $campaign["name"];
        $assignmentMode = $campaign["customer_assignment_mode"] ?? "automatic";

        // ============================================
        // SYSTÈME DE CACHE
        // ============================================

        // 1. Calculer le scope d'accès selon le rôle
        $accessScope = $this->getExportAccessScope();

        // 2. Calculer le hash des données actuelles
        $currentHash = $this->getExportDataHash($campaignId, $accessScope);

        // 3. Vérifier si un cache existe
        $cache = $this->getExportCache($campaignId, 'reps_excel', $accessScope);

        // 4. Si cache valide (même hash) et pas de régénération forcée → servir le fichier
        if ($cache && $cache['data_hash'] === $currentHash && !$forceRegenerate) {
            $this->serveExportFile($cache, $downloadToken);
            exit();
        }

        // 5. Sinon → générer le fichier
        // (cache inexistant, hash différent, ou régénération forcée)

        // Récupérer les représentants avec leurs stats (filtrés par campagne)
        $allReps = $this->statsModel->getRepStats($campaignCountry, $campaignId);

        // Filtrer selon le rôle (manager_reps ne voit que ses reps)
        $filteredReps = $this->filterRepsList($allReps);

        // FILTRER : Ne garder que les reps qui ont des clients assignés (total_clients > 0)
        $reps = array_filter($filteredReps, function($rep) {
            return ($rep["total_clients"] ?? 0) > 0;
        });
        $reps = array_values($reps); // Réindexer

        if (empty($reps)) {
            Session::setFlash("error", "Aucun représentant avec des clients trouvé pour cette campagne");
            header("Location: /stm/admin/stats/campaigns?campaign_id=" . $campaignId);
            exit();
        }

        // Récupérer tous les produits de la campagne (pour les colonnes)
        $campaignProducts = $this->statsModel->getCampaignProducts($campaignId);

        // Trier les produits par code article
        usort($campaignProducts, function($a, $b) {
            return strcasecmp($a["product_code"] ?? "", $b["product_code"] ?? "");
        });

        // En mode MANUAL, récupérer la liste des clients autorisés
        $authorizedCustomers = [];
        if ($assignmentMode === "manual") {
            $authorizedCustomers = $this->getAuthorizedCustomersForCampaign($campaignId);
        }

        // Créer le spreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        // Style en-têtes
        $headerStyle = [
            "font" => ["bold" => true, "color" => ["rgb" => "FFFFFF"]],
            "fill" => [
                "fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                "startColor" => ["rgb" => "4F46E5"]
            ],
            "alignment" => ["horizontal" => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
        ];

        // ============================================
        // FEUILLE 1 : RÉCAP REPRÉSENTANTS
        // ============================================
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle("Récap Représentants");

        // En-têtes
        $headers = ["N° Rep", "Nom", "Email", "Cluster", "Clients assignés", "Ont commandé", "Pas commandé", "Qté totale", "% Conversion"];
        $col = "A";
        foreach ($headers as $header) {
            $sheet->setCellValue($col . "1", $header);
            $col++;
        }

        // Style en-têtes
        $sheet->getStyle("A1:I1")->applyFromArray($headerStyle);

        // Préparer les noms de feuilles pour les liens (même logique que la création)
        $sheetNames = [];
        $usedNames = ["Récap Représentants" => true];
        foreach ($reps as $repIndex => $rep) {
            $cleanName = preg_replace("/[^a-zA-Z0-9\s\-]/", "", $rep["name"] ?? "Inconnu");
            $sheetName = substr("Rep - " . $cleanName, 0, 31);

            $originalSheetName = $sheetName;
            $counter = 1;
            while (isset($usedNames[$sheetName])) {
                $sheetName = substr($originalSheetName, 0, 28) . " " . $counter;
                $counter++;
            }
            $usedNames[$sheetName] = true;
            $sheetNames[$repIndex] = $sheetName;
        }

        // Données
        $row = 2;
        foreach ($reps as $repIndex => $rep) {
            $totalClients = $rep["total_clients"] ?? 0;
            $customersOrdered = $rep["stats"]["customers_ordered"] ?? 0;
            $notOrdered = $totalClients - $customersOrdered;
            $totalQty = $rep["stats"]["total_quantity"] ?? 0;
            $convRate = $totalClients > 0 ? round(($customersOrdered / $totalClients) * 100, 1) : 0;

            $sheet->setCellValue("A" . $row, $rep["id"] ?? "");
            $sheet->setCellValue("B" . $row, $rep["name"] ?? "");
            $sheet->setCellValue("C" . $row, $rep["email"] ?? "");
            $sheet->setCellValue("D" . $row, $rep["cluster"] ?? "");
            $sheet->setCellValue("E" . $row, $totalClients);
            $sheet->setCellValue("F" . $row, $customersOrdered);
            $sheet->setCellValue("G" . $row, $notOrdered);
            $sheet->setCellValue("H" . $row, $totalQty);
            $sheet->setCellValue("I" . $row, $convRate . "%");

            // Ajouter hyperlien vers la feuille du représentant (double-clic ou clic)
            $targetSheet = $sheetNames[$repIndex];
            $sheet->getCell("B" . $row)->getHyperlink()->setUrl("sheet://'" . $targetSheet . "'!A1");
            $sheet->getStyle("B" . $row)->applyFromArray([
                "font" => ["color" => ["rgb" => "0066CC"], "underline" => true]
            ]);

            $row++;
        }

        // Largeurs fixes
        $sheet->getColumnDimension("A")->setWidth(12);
        $sheet->getColumnDimension("B")->setWidth(25);
        $sheet->getColumnDimension("C")->setWidth(30);
        $sheet->getColumnDimension("D")->setWidth(20);
        $sheet->getColumnDimension("E")->setWidth(15);
        $sheet->getColumnDimension("F")->setWidth(15);
        $sheet->getColumnDimension("G")->setWidth(15);
        $sheet->getColumnDimension("H")->setWidth(12);
        $sheet->getColumnDimension("I")->setWidth(12);

        // ============================================
        // FEUILLES PAR REPRÉSENTANT
        // ============================================
        foreach ($reps as $repIndex => $rep) {
            // Créer une nouvelle feuille
            $repSheet = $spreadsheet->createSheet();

            // Utiliser le nom pré-calculé (cohérent avec les liens)
            $sheetName = $sheetNames[$repIndex];
            $repSheet->setTitle($sheetName);

            // Récupérer les clients du représentant (TOUS depuis la DB externe)
            $allRepClients = $this->statsModel->getRepClients($rep["id"], $rep["country"], $campaignId);

            // FILTRER selon le mode d'attribution
            if ($assignmentMode === "manual" && !empty($authorizedCustomers)) {
                // Mode MANUAL : ne garder que les clients autorisés pour cette campagne
                $repClients = array_filter($allRepClients, function($client) use ($authorizedCustomers) {
                    return in_array($client["customer_number"], $authorizedCustomers);
                });
                $repClients = array_values($repClients);
            } else {
                // Mode AUTOMATIC ou PROTECTED : tous les clients du rep
                $repClients = $allRepClients;
            }

            // Si aucun client pour ce rep (après filtrage), on met une feuille vide avec message
            if (empty($repClients)) {
                $repSheet->setCellValue("A1", "Aucun client assigné pour ce représentant");
                continue;
            }

            // En-têtes fixes
            $fixedHeaders = ["N° Client", "Nom", "Ville", "A commandé", "Qté totale"];
            $col = "A";
            foreach ($fixedHeaders as $header) {
                $repSheet->setCellValue($col . "1", $header);
                $col++;
            }

            // En-têtes produits (colonnes dynamiques) - Utiliser le code article
            $productColumns = [];
            $colIndex = 6;
            foreach ($campaignProducts as $product) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $productColumns[$product["id"]] = $colLetter;
                $repSheet->setCellValue($colLetter . "1", $product["product_code"]);
                $colIndex++;
            }

            $lastColIndex = $colIndex - 1;
            $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColIndex);

            // Style en-têtes
            $repSheet->getStyle("A1:" . $lastColLetter . "1")->applyFromArray($headerStyle);

            // Récupérer les quantités par produit pour chaque client
            $clientProductQuantities = $this->getClientProductQuantities($campaignId, $rep["id"], $rep["country"]);

            // Trier les clients : ceux qui ont commandé en premier, puis par quantité décroissante
            usort($repClients, function($a, $b) {
                $aOrdered = $a["has_ordered"] ?? false;
                $bOrdered = $b["has_ordered"] ?? false;

                if ($aOrdered && !$bOrdered) return -1;
                if (!$aOrdered && $bOrdered) return 1;

                return ($b["total_quantity"] ?? 0) - ($a["total_quantity"] ?? 0);
            });

            // Données clients
            $row = 2;
            foreach ($repClients as $client) {
                $customerNumber = $client["customer_number"] ?? "";
                $hasOrdered = $client["has_ordered"] ?? false;

                $repSheet->setCellValue("A" . $row, $customerNumber);
                $repSheet->setCellValue("B" . $row, $client["company_name"] ?? "-");
                $repSheet->setCellValue("C" . $row, $client["city"] ?? "-");
                $repSheet->setCellValue("D" . $row, $hasOrdered ? "Oui" : "Non");
                $repSheet->setCellValue("E" . $row, $client["total_quantity"] ?? 0);

                // Style conditionnel pour "A commandé"
                if ($hasOrdered) {
                    $repSheet->getStyle("D" . $row)->applyFromArray([
                        "font" => ["color" => ["rgb" => "059669"]],
                        "fill" => [
                            "fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            "startColor" => ["rgb" => "D1FAE5"]
                        ]
                    ]);
                } else {
                    $repSheet->getStyle("D" . $row)->applyFromArray([
                        "font" => ["color" => ["rgb" => "DC2626"]],
                        "fill" => [
                            "fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            "startColor" => ["rgb" => "FEE2E2"]
                        ]
                    ]);
                }

                // Quantités par produit (avec coloration si > 0)
                foreach ($campaignProducts as $product) {
                    $productId = $product["id"];
                    $colLetter = $productColumns[$productId];
                    $qty = $clientProductQuantities[$customerNumber][$productId] ?? 0;
                    $repSheet->setCellValue($colLetter . $row, $qty);

                    // Colorer en vert clair si quantité > 0
                    if ($qty > 0) {
                        $repSheet->getStyle($colLetter . $row)->applyFromArray([
                            "font" => ["bold" => true, "color" => ["rgb" => "065F46"]],
                            "fill" => [
                                "fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                "startColor" => ["rgb" => "D1FAE5"]
                            ]
                        ]);
                    }
                }

                $row++;
            }

            // Largeurs fixes
            $repSheet->getColumnDimension("A")->setWidth(12);
            $repSheet->getColumnDimension("B")->setWidth(30);
            $repSheet->getColumnDimension("C")->setWidth(20);
            $repSheet->getColumnDimension("D")->setWidth(12);
            $repSheet->getColumnDimension("E")->setWidth(10);

            foreach ($productColumns as $colLetter) {
                $repSheet->getColumnDimension($colLetter)->setWidth(8);
            }

            // Figer la première ligne et les 5 premières colonnes
            $repSheet->freezePane("F2");

            // Ajouter lien retour vers récap (en haut à droite, après les données)
            $lastDataRow = $row;
            $repSheet->setCellValue("A" . ($lastDataRow + 2), "← Retour Récap");
            $repSheet->getCell("A" . ($lastDataRow + 2))->getHyperlink()->setUrl("sheet://'Récap Représentants'!A1");
            $repSheet->getStyle("A" . ($lastDataRow + 2))->applyFromArray([
                "font" => ["color" => ["rgb" => "0066CC"], "underline" => true, "bold" => true]
            ]);
        }

        // Activer la première feuille
        $spreadsheet->setActiveSheetIndex(0);

        // ============================================
        // SAUVEGARDE EN CACHE
        // ============================================

        // Créer le dossier de stockage si nécessaire
        $storageDir = __DIR__ . '/../../storage/exports';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        // Générer un nom de fichier unique pour le cache
        $filename = "export_reps_" . preg_replace("/[^a-zA-Z0-9]/", "_", $campaignName) . "_" . $campaignId . "_" . md5($accessScope) . ".xlsx";
        $filePath = $storageDir . '/' . $filename;

        // Sauvegarder le fichier sur le serveur
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($filePath);

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        // Enregistrer dans la table de cache
        $fileSize = filesize($filePath);
        $this->saveExportCache($campaignId, 'reps_excel', $accessScope, $filePath, $filename, $currentHash, $fileSize);

        // Servir le fichier
        $cacheData = [
            'file_path' => $filePath,
            'file_name' => $filename
        ];
        $this->serveExportFile($cacheData, $downloadToken);

        exit();
    }

    /**
     * Récupère la liste des numéros clients autorisés pour une campagne (mode manual)
     *
     * @param int $campaignId
     * @return array Liste des customer_number autorisés
     * @created 2025/12/10
     */
    private function getAuthorizedCustomersForCampaign(int $campaignId): array
    {
        $db = \Core\Database::getInstance();

        $query = "
            SELECT customer_number
            FROM campaign_customers
            WHERE campaign_id = :campaign_id
            AND is_authorized = 1
        ";

        $results = $db->query($query, [":campaign_id" => $campaignId]);

        return array_column($results, "customer_number");
    }

    /**
     * Récupère les quantités par produit pour chaque client d'un représentant
     *
     * @param int $campaignId
     * @param string $repId
     * @param string $repCountry
     * @return array [customer_number => [product_id => quantity]]
     * @created 2025/12/10
     * @modified 2025/12/22 - Correction : utiliser customer_number IN au lieu de rep_id
     */
    private function getClientProductQuantities(int $campaignId, string $repId, string $repCountry): array
    {
        $db = \Core\Database::getInstance();

        // Étape 1 : Récupérer les customer_numbers du représentant depuis la DB externe
        try {
            $extDb = \Core\ExternalDatabase::getInstance();
        } catch (\Exception $e) {
            error_log("getClientProductQuantities: Impossible de se connecter à la DB externe - " . $e->getMessage());
            return [];
        }

        try {
            $tableClient = $repCountry === "BE" ? "BE_CLL" : "LU_CLL";
            $clientsResult = $extDb->query(
                "SELECT CLL_NCLIXX as customer_number FROM {$tableClient} WHERE IDE_REP = :rep_id",
                [":rep_id" => $repId]
            );

            if (empty($clientsResult)) {
                return [];
            }

            // Construire la liste des numéros clients (filtrer les nulls)
            $customerNumbers = array_filter(
                array_column($clientsResult, "customer_number"),
                fn($n) => $n !== null && $n !== ""
            );

            if (empty($customerNumbers)) {
                return [];
            }

            // Échapper les numéros pour la requête IN
            $escapedNumbers = array_map(function ($num) {
                return "'" . addslashes((string) $num) . "'";
            }, $customerNumbers);
            $inClause = implode(",", $escapedNumbers);

            // Étape 2 : Requête pour les quantités par produit
            $query = "
                SELECT
                    cu.customer_number,
                    ol.product_id,
                    SUM(ol.quantity) as quantity
                FROM orders o
                INNER JOIN customers cu ON o.customer_id = cu.id
                INNER JOIN order_lines ol ON o.id = ol.order_id
                WHERE o.campaign_id = :campaign_id
                AND o.status = 'synced'
                AND cu.customer_number IN ({$inClause})
                AND cu.country = :country
                GROUP BY cu.customer_number, ol.product_id
            ";

            $results = $db->query($query, [
                ":campaign_id" => $campaignId,
                ":country" => $repCountry
            ]);

            $quantities = [];
            foreach ($results as $row) {
                $customerNumber = $row["customer_number"];
                $productId = $row["product_id"];

                if (!isset($quantities[$customerNumber])) {
                    $quantities[$customerNumber] = [];
                }
                $quantities[$customerNumber][$productId] = (int) $row["quantity"];
            }

            return $quantities;

        } catch (\Exception $e) {
            error_log("getClientProductQuantities error: " . $e->getMessage());
            return [];
        }
    }

    // =========================================================================
    // MÉTHODES DE CACHE POUR LES EXPORTS
    // =========================================================================

    /**
     * Calcule le scope d'accès selon le rôle de l'utilisateur
     * Gère aussi le mode impersonation (se connecter en tant que)
     *
     * @return string Le scope d'accès (global, createur_X, manager_X, rep_X)
     * @created 2025/12/22
     * @modified 2025/12/23 - Correction support impersonation
     */
    private function getExportAccessScope(): string
    {
        // Vérifier si on est en mode impersonation
        $isImpersonating = Session::get('impersonate_original_user') !== null;

        if ($isImpersonating) {
            // En mode impersonation : lire le rôle depuis Session::get('user')
            $user = Session::get('user');
            $userRole = strtolower($user['role'] ?? '');
            $userId = $user['id'] ?? Session::get('user_id');
        } else {
            // Mode normal : utiliser user_id et user_role
            $userId = Session::get('user_id');
            $userRole = strtolower(Session::get('user_role') ?? '');
        }

        // Admin et superadmin : accès global (même fichier pour tous)
        if (in_array($userRole, ['superadmin', 'admin', 'super_admin'])) {
            return 'global';
        }

        // Créateur : scope basé sur user_id
        if ($userRole === 'createur') {
            return 'createur_' . $userId;
        }

        // Manager reps : scope basé sur user_id
        if ($userRole === 'manager_reps') {
            return 'manager_' . $userId;
        }

        // Rep : scope basé sur user_id
        if ($userRole === 'rep') {
            return 'rep_' . $userId;
        }

        // Par défaut : scope unique par utilisateur
        return 'user_' . $userId;
    }

    /**
     * Calcule le hash des données pour détecter les changements
     *
     * @param int $campaignId ID de la campagne
     * @param string $accessScope Scope d'accès
     * @return string Hash SHA256 des données
     * @created 2025/12/22
     */
    private function getExportDataHash(int $campaignId, string $accessScope): string
    {
        $db = \Core\Database::getInstance();

        // Récupérer les stats de base selon le scope
        $accessibleCampaignIds = $this->getAccessibleCampaignIds();

        // Filtre campagne si on a des restrictions
        $campaignFilter = "";
        $params = [":campaign_id" => $campaignId];

        if ($accessibleCampaignIds !== null && !in_array($campaignId, $accessibleCampaignIds)) {
            // Pas d'accès à cette campagne - hash vide
            return hash('sha256', 'no_access');
        }

        // Compter les commandes validées
        $query = "
            SELECT
                COUNT(DISTINCT o.id) as orders_count,
                COALESCE(SUM(ol.quantity), 0) as total_quantity,
                MAX(o.created_at) as last_order_date
            FROM orders o
            LEFT JOIN order_lines ol ON o.id = ol.order_id
            WHERE o.campaign_id = :campaign_id
            AND o.status = 'synced'
        ";

        $result = $db->query($query, $params);
        $stats = $result[0] ?? ['orders_count' => 0, 'total_quantity' => 0, 'last_order_date' => null];

        // Compter les représentants avec clients (selon le scope)
        $repsCount = 0;
        if (strpos($accessScope, 'rep_') === 0) {
            // Rep : un seul rep
            $repsCount = 1;
        } elseif (strpos($accessScope, 'manager_') === 0) {
            // Manager : compter ses reps
            $managedReps = $this->getManagedRepIds();
            $repsCount = count($managedReps);
        } else {
            // Admin/Global : compter tous les reps de la campagne
            $campaign = $this->campaignModel->findById($campaignId);
            $allReps = $this->statsModel->getRepStats($campaign['country'] ?? 'BE', $campaignId);
            $repsCount = count($allReps);
        }

        // Construire une chaîne unique pour le hash
        $hashString = implode('|', [
            $campaignId,
            $accessScope,
            $stats['orders_count'],
            $stats['total_quantity'],
            $stats['last_order_date'] ?? 'none',
            $repsCount
        ]);

        return hash('sha256', $hashString);
    }

    /**
     * Récupère le cache d'un export s'il existe
     *
     * @param int $campaignId ID de la campagne
     * @param string $exportType Type d'export
     * @param string $accessScope Scope d'accès
     * @return array|null Données du cache ou null
     * @created 2025/12/22
     */
    private function getExportCache(int $campaignId, string $exportType, string $accessScope): ?array
    {
        $db = \Core\Database::getInstance();

        try {
            $query = "
                SELECT * FROM export_cache
                WHERE campaign_id = :campaign_id
                AND export_type = :export_type
                AND access_scope = :access_scope
            ";

            $result = $db->query($query, [
                ':campaign_id' => $campaignId,
                ':export_type' => $exportType,
                ':access_scope' => $accessScope
            ]);

            if (empty($result)) {
                return null;
            }

            $cache = $result[0];

            // Vérifier que le fichier existe toujours
            if (!file_exists($cache['file_path'])) {
                // Fichier supprimé, nettoyer le cache
                $db->query("DELETE FROM export_cache WHERE id = :id", [':id' => $cache['id']]);
                return null;
            }

            return $cache;

        } catch (\Exception $e) {
            error_log("getExportCache error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Sauvegarde les infos d'un export en cache
     *
     * @param int $campaignId ID de la campagne
     * @param string $exportType Type d'export
     * @param string $accessScope Scope d'accès
     * @param string $filePath Chemin du fichier
     * @param string $fileName Nom du fichier
     * @param string $dataHash Hash des données
     * @param int $fileSize Taille du fichier
     * @created 2025/12/22
     */
    private function saveExportCache(
        int $campaignId,
        string $exportType,
        string $accessScope,
        string $filePath,
        string $fileName,
        string $dataHash,
        int $fileSize
    ): void {
        $db = \Core\Database::getInstance();

        try {
            // Supprimer l'ancien cache s'il existe (et son fichier)
            $oldCache = $this->getExportCache($campaignId, $exportType, $accessScope);
            if ($oldCache && $oldCache['file_path'] !== $filePath && file_exists($oldCache['file_path'])) {
                unlink($oldCache['file_path']);
            }

            // Insérer ou mettre à jour le cache
            $query = "
                INSERT INTO export_cache
                (campaign_id, export_type, access_scope, file_path, file_name, data_hash, file_size, created_at)
                VALUES
                (:campaign_id, :export_type, :access_scope, :file_path, :file_name, :data_hash, :file_size, NOW())
                ON DUPLICATE KEY UPDATE
                file_path = VALUES(file_path),
                file_name = VALUES(file_name),
                data_hash = VALUES(data_hash),
                file_size = VALUES(file_size),
                updated_at = NOW()
            ";

            $db->query($query, [
                ':campaign_id' => $campaignId,
                ':export_type' => $exportType,
                ':access_scope' => $accessScope,
                ':file_path' => $filePath,
                ':file_name' => $fileName,
                ':data_hash' => $dataHash,
                ':file_size' => $fileSize
            ]);

            // Nettoyer les vieux fichiers (> 6 mois) - occasionnellement
            if (rand(1, 100) === 1) {
                $this->cleanOldExportCache();
            }

        } catch (\Exception $e) {
            error_log("saveExportCache error: " . $e->getMessage());
        }
    }

    /**
     * Sert un fichier d'export au navigateur
     *
     * @param array $cache Données du cache
     * @param string $downloadToken Token pour signaler la fin du téléchargement
     * @created 2025/12/22
     */
    private function serveExportFile(array $cache, string $downloadToken): void
    {
        $filePath = $cache['file_path'];
        $fileName = $cache['file_name'];

        // Vérifier que le fichier existe
        if (!file_exists($filePath)) {
            Session::setFlash("error", "Fichier d'export introuvable");
            header("Location: /stm/admin/stats/campaigns");
            exit();
        }

        // Cookie pour signaler au JS que le téléchargement est terminé
        if ($downloadToken) {
            setcookie('download_complete', $downloadToken, time() + 60, '/');
        }

        // Headers pour le téléchargement
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header("Content-Disposition: attachment;filename=\"" . $fileName . "\"");
        header("Content-Length: " . filesize($filePath));
        header("Cache-Control: max-age=0");
        header("Pragma: public");

        // Envoyer le fichier
        readfile($filePath);
    }

    /**
     * Nettoie les exports en cache de plus de 6 mois
     *
     * @created 2025/12/22
     */
    private function cleanOldExportCache(): void
    {
        $db = \Core\Database::getInstance();

        try {
            // Récupérer les vieux caches
            $query = "
                SELECT id, file_path FROM export_cache
                WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH)
            ";

            $oldCaches = $db->query($query);

            foreach ($oldCaches as $cache) {
                // Supprimer le fichier
                if (file_exists($cache['file_path'])) {
                    unlink($cache['file_path']);
                }

                // Supprimer l'entrée en base
                $db->query("DELETE FROM export_cache WHERE id = :id", [':id' => $cache['id']]);
            }

            error_log("cleanOldExportCache: " . count($oldCaches) . " fichiers nettoyés");

        } catch (\Exception $e) {
            error_log("cleanOldExportCache error: " . $e->getMessage());
        }
    }

    /**
     * API pour vérifier l'état du cache d'un export (appelé en AJAX)
     *
     * @created 2025/12/22
     */
    public function checkExportCache(): void
    {
        header('Content-Type: application/json');

        $campaignId = !empty($_GET["campaign_id"]) ? (int) $_GET["campaign_id"] : null;

        if (!$campaignId) {
            echo json_encode(['error' => 'campaign_id requis']);
            exit();
        }

        // Vérifier l'accès à la campagne
        if (!$this->canAccessCampaign($campaignId)) {
            echo json_encode(['error' => 'Accès non autorisé']);
            exit();
        }

        // Debug : récupérer le rôle (prend en compte l'impersonation)
        $isImpersonating = Session::get('impersonate_original_user') !== null;
        if ($isImpersonating) {
            $user = Session::get('user');
            $rawRole = $user['role'] ?? 'unknown';
            $userId = $user['id'] ?? Session::get('user_id');
        } else {
            $rawRole = Session::get('user_role');
            $userId = Session::get('user_id');
        }

        $accessScope = $this->getExportAccessScope();
        $currentHash = $this->getExportDataHash($campaignId, $accessScope);
        $cache = $this->getExportCache($campaignId, 'reps_excel', $accessScope);

        // Debug : log le scope calculé
        error_log("checkExportCache - user_id: " . $userId . ", role: " . $rawRole . ", scope: " . $accessScope . ", impersonating: " . ($isImpersonating ? 'yes' : 'no'));

        if (!$cache) {
            // Pas de cache
            echo json_encode([
                'status' => 'no_cache',
                'message' => 'Première génération requise',
                'debug_scope' => $accessScope,
                'debug_role' => $rawRole,
                'debug_user_id' => $userId,
                'debug_impersonating' => $isImpersonating
            ]);
        } elseif ($cache['data_hash'] !== $currentHash) {
            // Cache obsolète
            echo json_encode([
                'status' => 'outdated',
                'message' => 'Nouvelles données détectées',
                'cached_at' => $cache['created_at'],
                'file_size' => $cache['file_size'],
                'debug_scope' => $accessScope,
                'debug_role' => $rawRole,
                'debug_impersonating' => $isImpersonating
            ]);
        } else {
            // Cache valide
            echo json_encode([
                'status' => 'valid',
                'message' => 'Fichier en cache',
                'cached_at' => $cache['created_at'],
                'file_size' => $cache['file_size'],
                'debug_scope' => $accessScope,
                'debug_role' => $rawRole,
                'debug_impersonating' => $isImpersonating
            ]);
        }

        exit();
    }

    /**
     * API : Récupère les commandes d'un client pour une campagne (AJAX)
     *
     * @return void
     * @created 2025/12/23
     */
    public function getCustomerOrdersApi(): void
    {
        header('Content-Type: application/json');

        $campaignId = isset($_GET['campaign_id']) ? (int)$_GET['campaign_id'] : 0;
        $customerNumber = $_GET['customer_number'] ?? '';
        $country = $_GET['country'] ?? '';

        if (!$campaignId || !$customerNumber || !$country) {
            echo json_encode([
                'success' => false,
                'error' => 'Paramètres manquants'
            ]);
            exit();
        }

        // Vérifier l'accès à la campagne
        if (!$this->canAccessCampaign($campaignId)) {
            echo json_encode([
                'success' => false,
                'error' => 'Accès non autorisé à cette campagne'
            ]);
            exit();
        }

        // Vérifier l'accès au client (selon le rôle)
        $accessibleCustomerNumbers = StatsAccessHelper::getAccessibleCustomerNumbersOnly();
        if ($accessibleCustomerNumbers !== null && !in_array($customerNumber, $accessibleCustomerNumbers)) {
            echo json_encode([
                'success' => false,
                'error' => 'Accès non autorisé à ce client'
            ]);
            exit();
        }

        try {
            $db = \Core\Database::getInstance();

            // Récupérer les commandes du client pour cette campagne
            $query = "
                SELECT
                    o.id as order_id,
                    o.created_at,
                    o.total_items,
                    o.status
                FROM orders o
                INNER JOIN customers cu ON o.customer_id = cu.id
                WHERE o.campaign_id = :campaign_id
                AND cu.customer_number = :customer_number
                AND cu.country = :country
                AND o.status = 'synced'
                ORDER BY o.created_at DESC
            ";

            $orders = $db->query($query, [
                ':campaign_id' => $campaignId,
                ':customer_number' => $customerNumber,
                ':country' => $country
            ]);

            // Pour chaque commande, récupérer les lignes de commande
            foreach ($orders as &$order) {
                $linesQuery = "
                    SELECT
                        ol.quantity,
                        p.product_code,
                        p.name_fr as product_name,
                        p.image_fr as product_image
                    FROM order_lines ol
                    INNER JOIN products p ON ol.product_id = p.id
                    WHERE ol.order_id = :order_id
                    ORDER BY ol.quantity DESC, p.name_fr ASC
                ";

                $order['lines'] = $db->query($linesQuery, [':order_id' => $order['order_id']]);
                $order['total_quantity'] = array_sum(array_column($order['lines'], 'quantity'));
            }

            // Récupérer les infos du client
            $customerQuery = "
                SELECT company_name
                FROM customers
                WHERE customer_number = :customer_number
                AND country = :country
                LIMIT 1
            ";

            $customerInfo = $db->query($customerQuery, [
                ':customer_number' => $customerNumber,
                ':country' => $country
            ]);

            echo json_encode([
                'success' => true,
                'customer' => [
                    'customer_number' => $customerNumber,
                    'country' => $country,
                    'company_name' => $customerInfo[0]['company_name'] ?? $customerNumber
                ],
                'orders' => $orders,
                'total_orders' => count($orders),
                'total_quantity' => array_sum(array_column($orders, 'total_quantity'))
            ]);

        } catch (\Exception $e) {
            error_log("getCustomerOrdersApi error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'Erreur lors de la récupération des commandes'
            ]);
        }

        exit();
    }
}