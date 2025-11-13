<?php
/**
 * TEST FORMULAIRE INDÉPENDANT
 * À mettre dans /public/test_form.php
 */

session_start();

// Générer un token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Si POST, afficher les données reçues
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h1>DONNÉES REÇUES !</h1>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    echo "<a href='test_form.php'>Retour</a>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>TEST FORMULAIRE</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        input, select, textarea { display: block; margin: 10px 0; padding: 5px; width: 300px; }
        button { padding: 10px 20px; background: blue; color: white; border: none; cursor: pointer; }
        button:hover { background: darkblue; }
    </style>
</head>
<body>
    <h1>🧪 TEST FORMULAIRE BASIQUE</h1>
    
    <form method="POST" action="">
        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
        
        <label>Nom campagne</label>
        <input type="text" name="name" value="Test Campaign">
        
        <label>Pays</label>
        <select name="country">
            <option value="BE">Belgique</option>
            <option value="LU">Luxembourg</option>
        </select>
        
        <label>Date début</label>
        <input type="date" name="start_date" value="<?= date('Y-m-d') ?>">
        
        <label>Date fin</label>
        <input type="date" name="end_date" value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
        
        <label>Titre FR</label>
        <input type="text" name="title_fr" value="Titre test">
        
        <label>Titre NL</label>
        <input type="text" name="title_nl" value="Test titel">
        
        <button type="submit">SOUMETTRE</button>
    </form>
    
    <hr>
    <p><strong>Si tu vois les données POST après avoir cliqué, le problème n'est PAS PHP mais le routing/layout STM.</strong></p>
</body>
</html>