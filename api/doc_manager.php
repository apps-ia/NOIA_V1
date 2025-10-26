<?php
/**
 * NOIA - Document Manager
 * Gestion des documents de la base documentaire (upload, indexation, cloud)
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/embeddings.php';

class DocumentManager {

    private $upload_dir;
    private $embeddings_manager;

    public function __construct() {
        $this->upload_dir = DOCUMENTS_DIR;
        $this->embeddings_manager = new EmbeddingsManager();

        // Créer le dossier s'il n'existe pas
        if (!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0755, true);
        }
    }

    /**
     * Upload un document
     */
    public function uploadDocument($file, $category = 'general', $commune = 'general') {
        // Validation
        $allowed_types = ['pdf', 'docx', 'txt', 'md', 'odt'];
        $max_size = 10 * 1024 * 1024; // 10 MB

        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($file_ext, $allowed_types)) {
            return ['success' => false, 'error' => 'Type de fichier non autorisé'];
        }

        if ($file['size'] > $max_size) {
            return ['success' => false, 'error' => 'Fichier trop volumineux (max 10 MB)'];
        }

        // Générer un nom unique
        $safe_name = preg_replace('/[^a-z0-9_-]/i', '_', pathinfo($file['name'], PATHINFO_FILENAME));
        $unique_name = $safe_name . '_' . time() . '.' . $file_ext;
        $file_path = $this->upload_dir . '/' . $unique_name;

        // Déplacer le fichier
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            return ['success' => false, 'error' => 'Erreur lors de l\'upload'];
        }

        // Extraire le texte
        $text = $this->extractText($file_path, $file_ext);

        if (!$text) {
            return ['success' => false, 'error' => 'Impossible d\'extraire le texte du document'];
        }

        // Stocker en base
        $doc_id = $this->storeDocumentMetadata($unique_name, $file['name'], $category, $commune, $file_ext);

        if (!$doc_id) {
            return ['success' => false, 'error' => 'Erreur lors de l\'enregistrement en base'];
        }

        // Indexer avec embeddings
        $indexation = $this->embeddings_manager->indexDocument(
            $doc_id,
            'document',
            $file['name'],
            $text
        );

        return [
            'success' => true,
            'doc_id' => $doc_id,
            'filename' => $unique_name,
            'size' => $file['size'],
            'indexation' => $indexation
        ];
    }

    /**
     * Extraire le texte selon le type de fichier
     */
    private function extractText($file_path, $extension) {
        switch ($extension) {
            case 'txt':
            case 'md':
                return file_get_contents($file_path);

            case 'pdf':
                return $this->extractPdfText($file_path);

            case 'docx':
                return $this->extractDocxText($file_path);

            default:
                return null;
        }
    }

    /**
     * Extraire texte d'un PDF (nécessite pdftotext ou gs)
     */
    private function extractPdfText($file_path) {
        // Méthode 1 : pdftotext (si installé)
        if (shell_exec('which pdftotext')) {
            $output = shell_exec("pdftotext " . escapeshellarg($file_path) . " -");
            if ($output) return $output;
        }

        // Méthode 2 : lecture basique (extraction limitée)
        $content = file_get_contents($file_path);

        // Extraction très basique du texte PDF (ne fonctionne que pour les PDF simples)
        preg_match_all('/\((.*?)\)/s', $content, $matches);
        return implode(' ', $matches[1]);
    }

    /**
     * Extraire texte d'un DOCX
     */
    private function extractDocxText($file_path) {
        // DOCX est un ZIP contenant document.xml
        $zip = new ZipArchive();

        if ($zip->open($file_path) === true) {
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();

            if ($xml) {
                // Extraire le texte des balises <w:t>
                $text = strip_tags($xml);
                return $text;
            }
        }

        return null;
    }

    /**
     * Stocker les métadonnées du document en BDD
     */
    private function storeDocumentMetadata($filename, $original_name, $category, $commune, $type) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $stmt = $pdo->prepare("
                INSERT INTO documents (filename, original_name, category, commune, type, uploaded_at)
                VALUES (:filename, :original_name, :category, :commune, :type, NOW())
            ");

            $stmt->execute([
                'filename' => $filename,
                'original_name' => $original_name,
                'category' => $category,
                'commune' => $commune,
                'type' => $type
            ]);

            return $pdo->lastInsertId();

        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                error_log("Database error: " . $e->getMessage());
            }
            return null;
        }
    }

    /**
     * Lister les documents
     */
    public function listDocuments($commune = null, $category = null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $sql = "SELECT * FROM documents WHERE 1=1";
            $params = [];

            if ($commune) {
                $sql .= " AND commune = :commune";
                $params['commune'] = $commune;
            }

            if ($category) {
                $sql .= " AND category = :category";
                $params['category'] = $category;
            }

            $sql .= " ORDER BY uploaded_at DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                error_log("Database error: " . $e->getMessage());
            }
            return [];
        }
    }

    /**
     * Supprimer un document
     */
    public function deleteDocument($doc_id) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // Récupérer le nom du fichier
            $stmt = $pdo->prepare("SELECT filename FROM documents WHERE id = :id");
            $stmt->execute(['id' => $doc_id]);
            $doc = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$doc) {
                return ['success' => false, 'error' => 'Document non trouvé'];
            }

            // Supprimer le fichier
            $file_path = $this->upload_dir . '/' . $doc['filename'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }

            // Supprimer les embeddings
            $stmt = $pdo->prepare("DELETE FROM embeddings WHERE doc_id LIKE :doc_id");
            $stmt->execute(['doc_id' => $doc_id . '%']);

            // Supprimer de la table documents
            $stmt = $pdo->prepare("DELETE FROM documents WHERE id = :id");
            $stmt->execute(['id' => $doc_id]);

            return ['success' => true];

        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                error_log("Database error: " . $e->getMessage());
            }
            return ['success' => false, 'error' => 'Erreur de suppression'];
        }
    }
}

// ============================================================================
// API ENDPOINTS
// ============================================================================

// Gestion CORS
header('Access-Control-Allow-Origin: ' . (ALLOWED_ORIGINS === '*' ? '*' : ALLOWED_ORIGINS));
header('Access-Control-Allow-Methods: POST, GET, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$manager = new DocumentManager();

// Upload de document
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    $category = $_POST['category'] ?? 'general';
    $commune = $_POST['commune'] ?? 'general';

    $result = $manager->uploadDocument($_FILES['document'], $category, $commune);
    echo json_encode($result);
    exit;
}

// Liste des documents
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $commune = $_GET['commune'] ?? null;
    $category = $_GET['category'] ?? null;

    $documents = $manager->listDocuments($commune, $category);
    echo json_encode(['success' => true, 'documents' => $documents]);
    exit;
}

// Suppression
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $doc_id = $input['doc_id'] ?? null;

    if (!$doc_id) {
        echo json_encode(['success' => false, 'error' => 'doc_id manquant']);
        exit;
    }

    $result = $manager->deleteDocument($doc_id);
    echo json_encode($result);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Requête invalide']);
