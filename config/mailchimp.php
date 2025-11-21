<?php
/**
 * Configuration Mailchimp Transactional (Mandrill)
 * 
 * Configuration pour l'envoi d'emails transactionnels via Mandrill
 * 
 * @package STM\Config
 * @created 2025/11/18
 * @author Fabian Hardy
 */

return [
    
    /**
     * Clé API Mailchimp Transactional
     * 
     * Obtenir la clé API :
     * 1. Connexion à https://mandrillapp.com
     * 2. Menu "Settings" → "SMTP & API Info"
     * 3. Section "API Keys"
     * 
     * Format : xxxxxxxxxxxxxxxxxxxx-us21
     */
    'api_key' => $_ENV['MAILCHIMP_API_KEY'] ?? '',
    
    /**
     * Email expéditeur par défaut
     * 
     * Doit être un domaine vérifié dans Mandrill
     * Settings → Sending Domains
     */
    'from_email' => $_ENV['MAILCHIMP_FROM_EMAIL'] ?? 'noreply@trendyfoods.com',
    
    /**
     * Nom de l'expéditeur par défaut
     */
    'from_name' => $_ENV['MAILCHIMP_FROM_NAME'] ?? 'Trendy Foods',
    
    /**
     * Options avancées
     */
    'options' => [
        
        /**
         * Activer le tracking des ouvertures d'emails
         */
        'track_opens' => true,
        
        /**
         * Activer le tracking des clics dans les emails
         */
        'track_clicks' => true,
        
        /**
         * Générer automatiquement une version texte de l'email
         */
        'auto_text' => true,
        
        /**
         * Inliner automatiquement le CSS
         */
        'inline_css' => true,
        
        /**
         * Tags par défaut pour tous les emails
         * Permet de filtrer dans le dashboard Mandrill
         */
        'default_tags' => ['stm-v2', 'transactional'],
        
        /**
         * Timeout pour les requêtes API (en secondes)
         */
        'timeout' => 30,
        
        /**
         * Nombre de tentatives en cas d'échec
         */
        'retry_attempts' => 3,
    ],
    
    /**
     * Configuration des templates d'emails
     */
    'templates' => [
        
        /**
         * Email de confirmation de commande
         */
        'order_confirmation' => [
            'subject_fr' => 'Confirmation de votre commande - {{campaign_name}}',
            'subject_nl' => 'Bevestiging van uw bestelling - {{campaign_name}}',
            'from_email' => 'noreply@trendyfoods.com',
            'from_name' => 'Trendy Foods',
            'tags' => ['order', 'confirmation'],
        ],
        
        /**
         * Email de notification admin (à implémenter)
         */
        'order_notification_admin' => [
            'subject_fr' => 'Nouvelle commande reçue - {{order_number}}',
            'subject_nl' => 'Nieuwe bestelling ontvangen - {{order_number}}',
            'from_email' => 'noreply@trendyfoods.com',
            'from_name' => 'STM v2',
            'tags' => ['order', 'admin', 'notification'],
        ],
    ],
    
    /**
     * Adresses email de test
     * 
     * En mode développement, tous les emails seront envoyés à ces adresses
     * au lieu des vraies adresses clients
     */
    'test_mode' => [
        'enabled' => ($_ENV['APP_ENV'] ?? 'production') === 'development',
        'test_emails' => [
            // Ajouter vos emails de test ici
            // 'test@example.com',
        ],
    ],
    
    /**
     * Webhooks Mandrill (optionnel)
     * 
     * Pour recevoir des notifications sur les événements :
     * - Ouvertures
     * - Clics
     * - Bounces
     * - Spam
     * 
     * À configurer dans Mandrill : Settings → Webhooks
     */
    'webhooks' => [
        'enabled' => false,
        'url' => $_ENV['APP_URL'] ?? '' . '/webhooks/mailchimp',
        'events' => ['send', 'open', 'click', 'bounce', 'spam', 'reject'],
    ],
    
];