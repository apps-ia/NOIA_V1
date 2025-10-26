# NOIA v4.0 - Architecture Technique

## 📐 Vue d'ensemble

```
┌─────────────────────────────────────────────────────────────────┐
│                        UTILISATEURS                              │
│          Admin | Agent | Invité (multi-niveaux)                 │
└────────────────────────┬────────────────────────────────────────┘
                         │
         ┌───────────────┴──────────────┐
         │                              │
    ┌────▼─────┐                   ┌───▼────┐
    │ Frontend │                   │  API   │
    │ Respon-  │◄──── Token ──────►│ Backend│
    │  sive    │      JWT/Session  │  PHP   │
    └──────────┘                   └────┬───┘
         │                              │
         │        ┌─────────────────────┼─────────────────┐
         │        │                     │                 │
         │   ┌────▼────┐        ┌───────▼──────┐   ┌─────▼─────┐
         │   │ MySQL   │        │  OpenAI API  │   │   Cache   │
         │   │ Database│        │ - GPT-4      │   │  (Redis)  │
         │   │         │        │ - Embeddings │   │  (futur)  │
         │   └─────────┘        │ - Functions  │   └───────────┘
         │                      └──────────────┘
         │
         └──────► Logs & Statistiques
                 - queries.log
                 - access.log
                 - errors.log
```

---

## 🗂️ Structure des Données

### Nouvelles Tables MySQL

#### 1. **users** - Authentification multi-niveaux
```sql
- id (PK)
- email (UNIQUE)
- password_hash (bcrypt)
- role (admin|agent|guest)
- commune (nullable)
- created_at
- last_login
- is_active
```

#### 2. **historique_conversation** - Mémoire contextuelle
```sql
- id (PK)
- user_id (FK)
- session_id (UUID)
- question
- reponse
- sources_utilisees (JSON)
- cout_tokens
- duree_ms
- created_at
```

#### 3. **documents_generes** - Traçabilité documents
```sql
- id (PK)
- user_id (FK)
- type (deliberation|arrete|courrier)
- titre
- contenu (LONGTEXT)
- metadata (JSON)
- created_at
```

#### 4. **statistiques** - Pilotage performance
```sql
- id (PK)
- date
- nb_requetes
- nb_documents_generes
- cout_openai_euro
- temps_reponse_moyen_ms
- top_themes (JSON)
```

#### 5. **legal_facts** - Base juridique interne
```sql
- id (PK)
- categorie (quorum|fctva|ifse|cgct|m57)
- titre
- reference_legale
- contenu
- priority (1-10)
- source_url
- date_maj
```

#### 6. **fichiers_uploades** - Extension de documents
```sql
+ file_hash (SHA256) - éviter doublons
+ indexed_chunks (nombre)
+ priority_score (1-10) - pondération
```

---

## 🧠 Intelligence & Précision Juridique

### Flux de traitement d'une question

```
1. PRÉ-ANALYSE CONTEXTUELLE
   ├─ Détection de mots-clés juridiques
   ├─ Classification du sujet (quorum, FCTVA, RH, etc.)
   └─ Extraction des entités (dates, montants, articles)

2. CONSULTATION FORCÉE DES SOURCES
   ├─ Recherche legal_facts (base interne)
   ├─ Recherche Web OBLIGATOIRE (Légifrance prioritaire)
   ├─ Recherche RAG avec pondération
   └─ Synthèse des extraits avant envoi à GPT

3. APPEL OPENAI AVEC CONTEXTE ENRICHI
   ├─ System prompt renforcé (Légifrance > tout)
   ├─ Contexte juridique + sources + historique
   └─ Function calling si besoin

4. POST-TRAITEMENT
   ├─ Vérification cohérence juridique
   ├─ Ajout suggestions proactives
   └─ Sauvegarde historique + statistiques
```

### Pondération des sources RAG

```
Priority Score:
- Articles CGCT, décrets, lois : 10
- Instructions officielles (M57, DGFiP) : 9
- Circulaires, arrêtés : 8
- Délibérations locales : 7
- Notes internes : 5
- Documents généraux : 3
```

---

## 🔐 Sécurité & Authentification

### Système d'authentification

**Sessions PHP sécurisées** (Phase 1)
- Stockage en base MySQL
- Token CSRF pour chaque requête
- Expiration configurable

**JWT** (Phase 2 - optionnel)
- Token access (15 min)
- Token refresh (7 jours)
- Signature HMAC-SHA256

### Protection des endpoints

```php
api/
├── auth/
│   ├── login.php          [Public]
│   ├── logout.php         [Auth]
│   └── register.php       [Admin only]
├── proxy.php              [Auth - Agent+]
├── doc_manager.php        [Auth - Agent+]
├── stats.php              [Auth - Admin only]
└── users.php              [Auth - Admin only]
```

### RGPD

- Consentement cookies
- Export données utilisateur
- Suppression historique
- Anonymisation logs après 12 mois
- Journalisation des accès sensibles

---

## 🎨 Frontend - Structure

### Pages principales

