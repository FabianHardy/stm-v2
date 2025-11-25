<?php
/**
 * DevToolsController - Outils de développement
 *
 * Gère les outils disponibles uniquement en mode développement :
 * - Synchronisation base de données prod → dev
 * - Synchronisation des fichiers uploadés
 *
 * @package Controllers\Admin
 * @created 2025/11/25 12:00
 * @modified 2025/11/25 13:30 - Correction Session::flash()
 */

namespace App\Controllers\Admin;

use Core\Database;
use Core\DatabaseSync;
use Core\FileSync;
use Core\Session;
use Exception;

class DevToolsController
{
    /**
     * Configuration de la base de production
     * @var array
     */
    private array $prodDbConfig;

    /**
     * Configuration de la base de développement
     * @var array
     */
    private array $devDbConfig;

    /**
     * Constructeur
     */
    public function __construct()
    {
        // Vérifier qu'on est en mode développement
        $this->checkDevMode();

        // Configuration des bases de données
        $this->prodDbConfig = [
            "host" => "localhost",
            "port" => "3306",
            "database" => "trendyblog_stm_v2", // Base de production
            "username" => $this->env("DB_USER"),
            "password" => $this->env("DB_PASS"),
            "charset" => "utf8mb4",
        ];

        $this->devDbConfig = [
            "host" => "localhost",
            "port" => "3306",
            "database" => "trendyblog_stm_dev", // Base de développement
            "username" => $this->env("DB_USER"),
            "password" => $this->env("DB_PASS"),
            "charset" => "utf8mb4",
        ];
    }

    /**
     * Récupère une variable d'environnement
     *
     * @param string $key
     * @param string $default
     * @return string
     */
    private function env(string $key, string $default = ""): string
    {
        $value = getenv($key);
        if ($value !== false && $value !== "") {
            return $value;
        }

        return $_ENV[$key] ?? $default;
    }

    /**
     * Vérifie que l'application est en mode développement
     *
     * @throws Exception Si pas en mode dev
     */
    private function checkDevMode(): void
    {
        $appEnv = $this->env("APP_ENV", "production");

        if ($appEnv !== "development") {
            Session::flash("error", "Cette fonctionnalité est disponible uniquement en mode développement.");
            header("Location: /stm/admin/dashboard");
            exit();
        }
    }

    /**
     * Affiche la page de synchronisation de base de données
     */
    public function syncDatabase(): void
    {
        $data = [
            "title" => "Synchronisation Base de Données",
            "error" => null,
            "structureReport" => null,
            "tablesStats" => null,
            "excludedTables" => [],
            "optionalTables" => [],
        ];

        try {
            $sync = new DatabaseSync($this->prodDbConfig, $this->devDbConfig);

            // Vérifier si les connexions sont établies
            if (!$sync->isConnected()) {
                $data["error"] = $sync->getConnectionError();
            } else {
                // Récupérer les tables disponibles
                $tables = $sync->getSourceTables();

                // Récupérer les tables exclues et optionnelles
                $data["excludedTables"] = $sync->getExcludedTables();
                $data["optionalTables"] = $sync->getOptionalTables();

                // Vérifier la structure
                $data["structureReport"] = $sync->verifyAllStructures($tables);

                // Récupérer les stats
                $data["tablesStats"] = $sync->getTablesStats($tables);
            }

            $sync->close();
        } catch (Exception $e) {
            $data["error"] = $e->getMessage();
        }

        // Charger la vue
        $this->render("dev-tools/sync-database", $data);
    }

    /**
     * Exécute la synchronisation de la base de données
     */
    public function executeSyncDatabase(): void
    {
        // Vérifier le token CSRF
        if (!Session::validateCsrfToken($_POST["_token"] ?? "")) {
            Session::flash("error", "Token CSRF invalide");
            header("Location: /stm/admin/dev-tools/sync-db");
            exit();
        }

        // Récupérer les tables sélectionnées
        $selectedTables = $_POST["tables"] ?? [];

        if (empty($selectedTables)) {
            Session::flash("error", "Aucune table sélectionnée");
            header("Location: /stm/admin/dev-tools/sync-db");
            exit();
        }

        try {
            $sync = new DatabaseSync($this->prodDbConfig, $this->devDbConfig);

            // Vérifier la connexion
            if (!$sync->isConnected()) {
                throw new Exception($sync->getConnectionError());
            }

            // Vérifier la structure avant de synchroniser
            $structureReport = $sync->verifyAllStructures($selectedTables);

            if (!$structureReport["success"]) {
                throw new Exception(
                    "La structure des tables est différente. Veuillez corriger les différences avant de synchroniser.",
                );
            }

            // Exécuter la synchronisation
            $results = $sync->syncTables($selectedTables);

            $sync->close();

            if ($results["success"]) {
                Session::flash(
                    "success",
                    sprintf(
                        "Synchronisation réussie ! %d lignes copiées dans %d tables.",
                        $results["total_rows_copied"],
                        count($results["tables"]),
                    ),
                );
            } else {
                $errors = [];
                foreach ($results["tables"] as $table => $result) {
                    if (!$result["success"]) {
                        $errors[] = "{$table}: {$result["error"]}";
                    }
                }
                Session::flash("error", "Erreurs lors de la synchronisation: " . implode(", ", $errors));
            }
        } catch (Exception $e) {
            Session::flash("error", "Erreur: " . $e->getMessage());
        }

        header("Location: /stm/admin/dev-tools/sync-db");
        exit();
    }

