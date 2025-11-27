<?php
/**
 * Test API Trendy Foods - Vérification éligibilité client/promo
 * 
 * Script standalone pour tester le flux :
 * 1. POST /api/tokens -> Obtenir un token
 * 2. GET /api/ipad/prices_list_art -> Vérifier éligibilité
 * 
 * @created  2025/11/24
 * @author   STM v2
 */

// ============================================================================
// CONFIGURATION
// ============================================================================

$config = [
    'api_base_url' => 'https://api-prod.trendyfoods.com/api',
    'credentials' => [
        'login' => 'reps_test',
        'password' => 'pass',
        'application' => 'reps',
        'device_unique_id' => '3E2D9DAA-79A5-437E-A6D7-C8E2C5E4C8D0'
    ],
    'test_data' => [
        'customer_number' => '802412',
        'product_code' => '051962',
        'country' => 'be',
        'language' => 'fr'
    ]
];

// ============================================================================
// FONCTIONS
// ============================================================================

/**
 * Effectue une requête HTTP avec cURL
 */
function makeRequest(string $method, string $url, array $headers = [], ?array $body = null): array
{
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $errno = curl_errno($ch);
    
    curl_close($ch);
    
    return [
        'success' => $errno === 0,
        'http_code' => $httpCode,
        'response' => $response,
        'error' => $error,
        'data' => json_decode($response, true)
    ];
}

/**
 * Étape 1 : Obtenir un token d'authentification
 */
function getToken(array $config): ?string
{
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "ÉTAPE 1 : AUTHENTIFICATION (POST /tokens)\n";
    echo str_repeat("=", 60) . "\n";
    
    $url = $config['api_base_url'] . '/tokens';
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'language: ' . $config['test_data']['language'],
        'country: ' . $config['test_data']['country'],
        'platform: reps'
    ];
    
    echo "\n📤 Requête:\n";
    echo "   URL: $url\n";
    echo "   Method: POST\n";
    echo "   Headers:\n";
    foreach ($headers as $h) {
        echo "     - $h\n";
    }
    echo "   Body: " . json_encode($config['credentials'], JSON_PRETTY_PRINT) . "\n";
    
    $result = makeRequest('POST', $url, $headers, $config['credentials']);
    
    echo "\n📥 Réponse:\n";
    echo "   HTTP Code: " . $result['http_code'] . "\n";
    
    if (!$result['success']) {
        echo "   ❌ Erreur cURL: " . $result['error'] . "\n";
        return null;
    }
    
    if ($result['http_code'] !== 201 && $result['http_code'] !== 200) {
        echo "   ❌ Erreur API: " . $result['response'] . "\n";
        return null;
    }
    
    echo "   ✅ Succès!\n";
    echo "   Data: " . json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    
    // Extraire le token (adapter selon la structure de réponse réelle)
    $token = $result['data']['token'] ?? $result['data']['access_token'] ?? $result['data']['data']['token'] ?? null;
    
    if ($token) {
        echo "\n   🔑 Token obtenu: " . substr($token, 0, 50) . "...\n";
    } else {
        echo "\n   ⚠️ Token non trouvé dans la réponse. Structure:\n";
        print_r($result['data']);
    }
    
    return $token;
}

/**
 * Étape 2 : Vérifier l'éligibilité client/article
 */
