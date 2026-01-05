<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';

// Charger l'environnement
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "<h1>Test chargement</h1>";

try {
    echo "<p>1. Test Database...</p>";
    $db = \Core\Database::getInstance();
    echo "<p style='color:green'>✓ Database OK</p>";

    echo "<p>2. Test PublicCampaignController...</p>";
    $controller = new \App\Controllers\PublicCampaignController();
    echo "<p style='color:green'>✓ Controller OK</p>";

    echo "<p>3. Test TrendyFoodsApiService...</p>";
    $api = new \App\Services\TrendyFoodsApiService();
    echo "<p style='color:green'>✓ API Service OK</p>";

} catch (\Throwable $e) {
    echo "<p style='color:red'>✗ ERREUR : " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}