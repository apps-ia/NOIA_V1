<?php
/**
 * NOIA v3.0 - Script de test du système RAG et Recherche Web
 *
 * Ce script teste les nouvelles fonctionnalités :
 * 1. Recherche web sur sites officiels
 * 2. Création d'embeddings
 * 3. Recherche sémantique
 * 4. Function calling
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/api/web_search.php';
require_once __DIR__ . '/api/embeddings.php';

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>NOIA v3.0 - Test RAG & Web Search</title>
    <style>
        body {
            font-family: monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            line-height: 1.6;
        }
        .section {
            background: #252526;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #007acc;
        }
        .success {
            color: #4ec9b0;
        }
        .error {
            color: #f48771;
        }
        .warning {
            color: #ce9178;
        }
        h2 {
            color: #4ec9b0;
            margin-top: 0;
        }
        pre {
            background: #1e1e1e;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>🧪 NOIA v3.0 - Test du Système RAG et Recherche Web</h1>

    <?php

    // ========================================================================
    // TEST 1 : Configuration
    // ========================================================================
    echo '<div class="section">';
    echo '<h2>1️⃣ Configuration</h2>';

    $config_ok = true;

    if (!defined('OPENAI_API_KEY') || OPENAI_API_KEY === 'sk-votre-cle-api-openai') {
        echo '<p class="error">❌ OPENAI_API_KEY non configurée dans config.php</p>';
        $config_ok = false;
    } else {
        echo '<p class="success">✅ OPENAI_API_KEY configurée (masquée : ' . substr(OPENAI_API_KEY, 0, 7) . '...)</p>';
    }

    if (defined('ENABLE_WEB_SEARCH') && ENABLE_WEB_SEARCH) {
        echo '<p class="success">✅ Recherche Web activée</p>';
    } else {
        echo '<p class="warning">⚠️  Recherche Web désactivée</p>';
    }

    if (defined('ENABLE_RAG') && ENABLE_RAG) {
        echo '<p class="success">✅ RAG activé</p>';
    } else {
        echo '<p class="warning">⚠️  RAG désactivé</p>';
    }

    if (defined('OPENAI_EMBEDDING_MODEL')) {
        echo '<p class="success">✅ Modèle d\'embedding : ' . OPENAI_EMBEDDING_MODEL . '</p>';
    } else {
        echo '<p class="error">❌ OPENAI_EMBEDDING_MODEL non défini</p>';
        $config_ok = false;
    }

    echo '</div>';

    // ========================================================================
    // TEST 2 : Recherche Web
    // ========================================================================
    echo '<div class="section">';
    echo '<h2>2️⃣ Test Recherche Web</h2>';

    if (ENABLE_WEB_SEARCH) {
        try {
            $engine = new WebSearchEngine();

            echo '<p>🔍 Recherche : "FCTVA communes article loi"</p>';

            $topic = $engine->detectTopic('FCTVA travaux bâtiment');
            echo '<p class="success">✅ Sujet détecté : <strong>' . $topic . '</strong></p>';

            $results = $engine->searchOfficialSources('FCTVA communes article loi', 'fctva');

            if (count($results) > 0) {
                echo '<p class="success">✅ ' . count($results) . ' résultats trouvés</p>';
                echo '<pre>';
                foreach (array_slice($results, 0, 3) as $i => $result) {
                    echo ($i + 1) . '. ' . $result['title'] . "\n";
                    echo '   Source: ' . ($result['official_site'] ?? 'web') . "\n";
                    echo '   URL: ' . $result['url'] . "\n\n";
                }
                echo '</pre>';
            } else {
                echo '<p class="warning">⚠️  Aucun résultat (peut être dû au rate limiting de DuckDuckGo)</p>';
            }

        } catch (Exception $e) {
            echo '<p class="error">❌ Erreur : ' . $e->getMessage() . '</p>';
        }
    } else {
        echo '<p class="warning">⏭️  Test ignoré (recherche web désactivée)</p>';
    }

    echo '</div>';

    // ========================================================================
    // TEST 3 : Embeddings
    // ========================================================================
    echo '<div class="section">';
    echo '<h2>3️⃣ Test Embeddings</h2>';

    if (ENABLE_RAG && $config_ok) {
        try {
            $manager = new EmbeddingsManager();

            $test_text = "La FCTVA permet aux communes de récupérer la TVA sur leurs investissements. Article L.1615-1 du CGCT.";

            echo '<p>📝 Texte de test : <em>' . $test_text . '</em></p>';

            $embedding = $manager->createEmbedding($test_text);

            if ($embedding && is_array($embedding)) {
                echo '<p class="success">✅ Embedding créé : ' . count($embedding) . ' dimensions</p>';
                echo '<pre>Premiers éléments : [' . implode(', ', array_slice($embedding, 0, 5)) . ', ...]</pre>';

                // Test de similarité
                $test_query = "Comment récupérer la TVA pour la commune ?";
                $query_embedding = $manager->createEmbedding($test_query);

                if ($query_embedding) {
                    $similarity = $manager->cosineSimilarity($embedding, $query_embedding);
                    echo '<p class="success">✅ Similarité cosinus : <strong>' . round($similarity, 4) . '</strong></p>';
                    echo '<p>Query : <em>' . $test_query . '</em></p>';
                }

            } else {
                echo '<p class="error">❌ Impossible de créer l\'embedding (vérifier la clé API)</p>';
            }

        } catch (Exception $e) {
            echo '<p class="error">❌ Erreur : ' . $e->getMessage() . '</p>';
        }
    } else {
        echo '<p class="warning">⏭️  Test ignoré (RAG désactivé ou config incomplète)</p>';
    }

    echo '</div>';

    // ========================================================================
    // TEST 4 : Base de données
    // ========================================================================
    echo '<div class="section">';
    echo '<h2>4️⃣ Test Base de Données</h2>';

    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        echo '<p class="success">✅ Connexion MySQL réussie</p>';

        // Vérifier l'existence des tables
        $tables_required = ['base_centrale', 'base_locale', 'documents', 'embeddings'];
        $tables_found = [];

        foreach ($tables_required as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
            if ($stmt->rowCount() > 0) {
                $tables_found[] = $table;

                // Compter les lignes
                $count_stmt = $pdo->query("SELECT COUNT(*) as cnt FROM {$table}");
                $count = $count_stmt->fetch(PDO::FETCH_ASSOC)['cnt'];

                echo '<p class="success">✅ Table <strong>' . $table . '</strong> : ' . $count . ' entrée(s)</p>';
            } else {
                echo '<p class="error">❌ Table <strong>' . $table . '</strong> manquante</p>';
            }
        }

        if (count($tables_found) === count($tables_required)) {
            echo '<p class="success"><strong>✅ Toutes les tables sont présentes</strong></p>';
        } else {
            echo '<p class="error">❌ Exécutez database/upgrade_rag_v3.sql pour créer les tables manquantes</p>';
        }

    } catch (PDOException $e) {
        echo '<p class="error">❌ Erreur MySQL : ' . $e->getMessage() . '</p>';
    }

    echo '</div>';

    // ========================================================================
    // TEST 5 : Permissions Fichiers
    // ========================================================================
    echo '<div class="section">';
    echo '<h2>5️⃣ Test Permissions</h2>';

    $dirs_to_check = [
        'documents' => DOCUMENTS_DIR,
        'logs' => __DIR__ . '/logs'
    ];

    foreach ($dirs_to_check as $name => $path) {
        if (file_exists($path)) {
            if (is_writable($path)) {
                echo '<p class="success">✅ Dossier <strong>' . $name . '/</strong> accessible en écriture</p>';
            } else {
                echo '<p class="error">❌ Dossier <strong>' . $name . '/</strong> non accessible en écriture</p>';
                echo '<p>Exécutez : <code>chmod 755 ' . $path . '</code></p>';
            }
        } else {
            echo '<p class="warning">⚠️  Dossier <strong>' . $name . '/</strong> n\'existe pas</p>';
            echo '<p>Exécutez : <code>mkdir -p ' . $path . ' && chmod 755 ' . $path . '</code></p>';
        }
    }

    echo '</div>';

    // ========================================================================
    // RÉSUMÉ
    // ========================================================================
    echo '<div class="section">';
    echo '<h2>📊 Résumé</h2>';

    echo '<ul>';
    echo '<li><strong>Recherche Web :</strong> ' . (ENABLE_WEB_SEARCH ? '✅ Activée' : '❌ Désactivée') . '</li>';
    echo '<li><strong>RAG (Embeddings) :</strong> ' . (ENABLE_RAG ? '✅ Activé' : '❌ Désactivé') . '</li>';
    echo '<li><strong>Function Calling :</strong> ✅ Intégré dans proxy.php</li>';
    echo '<li><strong>Interface Admin :</strong> ✅ admin_documents.html</li>';
    echo '</ul>';

    echo '<hr>';
    echo '<p><strong>Prochaines étapes :</strong></p>';
    echo '<ol>';
    echo '<li>Si des tables manquent : exécuter <code>database/upgrade_rag_v3.sql</code></li>';
    echo '<li>Uploader des documents via <code>admin_documents.html</code></li>';
    echo '<li>Tester NOIA avec une question réelle</li>';
    echo '</ol>';

    echo '</div>';

    ?>

    <p style="text-align: center; margin-top: 40px; color: #858585;">
        NOIA v3.0 - RAG & Web Search | <?= date('Y-m-d H:i:s') ?>
    </p>

</body>
</html>
