<?php
/**
 * Dashboard Controller
 * 
 * Gère l'affichage du tableau de bord administrateur.
 * 
 * @package STM
 * @version 2.0
 */

namespace App\Controllers;

use Core\View;

class DashboardController
{
    /**
     * Affiche le dashboard principal
     * 
     * @return void
     */
    public function index(): void
    {
        // Le dashboard gère lui-même le layout et les données
        // On inclut simplement la vue
        require_once __DIR__ . '/../Views/admin/dashboard.php';
    }
}
