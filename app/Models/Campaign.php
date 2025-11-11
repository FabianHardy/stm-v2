<?php
/**
 * Model Campaign
 * Gestion des campagnes promotionnelles
 * 
 * @package STM/Models
 * @version 2.2.0
 * @modified 11/11/2025 - Ajout méthode getActiveOrFuture() pour dropdown produits
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
     * 
     * @param array $filters Filtres optionnels (country, is_active, search)
     * @param int $page Numéro de page
     * @param int $perPage Nombre de résultats par page
     * @return array
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

        // Filtre par recherche (nom ou titres)
        if (!empty($filters['search'])) {
            $sql .= " AND (name LIKE :search OR title_fr LIKE :search OR title_nl LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        // Filtre par statut temporel
        if (!empty($filters['status'])) {
            switch ($filters['status']) {
                case 'active':
                    $sql .= " AND is_active = 1 AND start_date <= CURDATE() AND end_date >= CURDATE()";
                    break;
                case 'upcoming':
                    $sql .= " AND start_date > CURDATE()";
                    break;
                case 'ended':
                    $sql .= " AND end_date < CURDATE()";
                    break;
                case 'inactive':
                    $sql .= " AND is_active = 0";
                    break;
            }
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
     * 
     * @param array $filters Filtres optionnels
     * @return int
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

        if (!empty($filters['status'])) {
            switch ($filters['status']) {
                case 'active':
                    $sql .= " AND is_active = 1 AND start_date <= CURDATE() AND end_date >= CURDATE()";
                    break;
                case 'upcoming':
                    $sql .= " AND start_date > CURDATE()";
                    break;
                case 'ended':
                    $sql .= " AND end_date < CURDATE()";
                    break;
                case 'inactive':
                    $sql .= " AND is_active = 0";
                    break;
            }
        }

        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        $stmt->execute();
        
        return (int) $stmt->fetchColumn();
    }

    /**
     * Récupérer une campagne par son ID
     * 
     * @param int $id ID de la campagne
     * @return array|false
     */
    public function findById(int $id): array|false
    {
        $sql = "SELECT * FROM campaigns WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        return $stmt->fetch();
    }

    /**
     * Récupérer une campagne par son UUID
     * 
     * @param string $uuid UUID de la campagne
     * @return array|false
     */
    public function findByUuid(string $uuid): array|false
    {
        $sql = "SELECT * FROM campaigns WHERE uuid = :uuid";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uuid' => $uuid]);
        
        return $stmt->fetch();
    }

    /**
     * Récupérer une campagne par son slug
     * 
     * @param string $slug Slug de la campagne
     * @return array|false
     */
    public function findBySlug(string $slug): array|false
    {
        $sql = "SELECT * FROM campaigns WHERE slug = :slug";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['slug' => $slug]);
        
        return $stmt->fetch();
    }

    /**
     * Créer une nouvelle campagne
     * 
     * @param array $data Données de la campagne
     * @return int|false ID de la campagne créée ou false
     */
    public function create(array $data): int|false
    {
        // Générer UUID et slug automatiquement
        $uuid = $this->generateUuid();
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

        if ($stmt->execute($params)) {
            return (int) $this->db->lastInsertId();
        }
        
        return false;
    }

    /**
     * Mettre à jour une campagne
     * 
     * @param int $id ID de la campagne
     * @param array $data Nouvelles données
     * @return bool
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
     * 
     * @param int $id ID de la campagne
     * @return bool
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM campaigns WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Récupérer les campagnes actives (en cours de validité)
     * 
     * @return array
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
     * Utilisé pour les dropdowns produits : on ne veut pas les campagnes passées
     * 
     * @return array
     * @created 11/11/2025
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
     * Récupérer les campagnes archivées (inactives ou terminées)
     * 
     * @return array
     */
    public function getArchived(): array
    {
        $sql = "SELECT * FROM campaigns 
                WHERE is_active = 0 OR end_date < CURDATE()
                ORDER BY end_date DESC";
        
        return $this->db->query($sql);
    }

    /**
     * Récupérer les statistiques des campagnes
     * 
     * @return array
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
        $sql = "SELECT COUNT(*) as count FROM campaigns";
        $result = $this->db->query($sql);
        $stats['total'] = (int) $result[0]['count'];

        // Actives (en cours de validité)
        $sql = "SELECT COUNT(*) as count FROM campaigns 
                WHERE is_active = 1 
                AND start_date <= CURDATE() 
                AND end_date >= CURDATE()";
        $result = $this->db->query($sql);
        $stats['active'] = (int) $result[0]['count'];

        // Archivées (inactives ou terminées)
        $sql = "SELECT COUNT(*) as count FROM campaigns 
                WHERE is_active = 0 OR end_date < CURDATE()";
        $result = $this->db->query($sql);
        $stats['archived'] = (int) $result[0]['count'];

        // Par pays
        $sql = "SELECT COUNT(*) as count FROM campaigns WHERE country = 'BE'";
        $result = $this->db->query($sql);
        $stats['be'] = (int) $result[0]['count'];

        $sql = "SELECT COUNT(*) as count FROM campaigns WHERE country = 'LU'";
        $result = $this->db->query($sql);
        $stats['lu'] = (int) $result[0]['count'];

        return $stats;
    }

    /**
     * Valider les données d'une campagne
     * 
     * @param array $data Données à valider
     * @return array Tableau des erreurs (vide si OK)
     */
    public function validate(array $data): array
    {
        $errors = [];

        // Nom obligatoire
        if (empty($data['name'])) {
            $errors['name'] = 'Le nom de la campagne est obligatoire';
        }

        // Pays obligatoire et valide
        if (empty($data['country'])) {
            $errors['country'] = 'Le pays est obligatoire';
        } elseif (!in_array($data['country'], ['BE', 'LU'])) {
            $errors['country'] = 'Le pays doit être BE ou LU';
        }

        // Dates obligatoires
        if (empty($data['start_date'])) {
            $errors['start_date'] = 'La date de début est obligatoire';
        }

        if (empty($data['end_date'])) {
            $errors['end_date'] = 'La date de fin est obligatoire';
        }

        // Vérifier que end_date > start_date
        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            if (strtotime($data['end_date']) < strtotime($data['start_date'])) {
                $errors['end_date'] = 'La date de fin doit être postérieure à la date de début';
            }
        }

        // Titre FR obligatoire
        if (empty($data['title_fr'])) {
            $errors['title_fr'] = 'Le titre en français est obligatoire';
        }

        return $errors;
    }

    /**
     * Générer un UUID unique
     * 
     * @return string
     */
    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Générer un slug à partir d'un texte
     * 
     * @param string $text Texte à transformer
     * @return string
     */
    private function generateSlug(string $text): string
    {
        // Remplacer les caractères accentués
        $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        
        // Mettre en minuscule
        $text = strtolower($text);
        
        // Remplacer tout ce qui n'est pas alphanumérique par des tirets
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        
        // Enlever les tirets en début et fin
        $text = trim($text, '-');
        
        // S'assurer que le slug est unique
        $slug = $text;
        $counter = 1;
        
        while ($this->findBySlug($slug)) {
            $slug = $text . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    /**
     * Basculer le statut actif/inactif d'une campagne
     * 
     * @param int $id ID de la campagne
     * @return bool
     */
    public function toggleActive(int $id): bool
    {
        $sql = "UPDATE campaigns SET is_active = NOT is_active WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute(['id' => $id]);
    }
}