# 📸 NOIA - Guide : Vision (Screenshots) & Textes Volumineux

## 🎯 Vue d'Ensemble

Les **Assistants API OpenAI** supportent :
- ✅ **Vision** : Analyse d'images (screenshots, photos, scans)
- ✅ **Textes volumineux** : Jusqu'à 32,768 tokens input (~25,000 mots)
- ✅ **Upload de fichiers** : PDF, DOCX, TXT, images

---

## 📸 PARTIE 1 : Vision (Screenshots)

### **Modèles Compatibles**

| Modèle | Vision | Coût Image | Qualité |
|--------|--------|------------|---------|
| **GPT-4o** | ✅ Native | Inclus (gratuit) | ⭐⭐⭐⭐⭐ |
| **GPT-4 Turbo** | ✅ Vision | $0.003-0.015/image | ⭐⭐⭐⭐⭐ |
| GPT-4o Mini | ✅ | Inclus | ⭐⭐⭐ |
| GPT-3.5 | ❌ | N/A | N/A |

**💡 Recommandation : GPT-4o** (Vision incluse sans surcoût)

---

### **Cas d'Usage pour NOIA**

#### **✅ Utilisations Pertinentes**

1. **Analyse de délibérations scannées**
   - Screenshot d'une délibération municipale
   - Extraction des informations clés
   - Vérification de conformité

2. **Tableaux comptables (M57)**
   - Capture d'écran d'un budget
   - Analyse des lignes comptables
   - Détection d'erreurs d'imputation

3. **Documents administratifs**
   - Arrêtés municipaux
   - Convocations
   - Procès-verbaux

4. **Grilles indiciaires**
   - Photo d'une grille papier
   - Comparaison avec grille officielle
   - Calcul de salaire

5. **Plans et schémas**
   - Plans de zonage (PLU)
   - Organigrammes
   - Diagrammes de procédures

---

#### **❌ Limitations**

- ⚠️ **Qualité de l'image** : Minimum 300 DPI pour texte lisible
- ⚠️ **Écriture manuscrite** : Reconnaissance limitée
- ⚠️ **Multiples pages** : 1 image = 1 page (pas de PDF multi-pages en Vision)
- ⚠️ **Tableaux complexes** : Meilleur résultat avec texte qu'avec image

---

### **Implémentation : Upload de Screenshot**

#### **Option A : Base64 (Images < 5 MB)**

**Frontend (JavaScript) :**

```javascript
// Capture ou upload d'image
const fileInput = document.getElementById('screenshot');
const file = fileInput.files[0];

// Convertir en base64
const reader = new FileReader();
reader.onload = function(e) {
    const base64Image = e.target.result;

    // Envoyer à NOIA
    fetch('/api/assistant.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            question: "Analyse cette délibération",
            image: base64Image,
            type: "vision"
        })
    });
};
reader.readAsDataURL(file);
```

**Backend (PHP) :**

```php
<?php
// assistant.php
$question = $_POST['question'];
$image_base64 = $_POST['image'];

// Créer thread
$thread = createThread();

// Ajouter message avec image
$message = [
    'role' => 'user',
    'content' => [
        [
            'type' => 'text',
            'text' => $question
        ],
        [
            'type' => 'image_url',
            'image_url' => [
                'url' => $image_base64,
                'detail' => 'high'  // 'low', 'high', ou 'auto'
            ]
        ]
    ]
];

addMessageToThread($thread['id'], $message);

// Lancer assistant
$run = runAssistant($thread['id'], $assistant_id);
$response = waitForResponse($thread['id'], $run['id']);

echo json_encode(['response' => $response]);
?>
```

---

#### **Option B : Upload Fichier OpenAI (Images > 5 MB)**

**Backend (PHP) :**

