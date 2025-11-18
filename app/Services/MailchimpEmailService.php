<?php
/**
 * Service d'envoi d'emails via Mailchimp Transactional (Mandrill)
 * 
 * Gère l'envoi des emails transactionnels (confirmations de commande)
 * via l'API Mailchimp Transactional.
 * 
 * @package STM\Services
 * @version 1.0.0
 * @created 2025/11/18
 * @author Fabian Hardy
 */

namespace App\Services;

use MailchimpTransactional\ApiClient;
use Exception;

class MailchimpEmailService
{
    /**
     * Instance du client Mailchimp Transactional
     * 
     * @var ApiClient|null
     */
    private ?ApiClient $client = null;

    /**
     * Configuration du service
     * 
     * @var array
     */
    private array $config = [];

    /**
     * Constructeur - Initialise le client Mailchimp
     * 
     * @throws Exception Si la clé API est manquante
     */
    public function __construct()
    {
        // Charger la configuration
        $configFile = dirname(__DIR__, 2) . '/config/mailchimp.php';
        
        if (!file_exists($configFile)) {
            throw new Exception("Fichier de configuration Mailchimp introuvable: {$configFile}");
        }
        
        $this->config = require $configFile;
        
        // Vérifier la clé API
        if (empty($this->config['api_key'])) {
            throw new Exception("Clé API Mailchimp manquante dans la configuration");
        }
        
        // Initialiser le client Mandrill
        try {
            $this->client = new ApiClient();
            $this->client->setApiKey($this->config['api_key']);
        } catch (Exception $e) {
            error_log("Erreur initialisation Mailchimp: " . $e->getMessage());
            throw new Exception("Impossible d'initialiser le client Mailchimp: " . $e->getMessage());
        }
    }

