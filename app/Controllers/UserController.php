<?php
/**
 * Controller : UserController
 *
 * Gestion des utilisateurs (CRUD)
 * Accessible uniquement aux superadmin
 *
 * @package STM
 * @created 2025/12/10
 * @modified 2025/12/16 - Ajout fonctionnalité "Se connecter en tant que"
 */

namespace App\Controllers;

use App\Models\User;
use Core\Session;

class UserController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Liste des utilisateurs
     * GET /admin/users
     */
    public function index(): void
    {
        // Récupérer les filtres
        $filters = [
            'role' => $_GET['role'] ?? '',
            'status' => $_GET['status'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];

        $page = max(1, (int) ($_GET['page'] ?? 1));

        // Récupérer les utilisateurs
        $result = $this->userModel->getAll($page, 20, $filters);

        // Statistiques
        $stats = $this->userModel->getStats();

        // Liste des rôles pour le filtre
        $roles = User::ROLE_LABELS;

        // Variables pour la vue
        $users = $result['data'];
        $pagination = [
            'current' => $result['page'],
            'total' => $result['totalPages'],
            'count' => $result['total']
        ];

        $title = 'Gestion des utilisateurs';

        require __DIR__ . '/../Views/admin/users/index.php';
    }

    /**
     * Formulaire de création
     * GET /admin/users/create
     */
    public function create(): void
    {
        $title = 'Nouvel utilisateur';

        require __DIR__ . '/../Views/admin/users/create.php';
    }

    /**
     * Enregistrer un nouvel utilisateur
     * POST /admin/users
     */
    public function store(): void
    {
        // Validation CSRF
        if (!$this->validateCsrf()) {
            Session::setFlash('error', 'Token CSRF invalide');
            header('Location: /stm/admin/users/create');
            exit();
        }

        // Validation des données
        $errors = $this->validateUserData($_POST);

        if (!empty($errors)) {
            Session::setFlash('error', implode('<br>', $errors));
            Session::set('old_input', $_POST);
            header('Location: /stm/admin/users/create');
            exit();
        }

        // Vérifier si l'email existe déjà
        if ($this->userModel->findByEmail($_POST['email'])) {
            Session::setFlash('error', 'Cet email est déjà utilisé');
            Session::set('old_input', $_POST);
            header('Location: /stm/admin/users/create');
            exit();
        }

        // Créer l'utilisateur (sans rep_id/rep_country - sera rempli à la connexion Microsoft)
        $data = [
            'email' => trim($_POST['email']),
            'name' => trim($_POST['name']),
            'role' => $_POST['role'],
            'rep_id' => null,
            'rep_country' => null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'created_by' => Session::get('user')['id'] ?? null
        ];

        $userId = $this->userModel->create($data);

        if ($userId) {
            Session::setFlash('success', 'Utilisateur créé avec succès');
            header('Location: /stm/admin/users');
        } else {
            Session::setFlash('error', 'Erreur lors de la création');
            Session::set('old_input', $_POST);
            header('Location: /stm/admin/users/create');
        }

        exit();
    }

    /**
     * Formulaire de modification
     * GET /admin/users/{id}/edit
     */
    public function edit(int $id): void
    {
        $user = $this->userModel->findById($id);

        if (!$user) {
            Session::setFlash('error', 'Utilisateur introuvable');
            header('Location: /stm/admin/users');
            exit();
        }

        $title = 'Modifier ' . $user['name'];

        require __DIR__ . '/../Views/admin/users/edit.php';
    }

    /**
     * Mettre à jour un utilisateur
     * POST /admin/users/{id}
     */
    public function update(int $id): void
    {
        // Validation CSRF
        if (!$this->validateCsrf()) {
            Session::setFlash('error', 'Token CSRF invalide');
            header("Location: /stm/admin/users/{$id}/edit");
            exit();
        }

        $user = $this->userModel->findById($id);

        if (!$user) {
            Session::setFlash('error', 'Utilisateur introuvable');
            header('Location: /stm/admin/users');
            exit();
        }

        // Protection : un superadmin ne peut pas changer son propre rôle
        if ($user['role'] === 'superadmin') {
            $_POST['role'] = 'superadmin';
            $_POST['is_active'] = 1;
        }

        // Validation
        $errors = $this->validateUserData($_POST, $id);

        if (!empty($errors)) {
            Session::setFlash('error', implode('<br>', $errors));
            header("Location: /stm/admin/users/{$id}/edit");
            exit();
        }

        // Mise à jour (on ne touche pas à rep_id/rep_country)
        $data = [
            'name' => trim($_POST['name']),
            'role' => $_POST['role'],
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];

        if ($this->userModel->update($id, $data)) {
            Session::setFlash('success', 'Utilisateur mis à jour');
        } else {
            Session::setFlash('error', 'Erreur lors de la mise à jour');
        }

        header('Location: /stm/admin/users');
        exit();
    }

    /**
     * Supprimer un utilisateur
     * DELETE /admin/users/{id}
     */
    public function destroy(int $id): void
    {
        // Validation CSRF
        if (!$this->validateCsrf()) {
            Session::setFlash('error', 'Token CSRF invalide');
            header('Location: /stm/admin/users');
            exit();
        }

        $user = $this->userModel->findById($id);

        if (!$user) {
            Session::setFlash('error', 'Utilisateur introuvable');
            header('Location: /stm/admin/users');
            exit();
        }

        // Protection : impossible de supprimer un superadmin
        if ($user['role'] === 'superadmin') {
            Session::setFlash('error', 'Impossible de supprimer un compte Super Admin');
            header('Location: /stm/admin/users');
            exit();
        }

        // Empêcher la suppression de son propre compte
        $currentUser = Session::get('user');
        if ($currentUser && $currentUser['id'] == $id) {
            Session::setFlash('error', 'Vous ne pouvez pas supprimer votre propre compte');
            header('Location: /stm/admin/users');
            exit();
        }

        if ($this->userModel->delete($id)) {
            Session::setFlash('success', 'Utilisateur supprimé');
        } else {
            Session::setFlash('error', 'Erreur lors de la suppression');
        }

        header('Location: /stm/admin/users');
        exit();
    }

    /**
     * Activer/Désactiver un utilisateur (AJAX)
     * POST /admin/users/{id}/toggle
     */
    public function toggle(int $id): void
    {
        header('Content-Type: application/json');

        $user = $this->userModel->findById($id);

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable']);
            exit();
        }

        // Protection : impossible de désactiver un superadmin
        if ($user['role'] === 'superadmin') {
            echo json_encode(['success' => false, 'message' => 'Impossible de désactiver un compte Super Admin']);
            exit();
        }

        // Empêcher la désactivation de son propre compte
        $currentUser = Session::get('user');
        if ($currentUser && $currentUser['id'] == $id) {
            echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas désactiver votre propre compte']);
            exit();
        }

        $newStatus = !$user['is_active'];

        if ($this->userModel->setActive($id, $newStatus)) {
            echo json_encode([
                'success' => true,
                'active' => $newStatus,
                'message' => $newStatus ? 'Utilisateur activé' : 'Utilisateur désactivé'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
        }

        exit();
    }

    /**
     * Se connecter en tant qu'un autre utilisateur
     * GET /admin/users/{id}/impersonate
     * 
     * Permet au superadmin de voir l'interface comme un autre utilisateur
     * sans modifier son mot de passe.
     */
    public function impersonate(int $id): void
    {
        $currentUser = Session::get('user');

        // Vérifier que l'utilisateur actuel est superadmin
        if (!$currentUser || $currentUser['role'] !== 'superadmin') {
            Session::setFlash('error', 'Accès non autorisé');
            header('Location: /stm/admin/users');
            exit();
        }

        // Vérifier qu'on n'est pas déjà en mode impersonate
        if (Session::get('impersonate_original_user')) {
            Session::setFlash('error', 'Vous êtes déjà en mode "Se connecter en tant que". Revenez d\'abord à votre compte.');
            header('Location: /stm/admin/users');
            exit();
        }

        $targetUser = $this->userModel->findById($id);

        if (!$targetUser) {
            Session::setFlash('error', 'Utilisateur introuvable');
            header('Location: /stm/admin/users');
            exit();
        }

        // Protection : impossible d'impersonate un superadmin
        if ($targetUser['role'] === 'superadmin') {
            Session::setFlash('error', 'Impossible de se connecter en tant qu\'un Super Admin');
            header('Location: /stm/admin/users');
            exit();
        }

        // Vérifier que l'utilisateur cible est actif
        if (!$targetUser['is_active']) {
            Session::setFlash('error', 'Impossible de se connecter en tant qu\'un utilisateur inactif');
            header('Location: /stm/admin/users');
            exit();
        }

        // Sauvegarder l'utilisateur original
        Session::set('impersonate_original_user', $currentUser);

        // Créer la nouvelle session avec l'utilisateur cible
        $impersonatedUser = [
            'id' => $targetUser['id'],
            'username' => $targetUser['name'],
            'email' => $targetUser['email'],
            'role' => $targetUser['role'],
            'rep_id' => $targetUser['rep_id'] ?? null,
            'rep_country' => $targetUser['rep_country'] ?? null,
            'manager_id' => $targetUser['manager_id'] ?? null
        ];

        Session::set('user', $impersonatedUser);

        Session::setFlash('success', 'Vous êtes maintenant connecté en tant que ' . $targetUser['name']);
        header('Location: /stm/admin/dashboard');
        exit();
    }

    /**
     * Revenir à son compte original
     * GET /admin/impersonate/stop
     */
    public function stopImpersonate(): void
    {
        $originalUser = Session::get('impersonate_original_user');

        if (!$originalUser) {
            Session::setFlash('error', 'Vous n\'êtes pas en mode "Se connecter en tant que"');
            header('Location: /stm/admin/dashboard');
            exit();
        }

        // Restaurer la session originale
        Session::set('user', $originalUser);
        Session::delete('impersonate_original_user');

        Session::setFlash('success', 'Vous êtes revenu à votre compte ' . $originalUser['username']);
        header('Location: /stm/admin/users');
        exit();
    }

    /**
     * Valider les données utilisateur
     *
     * @param array $data
     * @param int|null $excludeId ID à exclure (pour update)
     * @return array Erreurs
     */
    private function validateUserData(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        // Email requis et valide (seulement pour création)
        if ($excludeId === null) {
            if (empty($data['email'])) {
                $errors[] = 'L\'email est requis';
            } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'L\'email n\'est pas valide';
            }
        }

        // Nom requis
        if (empty($data['name'])) {
            $errors[] = 'Le nom est requis';
        }

        // Rôle requis et valide
        $validRoles = array_keys(User::ROLE_LABELS);
        if (empty($data['role']) || !in_array($data['role'], $validRoles)) {
            $errors[] = 'Le rôle sélectionné n\'est pas valide';
        }

        return $errors;
    }

    /**
     * Valider le token CSRF
     *
     * @return bool
     */
    private function validateCsrf(): bool
    {
        $token = $_POST['_token'] ?? '';
        return $token === Session::get('csrf_token');
    }
}
