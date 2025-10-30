# 🚀 NOIA - Déploiement Automatique via Git OVH

## 🎯 Avantages du Déploiement Git

**VS Méthode Manuelle (cPanel) :**

| Critère | cPanel Manuel | Git OVH |
|---------|--------------|---------|
| **Mise à jour** | ❌ Copier-coller chaque fichier | ✅ `git push` (1 commande) |
| **Temps déploiement** | ❌ 10-15 min | ✅ 30 secondes |
| **Risque erreur** | ❌ Élevé (oubli fichiers) | ✅ Aucun (automatique) |
| **Rollback** | ❌ Complexe (backup manuel) | ✅ `git revert` (1 commande) |
| **Versions** | ❌ Pas de suivi | ✅ Historique complet |
| **Collaboration** | ❌ Difficile | ✅ Multi-développeurs |

**Verdict : Git OVH est LA solution professionnelle** ✅

---

## 📋 Prérequis OVH

### **Hébergements Compatibles**

| Offre OVH | Git Déploiement | Prix/mois |
|-----------|-----------------|-----------|
| Perso (100 Go) | ❌ Non | ~3€ |
| Pro (1 TB) | ❌ Non | ~7€ |
| **Performance 1** | ✅ **OUI** | ~10€ |
| **Performance 2** | ✅ **OUI** | ~15€ |
| **Cloud Web** | ✅ **OUI** | ~9€ |

**⚠️ Vérification :**
1. Connectez-vous à votre espace client OVH
2. Allez dans "Hébergements"
3. Cliquez sur votre hébergement
4. Si vous voyez l'onglet **"Associer Git"** → ✅ Compatible
5. Si absent → ❌ Upgrade nécessaire vers Performance

**Alternative si hébergement non compatible :**
- Utiliser **GitHub Actions** + FTP (guide fourni)
- Upgrade vers OVH Performance (~10€/mois)

---

## 🏗️ Structure de Projet Adaptée Git

### **Architecture des Dossiers**

```
NOIA_V1/
├── .git/                          # Dépôt Git
├── .gitignore                     # Fichiers à ignorer
├── .env.example                   # Template variables d'environnement
├── README.md                      # Documentation
│
├── public/                        # ← RACINE WEB (DocumentRoot)
│   ├── index.html                 # Page d'accueil
│   ├── login.html                 # Page de connexion
│   ├── chat.html                  # Interface chat
│   ├── .htaccess                  # Config Apache
│   │
│   ├── css/
│   │   └── style.css
│   │
│   ├── js/
│   │   ├── chat.js
│   │   └── auth.js
│   │
│   └── api/                       # Points d'entrée API
│       ├── assistant.php          # API principale
│       ├── auth.php               # Authentification
│       └── upload.php             # Upload documents
│
├── config/                        # ← HORS WEB (sécurisé)
│   ├── config.php                 # Configuration (chargé depuis .env)
│   └── database.php               # Connexion BDD
│
├── src/                           # ← HORS WEB (sécurisé)
│   ├── Assistant/
│   │   ├── AssistantManager.php
│   │   └── ThreadManager.php
│   ├── Auth/
│   │   └── AuthManager.php
│   └── Database/
│       └── Database.php
│
├── docs/                          # Documentation
│   ├── legal_facts_quorum.pdf
│   ├── legal_facts_fctva.pdf
│   └── ...
│
├── scripts/                       # Scripts utilitaires
│   ├── setup_vector_store.php
│   └── create_tables.sql
│
└── tests/                         # Tests (futur)
    └── test_assistant.php
```

**Point CRUCIAL pour OVH :**
- ✅ **`public/`** = racine web (DocumentRoot)
- ✅ **`config/`, `src/`** = HORS WEB (sécurisé, pas accessible HTTP)
- ✅ `.env` = JAMAIS dans Git (contient secrets)

---

## 🔒 Fichier .gitignore

**À créer à la racine du projet :**

```gitignore
# Fichiers de configuration sensibles
.env
config/config.php

# Logs
*.log
logs/
error_log

# Fichiers temporaires
*.tmp
tmp/
temp/

# Uploads utilisateurs (si applicable)
uploads/
storage/

# Cache
cache/
*.cache

# Dépendances (si Composer utilisé)
vendor/
node_modules/

# Fichiers IDE
.vscode/
.idea/
*.swp
*.swo
*~

# Fichiers système
.DS_Store
Thumbs.db
desktop.ini

# Sauvegardes
*.bak
*.backup
*.sql.gz

# Fichiers de sessions PHP
sessions/
tmp/sess_*

# Rate limiting
rate_limit.json
```

**Important :** Ne JAMAIS commiter `.env` ou `config.php` avec secrets !

