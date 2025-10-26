# ⚠️ Solution Erreur MySQL "IF NOT EXISTS"

## 🔴 Le Problème

L'erreur que vous voyez :
```
#1064 - Erreur de syntaxe près de 'IF NOT EXISTS `file_hash`...
```

**Cause :** MySQL (version OVH) ne supporte pas `ADD COLUMN IF NOT EXISTS`. Cette syntaxe existe seulement dans MariaDB.

---

## ✅ SOLUTION SIMPLE (Recommandée)

### Option 1 : Exécuter en 2 Parties

**PARTIE A : Créer les nouvelles tables** (ce qui fonctionne toujours)

**PARTIE B : Modifier la table documents** (seulement si elle existe)

---

## 📝 PARTIE A : Nouvelles Tables (À exécuter en premier)

Copier/coller ce script dans phpMyAdmin :

```sql
-- ============================================================================
-- NOIA v4.0 - PARTIE A : Création des nouvelles tables
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
  INDEX `idx_role` (`role`),
  INDEX `idx_commune` (`commune`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
  INDEX `idx_created` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table statistiques
CREATE TABLE IF NOT EXISTS `statistiques` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `date` DATE NOT NULL UNIQUE,
  `nb_requetes` INT(11) DEFAULT 0,
  `nb_utilisateurs_actifs` INT(11) DEFAULT 0,
  `nb_documents_generes` INT(11) DEFAULT 0,
  `nb_uploads` INT(11) DEFAULT 0,
  `cout_openai_euro` DECIMAL(10, 4) DEFAULT 0.0000,
  `temps_reponse_moyen_ms` INT(11) DEFAULT 0,
  `top_themes` JSON DEFAULT NULL,
  `erreurs_count` INT(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
  INDEX `idx_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table cache_rag
CREATE TABLE IF NOT EXISTS `cache_rag` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `query_hash` VARCHAR(64) NOT NULL UNIQUE,
  `query_text` VARCHAR(500) NOT NULL,
  `results` JSON NOT NULL,
  `hit_count` INT(11) DEFAULT 1,
  `created_at` DATETIME NOT NULL,
  `expires_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Créer l'utilisateur admin
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

SELECT 'Partie A terminée : 8 tables créées + utilisateur admin' AS status;
```

**✅ Cliquer "Exécuter"**

---

## 📝 PARTIE B : Modifier Table Documents (OPTIONNEL)

**⚠️ Exécuter seulement si la table `documents` existe déjà (si vous avez installé Phase 3 RAG)**

Si vous voyez une erreur "Column already exists", **c'est normal**, ignorez-la.

```sql
-- ============================================================================
-- NOIA v4.0 - PARTIE B : Modification table documents (OPTIONNEL)
-- ============================================================================

-- Ajouter colonne file_hash (ignore l'erreur si existe déjà)
ALTER TABLE `documents`
ADD COLUMN `file_hash` VARCHAR(64) DEFAULT NULL COMMENT 'SHA256 hash doublons';

-- Ajouter colonne indexed_chunks (ignore l'erreur si existe déjà)
ALTER TABLE `documents`
ADD COLUMN `indexed_chunks` INT(11) DEFAULT 0 COMMENT 'Nombre chunks';

-- Ajouter colonne priority_score (ignore l'erreur si existe déjà)
ALTER TABLE `documents`
ADD COLUMN `priority_score` INT(11) DEFAULT 5 COMMENT 'Priorité 1-10';

-- Ajouter index (ignore l'erreur si existe déjà)
ALTER TABLE `documents`
ADD INDEX `idx_priority` (`priority_score`);

SELECT 'Partie B terminée : table documents enrichie' AS status;
```

**Si vous voyez des erreurs "Duplicate column name", c'est NORMAL. Cliquez simplement "Exécuter".**

---

## 📝 PARTIE C : Charger la Base Juridique

Exécuter le fichier `legal_facts_data.sql` COMPLET (sans modification).

---

## ✅ Vérification

Après avoir exécuté les 3 parties, vérifier :

```sql
SELECT COUNT(*) as nb_users FROM users;
SELECT COUNT(*) as nb_legal_facts FROM legal_facts;
SHOW TABLES;
```

**Résultat attendu :**
```
nb_users       : 1
nb_legal_facts : 20+
Tables         : 13+ tables
```

---

## 🎯 Résumé des Étapes

1. ✅ **Partie A** : Créer 8 nouvelles tables + admin (OBLIGATOIRE)
2. ⚠️ **Partie B** : Modifier table documents (OPTIONNEL - seulement si table existe)
3. ✅ **Partie C** : Charger base juridique avec legal_facts_data.sql (OBLIGATOIRE)

---

## 💡 Pourquoi cette solution ?

- ✅ Compatible MySQL 5.7+ (OVH)
- ✅ Pas besoin de syntaxe avancée
- ✅ Les erreurs "Duplicate" sont ignorées automatiquement
- ✅ Fonctionne même si certaines tables existent déjà

---

## ❓ Questions Fréquentes

**Q : J'ai une erreur "Duplicate column name"**
R : Normal ! Cela signifie que la colonne existe déjà. Continuez simplement.

**Q : J'ai une erreur "Table already exists"**
R : Normal si vous réexécutez le script. Les tables ne seront pas recréées.

**Q : Dois-je exécuter Partie B ?**
R : Seulement si vous avez déjà la table `documents` (Phase 3 RAG installée).

**Q : Le script est trop long pour phpMyAdmin**
R : Copiez/collez en 3 fois : Partie A, Partie B, Partie C séparément.

---

Dites-moi si vous avez d'autres erreurs ! 🚀
