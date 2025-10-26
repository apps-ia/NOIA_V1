-- ============================================================================
-- NOIA v4.0 - Schéma Complet Base de Données
-- ============================================================================
-- Ce script crée toutes les tables nécessaires pour NOIA v4.0 :
-- - Authentification multi-niveaux
-- - Historique conversationnel avec mémoire contextuelle
-- - Documents générés avec traçabilité
-- - Statistiques et pilotage
-- - Base juridique interne (legal_facts)
-- - Amélioration des tables existantes
-- ============================================================================

-- ============================================================================
-- 1. TABLE USERS - Authentification multi-niveaux
-- ============================================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL UNIQUE COMMENT 'Email utilisateur (identifiant unique)',
  `password_hash` VARCHAR(255) NOT NULL COMMENT 'Hash bcrypt du mot de passe',
  `nom` VARCHAR(100) DEFAULT NULL COMMENT 'Nom de l\'utilisateur',
  `prenom` VARCHAR(100) DEFAULT NULL COMMENT 'Prénom de l\'utilisateur',
  `role` ENUM('admin', 'agent', 'guest') DEFAULT 'agent' COMMENT 'Niveau d\'accès',
  `commune` VARCHAR(100) DEFAULT NULL COMMENT 'Commune de rattachement (optionnel)',
  `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Compte actif ou désactivé',
  `created_at` DATETIME NOT NULL COMMENT 'Date de création',
  `last_login` DATETIME DEFAULT NULL COMMENT 'Dernière connexion',
  `login_attempts` INT(11) DEFAULT 0 COMMENT 'Nombre de tentatives échouées',
  `locked_until` DATETIME DEFAULT NULL COMMENT 'Verrouillage temporaire après échecs',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_unique` (`email`),
  INDEX `idx_role` (`role`),
  INDEX `idx_commune` (`commune`),
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Utilisateurs avec authentification';

