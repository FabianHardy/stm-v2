<?php
/**
 * CampaignController
 *
 * Contrôleur pour la gestion des campagnes promotionnelles
 * Intègre le système de permissions (Phase 5)
 *
 * @created  2025/11/07 10:00
 * @modified 2025/12/10 - Ajout gestion équipe (assignees) pour onglet Équipe
 * @modified 2025/12/12 - Intégration PermissionMiddleware (Phase 5)
 * @modified 2025/12/18 - Utilisation StatsAccessHelper pour filtrage reps/manager_reps
 * @modified 2025/12/19 - Correction comptage actives (is_active=1 + dates)
 * @modified 2026/01/08 - Sprint 14/15 : Ajout show_prices et order_processing_mode dans store/update
 */

namespace App\Controllers;

use App\Models\Campaign;
use App\Helpers\PermissionHelper;
use App\Helpers\StatsAccessHelper;
use Middleware\PermissionMiddleware;
use Core\Database;
use Core\Session;

class CampaignController
{
    private Campaign $campaignModel;

    public function __construct()
    {
        $this->campaignModel = new Campaign();
    }

    /**
     * Afficher la liste de toutes les campagnes avec filtres
     */
    public function index(): void
    {
        // ✅ Permission requise : voir les campagnes
        PermissionMiddleware::require('campaigns.view');

        $filters = [
            "search" => $_GET["search"] ?? "",
            "country" => $_GET["country"] ?? "",
            "status" => $_GET["status"] ?? "",
        ];

        // Filtrer par campagnes accessibles selon le rôle (utilise StatsAccessHelper pour rep/manager_reps)
        $accessibleCampaignIds = StatsAccessHelper::getAccessibleCampaignIds();
        if ($accessibleCampaignIds !== null) {
            $filters['campaign_ids'] = $accessibleCampaignIds;
        }

        // Filtrer aussi par pays accessibles
        $accessibleCountries = StatsAccessHelper::getAccessibleCountries();
        if ($accessibleCountries !== null && empty($filters['country'])) {
            $filters['accessible_countries'] = $accessibleCountries;
        }

        $campaigns = $this->campaignModel->getAll($filters);

        foreach ($campaigns as &$campaign) {
            $campaign["customer_stats"] = $this->campaignModel->getCustomerStats($campaign["id"]);
            $campaign["promotion_count"] = $this->campaignModel->countPromotions($campaign["id"]);
        }
        unset($campaign);

        // Recalculer les stats en fonction des filtres d'accès
        $stats = $this->getFilteredStats($accessibleCampaignIds, $accessibleCountries);

        $total = count($campaigns);
        $perPage = 20;
        $currentPage = isset($_GET["page"]) ? (int) $_GET["page"] : 1;
        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;

        require_once __DIR__ . "/../Views/admin/campaigns/index.php";
    }

    /**
     * Calcule les stats filtrées selon l'accès
     */
    private function getFilteredStats(?array $campaignIds, ?array $countries): array
    {
        $db = Database::getInstance();

        $params = [];
        $whereClause = "WHERE 1=1";

        // Filtre par IDs de campagnes
        if ($campaignIds !== null) {
            if (empty($campaignIds)) {
                return ['total' => 0, 'active' => 0, 'be' => 0, 'lu' => 0];
            }
            $placeholders = implode(",", array_fill(0, count($campaignIds), "?"));
            $whereClause .= " AND id IN ({$placeholders})";
            $params = $campaignIds;
        }

        // Filtre par pays
        if ($countries !== null) {
            $countryPlaceholders = implode(",", array_fill(0, count($countries), "?"));
            $whereClause .= " AND country IN ({$countryPlaceholders})";
            $params = array_merge($params, $countries);
        }

        try {
            // Total
            $result = $db->query("SELECT COUNT(*) as total FROM campaigns {$whereClause}", $params);
            $total = (int) ($result[0]['total'] ?? 0);

            // Actives (is_active=1 ET dans la période)
            $result = $db->query(
                "SELECT COUNT(*) as active FROM campaigns {$whereClause} AND is_active = 1 AND CURDATE() BETWEEN start_date AND end_date",
                $params
            );
            $active = (int) ($result[0]['active'] ?? 0);

            // Par pays BE
            $beParams = $params;
            $result = $db->query(
                "SELECT COUNT(*) as be FROM campaigns {$whereClause} AND country = 'BE'",
                $beParams
            );
            $be = (int) ($result[0]['be'] ?? 0);

            // Par pays LU
            $luParams = $params;
            $result = $db->query(
                "SELECT COUNT(*) as lu FROM campaigns {$whereClause} AND country = 'LU'",
                $luParams
            );
            $lu = (int) ($result[0]['lu'] ?? 0);

            return [
                'total' => $total,
                'active' => $active,
                'be' => $be,
                'lu' => $lu
            ];
        } catch (\Exception $e) {
            error_log("Erreur getFilteredStats: " . $e->getMessage());
            return ['total' => 0, 'active' => 0, 'be' => 0, 'lu' => 0];
        }
    }

