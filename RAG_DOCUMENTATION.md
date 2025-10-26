# 📚 NOIA v3.0 - RAG et Recherche Web

## 🎯 Nouvelle Fonctionnalité : Consultation Automatique des Sources

NOIA consulte maintenant **automatiquement** les sources officielles et votre base documentaire **avant de répondre**, grâce à :

1. **Recherche Web sur Sites Officiels** (Légifrance, service-public.fr, DGCL, DGFIP, CNFPT, emploi-collectivites.fr)
2. **Recherche Sémantique RAG** dans vos documents uploadés (avec embeddings OpenAI)
3. **Function Calling** : GPT-4 décide intelligemment quand chercher

---

## 🚀 Installation et Configuration

### 1. Mise à jour de la base de données

Exécutez le script SQL pour créer les nouvelles tables :

```bash
mysql -u votre_utilisateur -p votre_base < database/upgrade_rag_v3.sql
```

Ou via phpMyAdmin :
1. Ouvrir phpMyAdmin
2. Sélectionner votre base NOIA
3. Onglet "SQL"
4. Copier/coller le contenu de `database/upgrade_rag_v3.sql`
5. Exécuter

**Tables créées :**
- `documents` : Métadonnées des fichiers uploadés
- `embeddings` : Vecteurs d'embeddings pour recherche sémantique

---

### 2. Mise à jour de config.php

Ajoutez ces nouvelles lignes dans `config/config.php` :

```php
// Configuration OpenAI - Embeddings
define('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'); // Modèle pour RAG

// Configuration RAG et Recherche Web
define('ENABLE_WEB_SEARCH', true);        // Activer la recherche web
define('ENABLE_RAG', true);                // Activer la recherche documentaire
define('DOCUMENTS_DIR', __DIR__ . '/../documents'); // Dossier documents
```

---

### 3. Créer le dossier de stockage

```bash
mkdir -p documents
chmod 755 documents
```

---

### 4. Uploader les fichiers sur votre serveur

**Fichiers à ajouter via FTP :**

```
/www/noia/
├── api/
│   ├── web_search.php          ← NOUVEAU
│   ├── embeddings.php          ← NOUVEAU
│   ├── doc_manager.php         ← NOUVEAU
│   └── proxy.php               ← MODIFIÉ (Function Calling)
├── config/
│   ├── config.php              ← MODIFIÉ (nouveaux paramètres)
│   └── config.example.php      ← MODIFIÉ
├── database/
│   └── upgrade_rag_v3.sql      ← NOUVEAU
├── documents/                  ← NOUVEAU DOSSIER
├── admin_documents.html        ← NOUVEAU
└── RAG_DOCUMENTATION.md        ← CE FICHIER
```

---

## 📤 Utilisation : Gestion Documentaire

### Accéder à l'interface d'administration

Ouvrir dans votre navigateur :
```
https://noia.votre-domaine.fr/admin_documents.html
```

### Uploader un document

1. Cliquer sur "Choisir un fichier"
2. Sélectionner un PDF, DOCX, TXT, MD ou ODT (max 10 MB)
3. Choisir la **catégorie** :
   - FCTVA
   - Comptabilité
   - M57
   - Ressources Humaines
   - Juridique
   - Délibérations
   - Marchés Publics
   - Règlements
4. Optionnel : sélectionner une commune
5. Cliquer sur "📤 Uploader et indexer"

**Que se passe-t-il ?**
- Le fichier est uploadé dans `/documents/`
- Le texte est extrait (PDF, DOCX, TXT)
- Le document est découpé en chunks (~1000 caractères)
- Chaque chunk est converti en **embedding** via OpenAI
- Les embeddings sont stockés en BDD pour recherche sémantique

---

## 🔍 Fonctionnement du RAG

### Exemple : Question FCTVA

**Utilisateur pose :**
> "Quel compte d'imputation pour des travaux dans un bâtiment de la commune et pouvant prétendre à la FCTVA ?"

**1. Première étape : Function Calling**

GPT-4 analyse la question et **décide automatiquement** :
- ✅ Rechercher sur sites officiels (Légifrance, DGFIP, DGCL)
- ✅ Rechercher dans la base documentaire locale

**2. Recherche Web Automatique**

NOIA cherche sur :
- `site:legifrance.gouv.fr FCTVA communes article loi`
- `site:impots.gouv.fr FCTVA travaux bâtiment`
- `site:collectivites-locales.gouv.fr FCTVA compte imputation`

Résultats récupérés :
```json
{
  "results": [
    {
      "title": "Article L.1615-1 du CGCT - Légifrance",
      "url": "https://www.legifrance.gouv.fr/...",
      "source": "legifrance",
      "content": "Le fonds de compensation..."
    },
    {
      "title": "Instruction M57 - DGFiP",
      "url": "https://www.impots.gouv.fr/...",
      "source": "dgfip",
      "content": "Compte 2131, 2313, 615221..."
    }
  ]
}
```

**3. Recherche RAG (Embeddings)**

NOIA cherche dans vos documents uploadés :
- Convertit la question en embedding
- Compare avec les embeddings stockés (similarité cosinus)
- Retourne les 5 documents les plus pertinents

Exemple de résultats :
```json
{
  "documents": [
    {
      "doc_id": "42_chunk_0",
      "text": "Délibération 2024-03 : Imputation FCTVA sur compte 2131...",
      "similarity": 0.89
    }
  ]
}
```

**4. Réponse Enrichie**

GPT-4 reçoit :
- Base centrale MySQL (existant)
- Résultats Web officiels
- Documents locaux (RAG)

