<?php
echo "<pre>";

// Test 1 - $_ENV
echo "1. \$_ENV: " . ($_ENV['OPENAI_API_KEY'] ?? 'NON TROUVÉ') . "\n";

// Test 2 - getenv
echo "2. getenv(): " . (getenv('OPENAI_API_KEY') ?: 'NON TROUVÉ') . "\n";

// Test 3 - $_SERVER
echo "3. \$_SERVER: " . ($_SERVER['OPENAI_API_KEY'] ?? 'NON TROUVÉ') . "\n";

// Test 4 - Fichier .env
$envFile = dirname(__DIR__) . '/.env';
echo "4. Chemin .env: " . $envFile . "\n";
echo "5. Fichier existe: " . (file_exists($envFile) ? 'OUI' : 'NON') . "\n";

if (file_exists($envFile)) {
    $content = file_get_contents($envFile);
    // Masquer la clé
    $content = preg_replace('/OPENAI_API_KEY=.+/', 'OPENAI_API_KEY=sk-xxx...MASQUÉ', $content);
    echo "6. Contenu .env:\n" . $content;
}

echo "</pre>";