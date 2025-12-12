<?php
/**
 * Contrôleur AgentConfigController
 *
 * Gestion de la configuration de l'Agent STM
 * Accessible depuis /admin/settings/agent
 * Les configs sensibles (IA) sont réservées aux super admins
 *
 * @created  2025/12/11
 * @modified 2025/12/12 - Ajout gestion des Tools
 */

namespace App\Controllers;

use Core\Auth;
use App\Models\AgentConfig;
use App\Models\AgentTool;

class AgentConfigController
{
    private AgentConfig $config;
    private AgentTool $tools;
    private bool $isSuperAdmin;

    public function __construct()
    {
        // Vérifier l'authentification
        if (!Auth::check()) {
            header('Location: /stm/login');
            exit;
        }

        $this->config = AgentConfig::getInstance();
        $this->tools = AgentTool::getInstance();

        // Vérifier si super admin (role_id = 1 ou role = 'super_admin')
        $this->isSuperAdmin = $this->checkSuperAdmin();
    }

    /**
     * Vérifier si l'utilisateur est super admin
     */
    private function checkSuperAdmin(): bool
    {
        // Récupérer le rôle depuis $_SESSION['user']['role']
        $role = $_SESSION['user']['role'] ?? $_SESSION['role'] ?? null;
        $roleId = $_SESSION['user']['role_id'] ?? $_SESSION['role_id'] ?? null;

        // Vérification souple
        $isSuperAdmin = $roleId == 1
            || strtolower($role ?? '') === 'super_admin'
            || strtolower($role ?? '') === 'superadmin';

        return $isSuperAdmin;
    }

    /**
     * Afficher la page de configuration de l'Agent
     */
    public function index(): void
    {
        // Charger les configs (avec ou sans sensibles selon le rôle)
        $configs = $this->config->getAll($this->isSuperAdmin);

        // Organiser par clé pour l'affichage
        $configsByKey = [];
        foreach ($configs as $config) {
            $configsByKey[$config['key']] = $config;
        }

        // Labels et descriptions pour l'interface - Prompt
        $promptFields = [
            'general_instructions' => [
                'label' => 'Instructions générales',
                'description' => 'Définit le ton, le style et le comportement global de l\'assistant.',
                'placeholder' => 'Ex: Sois toujours positif et encourage l\'utilisateur. Réponds de manière concise.',
                'icon' => 'fa-cog',
                'rows' => 4
            ],
            'business_vocabulary' => [
                'label' => 'Vocabulaire métier',
                'description' => 'Termes spécifiques à Trendy Foods que l\'assistant doit connaître.',
                'placeholder' => 'Ex: Un "cluster" = groupe de clients. "V" = vente classique, "W" = prêt.',
                'icon' => 'fa-book',
                'rows' => 6
            ],
            'response_rules' => [
                'label' => 'Règles de réponse',
                'description' => 'Contraintes et règles que l\'assistant doit respecter dans ses réponses.',
                'placeholder' => 'Ex: Ne jamais afficher les numéros clients. Toujours préciser le pays.',
                'icon' => 'fa-list-check',
                'rows' => 5
            ],
            'qa_examples' => [
                'label' => 'Exemples Q/R',
                'description' => 'Questions types et réponses attendues pour guider l\'assistant.',
                'placeholder' => "Ex:\nQ: C'est quoi un rep ?\nR: Un représentant commercial qui gère des clients.",
                'icon' => 'fa-comments',
                'rows' => 8
            ]
        ];

        // Providers IA disponibles
        $aiProviders = [
            'openai' => [
                'name' => 'OpenAI',
                'icon' => 'fa-brain',
                'color' => 'emerald',
                'models' => ['gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo', 'gpt-3.5-turbo'],
                'description' => 'ChatGPT - Le plus populaire'
            ],
            'claude' => [
                'name' => 'Anthropic Claude',
                'icon' => 'fa-message',
                'color' => 'orange',
                'models' => ['claude-3-5-sonnet-20241022', 'claude-3-opus-20240229', 'claude-3-haiku-20240307'],
                'description' => 'Claude - Très bon en raisonnement'
            ],
            'ollama' => [
                'name' => 'Ollama (Local)',
                'icon' => 'fa-server',
                'color' => 'purple',
                'models' => ['llama3', 'llama3.1', 'mistral', 'codellama', 'mixtral'],
                'description' => 'IA locale - Pas de données envoyées'
            ]
        ];

        $isSuperAdmin = $this->isSuperAdmin;
        $fields = $promptFields;

        // Charger les tools
        $agentTools = $this->tools->getAll();
        $toolsStats = $this->tools->getStats();

        require __DIR__ . '/../Views/admin/settings/agent.php';
    }

