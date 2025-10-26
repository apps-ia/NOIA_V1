-- ============================================================================
-- NOIA v4.0 - PARTIE B : Modification Table Documents (OPTIONNEL)
-- ============================================================================
-- ⚠️ Exécuter SEULEMENT si vous avez déjà la table `documents`
-- (si vous avez installé NOIA v3.0 avec RAG)
--
-- Si vous voyez des erreurs "Duplicate column name", c'est NORMAL.
-- Cela signifie que les colonnes existent déjà.
-- ============================================================================

-- Ajouter colonne file_hash (pour éviter doublons de fichiers)
ALTER TABLE `documents`
ADD COLUMN `file_hash` VARCHAR(64) DEFAULT NULL COMMENT 'SHA256 hash pour éviter doublons';

-- Ajouter colonne indexed_chunks (nombre de chunks indexés)
ALTER TABLE `documents`
ADD COLUMN `indexed_chunks` INT(11) DEFAULT 0 COMMENT 'Nombre de chunks indexés';

-- Ajouter colonne priority_score (pondération RAG)
ALTER TABLE `documents`
ADD COLUMN `priority_score` INT(11) DEFAULT 5 COMMENT 'Score de priorité (1-10)';

-- Ajouter index sur priority_score
ALTER TABLE `documents`
ADD INDEX `idx_priority` (`priority_score`);

-- Ajouter index unique sur file_hash
ALTER TABLE `documents`
ADD UNIQUE KEY `hash_unique` (`file_hash`);

-- Vérification
SELECT 'PARTIE B TERMINÉE : table documents enrichie avec pondération RAG' AS status;
SELECT COUNT(*) AS nb_documents FROM documents;

-- Afficher la structure de la table documents
SHOW COLUMNS FROM documents;
