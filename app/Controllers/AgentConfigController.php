<?php
/**
 * Contrôleur AgentConfigController
 *
 * Gestion de la configuration de l'Agent STM
 * Accessible depuis /admin/settings/agent
 * Les configs sensibles (IA) sont réservées aux super admins
 *
 * @created  2025/12/11
 * @modified 2025/12/11 - Ajout config fournisseur IA
 */

namespace App\Controllers;

use Core\Auth;
use App\Models\AgentConfig;

class AgentConfigController
{
    private AgentConfig $config;
    private bool $isSuperAdmin;

    public function __construct()
    {
        // Vérifier l'authentification
        if (!Auth::check()) {
            header('Location: /stm/login');
            exit;
        }

        $this->config = AgentConfig::getInstance();

        // Vérifier si super admin (role_id = 1 ou role = 'super_admin')
        $this->isSuperAdmin = $this->checkSuperAdmin();
    }

    /**
     * Vérifier si l'utilisateur est super admin
     */
    private function checkSuperAdmin(): bool
    {
        // Adapter selon votre système de rôles
        $roleId = $_SESSION['role_id'] ?? null;
        $role = $_SESSION['role'] ?? null;

        return $roleId === 1 || $role === 'super_admin' || $role === 'superadmin';
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
}