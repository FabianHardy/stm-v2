<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "<h1>Variables d'environnement</h1>";
echo "<pre>";
echo "DB_HOST = " . ($_ENV['DB_HOST'] ?? 'NON DÉFINI') . "\n";
echo "DB_NAME = " . ($_ENV['DB_NAME'] ?? 'NON DÉFINI') . "\n";
echo "DB_USER = " . ($_ENV['DB_USER'] ?? 'NON DÉFINI') . "\n";
echo "DB_PASS = " . ($_ENV['DB_PASS'] ?? 'NON DÉFINI') . "\n\n";

echo "EXTERNAL_DB_HOST = " . ($_ENV['EXTERNAL_DB_HOST'] ?? 'NON DÉFINI') . "\n";
echo "EXTERNAL_DB_NAME = " . ($_ENV['EXTERNAL_DB_NAME'] ?? 'NON DÉFINI') . "\n";
echo "EXTERNAL_DB_USER = " . ($_ENV['EXTERNAL_DB_USER'] ?? 'NON DÉFINI') . "\n";
echo "EXTERNAL_DB_PASSWORD = " . ($_ENV['EXTERNAL_DB_PASSWORD'] ?? 'NON DÉFINI') . "\n";
echo "</pre>";
?>