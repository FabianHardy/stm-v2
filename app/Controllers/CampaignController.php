<?php
/**
 * Contrôleur des campagnes
 * 
 * Gère le CRUD complet des campagnes promotionnelles.
 * 
 * @package STM
 * @version 2.1.0
 * @created 07/11/2025
 * @modified 14/11/2025 - Ajout enrichissement statistiques dans index() et show()
 */

namespace App\Controllers;

use App\Models\Campaign;
use Core\Session;

class CampaignController
{
    private Campaign $campaignModel;

    public function __construct()
    {
        $this->campaignModel = new Campaign();
    }

    /**
     * Afficher la liste de toutes les campagnes avec filtres et statistiques enrichies
     * 
     * @return void
     * @created 07/11/2025
     * @modified 14/11/2025 - Ajout statistiques clients et promotions pour chaque campagne
     */
    public function index(): void
    {
        // Récupérer les filtres de la requête
        $filters = [
            'search' => $_GET['search'] ?? '',
            'country' => $_GET['country'] ?? '',
            'status' => $_GET['status'] ?? ''
        ];
        
        // Récupérer les campagnes filtrées
        $campaigns = $this->campaignModel->getAll($filters);
        
        // 🆕 Enrichir chaque campagne avec ses statistiques
        foreach ($campaigns as &$campaign) {
            $campaign['customer_stats'] = $this->campaignModel->getCustomerStats($campaign['id']);
            $campaign['promotion_count'] = $this->campaignModel->countPromotions($campaign['id']);
        }
        unset($campaign); // Libérer la référence
        
        // Récupérer les statistiques globales
        $stats = $this->campaignModel->getStats();
        
        // Charger la vue
        require_once __DIR__ . '/../Views/admin/campaigns/index.php';
    }

    /**
     * Afficher le formulaire de création
     * 
     * @return void
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
     * 
     * @return void
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
            'customer_assignment_mode' => $_POST['customer_assignment_mode'] ?? 'automatic',
            'order_password' => $_POST['order_password'] ?? null,
            'order_type' => $_POST['order_type'] ?? 'W',
            'deferred_delivery' => isset($_POST['deferred_delivery']) ? 1 : 0,
            'delivery_date' => $_POST['delivery_date'] ?? null,
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
                // Si mode MANUAL et qu'il y a une liste de clients
                if ($data['customer_assignment_mode'] === 'manual' && !empty($_POST['customer_list'])) {
                    $customerNumbers = array_filter(
                        array_map('trim', explode("\n", $_POST['customer_list'])),
                        fn($num) => !empty($num)
                    );
                    
                    if (!empty($customerNumbers)) {
                        $addedCount = $this->campaignModel->addCustomersToCampaign($campaignId, $customerNumbers);
                        Session::setFlash('success', "Campagne créée avec succès. {$addedCount} client(s) ajouté(s).");
                    } else {
                        Session::setFlash('success', 'Campagne créée avec succès');
                    }
                } else {
                    Session::setFlash('success', 'Campagne créée avec succès');
                }
                
                header('Location: /stm/admin/campaigns/' . $campaignId);
            } else {
                Session::setFlash('error', 'Erreur lors de la création');
                Session::setFlash('old', $data);
                header('Location: /stm/admin/campaigns/create');
            }
        } catch (\Exception $e) {
            error_log("Erreur création campagne: " . $e->getMessage());
            Session::setFlash('error', 'Erreur lors de la création');
            Session::setFlash('old', $data);
            header('Location: /stm/admin/campaigns/create');
        }
        
        exit;
    }

    /**
     * Afficher les détails d'une campagne avec statistiques complètes
     * 
     * @param int $id ID de la campagne
     * @return void
     * @modified 14/11/2025 - Ajout $customersWithOrders
     */
    public function show(int $id): void
    {
        // Récupérer la campagne
        $campaign = $this->campaignModel->findById($id);
        
        if (!$campaign) {
            Session::setFlash('error', 'Campagne introuvable');
            header('Location: /stm/admin/campaigns');
            exit;
        }

        // Statistiques
        $customerCount = $this->campaignModel->countCustomers($id);
        $promotionCount = $this->campaignModel->countPromotions($id);
        
        // 🆕 Ajouter le nombre de clients ayant commandé
        $customersWithOrders = $this->campaignModel->countCustomersWithOrders($id);
        
        // Charger la vue
        require_once __DIR__ . '/../Views/admin/campaigns/show.php';
    }

    /**
     * Afficher le formulaire d'édition
     * 
     * @param int $id ID de la campagne
     * @return void
     */
    public function edit(int $id): void
    {
        // Récupérer la campagne
        $campaign = $this->campaignModel->findById($id);
        
        if (!$campaign) {
            Session::setFlash('error', 'Campagne introuvable');
            header('Location: /stm/admin/campaigns');
            exit;
        }

        // Si mode manual, récupérer la liste des clients
        if ($campaign['customer_assignment_mode'] === 'manual') {
            $customerNumbers = $this->campaignModel->getCustomerNumbers($id);
            $campaign['customer_list'] = implode("\n", $customerNumbers);
        }

        // Préparer les variables pour la vue
        $errors = Session::getFlash('errors', []);
        $old = Session::getFlash('old', $campaign);
        
        // Charger la vue
        require_once __DIR__ . '/../Views/admin/campaigns/edit.php';
    }

