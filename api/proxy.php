<?php
/**
 * NOIA - API Proxy (Direct OpenAI Integration)
 * Version: 4.0.0 - Phase 2 : Intelligence & Précision Légale
 *
 * Nouvelles fonctionnalités Phase 2 :
 * - Pré-analyse intelligente (détection quorum, FCTVA, IFSE, M57, etc.)
 * - Consultation automatique des legal_facts (règles validées priorité 10/10)
 * - Forçage de la consultation des sources officielles selon le contexte
 * - Logging avancé (coûts OpenAI, tokens, sources utilisées)
 * - System prompt amélioré avec legal_facts injectés
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/web_search.php';
require_once __DIR__ . '/embeddings.php';
require_once __DIR__ . '/pre_analysis.php';
require_once __DIR__ . '/legal_facts_manager.php';

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
 *
 * @param string $question Question posée
 * @param string $commune Commune concernée
 * @param array $search_results Résultats de la recherche DB
 * @param array $pre_analysis Résultat de la pré-analyse (Phase 2)
 * @param array $legal_facts Règles légales validées (Phase 2)
 */
function callOpenAI($question, $commune, $search_results, $pre_analysis = [], $legal_facts = []) {
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

    // ========================================================================
    // PHASE 2 : INJECTION DES LEGAL FACTS (RÈGLES VALIDÉES - PRIORITÉ 10/10)
    // ========================================================================
    $legal_facts_section = '';
    if (!empty($legal_facts)) {
        $legal_facts_section = LegalFactsManager::formatForPrompt($legal_facts);
    }

    // ========================================================================
    // PHASE 2 : CONTEXTE DE PRÉ-ANALYSE
    // ========================================================================
    $pre_analysis_context = '';
    if (!empty($pre_analysis)) {
        $pre_analysis_context = "\n🔍 CONTEXTE DÉTECTÉ PAR PRÉ-ANALYSE :\n";
        $pre_analysis_context .= "- Catégorie principale : " . strtoupper($pre_analysis['main_category']) . "\n";
        $pre_analysis_context .= "- Priorité : {$pre_analysis['priority']}/10\n";
        $pre_analysis_context .= "- Question critique : " . ($pre_analysis['is_critical'] ? 'OUI ⚠️' : 'Non') . "\n";

        if (!empty($pre_analysis['detected_contexts'])) {
            $pre_analysis_context .= "- Contextes détectés : ";
            $contexts = array_map(function($c) {
                return $c['name'];
            }, $pre_analysis['detected_contexts']);
            $pre_analysis_context .= implode(', ', $contexts) . "\n";
        }

        if ($pre_analysis['force_official_sources']) {
            $pre_analysis_context .= "⚠️ SOURCES OFFICIELLES OBLIGATOIRES\n";
        }

        $pre_analysis_context .= "\n";
    }

    // Construire le prompt système
    $system_prompt = $legal_facts_section . $pre_analysis_context . "Tu es NOIA_Collectivités, un assistant IA de l'intelligence partagée du service public local.

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
4. Pour les questions RH : cite les grilles indiciaires COMPLÈTES avec TOUS les échelons, indices bruts ET majorés, salaires calculés
5. Pour les délibérations : référence les articles CGCT exacts (ex: L2121-9 à L2121-21)
6. En cas d'ambiguïté ou d'information manquante : écris explicitement \"À vérifier auprès du CDG / trésorier / préfecture\"
7. Style : professionnel, administratif, clair et neutre (niveau cadre A FPT)
8. Ne jamais improviser : base-toi sur des textes officiels
9. LONGUEUR REQUISE : 800-1500 mots minimum pour être complet et exhaustif

MÉTHODE DE RÉPONSE OBLIGATOIRE :

Tu DOIS structurer ta réponse avec des numéros emoji (1️⃣, 2️⃣, 3️⃣, 4️⃣) et des sections claires :

<div class=\"structured-response\">
  <div class=\"response-section\">
    <div class=\"section-title\">1️⃣ Références juridiques</div>
    <div class=\"section-content\">
    <p>Liste TOUS les textes OFFICIELS avec références EXACTES :</p>
    <ul>
      <li>Décrets avec numéros complets (ex: Décret n° 85-1148 du 24 octobre 1985)</li>
      <li>Articles de loi précis (ex: Article L.1615-1 du CGCT)</li>
      <li>Instructions officielles (ex: Instruction M57 2025 – DGFiP)</li>
      <li>Sources consultées avec mentions explicites</li>
    </ul>
    <p><strong>IMPORTANT :</strong> Cite les sources exactes consultées (emploi-collectivites.fr, Légifrance, DGCL, etc.)</p>
    </div>
  </div>

  <div class=\"response-section\">
    <div class=\"section-title\">2️⃣ Analyse de la situation</div>
    <div class=\"section-content\">
    <p>Explique le cadre juridique applicable de manière détaillée :</p>
    <ul>
      <li>Contexte réglementaire</li>
      <li>Conditions d'application</li>
      <li>Distinctions entre différents cas si nécessaire</li>
      <li>Spécificités pour les communes < 3 500 habitants</li>
    </ul>
    </div>
  </div>

  <div class=\"response-section\">
    <div class=\"section-title\">3️⃣ Application pratique</div>
    <div class=\"section-content\">
    <p><strong>Instructions CONCRÈTES et OPÉRATIONNELLES :</strong></p>

    <p><strong>Pour les questions comptables (M57, FCTVA) :</strong></p>
    <ul>
      <li>Numéros de comptes EXACTS (ex: compte 2131, 2135, 615221)</li>
      <li>Imputation précise (fonctionnement/investissement)</li>
      <li>Durée d'amortissement si applicable</li>
    </ul>

    <p><strong>Pour les questions RH (grilles indiciaires) :</strong></p>
    <ul>
      <li>Valeur du point indiciaire en vigueur (ex: 4,92302 € au 01/07/2024)</li>
      <li>Formule de calcul : IM × valeur du point = salaire brut mensuel</li>
      <li>TABLEAU COMPLET de la grille avec :</li>
    </ul>

    <p><strong>Tableau obligatoire pour les grilles RH :</strong></p>
    <p>Grade « [Nom du grade] »</p>
    <ul>
      <li>Échelon 1 : indice brut = XXX, indice majoré = XXX → salaire brut indiciaire ≈ X XXX,XX €/mois</li>
      <li>Échelon 2 : indice brut = XXX, indice majoré = XXX → salaire brut indiciaire ≈ X XXX,XX €/mois</li>
      <li>...</li>
      <li>Échelon final : indice brut = XXX, indice majoré = XXX → salaire brut indiciaire ≈ X XXX,XX €/mois</li>
    </ul>

    <p><strong>Répète ce tableau pour CHAQUE grade du cadre d'emploi.</strong></p>

    <p><strong>Pour les questions juridiques :</strong></p>
    <ul>
      <li>Délais exacts (5 jours francs, 3 jours en urgence, etc.)</li>
      <li>Procédures à suivre étape par étape</li>
      <li>Articles CGCT applicables</li>
    </ul>

    <p>👉 Précise que les montants RH sont « traitement brut indiciaire » (hors primes, hors indemnités, hors bonifications).</p>
    </div>
  </div>

  <div class=\"response-section\">
    <div class=\"section-title\">4️⃣ Proposition d'usage pour ta situation</div>
    <div class=\"section-content\">
    <p>Modèle ou proposition d'acte administratif / tableau de synthèse :</p>
    <ul>
      <li>Pour RH : tableau récapitulatif à intégrer dans le logiciel de paie</li>
      <li>Pour comptabilité : délibération ou mandat type</li>
      <li>Pour juridique : modèle de délibération avec visas obligatoires</li>
    </ul>
    <p><strong>À vérifier :</strong> Mentionne les points à vérifier ou valider (CDG, trésorier, préfecture).</p>
    </div>
  </div>
</div>

<p><strong>⚠️ Validation requise :</strong> Cet acte, ce calcul ou cette grille doit être validé(e) par le secrétaire général de mairie, le CDG ou le trésorier avant application.</p>

EXEMPLES DE PRÉCISION ATTENDUE :

Pour une question sur le FCTVA :
❌ Mauvais : \"Imputation sur compte d'immobilisation\"
✅ Bon : \"Compte 2131 (Bâtiments publics) ou 2313 (Immobilisations en cours) pour l'investissement, compte 615221 (Entretien des bâtiments publics) pour le fonctionnement. Référence : Article L1615-1 du CGCT. Éligibilité : dépenses réelles d'investissement > 5 000 € HT (sauf voirie). Délai de déclaration : année N+1 maximum.\"

Pour une question RH (grilles indiciaires) :
❌ Mauvais : \"Calculé selon les grilles\"
✅ Bon : \"Le cadre d'emploi des adjoints administratifs territoriaux comprend 3 grades (Décret n° 2016-604 du 12 mai 2016) :

Grade 'Adjoint administratif' :
- Échelon 1 : indice brut = 367, indice majoré = 366 → salaire brut indiciaire ≈ 1 801,74 €/mois
- Échelon 2 : indice brut = 369, indice majoré = 368 → salaire brut indiciaire ≈ 1 811,58 €/mois
- ...
- Échelon 11 : indice brut = 432, indice majoré = 387 → salaire brut indiciaire ≈ 1 905,12 €/mois

Grade 'Adjoint administratif principal de 2e classe' :
[Même détail pour tous les échelons]

Grade 'Adjoint administratif principal de 1re classe' :
[Même détail pour tous les échelons]

Formule de calcul : IM × 4,92302 € (valeur au 01/07/2024)
Source : emploi-collectivites.fr, Décret n° 85-1148\"

Pour une délibération :
❌ Mauvais : \"Respecter les règles de convocation\"
✅ Bon : \"Convocation 5 jours francs avant la séance (3 jours en urgence sur décision du maire motivée - Article L2121-11 du CGCT). Quorum : majorité absolue des membres en exercice à la 1re convocation (art. L2121-17). Reconvocation : AUCUN quorum requis (art. L2121-17 alinéa 2). Publicité : affichage obligatoire 1 semaine minimum avant séance. Ordre du jour : joint à la convocation (art. L2121-12).\"

CONSIGNES TECHNIQUES :
- Utilise uniquement ces balises HTML : <div>, <p>, <strong>, <em>, <ul>, <li>, <br>
- Les classes autorisées : structured-response, response-section, section-title, section-content
- Longueur : 1000-1500 mots pour être complet, exhaustif et professionnel
- Ton : formel, administratif, expert niveau cadre A de la fonction publique territoriale
- Structure : TOUJOURS 4 sections avec emoji numérotés (1️⃣, 2️⃣, 3️⃣, 4️⃣)
- Tableaux : OBLIGATOIRES pour grilles RH, comptes M57, ou données chiffrées
- Sources : TOUJOURS mentionner explicitement les sources consultées (emploi-collectivites.fr, Légifrance, etc.)
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

    // ========================================================================
    // PHASE 2 : DÉTERMINATION INTELLIGENTE DU TOOL_CHOICE
    // ========================================================================
    $tool_choice = 'auto'; // Par défaut, GPT décide

    if (!empty($pre_analysis)) {
        // Si contexte critique ou force_official_sources = true, forcer la recherche
        $tool_choice = PreAnalysis::determineToolChoice($pre_analysis);
    }

    // Configuration de la requête avec Function Calling
    $data = [
        'model' => OPENAI_MODEL,
        'messages' => $messages,
        'max_tokens' => OPENAI_MAX_TOKENS,
        'temperature' => OPENAI_TEMPERATURE,
        'tools' => array_map(function($func) {
            return ['type' => 'function', 'function' => $func];
        }, getAvailableFunctions()),
        'tool_choice' => $tool_choice
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
        // Diagnostic amélioré v4.0.1
        $finish_reason = $response_data['choices'][0]['finish_reason'] ?? 'unknown';
        $has_tool_calls = isset($response_data['choices'][0]['message']['tool_calls']);

        $debug_info = "Réponse OpenAI sans content. ";
        $debug_info .= "finish_reason: {$finish_reason}, ";
        $debug_info .= "iterations: {$iteration}/{$max_iterations}, ";
        $debug_info .= "has_tool_calls: " . ($has_tool_calls ? 'oui' : 'non');

        if ($iteration >= $max_iterations && $has_tool_calls) {
            throw new Exception("Trop d'appels de fonctions consécutifs (max: {$max_iterations})");
        }

        throw new Exception($debug_info);
    }

    // ========================================================================
    // PHASE 2 : EXTRACTION DES MÉTRIQUES OPENAI (tokens, coûts)
    // ========================================================================
    $usage = $response_data['usage'] ?? [];
    $metrics = [
        'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
        'completion_tokens' => $usage['completion_tokens'] ?? 0,
        'total_tokens' => $usage['total_tokens'] ?? 0,
        'model' => OPENAI_MODEL,
        'function_calls_count' => $iteration
    ];

    // Calcul approximatif des coûts (tarifs GPT-4 Turbo)
    // Input: $0.01 / 1K tokens, Output: $0.03 / 1K tokens
    $cost_input = ($metrics['prompt_tokens'] / 1000) * 0.01;
    $cost_output = ($metrics['completion_tokens'] / 1000) * 0.03;
    $metrics['estimated_cost_usd'] = round($cost_input + $cost_output, 4);

    return [
        'content' => $response_data['choices'][0]['message']['content'],
        'metrics' => $metrics
    ];
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
 * Logging basique (maintenu pour compatibilité)
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

/**
 * Logging avancé Phase 2 (métriques détaillées)
 */
function logAdvancedMetrics($data) {
    if (!ENABLE_LOGGING) return;

    // Ajouter timestamp et IP
    $data['timestamp'] = date('Y-m-d H:i:s');
    $data['ip'] = $_SERVER['REMOTE_ADDR'];

    // Fichier de log séparé pour les métriques avancées
    $advanced_log_file = __DIR__ . '/../logs/metrics_phase2.log';

    // Créer le dossier logs si nécessaire
    $log_dir = dirname($advanced_log_file);
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    // Logger en JSON pour analyse ultérieure
    file_put_contents($advanced_log_file, json_encode($data) . "\n", FILE_APPEND);
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

    // ========================================================================
    // PHASE 2 : PRÉ-ANALYSE INTELLIGENTE
    // ========================================================================
    $pre_analysis = PreAnalysis::analyze($question);

    // ========================================================================
    // PHASE 2 : CONSULTATION DES LEGAL FACTS
    // ========================================================================
    $legal_facts = [];
    if ($pre_analysis['force_legal_facts'] || $pre_analysis['is_critical']) {
        try {
            $legal_manager = new LegalFactsManager();
            $legal_facts = $legal_manager->smartSearch(
                $pre_analysis['legal_facts_tags'],
                $question
            );
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Legal Facts error: " . $e->getMessage());
            }
            // Continue sans legal facts en cas d'erreur
        }
    }

    // 4. Appel à OpenAI avec pré-analyse et legal facts
    $ai_result = callOpenAI($question, $commune, $search_results, $pre_analysis, $legal_facts);

    // Extraire le contenu et les métriques
    $ai_response = $ai_result['content'];
    $openai_metrics = $ai_result['metrics'];

    // 5. Réponse réussie
    $response_time = round((microtime(true) - $start_time) * 1000);

    // ========================================================================
    // PHASE 2 : LOGGING AVANCÉ
    // ========================================================================
    logRequest($question, $commune, $response_time, true);

    // Log détaillé Phase 2 (si activé)
    logAdvancedMetrics([
        'question' => substr($question, 0, 100),
        'commune' => $commune,
        'response_time_ms' => $response_time,
        'pre_analysis' => PreAnalysis::getAnalysisLog($pre_analysis),
        'legal_facts' => LegalFactsManager::getSummaryForLog($legal_facts),
        'openai_metrics' => $openai_metrics,
        'sources' => [
            'central' => count($search_results['central']),
            'local' => count($search_results['local']),
            'legal_facts' => count($legal_facts)
        ]
    ]);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'response' => $ai_response,
        'commune' => $commune,
        'response_time' => $response_time,
        'sources_count' => [
            'central' => count($search_results['central']),
            'local' => count($search_results['local']),
            'legal_facts' => count($legal_facts)
        ],
        // Phase 2 : métriques optionnelles (affichées si DEBUG_MODE)
        'metrics' => DEBUG_MODE ? [
            'pre_analysis' => $pre_analysis,
            'openai' => $openai_metrics
        ] : null
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
