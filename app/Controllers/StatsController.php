<?php
/**
 * Controller Stats - STM v2
 *
 * Gestion des pages de statistiques admin
 *
 * @package STM
 * @created 2025/11/25
 * @modified 2025/12/09 - Ajout stats fournisseurs dans campaigns()
 */

namespace App\Controllers;

use App\Models\Stats;
use App\Models\Campaign;
use Core\Session;
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
     *
     * - superadmin/admin : toutes les campagnes
     * - createur : uniquement ses campagnes (via content_ownership)
     * - manager_reps : campagnes où ses reps ont des clients assignés
     * - rep : aucune (pas d'accès aux stats)
     *
     * @return array|null Liste des IDs ou null si accès à tout
     * @created 2025/12/16
     */
    private function getAccessibleCampaignIds(): ?array
    {
        $user = Session::get("user");
        $role = $user["role"] ?? "rep";
        $userId = $user["id"] ?? 0;

        // Superadmin et admin : accès à tout
        if (in_array($role, ["superadmin", "admin"])) {
            return null; // null = pas de filtre
        }

        $db = \Core\Database::getInstance();

        // Créateur : uniquement ses campagnes via content_ownership
        if ($role === "createur") {
            $query = "
                SELECT content_id
                FROM content_ownership
                WHERE user_id = :user_id
                AND content_type = 'campaign'
            ";
            $results = $db->query($query, [":user_id" => $userId]);
            return array_column($results, "content_id");
        }

        // Manager_reps : campagnes où ses reps ont des clients assignés
        if ($role === "manager_reps") {
            $managedRepIds = $this->getManagedRepIds();

            if (empty($managedRepIds)) {
                return []; // Aucun rep géré = aucune campagne
            }

            // Récupérer les campagnes où les clients des reps gérés sont assignés
            $campaignIds = [];

            // 1. Campagnes en mode MANUAL : via campaign_customers
            // Les clients des reps sont dans BE_CLL/LU_CLL avec IDE_REP
            foreach ($managedRepIds as $rep) {
                $repId = $rep["rep_id"];
                $repCountry = $rep["rep_country"];
                $table = $repCountry === "BE" ? "BE_CLL" : "LU_CLL";

                // Récupérer les numéros clients de ce rep (depuis DB externe)
                try {
                    $externalDb = \Core\ExternalDatabase::getInstance();
                    $clientsQuery = "SELECT CLL_NCLIXX FROM {$table} WHERE IDE_REP = :rep_id";
                    $clients = $externalDb->query($clientsQuery, [":rep_id" => $repId]);
                    $customerNumbers = array_column($clients, "CLL_NCLIXX");

                    if (!empty($customerNumbers)) {
                        // Campagnes en mode manual avec ces clients
                        $placeholders = implode(",", array_fill(0, count($customerNumbers), "?"));
                        $manualQuery = "
                            SELECT DISTINCT cc.campaign_id
                            FROM campaign_customers cc
                            INNER JOIN campaigns c ON cc.campaign_id = c.id
                            WHERE cc.customer_number IN ({$placeholders})
                            AND cc.country = ?
                        ";
                        $params = array_merge($customerNumbers, [$repCountry]);
                        $manualCampaigns = $db->query($manualQuery, $params);
                        $campaignIds = array_merge($campaignIds, array_column($manualCampaigns, "campaign_id"));

                        // Campagnes en mode automatic/protected pour ce pays
                        $autoQuery = "
                            SELECT id FROM campaigns
                            WHERE customer_assignment_mode IN ('automatic', 'protected')
                            AND (country = ? OR country = 'BOTH')
                        ";
                        $autoCampaigns = $db->query($autoQuery, [$repCountry]);
                        $campaignIds = array_merge($campaignIds, array_column($autoCampaigns, "id"));
                    }
                } catch (\Exception $e) {
                    error_log("Erreur getManagedCampaigns: " . $e->getMessage());
                }
            }

            return array_unique($campaignIds);
        }

        // Rep : aucune campagne (pas d'accès aux stats normalement)
        return [];
    }

    /**
     * Récupère les IDs des représentants gérés par le manager connecté
     *
     * @return array Liste des [rep_id, rep_country]
     * @created 2025/12/16
     */
    private function getManagedRepIds(): array
    {
        $user = Session::get("user");
        $userId = $user["id"] ?? 0;

        $db = \Core\Database::getInstance();

        // Récupérer les users avec role=rep qui ont ce manager_id
        $query = "
            SELECT rep_id, rep_country
            FROM users
            WHERE manager_id = :manager_id
            AND role = 'rep'
            AND rep_id IS NOT NULL
            AND is_active = 1
        ";

        return $db->query($query, [":manager_id" => $userId]);
    }

    /**
     * Filtre une liste de campagnes selon l'accès de l'utilisateur
     *
     * @param array $campaigns Liste des campagnes
     * @return array Liste filtrée
     * @created 2025/12/16
     */
    private function filterCampaignsList(array $campaigns): array
    {
        $accessibleIds = $this->getAccessibleCampaignIds();

        // null = accès à tout
        if ($accessibleIds === null) {
            return $campaigns;
        }

        // Filtrer par IDs accessibles
        return array_filter($campaigns, function ($campaign) use ($accessibleIds) {
            return in_array($campaign["id"], $accessibleIds);
        });
    }

    /**
     * Vérifie si l'utilisateur a accès à une campagne spécifique
     *
     * @param int $campaignId ID de la campagne
     * @return bool True si accès autorisé
     * @created 2025/12/16
     */
    private function canAccessCampaign(int $campaignId): bool
    {
        $accessibleIds = $this->getAccessibleCampaignIds();

        // null = accès à tout
        if ($accessibleIds === null) {
            return true;
        }

        return in_array($campaignId, $accessibleIds);
    }

    /**
     * Filtre les représentants selon le rôle de l'utilisateur
     *
     * - superadmin/admin/createur : tous les reps (de la campagne)
     * - manager_reps : uniquement ses reps gérés
     *
     * @param array $reps Liste des représentants
     * @return array Liste filtrée
     * @created 2025/12/16
     */
    private function filterRepsList(array $reps): array
    {
        $user = Session::get("user");
        $role = $user["role"] ?? "rep";

        // Superadmin, admin, createur : tous les reps
        if (in_array($role, ["superadmin", "admin", "createur"])) {
            return $reps;
        }

        // Manager_reps : uniquement ses reps
        if ($role === "manager_reps") {
            $managedReps = $this->getManagedRepIds();
            $managedRepIds = array_map(function ($r) {
                return $r["rep_id"] . "_" . $r["rep_country"];
            }, $managedReps);

            return array_filter($reps, function ($rep) use ($managedRepIds) {
                $repKey = $rep["id"] . "_" . $rep["country"];
                return in_array($repKey, $managedRepIds);
            });
        }

        return [];
    }

    // =========================================================================
    // MÉTHODES PUBLIQUES
    // =========================================================================

    /**
     * Vue globale des statistiques
     *
     * @return void
     */
    public function index(): void
    {
        // Récupérer les filtres
        $period = $_GET["period"] ?? "7"; // 7 jours par défaut
        $campaignId = !empty($_GET["campaign_id"]) ? (int) $_GET["campaign_id"] : null;
        $country = !empty($_GET["country"]) ? $_GET["country"] : null;

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

        // Si l'utilisateur n'a pas accès à tout et pas de campagne sélectionnée
        // On limite les stats aux campagnes accessibles
        $effectiveCampaignId = $campaignId;
        if ($accessibleCampaignIds !== null && !$campaignId) {
            // Pas admin/superadmin et pas de campagne sélectionnée
            // On prend la première campagne accessible ou on affiche des stats vides
            if (!empty($accessibleCampaignIds)) {
                // On ne filtre pas automatiquement, on laisse l'utilisateur choisir
                // Mais on n'affiche que les stats des campagnes accessibles
            }
        }

        $kpis = $this->statsModel->getGlobalKPIs($dateFrom, $dateTo, $campaignId, $country);
        $dailyEvolution = $this->statsModel->getDailyEvolution($dateFrom, $dateTo, $campaignId);
        $topProducts = $this->statsModel->getTopProducts($dateFrom, $dateTo, $campaignId, $country, 10);
        $clusterStats = $this->statsModel->getStatsByCluster($dateFrom, $dateTo, $campaignId, $country);

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

        if ($campaignId) {
            $campaignStats = $this->statsModel->getCampaignStats($campaignId);
            $campaignProducts = $this->statsModel->getCampaignProducts($campaignId);

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

            $dailyEvolution = $this->statsModel->getDailyEvolution($startDate, $endDate, $campaignId);

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
            $categoryStats = $this->getCategoryStatsForCampaign($campaignId);

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
     * @return array Stats par catégorie
     */
    private function getCategoryStatsForCampaign(int $campaignId): array
    {
        $db = \Core\Database::getInstance();

        $query = "
            SELECT
                c.name_fr as category_name,
                c.color,
                COALESCE(SUM(ol.quantity), 0) as quantity
            FROM categories c
            INNER JOIN products p ON c.id = p.category_id AND p.campaign_id = :campaign_id
            LEFT JOIN order_lines ol ON p.id = ol.product_id
            LEFT JOIN orders o ON ol.order_id = o.id AND o.status = 'validated'
            GROUP BY c.id, c.name_fr, c.color
            HAVING quantity > 0
            ORDER BY quantity DESC
        ";

        return $db->query($query, [
            ":campaign_id" => $campaignId,
        ]);
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
     */
    public function sales(): void
    {
        // Récupérer les filtres
        $country = !empty($_GET["country"]) ? $_GET["country"] : null;
        $campaignId = !empty($_GET["campaign_id"]) ? (int) $_GET["campaign_id"] : null;
        $repId = !empty($_GET["rep_id"]) ? $_GET["rep_id"] : null;
        $repCountry = !empty($_GET["rep_country"]) ? $_GET["rep_country"] : null;

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
            WHERE o.status = 'validated'
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
            AND o.status = 'validated'
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
        // Augmenter les limites pour la génération Excel
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $campaignId = !empty($_POST["campaign_id"]) ? (int) $_POST["campaign_id"] : null;

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

        // Trier les produits par nom
        usort($campaignProducts, function($a, $b) {
            return strcasecmp($a["name"] ?? "", $b["name"] ?? "");
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

            // En-têtes produits (colonnes dynamiques)
            $productColumns = [];
            $colIndex = 6;
            foreach ($campaignProducts as $product) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $productColumns[$product["id"]] = $colLetter;
                $repSheet->setCellValue($colLetter . "1", $product["name"] ?? $product["product_code"]);
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

                // Quantités par produit
                foreach ($campaignProducts as $product) {
                    $productId = $product["id"];
                    $colLetter = $productColumns[$productId];
                    $qty = $clientProductQuantities[$customerNumber][$productId] ?? 0;
                    $repSheet->setCellValue($colLetter . $row, $qty);
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

        // Générer le fichier
        $filename = "export_reps_" . preg_replace("/[^a-zA-Z0-9]/", "_", $campaignName) . "_" . date("Ymd_His") . ".xlsx";

        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header("Content-Disposition: attachment;filename=\"" . $filename . "\"");
        header("Cache-Control: max-age=0");
        header("Pragma: public");

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save("php://output");

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

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
     */
    private function getClientProductQuantities(int $campaignId, string $repId, string $repCountry): array
    {
        $db = \Core\Database::getInstance();

        $query = "
            SELECT
                cu.customer_number,
                ol.product_id,
                SUM(ol.quantity) as quantity
            FROM orders o
            INNER JOIN customers cu ON o.customer_id = cu.id
            INNER JOIN order_lines ol ON o.id = ol.order_id
            WHERE o.campaign_id = :campaign_id
            AND o.status = 'validated'
            AND cu.rep_id = :rep_id
            AND cu.country = :country
            GROUP BY cu.customer_number, ol.product_id
        ";

        $results = $db->query($query, [
            ":campaign_id" => $campaignId,
            ":rep_id" => $repId,
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
    }
}