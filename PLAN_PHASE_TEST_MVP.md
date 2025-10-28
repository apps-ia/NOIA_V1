# 🚀 NOIA - Phase Test MVP (Budget Réduit)

## 🎯 Philosophie : Minimum Viable Product

**Objectif :** Version **fonctionnelle** et **stable** pour tester avec **vraies collectivités**

**Budget cible :** ✅ **$0-500** (développement interne ou contribution bénévole)

**Délai :** ✅ **1-2 semaines** maximum

---

## ❌ Ce Qu'on RETIRE (pour l'instant)

**Fonctionnalités avancées reportées en Phase 2 :**

| Fonctionnalité | Pourquoi reportée | Économie |
|----------------|-------------------|----------|
| ❌ Génération DOCX automatique | Complexe (20h dev) | -$2,000 |
| ❌ Assistant proactif | Complexe (30h dev) | -$3,000 |
| ❌ Vérification juridique | Complexe (40h dev) | -$4,000 |
| ❌ Collaboration multi-agents | Complexe (60h dev) | -$6,000 |
| ❌ Calendrier/rappels | Complexe (25h dev) | -$2,500 |
| ❌ Mode formation | Non essentiel MVP | -$3,000 |
| ❌ Intégrations API externes | Trop spécifique | -$8,000 |

**Total économisé : $28,500** ✅

---

## ✅ Ce Qu'on GARDE (Essentiel MVP)

### **Fonctionnalités CORE (Phase Test)**

| # | Fonctionnalité | Justification | Complexité |
|---|----------------|---------------|------------|
| 1 | **Questions/Réponses GPT-4o** | CORE métier | 🟢 Acquis |
| 2 | **Authentification simple** | Sécurité de base | 🟢 Acquis |
| 3 | **Base documentaire générale** | Legal facts partagés | 🟢 Facile |
| 4 | **Conversations sauvegardées** | UX de base | 🟢 Simple |
| 5 | **Interface chat simple** | Minimum UX | 🟢 Simple |

**Total dev additionnel : ~5-10h** = **$0-500**

---

## 🏗️ Architecture MVP Simplifiée

### **Stack Technique Minimaliste**

```
┌─────────────────────────────────────────────┐
│         Frontend Simple (HTML/JS)            │
│  - Chat basique (comme ChatGPT)             │
│  - Pas de sidebar complexe                  │
│  - Pas de dossiers (juste historique)      │
└────────────────┬────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────┐
│      Backend PHP Minimal (assistant.php)    │
│  - Authentification (existante)             │
│  - Appels Assistants API                    │
│  - Sauvegarde threads simple                │
└────────────────┬────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────┐
│          OpenAI Assistants API              │
│  ┌─────────────────────────────────────┐   │
│  │  Assistant NOIA                     │   │
│  │  - GPT-4o (pas GPT-5 pour MVP)     │   │
│  │  - Prompt optimisé                  │   │
│  │  - File Search (RAG simple)         │   │
│  └─────────────────────────────────────┘   │
│  ┌─────────────────────────────────────┐   │
│  │  Vector Store UNIQUE                │   │
│  │  (Pas multi-collectivités MVP)      │   │
│  │  - 10 PDF legal facts               │   │
│  │  - Grilles RH                       │   │
│  │  - Guides M57                       │   │
│  └─────────────────────────────────────┘   │
└─────────────────────────────────────────────┘
```

**Simplifications vs Architecture v5.0 :**
- ❌ Pas de multi-collectivités (1 seul Vector Store)
- ❌ Pas de dossiers (conversations en liste simple)
- ❌ Pas d'upload docs par utilisateur (uniquement admin)
- ❌ Pas de sidebar ChatGPT-like (juste historique)
- ❌ Pas de statistiques

---

## 💾 Base de Données Minimale

### **Tables Nécessaires (3 seulement)**

**1. Table `users` (déjà existante)**
```sql
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100),
  password_hash VARCHAR(255),
  email VARCHAR(255),
  role ENUM('admin', 'user'),
  created_at TIMESTAMP
);
```

**2. Table `conversations` (simplifiée)**
```sql
CREATE TABLE conversations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  thread_id VARCHAR(255) NOT NULL,     -- ID thread OpenAI
  title VARCHAR(255) DEFAULT 'Nouvelle conversation',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user (user_id),
  INDEX idx_thread (thread_id)
);
```

