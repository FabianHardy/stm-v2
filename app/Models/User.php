<?php
/**
 * Model User - STM v2
 * 
 * Gestion des utilisateurs et de leurs permissions
 * 
 * @package STM
 * @created 2025/12/10
 */

namespace App\Models;

use Core\Database;

class User
{
    private Database $db;
    
    /**
     * Labels des rôles en français
     */
    public const ROLE_LABELS = [
        'superadmin' => 'Super Admin',
        'admin' => 'Administrateur',
        'createur' => 'Créateur',
        'manager_reps' => 'Manager Reps',
        'rep' => 'Commercial'
    ];
    
    /**
     * Couleurs des badges par rôle
     */
    public const ROLE_COLORS = [
        'superadmin' => 'bg-red-100 text-red-800',
        'admin' => 'bg-purple-100 text-purple-800',
        'createur' => 'bg-blue-100 text-blue-800',
        'manager_reps' => 'bg-orange-100 text-orange-800',
        'rep' => 'bg-green-100 text-green-800'
    ];
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Récupérer tous les utilisateurs avec pagination
     * 
     * @param int $page Numéro de page
     * @param int $perPage Éléments par page
     * @param array $filters Filtres (role, status, search)
     * @return array
     */
    public function getAll(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $whereClause = "WHERE 1=1";
        
        // Filtre par rôle
        if (!empty($filters['role'])) {
            $whereClause .= " AND role = :role";
            $params[':role'] = $filters['role'];
        }
        
        // Filtre par statut
        if (isset($filters['status']) && $filters['status'] !== '') {
            $whereClause .= " AND is_active = :status";
            $params[':status'] = (int) $filters['status'];
        }
        
        // Recherche par nom ou email
        if (!empty($filters['search'])) {
            $whereClause .= " AND (name LIKE :search OR email LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        // Requête principale
        $query = "
            SELECT * FROM users 
            {$whereClause}
            ORDER BY 
                FIELD(role, 'superadmin', 'admin', 'createur', 'manager_reps', 'rep'),
                name ASC
            LIMIT {$perPage} OFFSET {$offset}
        ";
        
        $users = $this->db->query($query, $params);
        
        // Compter le total pour la pagination
        $countQuery = "SELECT COUNT(*) as total FROM users {$whereClause}";
        $countResult = $this->db->query($countQuery, $params);
        $total = (int) ($countResult[0]['total'] ?? 0);
        
        return [
            'data' => $users,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * Récupérer un utilisateur par ID
     * 
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $result = $this->db->query(
            "SELECT * FROM users WHERE id = :id",
            [':id' => $id]
        );
        
        return $result[0] ?? null;
    }
    
    /**
     * Récupérer un utilisateur par email
     * 
     * @param string $email
     * @return array|null
     */
    public function findByEmail(string $email): ?array
    {
        $result = $this->db->query(
            "SELECT * FROM users WHERE email = :email",
            [':email' => $email]
        );
        
        return $result[0] ?? null;
    }
    
    /**
     * Récupérer un utilisateur par Microsoft ID
     * 
     * @param string $microsoftId
     * @return array|null
     */
    public function findByMicrosoftId(string $microsoftId): ?array
    {
        $result = $this->db->query(
            "SELECT * FROM users WHERE microsoft_id = :microsoft_id",
            [':microsoft_id' => $microsoftId]
        );
        
        return $result[0] ?? null;
    }
    
    /**
     * Créer un utilisateur
     * 
     * @param array $data
     * @return int|false ID de l'utilisateur créé ou false
     */
    public function create(array $data): int|false
    {
        $query = "
            INSERT INTO users (microsoft_id, email, name, role, rep_id, rep_country, is_active, created_by)
            VALUES (:microsoft_id, :email, :name, :role, :rep_id, :rep_country, :is_active, :created_by)
        ";
        
        $params = [
            ':microsoft_id' => $data['microsoft_id'] ?? null,
            ':email' => $data['email'],
            ':name' => $data['name'],
            ':role' => $data['role'] ?? 'rep',
            ':rep_id' => $data['rep_id'] ?? null,
            ':rep_country' => $data['rep_country'] ?? null,
            ':is_active' => $data['is_active'] ?? 1,
            ':created_by' => $data['created_by'] ?? null
        ];
        
        $this->db->query($query, $params);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Mettre à jour un utilisateur
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];
        
        $allowedFields = ['name', 'role', 'rep_id', 'rep_country', 'is_active'];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $query = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        
        return $this->db->query($query, $params) !== false;
    }
    
    /**
     * Supprimer un utilisateur
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->db->query(
            "DELETE FROM users WHERE id = :id",
            [':id' => $id]
        ) !== false;
    }
    
    /**
     * Activer/Désactiver un utilisateur
     * 
     * @param int $id
     * @param bool $active
     * @return bool
     */
    public function setActive(int $id, bool $active): bool
    {
        return $this->db->query(
            "UPDATE users SET is_active = :active WHERE id = :id",
            [':id' => $id, ':active' => $active ? 1 : 0]
        ) !== false;
    }
    
    /**
     * Mettre à jour la date de dernière connexion
     * 
     * @param int $id
     * @return bool
     */
    public function updateLastLogin(int $id): bool
    {
        return $this->db->query(
            "UPDATE users SET last_login_at = NOW() WHERE id = :id",
            [':id' => $id]
        ) !== false;
    }
    
    /**
     * Récupérer les permissions d'un utilisateur (via son rôle)
     * 
     * @param int $userId
     * @return array
     */
    public function getPermissions(int $userId): array
    {
        $user = $this->findById($userId);
        
        if (!$user) {
            return [];
        }
        
        $result = $this->db->query(
            "SELECT p.code 
             FROM role_permissions rp
             INNER JOIN permissions p ON rp.permission_id = p.id
             WHERE rp.role = :role",
            [':role' => $user['role']]
        );
        
        return array_column($result, 'code');
    }
    
    /**
     * Vérifier si un utilisateur a une permission
     * 
     * @param int $userId
     * @param string $permissionCode
     * @return bool
     */
    public function hasPermission(int $userId, string $permissionCode): bool
    {
        $permissions = $this->getPermissions($userId);
        return in_array($permissionCode, $permissions);
    }
    
    /**
     * Statistiques des utilisateurs
     * 
     * @return array
     */
    public function getStats(): array
    {
        $result = $this->db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive,
                SUM(CASE WHEN role = 'superadmin' THEN 1 ELSE 0 END) as superadmins,
                SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins,
                SUM(CASE WHEN role = 'createur' THEN 1 ELSE 0 END) as createurs,
                SUM(CASE WHEN role = 'manager_reps' THEN 1 ELSE 0 END) as managers,
                SUM(CASE WHEN role = 'rep' THEN 1 ELSE 0 END) as reps
            FROM users
        ");
        
        return $result[0] ?? [];
    }
    
    /**
     * Récupérer le label d'un rôle
     * 
     * @param string $role
     * @return string
     */
    public static function getRoleLabel(string $role): string
    {
        return self::ROLE_LABELS[$role] ?? ucfirst($role);
    }
    
    /**
     * Récupérer la couleur d'un rôle
     * 
     * @param string $role
     * @return string
     */
    public static function getRoleColor(string $role): string
    {
        return self::ROLE_COLORS[$role] ?? 'bg-gray-100 text-gray-800';
    }
}
