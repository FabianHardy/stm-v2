<?php
/**
 * Contrôleur des promotions
 * 
 * Gère le CRUD complet des promotions par campagne.
 * 
 * @package STM
 * @version 2.0
 * @created 11/11/2025
 */

namespace App\Controllers;

use App\Models\Product;
use App\Models\Campaign;
use Core\Session;

class ProductController
{
    private Product $productModel;
    private Campaign $campaignModel;

    public function __construct()
    {
        $this->productModel = new Product();
        $this->campaignModel = new Campaign();
    }

    /**
     * Afficher la liste des promotions
     */
    public function index(): void
    {
        // Récupérer les filtres
        $filters = [
            'search' => $_GET['search'] ?? '',
            'campaign_id' => $_GET['campaign_id'] ?? '',
            'is_active' => isset($_GET['is_active']) ? (int) $_GET['is_active'] : null,
        ];
        
        // Récupérer les promotions
        $products = $this->productModel->getAll($filters);
        
        // Récupérer les statistiques
        $stats = $this->productModel->getStats();
        
        // Charger les campagnes pour le filtre
        $campaigns = $this->campaignModel->getAll();
        
        // Charger la vue
        require_once __DIR__ . '/../Views/admin/products/index.php';
    }

    /**
     * Afficher le formulaire de création
     */
    public function create(): void
    {
        // ✅ CORRECT : Charger les campagnes actives ou futures
        $campaigns = $this->campaignModel->getActiveOrFuture();
        
        // Préparer les variables pour la vue
        $errors = Session::getFlash('errors', []);
        $old = Session::getFlash('old', []);
        
        // Charger la vue
        require_once __DIR__ . '/../Views/admin/products/create.php';
    }

