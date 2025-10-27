# 🚀 NOIA v4.0 - Démarrage Rapide Login

## ✅ Checklist Installation (5 minutes)

### Étape 1 : Base de données (phpMyAdmin)

**A. Vérifier que les tables existent :**
```sql
SHOW TABLES LIKE '%users%';
```

**Résultat attendu :** Vous devez voir la table `users`

**Si la table n'existe pas :**
```sql
-- Exécuter le fichier : database/upgrade_v4_PARTIE_A.sql
```

---

### Étape 2 : Créer/Réinitialiser le compte admin

**Copier/coller cette requête dans phpMyAdmin :**

```sql
-- Supprimer l'ancien compte si existe
DELETE FROM users WHERE email = 'admin@noia.local';

-- Créer le compte admin avec mot de passe "admin123"
INSERT INTO users (
  email,
  password_hash,
  nom,
  prenom,
  role,
  is_active,
  login_attempts,
  locked_until,
  created_at
) VALUES (
  'admin@noia.local',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  'Administrateur',
  'NOIA',
  'admin',
  1,
  0,
  NULL,
  NOW()
);

-- Vérifier
SELECT id, email, role, is_active, login_attempts, locked_until
FROM users
WHERE email = 'admin@noia.local';
```

**Résultat attendu :**
```
id: 1
email: admin@noia.local
role: admin
is_active: 1
login_attempts: 0
locked_until: NULL
```

---

### Étape 3 : Vérifier les fichiers API (FTP)

**Fichiers obligatoires :**
```
✅ /www/noia/api/auth.php
✅ /www/noia/api/auth/login.php
✅ /www/noia/api/auth/check.php
✅ /www/noia/api/auth/logout.php
✅ /www/noia/login.html
```

**Permissions (via FileZilla) :**
- Fichiers `.php` : **644** (rw-r--r--)
- Dossiers : **755** (rwxr-xr-x)

---

### Étape 4 : Tester la connexion

**A. Ouvrir la page de login :**
```
https://noia.votre-domaine.fr/login.html
```

**B. Se connecter avec :**
```
Email    : admin@noia.local
Password : admin123
```

**C. Résultats possibles :**

✅ **Succès :** Message "Connexion réussie ! Redirection..." → Erreur 404 normale (dashboard pas encore créé)

❌ **Échec :** Voir section dépannage ci-dessous

---

## 🔧 Dépannage Express

### Erreur : "Identifiants invalides"

**Cause 1 : Hash incorrect**

**Solution :**
```sql
-- Réexécuter l'INSERT de l'Étape 2 ci-dessus
-- OU utiliser generate-hash.php pour créer un nouveau hash
```

---

### Erreur : "Compte temporairement verrouillé"

**Solution :**
```sql
UPDATE users
SET login_attempts = 0,
    locked_until = NULL
WHERE email = 'admin@noia.local';
```

---

### Erreur : "Table 'users' doesn't exist"

**Solution :**
```sql
-- Exécuter : database/upgrade_v4_PARTIE_A.sql dans phpMyAdmin
```

---

### Erreur 500 (page blanche)

**Solution 1 : Vérifier config.php**

Ouvrir `/www/noia/config/config.php` et vérifier :
```php
define('DB_HOST', 'votre_host_mysql');      // Ex: mysql51-45.perso
define('DB_NAME', 'votre_base_noia');       // Ex: noia_db
define('DB_USER', 'votre_user_mysql');
define('DB_PASS', 'votre_password_mysql');
```

**Solution 2 : Activer le mode debug**

Dans `config/config.php` :
```php
define('DEBUG_MODE', true);
```

Puis recharger la page pour voir l'erreur complète.

---

### Erreur : Aucune réponse (page loading infinie)

**Solution : Vérifier les chemins API**

Dans `login.html`, vérifier les chemins :
```javascript
// Doit pointer vers : ./api/auth/login.php
fetch('./api/auth/login.php', { ... })
```

---

## 🛠️ Outils de Diagnostic

### Option 1 : Diagnostic automatique (RECOMMANDÉ)

1. Uploader le fichier **`debug-auth.php`** à la racine
2. Ouvrir : `https://noia.votre-domaine.fr/debug-auth.php`
3. Cliquer sur **"Créer/Réinitialiser Admin"**
4. Tester la connexion
5. **⚠️ SUPPRIMER debug-auth.php après utilisation**

---

### Option 2 : Créer un hash personnalisé

