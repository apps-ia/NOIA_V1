# 📦 Fichiers Phase 2 à Télécharger

## ⬇️ Liens Directs vers les Fichiers

Téléchargez ces 3 fichiers depuis votre dépôt Git :

### **Branche à utiliser :**
```
claude/remove-make-interface-011CUTxrLten4c6qKf8YMNek
```

### **Fichiers à télécharger :**

1. **api/pre_analysis.php**
   - Chemin complet : `NOIA_V1/api/pre_analysis.php`
   - Rôle : Système de pré-analyse intelligente (détecte quorum, FCTVA, M57, etc.)

2. **api/legal_facts_manager.php**
   - Chemin complet : `NOIA_V1/api/legal_facts_manager.php`
   - Rôle : Gestionnaire de règles légales validées (priorité 10/10)

3. **api/proxy.php**
   - Chemin complet : `NOIA_V1/api/proxy.php`
   - Rôle : API principale avec Phase 2 intégrée (version 4.0.0)
   - ⚠️ **IMPORTANT** : Remplace l'ancien proxy.php

---

## 📤 Procédure d'Upload FTP Ultra-Simple

### **Étape 1 : Ouvrir FileZilla**

1. Lancer FileZilla
2. Entrer vos identifiants OVH :
   ```
   Hôte : ftp.cluster0XX.hosting.ovh.net
   Identifiant : [votre login FTP]
   Mot de passe : [votre password FTP]
   Port : 21
   ```
3. Cliquer sur "Connexion rapide"

---

### **Étape 2 : Naviguer vers le bon dossier**

**Côté serveur (fenêtre de droite) :**
```
/www/noia/api/
```

**Vous devriez voir :**
- proxy.php (ancien)
- web_search.php
- embeddings.php
- auth.php
- etc.

---

### **Étape 3 : Backup de l'ancien proxy.php**

**IMPORTANT - Faire un backup avant de remplacer :**

1. Clic droit sur `proxy.php`
2. Choisir "Renommer"
3. Nouveau nom : `proxy_V2_BACKUP.php`
4. ✅ Maintenant vous avez une sauvegarde

---

### **Étape 4 : Upload des 3 nouveaux fichiers**

**Côté local (fenêtre de gauche) :**
1. Naviguer vers le dossier où vous avez téléchargé les 3 fichiers
2. Sélectionner les 3 fichiers :
   - `pre_analysis.php`
   - `legal_facts_manager.php`
   - `proxy.php`

**Glisser-déposer vers la fenêtre de droite** (serveur)

**FileZilla va uploader les fichiers.**

---

### **Étape 5 : Vérifier les permissions**

Pour chaque fichier uploadé :
1. Clic droit → "Permissions"
2. Valeur numérique : **644**
3. ✅ OK

---

## ✅ Vérification Post-Upload

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

### **Test 2 : Plus de mode démo ?**

1. Aller sur : https://noia.erelys.fr/
2. Se connecter
3. Poser une question : "Quel est le quorum pour une reconvocation ?"

**Résultat attendu :**
- ❌ PLUS de message "Mode démonstration"
- ✅ Réponse structurée avec 1️⃣ 2️⃣ 3️⃣ 4️⃣
- ✅ Article L2121-17 du CGCT cité

---

## 🚨 Si ça ne fonctionne pas

### **Outil de diagnostic automatique**

1. Télécharger depuis Git : `test-api.php`
2. Uploader à la racine : `/www/noia/test-api.php`
3. Accéder à : https://noia.erelys.fr/test-api.php
4. Lire les diagnostics (vert = OK, rouge = problème)
5. **⚠️ SUPPRIMER test-api.php après utilisation**

---

## 📞 Besoin d'Aide ?

**Vous bloquez à quelle étape ?**
- Téléchargement depuis Git ?
- Connexion FTP ?
- Upload des fichiers ?
- Tests ?

**Dites-moi et je vous aide en temps réel !**