    /**
     * Afficher le formulaire de création
     */
    public function create(): void
    {
        // ✅ Permission requise : créer des campagnes
        PermissionMiddleware::require('campaigns.create');

        $errors = Session::getFlash("errors", []);
        $old = Session::getFlash("old", []);

        // Récupérer les utilisateurs disponibles pour les collaborateurs
        $availableUsers = $this->getAllActiveUsers();

        require_once __DIR__ . "/../Views/admin/campaigns/create.php";
    }

    /**
     * Enregistrer une nouvelle campagne (POST)
     */
    public function store(): void
    {
        // ✅ Permission requise : créer des campagnes
        PermissionMiddleware::require('campaigns.create');

        if (!$this->validateCSRF()) {
            Session::setFlash("error", "Token de sécurité invalide");
            header("Location: /stm/admin/campaigns/create");
            exit();
        }

        $data = [
            "name" => $_POST["name"] ?? "",
            "country" => $_POST["country"] ?? "",
            "is_active" => isset($_POST["is_active"]) ? 1 : 0,
            "start_date" => !empty($_POST["start_date"]) ? $_POST["start_date"] . " 00:01:00" : "",
            "end_date" => !empty($_POST["end_date"]) ? $_POST["end_date"] . " 23:59:00" : "",
            "title_fr" => $_POST["title_fr"] ?? "",
            "description_fr" => $_POST["description_fr"] ?? "",
            "title_nl" => $_POST["title_nl"] ?? "",
            "description_nl" => $_POST["description_nl"] ?? "",
            "customer_assignment_mode" => $_POST["customer_assignment_mode"] ?? "automatic",
            "order_password" => $_POST["order_password"] ?? null,
            "order_type" => $_POST["order_type"] ?? "W",
            "show_prices" => isset($_POST["show_prices"]) ? 1 : 0, // Sprint 14
            "order_processing_mode" => $_POST["order_processing_mode"] ?? "direct", // Sprint 15
            "allow_prospects" => isset($_POST["allow_prospects"]) ? 1 : 0, // Sprint 16
            "deferred_delivery" => isset($_POST["deferred_delivery"]) ? 1 : 0,
            "delivery_date" => !empty($_POST["delivery_date"]) ? $_POST["delivery_date"] : null,
        ];

        $errors = $this->campaignModel->validate($data);

        if (!empty($errors)) {
            Session::setFlash("errors", $errors);
            Session::setFlash("old", $data);
            header("Location: /stm/admin/campaigns/create");
            exit();
        }

        try {
            $campaignId = $this->campaignModel->create($data);

            if ($campaignId) {
                // Assigner le créateur comme owner
                $this->assignOwner($campaignId);

                // Ajouter les collaborateurs sélectionnés
                if (!empty($_POST['collaborators']) && is_array($_POST['collaborators'])) {
                    $this->addInitialCollaborators($campaignId, $_POST['collaborators']);
                }

                // Gérer la liste de clients si mode manual
                if ($data["customer_assignment_mode"] === "manual" && !empty($_POST["customer_list"])) {
                    $customerList = str_replace(["\r\n", "\r"], "\n", $_POST["customer_list"]);
                    $customerNumbers = array_filter(array_map("trim", explode("\n", $customerList)));

                    if (!empty($customerNumbers)) {
                        $added = $this->campaignModel->addCustomersToCampaign($campaignId, $customerNumbers);
                        Session::setFlash("info", "{$added} client(s) ajouté(s) à la campagne");
                    }
                }

                Session::setFlash("success", "Campagne créée avec succès");
                header("Location: /stm/admin/campaigns/" . $campaignId);
            } else {
                Session::setFlash("error", "Erreur lors de la création de la campagne");
                Session::setFlash("old", $data);
                header("Location: /stm/admin/campaigns/create");
            }
        } catch (\Exception $e) {
            error_log("Erreur création campagne: " . $e->getMessage());
            Session::setFlash("error", "Erreur lors de la création de la campagne");
            Session::setFlash("old", $data);
            header("Location: /stm/admin/campaigns/create");
        }

        exit();
    }

