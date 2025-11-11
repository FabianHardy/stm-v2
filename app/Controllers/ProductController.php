<?php
/**
 * ProductController - Gestion des produits
 * 
 * @created 11/11/2025
 * @modified 12/11/2025 00:15 - Version 3 avec campagnes obligatoires
 */

namespace App\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Campaign;
use Core\Session;

class ProductController
{
    private Product $productModel;

    public function __construct()
    {
        $this->productModel = new Product();
    }

    /**
     * Afficher la liste des produits avec filtres
     */
    public function index(): void
    {
        // Récupérer les filtres
        $filters = [
            'search' => $_GET['search'] ?? '',
            'category' => $_GET['category'] ?? '',
            'status' => $_GET['status'] ?? ''
        ];

        // Récupérer les produits filtrés
        $products = $this->productModel->getAll($filters);

        // Récupérer les statistiques
        $stats = $this->productModel->getStats();

        // Récupérer les catégories pour le filtre
        $categoryModel = new Category();
        $categories = $categoryModel->getAll();

        // Charger la vue
        require_once __DIR__ . '/../Views/admin/products/index.php';
    }

    /**
     * Afficher le formulaire de création
     */
    public function create(): void
    {
        // Récupérer les catégories
        $categoryModel = new Category();
        $categories = $categoryModel->getAll();

        // Récupérer les campagnes ACTIVES OU FUTURES (pas les passées)
        $campaignModel = new Campaign();
        
        if (method_exists($campaignModel, 'getActiveOrFuture')) {
            $campaigns = $campaignModel->getActiveOrFuture();
        } elseif (method_exists($campaignModel, 'getActive')) {
            $campaigns = $campaignModel->getActive();
        } else {
            $campaigns = $campaignModel->getAll();
        }

        // Debug: vérifier si on a des campagnes
        error_log("ProductController::create() - Nombre de campagnes: " . count($campaigns));

        // Charger la vue
        require_once __DIR__ . '/../Views/admin/products/create.php';
    }

