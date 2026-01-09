<?php
/**
 * Model Prospect
 *
 * Gestion des prospects (clients potentiels non existants)
 * Sprint 16 : Mode Prospect
 *
 * @package    App\Models
 * @author     Claude AI
 * @version    1.0.0
 * @created    2026/01/09
 */

namespace App\Models;

use Core\Database;

class Prospect
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Générer un numéro de prospect unique
     * Format: PROSP-BE-000001 ou PROSP-LU-000001
     *
     * @param string $country BE ou LU
     * @return string
     */
    public function generateProspectNumber(string $country): string
    {
        $country = strtoupper($country);
        
        // Récupérer et incrémenter le compteur
        $query = "UPDATE prospect_sequences 
                  SET last_number = last_number + 1, updated_at = NOW() 
                  WHERE country = :country";
        $this->db->execute($query, [":country" => $country]);
        
        // Récupérer le nouveau numéro
        $query = "SELECT last_number FROM prospect_sequences WHERE country = :country";
        $result = $this->db->query($query, [":country" => $country]);
        
        $number = $result[0]["last_number"] ?? 1;
        
        return sprintf("PROSP-%s-%06d", $country, $number);
    }

    /**
     * Créer un nouveau prospect
     *
     * @param array $data Données du prospect
     * @return int|false ID du prospect créé ou false
     */
    public function create(array $data): int|false
    {
        // Générer le numéro de prospect
        $prospectNumber = $this->generateProspectNumber($data["country"]);
        
        $query = "INSERT INTO prospects (
                    prospect_number, civility, company_name,
                    vat_number, is_vat_liable,
                    email, phone, fax,
                    shop_type_id,
                    address, postal_code, city, country,
                    additional_info, language,
                    campaign_id,
                    ip_address, user_agent,
                    created_at
                ) VALUES (
                    :prospect_number, :civility, :company_name,
                    :vat_number, :is_vat_liable,
                    :email, :phone, :fax,
                    :shop_type_id,
                    :address, :postal_code, :city, :country,
                    :additional_info, :language,
                    :campaign_id,
                    :ip_address, :user_agent,
                    NOW()
                )";

        $params = [
            ":prospect_number" => $prospectNumber,
            ":civility" => $data["civility"],
            ":company_name" => $data["company_name"],
            ":vat_number" => $data["vat_number"],
            ":is_vat_liable" => $data["is_vat_liable"] ?? 1,
            ":email" => $data["email"],
            ":phone" => $data["phone"],
            ":fax" => $data["fax"] ?? null,
            ":shop_type_id" => $data["shop_type_id"],
            ":address" => $data["address"],
            ":postal_code" => $data["postal_code"],
            ":city" => $data["city"],
            ":country" => $data["country"],
            ":additional_info" => $data["additional_info"] ?? null,
            ":language" => $data["language"] ?? "fr",
            ":campaign_id" => $data["campaign_id"],
            ":ip_address" => $data["ip_address"] ?? null,
            ":user_agent" => $data["user_agent"] ?? null,
        ];

        try {
            $this->db->execute($query, $params);
            return (int) $this->db->lastInsertId();
        } catch (\PDOException $e) {
            error_log("Erreur création prospect: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Trouver un prospect par ID
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $query = "SELECT p.*, st.name as shop_type_name
                  FROM prospects p
                  LEFT JOIN shop_types st ON p.shop_type_id = st.id
                  WHERE p.id = :id";
        
        $result = $this->db->query($query, [":id" => $id]);
        return $result[0] ?? null;
    }

    /**
     * Trouver un prospect par numéro
     *
     * @param string $prospectNumber
     * @return array|null
     */
    public function findByNumber(string $prospectNumber): ?array
    {
        $query = "SELECT p.*, st.name as shop_type_name
                  FROM prospects p
                  LEFT JOIN shop_types st ON p.shop_type_id = st.id
                  WHERE p.prospect_number = :prospect_number";
        
        $result = $this->db->query($query, [":prospect_number" => $prospectNumber]);
        return $result[0] ?? null;
    }

    /**
     * Trouver les prospects d'une campagne
     *
     * @param int $campaignId
     * @return array
     */
    public function findByCampaign(int $campaignId): array
    {
        $query = "SELECT p.*, st.name as shop_type_name
                  FROM prospects p
                  LEFT JOIN shop_types st ON p.shop_type_id = st.id
                  WHERE p.campaign_id = :campaign_id
                  ORDER BY p.created_at DESC";
        
        return $this->db->query($query, [":campaign_id" => $campaignId]);
    }

    /**
     * Compter les prospects d'une campagne
     *
     * @param int $campaignId
     * @return int
     */
    public function countByCampaign(int $campaignId): int
    {
        $query = "SELECT COUNT(*) as total FROM prospects WHERE campaign_id = :campaign_id";
        $result = $this->db->query($query, [":campaign_id" => $campaignId]);
        return (int) ($result[0]["total"] ?? 0);
    }

    /**
     * Vérifier si un email existe déjà pour une campagne
     *
     * @param string $email
     * @param int $campaignId
     * @return bool
     */
    public function emailExistsForCampaign(string $email, int $campaignId): bool
    {
        $query = "SELECT COUNT(*) as total FROM prospects 
                  WHERE email = :email AND campaign_id = :campaign_id";
        $result = $this->db->query($query, [
            ":email" => $email,
            ":campaign_id" => $campaignId
        ]);
        return (int) ($result[0]["total"] ?? 0) > 0;
    }

    /**
     * Valider les données du prospect
     *
     * @param array $data
     * @param int|null $campaignId Pour vérifier email unique par campagne
     * @return array Erreurs de validation
     */
    public function validate(array $data, ?int $campaignId = null): array
    {
        $errors = [];

        // Champs obligatoires
        if (empty($data["civility"])) {
            $errors["civility"] = "La civilité est obligatoire";
        }
        if (empty($data["company_name"])) {
            $errors["company_name"] = "Le nom de l'entreprise est obligatoire";
        }
        if (empty($data["vat_number"])) {
            $errors["vat_number"] = "Le numéro de TVA est obligatoire";
        }
        if (empty($data["email"])) {
            $errors["email"] = "L'email est obligatoire";
        } elseif (!filter_var($data["email"], FILTER_VALIDATE_EMAIL)) {
            $errors["email"] = "L'email n'est pas valide";
        } elseif ($campaignId && $this->emailExistsForCampaign($data["email"], $campaignId)) {
            $errors["email"] = "Cet email a déjà été utilisé pour cette campagne";
        }
        if (empty($data["phone"])) {
            $errors["phone"] = "Le téléphone est obligatoire";
        }
        if (empty($data["shop_type_id"])) {
            $errors["shop_type_id"] = "Le type de magasin est obligatoire";
        }
        if (empty($data["address"])) {
            $errors["address"] = "L'adresse est obligatoire";
        }
        if (empty($data["postal_code"])) {
            $errors["postal_code"] = "Le code postal est obligatoire";
        }
        if (empty($data["city"])) {
            $errors["city"] = "La localité est obligatoire";
        }
        if (empty($data["country"]) || !in_array($data["country"], ["BE", "LU"])) {
            $errors["country"] = "Le pays doit être BE ou LU";
        }

        return $errors;
    }

    /**
     * Récupérer les prospects avec leurs commandes pour une campagne
     *
     * @param int $campaignId
     * @return array
     */
    public function getProspectsWithOrdersForCampaign(int $campaignId): array
    {
        $query = "SELECT 
                    p.*,
                    st.name as shop_type_name,
                    COUNT(o.id) as order_count,
                    SUM(o.total_items) as total_items
                  FROM prospects p
                  LEFT JOIN shop_types st ON p.shop_type_id = st.id
                  LEFT JOIN orders o ON o.prospect_id = p.id
                  WHERE p.campaign_id = :campaign_id
                  GROUP BY p.id
                  ORDER BY p.created_at DESC";
        
        return $this->db->query($query, [":campaign_id" => $campaignId]);
    }
}
