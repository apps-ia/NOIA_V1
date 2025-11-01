# 🚀 NOIA MVP - Installation et Déploiement

**NOIA** (Nouvel Outil d'Intelligence Administrative) - Assistant IA pour l'administration territoriale française.

## 📋 Prérequis

- **Serveur**: OVH Performance (ou équivalent) avec support Git
- **PHP**: 7.4+ avec extensions: curl, pdo_mysql, mbstring
- **MySQL**: 5.7+ ou MariaDB 10.3+
- **Compte OpenAI**: API Key + Assistant ID
- **Git**: Pour le déploiement automatique

## 🔧 Installation

### 1. Cloner le Repository

```bash
git clone <votre-repo> noia-mvp
cd noia-mvp
```

### 2. Configuration de l'Environnement

```bash
# Copier le template
cp .env.example .env

# Éditer avec vos valeurs
nano .env
```

**Valeurs à configurer dans `.env`:**

```env
# OpenAI (à obtenir sur https://platform.openai.com)
OPENAI_API_KEY=sk-proj-VOTRE_CLE_ICI
OPENAI_ASSISTANT_ID=asst_VOTRE_ID_ICI  # Sera généré à l'étape 5
OPENAI_MODEL=gpt-4o

# Base de données
DB_HOST=localhost
DB_NAME=noia_db
DB_USER=votre_utilisateur
DB_PASSWORD=votre_mot_de_passe

# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://noia.erelys.fr

# Sécurité
SESSION_LIFETIME=7200
SESSION_SECURE=true

# Rate Limiting
RATE_LIMIT_REQUESTS=100
RATE_LIMIT_PERIOD=3600
```

### 3. Créer la Configuration PHP

```bash
# Copier le template
cp config/config.example.php config/config.php

# Le fichier config.php charge automatiquement .env
# Aucune modification nécessaire
```

### 4. Créer la Base de Données

```bash
# Se connecter à MySQL
mysql -u root -p

# Exécuter le script
source scripts/create_tables.sql
```

Ou via phpMyAdmin: importer `scripts/create_tables.sql`

**Utilisateur de test créé automatiquement:**
- Email: `demo@noia.fr`
- Mot de passe: `noia2024`

### 5. Préparer les Documents PDF

Placer vos documents dans le dossier `docs/`:

```
docs/
├── legal_facts_quorum.pdf
├── legal_facts_fctva.pdf
├── grilles_rh_2024.pdf
├── guide_m57.pdf
└── ...
```

**Documents recommandés (10 PDFs):**
1. Règles de quorum (CGCT)
2. FCTVA (Fonds de Compensation TVA)
3. Marchés publics
4. Convocations et délibérations
5. Budget et M57
6. Grilles indiciaires RH
7. IFSE et RIFSEEP
8. Procédures courantes
9. Modèles d'actes
10. Guide administratif général

### 6. Configurer le Vector Store et l'Assistant

```bash
php scripts/setup_vector_store.php
```

**Ce script va:**
1. Créer un Vector Store OpenAI
2. Uploader tous les PDFs du dossier `docs/`
3. Créer l'Assistant NOIA
4. Afficher les IDs à copier dans `.env`

**Exemple de sortie:**

```
✅ Vector Store créé: vs_abc123
✅ Assistant créé: asst_xyz789

📝 Ajoutez ces valeurs dans votre fichier .env:

OPENAI_ASSISTANT_ID=asst_xyz789
OPENAI_VECTOR_STORE_ID=vs_abc123
```

**Copier ces valeurs dans `.env`**

### 7. Tester Localement

```bash
# Lancer un serveur PHP local
php -S localhost:8000 -t public

# Ouvrir dans le navigateur
http://localhost:8000
```

**Connexion:**
- Email: `demo@noia.fr`
- Mot de passe: `noia2024`

## 🌐 Déploiement sur OVH

### Méthode 1: Git (Recommandé)

**Configuration OVH:**

1. Se connecter à l'espace client OVH
2. Hébergements → Votre hébergement → Associer Git
3. Configurer:
   - **URL du dépôt**: `https://github.com/votre-user/noia-mvp.git`
   - **Branche**: `main`
   - **Répertoire cible**: `/www`
   - **Répertoire source**: `public/`

**Structure Git OVH:**

```
/home/votre_login/
├── .env                    # Créer manuellement via SSH/FTP
├── config/
│   └── config.php         # Créer manuellement via SSH/FTP
├── src/
├── scripts/
└── www/                   # Répertoire web (public/)
    ├── index.html
    ├── chat.html
    ├── api/
    ├── js/
    └── css/
```

**Déploiement:**

```bash
# Pousser les modifications
git add .
git commit -m "Update NOIA"
git push origin main

# OVH déploie automatiquement en 30 secondes
```

### Méthode 2: FTP Manuel

**Structure FTP:**

```
/www/                      # Contenu du dossier public/
├── index.html
├── chat.html
├── .htaccess
├── api/
├── js/
└── css/

/config/                   # EN DEHORS de /www (sécurité)
└── config.php

/.env                      # EN DEHORS de /www (sécurité)
```

**⚠️ IMPORTANT:**
- `.env` et `config/config.php` doivent être **EN DEHORS** du répertoire web `/www`
- Ne JAMAIS committer `.env` ou `config/config.php` sur Git

## 🔒 Sécurité

### Permissions Fichiers (SSH)

```bash
# Sécuriser .env
chmod 600 .env
chmod 600 config/config.php

# Permissions répertoires
chmod 755 public
chmod 755 src
chmod 755 config
```

### Créer des Utilisateurs

```sql
-- Connexion MySQL
mysql -u root -p noia_db

-- Créer un utilisateur
INSERT INTO users (email, password_hash, nom, prenom, role) VALUES
('email@example.com', '$2y$10$...', 'Nom', 'Prenom', 'agent');
```

**Générer un mot de passe hashé:**

```php
<?php
echo password_hash('votre_mot_de_passe', PASSWORD_DEFAULT);
```

### CRON: Nettoyage RGPD (Threads > 30 jours)

**Configurer via cPanel ou SSH:**

```bash
# Éditer crontab
crontab -e

# Ajouter cette ligne (exécution quotidienne à 2h)
0 2 * * * /usr/bin/php /home/votre_login/noia-mvp/scripts/cleanup_old_threads.php >> /home/votre_login/logs/cleanup.log 2>&1
```

## 📊 Coûts Estimés

**Hébergement OVH Performance:** ~10-15€/mois

**OpenAI API (150 questions/jour avec GPT-4o):**
- Input tokens: ~$3/mois
- Output tokens: ~$68/mois
- File Search (RAG): ~$0.15/mois
- **Total API: ~$71/mois**

**TOTAL: ~85€/mois** (~$92/mois)

## 🧪 Tests

**Questions de test:**

1. "Quelles sont les règles de quorum pour un conseil municipal de 15 membres ?"
2. "Comment calculer la FCTVA ?"
3. "Quelle est la grille indiciaire d'un adjoint administratif territorial ?"
4. "Procédure pour une convocation de conseil municipal"
5. "Qu'est-ce que le RIFSEEP ?"

**Vérifications:**

- ✅ Réponses détaillées (1500-2500 mots)
- ✅ Format Markdown avec en-têtes `##`
- ✅ Citations légales précises
- ✅ Pas de numérotation emoji (1️⃣2️⃣3️⃣)
- ✅ Sources en fin de réponse

## 🐛 Dépannage

### Erreur "Réponse OpenAI invalide"

**Vérifier:**
1. `OPENAI_API_KEY` valide dans `.env`
2. `OPENAI_ASSISTANT_ID` correct
3. Crédits OpenAI suffisants

### Erreur de connexion base de données

**Vérifier:**
1. `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` dans `.env`
2. Base de données créée (`noia_db`)
3. Utilisateur MySQL avec droits suffisants

### Session expirée

**Augmenter la durée dans `.env`:**
```env
SESSION_LIFETIME=14400  # 4 heures au lieu de 2
```

### Logs

**Activer le debug (développement uniquement):**

```env
APP_ENV=development
APP_DEBUG=true
```

**Voir les logs Apache:**
```bash
tail -f /var/log/apache2/error.log
```

## 📚 Documentation

- **OpenAI Assistants API**: https://platform.openai.com/docs/assistants/overview
- **OpenAI RGPD/DPA**: https://openai.com/enterprise-privacy
- **OVH Git**: https://docs.ovh.com/fr/hosting/configurer-deploiement-git-hebergement-web/

## 🆕 Mises à Jour

### Ajouter des Documents

```bash
# 1. Ajouter PDFs dans docs/
cp nouveau_document.pdf docs/

# 2. Uploader vers OpenAI
# Option A: Re-exécuter setup_vector_store.php
php scripts/setup_vector_store.php

# Option B: Upload manuel via OpenAI Playground
# https://platform.openai.com/assistants
```

### Mettre à Jour le Prompt

Modifier `PROMPT_NOIA.md` puis mettre à jour l'Assistant:

```bash
# Via API ou OpenAI Playground
# https://platform.openai.com/assistants/<VOTRE_ASSISTANT_ID>
```

### Migration vers GPT-5 (quand disponible)

**Modifier `.env`:**
```env
OPENAI_MODEL=gpt-5
```

**C'est tout !** L'architecture est prête.

## 👥 Support

**Contact technique:** contact@noia.fr
**Documentation complète:** Voir fichiers `ARCHITECTURE_*.md` et `GUIDE_*.md`

## 📝 Licence

Propriétaire - NOIA v1.0 - 2025
