<?php
/**
 * Model : Translation
 * 
 * Gestion des traductions FR/NL pour le front client
 * Inclut système de cache pour performance optimale
 * 
 * @package    App\Models
 * @author     Fabian Hardy
 * @version    1.0.0
 * @created    2025/12/30
 */

namespace App\Models;

use Core\Database;

class Translation
{
    private Database $db;
    
    /** @var array|null Cache des traductions en mémoire */
    private static ?array $cache = null;
    
    /** @var string Chemin du fichier cache JSON */
    private const CACHE_FILE = __DIR__ . '/../../storage/cache/translations.json';
    
    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    // ========================================
    // MÉTHODES DE LECTURE
    // ========================================
    
    /**
     * Récupérer toutes les traductions (avec cache)
     * 
     * @return array Traductions indexées par clé
     */
    public function getAll(): array
    {
        // Retourner le cache mémoire si disponible
        if (self::$cache !== null) {
            return self::$cache;
        }
        
        // Essayer le cache fichier
        if ($this->loadFromFileCache()) {
            return self::$cache;
        }
        
        // Charger depuis la DB
        return $this->loadFromDatabase();
    }
    
    /**
     * Récupérer toutes les traductions pour l'admin (liste complète)
     * 
     * @param string|null $category Filtrer par catégorie
     * @param string|null $search Recherche texte
     * @return array
     */
    public function getAllForAdmin(?string $category = null, ?string $search = null): array
    {
        $query = "SELECT * FROM translations WHERE 1=1";
        $params = [];
        
        if ($category) {
            $query .= " AND category = :category";
            $params[':category'] = $category;
        }
        
        if ($search) {
            $query .= " AND (`key` LIKE :search OR text_fr LIKE :search2 OR text_nl LIKE :search3)";
            $params[':search'] = "%{$search}%";
            $params[':search2'] = "%{$search}%";
            $params[':search3'] = "%{$search}%";
        }
        
        $query .= " ORDER BY category, `key`";
        
        return $this->db->query($query, $params);
    }
    
    /**
     * Récupérer une traduction par ID
     * 
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $query = "SELECT * FROM translations WHERE id = :id";
        $result = $this->db->query($query, [':id' => $id]);
        return $result[0] ?? null;
    }
    
    /**
     * Récupérer une traduction par clé
     * 
     * @param string $key
     * @return array|null
     */
    public function findByKey(string $key): ?array
    {
        $translations = $this->getAll();
        return $translations[$key] ?? null;
    }
    
    /**
     * Récupérer le texte traduit
     * 
     * @param string $key Clé de traduction
     * @param string $lang Langue (fr/nl)
     * @param array $params Paramètres de remplacement
     * @return string
     */
    public function get(string $key, string $lang = 'fr', array $params = []): string
    {
        $translations = $this->getAll();
        
        if (!isset($translations[$key])) {
            // Clé non trouvée, retourner la clé elle-même
            return $key;
        }
        
        $translation = $translations[$key];
        
        // Prendre la langue demandée ou fallback sur FR
        $text = ($lang === 'nl' && !empty($translation['text_nl'])) 
            ? $translation['text_nl'] 
            : $translation['text_fr'];
        
        // Remplacer les paramètres {var}
        foreach ($params as $var => $value) {
            $text = str_replace('{' . $var . '}', $value, $text);
        }
        
        return $text;
    }
    
    /**
     * Récupérer toutes les catégories distinctes
     * 
     * @return array
     */
    public function getCategories(): array
    {
        $query = "SELECT DISTINCT category FROM translations ORDER BY category";
        $results = $this->db->query($query);
        return array_column($results, 'category');
    }
    
    /**
     * Compter les traductions par catégorie
     * 
     * @return array
     */
    public function countByCategory(): array
    {
        $query = "SELECT category, COUNT(*) as count FROM translations GROUP BY category ORDER BY category";
        return $this->db->query($query);
    }
    
    // ========================================
    // MÉTHODES D'ÉCRITURE
    // ========================================
    
