<?php
/**
 * InternalCustomerController
 *
 * Contrôleur pour la gestion des comptes internes
 *
 * @created  2025/12/03 14:00
 * @modified 2025/12/30 - Ajout vérifications de permissions
 */

namespace App\Controllers;

use App\Models\InternalCustomer;
use App\Helpers\PermissionHelper;
use Core\Session;

class InternalCustomerController
{
    private InternalCustomer $model;

    public function __construct()
    {
        $this->model = new InternalCustomer();
    }

    /**
     * Vérifie la permission de visualisation
     *
     * @return void
     */
    private function requireViewPermission(): void
    {
        if (!PermissionHelper::can('internal_accounts.view')) {
            Session::setFlash('error', 'Vous n\'avez pas accès à cette fonctionnalité.');
            header('Location: /stm/admin/dashboard');
            exit;
        }
    }

    /**
     * Vérifie la permission de gestion (create/edit/delete)
     *
     * @return void
     */
    private function requireManagePermission(): void
    {
        if (!PermissionHelper::can('internal_accounts.manage')) {
            Session::setFlash('error', 'Vous n\'avez pas la permission de gérer les comptes internes.');
            header('Location: /stm/admin/config/internal-customers');
            exit;
        }
    }

    /**
     * Liste des comptes internes
     */
    public function index(): void
    {
        $this->requireViewPermission();

        // Récupérer le filtre pays
        $filters = [];
        if (!empty($_GET["country"])) {
            $filters["country"] = $_GET["country"];
        }

        $customers = $this->model->getAll($filters);
        $stats = $this->model->countByCountry();

        // Permission de gestion pour la vue
        $canManage = PermissionHelper::can('internal_accounts.manage');

        require_once __DIR__ . "/../Views/admin/config/internal_customers_index.php";
    }

    /**
     * Formulaire de création
     */
    public function create(): void
    {
        $this->requireManagePermission();

        $errors = Session::getFlash("errors", []);
        $old = Session::getFlash("old", []);

        require_once __DIR__ . "/../Views/admin/config/internal_customers_create.php";
    }

    /**
     * Enregistrer un nouveau compte (POST)
     */
    public function store(): void
    {
        $this->requireManagePermission();

        // Vérifier CSRF
        if (!$this->validateCSRF()) {
            Session::setFlash("error", "Token de sécurité invalide");
            header("Location: /stm/admin/config/internal-customers/create");
            exit();
        }

        $data = [
            "customer_number" => $_POST["customer_number"] ?? "",
            "country" => $_POST["country"] ?? "",
            "description" => $_POST["description"] ?? "",
            "is_active" => isset($_POST["is_active"]) ? 1 : 0,
        ];

        // Valider
        $errors = $this->model->validate($data);

        if (!empty($errors)) {
            Session::setFlash("errors", $errors);
            Session::setFlash("old", $data);
            header("Location: /stm/admin/config/internal-customers/create");
            exit();
        }

        // Créer
        $id = $this->model->create($data);

        if ($id) {
            Session::setFlash("success", "Compte interne ajouté avec succès");
            header("Location: /stm/admin/config/internal-customers");
        } else {
            Session::setFlash("error", "Erreur lors de la création");
            Session::setFlash("old", $data);
            header("Location: /stm/admin/config/internal-customers/create");
        }

        exit();
    }

    /**
     * Formulaire de modification
     */
    public function edit(int $id): void
    {
        $this->requireManagePermission();

        $customer = $this->model->findById($id);

        if (!$customer) {
            Session::setFlash("error", "Compte interne introuvable");
            header("Location: /stm/admin/config/internal-customers");
            exit();
        }

        $errors = Session::getFlash("errors", []);
        $old = Session::getFlash("old", []);

        require_once __DIR__ . "/../Views/admin/config/internal_customers_edit.php";
    }

    /**
     * Mettre à jour (POST)
     */
    public function update(int $id): void
    {
        $this->requireManagePermission();

        // Vérifier que le compte existe
        $customer = $this->model->findById($id);

        if (!$customer) {
            Session::setFlash("error", "Compte interne introuvable");
            header("Location: /stm/admin/config/internal-customers");
            exit();
        }

        // Vérifier CSRF
        if (!$this->validateCSRF()) {
            Session::setFlash("error", "Token de sécurité invalide");
            header("Location: /stm/admin/config/internal-customers/" . $id . "/edit");
            exit();
        }

        $data = [
            "customer_number" => $_POST["customer_number"] ?? "",
            "country" => $_POST["country"] ?? "",
            "description" => $_POST["description"] ?? "",
            "is_active" => isset($_POST["is_active"]) ? 1 : 0,
        ];

        // Valider
        $errors = $this->model->validate($data, $id);

        if (!empty($errors)) {
            Session::setFlash("errors", $errors);
            Session::setFlash("old", $data);
            header("Location: /stm/admin/config/internal-customers/" . $id . "/edit");
            exit();
        }

        // Mettre à jour
        if ($this->model->update($id, $data)) {
            Session::setFlash("success", "Compte interne mis à jour");
            header("Location: /stm/admin/config/internal-customers");
        } else {
            Session::setFlash("error", "Erreur lors de la mise à jour");
            header("Location: /stm/admin/config/internal-customers/" . $id . "/edit");
        }

        exit();
    }

    /**
     * Supprimer (POST)
     */
    public function destroy(int $id): void
    {
        $this->requireManagePermission();

        // Vérifier que le compte existe
        $customer = $this->model->findById($id);

        if (!$customer) {
            Session::setFlash("error", "Compte interne introuvable");
            header("Location: /stm/admin/config/internal-customers");
            exit();
        }

        // Vérifier CSRF
        if (!$this->validateCSRF()) {
            Session::setFlash("error", "Token de sécurité invalide");
            header("Location: /stm/admin/config/internal-customers");
            exit();
        }

        if ($this->model->delete($id)) {
            Session::setFlash("success", "Compte interne supprimé");
        } else {
            Session::setFlash("error", "Erreur lors de la suppression");
        }

        header("Location: /stm/admin/config/internal-customers");
        exit();
    }

    /**
     * Toggle actif/inactif (AJAX)
     */
    public function toggleActive(int $id): void
    {
        header("Content-Type: application/json");

        // Vérifier la permission
        if (!PermissionHelper::can('internal_accounts.manage')) {
            echo json_encode(["success" => false, "message" => "Permission refusée"]);
            exit();
        }

        $customer = $this->model->findById($id);

        if (!$customer) {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Compte introuvable"]);
            exit();
        }

        $newStatus = !$customer["is_active"];

        if ($this->model->toggleActive($id, $newStatus)) {
            echo json_encode(["success" => true, "is_active" => $newStatus]);
        } else {
            echo json_encode(["success" => false, "message" => "Erreur mise à jour"]);
        }

        exit();
    }

    /**
     * Valider le token CSRF
     */
    private function validateCSRF(): bool
    {
        $token = $_POST["_token"] ?? "";
        return !empty($token) && isset($_SESSION["csrf_token"]) && $token === $_SESSION["csrf_token"];
    }
}