```
/
├── index.html              → Redirection vers login ou dashboard
├── login.html              → Authentification
├── dashboard.html          → Page principale (après login)
├── documents.html          → Gestion documentaire
├── historique.html         → Historique conversationnel
├── statistiques.html       → Tableau de bord stats (admin)
├── admin.html              → Administration système (admin)
└── config.html             → Configuration UI (admin)
```

### Composants UI

```javascript
components/
├── sidebar.js              → Barre latérale rétractable
├── theme-switcher.js       → Mode sombre/clair
├── chat-interface.js       → Interface conversationnelle
├── stats-charts.js         → Graphiques (Chart.js)
└── file-uploader.js        → Upload avec preview
```

### Personnalisation graphique

**Fichier config/ui.json**
```json
{
  "theme": {
    "primary": "#667eea",
    "secondary": "#764ba2",
    "accent": "#4ec9b0",
    "background": "#ffffff",
    "text": "#333333"
  },
  "logo": "/assets/logo-commune.png",
  "nom_application": "NOIA - Commune de ...",
  "mode_defaut": "clair"
}
```

---

## 📊 Module Statistiques

### Métriques collectées

```javascript
{
  "quotidien": {
    "date": "2024-03-15",
    "requetes": 45,
    "documents_generes": 8,
    "cout_openai": 1.23,
    "temps_moyen_ms": 2340,
    "top_themes": ["FCTVA", "Délibération", "RH"]
  },
  "mensuel": {
    "mois": "2024-03",
    "total_requetes": 892,
    "total_documents": 156,
    "cout_total": 28.45
  }
}
```

### Graphiques Dashboard

- Évolution requêtes (7 derniers jours)
- Répartition par thème (camembert)
- Coûts OpenAI (barre)
- Temps de réponse moyen (ligne)

---

## 🔄 Mémoire Conversationnelle

### Contexte de session

Stockage des 5 dernières interactions pour continuité :

```javascript
session_context = {
  "session_id": "uuid-xxx",
  "user_id": 42,
  "historique": [
    {"q": "Quel est le quorum ?", "r": "...", "timestamp": "..."},
    {"q": "Et si absent ?", "r": "...", "timestamp": "..."}
  ],
  "entites_extraites": {
    "commune": "Saint-Martin",
    "contexte": "conseil_municipal"
  }
}
```

### Suggestions proactives

Après chaque réponse, proposer :
- "Générer une délibération" (si sujet identifié)
- "Créer un modèle de courrier"
- "Consulter les textes officiels"
- "Voir l'historique lié"

---

## 🚀 Performance & Cache

### Stratégie de cache

**Niveau 1 - Cache applicatif (PHP)**
- Legal facts en mémoire
- Résultats web (1 heure)
- Embeddings textes officiels

**Niveau 2 - Redis (futur)**
- Sessions utilisateurs
- Résultats RAG fréquents
- Statistiques temps réel

### Optimisations base de données

- Index sur tables historique, embeddings
- Partitionnement des logs par mois
- Archive automatique > 12 mois

---

## 📦 Déploiement OVH

### Prérequis serveur

```
PHP >= 7.4 (idéalement 8.1+)
MySQL >= 5.7
Extensions PHP :
  - pdo_mysql
  - curl
  - json
  - mbstring
  - openssl
  - zip (pour DOCX)
```

### Configuration Apache

```apache
<VirtualHost *:443>
    ServerName noia.commune.fr
    DocumentRoot /www/noia

    # Force HTTPS
    SSLEngine on
    SSLCertificateFile /path/cert.pem
    SSLCertificateKeyFile /path/key.pem

    # Headers sécurité
    Header always set X-Frame-Options "DENY"
    Header always set X-Content-Type-Options "nosniff"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</VirtualHost>
```

---

## 📝 Convention de code

### PHP
- PSR-12 pour le style
- Typage strict (`declare(strict_types=1)`)
- Documentation PHPDoc
- Gestion d'erreurs systématique (try/catch)

### JavaScript
- ES6+ (modules)
- Async/await pour requêtes
- Documentation JSDoc
- Prettier pour formatage

### SQL
- Nommage snake_case
- Clés étrangères avec ON DELETE/UPDATE
- Index sur colonnes fréquemment recherchées

---

## 🧪 Tests

### Tests unitaires PHP (PHPUnit)
- Fonctions de pré-analyse
- Pondération RAG
- Extraction entités

### Tests d'intégration
- Flux complet question → réponse
- Authentification
- Upload documents

### Tests frontend (Jest)
- Composants UI
- Gestion du state
- Requêtes API

---

## 📅 Calendrier de mise en œuvre

**Phase 1 - Fondations (Semaine 1-2)**
- Schéma base de données
- Système authentification
- Base juridique (legal_facts)

**Phase 2 - Intelligence (Semaine 3-4)**
- Pré-analyse contextuelle
- Pondération RAG
- Consultation forcée sources

**Phase 3 - Backend APIs (Semaine 5-6)**
- API statistiques
- API historique
- Journalisation avancée

**Phase 4 - Frontend (Semaine 7-9)**
- Interface responsive
- Dashboard
- Mode sombre

**Phase 5 - Sécurité & RGPD (Semaine 10)**
- Renforcement sécurité
- Conformité RGPD
- Tests finaux

---

**Version :** 4.0.0
**Date :** Octobre 2024
**Statut :** Architecture validée
