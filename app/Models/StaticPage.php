<?php
/**
 * Model StaticPage
 * 
 * Gestion des pages fixes (CGU, CGV, Mentions légales, etc.)
 * Supporte les pages globales et les surcharges par campagne.
 * 
 * @package    App\Models
 * @author     Fabian Hardy
 * @version    1.0.0
 * @created    2025/12/30
 */

namespace App\Models;

use Core\Database;

class StaticPage
{
    private Database $db;

    /**
     * Pages par défaut avec leurs descriptions
     */
    public const DEFAULT_PAGES = [
        'cgu' => 'Conditions Générales d\'Utilisation',
        'cgv' => 'Conditions Générales de Vente',
        'mentions-legales' => 'Mentions légales',
        'confidentialite' => 'Politique de confidentialité',
    ];

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Récupérer toutes les pages (globales uniquement)
     *
     * @return array
     */
    public function getAll(): array
    {
        return $this->db->query("
            SELECT 
                id, slug, title_fr, title_nl, 
                campaign_id, is_active, show_in_footer, sort_order,
                created_at, updated_at
            FROM static_pages
            WHERE campaign_id IS NULL
            ORDER BY sort_order ASC, title_fr ASC
        ");
    }

    /**
     * Récupérer toutes les pages avec leurs surcharges
     *
     * @return array
     */
    public function getAllWithOverrides(): array
    {
        return $this->db->query("
            SELECT 
                sp.*,
                c.name as campaign_name
            FROM static_pages sp
            LEFT JOIN campaigns c ON sp.campaign_id = c.id
            ORDER BY sp.slug ASC, sp.campaign_id ASC
        ");
    }

    /**
     * Récupérer les surcharges pour une campagne
     *
     * @param int $campaignId
     * @return array
     */
    public function getOverridesForCampaign(int $campaignId): array
    {
        return $this->db->query("
            SELECT * FROM static_pages
            WHERE campaign_id = :campaign_id
            ORDER BY sort_order ASC
        ", [':campaign_id' => $campaignId]);
    }

    /**
     * Récupérer une page par son ID
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $result = $this->db->queryOne(
            "SELECT * FROM static_pages WHERE id = :id",
            [':id' => $id]
        );
        return $result ?: null;
    }

    /**
     * Récupérer une page par son slug (avec logique de surcharge)
     * 
     * Priorité :
     * 1. Page spécifique à la campagne (si campaignId fourni)
     * 2. Page globale (campaign_id = NULL)
     *
     * @param string $slug
     * @param int|null $campaignId
     * @return array|null
     */
    public function findBySlug(string $slug, ?int $campaignId = null): ?array
    {
        // D'abord chercher une surcharge pour la campagne
        if ($campaignId !== null) {
            $result = $this->db->queryOne(
                "SELECT * FROM static_pages 
                 WHERE slug = :slug AND campaign_id = :campaign_id AND is_active = 1",
                [':slug' => $slug, ':campaign_id' => $campaignId]
            );
            
            if ($result) {
                return $result;
            }
        }
        
        // Sinon, retourner la page globale
        $result = $this->db->queryOne(
            "SELECT * FROM static_pages 
             WHERE slug = :slug AND campaign_id IS NULL AND is_active = 1",
            [':slug' => $slug]
        );
        
        return $result ?: null;
    }

    /**
     * Récupérer les pages à afficher dans le footer
     *
     * @param int|null $campaignId Pour inclure les surcharges
     * @return array
     */
    public function getFooterPages(?int $campaignId = null): array
    {
        // Récupérer les pages globales actives pour le footer
        $globalPages = $this->db->query("
            SELECT slug, title_fr, title_nl, sort_order
            FROM static_pages
            WHERE campaign_id IS NULL 
              AND is_active = 1 
              AND show_in_footer = 1
            ORDER BY sort_order ASC
        ");

        // Si une campagne est spécifiée, vérifier les surcharges
        if ($campaignId !== null) {
            $overrides = $this->db->query("
                SELECT slug, title_fr, title_nl, sort_order
                FROM static_pages
                WHERE campaign_id = :campaign_id 
                  AND is_active = 1 
                  AND show_in_footer = 1
            ", [':campaign_id' => $campaignId]);

            // Fusionner : les surcharges remplacent les globales
            $overridesBySlug = [];
            foreach ($overrides as $override) {
                $overridesBySlug[$override['slug']] = $override;
            }

            foreach ($globalPages as &$page) {
                if (isset($overridesBySlug[$page['slug']])) {
                    $page = $overridesBySlug[$page['slug']];
                }
            }
        }

        return $globalPages;
    }

    /**
     * Créer une nouvelle page
     *
     * @param array $data
     * @return int|false ID de la page créée ou false
     */
    public function create(array $data): int|false
    {
        $sql = "INSERT INTO static_pages 
                (slug, title_fr, title_nl, content_fr, content_nl, campaign_id, is_active, show_in_footer, sort_order)
                VALUES 
                (:slug, :title_fr, :title_nl, :content_fr, :content_nl, :campaign_id, :is_active, :show_in_footer, :sort_order)";

        $success = $this->db->execute($sql, [
            ':slug' => $data['slug'],
            ':title_fr' => $data['title_fr'],
            ':title_nl' => $data['title_nl'] ?? null,
            ':content_fr' => $data['content_fr'],
            ':content_nl' => $data['content_nl'] ?? null,
            ':campaign_id' => $data['campaign_id'] ?? null,
            ':is_active' => $data['is_active'] ?? 1,
            ':show_in_footer' => $data['show_in_footer'] ?? 1,
            ':sort_order' => $data['sort_order'] ?? 0,
        ]);

        return $success ? (int)$this->db->lastInsertId() : false;
    }

    /**
     * Mettre à jour une page
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE static_pages SET 
                title_fr = :title_fr,
                title_nl = :title_nl,
                content_fr = :content_fr,
                content_nl = :content_nl,
                is_active = :is_active,
                show_in_footer = :show_in_footer,
                sort_order = :sort_order,
                updated_at = NOW()
                WHERE id = :id";

        return $this->db->execute($sql, [
            ':title_fr' => $data['title_fr'],
            ':title_nl' => $data['title_nl'] ?? null,
            ':content_fr' => $data['content_fr'],
            ':content_nl' => $data['content_nl'] ?? null,
            ':is_active' => $data['is_active'] ?? 1,
            ':show_in_footer' => $data['show_in_footer'] ?? 1,
            ':sort_order' => $data['sort_order'] ?? 0,
            ':id' => $id,
        ]);
    }

    /**
     * Supprimer une page
     * 
     * Note : Ne permet pas de supprimer une page globale par défaut
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        // Vérifier que ce n'est pas une page globale par défaut
        $page = $this->findById($id);
        if (!$page) {
            return false;
        }

        // Interdire la suppression des pages globales par défaut
        if ($page['campaign_id'] === null && in_array($page['slug'], array_keys(self::DEFAULT_PAGES))) {
            return false;
        }

        return $this->db->execute(
            "DELETE FROM static_pages WHERE id = :id",
            [':id' => $id]
        );
    }

    /**
     * Créer une surcharge de page pour une campagne
     *
     * @param string $slug
     * @param int $campaignId
     * @return int|false
     */
    public function createOverride(string $slug, int $campaignId): int|false
    {
        // Récupérer la page globale
        $globalPage = $this->findBySlug($slug);
        if (!$globalPage) {
            return false;
        }

        // Vérifier qu'une surcharge n'existe pas déjà
        $existing = $this->db->queryOne(
            "SELECT id FROM static_pages WHERE slug = :slug AND campaign_id = :campaign_id",
            [':slug' => $slug, ':campaign_id' => $campaignId]
        );

        if ($existing) {
            return (int)$existing['id'];
        }

        // Créer la surcharge avec le contenu de la page globale
        return $this->create([
            'slug' => $slug,
            'title_fr' => $globalPage['title_fr'],
            'title_nl' => $globalPage['title_nl'],
            'content_fr' => $globalPage['content_fr'],
            'content_nl' => $globalPage['content_nl'],
            'campaign_id' => $campaignId,
            'is_active' => 1,
            'show_in_footer' => $globalPage['show_in_footer'],
            'sort_order' => $globalPage['sort_order'],
        ]);
    }

    /**
     * Activer/désactiver une page
     *
     * @param int $id
     * @param bool $active
     * @return bool
     */
    public function setActive(int $id, bool $active): bool
    {
        return $this->db->execute(
            "UPDATE static_pages SET is_active = :active, updated_at = NOW() WHERE id = :id",
            [':active' => $active ? 1 : 0, ':id' => $id]
        );
    }

    /**
     * Vérifier si un slug existe déjà (pour une campagne donnée)
     *
     * @param string $slug
     * @param int|null $campaignId
     * @param int|null $excludeId
     * @return bool
     */
    public function slugExists(string $slug, ?int $campaignId = null, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM static_pages 
                WHERE slug = :slug AND (campaign_id IS NULL OR campaign_id = :campaign_id)";
        $params = [':slug' => $slug, ':campaign_id' => $campaignId];

        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }

        $result = $this->db->queryOne($sql, $params);
        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Récupérer le contenu rendu d'une page dans la bonne langue
     *
     * @param string $slug
     * @param string $language 'fr' ou 'nl'
     * @param int|null $campaignId
     * @return array|null ['title' => string, 'content' => string]
     */
    public function render(string $slug, string $language = 'fr', ?int $campaignId = null): ?array
    {
        $page = $this->findBySlug($slug, $campaignId);
        if (!$page) {
            return null;
        }

        // Sélectionner la langue (fallback FR si NL vide)
        $title = $page['title_fr'];
        $content = $page['content_fr'];

        if ($language === 'nl') {
            if (!empty($page['title_nl'])) {
                $title = $page['title_nl'];
            }
            if (!empty($page['content_nl'])) {
                $content = $page['content_nl'];
            }
        }

        return [
            'title' => $title,
            'content' => $content,
            'slug' => $page['slug'],
            'is_override' => $page['campaign_id'] !== null,
        ];
    }
}
