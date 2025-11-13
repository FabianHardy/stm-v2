<?php
/**
 * CampaignController
 * 
 * Contrôleur pour la gestion des campagnes promotionnelles
 * 
 * @created  2025/11/07 10:00
 * @modified 2025/11/14 01:00 - Sprint 5 : Ajout gestion nouveaux champs (customer_assignment_mode, order_password, order_type, deferred_delivery, delivery_date, quotas) + compteurs clients/promotions
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
     * Afficher la liste de toutes les campagnes avec filtres
     * 
     * @return void
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
        
        // Récupérer les statistiques
        $stats = $this->campaignModel->getStats();
        
        // Ajouter statistiques par pays
        $stats['be'] = $this->campaignModel->countByCountry('BE');
        $stats['lu'] = $this->campaignModel->countByCountry('LU');
        
        // Calcul pagination (variables attendues par la vue)
        $total = $this->campaignModel->count($filters);
        $perPage = 20;
        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $totalPages = $total > 0 ? (int)ceil($total / $perPage) : 1;
        
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
     * Enregistrer une nouvelle campagne (POST)
     * 
     * @return void
     */
    public function store(): void
    {
        // 1. Vérifier le token CSRF
        if (!$this->validateCSRF()) {
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /stm/admin/campaigns/create');
            exit;
        }

        // 2. Récupérer les données du formulaire
        $data = [
            'name' => $_POST['name'] ?? '',
            'country' => $_POST['country'] ?? '',
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] . ' 00:01:00' : '',
            'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] . ' 23:59:00' : '',
            'title_fr' => $_POST['title_fr'] ?? '',
            'description_fr' => $_POST['description_fr'] ?? '',
            'title_nl' => $_POST['title_nl'] ?? '',
            'description_nl' => $_POST['description_nl'] ?? '',
            
            // Champs Sprint 5 (Attribution clients + Paramètres commande)
            'customer_assignment_mode' => $_POST['customer_assignment_mode'] ?? 'automatic',
            'order_password' => $_POST['order_password'] ?? null,
            'order_type' => $_POST['order_type'] ?? 'W',
            'deferred_delivery' => isset($_POST['deferred_delivery']) ? 1 : 0,
            'delivery_date' => !empty($_POST['delivery_date']) ? $_POST['delivery_date'] : null,
        ];

        // 3. Valider les données
        $errors = $this->campaignModel->validate($data);
        
        if (!empty($errors)) {
            Session::setFlash('errors', $errors);
            Session::setFlash('old', $data);
            header('Location: /stm/admin/campaigns/create');
            exit;
        }

        // 4. Créer la campagne
        try {
            $campaignId = $this->campaignModel->create($data);
            
            if ($campaignId) {
                // Si mode MANUAL, gérer la liste de clients
                if ($data['customer_assignment_mode'] === 'manual' && !empty($_POST['customer_list'])) {
                    $customerList = $_POST['customer_list'];
                    $customerNumbers = explode("\n", $customerList);
                    $customerNumbers = array_map('trim', $customerNumbers);
                    $customerNumbers = array_filter($customerNumbers); // Supprimer les lignes vides
                    
                    if (!empty($customerNumbers)) {
                        $added = $this->campaignModel->addCustomersToCampaign($campaignId, $customerNumbers);
                        Session::setFlash('info', "{$added} client(s) ajouté(s) à la campagne");
                    }
                }
                
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
     * 
     * @param int $id ID de la campagne
     * @return void
     */
    public function show(int $id): void
    {
        $campaign = $this->campaignModel->findById($id);
        
        if (!$campaign) {
            Session::setFlash('error', 'Campagne introuvable');
            header('Location: /stm/admin/campaigns');
            exit;
        }
        
        // Récupérer les compteurs pour les statistiques
        $customerCount = $this->campaignModel->countCustomers($id);
        $promotionCount = $this->campaignModel->countPromotions($id);
        
        // TODO: Ajouter compteurs commandes et montant total quand module Commandes sera prêt
        $orderCount = 0;
        $totalAmount = 0;
        
        // Charger la vue
        require_once __DIR__ . '/../Views/admin/campaigns/show.php';
    }

    /**
     * Afficher le formulaire de modification
     * 
     * @param int $id ID de la campagne
     * @return void
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
        
        // Si mode manual, récupérer la liste actuelle des clients
        if ($campaign['customer_assignment_mode'] === 'manual') {
            $customers = $this->campaignModel->getCustomerNumbers($id);
            $campaign['customer_list'] = implode("\n", $customers);
        }
        
        // Charger la vue
        require_once __DIR__ . '/../Views/admin/campaigns/edit.php';
    }

    /**
     * Mettre à jour une campagne (POST)
     * 
     * @param int $id ID de la campagne
     * @return void
     */
    public function update(int $id): void
    {
        // 1. Vérifier que la campagne existe
        $campaign = $this->campaignModel->findById($id);
        
        if (!$campaign) {
            Session::setFlash('error', 'Campagne introuvable');
            header('Location: /stm/admin/campaigns');
            exit;
        }

        // 2. Vérifier le token CSRF
        if (!$this->validateCSRF()) {
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /stm/admin/campaigns/' . $id . '/edit');
            exit;
        }

        // 3. Récupérer les données du formulaire
        $data = [
            'name' => $_POST['name'] ?? '',
            'country' => $_POST['country'] ?? '',
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'start_date' => !empty($_POST['start_date']) ? substr($_POST['start_date'], 0, 10) . ' 00:01:00' : '',
            'end_date' => !empty($_POST['end_date']) ? substr($_POST['end_date'], 0, 10) . ' 23:59:00' : '',
            'title_fr' => $_POST['title_fr'] ?? '',
            'description_fr' => $_POST['description_fr'] ?? '',
            'title_nl' => $_POST['title_nl'] ?? '',
            'description_nl' => $_POST['description_nl'] ?? '',
            
            // Champs Sprint 5 (Attribution clients + Paramètres commande)
            'customer_assignment_mode' => $_POST['customer_assignment_mode'] ?? 'automatic',
            'order_password' => $_POST['order_password'] ?? null,
            'order_type' => $_POST['order_type'] ?? 'W',
            'deferred_delivery' => isset($_POST['deferred_delivery']) ? 1 : 0,
            'delivery_date' => !empty($_POST['delivery_date']) ? $_POST['delivery_date'] : null,
        ];

        // 4. Valider les données
        $errors = $this->campaignModel->validate($data);
        
        if (!empty($errors)) {
            Session::setFlash('errors', $errors);
            Session::setFlash('old', $data);
            header('Location: /stm/admin/campaigns/' . $id . '/edit');
            exit;
        }

        // 5. Mettre à jour la campagne
        try {
            $success = $this->campaignModel->update($id, $data);
            
            if ($success) {
                // Gérer le changement de mode d'attribution
                $oldMode = $campaign['customer_assignment_mode'];
                $newMode = $data['customer_assignment_mode'];
                
                // Si passage de manual à autre mode : supprimer les clients
                if ($oldMode === 'manual' && $newMode !== 'manual') {
                    $this->campaignModel->removeAllCustomers($id);
                }
                
                // Si passage à manual : gérer la nouvelle liste
                if ($newMode === 'manual') {
                    // Supprimer l'ancienne liste
                    $this->campaignModel->removeAllCustomers($id);
                    
                    // Ajouter la nouvelle liste
                    if (!empty($_POST['customer_list'])) {
                        $customerList = $_POST['customer_list'];
                        $customerNumbers = explode("\n", $customerList);
                        $customerNumbers = array_map('trim', $customerNumbers);
                        $customerNumbers = array_filter($customerNumbers);
                        
                        if (!empty($customerNumbers)) {
                            $added = $this->campaignModel->addCustomersToCampaign($id, $customerNumbers);
                            Session::setFlash('info', "{$added} client(s) ajouté(s) à la campagne");
                        }
                    }
                }
                
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
     * Supprimer une campagne (POST)
     * 
     * @param int $id ID de la campagne
     * @return void
     */
    public function destroy(int $id): void
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
     * Afficher les campagnes actives uniquement
     * 
     * @return void
     */
    public function active(): void
    {
        $campaigns = $this->campaignModel->getActive();
        $stats = $this->campaignModel->getStats();
        
        // Ajouter les compteurs pour chaque campagne
        foreach ($campaigns as &$campaign) {
            $campaign['customer_count'] = $this->campaignModel->countCustomers($campaign['id']);
            $campaign['promotion_count'] = $this->campaignModel->countPromotions($campaign['id']);
        }
        
        // Préparer les filtres pour la vue
        $filters = ['status' => 'active'];
        
        // Charger la vue
        $pageTitle = 'Campagnes actives';
        require_once __DIR__ . '/../Views/admin/campaigns/active.php';
    }

    /**
     * Afficher les campagnes archivées (terminées + inactives)
     * 
     * @return void
     */
    public function archives(): void
    {
        $campaigns = $this->campaignModel->getArchived();
        $stats = $this->campaignModel->getStats();
        
        // Ajouter les compteurs pour chaque campagne
        foreach ($campaigns as &$campaign) {
            $campaign['customer_count'] = $this->campaignModel->countCustomers($campaign['id']);
            $campaign['promotion_count'] = $this->campaignModel->countPromotions($campaign['id']);
        }
        
        // Préparer les filtres pour la vue
        $filters = ['status' => 'archived'];
        
        // Charger la vue
        $pageTitle = 'Campagnes archivées';
        require_once __DIR__ . '/../Views/admin/campaigns/archives.php';
    }

    /**
     * Activer/Désactiver une campagne (AJAX)
     * 
     * @param int $id ID de la campagne
     * @return void
     */
    public function toggleActive(int $id): void
    {
        $campaign = $this->campaignModel->findById($id);
        
        if (!$campaign) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Campagne introuvable']);
            exit;
        }
        
        try {
            $newStatus = !$campaign['is_active'];
            $success = $this->campaignModel->update($id, ['is_active' => $newStatus]);
            
            if ($success) {
                echo json_encode(['success' => true, 'is_active' => $newStatus]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur mise à jour']);
            }
        } catch (\Exception $e) {
            error_log("Erreur toggle active: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
        }
        
        exit;
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