```php
<?php
// Upload vers OpenAI
function uploadImageToOpenAI($file_path) {
    $ch = curl_init('https://api.openai.com/v1/files');

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'file' => new CURLFile($file_path),
        'purpose' => 'assistants'
    ]);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true)['id'];
}

// Utiliser dans message
$file_id = uploadImageToOpenAI('/tmp/screenshot.jpg');

$message = [
    'role' => 'user',
    'content' => [
        [
            'type' => 'text',
            'text' => 'Analyse ce document'
        ],
        [
            'type' => 'image_file',
            'image_file' => [
                'file_id' => $file_id
            ]
        ]
    ]
];
?>
```

---

### **Paramètres Vision**

#### **Niveau de Détail**

| Paramètre | Tokens Utilisés | Coût | Usage |
|-----------|----------------|------|-------|
| `detail: 'low'` | 85 tokens | $0.0002 | Aperçu rapide |
| `detail: 'auto'` | Variable | Variable | OpenAI décide |
| `detail: 'high'` | 765-2000 tokens | $0.002-0.005 | Analyse détaillée |

**💡 Recommandation :** `detail: 'high'` pour documents administratifs

---

### **Exemples de Prompts Vision**

#### **1. Analyse de Délibération**

```javascript
{
  question: "Analyse cette délibération municipale. Extrais : date, objet, vote (pour/contre/abstention), et vérifie la conformité du quorum selon l'article L2121-17 du CGCT.",
  image: "data:image/jpeg;base64,/9j/4AAQ..."
}
```

**Réponse attendue :**
```
1️⃣ Informations extraites :
- Date : 15 janvier 2024
- Objet : Attribution marché public voirie
- Vote : 12 pour, 2 contre, 1 abstention

2️⃣ Vérification quorum :
- Conseillers en exercice : 15
- Quorum requis : 8 (majorité absolue)
- Présents : 15 ✅ Quorum atteint

3️⃣ Conformité :
✅ Délibération valide selon art. L2121-17 CGCT
```

---

#### **2. Vérification Budget M57**

```javascript
{
  question: "Vérifie les imputations comptables M57 dans ce tableau. Signale toute erreur.",
  image: "data:image/jpeg;base64,/9j/4AAQ..."
}
```

---

#### **3. Extraction Grille RH**

```javascript
{
  question: "Extrais toutes les données de cette grille indiciaire (échelons, indices bruts, indices majorés) au format tableau Markdown.",
  image: "data:image/jpeg;base64,/9j/4AAQ..."
}
```

---

### **Coûts Vision**

#### **Calcul Automatique par OpenAI**

**Résolution basse (< 512px) :**
- 85 tokens
- Coût : $0.0002 (GPT-4o)

**Résolution haute (> 512px) :**
- Formule : `(width/512) × (height/512) × 170 tokens + 85`
- Exemple : Image 2048×1536
  - Tiles : (2048/512) × (1536/512) = 4 × 3 = 12 tiles
  - Tokens : 12 × 170 + 85 = 2125 tokens
  - Coût : 2125 × $0.0025 / 1000 = $0.005

**💡 Astuce :** Redimensionner à 1024×768 = économie 75% (512 tokens au lieu de 2125)

---

## 📄 PARTIE 2 : Textes Volumineux

### **Limites par Modèle**

| Modèle | Context Window | Max Input | Max Output |
|--------|---------------|-----------|------------|
| **GPT-4o** | 128K tokens | ~100K tokens | 16K tokens |
| **GPT-4 Turbo** | 128K tokens | ~100K tokens | 4K tokens |
| GPT-4o Mini | 128K tokens | ~100K tokens | 16K tokens |

**1 token ≈ 0.75 mots français**
**100K tokens ≈ 75,000 mots ≈ 150 pages**

---

### **Cas d'Usage pour NOIA**

#### **✅ Textes Volumineux Pertinents**

1. **Délibérations complètes**
   - Copier-coller d'une délibération de 10 pages
   - Analyse de conformité
   - Extraction d'informations clés

2. **Arrêtés municipaux**
   - Texte complet d'un arrêté
   - Vérification des visas
   - Détection d'erreurs

