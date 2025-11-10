<?php
/**
 * Fichier : Response.php
 * Description : Classe de gestion des réponses HTTP
 *              Gère l'envoi de réponses HTML, JSON, redirections, etc.
 * 
 * Auteur : Fabian Hardy
 * Date : 04/11/2025
 * Version : 1.0
 */

declare(strict_types=1);

namespace Core;

/**
 * Classe Response
 * 
 * Gère l'envoi des réponses HTTP avec support de différents
 * formats (HTML, JSON, XML) et codes de statut
 */
class Response
{
    /**
     * Code de statut HTTP
     */
    private int $statusCode = 200;

    /**
     * Headers HTTP à envoyer
     */
    private array $headers = [];

    /**
     * Contenu de la réponse
     */
    private string $content = '';

    /**
     * Codes de statut HTTP courants
     */
    private const STATUS_CODES = [
        200 => 'OK',
        201 => 'Created',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        304 => 'Not Modified',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        422 => 'Unprocessable Entity',
        500 => 'Internal Server Error',
        503 => 'Service Unavailable'
    ];

    /**
     * Définir le code de statut HTTP
     * 
     * @param int $code Code de statut
     * @return self Pour le chaînage
     */
    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Obtenir le code de statut
     * 
     * @return int Code de statut
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Ajouter un header HTTP
     * 
     * @param string $name Nom du header
     * @param string $value Valeur du header
     * @return self Pour le chaînage
     */
    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Ajouter plusieurs headers
     * 
     * @param array $headers Tableau de headers
     * @return self Pour le chaînage
     */
    public function withHeaders(array $headers): self
    {
        foreach ($headers as $name => $value) {
            $this->header($name, $value);
        }
        return $this;
    }

    /**
     * Définir le contenu de la réponse
     * 
     * @param string $content Contenu
     * @return self Pour le chaînage
     */
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Envoyer une réponse HTML
     * 
     * @param string $html Contenu HTML
     * @param int $statusCode Code de statut
     * @return void
     */
    public function html(string $html, int $statusCode = 200): void
    {
        $this->setStatusCode($statusCode)
             ->header('Content-Type', 'text/html; charset=UTF-8')
             ->setContent($html)
             ->send();
    }