---

## 🔑 Gestion des Variables d'Environnement

### **Fichier .env.example (à commiter)**

```env
# OpenAI Configuration
OPENAI_API_KEY=sk-proj-VOTRE_CLE_ICI
OPENAI_ASSISTANT_ID=asst_VOTRE_ID_ICI
OPENAI_MODEL=gpt-4o

# Database Configuration
DB_HOST=localhost
DB_NAME=noia_db
DB_USER=noia_user
DB_PASSWORD=VOTRE_MOT_DE_PASSE

# Application Configuration
APP_ENV=production
APP_DEBUG=false
APP_URL=https://noia.erelys.fr

# Session Configuration
SESSION_LIFETIME=7200
SESSION_SECURE=true

# Rate Limiting
RATE_LIMIT_REQUESTS=100
RATE_LIMIT_PERIOD=3600
```

### **Fichier .env (JAMAIS commité, créé manuellement sur serveur)**

Copier `.env.example` → `.env` et remplir les vraies valeurs sur le serveur OVH.

---

### **Fichier config/config.php (charge .env)**

```php
<?php
/**
 * NOIA - Configuration
 * Charge les variables depuis .env
 */

// Charger .env (librairie simple)
function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception(".env file not found at: {$path}");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignorer commentaires
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parser KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Retirer guillemets si présents
            $value = trim($value, '"\'');

            // Définir comme constante
            if (!defined($key)) {
                define($key, $value);
            }
        }
    }
}

// Charger .env depuis la racine du projet
$envPath = __DIR__ . '/../.env';
loadEnv($envPath);

// Utiliser les constantes
define('OPENAI_API_KEY', OPENAI_API_KEY ?? 'sk-default');
define('OPENAI_ASSISTANT_ID', OPENAI_ASSISTANT_ID ?? '');
define('OPENAI_MODEL', OPENAI_MODEL ?? 'gpt-4o');

define('DB_HOST', DB_HOST ?? 'localhost');
define('DB_NAME', DB_NAME ?? 'noia_db');
define('DB_USER', DB_USER ?? 'root');
define('DB_PASSWORD', DB_PASSWORD ?? '');

define('APP_ENV', APP_ENV ?? 'production');
define('APP_DEBUG', APP_DEBUG === 'true');
define('APP_URL', APP_URL ?? 'https://noia.erelys.fr');

define('SESSION_LIFETIME', (int)(SESSION_LIFETIME ?? 7200));
define('RATE_LIMIT_REQUESTS', (int)(RATE_LIMIT_REQUESTS ?? 100));
define('RATE_LIMIT_PERIOD', (int)(RATE_LIMIT_PERIOD ?? 3600));

// Mode debug
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
```

---

## 🔧 Configuration OVH Git

### **Étape 1 : Créer Dépôt Git (GitHub/GitLab)**

**Option A : GitHub (Gratuit)**

1. Aller sur https://github.com/new
2. Nom du dépôt : `noia-app`
3. Visibilité : **Private** (recommandé)
4. Créer le dépôt

**Option B : GitLab (Gratuit)**

1. Aller sur https://gitlab.com/projects/new
2. Nom : `noia-app`
3. Visibilité : **Private**
4. Créer

---

### **Étape 2 : Pousser Code vers Dépôt**

**Depuis votre machine locale :**

```bash
# Initialiser Git (si pas déjà fait)
cd /chemin/vers/NOIA_V1
git init

# Ajouter remote
git remote add origin https://github.com/VOTRE_USERNAME/noia-app.git

# Créer .gitignore
cat > .gitignore << 'EOF'
.env
config/config.php
*.log
logs/
tmp/
cache/
uploads/
vendor/
node_modules/
.DS_Store
EOF

# Ajouter tous les fichiers
git add .

# Premier commit
git commit -m "Initial commit - NOIA MVP"

# Pousser vers GitHub/GitLab
git push -u origin main
```

---

### **Étape 3 : Associer Git sur OVH**

**Interface OVH :**

1. **Connexion espace client OVH** : https://www.ovh.com/manager/
2. **Hébergements** → Sélectionner votre hébergement
3. **Onglet "Associer Git"**
4. **Cliquer "Associer un dépôt Git"**

**Configuration :**

| Champ | Valeur |
|-------|--------|
| **URL du dépôt** | `https://github.com/VOTRE_USERNAME/noia-app.git` |
| **Branche** | `main` (ou `master`) |
| **Clé de déploiement** | Générer et copier la clé SSH fournie |
| **Répertoire cible** | `/www` (ou votre racine web) |
| **Répertoire source** | `public` (important !) |

