<?php
/**
 * TEST FORMULAIRE VERS ROUTE STM
 * À mettre dans /public/test_form_stm.php
 */

session_start();

// Générer un token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>TEST VERS ROUTE STM</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        input, select, textarea { display: block; margin: 10px 0; padding: 5px; width: 300px; }
        button { padding: 10px 20px; background: green; color: white; border: none; cursor: pointer; }
        button:hover { background: darkgreen; }
    </style>
</head>
<body>
    <h1>🧪 TEST VERS ROUTE STM</h1>
    
    <form method="POST" action="/stm/admin/campaigns">
        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
        
        <label>Nom campagne</label>
        <input type="text" name="name" value="Test Campaign STM">
        
        <label>Pays</label>
        <select name="country">
            <option value="BE">Belgique</option>
            <option value="LU">Luxembourg</option>
        </select>
        
        <label>Actif</label>
        <input type="checkbox" name="is_active" value="1" checked>
        
        <label>Date début</label>
        <input type="date" name="start_date" value="<?= date('Y-m-d') ?>">
        
        <label>Date fin</label>
        <input type="date" name="end_date" value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
        
        <label>Titre FR</label>
        <input type="text" name="title_fr" value="Titre test STM">
        
        <label>Titre NL</label>
        <input type="text" name="title_nl" value="Test titel STM">
        
        <label>Mode attribution</label>
        <select name="customer_assignment_mode">
            <option value="automatic">Automatique</option>
            <option value="manual">Manuel</option>
            <option value="protected">Protégé</option>
        </select>
        
        <label>Type commande</label>
        <select name="order_type">
            <option value="W">Normal (W)</option>
            <option value="V">Prospection (V)</option>
        </select>
        
        <button type="submit">CRÉER CAMPAGNE</button>
    </form>
    
    <hr>
    <p><strong>Ce formulaire envoie directement vers /stm/admin/campaigns (POST)</strong></p>
    <p>Si ça fonctionne : le problème est dans le layout admin.php</p>
    <p>Si ça ne fonctionne pas : le problème est dans le routing ou le controller</p>
</body>
</html>