<?php
/**
 * EmailService.php
 * 
 * Service de gestion des emails (confirmation commandes)
 * Utilise la fonction mail() PHP native (serveur O2switch)
 * 
 * @created  2025/11/18 10:00
 */

namespace App\Services;

use Core\Database;

class EmailService
{
    private string $fromEmail;
    private string $fromName;
    private Database $db;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->fromEmail = 'noreply@trendyfoods.com';
        $this->fromName = 'Trendy Foods - Commandes';
        $this->db = Database::getInstance();
    }

    /**
     * Envoyer email de confirmation de commande
     * 
     * @param int $orderId ID de la commande
     * @param string $toEmail Email du destinataire
     * @param string $language Langue (fr|nl)
     * @return bool True si envoyé avec succès
     */
    public function sendOrderConfirmation(int $orderId, string $toEmail, string $language = 'fr'): bool
    {
        try {
            // 1. Récupérer les données de la commande
            $orderData = $this->getOrderData($orderId);
            
            if (!$orderData) {
                error_log("EmailService: Commande #{$orderId} introuvable");
                return false;
            }

            // 2. Valider l'email
            if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
                error_log("EmailService: Email invalide: {$toEmail}");
                return false;
            }

            // 3. Générer le contenu HTML selon la langue
            $html = $this->renderTemplate($orderData, $language);
            
            // 4. Préparer le sujet selon la langue
            $subject = $this->getSubject($orderData, $language);
            
            // 5. Envoyer l'email
            $sent = $this->send($toEmail, $subject, $html);
            
            // 6. Logger le résultat
            if ($sent) {
                error_log("EmailService: Email confirmation envoyé pour commande #{$orderId} à {$toEmail}");
            } else {
                error_log("EmailService: Échec envoi email pour commande #{$orderId}");
            }
            
            return $sent;
            
        } catch (\Exception $e) {
            error_log("EmailService: Erreur sendOrderConfirmation: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer les données complètes de la commande
     * 
     * @param int $orderId ID de la commande
     * @return array|null Données de la commande ou null
     */
    private function getOrderData(int $orderId): ?array
    {
        try {
            // Récupérer la commande
            $query = "
                SELECT 
                    o.id,
                    o.order_number,
                    o.customer_id,
                    o.campaign_id,
                    o.customer_email,
                    o.status,
                    o.created_at,
                    c.company_name,
                    c.customer_number,
                    c.country,
                    camp.name as campaign_name,
                    camp.title_fr as campaign_title_fr,
                    camp.title_nl as campaign_title_nl,
                    camp.deferred_delivery,
                    camp.delivery_date
                FROM orders o
                JOIN customers c ON o.customer_id = c.id
                JOIN campaigns camp ON o.campaign_id = camp.id
                WHERE o.id = :order_id
            ";
            
            $orderResult = $this->db->query($query, [':order_id' => $orderId]);
            
            if (empty($orderResult)) {
                return null;
            }
            
            $order = $orderResult[0];
            
            // Récupérer les lignes de commande
            $queryLines = "
                SELECT 
                    ol.quantity,
                    p.name_fr,
                    p.name_nl,
                    p.product_code
                FROM order_lines ol
                JOIN products p ON ol.product_id = p.id
                WHERE ol.order_id = :order_id
                ORDER BY p.name_fr
            ";
            
            $lines = $this->db->query($queryLines, [':order_id' => $orderId]);
            
            $order['lines'] = $lines;
            
            return $order;
            
        } catch (\Exception $e) {
            error_log("EmailService: Erreur getOrderData: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Générer le sujet de l'email selon la langue
     * 
     * @param array $orderData Données de la commande
     * @param string $language Langue
     * @return string Sujet de l'email
     */
    private function getSubject(array $orderData, string $language): string
    {
        $campaignName = $language === 'nl' 
            ? $orderData['campaign_title_nl'] 
            : $orderData['campaign_title_fr'];
        
        if ($language === 'nl') {
            return "Bevestiging van uw bestelling - {$campaignName}";
        } else {
            return "Confirmation de votre commande - {$campaignName}";
        }
    }

    /**
     * Générer le HTML de l'email à partir du template
     * 
     * @param array $orderData Données de la commande
     * @param string $language Langue (fr|nl)
     * @return string HTML de l'email
     */
    private function renderTemplate(array $orderData, string $language): string
    {
        // Démarrer la capture de sortie
        ob_start();
        
        // Inclure le bon template
        $templatePath = __DIR__ . "/../Views/emails/order_confirmation_{$language}.php";
        
        if (!file_exists($templatePath)) {
            error_log("EmailService: Template introuvable: {$templatePath}");
            $templatePath = __DIR__ . "/../Views/emails/order_confirmation_fr.php";
        }
        
        // Variables disponibles dans le template
        $order = $orderData;
        
        require $templatePath;
        
        // Récupérer le contenu
        $html = ob_get_clean();
        
        return $html;
    }

    /**
     * Envoyer l'email via fonction mail() PHP
     * 
     * @param string $to Email destinataire
     * @param string $subject Sujet
     * @param string $html Contenu HTML
     * @return bool True si envoyé
     */
    private function send(string $to, string $subject, string $html): bool
    {
        // Headers optimisés pour éviter le spam
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            "From: Trendy Foods <{$this->fromEmail}>",
            "Reply-To: contact@trendyfoods.com",
            'X-Mailer: PHP/' . phpversion(),
            'X-Priority: 3',
            'Importance: Normal',
            'Message-ID: <' . time() . '.' . md5($to . time()) . '@trendyfoods.com>',
            'Return-Path: ' . $this->fromEmail,
        ];
        
        // Joindre les headers
        $headersString = implode("\r\n", $headers);
        
        // Paramètres additionnels pour améliorer la délivrabilité
        $additionalParams = '-f' . $this->fromEmail;
        
        // Envoyer l'email
        return mail($to, $subject, $html, $headersString, $additionalParams);
    }
}