<?php
/**
 * Contrôleur des clients
 * 
 * Gère le CRUD complet des clients, l'import depuis la base externe
 * et l'attribution des clients aux campagnes promotionnelles.
 * 
 * @package STM
 * @version 2.0
 * @created 12/11/2025 19:00
 */

namespace App\Controllers;

use App\Models\Customer;
use App\Models\Campaign;
use Core\Auth;
use Core\Request;
use Core\Session;
use Core\Database;
use Core\ExternalDatabase;

class CustomerController
{
    private Customer $customerModel;
    private Campaign $campaignModel;

    public function __construct()
    {
        $this->customerModel = new Customer();
        $this->campaignModel = new Campaign();
    }

    /**
     * Afficher la liste de tous les clients avec filtres
     * 
     * @return void
     * @created 12/11/2025 19:00
     */
    public function index(): void
    {
        // Récupérer les filtres de la requête
        $filters = [
            'search' => $_GET['search'] ?? '',
            'country' => $_GET['country'] ?? '',
            'representative' => $_GET['representative'] ?? ''
        ];
        
        // Récupérer les clients filtrés
        $customers = $this->customerModel->getAll($filters);
        
        // Récupérer les statistiques
        $stats = $this->customerModel->getStats();
        
        // Récupérer la liste des représentants pour le filtre
        $representatives = $this->getRepresentatives();
        
        // Charger la vue
        require_once __DIR__ . '/../Views/admin/customers/index.php';
    }

    /**
     * Afficher le formulaire de création
     * 
     * @return void
     * @created 12/11/2025 19:00
     */
    public function create(): void
    {
        // Préparer les variables pour la vue
        $errors = Session::getFlash('errors', []);
        $old = Session::getFlash('old', []);
        
        // Récupérer la liste des représentants
        $representatives = $this->getRepresentatives();
        
        // Charger la vue
        require_once __DIR__ . '/../Views/admin/customers/create.php';
    }

