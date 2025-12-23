<?php
/**
 * Contrôleur : AuthEntraController
 * 
 * Gestion de l'authentification via Microsoft Entra ID (SSO)
 * - Redirection vers Microsoft
 * - Traitement du callback
 * - Création/liaison de compte
 * 
 * @package STM
 * @created 2025/12/15
 */

namespace App\Controllers;

use App\Services\MicrosoftAuthService;
use Core\Database;
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
            return $this->handleExistingUser($existingUser, $microsoftId);
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
     * Gère la connexion d'un utilisateur existant
     */
    private function handleExistingUser(array $user, string $microsoftId): array
    {
        // Vérifier si le compte est actif
        if (!$user['is_active']) {
            return [
                'success' => false,
                'pending' => true,
                'message' => 'Votre compte est en attente d\'activation par un administrateur.'
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
        
        // Mettre à jour la dernière connexion
        $this->db->query(
            "UPDATE users SET last_login_at = NOW(), updated_at = NOW() WHERE id = :id",
            [':id' => $user['id']]
        );
        
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
                    is_active, created_at, updated_at
                ) VALUES (
                    :username, :email, :name, :role, :microsoft_id,
                    :is_active, NOW(), NOW()
                )
            ");
            
            $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':name' => $displayName,
                ':role' => $role,
                ':microsoft_id' => $microsoftId,
                ':is_active' => $isActive ? 1 : 0,
            ]);
            
            $userId = $this->db->getConnection()->lastInsertId();
            
            // Si on a un manager, mettre à jour rep_id (si la colonne existe)
            if ($managerId && $isActive) {
                // Note: adapter selon la structure de la table users
                // $this->db->query("UPDATE users SET rep_id = :manager_id WHERE id = :id", [...]);
            }
            
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
