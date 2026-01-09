<?php
/**
 * Contrôleur : AuthEntraController
 *
 * Gestion de l'authentification via Microsoft Entra ID (SSO)
 * - Redirection vers Microsoft
 * - Traitement du callback
 * - Création/liaison de compte
 * - Auto-matching rep_id par email dans BE_REP/LU_REP
 *
 * @package STM
 * @created 2025/12/15
 * @modified 2025/01/05 - Ajout auto-matching rep_id par email
 * @modified 2026/01/08 - Message compte désactivé plus explicite
 */

namespace App\Controllers;

use App\Services\MicrosoftAuthService;
use Core\Database;
use Core\ExternalDatabase;
use Core\Session;

class AuthEntraController
{
    private MicrosoftAuthService $microsoftAuth;
    private Database $db;

    public function __construct()
    {
        $this->microsoftAuth = new MicrosoftAuthService();
        $this->db = Database::getInstance();
    }

    /**
     * Redirige l'utilisateur vers la page de connexion Microsoft
     * GET /auth/microsoft
     */
    public function redirectToMicrosoft(): void
    {
        // Vérifier que la configuration est complète
        if (!$this->microsoftAuth->isConfigured()) {
            Session::setFlash('error', 'La connexion Microsoft n\'est pas configurée.');
            header('Location: /stm/admin/login');
            exit;
        }

        $authUrl = $this->microsoftAuth->getAuthorizationUrl();
        header('Location: ' . $authUrl);
        exit;
    }

    /**
     * Traite le callback de Microsoft après authentification
     * GET /auth/callback
     */
    public function handleCallback(): void
    {
        // Vérifier les erreurs retournées par Microsoft
        if (isset($_GET['error'])) {
            $errorDesc = $_GET['error_description'] ?? 'Erreur inconnue';
            error_log("Microsoft Auth Error: " . $errorDesc);
            Session::setFlash('error', 'Erreur d\'authentification Microsoft : ' . htmlspecialchars($errorDesc));
            header('Location: /stm/admin/login');
            exit;
        }

        // Vérifier la présence du code
        $code = $_GET['code'] ?? '';
        $state = $_GET['state'] ?? '';

        if (empty($code)) {
            Session::setFlash('error', 'Code d\'autorisation manquant.');
            header('Location: /stm/admin/login');
            exit;
        }

        // Valider le state (protection CSRF)
        if (!$this->microsoftAuth->validateState($state)) {
            Session::setFlash('error', 'État de sécurité invalide. Veuillez réessayer.');
            header('Location: /stm/admin/login');
            exit;
        }

        // Échanger le code contre un token
        $tokenData = $this->microsoftAuth->getAccessToken($code);

        if (!$tokenData || !isset($tokenData['access_token'])) {
            Session::setFlash('error', 'Impossible d\'obtenir le token d\'accès.');
            header('Location: /stm/admin/login');
            exit;
        }

        $accessToken = $tokenData['access_token'];

        // Récupérer les infos utilisateur + manager
        $userInfo = $this->microsoftAuth->getFullUserInfo($accessToken);

        if (!$userInfo['user']) {
            Session::setFlash('error', 'Impossible de récupérer votre profil Microsoft.');
            header('Location: /stm/admin/login');
            exit;
        }

        // Traiter la connexion/création du compte
        $result = $this->processUser($userInfo['user'], $userInfo['manager']);

        if ($result['success']) {
            if ($result['pending']) {
                // Compte créé mais en attente d'activation
                Session::setFlash('warning', 'Votre compte a été créé mais est en attente d\'activation par un administrateur.');
                header('Location: /stm/admin/login');
            } else {
                // Connexion réussie
                Session::setFlash('success', 'Connexion réussie via Microsoft !');
                header('Location: /stm/admin/dashboard');
            }
        } else {
            Session::setFlash('error', $result['message']);
            header('Location: /stm/admin/login');
        }
        exit;
    }

    /**
     * Traite l'utilisateur : création ou liaison de compte
     */
    private function processUser(array $msUser, ?array $msManager): array
    {
        $microsoftId = $msUser['id'] ?? null;
        $email = $msUser['mail'] ?? $msUser['userPrincipalName'] ?? null;
        $displayName = $msUser['displayName'] ?? 'Utilisateur';
        $givenName = $msUser['givenName'] ?? '';
        $surname = $msUser['surname'] ?? '';

        if (!$microsoftId || !$email) {
            return [
                'success' => false,
                'pending' => false,
                'message' => 'Informations utilisateur incomplètes depuis Microsoft.'
            ];
        }

        // Chercher si l'utilisateur existe déjà (par microsoft_id ou email)
        $existingUser = $this->findExistingUser($microsoftId, $email);

        if ($existingUser) {
            // Utilisateur existant
            return $this->handleExistingUser($existingUser, $microsoftId, $email);
        } else {
            // Nouvel utilisateur : création
            return $this->createNewUser($microsoftId, $email, $displayName, $msManager);
        }
    }

