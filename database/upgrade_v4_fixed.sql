-- ============================================================================
-- NOIA v4.0 - Script de Migration Corrigé (Compatible MySQL 5.7+)
-- ============================================================================
-- IMPORTANT : Ce script gère les erreurs si les colonnes existent déjà
-- ============================================================================

-- ============================================================================
-- 1. TABLES PRINCIPALES
-- ============================================================================

-- Table users
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `nom` VARCHAR(100) DEFAULT NULL,
  `prenom` VARCHAR(100) DEFAULT NULL,
  `role` ENUM('admin', 'agent', 'guest') DEFAULT 'agent',
  `commune` VARCHAR(100) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME NOT NULL,
  `last_login` DATETIME DEFAULT NULL,
  `login_attempts` INT(11) DEFAULT 0,
  `locked_until` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_unique` (`email`),
  INDEX `idx_role` (`role`),
  INDEX `idx_commune` (`commune`),
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table sessions
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` VARCHAR(128) NOT NULL,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `last_activity` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_expires` (`expires_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table historique_conversation
CREATE TABLE IF NOT EXISTS `historique_conversation` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `session_id` VARCHAR(128) DEFAULT NULL,
  `question` TEXT NOT NULL,
  `reponse` LONGTEXT NOT NULL,
  `sources_utilisees` JSON DEFAULT NULL,
  `contexte_detecte` JSON DEFAULT NULL,
  `cout_tokens` INT(11) DEFAULT 0,
  `cout_euro` DECIMAL(10, 6) DEFAULT 0.000000,
  `duree_ms` INT(11) DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  `feedback` ENUM('positive', 'negative', 'neutral') DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_session_id` (`session_id`),
  INDEX `idx_created` (`created_at`),
  FULLTEXT KEY `ft_question` (`question`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table documents_generes
CREATE TABLE IF NOT EXISTS `documents_generes` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `type` ENUM('deliberation', 'arrete', 'courrier', 'note', 'modele', 'autre') NOT NULL,
  `titre` VARCHAR(255) NOT NULL,
  `contenu` LONGTEXT NOT NULL,
  `metadata` JSON DEFAULT NULL,
  `format` ENUM('html', 'docx', 'pdf', 'txt') DEFAULT 'html',
  `file_path` VARCHAR(500) DEFAULT NULL,
  `commune` VARCHAR(100) DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_type` (`type`),
  INDEX `idx_commune` (`commune`),
  INDEX `idx_created` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table statistiques
