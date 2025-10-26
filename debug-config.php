<?php
/**
 * NOIA - Debug Configuration
 *
 * Ce fichier vérifie que config.php est correctement configuré
 * Uploadez ce fichier sur votre serveur et accédez-y via navigateur
 */

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔍 NOIA - Diagnostic de Configuration</h1>";
echo "<style>
body { font-family: sans-serif; padding: 20px; max-width: 800px; }
.ok { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
table { border-collapse: collapse; width: 100%; margin: 20px 0; }
td, th { border: 1px solid #ddd; padding: 10px; text-align: left; }
th { background: #f5f5f5; }
</style>";

// Test 1: PHP Version
echo "<h2>1. Version PHP</h2>";
$php_version = phpversion();
echo "<p>Version: <strong>$php_version</strong> ";
if (version_compare($php_version, '7.4', '>=')) {
    echo "<span class='ok'>✅ OK</span>";
} else {
    echo "<span class='error'>❌ PHP 7.4+ requis</span>";
}
echo "</p>";

// Test 2: Extensions PHP requises
echo "<h2>2. Extensions PHP</h2>";
echo "<table>";
echo "<tr><th>Extension</th><th>Statut</th></tr>";

$extensions = [
    'curl' => 'Pour appeler l\'API OpenAI',
    'json' => 'Pour traiter les données JSON',
    'pdo' => 'Pour la base de données',
    'pdo_mysql' => 'Pour MySQL'
];

foreach ($extensions as $ext => $desc) {
    $loaded = extension_loaded($ext);
    echo "<tr>";
    echo "<td><strong>$ext</strong><br><small>$desc</small></td>";
    echo "<td>" . ($loaded ? "<span class='ok'>✅ Installée</span>" : "<span class='error'>❌ Manquante</span>") . "</td>";
    echo "</tr>";
}
echo "</table>";

// Test 3: Fichier config.php
echo "<h2>3. Fichier config.php</h2>";
$config_path = __DIR__ . '/config/config.php';
$config_exists = file_exists($config_path);

echo "<p>Chemin: <code>$config_path</code></p>";
echo "<p>Existe: " . ($config_exists ? "<span class='ok'>✅ Oui</span>" : "<span class='error'>❌ Non</span>") . "</p>";

if ($config_exists) {
    echo "<p>Taille: " . filesize($config_path) . " octets</p>";
    echo "<p>Permissions: " . substr(sprintf('%o', fileperms($config_path)), -4) . "</p>";

    // Charger config.php
    try {
        require_once $config_path;
        echo "<p><span class='ok'>✅ Chargé sans erreur</span></p>";

        // Test 4: Constantes définies
        echo "<h2>4. Constantes de configuration</h2>";
        echo "<table>";
        echo "<tr><th>Constante</th><th>Valeur</th><th>Statut</th></tr>";

        $required_constants = [
            'DB_HOST' => 'string',
            'DB_NAME' => 'string',
            'DB_USER' => 'string',
            'DB_PASS' => 'string',
            'OPENAI_API_KEY' => 'string',
            'OPENAI_MODEL' => 'string',
            'OPENAI_MAX_TOKENS' => 'int',
            'OPENAI_TEMPERATURE' => 'float',
            'RATE_LIMIT_REQUESTS' => 'int',
            'RATE_LIMIT_PERIOD' => 'int'
        ];

        foreach ($required_constants as $const => $type) {
            $defined = defined($const);
            echo "<tr>";
            echo "<td><strong>$const</strong></td>";

            if ($defined) {
                $value = constant($const);
                // Masquer les clés sensibles
                if (in_array($const, ['DB_PASS', 'OPENAI_API_KEY'])) {
                    if (!empty($value) && $value !== 'votre_mot_de_passe' && $value !== 'sk-votre-cle-api-openai') {
                        echo "<td><em>***masqué***</em></td>";
                        echo "<td><span class='ok'>✅ Configuré</span></td>";
                    } else {
                        echo "<td><span class='warning'>⚠️ Valeur par défaut</span></td>";
                        echo "<td><span class='error'>❌ À configurer</span></td>";
                    }
                } else {
                    echo "<td><code>" . htmlspecialchars($value) . "</code></td>";
                    echo "<td><span class='ok'>✅ Défini</span></td>";
                }
            } else {
                echo "<td><em>Non définie</em></td>";
                echo "<td><span class='error'>❌ Manquante</span></td>";
            }
            echo "</tr>";
        }
        echo "</table>";

        // Test 5: Connexion base de données
        if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
            echo "<h2>5. Connexion Base de Données</h2>";
            try {
                $pdo = new PDO(
                    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                    DB_USER,
                    DB_PASS,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                echo "<p><span class='ok'>✅ Connexion réussie</span></p>";

                // Vérifier les tables
                $tables = ['base_centrale', 'base_locale'];
                echo "<p>Tables:</p><ul>";
                foreach ($tables as $table) {
                    $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                    if ($stmt->rowCount() > 0) {
                        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                        echo "<li><span class='ok'>✅ $table</span> ($count enregistrements)</li>";
                    } else {
                        echo "<li><span class='error'>❌ $table manquante</span></li>";
                    }
                }
                echo "</ul>";

            } catch (PDOException $e) {
                echo "<p><span class='error'>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</span></p>";
            }
        }

        // Test 6: Clé API OpenAI
        if (defined('OPENAI_API_KEY')) {
            echo "<h2>6. Clé API OpenAI</h2>";
            $api_key = constant('OPENAI_API_KEY');

            if (empty($api_key) || $api_key === 'sk-votre-cle-api-openai') {
                echo "<p><span class='error'>❌ Clé API non configurée</span></p>";
                echo "<p>Obtenez une clé sur <a href='https://platform.openai.com/api-keys' target='_blank'>https://platform.openai.com/api-keys</a></p>";
            } elseif (substr($api_key, 0, 3) !== 'sk-') {
                echo "<p><span class='error'>❌ Format de clé invalide (doit commencer par 'sk-')</span></p>";
            } else {
                echo "<p><span class='ok'>✅ Clé API configurée</span></p>";
                echo "<p>Format: " . substr($api_key, 0, 7) . "..." . substr($api_key, -4) . "</p>";
            }
        }

        // Test 7: Dossier logs
        echo "<h2>7. Dossier logs</h2>";
        $logs_dir = __DIR__ . '/logs';
        if (is_dir($logs_dir)) {
            echo "<p><span class='ok'>✅ Existe</span></p>";
            echo "<p>Permissions: " . substr(sprintf('%o', fileperms($logs_dir)), -4) . "</p>";
            if (is_writable($logs_dir)) {
                echo "<p><span class='ok'>✅ Accessible en écriture</span></p>";
            } else {
                echo "<p><span class='error'>❌ Pas d'accès en écriture</span></p>";
                echo "<p>Commande: <code>chmod 755 " . $logs_dir . "</code></p>";
            }
        } else {
            echo "<p><span class='error'>❌ N'existe pas</span></p>";
            echo "<p>Créez-le via FTP avec permissions 755</p>";
        }

    } catch (Exception $e) {
        echo "<p><span class='error'>❌ Erreur lors du chargement: " . htmlspecialchars($e->getMessage()) . "</span></p>";
    }
} else {
    echo "<p><span class='error'>❌ Le fichier config.php n'existe pas</span></p>";
    echo "<p>Créez-le à partir de <code>config.example.php</code></p>";
}

// Test 8: Fichier proxy.php
echo "<h2>8. Fichier proxy.php</h2>";
$proxy_path = __DIR__ . '/api/proxy.php';
$proxy_exists = file_exists($proxy_path);

echo "<p>Chemin: <code>$proxy_path</code></p>";
echo "<p>Existe: " . ($proxy_exists ? "<span class='ok'>✅ Oui</span>" : "<span class='error'>❌ Non</span>") . "</p>";

if ($proxy_exists) {
    echo "<p>Taille: " . filesize($proxy_path) . " octets</p>";
    echo "<p>Permissions: " . substr(sprintf('%o', fileperms($proxy_path)), -4) . "</p>";
}

// Résumé
echo "<h2>📊 Résumé</h2>";

$all_ok = true;
$issues = [];

if (!version_compare($php_version, '7.4', '>=')) {
    $all_ok = false;
    $issues[] = "PHP 7.4+ requis";
}

if (!extension_loaded('curl')) {
    $all_ok = false;
    $issues[] = "Extension cURL manquante";
}

if (!$config_exists) {
    $all_ok = false;
    $issues[] = "config.php manquant";
}

if (!$proxy_exists) {
    $all_ok = false;
    $issues[] = "proxy.php manquant";
}

if (defined('OPENAI_API_KEY')) {
    $api_key = constant('OPENAI_API_KEY');
    if (empty($api_key) || $api_key === 'sk-votre-cle-api-openai') {
        $all_ok = false;
        $issues[] = "Clé API OpenAI non configurée";
    }
}

if ($all_ok) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px;'>";
    echo "<p style='color: #155724; margin: 0;'><strong>✅ Configuration OK - NOIA devrait fonctionner</strong></p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;'>";
    echo "<p style='color: #721c24; margin: 0 0 10px 0;'><strong>❌ Problèmes détectés:</strong></p>";
    echo "<ul style='color: #721c24; margin: 0;'>";
    foreach ($issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
    echo "</div>";
}

echo "<hr>";
echo "<p><small>Date: " . date('Y-m-d H:i:s') . "</small></p>";
echo "<p><small>⚠️ Supprimez ce fichier après diagnostic !</small></p>";
