<?php
/**
 * NOIA - Script d'Auto-Mise à Jour
 * Copiez ce fichier sur le serveur via l'explorateur OVH, puis ouvrez-le dans le navigateur
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🚀 NOIA - Auto-Update</h1>";
echo "<p>Ce script va corriger les fichiers API pour résoudre le problème de session.</p>";

$baseDir = __DIR__;

// Fichier 1 : api/auth.php
$authFile = $baseDir . '/api/auth.php';
$authContent = <<<'PHP'
<?php
/**
 * NOIA MVP - Authentication API
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Auth/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();

// Récupérer l'action
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'login':
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                throw new Exception("Email et mot de passe requis");
            }

            if ($auth->login($email, $password)) {
                echo json_encode([
                    'success' => true,
                    'user' => $auth->getCurrentUser()
                ]);
            } else {
                throw new Exception("Email ou mot de passe incorrect");
            }
            break;

        case 'logout':
            $auth->logout();
            echo json_encode(['success' => true]);
            break;

        case 'check':
            if ($auth->isAuthenticated()) {
                echo json_encode([
                    'authenticated' => true,
                    'user' => $auth->getCurrentUser()
                ]);
            } else {
                echo json_encode(['authenticated' => false]);
            }
            break;

        default:
            throw new Exception("Action non reconnue");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
PHP;

// Fichier 2 : api/assistant.php (début seulement, garde le reste)
echo "<h2>Mise à jour de auth.php</h2>";
if (file_put_contents($authFile, $authContent)) {
    echo "✅ auth.php mis à jour avec succès<br>";
} else {
    echo "❌ Erreur lors de la mise à jour de auth.php<br>";
}

// Pour assistant.php, on fait juste une correction simple
echo "<h2>Mise à jour de assistant.php</h2>";
$assistantFile = $baseDir . '/api/assistant.php';
if (file_exists($assistantFile)) {
    $assistantContent = file_get_contents($assistantFile);

    // Supprimer session_start(); après le commentaire initial
    $assistantContent = preg_replace(
        '/(<\?php\s*\/\*\*.*?\*\/\s*)session_start\(\);\s*/s',
        '$1',
        $assistantContent
    );

    if (file_put_contents($assistantFile, $assistantContent)) {
        echo "✅ assistant.php mis à jour avec succès<br>";
    } else {
        echo "❌ Erreur lors de la mise à jour de assistant.php<br>";
    }
} else {
    echo "⚠️ assistant.php non trouvé<br>";
}

echo "<h2>✅ Mise à jour terminée !</h2>";
echo "<p><strong>Prochaines étapes :</strong></p>";
echo "<ol>";
echo "<li>Retournez sur <a href='https://sgm.erelys.fr'>https://sgm.erelys.fr</a></li>";
echo "<li>Rechargez la page (Ctrl+F5)</li>";
echo "<li>Connectez-vous avec demo@noia.fr / noia2024</li>";
echo "</ol>";

echo "<hr>";
echo "<p><em>Vous pouvez supprimer ce fichier update.php après usage.</em></p>";
?>
