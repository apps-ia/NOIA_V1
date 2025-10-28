# 🎯 Plan d'Action pour Finaliser NOIA

## ✅ Objectif Final

**NOIA doit fonctionner comme votre GPT personnalisé avec des réponses de qualité professionnelle identiques.**

---

## 📋 Actions à Réaliser (Par Priorité)

### **🚨 ACTION 1 : Upload des Fichiers Phase 2 (URGENT - 10 min)**

**Statut :** ❌ Bloquant - NOIA est en mode démo

**À faire :**
1. Télécharger 3 fichiers depuis Git (branche `claude/remove-make-interface-011CUTxrLten4c6qKf8YMNek`) :
   - `api/pre_analysis.php`
   - `api/legal_facts_manager.php`
   - `api/proxy.php` (version 4.0.0)

2. Connexion FTP (FileZilla) :
   - Hôte : `ftp.cluster0XX.hosting.ovh.net`
   - Login/Password : Vos identifiants OVH

3. Naviguer vers : `/www/noia/api/`

4. Backup : Renommer l'ancien `proxy.php` → `proxy_BACKUP.php`

5. Upload des 3 fichiers

6. Permissions : 644 pour chaque fichier

**Guide complet :** `UPLOAD_FICHIERS_GUIDE.md`

**Test de validation :**
```
https://noia.erelys.fr/api/proxy.php
→ Doit afficher : {"error":"Méthode non autorisée. Utilisez POST."}
✅ = API fonctionne
```

---

### **✅ ACTION 2 : Test de Qualité des Réponses (15 min)**

**Statut :** ⏳ Après ACTION 1

**À faire :**
1. Aller sur : `https://noia.erelys.fr/`
2. Se connecter
3. Poser ces 5 questions de test :

#### **Test 1 : Grille RH**
```
Quelle est la grille indiciaire pour un adjoint administratif territorial ?
```

**Attendu :**
- ✅ 1️⃣ 2️⃣ 3️⃣ 4️⃣ structure présente
- ✅ Décret n° 85-1148 et n° 2016-604 cités
- ✅ Valeur du point : 4,92302 €
- ✅ **TOUS les échelons** de chaque grade (pas de "...")
- ✅ Indices bruts + majorés + salaires
- ✅ Longueur : 1000-1500 mots

#### **Test 2 : Quorum**
```
Quel est le quorum nécessaire lors d'une reconvocation du conseil municipal ?
```

**Attendu :**
- ✅ Article L2121-17 du CGCT
- ✅ "**AUCUN QUORUM** requis" mentionné clairement

#### **Test 3 : FCTVA**
```
Quelles sont les dépenses éligibles au FCTVA ?
```

**Attendu :**
- ✅ Article L1615-1 du CGCT
- ✅ Seuil 5 000 € HT (sauf voirie)

#### **Test 4 : M57**
```
Quel compte M57 utiliser pour l'achat d'un broyeur de végétaux ?
```

**Attendu :**
- ✅ Numéro de compte précis (2128 ou 2158)
- ✅ Instruction M57 - DGFiP

#### **Test 5 : Délibération**
```
Quels sont les délais de convocation pour un conseil municipal ?
```

**Attendu :**
- ✅ 5 jours francs (3 en urgence)
- ✅ Article L2121-11 du CGCT

---

### **🎨 ACTION 3 : Comparaison avec Votre GPT (20 min)**

**Statut :** ⏳ Après ACTION 2

**À faire :**

1. **Poser les mêmes questions à votre GPT personnalisé**

2. **Comparer les réponses :**
   - Longueur (NOIA vs GPT)
   - Structure (sections, numérotation)
   - Niveau de détail (complet vs résumé)
   - Style (formel vs conversationnel)
   - Sources citées

3. **M'envoyer les résultats :**
   - "NOIA est plus/moins détaillé que mon GPT"
   - "NOIA manque de X comparé à mon GPT"
   - "Mon GPT fait Y que NOIA ne fait pas"

**Avec cette comparaison, je pourrai ajuster le prompt exactement comme votre GPT.**

---

### **⚙️ ACTION 4 : Optimisation des Paramètres (5 min)**

**Statut :** ⏳ Si les réponses ne sont pas assez détaillées

**À faire :**

Dans `config/config.php`, modifier :

```php
// Si réponses trop courtes :
define('OPENAI_MAX_TOKENS', 3000);  // Au lieu de 2500

// Si réponses pas assez précises :
define('OPENAI_TEMPERATURE', 0.3);  // Déjà optimal, ne pas changer

// Si trop lent :
define('OPENAI_MODEL', 'gpt-4o');  // Au lieu de 'gpt-4-turbo'
```

**Guide complet :** `OPTIMISATION_CONFIG.md`

---

### **🔍 ACTION 5 : Vérification Base de Données (5 min)**

**Statut :** ⏳ Si les réponses critiques manquent de précision

**À faire :**

Dans phpMyAdmin :

```sql
-- Vérifier la table legal_facts
SELECT COUNT(*) FROM legal_facts WHERE is_active = 1;
-- Attendu : 20+

-- Vérifier les catégories
SELECT categorie, COUNT(*) as count
FROM legal_facts
WHERE is_active = 1
GROUP BY categorie;

-- Attendu :
-- quorum : 2+
-- FCTVA : 3+
-- M57 : 5+
-- RH : 4+
-- marchés publics : 2+
```

