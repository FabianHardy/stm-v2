<?php
/**
 * Helper StatsAccess - STM v2
 *
 * Gestion centralisée des accès aux statistiques selon le rôle utilisateur.
 * Utilisé par StatsController et dashboard.php
 *
 * Règles d'accès :
 * - superadmin/admin : accès à tout
 * - createur : uniquement ses campagnes (via content_ownership)
 * - manager_reps : campagnes où ses reps ont des clients assignés
 * - rep : aucun accès aux stats
 *
 * @package STM
 * @created 2025/12/16
 */

namespace App\Helpers;

use Core\Database;
use Core\Session;

class StatsAccessHelper
{
    /**
     * Récupère les IDs des campagnes accessibles selon le rôle de l'utilisateur
     *
     * @return array|null Liste des IDs ou null si accès à tout
     */
    public static function getAccessibleCampaignIds(): ?array
    {
        $user = Session::get("user");
        $role = $user["role"] ?? "rep";
        $userId = $user["id"] ?? 0;

        // Superadmin et admin : accès à tout
        if (in_array($role, ["superadmin", "admin"])) {
            return null; // null = pas de filtre
        }

        $db = Database::getInstance();

        // Créateur : uniquement ses campagnes via content_ownership
        if ($role === "createur") {
            $query = "
                SELECT content_id
                FROM content_ownership
                WHERE user_id = :user_id
                AND content_type = 'campaign'
            ";
            $results = $db->query($query, [":user_id" => $userId]);
            return array_column($results, "content_id");
        }

        // Manager_reps : campagnes où ses reps ont des clients assignés
        if ($role === "manager_reps") {
            $managedReps = self::getManagedRepIds();

            if (empty($managedReps)) {
                return []; // Aucun rep géré = aucune campagne
            }

            $campaignIds = [];

            try {
                $externalDb = \Core\ExternalDatabase::getInstance();

                foreach ($managedReps as $rep) {
                    $repId = $rep["rep_id"];
                    $repCountry = $rep["rep_country"];
                    $table = $repCountry === "BE" ? "BE_CLL" : "LU_CLL";

                    // Récupérer les numéros clients de ce rep (depuis DB externe)
                    $clientsQuery = "SELECT CLL_NCLIXX FROM {$table} WHERE IDE_REP = :rep_id";
                    $clients = $externalDb->query($clientsQuery, [":rep_id" => $repId]);
                    $customerNumbers = array_column($clients, "CLL_NCLIXX");

                    if (!empty($customerNumbers)) {
                        // Campagnes en mode manual avec ces clients
                        $placeholders = implode(",", array_fill(0, count($customerNumbers), "?"));
                        $manualQuery = "
                            SELECT DISTINCT cc.campaign_id
                            FROM campaign_customers cc
                            INNER JOIN campaigns c ON cc.campaign_id = c.id
                            WHERE cc.customer_number IN ({$placeholders})
                            AND cc.country = ?
                        ";
                        $params = array_merge($customerNumbers, [$repCountry]);
                        $manualCampaigns = $db->query($manualQuery, $params);
                        $campaignIds = array_merge($campaignIds, array_column($manualCampaigns, "campaign_id"));

                        // Campagnes en mode automatic/protected pour ce pays
                        $autoQuery = "
                            SELECT id FROM campaigns
                            WHERE customer_assignment_mode IN ('automatic', 'protected')
                            AND (country = ? OR country = 'BOTH')
                        ";
                        $autoCampaigns = $db->query($autoQuery, [$repCountry]);
                        $campaignIds = array_merge($campaignIds, array_column($autoCampaigns, "id"));
                    }
                }
            } catch (\Exception $e) {
                error_log("StatsAccessHelper::getAccessibleCampaignIds error: " . $e->getMessage());
            }

            return array_unique($campaignIds);
        }

        // Rep : aucune campagne (pas d'accès aux stats)
        return [];
    }

