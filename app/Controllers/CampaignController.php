<?php
/**
 * CampaignController
 *
 * Contrôleur pour la gestion des campagnes promotionnelles
 *
 * @created  2025/11/07 10:00
 * @modified 2025/12/10 - Ajout gestion équipe (assignees) pour onglet Équipe
 */

namespace App\Controllers;

use App\Models\Campaign;
use App\Helpers\PermissionHelper;
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
        $filters = [
            "search" => $_GET["search"] ?? "",
            "country" => $_GET["country"] ?? "",
            "status" => $_GET["status"] ?? "",
        ];

        $campaigns = $this->campaignModel->getAll($filters);

        foreach ($campaigns as &$campaign) {
            $campaign["customer_stats"] = $this->campaignModel->getCustomerStats($campaign["id"]);
            $campaign["promotion_count"] = $this->campaignModel->countPromotions($campaign["id"]);
        }
        unset($campaign);

        $stats = $this->campaignModel->getStats();
        $stats["be"] = $this->campaignModel->countByCountry("BE");
        $stats["lu"] = $this->campaignModel->countByCountry("LU");

        $total = $this->campaignModel->count($filters);
        $perPage = 20;
        $currentPage = isset($_GET["page"]) ? (int) $_GET["page"] : 1;
        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;

        require_once __DIR__ . "/../Views/admin/campaigns/index.php";
    }

    /**
     * Afficher le formulaire de création
     */
    public function create(): void
    {
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
     * Afficher le formulaire de modification
     */
    public function edit(int $id): void
    {
        $campaign = $this->campaignModel->findById($id);

        if (!$campaign) {
            Session::setFlash("error", "Campagne introuvable");
            header("Location: /stm/admin/campaigns");
            exit();
        }

        $errors = Session::getFlash("errors", []);
        $old = Session::getFlash("old", []);

        if ($campaign["customer_assignment_mode"] === "manual") {
            $customers = $this->campaignModel->getCustomerNumbers($id);
            $campaign["customer_list"] = implode("\n", $customers);
        }

        // Équipe (pour section Équipe)
        $assignees = $this->getAssignees($id);
        $availableUsers = $this->getAvailableUsers($id);

        require_once __DIR__ . "/../Views/admin/campaigns/edit.php";
    }

    /**
     * Mettre à jour une campagne (POST)
     */
    public function update(int $id): void
    {
        $campaign = $this->campaignModel->findById($id);

        if (!$campaign) {
            Session::setFlash("error", "Campagne introuvable");
            header("Location: /stm/admin/campaigns");
            exit();
        }

        if (!$this->validateCSRF()) {
            Session::setFlash("error", "Token de sécurité invalide");
            header("Location: /stm/admin/campaigns/" . $id . "/edit");
            exit();
        }

        $data = [
            "name" => $_POST["name"] ?? "",
            "country" => $_POST["country"] ?? "",
            "is_active" => isset($_POST["is_active"]) ? 1 : 0,
            "start_date" => !empty($_POST["start_date"]) ? substr($_POST["start_date"], 0, 10) . " 00:01:00" : "",
            "end_date" => !empty($_POST["end_date"]) ? substr($_POST["end_date"], 0, 10) . " 23:59:00" : "",
            "title_fr" => $_POST["title_fr"] ?? "",
            "description_fr" => $_POST["description_fr"] ?? "",
            "title_nl" => $_POST["title_nl"] ?? "",
            "description_nl" => $_POST["description_nl"] ?? "",
            "customer_assignment_mode" => $_POST["customer_assignment_mode"] ?? "automatic",
            "order_password" => $_POST["order_password"] ?? null,
            "order_type" => $_POST["order_type"] ?? "W",
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
                $oldMode = $campaign["customer_assignment_mode"];
                $newMode = $data["customer_assignment_mode"];

                if ($oldMode === "manual" && $newMode !== "manual") {
                    $this->campaignModel->removeAllCustomers($id);
                }

                if ($newMode === "manual") {
                    $this->campaignModel->removeAllCustomers($id);

                    if (!empty($_POST["customer_list"])) {
                        $customerList = str_replace(["\r\n", "\r"], "\n", $_POST["customer_list"]);
                        $customerNumbers = array_filter(array_map("trim", explode("\n", $customerList)));

                        if (!empty($customerNumbers)) {
                            $added = $this->campaignModel->addCustomersToCampaign($id, $customerNumbers);
                            Session::setFlash("info", "{$added} client(s) ajouté(s) à la campagne");
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
     * Supprimer une campagne (POST)
     */
    public function destroy(int $id): void
    {
        $campaign = $this->campaignModel->findById($id);

        if (!$campaign) {
            Session::setFlash("error", "Campagne introuvable");
            header("Location: /stm/admin/campaigns");
            exit();
        }

        if (!$this->validateCSRF()) {
            Session::setFlash("error", "Token de sécurité invalide");
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
     * Afficher les campagnes actives uniquement
     */
    public function active(): void
    {
        $campaigns = $this->campaignModel->getActive();
        $stats = $this->campaignModel->getStats();

        foreach ($campaigns as &$campaign) {
            $campaign["customer_stats"] = $this->campaignModel->getCustomerStats($campaign["id"]);
            $campaign["promotion_count"] = $this->campaignModel->countPromotions($campaign["id"]);
        }
        unset($campaign);

        $filters = ["status" => "active"];
        $pageTitle = "Campagnes actives";
        require_once __DIR__ . "/../Views/admin/campaigns/active.php";
    }

    /**
     * Afficher les campagnes archivées
     */
    public function archives(): void
    {
        $campaigns = $this->campaignModel->getArchived();
        $stats = $this->campaignModel->getStats();

        foreach ($campaigns as &$campaign) {
            $campaign["customer_stats"] = $this->campaignModel->getCustomerStats($campaign["id"]);
            $campaign["promotion_count"] = $this->campaignModel->countPromotions($campaign["id"]);
        }
        unset($campaign);

        $filters = ["status" => "archived"];
        $pageTitle = "Campagnes archivées";
        require_once __DIR__ . "/../Views/admin/campaigns/archives.php";
    }

    /**
     * Activer/Désactiver une campagne (AJAX)
     */
    public function toggleActive(int $id): void
    {
        $campaign = $this->campaignModel->findById($id);

        if (!$campaign) {
            $this->jsonResponse(["success" => false, "message" => "Campagne introuvable"], 404);
            return;
        }

        try {
            $newStatus = !$campaign["is_active"];
            $success = $this->campaignModel->update($id, ["is_active" => $newStatus]);

            if ($success) {
                $this->jsonResponse(["success" => true, "is_active" => $newStatus]);
            } else {
                $this->jsonResponse(["success" => false, "message" => "Erreur mise à jour"]);
            }
        } catch (\Exception $e) {
            error_log("Erreur toggle active: " . $e->getMessage());
            $this->jsonResponse(["success" => false, "message" => "Erreur serveur"], 500);
        }
    }

    // =========================================================================
    // GESTION ÉQUIPE (ASSIGNEES)
    // =========================================================================

    /**
     * Assigne le créateur comme owner lors de la création
     */
    private function assignOwner(int $campaignId): void
    {
        $currentUser = Session::get('user');
        if (!$currentUser || !$currentUser['id']) {
            return;
        }

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
            error_log("Erreur assignation owner: " . $e->getMessage());
        }
    }

    /**
     * Récupère les utilisateurs assignés à une campagne
     */
    private function getAssignees(int $campaignId): array
    {
        try {
            $db = Database::getInstance();
            return $db->query(
                "SELECT
                    ca.id,
                    ca.campaign_id,
                    ca.user_id,
                    ca.role,
                    ca.assigned_at,
                    ca.assigned_by,
                    u.name AS user_name,
                    u.email AS user_email,
                    u.role AS user_role
                 FROM campaign_assignees ca
                 JOIN users u ON u.id = ca.user_id
                 WHERE ca.campaign_id = :campaign_id
                 ORDER BY ca.role DESC, ca.assigned_at ASC",
                [':campaign_id' => $campaignId]
            );
        } catch (\PDOException $e) {
            error_log("Erreur getAssignees: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les utilisateurs NON assignés à une campagne
     */
    private function getAvailableUsers(int $campaignId): array
    {
        try {
            $db = Database::getInstance();
            return $db->query(
                "SELECT u.id, u.name, u.email, u.role
                 FROM users u
                 WHERE u.is_active = 1
                 AND u.id NOT IN (
                     SELECT user_id FROM campaign_assignees WHERE campaign_id = :campaign_id
                 )
                 ORDER BY u.name ASC",
                [':campaign_id' => $campaignId]
            );
        } catch (\PDOException $e) {
            error_log("Erreur getAvailableUsers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ajoute un collaborateur à une campagne
     * POST /admin/campaigns/{id}/assignees
     */
    public function addAssignee(int $campaignId): void
    {
        // Vérifier la permission
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

        // Vérifier que l'utilisateur existe et est actif
        $user = $db->query(
            "SELECT id, name FROM users WHERE id = :id AND is_active = 1 LIMIT 1",
            [':id' => $userId]
        );

        if (empty($user)) {
            $this->jsonResponse(['success' => false, 'message' => 'Utilisateur introuvable'], 404);
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
     * DELETE /admin/campaigns/{id}/assignees/{userId}
     */
    public function removeAssignee(int $campaignId, int $userId): void
    {
        // Vérifier la permission
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
     */
    private function getAllActiveUsers(): array
    {
        try {
            $db = Database::getInstance();
            $currentUser = Session::get('user');
            $currentUserId = $currentUser['id'] ?? 0;

            // Exclure l'utilisateur courant (il sera owner)
            return $db->query(
                "SELECT id, name, email, role
                 FROM users
                 WHERE is_active = 1 AND id != :current_user_id
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