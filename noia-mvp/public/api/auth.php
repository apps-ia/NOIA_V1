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