**3. Table `legal_facts` (optionnelle - peut être remplacée par PDFs)**
```sql
-- OPTION : Garder pour référence admin
-- Mais pas obligatoire si tout est en PDF dans Vector Store
```

**Total : 2-3 tables** (vs 10+ dans solution complète)

---

## 🎨 Interface Utilisateur Minimaliste

### **Design Ultra-Simple**

```html
┌────────────────────────────────────────────────────────┐
│  NOIA Collectivités                    👤 Jean DUPONT │
├────────────────────────────────────────────────────────┤
│                                                        │
│  💬 Zone de Chat                                       │
│                                                        │
│  ┌──────────────────────────────────────────────────┐ │
│  │ [Messages de conversation]                       │ │
│  │                                                  │ │
│  │ User: Quel est le quorum pour reconvocation ?   │ │
│  │                                                  │ │
│  │ NOIA: Lors d'une reconvocation, AUCUN quorum... │ │
│  │                                                  │ │
│  │ [Fin de la conversation]                         │ │
│  └──────────────────────────────────────────────────┘ │
│                                                        │
│  ┌──────────────────────────────────────────────────┐ │
│  │ Posez votre question...                          │ │
│  │                                      [📎] [Envoyer]│
│  └──────────────────────────────────────────────────┘ │
│                                                        │
│  📜 Historique (5 dernières conversations)            │
│  - Quorum reconvocation (Il y a 2h)                   │
│  - Grille adjoint administratif (Hier)                │
│  - FCTVA dépenses éligibles (Il y a 3 jours)          │
│                                                        │
│  [Nouvelle conversation]  [Déconnexion]               │
└────────────────────────────────────────────────────────┘
```

**Pas de sidebar complexe** = Gain de 30h dev ✅

---

## 📝 Fonctionnalités MVP Détaillées

### **1. Questions/Réponses (CORE)**

**Fonctionnement :**
```javascript
// Frontend simple
async function sendQuestion() {
    const question = document.getElementById('question').value;

    const response = await fetch('/api/assistant.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'ask',
            question: question
        })
    });

    const data = await response.json();
    displayMessage('noia', data.response);
}
```

**Backend minimal :**
```php
<?php
// assistant.php (version MVP)
require_once 'config/config.php';

// Authentification
session_start();
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Non authentifié']));
}

$user_id = $_SESSION['user_id'];
$question = $_POST['question'];

// Créer ou récupérer thread
if (!isset($_SESSION['current_thread_id'])) {
    $thread = createThread();
    $_SESSION['current_thread_id'] = $thread['id'];

    // Sauvegarder en BDD
    saveConversation($user_id, $thread['id']);
}

$thread_id = $_SESSION['current_thread_id'];

// Ajouter message
addMessageToThread($thread_id, $question);

// Lancer assistant
$run = runAssistant($thread_id, ASSISTANT_ID);

// Attendre réponse
$response = waitForResponse($thread_id, $run['id']);

// Retourner
echo json_encode(['success' => true, 'response' => $response]);
?>
```

**Total : ~100 lignes de code** ✅

---

### **2. Authentification Simple**

**Déjà existante dans NOIA actuel** → Garder tel quel

**Login basique :**
- Email/mot de passe
- Session PHP
- Pas de 2FA (pour MVP)
- Pas de rôles complexes (juste admin/user)

**Code : 0 ligne (déjà fait)** ✅

---

### **3. Base Documentaire Générale (10 PDF)**

**Documents à créer (1 fois) :**

| Document | Contenu | Pages |
|----------|---------|-------|
| `legal_facts_quorum.pdf` | Quorum 1ère convocation + reconvocation | 2 |
| `legal_facts_fctva.pdf` | FCTVA dépenses éligibles + taux | 3 |
| `legal_facts_marches_publics.pdf` | Seuils 2024 + procédures | 3 |
| `legal_facts_convocation.pdf` | Délais convocation CM (5j, 3j) | 2 |
| `legal_facts_budget.pdf` | Vote budget primitif + délais | 2 |
| `grilles_rh_2024.pdf` | Grilles indiciaires principales | 10 |
| `guide_m57.pdf` | Nomenclature M57 simplifiée | 5 |
| `cgct_extraits.pdf` | Articles CGCT fréquents | 5 |
| `deliberations_types.pdf` | Exemples délibérations | 8 |
| `procedures_courantes.pdf` | Procédures administratives | 5 |

**Total : ~45 pages** (1 après-midi de travail)

