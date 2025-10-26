-- ============================================================================
-- NOIA v4.0 - PARTIE A : Création des Nouvelles Tables
-- ============================================================================
-- Compatible MySQL 5.7+ (OVH)
-- Durée : ~5 secondes
-- ============================================================================

-- Table users (authentification multi-niveaux)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
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
  INDEX `idx_commune` (`commune`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table sessions (gestion sessions sécurisées)
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

-- Table historique_conversation (mémoire contextuelle)
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
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table documents_generes (traçabilité documents produits)
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
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table statistiques (métriques quotidiennes)
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

-- Table legal_facts (base juridique validée - PRIORITÉ ABSOLUE)
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
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table access_logs (journalisation sécurité et RGPD)
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

-- Table cache_rag (cache performance)
CREATE TABLE IF NOT EXISTS `cache_rag` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `query_hash` VARCHAR(64) NOT NULL,
  `query_text` VARCHAR(500) NOT NULL,
  `results` JSON NOT NULL,
  `hit_count` INT(11) DEFAULT 1,
  `created_at` DATETIME NOT NULL,
  `expires_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hash_unique` (`query_hash`),
  INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Créer l'utilisateur admin par défaut
-- Mot de passe : "admin123" (À CHANGER IMMÉDIATEMENT !)
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

-- Vérification
SELECT 'PARTIE A TERMINÉE : 8 tables créées + utilisateur admin' AS status;
SELECT COUNT(*) AS nb_users FROM users;
SELECT COUNT(*) AS nb_tables_noia FROM information_schema.tables
WHERE table_schema = DATABASE()
AND table_name IN ('users', 'sessions', 'historique_conversation', 'documents_generes',
                   'statistiques', 'legal_facts', 'access_logs', 'cache_rag');
