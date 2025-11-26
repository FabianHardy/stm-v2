<?php
/**
 * Model Stats - STM v2
 *
 * Gestion des statistiques et rapports
 * Connexion aux tables locales ET externes (trendyblog_sig)
 *
 * @package STM
 * @created 2025/11/25
 */

namespace App\Models;

use Core\Database;
use Core\ExternalDatabase;

class Stats
{
    private $db;
    private $extDb;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->db = Database::getInstance();

        try {
            $this->extDb = ExternalDatabase::getInstance();
        } catch (\Exception $e) {
            error_log("Stats: Impossible de se connecter à la DB externe - " . $e->getMessage());
            $this->extDb = null;
        }
    }

    // ========================================
    // STATISTIQUES GLOBALES
    // ========================================

    /**
     * Récupère les KPIs globaux
     *
     * @param string $dateFrom Date début (Y-m-d)
     * @param string $dateTo Date fin (Y-m-d)
     * @param int|null $campaignId Filtrer par campagne
     * @param string|null $country Filtrer par pays (BE, LU)
     * @return array
     */
    public function getGlobalKPIs(
        string $dateFrom,
        string $dateTo,
        ?int $campaignId = null,
        ?string $country = null,
    ): array {
        $params = [
            ":date_from" => $dateFrom . " 00:00:00",
            ":date_to" => $dateTo . " 23:59:59",
        ];

        $campaignFilter = "";
        $countryFilter = "";

        if ($campaignId) {
            $campaignFilter = " AND o.campaign_id = :campaign_id";
            $params[":campaign_id"] = $campaignId;
        }

        if ($country) {
            $countryFilter = " AND cu.country = :country";
            $params[":country"] = $country;
        }

        // Total commandes
        $queryOrders = "
            SELECT COUNT(DISTINCT o.id) as total_orders,
                   COUNT(DISTINCT o.customer_id) as unique_customers,
                   SUM(o.total_items) as total_items
            FROM orders o
            LEFT JOIN customers cu ON o.customer_id = cu.id
            WHERE o.status = 'validated'
            AND o.created_at BETWEEN :date_from AND :date_to
            {$campaignFilter}
            {$countryFilter}
        ";

        $resultOrders = $this->db->query($queryOrders, $params);

        // Total quantités commandées (somme des order_lines)
        $queryQuantity = "
            SELECT COALESCE(SUM(ol.quantity), 0) as total_quantity
            FROM order_lines ol
            INNER JOIN orders o ON ol.order_id = o.id
            LEFT JOIN customers cu ON o.customer_id = cu.id
            WHERE o.status = 'validated'
            AND o.created_at BETWEEN :date_from AND :date_to
            {$campaignFilter}
            {$countryFilter}
        ";

        $resultQuantity = $this->db->query($queryQuantity, $params);

        // Répartition BE/LU
        $queryCountry = "
            SELECT cu.country,
                   COUNT(DISTINCT o.id) as orders_count,
                   COUNT(DISTINCT o.customer_id) as customers_count,
                   COALESCE(SUM(ol.quantity), 0) as quantity
            FROM orders o
            INNER JOIN customers cu ON o.customer_id = cu.id
            LEFT JOIN order_lines ol ON o.id = ol.order_id
            WHERE o.status = 'validated'
            AND o.created_at BETWEEN :date_from AND :date_to
            {$campaignFilter}
            GROUP BY cu.country
        ";

        // Params sans country filter pour cette requête
        $paramsCountry = [
            ":date_from" => $dateFrom . " 00:00:00",
            ":date_to" => $dateTo . " 23:59:59",
        ];
        if ($campaignId) {
            $paramsCountry[":campaign_id"] = $campaignId;
        }

        $resultCountry = $this->db->query($queryCountry, $paramsCountry);

        $countryStats = ["BE" => 0, "LU" => 0];
        $countryQuantity = ["BE" => 0, "LU" => 0];
        $countryCustomers = ["BE" => 0, "LU" => 0];
        foreach ($resultCountry as $row) {
            $countryStats[$row["country"]] = (int) $row["orders_count"];
            $countryQuantity[$row["country"]] = (int) $row["quantity"];
            $countryCustomers[$row["country"]] = (int) $row["customers_count"];
        }

        return [
            "total_orders" => (int) ($resultOrders[0]["total_orders"] ?? 0),
            "unique_customers" => (int) ($resultOrders[0]["unique_customers"] ?? 0),
            "total_items" => (int) ($resultOrders[0]["total_items"] ?? 0),
            "total_quantity" => (int) ($resultQuantity[0]["total_quantity"] ?? 0),
            "orders_by_country" => $countryStats,
            "quantity_by_country" => $countryQuantity,
            "customers_by_country" => $countryCustomers,
        ];
    }

    /**
     * Évolution quotidienne des commandes
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @param int|null $campaignId
     * @return array
     */
    public function getDailyEvolution(string $dateFrom, string $dateTo, ?int $campaignId = null): array
    {
        $params = [
            ":date_from" => $dateFrom,
            ":date_to" => $dateTo,
        ];

        $campaignFilter = "";
        if ($campaignId) {
            $campaignFilter = " AND o.campaign_id = :campaign_id";
            $params[":campaign_id"] = $campaignId;
        }

        $query = "
            SELECT DATE(o.created_at) as day,
                   COUNT(DISTINCT o.id) as orders_count,
                   COALESCE(SUM(ol.quantity), 0) as quantity
            FROM orders o
            LEFT JOIN order_lines ol ON o.id = ol.order_id
            WHERE o.status = 'validated'
            AND DATE(o.created_at) BETWEEN :date_from AND :date_to
            {$campaignFilter}
            GROUP BY DATE(o.created_at)
            ORDER BY day ASC
        ";

        return $this->db->query($query, $params);
    }

    /**
     * Top produits les plus commandés
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @param int|null $campaignId
     * @param int $limit
     * @return array
     */
    public function getTopProducts(
        string $dateFrom,
        string $dateTo,
        ?int $campaignId = null,
        ?string $country = null,
        int $limit = 10,
    ): array {
        $params = [
            ":date_from" => $dateFrom . " 00:00:00",
            ":date_to" => $dateTo . " 23:59:59",
        ];

        $campaignFilter = "";
        $countryFilter = "";

        if ($campaignId) {
            $campaignFilter = " AND o.campaign_id = :campaign_id";
            $params[":campaign_id"] = $campaignId;
        }

        if ($country) {
            $countryFilter = " AND camp.country = :country";
            $params[":country"] = $country;
        }

        $query = "
            SELECT p.id, p.product_code, p.name_fr as product_name,
                   camp.name as campaign_name, camp.country as campaign_country,
                   SUM(ol.quantity) as total_quantity,
                   COUNT(DISTINCT o.id) as orders_count
            FROM order_lines ol
            INNER JOIN orders o ON ol.order_id = o.id
            INNER JOIN products p ON ol.product_id = p.id
            INNER JOIN campaigns camp ON o.campaign_id = camp.id
            WHERE o.status = 'validated'
            AND o.created_at BETWEEN :date_from AND :date_to
            {$campaignFilter}
            {$countryFilter}
            GROUP BY p.id, camp.id
            ORDER BY total_quantity DESC
            LIMIT {$limit}
        ";

        return $this->db->query($query, $params);
    }

    /**
     * Statistiques par cluster commercial
     *
     * Logique :
     * 1. DB locale : orders → customers → customer_number + country + quantités
     * 2. DB externe : CLL (via CLL_NCLIXX) → IDE_REP
     * 3. DB externe : REPCLU → CLU
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @param int|null $campaignId
     * @param string|null $country Filtre par pays (BE, LU)
     * @return array ['cluster_name' => ['quantity' => X, 'customers' => Y], ...]
     */
    public function getStatsByCluster(
        string $dateFrom,
        string $dateTo,
        ?int $campaignId = null,
        ?string $country = null,
    ): array {
        // Si pas de connexion externe, retourner vide
        if (!$this->extDb) {
            return [];
        }

        $params = [
            ":date_from" => $dateFrom . " 00:00:00",
            ":date_to" => $dateTo . " 23:59:59",
        ];

        $campaignFilter = "";
        $countryFilter = "";

        if ($campaignId) {
            $campaignFilter = " AND o.campaign_id = :campaign_id";
            $params[":campaign_id"] = $campaignId;
        }

        if ($country) {
            $countryFilter = " AND cu.country = :country";
            $params[":country"] = $country;
        }

        // Étape 1 : Récupérer les stats par customer_number et country depuis la DB locale
        $query = "
            SELECT cu.customer_number, cu.country,
                   COUNT(DISTINCT o.customer_id) as customer_count,
                   COUNT(DISTINCT o.id) as orders_count,
                   COALESCE(SUM(ol.quantity), 0) as total_quantity
            FROM orders o
            INNER JOIN customers cu ON o.customer_id = cu.id
            LEFT JOIN order_lines ol ON o.id = ol.order_id
            WHERE o.status = 'validated'
            AND o.created_at BETWEEN :date_from AND :date_to
            {$campaignFilter}
            {$countryFilter}
            GROUP BY cu.customer_number, cu.country
        ";

        $customerStats = $this->db->query($query, $params);

        if (empty($customerStats)) {
            return [];
        }

        // Séparer par pays
        $customersBE = [];
        $customersLU = [];
        $statsByCustomer = [];

        foreach ($customerStats as $row) {
            $key = $row["customer_number"] . "_" . $row["country"];
            $statsByCustomer[$key] = [
                "quantity" => (int) $row["total_quantity"],
                "orders" => (int) $row["orders_count"],
                "count" => 1,
            ];

            if ($row["country"] === "BE") {
                $customersBE[] = $row["customer_number"];
            } else {
                $customersLU[] = $row["customer_number"];
            }
        }

        // Étape 2 : Récupérer les clusters depuis la DB externe
        $clustersByCustomer = [];

        // Pour la Belgique
        if (!empty($customersBE)) {
            $clustersByCustomer = array_merge($clustersByCustomer, $this->getClusterForCustomers($customersBE, "BE"));
        }

        // Pour le Luxembourg
        if (!empty($customersLU)) {
            $clustersByCustomer = array_merge($clustersByCustomer, $this->getClusterForCustomers($customersLU, "LU"));
        }

        // Étape 3 : Agréger par cluster
        $clusterStats = [];

        foreach ($statsByCustomer as $key => $stats) {
            $cluster = $clustersByCustomer[$key] ?? "Non défini";

            if (!isset($clusterStats[$cluster])) {
                $clusterStats[$cluster] = [
                    "quantity" => 0,
                    "orders" => 0,
                    "customers" => 0,
                ];
            }

            $clusterStats[$cluster]["quantity"] += $stats["quantity"];
            $clusterStats[$cluster]["orders"] += $stats["orders"];
            $clusterStats[$cluster]["customers"] += $stats["count"];
        }

        return $clusterStats;
    }

    /**
     * Récupère le cluster pour une liste de clients depuis la DB externe
     *
     * Jointures : CLL → REPCLU → CLU
     *
     * @param array $customerNumbers Liste des numéros clients
     * @param string $country BE ou LU
     * @return array ['customerNumber_country' => 'clusterName', ...]
     */
    private function getClusterForCustomers(array $customerNumbers, string $country): array
    {
        if (empty($customerNumbers) || !$this->extDb) {
            return [];
        }

        $tableClient = $country === "BE" ? "BE_CLL" : "LU_CLL";
        $tableRepClu = $country === "BE" ? "BE_REPCLU" : "LU_REPCLU";
        $tableClu = $country === "BE" ? "BE_CLU" : "LU_CLU";

        // Créer les placeholders pour la requête IN
        $placeholders = [];
        $params = [];
        foreach ($customerNumbers as $i => $num) {
            $placeholders[] = ":num{$i}";
            $params[":num{$i}"] = $num;
        }
        $inClause = implode(",", $placeholders);

        // Requête avec jointure CLL → REPCLU → CLU
        $query = "
            SELECT c.CLL_NCLIXX as customer_number, clu.CLU_LIB1 as cluster_name
            FROM {$tableClient} c
            LEFT JOIN {$tableRepClu} rc ON c.IDE_REP = rc.IDE_REP
            LEFT JOIN {$tableClu} clu ON rc.IDE_CLU = clu.IDE_CLU
            WHERE c.CLL_NCLIXX IN ({$inClause})
        ";

        try {
            $results = $this->extDb->query($query, $params);

            $clusterMap = [];
            foreach ($results as $row) {
                $key = $row["customer_number"] . "_" . $country;
                $clusterMap[$key] = $row["cluster_name"] ?: "Non défini";
            }

            return $clusterMap;
        } catch (\Exception $e) {
            error_log("Stats::getClusterForCustomers error: " . $e->getMessage());
            return [];
        }
    }

    // ========================================
    // STATISTIQUES PAR CAMPAGNE
    // ========================================

    /**
     * Stats détaillées pour une campagne
     *
     * @param int $campaignId
     * @return array
     */
    public function getCampaignStats(int $campaignId): array
    {
        // Infos campagne
        $campaign = $this->db->query("SELECT * FROM campaigns WHERE id = :id", [":id" => $campaignId]);

        if (empty($campaign)) {
            return ["error" => "Campagne introuvable"];
        }

        $campaign = $campaign[0];

        // Calculer le statut dynamiquement selon les dates
        $today = date("Y-m-d");
        $startDate = $campaign["start_date"] ?? null;
        $endDate = $campaign["end_date"] ?? null;

        if ($startDate && $endDate) {
            if ($today < $startDate) {
                $campaign["status"] = "scheduled";
            } elseif ($today > $endDate) {
                $campaign["status"] = "ended";
            } else {
                $campaign["status"] = "active";
            }
        }

        // Stats commandes
        $queryOrders = "
            SELECT COUNT(DISTINCT o.id) as total_orders,
                   COUNT(DISTINCT o.customer_id) as customers_ordered,
                   COALESCE(SUM(ol.quantity), 0) as total_quantity
            FROM orders o
            LEFT JOIN order_lines ol ON o.id = ol.order_id
            WHERE o.campaign_id = :campaign_id
            AND o.status = 'validated'
        ";

        $ordersStats = $this->db->query($queryOrders, [":campaign_id" => $campaignId]);

        // Stats par pays
        $queryCountry = "
            SELECT cu.country,
                   COUNT(DISTINCT o.id) as orders_count,
                   COUNT(DISTINCT o.customer_id) as customers_count,
                   COALESCE(SUM(ol.quantity), 0) as quantity
            FROM orders o
            INNER JOIN customers cu ON o.customer_id = cu.id
            LEFT JOIN order_lines ol ON o.id = ol.order_id
            WHERE o.campaign_id = :campaign_id
            AND o.status = 'validated'
            GROUP BY cu.country
        ";

        $countryStats = $this->db->query($queryCountry, [":campaign_id" => $campaignId]);

        // Formater par pays
        $byCountry = [
            "BE" => ["orders" => 0, "customers" => 0, "quantity" => 0],
            "LU" => ["orders" => 0, "customers" => 0, "quantity" => 0],
        ];
        foreach ($countryStats as $row) {
            $byCountry[$row["country"]] = [
                "orders" => (int) $row["orders_count"],
                "customers" => (int) $row["customers_count"],
                "quantity" => (int) $row["quantity"],
            ];
        }

        // Clients éligibles
        $eligibleCustomers = $this->getEligibleCustomersCount($campaignId, $campaign);

        return [
            "campaign" => $campaign,
            "total_orders" => (int) ($ordersStats[0]["total_orders"] ?? 0),
            "customers_ordered" => (int) ($ordersStats[0]["customers_ordered"] ?? 0),
            "total_quantity" => (int) ($ordersStats[0]["total_quantity"] ?? 0),
            "eligible_customers" => $eligibleCustomers,
            "participation_rate" =>
                $eligibleCustomers > 0
                    ? round(($ordersStats[0]["customers_ordered"] / $eligibleCustomers) * 100, 1)
                    : 0,
            "by_country" => $byCountry,
        ];
    }

    /**
     * Compte le nombre de clients éligibles pour une campagne
     *
     * @param int $campaignId
     * @param array $campaign
     * @return int|string
     */
    private function getEligibleCustomersCount(int $campaignId, array $campaign)
    {
        $mode = $campaign["customer_assignment_mode"] ?? "automatic";
        $country = $campaign["country"] ?? "BE";

        if ($mode === "manual") {
            // Compter dans campaign_customers
            $result = $this->db->query(
                "SELECT COUNT(*) as total FROM campaign_customers WHERE campaign_id = :id AND is_authorized = 1",
                [":id" => $campaignId],
            );
            return (int) ($result[0]["total"] ?? 0);
        }

        // Mode automatic ou protected : tous les clients du/des pays
        if (!$this->extDb) {
            return "N/A";
        }

        try {
            $total = 0;

            if ($country === "BE" || $country === "BOTH") {
                $result = $this->extDb->query("SELECT COUNT(*) as total FROM BE_CLL");
                $total += (int) ($result[0]["total"] ?? 0);
            }

            if ($country === "LU" || $country === "BOTH") {
                $result = $this->extDb->query("SELECT COUNT(*) as total FROM LU_CLL");
                $total += (int) ($result[0]["total"] ?? 0);
            }

            return $total;
        } catch (\Exception $e) {
            error_log("Stats::getEligibleCustomersCount error: " . $e->getMessage());
            return "N/A";
        }
    }

    /**
     * Produits vendus pour une campagne
     *
     * @param int $campaignId
     * @return array
     */
    public function getCampaignProducts(int $campaignId): array
    {
        $query = "
            SELECT p.id, p.product_code, p.name_fr as product_name,
                   COALESCE(SUM(ol.quantity), 0) as quantity_sold,
                   COUNT(DISTINCT o.id) as orders_count,
                   COUNT(DISTINCT o.customer_id) as customers_count
            FROM products p
            LEFT JOIN order_lines ol ON p.id = ol.product_id
            LEFT JOIN orders o ON ol.order_id = o.id AND o.status = 'validated'
            WHERE p.campaign_id = :campaign_id
            AND p.is_active = 1
            GROUP BY p.id
            ORDER BY quantity_sold DESC
        ";

        return $this->db->query($query, [":campaign_id" => $campaignId]);
    }

    /**
     * Clients n'ayant pas commandé pour une campagne
     *
     * @param int $campaignId
     * @param int $limit
     * @return array
     */
    public function getCustomersNotOrdered(int $campaignId, int $limit = 100): array
    {
        // Récupérer les infos de la campagne
        $campaign = $this->db->query("SELECT * FROM campaigns WHERE id = :id", [":id" => $campaignId]);

        if (empty($campaign)) {
            return [];
        }

        $campaign = $campaign[0];
        $mode = $campaign["customer_assignment_mode"] ?? "automatic";
        $country = $campaign["country"] ?? "BE";

        // Récupérer les clients qui ONT commandé
        $orderedCustomers = $this->db->query(
            "SELECT DISTINCT cu.customer_number, cu.country
             FROM orders o
             INNER JOIN customers cu ON o.customer_id = cu.id
             WHERE o.campaign_id = :id AND o.status = 'validated'",
            [":id" => $campaignId],
        );

        $orderedNumbers = [];
        foreach ($orderedCustomers as $row) {
            $orderedNumbers[$row["country"]][] = $row["customer_number"];
        }

        if ($mode === "manual") {
            // Mode manuel : liste depuis campaign_customers
            $eligibleCustomers = $this->db->query(
                "SELECT cc.customer_number, cc.country, cu.company_name, cu.rep_name
                 FROM campaign_customers cc
                 LEFT JOIN customers cu ON cc.customer_id = cu.id
                 WHERE cc.campaign_id = :id AND cc.is_authorized = 1
                 LIMIT :limit",
                [":id" => $campaignId, ":limit" => $limit * 2], // Récupérer plus pour filtrer
            );

            // Filtrer ceux qui n'ont pas commandé
            $notOrdered = [];
            foreach ($eligibleCustomers as $cust) {
                $inOrdered =
                    isset($orderedNumbers[$cust["country"]]) &&
                    in_array($cust["customer_number"], $orderedNumbers[$cust["country"]]);
                if (!$inOrdered) {
                    $notOrdered[] = $cust;
                    if (count($notOrdered) >= $limit) {
                        break;
                    }
                }
            }

            return $notOrdered;
        }

        // Mode automatic/protected : lire depuis DB externe
        if (!$this->extDb) {
            return [];
        }

        $notOrdered = [];

        try {
            if ($country === "BE" || $country === "BOTH") {
                $orderedBE = $orderedNumbers["BE"] ?? [];
                $clients = $this->extDb->query(
                    "SELECT CLL_NCLIXX as customer_number, CLL_NOM as company_name, IDE_REP as rep_id
                     FROM BE_CLL
                     LIMIT " .
                        $limit * 3, // Récupérer plus pour filtrer
                );

                foreach ($clients as $c) {
                    if (!in_array($c["customer_number"], $orderedBE)) {
                        $c["country"] = "BE";
                        $c["rep_name"] = $this->getRepName($c["rep_id"], "BE");
                        $notOrdered[] = $c;
                        if (count($notOrdered) >= $limit) {
                            break;
                        }
                    }
                }
            }

            if (count($notOrdered) < $limit && ($country === "LU" || $country === "BOTH")) {
                $orderedLU = $orderedNumbers["LU"] ?? [];
                $remaining = $limit - count($notOrdered);

                $clients = $this->extDb->query(
                    "SELECT CLL_NCLIXX as customer_number, CLL_NOM as company_name, IDE_REP as rep_id
                     FROM LU_CLL
                     LIMIT " .
                        $remaining * 3,
                );

                foreach ($clients as $c) {
                    if (!in_array($c["customer_number"], $orderedLU)) {
                        $c["country"] = "LU";
                        $c["rep_name"] = $this->getRepName($c["rep_id"], "LU");
                        $notOrdered[] = $c;
                        if (count($notOrdered) >= $limit) {
                            break;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            error_log("Stats::getCustomersNotOrdered error: " . $e->getMessage());
        }

        return $notOrdered;
    }

    // ========================================
    // STATISTIQUES PAR COMMERCIAL
    // ========================================

    /**
     * Liste des représentants avec leurs stats
     *
     * @param string|null $country
     * @param int|null $campaignId
     * @return array
     */
    public function getRepStats(?string $country = null, ?int $campaignId = null): array
    {
        if (!$this->extDb) {
            return [];
        }

        $reps = [];

        try {
            // Récupérer les reps BE avec le nom du cluster via BE_REPCLU → BE_CLU
            if (!$country || $country === "BE") {
                $beReps = $this->extDb->query(
                    "SELECT r.IDE_REP, r.REP_PRENOM, r.REP_NOM, r.REP_EMAIL,
                            COALESCE(c.CLU_LIB1, 'Non défini') as cluster_name
                     FROM BE_REP r
                     LEFT JOIN BE_REPCLU rc ON r.IDE_REP = rc.IDE_REP
                     LEFT JOIN BE_CLU c ON rc.IDE_CLU = c.IDE_CLU
                     ORDER BY c.CLU_LIB1, r.REP_NOM",
                );

                foreach ($beReps as $rep) {
                    $reps[] = [
                        "id" => $rep["IDE_REP"],
                        "name" => trim($rep["REP_PRENOM"] . " " . $rep["REP_NOM"]),
                        "email" => $rep["REP_EMAIL"],
                        "cluster" => $rep["cluster_name"] ?: "Non défini",
                        "country" => "BE",
                    ];
                }
            }

            // Récupérer les reps LU avec le nom du cluster via LU_REPCLU → LU_CLU
            if (!$country || $country === "LU") {
                $luReps = $this->extDb->query(
                    "SELECT r.IDE_REP, r.REP_PRENOM, r.REP_NOM, r.REP_EMAIL,
                            COALESCE(c.CLU_LIB1, 'Non défini') as cluster_name
                     FROM LU_REP r
                     LEFT JOIN LU_REPCLU rc ON r.IDE_REP = rc.IDE_REP
                     LEFT JOIN LU_CLU c ON rc.IDE_CLU = c.IDE_CLU
                     ORDER BY c.CLU_LIB1, r.REP_NOM",
                );

                foreach ($luReps as $rep) {
                    $reps[] = [
                        "id" => $rep["IDE_REP"],
                        "name" => trim($rep["REP_PRENOM"] . " " . $rep["REP_NOM"]),
                        "email" => $rep["REP_EMAIL"],
                        "cluster" => $rep["cluster_name"] ?: "Non défini",
                        "country" => "LU",
                    ];
                }
            }

            // Enrichir avec les stats de commandes
            foreach ($reps as &$rep) {
                $rep["stats"] = $this->getRepOrderStats($rep["id"], $rep["country"], $campaignId);
                $rep["total_clients"] = $this->getRepClientsCount($rep["id"], $rep["country"]);
            }
        } catch (\Exception $e) {
            error_log("Stats::getRepStats error: " . $e->getMessage());
        }

        // Trier par quantité commandée
        usort($reps, function ($a, $b) {
            return ($b["stats"]["total_quantity"] ?? 0) - ($a["stats"]["total_quantity"] ?? 0);
        });

        return $reps;
    }

    /**
     * Stats de commandes pour un représentant
     *
     * @param string $repId
     * @param string $country
     * @param int|null $campaignId
     * @return array
     */
    private function getRepOrderStats(string $repId, string $country, ?int $campaignId = null): array
    {
        // D'abord, récupérer les numéros clients du représentant depuis la DB externe
        if (!$this->extDb) {
            return ["orders_count" => 0, "customers_ordered" => 0, "total_quantity" => 0];
        }

        try {
            $tableClient = $country === "BE" ? "BE_CLL" : "LU_CLL";
            $clientsResult = $this->extDb->query(
                "SELECT CLL_NCLIXX as customer_number FROM {$tableClient} WHERE IDE_REP = :rep_id",
                [":rep_id" => $repId],
            );

            if (empty($clientsResult)) {
                return ["orders_count" => 0, "customers_ordered" => 0, "total_quantity" => 0];
            }

            // Construire la liste des numéros clients
            $customerNumbers = array_column($clientsResult, "customer_number");

            // Échapper les numéros pour la requête IN
            $escapedNumbers = array_map(function ($num) {
                return "'" . addslashes($num) . "'";
            }, $customerNumbers);
            $inClause = implode(",", $escapedNumbers);

            // Requête sur la DB locale pour les commandes
            $params = [":country" => $country];

            $campaignFilter = "";
            if ($campaignId) {
                $campaignFilter = " AND o.campaign_id = :campaign_id";
                $params[":campaign_id"] = $campaignId;
            }

            $query = "
                SELECT COUNT(DISTINCT o.id) as orders_count,
                       COUNT(DISTINCT o.customer_id) as customers_ordered,
                       COALESCE(SUM(ol.quantity), 0) as total_quantity
                FROM orders o
                INNER JOIN customers cu ON o.customer_id = cu.id
                LEFT JOIN order_lines ol ON o.id = ol.order_id
                WHERE cu.customer_number IN ({$inClause})
                AND cu.country = :country
                AND o.status = 'validated'
                {$campaignFilter}
            ";

            $result = $this->db->query($query, $params);

            return [
                "orders_count" => (int) ($result[0]["orders_count"] ?? 0),
                "customers_ordered" => (int) ($result[0]["customers_ordered"] ?? 0),
                "total_quantity" => (int) ($result[0]["total_quantity"] ?? 0),
            ];
        } catch (\Exception $e) {
            error_log("Stats::getRepOrderStats error: " . $e->getMessage());
            return ["orders_count" => 0, "customers_ordered" => 0, "total_quantity" => 0];
        }
    }

    /**
     * Nombre de clients d'un représentant (depuis DB externe)
     *
     * @param string $repId
     * @param string $country
     * @return int
     */
    private function getRepClientsCount(string $repId, string $country): int
    {
        if (!$this->extDb) {
            return 0;
        }

        try {
            $table = $country === "BE" ? "BE_CLL" : "LU_CLL";
            $result = $this->extDb->query("SELECT COUNT(*) as total FROM {$table} WHERE IDE_REP = :rep_id", [
                ":rep_id" => $repId,
            ]);

            return (int) ($result[0]["total"] ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Récupère le cluster d'un représentant via REPCLU → CLU
     *
     * @param string $repId
     * @param string $country
     * @return string|null
     */
    private function getRepCluster(string $repId, string $country): ?string
    {
        if (!$this->extDb) {
            return null;
        }

        try {
            $tableRepClu = $country === "BE" ? "BE_REPCLU" : "LU_REPCLU";
            $tableClu = $country === "BE" ? "BE_CLU" : "LU_CLU";

            $result = $this->extDb->query(
                "SELECT c.CLU_LIB1 as cluster_name
                 FROM {$tableRepClu} rc
                 LEFT JOIN {$tableClu} c ON rc.IDE_CLU = c.IDE_CLU
                 WHERE rc.IDE_REP = :rep_id",
                [":rep_id" => $repId],
            );

            return $result[0]["cluster_name"] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Récupère le nom d'un représentant
     *
     * @param string $repId
     * @param string $country
     * @return string|null
     */
    private function getRepName(?string $repId, string $country): ?string
    {
        if (!$this->extDb || empty($repId)) {
            return null;
        }

        try {
            $table = $country === "BE" ? "BE_REP" : "LU_REP";
            $result = $this->extDb->query("SELECT REP_PRENOM, REP_NOM FROM {$table} WHERE IDE_REP = :rep_id", [
                ":rep_id" => $repId,
            ]);

            if (!empty($result)) {
                return trim($result[0]["REP_PRENOM"] . " " . $result[0]["REP_NOM"]);
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Détails des clients d'un représentant
     *
     * @param string $repId
     * @param string $country
     * @param int|null $campaignId
     * @return array
     */
    public function getRepClients(string $repId, string $country, ?int $campaignId = null): array
    {
        if (!$this->extDb) {
            return [];
        }

        try {
            $table = $country === "BE" ? "BE_CLL" : "LU_CLL";

            // Récupérer tous les clients du rep
            $clients = $this->extDb->query(
                "SELECT CLL_NCLIXX as customer_number, CLL_NOM as company_name,
                        CLL_LOCALITE as city
                 FROM {$table}
                 WHERE IDE_REP = :rep_id
                 ORDER BY CLL_NOM",
                [":rep_id" => $repId],
            );

            // Récupérer les clients qui ont commandé
            $params = [":country" => $country];
            $campaignFilter = "";

            if ($campaignId) {
                $campaignFilter = " AND o.campaign_id = :campaign_id";
                $params[":campaign_id"] = $campaignId;
            }

            $orderedResult = $this->db->query(
                "SELECT DISTINCT cu.customer_number,
                        SUM(ol.quantity) as total_quantity,
                        COUNT(DISTINCT o.id) as orders_count
                 FROM orders o
                 INNER JOIN customers cu ON o.customer_id = cu.id
                 LEFT JOIN order_lines ol ON o.id = ol.order_id
                 WHERE cu.country = :country
                 AND o.status = 'validated'
                 {$campaignFilter}
                 GROUP BY cu.customer_number",
                $params,
            );

            $orderedMap = [];
            foreach ($orderedResult as $row) {
                $orderedMap[$row["customer_number"]] = [
                    "quantity" => (int) $row["total_quantity"],
                    "orders" => (int) $row["orders_count"],
                ];
            }

            // Enrichir les clients avec le statut de commande
            foreach ($clients as &$client) {
                $client["country"] = $country;
                if (isset($orderedMap[$client["customer_number"]])) {
                    $client["has_ordered"] = true;
                    $client["total_quantity"] = $orderedMap[$client["customer_number"]]["quantity"];
                    $client["orders_count"] = $orderedMap[$client["customer_number"]]["orders"];
                } else {
                    $client["has_ordered"] = false;
                    $client["total_quantity"] = 0;
                    $client["orders_count"] = 0;
                }
            }

            return $clients;
        } catch (\Exception $e) {
            error_log("Stats::getRepClients error: " . $e->getMessage());
            return [];
        }
    }

    // ========================================
    // LISTES ET HELPERS
    // ========================================

    /**
     * Liste des campagnes pour les filtres
     * Calcule le statut automatiquement basé sur les dates
     *
     * @return array
     */
    public function getCampaignsList(): array
    {
        $campaigns = $this->db->query(
            "SELECT id, name, title_fr, country, status, start_date, end_date
             FROM campaigns
             ORDER BY start_date DESC",
        );

        // Calculer le statut dynamiquement selon les dates
        $today = date("Y-m-d");

        foreach ($campaigns as &$campaign) {
            $startDate = $campaign["start_date"] ?? null;
            $endDate = $campaign["end_date"] ?? null;

            if ($startDate && $endDate) {
                if ($today < $startDate) {
                    $campaign["status"] = "scheduled"; // Programmée
                } elseif ($today > $endDate) {
                    $campaign["status"] = "ended"; // Terminée
                } else {
                    $campaign["status"] = "active"; // En cours
                }
            }
        }

        return $campaigns;
    }

    /**
     * Liste des clusters (depuis DB externe)
     *
     * @return array
     */
    public function getClustersList(): array
    {
        if (!$this->extDb) {
            return [];
        }

        try {
            $clusters = [];

            $beResult = $this->extDb->query(
                "SELECT DISTINCT REP_CLU FROM BE_REP WHERE REP_CLU IS NOT NULL AND REP_CLU != '' ORDER BY REP_CLU",
            );
            foreach ($beResult as $row) {
                $clusters[] = $row["REP_CLU"];
            }

            $luResult = $this->extDb->query(
                "SELECT DISTINCT REP_CLU FROM LU_REP WHERE REP_CLU IS NOT NULL AND REP_CLU != '' ORDER BY REP_CLU",
            );
            foreach ($luResult as $row) {
                if (!in_array($row["REP_CLU"], $clusters)) {
                    $clusters[] = $row["REP_CLU"];
                }
            }

            sort($clusters);
            return $clusters;
        } catch (\Exception $e) {
            return [];
        }
    }
}
