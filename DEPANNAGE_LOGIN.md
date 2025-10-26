# 🔧 NOIA v4.0 - Dépannage Connexion Impossible

## 🔴 Problème : "admin@noia.local / admin123 ne fonctionne pas"

---

## ✅ SOLUTION RAPIDE (3 minutes)

### Méthode 1 : Script de Diagnostic (Recommandé)

**Étape 1 :** Uploader le fichier `debug-auth.php` à la racine de votre serveur

**Étape 2 :** Aller sur :
```
https://noia.votre-domaine.fr/debug-auth.php
```

**Ce script va :**
- ✅ Vérifier la connexion MySQL
- ✅ Vérifier l'existence de la table `users`
- ✅ Vérifier l'existence de l'utilisateur admin
- ✅ Tester la validité du mot de passe
- ✅ Vous permettre de créer/réinitialiser le compte admin en 1 clic

**Étape 3 :** Cliquer sur le bouton "Créer/Réinitialiser Admin"

**Étape 4 :** Noter le nouveau mot de passe affiché

**Étape 5 :** ⚠️ **SUPPRIMER le fichier debug-auth.php après utilisation !**

---

### Méthode 2 : Requête SQL Directe

**Dans phpMyAdmin :**

```sql
-- Supprimer l'ancien compte admin
DELETE FROM users WHERE email = 'admin@noia.local';

-- Créer un nouveau compte admin
INSERT INTO users (
    email,
    password_hash,
    nom,
    prenom,
    role,
    is_active,
    created_at,
    login_attempts
)
VALUES (
    'admin@noia.local',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Administrateur',
    'NOIA',
    'admin',
    1,
    NOW(),
    0
);

-- Vérifier
SELECT * FROM users WHERE email = 'admin@noia.local';
```

**Mot de passe :** `admin123`

---

## 🔍 DIAGNOSTIC : Causes Possibles

### Cause 1 : Table `users` n'existe pas

**Symptôme :** Erreur "Table 'users' doesn't exist" dans les logs

**Solution :**
```sql
-- Vérifier
SHOW TABLES LIKE 'users';

-- Si rien ne s'affiche, exécuter :
-- database/upgrade_v4_PARTIE_A.sql
```

---

### Cause 2 : Utilisateur admin non créé

**Symptôme :** Erreur "Identifiants invalides"

**Vérifier :**
```sql
SELECT COUNT(*) FROM users WHERE email = 'admin@noia.local';
```

**Si le résultat est 0 :**
- Exécuter `database/CREATE_ADMIN.sql`
- Ou utiliser `debug-auth.php`

---

### Cause 3 : Hash du mot de passe corrompu

**Symptôme :** L'utilisateur existe mais le mot de passe ne fonctionne pas

**Vérifier :**
```sql
SELECT
    email,
    password_hash,
    LENGTH(password_hash) as hash_length
FROM users
WHERE email = 'admin@noia.local';
```

**Le hash doit :**
- Commencer par `$2y$` (bcrypt)
- Faire environ 60 caractères de long

**Si le hash est incorrect :**
```sql
UPDATE users
SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE email = 'admin@noia.local';
```

---

### Cause 4 : Compte verrouillé (trop de tentatives)

**Symptôme :** Message "Compte temporairement verrouillé"

**Vérifier :**
```sql
SELECT
    email,
    login_attempts,
    locked_until
FROM users
WHERE email = 'admin@noia.local';
```

**Déverrouiller :**
```sql
UPDATE users
SET login_attempts = 0,
    locked_until = NULL
WHERE email = 'admin@noia.local';
```

---

### Cause 5 : Compte désactivé

**Symptôme :** Message "Compte désactivé"

**Vérifier :**
```sql
SELECT email, is_active FROM users WHERE email = 'admin@noia.local';
```

**Réactiver :**
```sql
UPDATE users
SET is_active = 1
WHERE email = 'admin@noia.local';
```

---

### Cause 6 : Fichiers API manquants

**Symptôme :** Erreur 404 ou erreur 500 lors de la connexion

**Vérifier via FTP :**
```
/www/noia/api/auth.php              ← Doit exister
/www/noia/api/auth/login.php        ← Doit exister
/www/noia/api/middleware.php        ← Doit exister
/www/noia/login.html                ← Doit exister
```