    /**
     * Afficher les détails d'une campagne
     */
    public function show(int $id): void
    {
        // ✅ Vérifier l'accès à cette campagne spécifique
        if (!$this->canAccessCampaign($id)) {
            Session::setFlash("error", "Vous n'avez pas accès à cette campagne");
            header("Location: /stm/admin/campaigns");
            exit();
        }

        $campaign = $this->campaignModel->findById($id);

        if (!$campaign) {
            Session::setFlash("error", "Campagne introuvable");
            header("Location: /stm/admin/campaigns");
            exit();
        }

        // Statistiques
        $customerCount = $this->campaignModel->countCustomers($id);
        $promotionCount = $this->campaignModel->countPromotions($id);
        $customersWithOrders = $this->campaignModel->countCustomersWithOrders($id);
        $orderCount = 0;
        $totalAmount = 0;

        // Équipe (onglet Team)
        $assignees = $this->getAssignees($id);
        $availableUsers = $this->getAvailableUsers($id);

        require_once __DIR__ . "/../Views/admin/campaigns/show.php";
    }

    /**
     * Vérifie si l'utilisateur peut accéder à une campagne
     */
    private function canAccessCampaign(int $campaignId): bool
    {
        $accessibleIds = StatsAccessHelper::getAccessibleCampaignIds();

        // null = accès à tout
        if ($accessibleIds === null) {
            return true;
        }

        return in_array($campaignId, $accessibleIds);
    }

    /**
     * Afficher le formulaire de modification
     */
    public function edit(int $id): void
    {
        // ✅ Vérifier l'accès à cette campagne
        if (!$this->canAccessCampaign($id)) {
            Session::setFlash("error", "Vous n'avez pas accès à cette campagne");
            header("Location: /stm/admin/campaigns");
            exit();
        }

        // ✅ Permission requise : modifier les campagnes
        PermissionMiddleware::require('campaigns.edit');

        $campaign = $this->campaignModel->findById($id);

        if (!$campaign) {
            Session::setFlash("error", "Campagne introuvable");
            header("Location: /stm/admin/campaigns");
            exit();
        }

        $errors = Session::getFlash("errors", []);
        $old = Session::getFlash("old", []);

        // Récupérer les clients assignés si mode manual
        $assignedCustomers = [];
        if ($campaign["customer_assignment_mode"] === "manual") {
            $assignedCustomers = $this->campaignModel->getCampaignCustomers($id);
        }

        // Collaborateurs actuels
        $assignees = $this->getAssignees($id);
        $availableUsers = $this->getAvailableUsers($id);

        require_once __DIR__ . "/../Views/admin/campaigns/edit.php";
    }

