<?php
// Vider tous les caches
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ OPcache vidé<br>";
} else {
    echo "❌ OPcache non disponible<br>";
}

// Invalider spécifiquement MailchimpEmailService
$file = __DIR__ . '/../app/Services/MailchimpEmailService.php';
if (function_exists('opcache_invalidate')) {
    opcache_invalidate($file, true);
    echo "✅ MailchimpEmailService.php invalidé<br>";
}

// Vider aussi le cache de stat des fichiers
clearstatcache(true, $file);
echo "✅ Stat cache vidé<br>";

echo "<br><strong>Cache vidé ! Teste à nouveau ta commande.</strong>";