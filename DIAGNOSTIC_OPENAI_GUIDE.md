# 🔍 Diagnostic OpenAI - Guide d'Utilisation

## 🎯 Objectif

Identifier exactement pourquoi OpenAI retourne "Réponse OpenAI invalide".

---

## 📋 ÉTAPE 1 : Upload du Fichier Diagnostic

### **Via cPanel File Manager :**

1. **Accéder à cPanel** → File Manager
2. **Naviguer vers** : `/www/noia/`
3. **Créer un nouveau fichier** : `diagnostic_openai.php`
4. **Ouvrir le fichier** `COPIER_diagnostic_openai.txt` (dans votre dépôt Git)
5. **Sélectionner TOUT** (Ctrl+A)
6. **Copier** (Ctrl+C)
7. **Retour dans cPanel** → **Éditer** `diagnostic_openai.php`
8. **Coller** (Ctrl+V)
9. **Save Changes**
10. **Permissions** : 644

---

## 🧪 ÉTAPE 2 : Exécuter le Diagnostic

1. **Ouvrir dans le navigateur :**
   ```
   https://noia.erelys.fr/diagnostic_openai.php
   ```

2. **Lire les résultats** des 4 tests :

### **TEST 1 : Clé API OpenAI**
- ✅ Si vert = Clé API configurée
- ❌ Si rouge = **PROBLÈME : Clé API invalide**

### **TEST 2 : Appel Simple OpenAI**
- ✅ Si vert = OpenAI fonctionne, clé valide
- ❌ Si rouge avec code 401 = **PROBLÈME : Clé API invalide**
- ❌ Si rouge avec code 429 = **PROBLÈME : Quota dépassé**

### **TEST 3 : Appel avec Function Calling**
- ✅ Si "content : PRÉSENT" = OpenAI retourne du contenu
- ⚠️ Si "content : ABSENT" mais "tool_calls : PRÉSENT" = **PROBLÈME IDENTIFIÉ**

### **TEST 4 : Configuration**
- Vérifier que les paramètres sont corrects

---

## 🎯 Interprétation des Résultats

### **Scénario A : TEST 2 = ❌ (Code 401)**

**PROBLÈME :** Clé API OpenAI invalide

**SOLUTION :**
1. Aller sur : https://platform.openai.com/api-keys
2. Créer une nouvelle clé API
3. Modifier `config/config.php` :
   ```php
   define('OPENAI_API_KEY', 'sk-proj-VOTRE-VRAIE-CLE-ICI');
   ```
4. Sauvegarder
5. Retester

---

### **Scénario B : TEST 2 = ❌ (Code 429)**

**PROBLÈME :** Quota OpenAI dépassé ou compte sans crédit

**SOLUTION :**
1. Aller sur : https://platform.openai.com/usage
2. Vérifier le crédit disponible
3. Ajouter du crédit si nécessaire
4. Retester

---

### **Scénario C : TEST 2 = ✅ mais TEST 3 = "PAS DE CONTENU"**

**PROBLÈME :** OpenAI retourne seulement `tool_calls` sans `content`

**EXPLICATION :**
- Quand on utilise `tool_choice`, GPT peut décider d'appeler une fonction AVANT de répondre
- Dans ce cas, il retourne seulement `tool_calls` dans la réponse
- Il faut alors :
  1. Exécuter la fonction demandée
  2. Renvoyer le résultat à OpenAI
  3. Obtenir la réponse finale

**SOLUTION :** Modifier `proxy.php` pour gérer correctement les `tool_calls`

Je vais créer un patch pour proxy.php si c'est ce scénario.

---

### **Scénario D : TEST 2 = ✅ et TEST 3 = ✅**

**PROBLÈME :** OpenAI fonctionne, le problème est ailleurs

**SOLUTION :** Vérifier :
- Les permissions des fichiers (644)
- La connexion MySQL
- Les logs d'erreur PHP

---

## 📞 Que Faire Après le Diagnostic ?

**M'envoyer les résultats des 4 tests :**

```
TEST 1 : [✅ ou ❌]
TEST 2 : [✅ ou ❌] Code HTTP : [...]
TEST 3 : content [PRÉSENT ou ABSENT] / tool_calls [PRÉSENT ou ABSENT]
TEST 4 : [Configuration affichée]
```

**Avec ces infos, je vais :**
1. Identifier le problème exact
2. Créer le patch approprié
3. Vous guider pour le fix final

---

## ⚠️ IMPORTANT : Sécurité

**Après le diagnostic, SUPPRIMER immédiatement le fichier :**

1. cPanel → File Manager
2. Naviguer vers `/www/noia/`
3. Clic droit sur `diagnostic_openai.php`
4. **Delete**

Ce fichier contient des informations sensibles sur votre configuration.

---

## 🚀 Prochaine Étape

**Vous :** Exécuter le diagnostic et m'envoyer les résultats

**Moi :** Créer le patch exact pour résoudre le problème
