<?php
/**
 * Service API Trendy Foods
 *
 * Gère l'authentification et les appels à l'API Trendy Foods
 * pour vérifier l'éligibilité des clients aux produits et récupérer les prix.
 *
 * @created  2025/01/05
 * @package  App\Services
 */

namespace App\Services;

class TrendyFoodsApiService
{
    /**
     * URL de base de l'API
     */
    private string $apiUrl;

    /**
     * Identifiants de connexion
     */
    private string $login;
    private string $password;

    /**
     * Durée de validité du token en secondes (1h30 = 5400s, on prend 1h pour marge)
     */
    private const TOKEN_TTL = 3600;

    /**
     * Clé de session pour le cache du token
     */
    private const TOKEN_SESSION_KEY = 'trendy_api_token';
    private const TOKEN_EXPIRY_KEY = 'trendy_api_token_expiry';

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->apiUrl = rtrim(getenv('TRENDY_API_URL') ?: 'https://api-prod.trendyfoods.com/api', '/');
        $this->login = getenv('TRENDY_API_LOGIN') ?: 'reps_test';
        $this->password = getenv('TRENDY_API_PASSWORD') ?: 'pass';
    }

    /**
     * Obtenir un token d'authentification (avec cache en session)
     *
     * @return string|null Token JWT ou null si erreur
     */
    public function getToken(): ?string
    {
        // Vérifier le cache en session
        if ($this->hasValidCachedToken()) {
            return $_SESSION[self::TOKEN_SESSION_KEY];
        }

        // Appeler l'API pour obtenir un nouveau token
        $token = $this->fetchNewToken();

        if ($token) {
            // Mettre en cache
            $_SESSION[self::TOKEN_SESSION_KEY] = $token;
            $_SESSION[self::TOKEN_EXPIRY_KEY] = time() + self::TOKEN_TTL;
        }

        return $token;
    }

    /**
     * Vérifier si un token valide est en cache
     *
     * @return bool
     */
    private function hasValidCachedToken(): bool
    {
        if (!isset($_SESSION[self::TOKEN_SESSION_KEY]) || !isset($_SESSION[self::TOKEN_EXPIRY_KEY])) {
            return false;
        }

        // Vérifier l'expiration (avec 5 min de marge)
        return $_SESSION[self::TOKEN_EXPIRY_KEY] > (time() + 300);
    }

    /**
     * Appeler l'API pour obtenir un nouveau token
     *
     * @return string|null
     */
    private function fetchNewToken(): ?string
    {
        $url = $this->apiUrl . '/tokens';

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'language: fr',
            'country: be',
            'platform: reps'
        ];

        $body = json_encode([
            'login' => $this->login,
            'password' => $this->password,
            'application' => 'reps',
            'device_unique_id' => 'stm-v2-' . ($_SERVER['SERVER_NAME'] ?? 'server')
        ]);

        $response = $this->makeRequest('POST', $url, $headers, $body);

        if ($response && isset($response['token'])) {
            return $response['token'];
        }

        // Log l'erreur
        error_log('[TrendyFoodsAPI] Échec obtention token: ' . json_encode($response));
        return null;
    }

    /**
     * Récupérer les informations de produits pour un client
     *
     * @param string $customerNumber Numéro client
     * @param string $country Code pays (be/lu)
     * @param array $productCodes Liste des codes produits
     * @return array Tableau indexé par code produit avec 'adroit' et 'prix'
     */
    public function getProductsInfo(string $customerNumber, string $country, array $productCodes): array
    {
        if (empty($productCodes)) {
            return [];
        }

        $token = $this->getToken();

        if (!$token) {
            error_log('[TrendyFoodsAPI] Impossible d\'obtenir le token pour getProductsInfo');
            return $this->buildErrorResponse($productCodes, 'api_token_error');
        }

        $url = $this->apiUrl . '/ipad/prices_list_art?' . http_build_query([
            'cltno' => $customerNumber,
            'listArt' => implode(',', $productCodes)
        ]);

        $headers = [
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
            'language: fr',
            'country: ' . strtolower($country),
            'platform: reps'
        ];

        $response = $this->makeRequest('GET', $url, $headers);

        if (!$response || !isset($response['priceFlows'])) {
            error_log('[TrendyFoodsAPI] Réponse invalide pour getProductsInfo: ' . json_encode($response));
            return $this->buildErrorResponse($productCodes, 'api_response_error');
        }

        // Parser la réponse
        return $this->parseProductsResponse($response['priceFlows'], $productCodes);
    }

    /**
     * Parser la réponse de l'API pour les produits
     *
     * @param array $priceFlows Données de l'API
     * @param array $requestedCodes Codes demandés (pour vérifier les manquants)
     * @return array
     */
    private function parseProductsResponse(array $priceFlows, array $requestedCodes): array
    {
        $result = [];

        // Indexer les réponses par code
        foreach ($priceFlows as $item) {
            $code = $item['rechart'] ?? '';

            if (empty($code)) {
                continue;
            }

            $result[$code] = [
                'adroit' => ($item['adroit'] ?? 'N') === 'Y',
                'prix' => $item['prix'] ?? null,
                'prix_promo' => $item['prixp'] ?? null,
                'prix_colis' => $item['prixc'] ?? null,
                'prix_colis_promo' => $item['prixcp'] ?? null,
                'qte_disponible' => $item['qte_disp1'] ?? null,
                'api_success' => true
            ];
        }

        // Ajouter les codes demandés mais non retournés (= non autorisés)
        foreach ($requestedCodes as $code) {
            if (!isset($result[$code])) {
                $result[$code] = [
                    'adroit' => false,
                    'prix' => null,
                    'prix_promo' => null,
                    'prix_colis' => null,
                    'prix_colis_promo' => null,
                    'qte_disponible' => null,
                    'api_success' => true
                ];
            }
        }

        return $result;
    }

    /**
     * Construire une réponse d'erreur (tous les produits marqués comme erreur)
     *
     * @param array $productCodes
     * @param string $errorType
     * @return array
     */
    private function buildErrorResponse(array $productCodes, string $errorType): array
    {
        $result = [];

        foreach ($productCodes as $code) {
            $result[$code] = [
                'adroit' => null, // null = impossible de vérifier
                'prix' => null,
                'prix_promo' => null,
                'prix_colis' => null,
                'prix_colis_promo' => null,
                'qte_disponible' => null,
                'api_success' => false,
                'error' => $errorType
            ];
        }

        return $result;
    }

    /**
     * Filtrer les produits autorisés
     *
     * @param array $productsInfo Résultat de getProductsInfo()
     * @return array Codes des produits autorisés uniquement
     */
    public function filterAuthorizedProducts(array $productsInfo): array
    {
        $authorized = [];

        foreach ($productsInfo as $code => $info) {
            if ($info['adroit'] === true) {
                $authorized[] = $code;
            }
        }

        return $authorized;
    }

    /**
     * Vérifier si au moins un produit est autorisé
     *
     * @param array $productsInfo Résultat de getProductsInfo()
     * @return bool
     */
    public function hasAuthorizedProducts(array $productsInfo): bool
    {
        foreach ($productsInfo as $info) {
            if ($info['adroit'] === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifier si l'API a répondu correctement
     *
     * @param array $productsInfo Résultat de getProductsInfo()
     * @return bool
     */
    public function isApiResponseValid(array $productsInfo): bool
    {
        if (empty($productsInfo)) {
            return false;
        }

        // Vérifier qu'au moins un produit a une réponse valide
        foreach ($productsInfo as $info) {
            if ($info['api_success'] === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Effectuer une requête HTTP
     *
     * @param string $method GET ou POST
     * @param string $url URL complète
     * @param array $headers Headers HTTP
     * @param string|null $body Corps de la requête (pour POST)
     * @return array|null Réponse décodée ou null
     */
    private function makeRequest(string $method, string $url, array $headers, ?string $body = null): ?array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            error_log("[TrendyFoodsAPI] cURL error: $error");
            return null;
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            error_log("[TrendyFoodsAPI] HTTP $httpCode: $response");
            return null;
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("[TrendyFoodsAPI] JSON decode error: " . json_last_error_msg());
            return null;
        }

        return $decoded;
    }

    /**
     * Invalider le token en cache (force un nouveau token au prochain appel)
     *
     * @return void
     */
    public function invalidateToken(): void
    {
        unset($_SESSION[self::TOKEN_SESSION_KEY]);
        unset($_SESSION[self::TOKEN_EXPIRY_KEY]);
    }
}