function checkEligibility(array $config, string $token): void
{
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "ÉTAPE 2 : VÉRIFICATION ÉLIGIBILITÉ (GET /ipad/prices_list_art)\n";
    echo str_repeat("=", 60) . "\n";
    
    $queryParams = http_build_query([
        'cltno' => $config['test_data']['customer_number'],
        'listArt' => $config['test_data']['product_code']
    ]);
    
    $url = $config['api_base_url'] . '/ipad/prices_list_art?' . $queryParams;
    
    $headers = [
        'Accept: application/json',
        'Authorization: Bearer ' . $token,
        'language: ' . $config['test_data']['language'],
        'country: ' . $config['test_data']['country'],
        'platform: reps'
    ];
    
    echo "\n📤 Requête:\n";
    echo "   URL: $url\n";
    echo "   Method: GET\n";
    echo "   Headers:\n";
    foreach ($headers as $h) {
        // Masquer partiellement le token pour la lisibilité
        if (strpos($h, 'Authorization') !== false) {
            echo "     - Authorization: Bearer " . substr($token, 0, 20) . "...\n";
        } else {
            echo "     - $h\n";
        }
    }
    echo "   Params:\n";
    echo "     - cltno: " . $config['test_data']['customer_number'] . "\n";
    echo "     - listArt: " . $config['test_data']['product_code'] . "\n";
    
    $result = makeRequest('GET', $url, $headers);
    
    echo "\n📥 Réponse:\n";
    echo "   HTTP Code: " . $result['http_code'] . "\n";
    
    if (!$result['success']) {
        echo "   ❌ Erreur cURL: " . $result['error'] . "\n";
        return;
    }
    
    if ($result['http_code'] !== 200) {
        echo "   ❌ Erreur API (HTTP " . $result['http_code'] . "): " . $result['response'] . "\n";
        return;
    }
    
    echo "   ✅ Succès!\n";
    echo "\n   📋 Réponse complète (JSON formaté):\n";
    echo "   " . str_repeat("-", 50) . "\n";
    echo json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    echo "   " . str_repeat("-", 50) . "\n";
    
    // Analyse de la réponse pour trouver l'info "a droit"
    echo "\n   🔍 Analyse de la réponse:\n";
    analyzeResponse($result['data']);
}

/**
 * Analyse la réponse pour identifier le champ "éligibilité"
 */
function analyzeResponse($data): void
{
    if (empty($data)) {
        echo "      ⚠️ Réponse vide\n";
        return;
    }
    
    // Chercher des champs potentiels d'éligibilité
    $eligibilityFields = ['eligible', 'a_droit', 'has_access', 'allowed', 'authorized', 'can_buy', 'available'];
    
    $flatData = flattenArray($data);
    
    echo "      Champs trouvés dans la réponse:\n";
    foreach ($flatData as $key => $value) {
        $displayValue = is_bool($value) ? ($value ? 'true' : 'false') : $value;
        if (is_string($displayValue) && strlen($displayValue) > 50) {
            $displayValue = substr($displayValue, 0, 50) . '...';
        }
        echo "        - $key: $displayValue\n";
        
        // Mettre en évidence les champs potentiellement liés à l'éligibilité
        foreach ($eligibilityFields as $field) {
            if (stripos($key, $field) !== false) {
                echo "          ⭐ Champ d'éligibilité potentiel!\n";
            }
        }
    }
}

/**
 * Aplatit un tableau multidimensionnel pour l'analyse
 */
function flattenArray(array $array, string $prefix = ''): array
{
    $result = [];
    foreach ($array as $key => $value) {
        $newKey = $prefix ? "$prefix.$key" : $key;
        if (is_array($value)) {
            $result = array_merge($result, flattenArray($value, $newKey));
        } else {
            $result[$newKey] = $value;
        }
    }
    return $result;
}

// ============================================================================
// EXÉCUTION
// ============================================================================

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║    TEST API TRENDY FOODS - ÉLIGIBILITÉ CLIENT/PROMO        ║\n";
echo "╠════════════════════════════════════════════════════════════╣\n";
echo "║  Client test : " . str_pad($config['test_data']['customer_number'], 42) . " ║\n";
echo "║  Article test: " . str_pad($config['test_data']['product_code'], 42) . " ║\n";
echo "║  Pays        : " . str_pad($config['test_data']['country'], 42) . " ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";

// Étape 1 : Obtenir le token
$token = getToken($config);

if ($token === null) {
    echo "\n❌ ÉCHEC : Impossible d'obtenir le token. Arrêt du test.\n\n";
    exit(1);
}

// Étape 2 : Vérifier l'éligibilité
checkEligibility($config, $token);

echo "\n" . str_repeat("=", 60) . "\n";
echo "FIN DU TEST\n";
echo str_repeat("=", 60) . "\n\n";