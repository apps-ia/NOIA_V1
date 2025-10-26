<?php
/**
 * NOIA v4.0 - Endpoint Authentification (Login)
 *
 * POST /api/auth/login.php
 * Body: { "email": "...", "password": "...", "remember": true/false }
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../auth.php';

// CORS
header('Access-Control-Allow-Origin: ' . (ALLOWED_ORIGINS === '*' ? '*' : ALLOWED_ORIGINS));
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

try {
    // Récupérer les données POST
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Données JSON invalides']);
        exit;
    }

    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    $remember = $input['remember'] ?? false;

    // Validation basique
    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Email et mot de passe requis']);
        exit;
    }

    // Tentative de connexion
    $auth = new Auth();
    $result = $auth->login($email, $password, $remember);

    if ($result['success']) {
        http_response_code(200);
        echo json_encode($result);
    } else {
        http_response_code(401);
        echo json_encode($result);
    }

} catch (Exception $e) {
    if (DEBUG_MODE) {
        error_log('Login error: ' . $e->getMessage());
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => DEBUG_MODE ? $e->getMessage() : 'Erreur serveur'
    ]);
}
