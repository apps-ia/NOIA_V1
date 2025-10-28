# 🏗️ NOIA v5.0 - Architecture Simplifiée avec Assistants API v2

## 🎯 Cahier des Charges Final

### ✅ Exigences Fonctionnelles

| # | Exigence | Faisabilité | Complexité |
|---|----------|-------------|------------|
| 1 | **LLM : GPT-5** (quand disponible) | ✅ Oui | 🟢 Aucune (paramètre) |
| 2 | **Conversations sauvegardées 30 jours** | ✅ Oui | 🟢 Faible (natif) |
| 3 | **Dossiers conversations** (comme ChatGPT) | ✅ Oui | 🟢 Faible (metadata) |
| 4 | **Pas de statistiques** | ✅ Oui | 🟢 Aucune (retirer code) |
| 5 | **Pas de questions pré-posées** | ✅ Oui | 🟢 Aucune (retirer UI) |
| 6 | **Upload docs par collectivité** | ✅ Oui | 🟡 Moyenne (Vector Stores) |
| 7 | **Hiérarchie : Général → Collectivité → Web** | ✅ Oui | 🟡 Moyenne (priorité sources) |
| 8 | **Upload modèles d'actes** | ✅ Oui | 🟢 Faible (même que docs) |

**Verdict : 🟢 FAISABLE et PLUS SIMPLE que solution actuelle**

---

## 🏛️ Architecture : Multi-Collectivités avec Vector Stores

### **Schéma Conceptuel**

```
┌─────────────────────────────────────────────────────────────┐
│                     NOIA Frontend                            │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐         │
│  │ Mes Convos  │  │ Upload Docs │  │ Mes Modèles │         │
│  │ (Dossiers)  │  │             │  │  d'Actes    │         │
│  └─────────────┘  └─────────────┘  └─────────────┘         │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              API Backend (assistant.php)                     │
│  - Authentification (commune actuelle)                       │
│  - Gestion threads (conversations)                           │
│  - Gestion Vector Stores (par collectivité)                 │
│  - Upload documents                                          │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│             OpenAI Assistants API v2                         │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  Assistant "NOIA_Collectivités"                        │ │
│  │  - Model: gpt-5 (ou gpt-4o en attendant)              │ │
│  │  - Instructions: Prompt optimisé                       │ │
│  │  - Tools: [File Search, Web Search]                    │ │
│  └────────────────────────────────────────────────────────┘ │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  Vector Store #1 : BASE GÉNÉRALE (Priority 1)         │ │
│  │  ├── legal_facts_quorum.pdf                           │ │
│  │  ├── legal_facts_fctva.pdf                            │ │
│  │  ├── grilles_rh_2024.pdf                              │ │
│  │  ├── guide_m57.pdf                                     │ │
│  │  └── marches_publics_2024.pdf                         │ │
│  └────────────────────────────────────────────────────────┘ │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  Vector Store #2 : COMMUNE_TOULOUSE (Priority 2)      │ │
│  │  ├── reglement_interieur_toulouse.pdf                 │ │
│  │  ├── deliberations_2024.pdf                           │ │
│  │  ├── modele_arrete_maire.docx                         │ │
│  │  └── organigramme_services.pdf                        │ │
│  └────────────────────────────────────────────────────────┘ │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  Vector Store #3 : COMMUNE_PARIS (Priority 2)         │ │
│  │  ├── charte_paris.pdf                                  │ │
│  │  ├── modele_deliberation_paris.docx                   │ │
│  │  └── procedures_internes.pdf                          │ │
│  └────────────────────────────────────────────────────────┘ │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  Web Search (Priority 3)                               │ │
│  │  - legifrance.gouv.fr                                  │ │
│  │  - collectivites-locales.gouv.fr                       │ │
│  │  - emploi-collectivites.fr                             │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

---

## 🗂️ Système de Vector Stores : 3 Niveaux

### **Niveau 1 : Base Documentaire GÉNÉRALE (Partagée)**

**Contenu :**
- Legal facts (quorum, FCTVA, IFSE, M57, marchés publics...)
- Grilles indiciaires nationales
- Législation générale (CGCT, décrets...)
- Guides nationaux

**Gestion :**
- ✅ Créé par l'administrateur NOIA
- ✅ Accessible à TOUTES les collectivités
- ✅ Mis à jour centralement
- ✅ Priorité 1 (consulté en premier)

**Code :**
```php
// Créer Vector Store Général (une seule fois)
$vector_store_general = createVectorStore([
    'name' => 'NOIA_Base_Generale',
    'file_ids' => [
        uploadFile('legal_facts_quorum.pdf'),
        uploadFile('legal_facts_fctva.pdf'),
        uploadFile('grilles_rh_2024.pdf'),
        uploadFile('guide_m57.pdf'),
        uploadFile('marches_publics_2024.pdf')
    ]
]);

