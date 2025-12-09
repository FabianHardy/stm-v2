<?php
/**
 * AgentTools.php
 *
 * Définition et exécution des tools disponibles pour l'agent
 * Chaque tool correspond à une fonction que l'agent peut appeler
 *
 * @created  2025/12/09
 * @package  STM Agent
 */

namespace App\Agent;

use Core\Database;

class AgentTools
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Obtenir la définition des tools au format OpenAI
     *
     * @return array Liste des tools
     */
    public function getToolsDefinition(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'list_campaigns',
                    'description' => 'Liste toutes les campagnes disponibles avec leur statut. Utiliser pour savoir quelles campagnes existent.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'country' => [
                                'type' => 'string',
                                'description' => 'Filtrer par pays (BE ou LU). Optionnel.',
                                'enum' => ['BE', 'LU']
                            ],
                            'status' => [
                                'type' => 'string',
                                'description' => 'Filtrer par statut. Optionnel.',
                                'enum' => ['draft', 'scheduled', 'active', 'ended', 'cancelled']
                            ]
                        ],
                        'required' => []
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_campaign_stats',
                    'description' => 'Obtenir les statistiques détaillées d\'une campagne: nombre de commandes, clients, promos vendues, taux de participation.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'campaign_name' => [
                                'type' => 'string',
                                'description' => 'Nom ou partie du nom de la campagne à rechercher'
                            ],
                            'campaign_id' => [
                                'type' => 'integer',
                                'description' => 'ID de la campagne (si connu)'
                            ]
                        ],
                        'required' => []
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_top_products',
                    'description' => 'Obtenir le classement des produits les plus vendus d\'une campagne',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'campaign_name' => [
                                'type' => 'string',
                                'description' => 'Nom ou partie du nom de la campagne'
                            ],
                            'campaign_id' => [
                                'type' => 'integer',
                                'description' => 'ID de la campagne (si connu)'
                            ],
                            'limit' => [
                                'type' => 'integer',
                                'description' => 'Nombre de produits à retourner (défaut: 10)'
                            ]
                        ],
                        'required' => []
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_rep_stats',
                    'description' => 'Obtenir les statistiques par représentant pour une campagne: clients, commandes, promos vendues par rep',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'campaign_name' => [
                                'type' => 'string',
                                'description' => 'Nom ou partie du nom de la campagne'
                            ],
                            'campaign_id' => [
                                'type' => 'integer',
                                'description' => 'ID de la campagne (si connu)'
                            ],
                            'limit' => [
                                'type' => 'integer',
                                'description' => 'Nombre de représentants à retourner (défaut: 10)'
                            ]
                        ],
                        'required' => []
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'compare_campaigns',
                    'description' => 'Comparer les performances de plusieurs campagnes entre elles',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'campaign_names' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                                'description' => 'Liste des noms de campagnes à comparer'
                            ]
                        ],
                        'required' => ['campaign_names']
                    ]
                ]
            ]
        ];
    }

    /**
     * Exécuter un tool avec ses arguments
     *
     * @param string $toolName Nom du tool
     * @param array $arguments Arguments du tool
     * @return array Résultat de l'exécution
     */
    public function executeTool(string $toolName, array $arguments): array
    {
        try {
            switch ($toolName) {
                case 'list_campaigns':
                    return $this->listCampaigns($arguments);

                case 'get_campaign_stats':
                    return $this->getCampaignStats($arguments);

                case 'get_top_products':
                    return $this->getTopProducts($arguments);

                case 'get_rep_stats':
                    return $this->getRepStats($arguments);

                case 'compare_campaigns':
                    return $this->compareCampaigns($arguments);

                default:
                    return ['error' => "Tool inconnu: {$toolName}"];
            }
        } catch (\Exception $e) {
            error_log("AgentTools error ({$toolName}): " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Liste des campagnes
     */
    private function listCampaigns(array $args): array
    {
        $sql = "SELECT id, name, country, status, start_date, end_date FROM campaigns WHERE 1=1";
        $params = [];

        if (!empty($args['country'])) {
            $sql .= " AND country = :country";
            $params[':country'] = $args['country'];
        }

        if (!empty($args['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $args['status'];
        }

        $sql .= " ORDER BY start_date DESC LIMIT 20";

        $campaigns = $this->db->query($sql, $params);

        $statusLabels = [
            'draft' => 'Brouillon',
            'scheduled' => 'Programmée',
            'active' => 'En cours',
            'ended' => 'Terminée',
            'cancelled' => 'Annulée'
        ];

        $result = [];
        foreach ($campaigns as $c) {
            $result[] = [
                'id' => $c['id'],
                'nom' => $c['name'],
                'pays' => $c['country'],
                'statut' => $statusLabels[$c['status']] ?? $c['status'],
                'debut' => date('d/m/Y', strtotime($c['start_date'])),
                'fin' => date('d/m/Y', strtotime($c['end_date']))
            ];
        }

        return [
            'campagnes' => $result,
            'total' => count($result)
        ];
    }

    /**
     * Stats d'une campagne
     */
    private function getCampaignStats(array $args): array
    {
        // Trouver la campagne
        $campaign = $this->findCampaign($args);

        if (!$campaign) {
            return ['error' => 'Campagne non trouvée'];
        }

        $campaignId = $campaign['id'];

        // Nombre de commandes
        $ordersResult = $this->db->query(
            "SELECT COUNT(*) as total, SUM(total_quantity) as quantity
             FROM orders WHERE campaign_id = :id",
            [':id' => $campaignId]
        );
        $orders = $ordersResult[0] ?? ['total' => 0, 'quantity' => 0];

        // Clients ayant commandé
        $customersResult = $this->db->query(
            "SELECT COUNT(DISTINCT customer_id) as total
             FROM orders WHERE campaign_id = :id",
            [':id' => $campaignId]
        );
        $customersOrdered = $customersResult[0]['total'] ?? 0;

        // Clients éligibles
        $eligibleResult = $this->db->query(
            "SELECT COUNT(*) as total FROM campaign_customers WHERE campaign_id = :id",
            [':id' => $campaignId]
        );
        $eligibleCustomers = $eligibleResult[0]['total'] ?? 0;

        // Nombre de produits
        $productsResult = $this->db->query(
            "SELECT COUNT(*) as total FROM products WHERE campaign_id = :id AND is_active = 1",
            [':id' => $campaignId]
        );
        $productsCount = $productsResult[0]['total'] ?? 0;

        // Calcul taux de participation
        $participationRate = $eligibleCustomers > 0
            ? round(($customersOrdered / $eligibleCustomers) * 100, 1)
            : 0;

        // Moyenne par commande
        $avgPerOrder = $orders['total'] > 0
            ? round($orders['quantity'] / $orders['total'], 1)
            : 0;

        return [
            'campagne' => $campaign['name'],
            'pays' => $campaign['country'],
            'statut' => $campaign['status'],
            'periode' => date('d/m/Y', strtotime($campaign['start_date'])) . ' - ' . date('d/m/Y', strtotime($campaign['end_date'])),
            'stats' => [
                'clients_eligibles' => (int) $eligibleCustomers,
                'clients_ayant_commande' => (int) $customersOrdered,
                'taux_participation' => $participationRate . '%',
                'nombre_commandes' => (int) $orders['total'],
                'promos_vendues' => (int) $orders['quantity'],
                'moyenne_par_commande' => $avgPerOrder,
                'nombre_produits' => (int) $productsCount
            ]
        ];
    }

    /**
     * Top produits d'une campagne
     */
    private function getTopProducts(array $args): array
    {
        $campaign = $this->findCampaign($args);

        if (!$campaign) {
            return ['error' => 'Campagne non trouvée'];
        }

        $limit = min($args['limit'] ?? 10, 50);

        $products = $this->db->query(
            "SELECT
                p.name_fr as nom,
                p.product_code as code,
                COUNT(DISTINCT ol.order_id) as commandes,
                SUM(ol.quantity) as quantite_vendue
             FROM products p
             LEFT JOIN order_lines ol ON p.id = ol.product_id
             WHERE p.campaign_id = :campaign_id AND p.is_active = 1
             GROUP BY p.id
             ORDER BY quantite_vendue DESC
             LIMIT {$limit}",
            [':campaign_id' => $campaign['id']]
        );

        $result = [];
        $rank = 0;
        foreach ($products as $p) {
            $rank++;
            $result[] = [
                'rang' => $rank,
                'nom' => $p['nom'],
                'code' => $p['code'],
                'commandes' => (int) $p['commandes'],
                'quantite_vendue' => (int) $p['quantite_vendue']
            ];
        }

        return [
            'campagne' => $campaign['name'],
            'top_produits' => $result
        ];
    }

    /**
     * Stats par représentant
     */
    private function getRepStats(array $args): array
    {
        $campaign = $this->findCampaign($args);

        if (!$campaign) {
            return ['error' => 'Campagne non trouvée'];
        }

        $limit = min($args['limit'] ?? 10, 50);

        // Stats par rep depuis les commandes
        $reps = $this->db->query(
            "SELECT
                o.rep_id,
                COUNT(DISTINCT o.id) as commandes,
                COUNT(DISTINCT o.customer_id) as clients,
                SUM(o.total_quantity) as promos_vendues
             FROM orders o
             WHERE o.campaign_id = :campaign_id AND o.rep_id IS NOT NULL
             GROUP BY o.rep_id
             ORDER BY promos_vendues DESC
             LIMIT {$limit}",
            [':campaign_id' => $campaign['id']]
        );

        // Récupérer les noms des reps depuis la base externe si possible
        $result = [];
        $rank = 0;
        foreach ($reps as $r) {
            $rank++;
            $result[] = [
                'rang' => $rank,
                'rep_id' => $r['rep_id'],
                'clients' => (int) $r['clients'],
                'commandes' => (int) $r['commandes'],
                'promos_vendues' => (int) $r['promos_vendues']
            ];
        }

        return [
            'campagne' => $campaign['name'],
            'representants' => $result
        ];
    }

    /**
     * Comparer plusieurs campagnes
     */
    private function compareCampaigns(array $args): array
    {
        $names = $args['campaign_names'] ?? [];

        if (empty($names)) {
            return ['error' => 'Aucune campagne spécifiée'];
        }

        $comparisons = [];

        foreach ($names as $name) {
            $stats = $this->getCampaignStats(['campaign_name' => $name]);

            if (!isset($stats['error'])) {
                $comparisons[] = [
                    'campagne' => $stats['campagne'],
                    'pays' => $stats['pays'],
                    'commandes' => $stats['stats']['nombre_commandes'],
                    'promos_vendues' => $stats['stats']['promos_vendues'],
                    'taux_participation' => $stats['stats']['taux_participation']
                ];
            }
        }

        return [
            'comparaison' => $comparisons,
            'nb_campagnes' => count($comparisons)
        ];
    }

    /**
     * Trouver une campagne par nom ou ID
     */
    private function findCampaign(array $args): ?array
    {
        // Par ID
        if (!empty($args['campaign_id'])) {
            $result = $this->db->query(
                "SELECT * FROM campaigns WHERE id = :id",
                [':id' => $args['campaign_id']]
            );
            return $result[0] ?? null;
        }

        // Par nom (recherche partielle)
        if (!empty($args['campaign_name'])) {
            $result = $this->db->query(
                "SELECT * FROM campaigns WHERE name LIKE :name ORDER BY start_date DESC LIMIT 1",
                [':name' => '%' . $args['campaign_name'] . '%']
            );
            return $result[0] ?? null;
        }

        return null;
    }
}