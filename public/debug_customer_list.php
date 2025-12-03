<?php
/**
 * Script de diagnostic - Problème ajout clients campagne
 *
 * À placer à la racine du site STM et accéder via :
 * https://actions.trendyfoods.com/stm/debug_customer_list.php
 *
 * SUPPRIMER APRÈS UTILISATION !
 */

echo "<h1>🔍 Diagnostic - Liste clients</h1>";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    echo "<h2>Données reçues :</h2>";

    $customerList = $_POST["customer_list"] ?? "";

    echo "<h3>1. Contenu brut (var_dump) :</h3>";
    echo "<pre>";
    var_dump($customerList);
    echo "</pre>";

    echo "<h3>2. Caractères ASCII :</h3>";
    echo "<pre>";
    for ($i = 0; $i < strlen($customerList); $i++) {
        $char = $customerList[$i];
        $ord = ord($char);
        if ($ord === 10) {
            echo "[LF-\\n]";
        } elseif ($ord === 13) {
            echo "[CR-\\r]";
        } elseif ($ord === 32) {
            echo "[SPACE]";
        } elseif ($ord === 9) {
            echo "[TAB]";
        } else {
            echo $char;
        }
    }
    echo "</pre>";

    echo "<h3>3. Après normalisation :</h3>";
    $normalized = str_replace(["\r\n", "\r"], "\n", $customerList);
    echo "<pre>";
    var_dump($normalized);
    echo "</pre>";

    echo "<h3>4. Après explode('\\n') :</h3>";
    $lines = explode("\n", $normalized);
    echo "<pre>";
    var_dump($lines);
    echo "</pre>";

    echo "<h3>5. Après array_map('trim') :</h3>";
    $trimmed = array_map("trim", $lines);
    echo "<pre>";
    var_dump($trimmed);
    echo "</pre>";

    echo "<h3>6. Après array_filter() :</h3>";
    $filtered = array_filter($trimmed);
    echo "<pre>";
    var_dump($filtered);
    echo "</pre>";

    echo "<h3>7. Nombre final de clients : <strong>" . count($filtered) . "</strong></h3>";

    echo "<hr>";
    echo "<p><a href='debug_customer_list.php'>← Recommencer le test</a></p>";
} else {
     ?>
    <form method="POST">
        <p><strong>Colle ta liste de numéros clients (copier depuis Excel) :</strong></p>
        <textarea name="customer_list" rows="10" cols="40" style="font-family: monospace;"></textarea>
        <br><br>
        <button type="submit" style="padding: 10px 20px; font-size: 16px;">Analyser</button>
    </form>
    <?php
}
?>