// Attacher à l'assistant
attachVectorStoreToAssistant($assistant_id, $vector_store_general['id']);
```

---

### **Niveau 2 : Base Documentaire PAR COLLECTIVITÉ**

**Contenu :**
- Documents spécifiques à la commune
- Règlements intérieurs locaux
- Modèles d'actes personnalisés
- Délibérations types
- Organigrammes
- Procédures internes

**Gestion :**
- ✅ Créé automatiquement à la création d'une commune
- ✅ Agents de la commune peuvent uploader
- ✅ Visible UNIQUEMENT par cette commune
- ✅ Priorité 2 (consulté en second)

**Code :**
```php
// À la création d'une commune
function createCommune($nom_commune, $admin_user_id) {
    // 1. Créer dans MySQL
    $commune_id = insertCommune($nom_commune);

    // 2. Créer Vector Store dédié
    $vector_store = createVectorStore([
        'name' => "NOIA_Commune_" . $commune_id,
        'metadata' => [
            'commune_id' => $commune_id,
            'nom' => $nom_commune
        ]
    ]);

    // 3. Sauvegarder l'ID du Vector Store
    updateCommune($commune_id, [
        'vector_store_id' => $vector_store['id']
    ]);

    return $commune_id;
}
```

---

### **Niveau 3 : Web Search (Sites Officiels)**

**Sources :**
- legifrance.gouv.fr
- collectivites-locales.gouv.fr
- emploi-collectivites.fr
- service-public.fr
- economie.gouv.fr

**Gestion :**
- ✅ Automatique (Web Search tool)
- ✅ Consulté en dernier recours
- ✅ Priorité 3

**Configuration dans prompt :**
```
Si informations non trouvées dans les documents :
1. Consulter d'ABORD la base générale
2. Puis la base de la collectivité
3. En dernier recours, chercher sur sites officiels
```

---

## 📁 Système de Conversations avec Dossiers

### **Architecture Threads + Metadata**

**Table MySQL : `conversations`**

```sql
CREATE TABLE conversations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  commune_id INT NOT NULL,
  thread_id VARCHAR(255) NOT NULL,  -- ID thread OpenAI
  title VARCHAR(255) NOT NULL,      -- Titre auto-généré ou manuel
  folder VARCHAR(100) DEFAULT 'Général',  -- Dossier
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  last_message_preview TEXT,        -- Aperçu dernier message
  is_archived BOOLEAN DEFAULT FALSE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (commune_id) REFERENCES communes(id) ON DELETE CASCADE,
  INDEX idx_user_folder (user_id, folder),
  INDEX idx_thread (thread_id)
);
```

---

### **Dossiers Prédéfinis (Modifiables)**

**Catégories par défaut :**
- 📂 **Général**
- 📂 **RH & Personnel**
- 📂 **Finances & Budget**
- 📂 **Marchés Publics**
- 📂 **Urbanisme**
- 📂 **Délibérations**
- 📂 **Juridique**

**Fonctionnalités :**
- ✅ Déplacer conversation entre dossiers
- ✅ Créer nouveaux dossiers personnalisés
- ✅ Renommer conversation
- ✅ Archiver (mais conservé 30 jours)
- ✅ Recherche dans conversations

---

### **Interface Frontend : Sidebar ChatGPT-like**

**HTML Structure :**

```html
<div class="noia-layout">
    <!-- Sidebar Gauche : Conversations -->
    <aside class="conversations-sidebar">
        <div class="sidebar-header">
            <h2>NOIA Collectivités</h2>
            <button id="new-conversation">+ Nouvelle conversation</button>
        </div>

        <!-- Dossiers -->
        <div class="folders">
            <div class="folder" data-folder="Général">
                📂 Général (5)
                <div class="conversations-list">
                    <div class="conversation" data-thread-id="thread_abc123">
                        <span class="title">Quorum reconvocation</span>
                        <span class="date">Il y a 2h</span>
                    </div>
                    <div class="conversation" data-thread-id="thread_def456">
                        <span class="title">Grille adjoint admin</span>
                        <span class="date">Hier</span>
                    </div>
                </div>
            </div>

            <div class="folder" data-folder="RH & Personnel">
                📂 RH & Personnel (3)
                <div class="conversations-list">
                    <div class="conversation" data-thread-id="thread_ghi789">
                        <span class="title">Calcul IFSE agent</span>
                        <span class="date">Il y a 3 jours</span>
                    </div>
                </div>
            </div>

            <div class="folder" data-folder="Finances & Budget">
                📂 Finances & Budget (2)
            </div>
        </div>

        <!-- Paramètres -->
        <div class="sidebar-footer">
            <button id="upload-docs">📤 Mes documents</button>
            <button id="settings">⚙️ Paramètres</button>
            <button id="logout">🚪 Déconnexion</button>
        </div>
    </aside>

    <!-- Zone Principale : Chat -->
    <main class="chat-area">
        <div class="chat-header">
            <h3 id="conversation-title">Nouvelle conversation</h3>
            <div class="actions">
                <select id="move-to-folder">
                    <option>Déplacer vers...</option>
                    <option value="Général">Général</option>
                    <option value="RH & Personnel">RH & Personnel</option>
                    <option value="Finances & Budget">Finances & Budget</option>
                </select>
                <button id="rename-conversation">✏️</button>
                <button id="archive-conversation">🗄️</button>
            </div>
        </div>

        <div class="messages-container" id="messages">
            <!-- Messages ici -->
        </div>

        <div class="input-area">
            <textarea id="question" placeholder="Posez votre question..."></textarea>
            <button id="upload-file">📎</button>
            <button id="send">Envoyer</button>
        </div>
    </main>

    <!-- Panel Droite : Documents (Optionnel) -->
    <aside class="documents-panel" id="docs-panel" style="display:none">
        <h3>Mes Documents</h3>
        <div class="docs-list">
            <!-- Documents uploadés -->
        </div>
    </aside>
