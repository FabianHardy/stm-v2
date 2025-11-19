<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Test de chargement...<br>";

try {
    require_once __DIR__ . '/../vendor/autoload.php';
    echo "✅ Autoload OK<br>";
    
    // Charger .env
    if (file_exists(__DIR__ . '/../.env')) {
        $lines = file(__DIR__ . '/../.env');
        foreach ($lines as $line) {
            if (strpos($line, 'MAILCHIMP_') === 0) {
                echo htmlspecialchars(trim($line)) . "<br>";
            }
        }
        echo "✅ .env OK<br>";
    }
    
    // Tester Mailchimp
    use App\Services\MailchimpEmailService;
    echo "✅ Import MailchimpEmailService OK<br>";
    
    $service = new MailchimpEmailService();
    echo "✅ Instance MailchimpEmailService OK<br>";
    
    $ping = $service->ping();
    echo $ping ? "✅ Ping Mailchimp OK<br>" : "❌ Ping Mailchimp ÉCHEC<br>";
    
} catch (Exception $e) {
    echo "<strong style='color:red'>❌ ERREUR :</strong><br>";
    echo htmlspecialchars($e->getMessage()) . "<br>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}