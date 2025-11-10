<?php
/**
 * Fichier : config/mail.php
 * Description : Configuration de l'envoi d'emails
 * Auteur : Fabian Hardy
 * Date : 04/11/2025
 */

return [
    
    /**
     * Configuration SMTP
     */
    'host' => $_ENV['MAIL_HOST'] ?? 'localhost',
    'port' => (int) ($_ENV['MAIL_PORT'] ?? 587),
    'username' => $_ENV['MAIL_USERNAME'] ?? '',
    'password' => $_ENV['MAIL_PASSWORD'] ?? '',
    'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls', // tls ou ssl
    
    /**
     * Expéditeur par défaut
     */
    'from' => [
        'address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@trendyfoods.be',
        'name' => $_ENV['MAIL_FROM_NAME'] ?? 'Trendy Foods',
    ],
    
    /**
     * Configuration des emails de commande
     */
    'order_confirmation' => [
        'subject_fr' => 'Confirmation de votre commande - {{campaign_name}}',
        'subject_nl' => 'Bevestiging van uw bestelling - {{campaign_name}}',
    ],
    
    /**
     * Templates d'emails
     */
    'templates' => [
        'order_confirmation' => APP_PATH . '/Views/emails/order_confirmation.php',
        'order_notification_admin' => APP_PATH . '/Views/emails/order_notification_admin.php',
    ],
    
    /**
     * Options
     */
    'options' => [
        'timeout' => 30,
        'verify_ssl' => true,
    ],
    
];