    /**
     * Sauvegarder la configuration
     */
    public function save(): void
    {
        // Vérifier le token CSRF
        if (!isset($_POST['_token']) || $_POST['_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Token CSRF invalide'];
            header('Location: /stm/admin/settings/agent');
            exit;
        }

        // Configs prompt (accessibles à tous les admins)
        $promptKeys = ['general_instructions', 'business_vocabulary', 'response_rules', 'qa_examples'];
        $configs = [];

        foreach ($promptKeys as $key) {
            $configs[$key] = [
                'value' => trim($_POST[$key] ?? ''),
                'is_active' => isset($_POST[$key . '_active'])
            ];
        }

        // Configs IA (super admin uniquement)
        if ($this->isSuperAdmin) {
            $aiKeys = ['ai_provider', 'ai_model', 'ai_api_key', 'ai_api_url', 'ai_temperature'];
            foreach ($aiKeys as $key) {
                // Ne pas écraser la clé API si le champ est vide (masqué)
                if ($key === 'ai_api_key' && empty($_POST[$key])) {
                    continue;
                }
                $configs[$key] = [
                    'value' => trim($_POST[$key] ?? ''),
                    'is_active' => true
                ];
            }
        }

        if ($this->config->setMultiple($configs)) {
            $_SESSION['flash'] = [
                'type' => 'success',
                'message' => 'Configuration de l\'Agent sauvegardée avec succès !'
            ];
        } else {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Erreur lors de la sauvegarde de la configuration.'
            ];
        }

