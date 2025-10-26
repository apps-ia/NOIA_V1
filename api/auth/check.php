<?php
/**
 * NOIA v4.0 - Endpoint Vérification Session
 *
 * GET /api/auth/check.php
 * Retourne les informations de l'utilisateur connecté
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../auth.php';

// CORS
header('Access-Control-Allow-Origin: ' . (ALLOWED_ORIGINS === '*' ? '*' : ALLOWED_ORIGINS));
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

try {
    $auth = new Auth();

    if (!$auth->check()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'authenticated' => false,
            'error' => 'Non authentifié'
        ]);
        exit;
    }

    $user = $auth->getCurrentUser();

    if (!$user) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'authenticated' => false,
            'error' => 'Session invalide'
        ]);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'authenticated' => true,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'nom' => $user['nom'],
            'prenom' => $user['prenom'],
            'role' => $user['role'],
            'commune' => $user['commune']
        ],
        'csrf_token' => $_SESSION['csrf_token'] ?? null
    ]);

} catch (Exception $e) {
    if (DEBUG_MODE) {
        error_log('Check session error: ' . $e->getMessage());
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => DEBUG_MODE ? $e->getMessage() : 'Erreur serveur'
    ]);
}
