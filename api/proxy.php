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
 * Appel direct à l'API OpenAI
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
    $system_prompt = "Tu es NOIA, assistant spécialisé dans la réglementation des collectivités territoriales françaises.

CONTEXTE :
- Commune : {$commune}
- Question : {$question}

SOURCES DISPONIBLES :
{$central_context}
{$local_context}

CONSIGNES :
1. Structure ta réponse avec ces sections (format HTML) :
   <div class=\"structured-response\">
     <div class=\"response-section\">
       <div class=\"section-title\">📚 Références juridiques</div>
       <div class=\"section-content\">Liste les textes et références légales pertinents</div>
     </div>
     <div class=\"response-section\">
       <div class=\"section-title\">🔍 Analyse réglementaire</div>
       <div class=\"section-content\">Analyse détaillée du cadre réglementaire</div>
     </div>
     <div class=\"response-section\">
       <div class=\"section-title\">✅ Application pratique</div>
       <div class=\"section-content\">Comment appliquer concrètement ces règles</div>
     </div>
     <div class=\"response-section\">
       <div class=\"section-title\">📄 Proposition d'acte</div>
       <div class=\"section-content\">Modèle ou proposition d'acte administratif si pertinent</div>
     </div>
   </div>

2. Cite systématiquement tes sources (Légifrance, CGCT, etc.)
3. Si manque d'info locale, indique clairement \"Aucune donnée spécifique trouvée pour cette commune\"
4. Utilise un ton professionnel mais accessible
5. Maximum 500 mots pour la clarté
6. Utilise uniquement les balises HTML autorisées : <div>, <p>, <strong>, <em>, <ul>, <li>, <br>
";

    // Préparer la requête OpenAI
    $data = [
        'model' => OPENAI_MODEL,
        'messages' => [
            [
                'role' => 'system',
                'content' => $system_prompt
            ],
            [
                'role' => 'user',
                'content' => $question
            ]
        ],
        'max_tokens' => OPENAI_MAX_TOKENS,
        'temperature' => OPENAI_TEMPERATURE
    ];

    // Appel à l'API OpenAI
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

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

    if (!isset($result['choices'][0]['message']['content'])) {
        throw new Exception("Réponse OpenAI invalide");
    }

    return $result['choices'][0]['message']['content'];
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
