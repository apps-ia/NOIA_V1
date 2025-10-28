# 📋 GUIDE Cpanel : Copier-Coller des Fichiers Phase 2

## 🎯 Vous allez créer 3 fichiers via cPanel

---

## 📍 ÉTAPE 1 : Accéder à cPanel File Manager

1. **Aller sur votre espace client OVH**
2. **Hébergements** → Votre hébergement
3. **FTP** ou **File Manager** → Cliquer sur "Gestionnaire de fichiers"
4. **Naviguer vers** : `/www/noia/api/`

---

## 🔄 ÉTAPE 2 : Backup de l'ancien proxy.php

1. **Trouver le fichier `proxy.php`** dans `/www/noia/api/`
2. **Clic droit** → **Rename/Renommer**
3. **Nouveau nom** : `proxy_BACKUP_V2.php`
4. ✅ **Sauvegarde effectuée**

---

## 📝 ÉTAPE 3 : Créer les 3 nouveaux fichiers

### **FICHIER 1 : pre_analysis.php**

1. Dans `/www/noia/api/`, cliquer sur **"New File"** ou **"+ File"**
2. **Nom** : `pre_analysis.php`
3. **Créer**
4. **Clic droit sur le fichier** → **Edit** ou **Code Editor**
5. **Supprimer tout** le contenu (si vide, c'est normal)
6. **Ouvrir le fichier** : `COPIER_pre_analysis.txt` (dans votre dépôt Git)
7. **Sélectionner TOUT** (Ctrl+A)
8. **Copier** (Ctrl+C)
9. **Retour dans cPanel** → **Coller** (Ctrl+V)
10. **Save Changes / Enregistrer**

---

### **FICHIER 2 : legal_facts_manager.php**

1. Dans `/www/noia/api/`, cliquer sur **"New File"**
2. **Nom** : `legal_facts_manager.php`
3. **Créer**
4. **Clic droit** → **Edit**
5. **Ouvrir le fichier** : `COPIER_legal_facts_manager.txt` (dans votre dépôt Git)
6. **Sélectionner TOUT** (Ctrl+A)
7. **Copier** (Ctrl+C)
8. **Retour dans cPanel** → **Coller** (Ctrl+V)
9. **Save Changes / Enregistrer**

---

### **FICHIER 3 : proxy.php**

1. Dans `/www/noia/api/`, cliquer sur **"New File"**
2. **Nom** : `proxy.php`
3. **Créer**
4. **Clic droit** → **Edit**
5. **Ouvrir le fichier** : `COPIER_proxy.txt` (dans votre dépôt Git)
6. **Sélectionner TOUT** (Ctrl+A)
7. **Copier** (Ctrl+C)
8. **Retour dans cPanel** → **Coller** (Ctrl+V)
9. **Save Changes / Enregistrer**

---

## ✅ ÉTAPE 4 : Vérifier les permissions

Pour chaque fichier (pre_analysis.php, legal_facts_manager.php, proxy.php) :

1. **Clic droit** → **Change Permissions** ou **Permissions**
2. **Valeur** : **644**
   - Owner: Read + Write
   - Group: Read only
   - World: Read only
3. **Apply**

---

## 🧪 ÉTAPE 5 : Test de Validation

### **Test 1 : API accessible ?**

Ouvrir dans le navigateur :
```
https://noia.erelys.fr/api/proxy.php
```

**Résultat attendu :**
```json
{"error":"Méthode non autorisée. Utilisez POST."}
```

✅ **Si vous voyez cette erreur = C'EST BON !**

---

### **Test 2 : Mode démo disparu ?**

1. Aller sur : `https://noia.erelys.fr/`
2. Se connecter
3. Poser : "Quel est le quorum pour une reconvocation ?"

**Résultat attendu :**
- ❌ **PLUS de message "Mode démonstration"**
- ✅ **Réponse structurée avec 1️⃣ 2️⃣ 3️⃣ 4️⃣**
- ✅ **Article L2121-17 du CGCT mentionné**

---

## 📦 Où Trouver les Fichiers COPIER_*.txt ?

**Option 1 : Depuis votre dépôt Git**

Les fichiers sont dans votre dépôt (branche `claude/remove-make-interface-011CUTxrLten4c6qKf8YMNek`) :

```
COPIER_pre_analysis.txt
COPIER_legal_facts_manager.txt
COPIER_proxy.txt
```

**Option 2 : Je vous les envoie directement**

Si vous ne trouvez pas les fichiers dans Git, dites-moi et je vous donne le contenu complet ici (en plusieurs messages).

---

## 🚨 Si Erreur 500 Après Upload

**Cause possible :** Erreur de copier-coller (caractères manquants)

**Solution :**
1. Activer DEBUG dans `config/config.php` :
   ```php
   define('DEBUG_MODE', true);
   ```
2. Recharger : `https://noia.erelys.fr/api/proxy.php`
3. Lire l'erreur affichée
4. Me la transmettre

---

## ✅ Checklist Complète

```
cPanel File Manager :
☐ Accès à cPanel réussi
☐ Navigation vers /www/noia/api/ OK
☐ Backup de l'ancien proxy.php → proxy_BACKUP_V2.php

Création fichiers :
☐ pre_analysis.php créé et contenu collé
☐ legal_facts_manager.php créé et contenu collé
☐ proxy.php créé et contenu collé
☐ Permissions 644 pour les 3 fichiers

Validation :
☐ Test https://noia.erelys.fr/api/proxy.php → erreur 405
☐ Test question sur NOIA → Plus de "Mode démonstration"
☐ Réponse structurée avec 1️⃣ 2️⃣ 3️⃣ 4️⃣
```

---

## 📞 Besoin d'Aide ?

**Où bloquez-vous ?**

- ❓ Accès cPanel ?
- ❓ Trouver les fichiers COPIER_*.txt ?
- ❓ Erreur après upload ?
- ❓ Mode démo toujours actif ?

**Dites-moi et je vous aide immédiatement !**