    /**
     * Cherche un utilisateur existant par microsoft_id ou email
     */
    private function findExistingUser(string $microsoftId, string $email): ?array
    {
        // D'abord par microsoft_id
        $user = $this->db->query(
            "SELECT * FROM users WHERE microsoft_id = :ms_id LIMIT 1",
            [':ms_id' => $microsoftId]
        );

        if (!empty($user)) {
            return $user[0];
        }

        // Ensuite par email
        $user = $this->db->query(
            "SELECT * FROM users WHERE email = :email LIMIT 1",
            [':email' => $email]
        );

        if (!empty($user)) {
            return $user[0];
        }

        return null;
    }

    /**
     * Cherche un représentant dans BE_REP ou LU_REP par email
     * Retourne ['rep_id' => 'XXX', 'rep_country' => 'BE'|'LU'] ou null
     */
    private function findRepByEmail(string $email): ?array
    {
        try {
            $extDb = ExternalDatabase::getInstance();

            // Chercher dans BE_REP
            $result = $extDb->query(
                "SELECT IDE_REP FROM BE_REP WHERE REP_EMAIL = ? LIMIT 1",
                [$email]
            );

            if (!empty($result) && is_array($result) && !empty($result[0]['IDE_REP'])) {
                error_log("Rep trouvé dans BE_REP pour {$email}: {$result[0]['IDE_REP']}");
                return [
                    'rep_id' => $result[0]['IDE_REP'],
                    'rep_country' => 'BE'
                ];
            }

            // Chercher dans LU_REP
            $result = $extDb->query(
                "SELECT IDE_REP FROM LU_REP WHERE REP_EMAIL = ? LIMIT 1",
                [$email]
            );

            if (!empty($result) && is_array($result) && !empty($result[0]['IDE_REP'])) {
                error_log("Rep trouvé dans LU_REP pour {$email}: {$result[0]['IDE_REP']}");
                return [
                    'rep_id' => $result[0]['IDE_REP'],
                    'rep_country' => 'LU'
                ];
            }

            error_log("Aucun rep trouvé pour l'email: {$email}");
            return null;

        } catch (\Exception $e) {
            error_log("Erreur findRepByEmail: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Met à jour rep_id et rep_country pour un utilisateur
     */
    private function updateRepInfo(int $userId, string $repId, string $repCountry): void
    {
        try {
            $this->db->query(
                "UPDATE users SET rep_id = :rep_id, rep_country = :rep_country, updated_at = NOW() WHERE id = :id",
                [
                    ':rep_id' => $repId,
                    ':rep_country' => $repCountry,
                    ':id' => $userId
                ]
            );
            error_log("Rep info mise à jour pour user {$userId}: rep_id={$repId}, rep_country={$repCountry}");
        } catch (\Exception $e) {
            error_log("Erreur updateRepInfo: " . $e->getMessage());
        }
    }

    /**
     * Gère la connexion d'un utilisateur existant
     */
    private function handleExistingUser(array $user, string $microsoftId, string $email): array
    {
        // Vérifier si le compte est actif
        if (!$user['is_active']) {
            return [
                'success' => false,
                'pending' => true,
                'message' => 'Votre compte a été désactivé. Veuillez contacter un administrateur.'
            ];
        }

        // Vérifier si le compte est verrouillé
        if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
            return [
                'success' => false,
                'pending' => false,
                'message' => 'Votre compte est temporairement verrouillé.'
            ];
        }

        // Lier le microsoft_id si pas encore fait
        if (empty($user['microsoft_id'])) {
            $this->db->query(
                "UPDATE users SET microsoft_id = :ms_id, updated_at = NOW() WHERE id = :id",
                [':ms_id' => $microsoftId, ':id' => $user['id']]
            );
        }

        // ============================================================
        // AUTO-MATCHING REP_ID : Si rep_id est NULL, chercher par email
        // ============================================================
        if (empty($user['rep_id']) && in_array($user['role'], ['rep', 'manager_reps'])) {
            $repInfo = $this->findRepByEmail($email);

            if ($repInfo) {
                $this->updateRepInfo($user['id'], $repInfo['rep_id'], $repInfo['rep_country']);
                // Mettre à jour le tableau user pour la session
                $user['rep_id'] = $repInfo['rep_id'];
                $user['rep_country'] = $repInfo['rep_country'];
            }
        }

        // Mettre à jour la dernière connexion
        $this->db->query(
            "UPDATE users SET last_login_at = NOW(), updated_at = NOW() WHERE id = :id",
            [':id' => $user['id']]
        );

        // Recharger l'utilisateur avec les infos mises à jour
        $updatedUser = $this->db->query("SELECT * FROM users WHERE id = :id", [':id' => $user['id']]);
        if (!empty($updatedUser)) {
            $user = $updatedUser[0];
        }

        // Connecter l'utilisateur
        $this->loginUser($user);

        return [
            'success' => true,
            'pending' => false,
            'message' => 'Connexion réussie.'
        ];
    }

    /**
     * Crée un nouvel utilisateur depuis Microsoft
     */
    private function createNewUser(string $microsoftId, string $email, string $displayName, ?array $msManager): array
    {
        $role = 'rep'; // Rôle par défaut
        $isActive = false; // Par défaut en pending
        $managerId = null;
        $repId = null;
        $repCountry = null;

        // ============================================================
        // AUTO-MATCHING REP_ID : Chercher par email dans BE_REP/LU_REP
        // ============================================================
        $repInfo = $this->findRepByEmail($email);
        if ($repInfo) {
            $repId = $repInfo['rep_id'];
            $repCountry = $repInfo['rep_country'];
        }

        // Si on a un manager Microsoft, chercher s'il existe dans STM
        if ($msManager && isset($msManager['id'])) {
            $managerMicrosoftId = $msManager['id'];

            $stmManager = $this->db->query(
                "SELECT id, role FROM users WHERE microsoft_id = :ms_id AND is_active = 1 LIMIT 1",
                [':ms_id' => $managerMicrosoftId]
            );

            if (!empty($stmManager)) {
                $stmManager = $stmManager[0];

                // Si le manager STM a le rôle manager_reps → créer le rep actif
                if ($stmManager['role'] === 'manager_reps') {
                    $isActive = true;
                    $managerId = $stmManager['id'];
                    $role = 'rep';
                }
                // Sinon → pending (manager existe mais pas manager_reps)
            }
            // Si manager non trouvé dans STM → pending
        }
        // Si pas de manager Microsoft → pending

        // Générer un username unique à partir de l'email
        $username = strtolower(explode('@', $email)[0]);
        $baseUsername = $username;
        $counter = 1;

        while ($this->usernameExists($username)) {
            $username = $baseUsername . $counter;
            $counter++;
        }

        // Créer l'utilisateur
        try {
            $stmt = $this->db->getConnection()->prepare("
                INSERT INTO users (
                    username, email, name, role, microsoft_id,
                    rep_id, rep_country, manager_id,
                    is_active, created_at, updated_at
                ) VALUES (
                    :username, :email, :name, :role, :microsoft_id,
                    :rep_id, :rep_country, :manager_id,
                    :is_active, NOW(), NOW()
                )
            ");

            $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':name' => $displayName,
                ':role' => $role,
                ':microsoft_id' => $microsoftId,
                ':rep_id' => $repId,
                ':rep_country' => $repCountry,
                ':manager_id' => $managerId,
                ':is_active' => $isActive ? 1 : 0,
            ]);

            $userId = $this->db->getConnection()->lastInsertId();

            if ($isActive) {
                // Connecter directement
                $newUser = $this->db->query("SELECT * FROM users WHERE id = :id", [':id' => $userId]);
                if (!empty($newUser)) {
                    $this->loginUser($newUser[0]);
                }

                return [
                    'success' => true,
                    'pending' => false,
                    'message' => 'Compte créé et connecté.'
                ];
            } else {
                return [
                    'success' => true,
                    'pending' => true,
                    'message' => 'Compte créé en attente d\'activation.'
                ];
            }

        } catch (\PDOException $e) {
            error_log("Erreur création utilisateur Microsoft: " . $e->getMessage());
            return [
                'success' => false,
                'pending' => false,
                'message' => 'Erreur lors de la création du compte.'
            ];
        }
    }

    /**
     * Vérifie si un username existe déjà
     */
    private function usernameExists(string $username): bool
    {
        $result = $this->db->query(
            "SELECT COUNT(*) as count FROM users WHERE username = :username",
            [':username' => $username]
        );
        return ($result[0]['count'] ?? 0) > 0;
    }

    /**
     * Connecte l'utilisateur en session
     */
    private function loginUser(array $user): void
    {
        Session::regenerate();

        Session::set('user_id', $user['id']);
        Session::set('user_username', $user['username']);
        Session::set('user_role', $user['role']);
        Session::set('user', [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role'],
            'rep_id' => $user['rep_id'] ?? null,
            'rep_country' => $user['rep_country'] ?? null,
            'microsoft_id' => $user['microsoft_id'] ?? null,
        ]);
    }
}