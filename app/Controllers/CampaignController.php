<?php
/**
 * Contrôleur des campagnes
 * 
 * Gère le CRUD complet des campagnes promotionnelles.
 * 
 * @package STM
 * @version 2.0
 * @created 07/11/2025
 * @modified 11/11/2025 - Correction bugs destroy() + variables pagination
 */

namespace App\Controllers;

use App\Models\Campaign;
use Core\Auth;
use Core\Request;
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
     * 
     * @return void
     * @created 07/11/2025
     * @modified 11/11/2025 - Ajout variables pagination ($total, $currentPage, etc.)
     */
    public function index(): void
    {
        // Récupérer les filtres de la requête
        $filters = [
            'search' => $_GET['search'] ?? '',
            'country' => $_GET['country'] ?? '',
            'is_active' => isset($_GET['is_active']) && $_GET['is_active'] !== '' ? (int)$_GET['is_active'] : null
        ];
        
        // Pagination
        $perPage = 10;
        $currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        
        // Récupérer le nombre total de campagnes (avec filtres)
        $total = $this->campaignModel->count($filters);
        
        // Calculer le nombre de pages
        $totalPages = $total > 0 ? (int)ceil($total / $perPage) : 1;
        
        // Récupérer les campagnes filtrées avec pagination
        $campaigns = $this->campaignModel->getAll($filters, $currentPage, $perPage);
        
        // Récupérer les statistiques
        $stats = $this->campaignModel->getStats();
        
        // Charger la vue avec TOUTES les variables nécessaires
        require_once __DIR__ . '/../Views/admin/campaigns/index.php';
    }

    /**
     * Afficher le formulaire de création
     */
    public function create(): void
    {
        // Préparer les variables pour la vue
        $errors = Session::getFlash('errors', []);
        $old = Session::getFlash('old', []);
        
        // Charger la vue
        require_once __DIR__ . '/../Views/admin/campaigns/create.php';
    }

    /**
     * Enregistrer une nouvelle campagne
     */
    public function store(): void
    {
        // Vérifier le token CSRF
        if (!$this->validateCSRF()) {
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /stm/admin/campaigns/create');
            exit;
        }

        // Récupérer les données du formulaire
        $data = [
            'name' => $_POST['name'] ?? '',
            'country' => $_POST['country'] ?? '',
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'start_date' => $_POST['start_date'] ?? '',
            'end_date' => $_POST['end_date'] ?? '',
            'title_fr' => $_POST['title_fr'] ?? '',
            'description_fr' => $_POST['description_fr'] ?? '',
            'title_nl' => $_POST['title_nl'] ?? '',
            'description_nl' => $_POST['description_nl'] ?? '',
        ];

        // Valider les données
        $errors = $this->campaignModel->validate($data);
        
        if (!empty($errors)) {
            // Erreurs de validation
            Session::setFlash('errors', $errors);
            Session::setFlash('old', $data);
            header('Location: /stm/admin/campaigns/create');
            exit;
        }

        // Créer la campagne
        try {
            $campaignId = $this->campaignModel->create($data);
            
            if ($campaignId) {
                Session::setFlash('success', 'Campagne créée avec succès');
                header('Location: /stm/admin/campaigns/' . $campaignId);
            } else {
                Session::setFlash('error', 'Erreur lors de la création de la campagne');
                Session::setFlash('old', $data);
                header('Location: /stm/admin/campaigns/create');
            }
        } catch (\Exception $e) {
            error_log("Erreur création campagne: " . $e->getMessage());
            Session::setFlash('error', 'Erreur lors de la création de la campagne');
            Session::setFlash('old', $data);
            header('Location: /stm/admin/campaigns/create');
        }
        
        exit;
    }

    /**
     * Afficher les détails d'une campagne
     */
    public function show(int $id): void
    {
        $campaign = $this->campaignModel->findById($id);
        
        if (!$campaign) {
            Session::setFlash('error', 'Campagne introuvable');
            header('Location: /stm/admin/campaigns');
            exit;
        }
        
        // Charger la vue
        require_once __DIR__ . '/../Views/admin/campaigns/show.php';
    }

    /**
     * Afficher le formulaire de modification
     */
    public function edit(int $id): void
    {
        $campaign = $this->campaignModel->findById($id);
        
        if (!$campaign) {
            Session::setFlash('error', 'Campagne introuvable');
            header('Location: /stm/admin/campaigns');
            exit;
        }
        
        // Préparer les variables pour la vue
        $errors = Session::getFlash('errors', []);
        $old = Session::getFlash('old', []);
        
        // Charger la vue
        require_once __DIR__ . '/../Views/admin/campaigns/edit.php';
    }

    /**
     * Mettre à jour une campagne
     */
    public function update(int $id): void
    {
        // Vérifier que la campagne existe
        $campaign = $this->campaignModel->findById($id);
        
        if (!$campaign) {
            Session::setFlash('error', 'Campagne introuvable');
            header('Location: /stm/admin/campaigns');
            exit;
        }

        // Vérifier le token CSRF
        if (!$this->validateCSRF()) {
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /stm/admin/campaigns/' . $id . '/edit');
            exit;
        }

        // Récupérer les données du formulaire
        $data = [
            'name' => $_POST['name'] ?? '',
            'country' => $_POST['country'] ?? '',
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'start_date' => $_POST['start_date'] ?? '',
            'end_date' => $_POST['end_date'] ?? '',
            'title_fr' => $_POST['title_fr'] ?? '',
            'description_fr' => $_POST['description_fr'] ?? '',
            'title_nl' => $_POST['title_nl'] ?? '',
            'description_nl' => $_POST['description_nl'] ?? '',
        ];

        // Valider les données
        $errors = $this->campaignModel->validate($data);
        
        if (!empty($errors)) {
            // Erreurs de validation
            Session::setFlash('errors', $errors);
            Session::setFlash('old', $data);
            header('Location: /stm/admin/campaigns/' . $id . '/edit');
            exit;
        }

        // Mettre à jour la campagne
        try {
            $success = $this->campaignModel->update($id, $data);
            
            if ($success) {
                Session::setFlash('success', 'Campagne mise à jour avec succès');
                header('Location: /stm/admin/campaigns/' . $id);
            } else {
                Session::setFlash('error', 'Erreur lors de la mise à jour');
                Session::setFlash('old', $data);
                header('Location: /stm/admin/campaigns/' . $id . '/edit');
            }
        } catch (\Exception $e) {
            error_log("Erreur mise à jour campagne: " . $e->getMessage());
            Session::setFlash('error', 'Erreur lors de la mise à jour');
            Session::setFlash('old', $data);
            header('Location: /stm/admin/campaigns/' . $id . '/edit');
        }
        
        exit;
    }

    /**
     * Supprimer une campagne
     * 
     * @param int $id ID de la campagne
     * @return void
     * @created 11/11/2025
     */
    public function delete(int $id): void
    {
        // Vérifier que la campagne existe
        $campaign = $this->campaignModel->findById($id);
        
        if (!$campaign) {
            Session::setFlash('error', 'Campagne introuvable');
            header('Location: /stm/admin/campaigns');
            exit;
        }

        // Supprimer la campagne
        try {
            $success = $this->campaignModel->delete($id);
            
            if ($success) {
                Session::setFlash('success', 'Campagne supprimée avec succès');
            } else {
                Session::setFlash('error', 'Erreur lors de la suppression');
            }
        } catch (\Exception $e) {
            error_log("Erreur suppression campagne: " . $e->getMessage());
            Session::setFlash('error', 'Erreur lors de la suppression');
        }
        
        header('Location: /stm/admin/campaigns');
        exit;
    }

    /**
     * Alias pour delete() - Pour compatibilité avec routes.php
     * 
     * @param int $id ID de la campagne
     * @return void
     * @created 11/11/2025 - Correction bug "destroy() not found"
     */
    public function destroy(int $id): void
    {
        // Simplement appeler delete()
        $this->delete($id);
    }

    /**
     * Afficher les campagnes actives uniquement
     */
    public function active(): void
    {
        $campaigns = $this->campaignModel->getActive();
        $stats = $this->campaignModel->getStats();
        
        // Préparer les filtres pour la vue
        $filters = ['status' => 'active'];
        
        // Charger la vue (réutiliser index.php avec un titre différent)
        $pageTitle = 'Campagnes actives';
        require_once __DIR__ . '/../Views/admin/campaigns/active.php';
    }

    /**
     * Afficher les campagnes archivées (terminées + inactives)
     */
    public function archives(): void
    {
        $campaigns = $this->campaignModel->getArchived();
        $stats = $this->campaignModel->getStats();
        
        // Préparer les filtres pour la vue
        $filters = ['status' => 'archived'];
        
        // Charger la vue
        $pageTitle = 'Campagnes archivées';
        require_once __DIR__ . '/../Views/admin/campaigns/archives.php';
    }

    /**
     * Valider le token CSRF
     */
    private function validateCSRF(): bool
    {
        $token = $_POST['_token'] ?? '';
        return !empty($token) && 
               isset($_SESSION['csrf_token']) && 
               $token === $_SESSION['csrf_token'];
    }
}