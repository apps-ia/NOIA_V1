<?php
/**
 * NOIA - API Proxy (Direct OpenAI Integration)
 * Version: 2.0.0
 *
 * Remplace Make.com par un appel direct à l'API OpenAI
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/web_search.php';
require_once __DIR__ . '/embeddings.php';

// CORS
if (ALLOWED_ORIGINS !== '*') {
    header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGINS);
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Gestion des requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée. Utilisez POST.']);
    exit;
}

/**
 * Rate Limiting
 */
function checkRateLimit() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $now = time();

    if (!file_exists(RATE_LIMIT_FILE)) {
        file_put_contents(RATE_LIMIT_FILE, json_encode([]));
    }

    $limits = json_decode(file_get_contents(RATE_LIMIT_FILE), true) ?: [];

    // Nettoyer les anciennes entrées
    foreach ($limits as $key => $data) {
        if ($now - $data['first_request'] > RATE_LIMIT_PERIOD) {
            unset($limits[$key]);
        }
    }

    // Vérifier la limite pour cette IP
    if (!isset($limits[$ip])) {
        $limits[$ip] = [
            'count' => 1,
            'first_request' => $now
        ];
    } else {
        if ($limits[$ip]['count'] >= RATE_LIMIT_REQUESTS) {
            $remaining = RATE_LIMIT_PERIOD - ($now - $limits[$ip]['first_request']);
            http_response_code(429);
            echo json_encode([
                'error' => 'Trop de requêtes. Veuillez patienter.',
                'retry_after' => $remaining
            ]);
            exit;
        }
        $limits[$ip]['count']++;
    }

    file_put_contents(RATE_LIMIT_FILE, json_encode($limits));
}

/**
 * Validation des données
 */
function validateInput($data) {
    if (!isset($data['question']) || empty(trim($data['question']))) {
        return ['valid' => false, 'error' => 'La question est obligatoire.'];
    }

    if (strlen($data['question']) > MAX_QUESTION_LENGTH) {
        return ['valid' => false, 'error' => 'Question trop longue (max ' . MAX_QUESTION_LENGTH . ' caractères).'];
    }

    if (!isset($data['commune'])) {
        $data['commune'] = 'general';
    }

    // Nettoyer les entrées
    $data['question'] = strip_tags($data['question']);
    $data['commune'] = strip_tags($data['commune']);

    return ['valid' => true, 'data' => $data];
}

/**
 * Recherche dans la base de données
 */
function searchDatabase($question, $commune) {
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        // Recherche dans la base centrale
        $stmt = $pdo->prepare("
            SELECT titre, contenu, categorie, source, reference
            FROM base_centrale
            WHERE MATCH(titre, contenu) AGAINST(:question IN NATURAL LANGUAGE MODE)
            LIMIT 5
        ");
        $stmt->execute(['question' => $question]);
        $central_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Recherche dans la base locale (si commune spécifiée)
        $local_results = [];
        if ($commune !== 'general') {
            $stmt = $pdo->prepare("
                SELECT titre, contenu, categorie, date_document
                FROM base_locale
                WHERE commune = :commune
                AND MATCH(titre, contenu) AGAINST(:question IN NATURAL LANGUAGE MODE)
                LIMIT 5
            ");
            $stmt->execute(['commune' => $commune, 'question' => $question]);
            $local_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return [
            'central' => $central_results,
            'local' => $local_results
        ];

    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            error_log("Database error: " . $e->getMessage());
        }
        return ['central' => [], 'local' => []];
    }
}

/**
 * Définition des fonctions disponibles pour Function Calling
 */
function getAvailableFunctions() {
    return [
        [
            'name' => 'search_official_websites',
            'description' => 'Recherche d\'informations sur les sites officiels (Légifrance, service-public.fr, DGCL, DGFIP, CNFPT, emploi-collectivites.fr) pour obtenir des références juridiques précises, des articles de loi, des décrets, ou des informations officielles.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'query' => [
                        'type' => 'string',
                        'description' => 'La requête de recherche (ex: "FCTVA communes article loi", "grille indiciaire adjoint administratif 2024")'
                    ],
                    'topic' => [
                        'type' => 'string',
                        'enum' => ['fctva', 'comptabilite', 'm57', 'rh', 'juridique', 'deliberation', 'marches_publics', 'general'],
                        'description' => 'Le sujet pour cibler les sites pertinents'
                    ]
                ],
                'required' => ['query']
            ]
        ],
        [
            'name' => 'search_document_base',
            'description' => 'Recherche dans la base documentaire locale (documents uploadés par la commune : délibérations, notes, règlements, etc.) en utilisant la recherche sémantique par embeddings.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'query' => [
                        'type' => 'string',
                        'description' => 'La question ou recherche (ex: "règlement intérieur cantine", "délibération budget 2024")'
                    ]
                ],
                'required' => ['query']
            ]
        ]
    ];
}

