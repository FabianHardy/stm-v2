<?php
/**
 * ProspectController
 *
 * API pour la gestion des prospects et autocomplete codes postaux
 * Sprint 16 : Mode Prospect
 *
 * @package    App\Controllers\Api
 * @author     Claude AI
 * @version    1.0.0
 * @created    2026/01/09
 */

namespace App\Controllers\Api;

use App\Models\PostalCode;
use App\Models\ShopType;
use App\Models\Prospect;

class ProspectController
{
    private PostalCode $postalCodeModel;
    private ShopType $shopTypeModel;
    private Prospect $prospectModel;

    public function __construct()
    {
        $this->postalCodeModel = new PostalCode();
        $this->shopTypeModel = new ShopType();
        $this->prospectModel = new Prospect();
    }

    /**
     * API : Recherche autocomplete codes postaux
     * GET /api/postal-codes/search?q=1000&country=BE
     */
    public function searchPostalCodes(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        $search = $_GET['q'] ?? '';
        $country = $_GET['country'] ?? null;
        $limit = min((int) ($_GET['limit'] ?? 10), 50);

        if (strlen($search) < 2) {
            echo json_encode(['success' => false, 'message' => 'Minimum 2 caractères requis']);
            return;
        }

        try {
            $results = $this->postalCodeModel->search($search, $country, $limit);
            echo json_encode([
                'success' => true,
                'data' => $results
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            error_log("Erreur recherche codes postaux: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
        }
    }

    /**
     * API : Récupérer les localités pour un code postal
     * GET /api/postal-codes/localities?code=1000&country=BE
     */
    public function getLocalities(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        $code = $_GET['code'] ?? '';
        $country = $_GET['country'] ?? null;

        if (empty($code)) {
            echo json_encode(['success' => false, 'message' => 'Code postal requis']);
            return;
        }

        try {
            $localities = $this->postalCodeModel->getLocalitiesByCode($code, $country);
            echo json_encode([
                'success' => true,
                'data' => $localities
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            error_log("Erreur récupération localités: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
        }
    }

    /**
     * API : Récupérer tous les types de magasin
     * GET /api/shop-types
     */
    public function getShopTypes(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $types = $this->shopTypeModel->getAllActive();
            echo json_encode([
                'success' => true,
                'data' => $types
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            error_log("Erreur récupération types magasin: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
        }
    }

    /**
     * API : Valider les données d'un prospect (AJAX)
     * POST /api/prospects/validate
     */
    public function validateProspect(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        $data = $_POST;
        $campaignId = (int) ($_POST['campaign_id'] ?? 0);

        $errors = $this->prospectModel->validate($data, $campaignId);

        echo json_encode([
            'success' => empty($errors),
            'errors' => $errors
        ], JSON_UNESCAPED_UNICODE);
    }
}
