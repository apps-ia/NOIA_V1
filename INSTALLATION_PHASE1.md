# 🚀 NOIA v4.0 - Guide d'Installation Phase 1

## 📋 Vue d'ensemble

Cette phase ajoute :
- ✅ Système d'authentification sécurisé (admin/agent/guest)
- ✅ Base juridique validée (20+ règles de droit)
- ✅ 8 nouvelles tables MySQL
- ✅ Page de connexion moderne
- ✅ Protection CSRF et brute force

**Durée estimée : 15-20 minutes**

---

## 🗂️ PARTIE 1 : Fichiers à Uploader (via FTP)

### ✅ Fichiers NOUVEAUX (à ajouter)

```
📁 Votre serveur OVH : /www/noia/

Ajouter ces fichiers :

📄 login.html                           [NOUVEAU]
   └─ Page de connexion moderne

📁 api/
   ├─ auth.php                          [NOUVEAU]
   │  └─ Système d'authentification
   │
   ├─ middleware.php                    [NOUVEAU]
   │  └─ Protection des APIs
   │
   └─ 📁 auth/                          [NOUVEAU DOSSIER]
      ├─ login.php                      [NOUVEAU]
      ├─ logout.php                     [NOUVEAU]
      └─ check.php                      [NOUVEAU]

📁 database/
   ├─ upgrade_v4_complete.sql           [NOUVEAU]
   │  └─ Schéma complet 8 tables
   │
   └─ legal_facts_data.sql              [NOUVEAU]
      └─ Base juridique validée (20+ règles)

📄 ARCHITECTURE_V4.md                   [NOUVEAU - Optionnel]
   └─ Documentation technique
```

### ⚠️ Fichiers EXISTANTS (ne pas toucher)

**Ces fichiers restent INCHANGÉS pour l'instant :**

```
✋ NE PAS MODIFIER :
   ├─ index.html              (interface utilisateur actuelle)
   ├─ script.js               (logique frontend actuelle)
   ├─ api/proxy.php           (appels OpenAI - Phase 2)
   ├─ api/embeddings.php      (RAG - Phase 2)
   ├─ api/web_search.php      (recherche web - Phase 2)
   ├─ api/doc_manager.php     (gestion docs)
   ├─ config/config.php       (configuration - garder vos clés API)
   └─ admin_documents.html    (gestion documentaire)
```

---

## 🗄️ PARTIE 2 : Base de Données MySQL

### Étape 1 : Se connecter à phpMyAdmin

1. Aller sur **phpMyAdmin** (fourni par OVH)
2. Sélectionner votre base NOIA (ex: `noia_db`)

### Étape 2 : Exécuter le script principal

**Fichier : `database/upgrade_v4_complete.sql`**

1. Dans phpMyAdmin, cliquer sur l'onglet **"SQL"**
2. Ouvrir le fichier `upgrade_v4_complete.sql` depuis votre ordinateur
3. Copier TOUT le contenu
4. Coller dans la zone de texte phpMyAdmin
5. Cliquer sur **"Exécuter"**

**Résultat attendu :**
```sql
✅ Table 'users' créée
✅ Table 'sessions' créée
✅ Table 'historique_conversation' créée
✅ Table 'documents_generes' créée
✅ Table 'statistiques' créée
✅ Table 'legal_facts' créée
✅ Table 'access_logs' créée
✅ Table 'cache_rag' créée
✅ Table 'documents' modifiée (ajout colonnes)
✅ Utilisateur admin créé : admin@noia.local
```

### Étape 3 : Charger la base juridique

**Fichier : `database/legal_facts_data.sql`**

1. Toujours dans l'onglet **"SQL"**
2. Ouvrir le fichier `legal_facts_data.sql`
3. Copier TOUT le contenu
4. Coller et **"Exécuter"**

**Résultat attendu :**
```sql
✅ 20+ règles juridiques insérées :
   - Quorum (3 règles)
   - FCTVA (3 règles)
   - M57 (2 règles)
   - CGCT (2 règles)
   - RH (2 règles)
   - Marchés publics (1 règle)
   - IFSE (1 règle)
   - Budget (1 règle)
   - Délibérations (2 règles)
```

### Étape 4 : Vérifier l'installation

Dans phpMyAdmin, exécuter cette requête pour vérifier :

```sql
SELECT
  'users' as table_name, COUNT(*) as nb_lignes FROM users
UNION ALL
SELECT 'legal_facts', COUNT(*) FROM legal_facts
UNION ALL
SELECT 'sessions', COUNT(*) FROM sessions
UNION ALL
SELECT 'historique_conversation', COUNT(*) FROM historique_conversation;
```

**Résultat attendu :**
```
users                    : 1 ligne  (compte admin)
legal_facts             : 20+ lignes
sessions                : 0 ligne   (normal, pas encore de connexions)
historique_conversation : 0 ligne   (normal)
```

