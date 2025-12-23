<?php
/**
 * InternalCustomerController
 *
 * Contrôleur pour la gestion des comptes internes
 *
 * @created  2025/12/03 14:00
 */

namespace App\Controllers;

use App\Models\InternalCustomer;
use Core\Session;

class InternalCustomerController
{
    private InternalCustomer $model;

    public function __construct()
    {
        $this->model = new InternalCustomer();
    }

    /**
     * Liste des comptes internes
     */
    public function index(): void
    {
        // Récupérer le filtre pays
        $filters = [];
        if (!empty($_GET["country"])) {
            $filters["country"] = $_GET["country"];
        }

        $customers = $this->model->getAll($filters);
        $stats = $this->model->countByCountry();

        require_once __DIR__ . "/../Views/admin/config/internal_customers_index.php";
    }

    /**
     * Formulaire de création
     */
    public function create(): void
    {
        $errors = Session::getFlash("errors", []);
        $old = Session::getFlash("old", []);

        require_once __DIR__ . "/../Views/admin/config/internal_customers_create.php";
    }

    /**
     * Enregistrer un nouveau compte (POST)
     */
    public function store(): void
    {
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