3. **Rapports annuels**
   - Compte administratif complet
   - Budget primitif
   - Synthèse automatique

4. **Codes et règlements**
   - Règlement intérieur
   - Règlement de service
   - Comparaison avec législation

5. **Correspondance longue**
   - Échanges préfet/maire
   - Courriers contentieux
   - Synthèse et recommandations

---

### **Implémentation : Textes Longs**

#### **Méthode 1 : Envoi Direct (< 50,000 caractères)**

**Frontend :**

```javascript
// Zone de texte large
<textarea id="long-text" rows="20" cols="80" maxlength="100000">
  Collez votre texte ici...
</textarea>

<button onclick="analyzeText()">Analyser</button>

<script>
function analyzeText() {
    const text = document.getElementById('long-text').value;

    fetch('/api/assistant.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            question: "Analyse cette délibération",
            long_text: text,
            type: "long_text"
        })
    });
}
</script>
```

**Backend :**

```php
<?php
// assistant.php
$question = $_POST['question'];
$long_text = $_POST['long_text'];

// Vérifier longueur
$token_count = strlen($long_text) / 4; // Estimation rapide

if ($token_count > 100000) {
    die(json_encode(['error' => 'Texte trop long (max 100K tokens)']));
}

// Créer thread
$thread = createThread();

// Ajouter message avec texte long
$full_question = $question . "\n\n─────────────\n" . $long_text;

$message = [
    'role' => 'user',
    'content' => $full_question
];

addMessageToThread($thread['id'], $message);

// Lancer assistant
$run = runAssistant($thread['id'], $assistant_id);
$response = waitForResponse($thread['id'], $run['id']);

echo json_encode(['response' => $response]);
?>
```

---

#### **Méthode 2 : Upload Fichier (> 50,000 caractères)**

**Meilleur pour très longs textes (évite timeout)**

```php
<?php
// Créer fichier temporaire
$temp_file = tempnam(sys_get_temp_dir(), 'noia_');
file_put_contents($temp_file . '.txt', $long_text);

// Upload vers OpenAI
$file_id = uploadFileToOpenAI($temp_file . '.txt');

// Attacher à l'assistant (File Search)
attachFileToThread($thread_id, $file_id);

// Poser question (File Search automatique)
$message = [
    'role' => 'user',
    'content' => 'Analyse le document que je viens d\'uploader et ' . $question
];

addMessageToThread($thread['id'], $message);

// Lancer avec File Search
$run = runAssistant($thread['id'], $assistant_id);

// Nettoyer
unlink($temp_file . '.txt');
deleteFile($file_id); // Après traitement
?>
```

---

### **Optimisations : Traitement de Longs Textes**

#### **1. Chunking Intelligent**

**Si texte > 100K tokens :**

```php
<?php
function chunkText($text, $max_tokens = 80000) {
    // Découper par paragraphes
    $paragraphs = explode("\n\n", $text);

    $chunks = [];
    $current_chunk = "";
    $current_tokens = 0;

    foreach ($paragraphs as $para) {
        $para_tokens = strlen($para) / 4;

        if ($current_tokens + $para_tokens > $max_tokens) {
            $chunks[] = $current_chunk;
            $current_chunk = $para;
            $current_tokens = $para_tokens;
        } else {
            $current_chunk .= "\n\n" . $para;
            $current_tokens += $para_tokens;
        }
    }

    if ($current_chunk) {
        $chunks[] = $current_chunk;
    }

    return $chunks;
}

// Traiter chaque chunk
$chunks = chunkText($long_text);
$summaries = [];

foreach ($chunks as $i => $chunk) {
    $response = askAssistant("Résume ce passage (partie " . ($i+1) . "/" . count($chunks) . ") :\n\n" . $chunk);
    $summaries[] = $response;
}

// Synthèse finale
$final_summary = askAssistant("Voici les résumés de chaque partie :\n\n" . implode("\n\n", $summaries) . "\n\nFais une synthèse globale.");
?>
```

---

#### **2. Extraction Sélective**