---

## 📤 PARTIE 3 : Upload FTP (FileZilla)

### Structure finale sur le serveur :

```
/www/noia/
│
├─ 📄 login.html                    ← [NOUVEAU]
├─ 📄 index.html                    ← [EXISTANT - ne pas toucher]
├─ 📄 admin_documents.html          ← [EXISTANT - ne pas toucher]
├─ 📄 ARCHITECTURE_V4.md            ← [NOUVEAU - optionnel]
│
├─ 📁 api/
│   ├─ 📄 auth.php                  ← [NOUVEAU]
│   ├─ 📄 middleware.php            ← [NOUVEAU]
│   ├─ 📄 proxy.php                 ← [EXISTANT - ne pas toucher]
│   ├─ 📄 embeddings.php            ← [EXISTANT - ne pas toucher]
│   ├─ 📄 web_search.php            ← [EXISTANT - ne pas toucher]
│   ├─ 📄 doc_manager.php           ← [EXISTANT - ne pas toucher]
│   │
│   └─ 📁 auth/                     ← [NOUVEAU DOSSIER]
│       ├─ 📄 login.php             ← [NOUVEAU]
│       ├─ 📄 logout.php            ← [NOUVEAU]
│       └─ 📄 check.php             ← [NOUVEAU]
│
├─ 📁 config/
│   ├─ 📄 config.php                ← [EXISTANT - garder vos clés API]
│   └─ 📄 config.example.php        ← [EXISTANT]
│
├─ 📁 database/
│   ├─ 📄 upgrade_v4_complete.sql   ← [NOUVEAU - pour référence]
│   └─ 📄 legal_facts_data.sql      ← [NOUVEAU - pour référence]
│
├─ 📁 documents/                    ← [EXISTANT]
│
└─ 📁 logs/                         ← [EXISTANT]
```

### Étapes FileZilla :

1. **Connectez-vous à votre serveur OVH**

2. **Créer le dossier `api/auth/`** :
   - Aller dans `/www/noia/api/`
   - Clic droit → Créer un répertoire
   - Nommer : `auth`

3. **Uploader les nouveaux fichiers** :

   **Racine `/www/noia/` :**
   ```
   ✅ login.html
   ✅ ARCHITECTURE_V4.md (optionnel)
   ```

   **Dans `/www/noia/api/` :**
   ```
   ✅ auth.php
   ✅ middleware.php
   ```

   **Dans `/www/noia/api/auth/` :**
   ```
   ✅ login.php
   ✅ logout.php
   ✅ check.php
   ```

   **Dans `/www/noia/database/` (optionnel, pour référence) :**
   ```
   ✅ upgrade_v4_complete.sql
   ✅ legal_facts_data.sql
   ```

4. **Vérifier les permissions** :
   - Tous les fichiers `.php` : **644** (rw-r--r--)
   - Tous les dossiers : **755** (rwxr-xr-x)

---

## 🧪 PARTIE 4 : Tests

### Test 1 : Vérifier la page de connexion

Ouvrir dans votre navigateur :
```
https://noia.votre-domaine.fr/login.html
```

**Résultat attendu :**
- ✅ Page moderne avec dégradé violet/bleu
- ✅ Logo NOIA 🏛️
- ✅ Badge "v4.0 • Enterprise"
- ✅ Formulaires email et mot de passe
- ✅ Case "Se souvenir de moi"

### Test 2 : Se connecter avec le compte admin

**Identifiants par défaut :**
```
Email    : admin@noia.local
Password : admin123
```

**⚠️ IMPORTANT : Changer ce mot de passe immédiatement après !**

