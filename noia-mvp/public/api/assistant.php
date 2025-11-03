<?php
/**
 * NOIA MVP - Assistant API
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Auth/Auth.php';
require_once __DIR__ . '/../../src/Assistant/AssistantManager.php';

header('Content-Type: application/json');

$auth = new Auth();

// Vérifier l'authentification
if (!$auth->isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

$user = $auth->getCurrentUser();
$assistant = new AssistantManager();
$db = Database::getInstance()->getConnection();

// Vérifier rate limit
if (!$auth->checkRateLimit($user['id'])) {
    http_response_code(429);
    echo json_encode(['error' => 'Limite de requêtes atteinte. Veuillez patienter.']);
    exit;
}

try {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'new_conversation':
            // Créer un nouveau thread
            $threadId = $assistant->createThread();

            // Sauvegarder en base
            $stmt = $db->prepare("
                INSERT INTO noia_conversations (user_id, thread_id, created_at)
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$user['id'], $threadId]);
            $conversationId = $db->lastInsertId();

            echo json_encode([
                'success' => true,
                'conversation_id' => $conversationId,
                'thread_id' => $threadId
            ]);
            break;

        case 'send_message':
            $threadId = $_POST['thread_id'] ?? '';
            $message = $_POST['message'] ?? '';

            if (empty($threadId) || empty($message)) {
                throw new Exception("Thread ID et message requis");
            }

            // Ajouter le message
            $assistant->addMessage($threadId, $message);

            // Lancer l'assistant
            $run = $assistant->runAssistant($threadId);
            $runId = $run['id'];

            // Attendre la fin de l'exécution (polling)
            $maxAttempts = 60; // 60 secondes max
            $attempt = 0;

            while ($attempt < $maxAttempts) {
                $status = $assistant->getRunStatus($threadId, $runId);

                if ($status['status'] === 'completed') {
                    // Récupérer la réponse
                    $messages = $assistant->getMessages($threadId);
                    $lastMessage = $messages['data'][0] ?? null;

                    if ($lastMessage && $lastMessage['role'] === 'assistant') {
                        $content = $lastMessage['content'][0]['text']['value'] ?? '';

                        echo json_encode([
                            'success' => true,
                            'response' => $content
                        ]);
                        exit;
                    }
                } elseif (in_array($status['status'], ['failed', 'cancelled', 'expired'])) {
                    throw new Exception("L'assistant a rencontré une erreur: " . $status['status']);
                }

                sleep(1);
                $attempt++;
            }

            throw new Exception("Délai d'attente dépassé");
            break;

        case 'get_conversations':
            // Récupérer les conversations de l'utilisateur
            $stmt = $db->prepare("
                SELECT id, thread_id, created_at
                FROM noia_conversations
                WHERE user_id = ?
                AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
                ORDER BY created_at DESC
            ");
            $stmt->execute([$user['id']]);
            $conversations = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'conversations' => $conversations
            ]);
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