/**
 * Exécuter un appel de fonction
 */
function executeFunctionCall($function_name, $arguments) {
    switch ($function_name) {
        case 'search_official_websites':
            if (ENABLE_WEB_SEARCH) {
                $query = $arguments['query'] ?? '';
                $topic = $arguments['topic'] ?? 'general';

                $engine = new WebSearchEngine();
                $results = $engine->searchOfficialSources($query, $topic);

                // Récupérer le contenu des 3 premiers résultats
                $detailed_results = [];
                foreach (array_slice($results, 0, 3) as $result) {
                    $content = $engine->fetchPageContent($result['url'], 2000);
                    $detailed_results[] = [
                        'title' => $result['title'],
                        'url' => $result['url'],
                        'source' => $result['official_site'] ?? 'web',
                        'content' => $content ?? 'Contenu non disponible'
                    ];
                }

                return json_encode([
                    'success' => true,
                    'topic' => $topic,
                    'results_count' => count($detailed_results),
                    'results' => $detailed_results
                ]);
            }
            return json_encode(['success' => false, 'error' => 'Recherche web désactivée']);

        case 'search_document_base':
            if (ENABLE_RAG) {
                $query = $arguments['query'] ?? '';

                $manager = new EmbeddingsManager();
                $similar_docs = $manager->searchSimilar($query, 5);

                return json_encode([
                    'success' => true,
                    'results_count' => count($similar_docs),
                    'documents' => $similar_docs
                ]);
            }
            return json_encode(['success' => false, 'error' => 'RAG désactivé']);

        default:
            return json_encode(['success' => false, 'error' => 'Fonction inconnue']);
    }
}

/**
 * Appel direct à l'API OpenAI avec Function Calling
 */
