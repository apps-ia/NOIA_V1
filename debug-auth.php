<?php
/**
 * NOIA v4.0 - Script de Diagnostic Authentification
 *
 * ⚠️ À SUPPRIMER après utilisation !
 *
 * Ce script vérifie :
 * 1. Connexion à la base de données
 * 2. Existence de la table users
 * 3. Existence de l'utilisateur admin
 * 4. Validité du mot de passe
 * 5. Création d'un nouveau compte admin si nécessaire
 */

require_once __DIR__ . '/config/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>NOIA - Diagnostic Authentification</title>
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
        .btn {
            background: #007acc;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
        }
        .btn:hover {
            background: #005a9e;
        }
    </style>
</head>
<body>
    <h1>🔍 NOIA v4.0 - Diagnostic Authentification</h1>

    <?php

    // ========================================================================
    // TEST 1 : Connexion Base de Données
    // ========================================================================
    echo '<div class="section">';
    echo '<h2>1️⃣ Test Connexion Base de Données</h2>';

    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        echo '<p class="success">✅ Connexion MySQL réussie</p>';
        echo '<pre>';
        echo 'Host     : ' . DB_HOST . "\n";
        echo 'Database : ' . DB_NAME . "\n";
        echo 'User     : ' . DB_USER . "\n";
        echo '</pre>';

    } catch (PDOException $e) {
        echo '<p class="error">❌ Erreur de connexion MySQL</p>';
        echo '<pre>' . $e->getMessage() . '</pre>';
        echo '<p class="warning">Vérifiez votre fichier config/config.php</p>';
        echo '</div></body></html>';
        exit;
    }

    echo '</div>';

    // ========================================================================
    // TEST 2 : Vérification Table users
    // ========================================================================
    echo '<div class="section">';
    echo '<h2>2️⃣ Vérification Table users</h2>';

    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");

        if ($stmt->rowCount() > 0) {
            echo '<p class="success">✅ Table users existe</p>';

            // Compter les utilisateurs
            $stmt = $pdo->query("SELECT COUNT(*) as nb FROM users");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo '<p>Nombre d\'utilisateurs : <strong>' . $result['nb'] . '</strong></p>';

            // Afficher la structure de la table
            $stmt = $pdo->query("DESCRIBE users");
            echo '<pre>';
            echo "Structure de la table users:\n";
            echo str_pad('Colonne', 25) . str_pad('Type', 30) . "Null\n";
            echo str_repeat('-', 70) . "\n";
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo str_pad($row['Field'], 25) . str_pad($row['Type'], 30) . $row['Null'] . "\n";
            }
            echo '</pre>';

        } else {
            echo '<p class="error">❌ Table users n\'existe pas</p>';
            echo '<p class="warning">⚠️ Vous devez exécuter le script upgrade_v4_PARTIE_A.sql</p>';
            echo '</div></body></html>';
            exit;
        }

    } catch (PDOException $e) {
        echo '<p class="error">❌ Erreur : ' . $e->getMessage() . '</p>';
        echo '</div></body></html>';
        exit;
    }

    echo '</div>';

    // ========================================================================
    // TEST 3 : Vérification Utilisateur admin
    // ========================================================================
    echo '<div class="section">';
    echo '<h2>3️⃣ Vérification Utilisateur admin@noia.local</h2>';

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = 'admin@noia.local'");
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin) {
            echo '<p class="success">✅ Utilisateur admin existe</p>';
            echo '<pre>';
            echo 'ID              : ' . $admin['id'] . "\n";
            echo 'Email           : ' . $admin['email'] . "\n";
            echo 'Nom             : ' . $admin['nom'] . "\n";
            echo 'Prénom          : ' . $admin['prenom'] . "\n";
            echo 'Rôle            : ' . $admin['role'] . "\n";
            echo 'Actif           : ' . ($admin['is_active'] ? 'Oui' : 'Non') . "\n";
            echo 'Créé le         : ' . $admin['created_at'] . "\n";
            echo 'Dernière connexion : ' . ($admin['last_login'] ?? 'Jamais') . "\n";
            echo 'Tentatives échouées : ' . $admin['login_attempts'] . "\n";
            if ($admin['locked_until']) {
                echo 'Verrouillé jusqu\'à : ' . $admin['locked_until'] . "\n";
            }
            echo "\n";
            echo 'Hash du mot de passe : ' . substr($admin['password_hash'], 0, 30) . '...' . "\n";
            echo '</pre>';

            // Test de vérification du mot de passe
            $test_password = 'admin123';
            if (password_verify($test_password, $admin['password_hash'])) {
                echo '<p class="success">✅ Le mot de passe "admin123" est VALIDE</p>';
            } else {
                echo '<p class="error">❌ Le mot de passe "admin123" NE CORRESPOND PAS au hash en base</p>';
                echo '<p class="warning">Le hash a peut-être été modifié ou corrompu.</p>';
            }

        } else {
            echo '<p class="error">❌ Utilisateur admin@noia.local n\'existe PAS</p>';
            echo '<p class="warning">L\'utilisateur admin n\'a pas été créé lors de l\'exécution du script SQL.</p>';
        }

    } catch (PDOException $e) {
        echo '<p class="error">❌ Erreur : ' . $e->getMessage() . '</p>';
    }

    echo '</div>';

    // ========================================================================
    // TEST 4 : Test de l'API login
    // ========================================================================
    echo '<div class="section">';
    echo '<h2>4️⃣ Test API Login</h2>';

    $login_endpoint = './api/auth/login.php';

    if (file_exists(__DIR__ . '/api/auth/login.php')) {
        echo '<p class="success">✅ Fichier api/auth/login.php existe</p>';
    } else {
        echo '<p class="error">❌ Fichier api/auth/login.php MANQUANT</p>';
        echo '<p class="warning">Vous devez uploader le fichier login.php dans /api/auth/</p>';
    }

    if (file_exists(__DIR__ . '/api/auth.php')) {
        echo '<p class="success">✅ Fichier api/auth.php existe</p>';
    } else {
        echo '<p class="error">❌ Fichier api/auth.php MANQUANT</p>';
        echo '<p class="warning">Vous devez uploader le fichier auth.php dans /api/</p>';
    }

    echo '</div>';

    // ========================================================================
    // SOLUTION : Créer/Réinitialiser Admin
    // ========================================================================
    echo '<div class="section">';
    echo '<h2>5️⃣ Solution : Créer/Réinitialiser Compte Admin</h2>';

    if (isset($_POST['create_admin'])) {
        try {
            $new_password = $_POST['new_password'] ?? 'admin123';
            $password_hash = password_hash($new_password, PASSWORD_BCRYPT);

            // Supprimer l'ancien admin s'il existe
            $stmt = $pdo->prepare("DELETE FROM users WHERE email = 'admin@noia.local'");
            $stmt->execute();

            // Créer le nouvel admin
            $stmt = $pdo->prepare("
                INSERT INTO users (email, password_hash, nom, prenom, role, is_active, created_at, login_attempts)
                VALUES ('admin@noia.local', :hash, 'Administrateur', 'NOIA', 'admin', 1, NOW(), 0)
            ");
            $stmt->execute(['hash' => $password_hash]);

            echo '<p class="success">✅ Compte admin créé avec succès !</p>';
            echo '<pre>';
            echo 'Email        : admin@noia.local' . "\n";
            echo 'Mot de passe : ' . $new_password . "\n";
            echo '</pre>';
            echo '<p><strong>⚠️ Notez bien ce mot de passe !</strong></p>';
            echo '<p><a href="login.html">→ Aller à la page de connexion</a></p>';

        } catch (PDOException $e) {
            echo '<p class="error">❌ Erreur lors de la création : ' . $e->getMessage() . '</p>';
        }
    } else {
        echo '<p>Cliquez sur le bouton ci-dessous pour créer/réinitialiser le compte admin :</p>';
        echo '<form method="POST">';
        echo '<label for="new_password">Mot de passe (minimum 8 caractères) :</label><br>';
        echo '<input type="text" id="new_password" name="new_password" value="admin123" style="padding: 8px; width: 300px; margin: 10px 0;"><br>';
        echo '<button type="submit" name="create_admin" class="btn">🔧 Créer/Réinitialiser Admin</button>';
        echo '</form>';
        echo '<p class="warning">⚠️ Cette action supprimera l\'ancien compte admin s\'il existe.</p>';
    }

    echo '</div>';

    // ========================================================================
    // DIAGNOSTIC COMPLET
    // ========================================================================
    echo '<div class="section">';
    echo '<h2>📊 Résumé du Diagnostic</h2>';
    echo '<ul>';
    echo '<li><strong>Connexion MySQL :</strong> ' . (isset($pdo) ? '✅ OK' : '❌ Échec') . '</li>';
    echo '<li><strong>Table users :</strong> ' . (isset($admin) || isset($result) ? '✅ Existe' : '❌ Manquante') . '</li>';
    echo '<li><strong>Utilisateur admin :</strong> ' . (isset($admin) && $admin ? '✅ Existe' : '❌ Manquant') . '</li>';
    echo '<li><strong>Mot de passe valide :</strong> ' . (isset($admin) && password_verify('admin123', $admin['password_hash']) ? '✅ OK' : '❌ Incorrect') . '</li>';
    echo '<li><strong>API Login :</strong> ' . (file_exists(__DIR__ . '/api/auth/login.php') ? '✅ Présente' : '❌ Manquante') . '</li>';
    echo '</ul>';
    echo '</div>';

    ?>

    <div class="section">
        <h2>⚠️ IMPORTANT</h2>
        <p><strong>SUPPRIMEZ ce fichier après avoir résolu le problème !</strong></p>
        <p>Ce fichier de diagnostic contient des informations sensibles sur votre base de données.</p>
        <p>Fichier à supprimer : <code>debug-auth.php</code></p>
    </div>

    <p style="text-align: center; margin-top: 40px; color: #858585;">
        NOIA v4.0 - Diagnostic Authentification | <?= date('Y-m-d H:i:s') ?>
    </p>

</body>
</html>
