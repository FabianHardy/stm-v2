<?php
/**
 * Model EmailTemplate
 *
 * Gestion des templates d'emails stockés en base de données
 * Permet aux admins de modifier les emails sans toucher au code
 *
 * @package    App\Models
 * @author     Fabian Hardy
 * @version    1.0.0
 * @created    2025/12/30
 */

namespace App\Models;

use Core\Database;

class EmailTemplate
{
    private Database $db;

    /**
     * Variables disponibles par type de template
     */
    public const TEMPLATE_VARIABLES = [
        'order_confirmation' => [
            'campaign_name' => 'Nom de la campagne',
            'company_name' => 'Nom de la société du client',
            'customer_number' => 'Numéro client',
            'customer_email' => 'Email du client',
            'order_date' => 'Date de la commande',
            'total_items' => 'Nombre total d\'articles',
            'total_products' => 'Nombre de produits différents',
            'order_lines' => 'Liste des produits commandés (HTML)',
            'delivery_date' => 'Date de livraison (si applicable)',
        ],
        'order_cancelled' => [
            'campaign_name' => 'Nom de la campagne',
            'company_name' => 'Nom de la société du client',
            'customer_number' => 'Numéro client',
            'order_date' => 'Date de la commande',
            'cancellation_reason' => 'Raison de l\'annulation',
        ],
    ];

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Récupérer tous les templates
     *
     * @return array
     */
    public function getAll(): array
    {
        return $this->db->query("
            SELECT
                id,
                type,
                type as code,
                type as name,
                subject_fr,
                subject_nl,
                variables,
                1 as is_active,
                created_at,
                updated_at
            FROM email_templates
            ORDER BY type ASC
        ");
    }

    /**
     * Récupérer un template par son ID
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $result = $this->db->queryOne(
            "SELECT *, type as code, type as name, 1 as is_active FROM email_templates WHERE id = :id",
            [':id' => $id]
        );
        return $result ?: null;
    }

    /**
     * Récupérer un template par son type (code)
     *
     * @param string $type
     * @return array|null
     */
    public function findByCode(string $type): ?array
    {
        $result = $this->db->queryOne(
            "SELECT *, type as code, type as name FROM email_templates WHERE type = :type",
            [':type' => $type]
        );
        return $result ?: null;
    }

    /**
     * Mettre à jour un template
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        // Mise à jour des colonnes qui existent à coup sûr
        $sql = "UPDATE email_templates SET
            subject_fr = :subject_fr,
            subject_nl = :subject_nl,
            body_fr = :body_fr,
            body_nl = :body_nl,
            updated_at = NOW()
            WHERE id = :id";

        return $this->db->execute($sql, [
            ':subject_fr' => $data['subject_fr'],
            ':subject_nl' => $data['subject_nl'] ?? null,
            ':body_fr' => $data['body_fr'],
            ':body_nl' => $data['body_nl'] ?? null,
            ':id' => $id
        ]);
    }

    /**
     * Créer un nouveau template
     *
     * @param array $data
     * @return int|false ID du template créé ou false
     */
    public function create(array $data): int|false
    {
        $sql = "INSERT INTO email_templates
            (code, name, description, subject_fr, subject_nl, body_fr, body_nl, variables, is_active, created_at, updated_at)
            VALUES
            (:code, :name, :description, :subject_fr, :subject_nl, :body_fr, :body_nl, :variables, :is_active, NOW(), NOW())";

        $result = $this->db->execute($sql, [
            ':code' => $data['code'],
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':subject_fr' => $data['subject_fr'],
            ':subject_nl' => $data['subject_nl'] ?? null,
            ':body_fr' => $data['body_fr'],
            ':body_nl' => $data['body_nl'] ?? null,
            ':variables' => $data['variables'] ?? null,
            ':is_active' => $data['is_active'] ?? 1
        ]);

        return $result ? (int)$this->db->lastInsertId() : false;
    }

    /**
     * Supprimer un template
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->db->execute(
            "DELETE FROM email_templates WHERE id = :id",
            [':id' => $id]
        );
    }

    /**
     * Activer/Désactiver un template
     *
     * @param int $id
     * @param bool $active
     * @return bool
     */
    public function setActive(int $id, bool $active): bool
    {
        return $this->db->execute(
            "UPDATE email_templates SET is_active = :active, updated_at = NOW() WHERE id = :id",
            [':active' => $active ? 1 : 0, ':id' => $id]
        );
    }

    /**
     * Rendre un template avec les données
     * Remplace les variables {{variable}} par leurs valeurs
     *
     * @param string $code Code du template
     * @param string $language Langue (fr|nl)
     * @param array $data Données à injecter
     * @return array|null ['subject' => string, 'body' => string] ou null si template introuvable
     */
    public function render(string $code, string $language, array $data): ?array
    {
        $template = $this->findByCode($code);

        if (!$template) {
            error_log("EmailTemplate::render() - Template introuvable: {$code}");
            return null;
        }

        // Sélectionner la bonne langue
        $subject = $language === 'nl' && !empty($template['subject_nl'])
            ? $template['subject_nl']
            : $template['subject_fr'];

        $body = $language === 'nl' && !empty($template['body_nl'])
            ? $template['body_nl']
            : $template['body_fr'];

        // Remplacer les variables {variable}
        foreach ($data as $key => $value) {
            // Ne pas traiter les valeurs qui sont des tableaux
            if (is_array($value)) {
                continue;
            }
            $subject = str_replace('{' . $key . '}', $value, $subject);
            $body = str_replace('{' . $key . '}', $value, $body);
        }

        // Gérer les conditions {#if variable}...{/if}
        $body = $this->processConditionals($body, $data);

        return [
            'subject' => $subject,
            'body' => $body
        ];
    }

    /**
     * Traiter les conditions {#if variable}...{/if}
     *
     * @param string $content
     * @param array $data
     * @return string
     */
    private function processConditionals(string $content, array $data): string
    {
        // Pattern pour {#if variable}contenu{/if}
        $pattern = '/\{#if\s+(\w+)\}(.*?)\{\/if\}/s';

        return preg_replace_callback($pattern, function($matches) use ($data) {
            $variable = $matches[1];
            $innerContent = $matches[2];

            // Si la variable existe et n'est pas vide, afficher le contenu
            if (!empty($data[$variable])) {
                // Remplacer aussi la variable dans le contenu interne
                return str_replace('{' . $variable . '}', $data[$variable], $innerContent);
            }

            // Sinon, ne rien afficher
            return '';
        }, $content);
    }

    /**
     * Générer le HTML des lignes de commande pour l'email
     *
     * @param array $orderLines
     * @param string $language
     * @return string HTML
     */
    public static function renderOrderLines(array $orderLines, string $language = 'fr'): string
    {
        $html = '';

        foreach ($orderLines as $line) {
            $productName = $language === 'nl' && !empty($line['product_name_nl'])
                ? $line['product_name_nl']
                : ($line['product_name_fr'] ?? $line['product_name'] ?? 'Produit');

            $quantity = (int)($line['quantity'] ?? 0);

            $html .= '<tr>
                <td style="padding: 12px 15px; border-bottom: 1px solid #e0e0e0; font-size: 14px; color: #333;">'
                . htmlspecialchars($productName) . '</td>
                <td style="padding: 12px 15px; border-bottom: 1px solid #e0e0e0; text-align: center; font-size: 14px; font-weight: bold; color: #e73029;">'
                . $quantity . '</td>
            </tr>';
        }

        return $html;
    }

    /**
     * Obtenir les variables disponibles pour un template
     *
     * @param string $code
     * @return array
     */
    public function getAvailableVariables(string $code): array
    {
        return self::TEMPLATE_VARIABLES[$code] ?? [];
    }

    /**
     * Compter le nombre de templates
     *
     * @return int
     */
    public function count(): int
    {
        $result = $this->db->queryOne("SELECT COUNT(*) as total FROM email_templates");
        return (int)($result['total'] ?? 0);
    }
}