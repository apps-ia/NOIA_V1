# ⚙️ Optimisation de la Configuration NOIA

## 🎯 Pour des Réponses de Qualité ChatGPT

Voici les paramètres optimaux dans `config/config.php` pour obtenir des réponses de qualité professionnelle.

---

## 📊 Paramètres Recommandés

### **1. Tokens (Longueur des réponses)**

```php
define('OPENAI_MAX_TOKENS', 3000);  // Au lieu de 2500
```

**Pourquoi :**
- 2500 tokens ≈ 1800-2000 mots
- 3000 tokens ≈ 2200-2400 mots
- Pour des grilles RH complètes avec TOUS les échelons → 3000 tokens minimum

**Impact :**
- ✅ Réponses plus complètes
- ✅ Tableaux complets (pas de "..." ou échelons manquants)
- ✅ Détails exhaustifs pour chaque section

---

### **2. Température (Précision vs Créativité)**

```php
define('OPENAI_TEMPERATURE', 0.3);  // Déjà optimal
```

**Pourquoi :**
- 0.0 = Déterministe mais robotique
- 0.3 = **Optimal** : Précis + naturel
- 0.7 = Créatif mais moins précis
- 1.0+ = Trop créatif pour contexte administratif

**Garder 0.3 = Parfait pour administration publique**

---

### **3. Modèle OpenAI**

```php
define('OPENAI_MODEL', 'gpt-4-turbo');  // Déjà optimal
```

**Options :**
- `gpt-4-turbo` : **Recommandé** (meilleur rapport qualité/coût)
- `gpt-4o` : Plus rapide, moins cher, qualité équivalente
- `gpt-4` : Plus cher, pas forcément meilleur
- `gpt-3.5-turbo` : Moins cher mais qualité inférieure

**Garder `gpt-4-turbo` ou tester `gpt-4o`**

---

### **4. Debug Mode**

**Pour les tests :**
```php
define('DEBUG_MODE', true);
```

**En production :**
```php
define('DEBUG_MODE', false);
```

---

## 🧪 Tests de Qualité

### **Test 1 : Grille RH Complète**

**Question :**
```
Quelle est la grille indiciaire pour un adjoint administratif territorial ?
```

**Réponse attendue :**
- 1️⃣ Références juridiques : Décret n° 85-1148, n° 2016-604
- 2️⃣ Analyse : 3 grades, valeur du point 4,92302 €
- 3️⃣ Application pratique :
  - Grade "Adjoint administratif" : échelons 1 à 11 COMPLETS
  - Grade "Adjoint administratif principal 2e classe" : échelons 1 à 12 COMPLETS
  - Grade "Adjoint administratif principal 1re classe" : échelons 1 à 10 COMPLETS
  - Pour CHAQUE échelon : indice brut + indice majoré + salaire brut
- 4️⃣ Proposition d'usage : Tableau récapitulatif

**Longueur attendue :** 1000-1500 mots

---

### **Test 2 : Quorum (Legal Facts)**

**Question :**
```
Quel est le quorum nécessaire lors d'une reconvocation du conseil municipal ?
```

**Réponse attendue :**
- 1️⃣ Références : Article L2121-17 du CGCT
- 2️⃣ Analyse : Différence 1ère convocation vs reconvocation
- 3️⃣ Application : **AUCUN QUORUM** requis à la reconvocation
- 4️⃣ Proposition : Modèle de convocation

**Source :** Legal_facts table (priorité 10/10)

---

### **Test 3 : FCTVA**

**Question :**
```
Quelles sont les dépenses éligibles au FCTVA ?
```

**Réponse attendue :**
- 1️⃣ Références : Article L1615-1 du CGCT, Instruction DGFiP
- 2️⃣ Analyse : Conditions d'éligibilité (> 5 000 € HT sauf voirie)
- 3️⃣ Application : Liste exhaustive des dépenses éligibles
- 4️⃣ Proposition : Tableau de suivi FCTVA

---

### **Test 4 : M57 Comptabilité**

**Question :**
```
Quel compte M57 utiliser pour l'achat d'un broyeur de végétaux ?
```

**Réponse attendue :**
- 1️⃣ Références : Instruction M57 - DGFiP
- 2️⃣ Analyse : Matériel technique, durée d'utilisation
- 3️⃣ Application :
  - Compte **2128** (Autres installations, matériel et outillages techniques)
  - Ou compte **2158** (Autres immobilisations corporelles) selon montant
  - Chapitre 21 (Immobilisations corporelles)
- 4️⃣ Proposition : Délibération d'acquisition

---

### **Test 5 : Délibération**

**Question :**
```
Quels sont les délais de convocation pour un conseil municipal ?
```