**Important : Répertoire source = `public`**
- OVH copiera seulement le contenu de `public/` vers `/www/`
- Les dossiers `config/`, `src/` seront un niveau au-dessus (sécurisés)

---

### **Étape 4 : Ajouter Clé de Déploiement**

**OVH génère une clé SSH publique. Il faut l'ajouter à GitHub/GitLab :**

**Pour GitHub :**
1. Aller sur votre dépôt
2. **Settings** → **Deploy keys**
3. **Add deploy key**
4. Titre : `OVH Deployment`
5. Coller la clé SSH générée par OVH
6. ✅ Cocher **"Allow write access"** (si vous voulez push depuis OVH)
7. **Add key**

**Pour GitLab :**
1. Projet → **Settings** → **Repository**
2. Section **Deploy Keys**
3. **Add new key**
4. Coller la clé SSH OVH
5. **Add key**

---

### **Étape 5 : Premier Déploiement**

**Dans l'interface OVH :**

1. Après configuration, cliquer **"Déployer"**
2. OVH va :
   - Clone le dépôt
   - Copier `public/` vers `/www/`
   - Exécuter les scripts de déploiement (si configurés)

**Logs de déploiement :**
- Visibles dans l'interface OVH
- En cas d'erreur, vérifier les logs

---

## 📁 Structure Finale sur Serveur OVH

**Après déploiement, sur le serveur OVH :**

```
/home/votre_login/
├── .git/                    # Dépôt Git
├── config/                  # ← HORS WEB (sécurisé)
├── src/                     # ← HORS WEB (sécurisé)
├── docs/
├── scripts/
│
└── www/                     # ← RACINE WEB (DocumentRoot)
    ├── index.html
    ├── chat.html
    ├── .htaccess
    ├── css/
    ├── js/
    └── api/
        ├── assistant.php
        └── auth.php
```

**Avantages sécurité :**
- ✅ `config/` et `src/` ne sont PAS accessibles via HTTP
- ✅ Seul `www/` (= `public/`) est exposé
- ✅ `.env` est un niveau au-dessus de la racine web

---

## 🔄 Workflow de Développement

### **1. Développement Local**

```bash
# Créer une branche de feature
git checkout -b feature/nouvelle-fonctionnalite

# Développer...
# Modifier fichiers

# Commiter
git add .
git commit -m "Ajout nouvelle fonctionnalité"

# Pousser vers GitHub
git push origin feature/nouvelle-fonctionnalite
```

---

### **2. Tests en Staging (Optionnel)**

**Si vous avez un environnement de staging :**

```bash
# Merge dans branche dev
git checkout dev
git merge feature/nouvelle-fonctionnalite
git push origin dev

# OVH déploie automatiquement sur https://staging.noia.erelys.fr
```

---

### **3. Déploiement Production**

```bash
# Merge dans main
git checkout main
git merge dev
git push origin main

# OVH déploie automatiquement sur https://noia.erelys.fr
```

**🎉 C'est tout !** Le déploiement est automatique.

---

### **4. Rollback en Cas de Problème**

```bash
# Voir l'historique
git log --oneline

# Revenir à la version précédente
git revert HEAD
git push origin main

# OU annuler jusqu'à un commit spécifique
git reset --hard abc1234
git push --force origin main

# OVH redéploie automatiquement la version précédente
```

---

## ⚙️ Configuration .htaccess

**Fichier `public/.htaccess` :**

```apache
# NOIA - Configuration Apache
# Déploiement Git OVH

<IfModule mod_rewrite.c>
    RewriteEngine On

    # Forcer HTTPS
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

    # Rediriger / vers /index.html
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^$ index.html [L]

    # Empêcher accès aux fichiers sensibles
    RewriteRule ^\.env$ - [F,L]
    RewriteRule ^config/ - [F,L]
    RewriteRule ^src/ - [F,L]
</IfModule>

# Désactiver listing des répertoires
Options -Indexes

# Protéger fichiers sensibles
<FilesMatch "^\.(?!well-known)">
    Require all denied
</FilesMatch>

# PHP configuration
<IfModule mod_php.c>
    php_value upload_max_filesize 20M
    php_value post_max_size 20M
    php_value max_execution_time 300
    php_value max_input_time 300
</IfModule>

# Compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
</IfModule>

# Cache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
</IfModule>
```

---

## 🚨 Création du .env sur Serveur

**IMPORTANT : Le fichier .env n'est PAS dans Git !**

**Après premier déploiement, se connecter en SSH :**

```bash
# Connexion SSH OVH
ssh votre_login@ssh.cluster0XX.hosting.ovh.net

# Aller à la racine du projet
cd ~/

# Créer .env depuis .env.example
cp .env.example .env

# Éditer avec nano
nano .env

# Remplir les vraies valeurs :
# - OPENAI_API_KEY=sk-proj-...
# - DB_PASSWORD=...
# - etc.

# Sauvegarder : Ctrl+O, Enter, Ctrl+X

# Protéger le fichier
chmod 600 .env
```

