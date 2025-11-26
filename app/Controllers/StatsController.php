<?php
/**
 * Controller Stats - STM v2
 * 
 * Gestion des pages de statistiques admin
 * 
 * @package STM
 * @created 2025/11/25
 */

namespace App\Controllers;

use App\Models\Stats;
use App\Models\Campaign;
use Core\Session;

class StatsController
{
    private Stats $statsModel;
    private Campaign $campaignModel;
    
    /**
     * Constructeur
     */
    public function __construct()
    {
        // Vérifier l'authentification
        if (!Session::get('user')) {
            header('Location: /stm/admin/login');
            exit;
        }
        
        $this->statsModel = new Stats();
        $this->campaignModel = new Campaign();
    }
    
    /**
     * Vue globale des statistiques
     * 
     * @return void
     */
    public function index(): void
    {
        // Récupérer les filtres
        $period = $_GET['period'] ?? '14'; // 7, 14, 30, month
        $campaignId = !empty($_GET['campaign_id']) ? (int)$_GET['campaign_id'] : null;
        $country = !empty($_GET['country']) ? $_GET['country'] : null;
        
        // Calculer les dates selon la période
        $dateTo = date('Y-m-d');
        
        switch ($period) {
            case '7':
                $dateFrom = date('Y-m-d', strtotime('-7 days'));
                $periodLabel = '7 derniers jours';
                break;
            case '30':
                $dateFrom = date('Y-m-d', strtotime('-30 days'));
                $periodLabel = '30 derniers jours';
                break;
            case 'month':
                $dateFrom = date('Y-m-01'); // Premier jour du mois
                $periodLabel = 'Ce mois (' . date('F Y') . ')';
                break;
            case '14':
            default:
                $dateFrom = date('Y-m-d', strtotime('-14 days'));
                $periodLabel = '14 derniers jours';
                break;
        }
        
        // Récupérer les données
        $kpis = $this->statsModel->getGlobalKPIs($dateFrom, $dateTo, $campaignId, $country);
        $dailyEvolution = $this->statsModel->getDailyEvolution($dateFrom, $dateTo, $campaignId);
        $topProducts = $this->statsModel->getTopProducts($dateFrom, $dateTo, $campaignId, 10);
        $clusterStats = $this->statsModel->getStatsByCluster($dateFrom, $dateTo, $campaignId);
        
        // Liste des campagnes pour le filtre
        $campaigns = $this->statsModel->getCampaignsList();
        
        // Préparer les données pour les graphiques
        $chartLabels = [];
        $chartOrders = [];
        $chartQuantity = [];
        
        // Générer toutes les dates de la période
        $currentDate = new \DateTime($dateFrom);
        $endDate = new \DateTime($dateTo);
        $dailyMap = [];
        
        foreach ($dailyEvolution as $row) {
            $dailyMap[$row['day']] = $row;
        }
        
        while ($currentDate <= $endDate) {
            $day = $currentDate->format('Y-m-d');
            $chartLabels[] = $currentDate->format('d/m');
            $chartOrders[] = (int)($dailyMap[$day]['orders_count'] ?? 0);
            $chartQuantity[] = (int)($dailyMap[$day]['quantity'] ?? 0);
            $currentDate->modify('+1 day');
        }
        
        // Données pour le graphique cluster (grouper par cluster)
        $clusterGroups = [];
        foreach ($clusterStats as $row) {
            $cluster = $row['cluster'] ?? 'Non défini';
            if (!isset($clusterGroups[$cluster])) {
                $clusterGroups[$cluster] = ['quantity' => 0, 'orders' => 0, 'customers' => 0];
            }
            $clusterGroups[$cluster]['quantity'] += (int)$row['total_quantity'];
            $clusterGroups[$cluster]['orders'] += (int)$row['orders_count'];
            $clusterGroups[$cluster]['customers'] += (int)$row['customers_count'];
        }
        
        // Variables pour la vue
        $title = 'Statistiques - Vue globale';
        
        require __DIR__ . '/../Views/admin/stats/index.php';
    }
    
    /**
     * Statistiques par campagne
     * 
     * @return void
     */
    public function campaigns(): void
    {
        // Récupérer le filtre campagne
        $campaignId = !empty($_GET['campaign_id']) ? (int)$_GET['campaign_id'] : null;
        
        // Liste des campagnes
        $campaigns = $this->statsModel->getCampaignsList();
        
        // Stats de la campagne sélectionnée
        $campaignStats = null;
        $campaignProducts = [];
        $customersNotOrdered = [];
        
        if ($campaignId) {
            $campaignStats = $this->statsModel->getCampaignStats($campaignId);
            $campaignProducts = $this->statsModel->getCampaignProducts($campaignId);
            $customersNotOrdered = $this->statsModel->getCustomersNotOrdered($campaignId, 50);
        }
        
        $title = 'Statistiques - Par campagne';
        
        require __DIR__ . '/../Views/admin/stats/campaigns.php';
    }
    
    /**
     * Statistiques par commercial
     * 
     * @return void
     */
    public function sales(): void
    {
        // Récupérer les filtres
        $country = !empty($_GET['country']) ? $_GET['country'] : null;
        $campaignId = !empty($_GET['campaign_id']) ? (int)$_GET['campaign_id'] : null;
        $repId = !empty($_GET['rep_id']) ? $_GET['rep_id'] : null;
        $repCountry = !empty($_GET['rep_country']) ? $_GET['rep_country'] : null;
        
        // Liste des campagnes pour le filtre
        $campaigns = $this->statsModel->getCampaignsList();
        
        // Liste des clusters
        $clusters = $this->statsModel->getClustersList();
        
        // Liste des représentants avec leurs stats
        $reps = $this->statsModel->getRepStats($country, $campaignId);
        
        // Détail d'un représentant si sélectionné
        $repDetail = null;
        $repClients = [];
        
        if ($repId && $repCountry) {
            $repClients = $this->statsModel->getRepClients($repId, $repCountry, $campaignId);
            
            // Trouver les infos du rep
            foreach ($reps as $rep) {
                if ($rep['id'] === $repId && $rep['country'] === $repCountry) {
                    $repDetail = $rep;
                    break;
                }
            }
        }
        
        $title = 'Statistiques - Par commercial';
        
        require __DIR__ . '/../Views/admin/stats/sales.php';
    }
    
