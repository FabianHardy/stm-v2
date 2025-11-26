<?php
/**
 * TEST COMPLET - Module Stats
 */
error_reporting(E_ALL);
ini_set("display_errors", 1);

define("BASE_PATH", dirname(__DIR__));
require_once BASE_PATH . "/vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

session_start();

echo "<h1>Test Complet Stats</h1>";

// Vérifier si connecté
echo "<h2>1. Session</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (!isset($_SESSION["user"])) {
    echo "<p style='color:orange'><strong>⚠️ Pas connecté ! Connecte-toi d'abord sur /stm/admin/login</strong></p>";
    echo "<p><a href='/stm/admin/login'>Se connecter →</a></p>";
    die();
}

echo "<p style='color:green'>✅ Connecté en tant que : " . ($_SESSION["user"]["username"] ?? "inconnu") . "</p>";

// Test Database
echo "<h2>2. Database</h2>";
try {
    $db = \Core\Database::getInstance();
    echo "<p style='color:green'>✅ Database OK</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>❌ ERREUR: " . $e->getMessage() . "</p>";
    die();
}

// Test Stats Model
echo "<h2>3. Stats Model</h2>";
try {
    $stats = new \App\Models\Stats();
    echo "<p style='color:green'>✅ Stats Model OK</p>";

    // Test une méthode simple
    echo "<p>Test getCampaignsList()... ";
    $campaigns = $stats->getCampaignsList();
    echo "<strong style='color:green'>OK - " . count($campaigns) . " campagnes</strong></p>";

    // Test getGlobalKPIs (celle qui pose problème)
    echo "<p>Test getGlobalKPIs()... ";
    $dateFrom = date("Y-m-d", strtotime("-14 days"));
    $dateTo = date("Y-m-d");
    $kpis = $stats->getGlobalKPIs($dateFrom, $dateTo);
    echo "<strong style='color:green'>OK</strong></p>";
    echo "<pre>" . print_r($kpis, true) . "</pre>";

    // Test getTopProducts (celle qui avait l'erreur)
    echo "<p>Test getTopProducts()... ";
    $topProducts = $stats->getTopProducts($dateFrom, $dateTo, null, 5);
    echo "<strong style='color:green'>OK - " . count($topProducts) . " produits</strong></p>";
} catch (Throwable $e) {
    echo "<strong style='color:red'>❌ ERREUR: " . $e->getMessage() . "</strong></p>";
    echo "<pre style='background:#fee; padding:10px'>" . $e->getTraceAsString() . "</pre>";
    die();
}

// Test StatsController
echo "<h2>4. StatsController</h2>";
try {
    echo "<p>Instanciation... ";
    // NE PAS instancier car le constructeur vérifie la session différemment
    echo "<strong style='color:green'>Sauté (vérifie session)</strong></p>";
} catch (Throwable $e) {
    echo "<strong style='color:red'>❌ ERREUR: " . $e->getMessage() . "</strong></p>";
}

echo "<hr>";
echo "<h2>5. Test direct de la vue</h2>";
echo "<p>Si tout est vert ci-dessus, le problème est dans le routeur ou la vue.</p>";
echo "<p><a href='/stm/admin/stats' style='font-size:18px; font-weight:bold'>→ Tester /stm/admin/stats</a></p>";
