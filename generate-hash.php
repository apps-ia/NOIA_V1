<?php
/**
 * Générateur de Hash Bcrypt pour NOIA
 *
 * ⚠️ À SUPPRIMER après utilisation !
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Générateur Hash Bcrypt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #667eea;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        button {
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
        }
        button:hover {
            background: #5568d3;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            background: #f0f0f0;
            border-left: 4px solid #667eea;
            border-radius: 4px;
        }
        .result h3 {
            margin-top: 0;
            color: #667eea;
        }
        .hash {
            background: #1e1e1e;
            color: #4ec9b0;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            word-break: break-all;
            margin: 10px 0;
        }
        .sql {
            background: #1e1e1e;
            color: #ce9178;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            margin: 10px 0;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 12px;
            border-radius: 4px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Générateur Hash Bcrypt</h1>
        <p>Créez un hash sécurisé pour votre mot de passe NOIA.</p>

        <form method="POST">
            <div class="form-group">
                <label for="password">Entrez votre mot de passe :</label>
                <input
                    type="text"
                    id="password"
                    name="password"
                    placeholder="Minimum 8 caractères"
                    required
                    minlength="8"
                    value="<?= $_POST['password'] ?? '' ?>"
                >
            </div>

            <div class="form-group">
                <label for="email">Email de l'utilisateur :</label>
                <input
                    type="text"
                    id="email"
                    name="email"
                    placeholder="admin@noia.local"
                    value="<?= $_POST['email'] ?? 'admin@noia.local' ?>"
                    required
                >
            </div>

            <button type="submit">Générer le Hash</button>
        </form>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['password'])): ?>
            <?php
                $password = $_POST['password'];
                $email = $_POST['email'] ?? 'admin@noia.local';
                $hash = password_hash($password, PASSWORD_BCRYPT);
            ?>

            <div class="result">
                <h3>✅ Hash Généré avec Succès</h3>

                <p><strong>Mot de passe :</strong> <?= htmlspecialchars($password) ?></p>
                <p><strong>Email :</strong> <?= htmlspecialchars($email) ?></p>

                <p><strong>Hash Bcrypt :</strong></p>
                <div class="hash"><?= $hash ?></div>

                <h3>📋 Requête SQL à Exécuter</h3>
                <p>Copiez cette requête et exécutez-la dans phpMyAdmin :</p>
                <div class="sql">UPDATE users
SET password_hash = '<?= $hash ?>',
    login_attempts = 0,
    locked_until = NULL
WHERE email = '<?= addslashes($email) ?>';

-- Vérifier
SELECT id, email, role FROM users WHERE email = '<?= addslashes($email) ?>';</div>

                <h3>✅ Prochaines Étapes</h3>
                <ol>
                    <li>Copier la requête SQL ci-dessus</li>
                    <li>Aller dans phpMyAdmin</li>
                    <li>Onglet "SQL"</li>
                    <li>Coller et exécuter</li>
                    <li>Tester la connexion sur login.html</li>
                    <li><strong>⚠️ SUPPRIMER ce fichier (generate-hash.php)</strong></li>
                </ol>
            </div>
        <?php endif; ?>

        <div class="warning">
            <strong>⚠️ ATTENTION SÉCURITÉ</strong><br>
            Supprimez ce fichier immédiatement après avoir généré votre hash !<br>
            Fichier à supprimer : <code>generate-hash.php</code>
        </div>
    </div>
</body>
</html>
