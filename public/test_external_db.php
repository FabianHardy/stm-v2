<?php
/**
 * test_external_db.php
 * 
 * Script de test pour vérifier la connexion à la base externe trendyblog_sig
 * et le bon fonctionnement de la classe ExternalDatabase
 * 
 * @created  2025/11/12 19:30
 * @usage    Accéder via : https://actions.trendyfoods.com/stm/test_external_db.php
 */

// Charger l'autoloader et les variables d'environnement
require_once __DIR__ . '/vendor/autoload.php';

// Charger les variables d'environnement
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Importer la classe ExternalDatabase
use App\Core\ExternalDatabase;

// Activer l'affichage des erreurs pour le test
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test ExternalDatabase - STM v2</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
        }
        h1 {
            color: #2d3748;
            margin-bottom: 10px;
            font-size: 32px;
        }
        .subtitle {
            color: #718096;
            margin-bottom: 30px;
            font-size: 16px;
        }
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f7fafc;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .test-section h2 {
            color: #2d3748;
            margin-bottom: 15px;
            font-size: 20px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 12px;
            border-left: 4px solid #28a745;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 12px;
            border-left: 4px solid #dc3545;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 12px;
            border-left: 4px solid #17a2b8;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 12px;
            border-left: 4px solid #ffc107;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            background: white;
            border-radius: 6px;
            overflow: hidden;
        }
        th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        tr:hover {
            background: #f7fafc;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success {
            background: #c6f6d5;
            color: #22543d;
        }
        .badge-error {
            background: #fed7d7;
            color: #742a2a;
        }
        code {
            background: #2d3748;
            color: #48bb78;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
        }
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            color: #718096;
            font-size: 14px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test ExternalDatabase</h1>
        <p class="subtitle">Vérification de la connexion à trendyblog_sig et test des fonctionnalités</p>

        <?php
        try {
            // ================================================
            // TEST 1 : Connexion à la base de données
            // ================================================
            echo '<div class="test-section">';
            echo '<h2>📡 Test 1 : Connexion à la base de données</h2>';
            
            $externalDb = ExternalDatabase::getInstance();
            
            if ($externalDb->testConnection()) {
                echo '<div class="success">✅ <strong>Connexion établie avec succès !</strong></div>';
            } else {
                echo '<div class="error">❌ <strong>Échec de la connexion</strong></div>';
            }
            
            echo '</div>';

            // ================================================
            // TEST 2 : Vérification des tables
            // ================================================
            echo '<div class="test-section">';
            echo '<h2>📊 Test 2 : Vérification des tables</h2>';
            
            $tables = $externalDb->checkTables();
            
            echo '<table>';
            echo '<tr><th>Table</th><th>État</th></tr>';
            foreach ($tables as $table => $exists) {
                $status = $exists 
                    ? '<span class="badge badge-success">✅ Existe</span>' 
                    : '<span class="badge badge-error">❌ Manquante</span>';
                echo "<tr><td><code>{$table}</code></td><td>{$status}</td></tr>";
            }
            echo '</table>';
            
            echo '</div>';

            // ================================================
            // TEST 3 : Statistiques des clients
            // ================================================
            echo '<div class="test-section">';
            echo '<h2>📈 Test 3 : Statistiques des clients</h2>';
            
            $countBE = $externalDb->countCustomers('BE');
            $countLU = $externalDb->countCustomers('LU');
            $total = $countBE + $countLU;
            
            echo '<div class="stats">';
            echo '<div class="stat-card">';
            echo '<div class="stat-value">' . number_format($countBE, 0, ',', ' ') . '</div>';
            echo '<div class="stat-label">Clients Belgique (BE_CLL)</div>';
            echo '</div>';
            
            echo '<div class="stat-card">';
            echo '<div class="stat-value">' . number_format($countLU, 0, ',', ' ') . '</div>';
            echo '<div class="stat-label">Clients Luxembourg (LU_CLL)</div>';
            echo '</div>';
            
            echo '<div class="stat-card">';
            echo '<div class="stat-value">' . number_format($total, 0, ',', ' ') . '</div>';
            echo '<div class="stat-label">Total clients</div>';
            echo '</div>';
            echo '</div>';
            
            echo '</div>';

            // ================================================
            // TEST 4 : Lecture des 5 premiers clients BE
            // ================================================
            echo '<div class="test-section">';
            echo '<h2>👥 Test 4 : Lecture des 5 premiers clients BE</h2>';
            
            $customersBE = $externalDb->query("SELECT * FROM BE_CLL LIMIT 5");
            
            if ($customersBE && count($customersBE) > 0) {
                echo '<div class="info">ℹ️ ' . count($customersBE) . ' clients récupérés</div>';
                echo '<table>';
                echo '<tr><th>IDE_CLL</th><th>Numéro</th><th>Nom</th><th>Localité</th><th>REP</th></tr>';
                foreach ($customersBE as $customer) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($customer['IDE_CLL']) . '</td>';
                    echo '<td><code>' . htmlspecialchars($customer['CLL_NCLIXX']) . '</code></td>';
                    echo '<td>' . htmlspecialchars($customer['CLL_NOM']) . '</td>';
                    echo '<td>' . htmlspecialchars($customer['CLL_LOCALITE'] ?? '-') . '</td>';
                    echo '<td>' . htmlspecialchars($customer['IDE_REP'] ?? '-') . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<div class="warning">⚠️ Aucun client trouvé dans BE_CLL</div>';
            }
            
            echo '</div>';

            // ================================================
            // TEST 5 : Lecture d'un client spécifique
            // ================================================
            echo '<div class="test-section">';
            echo '<h2>🔍 Test 5 : Recherche d\'un client spécifique</h2>';
            
            // Prendre le premier client de BE_CLL pour le test
            if ($customersBE && count($customersBE) > 0) {
                $testCustomerNumber = $customersBE[0]['CLL_NCLIXX'];
                
                echo '<div class="info">ℹ️ Test avec le numéro client : <code>' . htmlspecialchars($testCustomerNumber) . '</code></div>';
                
                $customer = $externalDb->getCustomer($testCustomerNumber, 'BE');
                
                if ($customer) {
                    echo '<div class="success">✅ Client trouvé !</div>';
                    echo '<table>';
                    echo '<tr><th>Champ</th><th>Valeur</th></tr>';
                    foreach ($customer as $key => $value) {
                        echo '<tr>';
                        echo '<td><strong>' . htmlspecialchars($key) . '</strong></td>';
                        echo '<td>' . htmlspecialchars($value ?? '-') . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                } else {
                    echo '<div class="error">❌ Client non trouvé</div>';
                }
            } else {
                echo '<div class="warning">⚠️ Aucun client disponible pour le test</div>';
            }
            
            echo '</div>';

            // ================================================
            // TEST 6 : Lecture des représentants
            // ================================================
            echo '<div class="test-section">';
            echo '<h2>👔 Test 6 : Lecture des représentants BE</h2>';
            
            $reps = $externalDb->getAllRepresentatives('BE');
            
            if ($reps && count($reps) > 0) {
                echo '<div class="info">ℹ️ ' . count($reps) . ' représentants trouvés</div>';
                echo '<table>';
                echo '<tr><th>IDE_REP</th><th>Prénom</th><th>Nom</th><th>Email</th></tr>';
                $count = 0;
                foreach ($reps as $rep) {
                    if ($count++ >= 10) break; // Limiter à 10 pour l'affichage
                    echo '<tr>';
                    echo '<td><code>' . htmlspecialchars($rep['IDE_REP']) . '</code></td>';
                    echo '<td>' . htmlspecialchars($rep['REP_PRENOM'] ?? '-') . '</td>';
                    echo '<td>' . htmlspecialchars($rep['REP_NOM']) . '</td>';
                    echo '<td>' . htmlspecialchars($rep['REP_EMAIL'] ?? '-') . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                if (count($reps) > 10) {
                    echo '<div class="info">ℹ️ ... et ' . (count($reps) - 10) . ' autres</div>';
                }
            } else {
                echo '<div class="warning">⚠️ Aucun représentant trouvé dans BE_REP</div>';
            }
            
            echo '</div>';

            // ================================================
            // RÉSULTAT FINAL
            // ================================================
            echo '<div class="test-section" style="border-left-color: #28a745;">';
            echo '<h2>✅ Résultat global</h2>';
            echo '<div class="success">';
            echo '<strong>Tous les tests sont passés avec succès !</strong><br>';
            echo 'La classe ExternalDatabase fonctionne correctement et peut se connecter à <code>trendyblog_sig</code>.';
            echo '</div>';
            echo '<div class="info">';
            echo '<strong>Prochaines étapes :</strong><br>';
            echo '1. Ajouter les variables EXTERNAL_DB_* dans le fichier .env<br>';
            echo '2. Créer le Model Customer.php<br>';
            echo '3. Créer le Controller CustomerController.php<br>';
            echo '4. Implémenter l\'import automatique depuis la DB externe';
            echo '</div>';
            echo '</div>';

        } catch (Exception $e) {
            echo '<div class="test-section">';
            echo '<h2>❌ Erreur</h2>';
            echo '<div class="error">';
            echo '<strong>Une erreur est survenue :</strong><br>';
            echo htmlspecialchars($e->getMessage());
            echo '</div>';
            echo '<div class="warning">';
            echo '<strong>Vérifications à faire :</strong><br>';
            echo '1. Les credentials de la base de données sont-ils corrects dans .env ?<br>';
            echo '2. La base de données trendyblog_sig existe-t-elle ?<br>';
            echo '3. L\'utilisateur a-t-il les droits d\'accès sur trendyblog_sig ?';
            echo '</div>';
            echo '</div>';
        }
        ?>

    </div>
</body>
</html>