    /**
     * Envoyer un email transactionnel
     * 
     * @param string $to Email du destinataire
     * @param string $subject Sujet de l'email
     * @param string $htmlContent Contenu HTML de l'email
     * @param string|null $fromName Nom de l'expéditeur (optionnel)
     * @param string|null $fromEmail Email expéditeur (optionnel)
     * @param array $attachments Pièces jointes (optionnel)
     * @return bool True si envoi réussi, False sinon
     */
    public function send(
        string $to,
        string $subject,
        string $htmlContent,
        ?string $fromName = null,
        ?string $fromEmail = null,
        array $attachments = []
    ): bool {
        try {
            // Valider l'email destinataire
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                error_log("Email destinataire invalide: {$to}");
                return false;
            }

            // Préparer l'expéditeur
            $fromName = $fromName ?? $this->config['from_name'];
            $fromEmail = $fromEmail ?? $this->config['from_email'];

            // Construire le message
            $message = [
                'html' => $htmlContent,
                'subject' => $subject,
                'from_email' => $fromEmail,
                'from_name' => $fromName,
                'to' => [
                    [
                        'email' => $to,
                        'type' => 'to'
                    ]
                ],
                'headers' => [
                    'Reply-To' => $fromEmail
                ],
                'important' => false,
                'track_opens' => true,
                'track_clicks' => true,
                'auto_text' => true,
                'inline_css' => true,
                'preserve_recipients' => false,
                'view_content_link' => false,
                'tags' => ['stm-v2', 'order-confirmation'],
                'subaccount' => null,
            ];

            // Ajouter les pièces jointes si présentes
            if (!empty($attachments)) {
                $message['attachments'] = $this->formatAttachments($attachments);
            }

            // Envoyer via Mandrill
            $response = $this->client->messages->send([
                'message' => $message
            ]);

            // Vérifier la réponse
            if (!empty($response) && is_array($response)) {
                $status = $response[0]['status'] ?? 'unknown';
                
                if (in_array($status, ['sent', 'queued', 'scheduled'])) {
                    $this->logSuccess($to, $subject, $response);
                    return true;
                } else {
                    $this->logError($to, $subject, "Status: {$status}", $response);
                    return false;
                }
            }

            $this->logError($to, $subject, "Réponse API invalide", $response ?? []);
            return false;

        } catch (Exception $e) {
            $this->logError($to, $subject, $e->getMessage());
            return false;
        }
    }

    /**
     * Envoyer un email de confirmation de commande
     * 
     * @param string $to Email du destinataire
     * @param array $order Données de la commande
     * @param string $language Langue (fr ou nl)
     * @return bool
     */
    public function sendOrderConfirmation(string $to, array $order, string $language = 'fr'): bool
    {
        try {
            // Déterminer le template selon la langue
            $templateFile = dirname(__DIR__) . "/Views/emails/order_confirmation_{$language}.php";
            
            if (!file_exists($templateFile)) {
                error_log("Template email introuvable: {$templateFile}");
                return false;
            }

            // Préparer les données pour le template
            $orderLines = $order['lines'] ?? [];
            
            // Générer le contenu HTML
            ob_start();
            include $templateFile;
            $htmlContent = ob_get_clean();

            // Déterminer le sujet selon la langue
            $campaignName = $language === 'nl' 
                ? ($order['campaign_title_nl'] ?? 'Campagne')
                : ($order['campaign_title_fr'] ?? 'Campagne');
            
            $subject = $language === 'nl'
                ? "Bevestiging van uw bestelling - {$campaignName}"
                : "Confirmation de votre commande - {$campaignName}";

            // Envoyer l'email
            return $this->send($to, $subject, $htmlContent);

        } catch (Exception $e) {
            error_log("Erreur envoi email confirmation: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Formater les pièces jointes pour Mandrill
     * 
     * @param array $attachments
     * @return array
     */
    private function formatAttachments(array $attachments): array
    {
        $formatted = [];
        
        foreach ($attachments as $attachment) {
            if (!isset($attachment['path']) || !file_exists($attachment['path'])) {
                continue;
            }
            
            $formatted[] = [
                'type' => $attachment['type'] ?? mime_content_type($attachment['path']),
                'name' => $attachment['name'] ?? basename($attachment['path']),
                'content' => base64_encode(file_get_contents($attachment['path']))
            ];
        }
        
        return $formatted;
    }

    /**
     * Logger un succès d'envoi
     * 
     * @param string $to
     * @param string $subject
     * @param array $response
     * @return void
     */
    private function logSuccess(string $to, string $subject, array $response): void
    {
        $messageId = $response[0]['_id'] ?? 'unknown';
        $status = $response[0]['status'] ?? 'unknown';
        
        error_log(sprintf(
            "Email envoyé avec succès via Mailchimp - To: %s | Subject: %s | ID: %s | Status: %s",
            $to,
            $subject,
            $messageId,
            $status
        ));
    }

    /**
     * Logger une erreur d'envoi
     * 
     * @param string $to
     * @param string $subject
     * @param string $error
     * @param array $details
     * @return void
     */
    private function logError(string $to, string $subject, string $error, array $details = []): void
    {
        $logMessage = sprintf(
            "Erreur envoi email Mailchimp - To: %s | Subject: %s | Error: %s",
            $to,
            $subject,
            $error
        );
        
        if (!empty($details)) {
            $logMessage .= " | Details: " . json_encode($details);
        }
        
        error_log($logMessage);
    }

    /**
     * Vérifier si l'API Mailchimp est accessible
     * 
     * @return bool
     */
    public function ping(): bool
    {
        try {
            $response = $this->client->users->ping();
            return !empty($response) && $response === "PONG!";
        } catch (Exception $e) {
            error_log("Erreur ping Mailchimp: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtenir les informations du compte Mailchimp
     * 
     * @return array|null
     */
    public function getAccountInfo(): ?array
    {
        try {
            return $this->client->users->info();
        } catch (Exception $e) {
            error_log("Erreur récupération info compte Mailchimp: " . $e->getMessage());
            return null;
        }
    }
}