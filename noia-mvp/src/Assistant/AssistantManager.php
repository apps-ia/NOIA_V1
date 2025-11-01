<?php
/**
 * NOIA MVP - OpenAI Assistant Manager
 */

class AssistantManager {
    private $apiKey;
    private $assistantId;
    private $model;

    public function __construct() {
        $this->apiKey = OPENAI_API_KEY;
        $this->assistantId = OPENAI_ASSISTANT_ID;
        $this->model = OPENAI_MODEL;
    }

    /**
     * Créer un nouveau thread de conversation
     */
    public function createThread() {
        $response = $this->apiRequest('POST', 'threads', []);
        return $response['id'] ?? null;
    }

    /**
     * Ajouter un message au thread
     */
    public function addMessage($threadId, $content) {
        return $this->apiRequest('POST', "threads/{$threadId}/messages", [
            'role' => 'user',
            'content' => $content
        ]);
    }

    /**
     * Exécuter l'assistant sur le thread
     */
    public function runAssistant($threadId) {
        return $this->apiRequest('POST', "threads/{$threadId}/runs", [
            'assistant_id' => $this->assistantId
        ]);
    }

    /**
     * Vérifier le statut d'une exécution
     */
    public function getRunStatus($threadId, $runId) {
        return $this->apiRequest('GET', "threads/{$threadId}/runs/{$runId}");
    }

    /**
     * Récupérer les messages du thread
     */
    public function getMessages($threadId) {
        return $this->apiRequest('GET', "threads/{$threadId}/messages");
    }

    /**
     * Supprimer un thread (RGPD - 30 jours)
     */
    public function deleteThread($threadId) {
        return $this->apiRequest('DELETE', "threads/{$threadId}");
    }

    /**
     * Requête générique vers l'API OpenAI
     */
    private function apiRequest($method, $endpoint, $data = null) {
        $url = "https://api.openai.com/v1/{$endpoint}";

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'OpenAI-Beta: assistants=v2'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            error_log("OpenAI API Error {$httpCode}: {$response}");
            throw new Exception("Erreur API OpenAI: {$httpCode}");
        }

        return json_decode($response, true);
    }
}