**Résultat attendu :**
- ✅ Message "Connexion réussie ! Redirection..."
- ✅ Redirection vers `dashboard.html` (qui n'existe pas encore)
- ⚠️ Erreur 404 normale, le dashboard sera créé en Phase 4

### Test 3 : Vérifier l'API d'authentification

Ouvrir directement :
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
    "role": "admin",
    "commune": null
  },
  "csrf_token": "abc123..."
}
```

**Résultat si non connecté :**
```json
{
  "success": false,
  "authenticated": false,
  "error": "Non authentifié"
}
```

### Test 4 : Vérifier les tables en base

Dans phpMyAdmin, exécuter :
```sql
SELECT COUNT(*) as nb_utilisateurs FROM users;
SELECT COUNT(*) as nb_regles_juridiques FROM legal_facts;
SHOW TABLES;
```

**Résultat attendu :**
```
nb_utilisateurs       : 1
nb_regles_juridiques : 20+
Tables                : 13+ tables (incluant les 8 nouvelles)
```

---

## 🔒 PARTIE 5 : Sécurité Post-Installation

### Action 1 : Changer le mot de passe admin

**Via phpMyAdmin :**

1. Aller dans la table `users`
2. Cliquer sur "Modifier" pour `admin@noia.local`
3. Dans le champ `password_hash`, cliquer sur "Fonction" → choisir `PASSWORD`
4. Entrer votre nouveau mot de passe (minimum 8 caractères)
5. Sauvegarder

**Le hash sera automatiquement généré par MySQL.**

Ou via PHP (créer un fichier temporaire `change_password.php`) :

```php
<?php
// À SUPPRIMER après utilisation !
$nouveau_password = 'VotreNouveauMotDePasse123!';
$hash = password_hash($nouveau_password, PASSWORD_BCRYPT);
echo "Nouveau hash : " . $hash;
// Copier ce hash dans phpMyAdmin, colonne password_hash
?>
```

### Action 2 : Créer un utilisateur agent (optionnel)

Dans phpMyAdmin, exécuter :

```sql
INSERT INTO users (email, password_hash, nom, prenom, role, commune, is_active, created_at)
VALUES (
  'agent@votre-commune.fr',
  PASSWORD('MotDePasseAgent123'),
  'Dupont',
  'Marie',
  'agent',
  'Votre Commune',
  1,
  NOW()
);
```

### Action 3 : Protéger les fichiers SQL

**Supprimer ou déplacer les fichiers SQL du serveur :**
```
/www/noia/database/upgrade_v4_complete.sql     → SUPPRIMER
/www/noia/database/legal_facts_data.sql        → SUPPRIMER
```

Ou les déplacer dans un dossier non accessible depuis le web.

---

## 📊 PARTIE 6 : Vérification Finale

### Checklist complète :

```
Base de données :
✅ 8 nouvelles tables créées
✅ 1 utilisateur admin créé
✅ 20+ règles juridiques chargées
✅ Événements automatiques (nettoyage) actifs

Fichiers uploadés :
✅ login.html
✅ api/auth.php
✅ api/middleware.php
✅ api/auth/login.php
✅ api/auth/logout.php
✅ api/auth/check.php

Tests fonctionnels :
✅ Page login accessible
✅ Connexion admin réussie
✅ API check.php répond correctement
✅ Sessions stockées en base

Sécurité :
✅ Mot de passe admin changé
✅ Fichiers SQL supprimés du serveur
✅ Permissions correctes (644 pour PHP)
```

---

## ⚠️ Problèmes Courants

### Problème 1 : Erreur "Table 'users' already exists"

**Cause :** Les tables existent déjà.

**Solution :**
```sql
-- Supprimer les tables et recommencer
DROP TABLE IF EXISTS users, sessions, historique_conversation,
                     documents_generes, statistiques, legal_facts,
                     access_logs, cache_rag;
-- Puis réexécuter upgrade_v4_complete.sql
```

### Problème 2 : Page login.html affiche du code PHP

**Cause :** Serveur n'interprète pas le PHP (mais login.html est en HTML pur, pas de PHP).

**Solution :** Vérifier que vous accédez bien à `login.html` et non `login.php`.

### Problème 3 : Erreur 500 sur api/auth/login.php

**Cause :** Erreur dans config.php ou connexion base de données.

**Solution :**
1. Vérifier `config/config.php` (DB_HOST, DB_NAME, DB_USER, DB_PASS)
2. Activer DEBUG_MODE temporairement :
   ```php
   define('DEBUG_MODE', true);
   ```
3. Regarder les erreurs dans les logs Apache/PHP

### Problème 4 : "Authentification requise" en boucle

**Cause :** Sessions PHP non démarrées ou cookies bloqués.

**Solution :**
1. Vérifier que le dossier `/tmp` ou `/var/lib/php/sessions` est accessible en écriture
2. Tester avec un autre navigateur
3. Désactiver les bloqueurs de cookies

---

## 📞 Support

Si vous rencontrez des problèmes :

1. **Activer le mode debug** dans `config/config.php` :
   ```php
   define('DEBUG_MODE', true);
   ```

2. **Consulter les logs** :
   - Logs PHP : `/www/logs/error.log` (chemin OVH)
   - Logs NOIA : `/www/noia/logs/queries.log`
   - Logs accès : Table `access_logs` en base

3. **Tester les endpoints** directement :
   ```
   /api/auth/check.php        → Vérifier session
   /api/auth/login.php        → Tester connexion
   ```

---

## ✅ Phase 1 Terminée !

Une fois tous les tests passés, vous avez :

✅ **Système d'authentification fonctionnel**
✅ **Base juridique de 20+ règles validées**
✅ **8 nouvelles tables MySQL opérationnelles**
✅ **Page de connexion moderne et sécurisée**

**Prochaine étape :** Phase 2 (Intelligence & Précision juridique)

---

**Date : Octobre 2024**
**Version : NOIA v4.0 - Phase 1**
**Auteur : Claude Code**
