<?php
/**
 * Controller : SettingsController
 *
 * Gestion de la configuration système et des permissions
 *
 * @package STM
 * @created 2025/12/10
 * @modified 2025/12/10 - Ajout gestion permissions éditable
 */

namespace App\Controllers;

use App\Helpers\PermissionHelper;
use App\Models\User;
use Core\Session;

class SettingsController
{
    /**
     * Page principale de configuration
     * GET /admin/settings
     */
    public function index(): void
    {
        // Vérifier la permission
        if (PermissionHelper::cannot('settings.view')) {
            Session::setFlash('error', 'Vous n\'avez pas accès à cette page');
            header('Location: /stm/admin/dashboard');
            exit();
        }

        // Onglet actif
        $activeTab = $_GET['tab'] ?? 'permissions';

        // Données pour l'onglet Permissions
        $matrixData = PermissionHelper::getPermissionMatrix();
        $categories = PermissionHelper::getCategories();

        // Peut modifier les permissions ?
        $canEditPermissions = PermissionHelper::can('permissions.manage');

        // Labels des rôles
        $roleLabels = [
            'superadmin' => 'Super Admin',
            'admin' => 'Administrateur',
            'createur' => 'Créateur',
            'manager_reps' => 'Manager Reps',
            'rep' => 'Commercial'
        ];

        $title = 'Configuration';

        require __DIR__ . '/../Views/admin/settings/index.php';
    }

    /**
     * Sauvegarde les permissions
     * POST /admin/settings/permissions
     */
    public function savePermissions(): void
    {
        // Vérifier la permission
        if (PermissionHelper::cannot('permissions.manage')) {
            $this->jsonResponse(['success' => false, 'message' => 'Permission refusée'], 403);
            return;
        }

        // Vérifier le token CSRF
        $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Session::validateCsrfToken($csrfToken)) {
            $this->jsonResponse(['success' => false, 'message' => 'Token CSRF invalide'], 403);
            return;
        }

        // Récupérer les données
        $rawData = file_get_contents('php://input');
        $data = json_decode($rawData, true);

        if (!$data || !isset($data['permissions'])) {
            // Essayer avec $_POST
            $data = $_POST;
        }

        if (!isset($data['permissions']) || !is_array($data['permissions'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Données invalides'], 400);
            return;
        }

        // Construire la matrice
        $matrix = [];
        foreach ($data['permissions'] as $role => $permissions) {
            // Ignorer superadmin (protégé)
            if (PermissionHelper::isProtectedRole($role)) {
                continue;
            }

            $matrix[$role] = [];
            if (is_array($permissions)) {
                foreach ($permissions as $permCode => $value) {
                    $matrix[$role][$permCode] = (bool) $value;
                }
            }
        }

        // Sauvegarder
        $success = PermissionHelper::savePermissionMatrix($matrix);

        if ($success) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Permissions enregistrées avec succès'
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde'
            ], 500);
        }
    }

    /**
     * Réponse JSON
     *
     * @param array $data
     * @param int $statusCode
     */
    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
}