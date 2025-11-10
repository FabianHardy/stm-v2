<?php
/**
 * Contrôleur des catégories de produits
 * 
 * Gère le CRUD complet des catégories.
 * 
 * @package STM/Controllers
 * @version 1.0.0
 * @created 11/11/2025 09:45
 * @modified 11/11/2025 09:45 - Création initiale
 */

namespace App\Controllers;

use App\Models\Category;
use Core\Session;

class CategoryController
{
    private Category $categoryModel;

    public function __construct()
    {
        $this->categoryModel = new Category();
    }

    /**
     * Afficher la liste de toutes les catégories
     * 
     * @return void
     * @created 11/11/2025 09:45
     */
    public function index(): void
    {
        // Récupérer les filtres de la requête
        $filters = [];
        
        // Filtre par statut (active/inactive)
        if (isset($_GET['status'])) {
            if ($_GET['status'] === 'active') {
                $filters['is_active'] = 1;
            } elseif ($_GET['status'] === 'inactive') {
                $filters['is_active'] = 0;
            }
        }
        
        // Récupérer toutes les catégories
        $categories = $this->categoryModel->getAll($filters);
        
        // Récupérer les statistiques
        $stats = $this->categoryModel->getStats();
        
        // Calculer les totaux pour les filtres
        $total = count($categories);
        
        // Charger la vue
        require_once __DIR__ . '/../Views/admin/categories/index.php';
    }

    /**
     * Afficher une catégorie spécifique
     * 
     * @param int $id ID de la catégorie
     * @return void
     * @created 11/11/2025 09:45
     */
    public function show(int $id): void
    {
        $category = $this->categoryModel->findById($id);
        
        if (!$category) {
            Session::setFlash('error', 'Catégorie introuvable');
            header('Location: /stm/admin/categories');
            exit;
        }
        
        // Compter les produits associés (optionnel, besoin du ProductModel)
        // $productsCount = $productModel->countByCategory($id);
        
        // Charger la vue
        require_once __DIR__ . '/../Views/admin/categories/show.php';
    }

    /**
     * Afficher le formulaire de création
     * 
     * @return void
     * @created 11/11/2025 09:45
     */
    public function create(): void
    {
        // Préparer les variables pour la vue
        $errors = Session::getFlash('errors', []);
        $old = Session::getFlash('old', []);
        
        // Charger la vue
        require_once __DIR__ . '/../Views/admin/categories/create.php';
    }

