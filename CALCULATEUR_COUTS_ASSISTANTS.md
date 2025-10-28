# 💰 NOIA - Calculateur de Coûts : Assistants API vs Solution Actuelle

## 📊 Pricing OpenAI (Janvier 2025)

### **GPT-4 Turbo (Recommandé pour NOIA)**

| Composant | Prix | Unité |
|-----------|------|-------|
| **Input tokens** | $0.01 | par 1K tokens |
| **Output tokens** | $0.03 | par 1K tokens |
| **File Search (RAG)** | $0.10 | par GB/jour |
| **Image input** (Vision) | $0.003-0.015 | par image |
| **Code Interpreter** | $0.03 | par session |

---

### **GPT-4o (Alternative Plus Rapide)**

| Composant | Prix | Unité |
|-----------|------|-------|
| **Input tokens** | $0.0025 | par 1K tokens (4x moins cher) |
| **Output tokens** | $0.01 | par 1K tokens (3x moins cher) |
| **File Search (RAG)** | $0.10 | par GB/jour |
| **Vision native** | Inclus | (pas de surcoût) |

---

### **GPT-4o Mini (Économique)**

| Composant | Prix | Unité |
|-----------|------|-------|
| **Input tokens** | $0.00015 | par 1K tokens (67x moins cher !) |
| **Output tokens** | $0.0006 | par 1K tokens (50x moins cher !) |
| **File Search (RAG)** | $0.10 | par GB/jour |

**⚠️ Attention :** Qualité inférieure pour administration complexe

---

## 📈 Scénarios de Coûts pour NOIA

### **Hypothèses de Base**

| Métrique | Valeur |
|----------|--------|
| **Questions par jour** | Variable (50-500) |
| **Tokens input moyen** | 300 tokens (question + contexte RAG) |
| **Tokens output moyen** | 1500 tokens (réponse complète 1000-1500 mots) |
| **Documents RAG** | 10 PDF (total 50 MB = 0.05 GB) |
| **Images par jour** | 0-20 (screenshots) |

---

## 💵 SCÉNARIO 1 : Usage Faible (50 questions/jour)

**Profil :** Petite commune, 1-2 utilisateurs

### **Avec GPT-4 Turbo**

```
Input :  50 × 300 tokens  = 15,000 tokens/jour  = $0.15
Output : 50 × 1500 tokens = 75,000 tokens/jour  = $2.25
RAG :    0.05 GB × $0.10  = $0.005/jour         = $0.005
─────────────────────────────────────────────────────────
Total/jour :  $2.40
Total/mois :  $72
Total/an :    $864
```

### **Avec GPT-4o (Recommandé - Meilleur ratio qualité/prix)**

```
Input :  15,000 tokens  = $0.04
Output : 75,000 tokens  = $0.75
RAG :    0.05 GB        = $0.005
─────────────────────────────────
Total/jour :  $0.80
Total/mois :  $24
Total/an :    $288
```

**💡 Économie : $576/an avec GPT-4o vs GPT-4 Turbo**

---

## 💵 SCÉNARIO 2 : Usage Moyen (150 questions/jour)

**Profil :** Commune moyenne, 5-10 utilisateurs

### **Avec GPT-4 Turbo**

```
Input :  150 × 300 tokens  = 45,000 tokens/jour   = $0.45
Output : 150 × 1500 tokens = 225,000 tokens/jour  = $6.75
RAG :    0.05 GB           = $0.005/jour          = $0.005
─────────────────────────────────────────────────────────
Total/jour :  $7.20
Total/mois :  $216
Total/an :    $2,592
```

### **Avec GPT-4o**

```
Input :  45,000 tokens   = $0.11
Output : 225,000 tokens  = $2.25
RAG :    0.05 GB         = $0.005
─────────────────────────────────
Total/jour :  $2.37
Total/mois :  $71
Total/an :    $852
```

**💡 Économie : $1,740/an avec GPT-4o**

---

## 💵 SCÉNARIO 3 : Usage Élevé (500 questions/jour)

**Profil :** Grande commune/Intercommunalité, 20+ utilisateurs

### **Avec GPT-4 Turbo**

```
Input :  500 × 300 tokens  = 150,000 tokens/jour  = $1.50
Output : 500 × 1500 tokens = 750,000 tokens/jour  = $22.50
RAG :    0.05 GB           = $0.005/jour          = $0.005
─────────────────────────────────────────────────────────
Total/jour :  $24.00
Total/mois :  $720
Total/an :    $8,640
```

### **Avec GPT-4o**