-- ============================================================================
-- 2. TABLE SESSIONS - Gestion des sessions utilisateurs
-- ============================================================================
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` VARCHAR(128) NOT NULL COMMENT 'Session ID (hash)',
  `user_id` INT(11) UNSIGNED NOT NULL COMMENT 'Utilisateur lié',
  `token` VARCHAR(255) NOT NULL COMMENT 'Token CSRF',
  `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'Adresse IP',
  `user_agent` VARCHAR(255) DEFAULT NULL COMMENT 'Navigateur',
  `created_at` DATETIME NOT NULL COMMENT 'Création de la session',
  `expires_at` DATETIME NOT NULL COMMENT 'Expiration',
  `last_activity` DATETIME NOT NULL COMMENT 'Dernière activité',
  PRIMARY KEY (`id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_expires` (`expires_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sessions utilisateurs actives';

-- ============================================================================
-- 3. TABLE HISTORIQUE_CONVERSATION - Mémoire contextuelle
-- ============================================================================
CREATE TABLE IF NOT EXISTS `historique_conversation` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL COMMENT 'Utilisateur qui a posé la question',
  `session_id` VARCHAR(128) DEFAULT NULL COMMENT 'Session pour regroupement',
  `question` TEXT NOT NULL COMMENT 'Question posée',
  `reponse` LONGTEXT NOT NULL COMMENT 'Réponse de NOIA',
  `sources_utilisees` JSON DEFAULT NULL COMMENT 'Sources consultées (web, RAG, legal_facts)',
  `contexte_detecte` JSON DEFAULT NULL COMMENT 'Entités extraites (thème, commune, etc.)',
  `cout_tokens` INT(11) DEFAULT 0 COMMENT 'Nombre de tokens consommés',
  `cout_euro` DECIMAL(10, 6) DEFAULT 0.000000 COMMENT 'Coût estimé en euros',
  `duree_ms` INT(11) DEFAULT 0 COMMENT 'Temps de réponse en millisecondes',
  `created_at` DATETIME NOT NULL COMMENT 'Date de la question',
  `feedback` ENUM('positive', 'negative', 'neutral') DEFAULT NULL COMMENT 'Retour utilisateur (futur)',
  PRIMARY KEY (`id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_session_id` (`session_id`),
  INDEX `idx_created` (`created_at`),
  FULLTEXT KEY `ft_question` (`question`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historique des conversations avec mémoire contextuelle';

-- ============================================================================
-- 4. TABLE DOCUMENTS_GENERES - Traçabilité documents produits
-- ============================================================================
CREATE TABLE IF NOT EXISTS `documents_generes` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL COMMENT 'Utilisateur créateur',
  `type` ENUM('deliberation', 'arrete', 'courrier', 'note', 'modele', 'autre') NOT NULL COMMENT 'Type de document',
  `titre` VARCHAR(255) NOT NULL COMMENT 'Titre du document',
  `contenu` LONGTEXT NOT NULL COMMENT 'Contenu complet (HTML ou texte)',
  `metadata` JSON DEFAULT NULL COMMENT 'Métadonnées (date séance, numéro, etc.)',
  `format` ENUM('html', 'docx', 'pdf', 'txt') DEFAULT 'html' COMMENT 'Format du document',
  `file_path` VARCHAR(500) DEFAULT NULL COMMENT 'Chemin si fichier généré',
  `commune` VARCHAR(100) DEFAULT NULL COMMENT 'Commune concernée',
  `created_at` DATETIME NOT NULL COMMENT 'Date de génération',
  `updated_at` DATETIME DEFAULT NULL COMMENT 'Dernière modification',
  PRIMARY KEY (`id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_type` (`type`),
  INDEX `idx_commune` (`commune`),
  INDEX `idx_created` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Documents produits par NOIA';

-- ============================================================================
-- 5. TABLE STATISTIQUES - Pilotage performance
-- ============================================================================
CREATE TABLE IF NOT EXISTS `statistiques` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `date` DATE NOT NULL COMMENT 'Date du relevé (quotidien)',
  `nb_requetes` INT(11) DEFAULT 0 COMMENT 'Nombre total de requêtes',
  `nb_utilisateurs_actifs` INT(11) DEFAULT 0 COMMENT 'Utilisateurs uniques',
  `nb_documents_generes` INT(11) DEFAULT 0 COMMENT 'Documents créés',
  `nb_uploads` INT(11) DEFAULT 0 COMMENT 'Documents uploadés',
  `cout_openai_euro` DECIMAL(10, 4) DEFAULT 0.0000 COMMENT 'Coût total OpenAI',
  `temps_reponse_moyen_ms` INT(11) DEFAULT 0 COMMENT 'Temps moyen de réponse',
  `top_themes` JSON DEFAULT NULL COMMENT 'Thèmes les plus consultés (classement)',
  `erreurs_count` INT(11) DEFAULT 0 COMMENT 'Nombre d\'erreurs',
  PRIMARY KEY (`id`),
  UNIQUE KEY `date_unique` (`date`),
  INDEX `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Statistiques quotidiennes de performance';

-- ============================================================================
-- 6. TABLE LEGAL_FACTS - Base juridique interne validée
-- ============================================================================
CREATE TABLE IF NOT EXISTS `legal_facts` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `categorie` ENUM('quorum', 'fctva', 'ifse', 'cgct', 'm57', 'rh', 'marches', 'budget', 'deliberation', 'autre') NOT NULL COMMENT 'Catégorie juridique',
  `titre` VARCHAR(255) NOT NULL COMMENT 'Titre de la règle',
  `reference_legale` VARCHAR(255) NOT NULL COMMENT 'Article exact (ex: L2121-17 CGCT)',
  `contenu` TEXT NOT NULL COMMENT 'Règle juridique validée',
  `priority` INT(11) DEFAULT 10 COMMENT 'Priorité (1-10, 10 = max)',
  `source_url` VARCHAR(500) DEFAULT NULL COMMENT 'URL Légifrance ou source officielle',
  `date_maj` DATE NOT NULL COMMENT 'Date de dernière mise à jour',
  `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Règle active ou obsolète',
  PRIMARY KEY (`id`),
  INDEX `idx_categorie` (`categorie`),
  INDEX `idx_priority` (`priority`),
  INDEX `idx_active` (`is_active`),
  FULLTEXT KEY `ft_content` (`titre`, `contenu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Base de faits juridiques validés (priorité sur RAG)';

-- ============================================================================
-- 7. AMÉLIORATION TABLE DOCUMENTS (existante)
-- ============================================================================
ALTER TABLE `documents`
  ADD COLUMN IF NOT EXISTS `file_hash` VARCHAR(64) DEFAULT NULL COMMENT 'SHA256 hash pour éviter doublons' AFTER `filename`,
  ADD COLUMN IF NOT EXISTS `indexed_chunks` INT(11) DEFAULT 0 COMMENT 'Nombre de chunks indexés' AFTER `type`,
  ADD COLUMN IF NOT EXISTS `priority_score` INT(11) DEFAULT 5 COMMENT 'Score de priorité (1-10)' AFTER `indexed_chunks`,
  ADD UNIQUE KEY IF NOT EXISTS `hash_unique` (`file_hash`),
  ADD INDEX IF NOT EXISTS `idx_priority` (`priority_score`);

-- ============================================================================
-- 8. TABLE ACCESS_LOGS - Journalisation des accès (RGPD)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `access_logs` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED DEFAULT NULL COMMENT 'Utilisateur (NULL si anonyme)',
  `action` VARCHAR(100) NOT NULL COMMENT 'Action effectuée (login, query, upload, etc.)',
  `endpoint` VARCHAR(255) DEFAULT NULL COMMENT 'Endpoint API appelé',
  `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'Adresse IP',
  `user_agent` VARCHAR(255) DEFAULT NULL COMMENT 'Navigateur',
  `status` ENUM('success', 'error', 'blocked') DEFAULT 'success' COMMENT 'Résultat',
  `error_message` TEXT DEFAULT NULL COMMENT 'Message d\'erreur si échec',
  `created_at` DATETIME NOT NULL COMMENT 'Timestamp',
  PRIMARY KEY (`id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_action` (`action`),
  INDEX `idx_created` (`created_at`),
  INDEX `idx_ip` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Journalisation des accès pour sécurité et RGPD';

-- ============================================================================
-- 9. TABLE CACHE_RAG - Cache des résultats RAG (performance)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `cache_rag` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `query_hash` VARCHAR(64) NOT NULL UNIQUE COMMENT 'Hash MD5 de la question',
  `query_text` VARCHAR(500) NOT NULL COMMENT 'Question d\'origine',
  `results` JSON NOT NULL COMMENT 'Résultats RAG mis en cache',
  `hit_count` INT(11) DEFAULT 1 COMMENT 'Nombre d\'utilisations du cache',
  `created_at` DATETIME NOT NULL COMMENT 'Date de création',
  `expires_at` DATETIME NOT NULL COMMENT 'Expiration (1 heure par défaut)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `hash_unique` (`query_hash`),
  INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cache des résultats RAG pour performance';

-- ============================================================================
-- 10. INSERTION DE DONNÉES INITIALES
-- ============================================================================

-- Utilisateur admin par défaut (mot de passe : "admin123" - À CHANGER!)
-- Hash généré avec password_hash("admin123", PASSWORD_BCRYPT)
INSERT INTO `users` (`email`, `password_hash`, `nom`, `prenom`, `role`, `is_active`, `created_at`)
VALUES (
  'admin@noia.local',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  'Administrateur',
  'NOIA',
  'admin',
  1,
  NOW()
) ON DUPLICATE KEY UPDATE `email` = `email`;

-- Faits juridiques de base (exemples critiques)
INSERT INTO `legal_facts` (`categorie`, `titre`, `reference_legale`, `contenu`, `priority`, `source_url`, `date_maj`, `is_active`) VALUES
('quorum', 'Quorum conseil municipal - Première convocation', 'Article L2121-17 du CGCT', 'Le conseil municipal ne délibère valablement que lorsque la majorité absolue de ses membres en exercice est présente. Si, après une première convocation, ce quorum n\'est pas atteint, le conseil est à nouveau convoqué à trois jours au moins d\'intervalle. Il délibère alors valablement sans condition de quorum.', 10, 'https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000006389873', CURDATE(), 1),

('quorum', 'Quorum conseil municipal - Reconvocation (3 jours)', 'Article L2121-17 du CGCT', 'Lors d\'une reconvocation après absence de quorum à la première séance, le conseil municipal peut délibérer SANS CONDITION DE QUORUM. Délai minimum entre les deux convocations : 3 jours francs.', 10, 'https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000006389873', CURDATE(), 1),

('fctva', 'FCTVA - Comptes d\'imputation investissement', 'Article L1615-1 du CGCT + Instruction M57', 'Les dépenses éligibles à la FCTVA s\'imputent sur les comptes : 2131 (Bâtiments publics), 2135 (Installations générales), 2313 (Immobilisations en cours). Pour le fonctionnement : compte 615221 (Entretien bâtiments publics).', 10, 'https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000006389404', CURDATE(), 1),

('fctva', 'FCTVA - Taux de compensation', 'Arrêté du 30 janvier 2024', 'Le taux de FCTVA applicable en 2024 est de 16,404 % pour les dépenses réelles d\'investissement éligibles.', 9, 'https://www.legifrance.gouv.fr/jorf/id/JORFTEXT000049078307', CURDATE(), 1),

('cgct', 'Délai de convocation conseil municipal', 'Article L2121-11 du CGCT', 'Les membres du conseil municipal sont convoqués par le maire au moins 5 jours francs avant la réunion. En cas d\'urgence, ce délai peut être réduit à 1 jour franc.', 9, 'https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000006389869', CURDATE(), 1),

('deliberation', 'Majorité absolue pour délibération', 'Article L2121-20 du CGCT', 'Les délibérations sont prises à la majorité absolue des suffrages exprimés. En cas de partage des voix, celle du maire est prépondérante.', 9, 'https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000006389878', CURDATE(), 1),

('m57', 'Nomenclature M57 - Compte 2131', 'Instruction M57 DGFiP', 'Compte 2131 : Bâtiments publics. Concerne les constructions édifiées sur sol propre ou sur sol d\'autrui (mairies, écoles, salles communales, etc.). Éligible FCTVA si respecte conditions légales.', 9, 'https://www.collectivites-locales.gouv.fr/finances-locales/instruction-budgetaire-et-comptable-m57', CURDATE(), 1)
ON DUPLICATE KEY UPDATE `titre` = VALUES(`titre`);

-- ============================================================================
-- 11. VUES UTILES
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

-- Vue : Requêtes récentes avec utilisateur
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
-- 12. ÉVÉNEMENTS AUTOMATIQUES (nettoyage)
-- ============================================================================

-- Nettoyage automatique des sessions expirées (toutes les heures)
DELIMITER $$
CREATE EVENT IF NOT EXISTS `cleanup_expired_sessions`
ON SCHEDULE EVERY 1 HOUR
DO BEGIN
  DELETE FROM sessions WHERE expires_at < NOW();
END$$

-- Nettoyage du cache RAG expiré (toutes les heures)
CREATE EVENT IF NOT EXISTS `cleanup_expired_cache`
ON SCHEDULE EVERY 1 HOUR
DO BEGIN
  DELETE FROM cache_rag WHERE expires_at < NOW();
END$$

-- Archivage des logs anciens (tous les mois)
CREATE EVENT IF NOT EXISTS `archive_old_logs`
ON SCHEDULE EVERY 1 MONTH
DO BEGIN
  DELETE FROM access_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 12 MONTH);
END$$
DELIMITER ;

-- ============================================================================
-- RÉSUMÉ
-- ============================================================================
SELECT 'Migration NOIA v4.0 terminée !' AS status;

SHOW TABLES LIKE 'users';
SHOW TABLES LIKE 'sessions';
SHOW TABLES LIKE 'historique_conversation';
SHOW TABLES LIKE 'documents_generes';
SHOW TABLES LIKE 'statistiques';
SHOW TABLES LIKE 'legal_facts';
SHOW TABLES LIKE 'access_logs';
SHOW TABLES LIKE 'cache_rag';

SELECT
  'Tables créées avec succès' AS message,
  (SELECT COUNT(*) FROM users) AS nb_users,
  (SELECT COUNT(*) FROM legal_facts) AS nb_legal_facts,
  (SELECT COUNT(*) FROM documents) AS nb_documents,
  (SELECT COUNT(*) FROM embeddings) AS nb_embeddings;