    /**
     * Enregistrer une nouvelle catégorie
     * 
     * @return void
     * @created 11/11/2025 09:45
     */
    public function store(): void
    {
        // Vérifier le token CSRF
        if (!$this->validateCSRF()) {
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /stm/admin/categories/create');
            exit;
        }

        // Récupérer les données du formulaire
        $data = [
            'code' => trim($_POST['code'] ?? ''),
            'name_fr' => trim($_POST['name_fr'] ?? ''),
            'name_nl' => trim($_POST['name_nl'] ?? ''),
            'color' => trim($_POST['color'] ?? ''),
            'icon_path' => trim($_POST['icon_path'] ?? ''),
            'display_order' => (int) ($_POST['display_order'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];

        // Valider les données
        $errors = $this->categoryModel->validate($data);

        if (!empty($errors)) {
            Session::setFlash('errors', $errors);
            Session::setFlash('old', $data);
            header('Location: /stm/admin/categories/create');
            exit;
        }

        // Créer la catégorie
        $categoryId = $this->categoryModel->create($data);

        if ($categoryId) {
            Session::setFlash('success', 'Catégorie créée avec succès');
            header('Location: /stm/admin/categories/' . $categoryId);
        } else {
            Session::setFlash('error', 'Erreur lors de la création de la catégorie');
            Session::setFlash('old', $data);
            header('Location: /stm/admin/categories/create');
        }
        exit;
    }

    /**
     * Afficher le formulaire de modification
     * 
     * @param int $id ID de la catégorie
     * @return void
     * @created 11/11/2025 09:45
     */
    public function edit(int $id): void
    {
        $category = $this->categoryModel->findById($id);
        
        if (!$category) {
            Session::setFlash('error', 'Catégorie introuvable');
            header('Location: /stm/admin/categories');
            exit;
        }
        
        // Préparer les variables pour la vue
        $errors = Session::getFlash('errors', []);
        $old = Session::getFlash('old', $category);
        
        // Charger la vue
        require_once __DIR__ . '/../Views/admin/categories/edit.php';
    }

    /**
     * Mettre à jour une catégorie
     * 
     * @param int $id ID de la catégorie
     * @return void
     * @created 11/11/2025 09:45
     */
    public function update(int $id): void
    {
        // Vérifier le token CSRF
        if (!$this->validateCSRF()) {
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /stm/admin/categories/' . $id . '/edit');
            exit;
        }

        // Vérifier que la catégorie existe
        $category = $this->categoryModel->findById($id);
        
        if (!$category) {
            Session::setFlash('error', 'Catégorie introuvable');
            header('Location: /stm/admin/categories');
            exit;
        }

        // Récupérer les données du formulaire
        $data = [
            'code' => trim($_POST['code'] ?? ''),
            'name_fr' => trim($_POST['name_fr'] ?? ''),
            'name_nl' => trim($_POST['name_nl'] ?? ''),
            'color' => trim($_POST['color'] ?? ''),
            'icon_path' => trim($_POST['icon_path'] ?? ''),
            'display_order' => (int) ($_POST['display_order'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];

        // Valider les données
        $errors = $this->categoryModel->validate($data, $id);

        if (!empty($errors)) {
            Session::setFlash('errors', $errors);
            Session::setFlash('old', $data);
            header('Location: /stm/admin/categories/' . $id . '/edit');
            exit;
        }

        // Mettre à jour la catégorie
        $success = $this->categoryModel->update($id, $data);

        if ($success) {
            Session::setFlash('success', 'Catégorie mise à jour avec succès');
            header('Location: /stm/admin/categories/' . $id);
        } else {
            Session::setFlash('error', 'Erreur lors de la mise à jour de la catégorie');
            Session::setFlash('old', $data);
            header('Location: /stm/admin/categories/' . $id . '/edit');
        }
        exit;
    }

    /**
     * Supprimer une catégorie
     * 
     * @param int $id ID de la catégorie
     * @return void
     * @created 11/11/2025 09:45
     */
    public function destroy(int $id): void
    {
        // Vérifier le token CSRF
        if (!$this->validateCSRF()) {
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /stm/admin/categories');
            exit;
        }

        // Vérifier que la catégorie existe
        $category = $this->categoryModel->findById($id);
        
        if (!$category) {
            Session::setFlash('error', 'Catégorie introuvable');
            header('Location: /stm/admin/categories');
            exit;
        }

        // Supprimer la catégorie
        $success = $this->categoryModel->delete($id);

        if ($success) {
            Session::setFlash('success', 'Catégorie supprimée avec succès');
        } else {
            Session::setFlash('error', 'Impossible de supprimer cette catégorie (produits associés)');
        }
        
        header('Location: /stm/admin/categories');
        exit;
    }

    /**
     * Activer/Désactiver une catégorie
     * 
     * @param int $id ID de la catégorie
     * @return void
     * @created 11/11/2025 09:45
     */
    public function toggleActive(int $id): void
    {
        // Vérifier le token CSRF
        if (!$this->validateCSRF()) {
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /stm/admin/categories');
            exit;
        }

        $category = $this->categoryModel->findById($id);
        
        if (!$category) {
            Session::setFlash('error', 'Catégorie introuvable');
            header('Location: /stm/admin/categories');
            exit;
        }

        // Inverser le statut
        $newStatus = $category['is_active'] ? 0 : 1;
        
        $success = $this->categoryModel->update($id, [
            'code' => $category['code'],
            'name_fr' => $category['name_fr'],
            'name_nl' => $category['name_nl'],
            'color' => $category['color'],
            'icon_path' => $category['icon_path'],
            'display_order' => $category['display_order'],
            'is_active' => $newStatus
        ]);

        if ($success) {
            $status = $newStatus ? 'activée' : 'désactivée';
            Session::setFlash('success', "Catégorie $status avec succès");
        } else {
            Session::setFlash('error', 'Erreur lors du changement de statut');
        }
        
        header('Location: /stm/admin/categories');
        exit;
    }

    /**
     * Valider le token CSRF
     * 
     * @return bool
     * @created 11/11/2025 09:45
     */
    private function validateCSRF(): bool
    {
        $token = $_POST['csrf_token'] ?? '';
        return !empty($token) && 
               isset($_SESSION['csrf_token']) && 
               $token === $_SESSION['csrf_token'];
    }
}