CREATE TABLE IF NOT EXISTS `statistiques` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `date` DATE NOT NULL,
  `nb_requetes` INT(11) DEFAULT 0,
  `nb_utilisateurs_actifs` INT(11) DEFAULT 0,
  `nb_documents_generes` INT(11) DEFAULT 0,
  `nb_uploads` INT(11) DEFAULT 0,
  `cout_openai_euro` DECIMAL(10, 4) DEFAULT 0.0000,
  `temps_reponse_moyen_ms` INT(11) DEFAULT 0,
  `top_themes` JSON DEFAULT NULL,
  `erreurs_count` INT(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `date_unique` (`date`),
  INDEX `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table legal_facts
CREATE TABLE IF NOT EXISTS `legal_facts` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `categorie` ENUM('quorum', 'fctva', 'ifse', 'cgct', 'm57', 'rh', 'marches', 'budget', 'deliberation', 'autre') NOT NULL,
  `titre` VARCHAR(255) NOT NULL,
  `reference_legale` VARCHAR(255) NOT NULL,
  `contenu` TEXT NOT NULL,
  `priority` INT(11) DEFAULT 10,
  `source_url` VARCHAR(500) DEFAULT NULL,
  `date_maj` DATE NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  INDEX `idx_categorie` (`categorie`),
  INDEX `idx_priority` (`priority`),
  INDEX `idx_active` (`is_active`),
  FULLTEXT KEY `ft_content` (`titre`, `contenu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table access_logs
CREATE TABLE IF NOT EXISTS `access_logs` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `endpoint` VARCHAR(255) DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('success', 'error', 'blocked') DEFAULT 'success',
  `error_message` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_action` (`action`),
  INDEX `idx_created` (`created_at`),
  INDEX `idx_ip` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table cache_rag
CREATE TABLE IF NOT EXISTS `cache_rag` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `query_hash` VARCHAR(64) NOT NULL UNIQUE,
  `query_text` VARCHAR(500) NOT NULL,
  `results` JSON NOT NULL,
  `hit_count` INT(11) DEFAULT 1,
  `created_at` DATETIME NOT NULL,
  `expires_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hash_unique` (`query_hash`),
  INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2. MODIFICATION TABLE DOCUMENTS (si elle existe déjà)
-- ============================================================================

-- Vérifier si la table documents existe
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables
                     WHERE table_schema = DATABASE()
                     AND table_name = 'documents');

-- Ajouter les colonnes seulement si la table existe
SET @sql_file_hash = IF(@table_exists > 0,
  'ALTER TABLE `documents` ADD COLUMN `file_hash` VARCHAR(64) DEFAULT NULL COMMENT "SHA256 hash pour éviter doublons" AFTER `filename`',
  'SELECT "Table documents n\'existe pas" AS info'
);

SET @sql_indexed_chunks = IF(@table_exists > 0,
  'ALTER TABLE `documents` ADD COLUMN `indexed_chunks` INT(11) DEFAULT 0 COMMENT "Nombre de chunks indexés" AFTER `type`',
  'SELECT "Skip" AS info'
);

SET @sql_priority_score = IF(@table_exists > 0,
  'ALTER TABLE `documents` ADD COLUMN `priority_score` INT(11) DEFAULT 5 COMMENT "Score de priorité (1-10)" AFTER `indexed_chunks`',
  'SELECT "Skip" AS info'
);

-- Exécuter les ALTER TABLE (ignore les erreurs si colonnes existent déjà)
PREPARE stmt FROM @sql_file_hash;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

PREPARE stmt FROM @sql_indexed_chunks;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

PREPARE stmt FROM @sql_priority_score;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajouter les index (ignore si existent déjà)
SET @sql_add_index1 = IF(@table_exists > 0,
  'ALTER TABLE `documents` ADD UNIQUE KEY `hash_unique` (`file_hash`)',
  'SELECT "Skip" AS info'
);

SET @sql_add_index2 = IF(@table_exists > 0,
  'ALTER TABLE `documents` ADD INDEX `idx_priority` (`priority_score`)',
  'SELECT "Skip" AS info'
);

PREPARE stmt FROM @sql_add_index1;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

PREPARE stmt FROM @sql_add_index2;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- 3. DONNÉES INITIALES
-- ============================================================================

-- Utilisateur admin par défaut (mot de passe : "admin123" - À CHANGER!)
INSERT IGNORE INTO `users` (`email`, `password_hash`, `nom`, `prenom`, `role`, `is_active`, `created_at`)
VALUES (
  'admin@noia.local',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  'Administrateur',
  'NOIA',
  'admin',
  1,
  NOW()
);

-- ============================================================================
-- 4. VUES UTILES
-- ============================================================================

-- Vue : Statistiques utilisateurs
CREATE OR REPLACE VIEW `v_user_stats` AS
SELECT
  u.id,
  u.email,
  u.nom,
  u.prenom,
  u.role,
  u.commune,
  COUNT(DISTINCT h.id) as nb_requetes,
  COUNT(DISTINCT d.id) as nb_documents_generes,
  SUM(h.cout_euro) as cout_total_euro,
  MAX(h.created_at) as derniere_activite
FROM users u
LEFT JOIN historique_conversation h ON u.id = h.user_id
LEFT JOIN documents_generes d ON u.id = d.user_id
GROUP BY u.id;

-- Vue : Requêtes récentes
CREATE OR REPLACE VIEW `v_recent_queries` AS
SELECT
  h.id,
  u.email,
  u.nom,
  u.prenom,
  h.question,
  h.created_at,
  h.duree_ms,
  h.cout_euro,
  JSON_LENGTH(h.sources_utilisees) as nb_sources
FROM historique_conversation h
JOIN users u ON h.user_id = u.id
ORDER BY h.created_at DESC
LIMIT 100;

-- ============================================================================
-- 5. ÉVÉNEMENTS AUTOMATIQUES
-- ============================================================================

DELIMITER $$

-- Nettoyage sessions expirées
DROP EVENT IF EXISTS `cleanup_expired_sessions`$$
CREATE EVENT `cleanup_expired_sessions`
ON SCHEDULE EVERY 1 HOUR
DO BEGIN
  DELETE FROM sessions WHERE expires_at < NOW();
END$$

-- Nettoyage cache RAG expiré
DROP EVENT IF EXISTS `cleanup_expired_cache`$$
CREATE EVENT `cleanup_expired_cache`
ON SCHEDULE EVERY 1 HOUR
DO BEGIN
  DELETE FROM cache_rag WHERE expires_at < NOW();
END$$

-- Archivage logs anciens
DROP EVENT IF EXISTS `archive_old_logs`$$
CREATE EVENT `archive_old_logs`
ON SCHEDULE EVERY 1 MONTH
DO BEGIN
  DELETE FROM access_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 12 MONTH);
END$$

DELIMITER ;

-- ============================================================================
-- RÉSUMÉ
-- ============================================================================
SELECT 'Migration NOIA v4.0 terminée avec succès !' AS status;

SELECT
  'Vérification des tables' AS message,
  (SELECT COUNT(*) FROM users) AS nb_users,
  (SELECT COUNT(*) FROM legal_facts) AS nb_legal_facts,
  (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name LIKE '%') AS nb_total_tables;
