<!DOCTYPE html>
<html>
<head>
    <title>Diagnostic Catégories - STM v2</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        h2 { color: #333; border-bottom: 2px solid #333; padding-bottom: 5px; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #333; color: white; }
        .error { color: red; font-weight: bold; }
        .success { color: green; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
    </style>
</head>
<body>
    <h1>🔍 DIAGNOSTIC CATÉGORIES - CAMPAGNE</h1>
    
    <?php
    // Connexion DB
    require_once __DIR__ . '/../../core/Database.php';
    require_once __DIR__ . '/../../core/Session.php';
    
    use Core\Database;
    
    $db = Database::getInstance();
    
    // UUID de la campagne à tester
    $uuid = 'dc7f8ad3-51cb-4959-982d-66bd28e3061c'; // Campagne "Coca Cola"
    
    echo "<div class='section'>";
    echo "<h2>1. CAMPAGNE</h2>";
    
    $campaign = $db->query("SELECT * FROM campaigns WHERE uuid = :uuid", [':uuid' => $uuid]);
    if (!empty($campaign)) {
        $campaign = $campaign[0];
        echo "<p><strong>ID:</strong> {$campaign['id']}</p>";
        echo "<p><strong>Nom:</strong> {$campaign['name']}</p>";
        echo "<p><strong>Active:</strong> " . ($campaign['is_active'] ? 'Oui' : 'Non') . "</p>";
    } else {
        echo "<p class='error'>❌ Campagne introuvable</p>";
        exit;
    }
    echo "</div>";
    
    // Récupérer tous les produits de la campagne
    echo "<div class='section'>";
    echo "<h2>2. PRODUITS DE LA CAMPAGNE</h2>";
    
    $products = $db->query("
        SELECT 
            p.id,
            p.product_code,
            p.name_fr,
            p.category_id,
            p.is_active,
            c.name_fr as category_name,
            c.color as category_color
        FROM products p
        LEFT JOIN product_categories c ON c.id = p.category_id
        WHERE p.campaign_id = :campaign_id
        ORDER BY p.id
    ", [':campaign_id' => $campaign['id']]);
    
    if (!empty($products)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Code</th><th>Nom</th><th>Catégorie (ID)</th><th>Catégorie (Nom)</th><th>Couleur</th><th>Actif</th></tr>";
        foreach ($products as $p) {
            $activeClass = $p['is_active'] ? 'success' : 'error';
            echo "<tr>";
            echo "<td>{$p['id']}</td>";
            echo "<td>{$p['product_code']}</td>";
            echo "<td>{$p['name_fr']}</td>";
            echo "<td>{$p['category_id']}</td>";
            echo "<td>{$p['category_name']}</td>";
            echo "<td><span style='background:{$p['category_color']};color:white;padding:2px 8px;border-radius:3px;'>{$p['category_color']}</span></td>";
            echo "<td class='{$activeClass}'>" . ($p['is_active'] ? 'OUI' : 'NON') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Compter par catégorie
        $categoryCounts = [];
        foreach ($products as $p) {
            $catId = $p['category_id'];
            if (!isset($categoryCounts[$catId])) {
                $categoryCounts[$catId] = [
                    'name' => $p['category_name'],
                    'color' => $p['category_color'],
                    'count' => 0
                ];
            }
            $categoryCounts[$catId]['count']++;
        }
        
        echo "<h3>Résumé par catégorie :</h3>";
        echo "<ul>";
        foreach ($categoryCounts as $catId => $data) {
            echo "<li><strong>{$data['name']}</strong> (ID: {$catId}) : {$data['count']} produit(s)</li>";
        }
        echo "</ul>";
        
    } else {
        echo "<p class='error'>❌ Aucun produit trouvé</p>";
    }
    echo "</div>";
    
    // Requête exacte du controller
    echo "<div class='section'>";
    echo "<h2>3. CATÉGORIES RETOURNÉES (Requête Controller)</h2>";
    
    $categoriesQuery = "
        SELECT DISTINCT
            cat.id,
            cat.code,
            cat.name_fr,
            cat.color,
            cat.display_order
        FROM product_categories cat
        INNER JOIN products p ON p.category_id = cat.id
        WHERE p.campaign_id = :campaign_id
          AND p.is_active = 1
          AND cat.is_active = 1
        ORDER BY cat.display_order ASC, cat.name_fr ASC
    ";
    
    $categories = $db->query($categoriesQuery, [':campaign_id' => $campaign['id']]);
    
    if (!empty($categories)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Code</th><th>Nom FR</th><th>Couleur</th><th>Ordre</th></tr>";
        foreach ($categories as $cat) {
            echo "<tr>";
            echo "<td>{$cat['id']}</td>";
            echo "<td>{$cat['code']}</td>";
            echo "<td>{$cat['name_fr']}</td>";
            echo "<td><span style='background:{$cat['color']};color:white;padding:2px 8px;border-radius:3px;'>{$cat['color']}</span></td>";
            echo "<td>{$cat['display_order']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<p class='success'>✅ {count($categories)} catégorie(s) distincte(s) trouvée(s)</p>";
    } else {
        echo "<p class='error'>❌ Aucune catégorie trouvée</p>";
    }
    echo "</div>";
    
    // Pour chaque catégorie, compter les produits commandables
    echo "<div class='section'>";
    echo "<h2>4. PRODUITS COMMANDABLES PAR CATÉGORIE</h2>";
    
    foreach ($categories as $cat) {
        $productsQuery = "
            SELECT 
                p.id,
                p.name_fr,
                p.max_per_customer,
                p.max_total,
                p.is_active
            FROM products p
            WHERE p.category_id = :category_id
              AND p.campaign_id = :campaign_id
              AND p.is_active = 1
            ORDER BY p.display_order ASC, p.name_fr ASC
        ";
        
        $catProducts = $db->query($productsQuery, [
            ':category_id' => $cat['id'],
            ':campaign_id' => $campaign['id']
        ]);
        
        echo "<h3 style='color:{$cat['color']}'>{$cat['name_fr']} ({$cat['id']})</h3>";
        
        if (!empty($catProducts)) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Nom</th><th>Max/client</th><th>Max total</th><th>Actif</th></tr>";
            foreach ($catProducts as $p) {
                echo "<tr>";
                echo "<td>{$p['id']}</td>";
                echo "<td>{$p['name_fr']}</td>";
                echo "<td>" . ($p['max_per_customer'] ?: 'Illimité') . "</td>";
                echo "<td>" . ($p['max_total'] ?: 'Illimité') . "</td>";
                echo "<td class='success'>OUI</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "<p><strong>Total : " . count($catProducts) . " produit(s)</strong></p>";
        } else {
            echo "<p class='warning'>⚠️ Aucun produit actif dans cette catégorie</p>";
        }
    }
    echo "</div>";
    
    // Diagnostic couleurs
    echo "<div class='section'>";
    echo "<h2>5. TEST COULEURS CATÉGORIES</h2>";
    foreach ($categories as $cat) {
        echo "<div style='background:{$cat['color']};color:white;padding:10px;margin:5px 0;border-radius:5px;'>";
        echo "<strong>{$cat['name_fr']}</strong> - Couleur: {$cat['color']}";
        echo "</div>";
    }
    echo "</div>";
    
    ?>
    
    <div class="section">
        <h2>6. DIAGNOSTIC</h2>
        <p><strong>✅ Ce qui devrait être affiché :</strong></p>
        <ul>
            <li>Catégories distinctes avec leurs vraies couleurs</li>
            <li>Produits groupés par catégorie</li>
            <li>Layout adaptatif (1 ou 2 colonnes selon nombre de produits)</li>
        </ul>
        
        <p><strong>❌ Problèmes possibles :</strong></p>
        <ul>
            <li>Si 2 catégories identiques : problème dans la boucle d'affichage</li>
            <li>Si couleurs pas appliquées : vérifier les styles inline</li>
            <li>Si images manquantes : vérifier les chemins (retirer /stm/ en double)</li>
        </ul>
    </div>
    
</body>
</html>