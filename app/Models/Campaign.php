<?php
/**
 * Model Campaign
 * Gestion des campagnes promotionnelles
 * 
 * @package STM/Models
 * @version 2.1.0
 * @modified 11/11/2025 - Génération auto UUID + slug, suppression contrainte UNIQUE sur name
 */

namespace App\Models;

use Core\Database;
use PDO;

class Campaign
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Récupérer toutes les campagnes avec pagination et filtres
     */
    public function getAll(array $filters = [], int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT * FROM campaigns WHERE 1=1";
        $params = [];

        // Filtre par pays
        if (!empty($filters['country'])) {
            $sql .= " AND country = :country";
            $params['country'] = $filters['country'];
        }

        // Filtre par statut
        if (isset($filters['is_active'])) {
            $sql .= " AND is_active = :is_active";
            $params['is_active'] = $filters['is_active'];
        }

        // Filtre par recherche (nom)
        if (!empty($filters['search'])) {
            $sql .= " AND (name LIKE :search OR title_fr LIKE :search OR title_nl LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Compter les campagnes avec filtres
     */
    public function count(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) FROM campaigns WHERE 1=1";
        $params = [];

        if (!empty($filters['country'])) {
            $sql .= " AND country = :country";
            $params['country'] = $filters['country'];
        }

        if (isset($filters['is_active'])) {
            $sql .= " AND is_active = :is_active";
            $params['is_active'] = $filters['is_active'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (name LIKE :search OR title_fr LIKE :search OR title_nl LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        $stmt->execute();
        
        return (int) $stmt->fetchColumn();
    }

    /**
     * Récupérer une campagne par ID
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM campaigns WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Récupérer une campagne par UUID
     */
    public function findByUuid(string $uuid): ?array
    {
        $sql = "SELECT * FROM campaigns WHERE uuid = :uuid";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uuid' => $uuid]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Récupérer une campagne par slug
     */
    public function findBySlug(string $slug): ?array
    {
        $sql = "SELECT * FROM campaigns WHERE slug = :slug";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['slug' => $slug]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Créer une nouvelle campagne
     * Génère automatiquement l'UUID et le slug
     */
    public function create(array $data): int
    {
        // Générer UUID unique
        $uuid = $this->generateUniqueUuid();
        
        // Générer slug depuis le nom
        $slug = $this->generateSlug($data['name']);
        
        $sql = "INSERT INTO campaigns (
                    uuid, slug, name, country, is_active,
                    start_date, end_date,
                    title_fr, description_fr,
                    title_nl, description_nl
                ) VALUES (
                    :uuid, :slug, :name, :country, :is_active,
                    :start_date, :end_date,
                    :title_fr, :description_fr,
                    :title_nl, :description_nl
                )";

        $stmt = $this->db->prepare($sql);
        
        $params = [
            'uuid' => $uuid,
            'slug' => $slug,
            'name' => $data['name'],
            'country' => $data['country'],
            'is_active' => $data['is_active'] ?? 1,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'title_fr' => $data['title_fr'] ?? null,
            'description_fr' => $data['description_fr'] ?? null,
            'title_nl' => $data['title_nl'] ?? null,
            'description_nl' => $data['description_nl'] ?? null,
        ];

        $stmt->execute($params);
        
        return (int) $this->db->lastInsertId();
    }

    /**
     * Mettre à jour une campagne
     */
    public function update(int $id, array $data): bool
    {
        // Si le nom change, régénérer le slug
        $campaign = $this->findById($id);
        if ($campaign && $campaign['name'] !== $data['name']) {
            $data['slug'] = $this->generateSlug($data['name']);
        }
        
        $sql = "UPDATE campaigns SET
                    name = :name,
                    country = :country,
                    is_active = :is_active,
                    start_date = :start_date,
                    end_date = :end_date,
                    title_fr = :title_fr,
                    description_fr = :description_fr,
                    title_nl = :title_nl,
                    description_nl = :description_nl";
        
        if (isset($data['slug'])) {
            $sql .= ", slug = :slug";
        }
        
        $sql .= " WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        
        $params = [
            'id' => $id,
            'name' => $data['name'],
            'country' => $data['country'],
            'is_active' => $data['is_active'] ?? 1,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'title_fr' => $data['title_fr'] ?? null,
            'description_fr' => $data['description_fr'] ?? null,
            'title_nl' => $data['title_nl'] ?? null,
            'description_nl' => $data['description_nl'] ?? null,
        ];
        
        if (isset($data['slug'])) {
            $params['slug'] = $data['slug'];
        }

        return $stmt->execute($params);
    }

    /**
     * Supprimer une campagne
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM campaigns WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Récupérer les campagnes actives
     */
    public function getActive(): array
    {
        $sql = "SELECT * FROM campaigns 
                WHERE is_active = 1 
                AND start_date <= CURDATE() 
                AND end_date >= CURDATE()
                ORDER BY start_date DESC";
        
        return $this->db->query($sql);
    }

    /**
     * Récupérer les campagnes actives OU futures (pas les passées)
     * 
     * Utilisé pour les formulaires de sélection de campagne
     * Retourne les campagnes en cours + celles qui vont commencer
     * Exclut les campagnes terminées
     * 
     * @return array
     * @added 12/11/2025
     */
    public function getActiveOrFuture(): array
    {
        $sql = "SELECT * FROM campaigns 
                WHERE is_active = 1 
                AND end_date >= CURDATE()
                ORDER BY start_date ASC";
        
        return $this->db->query($sql);
    }

    /**
     * Récupérer les campagnes archivées
     */
    public function getArchived(): array
    {
        $sql = "SELECT * FROM campaigns 
                WHERE is_active = 0 OR end_date < CURDATE()
                ORDER BY end_date DESC";
        
        return $this->db->query($sql);
    }

    /**
     * Récupérer les statistiques
     */
    public function getStats(): array
    {
        $stats = [
            'total' => 0,
            'active' => 0,
            'archived' => 0,
            'be' => 0,
            'lu' => 0,
        ];

        // Total
        $sql = "SELECT COUNT(*) FROM campaigns";
        $stats['total'] = (int) $this->db->query($sql)[0]['COUNT(*)'];

        // Actives (en cours de validité)
        $sql = "SELECT COUNT(*) FROM campaigns 
                WHERE is_active = 1 
                AND start_date <= CURDATE() 
                AND end_date >= CURDATE()";
        $stats['active'] = (int) $this->db->query($sql)[0]['COUNT(*)'];

        // Archives
        $sql = "SELECT COUNT(*) FROM campaigns 
                WHERE is_active = 0 OR end_date < CURDATE()";
        $stats['archived'] = (int) $this->db->query($sql)[0]['COUNT(*)'];

        // Par pays
        $sql = "SELECT country, COUNT(*) as count FROM campaigns GROUP BY country";
        $results = $this->db->query($sql);
        
        foreach ($results as $row) {
            $country = strtolower($row['country']);
            $stats[$country] = (int) $row['count'];
        }

        return $stats;
    }

    /**
     * Valider les données de campagne
     */
    public function validate(array $data, bool $isUpdate = false): array
    {
        $errors = [];

        // Nom requis
        if (empty($data['name'])) {
            $errors['name'] = "Le nom de la campagne est requis";
        }

        // Pays requis
        if (empty($data['country']) || !in_array($data['country'], ['BE', 'LU'])) {
            $errors['country'] = "Le pays doit être BE ou LU";
        }

        // Date de début requise
        if (empty($data['start_date'])) {
            $errors['start_date'] = "La date de début est requise";
        }

        // Date de fin requise
        if (empty($data['end_date'])) {
            $errors['end_date'] = "La date de fin est requise";
        }

        // Vérifier que end_date > start_date
        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            if (strtotime($data['end_date']) < strtotime($data['start_date'])) {
                $errors['end_date'] = "La date de fin doit être après la date de début";
            }
        }

        return $errors;
    }

    /**
     * Générer un UUID v4 unique
     */
    private function generateUniqueUuid(): string
    {
        do {
            $uuid = $this->generateUuid();
            $existing = $this->findByUuid($uuid);
        } while ($existing !== null);

        return $uuid;
    }

    /**
     * Générer un UUID v4
     */
    private function generateUuid(): string
    {
        $data = random_bytes(16);
        
        // Set version (4) et variant
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Générer un slug depuis une chaîne
     */
    private function generateSlug(string $text): string
    {
        // Convertir en minuscules
        $slug = strtolower($text);
        
        // Remplacer les caractères accentués
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $slug);
        
        // Supprimer tout sauf lettres, chiffres, tirets
        $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
        
        // Supprimer les tirets multiples
        $slug = preg_replace('/-+/', '-', $slug);
        
        // Supprimer les tirets en début/fin
        $slug = trim($slug, '-');
        
        // Si le slug existe déjà, ajouter un suffixe numérique
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->findBySlug($slug) !== null) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    /**
     * Obtenir l'URL publique d'une campagne
     */
    public function getPublicUrl(array $campaign): string
    {
        $baseUrl = rtrim($_ENV['APP_URL'] ?? 'https://actions.trendyfoods.com/stm', '/');
        return $baseUrl . '/c/' . $campaign['uuid'];
    }
}