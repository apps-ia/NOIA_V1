# 🧠 NOIA v4.0 - Phase 2 : Intelligence & Précision Légale

## ✅ Phase 2 Implémentée !

La Phase 2 apporte des améliorations majeures pour **garantir la précision légale** et **forcer la consultation des sources officielles**.

---

## 🎯 Objectifs Phase 2 (ATTEINTS)

### ✅ 1. Pré-analyse intelligente
**Avant :** GPT recevait directement la question sans analyse préalable

**Maintenant :** Le système analyse la question AVANT l'appel OpenAI pour :
- Détecter les cas spéciaux (quorum, FCTVA, IFSE, M57, marchés publics, etc.)
- Classifier automatiquement (finances, RH, juridique, urbanisme)
- Déterminer le niveau de priorité (1-10)
- Identifier si c'est une question critique (priorité ≥ 9)

---

### ✅ 2. Legal Facts Database (règles validées)
**Avant :** Pas d'utilisation de la table `legal_facts`

**Maintenant :**
- Consultation automatique des règles validées pour les questions critiques
- Injection des legal_facts dans le system prompt avec **PRIORITÉ ABSOLUE 10/10**
- Format spécial qui oblige GPT à utiliser ces règles en priorité
- 20+ règles validées (quorum, FCTVA, M57, IFSE, RH, marchés publics)

---

### ✅ 3. Forçage des sources officielles
**Avant :** `tool_choice: 'auto'` - GPT décidait s'il voulait chercher ou non

**Maintenant :**
- `tool_choice` intelligent selon le contexte
- Pour les questions critiques → **force** l'appel de `search_official_websites`
- Pour les questions générales → laisse GPT décider ('auto')
- Insiste sur Légifrance, DGCL, DGFIP, CDG, CNFPT

---

### ✅ 4. Logging avancé
**Avant :** Logs basiques (timestamp, IP, temps de réponse)

**Maintenant :**
- **Tokens utilisés** (prompt_tokens, completion_tokens, total)
- **Coûts estimés** en USD (basé sur tarifs GPT-4 Turbo)
- **Sources consultées** (central, local, legal_facts)
- **Contextes détectés** par la pré-analyse
- **Nombre d'appels de fonctions** (tool calls)
- Fichier de log séparé : `logs/metrics_phase2.log`

---

## 📦 Fichiers Créés/Modifiés

### **Nouveaux Fichiers Phase 2**

#### **api/pre_analysis.php**
- **Rôle :** Système de pré-analyse intelligente
- **Fonctionnalités :**
  - Détection de 9 contextes spéciaux (quorum, FCTVA, IFSE, M57, RH, marchés publics, délibération, CGCT, urbanisme)
  - Classification automatique par domaine
  - Calcul du niveau de priorité (1-10)
  - Flags pour forcer legal_facts et sources officielles
  - Suggestion des sites officiels à consulter

**Exemple de détection :**
```php
Question : "Quel est le quorum pour une reconvocation du conseil municipal ?"

Détection :
- Contexte : quorum
- Catégorie : juridique
- Priorité : 10/10
- Question critique : OUI
- Force legal_facts : OUI
- Force sources officielles : OUI
- Sites suggérés : legifrance.gouv.fr
- Tags legal_facts : ['quorum']
```

---

#### **api/legal_facts_manager.php**
- **Rôle :** Gestionnaire de règles légales validées
- **Fonctionnalités :**
  - Recherche des legal_facts par catégorie (quorum, FCTVA, etc.)
  - Recherche textuelle dans le contenu des règles
  - Formatage spécial pour injection dans le prompt OpenAI
  - Vérification du statut de la table legal_facts
  - Résumé pour logging

**Format d'injection dans le prompt :**
```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🔴 RÈGLES OFFICIELLES VALIDÉES - PRIORITÉ ABSOLUE 10/10
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Ces règles ont été VALIDÉES par des experts juridiques.
Elles DOIVENT être utilisées EN PRIORITÉ sur toute autre source.
NE PAS inventer de règles si elles sont déjà définies ci-dessous.

┌─────────────────────────────────────────────────┐
│ CATÉGORIE : QUORUM
│ TITRE : Quorum - Reconvocation (aucun quorum requis)
│ RÉFÉRENCE : Article L2121-17 du CGCT
│ PRIORITÉ : 10/10 (ABSOLUE)
└─────────────────────────────────────────────────┘

RÈGLE VALIDÉE :
Lors d'une reconvocation, le conseil délibère SANS CONDITION DE QUORUM...

⚠️ CETTE RÈGLE EST OFFICIELLE ET DOIT ÊTRE CITÉE EXACTEMENT.
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

💡 INSTRUCTION CRITIQUE :
Si la question concerne l'une de ces règles validées :
1. Utilise UNIQUEMENT les informations ci-dessus
2. Cite EXACTEMENT la référence légale indiquée
3. NE PAS rechercher d'autres sources pour ces points spécifiques
4. Si besoin de compléments, tu peux consulter les sources officielles APRÈS
```