    /**
     * Récupère les IDs des représentants gérés par le manager connecté
     *
     * @return array Liste des [rep_id, rep_country]
     */
    public static function getManagedRepIds(): array
    {
        $user = Session::get("user");
        $userId = $user["id"] ?? 0;

        $db = Database::getInstance();

        $query = "
            SELECT rep_id, rep_country
            FROM users
            WHERE manager_id = :manager_id
            AND role = 'rep'
            AND rep_id IS NOT NULL
            AND is_active = 1
        ";

        return $db->query($query, [":manager_id" => $userId]);
    }

    /**
     * Vérifie si l'utilisateur a accès à une campagne spécifique
     *
     * @param int $campaignId ID de la campagne
     * @return bool True si accès autorisé
     */
    public static function canAccessCampaign(int $campaignId): bool
    {
        $accessibleIds = self::getAccessibleCampaignIds();

        // null = accès à tout
        if ($accessibleIds === null) {
            return true;
        }

        return in_array($campaignId, $accessibleIds);
    }

    /**
     * Filtre une liste de campagnes selon l'accès de l'utilisateur
     *
     * @param array $campaigns Liste des campagnes
     * @return array Liste filtrée
     */
    public static function filterCampaignsList(array $campaigns): array
    {
        $accessibleIds = self::getAccessibleCampaignIds();

        // null = accès à tout
        if ($accessibleIds === null) {
            return $campaigns;
        }

        return array_values(array_filter($campaigns, function ($campaign) use ($accessibleIds) {
            return in_array($campaign["id"], $accessibleIds);
        }));
    }

    /**
     * Filtre les représentants selon le rôle de l'utilisateur
     *
     * @param array $reps Liste des représentants
     * @return array Liste filtrée
     */
    public static function filterRepsList(array $reps): array
    {
        $user = Session::get("user");
        $role = $user["role"] ?? "rep";

        // Superadmin, admin, createur : tous les reps
        if (in_array($role, ["superadmin", "admin", "createur"])) {
            return $reps;
        }

        // Manager_reps : uniquement ses reps
        if ($role === "manager_reps") {
            $managedReps = self::getManagedRepIds();
            $managedRepIds = array_map(function ($r) {
                return $r["rep_id"] . "_" . $r["rep_country"];
            }, $managedReps);

            return array_values(array_filter($reps, function ($rep) use ($managedRepIds) {
                $repKey = $rep["id"] . "_" . $rep["country"];
                return in_array($repKey, $managedRepIds);
            }));
        }

        return [];
    }

    /**
     * Génère la clause SQL IN pour filtrer par campagnes accessibles
     *
     * @param string $columnName Nom de la colonne (ex: "o.campaign_id", "c.id")
     * @param array &$params Référence vers les paramètres de la requête
     * @return string Clause SQL vide si accès à tout, ou "AND column IN (...)"
     */
    public static function getCampaignFilterSQL(string $columnName, array &$params): string
    {
        $accessibleIds = self::getAccessibleCampaignIds();

        // null = accès à tout, pas de filtre
        if ($accessibleIds === null) {
            return "";
        }

        // Aucune campagne accessible = bloquer tout
        if (empty($accessibleIds)) {
            return " AND 1 = 0"; // Retourne toujours faux
        }

        // Générer la clause IN
        $placeholders = [];
        foreach ($accessibleIds as $i => $id) {
            $key = ":accessible_campaign_{$i}";
            $placeholders[] = $key;
            $params[$key] = $id;
        }

        return " AND {$columnName} IN (" . implode(",", $placeholders) . ")";
    }

    /**
     * Vérifie si l'utilisateur a un accès complet aux stats (admin/superadmin)
     *
     * @return bool
     */
    public static function hasFullAccess(): bool
    {
        $user = Session::get("user");
        $role = $user["role"] ?? "rep";

        return in_array($role, ["superadmin", "admin"]);
    }

    /**
     * Vérifie si l'utilisateur peut voir des stats (pas un simple rep)
     *
     * @return bool
     */
    public static function canViewStats(): bool
    {
        $user = Session::get("user");
        $role = $user["role"] ?? "rep";

        // Rep n'a pas accès aux stats
        return $role !== "rep";
    }

    /**
     * Récupère le rôle de l'utilisateur connecté
     *
     * @return string
     */
    public static function getUserRole(): string
    {
        $user = Session::get("user");
        return $user["role"] ?? "rep";
    }
}