# 🚀 Guide Déploiement OVH - Multisite + Git + phpMyAdmin

Guide pas à pas pour déployer NOIA MVP sur OVH avec nom de domaine personnalisé.

---

## 📋 Prérequis

- ✅ Hébergement OVH Performance (ou supérieur)
- ✅ Nom de domaine configuré sur OVH (ex: `noia.erelys.fr`)
- ✅ Accès à l'espace client OVH
- ✅ Repository Git accessible (GitHub, GitLab, etc.)
- ✅ FileZilla installé (optionnel)

---

## 🎯 Vue d'ensemble

Nous allons procéder en 5 étapes:

1. **Créer la base de données MySQL** (phpMyAdmin)
2. **Configurer le Multisite** (pointer le domaine)
3. **Associer Git** (déploiement automatique)
4. **Créer les fichiers de configuration** (.env, config.php via FileZilla)
5. **Configurer l'Assistant OpenAI** (Vector Store)

---

## ÉTAPE 1: Créer la Base de Données

### 1.1 Accéder à phpMyAdmin

1. Connectez-vous à l'**Espace Client OVH**: https://www.ovh.com/manager/
2. Menu **Hébergements** → Sélectionnez votre hébergement
3. Onglet **Bases de données**
4. Cliquez sur **Créer une base de données**

**Configuration:**
- **Type**: MySQL ou MariaDB
- **Version**: MySQL 8.0 ou MariaDB 10.6 (recommandé)
- Cliquez sur **Suivant**

5. Notez bien les informations:
   ```
   Serveur: xxxxx.mysql.db (ex: noia123.mysql.db)
   Nom de la base: nom_que_vous_choisissez (ex: noia_db)
   Utilisateur: même nom que la base
   Mot de passe: [GÉNÉRÉ ou créez-en un]
   ```

   ⚠️ **IMPORTANT**: Notez ces infos, vous en aurez besoin pour le `.env`

6. Une fois créée, cliquez sur le **lien phpMyAdmin** à droite de votre base

### 1.2 Importer le Schéma SQL

1. Dans **phpMyAdmin**, connectez-vous avec:
   - Utilisateur: `votre_nom_base`
   - Mot de passe: `celui_noté_précédemment`

2. Cliquez sur votre base de données dans le menu de gauche (ex: `noia_db`)

3. Cliquez sur l'onglet **SQL** (en haut)

4. Copiez-collez le contenu suivant:

```sql
-- NOIA MVP - Database Schema

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    role ENUM('agent', 'admin') DEFAULT 'agent',
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME NULL,
    INDEX idx_email (email),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des conversations
CREATE TABLE IF NOT EXISTS conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    thread_id VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_thread_id (thread_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Créer un utilisateur de test
-- Mot de passe: noia2024
INSERT INTO users (email, password_hash, nom, prenom, role) VALUES
('demo@noia.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo', 'Utilisateur', 'agent');
```

5. Cliquez sur **Exécuter** (en bas à droite)

6. Vérifiez que les 2 tables sont créées:
   - Onglet **Structure** → Vous devez voir `users` et `conversations`
   - Onglet **Parcourir** sur `users` → 1 ligne avec `demo@noia.fr`

✅ **Base de données créée!**

---

## ÉTAPE 2: Configurer le Multisite

Le multisite permet de pointer votre nom de domaine (ex: `noia.erelys.fr`) vers le bon répertoire.

### 2.1 Ajouter une Entrée Multisite

1. **Espace Client OVH** → **Hébergements** → Votre hébergement
2. Onglet **Multisite**
3. Cliquez sur **Ajouter un domaine ou sous-domaine**

**Configuration:**

**Étape 1: Choix du domaine**
- ☑️ **Ajouter un sous-domaine** (ex: `noia.erelys.fr`)
  - Domaine: `erelys.fr`
  - Sous-domaine: `noia`
- OU ☑️ **Ajouter un domaine** (ex: `noia-app.fr`) si vous avez un domaine dédié

Cliquez sur **Suivant**

**Étape 2: Configuration du répertoire**

- **Dossier racine**: `noia-mvp/public`

  ⚠️ **IMPORTANT**: Le répertoire racine doit pointer vers `public/` et non la racine du projet!

