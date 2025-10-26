# 📋 LISTE DES FICHIERS À REMPLACER - Migration v1.0.0 → v2.0.0

## ⚠️ IMPORTANT

Si vous avez seulement remplacé `index.html`, **NOIA ne fonctionnera pas** car il manque les autres fichiers critiques.

---

## ✅ FICHIERS À REMPLACER OBLIGATOIREMENT

### 1. **index.html** ✅ (déjà fait)
📍 Emplacement : `/www/noia/index.html`

**Action** : REMPLACER
- ✅ Vous l'avez déjà fait

---

### 2. **script.js** ⚠️ CRITIQUE
📍 Emplacement : `/www/noia/script.js`

**Action** : REMPLACER complètement
**Raison** : La nouvelle version appelle directement `proxy.php` avec la nouvelle API

**Ancien comportement** (v1.0.0) :
- Appelle l'ancien proxy.php
- Qui appelle Make.com

**Nouveau comportement** (v2.0.0) :
- Appelle le nouveau proxy.php
- Qui appelle OpenAI directement
- Fallback automatique en mode démo si pas de serveur PHP

---

### 3. **api/proxy.php** ⚠️ CRITIQUE
📍 Emplacement : `/www/noia/api/proxy.php`

**Action** : REMPLACER complètement
**Raison** : C'est le cœur du changement - appelle OpenAI au lieu de Make.com

**Ancien comportement** (v1.0.0) :
```php
// Appelle Make.com webhook
$webhook_url = MAKE_WEBHOOK;
curl_exec(...);
```

**Nouveau comportement** (v2.0.0) :
```php
// Appelle OpenAI directement
$openai_url = 'https://api.openai.com/v1/chat/completions';
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . OPENAI_API_KEY
]);
```

---

### 4. **config/config.php** ⚠️ CRITIQUE
📍 Emplacement : `/www/noia/config/config.php`

**Action** : MODIFIER (pas remplacer complètement)

**Avant** (v1.0.0) :
```php
define('DB_HOST', 'mysql47.perso.ovh.net');
define('DB_NAME', 'votre_base');
define('DB_USER', 'votre_utilisateur');
define('DB_PASS', 'votre_mot_de_passe');

define('MAKE_WEBHOOK', 'https://hook.eu1.make.com/xxxxx'); // ← À SUPPRIMER
```

**Après** (v2.0.0) :
```php
define('DB_HOST', 'mysql47.perso.ovh.net');        // GARDER
define('DB_NAME', 'votre_base');                    // GARDER
define('DB_USER', 'votre_utilisateur');             // GARDER
define('DB_PASS', 'votre_mot_de_passe');            // GARDER

// SUPPRIMER la ligne MAKE_WEBHOOK
// AJOUTER les lignes suivantes :
define('OPENAI_API_KEY', 'sk-votre-cle-openai');   // ← AJOUTER
define('OPENAI_MODEL', 'gpt-4-turbo');              // ← AJOUTER
define('OPENAI_MAX_TOKENS', 1500);                  // ← AJOUTER
define('OPENAI_TEMPERATURE', 0.7);                  // ← AJOUTER

// Configuration de sécurité
define('RATE_LIMIT_REQUESTS', 30);
define('RATE_LIMIT_PERIOD', 3600);
define('MAX_QUESTION_LENGTH', 500);
define('ENABLE_LOGGING', true);
define('LOG_FILE', __DIR__ . '/../logs/queries.log');
define('RATE_LIMIT_FILE', __DIR__ . '/../logs/rate_limit.json');

// Configuration CORS
define('ALLOWED_ORIGINS', '*');

// Timezone
date_default_timezone_set('Europe/Paris');

// Mode debug
define('DEBUG_MODE', false);
```

**📝 Note** : Vous pouvez aussi copier `config.example.php` et le remplir avec vos valeurs.

---

## 📁 FICHIERS OPTIONNELS (mais recommandés)

### 5. **.htaccess** (optionnel)
📍 Emplacement : `/www/noia/.htaccess`

