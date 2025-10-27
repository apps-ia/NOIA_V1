<?php
/**
 * NOIA - Test de l'API Phase 2
 * Uploadez ce fichier à la racine de votre site pour diagnostiquer les problèmes
 */

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🧪 Test NOIA Phase 2</h1>";
echo "<p>Diagnostics en cours...</p>";
echo "<hr>";

// Test 1 : Version PHP
echo "<h2>✅ Test 1 : Version PHP</h2>";
$php_version = phpversion();
echo "<p>Version PHP : <strong>$php_version</strong></p>";

if (version_compare($php_version, '7.4', '>=')) {
    echo "<p style='color:green'>✅ PHP 7.4+ requis : OK</p>";
} else {
    echo "<p style='color:red'>❌ PHP 7.4+ requis : Version trop ancienne</p>";
}

echo "<hr>";

// Test 2 : Fichier config.php
echo "<h2>✅ Test 2 : Fichier config.php</h2>";
if (file_exists(__DIR__ . '/config/config.php')) {
    echo "<p style='color:green'>✅ config/config.php existe</p>";
    require_once __DIR__ . '/config/config.php';

    if (defined('DB_HOST')) {
        echo "<p style='color:green'>✅ DB_HOST défini : " . DB_HOST . "</p>";
    }
    if (defined('DB_NAME')) {
        echo "<p style='color:green'>✅ DB_NAME défini : " . DB_NAME . "</p>";
    }
    if (defined('OPENAI_API_KEY')) {
        $key = OPENAI_API_KEY;
        $masked = substr($key, 0, 7) . '...' . substr($key, -4);
        echo "<p style='color:green'>✅ OPENAI_API_KEY défini : $masked</p>";
    }
} else {
    echo "<p style='color:red'>❌ config/config.php MANQUANT</p>";
}

echo "<hr>";

// Test 3 : Connexion base de données
echo "<h2>✅ Test 3 : Connexion MySQL</h2>";
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "<p style='color:green'>✅ Connexion MySQL réussie</p>";

    // Vérifier la table legal_facts
    $stmt = $pdo->query("SHOW TABLES LIKE 'legal_facts'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>✅ Table legal_facts existe</p>";

        $count = $pdo->query("SELECT COUNT(*) FROM legal_facts WHERE is_active = 1")->fetchColumn();
        echo "<p style='color:green'>✅ Legal facts actifs : $count</p>";
    } else {
        echo "<p style='color:red'>❌ Table legal_facts MANQUANTE (Phase 1 incomplète)</p>";
        echo "<p>➡️ Exécuter : database/upgrade_v4_PARTIE_A.sql puis database/legal_facts_data.sql</p>";
    }

    // Vérifier la table users
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>✅ Table users existe</p>";
    } else {
        echo "<p style='color:orange'>⚠️ Table users manquante (authentification non installée)</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Erreur connexion MySQL : " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test 4 : Fichiers Phase 2
echo "<h2>✅ Test 4 : Fichiers Phase 2</h2>";

$files = [
    'api/pre_analysis.php',
    'api/legal_facts_manager.php',
    'api/proxy.php'
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "<p style='color:green'>✅ $file existe</p>";

        // Vérifier si proxy.php est la bonne version
        if ($file === 'api/proxy.php') {
            $content = file_get_contents($path);
            if (strpos($content, 'Version: 4.0.0') !== false) {
                echo "<p style='color:green'>✅ proxy.php est la version 4.0.0 (Phase 2)</p>";
            } elseif (strpos($content, 'Version: 2.0.0') !== false) {
                echo "<p style='color:red'>❌ proxy.php est encore la version 2.0.0 (ANCIEN)</p>";
                echo "<p>➡️ Uploader le nouveau proxy.php v4.0.0</p>";
            } else {
                echo "<p style='color:orange'>⚠️ Version de proxy.php inconnue</p>";
            }
        }
    } else {
        echo "<p style='color:red'>❌ $file MANQUANT</p>";
        echo "<p>➡️ Uploader ce fichier depuis Git</p>";
    }
}

echo "<hr>";

// Test 5 : Inclusion des classes
echo "<h2>✅ Test 5 : Chargement des classes PHP</h2>";

try {
    if (file_exists(__DIR__ . '/api/pre_analysis.php')) {
        require_once __DIR__ . '/api/pre_analysis.php';
        echo "<p style='color:green'>✅ pre_analysis.php chargé</p>";

        // Test de la classe
        $test_analysis = PreAnalysis::analyze("Test quorum");
        echo "<p style='color:green'>✅ Classe PreAnalysis fonctionne</p>";
        echo "<p>Catégorie détectée : <strong>" . $test_analysis['main_category'] . "</strong></p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Erreur PreAnalysis : " . $e->getMessage() . "</p>";
}

try {
    if (file_exists(__DIR__ . '/api/legal_facts_manager.php')) {
        require_once __DIR__ . '/api/legal_facts_manager.php';
        echo "<p style='color:green'>✅ legal_facts_manager.php chargé</p>";

        // Test de la classe
        $manager = new LegalFactsManager();
        echo "<p style='color:green'>✅ Classe LegalFactsManager fonctionne</p>";

        $status = $manager->checkTableStatus();
        if ($status['exists']) {
            echo "<p style='color:green'>✅ Table legal_facts accessible (count: {$status['count']})</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Erreur LegalFactsManager : " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test 6 : Test de l'API proxy.php
echo "<h2>✅ Test 6 : API proxy.php</h2>";

echo "<p>Pour tester l'API directement :</p>";
echo "<p><a href='./api/proxy.php' target='_blank'>Tester api/proxy.php</a></p>";
echo "<p><em>Résultat attendu : Erreur 405 \"Méthode non autorisée. Utilisez POST.\"</em></p>";

echo "<hr>";

// Résumé
echo "<h2>📊 Résumé</h2>";

$all_ok = true;
$errors = [];

if (version_compare($php_version, '7.4', '<')) {
    $all_ok = false;
    $errors[] = "Version PHP trop ancienne";
}

if (!file_exists(__DIR__ . '/api/pre_analysis.php')) {
    $all_ok = false;
    $errors[] = "pre_analysis.php manquant";
}

if (!file_exists(__DIR__ . '/api/legal_facts_manager.php')) {
    $all_ok = false;
    $errors[] = "legal_facts_manager.php manquant";
}

$proxy_content = file_exists(__DIR__ . '/api/proxy.php') ? file_get_contents(__DIR__ . '/api/proxy.php') : '';
if (strpos($proxy_content, 'Version: 2.0.0') !== false) {
    $all_ok = false;
    $errors[] = "proxy.php est encore la version 2.0.0 (pas mis à jour)";
}

if ($all_ok) {
    echo "<p style='color:green;font-size:20px;font-weight:bold'>🎉 TOUT EST OK ! Phase 2 installée correctement.</p>";
    echo "<p>➡️ Allez sur <a href='./'>https://noia.erelys.fr/</a> et testez une question.</p>";
    echo "<p>⚠️ <strong>IMPORTANT :</strong> Supprimez ce fichier test-api.php après vérification (sécurité).</p>";
} else {
    echo "<p style='color:red;font-size:20px;font-weight:bold'>❌ Problèmes détectés :</p>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li style='color:red'>$error</li>";
    }
    echo "</ul>";
    echo "<p>➡️ Consultez le guide <strong>ARRETER_MODE_DEMO.md</strong></p>";
}

echo "<hr>";
echo "<p><em>Fichier de test généré par NOIA Phase 2 - À supprimer après utilisation</em></p>";
?>
