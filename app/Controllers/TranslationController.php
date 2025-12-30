<?php
/**
 * Controller : TranslationController
 *
 * Gestion des traductions FR/NL dans l'admin
 *
 * @package    App\Controllers
 * @author     Fabian Hardy
 * @version    1.1.0
 * @created    2025/12/30
 * @modified   2025/12/30 - Ajout vérifications de permissions
 */

namespace App\Controllers;

use App\Models\Translation;
use App\Helpers\PermissionHelper;
use Core\Database;
use Core\Session;

class TranslationController
{
    private Database $db;
    private Translation $translationModel;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->translationModel = new Translation();
    }

    /**
     * Vérifie la permission de visualisation
     *
     * @return void
     */
    private function requireViewPermission(): void
    {
        if (!PermissionHelper::can('translations.view')) {
            Session::setFlash('error', 'Vous n\'avez pas accès à cette fonctionnalité.');
            header('Location: /stm/admin/dashboard');
            exit;
        }
    }

    /**
     * Vérifie la permission d'édition
     *
     * @return void
     */
    private function requireEditPermission(): void
    {
        if (!PermissionHelper::can('translations.edit')) {
            Session::setFlash('error', 'Vous n\'avez pas la permission de modifier les traductions.');
            header('Location: /stm/admin/translations');
            exit;
        }
    }

    /**
     * Liste des traductions avec filtres
     *
     * @return void
     */
    public function index(): void
    {
        $this->requireViewPermission();

        // Récupérer les filtres
        $category = $_GET['category'] ?? null;
        $search = $_GET['search'] ?? null;

        // Récupérer les traductions
        $translations = $this->translationModel->getAllForAdmin($category, $search);

        // Récupérer les catégories pour le filtre
        $categories = $this->translationModel->getCategories();

        // Compter par catégorie
        $countByCategory = $this->translationModel->countByCategory();

        // Traductions manquantes (NL vide)
        $missingCount = count($this->translationModel->getMissingTranslations());

        // Permission d'édition pour la vue
        $canEdit = PermissionHelper::can('translations.edit');

        // Charger la vue
        require __DIR__ . '/../Views/admin/translations/index.php';
    }

    /**
     * Formulaire d'édition
     *
     * @param int $id
     * @return void
     */
    public function edit(int $id): void
    {
        $this->requireEditPermission();

        $translation = $this->translationModel->findById($id);

        if (!$translation) {
            Session::setFlash('error', 'Traduction introuvable.');
            header('Location: /stm/admin/translations');
            exit;
        }

        require __DIR__ . '/../Views/admin/translations/edit.php';
    }

    /**
     * Mise à jour d'une traduction
     *
     * @param int $id
     * @return void
     */
    public function update(int $id): void
    {
        $this->requireEditPermission();

        // Vérification CSRF
        if (!isset($_POST['_token']) || $_POST['_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token CSRF invalide.');
            header('Location: /stm/admin/translations/' . $id . '/edit');
            exit;
        }

        // Récupérer la traduction existante
        $translation = $this->translationModel->findById($id);

        if (!$translation) {
            Session::setFlash('error', 'Traduction introuvable.');
            header('Location: /stm/admin/translations');
            exit;
        }

        // Valider les données
        $textFr = trim($_POST['text_fr'] ?? '');
        $textNl = trim($_POST['text_nl'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $isHtml = isset($_POST['is_html']) ? 1 : 0;

        if (empty($textFr)) {
            Session::setFlash('error', 'Le texte français est obligatoire.');
            header('Location: /stm/admin/translations/' . $id . '/edit');
            exit;
        }

        // Mettre à jour
        $this->translationModel->update($id, [
            'text_fr' => $textFr,
            'text_nl' => $textNl ?: null,
            'description' => $description ?: null,
            'is_html' => $isHtml
        ]);

        Session::setFlash('success', 'Traduction mise à jour avec succès.');

        // Rediriger vers la liste avec le même filtre de catégorie
        $redirectUrl = '/stm/admin/translations';
        if (!empty($translation['category'])) {
            $redirectUrl .= '?category=' . urlencode($translation['category']);
        }

        header('Location: ' . $redirectUrl);
        exit;
    }

    /**
     * Mise à jour rapide via AJAX (inline edit)
     *
     * @return void
     */
    public function quickUpdate(): void
    {
        header('Content-Type: application/json');

        // Vérifier la permission
        if (!PermissionHelper::can('translations.edit')) {
            echo json_encode(['success' => false, 'error' => 'Permission refusée']);
            exit;
        }

        // Récupérer les données JSON
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || !isset($input['id']) || !isset($input['field']) || !isset($input['value'])) {
            echo json_encode(['success' => false, 'error' => 'Données invalides']);
            exit;
        }

        $id = (int) $input['id'];
        $field = $input['field'];
        $value = trim($input['value']);

        // Vérifier que le champ est autorisé
        if (!in_array($field, ['text_fr', 'text_nl'])) {
            echo json_encode(['success' => false, 'error' => 'Champ non autorisé']);
            exit;
        }

        // Récupérer la traduction
        $translation = $this->translationModel->findById($id);

        if (!$translation) {
            echo json_encode(['success' => false, 'error' => 'Traduction introuvable']);
            exit;
        }

        // Mettre à jour uniquement le champ concerné
        $data = [
            'text_fr' => $field === 'text_fr' ? $value : $translation['text_fr'],
            'text_nl' => $field === 'text_nl' ? ($value ?: null) : $translation['text_nl'],
            'description' => $translation['description'],
            'is_html' => $translation['is_html']
        ];

        $this->translationModel->update($id, $data);

        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * Régénérer le cache
     *
     * @return void
     */
    public function rebuildCache(): void
    {
        $this->requireEditPermission();

        $count = $this->translationModel->rebuildCache();

        // Vérifier si le fichier cache a été créé
        $cacheFile = defined('BASE_PATH')
            ? BASE_PATH . '/storage/cache/translations.json'
            : dirname(__DIR__, 2) . '/storage/cache/translations.json';

        if (file_exists($cacheFile)) {
            Session::setFlash('success', "Cache régénéré avec succès ({$count} traductions). Fichier : " . basename($cacheFile));
        } else {
            Session::setFlash('warning', "Cache mémoire régénéré ({$count} traductions) mais le fichier cache n'a pas pu être créé. Vérifiez le dossier /storage/cache/");
        }

        header('Location: /stm/admin/translations');
        exit;
    }

    /**
     * Exporter en JSON
     *
     * @return void
     */
    public function export(): void
    {
        $this->requireViewPermission();

        $json = $this->translationModel->exportJson();

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="translations_' . date('Y-m-d') . '.json"');

        echo $json;
        exit;
    }

    /**
     * Afficher les traductions manquantes
     *
     * @return void
     */
    public function missing(): void
    {
        $this->requireViewPermission();

        $translations = $this->translationModel->getMissingTranslations();
        $categories = $this->translationModel->getCategories();
        $countByCategory = $this->translationModel->countByCategory();
        $missingCount = count($translations);

        // Permission d'édition pour la vue
        $canEdit = PermissionHelper::can('translations.edit');

        // Utiliser la même vue avec un flag
        $showMissingOnly = true;

        require __DIR__ . '/../Views/admin/translations/index.php';
    }
}
