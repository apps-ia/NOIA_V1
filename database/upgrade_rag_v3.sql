-- ============================================================================
-- NOIA v3.0 - Upgrade SQL pour RAG et Recherche Web
-- ============================================================================
-- Ce script ajoute les tables nécessaires pour :
-- - Stockage des documents uploadés
-- - Stockage des embeddings pour la recherche sémantique (RAG)
-- ============================================================================

-- Table pour stocker les métadonnées des documents
CREATE TABLE IF NOT EXISTS `documents` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `filename` VARCHAR(255) NOT NULL COMMENT 'Nom du fichier stocké',
  `original_name` VARCHAR(255) NOT NULL COMMENT 'Nom original du fichier',
  `category` VARCHAR(100) DEFAULT 'general' COMMENT 'Catégorie (fctva, rh, juridique, etc.)',
  `commune` VARCHAR(100) DEFAULT 'general' COMMENT 'Commune concernée',
  `type` VARCHAR(20) NOT NULL COMMENT 'Extension du fichier (pdf, docx, txt)',
  `size` INT(11) DEFAULT NULL COMMENT 'Taille en octets',
  `uploaded_at` DATETIME NOT NULL COMMENT 'Date d\'upload',
  `updated_at` DATETIME DEFAULT NULL COMMENT 'Date de dernière modification',
  PRIMARY KEY (`id`),
  INDEX `idx_commune` (`commune`),
  INDEX `idx_category` (`category`),
  INDEX `idx_uploaded` (`uploaded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Documents de la base documentaire';

-- Table pour stocker les embeddings (vecteurs)
CREATE TABLE IF NOT EXISTS `embeddings` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `doc_id` VARCHAR(100) NOT NULL COMMENT 'ID du document (ou chunk_id)',
  `doc_type` VARCHAR(50) DEFAULT 'document' COMMENT 'Type de document',
  `text_content` TEXT COMMENT 'Extrait de texte (pour référence)',
  `embedding` LONGTEXT NOT NULL COMMENT 'Vecteur embedding (JSON)',
  `created_at` DATETIME NOT NULL COMMENT 'Date de création',
  `updated_at` DATETIME DEFAULT NULL COMMENT 'Date de mise à jour',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_doc` (`doc_id`, `doc_type`),
  INDEX `idx_doc_type` (`doc_type`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Embeddings pour recherche sémantique (RAG)';

-- Vérifier que les tables base_centrale et base_locale existent toujours
-- (elles ne sont pas modifiées, juste vérification)

-- Afficher les tables créées
SELECT 'Tables créées avec succès :' AS status;
SHOW TABLES LIKE 'documents';
SHOW TABLES LIKE 'embeddings';

-- Statistiques
SELECT
  'Prêt pour RAG et Recherche Web !' AS message,
  (SELECT COUNT(*) FROM documents) AS total_documents,
  (SELECT COUNT(*) FROM embeddings) AS total_embeddings;
