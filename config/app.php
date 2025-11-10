<?php
/**
 * Configuration de l'application
 */

return [
    'name'    => $_ENV['APP_NAME'] ?? 'STM v2',
    'env'     => $_ENV['APP_ENV'] ?? 'production',
    'debug'   => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url'     => $_ENV['APP_URL'] ?? 'https://stm.trendyfoods.be',
    'version' => '2.0.0',
    
    'session' => [
        'lifetime' => (int) ($_ENV['SESSION_LIFETIME'] ?? 7200),
        'secure'   => filter_var($_ENV['SESSION_SECURE'] ?? true, FILTER_VALIDATE_BOOLEAN),
    ],
    
    'paths' => [
        'storage' => __DIR__ . '/../storage',
        'logs'    => __DIR__ . '/../storage/logs',
        'orders'  => __DIR__ . '/../storage/orders',
        'cache'   => __DIR__ . '/../storage/cache',
    ]
];