    /**
     * Affiche la page de synchronisation des fichiers
     */
    public function syncFiles(): void
    {
        $data = [
            "title" => "Synchronisation Fichiers",
            "error" => null,
            "analysis" => null,
        ];

        try {
            // Chemins des dossiers uploads
            // Chemin absolu vers le dossier public de PROD
            $prodUploadsPath = "/home/trendyblog/public_html/actions.trendyfoods.com/stm/public";

            // Chemin local (dev) - utiliser BASE_PATH si défini
            if (defined("BASE_PATH")) {
                $devUploadsPath = BASE_PATH . "/public";
            } else {
                $devUploadsPath = dirname(dirname(dirname(dirname(__DIR__)))) . "/public";
            }

            // Vérifier que les chemins existent
            if (!is_dir($prodUploadsPath)) {
                $data["error"] = "Le dossier source (prod) n'existe pas ou n'est pas accessible: {$prodUploadsPath}";
            } elseif (!is_dir($devUploadsPath)) {
                $data["error"] = "Le dossier cible (dev) n'existe pas: {$devUploadsPath}";
            } else {
                $fileSync = new FileSync($prodUploadsPath, $devUploadsPath);
                $data["analysis"] = $fileSync->analyzeFiles();
            }
        } catch (Exception $e) {
            $data["error"] = $e->getMessage();
        }

        $this->render("dev-tools/sync-files", $data);
    }

    /**
     * Exécute la synchronisation des fichiers
     */
    public function executeSyncFiles(): void
    {
        // Vérifier le token CSRF
        if (!Session::validateCsrfToken($_POST["_token"] ?? "")) {
            Session::flash("error", "Token CSRF invalide");
            header("Location: /stm/admin/dev-tools/sync-files");
            exit();
        }

        try {
            // Chemins des dossiers uploads
            $prodUploadsPath = "/home/trendyblog/public_html/actions.trendyfoods.com/stm/public";

            if (defined("BASE_PATH")) {
                $devUploadsPath = BASE_PATH . "/public";
            } else {
                $devUploadsPath = dirname(dirname(dirname(dirname(__DIR__)))) . "/public";
            }

            $fileSync = new FileSync($prodUploadsPath, $devUploadsPath);
            $results = $fileSync->sync();

            if ($results["success"]) {
                Session::flash(
                    "success",
                    sprintf(
                        "Synchronisation réussie ! %d fichiers copiés (%s)",
                        $results["files_copied"],
                        FileSync::formatSize($results["total_size_copied"]),
                    ),
                );
            } else {
                Session::flash("error", "Erreurs: " . implode(", ", $results["errors"]));
            }
        } catch (Exception $e) {
            Session::flash("error", "Erreur: " . $e->getMessage());
        }

        header("Location: /stm/admin/dev-tools/sync-files");
        exit();
    }

    /**
     * Charge et affiche une vue
     *
     * @param string $view Nom de la vue (sans extension)
     * @param array $data Données à passer à la vue
     */
    private function render(string $view, array $data = []): void
    {
        // Extraire les données pour les rendre accessibles dans la vue
        extract($data);

        // Capturer le contenu
        ob_start();

        // Chemin vers la vue
        $viewPath = dirname(dirname(dirname(__DIR__))) . "/Views/admin/" . $view . ".php";

        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            echo "<div class='p-4 bg-red-100 text-red-700 rounded'>Vue non trouvée: {$viewPath}</div>";
        }

        $content = ob_get_clean();

        // Variables pour le layout
        $title = $data["title"] ?? "Outils Dev";
        $pageScripts = "";

        // Charger le layout admin
        require dirname(dirname(dirname(__DIR__))) . "/Views/layouts/admin.php";
    }
}
