<?php
require_once __DIR__ . '/../app/Core/Autoloader.php';
\Core\Autoloader::register();

$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            putenv(trim($line));
        }
    }
}

session_start();

echo "<h1>Test StatsAccessHelper - User: " . ($_SESSION['user']['name'] ?? 'Non connecté') . "</h1>";
echo "<pre>";

try {
    echo "=== Session utilisateur ===\n";
    print_r(\Core\Session::get('user'));

    echo "\n=== getAccessibleCampaignIds ===\n";
    $campaignIds = \App\Helpers\StatsAccessHelper::getAccessibleCampaignIds();
    echo "Type: " . gettype($campaignIds) . "\n";
    echo "Valeur: "; var_dump($campaignIds);

    echo "\n=== getAccessibleCustomerNumbersOnly ===\n";
    $customers = \App\Helpers\StatsAccessHelper::getAccessibleCustomerNumbersOnly();
    echo "Type: " . gettype($customers) . "\n";
    if (is_array($customers)) {
        echo "Nombre: " . count($customers) . "\n";
        echo "Premiers: " . implode(", ", array_slice($customers, 0, 10)) . "...\n";
    } else {
        echo "Valeur: null (accès total)\n";
    }

} catch (\Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "</pre>";