**Si manquants :** Uploader les fichiers (voir `FICHIERS_A_UPLOADER_PHASE1.md`)

---

### Cause 7 : Erreur de connexion base de données

**Symptôme :** Erreur "Erreur de connexion à la base de données"

**Vérifier dans `config/config.php` :**
```php
define('DB_HOST', 'mysql47.perso.ovh.net');  // Correct ?
define('DB_NAME', 'votre_base');              // Correct ?
define('DB_USER', 'votre_utilisateur');       // Correct ?
define('DB_PASS', 'votre_mot_de_passe');      // Correct ?
```

**Tester la connexion dans phpMyAdmin avec ces identifiants.**

---

## 🧪 Tests de Vérification

### Test 1 : Connexion MySQL

```sql
SELECT 'Connexion OK' AS status, DATABASE() AS base_actuelle, NOW() AS date_serveur;
```

### Test 2 : Table users existe

```sql
SHOW COLUMNS FROM users;
```

### Test 3 : Admin existe

```sql
SELECT
    email,
    nom,
    role,
    is_active,
    login_attempts,
    locked_until
FROM users
WHERE email = 'admin@noia.local';
```

### Test 4 : Hash bcrypt valide

```sql
SELECT
    email,
    SUBSTRING(password_hash, 1, 4) AS hash_prefix,
    LENGTH(password_hash) AS hash_length
FROM users
WHERE email = 'admin@noia.local';
```

**Résultat attendu :**
```
email             : admin@noia.local
hash_prefix       : $2y$
hash_length       : 60
```

---

## 📝 Procédure Complète de Réinitialisation

**Si rien ne fonctionne, réinitialisation complète :**

```sql
-- 1. Supprimer tous les utilisateurs (⚠️ ATTENTION)
TRUNCATE TABLE users;

-- 2. Supprimer toutes les sessions
TRUNCATE TABLE sessions;

-- 3. Créer l'admin
INSERT INTO users (
    email,
    password_hash,
    nom,
    prenom,
    role,
    is_active,
    created_at,
    login_attempts
)
VALUES (
    'admin@noia.local',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Administrateur',
    'NOIA',
    'admin',
    1,
    NOW(),
    0
);

-- 4. Vérifier
SELECT * FROM users;
```

---

## 🆘 Checklist Complète

```
Base de données :
☐ Connexion MySQL fonctionne
☐ Table users existe
☐ Utilisateur admin@noia.local existe
☐ Hash du mot de passe commence par $2y$ (bcrypt)
☐ login_attempts = 0
☐ locked_until = NULL
☐ is_active = 1

Fichiers :
☐ api/auth.php uploadé
☐ api/auth/login.php uploadé
☐ api/auth/logout.php uploadé
☐ api/auth/check.php uploadé
☐ api/middleware.php uploadé
☐ login.html uploadé

Tests :
☐ debug-auth.php affiche "✅ Connexion MySQL réussie"
☐ debug-auth.php affiche "✅ Utilisateur admin existe"
☐ debug-auth.php affiche "✅ Le mot de passe admin123 est VALIDE"
☐ Page login.html s'affiche correctement
☐ Pas d'erreur dans la console navigateur (F12)
```

---

## 💡 Astuce : Créer un Autre Admin

Si vous voulez créer un compte admin avec votre propre email :

**Méthode 1 : Via debug-auth.php**
1. Ouvrir `debug-auth.php`
2. Modifier le champ "Mot de passe"
3. Cliquer "Créer/Réinitialiser Admin"

**Méthode 2 : Via phpMyAdmin**
```sql
INSERT INTO users (email, password_hash, nom, prenom, role, is_active, created_at, login_attempts)
VALUES (
    'votre@email.fr',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Votre Nom',
    'Votre Prénom',
    'admin',
    1,
    NOW(),
    0
);
```

**Mot de passe :** `admin123` (changez-le ensuite)

---

## 📞 Toujours Bloqué ?

1. **Activer le mode debug** dans `config/config.php` :
   ```php
   define('DEBUG_MODE', true);
   ```

2. **Consulter les logs PHP** (demander à OVH où ils se trouvent)

3. **Tester l'API directement** :
   ```
   https://noia.votre-domaine.fr/api/auth/check.php
   ```

4. **Vérifier la console navigateur** (F12 → Console)

---

**Version :** NOIA v4.0 Phase 1
**Date :** Octobre 2024