**Upload unique vers OpenAI :**
```php
<?php
// setup_vector_store.php (à lancer 1 seule fois)
$files = [
    'legal_facts_quorum.pdf',
    'legal_facts_fctva.pdf',
    // ... etc
];

$file_ids = [];
foreach ($files as $file) {
    $file_ids[] = uploadFileToOpenAI($file);
}

// Créer Vector Store
$vector_store = createVectorStore([
    'name' => 'NOIA_Base_MVP',
    'file_ids' => $file_ids
]);

// Attacher à l'assistant
attachVectorStoreToAssistant(ASSISTANT_ID, $vector_store['id']);

echo "Vector Store créé : " . $vector_store['id'];
?>
```

**Temps : 2h** (création docs + upload) ✅

---

### **4. Conversations Sauvegardées (Simple)**

**Liste basique des conversations :**

```html
<div class="history">
    <h3>Mes dernières conversations</h3>
    <ul>
        <?php foreach ($conversations as $conv): ?>
        <li onclick="loadConversation('<?= $conv['thread_id'] ?>')">
            <?= $conv['title'] ?>
            <span class="date"><?= $conv['created_at'] ?></span>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
```

**Pas de dossiers** (trop complexe pour MVP)
**Pas de recherche** (pas nécessaire avec 10-20 convos)
**Juste liste chronologique** ✅

**Code : 50 lignes** ✅

---

### **5. Interface Chat Simple**

**Librairie CSS simple :** Tailwind CSS (CDN, pas de compilation)

```html
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>NOIA MVP</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto max-w-4xl p-4">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold mb-4">NOIA Collectivités</h1>

            <div id="messages" class="h-96 overflow-y-auto mb-4 space-y-4">
                <!-- Messages here -->
            </div>

            <div class="flex gap-2">
                <input type="text" id="question"
                       class="flex-1 border rounded p-2"
                       placeholder="Posez votre question...">
                <button onclick="sendQuestion()"
                        class="bg-blue-500 text-white px-4 py-2 rounded">
                    Envoyer
                </button>
            </div>
        </div>
    </div>
</body>
</html>
```

**Total : 100 lignes HTML+JS** ✅

---

## 💰 Budget Phase Test MVP

### **Coûts de Développement**

| Tâche | Temps | Coût* |
|-------|-------|-------|
| **Backend assistant.php** | 3h | $0 (vous) |
| **Interface chat simple** | 2h | $0 (vous) |
| **Création 10 PDF** | 3h | $0 (vous) |
| **Setup Vector Store** | 1h | $0 (vous) |
| **Tests** | 1h | $0 (vous) |
| **TOTAL DEV** | **10h** | **$0** |

*Si développement interne ou contribution communautaire

**Alternative si prestataire externe :** $1,000 max (10h × $100/h)

---

### **Coûts d'Exploitation (Mensuel)**

**Scénario : 1 collectivité test, 50 questions/jour**

| Poste | Coût/mois |
|-------|-----------|
| **API OpenAI (GPT-4o)** | $24 |
| **Vector Store (50 MB)** | $0.15 |
| **Hébergement cPanel** | $0 (existant) |
| **TOTAL** | **~$25/mois** |

**Coût par question : $0.016** (moins de 2 centimes !) ✅

---

## 🚦 Plan de Déploiement Phase Test

### **Semaine 1 : Développement**

**Jour 1-2 : Backend**
- ✅ Adapter assistant.php (3h)
- ✅ Setup OpenAI Assistant (1h)

**Jour 3-4 : Documents + Vector Store**
- ✅ Créer 10 PDF legal facts (3h)
- ✅ Upload vers OpenAI (1h)

**Jour 5 : Frontend**
- ✅ Interface chat simple (2h)

**Jour 6-7 : Tests**
- ✅ Tests fonctionnels (1h)
- ✅ Buffer debug (1h)

---

### **Semaine 2 : Test Utilisateur**

**Collectivité Pilote (1 seule) :**
- 3-5 agents testeurs
- 2 semaines d'utilisation
- Feedback quotidien

**Métriques à mesurer :**
- ✅ Nombre de questions posées
- ✅ Satisfaction réponses (échelle 1-5)
- ✅ Temps de réponse moyen
- ✅ Bugs rencontrés
- ✅ Fonctionnalités manquantes critiques

---

## 📊 Critères de Succès Phase Test

**Pour passer en Phase 2 (déploiement élargi) :**