    /**
     * Mettre à jour une campagne
     * 
     * @param int $id ID de la campagne
     * @return void
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
            'customer_assignment_mode' => $_POST['customer_assignment_mode'] ?? 'automatic',
            'order_password' => $_POST['order_password'] ?? null,
            'order_type' => $_POST['order_type'] ?? 'W',
            'deferred_delivery' => isset($_POST['deferred_delivery']) ? 1 : 0,
            'delivery_date' => $_POST['delivery_date'] ?? null,
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

        // Détecter changement de mode d'attribution
        $oldMode = $campaign['customer_assignment_mode'];
        $newMode = $data['customer_assignment_mode'];

        // Mettre à jour la campagne
        try {
            $success = $this->campaignModel->update($id, $data);
            
            if ($success) {
                // Gérer les clients selon le mode
                if ($oldMode === 'manual' && $newMode !== 'manual') {
                    // Passage de manual vers autre mode : supprimer les clients
                    $this->campaignModel->removeAllCustomers($id);
                }
                
                if ($newMode === 'manual') {
                    // Mode manual : gérer la liste de clients
                    $this->campaignModel->removeAllCustomers($id); // D'abord vider
                    
                    if (!empty($_POST['customer_list'])) {
                        $customerNumbers = array_filter(
                            array_map('trim', explode("\n", $_POST['customer_list'])),
                            fn($num) => !empty($num)
                        );
                        
                        if (!empty($customerNumbers)) {
                            $addedCount = $this->campaignModel->addCustomersToCampaign($id, $customerNumbers);
                            Session::setFlash('success', "Campagne mise à jour. {$addedCount} client(s) ajouté(s).");
                        } else {
                            Session::setFlash('success', 'Campagne mise à jour');
                        }
                    } else {
                        Session::setFlash('success', 'Campagne mise à jour');
                    }
                } else {
                    Session::setFlash('success', 'Campagne mise à jour avec succès');
                }
                
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
     */
    public function destroy(int $id): void
    {
        // Vérifier le token CSRF
        if (!$this->validateCSRF()) {
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /stm/admin/campaigns');
            exit;
        }

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
     * Activer/désactiver une campagne
     * 
     * @param int $id ID de la campagne
     * @return void
     */
    public function toggleActive(int $id): void
    {
        // Vérifier le token CSRF
        if (!$this->validateCSRF()) {
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /stm/admin/campaigns');
            exit;
        }

        // Récupérer la campagne
        $campaign = $this->campaignModel->findById($id);
        
        if (!$campaign) {
            Session::setFlash('error', 'Campagne introuvable');
            header('Location: /stm/admin/campaigns');
            exit;
        }

        // Inverser le statut
        $newStatus = !$campaign['is_active'];
        
        try {
            $success = $this->campaignModel->toggleActive($id, $newStatus);
            
            if ($success) {
                $message = $newStatus ? 'Campagne activée' : 'Campagne désactivée';
                Session::setFlash('success', $message);
            } else {
                Session::setFlash('error', 'Erreur lors de la modification du statut');
            }
        } catch (\Exception $e) {
            error_log("Erreur toggle active: " . $e->getMessage());
            Session::setFlash('error', 'Erreur lors de la modification du statut');
        }
        
        header('Location: /stm/admin/campaigns');
        exit;
    }

    /**
     * Afficher les campagnes actives uniquement avec statistiques enrichies
     * 
     * @return void
     * @modified 14/11/2025 - Ajout enrichissement statistiques
     */
    public function active(): void
    {
        $filters = ['is_active' => 1];
        $campaigns = $this->campaignModel->getAll($filters);
        
        // 🆕 Enrichir chaque campagne avec ses statistiques
        foreach ($campaigns as &$campaign) {
            $campaign['customer_stats'] = $this->campaignModel->getCustomerStats($campaign['id']);
            $campaign['promotion_count'] = $this->campaignModel->countPromotions($campaign['id']);
        }
        unset($campaign); // Libérer la référence
        
        $stats = $this->campaignModel->getStats();
        
        // Charger la vue
        require_once __DIR__ . '/../Views/admin/campaigns/active.php';
    }

    /**
     * Afficher les campagnes archivées avec statistiques enrichies
     * 
     * @return void
     * @modified 14/11/2025 - Ajout enrichissement statistiques
     */
    public function archives(): void
    {
        $filters = ['is_active' => 0];
        $campaigns = $this->campaignModel->getAll($filters);
        
        // 🆕 Enrichir chaque campagne avec ses statistiques
        foreach ($campaigns as &$campaign) {
            $campaign['customer_stats'] = $this->campaignModel->getCustomerStats($campaign['id']);
            $campaign['promotion_count'] = $this->campaignModel->countPromotions($campaign['id']);
        }
        unset($campaign); // Libérer la référence
        
        $stats = $this->campaignModel->getStats();
        
        // Charger la vue
        require_once __DIR__ . '/../Views/admin/campaigns/archives.php';
    }

    /**
     * Valider le token CSRF
     * 
     * @return bool
     */
    private function validateCSRF(): bool
    {
        $token = $_POST['_token'] ?? '';
        return !empty($token) && 
               isset($_SESSION['csrf_token']) && 
               $token === $_SESSION['csrf_token'];
    }
}