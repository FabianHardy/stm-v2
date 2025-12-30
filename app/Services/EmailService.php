<?php
/**
 * Service d'envoi d'emails
 * 
 * Gère l'envoi des emails avec templates stockés en base de données.
 * Fallback sur les fichiers templates si non trouvé en DB.
 * 
 * @package    App\Services
 * @author     Fabian Hardy
 * @version    2.0.0
 * @created    2025/11/18
 * @modified   2025/12/30 - Support templates DB + fallback fichiers
 */

namespace App\Services;

use Core\Database;
use App\Models\EmailTemplate;

class EmailService
{
    private Database $db;
    private string $fromEmail;
    private string $fromName;
    private ?EmailTemplate $emailTemplate = null;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->fromEmail = $_ENV['MAIL_FROM_EMAIL'] ?? 'noreply@trendyfoods.com';
        $this->fromName = $_ENV['MAIL_FROM_NAME'] ?? 'Trendy Foods';
    }

    /**
     * Obtenir l'instance EmailTemplate (lazy loading)
     */
    private function getEmailTemplate(): EmailTemplate
    {
        if ($this->emailTemplate === null) {
            $this->emailTemplate = new EmailTemplate();
        }
        return $this->emailTemplate;
    }

    /**
     * Envoyer un email de confirmation de commande
     *
     * @param int $orderId ID de la commande
     * @param string $toEmail Email du destinataire
     * @param string $language Langue (fr|nl)
     * @return bool True si envoyé avec succès
     */
    public function sendOrderConfirmation(int $orderId, string $toEmail, string $language = 'fr'): bool
    {
        try {
            // Récupérer les données de la commande
            $orderData = $this->getOrderData($orderId);
            
            if (!$orderData) {
                error_log("EmailService: Commande introuvable: {$orderId}");
                return false;
            }

            // Préparer les données pour le template
            $data = [
                'campaign_name' => $language === 'nl' 
                    ? ($orderData['campaign_title_nl'] ?? $orderData['campaign_title_fr'])
                    : $orderData['campaign_title_fr'],
                'company_name' => $orderData['company_name'],
                'customer_number' => $orderData['customer_number'],
                'customer_email' => $orderData['customer_email'],
                'order_date' => date('d/m/Y à H:i', strtotime($orderData['created_at'])),
                'total_items' => $orderData['total_items'],
                'total_products' => $orderData['total_products'],
                'delivery_date' => !empty($orderData['delivery_date']) 
                    ? date('d/m/Y', strtotime($orderData['delivery_date'])) 
                    : null,
                'order_lines' => $this->renderOrderLines($orderId, $language)
            ];

            // Essayer d'abord avec le template DB
            $rendered = $this->getEmailTemplate()->render('order_confirmation', $language, $data);

            if ($rendered) {
                // Template trouvé en DB
                $subject = $rendered['subject'];
                $html = $rendered['body'];
            } else {
                // Fallback sur le fichier template
                $subject = $this->getSubjectFromFile($orderData, $language);
                $html = $this->renderTemplateFromFile($orderData, $language);
                
                if (!$html) {
                    error_log("EmailService: Ni template DB ni fichier trouvé pour order_confirmation");
                    return false;
                }
            }

            // Envoyer l'email
            return $this->send($toEmail, $subject, $html);

        } catch (\Exception $e) {
            error_log("EmailService::sendOrderConfirmation() - Erreur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer les données d'une commande pour l'email
     *
     * @param int $orderId
     * @return array|null
     */
    private function getOrderData(int $orderId): ?array
    {
        try {
            $order = $this->db->queryOne("
                SELECT 
                    o.*,
                    c.name as campaign_title_fr,
                    c.name as campaign_title_nl,
                    c.delivery_date,
                    cu.company_name,
                    cu.customer_number,
                    cu.email as customer_email,
                    (SELECT COUNT(*) FROM order_lines WHERE order_id = o.id) as total_products,
                    (SELECT SUM(quantity) FROM order_lines WHERE order_id = o.id) as total_items
                FROM orders o
                LEFT JOIN campaigns c ON o.campaign_id = c.id
                LEFT JOIN customers cu ON o.customer_id = cu.id
                WHERE o.id = :id
            ", [':id' => $orderId]);

            return $order ?: null;

        } catch (\Exception $e) {
            error_log("EmailService::getOrderData() - Erreur: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Générer le HTML des lignes de commande
     *
     * @param int $orderId
     * @param string $language
     * @return string HTML
     */
    private function renderOrderLines(int $orderId, string $language): string
    {
        $lines = $this->db->query("
            SELECT 
                ol.quantity,
                ol.product_code,
                p.name_fr as product_name_fr,
                p.name_nl as product_name_nl
            FROM order_lines ol
            LEFT JOIN products p ON ol.product_id = p.id
            WHERE ol.order_id = :order_id
            ORDER BY ol.id ASC
        ", [':order_id' => $orderId]);

        return EmailTemplate::renderOrderLines($lines, $language);
    }

    /**
     * Obtenir le sujet depuis le fichier template (fallback)
     *
     * @param array $orderData
     * @param string $language
     * @return string
     */
    private function getSubjectFromFile(array $orderData, string $language): string
    {
        $campaignName = $language === 'nl' 
            ? ($orderData['campaign_title_nl'] ?? $orderData['campaign_title_fr'])
            : $orderData['campaign_title_fr'];

        if ($language === 'nl') {
            return "Bevestiging van uw bestelling - {$campaignName}";
        }
        return "Confirmation de votre commande - {$campaignName}";
    }

    /**
     * Rendre le template depuis un fichier (fallback)
     *
     * @param array $orderData
     * @param string $language
     * @return string|null
     */
    private function renderTemplateFromFile(array $orderData, string $language): ?string
    {
        $templatePath = __DIR__ . "/../Views/emails/order_confirmation_{$language}.php";

        if (!file_exists($templatePath)) {
            // Fallback FR si NL non trouvé
            $templatePath = __DIR__ . "/../Views/emails/order_confirmation_fr.php";
            if (!file_exists($templatePath)) {
                return null;
            }
        }

        // Variables pour le template
        $order = $orderData;

        ob_start();
        require $templatePath;
        return ob_get_clean();
    }

    /**
     * Envoyer un email
     *
     * @param string $to Email destinataire
     * @param string $subject Sujet
     * @param string $html Contenu HTML
     * @return bool
     */
    private function send(string $to, string $subject, string $html): bool
    {
        // Headers pour email HTML
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            "From: {$this->fromName} <{$this->fromEmail}>",
            "Reply-To: {$this->fromEmail}",
            'X-Mailer: PHP/' . phpversion()
        ];

        $headersString = implode("\r\n", $headers);

        // Envoyer l'email
        $result = mail($to, $subject, $html, $headersString);

        if (!$result) {
            error_log("EmailService::send() - Échec envoi à: {$to}");
        }

        return $result;
    }

    /**
     * Envoyer un email générique avec template DB
     *
     * @param string $templateCode Code du template (ex: 'order_confirmation')
     * @param string $toEmail Email destinataire
     * @param string $language Langue (fr|nl)
     * @param array $data Données à injecter
     * @return bool
     */
    public function sendWithTemplate(string $templateCode, string $toEmail, string $language, array $data): bool
    {
        try {
            $rendered = $this->getEmailTemplate()->render($templateCode, $language, $data);

            if (!$rendered) {
                error_log("EmailService::sendWithTemplate() - Template introuvable: {$templateCode}");
                return false;
            }

            return $this->send($toEmail, $rendered['subject'], $rendered['body']);

        } catch (\Exception $e) {
            error_log("EmailService::sendWithTemplate() - Erreur: " . $e->getMessage());
            return false;
        }
    }
}
