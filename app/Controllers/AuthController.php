<?php
/**
 * Auth Controller
 * 
 * Gère l'authentification des utilisateurs (login/logout).
 * 
 * @package STM
 * @version 2.0
 */

namespace App\Controllers;

use Core\Auth;
use Core\Session;
use Core\Validator;

class AuthController
{
    /**
     * Affiche le formulaire de connexion
     * 
     * @return void
     */
    public function showLoginForm(): void
    {
        // Si déjà connecté, rediriger vers le dashboard
        if (Auth::check()) {
            header('Location: /stm/admin/dashboard');
            exit;
        }
        
        // Afficher la vue de login
        require_once __DIR__ . '/../Views/admin/login.php';
    }
    
    /**
     * Traite la tentative de connexion
     * 
     * @return void
     */
    public function login(): void
    {
        // Vérifier le token CSRF
        $csrfToken = $_POST['csrf_token'] ?? '';
        
        if (!Session::validateCsrfToken($csrfToken)) {
            Session::flash('error', 'Token de sécurité invalide. Veuillez réessayer.');
            header('Location: /stm/admin/login');
            exit;
        }
        
        // Récupérer les données du formulaire
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        // Valider les données
        $validator = new Validator([
            'username' => $username,
            'password' => $password,
        ]);
        
        $validator->required('username', 'Le nom d\'utilisateur est requis');
        $validator->required('password', 'Le mot de passe est requis');
        $validator->min('username', 3, 'Le nom d\'utilisateur doit contenir au moins 3 caractères');
        
        // Si erreurs de validation
        if ($validator->fails()) {
            Session::set('errors', $validator->errors());
            Session::set('old', ['username' => $username]);
            header('Location: /stm/admin/login');
            exit;
        }
        
        // Tentative de connexion
        try {
            $result = Auth::attempt($username, $password, $remember);
            
            // Vérifier le format du résultat
            if (!is_array($result)) {
                // Si Auth::attempt() retourne autre chose qu'un tableau
                Session::flash('error', 'Erreur lors de la connexion. Veuillez réessayer.');
                Session::set('old', ['username' => $username]);
                header('Location: /stm/admin/login');
                exit;
            }
            
            // Vérifier que les clés existent
            $success = $result['success'] ?? false;
            $message = $result['message'] ?? '';
            
            if ($success) {
                // Connexion réussie
                Session::flash('success', $message ?: 'Connexion réussie !');
                header('Location: /stm/admin/dashboard');
                exit;
            } else {
                // Échec de connexion
                Session::flash('error', $message ?: 'Identifiants incorrects.');
                Session::set('old', ['username' => $username]);
                header('Location: /stm/admin/login');
                exit;
            }
        } catch (\Exception $e) {
            // Gérer les erreurs inattendues
            error_log('Erreur Auth::attempt() : ' . $e->getMessage());
            Session::flash('error', 'Une erreur est survenue lors de la connexion.');
            Session::set('old', ['username' => $username]);
            header('Location: /stm/admin/login');
            exit;
        }
    }
    
    /**
     * Déconnecte l'utilisateur
     * 
     * @return void
     */
    public function logout(): void
    {
        Auth::logout();
        Session::flash('success', 'Vous avez été déconnecté avec succès.');
        header('Location: /stm/admin/login');
        exit;
    }
}