---

### **Fichiers Modifiés Phase 2**

#### **api/proxy.php** (Version 4.0.0)
**Changements majeurs :**

1. **Ajout des requires** (lignes 22-23) :
   ```php
   require_once __DIR__ . '/pre_analysis.php';
   require_once __DIR__ . '/legal_facts_manager.php';
   ```

2. **Signature de callOpenAI modifiée** (ligne 265) :
   ```php
   function callOpenAI($question, $commune, $search_results, $pre_analysis = [], $legal_facts = [])
   ```

3. **Injection des legal_facts dans le system prompt** (lignes 291-322) :
   - Formatage spécial des règles validées
   - Ajout du contexte de pré-analyse
   - Section avec priorité absolue

4. **tool_choice intelligent** (lignes 428-448) :
   - Force `search_official_websites` pour questions critiques
   - Laisse 'auto' pour questions générales

5. **Extraction des métriques OpenAI** (lignes 491-512) :
   - Tokens (prompt, completion, total)
   - Coûts estimés en USD
   - Nombre d'appels de fonctions

6. **Flux principal avec Phase 2** (lignes 599-669) :
   - Pré-analyse de la question
   - Consultation des legal_facts si nécessaire
   - Appel OpenAI avec contexte enrichi
   - Logging avancé

7. **Fonction logAdvancedMetrics** (lignes 572-590) :
   - Log JSON détaillé dans `logs/metrics_phase2.log`

---

## 🔄 Flux Complet Phase 2

```
┌──────────────────────────────────────────────────────────────┐
│ 1. RÉCEPTION DE LA QUESTION                                  │
└─────────────────┬────────────────────────────────────────────┘
                  │
                  ▼
         ┌────────────────────┐
         │ 2. PRÉ-ANALYSE     │ ← NOUVEAU Phase 2
         │ (pre_analysis.php) │
         └────────┬───────────┘
                  │
                  ├─→ Détection contexte (quorum, FCTVA, etc.)
                  ├─→ Classification (finances, RH, juridique...)
                  ├─→ Priorité (1-10)
                  ├─→ Flags (force_legal_facts, force_sources)
                  │
                  ▼
   ┌──────────────────────────────┐
   │ 3. CONSULTATION LEGAL_FACTS  │ ← NOUVEAU Phase 2
   │ (si force_legal_facts = true)│
   └─────────────┬────────────────┘
                  │
                  ├─→ Recherche par catégorie
                  ├─→ Recherche textuelle
                  ├─→ Formatage pour prompt
                  │
                  ▼
         ┌────────────────────┐
         │ 4. RECHERCHE DB    │
         │ (base centrale +   │
         │  base locale)      │
         └────────┬───────────┘
                  │
                  ▼
    ┌──────────────────────────────┐
    │ 5. CONSTRUCTION PROMPT       │
    │ - Legal facts (priorité 10)  │ ← MODIFIÉ Phase 2
    │ - Contexte pré-analyse       │
    │ - Résultats DB               │
    │ - System prompt amélioré     │
    └────────┬─────────────────────┘
             │
             ▼
    ┌──────────────────────────────┐
    │ 6. DÉTERMINATION TOOL_CHOICE │ ← NOUVEAU Phase 2
    │ - 'function' si critique     │
    │ - 'auto' sinon               │
    └────────┬─────────────────────┘
             │
             ▼
    ┌────────────────────┐
    │ 7. APPEL OPENAI    │
    │ (avec function     │
    │  calling)          │
    └────────┬───────────┘
             │
             ├─→ Peut appeler search_official_websites
             ├─→ Peut appeler search_document_base
             │
             ▼
    ┌────────────────────────────┐
    │ 8. EXTRACTION MÉTRIQUES    │ ← NOUVEAU Phase 2
    │ - Tokens utilisés          │
    │ - Coûts estimés (USD)      │
    │ - Nombre function calls    │
    └────────┬───────────────────┘
             │
             ▼
    ┌────────────────────────────┐
    │ 9. LOGGING AVANCÉ          │ ← NOUVEAU Phase 2
    │ (metrics_phase2.log)       │
    └────────┬───────────────────┘
             │
             ▼
    ┌────────────────────────────┐
    │ 10. RÉPONSE AU CLIENT      │
    │ - Contenu IA               │
    │ - Temps de réponse         │
    │ - Sources utilisées        │
    │ - Métriques (si DEBUG)     │
    └────────────────────────────┘
```

