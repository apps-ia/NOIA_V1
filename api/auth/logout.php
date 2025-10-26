<?php
/**
 * NOIA v4.0 - Endpoint Déconnexion (Logout)
 *
 * POST /api/auth/logout.php
 * Requiert : Session active
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
    $auth = new Auth();

    // Déconnexion
    $result = $auth->logout();

    http_response_code(200);
    echo json_encode($result);

} catch (Exception $e) {
    if (DEBUG_MODE) {
        error_log('Logout error: ' . $e->getMessage());
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => DEBUG_MODE ? $e->getMessage() : 'Erreur serveur'
    ]);
}
