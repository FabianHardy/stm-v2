<?php
/**
 * Script AUTONOME de correction d'encodage pour postal_codes
 *
 * Exécuter via: https://actions.trendyfoods.com/stm/fix_encoding.php?key=fix2026
 *
 * @created  2026/01/09
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Sécurité
if (($_GET['key'] ?? '') !== 'fix2026') {
    die('Acces refuse. Ajouter ?key=fix2026');
}

echo "<pre style='font-family: monospace; background: #1a1a1a; color: #0f0; padding: 20px;'>\n";
echo "=== CORRECTION ENCODAGE postal_codes ===\n\n";

// Connexion directe PDO (modifier si necessaire)
$host = 'localhost';
$dbname = 'trendyblog_stm_dev';
$user = 'trendyblog_root';
$pass = 'fabianh2545';

// Essayer de lire le .env
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
    echo "[OK] Configuration lue depuis .env\n";
} else {
    echo "[!] Fichier .env non trouve, utilisation config par defaut\n";
}

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
    echo "[OK] Connexion base de donnees OK\n\n";
} catch (PDOException $e) {
    die("[ERREUR] Connexion: " . $e->getMessage());
}

// 1. Supprimer la ligne d'en-tete
echo "--- Suppression ligne d'en-tete ---\n";
$stmt = $pdo->query("SELECT id FROM postal_codes WHERE locality_fr = 'localityFR' LIMIT 1");
$row = $stmt->fetch();
if ($row) {
    $pdo->exec("DELETE FROM postal_codes WHERE id = " . (int)$row['id']);
    echo "[OK] Ligne d'en-tete supprimee (ID: {$row['id']})\n";
} else {
    echo "-> Pas de ligne d'en-tete trouvee\n";
}

// 2. Voir l'etat actuel
echo "\n--- Etat actuel ---\n";
$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM postal_codes WHERE locality_fr LIKE '%?%'");
$cnt = $stmt->fetch()['cnt'];
echo "Lignes avec '?' dans locality_fr: {$cnt}\n";

// 3. Afficher quelques exemples de problemes
echo "\n--- Exemples de donnees problematiques ---\n";
$stmt = $pdo->query("SELECT id, code, locality_fr, locality_nl FROM postal_codes WHERE locality_fr LIKE '%?%' LIMIT 15");
$examples = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($examples as $ex) {
    echo "ID {$ex['id']} | {$ex['code']} | FR: {$ex['locality_fr']}\n";
}

// 4. Corrections par remplacement direct
echo "\n--- Corrections appliquees ---\n";

$corrections = [
    // [pattern SQL LIKE, valeur correcte]
    ['%Assembl?e R?unie%', 'Assemblee Reunie de la Commission Communautaire'],
    ['%Assembl?e de la Commission Communautaire Fran?aise%', 'Assemblee de la Commission Communautaire Francaise'],
    ['%Chambre des Repr?sentants%', 'Chambre des Representants'],
    ['%S?nat de Belgique%', 'Senat de Belgique'],
    ['%Parlement de la Communaut? fran?aise%', 'Parlement de la Communaute francaise'],
    ['%Minist?re de la R?gion de Bruxelles%', 'Ministere de la Region de Bruxelles Capitale'],
    ['%Parlement Europ?en%', 'Parlement Europeen'],
    ['%Union Europ?enne%', 'Union Europeenne - Conseil'],
    ['%Organisations Sociales Chr?tiennes%', 'Organisations Sociales Chretiennes'],
];

$totalFixed = 0;
foreach ($corrections as $corr) {
    $pattern = $corr[0];
    $correct = $corr[1];

    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM postal_codes WHERE locality_fr LIKE ?");
    $stmt->execute([$pattern]);
    $cnt = $stmt->fetch()['cnt'];

    if ($cnt > 0) {
        $stmt = $pdo->prepare("UPDATE postal_codes SET locality_fr = ? WHERE locality_fr LIKE ?");
        $stmt->execute([$correct, $pattern]);
        echo "[OK] " . substr($pattern, 0, 40) . "... -> " . substr($correct, 0, 30) . "... ({$cnt})\n";
        $totalFixed += $cnt;
    }
}

echo "\nTotal corrige: {$totalFixed} lignes\n";

// 5. Verification finale
echo "\n--- Verification finale ---\n";
$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM postal_codes WHERE locality_fr LIKE '%?%'");
$remaining = $stmt->fetch()['cnt'];
echo "Lignes restantes avec '?': {$remaining}\n";

if ($remaining > 0) {
    echo "\n--- Problemes restants (valeurs distinctes) ---\n";
    $stmt = $pdo->query("SELECT DISTINCT locality_fr FROM postal_codes WHERE locality_fr LIKE '%?%' ORDER BY locality_fr");
    $problems = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($problems as $p) {
        echo "  - {$p}\n";
    }

    echo "\n[INFO] Copie ces valeurs et envoie-les moi pour creer les corrections.\n";
}

echo "\n=== TERMINE ===\n";
echo "\n[ATTENTION] SUPPRIMER ce fichier apres utilisation!\n";
echo "</pre>";