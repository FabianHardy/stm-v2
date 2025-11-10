<?php
/**
 * Contrôleur des catégories de produits
 * 
 * Gère le CRUD complet des catégories avec upload d'icônes.
 * 
 * @package STM
 * @version 1.5
 * @created 11/11/2025
 * @modified 11/11/2025 - Ajout upload d'icônes (handleIconUpload, deleteIcon)
 */

namespace App\Controllers;

use App\Models\Category;
use Core\Auth;
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
     */
    public function index(): void
    {
        // Récupérer les filtres
        $filters = [
            'search' => $_GET['search'] ?? '',
            'active' => $_GET['active'] ?? ''
        ];
        
        // Récupérer les catégories
        $categories = $this->categoryModel->getAll($filters);
        
        // Statistiques
        $stats = $this->categoryModel->getStats();
        
        // Charger la vue
        require_once __DIR__ . '/../Views/admin/categories/index.php';
    }

    /**
     * Afficher le formulaire de création
     * 
     * @return void
     */
    public function create(): void
    {
        $errors = Session::getFlash('errors', []);
        $old = Session::getFlash('old', []);
        
        require_once __DIR__ . '/../Views/admin/categories/create.php';
    }

    /**
     * Enregistrer une nouvelle catégorie
     * 
     * @return void
     */
    public function store(): void
    {
        // Vérifier le token CSRF
        if (!$this->validateCSRF()) {
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /stm/admin/categories/create');
            exit;
        }

        // Gérer l'upload d'icône si présent
        $uploadedIconPath = $this->handleIconUpload();

        // Récupérer les données du formulaire
        $data = [
            'code' => strtoupper(trim($_POST['code'] ?? '')),
            'name_fr' => trim($_POST['name_fr'] ?? ''),
            'name_nl' => trim($_POST['name_nl'] ?? ''),
            'color' => trim($_POST['color'] ?? '#6B7280'),
            'icon_path' => $uploadedIconPath ?? trim($_POST['icon_path'] ?? ''),
            'display_order' => (int)($_POST['display_order'] ?? 0),
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
        try {
            $categoryId = $this->categoryModel->create($data);
            
            if ($categoryId) {
                Session::setFlash('success', 'Catégorie créée avec succès');
                header('Location: /stm/admin/categories/' . $categoryId);
            } else {
                Session::setFlash('error', 'Erreur lors de la création');
                Session::setFlash('old', $data);
                header('Location: /stm/admin/categories/create');
            }
        } catch (\Exception $e) {
            error_log("Erreur création catégorie: " . $e->getMessage());
            Session::setFlash('error', 'Erreur lors de la création: ' . $e->getMessage());
            Session::setFlash('old', $data);
            header('Location: /stm/admin/categories/create');
        }
        
        exit;
    }

    /**
     * Afficher les détails d'une catégorie
     * 
     * @param int $id ID de la catégorie
     * @return void
     */
    public function show(int $id): void
    {
        $category = $this->categoryModel->findById($id);
        
        if (!$category) {
            Session::setFlash('error', 'Catégorie introuvable');
            header('Location: /stm/admin/categories');
            exit;
        }
        
        require_once __DIR__ . '/../Views/admin/categories/show.php';
    }

    /**
     * Afficher le formulaire de modification
     * 
     * @param int $id ID de la catégorie
     * @return void
     */
    public function edit(int $id): void
    {
        $category = $this->categoryModel->findById($id);
        
        if (!$category) {
            Session::setFlash('error', 'Catégorie introuvable');
            header('Location: /stm/admin/categories');
            exit;
        }
        
        $errors = Session::getFlash('errors', []);
        $old = Session::getFlash('old', []);
        
        require_once __DIR__ . '/../Views/admin/categories/edit.php';
    }

    /**
     * Mettre à jour une catégorie
     * 
     * @param int $id ID de la catégorie
     * @return void
     */
    public function update(int $id): void
    {
        // Vérifier que la catégorie existe
        $category = $this->categoryModel->findById($id);
        
        if (!$category) {
            Session::setFlash('error', 'Catégorie introuvable');
            header('Location: /stm/admin/categories');
            exit;
        }

        // Vérifier le token CSRF
        if (!$this->validateCSRF()) {
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /stm/admin/categories/' . $id . '/edit');
            exit;
        }

        // Gérer l'upload d'une nouvelle icône si présent
        $uploadedIconPath = $this->handleIconUpload();

        // Récupérer les données du formulaire
        $data = [
            'code' => strtoupper(trim($_POST['code'] ?? '')),
            'name_fr' => trim($_POST['name_fr'] ?? ''),
            'name_nl' => trim($_POST['name_nl'] ?? ''),
            'color' => trim($_POST['color'] ?? '#6B7280'),
            'icon_path' => $uploadedIconPath ?? trim($_POST['icon_path'] ?? ''),
            'display_order' => (int)($_POST['display_order'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];

        // Si une nouvelle icône a été uploadée, supprimer l'ancienne
        if ($uploadedIconPath && $category['icon_path']) {
            $this->deleteIcon($category['icon_path']);
        }

        // Valider les données
        $errors = $this->categoryModel->validate($data, $id);
        
        if (!empty($errors)) {
            Session::setFlash('errors', $errors);
            Session::setFlash('old', $data);
            header('Location: /stm/admin/categories/' . $id . '/edit');
            exit;
        }

        // Mettre à jour la catégorie
        try {
            $success = $this->categoryModel->update($id, $data);
            
            if ($success) {
                Session::setFlash('success', 'Catégorie mise à jour avec succès');
                header('Location: /stm/admin/categories/' . $id);
            } else {
                Session::setFlash('error', 'Erreur lors de la mise à jour');
                Session::setFlash('old', $data);
                header('Location: /stm/admin/categories/' . $id . '/edit');
            }
        } catch (\Exception $e) {
            error_log("Erreur mise à jour catégorie: " . $e->getMessage());
            Session::setFlash('error', 'Erreur lors de la mise à jour: ' . $e->getMessage());
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
     */
    public function destroy(int $id): void
    {
        // Vérifier que la catégorie existe
        $category = $this->categoryModel->findById($id);
        
        if (!$category) {
            Session::setFlash('error', 'Catégorie introuvable');
            header('Location: /stm/admin/categories');
            exit;
        }

        // Vérifier le token CSRF
        if (!$this->validateCSRF()) {
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /stm/admin/categories');
            exit;
        }

        // Vérifier si la catégorie est utilisée par des produits
        try {
            $isUsed = $this->categoryModel->isUsedByProducts($id);
            
            if ($isUsed) {
                Session::setFlash('error', 'Impossible de supprimer : cette catégorie est utilisée par des produits');
                header('Location: /stm/admin/categories/' . $id);
                exit;
            }
        } catch (\Exception $e) {
            error_log("Erreur vérification utilisation catégorie: " . $e->getMessage());
        }

        // Supprimer l'icône si elle existe
        if ($category['icon_path']) {
            $this->deleteIcon($category['icon_path']);
        }

        // Supprimer la catégorie
        try {
            $success = $this->categoryModel->delete($id);
            
            if ($success) {
                Session::setFlash('success', 'Catégorie supprimée avec succès');
            } else {
                Session::setFlash('error', 'Erreur lors de la suppression');
            }
        } catch (\Exception $e) {
            error_log("Erreur suppression catégorie: " . $e->getMessage());
            Session::setFlash('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
        
        header('Location: /stm/admin/categories');
        exit;
    }

    /**
     * 🆕 Gérer l'upload d'une icône
     * 
     * Valide et enregistre une icône uploadée dans /public/uploads/categories/
     * 
     * @return string|null Chemin de l'icône ou null si pas d'upload
     */
    private function handleIconUpload(): ?string
    {
        // Vérifier si un fichier a été uploadé
        if (!isset($_FILES['icon']) || $_FILES['icon']['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        // Vérifier les erreurs d'upload
        if ($_FILES['icon']['error'] !== UPLOAD_ERR_OK) {
            Session::setFlash('error', 'Erreur lors de l\'upload du fichier');
            return null;
        }

        $file = $_FILES['icon'];
        
        // Validation du type de fichier (MIME type)
        $allowedMimeTypes = [
            'image/svg+xml',
            'image/png',
            'image/jpeg',
            'image/jpg',
            'image/webp'
        ];
        
        $fileMimeType = mime_content_type($file['tmp_name']);
        
        if (!in_array($fileMimeType, $allowedMimeTypes)) {
            Session::setFlash('error', 'Type de fichier non autorisé. Formats acceptés : SVG, PNG, JPG, WEBP');
            return null;
        }

        // Validation de la taille (max 2MB)
        $maxFileSize = 2 * 1024 * 1024; // 2MB en octets
        
        if ($file['size'] > $maxFileSize) {
            Session::setFlash('error', 'Le fichier est trop volumineux (max 2MB)');
            return null;
        }

        // Créer le dossier de destination si nécessaire
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/stm/public/uploads/categories/';
        
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Générer un nom de fichier unique
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'category_' . uniqid() . '_' . time() . '.' . $fileExtension;
        $targetPath = $uploadDir . $fileName;

        // Déplacer le fichier uploadé
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Retourner le chemin relatif pour la base de données
            return '/stm/public/uploads/categories/' . $fileName;
        } else {
            Session::setFlash('error', 'Erreur lors de l\'enregistrement du fichier');
            return null;
        }
    }

    /**
     * 🆕 Supprimer une icône du serveur
     * 
     * Supprime physiquement le fichier icône (sauf si c'est une icône /assets/)
     * 
     * @param string $iconPath Chemin de l'icône à supprimer
     * @return void
     */
    private function deleteIcon(string $iconPath): void
    {
        // Ne pas supprimer les icônes par défaut dans /assets/
        if (strpos($iconPath, '/assets/') !== false) {
            return;
        }

        // Construire le chemin complet
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . $iconPath;

        // Supprimer le fichier s'il existe
        if (file_exists($fullPath) && is_file($fullPath)) {
            unlink($fullPath);
        }
    }

    /**
     * Valider le token CSRF
     * 
     * @return bool
     */
    private function validateCSRF(): bool
    {
        $token = $_POST['_token'] ?? '';
        $sessionToken = Session::get('csrf_token');
        
        return !empty($token) && hash_equals($sessionToken, $token);
    }
}