**Au lieu d'envoyer tout le texte :**

```php
<?php
// Extraire seulement les parties pertinentes
function extractRelevantSections($text, $keywords) {
    $lines = explode("\n", $text);
    $relevant = [];

    foreach ($lines as $i => $line) {
        foreach ($keywords as $keyword) {
            if (stripos($line, $keyword) !== false) {
                // Prendre 5 lignes avant et après
                $context = array_slice($lines, max(0, $i-5), 11);
                $relevant[] = implode("\n", $context);
                break;
            }
        }
    }

    return implode("\n\n─────────────\n\n", array_unique($relevant));
}

// Exemple : Rechercher sections sur "quorum"
$keywords = ['quorum', 'majorité', 'présents', 'membres en exercice'];
$relevant_text = extractRelevantSections($long_text, $keywords);

// Envoyer seulement le pertinent (économie tokens)
$response = askAssistant("Analyse ces extraits sur le quorum :\n\n" . $relevant_text);
?>
```

**💡 Économie : 80-90% de tokens**

---

### **Exemples de Prompts : Textes Longs**

#### **1. Synthèse de Délibération**

```
Voici une délibération municipale complète (copier-coller ci-dessous).

Extrais :
1️⃣ Date et lieu
2️⃣ Objet principal
3️⃣ Présents/absents/excusés
4️⃣ Vote détaillé (pour/contre/abstention avec noms)
5️⃣ Points d'attention juridiques

Format : Structure 1️⃣2️⃣3️⃣4️⃣5️⃣

────────────────
[TEXTE COMPLET DE LA DÉLIBÉRATION - 5000 mots]
────────────────
```

---

#### **2. Vérification Conformité**

```
Voici un arrêté municipal. Vérifie la conformité :

Checklist :
✅ Visas (CGCT, lois applicables)
✅ Considérants (motivations légales)
✅ Articles numérotés
✅ Formule exécutoire
✅ Date et signature
✅ Publicité et recours

────────────────
[TEXTE COMPLET DE L'ARRÊTÉ - 3000 mots]
────────────────
```

---

#### **3. Comparaison Avant/Après**

```
Compare ces 2 versions de règlement intérieur.

Identifie :
- Ajouts
- Suppressions
- Modifications
- Impact juridique

────────────────
VERSION 1 (2023) :
[Texte complet - 4000 mots]

VERSION 2 (2024) :
[Texte complet - 4500 mots]
────────────────
```

---

### **Coûts : Textes Longs**

| Longueur Texte | Tokens | Coût Input (GPT-4o) | Coût Output (1500 tokens) | Total |
|----------------|--------|---------------------|---------------------------|-------|
| 5 pages (2500 mots) | ~3300 | $0.008 | $0.015 | **$0.023** |
| 20 pages (10K mots) | ~13K | $0.033 | $0.015 | **$0.048** |
| 50 pages (25K mots) | ~33K | $0.083 | $0.015 | **$0.098** |
| 100 pages (50K mots) | ~66K | $0.165 | $0.015 | **$0.180** |

**💡 Astuce :** Utiliser extraction sélective pour réduire 80% des tokens

---

## 🔄 Workflow Complet : Image + Texte Long

### **Cas d'Usage : Délibération Scannée + Analyse**

**Étape 1 : Upload screenshot**
```javascript
fetch('/api/assistant.php', {
    body: JSON.stringify({
        action: 'extract_text',
        image: base64_image
    })
});
```

**Étape 2 : OpenAI extrait le texte (OCR)**
```
Réponse : "Texte extrait : CONSEIL MUNICIPAL DU 15 JANVIER 2024..."
```

**Étape 3 : Analyse du texte extrait**
```javascript
fetch('/api/assistant.php', {
    body: JSON.stringify({
        action: 'analyze',
        text: extracted_text,
        question: 'Vérifie la conformité de cette délibération'
    })
});
```