    /**
     * Mettre à jour une campagne (POST)
     */
    public function update(int $id): void
    {
        // ✅ Vérifier l'accès à cette campagne
        if (!$this->canAccessCampaign($id)) {
            Session::setFlash("error", "Vous n'avez pas accès à cette campagne");
            header("Location: /stm/admin/campaigns");
            exit();
        }

        // ✅ Permission requise : modifier les campagnes
        PermissionMiddleware::require('campaigns.edit');

        if (!$this->validateCSRF()) {
            Session::setFlash("error", "Token de sécurité invalide");
            header("Location: /stm/admin/campaigns/" . $id . "/edit");
            exit();
        }

        $campaign = $this->campaignModel->findById($id);

        if (!$campaign) {
            Session::setFlash("error", "Campagne introuvable");
            header("Location: /stm/admin/campaigns");
            exit();
        }

        $data = [
            "name" => $_POST["name"] ?? "",
            "country" => $_POST["country"] ?? "",
            "is_active" => isset($_POST["is_active"]) ? 1 : 0,
            "start_date" => !empty($_POST["start_date"]) ? $_POST["start_date"] . " 00:01:00" : "",
            "end_date" => !empty($_POST["end_date"]) ? $_POST["end_date"] . " 23:59:00" : "",
            "title_fr" => $_POST["title_fr"] ?? "",
            "description_fr" => $_POST["description_fr"] ?? "",
            "title_nl" => $_POST["title_nl"] ?? "",
            "description_nl" => $_POST["description_nl"] ?? "",
            "customer_assignment_mode" => $_POST["customer_assignment_mode"] ?? "automatic",
            "order_password" => $_POST["order_password"] ?? null,
            "order_type" => $_POST["order_type"] ?? "W",
            "show_prices" => isset($_POST["show_prices"]) ? 1 : 0, // Sprint 14
            "order_processing_mode" => $_POST["order_processing_mode"] ?? "direct", // Sprint 15
            "allow_prospects" => isset($_POST["allow_prospects"]) ? 1 : 0, // Sprint 16
            "deferred_delivery" => isset($_POST["deferred_delivery"]) ? 1 : 0,
            "delivery_date" => !empty($_POST["delivery_date"]) ? $_POST["delivery_date"] : null,
        ];

        $errors = $this->campaignModel->validate($data);

        if (!empty($errors)) {
            Session::setFlash("errors", $errors);
            Session::setFlash("old", $data);
            header("Location: /stm/admin/campaigns/" . $id . "/edit");
            exit();
        }

        try {
            $success = $this->campaignModel->update($id, $data);

            if ($success) {
                // Gérer la liste de clients si mode manual
                if ($data["customer_assignment_mode"] === "manual") {
                    // Supprimer les anciens clients
                    $this->campaignModel->removeAllCustomers($id);

                    // Ajouter les nouveaux clients
                    if (!empty($_POST["customer_list"])) {
                        $customerList = str_replace(["\r\n", "\r"], "\n", $_POST["customer_list"]);
                        $customerNumbers = array_filter(array_map("trim", explode("\n", $customerList)));

                        if (!empty($customerNumbers)) {
                            $added = $this->campaignModel->addCustomersToCampaign($id, $customerNumbers);
                            Session::setFlash("info", "{$added} client(s) mis à jour");
                        }
                    }
                }

                Session::setFlash("success", "Campagne mise à jour avec succès");
                header("Location: /stm/admin/campaigns/" . $id);
            } else {
                Session::setFlash("error", "Erreur lors de la mise à jour");
                Session::setFlash("old", $data);
                header("Location: /stm/admin/campaigns/" . $id . "/edit");
            }
        } catch (\Exception $e) {
            error_log("Erreur mise à jour campagne: " . $e->getMessage());
            Session::setFlash("error", "Erreur lors de la mise à jour");
            Session::setFlash("old", $data);
            header("Location: /stm/admin/campaigns/" . $id . "/edit");
        }

        exit();
    }

    /**
     * Supprimer une campagne
     */
    public function destroy(int $id): void
    {
        // ✅ Vérifier l'accès à cette campagne
        if (!$this->canAccessCampaign($id)) {
            Session::setFlash("error", "Vous n'avez pas accès à cette campagne");
            header("Location: /stm/admin/campaigns");
            exit();
        }

        // ✅ Permission requise : supprimer les campagnes
        PermissionMiddleware::require('campaigns.delete');

        if (!$this->validateCSRF()) {
            Session::setFlash("error", "Token de sécurité invalide");
            header("Location: /stm/admin/campaigns");
            exit();
        }

        $campaign = $this->campaignModel->findById($id);

        if (!$campaign) {
            Session::setFlash("error", "Campagne introuvable");
            header("Location: /stm/admin/campaigns");
            exit();
        }

        try {
            $success = $this->campaignModel->delete($id);

            if ($success) {
                Session::setFlash("success", "Campagne supprimée avec succès");
            } else {
                Session::setFlash("error", "Erreur lors de la suppression");
            }
        } catch (\Exception $e) {
            error_log("Erreur suppression campagne: " . $e->getMessage());
            Session::setFlash("error", "Erreur lors de la suppression");
        }

        header("Location: /stm/admin/campaigns");
        exit();
    }