    /**
     * Enregistrer un nouveau produit
     */
    public function store(): void
    {
        // Validation CSRF
        if (!$this->validateCSRF()) {
            Session::set('error', 'Token de sécurité invalide');
            header('Location: /stm/admin/products/create');
            exit;
        }

        // Récupérer les données du formulaire
        $data = [
            'campaign_id' => $_POST['campaign_id'] ?? null,
            'category_id' => $_POST['category_id'] ?? null,
            'product_code' => trim($_POST['product_code'] ?? ''),
            'name_fr' => trim($_POST['name_fr'] ?? ''),
            'name_nl' => trim($_POST['name_nl'] ?? ''),
            'description_fr' => trim($_POST['description_fr'] ?? ''),
            'description_nl' => trim($_POST['description_nl'] ?? ''),
            'display_order' => (int)($_POST['display_order'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];

        // Validation
        $errors = $this->productModel->validate($data);

        if (!empty($errors)) {
            Session::set('errors', $errors);
            Session::set('old', $data);
            header('Location: /stm/admin/products/create');
            exit;
        }

        // Upload image FR (OBLIGATOIRE)
        try {
            if (!empty($_FILES['image_fr']['tmp_name'])) {
                $data['image_fr'] = $this->handleImageUpload($_FILES['image_fr'], 'fr');
            } else {
                Session::set('error', 'L\'image française est obligatoire');
                Session::set('old', $data);
                header('Location: /stm/admin/products/create');
                exit;
            }
        } catch (\Exception $e) {
            Session::set('error', 'Erreur image FR : ' . $e->getMessage());
            Session::set('old', $data);
            header('Location: /stm/admin/products/create');
            exit;
        }

        // Upload image NL (OPTIONNEL - sinon copier FR)
        try {
            if (!empty($_FILES['image_nl']['tmp_name'])) {
                $data['image_nl'] = $this->handleImageUpload($_FILES['image_nl'], 'nl');
            } else {
                // Copier l'image FR vers NL
                $data['image_nl'] = $data['image_fr'];
            }
        } catch (\Exception $e) {
            // Si erreur, utiliser l'image FR
            $data['image_nl'] = $data['image_fr'];
        }

        // Créer le produit
        $productId = $this->productModel->create($data);

        if ($productId) {
            Session::set('success', 'Produit créé avec succès');
            header('Location: /stm/admin/products/' . $productId);
        } else {
            Session::set('error', 'Erreur lors de la création du produit');
            Session::set('old', $data);
            header('Location: /stm/admin/products/create');
        }
        exit;
    }

    /**
     * Afficher les détails d'un produit
     */
    public function show(int $id): void
    {
        $product = $this->productModel->find($id);

        if (!$product) {
            Session::set('error', 'Produit non trouvé');
            header('Location: /stm/admin/products');
            exit;
        }

        // Charger la vue
        require_once __DIR__ . '/../Views/admin/products/show.php';
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(int $id): void
    {
        $product = $this->productModel->find($id);

        if (!$product) {
            Session::set('error', 'Produit non trouvé');
            header('Location: /stm/admin/products');
            exit;
        }

        // Récupérer les catégories
        $categoryModel = new Category();
        $categories = $categoryModel->getAll();

        // Récupérer les campagnes ACTIVES OU FUTURES (pas les passées)
        $campaignModel = new Campaign();
        
        if (method_exists($campaignModel, 'getActiveOrFuture')) {
            $campaigns = $campaignModel->getActiveOrFuture();
        } elseif (method_exists($campaignModel, 'getActive')) {
            $campaigns = $campaignModel->getActive();
        } else {
            $campaigns = $campaignModel->getAll();
        }

        // Debug
        error_log("ProductController::edit() - Nombre de campagnes: " . count($campaigns));

        // Charger la vue
        require_once __DIR__ . '/../Views/admin/products/edit.php';
    }

    /**
     * Mettre à jour un produit
     */
    public function update(int $id): void
    {
        // Validation CSRF
        if (!$this->validateCSRF()) {
            Session::set('error', 'Token de sécurité invalide');
            header('Location: /stm/admin/products/' . $id . '/edit');
            exit;
        }

        // Récupérer le produit existant
        $product = $this->productModel->find($id);

        if (!$product) {
            Session::set('error', 'Produit non trouvé');
            header('Location: /stm/admin/products');
            exit;
        }

        // Récupérer les données
        $data = [
            'campaign_id' => $_POST['campaign_id'] ?? null,
            'category_id' => $_POST['category_id'] ?? null,
            'product_code' => trim($_POST['product_code'] ?? ''),
            'name_fr' => trim($_POST['name_fr'] ?? ''),
            'name_nl' => trim($_POST['name_nl'] ?? ''),
            'description_fr' => trim($_POST['description_fr'] ?? ''),
            'description_nl' => trim($_POST['description_nl'] ?? ''),
            'display_order' => (int)($_POST['display_order'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'image_fr' => $product['image_fr'], // Garder ancienne par défaut
            'image_nl' => $product['image_nl']
        ];

        // Validation
        $errors = $this->productModel->validate($data);

        if (!empty($errors)) {
            Session::set('errors', $errors);
            Session::set('old', $data);
            header('Location: /stm/admin/products/' . $id . '/edit');
            exit;
        }

        // Upload nouvelle image FR si fournie
        if (!empty($_FILES['image_fr']['tmp_name'])) {
            try {
                // Supprimer ancienne image FR
                $this->deleteImage($product['image_fr']);

                // Upload nouvelle
                $data['image_fr'] = $this->handleImageUpload($_FILES['image_fr'], 'fr');
            } catch (\Exception $e) {
                Session::set('error', 'Erreur image FR : ' . $e->getMessage());
            }
        }

        // Upload nouvelle image NL si fournie, sinon copier FR
        if (!empty($_FILES['image_nl']['tmp_name'])) {
            try {
                // Supprimer ancienne image NL (si différente de FR)
                if ($product['image_nl'] !== $product['image_fr']) {
                    $this->deleteImage($product['image_nl']);
                }

                // Upload nouvelle
                $data['image_nl'] = $this->handleImageUpload($_FILES['image_nl'], 'nl');
            } catch (\Exception $e) {
                // Utiliser FR en cas d'erreur
                $data['image_nl'] = $data['image_fr'];
            }
        } else {
            // Si pas de nouvel upload NL, copier FR
            $data['image_nl'] = $data['image_fr'];
        }

        // Mettre à jour
        if ($this->productModel->update($id, $data)) {
            Session::set('success', 'Produit modifié avec succès');
            header('Location: /stm/admin/products/' . $id);
        } else {
            Session::set('error', 'Erreur lors de la modification');
            header('Location: /stm/admin/products/' . $id . '/edit');
        }
        exit;
    }

    /**
     * Supprimer un produit
     */
    public function destroy(int $id): void
    {
        // Validation CSRF
        if (!$this->validateCSRF()) {
            Session::set('error', 'Token de sécurité invalide');
            header('Location: /stm/admin/products');
            exit;
        }

        $product = $this->productModel->find($id);

        if (!$product) {
            Session::set('error', 'Produit non trouvé');
            header('Location: /stm/admin/products');
            exit;
        }

        // Supprimer les images
        $this->deleteImage($product['image_fr']);
        if ($product['image_nl'] !== $product['image_fr']) {
            $this->deleteImage($product['image_nl']);
        }

        // Supprimer le produit
        if ($this->productModel->delete($id)) {
            Session::set('success', 'Produit supprimé avec succès');
        } else {
            Session::set('error', 'Erreur lors de la suppression');
        }

        header('Location: /stm/admin/products');
        exit;
    }

    /**
     * Gérer l'upload d'une image avec NOM ALÉATOIRE
     * 
     * @param array $file Fichier $_FILES
     * @param string $lang 'fr' ou 'nl'
     * @return string Chemin relatif de l'image
     * @throws \Exception
     */
    private function handleImageUpload(array $file, string $lang): string
    {
        // Vérifier si fichier uploadé
        if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception("Aucun fichier uploadé ou erreur d'upload");
        }

        // Validation type MIME
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            throw new \Exception("Type de fichier non autorisé. Formats acceptés : JPG, PNG, WEBP");
        }

        // Validation taille (5MB max)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new \Exception("L'image ne doit pas dépasser 5MB");
        }

        // Générer nom ALÉATOIRE (sécurité)
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $randomName = 'product_' . $lang . '_' . uniqid() . '_' . time() . '.' . $extension;

        // Chemin de destination
        $uploadDir = __DIR__ . '/../../public/uploads/products/';
        $filePath = $uploadDir . $randomName;

        // Créer dossier si inexistant
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Déplacer le fichier
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new \Exception("Erreur lors de l'enregistrement de l'image");
        }

        // Retourner chemin relatif
        return '/uploads/products/' . $randomName;
    }

    /**
     * Supprimer une image du serveur
     * 
     * @param string|null $imagePath Chemin relatif de l'image
     * @return bool
     */
    private function deleteImage(?string $imagePath): bool
    {
        if (empty($imagePath)) {
            return false;
        }

        $fullPath = __DIR__ . '/../../public' . $imagePath;

        if (file_exists($fullPath)) {
            return unlink($fullPath);
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
        return $token === Session::get('csrf_token');
    }
}