```
Input :  150,000 tokens  = $0.38
Output : 750,000 tokens  = $7.50
RAG :    0.05 GB         = $0.005
─────────────────────────────────
Total/jour :  $7.89
Total/mois :  $237
Total/an :    $2,844
```

**💡 Économie : $5,796/an avec GPT-4o**

---

## 📸 SCÉNARIO 4 : Avec Screenshots (20 images/jour)

**Profil :** Usage avancé avec analyse de documents scannés

### **GPT-4o (Vision incluse)**

```
Base (150 questions/jour) :     $2.37/jour
Images (20 × 765 tokens) :      15,300 tokens input = $0.04/jour
────────────────────────────────────────────────────────────
Total/jour :  $2.41
Total/mois :  $72
Total/an :    $864
```

**💡 Impact images : +$12/mois seulement**

---

## 📊 Tableau Comparatif Complet

| Scénario | Questions/jour | GPT-4 Turbo | GPT-4o | GPT-4o Mini | Économie GPT-4o |
|----------|----------------|-------------|--------|-------------|-----------------|
| **Faible** | 50 | $72/mois | $24/mois | $4/mois | -67% |
| **Moyen** | 150 | $216/mois | $71/mois | $11/mois | -67% |
| **Élevé** | 500 | $720/mois | $237/mois | $36/mois | -67% |
| **Avec images** | 150 + 20 img | $220/mois | $72/mois | $14/mois | -67% |

---

## 🆚 Comparaison : Assistants API vs Solution Actuelle

### **Solution Actuelle (proxy.php + Chat API)**

**Coûts :**
```
GPT-4 Turbo Chat API : Identique
Pas de RAG : $0
Embeddings DIY : Coût serveur (~$10/mois)
Maintenance : Temps développeur (coût caché)
───────────────────────────────────────────
Total : ~$70/mois (150 questions/jour)
```

### **Solution Assistants API**

**Coûts :**
```
GPT-4o Assistants : $71/mois (150 questions/jour)
RAG inclus : $0.15/mois (0.05 GB)
Maintenance : ~0 (automatique)
───────────────────────────────────────────
Total : ~$71/mois
```

**Différence : +$1/mois pour RAG automatique + maintenance zéro**

---

## 🎯 Optimisations de Coûts

### **1. Choisir le Bon Modèle**

| Cas d'Usage | Modèle Recommandé | Justification |
|-------------|------------------|---------------|
| **Questions complexes juridiques** | GPT-4 Turbo | Meilleure précision références légales |
| **Usage général** | **GPT-4o** ✅ | Meilleur rapport qualité/prix |
| **Budget très serré** | GPT-4o Mini | OK pour questions simples |

**💡 Recommandation NOIA : GPT-4o** (économie 67% vs GPT-4 Turbo)

---

### **2. Limiter les Tokens Output**

**Impact :**
```
Réponse 1500 mots = 2000 tokens → $0.06 (GPT-4o)
Réponse 1000 mots = 1300 tokens → $0.04 (GPT-4o)

Économie : -33% sur output
```

**Implémentation :**
```javascript
{
  "model": "gpt-4o",
  "max_tokens": 1500  // Au lieu de 4096
}
```

---

### **3. Prompt Caching (Bêta)**

**Principe :** OpenAI cache les prompts répétitifs (system prompt)

**Économie :**
```
Prompt système : 500 tokens
Sans cache : 500 tokens × $0.0025 = $0.00125 par requête
Avec cache : 500 tokens × $0.000125 (10x moins cher après 1er appel)

Économie : ~$0.001 par requête = $4.50/mois (150 req/jour)
```

**Note :** Actuellement en bêta, à activer quand disponible

---

### **4. Suppression Automatique des Threads**

**Impact RAG :**
```
10 threads conservés/jour × 30 jours = 300 threads
Taille moyenne thread : 10 KB
Storage : 300 × 10 KB = 3 MB = 0.003 GB

Coût storage threads : $0.003 × $0.10 = $0.0003/jour

Avec suppression 7 jours : $0.0001/jour (économie 66%)
```

**💡 Économie faible mais bonne pratique RGPD**

---

### **5. Batch Processing (Si Applicable)**

**Pour rapports/analyses en masse :**
```
Batch API : 50% de réduction de coût
Délai : 24h (asynchrone)

Cas d'usage : Génération quotidienne de synthèses
```

---

## 📉 Projection Annuelle Détaillée

### **Commune Moyenne (150 questions/jour)**

**Avec GPT-4o + Optimisations :**

| Composant | Coût Mensuel | Coût Annuel |
|-----------|--------------|-------------|
| **Input tokens** | $3.30 | $40 |
| **Output tokens** (limite 1500 tokens) | $67.50 | $810 |
| **File Search (RAG)** | $0.15 | $2 |
| **Images** (10/jour) | $6.00 | $72 |
| **Total** | **$77** | **$924** |