    /**
     * Créer une nouvelle traduction
     * 
     * @param array $data
     * @return int|false ID créé ou false
     */
    public function create(array $data): int|false
    {
        $query = "INSERT INTO translations (`key`, category, text_fr, text_nl, description, is_html)
                  VALUES (:key, :category, :text_fr, :text_nl, :description, :is_html)";
        
        $params = [
            ':key' => $data['key'],
            ':category' => $data['category'],
            ':text_fr' => $data['text_fr'],
            ':text_nl' => $data['text_nl'] ?? null,
            ':description' => $data['description'] ?? null,
            ':is_html' => $data['is_html'] ?? 0
        ];
        
        $this->db->query($query, $params);
        $id = $this->db->lastInsertId();
        
        if ($id) {
            $this->clearCache();
        }
        
        return $id ?: false;
    }
    
    /**
     * Mettre à jour une traduction
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $query = "UPDATE translations SET 
                    text_fr = :text_fr,
                    text_nl = :text_nl,
                    description = :description,
                    is_html = :is_html
                  WHERE id = :id";
        
        $params = [
            ':id' => $id,
            ':text_fr' => $data['text_fr'],
            ':text_nl' => $data['text_nl'] ?? null,
            ':description' => $data['description'] ?? null,
            ':is_html' => $data['is_html'] ?? 0
        ];
        
        $this->db->query($query, $params);
        $this->clearCache();
        
        return true;
    }
    
    /**
     * Supprimer une traduction
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $query = "DELETE FROM translations WHERE id = :id";
        $this->db->query($query, [':id' => $id]);
        $this->clearCache();
        
        return true;
    }
    
    // ========================================
    // GESTION DU CACHE
    // ========================================
    
    /**
     * Charger depuis le cache fichier
     * 
     * @return bool
     */
    private function loadFromFileCache(): bool
    {
        if (!file_exists(self::CACHE_FILE)) {
            return false;
        }
        
        // Vérifier si le cache n'est pas trop vieux (1 heure)
        $cacheAge = time() - filemtime(self::CACHE_FILE);
        if ($cacheAge > 3600) {
            return false;
        }
        
        $content = file_get_contents(self::CACHE_FILE);
        $data = json_decode($content, true);
        
        if ($data === null) {
            return false;
        }
        
        self::$cache = $data;
        return true;
    }
    
    /**
     * Charger depuis la base de données
     * 
     * @return array
     */
    private function loadFromDatabase(): array
    {
        $query = "SELECT * FROM translations ORDER BY `key`";
        $results = $this->db->query($query);
        
        // Indexer par clé
        $translations = [];
        foreach ($results as $row) {
            $translations[$row['key']] = $row;
        }
        
        // Mettre en cache mémoire
        self::$cache = $translations;
        
        // Sauvegarder en cache fichier
        $this->saveToFileCache($translations);
        
        return $translations;
    }
    
    /**
     * Sauvegarder dans le cache fichier
     * 
     * @param array $data
     * @return void
     */
    private function saveToFileCache(array $data): void
    {
        $dir = dirname(self::CACHE_FILE);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents(self::CACHE_FILE, json_encode($data, JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * Vider le cache (après modification)
     * 
     * @return void
     */
    public function clearCache(): void
    {
        self::$cache = null;
        
        if (file_exists(self::CACHE_FILE)) {
            unlink(self::CACHE_FILE);
        }
    }
    
    /**
     * Régénérer le cache
     * 
     * @return int Nombre de traductions mises en cache
     */
    public function rebuildCache(): int
    {
        $this->clearCache();
        $translations = $this->loadFromDatabase();
        return count($translations);
    }
    
    // ========================================
    // MÉTHODES UTILITAIRES
    // ========================================
    
    /**
     * Exporter toutes les traductions en JSON
     * 
     * @return string
     */
    public function exportJson(): string
    {
        $translations = $this->getAll();
        return json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Vérifier les traductions manquantes (NL vide)
     * 
     * @return array
     */
    public function getMissingTranslations(): array
    {
        $query = "SELECT * FROM translations WHERE text_nl IS NULL OR text_nl = '' ORDER BY category, `key`";
        return $this->db->query($query);
    }
}