        header('Location: /stm/admin/settings/agent');
        exit;
    }

    /**
     * Réinitialiser aux valeurs par défaut
     */
    public function reset(): void
    {
        // Vérifier le token CSRF
        if (!isset($_POST['_token']) || $_POST['_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Token CSRF invalide'];
            header('Location: /stm/admin/settings/agent');
            exit;
        }

        $defaults = [
            'general_instructions' => [
                'value' => 'Tu es un assistant professionnel et amical. Réponds de manière concise et précise.',
                'is_active' => true
            ],
            'business_vocabulary' => [
                'value' => "Vocabulaire métier Trendy Foods :\n- Rep / Représentant : Commercial qui gère un portefeuille de clients\n- Cluster : Groupe géographique de clients\n- V (Vente) : Commande classique\n- W (Prêt) : Commande en consignation\n- Quota : Limite de quantité par produit",
                'is_active' => true
            ],
            'response_rules' => [
                'value' => "Règles de réponse :\n- Ne jamais afficher les numéros clients aux utilisateurs\n- Arrondir les montants à 2 décimales\n- Toujours préciser le pays (BE/LU) quand pertinent\n- Proposer des options si la question est ambiguë",
                'is_active' => true
            ],
            'qa_examples' => [
                'value' => "Exemples de questions/réponses :\n\nQ: C'est quoi un cluster ?\nR: Un cluster est un regroupement géographique de clients gérés par un même représentant.\n\nQ: Différence entre V et W ?\nR: V = Vente classique, W = Prêt/Consignation (le client peut retourner les invendus).",
                'is_active' => true
            ]
        ];

        if ($this->config->setMultiple($defaults)) {
            $_SESSION['flash'] = [
                'type' => 'success',
                'message' => 'Configuration réinitialisée aux valeurs par défaut.'
            ];
        } else {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Erreur lors de la réinitialisation.'
            ];
        }

        header('Location: /stm/admin/settings/agent');
        exit;
    }

    /**
     * Tester le prompt généré (API endpoint)
     */
    public function preview(): void
    {
        header('Content-Type: application/json');

        $customPrompt = $this->config->buildCustomPrompt();

        echo json_encode([
            'success' => true,
            'prompt' => $customPrompt,
            'length' => strlen($customPrompt),
            'sections' => [
                'general_instructions' => $this->config->isActive('general_instructions'),
                'business_vocabulary' => $this->config->isActive('business_vocabulary'),
                'response_rules' => $this->config->isActive('response_rules'),
                'qa_examples' => $this->config->isActive('qa_examples')
            ]
        ]);
        exit;
    }

    /**
     * Tester la connexion au fournisseur IA (super admin uniquement)
     */
    public function testConnection(): void
    {
        header('Content-Type: application/json');

        if (!$this->isSuperAdmin) {
            echo json_encode(['success' => false, 'error' => 'Accès refusé']);
            exit;
        }

        try {
            $aiConfig = $this->config->getAIConfig();
            $provider = $aiConfig['provider'];
            $model = $aiConfig['model'];
            $apiKey = $aiConfig['api_key'];
            $apiUrl = $aiConfig['api_url'];

            // Test selon le provider
            switch ($provider) {
                case 'openai':
                    $result = $this->testOpenAI($apiKey, $model);
                    break;
                case 'claude':
                    $result = $this->testClaude($apiKey, $model);
                    break;
                case 'ollama':
                    $result = $this->testOllama($apiUrl, $model);
                    break;
                default:
                    $result = ['success' => false, 'error' => 'Provider inconnu'];
            }

            echo json_encode($result);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Tester OpenAI
     */
    private function testOpenAI(string $apiKey, string $model): array
    {
        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'Clé API manquante'];
        }

        $ch = curl_init('https://api.openai.com/v1/models/' . $model);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey
            ],
            CURLOPT_TIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return ['success' => true, 'message' => "Connexion OpenAI OK - Modèle {$model} disponible"];
        } else {
            return ['success' => false, 'error' => "Erreur HTTP {$httpCode}"];
        }
    }

    /**
     * Tester Claude/Anthropic
     */
    private function testClaude(string $apiKey, string $model): array
    {
        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'Clé API manquante'];
        }

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => $model,
                'max_tokens' => 10,
                'messages' => [['role' => 'user', 'content' => 'Test']]
            ]),
            CURLOPT_TIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return ['success' => true, 'message' => "Connexion Claude OK - Modèle {$model} disponible"];
        } else {
            $data = json_decode($response, true);
            $error = $data['error']['message'] ?? "Erreur HTTP {$httpCode}";
            return ['success' => false, 'error' => $error];
        }
    }

    /**
     * Tester Ollama (local)
     */
    private function testOllama(string $apiUrl, string $model): array
    {
        $url = rtrim($apiUrl ?: 'http://localhost:11434', '/') . '/api/tags';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            $models = array_column($data['models'] ?? [], 'name');

            if (in_array($model, $models)) {
                return ['success' => true, 'message' => "Connexion Ollama OK - Modèle {$model} disponible"];
            } else {
                return [
                    'success' => false,
                    'error' => "Modèle {$model} non trouvé. Modèles disponibles : " . implode(', ', $models)
                ];
            }
        } else {
            return ['success' => false, 'error' => $error ?: "Impossible de contacter Ollama sur {$apiUrl}"];
        }
    }

    // ============================================
    // GESTION DES TOOLS
    // ============================================

    /**
     * API : Lister les tools (JSON)
     */
    public function listTools(): void
    {
        header('Content-Type: application/json');

        try {
            $tools = $this->tools->getAll();
            $stats = $this->tools->getStats();

            echo json_encode([
                'success' => true,
                'tools' => $tools,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * API : Toggle (activer/désactiver) un tool
     */
    public function toggleTool(): void
    {
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);
        $toolId = $data['id'] ?? null;
        $active = $data['active'] ?? null;

        if (!$toolId) {
            echo json_encode(['success' => false, 'error' => 'ID manquant']);
            return;
        }

        try {
            if ($active !== null) {
                // Définir explicitement l'état
                if ($active) {
                    $result = $this->tools->activate((int)$toolId);
                } else {
                    $result = $this->tools->deactivate((int)$toolId);
                }
            } else {
                // Toggle
                $result = $this->tools->toggle((int)$toolId);
            }

            $tool = $this->tools->getById((int)$toolId);

            echo json_encode([
                'success' => $result,
                'tool' => $tool,
                'message' => $result ? 'Tool mis à jour' : 'Erreur lors de la mise à jour'
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * API : Créer un tool via IA
     */
    public function createTool(): void
    {
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);
        $description = $data['description'] ?? '';

        if (empty($description)) {
            echo json_encode(['success' => false, 'error' => 'Description manquante']);
            return;
        }

        try {
            // Générer le tool via IA
            $generatedTool = $this->generateToolWithAI($description);

            if (!$generatedTool) {
                echo json_encode(['success' => false, 'error' => 'Impossible de générer le tool']);
                return;
            }

            // Vérifier si le nom existe déjà
            $existing = $this->tools->getByName($generatedTool['name']);
            if ($existing) {
                echo json_encode(['success' => false, 'error' => 'Un tool avec ce nom existe déjà']);
                return;
            }

            // Créer le tool
            $userId = $_SESSION['user']['id'] ?? null;
            $generatedTool['created_by'] = $userId;
            $generatedTool['is_system'] = 0;
            $generatedTool['is_active'] = 1;

            $toolId = $this->tools->create($generatedTool);

            if ($toolId) {
                $tool = $this->tools->getById($toolId);
                echo json_encode([
                    'success' => true,
                    'tool' => $tool,
                    'message' => "Tool '{$generatedTool['display_name']}' créé avec succès"
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Erreur lors de la création']);
            }
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * API : Mettre à jour un tool
     */
    public function updateTool(): void
    {
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);
        $toolId = $data['id'] ?? null;

        if (!$toolId) {
            echo json_encode(['success' => false, 'error' => 'ID manquant']);
            return;
        }

        // Vérifier que ce n'est pas un tool système
        $tool = $this->tools->getById((int)$toolId);
        if (!$tool) {
            echo json_encode(['success' => false, 'error' => 'Tool non trouvé']);
            return;
        }
        if ($tool['is_system']) {
            echo json_encode(['success' => false, 'error' => 'Impossible de modifier un tool système']);
            return;
        }

        try {
            $updateData = [];
            if (isset($data['display_name'])) $updateData['display_name'] = $data['display_name'];
            if (isset($data['description'])) $updateData['description'] = $data['description'];
            if (isset($data['parameters'])) $updateData['parameters'] = $data['parameters'];

            $result = $this->tools->update((int)$toolId, $updateData);
            $tool = $this->tools->getById((int)$toolId);

            echo json_encode([
                'success' => $result,
                'tool' => $tool,
                'message' => 'Tool mis à jour'
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * API : Supprimer un tool
     */
    public function deleteTool(): void
    {
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);
        $toolId = $data['id'] ?? null;

        if (!$toolId) {
            echo json_encode(['success' => false, 'error' => 'ID manquant']);
            return;
        }

        // Vérifier que ce n'est pas un tool système
        $tool = $this->tools->getById((int)$toolId);
        if (!$tool) {
            echo json_encode(['success' => false, 'error' => 'Tool non trouvé']);
            return;
        }
        if ($tool['is_system']) {
            echo json_encode(['success' => false, 'error' => 'Impossible de supprimer un tool système']);
            return;
        }

        try {
            $result = $this->tools->delete((int)$toolId);

            echo json_encode([
                'success' => $result,
                'message' => 'Tool supprimé'
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Générer un tool via l'IA
     */
    private function generateToolWithAI(string $description): ?array
    {
        // Récupérer la config IA
        $provider = $this->config->get('ai_provider', 'openai');
        $model = $this->config->get('ai_model', 'gpt-4o');
        $apiKey = $this->config->get('ai_api_key', '');

        // Fallback sur .env si pas de clé en DB
        if (empty($apiKey)) {
            $apiKey = $_ENV['OPENAI_API_KEY'] ?? getenv('OPENAI_API_KEY') ?? '';
        }

        if (empty($apiKey)) {
            return null;
        }

        $prompt = <<<PROMPT
Tu es un expert en création de tools pour un système d'IA conversationnelle.
Génère un tool basé sur cette description : "{$description}"

Le tool doit permettre d'interagir avec une base de données de campagnes promotionnelles (STM).

Réponds UNIQUEMENT avec un JSON valide (sans markdown, sans backticks) avec cette structure :
{
    "name": "nom_technique_snake_case",
    "display_name": "Nom Affiché",
    "description": "Description détaillée de ce que fait le tool pour l'IA",
    "parameters": {
        "type": "object",
        "properties": {
            "param1": {
                "type": "string",
                "description": "Description du paramètre"
            }
        },
        "required": []
    }
}

Règles :
- name : snake_case, court, unique
- display_name : Titre lisible en français
- description : Détaillée pour l'IA (ce que le tool fait, quand l'utiliser)
- parameters : JSON Schema valide pour les paramètres
PROMPT;

        // Appel API OpenAI
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.openai.com/v1/chat/completions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.3
            ]),
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("generateToolWithAI: API error - HTTP {$httpCode}");
            return null;
        }

        $data = json_decode($response, true);
        $content = $data['choices'][0]['message']['content'] ?? '';

        // Parser le JSON généré
        $content = trim($content);
        // Supprimer les éventuels backticks markdown
        $content = preg_replace('/^```json\s*/i', '', $content);
        $content = preg_replace('/\s*```$/i', '', $content);

        $tool = json_decode($content, true);

        if (!$tool || !isset($tool['name']) || !isset($tool['display_name'])) {
            error_log("generateToolWithAI: Invalid JSON response");
            return null;
        }

        // Formater les parameters en JSON string
        $tool['parameters'] = json_encode($tool['parameters'] ?? []);

        return $tool;
    }
}