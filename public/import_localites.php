<?php
/**
 * Mise à jour postal_codes depuis le fichier CSV original
 *
 * 1. Uploader ce fichier + localite.csv dans /stm/
 * 2. Exécuter: https://actions.trendyfoods.com/stm/import_localites.php?key=import2026
 *
 * @created 2026/01/09
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 300);

if (($_GET['key'] ?? '') !== 'import2026') {
    die('Acces refuse. Ajouter ?key=import2026');
}

echo "<pre style='font-family: monospace; background: #1a1a1a; color: #0f0; padding: 20px;'>\n";
echo "=== IMPORT LOCALITES DEPUIS CSV ===\n\n";

// Connexion DB
$host = 'localhost';
$dbname = 'trendyblog_stm_v2';
$user = 'trendyblog_stm';
$pass = '';

$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if ($key === 'DB_HOST') $host = $value;
            if ($key === 'DB_NAME') $dbname = $value;
            if ($key === 'DB_USER') $user = $value;
            if ($key === 'DB_PASSWORD') $pass = $value;
        }
    }
    echo "[OK] Config .env chargee\n";
}

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->exec("SET NAMES utf8mb4");
    echo "[OK] Connexion DB OK\n\n";
} catch (PDOException $e) {
    die("[ERREUR] " . $e->getMessage());
}

// Lire le CSV
$csvFile = __DIR__ . '/localite.csv';
if (!file_exists($csvFile)) {
    die("[ERREUR] Fichier localite.csv non trouve dans " . __DIR__);
}

echo "--- Lecture du CSV ---\n";

$handle = fopen($csvFile, 'r');
if (!$handle) {
    die("[ERREUR] Impossible d'ouvrir le CSV");
}

// Lire l'en-tete
$header = fgetcsv($handle, 0, ';', '"');
echo "Colonnes: " . implode(', ', $header) . "\n";

// Preparer la requete UPDATE
$stmt = $pdo->prepare("
    UPDATE postal_codes
    SET locality_fr = :locality_fr, locality_nl = :locality_nl
    WHERE line = :line AND country = :country
");

$updated = 0;
$errors = 0;
$lineNum = 1;

echo "\n--- Mise a jour en cours ---\n";

while (($row = fgetcsv($handle, 0, ';', '"')) !== false) {
    $lineNum++;

    if (count($row) < 6) {
        echo "[SKIP] Ligne {$lineNum}: donnees incompletes\n";
        continue;
    }

    // Colonnes: id, line, country, code, localityFR, localityNL, created_at, updated_at
    $line = (int)$row[1];
    $country = $row[2];
    $localityFR = $row[4];
    $localityNL = $row[5];

    // Convertir l'encodage si necessaire (Latin1 -> UTF8)
    // Les \xe9 sont deja en UTF8 dans le CSV
    $localityFR = mb_convert_encoding($localityFR, 'UTF-8', 'UTF-8');
    $localityNL = mb_convert_encoding($localityNL, 'UTF-8', 'UTF-8');

    try {
        $stmt->execute([
            ':locality_fr' => $localityFR,
            ':locality_nl' => $localityNL,
            ':line' => $line,
            ':country' => $country,
        ]);

        if ($stmt->rowCount() > 0) {
            $updated++;
        }

        // Afficher progression tous les 500
        if ($lineNum % 500 == 0) {
            echo "... {$lineNum} lignes traitees, {$updated} mises a jour\n";
        }

    } catch (PDOException $e) {
        $errors++;
        if ($errors <= 10) {
            echo "[ERREUR] Ligne {$lineNum}: " . $e->getMessage() . "\n";
        }
    }
}

fclose($handle);

echo "\n--- Resultat ---\n";
echo "Lignes traitees: {$lineNum}\n";
echo "Mises a jour: {$updated}\n";
echo "Erreurs: {$errors}\n";

// Verification
echo "\n--- Verification ---\n";
$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM postal_codes WHERE locality_fr LIKE '%?%'");
$remaining = $stmt->fetch()['cnt'];
echo "Lignes avec '?' restantes: {$remaining}\n";

if ($remaining > 0) {
    echo "\nExemples de problemes restants:\n";
    $stmt = $pdo->query("SELECT code, locality_fr FROM postal_codes WHERE locality_fr LIKE '%?%' LIMIT 10");
    while ($row = $stmt->fetch()) {
        echo "  {$row['code']}: {$row['locality_fr']}\n";
    }
}

// Test quelques villes
echo "\n--- Test quelques villes ---\n";
$tests = ['1000', '4000', '5000', '6000', '7000'];
foreach ($tests as $code) {
    $stmt = $pdo->prepare("SELECT code, locality_fr, locality_nl FROM postal_codes WHERE code = ? LIMIT 1");
    $stmt->execute([$code]);
    $row = $stmt->fetch();
    if ($row) {
        echo "{$row['code']}: {$row['locality_fr']} / {$row['locality_nl']}\n";
    }
}

echo "\n=== TERMINE ===\n";
echo "\n[!] SUPPRIMER ce fichier et localite.csv apres utilisation!\n";
echo "</pre>";