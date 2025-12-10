<?php
/**
 * Controller : SettingsController
 * 
 * Gestion de la configuration système
 * 
 * @package STM
 * @created 2025/12/10
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
        $permissionMatrix = PermissionHelper::getPermissionMatrix();
        $permissionLabels = PermissionHelper::getPermissionLabels();
        $permissionCategories = PermissionHelper::getPermissionCategories();
        $roleLabels = User::ROLE_LABELS;
        
        // Peut modifier ?
        $canEdit = PermissionHelper::can('settings.manage');
        
        $title = 'Configuration';
        
        require __DIR__ . '/../Views/admin/settings/index.php';
    }
}