---

## 🎣 Webhooks pour Déploiement Automatique

**OVH peut déployer automatiquement à chaque push GitHub :**

### **Configuration Webhook GitHub**

1. **Dépôt GitHub** → **Settings** → **Webhooks**
2. **Add webhook**
3. **Payload URL** : URL fournie par OVH (dans "Associer Git")
4. **Content type** : `application/json`
5. **Secret** : Secret fourni par OVH
6. **Events** : "Just the push event"
7. **Add webhook**

**Résultat :** À chaque `git push`, GitHub notifie OVH qui déploie automatiquement ✅

---

## 📊 Monitoring des Déploiements

**Dans l'interface OVH "Associer Git" :**

| Information | Détail |
|-------------|--------|
| **Dernier déploiement** | Date/heure |
| **Commit déployé** | Hash + message |
| **Statut** | ✅ Succès / ❌ Échec |
| **Logs** | Logs complets du déploiement |
| **Durée** | Temps de déploiement |

**En cas d'erreur :**
- Consulter les logs
- Vérifier permissions fichiers
- Vérifier structure dossiers
- Vérifier .htaccess

---

## 🔐 Sécurité Avancée

### **1. Protéger .env avec .htaccess**

**Fichier `/.htaccess` (à la racine, hors public) :**

```apache
# Interdire accès à .env
<Files ".env">
    Require all denied
</Files>
```

---

### **2. Utiliser Secrets GitHub (CI/CD)**

**Si vous utilisez GitHub Actions :**

1. **Dépôt** → **Settings** → **Secrets and variables** → **Actions**
2. **New repository secret**
3. Ajouter :
   - `OPENAI_API_KEY`
   - `DB_PASSWORD`
   - etc.

---

## 📋 Checklist de Mise en Place

**Avant déploiement :**
- [ ] Structure de projet avec `public/` à la racine
- [ ] `.gitignore` configuré (ne pas commiter .env)
- [ ] `.env.example` créé et committé
- [ ] Dépôt GitHub/GitLab créé (private)
- [ ] Code pushé vers dépôt

**Configuration OVH :**
- [ ] Hébergement Performance ou Cloud Web (avec Git)
- [ ] "Associer Git" accessible dans l'interface
- [ ] Dépôt Git associé
- [ ] Clé de déploiement ajoutée à GitHub/GitLab
- [ ] Répertoire source = `public`
- [ ] Premier déploiement réussi

**Post-déploiement :**
- [ ] Connexion SSH au serveur
- [ ] Création `.env` depuis `.env.example`
- [ ] Remplissage valeurs .env (clés API, BDD)
- [ ] Permissions `.env` = 600
- [ ] Test du site : https://noia.erelys.fr
- [ ] Webhook GitHub configuré (déploiement auto)

---

## 🚀 Résumé des Avantages

| Avantage | Bénéfice |
|----------|----------|
| ✅ **Déploiement 1 commande** | `git push` = déploiement auto |
| ✅ **Historique complet** | Rollback facile en cas d'erreur |
| ✅ **Collaboration** | Plusieurs développeurs possible |
| ✅ **Sécurité** | `config/` hors racine web |
| ✅ **Professionnel** | Workflow moderne |
| ✅ **Zéro downtime** | Déploiement sans interruption |

---

## ❓ FAQ

**Q : Mon hébergement OVH n'a pas "Associer Git" ?**
**R :** Upgrade vers Performance (~10€/mois) ou utiliser GitHub Actions + FTP

**Q : Puis-je utiliser GitLab au lieu de GitHub ?**
**R :** Oui, même processus, compatibilité totale

**Q : Comment gérer plusieurs environnements (dev/prod) ?**
**R :** Créer 2 branches (`dev` et `main`) et 2 associations Git OVH distinctes

**Q : Le .env est-il vraiment sécurisé ?**
**R :** Oui, si hors racine web + permissions 600 + .htaccess protection

**Q : Puis-je tester en local avant déploiement ?**
**R :** Oui, copier `.env.example` → `.env` localement avec valeurs de test

---

## 🎯 Prochaines Étapes

**Voulez-vous que je :**
1. ✅ Crée la structure de projet complète (avec tous les fichiers)
2. ✅ Génère le code `assistant.php` + interface adaptés à cette structure
3. ✅ Fournisse un script d'installation automatique
4. ✅ Crée un guide vidéo pas à pas du déploiement

**Dites-moi et je prépare tout pour vous !** 🚀