**Étape 4 : Réponse structurée**
```
1️⃣ Conformité formelle : ✅
2️⃣ Quorum : ✅ 12/15 présents
3️⃣ Vote : Valide (majorité atteinte)
4️⃣ Points d'attention : Aucun
```

---

## ⚙️ Configuration Frontend : Interface Améliorée

### **Zone de Saisie Mixte**

**HTML :**

```html
<div class="noia-input-container">
    <!-- Question textuelle -->
    <textarea id="question" placeholder="Posez votre question..."></textarea>

    <!-- Upload image -->
    <div class="file-upload">
        <label for="image-upload">
            📸 Ajouter un screenshot
        </label>
        <input type="file" id="image-upload" accept="image/*" onchange="previewImage(this)">
        <div id="image-preview"></div>
    </div>

    <!-- Zone texte long -->
    <div class="long-text-toggle">
        <button onclick="toggleLongText()">📄 Coller un document long</button>
    </div>

    <div id="long-text-area" style="display:none">
        <textarea id="long-text" rows="15" placeholder="Collez votre document ici (délibération, arrêté, rapport...)"></textarea>
        <small>Max 100,000 caractères (~75,000 mots)</small>
    </div>

    <!-- Bouton envoi -->
    <button id="submit" onclick="sendToNoia()">Envoyer</button>
</div>
```

**JavaScript :**

```javascript
function sendToNoia() {
    const question = document.getElementById('question').value;
    const imageFile = document.getElementById('image-upload').files[0];
    const longText = document.getElementById('long-text').value;

    let payload = { question: question };

    // Si image
    if (imageFile) {
        const reader = new FileReader();
        reader.onload = function(e) {
            payload.image = e.target.result;
            sendRequest(payload);
        };
        reader.readAsDataURL(imageFile);
    }
    // Si texte long
    else if (longText) {
        payload.long_text = longText;
        sendRequest(payload);
    }
    // Question simple
    else {
        sendRequest(payload);
    }
}

function sendRequest(payload) {
    fetch('/api/assistant.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => displayResponse(data));
}
```

---

## 📊 Résumé des Capacités

| Fonctionnalité | Support | Limite | Coût Additionnel |
|----------------|---------|--------|------------------|
| **Screenshots** | ✅ GPT-4o / GPT-4 Turbo | 20 MB/image | Inclus (GPT-4o) |
| **Photos** | ✅ | 20 MB | Inclus |
| **Scans PDF** | ✅ (page par page) | 512 MB/fichier | Inclus |
| **Texte < 50K mots** | ✅ | 100K tokens | Standard |
| **Texte > 50K mots** | ✅ (upload fichier) | 512 MB | Standard + RAG |
| **OCR automatique** | ✅ | Qualité image-dépendant | Inclus |
| **Tableaux** | ✅ | Complexité limitée | Inclus |
| **Manuscrit** | ⚠️ Limité | Lisibilité requise | Inclus |

---

## ✅ Checklist d'Implémentation

### **Pour ajouter Vision à NOIA :**

- [ ] Modifier frontend : ajouter input file (type="file" accept="image/*")
- [ ] JavaScript : convertir image en base64
- [ ] Backend : envoyer image dans message Assistants API
- [ ] Tester avec screenshot de délibération
- [ ] Optimiser résolution images (1024×768 recommandé)

### **Pour ajouter Textes Longs :**

- [ ] Modifier frontend : ajouter textarea grande
- [ ] Backend : vérifier longueur (< 100K tokens)
- [ ] Si > 100K : implémenter chunking ou upload fichier
- [ ] Tester avec délibération 10 pages
- [ ] Implémenter extraction sélective (optionnel, économise tokens)

---

## 🎯 Recommandation Finale

**Pour NOIA, activez les 2 fonctionnalités :**

✅ **Vision (Screenshots)** → GPT-4o (gratuit)
✅ **Textes Longs** → Envoi direct < 50K caractères

**Coût additionnel : ~$0 à $15/mois** selon usage

**Bénéfice utilisateur : +300% de flexibilité** 🚀
