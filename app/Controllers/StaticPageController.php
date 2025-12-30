<?php
/**
 * Controller StaticPageController
 * 
 * Gestion admin des pages fixes (CGU, CGV, Mentions légales, etc.)
 * 
 * @package    App\Controllers
 * @author     Fabian Hardy
 * @version    1.0.0
 * @created    2025/12/30
 */

namespace App\Controllers;

use App\Models\StaticPage;
use App\Models\Campaign;
use Core\Session;

class StaticPageController
{
    private StaticPage $staticPage;
    private Campaign $campaign;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->staticPage = new StaticPage();
        $this->campaign = new Campaign();
    }

    /**
     * Liste des pages fixes
     */
    public function index(): void
    {
        // Récupérer les pages globales
        $pages = $this->staticPage->getAll();

        // Compter les surcharges par page
        $allPages = $this->staticPage->getAllWithOverrides();
        $overrideCounts = [];
        foreach ($allPages as $page) {
            if ($page['campaign_id'] !== null) {
                $slug = $page['slug'];
                $overrideCounts[$slug] = ($overrideCounts[$slug] ?? 0) + 1;
            }
        }

        // Variables pour la vue
        $pageTitle = 'Pages fixes';
        $pages = $pages;
        $overrideCounts = $overrideCounts;

        require __DIR__ . '/../Views/admin/static_pages/index.php';
    }

    /**
     * Formulaire d'édition d'une page
     *
     * @param int $id
     */
    public function edit(int $id): void
    {
        $page = $this->staticPage->findById($id);

        if (!$page) {
            Session::setFlash('error', 'Page introuvable.');
            header('Location: /stm/admin/static-pages');
            exit;
        }

        // Récupérer les campagnes pour les surcharges
        $campaigns = $this->campaign->getAll();

        // Variables pour la vue
        $pageTitle = 'Modifier : ' . $page['title_fr'];
        $page = $page;
        $campaigns = $campaigns;
        $isOverride = $page['campaign_id'] !== null;

        require __DIR__ . '/../Views/admin/static_pages/edit.php';
    }

    /**
     * Mettre à jour une page
     *
     * @param int $id
     */
    public function update(int $id): void
    {
        // Vérifier CSRF
        if (!isset($_POST['_token']) || $_POST['_token'] !== Session::get('csrf_token')) {
            Session::setFlash('error', 'Token de sécurité invalide.');
            header('Location: /stm/admin/static-pages/' . $id . '/edit');
            exit;
        }

        // Vérifier que la page existe
        $page = $this->staticPage->findById($id);
        if (!$page) {
            Session::setFlash('error', 'Page introuvable.');
            header('Location: /stm/admin/static-pages');
            exit;
        }

        // Validation
        $errors = [];

        if (empty($_POST['title_fr'])) {
            $errors[] = 'Le titre (FR) est requis.';
        }
        if (empty($_POST['content_fr'])) {
            $errors[] = 'Le contenu (FR) est requis.';
        }

        if (!empty($errors)) {
            Session::setFlash('error', implode('<br>', $errors));
            header('Location: /stm/admin/static-pages/' . $id . '/edit');
            exit;
        }

        // Mettre à jour
        $data = [
            'title_fr' => trim($_POST['title_fr']),
            'title_nl' => trim($_POST['title_nl'] ?? ''),
            'content_fr' => $_POST['content_fr'], // Ne pas trim pour garder le HTML
            'content_nl' => $_POST['content_nl'] ?? '',
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'show_in_footer' => isset($_POST['show_in_footer']) ? 1 : 0,
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
        ];

        if ($this->staticPage->update($id, $data)) {
            Session::setFlash('success', 'Page mise à jour avec succès.');
        } else {
            Session::setFlash('error', 'Erreur lors de la mise à jour.');
        }

        header('Location: /stm/admin/static-pages');
        exit;
    }

    /**
     * Prévisualisation d'une page (HTML direct)
     *
     * @param int $id
     */
    public function preview(int $id): void
    {
        $page = $this->staticPage->findById($id);

        if (!$page) {
            http_response_code(404);
            echo '<h1>Page introuvable</h1>';
            exit;
        }

        $language = $_GET['lang'] ?? 'fr';
        $title = $language === 'nl' && !empty($page['title_nl']) ? $page['title_nl'] : $page['title_fr'];
        $content = $language === 'nl' && !empty($page['content_nl']) ? $page['content_nl'] : $page['content_fr'];

        // Afficher la prévisualisation
        ?>
<!DOCTYPE html>
<html lang="<?= $language ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - Prévisualisation</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
            color: #333;
        }
        h1, h2, h3 {
            color: #1a1a1a;
        }
        h2 {
            margin-top: 1.5em;
            padding-bottom: 0.3em;
            border-bottom: 1px solid #eee;
        }
        h3 {
            margin-top: 1.2em;
        }
        ul, ol {
            margin: 1em 0;
            padding-left: 2em;
        }
        li {
            margin: 0.5em 0;
        }
        p {
            margin: 1em 0;
        }
        em {
            color: #666;
        }
        .preview-header {
            background: #f0f0f0;
            padding: 10px 20px;
            margin: -40px -20px 30px;
            border-bottom: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="preview-header">
        <strong>Prévisualisation</strong> - Langue : <?= strtoupper($language) ?> 
        | <a href="?lang=fr">FR</a> | <a href="?lang=nl">NL</a>
    </div>
    <?= $content ?>
</body>
</html>
        <?php
        exit;
    }

    /**
     * Créer une surcharge pour une campagne
     */
    public function createOverride(): void
    {
        // Vérifier CSRF
        if (!isset($_POST['_token']) || $_POST['_token'] !== Session::get('csrf_token')) {
            Session::setFlash('error', 'Token de sécurité invalide.');
            header('Location: /stm/admin/static-pages');
            exit;
        }

        $slug = $_POST['slug'] ?? '';
        $campaignId = (int)($_POST['campaign_id'] ?? 0);

        if (empty($slug) || $campaignId <= 0) {
            Session::setFlash('error', 'Paramètres invalides.');
            header('Location: /stm/admin/static-pages');
            exit;
        }

        $newId = $this->staticPage->createOverride($slug, $campaignId);

        if ($newId) {
            Session::setFlash('success', 'Surcharge créée. Vous pouvez maintenant la personnaliser.');
            header('Location: /stm/admin/static-pages/' . $newId . '/edit');
        } else {
            Session::setFlash('error', 'Erreur lors de la création de la surcharge.');
            header('Location: /stm/admin/static-pages');
        }
        exit;
    }

    /**
     * Liste des surcharges pour une page
     *
     * @param int $id ID de la page globale
     */
    public function overrides(int $id): void
    {
        $page = $this->staticPage->findById($id);

        if (!$page || $page['campaign_id'] !== null) {
            Session::setFlash('error', 'Page introuvable ou non globale.');
            header('Location: /stm/admin/static-pages');
            exit;
        }

        // Récupérer toutes les surcharges pour ce slug
        $allPages = $this->staticPage->getAllWithOverrides();
        $overrides = array_filter($allPages, function($p) use ($page) {
            return $p['slug'] === $page['slug'] && $p['campaign_id'] !== null;
        });

        // Récupérer les campagnes pour le formulaire de création
        $campaigns = $this->campaign->getAll();

        // Variables pour la vue
        $pageTitle = 'Surcharges : ' . $page['title_fr'];
        $page = $page;
        $overrides = array_values($overrides);
        $campaigns = $campaigns;

        require __DIR__ . '/../Views/admin/static_pages/overrides.php';
    }

    /**
     * Supprimer une surcharge
     *
     * @param int $id
     */
    public function delete(int $id): void
    {
        // Vérifier CSRF via GET (simple pour delete link)
        $page = $this->staticPage->findById($id);

        if (!$page) {
            Session::setFlash('error', 'Page introuvable.');
            header('Location: /stm/admin/static-pages');
            exit;
        }

        // Ne peut supprimer que les surcharges (pas les pages globales)
        if ($page['campaign_id'] === null) {
            Session::setFlash('error', 'Impossible de supprimer une page globale.');
            header('Location: /stm/admin/static-pages');
            exit;
        }

        if ($this->staticPage->delete($id)) {
            Session::setFlash('success', 'Surcharge supprimée.');
        } else {
            Session::setFlash('error', 'Erreur lors de la suppression.');
        }

        header('Location: /stm/admin/static-pages');
        exit;
    }

    /**
     * Toggle actif/inactif (AJAX)
     *
     * @param int $id
     */
    public function toggleActive(int $id): void
    {
        header('Content-Type: application/json');

        $page = $this->staticPage->findById($id);
        if (!$page) {
            echo json_encode(['success' => false, 'message' => 'Page introuvable']);
            exit;
        }

        $newStatus = !$page['is_active'];
        $success = $this->staticPage->setActive($id, $newStatus);

        echo json_encode([
            'success' => $success,
            'is_active' => $newStatus,
            'message' => $success ? 'Statut mis à jour' : 'Erreur'
        ]);
        exit;
    }
}
