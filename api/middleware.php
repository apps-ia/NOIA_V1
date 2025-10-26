<?php
/**
 * NOIA v4.0 - Middleware de Protection des APIs
 *
 * Fonctions helper pour protéger les endpoints selon les rôles
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';

/**
 * Require authentication (any role)
 */
function requireAuth() {
    $auth = new Auth();

    if (!$auth->check()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Authentification requise',
            'redirect' => '/login.html'
        ]);
        exit;
    }

    return $auth;
}

/**
 * Require specific role
 */
function requireRole($required_role) {
    $auth = requireAuth();

    if (!$auth->hasRole($required_role)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Accès refusé. Rôle requis : ' . $required_role
        ]);
        exit;
    }

    return $auth;
}

/**
 * Require admin role
 */
function requireAdmin() {
    return requireRole('admin');
}

/**
 * Require agent or admin role
 */
function requireAgent() {
    return requireRole('agent');
}

/**
 * Verify CSRF token
 */
function verifyCsrfToken() {
    $auth = new Auth();

    $input = json_decode(file_get_contents('php://input'), true);
    $token = $input['csrf_token'] ?? $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;

    if (!$token || !$auth->verifyCSRFToken($token)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Token CSRF invalide'
        ]);
        exit;
    }
}

/**
 * Get current user
 */
function getCurrentUser() {
    $auth = new Auth();
    return $auth->getCurrentUser();
}

/**
 * Log API access
 */
function logApiAccess($endpoint, $action, $status = 'success', $error_message = null) {
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $user_id = $_SESSION['user_id'] ?? null;

        $stmt = $pdo->prepare("
            INSERT INTO access_logs (user_id, action, endpoint, ip_address, user_agent, status, error_message, created_at)
            VALUES (:user_id, :action, :endpoint, :ip, :user_agent, :status, :error_message, NOW())
        ");

        $stmt->execute([
            'user_id' => $user_id,
            'action' => $action,
            'endpoint' => $endpoint,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'status' => $status,
            'error_message' => $error_message
        ]);

    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            error_log('Log API access error: ' . $e->getMessage());
        }
    }
}

/**
 * Return JSON response
 */
function jsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Return JSON error
 */
function jsonError($message, $status_code = 400, $additional_data = []) {
    jsonResponse(array_merge([
        'success' => false,
        'error' => $message
    ], $additional_data), $status_code);
}

/**
 * Return JSON success
 */
function jsonSuccess($data = [], $status_code = 200) {
    jsonResponse(array_merge([
        'success' => true
    ], $data), $status_code);
}

/**
 * Validate required fields
 */
function validateRequired($data, $required_fields) {
    $missing = [];

    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            $missing[] = $field;
        }
    }

    if (!empty($missing)) {
        jsonError('Champs requis manquants : ' . implode(', ', $missing), 400);
    }
}