**Si count < 20 :**
→ Exécuter : `database/legal_facts_data.sql`

---

### **📚 ACTION 6 : Documentation Utilisateur (30 min)**

**Statut :** ⏳ Optionnel (après validation)

**À faire :**

Créer un guide pour votre équipe avec :
- Exemples de questions types
- Exemples de réponses attendues
- Comment interpréter les références juridiques
- Quand utiliser NOIA vs contacter le CDG/trésorier

**Je peux créer ce guide si vous le souhaitez.**

---

## 🔧 Outils de Diagnostic

### **Outil 1 : test-api.php**

**Usage :**
1. Télécharger depuis Git : `test-api.php`
2. Uploader à la racine : `/www/noia/test-api.php`
3. Accéder à : `https://noia.erelys.fr/test-api.php`
4. Lire les diagnostics automatiques
5. **⚠️ SUPPRIMER après utilisation (sécurité)**

**Diagnostique :**
- ✅ Version PHP
- ✅ Connexion MySQL
- ✅ Table legal_facts
- ✅ Fichiers Phase 2 présents
- ✅ Classes PHP fonctionnelles

---

### **Outil 2 : Debug Mode**

**Usage :**

Dans `config/config.php` :
```php
define('DEBUG_MODE', true);  // Activer temporairement
```

**Puis :**
- Poser une question sur NOIA
- Ouvrir Console (F12) → Onglet Network
- Cliquer sur `proxy.php`
- Regarder la réponse JSON complète

**Voir :**
- `metrics.pre_analysis` : Contexte détecté
- `metrics.openai.total_tokens` : Tokens utilisés
- `sources_count.legal_facts` : Règles validées utilisées

**⚠️ Remettre `DEBUG_MODE: false` après test**

---

## 📊 Checklist Finale

```
Phase Critique (Bloquant) :
☐ Fichiers Phase 2 uploadés sur serveur
☐ API fonctionne (test https://noia.erelys.fr/api/proxy.php → erreur 405)
☐ Plus de message "Mode démonstration"

Tests de Qualité :
☐ Test grille RH → TOUS les échelons affichés
☐ Test quorum → Article L2121-17 + "AUCUN quorum"
☐ Test FCTVA → Article L1615-1 + seuil 5 000 €
☐ Test M57 → Numéro de compte précis
☐ Test délibération → 5 jours francs + Article L2121-11

Validation Finale :
☐ Longueur réponses : 1000-1500 mots
☐ Structure 4 sections (1️⃣ 2️⃣ 3️⃣ 4️⃣)
☐ Sources citées (emploi-collectivites.fr, Légifrance, etc.)
☐ Décrets avec numéros complets
☐ Tableaux complets (pas de "...")

Comparaison GPT :
☐ NOIA produit des réponses similaires à votre GPT personnalisé
☐ Niveau de détail équivalent
☐ Style professionnel équivalent

Base de Données :
☐ Table legal_facts : 20+ règles actives
☐ Table users : Compte admin existe
☐ Connexion MySQL fonctionne

Configuration :
☐ OPENAI_MAX_TOKENS : 2500-3000
☐ OPENAI_TEMPERATURE : 0.3
☐ ENABLE_WEB_SEARCH : true
☐ ENABLE_RAG : true
☐ DEBUG_MODE : false (en production)
```

---

## 🎯 Étapes Suivantes Immédiates

### **Maintenant (Vous) :**

1. **Upload des fichiers Phase 2** (10 min)
   - Suivre : `UPLOAD_FICHIERS_GUIDE.md`
   - Ou `ARRETER_MODE_DEMO.md`

2. **Test de validation** (5 min)
   - Aller sur NOIA
   - Poser : "Quel est le quorum pour une reconvocation ?"
   - Vérifier : Plus de "Mode démonstration"

3. **Me confirmer que ça fonctionne**
   - "✅ Upload OK, API fonctionne"
   - Ou "❌ Problème : [description]"

### **Ensuite (Moi) :**

4. **Je teste la qualité des réponses** avec vous

5. **Je compare avec votre GPT** (vous me donnez des exemples)

6. **J'ajuste le prompt** si nécessaire pour matcher votre GPT

7. **Validation finale** : NOIA = GPT personnalisé ✅

---

## 📞 Support en Temps Réel

**Vous bloquez à quelle étape ?**

- ❓ Téléchargement depuis Git ?
- ❓ Connexion FTP ?
- ❓ Upload des fichiers ?
- ❓ Tests ne fonctionnent pas ?
- ❓ Réponses pas assez détaillées ?

**➡️ Dites-moi et je vous guide en temps réel !**

---

## 🎉 Objectif Final

```
NOIA = GPT Personnalisé

✅ Réponses complètes (1000-1500 mots)
✅ Structure professionnelle (1️⃣ 2️⃣ 3️⃣ 4️⃣)
✅ Références juridiques exactes (décrets, articles CGCT)
✅ Grilles RH complètes (TOUS les échelons)
✅ Sources officielles (emploi-collectivites.fr, Légifrance)
✅ Legal facts intégrés (quorum, FCTVA, M57, etc.)
✅ Précision administrative (niveau Secrétaire Général)
```

**Temps estimé total : 1 heure (dont 10 min pour upload FTP)**

---

**🚀 Commencez par ACTION 1 (upload FTP) et tenez-moi au courant !**