- **Certificat SSL**: ✅ Activer (HTTPS gratuit Let's Encrypt)
- **Activer le CDN**: ☐ (optionnel, pas nécessaire pour le MVP)
- **Activer le firewall**: ✅ (recommandé)

Cliquez sur **Suivant** puis **Valider**

### 2.2 Attendre la Propagation DNS

⏱️ **Délai**: 15 minutes à 24 heures (généralement ~30 min)

Vous pouvez vérifier la propagation:
- Outil OVH: Les DNS se mettent à jour automatiquement
- Test: `ping noia.erelys.fr` (doit répondre l'IP de votre hébergement OVH)

✅ **Multisite configuré!**

---

## ÉTAPE 3: Associer Git (Déploiement Automatique)

### 3.1 Préparer votre Repository Git

**Option A: Si votre repo est déjà sur GitHub/GitLab (recommandé)**

1. Allez sur GitHub/GitLab
2. Copiez l'URL de votre repository:
   - HTTPS: `https://github.com/votre-user/NOIA_V1.git`
   - OU SSH: `git@github.com:votre-user/NOIA_V1.git` (si clé SSH configurée)

**Option B: Si votre repo est local seulement**

Créez d'abord un repository sur GitHub:

1. GitHub → **New Repository**
2. Nom: `NOIA_V1`
3. Visibilité: **Private** (recommandé pour une app métier)
4. **Ne pas** créer README, .gitignore, licence (déjà existants)
5. Cliquez sur **Create repository**

Puis poussez votre code local:

```bash
cd /home/user/NOIA_V1
git remote add origin https://github.com/votre-user/NOIA_V1.git
git branch -M main
git push -u origin main
```

### 3.2 Configurer "Associer Git" sur OVH

1. **Espace Client OVH** → **Hébergements** → Votre hébergement
2. Onglet **Git** (ou **Associer Git** selon version interface)
3. Cliquez sur **Associer un repository Git**

**Configuration:**

**Étape 1: Informations du repository**

- **Nom (libellé)**: `NOIA MVP Production`
- **URL du repository**:
  ```
  https://github.com/votre-user/NOIA_V1.git
  ```
  ⚠️ Pour un repo privé, utilisez un Personal Access Token (voir ci-dessous)

- **Branche**: `main` (ou `master` selon votre config)

**Pour un repository privé:**

GitHub → Settings → Developer settings → Personal access tokens → Generate new token

Permissions nécessaires:
- ✅ `repo` (Full control of private repositories)

Copiez le token généré: `ghp_xxxxxxxxxxxxx`

URL à utiliser:
```
https://ghp_VOTRE_TOKEN@github.com/votre-user/NOIA_V1.git
```

**Étape 2: Configuration du déploiement**

- **Répertoire de déploiement**:
  ```
  noia-mvp
  ```

  ⚠️ **C'est le répertoire où sera cloné le repo** (pas `noia-mvp/public`)

- **Répertoire cible dans le repo**:
  ```
  noia-mvp
  ```

  Si tout votre repo EST `noia-mvp`, mettez `/` ou `.`

- **Clé SSH de déploiement** (optionnel):
  - Pour GitHub avec SSH, générez une clé SSH sur OVH et ajoutez-la comme Deploy Key sur GitHub

**Étape 3: Webhooks (déploiement automatique)**

- **Activer le déploiement automatique**: ✅ OUI

  À chaque `git push`, OVH déploiera automatiquement!

- **URL de webhook**: OVH génère une URL, copiez-la

**Ajouter le webhook sur GitHub:**

1. GitHub → Votre repo → **Settings** → **Webhooks** → **Add webhook**
2. **Payload URL**: Collez l'URL webhook OVH
3. **Content type**: `application/json`
4. **Events**: ☑️ Just the `push` event
5. **Active**: ✅
6. Cliquez sur **Add webhook**

**Étape 4: Premier déploiement**

Cliquez sur **Valider** dans OVH

OVH va:
1. Cloner votre repository
2. Déployer dans `noia-mvp/`
3. Créer les liens symboliques nécessaires

⏱️ **Durée**: 30 secondes à 2 minutes

✅ **Git associé! À partir de maintenant:**

```bash
git add .
git commit -m "Update feature"
git push origin main
# → OVH déploie automatiquement en 30s!
```

---

## ÉTAPE 4: Créer les Fichiers de Configuration

Les fichiers `.env` et `config/config.php` ne sont **JAMAIS** commités sur Git (sécurité). Vous devez les créer manuellement via FileZilla.

### 4.1 Connexion FileZilla

1. Ouvrez **FileZilla**

**Informations de connexion FTP** (disponibles dans OVH):

- **Espace Client OVH** → **Hébergements** → Onglet **FTP-SSH**
- Notez:
  ```
  Hôte: ftp.votre-cluster.hosting.ovh.net
  Login: votre-login-ftp
  Mot de passe: [Réinitialisez si oublié]
  Port: 21 (FTP) ou 22 (SFTP recommandé)
  ```

**Configuration FileZilla:**

- **Hôte**: `sftp://ftp.votre-cluster.hosting.ovh.net`
- **Identifiant**: `votre-login-ftp`
- **Mot de passe**: `votre-mot-de-passe-ftp`
- **Port**: `22`

Cliquez sur **Connexion rapide**

### 4.2 Naviguer vers le Répertoire NOIA

Dans FileZilla (panneau de droite = serveur):

```
/
└── noia-mvp/          ← Naviguez ici
    ├── public/
    ├── src/
    ├── config/
    ├── scripts/
    └── docs/
```

### 4.3 Créer le Fichier `.env`

**Méthode 1: Créer localement puis uploader**

1. Sur votre ordinateur, créez un fichier `noia-mvp/.env` avec:

```env
# OpenAI Configuration
OPENAI_API_KEY=sk-proj-VOTRE_CLE_API_OPENAI
OPENAI_ASSISTANT_ID=asst_VOTRE_ASSISTANT_ID
OPENAI_MODEL=gpt-4o

# Database Configuration (valeurs notées à l'étape 1.1)
DB_HOST=xxxxx.mysql.db
DB_NAME=votre_nom_base
DB_USER=votre_nom_base
DB_PASSWORD=votre_mot_de_passe_mysql

# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://noia.erelys.fr

# Session Security
SESSION_LIFETIME=7200
SESSION_SECURE=true

# Rate Limiting
RATE_LIMIT_REQUESTS=100
RATE_LIMIT_PERIOD=3600
```

**Valeurs à remplacer:**

- `OPENAI_API_KEY`: Votre clé API OpenAI (https://platform.openai.com/api-keys)
- `OPENAI_ASSISTANT_ID`: Sera généré à l'étape 5
- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`: Valeurs notées à l'étape 1.1
- `APP_URL`: Votre nom de domaine (ex: `https://noia.erelys.fr`)

2. Dans FileZilla, uploadez le fichier `.env` dans `/noia-mvp/`

**Méthode 2: Créer directement sur le serveur**

1. FileZilla → Clic droit dans `/noia-mvp/` → **Créer un fichier**
2. Nom: `.env`
3. Clic droit sur `.env` → **Voir/Éditer**
4. Collez le contenu ci-dessus
5. Sauvegardez et fermez (FileZilla uploade automatiquement)

### 4.4 Créer le Fichier `config/config.php`

1. Dans FileZilla, naviguez vers `/noia-mvp/config/`

2. Copiez le fichier `config.example.php` → renommez en `config.php`

   OU créez `config.php` avec ce contenu:

```php
<?php
/**
 * NOIA MVP - Configuration
 * Ce fichier charge les variables depuis .env
 */

// Charger les variables d'environnement depuis .env
$envFile = __DIR__ . '/../.env';

if (!file_exists($envFile)) {
    die("❌ Fichier .env introuvable. Veuillez créer le fichier .env à la racine du projet.");
}

// Parser le fichier .env
$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    // Ignorer les commentaires
    if (strpos(trim($line), '#') === 0) {
        continue;
    }

    // Parser KEY=VALUE
    if (strpos($line, '=') !== false) {
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, '"\'');
        $_ENV[$key] = $value;
        putenv("{$key}={$value}");
    }
}

// Définir les constantes
define('OPENAI_API_KEY', $_ENV['OPENAI_API_KEY'] ?? '');
define('OPENAI_ASSISTANT_ID', $_ENV['OPENAI_ASSISTANT_ID'] ?? '');
define('OPENAI_MODEL', $_ENV['OPENAI_MODEL'] ?? 'gpt-4o');

define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'noia_db');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? '');

define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
define('APP_DEBUG', filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN));
define('APP_URL', $_ENV['APP_URL'] ?? '');

define('SESSION_LIFETIME', (int)($_ENV['SESSION_LIFETIME'] ?? 7200));
define('SESSION_SECURE', filter_var($_ENV['SESSION_SECURE'] ?? true, FILTER_VALIDATE_BOOLEAN));

define('RATE_LIMIT_REQUESTS', (int)($_ENV['RATE_LIMIT_REQUESTS'] ?? 100));
define('RATE_LIMIT_PERIOD', (int)($_ENV['RATE_LIMIT_PERIOD'] ?? 3600));

// Configuration PHP
date_default_timezone_set('Europe/Paris');

// Gestion des erreurs selon l'environnement
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php-errors.log');
}

// Configuration session sécurisée
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', SESSION_SECURE ? 1 : 0);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
```

3. Uploadez `config.php` dans `/noia-mvp/config/`

### 4.5 Vérifier les Permissions

Dans FileZilla, vérifiez les permissions:

- `.env`: `600` (lecture/écriture propriétaire uniquement)
- `config/config.php`: `600`
- `public/`: `755`
- Tous les `.php`: `644`

Clic droit sur un fichier → **Permissions du fichier** → Ajuster si nécessaire

### 4.6 Créer le Répertoire `logs/`

1. Dans `/noia-mvp/`, créez un dossier `logs/`
2. Permissions: `755`
3. Créez un fichier vide `logs/.gitkeep` pour le tracker (optionnel)

✅ **Configuration créée!**

---

## ÉTAPE 5: Configurer l'Assistant OpenAI

### 5.1 Préparer les Documents PDF

**Créez 10 documents PDF** avec vos contenus (legal facts, grilles RH, etc.):

1. Sur votre ordinateur local, créez le dossier `docs/` avec vos PDFs
2. Exemples de documents:
   - `legal_facts_quorum.pdf`
   - `legal_facts_fctva.pdf`
   - `grilles_rh_2024.pdf`
   - `guide_m57.pdf`
   - etc.

3. Via **FileZilla**, uploadez tous les PDFs dans `/noia-mvp/docs/`

### 5.2 Exécuter le Script de Configuration

**Via SSH** (recommandé):

1. **OVH Espace Client** → **Hébergements** → Onglet **FTP-SSH**
2. Activez l'**accès SSH** si ce n'est pas déjà fait
3. Notez l'adresse SSH: `ssh votre-login@ssh.cluster0XX.hosting.ovh.net`

Connectez-vous:

```bash
ssh votre-login@ssh.cluster0XX.hosting.ovh.net
cd noia-mvp
php scripts/setup_vector_store.php
```

**OU via Exécution locale** (si vous avez PHP):

1. Téléchargez via FileZilla le fichier `scripts/setup_vector_store.php`
2. Modifiez temporairement le chemin du `require_once` pour pointer vers votre `.env` local
3. Exécutez localement:

```bash
php setup_vector_store.php
```

**Le script va:**

1. ✅ Créer un Vector Store OpenAI
2. ✅ Uploader tous vos PDFs (10 fichiers)
3. ✅ Créer l'Assistant NOIA avec le bon prompt
4. ✅ Afficher les IDs à copier

**Sortie attendue:**

```
🚀 NOIA - Configuration du Vector Store
========================================

1️⃣ Création du Vector Store...
✅ Vector Store créé: vs_abc123xyz

2️⃣ Upload des fichiers PDF...
   📄 Upload de legal_facts_quorum.pdf... ✅ file-xyz123
   📄 Upload de legal_facts_fctva.pdf... ✅ file-abc456
   ...

3️⃣ Attachement des fichiers au Vector Store...
✅ Fichiers attachés avec succès

4️⃣ Création de l'Assistant NOIA...
✅ Assistant créé: asst_abc123xyz789

========================================
✅ Configuration terminée !

📝 Ajoutez ces valeurs dans votre fichier .env:

OPENAI_ASSISTANT_ID=asst_abc123xyz789
OPENAI_VECTOR_STORE_ID=vs_abc123xyz
```

### 5.3 Mettre à Jour le `.env`

1. Via **FileZilla**, ouvrez `/noia-mvp/.env`
2. Ajoutez/modifiez les lignes:

```env
OPENAI_ASSISTANT_ID=asst_abc123xyz789
```

(La ligne `OPENAI_VECTOR_STORE_ID` est optionnelle, juste pour référence)

3. Sauvegardez

✅ **Assistant OpenAI configuré!**

---

## ÉTAPE 6: Tester l'Application

### 6.1 Accéder à NOIA

Ouvrez votre navigateur:

```
https://noia.erelys.fr
```

Vous devriez voir la **page de connexion NOIA**.

### 6.2 Se Connecter

**Utilisateur de test:**
- Email: `demo@noia.fr`
- Mot de passe: `noia2024`

### 6.3 Tester une Question

Essayez:

```
Quelles sont les règles de quorum pour un conseil municipal de 15 membres ?
```

**Vérifications:**

- ✅ Réponse détaillée (1500-2500 mots)
- ✅ Format Markdown avec `## Titres`
- ✅ Pas d'emojis de numérotation (1️⃣2️⃣3️⃣)
- ✅ Citations légales précises
- ✅ Sources en fin de réponse

### 6.4 Dépannage

**Problème: "Page introuvable" (404)**

→ Vérifiez le **Multisite** (Étape 2):
- Le domaine pointe-t-il vers `noia-mvp/public` ?
- Le DNS a-t-il propagé ? (30 min à 24h)

**Problème: "Erreur 500"**

→ Activez le debug temporairement:

1. Éditez `.env`:
   ```env
   APP_DEBUG=true
   ```
2. Rechargez la page, lisez l'erreur complète
3. Corrigez le problème
4. Remettez `APP_DEBUG=false`

**Problème: "Erreur de connexion base de données"**

→ Vérifiez dans `.env`:
- `DB_HOST` doit être `xxxxx.mysql.db` (pas `localhost` sur OVH mutualisé!)
- `DB_NAME`, `DB_USER`, `DB_PASSWORD` corrects

**Problème: "Réponse OpenAI invalide"**

→ Vérifiez:
- `OPENAI_API_KEY` valide (https://platform.openai.com/api-keys)
- `OPENAI_ASSISTANT_ID` correct
- Crédits OpenAI disponibles (https://platform.openai.com/usage)

---

## 📊 Récapitulatif de l'Architecture OVH

```
Nom de domaine: noia.erelys.fr
        ↓
    Multisite OVH (Étape 2)
        ↓
    Pointe vers: noia-mvp/public/
        ↓
    ┌─────────────────────────────────────┐
    │  /noia-mvp/                         │
    │  ├── .env                   ← ÉTAPE 4 (FileZilla)
    │  ├── public/ (WEB ROOT)    ← ÉTAPE 2 (Multisite)
    │  │   ├── index.html                 │
    │  │   ├── chat.html                  │
    │  │   ├── api/                        │
    │  │   └── .htaccess                   │
    │  ├── config/                         │
    │  │   └── config.php        ← ÉTAPE 4 (FileZilla)
    │  ├── src/                  ← ÉTAPE 3 (Git)
    │  ├── docs/                 ← ÉTAPE 5 (FileZilla)
    │  │   └── *.pdf (10 docs)            │
    │  ├── scripts/                        │
    │  └── logs/                 ← ÉTAPE 4 (créer)
    └─────────────────────────────────────┘
            ↑
        Git Push (Étape 3)
    Déploiement automatique
```

**Base de données MySQL** (Étape 1):
- phpMyAdmin → Tables créées
- Connexion depuis `.env`

**OpenAI Assistant** (Étape 5):
- Vector Store créé
- 10 PDFs uploadés
- Assistant ID dans `.env`

---

## 🎉 Félicitations!

NOIA MVP est maintenant **déployé en production sur OVH** avec:

✅ Nom de domaine personnalisé (Multisite)
✅ Déploiement automatique Git (push = déploiement)
✅ Base de données MySQL (phpMyAdmin)
✅ Configuration sécurisée (.env hors Git)
✅ Assistant OpenAI avec RAG (10 PDFs)
✅ HTTPS activé (Let's Encrypt)

---

## 🔄 Workflow de Mise à Jour

Pour mettre à jour NOIA après le déploiement initial:

```bash
# 1. Modifier le code localement
nano noia-mvp/public/api/assistant.php

# 2. Committer
git add .
git commit -m "Fix: amélioration gestion erreurs"

# 3. Pousser
git push origin main

# 4. OVH déploie automatiquement en 30 secondes!
```

**Pour ajouter un nouveau document PDF:**

1. Via FileZilla, uploadez le PDF dans `/noia-mvp/docs/`
2. SSH sur le serveur:
   ```bash
   ssh votre-login@ssh.cluster0XX.hosting.ovh.net
   cd noia-mvp
   php scripts/setup_vector_store.php
   ```
3. Mettez à jour `OPENAI_ASSISTANT_ID` dans `.env` avec le nouvel ID

---

## 🔒 Sécurité - Checklist Finale

Avant de mettre en production:

- [ ] `.env` a les permissions `600`
- [ ] `APP_DEBUG=false` dans `.env`
- [ ] HTTPS activé (certificat SSL)
- [ ] `.htaccess` présent dans `public/`
- [ ] `.gitignore` exclut `.env` et `config.php`
- [ ] Mot de passe MySQL fort
- [ ] Compte utilisateur de test supprimé ou mot de passe changé
- [ ] Rate limiting activé (100 req/heure)
- [ ] Sessions sécurisées (`SESSION_SECURE=true`)

---

## 📞 Support

**Problème de déploiement:** Vérifiez les logs Apache
- OVH: Hébergements → Statistiques et logs

**Problème OpenAI:** Vérifiez l'usage
- https://platform.openai.com/usage

**Documentation complète:** `README.md` dans le projet

---

**NOIA MVP v1.0** - Déployé avec succès! 🚀
