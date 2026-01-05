<?php
/**
 * TrendyFoodsApiService.php
 *
 * Service pour communiquer avec l'API Trendy Foods
 * Permet de vérifier l'éligibilité des produits pour un client
 * et de récupérer les prix en temps réel
 *
 * @package STM
 * @created 2025/01/05
 * @modified 2025/01/05 - Correction device_unique_id autorisé
 */

namespace App\Services;

class TrendyFoodsApiService
{
    /**
     * URL de base de l'API
     */
    private string $apiUrl;

    /**
     * Login API
     */
    private string $apiLogin;

    /**
     * Mot de passe API
     */
    private string $apiPassword;

    /**
     * Device unique ID autorisé pour l'authentification
     */
    private string $deviceUniqueId = '45022A7A-1289-4A8D-ABF8-4A899362EA36';

    /**
     * Durée de validité du token en secondes (1 heure avec marge de sécurité)
     */
    private int $tokenTtl = 3600;

    /**
     * Constructeur - Charge la configuration depuis les variables d'environnement
     */
    public function __construct()
    {
        $this->apiUrl = rtrim($_ENV['TRENDY_API_URL'] ?? '', '/');
        $this->apiLogin = $_ENV['TRENDY_API_LOGIN'] ?? '';
        $this->apiPassword = $_ENV['TRENDY_API_PASSWORD'] ?? '';

        if (empty($this->apiUrl) || empty($this->apiLogin) || empty($this->apiPassword)) {
            error_log("[TrendyFoodsApiService] Configuration API manquante dans .env");
        }
    }

    /**
     * Obtenir un token d'authentification (avec cache session)
     *
     * @return string|null Token JWT ou null si erreur
     */
    public function getToken(): ?string
    {
        // Vérifier si un token valide existe en session
        if ($this->isTokenValid()) {
            return $_SESSION['trendy_api_token'];
        }

        // Demander un nouveau token
        $url = $this->apiUrl . '/tokens';

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'language: fr',
            'country: be',
            'platform: reps'
        ];

        $body = json_encode([
            'login' => $this->apiLogin,
            'password' => $this->apiPassword,
            'application' => 'reps',
            'device_unique_id' => $this->deviceUniqueId
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("[TrendyFoodsApiService] cURL error lors de getToken: {$error}");
            return null;
        }

        if ($httpCode !== 200 && $httpCode !== 201) {
            error_log("[TrendyFoodsApiService] HTTP {$httpCode} lors de getToken: {$response}");
            return null;
        }

        $data = json_decode($response, true);

        if (!isset($data['token'])) {
            error_log("[TrendyFoodsApiService] Pas de token dans la réponse: {$response}");
            return null;
        }

        // Stocker le token en session avec son expiration
        $_SESSION['trendy_api_token'] = $data['token'];
        $_SESSION['trendy_api_token_expiry'] = time() + $this->tokenTtl;

        return $data['token'];
    }

    /**
     * Vérifier si le token en session est encore valide
     *
     * @return bool True si le token est valide
     */
    private function isTokenValid(): bool
    {
        if (!isset($_SESSION['trendy_api_token']) || !isset($_SESSION['trendy_api_token_expiry'])) {
            return false;
        }

        // Marge de 5 minutes avant expiration
        return $_SESSION['trendy_api_token_expiry'] > (time() + 300);
    }

    /**
     * Invalider le token en session (force un nouveau token au prochain appel)
     */
    public function invalidateToken(): void
    {
        unset($_SESSION['trendy_api_token']);
        unset($_SESSION['trendy_api_token_expiry']);
    }