1. Uploader le fichier **`generate-hash.php`** à la racine
2. Ouvrir : `https://noia.votre-domaine.fr/generate-hash.php`
3. Entrer votre mot de passe personnalisé
4. Copier la requête SQL générée
5. Exécuter dans phpMyAdmin
6. **⚠️ SUPPRIMER generate-hash.php après utilisation**

---

### Option 3 : Vérification manuelle SQL

Exécuter dans phpMyAdmin :
```sql
-- Vérifier que le compte existe
SELECT * FROM users WHERE email = 'admin@noia.local';

-- Vérifier la structure de la table
DESCRIBE users;

-- Tester la connexion MySQL depuis PHP
SELECT 1;
```

---

## 🔑 Mots de passe pré-générés

Si vous voulez utiliser un autre mot de passe, voici des hashs pré-générés :

### Mot de passe : `admin123` (par défaut)
```sql
UPDATE users
SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE email = 'admin@noia.local';
```

### Mot de passe : `SecureAdmin2024!`
```sql
UPDATE users
SET password_hash = '$2y$10$7iKYKrZ.wHnZfQnGqLZzA.OKnV3aEznqNBW.j4KfZz2Ld3fH1gqT2'
WHERE email = 'admin@noia.local';
```

### Mot de passe : `NOIA_Admin_2024`
```sql
UPDATE users
SET password_hash = '$2y$10$YjX8YQzz5OZEXvQb1rX3y.jT8P4TgNp3qKH9Zx5fCvW8jL2RnTp6K'
WHERE email = 'admin@noia.local';
```

---

## ✅ Test de Validation Finale

Une fois connecté, vérifier que l'API fonctionne :

**Ouvrir directement :**
```
https://noia.votre-domaine.fr/api/auth/check.php
```

**Résultat attendu (si connecté) :**
```json
{
  "success": true,
  "authenticated": true,
  "user": {
    "id": 1,
    "email": "admin@noia.local",
    "nom": "Administrateur",
    "prenom": "NOIA",
    "role": "admin"
  },
  "csrf_token": "abc123..."
}
```

**Si non connecté :**
```json
{
  "success": false,
  "authenticated": false,
  "error": "Non authentifié"
}
```

---

## 🎯 Checklist Complète

```
Base de données :
☐ Table users existe (SHOW TABLES)
☐ Compte admin créé (SELECT * FROM users)
☐ Hash bcrypt valide (commence par $2y$)
☐ is_active = 1
☐ login_attempts = 0
☐ locked_until = NULL

Fichiers FTP :
☐ api/auth.php uploadé
☐ api/auth/login.php uploadé
☐ api/auth/check.php uploadé
☐ login.html uploadé
☐ config/config.php configuré (DB_HOST, DB_NAME, etc.)

Tests :
☐ login.html accessible
☐ Connexion admin@noia.local / admin123 réussie
☐ api/auth/check.php retourne authenticated: true
☐ Redirection vers dashboard.html (404 normal)

Sécurité :
☐ DEBUG_MODE = false dans config.php
☐ debug-auth.php supprimé (si utilisé)
☐ generate-hash.php supprimé (si utilisé)
☐ Mot de passe admin changé (après validation)
```

---

## 📞 Si Rien ne Fonctionne

**Dernier recours : Réinitialisation complète**

```sql
-- 1. Supprimer la table users
DROP TABLE IF EXISTS users;

-- 2. Recréer depuis zéro
CREATE TABLE `users` (
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
  INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Créer admin
INSERT INTO users (email, password_hash, nom, prenom, role, is_active, created_at)
VALUES (
  'admin@noia.local',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  'Administrateur',
  'NOIA',
  'admin',
  1,
  NOW()
);

-- 4. Vérifier
SELECT * FROM users;
```

---

## ✅ Succès !

Une fois connecté avec succès :

1. **Changer immédiatement le mot de passe admin** (utiliser generate-hash.php)
2. **Supprimer les fichiers de diagnostic** (debug-auth.php, generate-hash.php)
3. **Désactiver DEBUG_MODE** dans config.php
4. **Tester l'API check.php** pour valider la session

**Vous êtes prêt pour la Phase 2 !** 🎉

---

**Fichiers de référence :**
- Guide complet : `INSTALLATION_PHASE1.md`
- Dépannage détaillé : `DEPANNAGE_LOGIN.md`
- Fichiers à uploader : `FICHIERS_A_UPLOADER_PHASE1.md`
- Architecture : `ARCHITECTURE_V4.md`