    /**
     * Activer/désactiver une campagne
     */
    public function toggleActive(int $id): void
    {
        // ✅ Vérifier l'accès à cette campagne
        if (!$this->canAccessCampaign($id)) {
            Session::setFlash("error", "Vous n'avez pas accès à cette campagne");
            header("Location: /stm/admin/campaigns");
            exit();
        }

        // ✅ Permission requise : modifier les campagnes
        PermissionMiddleware::require('campaigns.edit');

        if (!$this->validateCSRF()) {
            Session::setFlash("error", "Token de sécurité invalide");
            header("Location: /stm/admin/campaigns");
            exit();
        }

        $campaign = $this->campaignModel->findById($id);

        if (!$campaign) {
            Session::setFlash("error", "Campagne introuvable");
            header("Location: /stm/admin/campaigns");
            exit();
        }

        try {
            $newStatus = $campaign["is_active"] ? 0 : 1;
            $success = $this->campaignModel->update($id, ["is_active" => $newStatus]);

            if ($success) {
                $statusText = $newStatus ? "activée" : "désactivée";
                Session::setFlash("success", "Campagne {$statusText} avec succès");
            } else {
                Session::setFlash("error", "Erreur lors du changement de statut");
            }
        } catch (\Exception $e) {
            error_log("Erreur toggle campagne: " . $e->getMessage());
            Session::setFlash("error", "Erreur lors du changement de statut");
        }

        header("Location: /stm/admin/campaigns/" . $id);
        exit();
    }

    /**
     * Afficher les campagnes actives uniquement
     */
    public function active(): void
    {
        // ✅ Permission requise : voir les campagnes
        PermissionMiddleware::require('campaigns.view');

        $filters = [
            "status" => "active",
        ];

        // Filtrer par campagnes accessibles selon le rôle
        $accessibleCampaignIds = StatsAccessHelper::getAccessibleCampaignIds();
        if ($accessibleCampaignIds !== null) {
            $filters['campaign_ids'] = $accessibleCampaignIds;
        }

        // Filtrer par pays accessibles
        $accessibleCountries = StatsAccessHelper::getAccessibleCountries();
        if ($accessibleCountries !== null) {
            $filters['accessible_countries'] = $accessibleCountries;
        }

        $campaigns = $this->campaignModel->getActive($filters);

        foreach ($campaigns as &$campaign) {
            $campaign["customer_stats"] = $this->campaignModel->getCustomerStats($campaign["id"]);
            $campaign["promotion_count"] = $this->campaignModel->countPromotions($campaign["id"]);
        }
        unset($campaign);

        $stats = $this->getFilteredStats($accessibleCampaignIds, $accessibleCountries);

        require_once __DIR__ . "/../Views/admin/campaigns/active.php";
    }

    /**
     * Afficher les campagnes archivées (terminées + inactives)
     */
    public function archives(): void
    {
        // ✅ Permission requise : voir les campagnes
        PermissionMiddleware::require('campaigns.view');

        $filters = [
            "status" => "archived",
        ];

        // Filtrer par campagnes accessibles selon le rôle
        $accessibleCampaignIds = StatsAccessHelper::getAccessibleCampaignIds();
        if ($accessibleCampaignIds !== null) {
            $filters['campaign_ids'] = $accessibleCampaignIds;
        }

        // Filtrer par pays accessibles
        $accessibleCountries = StatsAccessHelper::getAccessibleCountries();
        if ($accessibleCountries !== null) {
            $filters['accessible_countries'] = $accessibleCountries;
        }

        $campaigns = $this->campaignModel->getArchived($filters);

        foreach ($campaigns as &$campaign) {
            $campaign["customer_stats"] = $this->campaignModel->getCustomerStats($campaign["id"]);
            $campaign["promotion_count"] = $this->campaignModel->countPromotions($campaign["id"]);
        }
        unset($campaign);

        $stats = $this->getFilteredStats($accessibleCampaignIds, $accessibleCountries);

        require_once __DIR__ . "/../Views/admin/campaigns/archives.php";
    }

    // =========================================================================
    // GESTION EQUIPE (ASSIGNEES)
    // =========================================================================

