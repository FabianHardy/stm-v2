<?php
/**
 * Controller : ProductController
 * 
 * Gestion des produits (CRUD complet)
 * 
 * @created 11/11/2025 21:35
 */

namespace App\Controllers;

use App\Models\Product;
use App\Models\Category;
use Core\Session;

class ProductController
{
    private Product $productModel;
    private Category $categoryModel;

    public function __construct()
    {
        $this->productModel = new Product();
        $this->categoryModel = new Category();
    }

    /**
     * Afficher la liste des produits
     * 
     * @return void
     */
    public function index(): void
    {
        // Récupérer les filtres
        $filters = [
            'search' => $_GET['search'] ?? '',
            'category_id' => $_GET['category_id'] ?? '',
            'status' => $_GET['status'] ?? ''
        ];

        // Récupérer les produits et les stats
        $products = $this->productModel->getAll($filters);
        $stats = $this->productModel->getStats();
        $categories = $this->categoryModel->getAll();

        // Récupérer les messages flash
        $success = Session::getFlash('success');
        $error = Session::getFlash('error');

        // Charger la vue
        require_once __DIR__ . '/../Views/admin/products/index.php';
    }

    /**
     * Afficher le formulaire de création
     * 
     * @return void
     */
    public function create(): void
    {
        // Récupérer les catégories pour le select
        $categories = $this->categoryModel->getAll(['status' => 'active']);

        // Récupérer les anciennes valeurs et erreurs
        $old = Session::getFlash('old', []);
        $errors = Session::getFlash('errors', []);

        // Charger la vue
        require_once __DIR__ . '/../Views/admin/products/create.php';
    }

    /**
     * Enregistrer un nouveau produit
     * 
     * @return void
     */
    public function store(): void
    {
        // Vérifier le token CSRF
        if (!$this->validateCSRF()) {
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /stm/admin/products/create');
            exit;
        }

        // Récupérer les données
        $data = [
            'product_code' => $_POST['product_code'] ?? '',
            'package_number' => $_POST['package_number'] ?? '',
            'ean' => $_POST['ean'] ?? '',
            'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            'name_fr' => $_POST['name_fr'] ?? '',
            'name_nl' => $_POST['name_nl'] ?? '',
            'description_fr' => $_POST['description_fr'] ?? '',
            'description_nl' => $_POST['description_nl'] ?? '',
            'display_order' => !empty($_POST['display_order']) ? (int)$_POST['display_order'] : 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];

        // Gérer l'upload des images
        $imageFrUploaded = $this->handleImageUpload('image_fr', 'fr');
        if ($imageFrUploaded) {
            $data['image_fr'] = $imageFrUploaded;
        }

        $imageNlUploaded = $this->handleImageUpload('image_nl', 'nl');
        if ($imageNlUploaded) {
            $data['image_nl'] = $imageNlUploaded;
        }

        // Valider
        $errors = $this->productModel->validate($data);

        if (!empty($errors)) {
            Session::setFlash('errors', $errors);
            Session::setFlash('old', $data);
            header('Location: /stm/admin/products/create');
            exit;
        }

        // Créer le produit
        try {
            $productId = $this->productModel->create($data);

            if ($productId) {
                Session::setFlash('success', 'Produit créé avec succès');
                header('Location: /stm/admin/products/' . $productId);
            } else {
                Session::setFlash('error', 'Erreur lors de la création du produit');
                Session::setFlash('old', $data);
                header('Location: /stm/admin/products/create');
            }
        } catch (\Exception $e) {
            error_log("Erreur création produit: " . $e->getMessage());
            Session::setFlash('error', 'Erreur lors de la création');
            Session::setFlash('old', $data);
            header('Location: /stm/admin/products/create');
        }

        exit;
    }

    /**
     * Afficher les détails d'un produit
     * 
     * @param int $id
     * @return void
     */
    public function show(int $id): void
    {
        // Récupérer le produit
        $product = $this->productModel->findById($id);

        if (!$product) {
            Session::setFlash('error', 'Produit introuvable');
            header('Location: /stm/admin/products');
            exit;
        }

        // Récupérer les messages flash
        $success = Session::getFlash('success');
        $error = Session::getFlash('error');

        // Charger la vue
        require_once __DIR__ . '/../Views/admin/products/show.php';
    }

    /**
     * Afficher le formulaire de modification
     * 
     * @param int $id
     * @return void
     */
    public function edit(int $id): void
    {
        // Récupérer le produit
        $product = $this->productModel->findById($id);

        if (!$product) {
            Session::setFlash('error', 'Produit introuvable');
            header('Location: /stm/admin/products');
            exit;
        }

        // Récupérer les catégories pour le select
        $categories = $this->categoryModel->getAll(['status' => 'active']);

        // Récupérer les anciennes valeurs et erreurs
        $old = Session::getFlash('old', []);
        $errors = Session::getFlash('errors', []);

        // Charger la vue
        require_once __DIR__ . '/../Views/admin/products/edit.php';
    }