    /**
     * Récupérer les informations produits pour un client
     *
     * @param string $customerNumber Numéro client
     * @param string $country Code pays (BE/LU)
     * @param array $productCodes Liste des codes produits à vérifier
     * @return array Informations produits indexées par code produit
     */
    public function getProductsInfo(string $customerNumber, string $country, array $productCodes): array
    {
        if (empty($productCodes)) {
            return [];
        }

        $token = $this->getToken();

        if (!$token) {
            error_log("[TrendyFoodsApiService] Impossible d'obtenir un token pour getProductsInfo");
            return ['api_success' => false, 'error' => 'api_token_error'];
        }

        // Construire la liste des articles (séparés par virgule)
        $listArt = implode(',', $productCodes);

        // Construire l'URL avec les paramètres
        $url = $this->apiUrl . '/ipad/prices_list_art?' . http_build_query([
            'cltno' => $customerNumber,
            'listArt' => $listArt
        ]);

        $headers = [
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
            'language: fr',
            'country: ' . strtolower($country),
            'platform: reps'
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("[TrendyFoodsApiService] cURL error lors de getProductsInfo: {$error}");
            return ['api_success' => false, 'error' => 'api_curl_error'];
        }

        if ($httpCode !== 200) {
            error_log("[TrendyFoodsApiService] HTTP {$httpCode} lors de getProductsInfo: {$response}");

            // Si 401, invalider le token et réessayer une fois
            if ($httpCode === 401) {
                $this->invalidateToken();
                return $this->getProductsInfo($customerNumber, $country, $productCodes);
            }

            return ['api_success' => false, 'error' => 'api_http_error'];
        }

        $data = json_decode($response, true);

        if (!isset($data['priceFlows'])) {
            error_log("[TrendyFoodsApiService] Réponse invalide (pas de priceFlows): {$response}");
            return ['api_success' => false, 'error' => 'api_response_error'];
        }

        // Parser et indexer les résultats par code produit
        return $this->parseProductsResponse($data, $productCodes);
    }

    /**
     * Parser la réponse API et indexer par code produit
     *
     * @param array $data Réponse API brute
     * @param array $requestedCodes Codes produits demandés
     * @return array Produits indexés par code
     */
    private function parseProductsResponse(array $data, array $requestedCodes): array
    {
        $result = ['api_success' => true];

        // Indexer les produits retournés par l'API
        $apiProducts = [];
        foreach ($data['priceFlows'] as $product) {
            $code = $product['rechart'] ?? null;
            if ($code) {
                $apiProducts[$code] = [
                    'adroit' => ($product['adroit'] ?? 'N') === 'Y',
                    'prix' => $product['prix'] ?? null,
                    'prix_promo' => $product['prixp'] ?? null,
                    'prix_colis' => $product['prixc'] ?? null,
                    'prix_colis_promo' => $product['prixcp'] ?? null,
                    'qte_disponible' => $product['qte_disp1'] ?? null
                ];
            }
        }

        // Pour chaque code demandé, vérifier s'il est dans la réponse
        foreach ($requestedCodes as $code) {
            if (isset($apiProducts[$code])) {
                $result[$code] = $apiProducts[$code];
            } else {
                // Produit non retourné par l'API = non autorisé
                $result[$code] = [
                    'adroit' => false,
                    'prix' => null,
                    'prix_promo' => null,
                    'prix_colis' => null,
                    'prix_colis_promo' => null,
                    'qte_disponible' => null
                ];
            }
        }

        return $result;
    }

    /**
     * Filtrer les produits autorisés (adroit = true)
     *
     * @param array $productsInfo Résultat de getProductsInfo()
     * @return array Liste des codes produits autorisés
     */
    public function filterAuthorizedProducts(array $productsInfo): array
    {
        $authorized = [];

        foreach ($productsInfo as $code => $info) {
            // Ignorer les clés de métadonnées
            if (in_array($code, ['api_success', 'error'])) {
                continue;
            }

            if (isset($info['adroit']) && $info['adroit'] === true) {
                $authorized[] = $code;
            }
        }

        return $authorized;
    }

    /**
     * Vérifier si au moins un produit est autorisé
     *
     * @param array $productsInfo Résultat de getProductsInfo()
     * @return bool True si au moins un produit est autorisé
     */
    public function hasAuthorizedProducts(array $productsInfo): bool
    {
        return !empty($this->filterAuthorizedProducts($productsInfo));
    }

    /**
     * Vérifier si la réponse API est valide (pas d'erreur)
     *
     * @param array $productsInfo Résultat de getProductsInfo()
     * @return bool True si la réponse est valide
     */
    public function isApiResponseValid(array $productsInfo): bool
    {
        return isset($productsInfo['api_success']) && $productsInfo['api_success'] === true;
    }
}