function callOpenAI($question, $commune, $search_results) {
    // Préparer le contexte pour OpenAI
    $central_context = '';
    if (!empty($search_results['central'])) {
        $central_context = "Résultats de la base centrale:\n";
        foreach ($search_results['central'] as $result) {
            $central_context .= "- {$result['titre']} ({$result['categorie']})";
            if (!empty($result['reference'])) {
                $central_context .= " - {$result['reference']}";
            }
            $central_context .= "\n{$result['contenu']}\n\n";
        }
    }

    $local_context = '';
    if (!empty($search_results['local'])) {
        $local_context = "Résultats de la base locale pour {$commune}:\n";
        foreach ($search_results['local'] as $result) {
            $local_context .= "- {$result['titre']} ({$result['categorie']})";
            if (!empty($result['date_document'])) {
                $local_context .= " - {$result['date_document']}";
            }
            $local_context .= "\n{$result['contenu']}\n\n";
        }
    }

    // Construire le prompt système
    $system_prompt = "Tu es NOIA_Collectivités, un assistant IA de l'intelligence partagée du service public local.

RÔLE : Tu incarnes le rôle de Secrétaire Générale de Mairie numérique, spécialisé dans la gestion administrative, financière, juridique et RH des communes de moins de 3 500 habitants.

MISSION : Assister le secrétaire général de mairie dans toutes ses fonctions (RH, FINANCES, JURIDIQUE, NUMÉRIQUE) en garantissant la cohérence, la fiabilité et la conformité juridique de toutes les réponses.

CONTEXTE DE LA QUESTION :
- Commune : {$commune}
- Question posée : {$question}

SOURCES DOCUMENTAIRES DISPONIBLES :
{$central_context}
{$local_context}

RÈGLES ESSENTIELLES :
1. Privilégie TOUJOURS les sources officielles : Légifrance, DGCL, DGFIP, CDG, CNFPT, emploi-collectivites.fr, Service-public.fr
2. Cite les références juridiques PRÉCISES : numéro d'article, décret exact, circulaire avec date, arrêté complet
3. Pour les questions comptables (M57, FCTVA, etc.) : donne les NUMÉROS DE COMPTES EXACTS (ex: 2131, 2135, 2313, 615221)
4. Pour les questions RH : cite les grilles indiciaires précises, décrets avec numéros, portail emploi-collectivites.fr
5. Pour les délibérations : référence les articles CGCT exacts (ex: L2121-9 à L2121-21)
6. En cas d'ambiguïté ou d'information manquante : écris explicitement \"À vérifier auprès du CDG / trésorier / préfecture\"
7. Style : professionnel, administratif, clair et neutre (niveau cadre A FPT)
8. Ne jamais improviser : base-toi sur des textes officiels

MÉTHODE DE RÉPONSE OBLIGATOIRE :

<div class=\"structured-response\">
  <div class=\"response-section\">
    <div class=\"section-title\">📚 Références juridiques</div>
    <div class=\"section-content\">
    - Liste les textes OFFICIELS avec références EXACTES
    - Ex: \"Arrêté du 30 janvier 2024 modifiant l'arrêté du 30 décembre 2020\"
    - Ex: \"Article L.1615-1 du CGCT\"
    - Ex: \"Instruction M57 2025 – DGFiP\"
    - Ex: \"Décret n°87-1107 du 30 décembre 1987\"
    </div>
  </div>

  <div class=\"response-section\">
    <div class=\"section-title\">🔍 Analyse réglementaire</div>
    <div class=\"section-content\">
    - Explique le cadre juridique applicable
    - Distingue les différents cas si nécessaire
    - Cite les conditions d'éligibilité ou d'application
    </div>
  </div>

  <div class=\"response-section\">
    <div class=\"section-title\">✅ Application pratique</div>
    <div class=\"section-content\">
    - Instructions CONCRÈTES et OPÉRATIONNELLES
    - Pour la comptabilité : donne les NUMÉROS DE COMPTES PRÉCIS (ex: compte 2131, 2135, 615221)
    - Pour les RH : donne les formules de calcul exactes, grilles indiciaires
    - Pour le juridique : donne les délais exacts, procédures à suivre
    - Présente sous forme de tableau si pertinent
    </div>
  </div>

  <div class=\"response-section\">
    <div class=\"section-title\">📄 Proposition d'acte</div>
    <div class=\"section-content\">
    - Modèle ou proposition d'acte administratif
    - Mentions obligatoires à inclure
    - Références des textes à viser dans l'acte
    </div>
  </div>
</div>

<p><strong>Validation requise :</strong> Cet acte ou ce calcul doit être validé par le secrétaire général de mairie avant signature ou mise en paiement.</p>

EXEMPLES DE PRÉCISION ATTENDUE :

Pour une question sur le FCTVA :
❌ Mauvais : \"Imputation sur compte d'immobilisation\"
✅ Bon : \"Compte 2131 (Bâtiments publics) ou 2313 (Immobilisations en cours) pour l'investissement, compte 615221 (Entretien des bâtiments publics) pour le fonctionnement\"

Pour une question RH :
❌ Mauvais : \"Calculé selon les grilles\"
✅ Bon : \"Formule : (IM × 4,92302) / 100. Référence : Décret n°87-1107. Voir grille sur emploi-collectivites.fr\"

Pour une délibération :
❌ Mauvais : \"Respecter les règles de convocation\"
✅ Bon : \"Convocation 5 jours francs avant la séance (3 jours en urgence). Article L2121-11 du CGCT. Quorum : majorité absolue (art. L2121-17)\"

CONSIGNES TECHNIQUES :
- Utilise uniquement ces balises HTML : <div>, <p>, <strong>, <em>, <ul>, <li>, <br>
- Les classes autorisées : structured-response, response-section, section-title, section-content
- Longueur : 800-1200 mots pour être complet et professionnel
- Ton : formel, administratif, cadre A de la fonction publique territoriale
";

    // Préparer les messages initiaux
    $messages = [
        [
            'role' => 'system',
            'content' => $system_prompt
        ],
        [
            'role' => 'user',
            'content' => $question
        ]
    ];

    // Configuration de la requête avec Function Calling
    $data = [
        'model' => OPENAI_MODEL,
        'messages' => $messages,
        'max_tokens' => OPENAI_MAX_TOKENS,
        'temperature' => OPENAI_TEMPERATURE,
        'tools' => array_map(function($func) {
            return ['type' => 'function', 'function' => $func];
        }, getAvailableFunctions()),
        'tool_choice' => 'auto' // GPT décide s'il a besoin de chercher
    ];

    // Premier appel à OpenAI
    $response_data = makeOpenAIRequest($data);

    // Vérifier si GPT veut appeler une fonction
    $max_iterations = 5; // Limiter les appels en boucle
    $iteration = 0;

    while (isset($response_data['choices'][0]['message']['tool_calls']) && $iteration < $max_iterations) {
        $tool_calls = $response_data['choices'][0]['message']['tool_calls'];

        // Ajouter le message de l'assistant avec les tool_calls
        $messages[] = $response_data['choices'][0]['message'];

        // Exécuter chaque fonction appelée
        foreach ($tool_calls as $tool_call) {
            $function_name = $tool_call['function']['name'];
            $function_args = json_decode($tool_call['function']['arguments'], true);

            // Exécuter la fonction
            $function_result = executeFunctionCall($function_name, $function_args);

            // Ajouter le résultat aux messages
            $messages[] = [
                'role' => 'tool',
                'tool_call_id' => $tool_call['id'],
                'content' => $function_result
            ];
        }

        // Nouvel appel à OpenAI avec les résultats des fonctions
        $data['messages'] = $messages;
        $response_data = makeOpenAIRequest($data);

        $iteration++;
    }

    // Extraire la réponse finale
    if (!isset($response_data['choices'][0]['message']['content'])) {
        throw new Exception("Réponse OpenAI invalide");
    }

    return $response_data['choices'][0]['message']['content'];
}