    /**
     * Mettre à jour un produit
     * 
     * @param int $id
     * @return void
     */
    public function update(int $id): void
    {
        // Vérifier que le produit existe
        $product = $this->productModel->findById($id);

        if (!$product) {
            Session::setFlash('error', 'Produit introuvable');
            header('Location: /stm/admin/products');
            exit;
        }

        // Vérifier le token CSRF
        if (!$this->validateCSRF()) {
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /stm/admin/products/' . $id . '/edit');
            exit;
        }

        // Récupérer les données
        $data = [
            'product_code' => $_POST['product_code'] ?? '',
            'package_number' => $_POST['package_number'] ?? '',
            'ean' => $_POST['ean'] ?? '',
            'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            'name_fr' => $_POST['name_fr'] ?? '',
            'name_nl' => $_POST['name_nl'] ?? '',
            'description_fr' => $_POST['description_fr'] ?? '',
            'description_nl' => $_POST['description_nl'] ?? '',
            'display_order' => !empty($_POST['display_order']) ? (int)$_POST['display_order'] : 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];

        // Garder les images existantes par défaut
        $data['image_fr'] = $product['image_fr'];
        $data['image_nl'] = $product['image_nl'];

        // Gérer l'upload des nouvelles images
        $imageFrUploaded = $this->handleImageUpload('image_fr', 'fr');
        if ($imageFrUploaded) {
            // Supprimer l'ancienne image si elle existe
            if ($product['image_fr']) {
                $this->deleteImage($product['image_fr']);
            }
            $data['image_fr'] = $imageFrUploaded;
        }

        $imageNlUploaded = $this->handleImageUpload('image_nl', 'nl');
        if ($imageNlUploaded) {
            // Supprimer l'ancienne image si elle existe
            if ($product['image_nl']) {
                $this->deleteImage($product['image_nl']);
            }
            $data['image_nl'] = $imageNlUploaded;
        }

        // Valider
        $errors = $this->productModel->validate($data, $id);

        if (!empty($errors)) {
            Session::setFlash('errors', $errors);
            Session::setFlash('old', $data);
            header('Location: /stm/admin/products/' . $id . '/edit');
            exit;
        }

        // Mettre à jour
        try {
            $success = $this->productModel->update($id, $data);

            if ($success) {
                Session::setFlash('success', 'Produit mis à jour avec succès');
                header('Location: /stm/admin/products/' . $id);
            } else {
                Session::setFlash('error', 'Erreur lors de la mise à jour');
                Session::setFlash('old', $data);
                header('Location: /stm/admin/products/' . $id . '/edit');
            }
        } catch (\Exception $e) {
            error_log("Erreur mise à jour produit: " . $e->getMessage());
            Session::setFlash('error', 'Erreur lors de la mise à jour: ' . $e->getMessage());
            Session::setFlash('old', $data);
            header('Location: /stm/admin/products/' . $id . '/edit');
        }

        exit;
    }

    /**
     * Supprimer un produit
     * 
     * @param int $id
     * @return void
     */
    public function destroy(int $id): void
    {
        // Vérifier que le produit existe
        $product = $this->productModel->findById($id);

        if (!$product) {
            Session::setFlash('error', 'Produit introuvable');
            header('Location: /stm/admin/products');
            exit;
        }

        // Vérifier le token CSRF
        if (!$this->validateCSRF()) {
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /stm/admin/products');
            exit;
        }

        // Vérifier si le produit est utilisé dans des campagnes
        try {
            $isUsed = $this->productModel->isUsedByCampaigns($id);

            if ($isUsed) {
                Session::setFlash('error', 'Impossible de supprimer : ce produit est utilisé dans des campagnes');
                header('Location: /stm/admin/products/' . $id);
                exit;
            }
        } catch (\Exception $e) {
            error_log("Erreur vérification utilisation produit: " . $e->getMessage());
        }

        // Supprimer les images si elles existent
        if ($product['image_fr']) {
            $this->deleteImage($product['image_fr']);
        }
        if ($product['image_nl']) {
            $this->deleteImage($product['image_nl']);
        }

        // Supprimer le produit
        try {
            $success = $this->productModel->delete($id);

            if ($success) {
                Session::setFlash('success', 'Produit supprimé avec succès');
            } else {
                Session::setFlash('error', 'Erreur lors de la suppression');
            }
        } catch (\Exception $e) {
            error_log("Erreur suppression produit: " . $e->getMessage());
            Session::setFlash('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }

        header('Location: /stm/admin/products');
        exit;
    }

    /**
     * Gérer l'upload d'une image
     * 
     * @param string $fieldName Nom du champ de fichier
     * @param string $lang Langue (fr ou nl)
     * @return string|null Chemin de l'image ou null
     */
    private function handleImageUpload(string $fieldName, string $lang): ?string
    {
        // Vérifier si un fichier a été uploadé
        if (empty($_FILES[$fieldName]['name'])) {
            return null;
        }

        $file = $_FILES[$fieldName];

        // Vérifier les erreurs
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        // Vérifier le type de fichier
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            Session::setFlash('error', 'Type de fichier non autorisé. Formats acceptés : JPG, PNG, WEBP');
            return null;
        }

        // Vérifier la taille (max 5MB)
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            Session::setFlash('error', 'Le fichier est trop volumineux (max 5MB)');
            return null;
        }

        // Générer un nom unique
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'product_' . $lang . '_' . uniqid() . '_' . time() . '.' . $extension;

        // Définir le dossier d'upload
        $uploadDir = __DIR__ . '/../../public/uploads/products/';

        // Créer le dossier si nécessaire
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Déplacer le fichier
        $uploadPath = $uploadDir . $filename;
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return '/uploads/products/' . $filename;
        }

        return null;
    }

    /**
     * Supprimer une image
     * 
     * @param string $imagePath Chemin de l'image
     * @return void
     */
    private function deleteImage(string $imagePath): void
    {
        $fullPath = __DIR__ . '/../../public' . $imagePath;

        if (file_exists($fullPath) && is_file($fullPath)) {
            @unlink($fullPath);
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
        return !empty($token) &&
               isset($_SESSION['csrf_token']) &&
               $token === $_SESSION['csrf_token'];
    }
}