**Réponse attendue :**
- 1️⃣ Références : Article L2121-11 du CGCT
- 2️⃣ Analyse : Délais francs, exceptions urgence
- 3️⃣ Application :
  - **5 jours francs** (convocation standard)
  - **3 jours francs** en urgence (décision motivée du maire)
  - Quorum : majorité absolue (sauf reconvocation)
- 4️⃣ Proposition : Modèle de convocation avec visas

---

## 📈 Métriques de Qualité

### **Critères de validation :**

```
✅ Structure 4 sections (1️⃣ 2️⃣ 3️⃣ 4️⃣)
✅ Longueur : 1000-1500 mots
✅ Décrets cités avec numéros exacts
✅ Sources mentionnées (emploi-collectivites.fr, Légifrance, etc.)
✅ Tableaux pour grilles RH et données chiffrées
✅ Tous les échelons affichés (pas de "...")
✅ Indices bruts ET majorés
✅ Salaires calculés
✅ Formules de calcul expliquées
✅ Articles CGCT précis (L2121-17, L1615-1, etc.)
```

---

## 🔧 Si les Réponses sont Trop Courtes

### **Problème : Réponses de 500-800 mots au lieu de 1000-1500**

**Solution 1 : Augmenter MAX_TOKENS**
```php
define('OPENAI_MAX_TOKENS', 3500);  // Au lieu de 2500
```

**Solution 2 : Modifier le system prompt** (dans proxy.php)

Chercher la ligne :
```php
- Longueur : 1000-1500 mots pour être complet, exhaustif et professionnel
```

Remplacer par :
```php
- Longueur OBLIGATOIRE : 1500-2000 mots MINIMUM pour être exhaustif
- Les réponses de moins de 1500 mots sont INTERDITES
- Tu DOIS détailler CHAQUE échelon, CHAQUE cas, CHAQUE exception
```

---

## 🔧 Si les Réponses Manquent de Détails

### **Problème : Grilles RH avec "..." au lieu de tous les échelons**

**Solution : Renforcer le prompt** (dans proxy.php)

Chercher :
```php
<p><strong>Répète ce tableau pour CHAQUE grade du cadre d'emploi.</strong></p>
```

Remplacer par :
```php
<p><strong>⚠️ OBLIGATION : Répète ce tableau pour CHAQUE grade ET CHAQUE échelon du cadre d'emploi.</strong></p>
<p><strong>INTERDIT d'utiliser "..." ou "etc." - Tu DOIS lister TOUS les échelons.</strong></p>
<p><strong>Si un grade a 12 échelons, tu DOIS afficher les 12 lignes complètes.</strong></p>
```

---

## 🎯 Configuration Finale Recommandée

```php
// Dans config/config.php

define('OPENAI_API_KEY', 'sk-votre-cle');
define('OPENAI_MODEL', 'gpt-4-turbo');  // ou 'gpt-4o' pour + rapide
define('OPENAI_MAX_TOKENS', 3000);      // Augmenté à 3000
define('OPENAI_TEMPERATURE', 0.3);      // Optimal
define('ENABLE_WEB_SEARCH', true);      // Important pour sources officielles
define('ENABLE_RAG', true);             // Important pour documents locaux
define('DEBUG_MODE', false);            // En production
```

---

## 📊 Coûts Estimés

Avec `OPENAI_MAX_TOKENS: 3000` et `gpt-4-turbo` :

**Par requête :**
- Input : ~2500 tokens × $0.01/1K = $0.025
- Output : ~2000 tokens × $0.03/1K = $0.060
- **Total : ~$0.085 par question**

**Par mois (100 questions) :**
- 100 × $0.085 = **$8.50/mois**

**Acceptable pour un outil professionnel.**

---

## ✅ Checklist Optimisation

```
Configuration :
☐ OPENAI_MAX_TOKENS = 3000
☐ OPENAI_TEMPERATURE = 0.3
☐ OPENAI_MODEL = 'gpt-4-turbo' ou 'gpt-4o'
☐ ENABLE_WEB_SEARCH = true
☐ ENABLE_RAG = true

Tests de qualité :
☐ Test grille RH → TOUS les échelons affichés
☐ Test quorum → Article L2121-17 cité + "AUCUN quorum"
☐ Test FCTVA → Article L1615-1 + conditions détaillées
☐ Test M57 → Numéro de compte précis (2128, 2158, etc.)
☐ Test délibération → Délais exacts + quorum + articles

Validation finale :
☐ Longueur : 1000-1500 mots par réponse
☐ Structure 4 sections présente
☐ Sources citées (emploi-collectivites.fr, Légifrance, etc.)
☐ Tableaux complets pour données chiffrées
☐ Pas de "..." dans les grilles
```

---

**🎯 Avec ces optimisations, NOIA produira des réponses de qualité ChatGPT / GPT personnalisé !**