</div>
```

---

### **Code Backend : Gestion Conversations**

**1. Créer Nouvelle Conversation**

```php
<?php
// assistant.php
function createNewConversation($user_id, $commune_id, $question) {
    // 1. Créer thread OpenAI
    $thread = createThread();

    // 2. Générer titre (premier message ou auto)
    $title = generateTitle($question); // "Quorum reconvocation"

    // 3. Sauvegarder en BDD
    $conversation_id = insertConversation([
        'user_id' => $user_id,
        'commune_id' => $commune_id,
        'thread_id' => $thread['id'],
        'title' => $title,
        'folder' => 'Général',
        'last_message_preview' => substr($question, 0, 100)
    ]);

    return [
        'conversation_id' => $conversation_id,
        'thread_id' => $thread['id']
    ];
}
?>
```

**2. Continuer Conversation Existante**

```php
<?php
function continueConversation($conversation_id, $question) {
    // 1. Récupérer thread_id
    $conversation = getConversation($conversation_id);
    $thread_id = $conversation['thread_id'];

    // 2. Ajouter message au thread existant
    addMessageToThread($thread_id, $question);

    // 3. Lancer assistant
    $response = runAssistant($thread_id);

    // 4. Mettre à jour BDD
    updateConversation($conversation_id, [
        'updated_at' => now(),
        'last_message_preview' => substr($question, 0, 100)
    ]);

    return $response;
}
?>
```

**3. Déplacer vers Dossier**

```php
<?php
function moveToFolder($conversation_id, $new_folder) {
    updateConversation($conversation_id, [
        'folder' => $new_folder
    ]);

    return ['success' => true];
}
?>
```

**4. Archiver (Soft Delete)**

```php
<?php
function archiveConversation($conversation_id) {
    updateConversation($conversation_id, [
        'is_archived' => true
    ]);

    // Suppression définitive après 30 jours (cron job)
    // deleteOldArchivedConversations(30);

    return ['success' => true];
}
?>
```

---

## 📤 Système d'Upload Documents par Collectivité

### **Interface Upload**

**Modal Upload :**

```html
<div id="upload-modal" class="modal">
    <div class="modal-content">
        <h2>📤 Uploader un document</h2>

        <form id="upload-form">
            <label>Type de document :</label>
            <select name="doc_type">
                <option value="legal">Legal fact / Règlement</option>
                <option value="modele">Modèle d'acte</option>
                <option value="deliberation">Délibération type</option>
                <option value="procedure">Procédure interne</option>
                <option value="autre">Autre</option>
            </select>

            <label>Titre :</label>
            <input type="text" name="title" placeholder="Ex: Modèle arrêté municipal">

            <label>Fichier :</label>
            <input type="file" name="file" accept=".pdf,.docx,.txt">

            <label>Description (optionnelle) :</label>
            <textarea name="description" rows="3"></textarea>

            <button type="submit">Uploader</button>
        </form>

        <div class="uploaded-files">
            <h3>Documents uploadés (5)</h3>
            <ul>
                <li>
                    📄 Modèle arrêté municipal.docx
                    <span class="date">15/01/2024</span>
                    <button class="delete">🗑️</button>
                </li>
                <li>
                    📄 Règlement intérieur conseil.pdf
                    <span class="date">10/01/2024</span>
                    <button class="delete">🗑️</button>
                </li>
            </ul>
        </div>
    </div>