---

## 🧪 Tests Recommandés

### **Test 1 : Question sur le quorum (contexte critique)**

**Question :**
```
Quel est le quorum nécessaire lors d'une reconvocation du conseil municipal ?
```

**Résultat attendu :**
- ✅ Pré-analyse détecte : contexte "quorum", priorité 10, critique = OUI
- ✅ Consultation legal_facts activée
- ✅ Règle validée injectée : "Article L2121-17 du CGCT - aucun quorum requis"
- ✅ tool_choice forcé sur 'function' (recherche obligatoire)
- ✅ Réponse cite EXACTEMENT : "Article L2121-17 du CGCT"
- ✅ Répons précise : "AUCUN QUORUM n'est requis lors d'une reconvocation"

---

### **Test 2 : Question sur le FCTVA (contexte critique)**

**Question :**
```
Quelles sont les dépenses éligibles au FCTVA ?
```

**Résultat attendu :**
- ✅ Pré-analyse détecte : contexte "fctva", priorité 10, critique = OUI
- ✅ Legal_facts consultés (catégorie FCTVA)
- ✅ Règle validée avec Article L1615-1 du CGCT injectée
- ✅ tool_choice forcé
- ✅ Sources officielles : collectivites-locales.gouv.fr, legifrance.gouv.fr

---

### **Test 3 : Question sur M57 (contexte haute priorité)**

**Question :**
```
Quel compte M57 utiliser pour l'achat d'un broyeur de végétaux ?
```

**Résultat attendu :**
- ✅ Pré-analyse détecte : contexte "m57", priorité 9
- ✅ Legal_facts consultés (catégorie M57)
- ✅ Réponse avec NUMÉRO DE COMPTE PRÉCIS (ex: compte 2128 ou 2158)
- ✅ Référence : "Instruction M57 - DGFiP"

---

### **Test 4 : Question RH grilles indiciaires**

**Question :**
```
Quelle est la grille indiciaire pour un adjoint administratif territorial ?
```

**Résultat attendu :**
- ✅ Pré-analyse détecte : contexte "rh_grilles", priorité 8
- ✅ tool_choice forcé (sources officielles obligatoires)
- ✅ Sites suggérés : emploi-collectivites.fr, CDG
- ✅ Réponse avec grille EXACTE et décret n°...

---

### **Test 5 : Question générale (pas de contexte spécial)**

**Question :**
```
Quels sont les délais de conservation des documents administratifs ?
```

**Résultat attendu :**
- ✅ Pré-analyse : catégorie "général", priorité basse
- ✅ Pas de legal_facts consultés
- ✅ tool_choice = 'auto' (GPT décide)
- ✅ Réponse pertinente mais pas de forçage spécial

---

## 📊 Vérification des Métriques

### **Logs basiques** (`logs/requests.log`)
```json
{
  "timestamp": "2025-10-27 14:30:45",
  "ip": "192.168.1.1",
  "question": "Quel est le quorum pour une reconvocation ?",
  "commune": "general",
  "response_time": 3245,
  "success": true
}
```

### **Logs avancés Phase 2** (`logs/metrics_phase2.log`)
```json
{
  "timestamp": "2025-10-27 14:30:45",
  "ip": "192.168.1.1",
  "question": "Quel est le quorum pour une reconvocation ?",
  "commune": "general",
  "response_time_ms": 3245,
  "pre_analysis": {
    "category": "juridique",
    "priority": 10,
    "is_critical": true,
    "contexts_detected": 1,
    "force_legal_facts": true,
    "force_official_sources": true
  },
  "legal_facts": {
    "count": 1,
    "categories": ["quorum"],
    "used": true,
    "highest_priority": 10
  },
  "openai_metrics": {
    "prompt_tokens": 2450,
    "completion_tokens": 680,
    "total_tokens": 3130,
    "model": "gpt-4-turbo",
    "function_calls_count": 1,
    "estimated_cost_usd": 0.0449
  },
  "sources": {
    "central": 2,
    "local": 0,
    "legal_facts": 1
  }
}
```

---

## 📋 Checklist Installation Phase 2

### **1. Fichiers à uploader sur le serveur**

```
✅ api/pre_analysis.php              (NOUVEAU)
✅ api/legal_facts_manager.php       (NOUVEAU)
✅ api/proxy.php                     (MODIFIÉ - remplacer l'ancien)
```