    /**
     * Enregistrer un nouveau client
     * 
     * @return void
     * @created 12/11/2025 19:00
     */
    public function store(): void
    {
        // Vérifier le token CSRF
        if (!$this->validateCSRF()) {
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /stm/admin/customers/create');
            exit;
        }

        // Récupérer les données du formulaire
        $data = [
            'customer_number' => $_POST['customer_number'] ?? '',
            'name' => $_POST['name'] ?? '',
            'country' => $_POST['country'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'address' => $_POST['address'] ?? '',
            'postal_code' => $_POST['postal_code'] ?? '',
            'city' => $_POST['city'] ?? '',
            'representative' => $_POST['representative'] ?? '',
            'type' => $_POST['type'] ?? '',
            'segment' => $_POST['segment'] ?? '',
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];

        // Valider les données
        $errors = $this->customerModel->validate($data);

        if (!empty($errors)) {
            Session::setFlash('errors', $errors);
            Session::setFlash('old', $data);
            header('Location: /stm/admin/customers/create');
            exit;
        }

        // Créer le client
        try {
            $customerId = $this->customerModel->create($data);
            
            if ($customerId) {
                Session::setFlash('success', 'Client créé avec succès');
                header('Location: /stm/admin/customers/' . $customerId);
                exit;
            } else {
                Session::setFlash('error', 'Erreur lors de la création du client');
                Session::setFlash('old', $data);
                header('Location: /stm/admin/customers/create');
                exit;
            }
        } catch (\PDOException $e) {
            error_log("Erreur création client: " . $e->getMessage());
            
            // Vérifier si c'est une erreur de doublon
            if ($e->getCode() === '23000') {
                Session::setFlash('error', 'Ce numéro de client existe déjà pour ce pays');
            } else {
                Session::setFlash('error', 'Erreur lors de la création du client');
            }
            
            Session::setFlash('old', $data);
            header('Location: /stm/admin/customers/create');
            exit;
        }
    }

    /**
     * Afficher les détails d'un client
     * 
     * @param int $id ID du client
     * @return void
     * @created 12/11/2025 19:00
     */
    public function show(int $id): void
    {
        $customer = $this->customerModel->findById($id);

        if (!$customer) {
            Session::setFlash('error', 'Client introuvable');
            header('Location: /stm/admin/customers');
            exit;
        }

        // Récupérer les campagnes attribuées à ce client
        $campaigns = $this->customerModel->getCampaigns($id);
        
        // Récupérer les commandes du client
        $orders = $this->customerModel->getOrders($id);

        // Charger la vue
        require_once __DIR__ . '/../Views/admin/customers/show.php';
    }

    /**
     * Afficher le formulaire de modification
     * 
     * @param int $id ID du client
     * @return void
     * @created 12/11/2025 19:00
     */
    public function edit(int $id): void
    {
        $customer = $this->customerModel->findById($id);

        if (!$customer) {
            Session::setFlash('error', 'Client introuvable');
            header('Location: /stm/admin/customers');
            exit;
        }

        // Préparer les variables pour la vue
        $errors = Session::getFlash('errors', []);
        $old = Session::getFlash('old', $customer);
        
        // Récupérer la liste des représentants
        $representatives = $this->getRepresentatives();

        // Charger la vue
        require_once __DIR__ . '/../Views/admin/customers/edit.php';
    }

    /**
     * Mettre à jour un client
     * 
     * @param int $id ID du client
     * @return void
     * @created 12/11/2025 19:00
     */
    public function update(int $id): void
    {
        // Vérifier le token CSRF
        if (!$this->validateCSRF()) {
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /stm/admin/customers/' . $id . '/edit');
            exit;
        }

        // Vérifier que le client existe
        $customer = $this->customerModel->findById($id);
        if (!$customer) {
            Session::setFlash('error', 'Client introuvable');
            header('Location: /stm/admin/customers');
            exit;
        }

        // Récupérer les données du formulaire
        $data = [
            'id' => $id,
            'customer_number' => $_POST['customer_number'] ?? '',
            'name' => $_POST['name'] ?? '',
            'country' => $_POST['country'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'address' => $_POST['address'] ?? '',
            'postal_code' => $_POST['postal_code'] ?? '',
            'city' => $_POST['city'] ?? '',
            'representative' => $_POST['representative'] ?? '',
            'type' => $_POST['type'] ?? '',
            'segment' => $_POST['segment'] ?? '',
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];

        // Valider les données
        $errors = $this->customerModel->validate($data);

        if (!empty($errors)) {
            Session::setFlash('errors', $errors);
            Session::setFlash('old', $data);
            header('Location: /stm/admin/customers/' . $id . '/edit');
            exit;
        }

        // Mettre à jour le client
        try {
            $success = $this->customerModel->update($id, $data);
            
            if ($success) {
                Session::setFlash('success', 'Client mis à jour avec succès');
                header('Location: /stm/admin/customers/' . $id);
                exit;
            } else {
                Session::setFlash('error', 'Erreur lors de la mise à jour du client');
                Session::setFlash('old', $data);
                header('Location: /stm/admin/customers/' . $id . '/edit');
                exit;
            }
        } catch (\PDOException $e) {
            error_log("Erreur mise à jour client: " . $e->getMessage());
            
            // Vérifier si c'est une erreur de doublon
            if ($e->getCode() === '23000') {
                Session::setFlash('error', 'Ce numéro de client existe déjà pour ce pays');
            } else {
                Session::setFlash('error', 'Erreur lors de la mise à jour du client');
            }
            
            Session::setFlash('old', $data);
            header('Location: /stm/admin/customers/' . $id . '/edit');
            exit;
        }
    }

    /**
     * Supprimer un client (POST sécurisé avec CSRF)
     * 
     * @param int $id ID du client
     * @return void
     * @created 12/11/2025 19:00
     */
    public function delete(int $id): void
    {
        // Vérifier le token CSRF
        if (!$this->validateCSRF()) {
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /stm/admin/customers');
            exit;
        }

        // Vérifier que le client existe
        $customer = $this->customerModel->findById($id);
        if (!$customer) {
            Session::setFlash('error', 'Client introuvable');
            header('Location: /stm/admin/customers');
            exit;
        }

        // Vérifier si le client a des commandes
        $hasOrders = $this->customerModel->hasOrders($id);
        if ($hasOrders) {
            Session::setFlash('error', 'Impossible de supprimer un client ayant des commandes');
            header('Location: /stm/admin/customers/' . $id);
            exit;
        }

        // Supprimer le client
        try {
            $success = $this->customerModel->delete($id);
            
            if ($success) {
                Session::setFlash('success', 'Client supprimé avec succès');
            } else {
                Session::setFlash('error', 'Erreur lors de la suppression du client');
            }
        } catch (\PDOException $e) {
            error_log("Erreur suppression client: " . $e->getMessage());
            Session::setFlash('error', 'Erreur lors de la suppression du client');
        }

        header('Location: /stm/admin/customers');
        exit;
    }

    /**
     * Prévisualiser les clients disponibles dans la base externe
     * 
     * @return void
     * @created 12/11/2025 19:00
     */
    public function importPreview(): void
    {
        // Récupérer les filtres
        $country = $_GET['country'] ?? 'BE';
        $search = $_GET['search'] ?? '';
        
        try {
            // Récupérer les clients de la DB externe
            $externalCustomers = $this->customerModel->getExternalCustomers($country, $search);
            
            // Récupérer les numéros clients déjà importés pour ce pays
            $existingNumbers = $this->customerModel->getExistingCustomerNumbers($country);
            
            // Marquer les clients déjà importés
            foreach ($externalCustomers as &$customer) {
                $customer['already_imported'] = in_array($customer['customer_number'], $existingNumbers);
            }
            
            // Charger la vue
            require_once __DIR__ . '/../Views/admin/customers/import_preview.php';
            
        } catch (\Exception $e) {
            error_log("Erreur import preview: " . $e->getMessage());
            Session::setFlash('error', 'Erreur lors de la récupération des clients externes');
            header('Location: /stm/admin/customers/create');
            exit;
        }
    }

    /**
     * Exécuter l'import des clients sélectionnés depuis la base externe
     * 
     * @return void
     * @created 12/11/2025 19:00
     */
    public function importExecute(): void
    {
        // Vérifier le token CSRF
        if (!$this->validateCSRF()) {
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /stm/admin/customers/import');
            exit;
        }

        // Récupérer les clients sélectionnés
        $selectedCustomers = $_POST['customers'] ?? [];
        $country = $_POST['country'] ?? 'BE';
        
        if (empty($selectedCustomers)) {
            Session::setFlash('error', 'Aucun client sélectionné');
            header('Location: /stm/admin/customers/import?country=' . $country);
            exit;
        }

        try {
            // Importer les clients
            $result = $this->customerModel->importFromExternal($selectedCustomers, $country);
            
            $imported = $result['imported'];
            $skipped = $result['skipped'];
            $errors = $result['errors'];
            
            // Préparer le message de succès
            $message = sprintf(
                '%d client(s) importé(s) avec succès',
                $imported
            );
            
            if ($skipped > 0) {
                $message .= sprintf(' (%d déjà existant(s))', $skipped);
            }
            
            if ($errors > 0) {
                $message .= sprintf(' (%d erreur(s))', $errors);
            }
            
            Session::setFlash('success', $message);
            
        } catch (\Exception $e) {
            error_log("Erreur import clients: " . $e->getMessage());
            Session::setFlash('error', 'Erreur lors de l\'import des clients');
        }

        header('Location: /stm/admin/customers');
        exit;
    }

    /**
     * Afficher l'interface d'attribution des campagnes à un client
     * 
     * @param int $id ID du client
     * @return void
     * @created 12/11/2025 19:00
     */
    public function assignCampaigns(int $id): void
    {
        // Vérifier que le client existe
        $customer = $this->customerModel->findById($id);
        if (!$customer) {
            Session::setFlash('error', 'Client introuvable');
            header('Location: /stm/admin/customers');
            exit;
        }

        // Récupérer toutes les campagnes actives et à venir
        $availableCampaigns = $this->campaignModel->getActiveAndUpcoming($customer['country']);
        
        // Récupérer les campagnes déjà attribuées à ce client
        $assignedCampaigns = $this->customerModel->getCampaigns($id);
        $assignedIds = array_column($assignedCampaigns, 'id');

        // Charger la vue
        require_once __DIR__ . '/../Views/admin/customers/assign_campaigns.php';
    }

    /**
     * Mettre à jour les attributions de campagnes d'un client
     * 
     * @param int $id ID du client
     * @return void
     * @created 12/11/2025 19:00
     */
    public function updateCampaignAssignments(int $id): void
    {
        // Vérifier le token CSRF
        if (!$this->validateCSRF()) {
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /stm/admin/customers/' . $id . '/assign-campaigns');
            exit;
        }

        // Vérifier que le client existe
        $customer = $this->customerModel->findById($id);
        if (!$customer) {
            Session::setFlash('error', 'Client introuvable');
            header('Location: /stm/admin/customers');
            exit;
        }

        // Récupérer les campagnes sélectionnées
        $campaignIds = $_POST['campaigns'] ?? [];

        try {
            // Mettre à jour les attributions
            $success = $this->customerModel->updateCampaignAssignments($id, $campaignIds);
            
            if ($success) {
                $count = count($campaignIds);
                Session::setFlash('success', sprintf(
                    '%d campagne(s) attribuée(s) au client',
                    $count
                ));
            } else {
                Session::setFlash('error', 'Erreur lors de la mise à jour des attributions');
            }
            
        } catch (\Exception $e) {
            error_log("Erreur attribution campagnes: " . $e->getMessage());
            Session::setFlash('error', 'Erreur lors de la mise à jour des attributions');
        }

        header('Location: /stm/admin/customers/' . $id);
        exit;
    }

    /**
     * Récupérer la liste des représentants depuis la base externe
     * 
     * @return array Liste des représentants [BE => [...], LU => [...]]
     * @created 12/11/2025 19:00
     */
    private function getRepresentatives(): array
    {
        try {
            $externalDb = ExternalDatabase::getInstance();
            
            $representatives = [
                'BE' => [],
                'LU' => []
            ];
            
            // Récupérer les représentants BE
            $resultBE = $externalDb->query("
                SELECT DISTINCT REP_NOM, REP_PRENOM 
                FROM BE_REP 
                WHERE REP_NOM IS NOT NULL 
                ORDER BY REP_NOM, REP_PRENOM
            ");
            
            foreach ($resultBE as $rep) {
                $representatives['BE'][] = trim($rep['REP_NOM'] . ' ' . $rep['REP_PRENOM']);
            }
            
            // Récupérer les représentants LU
            $resultLU = $externalDb->query("
                SELECT DISTINCT REP_NOM, REP_PRENOM 
                FROM LU_REP 
                WHERE REP_NOM IS NOT NULL 
                ORDER BY REP_NOM, REP_PRENOM
            ");
            
            foreach ($resultLU as $rep) {
                $representatives['LU'][] = trim($rep['REP_NOM'] . ' ' . $rep['REP_PRENOM']);
            }
            
            return $representatives;
            
        } catch (\Exception $e) {
            error_log("Erreur récupération représentants: " . $e->getMessage());
            return ['BE' => [], 'LU' => []];
        }
    }

    /**
     * Valider le token CSRF
     * 
     * @return bool
     * @created 12/11/2025 19:00
     */
    private function validateCSRF(): bool
    {
        $token = $_POST['_token'] ?? '';
        return !empty($token) && 
               isset($_SESSION['csrf_token']) && 
               $token === $_SESSION['csrf_token'];
    }
}