</div>
```

---

### **Backend Upload**

**Code PHP :**

```php
<?php
// upload_document.php
function uploadDocumentForCommune($commune_id, $file, $metadata) {
    // 1. Upload vers OpenAI
    $openai_file = uploadFileToOpenAI($file['tmp_name'], $file['name']);

    // 2. Récupérer Vector Store de la commune
    $commune = getCommune($commune_id);
    $vector_store_id = $commune['vector_store_id'];

    // 3. Ajouter fichier au Vector Store
    addFileToVectorStore($vector_store_id, $openai_file['id']);

    // 4. Sauvegarder métadonnées en BDD
    insertDocument([
        'commune_id' => $commune_id,
        'openai_file_id' => $openai_file['id'],
        'filename' => $file['name'],
        'type' => $metadata['doc_type'],
        'title' => $metadata['title'],
        'description' => $metadata['description'],
        'uploaded_by' => $metadata['user_id']
    ]);

    return ['success' => true, 'file_id' => $openai_file['id']];
}

// Fonction Helper OpenAI
function uploadFileToOpenAI($file_path, $filename) {
    $ch = curl_init('https://api.openai.com/v1/files');

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'file' => new CURLFile($file_path, mime_content_type($file_path), $filename),
        'purpose' => 'assistants'
    ]);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

function addFileToVectorStore($vector_store_id, $file_id) {
    $ch = curl_init("https://api.openai.com/v1/vector_stores/{$vector_store_id}/files");

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'file_id' => $file_id
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY,
        'OpenAI-Beta: assistants=v2'
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}
?>
```

---

## 🔍 Hiérarchie de Consultation : 3 Niveaux

### **Configuration dans le Prompt Assistant**

```markdown
# NOIA_Collectivités - Instructions

Tu es NOIA, assistant spécialisé en administration territoriale française.

## ORDRE DE CONSULTATION DES SOURCES (PRIORITÉ ABSOLUE)

Quand tu réponds, tu DOIS suivre cet ordre strict :

### 1️⃣ PRIORITÉ 1 : Base Documentaire GÉNÉRALE
- Consulter d'ABORD les documents de la base générale (legal facts nationaux)
- Ce sont les règles officielles validées (lois, décrets, CGCT)
- Si réponse trouvée → l'utiliser et citer la source