    /**
     * Enregistrer une nouvelle promotion
     */
    public function store(): void
    {
        // 1. Vérifier le token CSRF
        if (!$this->validateCSRF()) {
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /stm/admin/products/create');
            exit;
        }

        // 2. Récupérer les données du formulaire
        $data = [
            'code_article' => trim($_POST['code_article'] ?? ''),
            'campaign_id' => !empty($_POST['campaign_id']) ? (int) $_POST['campaign_id'] : null,
            'title_fr' => trim($_POST['title_fr'] ?? $_POST['name_fr'] ?? ''),
            'title_nl' => trim($_POST['title_nl'] ?? $_POST['name_nl'] ?? ''),
            'name_fr' => trim($_POST['name_fr'] ?? $_POST['title_fr'] ?? ''),
            'name_nl' => trim($_POST['name_nl'] ?? $_POST['title_nl'] ?? ''),
            'description_fr' => trim($_POST['description_fr'] ?? ''),
            'description_nl' => trim($_POST['description_nl'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];

        // 3. Upload des images
        $data['image_fr'] = $this->handleImageUpload('image_fr');
        $data['image_nl'] = $this->handleImageUpload('image_nl');
        
        // Si pas d'image NL, copier FR
        if (empty($data['image_nl']) && !empty($data['image_fr'])) {
            $data['image_nl'] = $data['image_fr'];
        }

        // 4. Valider les données
        $errors = $this->productModel->validate($data);
        
        if (!empty($errors)) {
            Session::setFlash('errors', $errors);
            Session::setFlash('old', $data);
            header('Location: /stm/admin/products/create');
            exit;
        }

        // 5. Créer la promotion
        try {
            $productId = $this->productModel->create($data);
            
            if ($productId) {
                Session::setFlash('success', 'Promotion créée avec succès');
                header('Location: /stm/admin/products/' . $productId);
            } else {
                Session::setFlash('error', 'Erreur lors de la création de la promotion');
                Session::setFlash('old', $data);
                header('Location: /stm/admin/products/create');
            }
        } catch (\Exception $e) {
            error_log("Erreur création produit: " . $e->getMessage());
            Session::setFlash('error', 'Erreur lors de la création : ' . $e->getMessage());
            Session::setFlash('old', $data);
            header('Location: /stm/admin/products/create');
        }
        
        exit;
    }

    /**
     * Afficher les détails d'une promotion
     * 
     * @param int $id ID de la promotion
     */
    public function show(int $id): void
    {
        // ✅ CORRECT : Utiliser findById() pas find()
        $product = $this->productModel->findById($id);
        
        if (!$product) {
            Session::setFlash('error', 'Promotion introuvable');
            header('Location: /stm/admin/products');
            exit;
        }
        
        // Charger la vue
        require_once __DIR__ . '/../Views/admin/products/show.php';
    }

    /**
     * Afficher le formulaire d'édition
     * 
     * @param int $id ID de la promotion
     */
    public function edit(int $id): void
    {
        // ✅ CORRECT : Utiliser findById() pas find()
        $product = $this->productModel->findById($id);
        
        if (!$product) {
            Session::setFlash('error', 'Promotion introuvable');
            header('Location: /stm/admin/products');
            exit;
        }
        
        // Charger les campagnes
        $campaigns = $this->campaignModel->getActiveOrFuture();
        
        // Préparer les variables
        $errors = Session::getFlash('errors', []);
        $old = Session::getFlash('old', $product);
        
        // Charger la vue
        require_once __DIR__ . '/../Views/admin/products/edit.php';
    }

    /**
     * Mettre à jour une promotion
     * 
     * @param int $id ID de la promotion
     */
    public function update(int $id): void
    {
        // 1. Vérifier que la promotion existe
        $product = $this->productModel->findById($id);
        
        if (!$product) {
            Session::setFlash('error', 'Promotion introuvable');
            header('Location: /stm/admin/products');
            exit;
        }

        // 2. Vérifier le token CSRF
        if (!$this->validateCSRF()) {
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /stm/admin/products/' . $id . '/edit');
            exit;
        }

        // 3. Récupérer les données
        $data = [
            'id' => $id,
            'code_article' => trim($_POST['code_article'] ?? ''),
            'campaign_id' => !empty($_POST['campaign_id']) ? (int) $_POST['campaign_id'] : null,
            'title_fr' => trim($_POST['title_fr'] ?? $_POST['name_fr'] ?? ''),
            'title_nl' => trim($_POST['title_nl'] ?? $_POST['name_nl'] ?? ''),
            'name_fr' => trim($_POST['name_fr'] ?? $_POST['title_fr'] ?? ''),
            'name_nl' => trim($_POST['name_nl'] ?? $_POST['title_nl'] ?? ''),
            'description_fr' => trim($_POST['description_fr'] ?? ''),
            'description_nl' => trim($_POST['description_nl'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];

        // 4. Upload des nouvelles images
        $newImageFr = $this->handleImageUpload('image_fr');
        $newImageNl = $this->handleImageUpload('image_nl');
        
        $data['image_fr'] = $newImageFr ?: $product['image_fr'];
        $data['image_nl'] = $newImageNl ?: ($newImageFr ?: $product['image_nl']);

        // 5. Valider
        $errors = $this->productModel->validate($data);
        
        if (!empty($errors)) {
            Session::setFlash('errors', $errors);
            Session::setFlash('old', $data);
            header('Location: /stm/admin/products/' . $id . '/edit');
            exit;
        }

        // 6. Mettre à jour
        try {
            $success = $this->productModel->update($id, $data);
            
            if ($success) {
                Session::setFlash('success', 'Promotion mise à jour avec succès');
                header('Location: /stm/admin/products/' . $id);
            } else {
                Session::setFlash('error', 'Erreur lors de la mise à jour');
                Session::setFlash('old', $data);
                header('Location: /stm/admin/products/' . $id . '/edit');
            }
        } catch (\Exception $e) {
            error_log("Erreur mise à jour produit: " . $e->getMessage());
            Session::setFlash('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
            Session::setFlash('old', $data);
            header('Location: /stm/admin/products/' . $id . '/edit');
        }
        
        exit;
    }

    /**
     * Supprimer une promotion
     * 
     * @param int $id ID de la promotion
     */
    public function destroy(int $id): void
    {
        // Vérifier que la promotion existe
        $product = $this->productModel->findById($id);
        
        if (!$product) {
            Session::setFlash('error', 'Promotion introuvable');
            header('Location: /stm/admin/products');
            exit;
        }

        // Supprimer
        try {
            // Supprimer les images physiques
            if (!empty($product['image_fr'])) {
                $this->deleteImage($product['image_fr']);
            }
            if (!empty($product['image_nl']) && $product['image_nl'] !== $product['image_fr']) {
                $this->deleteImage($product['image_nl']);
            }
            
            // Supprimer de la BDD
            $success = $this->productModel->delete($id);
            
            if ($success) {
                Session::setFlash('success', 'Promotion supprimée avec succès');
            } else {
                Session::setFlash('error', 'Erreur lors de la suppression');
            }
        } catch (\Exception $e) {
            error_log("Erreur suppression produit: " . $e->getMessage());
            Session::setFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }
        
        header('Location: /stm/admin/products');
        exit;
    }

    /**
     * Gérer l'upload d'une image
     * 
     * @param string $fieldName Nom du champ fichier
     * @return string|null Chemin de l'image ou null
     */
    private function handleImageUpload(string $fieldName): ?string
    {
        // Vérifier si un fichier a été uploadé
        if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        // Validation
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        $fileType = $_FILES[$fieldName]['type'];
        $fileSize = $_FILES[$fieldName]['size'];

        if (!in_array($fileType, $allowedTypes)) {
            Session::setFlash('error', 'Format d\'image non autorisé pour ' . $fieldName);
            return null;
        }

        if ($fileSize > $maxSize) {
            Session::setFlash('error', 'Image trop volumineuse (max 5MB) pour ' . $fieldName);
            return null;
        }

        // Générer nom unique et sécurisé
        $extension = pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION);
        $filename = 'product_' . uniqid() . '_' . time() . '.' . strtolower($extension);

        // Créer le dossier si nécessaire
        $uploadDir = __DIR__ . '/../../public/uploads/products/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $destination = $uploadDir . $filename;

        // Upload
        if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $destination)) {
            return '/stm/public/uploads/products/' . $filename;
        }

        return null;
    }

    /**
     * Supprimer une image physique
     * 
     * @param string $imagePath Chemin de l'image
     * @return bool
     */
    private function deleteImage(string $imagePath): bool
    {
        if (empty($imagePath)) {
            return false;
        }

        // Construire le chemin physique
        $physicalPath = __DIR__ . '/../../public' . str_replace('/stm/public', '', $imagePath);

        if (file_exists($physicalPath)) {
            return unlink($physicalPath);
        }

        return false;
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