Et génère une réponse **précise** avec :
- ✅ Numéros de comptes exacts (2131, 2313, 615221)
- ✅ Références juridiques (Article L.1615-1 CGCT)
- ✅ Instructions officielles (Instruction M57 DGFiP)
- ✅ Documents locaux de votre commune

---

## ⚙️ Configuration Avancée

### Activer/Désactiver les fonctionnalités

Dans `config/config.php` :

```php
// Désactiver la recherche web (utiliser uniquement la base locale)
define('ENABLE_WEB_SEARCH', false);

// Désactiver le RAG (utiliser uniquement MySQL + Web)
define('ENABLE_RAG', false);
```

### Optimiser les coûts OpenAI

**Modèle d'embeddings :**

```php
// Plus rapide et moins cher (recommandé)
define('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small');

// Plus précis mais plus cher
define('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-large');
```

**Coûts estimés :**

| Action | Modèle | Coût |
|--------|--------|------|
| Créer embedding 1 document (5000 mots) | text-embedding-3-small | ~0.0001€ |
| Recherche sémantique (1 query) | text-embedding-3-small | ~0.00002€ |
| Appel GPT-4 avec function calling | gpt-4-turbo | ~0.03€ |

**Pour 100 questions/mois avec RAG + Web :**
- Embeddings : ~0.20€
- GPT-4 : ~3€
- **Total : ~3.20€/mois** (vs 9-30€/mois avec Make.com)

---

## 🧪 Test du Système

### 1. Tester la recherche web

```bash
curl -X POST https://noia.votre-domaine.fr/api/proxy.php \
  -H "Content-Type: application/json" \
  -d '{
    "question": "Quel est le délai de convocation du conseil municipal ?",
    "commune": "general"
  }'
```

**Attendu :**
- GPT appelle `search_official_websites`
- Recherche sur Légifrance et DGCL
- Réponse avec "5 jours francs (Article L2121-11 CGCT)"

### 2. Tester le RAG

1. Uploader un document via `admin_documents.html`
2. Poser une question liée au document
3. Vérifier que NOIA cite le document

---

## 🔒 Sécurité

### Protection de l'interface admin

Ajouter une authentification dans `.htaccess` :

```apache
<Files "admin_documents.html">
    AuthType Basic
    AuthName "Admin NOIA"
    AuthUserFile /home/votre_user/.htpasswd
    Require valid-user
</Files>
```

Créer le fichier `.htpasswd` :
```bash
htpasswd -c .htpasswd admin
```

---

## 📊 Suivi et Monitoring

### Logs des recherches

Les recherches web et RAG sont loguées dans :
```
/www/noia/logs/queries.log
```

Format :
```json
{
  "timestamp": "2024-03-15 14:32:10",
  "question": "FCTVA travaux...",
  "web_search": true,
  "rag_search": true,
  "sources_count": {"web": 3, "rag": 2}
}
```

---

## ❓ FAQ

### Q1 : NOIA cherche-t-il toujours sur le web ?
**R :** Non, GPT-4 décide intelligemment **si nécessaire**. Pour une question sur un règlement local, il cherchera uniquement dans vos documents.

### Q2 : Combien de documents puis-je uploader ?
**R :** Pas de limite technique, mais surveillez l'espace disque et les coûts d'embeddings.

### Q3 : Quels formats sont supportés ?
**R :** PDF, DOCX, TXT, MD, ODT (max 10 MB par fichier)

### Q4 : Comment mettre à jour un document ?
**R :** Supprimez l'ancien et uploadez le nouveau. Les embeddings sont recréés automatiquement.

### Q5 : Les recherches web sont-elles fiables ?
**R :** Oui, NOIA cherche **uniquement** sur les sites officiels (Légifrance, DGCL, DGFIP, service-public.fr, CNFPT, emploi-collectivites.fr).

---

## 🎓 Exemples d'Utilisation

### Exemple 1 : Question FCTVA avec documents locaux

**Documents uploadés :**
- `deliberation_fctva_2024.pdf` (catégorie: FCTVA)
- `instruction_m57_extrait.docx` (catégorie: Comptabilité)

**Question :**
> "Comment comptabiliser la FCTVA pour les travaux de voirie ?"

**NOIA va :**
1. Chercher sur Légifrance et DGFIP
2. Chercher dans vos documents (RAG)
3. Répondre en citant :
   - Article L.1615-1 CGCT (web)
   - Votre délibération 2024 (RAG)
   - Comptes 2313, 615221 (web + base centrale)

### Exemple 2 : Question RH locale

**Documents uploadés :**
- `reglement_interieur_2024.pdf` (catégorie: RH)

**Question :**
> "Quel est notre règlement sur les congés exceptionnels ?"

**NOIA va :**
1. NE PAS chercher sur le web (question locale)
2. Chercher uniquement dans vos documents (RAG)
3. Citer votre règlement intérieur

---

## 🔄 Migration v2.0 → v3.0

Si vous avez déjà NOIA v2.0 :

1. ✅ Sauvegarder `config/config.php`
2. ✅ Exécuter `upgrade_rag_v3.sql`
3. ✅ Uploader les nouveaux fichiers PHP
4. ✅ Ajouter les nouveaux paramètres dans config.php
5. ✅ Créer le dossier `documents/`
6. ✅ Tester avec une question

**Aucune régression :** Si ENABLE_WEB_SEARCH et ENABLE_RAG sont à `false`, NOIA fonctionne comme en v2.0.

---

## 📞 Support

Pour toute question ou problème :
1. Vérifier les logs : `/www/noia/logs/queries.log`
2. Activer DEBUG_MODE dans config.php
3. Tester avec `admin_documents.html`

**Version :** 3.0.0
**Date :** Mars 2024
**Auteur :** NOIA Team