### 2️⃣ PRIORITÉ 2 : Base Documentaire COLLECTIVITÉ
- Si info non trouvée dans base générale, consulter documents de la collectivité
- Règlements internes, modèles d'actes, procédures spécifiques
- Si réponse trouvée → l'utiliser et mentionner "selon vos documents internes"

### 3️⃣ PRIORITÉ 3 : Recherche Web (Sites Officiels)
- EN DERNIER RECOURS, si aucune info dans les 2 bases
- Chercher sur : legifrance.gouv.fr, collectivites-locales.gouv.fr, emploi-collectivites.fr
- Toujours citer la source exacte (URL)

## SYNTHÈSE DES SOURCES

À la fin de chaque réponse, indique :
📚 Sources consultées :
- [x] Base générale : [Nom document]
- [ ] Documents collectivité : Aucun pertinent
- [ ] Web : Non nécessaire

## FORMAT RÉPONSE
1️⃣ [Section 1]
2️⃣ [Section 2]
3️⃣ [Section 3]
4️⃣ [Section 4]

Longueur : 1000-1500 mots pour questions complexes
```

---

### **Implémentation Technique**

**Code Assistant :**

```php
<?php
function askAssistant($thread_id, $question, $user_commune_id) {
    // 1. Récupérer Vector Stores
    $vector_store_general = 'vs_general_xxx';
    $vector_store_commune = getCommuneVectorStore($user_commune_id);

    // 2. Ajouter message
    addMessageToThread($thread_id, $question);

    // 3. Lancer Run avec Vector Stores ordonnés
    $run = createRun([
        'thread_id' => $thread_id,
        'assistant_id' => ASSISTANT_ID,
        'tools' => [
            [
                'type' => 'file_search',
                'file_search' => [
                    'vector_store_ids' => [
                        $vector_store_general,    // Priorité 1
                        $vector_store_commune     // Priorité 2
                    ]
                ]
            ]
        ]
    ]);

    // 4. Attendre réponse
    $response = waitForRunCompletion($thread_id, $run['id']);

    return $response;
}
?>
```

---

## 🚀 Migration vers GPT-5

### **Préparation Architecture Compatible GPT-5**

**Actuellement (GPT-4o) :**
```php
$assistant = createAssistant([
    'name' => 'NOIA_Collectivités',
    'model' => 'gpt-4o',  // ← Modèle actuel
    'instructions' => $prompt
]);
```

**Après sortie GPT-5 (1 ligne à changer) :**
```php
$assistant = updateAssistant(ASSISTANT_ID, [
    'model' => 'gpt-5'  // ← Mise à jour instantanée
]);
```

**C'est TOUT !** Aucun autre changement nécessaire.

---

### **GPT-5 : Prévisions et Préparation**

| Aspect | GPT-4o | GPT-5 (prévu) |
|--------|--------|---------------|
| **Date sortie** | Disponible | Q2-Q3 2025 (estimation) |
| **Context window** | 128K tokens | 200K tokens (rumeur) |
| **Coût** | $0.0025/1K input | +30-50% estimé |
| **Qualité** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐+ |
| **Raisonnement** | Très bon | Excellent (PhD-level) |
| **Latence** | 2-5s | 3-7s (probablement) |

**💡 Recommandation :**
- Lancer NOIA avec **GPT-4o** maintenant
- Migrer vers **GPT-5** en 1 clic quand disponible
- Coût additionnel : +$20-40/mois environ

---

## 📊 Complexité de Développement

### **Comparaison : Solution Actuelle vs Assistants API v2**

| Composant | Solution Actuelle | Assistants API v2 | Gain |
|-----------|------------------|-------------------|------|
| **Gestion legal facts** | ❌ SQL manuel | ✅ Vector Store auto | -80% code |
| **Multi-collectivités** | ❌ À coder from scratch | ✅ Vector Stores natifs | -90% code |
| **Upload documents** | ❌ Storage + embeddings DIY | ✅ API OpenAI | -95% code |
| **Conversations/dossiers** | ❌ À coder | ✅ Threads + metadata | -70% code |
| **Hiérarchie sources** | ❌ Logique complexe | ✅ Prompt + VS order | -85% code |
| **Suppression 30j** | ❌ Cron jobs | ✅ Cron simple (DELETE) | -50% code |
| **Statistiques** | ❌ Retirer code existant | ✅ Pas de code | 0% |
| **Questions pré-posées** | ❌ Retirer UI | ✅ Pas d'UI | 0% |

**Réduction totale de code : ~80-85%**

**Lignes de code :**
- Solution actuelle : ~2500 lignes
- Assistants API v2 : ~400 lignes

---

### **Temps de Développement Estimé**

| Tâche | Temps |
|-------|-------|
| **Setup Assistants API** | 2h |
| **Système Vector Stores multi-collectivités** | 4h |
| **Interface conversations + dossiers** | 6h |
| **Upload documents par collectivité** | 3h |
| **Hiérarchie sources (prompt + config)** | 2h |
| **Tests et debugging** | 3h |
| **TOTAL** | **~20h** |

**VS Solution actuelle : ~80h de développement**

**Gain : -75% de temps de dev**

---

## ✅ Réponse à Votre Question

### **"Tout cela apporte-t-il de la complexité ?"**

# ❌ NON, C'EST PLUS SIMPLE !

**Voici pourquoi :**

### **1. GPT-5 : Complexité NULLE**
- ✅ 1 paramètre à changer (`model: 'gpt-5'`)
- ✅ Architecture déjà compatible
- ✅ Migration en 30 secondes

### **2. Conversations 30j + Dossiers : Complexité FAIBLE**
- ✅ Threads OpenAI natifs (sauvegarde auto)
- ✅ Dossiers = simple metadata en BDD
- ✅ UI inspirée ChatGPT (patterns connus)

### **3. Pas de Statistiques : Complexité NÉGATIVE**
- ✅ Retirer du code = simplification
- ✅ Moins de tables BDD
- ✅ Moins d'UI

### **4. Pas de Questions Pré-posées : Complexité NÉGATIVE**
- ✅ Retirer UI = simplification

### **5. Upload Documents par Collectivité : Complexité MOYENNE**
- ✅ Vector Stores OpenAI (gestion auto)
- ✅ API upload simple (CURLFile PHP)
- ⚠️ Nécessite gestion permissions (qui peut uploader)

### **6. Hiérarchie Sources (Général → Collectivité → Web) : Complexité MOYENNE**
- ✅ Ordre Vector Stores dans API call
- ✅ Instructions dans prompt
- ⚠️ Nécessite tests pour vérifier priorité

### **7. Upload Modèles d'Actes : Complexité NULLE**
- ✅ Identique aux autres documents
- ✅ Juste un type différent en BDD

---

## 🎯 Score de Complexité Globale

| Composant | Complexité | Justification |
|-----------|------------|---------------|
| Architecture générale | 🟢 FAIBLE | Assistants API v2 natifs |
| Multi-collectivités | 🟡 MOYENNE | Vector Stores (facile mais nouveau) |
| Conversations + dossiers | 🟢 FAIBLE | Threads + metadata simple |
| Upload documents | 🟢 FAIBLE | API OpenAI gère tout |
| Hiérarchie sources | 🟡 MOYENNE | Config + tests |
| Migration GPT-5 | 🟢 AUCUNE | 1 paramètre |

**Score global : 🟢 FAIBLE (3/10)**

**Complexité relative :**
- Solution actuelle : 8/10
- Assistants API v2 : 3/10

**Gain : -62% de complexité**

---

## 🚀 Prochaines Étapes

**Voulez-vous que je crée cette architecture ?**

**Je peux vous livrer :**

1. ✅ **assistant.php** - Backend complet (~400 lignes)
2. ✅ **Frontend ChatGPT-like** - UI conversations + dossiers
3. ✅ **Système Vector Stores** - Multi-collectivités
4. ✅ **Upload documents** - Interface + backend
5. ✅ **Script setup** - Création assistant + base générale
6. ✅ **10 documents PDF** - Base générale prête
7. ✅ **Prompt optimisé** - Hiérarchie sources
8. ✅ **Guide migration** - Étape par étape
9. ✅ **Préparation GPT-5** - Compatible dès maintenant

**Temps d'implémentation pour vous : ~1-2h** (upload + config)

**Dites-moi et je commence !** 🎯
