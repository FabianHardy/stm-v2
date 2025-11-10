<?php
/**
 * Nom du fichier : View.php
 * Description : Système de gestion des vues avec support des layouts et composants
 * Auteur : Fabian Hardy
 * Date : 05/11/2025
 * Modifié : 06/11/2025 - Ajout méthode statique make()
 */

namespace Core;

/**
 * Classe View
 * Gestion du rendu des vues avec layouts et données
 */
class View
{
    /**
     * Dossier racine des vues
     */
    private static string $viewsPath;
    
    /**
     * Layout par défaut
     */
    private static string $defaultLayout = 'layouts/app';
    
    /**
     * Données partagées entre toutes les vues
     */
    private static array $sharedData = [];
    
    /**
     * Initialise le chemin des vues
     */
    public static function init(): void
    {
        self::$viewsPath = dirname(__DIR__) . '/app/Views/';
    }
    
    /**
     * Définit le layout par défaut
     * 
     * @param string $layout Nom du layout
     */
    public static function setDefaultLayout(string $layout): void
    {
        self::$defaultLayout = $layout;
    }
    
    /**
     * Partage des données avec toutes les vues
     * 
     * @param array $data Données à partager
     */
    public static function share(array $data): void
    {
        self::$sharedData = array_merge(self::$sharedData, $data);
    }
    
    /**
     * Méthode statique pour rendre une vue avec layout
     * 
     * @param string $view Nom de la vue
     * @param array $data Données à passer à la vue
     * @param string|null $layout Layout à utiliser (null pour le défaut)
     * @return string Contenu rendu
     */
    public static function make(string $view, array $data = [], ?string $layout = null): string
    {
        // Initialiser si nécessaire
        if (!isset(self::$viewsPath)) {
            self::init();
        }
        
        // Fusionner avec les données partagées
        $data = array_merge(self::$sharedData, $data);
        
        // Extraire les données pour les rendre disponibles dans la vue
        extract($data);
        
        // Buffer de sortie pour capturer le contenu de la vue
        ob_start();
        
        // Construire le chemin complet de la vue
        $viewPath = self::$viewsPath . str_replace('.', '/', $view) . '.php';
        
        // Vérifier que la vue existe
        if (!file_exists($viewPath)) {
            throw new \RuntimeException("La vue {$view} est introuvable : {$viewPath}");
        }
        
        // Inclure la vue
        include $viewPath;
        
        // Récupérer le contenu de la vue
        $content = ob_get_clean();
        
        // Si un layout est spécifié (ou utiliser le layout par défaut)
        $layoutToUse = $layout ?? self::$defaultLayout;
        
        // Si pas de layout, retourner juste le contenu
        if ($layoutToUse === null || $layoutToUse === false) {
            return $content;
        }
        
        // Charger le layout avec le contenu
        ob_start();
        $layoutPath = self::$viewsPath . str_replace('.', '/', $layoutToUse) . '.php';
        
        if (!file_exists($layoutPath)) {
            // Si le layout n'existe pas, retourner juste le contenu
            return $content;
        }
        
        // Le contenu est disponible dans $content pour le layout
        include $layoutPath;
        
        return ob_get_clean();
    }
    
    /**
     * Rend une vue (méthode d'instance pour compatibilité)
     * 
     * @param string $view Nom de la vue
     * @param array $data Données à passer à la vue
     * @param string|null $layout Layout à utiliser
     * @return string Contenu HTML rendu
     */
    public function render(string $view, array $data = [], ?string $layout = null): string
    {
        return self::make($view, $data, $layout);
    }
    
    /**
     * Rend une vue partielle (sans layout)
     * 
     * @param string $view Nom de la vue
     * @param array $data Données à passer à la vue
     * @return string Contenu HTML rendu
     */
    public static function partial(string $view, array $data = []): string
    {
        return self::make($view, $data, null);
    }
    
    /**
     * Inclut un composant (alias de partial)
     * 
     * @param string $component Nom du composant
     * @param array $data Données à passer au composant
     * @return string Contenu HTML rendu
     */
    public static function component(string $component, array $data = []): string
    {
        return self::partial("components/{$component}", $data);
    }
    
    /**
     * Vérifie si une vue existe
     * 
     * @param string $view Nom de la vue
     * @return bool True si la vue existe
     */
    public static function exists(string $view): bool
    {
        if (!isset(self::$viewsPath)) {
            self::init();
        }
        
        $viewPath = self::$viewsPath . str_replace('.', '/', $view) . '.php';
        return file_exists($viewPath);
    }
    