/**
 * Fonction helper pour faire une requête OpenAI
 */
function makeOpenAIRequest($data) {
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Augmenté pour les recherches

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        throw new Exception("Erreur cURL: {$curl_error}");
    }

    if ($http_code !== 200) {
        throw new Exception("Erreur OpenAI (HTTP {$http_code}): {$response}");
    }

    $result = json_decode($response, true);

    if (!$result) {
        throw new Exception("Réponse JSON invalide");
    }

    return $result;
}

/**
 * Logging
 */
function logRequest($question, $commune, $response_time, $success) {
    if (!ENABLE_LOGGING) return;

    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'],
        'question' => substr($question, 0, 100),
        'commune' => $commune,
        'response_time' => $response_time,
        'success' => $success
    ];

    file_put_contents(LOG_FILE, json_encode($log_entry) . "\n", FILE_APPEND);
}

// ============================================================================
// TRAITEMENT PRINCIPAL
// ============================================================================

$start_time = microtime(true);

try {
    // 1. Rate limiting
    checkRateLimit();

    // 2. Récupérer et valider les données
    $raw_input = file_get_contents('php://input');
    $input = json_decode($raw_input, true);

    if (!$input) {
        throw new Exception('Données JSON invalides');
    }

    $validation = validateInput($input);
    if (!$validation['valid']) {
        throw new Exception($validation['error']);
    }

    $data = $validation['data'];
    $question = $data['question'];
    $commune = $data['commune'];

    // 3. Recherche dans la base de données
    $search_results = searchDatabase($question, $commune);

    // 4. Appel à OpenAI
    $ai_response = callOpenAI($question, $commune, $search_results);

    // 5. Réponse réussie
    $response_time = round((microtime(true) - $start_time) * 1000);
    logRequest($question, $commune, $response_time, true);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'response' => $ai_response,
        'commune' => $commune,
        'response_time' => $response_time,
        'sources_count' => [
            'central' => count($search_results['central']),
            'local' => count($search_results['local'])
        ]
    ]);

} catch (Exception $e) {
    $response_time = round((microtime(true) - $start_time) * 1000);
    logRequest($input['question'] ?? 'unknown', $input['commune'] ?? 'unknown', $response_time, false);

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => DEBUG_MODE ? $e->getMessage() : 'Une erreur est survenue. Veuillez réessayer.',
        'response_time' => $response_time
    ]);
}
