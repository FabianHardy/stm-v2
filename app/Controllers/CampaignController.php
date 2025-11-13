<?php
/**
 * Contrôleur des campagnes
 * 
 * Gère le CRUD complet des campagnes promotionnelles + connexion client publique
 * 
 * @package STM
 * @version 2.1.0
 * @created 07/11/2025
 * @modified 13/11/2025 - Ajout attribution clients + paramètres commande + connexion publique
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
     * @modified 13/11/2025 - Ajout compteurs clients/promotions
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
        
        // Ajouter les compteurs pour chaque campagne
        foreach ($campaigns as &$campaign) {
            $campaign['customers_count'] = $this->campaignModel->countCustomers($campaign['id']);
            $campaign['promotions_count'] = $this->campaignModel->countPromotions($campaign['id']);
        }
        
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
     * 
     * @modified 13/11/2025 - Ajout attribution clients + paramètres commande
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
            // NOUVEAUX CHAMPS
            'customer_assignment_mode' => $_POST['customer_assignment_mode'] ?? 'automatic',
            'order_type' => $_POST['order_type'] ?? 'W',
            'deferred_delivery' => isset($_POST['deferred_delivery']) ? 1 : 0,
            'delivery_date' => !empty($_POST['delivery_date']) ? $_POST['delivery_date'] : null,
        ];

        // Si mode manuel et liste fournie
        $customerList = null;
        if ($data['customer_assignment_mode'] === 'manual' && !empty($_POST['customer_list'])) {
            $customerList = trim($_POST['customer_list']);
            $customerNumbers = array_filter(array_map('trim', explode("\n", $customerList)));
            
            // Stocker temporairement pour ajouter après création campagne
            $_SESSION['pending_customers'] = $customerNumbers;
        }

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
                // Si mode manuel, ajouter les clients
                if ($data['customer_assignment_mode'] === 'manual' && isset($_SESSION['pending_customers'])) {
                    $added = $this->campaignModel->addCustomersToCampaign($campaignId, $_SESSION['pending_customers']);
                    unset($_SESSION['pending_customers']);
                    
                    if ($added > 0) {
                        Session::setFlash('success', 'Campagne créée avec succès. ' . $added . ' client(s) ajouté(s).');
                    } else {
                        Session::setFlash('success', 'Campagne créée avec succès.');
                    }
                } else {
                    Session::setFlash('success', 'Campagne créée avec succès');
                }
                
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
     * @modified 13/11/2025 - Ajout compteurs et listes clients/promotions
     */
    public function show(int $id): void
    {
        $campaign = $this->campaignModel->findById($id);
        
        if (!$campaign) {
            Session::setFlash('error', 'Campagne introuvable');
            header('Location: /stm/admin/campaigns');
            exit;
        }
        
        // Ajouter les compteurs
        $campaign['customers_count'] = $this->campaignModel->countCustomers($id);
        $campaign['promotions_count'] = $this->campaignModel->countPromotions($id);
        
        // Récupérer les listes si besoin
        $customersList = [];
        if ($campaign['customer_assignment_mode'] === 'manual') {
            $customersList = $this->campaignModel->getCustomersList($id);
        }
        
        $promotionsList = $this->campaignModel->getPromotions($id);
        
        // Charger la vue
        require_once __DIR__ . '/../Views/admin/campaigns/show.php';
    }

    /**
     * Afficher le formulaire de modification
     * 
     * @modified 13/11/2025 - Ajout liste clients pour pré-remplissage
     */
    public function edit(int $id): void
    {
        $campaign = $this->campaignModel->findById($id);
        
        if (!$campaign) {
            Session::setFlash('error', 'Campagne introuvable');
            header('Location: /stm/admin/campaigns');
            exit;
        }
        
        // Récupérer la liste des clients si mode manuel
        $customersList = [];
        if ($campaign['customer_assignment_mode'] === 'manual') {
            $customersList = $this->campaignModel->getCustomersList($id);
        }
        
        // Préparer les variables pour la vue
        $errors = Session::getFlash('errors', []);
        $old = Session::getFlash('old', []);
        
        // Charger la vue
        require_once __DIR__ . '/../Views/admin/campaigns/edit.php';
    }

    /**
     * Mettre à jour une campagne
     * 
     * @modified 13/11/2025 - Ajout gestion attribution clients + paramètres commande
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
            // NOUVEAUX CHAMPS
            'customer_assignment_mode' => $_POST['customer_assignment_mode'] ?? 'automatic',
            'order_type' => $_POST['order_type'] ?? 'W',
            'deferred_delivery' => isset($_POST['deferred_delivery']) ? 1 : 0,
            'delivery_date' => !empty($_POST['delivery_date']) ? $_POST['delivery_date'] : null,
        ];

        // Gérer les clients
        if ($data['customer_assignment_mode'] === 'manual' && !empty($_POST['customer_list'])) {
            $customerList = trim($_POST['customer_list']);
            $customerNumbers = array_filter(array_map('trim', explode("\n", $customerList)));
            
            // Supprimer anciens clients et ajouter les nouveaux
            $this->campaignModel->removeAllCustomersFromCampaign($id);
            $added = $this->campaignModel->addCustomersToCampaign($id, $customerNumbers);
            
            if ($added > 0) {
                Session::setFlash('info', $added . ' client(s) mis à jour.');
            }
        }

        // Si passage en mode automatique, supprimer la liste manuelle
        if ($data['customer_assignment_mode'] === 'automatic') {
            $this->campaignModel->removeAllCustomersFromCampaign($id);
        }

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
     */
    public function destroy(int $id): void
    {
        // Simplement appeler delete()
        $this->delete($id);
    }

    /**
     * Afficher les campagnes actives uniquement
     * 
     * @modified 13/11/2025 - Ajout compteurs clients/promotions
     */
    public function active(): void
    {
        $campaigns = $this->campaignModel->getActive();
        $stats = $this->campaignModel->getStats();
        
        // Ajouter les compteurs pour chaque campagne
        foreach ($campaigns as &$campaign) {
            $campaign['customers_count'] = $this->campaignModel->countCustomers($campaign['id']);
            $campaign['promotions_count'] = $this->campaignModel->countPromotions($campaign['id']);
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
     * @modified 13/11/2025 - Ajout compteurs clients/promotions
     */
    public function archives(): void
    {
        $campaigns = $this->campaignModel->getArchived();
        $stats = $this->campaignModel->getStats();
        
        // Ajouter les compteurs pour chaque campagne
        foreach ($campaigns as &$campaign) {
            $campaign['customers_count'] = $this->campaignModel->countCustomers($campaign['id']);
            $campaign['promotions_count'] = $this->campaignModel->countPromotions($campaign['id']);
        }
        
        // Préparer les filtres pour la vue
        $filters = ['status' => 'archived'];
        
        // Charger la vue
        $pageTitle = 'Campagnes archivées';
        require_once __DIR__ . '/../Views/admin/campaigns/archives.php';
    }

    // ============================================
    // NOUVELLES MÉTHODES - CONNEXION CLIENT PUBLIQUE
    // ============================================

    /**
     * Afficher la page de connexion client (publique)
     * 
     * @param string $uuid UUID de la campagne
     * @return void
     * @created 13/11/2025
     */
    public function showClientLogin(string $uuid): void
    {
        // Récupérer la campagne
        $campaign = $this->campaignModel->findByUuid($uuid);
        
        // Si campagne non trouvée
        if (!$campaign) {
            $campaign = null;
            $error = null;
            require_once __DIR__ . '/../Views/public/campaign_login.php';
            return;
        }
        
        // Vérifier que la campagne est active et dans les dates
        $now = date('Y-m-d');
        if (!$campaign['is_active'] || $now < $campaign['start_date'] || $now > $campaign['end_date']) {
            $error = 'Cette campagne n\'est pas disponible actuellement.';
            require_once __DIR__ . '/../Views/public/campaign_login.php';
            return;
        }
        
        // Afficher la page de connexion
        $error = Session::getFlash('error');
        require_once __DIR__ . '/../Views/public/campaign_login.php';
    }

    /**
     * Traiter la connexion client (publique)
     * 
     * @param string $uuid UUID de la campagne
     * @return void
     * @created 13/11/2025
     */
    public function processClientLogin(string $uuid): void
    {
        // Vérifier le token CSRF
        if (!$this->validateCSRF()) {
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /stm/c/' . $uuid);
            exit;
        }
        
        // Récupérer le numéro client
        $customerNumber = trim($_POST['customer_number'] ?? '');
        
        if (empty($customerNumber)) {
            Session::setFlash('error', 'Veuillez entrer votre numéro client');
            header('Location: /stm/c/' . $uuid);
            exit;
        }
        
        // Récupérer la campagne
        $campaign = $this->campaignModel->findByUuid($uuid);
        
        if (!$campaign) {
            Session::setFlash('error', 'Campagne introuvable');
            header('Location: /stm/c/' . $uuid);
            exit;
        }
        
        // Vérifier l'accès du client
        $hasAccess = $this->campaignModel->canAccessCampaign($customerNumber, $campaign['id']);
        
        if (!$hasAccess) {
            Session::setFlash('error', 'Numéro client non autorisé pour cette campagne');
            header('Location: /stm/c/' . $uuid);
            exit;
        }
        
        // Créer la session client
        $_SESSION['client_customer_number'] = $customerNumber;
        $_SESSION['client_campaign_id'] = $campaign['id'];
        $_SESSION['client_campaign_country'] = $campaign['country'];
        
        // Rediriger vers la page des promotions
        header('Location: /stm/c/' . $uuid . '/promotions');
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