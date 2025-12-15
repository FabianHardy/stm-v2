<?php
/**
 * Service : MicrosoftAuthService
 *
 * Gestion de l'authentification OAuth2 avec Microsoft Entra ID
 * - Génération URL d'autorisation
 * - Échange code → token
 * - Appel Graph API (profil user + manager)
 *
 * @package STM
 * @created 2025/12/15
 */

namespace App\Services;

use Core\Session;

class MicrosoftAuthService
{
    private string $tenantId;
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    private string $authorizeUrl;
    private string $tokenUrl;
    private string $graphUrl = 'https://graph.microsoft.com/v1.0';

    public function __construct()
    {
        // Charger la configuration depuis le fichier config/microsoft.php
        $configFile = BASE_PATH . '/config/microsoft.php';

        if (file_exists($configFile)) {
            $config = require $configFile;
            $this->tenantId = $config['tenant_id'] ?? '';
            $this->clientId = $config['client_id'] ?? '';
            $this->clientSecret = $config['client_secret'] ?? '';
            $this->redirectUri = $config['redirect_uri'] ?? '';
        } else {
            // Fallback sur $_ENV / $_SERVER
            $this->tenantId = $_ENV['MICROSOFT_TENANT_ID'] ?? $_SERVER['MICROSOFT_TENANT_ID'] ?? '';
            $this->clientId = $_ENV['MICROSOFT_CLIENT_ID'] ?? $_SERVER['MICROSOFT_CLIENT_ID'] ?? '';
            $this->clientSecret = $_ENV['MICROSOFT_CLIENT_SECRET'] ?? $_SERVER['MICROSOFT_CLIENT_SECRET'] ?? '';
            $this->redirectUri = $_ENV['MICROSOFT_REDIRECT_URI'] ?? $_SERVER['MICROSOFT_REDIRECT_URI'] ?? '';
        }

        $this->authorizeUrl = "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/authorize";
        $this->tokenUrl = "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token";
    }

    /**
     * Vérifie si la configuration Microsoft est complète
     */
    public function isConfigured(): bool
    {
        return !empty($this->tenantId)
            && !empty($this->clientId)
            && !empty($this->clientSecret)
            && !empty($this->redirectUri);
    }

    /**
     * Génère l'URL de redirection vers Microsoft pour l'authentification
     */
    public function getAuthorizationUrl(): string
    {
        // Générer un state aléatoire pour la sécurité CSRF
        $state = bin2hex(random_bytes(16));
        Session::set('oauth_state', $state);

        $params = [
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri,
            'scope' => 'openid profile email User.Read User.Read.All',
            'state' => $state,
            'response_mode' => 'query',
        ];

        return $this->authorizeUrl . '?' . http_build_query($params);
    }

    /**
     * Valide le state retourné par Microsoft
     */
    public function validateState(string $state): bool
    {
        $savedState = Session::get('oauth_state');
        Session::remove('oauth_state');

        return !empty($savedState) && hash_equals($savedState, $state);
    }

    /**
     * Échange le code d'autorisation contre un access token
     */
    public function getAccessToken(string $code): ?array
    {
        $params = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code',
        ];

        $response = $this->httpPost($this->tokenUrl, $params);

        if (!$response || isset($response['error'])) {
            error_log("Microsoft OAuth Error: " . ($response['error_description'] ?? 'Unknown error'));
            return null;
        }

        return $response;
    }

    /**
     * Récupère le profil de l'utilisateur connecté via Graph API
     */
    public function getUserProfile(string $accessToken): ?array
    {
        $response = $this->httpGet($this->graphUrl . '/me', $accessToken);

        if (!$response || isset($response['error'])) {
            error_log("Microsoft Graph Error (me): " . json_encode($response['error'] ?? 'Unknown error'));
            return null;
        }

        return $response;
    }

    /**
     * Récupère le manager de l'utilisateur connecté via Graph API
     * Retourne null si pas de manager (ex: CEO)
     */
    public function getUserManager(string $accessToken): ?array
    {
        $response = $this->httpGet($this->graphUrl . '/me/manager', $accessToken);

        // Pas de manager = normal pour certains users (404 ou error)
        if (!$response || isset($response['error'])) {
            // Ce n'est pas une erreur critique, juste pas de manager
            return null;
        }

        return $response;
    }

    /**
     * Récupère les infos complètes : user + manager
     */
    public function getFullUserInfo(string $accessToken): array
    {
        $user = $this->getUserProfile($accessToken);
        $manager = $this->getUserManager($accessToken);

        return [
            'user' => $user,
            'manager' => $manager,
        ];
    }

    /**
     * Requête HTTP GET avec Bearer token
     */
    private function httpGet(string $url, string $accessToken): ?array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("cURL Error: " . $error);
            return null;
        }

        if ($httpCode >= 400) {
            error_log("HTTP Error {$httpCode}: " . $response);
            return json_decode($response, true);
        }

        return json_decode($response, true);
    }

    /**
     * Requête HTTP POST (form-urlencoded)
     */
    private function httpPost(string $url, array $params): ?array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("cURL Error: " . $error);
            return null;
        }

        return json_decode($response, true);
    }
}