    /**
     * Page des rapports et exports
     * 
     * @return void
     */
    public function reports(): void
    {
        // Liste des campagnes pour les exports
        $campaigns = $this->statsModel->getCampaignsList();
        
        $title = 'Statistiques - Rapports';
        
        require __DIR__ . '/../Views/admin/stats/reports.php';
    }
    
    /**
     * Export CSV/Excel
     * 
     * @return void
     */
    public function export(): void
    {
        $type = $_POST['type'] ?? 'global';
        $format = $_POST['format'] ?? 'csv';
        $campaignId = !empty($_POST['campaign_id']) ? (int)$_POST['campaign_id'] : null;
        $dateFrom = $_POST['date_from'] ?? date('Y-m-d', strtotime('-14 days'));
        $dateTo = $_POST['date_to'] ?? date('Y-m-d');
        
        $data = [];
        $filename = '';
        $headers = [];
        
        switch ($type) {
            case 'campaign':
                if (!$campaignId) {
                    Session::setFlash('error', 'Veuillez sélectionner une campagne');
                    header('Location: /stm/admin/stats/reports');
                    exit;
                }
                
                // Export des commandes d'une campagne
                $data = $this->getExportCampaignData($campaignId);
                $headers = ['Num_Client', 'Nom', 'Pays', 'Promo_Art', 'Nom_Produit', 'Quantité', 'Email', 'Rep_Name', 'Date_Commande'];
                $filename = 'export_campagne_' . $campaignId . '_' . date('Ymd');
                break;
                
            case 'reps':
                // Export stats par représentant
                $data = $this->getExportRepsData($campaignId);
                $headers = ['Rep_ID', 'Rep_Nom', 'Cluster', 'Pays', 'Nb_Clients', 'Clients_Commandé', 'Taux_Conv', 'Total_Quantité'];
                $filename = 'export_reps_' . date('Ymd');
                break;
                
            case 'not_ordered':
                if (!$campaignId) {
                    Session::setFlash('error', 'Veuillez sélectionner une campagne');
                    header('Location: /stm/admin/stats/reports');
                    exit;
                }
                
                // Export clients n'ayant pas commandé
                $data = $this->statsModel->getCustomersNotOrdered($campaignId, 5000);
                $headers = ['Num_Client', 'Nom', 'Pays', 'Rep_Name'];
                $filename = 'clients_sans_commande_' . $campaignId . '_' . date('Ymd');
                break;
                
            default:
                // Export global
                $data = $this->getExportGlobalData($dateFrom, $dateTo, $campaignId);
                $headers = ['Num_Client', 'Nom', 'Pays', 'Promo_Art', 'Nom_Produit', 'Quantité', 'Rep_Name', 'Cluster', 'Date_Commande'];
                $filename = 'export_global_' . date('Ymd');
                break;
        }
        
        // Générer le fichier
        if ($format === 'csv') {
            $this->exportCSV($data, $headers, $filename);
        } else {
            // Pour Excel, on utilise CSV avec séparateur point-virgule
            $this->exportCSV($data, $headers, $filename, ';');
        }
    }
    
    /**
     * Récupère les données pour export global
     */
    private function getExportGlobalData(string $dateFrom, string $dateTo, ?int $campaignId): array
    {
        $db = \Core\Database::getInstance();
        
        $params = [
            ':date_from' => $dateFrom . ' 00:00:00',
            ':date_to' => $dateTo . ' 23:59:59'
        ];
        
        $campaignFilter = '';
        if ($campaignId) {
            $campaignFilter = ' AND o.campaign_id = :campaign_id';
            $params[':campaign_id'] = $campaignId;
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
        
        return $db->query($query, [':campaign_id' => $campaignId]);
    }
    
    /**
     * Récupère les données pour export représentants
     */
    private function getExportRepsData(?int $campaignId): array
    {
        $reps = $this->statsModel->getRepStats(null, $campaignId);
        
        $data = [];
        foreach ($reps as $rep) {
            $convRate = $rep['total_clients'] > 0 
                ? round(($rep['stats']['customers_ordered'] / $rep['total_clients']) * 100, 1) . '%'
                : '0%';
                
            $data[] = [
                'Rep_ID' => $rep['id'],
                'Rep_Nom' => $rep['name'],
                'Cluster' => $rep['cluster'],
                'Pays' => $rep['country'],
                'Nb_Clients' => $rep['total_clients'],
                'Clients_Commande' => $rep['stats']['customers_ordered'],
                'Taux_Conv' => $convRate,
                'Total_Quantite' => $rep['stats']['total_quantity']
            ];
        }
        
        return $data;
    }
    
    /**
     * Génère et télécharge un fichier CSV
     */
    private function exportCSV(array $data, array $headers, string $filename, string $delimiter = ','): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // BOM UTF-8 pour Excel
        echo "\xEF\xBB\xBF";
        
        $output = fopen('php://output', 'w');
        
        // En-têtes
        fputcsv($output, $headers, $delimiter);
        
        // Données
        foreach ($data as $row) {
            if (is_array($row)) {
                fputcsv($output, array_values($row), $delimiter);
            }
        }
        
        fclose($output);
        exit;
    }
}