**Action** : REMPLACER
**Raison** : Sécurité améliorée (bloque l'accès à config.php)

---

### 6. **.gitignore** (optionnel)
📍 Emplacement : `/www/noia/.gitignore`

**Action** : CRÉER
**Raison** : Protège config.php dans Git

---

## ❌ FICHIERS À NE PAS TOUCHER

### Base de données MySQL
- ✅ **AUCUNE modification nécessaire**
- Tables `base_centrale` et `base_locale` restent identiques

### Dossier logs/
- ✅ **CONSERVER** tel quel
- Les logs existants sont conservés

---

## 📊 RÉSUMÉ DES ACTIONS

| Fichier | Action | Priorité | Déjà fait ? |
|---------|--------|----------|-------------|
| `index.html` | ✅ Remplacer | CRITIQUE | ✅ OUI |
| `script.js` | ⚠️ Remplacer | CRITIQUE | ❌ NON |
| `api/proxy.php` | ⚠️ Remplacer | CRITIQUE | ❌ NON |
| `config/config.php` | ⚠️ Modifier | CRITIQUE | ❌ NON |
| `.htaccess` | Remplacer | Recommandé | ❌ NON |
| Base de données | ✅ Ne rien faire | - | ✅ OK |

---

## 🚀 PROCÉDURE D'INSTALLATION COMPLÈTE

### Étape 1 : Télécharger les fichiers depuis Git
```bash
# Tous les fichiers sont dans la branche :
claude/remove-make-interface-011CUTxrLten4c6qKf8YMNek
```

### Étape 2 : Uploader via FTP

**Via FileZilla ou WinSCP** :

1. **Remplacer** `index.html` (déjà fait ✅)
2. **Remplacer** `script.js` ⚠️
3. **Remplacer** `api/proxy.php` ⚠️
4. **Remplacer** ou **Créer** `.htaccess`
5. **Créer** `config/config.example.php` (référence)

### Étape 3 : Modifier config.php

**Option A - Modifier l'existant** :
```bash
# Via FTP, téléchargez config.php
# Éditez-le localement
# Supprimez MAKE_WEBHOOK
# Ajoutez OPENAI_API_KEY et autres constantes
# Re-uploadez
```

**Option B - Remplacer complètement** :
```bash
# Copiez config.example.php vers config.php
# Remplissez avec vos vraies valeurs :
#   - Identifiants DB (garder les anciens)
#   - OPENAI_API_KEY (nouvelle clé à obtenir)
```

### Étape 4 : Obtenir une clé OpenAI

1. Allez sur https://platform.openai.com/api-keys
2. Créez une nouvelle clé
3. Copiez-la dans `config.php` → `OPENAI_API_KEY`

### Étape 5 : Tester

1. Accédez à `https://noia.votre-domaine.fr/`
2. Posez une question test
3. Si vous voyez le bandeau jaune "Mode démonstration" → problème de config
4. Si la réponse arrive normalement → ✅ Migration réussie !

---

## 🔧 DÉPANNAGE

### Problème : "Mode démonstration" affiché
**Cause** : Le serveur PHP ne peut pas appeler OpenAI

**Solutions** :
1. Vérifiez que `proxy.php` a été remplacé
2. Vérifiez que `config.php` contient `OPENAI_API_KEY`
3. Vérifiez que la clé API OpenAI est valide
4. Vérifiez que PHP a l'extension cURL activée
5. Consultez `/logs/queries.log` pour voir les erreurs

### Problème : Sablier qui tourne indéfiniment
**Cause** : `script.js` n'a pas été remplacé

**Solution** : Remplacez `script.js` par la nouvelle version

### Problème : Erreur 500
**Cause** : Erreur dans `config.php` ou `proxy.php`

**Solution** :
1. Vérifiez la syntaxe de `config.php`
2. Vérifiez que tous les `define()` se terminent par `;`
3. Activez `DEBUG_MODE = true` dans config.php
4. Consultez les logs PHP d'OVH

---

## 📁 STRUCTURE FINALE

Après migration, vous devriez avoir :

```
/www/noia/
├── index.html              ← Nouvelle version ✅
├── script.js               ← Nouvelle version ⚠️
├── .htaccess              ← Nouvelle version
├── /api/
│   └── proxy.php          ← Nouvelle version ⚠️
├── /config/
│   ├── config.php         ← Modifié ⚠️
│   └── config.example.php ← Nouveau (référence)
└── /logs/                 ← Conservé tel quel ✅
```

---

## 📞 BESOIN D'AIDE ?

Consultez le fichier `MIGRATION.md` pour le guide complet de migration.

**Temps estimé pour tout remplacer** : 10 minutes

---

**Version du document** : 2.0.0
**Dernière mise à jour** : Octobre 2024
