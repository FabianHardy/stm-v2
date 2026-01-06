<?php
/**
 * ProfileController
 *
 * Gestion du profil utilisateur connecté
 *
 * @package STM
 * @version 1.0
 * @created 19/12/2025
 */

namespace App\Controllers;

use Core\Database;
use Core\Session;
use App\Helpers\StatsAccessHelper;

class ProfileController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Afficher la page profil
     */
    public function index(): void
    {
        $currentUser = Session::get('user');

        if (!$currentUser) {
            header('Location: /stm/admin/login');
            exit();
        }

        // Récupérer les infos complètes de l'utilisateur
        $user = $this->db->queryOne(
            "SELECT id, name, email, role, avatar, is_active, created_at, updated_at
             FROM users WHERE id = ?",
            [$currentUser['id']]
        );

        if (!$user) {
            Session::setFlash('error', 'Utilisateur introuvable');
            header('Location: /stm/admin/dashboard');
            exit();
        }

        // Récupérer les statistiques personnelles
        $stats = $this->getUserStats($user['id'], $user['role']);

        // Récupérer les campagnes assignées (pour createur, manager_reps, rep)
        $assignedCampaigns = $this->getAssignedCampaigns($user['id'], $user['role']);

        // Messages flash
        $success = Session::getFlash('success');
        $error = Session::getFlash('error');

        require_once __DIR__ . '/../Views/admin/profile/index.php';
    }

    /**
     * Mettre à jour l'avatar
     */
    public function updateAvatar(): void
    {
        $currentUser = Session::get('user');

        if (!$currentUser) {
            Session::setFlash('error', 'Non authentifié');
            header('Location: /stm/admin/login');
            exit();
        }

        // Vérifier CSRF
        $csrfToken = $_POST['_token'] ?? '';
        $sessionToken = Session::get('csrf_token');
        if (empty($csrfToken) || empty($sessionToken) || $csrfToken !== $sessionToken) {
            Session::setFlash('error', 'Token CSRF invalide');
            header('Location: /stm/admin/profile');
            exit();
        }

        // Vérifier les erreurs d'upload PHP
        if (!isset($_FILES['avatar']) || !is_array($_FILES['avatar'])) {
            Session::setFlash('error', 'Aucun fichier reçu');
            header('Location: /stm/admin/profile');
            exit();
        }

        $uploadError = $_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE;

        // Gérer les différentes erreurs d'upload PHP
        switch ($uploadError) {
            case UPLOAD_ERR_OK:
                // Tout va bien, on continue
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                Session::setFlash('error', 'L\'image est trop volumineuse. Taille maximum : 2 MB');
                header('Location: /stm/admin/profile');
                exit();
            case UPLOAD_ERR_PARTIAL:
                Session::setFlash('error', 'L\'upload a été interrompu. Veuillez réessayer.');
                header('Location: /stm/admin/profile');
                exit();
            case UPLOAD_ERR_NO_FILE:
                Session::setFlash('error', 'Aucune image sélectionnée');
                header('Location: /stm/admin/profile');
                exit();
            case UPLOAD_ERR_NO_TMP_DIR:
            case UPLOAD_ERR_CANT_WRITE:
            case UPLOAD_ERR_EXTENSION:
                Session::setFlash('error', 'Erreur serveur lors de l\'upload. Contactez l\'administrateur.');
                header('Location: /stm/admin/profile');
                exit();
            default:
                Session::setFlash('error', 'Erreur inconnue lors de l\'upload');
                header('Location: /stm/admin/profile');
                exit();
        }

        // Vérifier que le fichier temporaire existe
        if (empty($_FILES['avatar']['tmp_name']) || !is_uploaded_file($_FILES['avatar']['tmp_name'])) {
            Session::setFlash('error', 'Fichier temporaire introuvable');
            header('Location: /stm/admin/profile');
            exit();
        }

        try {
            $avatarPath = $this->handleAvatarUpload($_FILES['avatar'], $currentUser['id']);

            // Supprimer l'ancien avatar si existe
            $oldAvatar = $this->db->queryOne(
                "SELECT avatar FROM users WHERE id = ?",
                [$currentUser['id']]
            );

            if ($oldAvatar && !empty($oldAvatar['avatar'])) {
                $this->deleteAvatar($oldAvatar['avatar']);
            }

            // Mettre à jour en base
            $this->db->execute(
                "UPDATE users SET avatar = ?, updated_at = NOW() WHERE id = ?",
                [$avatarPath, $currentUser['id']]
            );

            // Mettre à jour la session
            $currentUser['avatar'] = $avatarPath;
            Session::set('user', $currentUser);

            Session::setFlash('success', 'Photo de profil mise à jour avec succès');
        } catch (\Exception $e) {
            error_log("Erreur upload avatar: " . $e->getMessage());
            Session::setFlash('error', $e->getMessage());
        }

        header('Location: /stm/admin/profile');
        exit();
    }

    /**
     * Supprimer l'avatar
     * Note: Utilise GET avec token en query string pour compatibilité avec le lien
     */
    public function deleteAvatarAction(): void
    {
        $currentUser = Session::get('user');

        if (!$currentUser) {
            header('Location: /stm/admin/login');
            exit();
        }

        // Vérifier CSRF via query string (pour GET)
        $csrfToken = $_GET['_token'] ?? '';
        $sessionToken = Session::get('csrf_token');
        if (empty($csrfToken) || empty($sessionToken) || $csrfToken !== $sessionToken) {
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /stm/admin/profile');
            exit();
        }

        try {
            // Récupérer l'ancien avatar
            $user = $this->db->queryOne(
                "SELECT avatar FROM users WHERE id = ?",
                [$currentUser['id']]
            );

            if ($user && !empty($user['avatar'])) {
                $this->deleteAvatar($user['avatar']);
            }

            // Mettre à jour en base
            $this->db->execute(
                "UPDATE users SET avatar = NULL, updated_at = NOW() WHERE id = ?",
                [$currentUser['id']]
            );

            // Mettre à jour la session
            $currentUser['avatar'] = null;
            Session::set('user', $currentUser);

            Session::setFlash('success', 'Photo de profil supprimée');
        } catch (\Exception $e) {
            error_log("Erreur suppression avatar: " . $e->getMessage());
            Session::setFlash('error', 'Erreur lors de la suppression');
        }

        header('Location: /stm/admin/profile');
        exit();
    }

    /**
     * Récupérer les statistiques de l'utilisateur
     */
    private function getUserStats(int $userId, string $role): array
    {
        $stats = [
            'campaigns_assigned' => 0,
            'campaigns_active' => 0,
            'orders_total' => 0,
            'orders_this_month' => 0,
            'products_managed' => 0,
            'customers_accessible' => 0,
        ];

        try {
            // Campagnes assignées (pour createur)
            if (in_array($role, ['createur'])) {
                $result = $this->db->queryOne(
                    "SELECT COUNT(*) as count FROM campaign_assignees WHERE user_id = ?",
                    [$userId]
                );
                $stats['campaigns_assigned'] = (int) ($result['count'] ?? 0);

                $result = $this->db->queryOne(
                    "SELECT COUNT(*) as count FROM campaign_assignees ca
                     JOIN campaigns c ON ca.campaign_id = c.id
                     WHERE ca.user_id = ? AND CURDATE() BETWEEN c.start_date AND c.end_date",
                    [$userId]
                );
                $stats['campaigns_active'] = (int) ($result['count'] ?? 0);
            }

            // Pour manager_reps et rep, utiliser StatsAccessHelper
            if (in_array($role, ['manager_reps', 'rep'])) {
                $accessibleCampaignIds = StatsAccessHelper::getAccessibleCampaignIds();

                if ($accessibleCampaignIds !== null && !empty($accessibleCampaignIds)) {
                    $stats['campaigns_assigned'] = count($accessibleCampaignIds);

                    // Campagnes actives
                    $placeholders = implode(',', array_fill(0, count($accessibleCampaignIds), '?'));
                    $result = $this->db->queryOne(
                        "SELECT COUNT(*) as count FROM campaigns
                         WHERE id IN ({$placeholders}) AND CURDATE() BETWEEN start_date AND end_date",
                        $accessibleCampaignIds
                    );
                    $stats['campaigns_active'] = (int) ($result['count'] ?? 0);
                }

                // Clients accessibles
                $accessibleCustomerIds = StatsAccessHelper::getAccessibleCustomerNumbersOnly();
                if ($accessibleCustomerIds !== null) {
                    $stats['customers_accessible'] = count($accessibleCustomerIds);
                }
            }

            // Pour admin/superadmin, toutes les campagnes
            if (in_array($role, ['superadmin', 'admin'])) {
                $result = $this->db->queryOne("SELECT COUNT(*) as count FROM campaigns");
                $stats['campaigns_assigned'] = (int) ($result['count'] ?? 0);

                $result = $this->db->queryOne(
                    "SELECT COUNT(*) as count FROM campaigns WHERE CURDATE() BETWEEN start_date AND end_date"
                );
                $stats['campaigns_active'] = (int) ($result['count'] ?? 0);

                $result = $this->db->queryOne("SELECT COUNT(*) as count FROM customers");
                $stats['customers_accessible'] = (int) ($result['count'] ?? 0);
            }

            // Commandes (filtrées selon le rôle)
            $accessibleCampaignIds = StatsAccessHelper::getAccessibleCampaignIds();

            if ($accessibleCampaignIds === null) {
                // Accès à tout
                $result = $this->db->queryOne("SELECT COUNT(*) as count FROM orders");
                $stats['orders_total'] = (int) ($result['count'] ?? 0);

                $result = $this->db->queryOne(
                    "SELECT COUNT(*) as count FROM orders WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())"
                );
                $stats['orders_this_month'] = (int) ($result['count'] ?? 0);

                $result = $this->db->queryOne("SELECT COUNT(*) as count FROM products WHERE is_active = 1");
                $stats['products_managed'] = (int) ($result['count'] ?? 0);
            } elseif (!empty($accessibleCampaignIds)) {
                $placeholders = implode(',', array_fill(0, count($accessibleCampaignIds), '?'));

                $result = $this->db->queryOne(
                    "SELECT COUNT(*) as count FROM orders WHERE campaign_id IN ({$placeholders})",
                    $accessibleCampaignIds
                );
                $stats['orders_total'] = (int) ($result['count'] ?? 0);

                $result = $this->db->queryOne(
                    "SELECT COUNT(*) as count FROM orders
                     WHERE campaign_id IN ({$placeholders})
                     AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())",
                    $accessibleCampaignIds
                );
                $stats['orders_this_month'] = (int) ($result['count'] ?? 0);

                $result = $this->db->queryOne(
                    "SELECT COUNT(*) as count FROM products WHERE campaign_id IN ({$placeholders}) AND is_active = 1",
                    $accessibleCampaignIds
                );
                $stats['products_managed'] = (int) ($result['count'] ?? 0);
            }

        } catch (\Exception $e) {
            error_log("Erreur getUserStats: " . $e->getMessage());
        }

        return $stats;
    }

    /**
     * Récupérer les campagnes assignées
     */
    private function getAssignedCampaigns(int $userId, string $role): array
    {
        try {
            if (in_array($role, ['superadmin', 'admin'])) {
                // Les 5 dernières campagnes actives
                return $this->db->query(
                    "SELECT id, name, country, start_date, end_date,
                            CASE
                                WHEN CURDATE() BETWEEN start_date AND end_date THEN 'active'
                                WHEN start_date > CURDATE() THEN 'upcoming'
                                ELSE 'ended'
                            END as status
                     FROM campaigns
                     WHERE is_active = 1
                     ORDER BY start_date DESC
                     LIMIT 5"
                );
            }

            if ($role === 'createur') {
                return $this->db->query(
                    "SELECT c.id, c.name, c.country, c.start_date, c.end_date, ca.role as assignment_role,
                            CASE
                                WHEN CURDATE() BETWEEN c.start_date AND c.end_date THEN 'active'
                                WHEN c.start_date > CURDATE() THEN 'upcoming'
                                ELSE 'ended'
                            END as status
                     FROM campaigns c
                     JOIN campaign_assignees ca ON c.id = ca.campaign_id
                     WHERE ca.user_id = ? AND c.is_active = 1
                     ORDER BY c.start_date DESC
                     LIMIT 5",
                    [$userId]
                );
            }

            if (in_array($role, ['manager_reps', 'rep'])) {
                $accessibleCampaignIds = StatsAccessHelper::getAccessibleCampaignIds();

                if ($accessibleCampaignIds !== null && !empty($accessibleCampaignIds)) {
                    $placeholders = implode(',', array_fill(0, count($accessibleCampaignIds), '?'));
                    return $this->db->query(
                        "SELECT id, name, country, start_date, end_date,
                                CASE
                                    WHEN CURDATE() BETWEEN start_date AND end_date THEN 'active'
                                    WHEN start_date > CURDATE() THEN 'upcoming'
                                    ELSE 'ended'
                                END as status
                         FROM campaigns
                         WHERE id IN ({$placeholders}) AND is_active = 1
                         ORDER BY start_date DESC
                         LIMIT 5",
                        $accessibleCampaignIds
                    );
                }
            }

            return [];
        } catch (\Exception $e) {
            error_log("Erreur getAssignedCampaigns: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Gérer l'upload d'avatar
     */
    private function handleAvatarUpload(array $file, int $userId): string
    {
        // Vérifier le type MIME
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            throw new \Exception('Format non autorisé. Utilisez JPG, PNG ou WEBP.');
        }

        // Vérifier la taille (2MB max)
        if ($file['size'] > 2 * 1024 * 1024) {
            throw new \Exception('L\'image ne doit pas dépasser 2MB.');
        }

        // Générer un nom unique
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'avatar_' . $userId . '_' . uniqid() . '.' . $extension;

        // Chemin de destination
        $uploadDir = __DIR__ . '/../../public/uploads/avatars/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filePath = $uploadDir . $filename;

        // Déplacer le fichier
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new \Exception('Erreur lors de l\'enregistrement du fichier.');
        }

        return '/stm/uploads/avatars/' . $filename;
    }

    /**
     * Supprimer un avatar du serveur
     */
    private function deleteAvatar(?string $avatarPath): bool
    {
        if (empty($avatarPath)) {
            return false;
        }

        // Convertir le chemin relatif en chemin absolu
        $fullPath = __DIR__ . '/../../public' . str_replace('/stm', '', $avatarPath);

        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }

        return false;
    }

    /**
     * Valider le token CSRF
     */
    private function validateCSRF(): bool
    {
        $token = $_POST['_token'] ?? '';
        $sessionToken = Session::get('csrf_token');
        return !empty($token) && !empty($sessionToken) && $token === $sessionToken;
    }

    /**
     * Réponse JSON
     */
    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
}