**Coût par question : $0.51**

---

### **Comparaison avec ChatGPT Plus (Utilisateur Final)**

| Solution | Coût/mois | Coût/an | Limitations |
|----------|-----------|---------|-------------|
| **ChatGPT Plus** (1 utilisateur) | $20 | $240 | 1 seul utilisateur |
| **NOIA (API)** | $77 | $924 | ✅ Illimité utilisateurs |
| **NOIA par utilisateur** (10 users) | $7.70 | $92 | **Économie 54%** |

**💡 À partir de 3 utilisateurs, NOIA API est plus rentable que ChatGPT Plus**

---

## 🎯 Recommandations Finales

### **Pour Budget Serré (< $50/mois)**

```
✅ GPT-4o Mini
✅ Limite 1000 tokens output
✅ Pas d'images
✅ Suppression threads 7 jours

Coût : ~$15/mois (150 questions/jour)
```

---

### **Pour Usage Optimal (Recommandé)**

```
✅ GPT-4o
✅ File Search activé
✅ Vision activée (images)
✅ Limite 1500 tokens output
✅ Suppression threads 30 jours

Coût : ~$77/mois (150 questions/jour)
```

---

### **Pour Qualité Maximum**

```
✅ GPT-4 Turbo
✅ File Search activé
✅ Vision activée
✅ Limite 2000 tokens output
✅ Pas de limite images

Coût : ~$220/mois (150 questions/jour)
```

---

## 🧮 Calculateur Interactif

**Estimez vos coûts personnalisés :**

```
Questions/jour :         [____]
Tokens output moyen :    [1500]
Images/jour :            [____]
Documents RAG (MB) :     [50]

Modèle :
[ ] GPT-4 Turbo    →  Coût : $_____/mois
[x] GPT-4o         →  Coût : $_____/mois
[ ] GPT-4o Mini    →  Coût : $_____/mois
```

**Formule :**
```javascript
// Input
input_cost = (questions * 300 * 0.0025) / 1000

// Output
output_cost = (questions * tokens_output * 0.01) / 1000

// Images
image_cost = images * 765 * 0.0025 / 1000

// RAG
rag_cost = (documents_mb / 1000) * 0.10

// Total/jour
total_day = input_cost + output_cost + image_cost + rag_cost

// Total/mois
total_month = total_day * 30
```

---

## 💡 Conseils pour Réduire les Coûts

### **1. Optimiser les Prompts**

❌ **Prompt verbeux (800 tokens) :**
```
Tu es NOIA_Collectivités, un assistant IA spécialisé...
[300 lignes de contexte répétitif]
```

✅ **Prompt concis (400 tokens) :**
```
Tu es NOIA. Réponds avec références légales précises.
Format : 1️⃣2️⃣3️⃣4️⃣. 1000-1500 mots.
```

**Économie : $0.001 × 150 questions = $4.50/mois**

---

### **2. Éviter les Réponses Trop Longues**

```javascript
// Forcer limite raisonnable
{
  "max_tokens": 1500,  // Au lieu de 4096
  "temperature": 0.3   // Réponses plus concises
}
```

**Économie : ~30% sur output**

---

### **3. Mettre en Cache les Questions Fréquentes**

**Implémentation côté NOIA :**
```php
// Cache Redis/Memcached
if (cache_exists($question_hash)) {
    return cache_get($question_hash);
}

$response = callAssistant($question);
cache_set($question_hash, $response, 3600); // 1h
```

**Économie : 20-40% si questions répétitives**

---

## 📊 Résumé Exécutif

| Métrique | Valeur |
|----------|--------|
| **Coût recommandé (GPT-4o, 150 q/j)** | **$77/mois** |
| **Coût par question** | **$0.51** |
| **Coût par utilisateur (10 users)** | **$7.70/mois** |
| **Économie vs GPT-4 Turbo** | **-67%** |
| **Économie vs ChatGPT Plus (10 users)** | **-62%** |
| **Optimisations possibles** | **-30% supplémentaires** |

---

## ✅ Conclusion

**Pour NOIA avec 150 questions/jour :**

💰 **Budget optimal : ~$77/mois** (GPT-4o + RAG + Vision)
💰 **Budget serré : ~$15/mois** (GPT-4o Mini)
💰 **Qualité max : ~$220/mois** (GPT-4 Turbo)

**La solution Assistants API est RENTABLE dès 3+ utilisateurs comparé à ChatGPT Plus** ✅