    /**
     * Envoyer une réponse JSON
     * 
     * @param mixed $data Données à encoder en JSON
     * @param int $statusCode Code de statut
     * @return void
     */
    public function json(mixed $data, int $statusCode = 200): void
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        $this->setStatusCode($statusCode)
             ->header('Content-Type', 'application/json; charset=UTF-8')
             ->setContent($json)
             ->send();
    }

    /**
     * Envoyer une réponse de succès JSON
     * 
     * @param mixed $data Données
     * @param string $message Message de succès
     * @param int $statusCode Code de statut
     * @return void
     */
    public function success(mixed $data = null, string $message = 'Succès', int $statusCode = 200): void
    {
        $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Envoyer une réponse d'erreur JSON
     * 
     * @param string $message Message d'erreur
     * @param array $errors Détails des erreurs
     * @param int $statusCode Code de statut
     * @return void
     */
    public function error(string $message, array $errors = [], int $statusCode = 400): void
    {
        $this->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $statusCode);
    }

    /**
     * Rediriger vers une URL
     * 
     * @param string $url URL de destination
     * @param int $statusCode Code de statut (301 ou 302)
     * @return void
     */
    public function redirect(string $url, int $statusCode = 302): void
    {
        $this->setStatusCode($statusCode)
             ->header('Location', $url)
             ->send();
        exit;
    }

    /**
     * Rediriger en arrière (referer)
     * 
     * @param string $default URL par défaut si pas de referer
     * @return void
     */
    public function back(string $default = '/'): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? $default;
        $this->redirect($referer);
    }

    /**
     * Rediriger avec des données en session (flash)
     * 
     * @param string $url URL de destination
     * @param array $flash Données flash
     * @return void
     */
    public function redirectWithFlash(string $url, array $flash): void
    {
        $_SESSION['flash'] = array_merge($_SESSION['flash'] ?? [], $flash);
        $this->redirect($url);
    }

    /**
     * Télécharger un fichier
     * 
     * @param string $filePath Chemin du fichier
     * @param string|null $filename Nom du fichier pour le téléchargement
     * @param array $headers Headers additionnels
     * @return void
     */
    public function download(string $filePath, ?string $filename = null, array $headers = []): void
    {
        if (!file_exists($filePath)) {
            $this->setStatusCode(404)
                 ->setContent('Fichier non trouvé')
                 ->send();
            return;
        }

        $filename = $filename ?? basename($filePath);
        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

        $this->withHeaders(array_merge([
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => filesize($filePath),
            'Cache-Control' => 'must-revalidate',
            'Pragma' => 'public'
        ], $headers));

        $this->sendHeaders();
        readfile($filePath);
        exit;
    }

    /**
     * Afficher un fichier en ligne (sans télécharger)
     * 
     * @param string $filePath Chemin du fichier
     * @param string|null $filename Nom du fichier
     * @return void
     */
    public function file(string $filePath, ?string $filename = null): void
    {
        if (!file_exists($filePath)) {
            $this->setStatusCode(404)
                 ->setContent('Fichier non trouvé')
                 ->send();
            return;
        }

        $filename = $filename ?? basename($filePath);
        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

        $this->withHeaders([
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Content-Length' => filesize($filePath)
        ]);

        $this->sendHeaders();
        readfile($filePath);
        exit;
    }

    /**
     * Envoyer une réponse vide
     * 
     * @param int $statusCode Code de statut
     * @return void
     */
    public function noContent(int $statusCode = 204): void
    {
        $this->setStatusCode($statusCode)->send();
    }

    /**
     * Envoyer une erreur 404
     * 
     * @param string $message Message d'erreur
     * @return void
     */
    public function notFound(string $message = 'Page non trouvée'): void
    {
        if ($this->isAjaxRequest()) {
            $this->json([
                'success' => false,
                'message' => $message
            ], 404);
        } else {
            $this->setStatusCode(404)
                 ->setContent($this->render404Page($message))
                 ->send();
        }
    }

    /**
     * Envoyer une erreur 403
     * 
     * @param string $message Message d'erreur
     * @return void
     */
    public function forbidden(string $message = 'Accès interdit'): void
    {
        if ($this->isAjaxRequest()) {
            $this->json([
                'success' => false,
                'message' => $message
            ], 403);
        } else {
            $this->setStatusCode(403)
                 ->setContent($this->render403Page($message))
                 ->send();
        }
    }

    /**
     * Envoyer une erreur 401
     * 
     * @param string $message Message d'erreur
     * @return void
     */
    public function unauthorized(string $message = 'Non autorisé'): void
    {
        if ($this->isAjaxRequest()) {
            $this->json([
                'success' => false,
                'message' => $message
            ], 401);
        } else {
            $this->redirect('/login');
        }
    }

    /**
     * Envoyer la réponse
     * 
     * @return void
     */
    public function send(): void
    {
        // Envoyer le code de statut
        http_response_code($this->statusCode);

        // Envoyer les headers
        $this->sendHeaders();

        // Envoyer le contenu
        echo $this->content;
    }

    /**
     * Envoyer les headers HTTP
     * 
     * @return void
     */
    private function sendHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
    }

    /**
     * Vérifier si c'est une requête AJAX
     * 
     * @return bool True si AJAX
     */
    private function isAjaxRequest(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Rendre une page 404
     * 
     * @param string $message Message d'erreur
     * @return string HTML de la page 404
     */
    private function render404Page(string $message): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page non trouvée</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        h1 { font-size: 48px; color: #e74c3c; }
        p { font-size: 18px; color: #555; }
        a { color: #3498db; text-decoration: none; }
    </style>
</head>
<body>
    <h1>404</h1>
    <p>{$message}</p>
    <a href="/">Retour à l'accueil</a>
</body>
</html>
HTML;
    }

    /**
     * Rendre une page 403
     * 
     * @param string $message Message d'erreur
     * @return string HTML de la page 403
     */
    private function render403Page(string $message): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Accès interdit</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        h1 { font-size: 48px; color: #e74c3c; }
        p { font-size: 18px; color: #555; }
        a { color: #3498db; text-decoration: none; }
    </style>
</head>
<body>
    <h1>403</h1>
    <p>{$message}</p>
    <a href="/">Retour à l'accueil</a>
</body>
</html>
HTML;
    }
}