    /**
     * Récupère les membres de l'équipe d'une campagne
     */
    private function getAssignees(int $campaignId): array
    {
        try {
            $db = Database::getInstance();
            return $db->query(
                "SELECT u.id as user_id, u.name as user_name, u.email as user_email,
                        u.role as user_role, ca.role as role, ca.assigned_at
                 FROM campaign_assignees ca
                 JOIN users u ON u.id = ca.user_id
                 WHERE ca.campaign_id = :campaign_id
                 ORDER BY ca.role ASC, ca.assigned_at ASC",
                [':campaign_id' => $campaignId]
            );
        } catch (\PDOException $e) {
            error_log("Erreur getAssignees: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les utilisateurs disponibles pour assignation
     */
    private function getAvailableUsers(int $campaignId): array
    {
        try {
            $db = Database::getInstance();
            return $db->query(
                "SELECT id, name, email, role
                 FROM users
                 WHERE is_active = 1
                 AND role IN ('superadmin', 'admin', 'createur')
                 AND id NOT IN (
                     SELECT user_id FROM campaign_assignees WHERE campaign_id = :campaign_id
                 )
                 ORDER BY name ASC",
                [':campaign_id' => $campaignId]
            );
        } catch (\PDOException $e) {
            error_log("Erreur getAvailableUsers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Assigne le créateur comme owner de la campagne
     */
    private function assignOwner(int $campaignId): void
    {
        $currentUser = Session::get('user');
        if (empty($currentUser['id'])) return;

        try {
            $db = Database::getInstance();
            $db->getConnection()->prepare(
                "INSERT INTO campaign_assignees (campaign_id, user_id, role, assigned_by)
                 VALUES (:campaign_id, :user_id, 'owner', :assigned_by)"
            )->execute([
                ':campaign_id' => $campaignId,
                ':user_id' => $currentUser['id'],
                ':assigned_by' => $currentUser['id']
            ]);
        } catch (\PDOException $e) {
            error_log("Erreur assignOwner: " . $e->getMessage());
        }
    }

    /**
     * Ajoute un collaborateur à une campagne
     * POST /admin/campaigns/{id}/assignees
     */
    public function addAssignee(int $campaignId): void
    {
        // ✅ Vérifier la permission d'assigner sur cette campagne
        if (!PermissionHelper::canAssignToCampaign($campaignId)) {
            $this->jsonResponse(['success' => false, 'message' => 'Permission refusée'], 403);
            return;
        }

        // Récupérer les données JSON
        $rawData = file_get_contents('php://input');
        $data = json_decode($rawData, true);

        // Vérifier le token CSRF
        $csrfToken = $data['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Session::validateCsrfToken($csrfToken)) {
            $this->jsonResponse(['success' => false, 'message' => 'Token CSRF invalide'], 403);
            return;
        }

        $userId = (int) ($data['user_id'] ?? 0);
        if (!$userId) {
            $this->jsonResponse(['success' => false, 'message' => 'Utilisateur non spécifié'], 400);
            return;
        }

        $db = Database::getInstance();

        // Vérifier que l'utilisateur existe, est actif, et a le bon rôle
        $user = $db->query(
            "SELECT id, name FROM users WHERE id = :id AND is_active = 1 AND role IN ('superadmin', 'admin', 'createur') LIMIT 1",
            [':id' => $userId]
        );

        if (empty($user)) {
            $this->jsonResponse(['success' => false, 'message' => 'Utilisateur introuvable ou non autorisé'], 404);
            return;
        }

        // Vérifier que l'utilisateur n'est pas déjà assigné
        $existing = $db->query(
            "SELECT id FROM campaign_assignees WHERE campaign_id = :campaign_id AND user_id = :user_id LIMIT 1",
            [':campaign_id' => $campaignId, ':user_id' => $userId]
        );

        if (!empty($existing)) {
            $this->jsonResponse(['success' => false, 'message' => 'Cet utilisateur est déjà assigné'], 400);
            return;
        }

        // Ajouter l'assignation
        try {
            $currentUser = Session::get('user');

            $db->getConnection()->prepare(
                "INSERT INTO campaign_assignees (campaign_id, user_id, role, assigned_by)
                 VALUES (:campaign_id, :user_id, 'collaborator', :assigned_by)"
            )->execute([
                ':campaign_id' => $campaignId,
                ':user_id' => $userId,
                ':assigned_by' => $currentUser['id'] ?? null
            ]);

            $this->jsonResponse([
                'success' => true,
                'message' => $user[0]['name'] . ' a été ajouté à l\'équipe'
            ]);
        } catch (\PDOException $e) {
            error_log("Erreur addAssignee: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Erreur lors de l\'ajout'], 500);
        }
    }

    /**
     * Retire un collaborateur d'une campagne
     * POST /admin/campaigns/{id}/assignees/{userId}/delete
     */
    public function removeAssignee(int $campaignId, int $userId): void
    {
        // ✅ Vérifier la permission d'assigner sur cette campagne
        if (!PermissionHelper::canAssignToCampaign($campaignId)) {
            $this->jsonResponse(['success' => false, 'message' => 'Permission refusée'], 403);
            return;
        }

        // Récupérer les données JSON
        $rawData = file_get_contents('php://input');
        $data = json_decode($rawData, true);

        // Vérifier le token CSRF
        $csrfToken = $data['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Session::validateCsrfToken($csrfToken)) {
            $this->jsonResponse(['success' => false, 'message' => 'Token CSRF invalide'], 403);
            return;
        }

        $db = Database::getInstance();

        // Vérifier que l'assignation existe
        $assignee = $db->query(
            "SELECT ca.id, ca.role, u.name AS user_name
             FROM campaign_assignees ca
             JOIN users u ON u.id = ca.user_id
             WHERE ca.campaign_id = :campaign_id AND ca.user_id = :user_id
             LIMIT 1",
            [':campaign_id' => $campaignId, ':user_id' => $userId]
        );

        if (empty($assignee)) {
            $this->jsonResponse(['success' => false, 'message' => 'Assignation introuvable'], 404);
            return;
        }

        // Empêcher de retirer l'owner
        if ($assignee[0]['role'] === 'owner') {
            $this->jsonResponse(['success' => false, 'message' => 'Impossible de retirer l\'owner'], 400);
            return;
        }

        // Supprimer l'assignation
        try {
            $db->getConnection()->prepare(
                "DELETE FROM campaign_assignees WHERE campaign_id = :campaign_id AND user_id = :user_id"
            )->execute([
                ':campaign_id' => $campaignId,
                ':user_id' => $userId
            ]);

            $this->jsonResponse([
                'success' => true,
                'message' => $assignee[0]['user_name'] . ' a été retiré de l\'équipe'
            ]);
        } catch (\PDOException $e) {
            error_log("Erreur removeAssignee: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Erreur lors de la suppression'], 500);
        }
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Récupère tous les utilisateurs actifs (pour le formulaire de création)
     * Exclut manager et rep qui ne gèrent pas les campagnes
     */
    private function getAllActiveUsers(): array
    {
        try {
            $db = Database::getInstance();
            $currentUser = Session::get('user');
            $currentUserId = $currentUser['id'] ?? 0;

            // Exclure l'utilisateur courant (il sera owner) et les rôles manager/rep
            return $db->query(
                "SELECT id, name, email, role
                 FROM users
                 WHERE is_active = 1
                 AND id != :current_user_id
                 AND role IN ('superadmin', 'admin', 'createur')
                 ORDER BY name ASC",
                [':current_user_id' => $currentUserId]
            );
        } catch (\PDOException $e) {
            error_log("Erreur getAllActiveUsers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ajoute les collaborateurs initiaux lors de la création
     */
    private function addInitialCollaborators(int $campaignId, array $userIds): void
    {
        $db = Database::getInstance();
        $currentUser = Session::get('user');
        $assignedBy = $currentUser['id'] ?? null;

        foreach ($userIds as $userId) {
            $userId = (int) $userId;
            if ($userId <= 0) continue;

            try {
                $db->getConnection()->prepare(
                    "INSERT IGNORE INTO campaign_assignees (campaign_id, user_id, role, assigned_by)
                     VALUES (:campaign_id, :user_id, 'collaborator', :assigned_by)"
                )->execute([
                    ':campaign_id' => $campaignId,
                    ':user_id' => $userId,
                    ':assigned_by' => $assignedBy
                ]);
            } catch (\PDOException $e) {
                error_log("Erreur addInitialCollaborators: " . $e->getMessage());
            }
        }
    }

    /**
     * Valider le token CSRF
     */
    private function validateCSRF(): bool
    {
        $token = $_POST["_token"] ?? "";
        return !empty($token) && isset($_SESSION["csrf_token"]) && $token === $_SESSION["csrf_token"];
    }

    /**
     * Réponse JSON
     */
    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
}