| Critère | Objectif | Mesure |
|---------|----------|--------|
| **Satisfaction** | ≥ 4/5 | Sondage utilisateurs |
| **Utilisation** | ≥ 30 questions/semaine | Analytics |
| **Qualité réponses** | ≥ 80% correctes | Revue manuelle |
| **Bugs critiques** | 0 | Tests |
| **Temps réponse** | < 10 secondes | Technique |

**Si 5/5 critères atteints → GO Phase 2** ✅

---

## 🔄 Stratégie de Migration

### **Option A : Démarrer from Scratch (Recommandé MVP)**

**Avantages :**
- ✅ Code propre et simple
- ✅ Pas de dette technique
- ✅ Architecture moderne (Assistants API)

**Inconvénients :**
- ⚠️ Repartir de zéro (10h dev)

---

### **Option B : Améliorer Solution Actuelle**

**Garder :**
- ✅ Authentification existante
- ✅ Interface actuelle (simplifier)
- ✅ Base de données users

**Remplacer :**
- ❌ proxy.php → assistant.php (Assistants API)
- ❌ pre_analysis.php → Supprimer (pas nécessaire MVP)
- ❌ legal_facts_manager.php → Remplacer par Vector Store

**Avantages :**
- ✅ Réutilise existant
- ✅ Pas de réapprentissage utilisateur

**Inconvénients :**
- ⚠️ Dette technique conservée
- ⚠️ Code complexe à maintenir

---

## 🎯 Ma Recommandation FINALE

### **Pour Phase Test : Option A (From Scratch)**

**Pourquoi ?**
1. **Simplicité maximale** (10h dev vs 20h migration)
2. **Coût zéro** (dev interne)
3. **Architecture propre** dès le départ
4. **Facile à itérer** après feedback
5. **Coût exploitation dérisoire** ($25/mois)

**Livrable Phase Test :**
- ✅ NOIA MVP fonctionnel
- ✅ 1 collectivité pilote
- ✅ 2 semaines de test
- ✅ Feedback utilisateur
- ✅ Décision GO/NO-GO Phase 2

---

## 📋 Checklist de Lancement

**Avant de commencer :**
- [ ] Créer compte OpenAI API (si pas fait)
- [ ] Ajouter $50 crédit (suffisant pour 2 mois test)
- [ ] Identifier collectivité pilote (3-5 agents)
- [ ] Préparer 10 PDF legal facts
- [ ] Bloquer 10h dev (1 semaine)

**Pendant le dev :**
- [ ] Créer Assistant OpenAI
- [ ] Upload Vector Store
- [ ] Coder assistant.php (~100 lignes)
- [ ] Coder interface chat (~100 lignes)
- [ ] Tester avec 10 questions types

**Après déploiement :**
- [ ] Former les 5 agents testeurs (30 min)
- [ ] Récolter feedback quotidien
- [ ] Corriger bugs critiques sous 24h
- [ ] Mesurer métriques de succès
- [ ] Décision GO/NO-GO Phase 2

---

## 💡 Et Après la Phase Test ?

**Si succès (critères atteints) :**

### **Phase 2 : Déploiement Élargi**
- 5-10 collectivités
- Multi-collectivités (Vector Stores)
- Upload documents par commune
- Dossiers conversations
- Budget : $2,000-3,000

### **Phase 3 : Fonctionnalités Avancées**
- Génération DOCX
- Assistant proactif
- Vérification juridique
- Budget : $6,000-8,000

---

## ✅ Résumé Exécutif

| Aspect | Phase Test MVP |
|--------|----------------|
| **Budget dev** | $0-1,000 |
| **Budget exploitation** | $25/mois |
| **Délai** | 1-2 semaines |
| **Collectivités** | 1 pilote |
| **Complexité** | 🟢 Faible |
| **Risque** | 🟢 Minimal |
| **ROI** | ⭐⭐⭐⭐⭐ |

**Verdict : FAISABLE et RECOMMANDÉ** ✅

---

## 🚀 Prêt à Démarrer ?

**Voulez-vous que je crée le code de base (assistant.php + interface) ?**

Je peux vous livrer en quelques heures :
1. ✅ assistant.php complet (~100 lignes)
2. ✅ Interface chat HTML/JS (~100 lignes)
3. ✅ Script setup Vector Store
4. ✅ Guide d'installation pas à pas

**Dites-moi et je commence !** 🎯
