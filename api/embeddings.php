<?php
/**
 * NOIA - Embeddings Manager
 * Gestion des embeddings OpenAI pour RAG
 */

require_once __DIR__ . '/../config/config.php';

class EmbeddingsManager {

    /**
     * Créer un embedding avec OpenAI
     */
    public function createEmbedding($text) {
        if (empty(trim($text))) {
            return null;
        }

        $data = [
            'model' => OPENAI_EMBEDDING_MODEL,
            'input' => $text
        ];

        $ch = curl_init('https://api.openai.com/v1/embeddings');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200 || !$response) {
            if (DEBUG_MODE) {
                error_log("Embedding API error: HTTP {$http_code} - {$response}");
            }
            return null;
        }

        $result = json_decode($response, true);

        if (!isset($result['data'][0]['embedding'])) {
            return null;
        }

        return $result['data'][0]['embedding'];
    }

    /**
     * Calculer la similarité cosinus entre 2 vecteurs
     */
    public function cosineSimilarity($vec1, $vec2) {
        if (count($vec1) !== count($vec2)) {
            return 0;
        }

        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;

        for ($i = 0; $i < count($vec1); $i++) {
            $dotProduct += $vec1[$i] * $vec2[$i];
            $magnitude1 += $vec1[$i] * $vec1[$i];
            $magnitude2 += $vec2[$i] * $vec2[$i];
        }

        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);

        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0;
        }

        return $dotProduct / ($magnitude1 * $magnitude2);
    }

    /**
     * Stocker un embedding dans la base de données
     */
    public function storeEmbedding($doc_id, $doc_type, $text, $embedding) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // Convertir l'embedding en JSON pour stockage
            $embedding_json = json_encode($embedding);

            $stmt = $pdo->prepare("
                INSERT INTO embeddings (doc_id, doc_type, text_content, embedding, created_at)
                VALUES (:doc_id, :doc_type, :text, :embedding, NOW())
                ON DUPLICATE KEY UPDATE
                    text_content = :text,
                    embedding = :embedding,
                    updated_at = NOW()
            ");

            $stmt->execute([
                'doc_id' => $doc_id,
                'doc_type' => $doc_type,
                'text' => substr($text, 0, 5000), // Limiter le texte stocké
                'embedding' => $embedding_json
            ]);

            return true;

        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                error_log("Database error storing embedding: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Rechercher les documents similaires
     */
    public function searchSimilar($query, $limit = 5, $doc_type = null) {
        // 1. Créer l'embedding de la query
        $query_embedding = $this->createEmbedding($query);

        if (!$query_embedding) {
            return [];
        }

        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // 2. Récupérer tous les embeddings (ou filtrer par type)
            $sql = "SELECT id, doc_id, doc_type, text_content, embedding FROM embeddings";
            if ($doc_type) {
                $sql .= " WHERE doc_type = :doc_type";
            }
            $sql .= " ORDER BY created_at DESC LIMIT 1000"; // Limiter pour performance

            $stmt = $pdo->prepare($sql);
            if ($doc_type) {
                $stmt->execute(['doc_type' => $doc_type]);
            } else {
                $stmt->execute();
            }

            $embeddings = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 3. Calculer les similarités
            $similarities = [];

            foreach ($embeddings as $row) {
                $stored_embedding = json_decode($row['embedding'], true);

                if (!$stored_embedding) continue;

                $similarity = $this->cosineSimilarity($query_embedding, $stored_embedding);

                $similarities[] = [
                    'doc_id' => $row['doc_id'],
                    'doc_type' => $row['doc_type'],
                    'text' => $row['text_content'],
                    'similarity' => $similarity
                ];
            }

            // 4. Trier par similarité décroissante
            usort($similarities, function($a, $b) {
                return $b['similarity'] <=> $a['similarity'];
            });

            // 5. Retourner les top résultats
            return array_slice($similarities, 0, $limit);

        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                error_log("Database error searching embeddings: " . $e->getMessage());
            }
            return [];
        }
    }

    /**
     * Indexer un document complet
     */
    public function indexDocument($doc_id, $doc_type, $title, $content, $chunk_size = 1000) {
        // Découper en chunks si le document est long
        $chunks = $this->chunkText($content, $chunk_size);

        $success_count = 0;

        foreach ($chunks as $index => $chunk) {
            $chunk_id = "{$doc_id}_chunk_{$index}";
            $chunk_text = "{$title}\n\n{$chunk}";

            // Créer l'embedding
            $embedding = $this->createEmbedding($chunk_text);

            if ($embedding) {
                if ($this->storeEmbedding($chunk_id, $doc_type, $chunk_text, $embedding)) {
                    $success_count++;
                }
            }

            // Pause pour ne pas surcharger l'API
            usleep(200000); // 0.2 seconde
        }

        return [
            'chunks_total' => count($chunks),
            'chunks_indexed' => $success_count
        ];
    }

    /**
     * Découper un texte en chunks
     */
    private function chunkText($text, $chunk_size) {
        $chunks = [];
        $sentences = preg_split('/(?<=[.!?])\s+/', $text);

        $current_chunk = '';

        foreach ($sentences as $sentence) {
            if (strlen($current_chunk) + strlen($sentence) > $chunk_size && !empty($current_chunk)) {
                $chunks[] = $current_chunk;
                $current_chunk = $sentence;
            } else {
                $current_chunk .= ' ' . $sentence;
            }
        }

        if (!empty($current_chunk)) {
            $chunks[] = $current_chunk;
        }

        return $chunks;
    }
}

/**
 * Helper function pour recherche rapide
 */
function searchDocuments($query, $limit = 5) {
    $manager = new EmbeddingsManager();
    return $manager->searchSimilar($query, $limit);
}