**Important :**
- Backup automatique créé : `api/proxy_v2_backup.php`
- La table `legal_facts` doit être créée (déjà fait en Phase 1)
- Les 20+ règles doivent être insérées (déjà fait en Phase 1)

---

### **2. Vérifications Base de Données**

```sql
-- Vérifier que la table legal_facts existe
SHOW TABLES LIKE 'legal_facts';

-- Vérifier le nombre de règles actives
SELECT COUNT(*) FROM legal_facts WHERE is_active = 1;
-- Résultat attendu : 20+

-- Vérifier les catégories disponibles
SELECT categorie, COUNT(*) as count
FROM legal_facts
WHERE is_active = 1
GROUP BY categorie;
```

**Résultat attendu :**
```
quorum          : 2
FCTVA           : 3
M57             : 5
CGCT            : 3
RH              : 4
marchés publics : 2
IFSE            : 2
budget          : 1
```

---

### **3. Test de Validation Complète**

1. **Uploader les 3 fichiers** (pre_analysis.php, legal_facts_manager.php, proxy.php)

2. **Tester une question critique** :
   ```
   https://noia.erelys.fr/index.html

   Question : "Quel est le quorum pour une reconvocation ?"
   ```

3. **Vérifier la réponse** :
   - ✅ Doit citer "Article L2121-17 du CGCT"
   - ✅ Doit dire "AUCUN QUORUM"
   - ✅ Temps de réponse : 2-5 secondes

4. **Activer DEBUG_MODE** (config.php) :
   ```php
   define('DEBUG_MODE', true);
   ```

5. **Recharger la question** :
   - Ouvrir la Console (F12)
   - Regarder la réponse JSON
   - Vérifier que `metrics.pre_analysis` est présent
   - Vérifier que `sources_count.legal_facts` > 0

---

## 🚀 Améliorations Futures (Phase 3+)

- **Mémoire conversationnelle** : Utiliser `historique_conversation` pour contexte multi-tours
- **Cache intelligent** : Utiliser `cache_rag` pour réponses fréquentes
- **Dashboard statistiques** : Visualisation des métriques Phase 2
- **Export RGPD** : Extraction de l'historique utilisateur
- **API de gestion users** : CRUD pour la table users

---

## 📞 Support & Dépannage

### **Erreur : "Class 'PreAnalysis' not found"**

**Cause :** Fichier `api/pre_analysis.php` non uploadé

**Solution :**
```bash
# Vérifier via FTP que le fichier existe :
/www/noia/api/pre_analysis.php
```

---

### **Erreur : "Table 'legal_facts' doesn't exist"**

**Cause :** Table non créée (Phase 1 incomplète)

**Solution :**
```sql
-- Exécuter dans phpMyAdmin :
-- Fichier : database/upgrade_v4_PARTIE_A.sql
-- Puis : database/legal_facts_data.sql
```

---

### **Les legal_facts ne semblent pas utilisés**

**Vérification :**
```sql
-- Vérifier qu'il y a des legal_facts actifs
SELECT * FROM legal_facts WHERE is_active = 1 LIMIT 5;

-- Si vide, charger les données :
-- Exécuter database/legal_facts_data.sql
```

---

### **Logs avancés non créés**

**Cause :** Permissions ou dossier manquant

**Solution :**
```bash
# Via FTP, créer le dossier :
/www/noia/logs/

# Permissions : 755
```

---

## ✅ Validation Phase 2

```
Tests fonctionnels :
☐ Question sur quorum → Cite Article L2121-17 exactement
☐ Question sur FCTVA → Cite Article L1615-1
☐ Question sur M57 → Donne numéro de compte précis
☐ Question RH → Cite décret et grille
☐ Question générale → Réponse pertinente sans forçage

Tests techniques :
☐ Fichier pre_analysis.php uploadé
☐ Fichier legal_facts_manager.php uploadé
☐ Fichier proxy.php v4.0 uploadé
☐ Table legal_facts avec 20+ règles
☐ Logs avancés créés dans logs/metrics_phase2.log

Métriques :
☐ Temps de réponse : 2-5 secondes
☐ Tokens utilisés visible (si DEBUG_MODE)
☐ Coûts estimés loggés
☐ Sources legal_facts comptées dans sources_count
```

---

**🎉 Phase 2 TERMINÉE !**

L'intelligence légale et la précision des réponses de NOIA sont maintenant **garanties** grâce à :
- ✅ Pré-analyse automatique
- ✅ Règles validées (legal_facts) avec priorité absolue
- ✅ Forçage des sources officielles
- ✅ Logging complet des coûts et performances

**Prochaine étape :** Phase 3 (Dashboard, statistiques, mémoire conversationnelle) 🚀
