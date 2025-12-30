<?php
/**
 * Controller EmailTemplate
 *
 * Gestion des templates d'emails depuis l'interface admin
 * CRUD complet avec prévisualisation
 *
 * @package    App\Controllers
 * @author     Fabian Hardy
 * @version    1.1.0
 * @created    2025/12/30
 * @modified   2025/12/30 - Ajout vérifications de permissions
 */

namespace App\Controllers;

use App\Models\EmailTemplate;
use App\Helpers\PermissionHelper;
use Core\Database;
use Core\Session;

class EmailTemplateController
{
    private Database $db;
    private EmailTemplate $emailTemplate;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->emailTemplate = new EmailTemplate();
    }

    /**
     * Vérifie la permission de visualisation
     *
     * @return void
     */
    private function requireViewPermission(): void
    {
        if (!PermissionHelper::can('email_templates.view')) {
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
        if (!PermissionHelper::can('email_templates.edit')) {
            Session::setFlash('error', 'Vous n\'avez pas la permission de modifier les templates.');
            header('Location: /stm/admin/email-templates');
            exit;
        }
    }

    /**
     * Liste des templates d'emails
     *
     * @return void
     */
    public function index(): void
    {
        $this->requireViewPermission();

        $templates = $this->emailTemplate->getAll();

        // Permission d'édition pour la vue
        $canEdit = PermissionHelper::can('email_templates.edit');

        $pageTitle = 'Templates d\'emails';

        require __DIR__ . '/../Views/admin/email_templates/index.php';
    }

    /**
     * Formulaire d'édition d'un template
     *
     * @param int $id
     * @return void
     */
    public function edit(int $id): void
    {
        $this->requireEditPermission();

        $template = $this->emailTemplate->findById($id);

        if (!$template) {
            Session::setFlash('error', 'Template introuvable.');
            header('Location: /stm/admin/email-templates');
            exit;
        }

        // Récupérer les variables disponibles
        $templateType = $template['type'] ?? $template['code'] ?? '';
        $availableVariables = $this->emailTemplate->getAvailableVariables($templateType);

        $pageTitle = 'Modifier le template : ' . ($template['type'] ?? $template['name'] ?? 'Email');

        require __DIR__ . '/../Views/admin/email_templates/edit.php';
    }

    /**
     * Mettre à jour un template
     *
     * @param int $id
     * @return void
     */
    public function update(int $id): void
    {
        $this->requireEditPermission();

        // Vérifier le token CSRF
        if (!isset($_POST['_token']) || $_POST['_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token CSRF invalide.');
            header('Location: /stm/admin/email-templates/' . $id . '/edit');
            exit;
        }

        // Validation
        $errors = [];

        if (empty($_POST['subject_fr'])) {
            $errors[] = 'Le sujet (FR) est requis.';
        }
        if (empty($_POST['body_fr'])) {
            $errors[] = 'Le contenu (FR) est requis.';
        }

        if (!empty($errors)) {
            Session::setFlash('error', implode('<br>', $errors));
            header('Location: /stm/admin/email-templates/' . $id . '/edit');
            exit;
        }

        // Mettre à jour
        $data = [
            'subject_fr' => trim($_POST['subject_fr']),
            'subject_nl' => trim($_POST['subject_nl'] ?? ''),
            'body_fr' => $_POST['body_fr'], // Ne pas trim pour garder le HTML
            'body_nl' => $_POST['body_nl'] ?? ''
        ];

        $result = $this->emailTemplate->update($id, $data);

        if ($result) {
            Session::setFlash('success', 'Template mis à jour avec succès.');
        } else {
            Session::setFlash('error', 'Erreur lors de la mise à jour.');
        }

        header('Location: /stm/admin/email-templates');
        exit;
    }

    /**
     * Prévisualiser un template (AJAX)
     *
     * @param int $id
     * @return void
     */
    public function preview(int $id): void
    {
        $this->requireViewPermission();

        $template = $this->emailTemplate->findById($id);

        if (!$template) {
            http_response_code(404);
            echo json_encode(['error' => 'Template introuvable']);
            exit;
        }

        // Données de test pour la prévisualisation
        $testData = [
            'campaign_name' => 'Campagne Test 2025',
            'company_name' => 'Entreprise Exemple SPRL',
            'customer_number' => '123456',
            'customer_email' => 'test@exemple.com',
            'order_date' => date('d/m/Y à H:i'),
            'total_items' => '15',
            'total_products' => '5',
            'delivery_date' => date('d/m/Y', strtotime('+7 days')),
            'delivery_info' => 'Livraison différée prévue le ' . date('d/m/Y', strtotime('+7 days')),
            'order_lines' => $this->getTestOrderLines($_GET['lang'] ?? 'fr')
        ];

        $language = $_GET['lang'] ?? 'fr';

        // Rendre le template avec les données de test
        $rendered = $this->emailTemplate->render($template['code'], $language, $testData);

        if (!$rendered) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur de rendu']);
            exit;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'subject' => $rendered['subject'],
            'body' => $rendered['body']
        ]);
        exit;
    }

    /**
     * Prévisualisation directe (iframe)
     *
     * @param int $id
     * @return void
     */
    public function previewHtml(int $id): void
    {
        $this->requireViewPermission();

        $template = $this->emailTemplate->findById($id);

        if (!$template) {
            echo '<p style="color: red; padding: 20px;">Template introuvable</p>';
            exit;
        }

        $language = $_GET['lang'] ?? 'fr';

        // Données de test
        $testData = [
            'campaign_name' => 'Campagne Test 2025',
            'company_name' => 'Entreprise Exemple SPRL',
            'customer_number' => '123456',
            'customer_email' => 'test@exemple.com',
            'order_date' => date('d/m/Y à H:i'),
            'total_items' => '15',
            'total_products' => '5',
            'delivery_date' => date('d/m/Y', strtotime('+7 days')),
            'delivery_info' => 'Livraison différée prévue le ' . date('d/m/Y', strtotime('+7 days')),
            'order_lines' => $this->getTestOrderLines($language)
        ];

        $rendered = $this->emailTemplate->render($template['code'], $language, $testData);

        if ($rendered) {
            echo $rendered['body'];
        } else {
            echo '<p style="color: red; padding: 20px;">Erreur de rendu du template</p>';
        }
        exit;
    }

    /**
     * Activer/Désactiver un template (AJAX)
     *
     * @param int $id
     * @return void
     */
    public function toggleActive(int $id): void
    {
        // Vérifier la permission
        if (!PermissionHelper::can('email_templates.edit')) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission refusée']);
            exit;
        }

        // Vérifier le token CSRF
        if (!isset($_POST['_token']) || $_POST['_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['error' => 'Token CSRF invalide']);
            exit;
        }

        $template = $this->emailTemplate->findById($id);

        if (!$template) {
            http_response_code(404);
            echo json_encode(['error' => 'Template introuvable']);
            exit;
        }

        $newStatus = !$template['is_active'];
        $result = $this->emailTemplate->setActive($id, $newStatus);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => $result,
            'is_active' => $newStatus
        ]);
        exit;
    }

    /**
     * Envoyer un email de test
     *
     * @param int $id
     * @return void
     */
    public function sendTest(int $id): void
    {
        $this->requireEditPermission();

        // Vérifier le token CSRF
        if (!isset($_POST['_token']) || $_POST['_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token CSRF invalide.');
            header('Location: /stm/admin/email-templates/' . $id . '/edit');
            exit;
        }

        $template = $this->emailTemplate->findById($id);

        if (!$template) {
            Session::setFlash('error', 'Template introuvable.');
            header('Location: /stm/admin/email-templates');
            exit;
        }

        $testEmail = trim($_POST['test_email'] ?? '');
        $language = $_POST['test_language'] ?? 'fr';

        if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            Session::setFlash('error', 'Email de test invalide.');
            header('Location: /stm/admin/email-templates/' . $id . '/edit');
            exit;
        }

        // Données de test
        $testData = [
            'campaign_name' => 'Campagne Test 2025',
            'company_name' => 'Entreprise Exemple SPRL',
            'customer_number' => '123456',
            'customer_email' => $testEmail,
            'order_date' => date('d/m/Y à H:i'),
            'total_items' => '15',
            'total_products' => '5',
            'delivery_date' => date('d/m/Y', strtotime('+7 days')),
            'delivery_info' => 'Livraison différée prévue le ' . date('d/m/Y', strtotime('+7 days')),
            'order_lines' => $this->getTestOrderLines($language)
        ];

        $rendered = $this->emailTemplate->render($template['code'], $language, $testData);

        if (!$rendered) {
            Session::setFlash('error', 'Erreur de rendu du template.');
            header('Location: /stm/admin/email-templates/' . $id . '/edit');
            exit;
        }

        // Envoyer l'email
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: Trendy Foods <noreply@trendyfoods.com>',
            'X-Mailer: PHP/' . phpversion()
        ];

        $sent = mail(
            $testEmail,
            '[TEST] ' . $rendered['subject'],
            $rendered['body'],
            implode("\r\n", $headers)
        );

        if ($sent) {
            Session::setFlash('success', 'Email de test envoyé à ' . $testEmail);
        } else {
            Session::setFlash('error', 'Échec de l\'envoi de l\'email de test.');
        }

        header('Location: /stm/admin/email-templates/' . $id . '/edit');
        exit;
    }

    /**
     * Générer des lignes de commande de test
     *
     * @param string $language
     * @return string HTML
     */
    private function getTestOrderLines(string $language): string
    {
        $testLines = [
            ['product_name_fr' => 'Coca-Cola 33cl x24', 'product_name_nl' => 'Coca-Cola 33cl x24', 'quantity' => 5],
            ['product_name_fr' => 'Fanta Orange 33cl x24', 'product_name_nl' => 'Fanta Sinaasappel 33cl x24', 'quantity' => 3],
            ['product_name_fr' => 'Sprite 33cl x24', 'product_name_nl' => 'Sprite 33cl x24', 'quantity' => 2],
            ['product_name_fr' => 'Red Bull 25cl x24', 'product_name_nl' => 'Red Bull 25cl x24', 'quantity' => 4],
            ['product_name_fr' => 'Evian 50cl x24', 'product_name_nl' => 'Evian 50cl x24', 'quantity' => 1],
        ];

        return EmailTemplate::renderOrderLines($testLines, $language);
    }
}