    /**
     * Échappe une chaîne HTML
     * 
     * @param string $string Chaîne à échapper
     * @return string Chaîne échappée
     */
    public static function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Alias court pour escape
     * 
     * @param string $string Chaîne à échapper
     * @return string Chaîne échappée
     */
    public static function e(string $string): string
    {
        return self::escape($string);
    }
    
    /**
     * Génère une URL asset
     * 
     * @param string $path Chemin de l'asset
     * @return string URL complète
     */
    public static function asset(string $path): string
    {
        $baseUrl = rtrim($_ENV['APP_URL'] ?? '', '/');
        return $baseUrl . '/' . ltrim($path, '/');
    }
    
    /**
     * Génère une URL route
     * 
     * @param string $name Nom de la route
     * @param array $params Paramètres de la route
     * @return string URL générée
     */
    public static function route(string $name, array $params = []): string
    {
        // TODO: Implémenter la génération d'URL basée sur les routes nommées
        // Pour l'instant, retourner un chemin simple
        $url = '/' . ltrim($name, '/');
        
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $url = str_replace("{{$key}}", $value, $url);
            }
        }
        
        return $url;
    }
    
    /**
     * Obtient l'URL actuelle
     * 
     * @return string URL actuelle
     */
    public static function currentUrl(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }
    
    /**
     * Vérifie si l'URL actuelle correspond à un pattern
     * 
     * @param string $pattern Pattern à vérifier
     * @return bool True si correspond
     */
    public static function isActive(string $pattern): bool
    {
        $currentUrl = self::currentUrl();
        return strpos($currentUrl, $pattern) === 0;
    }
    
    /**
     * Formatte un nombre avec séparateurs
     * 
     * @param float $number Nombre à formatter
     * @param int $decimals Nombre de décimales
     * @return string Nombre formatté
     */
    public static function number(float $number, int $decimals = 0): string
    {
        return number_format($number, $decimals, ',', ' ');
    }
    
    /**
     * Formatte une date
     * 
     * @param string $date Date à formatter
     * @param string $format Format de sortie
     * @return string Date formatée
     */
    public static function date(string $date, string $format = 'd/m/Y'): string
    {
        return date($format, strtotime($date));
    }
    
    /**
     * Formatte une date et heure
     * 
     * @param string $datetime DateTime à formatter
     * @param string $format Format de sortie
     * @return string DateTime formatée
     */
    public static function datetime(string $datetime, string $format = 'd/m/Y H:i'): string
    {
        return date($format, strtotime($datetime));
    }
    
    /**
     * Tronque un texte
     * 
     * @param string $text Texte à tronquer
     * @param int $length Longueur max
     * @param string $suffix Suffixe (ex: "...")
     * @return string Texte tronqué
     */
    public static function truncate(string $text, int $length = 100, string $suffix = '...'): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        
        return mb_substr($text, 0, $length) . $suffix;
    }
    
    /**
     * Pluralise un mot
     * 
     * @param int $count Nombre
     * @param string $singular Forme singulière
     * @param string|null $plural Forme plurielle (null = ajouter 's')
     * @return string Mot au pluriel si nécessaire
     */
    public static function pluralize(int $count, string $singular, ?string $plural = null): string
    {
        if ($count <= 1) {
            return $singular;
        }
        
        return $plural ?? $singular . 's';
    }
    
    /**
     * Affiche un message flash s'il existe
     * 
     * @param string $type Type de message (success, error, warning, info)
     * @return string HTML du message
     */
    public static function flash(string $type): string
    {
        if (!isset($_SESSION['flash'][$type])) {
            return '';
        }
        
        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        
        // Classes Tailwind selon le type
        $classes = [
            'success' => 'bg-green-100 border-green-400 text-green-700',
            'error' => 'bg-red-100 border-red-400 text-red-700',
            'warning' => 'bg-yellow-100 border-yellow-400 text-yellow-700',
            'info' => 'bg-blue-100 border-blue-400 text-blue-700',
        ];
        
        $class = $classes[$type] ?? $classes['info'];
        
        return <<<HTML
        <div class="border-l-4 p-4 mb-4 {$class}" role="alert">
            <p>{$message}</p>
        </div>
        HTML;
    }
    
    /**
     * Affiche tous les messages flash
     * 
     * @return string HTML de tous les messages
     */
    public static function flashAll(): string
    {
        if (!isset($_SESSION['flash']) || empty($_SESSION['flash'])) {
            return '';
        }
        
        $html = '';
        foreach ($_SESSION['flash'] as $type => $message) {
            $html .= self::flash($type);
        }
        
        return $html;
    }
}

// Initialisation automatique
View::init();